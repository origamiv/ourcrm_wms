<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import GoodsTabs from "../Components/GoodsTabs.vue";
import { createEntitySync } from "../lib/entitySync";
import type { EntityRow } from "../lib/cache";

interface GoodRow extends EntityRow {
    name?: string | null;
    shortname?: string | null;
    code?: string | null;
    parent_code?: string | null;
    type_good?: string | number | null;
    type_unit?: string | number | null;
    parent_id?: string | number | null;
    level?: number | null;
    is_category?: number | null;
    barcodes?: string[] | null;
    articul?: string[] | null;
    goodcard_id?: string | number | null;
    status?: number | null;
    deleted_at?: string | null;
    src?: Record<string, unknown> | null;
}
interface CardRow extends EntityRow { good_id?: string | number | null; name?: string | null; status?: number | null }
interface KizRow extends EntityRow { good_id?: string | number | null; code?: string | null; kind_kiz_id?: string | number | null; client_id?: string | number | null; entranced_at?: string | null; leaving_at?: string | null; printed_at?: string | null }

const page = usePage<any>();
const scope = `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`;
const goodId = String(page.props.goodId ?? page.url.split("/").at(-2) ?? "");
const goods = createEntitySync<GoodRow>(scope, "goods");
const cards = createEntitySync<CardRow>(scope, "good_cards");
const kizes = createEntitySync<KizRow>(scope, "kizes");
const stores = [goods, cards, kizes];
const expanded = ref<Record<string, boolean>>({ characteristics: true });
const modal = ref<string | null>(null);
const notice = ref("");

const good = computed(() => goods.rows.value.find((row) => String(row.id) === goodId) ?? null);
const goodCards = computed(() => cards.rows.value.filter((row) => String(row.good_id) === goodId));
const demoCards = [
    { id: "demo-1", version: "0", name: "Основная карточка", status: 1, good_id: goodId, demo: true },
    { id: "demo-2", version: "0", name: "Карточка WB", status: 1, good_id: goodId, demo: true },
    { id: "demo-3", version: "0", name: "Карточка OZON", status: 1, good_id: goodId, demo: true },
];
const cardTiles = computed(() => goodCards.value.length ? goodCards.value : demoCards);
const goodKizes = computed(() => kizes.rows.value.filter((row) => String(row.good_id) === goodId));
const title = computed(() => good.value?.name || good.value?.shortname || `Товар №${goodId}`);
const barcodes = computed(() => Array.isArray(good.value?.barcodes) ? good.value!.barcodes! : []);
const articuls = computed(() => Array.isArray(good.value?.articul) ? good.value!.articul! : []);
const showOtherProperties = ref(false);
const demoProperties: Record<string, unknown> = { "Материал": "Пластик", "Страна производства": "Россия", "Температурный режим": "от +5 до +25 °C" };
function srcValue(keys: string[], fallback: string) {
    const source = good.value?.src ?? {};
    for (const key of keys) if (source[key] !== undefined && source[key] !== null && source[key] !== "") return String(source[key]);
    return fallback;
}
function flattenProperties(value: unknown, prefix = ""): { key: string; value: string }[] {
    if (value === null || value === undefined) return [];
    if (Array.isArray(value)) return [{ key: prefix, value: value.map((item) => typeof item === "object" ? JSON.stringify(item) : String(item)).join(", ") }];
    if (typeof value !== "object") return [{ key: prefix, value: String(value) }];
    return Object.entries(value as Record<string, unknown>).flatMap(([key, child]) => flattenProperties(child, prefix ? `${prefix}.${key}` : key));
}
const otherProperties = computed(() => {
    const rows = flattenProperties(good.value?.src);
    return rows.length ? rows : flattenProperties(demoProperties);
});
const characteristicsSummary = computed(() => `${cardTiles.value.length} карточки · ${otherProperties.value.length} свойств`);
const stockSummary = "2 склада · 15 ячеек · 2 клиента · 678 шт.";
const ordersSummary = "3 заказа · 2 клиента · 15 шт.";

const demoStock = [
    { warehouse: "Основной склад", cell: "A-01-04", client: "ООО «Ромашка»", quantity: 500 },
    { warehouse: "Резервный склад", cell: "B-02-11", client: "ИП Смирнов", quantity: 178 },
];
const warehouseFilter = ref("all");
const cellFilter = ref("all");
const clientFilter = ref("all");
const filteredStock = computed(() => demoStock.filter((item) =>
    (warehouseFilter.value === "all" || item.warehouse === warehouseFilter.value) &&
    (cellFilter.value === "all" || item.cell === cellFilter.value) &&
    (clientFilter.value === "all" || item.client === clientFilter.value),
));
const demoOrders = [
    { number: "Заказ #10482", client: "ООО «Ромашка»", status: "В сборке", quantity: 10, date: "10.09.2026" },
    { number: "Заказ #10496", client: "ИП Смирнов", status: "Ожидает отгрузки", quantity: 4, date: "09.09.2026" },
    { number: "Заказ #10508", client: "ООО «Ромашка»", status: "Новый", quantity: 1, date: "08.09.2026" },
];
const demoServices = [
    { name: "Комплектация и упаковка", status: "Активна", price: "450 ₽" },
    { name: "Маркировка Честный знак", status: "По запросу", price: "35 ₽" },
];
const demoMovements = [
    { date: "10.09.2026 14:32", action: "Приход", from: "Поставщик", to: "Основной склад / A-01-04", quantity: "+120" },
    { date: "09.09.2026 18:10", action: "Перемещение", from: "A-01-02", to: "A-01-04", quantity: "24" },
];
const categoryTrail = computed(() => {
    const byId = new Map(goods.rows.value.map((row) => [String(row.id), row]));
    const result: GoodRow[] = [];
    const seen = new Set<string>();
    let current = good.value;
    while (current && (current.parent_id != null || current.parent_code) && !seen.has(String(current.parent_id ?? current.parent_code))) {
        const parentKey = String(current.parent_id ?? current.parent_code);
        seen.add(parentKey);
        const parent = byId.get(parentKey) ?? goods.rows.value.find((row) => String(row.code) === String(current?.parent_code));
        if (!parent) break;
        result.unshift(parent);
        current = parent;
    }
    return result;
});

function toggle(key: string) {
    expanded.value[key] = !expanded.value[key];
    localStorage.setItem(`good-detail:${goodId}`, JSON.stringify(expanded.value));
}
function openAction(action: string) {
    modal.value = action;
    notice.value = "";
}
function closeModal() { modal.value = null; }
function confirmDelete() {
    notice.value = "Удаление будет подключено после добавления серверной операции.";
    modal.value = null;
}
function back() { router.visit("/goods/goods"); }
function openCard(card: CardRow) { router.visit(`/goods/good_cards/${encodeURIComponent(card.id)}/view`); }
function openCardTile(card: CardRow & { demo?: boolean }) { card.demo ? openAction("card-demo") : openCard(card); }
function addCard() { openAction("new-card"); }
function label(value: unknown, fallback = "—") { return value === null || value === undefined || value === "" ? fallback : String(value); }
async function copyValue(value: string) {
    try {
        await navigator.clipboard.writeText(value);
        notice.value = `Скопировано: ${value}`;
        window.setTimeout(() => { notice.value = ""; }, 1800);
    } catch {
        notice.value = "Не удалось скопировать значение";
    }
}

onMounted(async () => {
    try {
        const saved = JSON.parse(localStorage.getItem(`good-detail:${goodId}`) ?? "{}");
        if (saved && typeof saved === "object") expanded.value = { ...saved };
        expanded.value.characteristics = true;
    } catch { /* defaults */ }
    await Promise.all(stores.map((store) => store.start()));
});
onUnmounted(() => stores.forEach((store) => store.stop()));
</script>

<template>
    <Head :title="title" />
    <div class="good-detail-page">
        <div class="content-breadcrumb">Товары › Товары › {{ title }}</div>
        <GoodsTabs />
        <div class="good-detail-heading">
            <div>
                <button class="good-back" type="button" @click="back">← К товарам</button>
                <h1>{{ title }}</h1>
                <p class="good-subtitle">Код: {{ label(good?.code) }} · ID: {{ goodId }}</p>
            </div>
            <div class="good-detail-actions">
                <button type="button" class="secondary" @click="openAction('labels')">🏷 Печать этикеток</button>
                <button type="button" class="secondary" @click="openAction('movement')">↔ История движения</button>
                <button type="button" class="secondary" @click="openAction('move')">⇄ Перемещение товара</button>
                <button type="button" class="secondary" @click="openAction('transfer')">⇥ Передача клиенту</button>
                <button type="button" class="danger" @click="openAction('delete')">⌫ Удалить товар</button>
            </div>
        </div>
        <p v-if="notice" class="notice" role="status">{{ notice }}</p>

        <main class="good-detail-layout"><div class="good-detail-sections">
            <section class="good-detail-section" :class="{ open: expanded.about !== false }">
                <button class="good-section-toggle" type="button" @click="toggle('about')"><span>О товаре</span><small class="good-section-summary">{{ label(good?.level, '0') }} уровень · {{ barcodes.length }} ШК · {{ articuls.length }} артикулов</small><b>{{ expanded.about !== false ? '⌃' : '⌄' }}</b></button>
                <div v-if="expanded.about !== false" class="good-section-content good-about-grid">
                    <nav class="good-category-breadcrumbs good-category-under-heading" aria-label="Категория товара"><span>Категория</span><template v-if="categoryTrail.length" v-for="(item, index) in categoryTrail" :key="item.id"><b>›</b><a href="#" @click.prevent="router.visit(`/goods/goods/${item.id}/view`)">{{ label(item.name, item.shortname ?? undefined) }}</a></template><em v-else>Без категории</em></nav>
                    <div class="good-gallery"><div class="good-image-placeholder"><img src="/design/crm/goods.svg" alt="" /></div><div class="good-gallery-dots"><i class="active"></i><i></i><i></i></div></div>
                    <div class="good-summary"><div class="good-product-status"><span>Статус</span><strong class="good-status-badge" :class="good?.status === 1 ? 'active' : 'muted-status'">{{ good?.status === 1 ? 'Активен' : 'Не указан' }}</strong></div><div class="good-code-columns"><div><dt>Штрихкоды</dt><div v-if="barcodes.length" class="good-code-list"><div v-for="value in barcodes" :key="value" class="good-code-row"><button type="button" class="good-copy-button" title="Копировать" aria-label="Копировать штрихкод" @click="copyValue(String(value))"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8h10v12H8zM5 16H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></button><span>{{ value }}</span></div></div><p v-else class="good-empty-code">Нет данных</p></div><div><dt>Артикулы</dt><div v-if="articuls.length" class="good-code-list"><div v-for="value in articuls" :key="value" class="good-code-row"><button type="button" class="good-copy-button" title="Копировать" aria-label="Копировать артикул" @click="copyValue(String(value))"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8h10v12H8zM5 16H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></button><span>{{ value }}</span></div></div><p v-else class="good-empty-code">Нет данных</p></div></div></div>
                </div>
            </section>

            <section class="good-detail-section" :class="{ open: expanded.characteristics }">
                <button class="good-section-toggle" type="button" @click="toggle('characteristics')"><span>Характеристики</span><small class="good-section-summary">{{ characteristicsSummary }}</small><b>{{ expanded.characteristics ? '⌃' : '⌄' }}</b></button>
                <div v-if="expanded.characteristics" class="good-section-content good-characteristics-layout">
                    <div class="good-characteristics-main">
                        <div class="good-data-grid good-main-properties">
                            <div><span>Длина</span><strong>{{ srcValue(['length', 'длина'], '30 см') }}</strong></div>
                            <div><span>Ширина</span><strong>{{ srcValue(['width', 'ширина'], '20 см') }}</strong></div>
                            <div><span>Высота</span><strong>{{ srcValue(['height', 'высота'], '15 см') }}</strong></div>
                            <div><span>Вес</span><strong>{{ srcValue(['weight', 'вес'], '1,2 кг') }}</strong></div>
                            <div><span>Цвет</span><strong>{{ srcValue(['color', 'цвет'], 'Синий') }}</strong></div>
                            <button class="good-other-tile" type="button" @click="showOtherProperties = !showOtherProperties"><span>Остальные</span><b>{{ showOtherProperties ? '✓' : '›' }}</b></button>
                        </div>
                        <div class="good-cards-block"><div class="good-cards-heading"><strong>Карточки товара</strong><button type="button" class="good-card-add" aria-label="Добавить карточку товара" title="Добавить карточку товара" @click="addCard">+</button></div><div class="good-card-avatars"><button v-for="(card, index) in cardTiles" :key="card.id" type="button" class="good-card-avatar" :class="[`color-${(index % 6) + 1}`]" :aria-label="card.name || `Карточка №${card.id}`" :title="card.name || `Карточка №${card.id}`" @click="openCardTile(card)"><span>{{ (card.name || `К${index + 1}`).slice(0, 2).toUpperCase() }}</span></button></div></div>
                    </div>
                </div>
            </section>

            <section class="good-detail-section" :class="{ open: expanded.stock }"><button class="good-section-toggle" type="button" @click="toggle('stock')"><span>Склад</span><small class="good-section-summary">{{ stockSummary }}</small><b>{{ expanded.stock ? '⌃' : '⌄' }}</b></button><div v-if="expanded.stock" class="good-section-content"><div class="good-stock-filters"><label>Склад<select v-model="warehouseFilter"><option value="all">Все склады</option><option v-for="item in [...new Set(demoStock.map((row) => row.warehouse))]" :key="item" :value="item">{{ item }}</option></select></label><label>Ячейка<select v-model="cellFilter"><option value="all">Все ячейки</option><option v-for="item in [...new Set(demoStock.map((row) => row.cell))]" :key="item" :value="item">{{ item }}</option></select></label><label>Клиент<select v-model="clientFilter"><option value="all">Все клиенты</option><option v-for="item in [...new Set(demoStock.map((row) => row.client))]" :key="item" :value="item">{{ item }}</option></select></label></div><div class="good-mini-table"><div class="good-mini-row header"><span>Склад / ячейка</span><span>Клиент</span><span>Остаток</span></div><div v-for="item in filteredStock" :key="item.cell" class="good-mini-row"><span><strong>{{ item.warehouse }}</strong><small>{{ item.cell }}</small></span><span>{{ item.client }}</span><b>{{ item.quantity }} шт.</b></div><p v-if="!filteredStock.length" class="empty-state">По выбранным фильтрам остатков нет.</p></div></div></section>
            <section class="good-detail-section" :class="{ open: expanded.orders }"><button class="good-section-toggle" type="button" @click="toggle('orders')"><span>Заказы с этим товаром</span><small class="good-section-summary">{{ ordersSummary }}</small><b>{{ expanded.orders ? '⌃' : '⌄' }}</b></button><div v-if="expanded.orders" class="good-section-content"><div class="good-mini-table"><div class="good-mini-row header"><span>Заказ</span><span>Клиент / статус</span><span>Количество</span></div><div v-for="item in demoOrders" :key="item.number" class="good-mini-row"><span><strong>{{ item.number }}</strong><small>{{ item.date }}</small></span><span>{{ item.client }}<small>{{ item.status }}</small></span><b>{{ item.quantity }} шт.</b></div></div></div></section>
            <section class="good-detail-section" :class="{ open: expanded.services }"><button class="good-section-toggle" type="button" @click="toggle('services')"><span>ТЗ и услуги</span><small class="good-section-summary"><em class="good-summary-badge">ТЗ заполнено</em> · 23 услуги</small><b>{{ expanded.services ? '⌃' : '⌄' }}</b></button><div v-if="expanded.services" class="good-section-content"><div class="good-service-list"><div v-for="item in demoServices" :key="item.name"><strong>{{ item.name }}</strong><span>{{ item.status }}</span><b>{{ item.price }}</b></div></div></div></section>
            <section class="good-detail-section" :class="{ open: expanded.kizes }"><button class="good-section-toggle" type="button" @click="toggle('kizes')"><span>Коды маркировки</span><b>{{ expanded.kizes ? '⌃' : '⌄' }}</b></button><div v-if="expanded.kizes" class="good-section-content"><div v-if="goodKizes.length" class="good-mini-table"><div class="good-mini-row header"><span>Код</span><span>Клиент</span><span>Вход / выход</span></div><div v-for="item in goodKizes" :key="item.id" class="good-mini-row"><span><strong>{{ label(item.code) }}</strong><small>Вид: {{ label(item.kind_kiz_id) }}</small></span><span>{{ label(item.client_id) }}</span><span>{{ label(item.entranced_at) }}<small>{{ item.leaving_at ? ` / ${item.leaving_at}` : ' / на складе' }}</small></span></div></div><p v-else class="empty-state">Для этого товара пока нет кодов маркировки.</p></div></section>
        </div><aside class="good-properties-panel good-properties-sidebar"><div v-if="showOtherProperties" class="good-properties-filled"><div class="good-properties-heading"><strong>Свойства из src</strong><button type="button" aria-label="Закрыть свойства" title="Закрыть" @click="showOtherProperties = false">×</button></div><div class="good-properties-grid"><div v-for="property in otherProperties" :key="property.key"><span>{{ property.key }}</span><strong>{{ property.value }}</strong></div></div></div><div v-else class="good-properties-placeholder"><span>Свойства товара</span><small>Нажмите «Остальные» в секции «Характеристики»</small></div></aside></main>
    </div>

    <div v-if="modal" class="good-modal-backdrop" @click.self="closeModal"><div class="good-modal" role="dialog" aria-modal="true"><button class="good-modal-close" type="button" aria-label="Закрыть" @click="closeModal">×</button><h2>{{ modal === 'delete' ? 'Удалить товар?' : modal === 'new-card' ? 'Новая карточка товара' : modal === 'card-demo' ? 'Демонстрационная карточка' : 'Действие с товаром' }}</h2><p v-if="modal === 'delete'">Товар «{{ title }}» будет удалён из каталога. В прототипе операция не выполняется.</p><p v-else-if="modal === 'new-card'">Форма добавления карточки товара будет подключена после утверждения полей карточки.</p><p v-else-if="modal === 'card-demo'">Это демонстрационная карточка. Экран карточки товара будет доступен после её сохранения.</p><p v-else>Здесь откроется форма действия «{{ modal === 'labels' ? 'Печать этикеток' : modal === 'movement' ? 'История движения' : modal === 'move' ? 'Перемещение товара' : 'Передача другому клиенту' }}».</p><div class="good-modal-actions"><button type="button" class="secondary" @click="closeModal">Отмена</button><button v-if="modal === 'delete'" type="button" class="danger" @click="confirmDelete">Подтвердить</button><button v-else type="button" class="primary" @click="closeModal">Понятно</button></div></div></div>
</template>

<style scoped>
.good-product-status { display:flex; align-items:center; gap:10px; margin:4px 0 22px; color:#7c898e; font-size:12px; }.good-status-badge { display:inline-flex; align-items:center; border-radius:999px; padding:5px 11px; font-size:12px; font-weight:700; }.good-status-badge.active { background:#e1f4e7; color:#238348; }.good-status-badge.muted-status { background:#eef1f2; color:#718087; }.good-code-columns { display:grid; grid-template-columns:1fr 1fr; gap:30px; }.good-code-columns dt { display:block; color:#7c898e; font-size:12px; margin-bottom:8px; }.good-code-list { display:flex; flex-direction:column; gap:7px; }.good-code-row { display:flex; align-items:center; gap:8px; min-height:30px; font-weight:600; }.good-copy-button { width:25px; height:25px; padding:4px; border:1px solid #d7e2dc; border-radius:6px; background:#f4faf6; color:#238348; cursor:pointer; }.good-copy-button svg { width:100%; height:100%; }.good-empty-code { color:#879398; margin:0; font-size:13px; }
.good-summary-title { display:flex; justify-content:space-between; gap:22px; align-items:flex-start; }.good-category-breadcrumbs { display:flex; align-items:center; gap:7px; flex-wrap:wrap; justify-content:flex-end; color:#78858a; font-size:12px; max-width:58%; }.good-category-breadcrumbs a { color:#238348; text-decoration:none; }.good-category-breadcrumbs b { color:#a4afb2; }
.good-category-under-heading { grid-column:1 / -1; justify-content:flex-start; max-width:none; margin-bottom:2px; }
.good-detail-page { padding: 22px 28px 40px; min-height: calc(100vh - 40px); background: #f7f8fa; color: #172126; }
.good-detail-heading { display:flex; justify-content:space-between; gap:24px; align-items:flex-start; margin:20px 0 18px; }
.good-detail-heading h1 { margin:8px 0 4px; font-size:30px; }.good-back { border:0; background:none; color:#238348; cursor:pointer; padding:0; font:inherit; }.good-subtitle,.muted { color:#728087; margin:0; }.good-detail-actions { display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-end; max-width:720px; }.secondary,.danger,.primary { border:1px solid #d7dee1; border-radius:8px; background:#fff; padding:9px 12px; cursor:pointer; }.danger { color:#be3a3a; border-color:#eccaca; }.primary { background:#238348; color:#fff; border-color:#238348; }
.good-detail-layout { display:grid; grid-template-columns:minmax(0,1fr) 390px; gap:20px; align-items:stretch; max-width:1600px; }.good-characteristics-layout { display:block; }.good-characteristics-main { min-width:0; }.good-main-properties { grid-template-columns:repeat(3,1fr); }.good-other-tile { display:flex; align-items:center; justify-content:space-between; width:100%; border:1px solid #b9dfc5; border-radius:8px; padding:12px; background:#eaf8ed; color:#238348; cursor:pointer; text-align:left; font:inherit; }.good-other-tile span { color:#238348; font-weight:700; }.good-other-tile b { font-size:18px; }.good-properties-panel { min-width:0; min-height:100%; border:1px solid #dfe7e8; border-radius:10px; background:#fbfcfc; overflow:hidden; position:sticky; top:20px; align-self:stretch; }.good-properties-filled { height:100%; display:flex; flex-direction:column; }.good-properties-placeholder { min-height:320px; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; color:#7c898e; text-align:center; padding:25px; }.good-properties-placeholder span { font-weight:700; color:#536168; }.good-properties-placeholder small { max-width:220px; line-height:1.45; }.good-properties-heading { display:flex; justify-content:space-between; align-items:center; padding:13px 15px; border-bottom:1px solid #e6ecec; }.good-properties-heading button { border:0; background:none; color:#748187; font-size:22px; cursor:pointer; }.good-properties-grid { flex:1; min-height:0; overflow:auto; padding:8px 15px 14px; }.good-properties-grid div { display:grid; grid-template-columns:minmax(110px,.8fr) minmax(0,1.2fr); gap:12px; padding:10px 0; border-bottom:1px solid #edf1f1; }.good-properties-grid span { color:#7c898e; font-size:12px; overflow-wrap:anywhere; }.good-properties-grid strong { overflow-wrap:anywhere; font-size:13px; }
.good-cards-block { margin-top:20px; border-top:1px solid #edf0f1; padding-top:16px; }.good-cards-heading { display:flex; justify-content:space-between; align-items:center; }.good-card-add { width:30px; height:30px; border:1px solid #bcdac8; color:#238348; background:#f3faf5; border-radius:50%; font-size:22px; line-height:1; cursor:pointer; }.good-card-avatars { display:flex; flex-wrap:wrap; gap:12px; margin-top:12px; }.good-card-avatar { width:58px; height:58px; display:grid; place-items:center; border:0; border-radius:13px; padding:4px; cursor:pointer; text-align:center; transition:transform .15s ease, box-shadow .15s ease; box-shadow:0 3px 8px rgba(31,45,52,.14); }.good-card-avatar:hover { transform:translateY(-3px) scale(1.04); box-shadow:0 7px 15px rgba(31,45,52,.24); }.good-card-avatar span { width:100%; height:100%; display:grid; place-items:center; border-radius:9px; color:#fff; font-weight:800; font-size:14px; text-shadow:0 1px 2px rgba(0,0,0,.22); }.good-card-avatar.color-1 { background:linear-gradient(135deg,#ff6b6b,#ee3f88); }.good-card-avatar.color-1 span { background:linear-gradient(135deg,#ff8b6b,#f12f83); }.good-card-avatar.color-2 { background:linear-gradient(135deg,#20c997,#087f8c); }.good-card-avatar.color-2 span { background:linear-gradient(135deg,#32d6a2,#078595); }.good-card-avatar.color-3 { background:linear-gradient(135deg,#845ef7,#4263eb); }.good-card-avatar.color-3 span { background:linear-gradient(135deg,#9775fa,#3b5bdb); }.good-card-avatar.color-4 { background:linear-gradient(135deg,#fcc419,#f08c00); }.good-card-avatar.color-4 span { background:linear-gradient(135deg,#ffd43b,#f08c00); }.good-card-avatar.color-5 { background:linear-gradient(135deg,#f06595,#cc5de8); }.good-card-avatar.color-5 span { background:linear-gradient(135deg,#f783ac,#be4bdb); }.good-card-avatar.color-6 { background:linear-gradient(135deg,#15aabf,#1971c2); }.good-card-avatar.color-6 span { background:linear-gradient(135deg,#22b8cf,#1864ab); }.good-card-empty { border:1px dashed #bcdac8; color:#238348; background:#f7fcf8; border-radius:8px; padding:10px 14px; cursor:pointer; }.good-stock-filters { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:15px; }.good-stock-filters label { color:#748187; font-size:12px; }.good-stock-filters select { display:block; width:100%; margin-top:5px; border:1px solid #d9e2e4; border-radius:7px; background:#fff; padding:9px 10px; color:#243338; }
.good-detail-sections { display:flex; flex-direction:column; gap:10px; max-width:1180px; }.good-detail-section { background:#fff; border:1px solid #e2e8ea; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(24,47,55,.03); }.good-section-toggle { width:100%; padding:17px 20px; border:0; background:#fff; display:flex; justify-content:space-between; align-items:center; font-size:17px; font-weight:650; cursor:pointer; text-align:left; }.good-section-toggle b { color:#238348; font-size:20px; }.good-summary-badge { display:inline-flex; padding:4px 8px; border-radius:999px; background:#e1f4e7; color:#238348; font-size:11px; font-style:normal; font-weight:700; }.good-section-summary { margin-left:auto; margin-right:16px; color:#7c898e; font-size:12px; font-weight:500; white-space:nowrap; }.good-section-content { padding:0 20px 20px; }.good-about-grid { display:grid; grid-template-columns:240px 1fr; gap:26px; }.good-gallery { text-align:center; }.good-image-placeholder { height:190px; border:1px solid #e3e9eb; border-radius:10px; background:#f5f8f8; display:grid; place-items:center; }.good-image-placeholder img { width:110px; height:110px; opacity:.55; }.good-gallery-dots { display:flex; justify-content:center; gap:6px; margin-top:10px; }.good-gallery-dots i { width:7px; height:7px; border-radius:50%; background:#cbd5d8; }.good-gallery-dots i.active { background:#238348; }.good-summary h2 { margin:4px 0 5px; }.good-summary dl { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-top:28px; }.good-summary dt,.good-data-grid span { color:#7c898e; font-size:12px; margin-bottom:5px; }.good-summary dd { margin:0; font-weight:600; }.good-data-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }.good-data-grid div { background:#f7f9f9; border-radius:8px; padding:12px; }.good-data-grid span,.good-data-grid strong { display:block; }.good-mini-table { border:1px solid #e4eaec; border-radius:8px; overflow:hidden; }.good-mini-row { display:grid; grid-template-columns:1.4fr 1fr .7fr; gap:15px; padding:12px 14px; border-top:1px solid #edf0f1; align-items:center; }.good-mini-row:first-child { border-top:0; }.good-mini-row.header { background:#f7f9f9; color:#758187; font-size:12px; }.good-mini-row small { display:block; color:#829096; font-size:12px; margin-top:3px; }.good-service-list > div { display:grid; grid-template-columns:1fr 150px 100px; padding:14px 0; border-top:1px solid #edf0f1; }.good-service-list > div:first-child { border-top:0; }.good-service-list span { color:#238348; }.empty-state { color:#7b888d; margin:0; }.good-modal-backdrop { position:fixed; inset:0; background:rgba(26,38,43,.38); display:grid; place-items:center; z-index:20; }.good-modal { width:min(460px,calc(100vw - 32px)); background:#fff; border-radius:14px; padding:26px; position:relative; box-shadow:0 20px 60px rgba(0,0,0,.2); }.good-modal h2 { margin:0 0 12px; }.good-modal p { color:#66757b; line-height:1.5; }.good-modal-close { position:absolute; top:10px; right:13px; border:0; background:none; font-size:25px; cursor:pointer; color:#728087; }.good-modal-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:24px; }
@media (max-width: 800px) { .good-detail-heading,.good-about-grid { display:block; }.good-detail-layout { display:block; }.good-properties-panel { margin-top:18px; min-height:260px; position:static; }.good-characteristics-layout { display:block; }.good-properties-panel { margin-top:18px; }.good-code-columns { grid-template-columns:1fr; gap:18px; }.good-detail-actions { justify-content:flex-start; margin-top:18px; }.good-section-summary { white-space:normal; text-align:right; }.good-summary { margin-top:20px; }.good-summary dl,.good-data-grid { grid-template-columns:1fr 1fr; }.good-main-properties { grid-template-columns:1fr 1fr; }.good-mini-row { grid-template-columns:1fr; gap:5px; }.good-service-list > div { grid-template-columns:1fr; gap:5px; } }
</style>
