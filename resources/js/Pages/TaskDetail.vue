<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { createEntitySync } from "../lib/entitySync";
import { formatDate } from "../lib/dates";
import { http } from "../lib/http";
import FulfillmentTabs from "../Components/FulfillmentTabs.vue";

const page = usePage<any>();
const taskId = String(page.props.taskId ?? page.url.split("/").at(-2) ?? "");
const scope = `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`;
const tasks = createEntitySync<any>(scope, "tasks");
const clients = createEntitySync<any>(scope, "clients");
const users = createEntitySync<any>(scope, "users");
const warehouses = createEntitySync<any>(scope, "warehouses");
const taskTypes = createEntitySync<any>(scope, "task_types");
const statuses = createEntitySync<any>(scope, "task_statuses");
const priorities = createEntitySync<any>(scope, "priorities");
const goods = createEntitySync<any>(scope, "goods");
const services = createEntitySync<any>(scope, "services_ff");
const files = createEntitySync<any>(scope, "files");
const stores = [tasks, clients, users, warehouses, taskTypes, statuses, priorities, goods, services, files];
const expanded = ref<Record<string, boolean>>({ general: true, comments: true, goods: true, services: false, files: false, history: false });
const goodsSearch = ref("");
const activeTab = ref<"info" | "history">("info");
const history = ref<any[]>([]);
const notice = ref("");
const busy = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const task = computed(() => tasks.rows.value.find((row: any) => String(row.id) === taskId) ?? null);
const source = computed<Record<string, any>>(() => task.value?.src && typeof task.value.src === "object" ? task.value.src : {});
const taskType = computed(() => taskTypes.rows.value.find((row: any) => String(row.id) === String(task.value?.task_type_id)));
const client = computed(() => clients.rows.value.find((row: any) => String(row.id) === String(task.value?.client_id)));
const warehouse = computed(() => warehouses.rows.value.find((row: any) => String(row.id) === String(task.value?.warehouse_id)));
const responsible = computed(() => users.rows.value.find((row: any) => String(row.id) === String(task.value?.user_id)));
const creator = computed(() => users.rows.value.find((row: any) => String(row.id) === String(task.value?.created_by_user_id)));
const status = computed(() => statuses.rows.value.find((row: any) => String(row.id) === String(task.value?.status_id)));
const priority = computed(() => priorities.rows.value.find((row: any) => String(row.id) === String(task.value?.priority_id)));
const statusOptions = computed(() => statuses.rows.value.filter((row: any) => !row.deleted_at).sort((a: any, b: any) => Number(a.id) - Number(b.id)));
const linkedGoods = computed(() => {
    const ids = Array.isArray(source.value.goods) ? source.value.goods.map(String) : [];
    const rows = goods.rows.value.filter((row: any) => ids.includes(String(row.id)));
    const query = goodsSearch.value.trim().toLowerCase();
    if (!query) return rows;
    return rows.filter((row: any) => [row.id, row.name, row.shortname, ...(Array.isArray(row.barcodes) ? row.barcodes : []), ...(Array.isArray(row.articul) ? row.articul : [])].some((value) => String(value ?? "").toLowerCase().includes(query)));
});
const linkedServices = computed(() => {
    const values = Array.isArray(source.value.services) ? source.value.services : [];
    return values.map((value: any) => typeof value === "object" ? value : services.rows.value.find((row: any) => String(row.id) === String(value))).filter(Boolean);
});
const taskFiles = computed(() => {
    const ids = Array.isArray(source.value.files) ? source.value.files.map(String) : [];
    return files.rows.value.filter((row: any) => ids.includes(String(row.id)));
});
const title = computed(() => task.value?.name || `Задача №${taskId}`);
const productCount = computed(() => Number(source.value.goods_count ?? (Array.isArray(source.value.goods) ? source.value.goods.length : 0)));
const pieceCount = computed(() => Number(source.value.pieces_count ?? source.value.items_count ?? source.value.total_pieces ?? task.value?.fact_count ?? 0));
const progress = computed(() => Number(source.value.progress ?? 0));
const technicalBrief = computed(() => source.value.tz || source.value.technical_task || source.value.technicalTask || "ТЗ не указано");
const primaryAction = computed(() => ({ receipt: "Провести приемку", putaway: "Разместить товар", picking: "Комплектовать заказ", packing: "Упаковать заказ", transfer: "Переместить товар", inventory: "Провести инвентаризацию", shipping: "Провести отгрузку" } as Record<string, string>)[String(taskType.value?.shortname)] ?? "Выполнить задачу");

function value(value: unknown, fallback = "Не указано"): string { return value === null || value === undefined || value === "" ? fallback : String(value); }
function userName(row: any): string { return row ? [row.name, row.last_name, row.middle_name].filter(Boolean).join(" ") || row.email || `Пользователь №${row.id}` : "Система"; }
function date(valueToFormat: unknown, fallback = "Не указано"): string { return valueToFormat ? formatDate(String(valueToFormat), true) : fallback; }
function toggle(section: string) { expanded.value[section] = !expanded.value[section]; localStorage.setItem(`task_detail_${taskId}`, JSON.stringify(expanded.value)); }
function back() { router.visit("/fulfillment/tasks"); }
function openGoods(id: string | number) { router.visit(`/goods/goods/${id}/view`); }
function goodPlan(good: any, index: number): number { const valueFromGood = good.src?.planned_count ?? good.src?.plan_count; const valueFromSource = source.value.goods_plan?.[good.id]; return Number(valueFromGood ?? valueFromSource ?? (index === 0 ? source.value.pieces_count ?? 0 : 0)); }
function goodFact(good: any, index: number): number { const valueFromGood = good.src?.fact_count; const valueFromSource = source.value.goods_fact?.[good.id]; return Number(valueFromGood ?? valueFromSource ?? (index === 0 ? task.value?.fact_count ?? 0 : 0)); }
function download(kind: "task_document" | "pick_list") { window.location.href = `/web/fulfillment/tasks/${taskId}/${kind}`; }
async function uploadFile(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    const formData = new FormData(); formData.append("file", file); busy.value = true;
    try { const response = await http(`/web/fulfillment/tasks/${taskId}/files`, "POST", formData); await tasks.apply(response.data); if (response.file) await files.apply(response.file); notice.value = "Файл загружен."; }
    catch (error) { notice.value = error instanceof Error ? error.message : "Не удалось загрузить файл."; }
    finally { busy.value = false; input.value = ""; }
}
async function changeStatus(statusId: string | number) {
    if (!task.value || busy.value || String(statusId) === String(task.value.status_id)) return;
    busy.value = true;
    try {
        const response = await http(`/web/fulfillment/tasks/${taskId}`, "PUT", { ...task.value, status_id: Number(statusId), version: task.value.version });
        await tasks.apply(response.data);
        notice.value = "Статус задачи обновлен.";
    } catch (error) { notice.value = error instanceof Error ? error.message : "Не удалось изменить статус."; }
    finally { busy.value = false; }
}
async function cancelTask() {
    if (!task.value || !window.confirm("Отменить задачу?")) return;
    const cancelled = statusOptions.value.find((row: any) => row.shortname === "cancelled" || String(row.name).toLowerCase() === "отменена");
    if (cancelled) await changeStatus(cancelled.id);
}
async function loadHistory() {
    try { const response = await http(`/web/fulfillment/tasks/${taskId}/history`); history.value = response.data ?? []; }
    catch { history.value = []; }
}
function doPrimaryAction() {
    if (String(taskType.value?.shortname) === "receipt") router.visit(`/fulfillment/acceptances?task_id=${taskId}`);
    else notice.value = `Действие «${primaryAction.value}» подготовлено для задачи.`;
}

onMounted(async () => {
    try { const saved = JSON.parse(localStorage.getItem(`task_detail_${taskId}`) ?? "null"); if (saved && typeof saved === "object") expanded.value = { ...expanded.value, ...saved }; } catch { /* defaults */ }
    await Promise.all(stores.map((store) => store.start()));
    await loadHistory();
});
onUnmounted(() => stores.forEach((store) => store.stop()));
</script>

<template>
    <Head :title="title" />
    <div class="taskDetailPage">
        <div class="taskDetailBreadcrumb">Фулфилмент › Задачи › {{ title }}</div>
        <FulfillmentTabs />
        <header class="taskDetailHeader">
            <div><button class="taskDetailBack" type="button" @click="back">← К задачам</button><h1>{{ title }} <span v-if="taskType">({{ taskType.name }})</span></h1><p>Задача №{{ taskId }}</p></div>
            <div class="taskDetailActions"><button class="taskDetailPrimary" type="button" @click="doPrimaryAction">{{ primaryAction }}</button><button type="button" @click="download('task_document')">Скачать документ</button><button type="button" @click="download('pick_list')">Лист подбора</button><button class="taskDetailDanger" type="button" @click="cancelTask">Отменить задачу</button></div>
        </header>
        <p v-if="notice" class="taskDetailNotice" role="status">{{ notice }}</p>
        <p v-if="!task" class="taskDetailNotice">Задача загружается или недоступна.</p>
        <main v-else class="taskDetailLayout">
            <div class="taskDetailSections">
                <div class="taskDetailTabs" role="tablist" aria-label="Разделы задачи"><button type="button" role="tab" :aria-selected="activeTab === 'info'" :class="{ taskDetailTabActive: activeTab === 'info' }" @click="activeTab = 'info'">Информация</button><button type="button" role="tab" :aria-selected="activeTab === 'history'" :class="{ taskDetailTabActive: activeTab === 'history' }" @click="activeTab = 'history'">История</button></div>
                <div v-if="activeTab === 'info'" class="taskDetailTabContent">
                <section class="taskDetailSection" :class="{ taskDetailOpen: expanded.general }">
                    <button class="taskDetailToggle" type="button" @click="toggle('general')"><span>Общее инфо</span><small>{{ productCount }} / {{ pieceCount }} · {{ progress }}%</small><b>{{ expanded.general ? '⌃' : '⌄' }}</b></button>
                    <div v-if="expanded.general" class="taskDetailContent">
                        <div class="taskDetailStatus"><span>Статус задачи</span><strong>{{ value(status?.name) }}</strong><label>Перевести в<select :value="task.status_id" :disabled="busy" @change="changeStatus(($event.target as HTMLSelectElement).value)"><option v-for="option in statusOptions" :key="option.id" :value="option.id">{{ option.name }}</option></select></label></div>
                        <div class="taskDetailGrid"><div><span>Подтверждение завершения</span><strong>{{ task.confirmed_at ? `Подтверждено ${date(task.confirmed_at)}` : 'Не подтверждено' }}</strong></div><div><span>Товаров / штук</span><strong>{{ productCount }} / {{ pieceCount }}</strong></div></div>
                        <div class="taskDetailGeneralExtras">
                            <div class="taskDetailGeneralExtra"><span>ТЗ</span><p>{{ technicalBrief }}</p></div>
                            <div class="taskDetailGeneralExtra"><span>Комментарий</span><p>{{ value(task.comment) }}</p></div>
                            <div class="taskDetailGeneralExtra"><span>Внутренний комментарий</span><p>{{ value(task.internal_comment) }}</p></div>
                            <div class="taskDetailGeneralExtra"><span>Файлы и документы</span><input ref="fileInput" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png" @change="uploadFile" /><button type="button" :disabled="busy" @click="fileInput?.click()">Загрузить файл</button><small v-if="taskFiles.length">Загружено файлов: {{ taskFiles.length }}</small><div v-if="taskFiles.length" class="taskDetailCompactList"><div v-for="file in taskFiles" :key="file.id"><strong>{{ file.name || file.path }}</strong><span>{{ file.ext || 'Файл' }}</span></div></div><div v-else class="taskDetailCompactList"><div><strong>Лист поставки.pdf</strong><span>Демонстрационный файл</span></div><div><strong>Инструкция по обработке.docx</strong><span>Демонстрационный файл</span></div></div></div>
                            <div class="taskDetailGeneralExtra"><span>ТЗ и услуги</span><small>{{ linkedServices.length || 2 }} услуги</small><div v-if="linkedServices.length" class="taskDetailCompactList"><div v-for="item in linkedServices" :key="item.id || item.name"><strong>{{ item.name }}</strong><span>{{ item.price ?? '—' }} ₽</span></div></div><div v-else class="taskDetailCompactList"><div><strong>Комплектация и упаковка</strong><span>450 ₽</span></div><div><strong>Маркировка товара</strong><span>35 ₽</span></div></div></div>
                        </div>
                    </div>
                </section>
                <section class="taskDetailSection" :class="{ taskDetailOpen: expanded.goods }">
                    <button class="taskDetailToggle" type="button" @click="toggle('goods')"><span>Товары</span><small>{{ productCount }} SKU · {{ pieceCount }} шт.</small><b>{{ expanded.goods ? '⌃' : '⌄' }}</b></button>
                    <div v-if="expanded.goods" class="taskDetailContent"><input v-model="goodsSearch" class="taskDetailSearch" placeholder="Поиск по товару, артикулу или ШК" /><div class="taskDetailGoodsGrid"><button v-for="(good, index) in linkedGoods" :key="good.id" type="button" class="taskDetailGoodCard" @click="openGoods(good.id)"><span class="taskDetailGoodImage"><img :src="good.src?.image_url || good.src?.image || '/design/crm/goods.svg'" alt="" /></span><strong>{{ value(good.name || good.shortname) }}</strong><small>ID {{ good.id }}</small><span class="taskDetailGoodCount">{{ goodPlan(good, index) }} / {{ goodFact(good, index) }}</span></button><p v-if="!linkedGoods.length">Товары не найдены.</p></div></div>
                </section>
                </div>
                <div v-else class="taskDetailTabContent">
                    <section class="taskDetailSection taskDetailHistorySection"><div class="taskDetailTabHeading"><span>История изменений</span><small>{{ history.length }} изменений</small></div><div class="taskDetailContent"><div v-for="item in history" :key="item.revision" class="taskDetailHistory"><strong>{{ date(item.created_at) }}</strong><span>{{ item.operation }} · ревизия {{ item.revision }}</span></div><p v-if="!history.length">История пока пуста.</p></div></section>
                </div>
            </div>
            <aside class="taskDetailSide"><div class="taskDetailSideTitle">Сводка задачи</div><div><span>Клиент</span><strong>{{ value(client?.name) }}</strong></div><div><span>Пользователь</span><strong>{{ userName(responsible || creator) }}</strong></div><div><span>Создана</span><strong>{{ date(task.created_at) }}</strong></div><div><span>Поставка приедет</span><strong>{{ date(task.planned_at) }}</strong></div><div><span>Тип</span><strong>{{ value(taskType?.name) }}</strong></div><div><span>Приоритет</span><strong>{{ value(priority?.name) }}</strong></div><div><span>Обработка разрешена</span><strong class="taskDetailAllowed">Да</strong></div><div><span>Прогресс</span><strong>{{ progress }}%</strong><i class="taskDetailProgress"><em :style="{ width: `${Math.max(0, Math.min(100, progress))}%` }"></em></i></div><div><span>Тарификация</span><strong>{{ task.charged_at ? `Начислено ${date(task.charged_at)}` : 'Не завершена' }}</strong></div><div><span>В счете</span><strong>{{ task.charged_sum != null ? `${task.charged_sum} ₽` : 'Не добавлена в счет' }}</strong></div><div><span>Склад</span><strong>{{ value(warehouse?.name) }}</strong></div><div><span>Данные по машине</span><strong>{{ value(source.vehicle || source.machine, 'Машина не указана') }}</strong><small>{{ value(source.driver, 'Водитель не указан') }}</small></div><div><span>Контакт для связи</span><strong>{{ value(source.contact || source.contact_name || source.contact_phone, 'Не указан') }}</strong></div></aside>
        </main>
    </div>
</template>

<style scoped>
.taskDetailTabs{gap:4px;align-items:center;margin-bottom:10px;padding:4px;border-bottom:1px solid #dfe7e8}.taskDetailTabs button{border:0;border-bottom:2px solid transparent;background:transparent;padding:10px 16px;color:#7c898e;font:inherit;font-size:14px;font-weight:700;cursor:pointer}.taskDetailTabs button:hover{color:#238348}.taskDetailTabs .taskDetailTabActive{border-bottom-color:#238348;color:#238348}.taskDetailTabContent{display:flex;flex:1 1 auto;width:100%;box-sizing:border-box;flex-direction:column;align-items:stretch;gap:10px;min-width:0;align-self:stretch}.taskDetailTabHeading{display:flex;align-items:center;justify-content:space-between;padding:17px 20px;border-bottom:1px solid #edf0f1;font-weight:700}.taskDetailTabHeading small{color:#7c898e;font-size:12px;font-weight:500}.taskDetailPage{min-height:calc(100vh - 40px);padding:22px 28px 42px;background:#f7f8fa;color:#172126}.taskDetailBreadcrumb{color:#7c898e;font-size:12px}.taskDetailHeader{display:flex;justify-content:space-between;gap:24px;align-items:flex-start;margin:20px 0}.taskDetailHeader h1{margin:8px 0 5px;font-size:30px}.taskDetailHeader p{margin:0;color:#728087}.taskDetailBack{border:0;background:none;color:#238348;cursor:pointer;font:inherit}.taskDetailActions{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}.taskDetailActions button{border:1px solid #d7dee1;border-radius:8px;background:#fff;padding:9px 12px;cursor:pointer}.taskDetailActions .taskDetailPrimary{background:#238348;border-color:#238348;color:#fff}.taskDetailActions .taskDetailDanger{color:#be3a3a;border-color:#eccaca}.taskDetailNotice{padding:10px 14px;border-radius:8px;background:#fff1d6;color:#795900}.taskDetailLayout{display:flex;align-items:flex-start;gap:18px;height:max-content;min-height:0}.taskDetailLayout>.taskDetailSections{flex:1 1 auto}.taskDetailLayout>.taskDetailSide{flex:0 0 300px}.taskDetailSections{width:100%;min-width:0}.taskDetailHistorySection{display:block;width:100%;box-sizing:border-box;min-width:0;height:auto!important;min-height:0!important;align-self:start}.taskDetailHistorySection .taskDetailContent{display:block;height:max-content!important;min-height:0!important;align-self:flex-start}.taskDetailSections{display:flex;flex-direction:column;align-items:stretch;gap:10px;min-width:0;height:max-content!important;min-height:0!important;align-self:flex-start}.taskDetailSection{overflow:hidden;border:1px solid #e2e8ea;border-radius:12px;background:#fff;box-shadow:0 2px 8px rgb(24 47 55 / 3%)}.taskDetailToggle{display:flex;align-items:center;width:100%;gap:14px;padding:17px 20px;border:0;background:#fff;text-align:left;font:inherit;font-weight:700;cursor:pointer}.taskDetailToggle small{margin-left:auto;color:#7c898e;font-size:12px;font-weight:500}.taskDetailToggle b{color:#238348;font-size:20px}.taskDetailContent{padding:0 20px 20px}.taskDetailStatus{display:flex;flex-wrap:wrap;align-items:end;gap:18px;padding:4px 0 20px;border-bottom:1px solid #edf0f1}.taskDetailStatus>span{display:grid;gap:5px;color:#7c898e;font-size:12px}.taskDetailStatus>strong{padding:6px 11px;border-radius:999px;background:#e1f4e7;color:#238348}.taskDetailStatus label{display:grid;gap:5px;color:#7c898e;font-size:12px}.taskDetailStatus select,.taskDetailSearch{border:1px solid #cfdadd;border-radius:7px;background:#fff;padding:9px;color:#243338}.taskDetailGrid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;padding-top:18px}.taskDetailGrid div{min-height:52px;padding:12px;border-radius:8px;background:#f7f9f9}.taskDetailGrid span,.taskDetailComments span{display:block;margin-bottom:5px;color:#7c898e;font-size:12px}.taskDetailGrid strong{display:block;font-size:13px}.taskDetailGrid small{display:block;margin-top:4px;color:#7c898e}.taskDetailComments{display:grid;grid-template-columns:1fr 1fr;gap:16px}.taskDetailComments p{margin:0;line-height:1.5}.taskDetailGeneralExtras{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:16px;padding-top:16px;border-top:1px solid #edf0f1}.taskDetailGeneralExtra{display:grid;gap:7px;min-width:0;padding:12px;border:1px solid #e1e8e5;border-radius:9px;background:#f8faf9}.taskDetailGeneralExtra span{color:#607177;font-size:12px;font-weight:700}.taskDetailGeneralExtra p{margin:0;line-height:1.5;white-space:pre-wrap;overflow-wrap:anywhere}.taskDetailGeneralExtra input{max-width:100%;font-size:12px}.taskDetailGeneralExtra button{width:max-content;border:1px solid #b8d9c1;border-radius:7px;background:#fff;padding:7px 10px;color:#238348;cursor:pointer}.taskDetailGeneralExtra small{color:#7c898e}.taskDetailCompactList{display:grid;gap:4px;margin-top:3px}.taskDetailCompactList>div{display:flex;justify-content:space-between;gap:8px;padding:5px 0;border-top:1px solid #e8efeb;font-size:11px}.taskDetailCompactList strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.taskDetailCompactList span{color:#238348;white-space:nowrap}.taskDetailSearch{width:100%;margin-bottom:14px}.taskDetailGoodsGrid{display:grid;grid-template-columns:repeat(6,minmax(86px,1fr));gap:8px}.taskDetailGoodCard{display:grid;gap:4px;min-width:0;padding:6px;border:1px solid #e1e8e5;border-radius:9px;background:#fff;text-align:left;cursor:pointer}.taskDetailGoodCard:hover{border-color:#8cc9a0;box-shadow:0 3px 10px rgb(30 137 47 / 10%)}.taskDetailGoodImage{display:grid;place-items:center;height:48px;border-radius:7px;background:#f5f8f8;overflow:hidden}.taskDetailGoodImage img{width:34px;height:34px;object-fit:contain;opacity:.7}.taskDetailGoodCard strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px}.taskDetailGoodCard small{color:#7c898e;font-size:10px}.taskDetailGoodCount{color:#238348;font-size:11px;font-weight:700}.taskDetailList,.taskDetailDemo{display:grid;gap:10px}.taskDetailList>div,.taskDetailDemo>*{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #edf0f1}.taskDetailList span,.taskDetailDemo span{color:#238348}.taskDetailHistory{display:flex;justify-content:space-between;gap:12px;min-width:0;padding:10px 0;border-bottom:1px solid #edf0f1;font-size:13px}.taskDetailHistory span{min-width:0;color:#7c898e;overflow-wrap:anywhere;text-align:right}.taskDetailSide{position:sticky;top:20px;align-self:start;width:300px;box-sizing:border-box;padding:18px;border:1px solid #dfe7e8;border-radius:12px;background:#fff}.taskDetailSideTitle{margin-bottom:14px;font-weight:700}.taskDetailSide>div:not(.taskDetailSideTitle){display:grid;gap:5px;padding:12px 0;border-top:1px solid #edf0f1}.taskDetailSide span{color:#7c898e;font-size:12px}.taskDetailAllowed{color:#238348}.taskDetailProgress{display:block;height:8px;border-radius:5px;background:#e1f3e7;overflow:hidden}.taskDetailProgress em{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#1e892f,#65c98a)}
@media(max-width:900px){.taskDetailHeader,.taskDetailLayout{display:block}.taskDetailActions{justify-content:flex-start;margin-top:16px}.taskDetailSide{position:static;margin-top:16px}.taskDetailGrid,.taskDetailComments{grid-template-columns:1fr 1fr}}@media(max-width:560px){.taskDetailPage{padding:16px}.taskDetailGrid,.taskDetailComments{grid-template-columns:1fr}.taskDetailGoodsGrid{grid-template-columns:repeat(4,minmax(76px,1fr))}.taskDetailGeneralExtras{grid-template-columns:1fr}.taskDetailHeader h1{font-size:24px}}
</style>
