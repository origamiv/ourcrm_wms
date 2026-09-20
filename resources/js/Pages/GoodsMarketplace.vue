<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import FulfillmentTabs from "../Components/FulfillmentTabs.vue";
import { createEntitySync } from "../lib/entitySync";
import type { EntityRow } from "../lib/cache";
import { http, HttpError } from "../lib/http";

interface MarketplaceRow extends EntityRow {
    webhook_id: string | number | null;
    marketplace: string | null;
    external_id: string | null;
    external_sku: string | null;
    offer_id: string | null;
    name: string | null;
    barcodes: string[] | null;
    status: number | null;
    good_id: string | number | null;
    match_type: string | null;
    synced_at: string | null;
    deleted_at: string | null;
}
interface GoodRow extends EntityRow { name: string | null; code: string | null; is_category: number | null; deleted_at: string | null; }
interface IntegrationWebhookRow extends EntityRow { name: string | null; deleted_at: string | null; }

const page = usePage<any>();
const scope = `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`;
const marketplaceStore = createEntitySync<MarketplaceRow>(scope, "goods_marketplace");
const goodStore = createEntitySync<GoodRow>(scope, "goods");
const webhookStore = createEntitySync<IntegrationWebhookRow>(scope, "integration_webhooks");
const query = ref("");
const integrationFilter = ref<string[]>([]);
const marketplaceFilter = ref<string[]>([]);
const matchFilter = ref<string[]>([]);
const currentPage = ref(1);
const pageSize = 25;
const expandedMobileRows = ref<Set<string>>(new Set());
const editingId = ref<string | null>(null);
const codeSearch = ref("");
const nameSearch = ref("");
const selectedGood = ref<GoodRow | null>(null);
const goodListOpen = ref(false);
const activeGoodIndex = ref(0);
const savingMatch = ref(false);
const matchError = ref("");

const marketplaceLabels: Record<string, string> = { wildberries: "WB", wb: "WB", ozon: "OZON", yandex_market: "Я.Маркет" };
const matchLabels: Record<string, string> = { auto: "Автоматически", pending: "Ожидает", created: "Создан", manual: "Вручную", failed: "Неуспешно", error: "Ошибка сопоставления", unmatched: "Не сопоставлен" };
const goodsById = computed(() => new Map(goodStore.rows.value.filter((row) => !row.deleted_at).map((row) => [String(row.id), row])));
const goodOptions = computed(() => {
    const code = codeSearch.value.trim().toLocaleLowerCase("ru");
    const name = nameSearch.value.trim().toLocaleLowerCase("ru");
    return goodStore.rows.value
        .filter((good) => !good.deleted_at)
        .filter((good) => !code || String(good.code ?? "").toLocaleLowerCase("ru").includes(code))
        .filter((good) => !name || String(good.name ?? "").toLocaleLowerCase("ru").includes(name))
        .sort((a, b) => {
            if (Number(a.is_category) !== Number(b.is_category)) return Number(a.is_category) - Number(b.is_category);
            return String(a.name ?? "").localeCompare(String(b.name ?? ""), "ru");
        });
});
const webhooksById = computed(() => new Map(webhookStore.rows.value.filter((row) => !row.deleted_at).map((row) => [String(row.id), row])));
const marketplaces = computed(() => [...new Set(marketplaceStore.rows.value.filter((row) => !row.deleted_at).map((row) => row.marketplace).filter(Boolean))]);
const integrations = computed(() => [...new Set(marketplaceStore.rows.value.filter((row) => !row.deleted_at && row.webhook_id !== null).map((row) => String(row.webhook_id)))].sort((a, b) => webhookNameById(a).localeCompare(webhookNameById(b), "ru")));
const matchOptions = [{ value: "auto", label: "Автоматически" }, { value: "pending", label: "Ожидает" }, { value: "created", label: "Создан" }, { value: "unmatched", label: "Не сопоставлен" }];
const filtered = computed(() => marketplaceStore.rows.value.filter((row) => {
    if (row.deleted_at) return false;
    if (integrationFilter.value.length && !integrationFilter.value.includes(String(row.webhook_id))) return false;
    if (marketplaceFilter.value.length && !marketplaceFilter.value.includes(String(row.marketplace))) return false;
    if (matchFilter.value.length && !matchFilter.value.includes(matchValue(row))) return false;
    const text = [row.name, row.external_id, row.external_sku, row.offer_id, webhookName(row), ...(row.barcodes ?? [])].join(" ").toLocaleLowerCase("ru");
    return text.includes(query.value.toLocaleLowerCase("ru"));
}).sort((a, b) => String(a.name ?? "").localeCompare(String(b.name ?? ""), "ru")));
const pages = computed(() => Math.max(1, Math.ceil(filtered.value.length / pageSize)));
const visible = computed(() => filtered.value.slice((currentPage.value - 1) * pageSize, currentPage.value * pageSize));
const matchedCount = computed(() => filtered.value.filter((row) => row.good_id).length);
watch([query, integrationFilter, marketplaceFilter, matchFilter], () => { currentPage.value = 1; });
watch(pages, (value) => { currentPage.value = Math.min(currentPage.value, value); });

function label(row: MarketplaceRow) { return marketplaceLabels[String(row.marketplace ?? "").toLowerCase()] ?? row.marketplace ?? "—"; }
function productName(row: MarketplaceRow) { return row.name || row.offer_id || row.external_sku || row.external_id || "Без названия"; }
function goodName(row: MarketplaceRow) { const good = row.good_id ? goodsById.value.get(String(row.good_id)) : null; return good?.name ?? (row.good_id ? `Товар #${row.good_id}` : "Не сопоставлен"); }
function webhookNameById(id: string) { const webhook = webhooksById.value.get(id); return webhook?.name ? `${webhook.name} (${id})` : `Интеграция #${id}`; }
function webhookName(row: MarketplaceRow) { return webhookNameById(String(row.webhook_id)); }
function matchValue(row: MarketplaceRow) { if (["failed", "error", "unmatched"].includes(String(row.match_type ?? ""))) return "unmatched"; return row.match_type ?? "pending"; }
function matchLabel(row: MarketplaceRow) { return matchLabels[matchValue(row)] ?? matchValue(row); }
function matchClass(row: MarketplaceRow) { const type = matchValue(row); if (type === "created") return "match-created"; if (type === "pending") return "match-pending"; if (type === "unmatched") return "match-failed"; return ""; }
function removeFilter(filter: string[], value: string) { const index = filter.indexOf(value); if (index >= 0) filter.splice(index, 1); }
function closeFilter(event: MouseEvent) { const details = (event.currentTarget as HTMLElement).closest("details") as HTMLDetailsElement | null; if (details) details.open = false; }
function formatDate(value: string | null) { if (!value) return "—"; const date = new Date(value); return Number.isNaN(date.valueOf()) ? value : date.toLocaleString("ru-RU", { day: "2-digit", month: "2-digit", year: "2-digit", hour: "2-digit", minute: "2-digit" }); }
function toggleMobileRow(id: string | number, event?: MouseEvent) {
    if (!window.matchMedia("(max-width: 900px)").matches || (event?.detail ?? 0) > 1) return;
    const key = String(id);
    const next = new Set(expandedMobileRows.value);
    if (next.has(key)) next.delete(key); else next.add(key);
    expandedMobileRows.value = next;
}
function openProduct(row: MarketplaceRow, event: MouseEvent) {
    event.stopPropagation();
    toggleMobileRow(row.id, event);
}
function goodCode(good: GoodRow | null) { return good?.code || "—"; }
function startMatch(row: MarketplaceRow) {
    const good = row.good_id ? goodsById.value.get(String(row.good_id)) ?? null : null;
    editingId.value = String(row.id);
    selectedGood.value = good;
    codeSearch.value = good?.code ?? "";
    nameSearch.value = good?.name ?? "";
    activeGoodIndex.value = 0;
    goodListOpen.value = true;
    matchError.value = "";
}
function closeMatch() {
    editingId.value = null;
    selectedGood.value = null;
    goodListOpen.value = false;
    matchError.value = "";
}
function chooseGood(good: GoodRow) {
    if (Number(good.is_category) === 1) return;
    selectedGood.value = good;
    codeSearch.value = good.code ?? "";
    nameSearch.value = good.name ?? "";
    goodListOpen.value = false;
}
function clearGoodSearch() {
    codeSearch.value = "";
    nameSearch.value = "";
    selectedGood.value = null;
    activeGoodIndex.value = 0;
    goodListOpen.value = true;
}
function searchGoods() {
    selectedGood.value = null;
    activeGoodIndex.value = 0;
    goodListOpen.value = true;
}
function moveGood(event: KeyboardEvent) {
    if (!goodOptions.value.length) return;
    goodListOpen.value = true;
    if (event.key === "ArrowDown" || event.key === "ArrowUp") {
        event.preventDefault();
        const direction = event.key === "ArrowDown" ? 1 : -1;
        let next = activeGoodIndex.value;
        for (let step = 0; step < goodOptions.value.length; step += 1) {
            next = (next + direction + goodOptions.value.length) % goodOptions.value.length;
            if (Number(goodOptions.value[next].is_category) !== 1) {
                activeGoodIndex.value = next;
                break;
            }
        }
        return;
    }
    if (event.key === "Enter") {
        event.preventDefault();
        chooseGood(goodOptions.value[activeGoodIndex.value]);
    }
}
async function saveMatch(row: MarketplaceRow) {
    if (!selectedGood.value || Number(selectedGood.value.is_category) === 1 || savingMatch.value) return;
    savingMatch.value = true;
    matchError.value = "";
    try {
        const response = await http(`/web/fulfillment/goods_marketplace/${row.id}`, "PUT", { good_id: selectedGood.value.id, version: row.version });
        await marketplaceStore.apply(response.data);
        closeMatch();
    } catch (error) {
        matchError.value = error instanceof HttpError ? error.message : "Не удалось сохранить сопоставление.";
    } finally {
        savingMatch.value = false;
    }
}
watch([codeSearch, nameSearch], () => { activeGoodIndex.value = 0; });
onMounted(() => { void marketplaceStore.start(); void goodStore.start(); void webhookStore.start(); });
onUnmounted(() => { marketplaceStore.stop(); goodStore.stop(); webhookStore.stop(); });
</script>

<template>
    <Head title="Фулфилмент — Каталоги МП" />
    <FulfillmentTabs />
    <section class="marketplace-catalog users-workspace">
        <div class="users-list">
        <div class="catalog-heading">
            <div><h1>Каталоги маркетплейсов</h1><p>Товары из WB, OZON и Яндекс Маркета и их сопоставление с мастер-каталогом WMS.</p></div>
            <div class="catalog-stats"><span>Всего: <b>{{ filtered.length }}</b></span><span>Сопоставлено: <b>{{ matchedCount }}</b></span></div>
        </div>
        <div class="catalog-filters">
            <input v-model="query" class="catalog-search" type="search" placeholder="Поиск по названию, SKU, артикулу или штрихкоду" />
            <details class="filter-menu"><summary><span class="filter-title">Интеграции</span><span class="summary-chips"><button v-for="item in integrationFilter" :key="item" type="button" class="filter-chip" @click.prevent.stop="removeFilter(integrationFilter, item)">{{ webhookNameById(item) }} ×</button></span></summary><div class="filter-options"><button type="button" class="filter-close" aria-label="Закрыть фильтр" @click="closeFilter">×</button><label v-for="item in integrations" :key="item"><input v-model="integrationFilter" type="checkbox" :value="item" />{{ webhookNameById(item) }}</label></div></details>
            <details class="filter-menu"><summary><span class="filter-title">Маркетплейсы</span><span class="summary-chips"><button v-for="item in marketplaceFilter" :key="String(item)" type="button" class="filter-chip" @click.prevent.stop="removeFilter(marketplaceFilter, item)">{{ label({ marketplace: item } as MarketplaceRow) }} ×</button></span></summary><div class="filter-options"><button type="button" class="filter-close" aria-label="Закрыть фильтр" @click="closeFilter">×</button><label v-for="item in marketplaces" :key="String(item)"><input v-model="marketplaceFilter" type="checkbox" :value="String(item)" />{{ label({ marketplace: item } as MarketplaceRow) }}</label></div></details>
            <details class="filter-menu"><summary><span class="filter-title">Сопоставления</span><span class="summary-chips"><button v-for="item in matchFilter" :key="item" type="button" class="filter-chip" @click.prevent.stop="removeFilter(matchFilter, item)">{{ matchLabels[item] }} ×</button></span></summary><div class="filter-options"><button type="button" class="filter-close" aria-label="Закрыть фильтр" @click="closeFilter">×</button><label v-for="item in matchOptions" :key="item.value"><input v-model="matchFilter" type="checkbox" :value="item.value" />{{ item.label }}</label></div></details>
        </div>
        <div v-if="marketplaceStore.error.value" class="catalog-message">{{ marketplaceStore.error.value }}</div>
        <div class="catalog-table-wrap table-scroll"><table><colgroup><col class="col-id" /><col class="col-marketplace" /><col class="col-integration" /><col class="col-product" /><col class="col-sku" /><col class="col-barcode" /><col class="col-good" /><col class="col-match" /><col class="col-sync" /></colgroup><thead><tr><th>#</th><th>МП</th><th>Интеграция</th><th>Товар маркетплейса</th><th>Внешний SKU</th><th>Штрихкоды</th><th>Мастер-каталог WMS</th><th>Сопоставление</th><th>Синхронизация</th></tr></thead><tbody><tr v-for="row in visible" :key="row.id" class="mmp-mobile-row" :class="{ 'mobile-card-expanded': expandedMobileRows.has(String(row.id)) }" @click="toggleMobileRow(row.id, $event)"><td class="id-column mmp-id">{{ row.id }}</td><td class="mmp-mobile-title"><button type="button" class="name-button" @click="openProduct(row, $event)">{{ productName(row) }}</button></td><td class="mmp-detail mmp-marketplace" data-label="Маркетплейс"><span class="marketplace-badge" :class="`mp-${row.marketplace}`">{{ label(row) }}</span></td><td class="mmp-detail" data-label="Интеграция">{{ webhookName(row) }}</td><td class="mmp-detail mmp-desktop-name" data-label="Товар маркетплейса"><div class="product-name">{{ productName(row) }}</div><small>ID: {{ row.external_id || "—" }}</small></td><td class="mmp-detail" data-label="Внешний SKU">{{ row.offer_id || row.external_sku || "—" }}</td><td class="mmp-detail" data-label="Штрихкоды">{{ (row.barcodes ?? []).join(", ") || "—" }}</td><td class="mmp-detail" data-label="Мастер-каталог WMS"><div class="good-cell"><button type="button" class="edit-good" aria-label="Изменить мастер-товар" title="Изменить мастер-товар" @click.stop="startMatch(row)">✎</button><a v-if="row.good_id" class="good-link" :href="`/goods/goods/${row.good_id}/view`">#{{ row.good_id }} · {{ goodName(row) }}</a><span v-else class="muted">Не сопоставлен</span><div v-if="editingId === String(row.id)" class="good-editor"><div class="good-search-row"><input v-model="codeSearch" type="search" placeholder="Код" @focus="goodListOpen = true" @input="searchGoods" @dblclick="clearGoodSearch" @keydown="moveGood" /><input v-model="nameSearch" type="search" placeholder="Наименование" @focus="goodListOpen = true" @input="searchGoods" @dblclick="clearGoodSearch" @keydown="moveGood" /><button type="button" class="good-save" :disabled="!selectedGood || Number(selectedGood.is_category) === 1 || savingMatch" @click.stop="saveMatch(row)">{{ savingMatch ? "Сохранение…" : "Сохранить" }}</button><button type="button" class="good-cancel" @click.stop="closeMatch">×</button></div><div v-if="goodListOpen" class="good-options" role="listbox" aria-label="Товары мастер-каталога"><button v-for="(good, index) in goodOptions" :key="String(good.id)" type="button" class="good-option" :class="{ active: index === activeGoodIndex, category: Number(good.is_category) === 1 }" :disabled="Number(good.is_category) === 1" role="option" :aria-selected="selectedGood?.id === good.id" @click.stop="chooseGood(good)"><span>#{{ good.id }}</span><span>{{ good.code || "—" }}</span><strong>{{ good.name || "Без названия" }}</strong><em v-if="Number(good.is_category) === 1">Категория</em></button><div v-if="!goodOptions.length" class="good-options-empty">Товары не найдены.</div></div><div v-if="matchError" class="good-error">{{ matchError }}</div></div></div></td><td class="mmp-detail" data-label="Сопоставление"><span class="match-badge" :class="matchClass(row)">{{ matchLabel(row) }}</span></td><td class="mmp-detail" data-label="Синхронизация">{{ formatDate(row.synced_at) }}</td></tr><tr v-if="!visible.length"><td colspan="9" class="catalog-message">{{ marketplaceStore.ready.value ? "По выбранным условиям товары не найдены." : "Загрузка каталога…" }}</td></tr></tbody></table></div>
        <div v-if="pages > 1" class="catalog-pagination"><button :disabled="currentPage <= 1" @click="currentPage--">Назад</button><span>Страница {{ currentPage }} из {{ pages }}</span><button :disabled="currentPage >= pages" @click="currentPage++">Вперёд</button></div>
        </div>
    </section>
</template>

<style scoped>
.marketplace-catalog { margin-top: 18px; padding: 24px; }
.catalog-heading, .catalog-filters, .catalog-pagination, .catalog-stats { display: flex; align-items: center; gap: 14px; }
.catalog-heading { justify-content: space-between; margin-bottom: 20px; }
h1 { margin: 0 0 6px; color: #123322; font-size: 24px; }
p { margin: 0; color: #687a70; }
.catalog-stats { color: #687a70; font-size: 14px; } .catalog-stats b { color: #1e892f; }
.catalog-filters { margin-bottom: 10px; flex-wrap: wrap; } .catalog-filters input { flex: 1 1 280px; min-width: 240px; }
.catalog-filters input, .catalog-filters select { border: 1px solid #d5e3d8; border-radius: 7px; padding: 10px 12px; background: #fff; color: #1c3024; }
.filter-menu { position: relative; min-width: 170px; } .filter-menu:last-child { min-width: 210px; } .filter-menu summary { display: flex; align-items: center; gap: 6px; min-height: 40px; list-style: none; cursor: pointer; border: 1px solid #d5e3d8; border-radius: 7px; padding: 5px 12px; background: #fff; color: #1c3024; } .filter-menu summary::-webkit-details-marker { display: none; } .filter-menu summary::after { content: "▾"; margin-left: auto; color: #687a70; } .filter-menu[open] summary { border-color: #1e892f; } .filter-title { white-space: nowrap; } .summary-chips { display: flex; flex-wrap: wrap; gap: 4px; } .filter-options { display: flex; flex-direction: column; position: absolute; z-index: 10; top: calc(100% + 5px); left: 0; min-width: 100%; max-height: 280px; overflow-y: auto; padding: 34px 7px 7px; border: 1px solid #d5e3d8; border-radius: 7px; background: #fff; box-shadow: 0 8px 20px rgb(18 51 34 / 12%); } .filter-close { position: absolute; top: 5px; right: 7px; border: 0; background: transparent; color: #687a70; font-size: 20px; line-height: 1; cursor: pointer; } .filter-options label { display: flex; flex: 0 0 auto; align-items: flex-start; gap: 7px; width: 100%; padding: 7px 5px; color: #1c3024; white-space: normal; overflow-wrap: anywhere; cursor: pointer; } .filter-options input { flex: 0 0 15px; margin-top: 2px; min-width: 15px; }
.filter-chip { border: 0; border-radius: 999px; padding: 4px 7px; background: #e4f4e7; color: #247435; cursor: pointer; font-size: 11px; }
.catalog-table-wrap { overflow-x: hidden; border: 1px solid #e1ebe3; border-radius: 8px; } table { width: 100%; border-collapse: collapse; min-width: 0; table-layout: fixed; } col.col-id { width: 4%; } col.col-marketplace { width: 5%; } col.col-integration { width: 14%; } col.col-product { width: 20%; } col.col-sku { width: 10%; } col.col-barcode { width: 12%; } col.col-good { width: 18%; } col.col-match { width: 10%; } col.col-sync { width: 7%; } th, td { box-sizing: border-box; min-width: 0; padding: 10px 7px; border-bottom: 1px solid #edf2ee; text-align: left; vertical-align: top; font-size: 13px; white-space: normal; overflow-wrap: anywhere; } th { background: #f4faf5; color: #53675a; font-weight: 600; } tbody tr:last-child td { border-bottom: 0; }
.marketplace-badge, .match-badge { display: inline-block; border-radius: 5px; padding: 4px 7px; font-size: 12px; font-weight: 700; } .mp-wildberries { background: #f8e4f5; color: #a20c8b; } .mp-wb { background: #f8e4f5; color: #a20c8b; } .mp-ozon { background: #e3edff; color: #005bff; } .mp-yandex_market { background: #fff0d9; color: #9a5a00; } .match-badge { background: #e4f4e7; color: #247435; } .match-created { background: #e3edff; color: #005bff; } .match-pending { background: #fff4cc; color: #966c00; } .match-failed { background: #fde2e1; color: #b42318; }
.product-name { width: 100%; max-width: 100%; color: #1d3025; font-weight: 600; white-space: normal; overflow-wrap: anywhere; word-break: normal; } small, .muted { color: #7d8a81; } .good-link { display: inline-block; max-width: 100%; color: #1e892f; font-weight: 600; text-decoration: underline; white-space: normal; overflow-wrap: anywhere; word-break: normal; }
.good-cell { position: relative; } .edit-good { margin-right: 5px; border: 0; padding: 0 2px; background: transparent; color: #1e892f; font-size: 17px; line-height: 1; cursor: pointer; } .edit-good:hover, .edit-good:focus-visible { color: #145f22; }
.good-editor { position: relative; z-index: 5; min-width: 420px; margin-top: 10px; padding: 9px; border: 1px solid #cfe0d2; border-radius: 7px; background: #fff; box-shadow: 0 8px 20px rgb(18 51 34 / 12%); } .good-search-row { display: grid; grid-template-columns: 1fr 1.6fr auto auto; gap: 6px; } .good-search-row input { min-width: 0; border: 1px solid #d5e3d8; border-radius: 5px; padding: 7px 8px; color: #1c3024; } .good-save, .good-cancel { border: 1px solid #cfe0d2; border-radius: 5px; padding: 6px 9px; background: #f4faf5; color: #1e892f; cursor: pointer; white-space: nowrap; } .good-save:disabled { cursor: default; opacity: .45; } .good-cancel { padding-inline: 8px; background: #fff; color: #687a70; font-size: 18px; line-height: 1; }
.good-options { max-height: 220px; margin-top: 7px; overflow-y: auto; border: 1px solid #e1ebe3; border-radius: 5px; } .good-option { display: grid; grid-template-columns: 45px 90px minmax(120px, 1fr) auto; align-items: center; width: 100%; gap: 7px; border: 0; border-bottom: 1px solid #edf2ee; padding: 7px 8px; background: #fff; color: #53675a; text-align: left; cursor: pointer; } .good-option:last-child { border-bottom: 0; } .good-option:hover, .good-option.active { background: #eef7f0; } .good-option strong { color: #1c3024; font-weight: 600; } .good-option em { color: #966c00; font-size: 11px; font-style: normal; } .good-option.category { background: #fafafa; color: #9aa59d; cursor: not-allowed; } .good-option.category strong { color: #7d8a81; } .good-options-empty, .good-error { padding: 8px; color: #687a70; font-size: 12px; } .good-error { color: #b42318; }
.catalog-message { padding: 35px; text-align: center; color: #687a70; } .catalog-pagination { justify-content: center; margin-top: 18px; } .catalog-pagination button { border: 1px solid #cfe0d2; border-radius: 6px; padding: 8px 14px; background: #fff; color: #1e892f; cursor: pointer; } .catalog-pagination button:disabled { cursor: default; opacity: .45; }
.mmp-mobile-title { display: none; }
@media (max-width: 760px) { .marketplace-catalog { padding: 16px; } .catalog-heading, .catalog-filters { align-items: stretch; flex-direction: column; } .catalog-filters input, .catalog-filters select { width: 100%; } .catalog-stats { align-self: flex-start; } .good-editor { min-width: 0; } .good-search-row { grid-template-columns: 1fr 1fr; } .good-option { grid-template-columns: 40px 1fr; } .good-option strong, .good-option em { grid-column: 2; } }
@media (max-width: 760px) { .marketplace-catalog .catalog-filters .catalog-search { flex: none; height: 40px; min-height: 40px; padding-block: 8px; } }
@media (max-width: 900px) {
    .marketplace-catalog .catalog-table-wrap { overflow: visible; border: 0; }
    .marketplace-catalog .catalog-table-wrap table { display: block; }
    .marketplace-catalog .catalog-table-wrap tbody { display: grid; gap: 10px; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row { display: grid; grid-template-columns: max-content minmax(0, 1fr); align-items: center; min-height: 44px; overflow: hidden; border: 1px solid #dcecef; border-radius: 10px; background: #fff; box-shadow: 0 2px 8px rgb(16 24 40 / 5%); }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row td { display: none; min-width: 0; border: 0; padding: 8px 10px; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row .mmp-id,
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row .mmp-mobile-title { display: flex; align-items: center; min-height: 42px; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row .mmp-id { grid-column: 1; color: #0c1821; font-size: 11px; font-weight: 600; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row .mmp-mobile-title { grid-column: 2; overflow: hidden; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row .mmp-mobile-title .name-button { display: block; width: 100%; min-width: 0; overflow: hidden; padding: 0; border: 0; background: transparent; color: #0c1821; font-size: 15px; font-weight: 600; text-align: left; text-overflow: ellipsis; white-space: nowrap; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row .mmp-desktop-name { display: none; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row .mmp-marketplace { display: none; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row.mobile-card-expanded { display: block; background: #fff; border-color: #95c59d; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row.mobile-card-expanded .mmp-id,
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row.mobile-card-expanded .mmp-mobile-title { display: flex; background: #fff; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row.mobile-card-expanded .mmp-id { display: inline-flex; width: 38px; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row.mobile-card-expanded .mmp-mobile-title { display: inline-flex; width: calc(100% - 38px); }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row.mobile-card-expanded .mmp-detail { display: flex; align-items: center; gap: 10px; min-height: 50px; border-top: 1px solid #a8d4a9; background: #e1f3e7; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row.mobile-card-expanded .mmp-detail::before { flex: 0 0 145px; color: #1e892f; font-size: 12px; content: attr(data-label); }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row.mobile-card-expanded .mmp-detail .good-cell { min-width: 0; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row.mobile-card-expanded .good-editor { min-width: 0; max-width: 100%; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row.mobile-card-expanded .good-search-row { grid-template-columns: 1fr 1fr; }
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row.mobile-card-expanded .good-save,
    .marketplace-catalog .catalog-table-wrap .mmp-mobile-row.mobile-card-expanded .good-cancel { grid-row: 2; }
}
</style>
