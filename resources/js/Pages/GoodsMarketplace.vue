<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import FulfillmentTabs from "../Components/FulfillmentTabs.vue";
import { createEntitySync } from "../lib/entitySync";
import type { EntityRow } from "../lib/cache";

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
interface GoodRow extends EntityRow { name: string | null; code: string | null; deleted_at: string | null; }
interface IntegrationWebhookRow extends EntityRow { name: string | null; deleted_at: string | null; }

const page = usePage<any>();
const scope = `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`;
const marketplaceStore = createEntitySync<MarketplaceRow>(scope, "goods_marketplace");
const goodStore = createEntitySync<GoodRow>(scope, "goods");
const webhookStore = createEntitySync<IntegrationWebhookRow>(scope, "integration_webhooks");
const query = ref("");
const marketplaceFilter = ref("all");
const matchFilter = ref("all");
const currentPage = ref(1);
const pageSize = 25;

const marketplaceLabels: Record<string, string> = { wildberries: "WB", wb: "WB", ozon: "OZON" };
const matchLabels: Record<string, string> = { auto: "Автоматически", created: "Создан автоматически", manual: "Вручную", failed: "Неуспешно", error: "Ошибка сопоставления", unmatched: "Не сопоставлено" };
const goodsById = computed(() => new Map(goodStore.rows.value.filter((row) => !row.deleted_at).map((row) => [String(row.id), row])));
const webhooksById = computed(() => new Map(webhookStore.rows.value.filter((row) => !row.deleted_at).map((row) => [String(row.id), row])));
const marketplaces = computed(() => [...new Set(marketplaceStore.rows.value.filter((row) => !row.deleted_at).map((row) => row.marketplace).filter(Boolean))]);
const filtered = computed(() => marketplaceStore.rows.value.filter((row) => {
    if (row.deleted_at) return false;
    if (marketplaceFilter.value !== "all" && row.marketplace !== marketplaceFilter.value) return false;
    if (matchFilter.value === "matched" && !row.good_id) return false;
    if (matchFilter.value === "unmatched" && row.good_id) return false;
    const text = [row.name, row.external_id, row.external_sku, row.offer_id, webhookName(row), ...(row.barcodes ?? [])].join(" ").toLocaleLowerCase("ru");
    return text.includes(query.value.toLocaleLowerCase("ru"));
}).sort((a, b) => String(a.name ?? "").localeCompare(String(b.name ?? ""), "ru")));
const pages = computed(() => Math.max(1, Math.ceil(filtered.value.length / pageSize)));
const visible = computed(() => filtered.value.slice((currentPage.value - 1) * pageSize, currentPage.value * pageSize));
const matchedCount = computed(() => filtered.value.filter((row) => row.good_id).length);
watch([query, marketplaceFilter, matchFilter], () => { currentPage.value = 1; });
watch(pages, (value) => { currentPage.value = Math.min(currentPage.value, value); });

function label(row: MarketplaceRow) { return marketplaceLabels[String(row.marketplace ?? "").toLowerCase()] ?? row.marketplace ?? "—"; }
function goodName(row: MarketplaceRow) { const good = row.good_id ? goodsById.value.get(String(row.good_id)) : null; return good?.name ?? (row.good_id ? `Товар #${row.good_id}` : "Не сопоставлен"); }
function webhookName(row: MarketplaceRow) { const webhook = webhooksById.value.get(String(row.webhook_id)); return webhook?.name ? `${webhook.name} (${row.webhook_id})` : `Интеграция #${row.webhook_id}`; }
function matchClass(row: MarketplaceRow) { return ["failed", "error", "unmatched"].includes(String(row.match_type ?? "")) ? "match-failed" : ""; }
function formatDate(value: string | null) { if (!value) return "—"; const date = new Date(value); return Number.isNaN(date.valueOf()) ? value : date.toLocaleString("ru-RU", { day: "2-digit", month: "2-digit", year: "2-digit", hour: "2-digit", minute: "2-digit" }); }
onMounted(() => { void marketplaceStore.start(); void goodStore.start(); void webhookStore.start(); });
onUnmounted(() => { marketplaceStore.stop(); goodStore.stop(); webhookStore.stop(); });
</script>

<template>
    <Head title="Фулфилмент — Каталоги МП" />
    <FulfillmentTabs />
    <section class="marketplace-catalog users-workspace">
        <div class="users-list">
        <div class="catalog-heading">
            <div><h1>Каталоги маркетплейсов</h1><p>Товары из WB и OZON и их сопоставление с мастер-каталогом WMS.</p></div>
            <div class="catalog-stats"><span>Всего: <b>{{ filtered.length }}</b></span><span>Сопоставлено: <b>{{ matchedCount }}</b></span></div>
        </div>
        <div class="catalog-filters">
            <input v-model="query" type="search" placeholder="Поиск по названию, SKU, артикулу или штрихкоду" />
            <select v-model="marketplaceFilter"><option value="all">Все МП</option><option v-for="item in marketplaces" :key="item" :value="item">{{ label({ marketplace: item } as MarketplaceRow) }}</option></select>
            <select v-model="matchFilter"><option value="all">Все сопоставления</option><option value="matched">Сопоставлены</option><option value="unmatched">Не сопоставлены</option></select>
        </div>
        <div v-if="marketplaceStore.error.value" class="catalog-message">{{ marketplaceStore.error.value }}</div>
        <div class="catalog-table-wrap table-scroll"><table><colgroup><col class="col-id" /><col class="col-marketplace" /><col class="col-integration" /><col class="col-product" /><col class="col-sku" /><col class="col-barcode" /><col class="col-good" /><col class="col-match" /><col class="col-sync" /></colgroup><thead><tr><th>#</th><th>МП</th><th>Интеграция</th><th>Товар маркетплейса</th><th>Внешний SKU</th><th>Штрихкоды</th><th>Мастер-каталог WMS</th><th>Сопоставление</th><th>Синхронизация</th></tr></thead><tbody><tr v-for="row in visible" :key="row.id"><td class="id-column">{{ row.id }}</td><td><span class="marketplace-badge" :class="`mp-${row.marketplace}`">{{ label(row) }}</span></td><td>{{ webhookName(row) }}</td><td><div class="product-name">{{ row.name || "Без названия" }}</div><small>ID: {{ row.external_id || "—" }}</small></td><td>{{ row.offer_id || row.external_sku || "—" }}</td><td>{{ (row.barcodes ?? []).join(", ") || "—" }}</td><td><span v-if="row.good_id" class="good-link">#{{ row.good_id }} · {{ goodName(row) }}</span><span v-else class="muted">Не сопоставлен</span></td><td><span v-if="row.match_type" class="match-badge" :class="matchClass(row)">{{ matchLabels[row.match_type] ?? row.match_type }}</span><span v-else class="match-badge match-pending">Ожидает сопоставления</span></td><td>{{ formatDate(row.synced_at) }}</td></tr><tr v-if="!visible.length"><td colspan="9" class="catalog-message">{{ marketplaceStore.ready.value ? "По выбранным условиям товары не найдены." : "Загрузка каталога…" }}</td></tr></tbody></table></div>
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
.catalog-filters { margin-bottom: 18px; } .catalog-filters input { flex: 1; min-width: 240px; }
.catalog-filters input, .catalog-filters select { border: 1px solid #d5e3d8; border-radius: 7px; padding: 10px 12px; background: #fff; color: #1c3024; }
.catalog-table-wrap { overflow-x: hidden; border: 1px solid #e1ebe3; border-radius: 8px; } table { width: 100%; border-collapse: collapse; min-width: 0; table-layout: fixed; } col.col-id { width: 4%; } col.col-marketplace { width: 5%; } col.col-integration { width: 14%; } col.col-product { width: 20%; } col.col-sku { width: 10%; } col.col-barcode { width: 12%; } col.col-good { width: 18%; } col.col-match { width: 10%; } col.col-sync { width: 7%; } th, td { box-sizing: border-box; padding: 10px 7px; border-bottom: 1px solid #edf2ee; text-align: left; vertical-align: top; font-size: 13px; overflow-wrap: anywhere; } th { background: #f4faf5; color: #53675a; font-weight: 600; white-space: normal; } tbody tr:last-child td { border-bottom: 0; }
.marketplace-badge, .match-badge { display: inline-block; border-radius: 5px; padding: 4px 7px; font-size: 12px; font-weight: 700; } .mp-wildberries { background: #f8e4f5; color: #a20c8b; } .mp-wb { background: #f8e4f5; color: #a20c8b; } .mp-ozon { background: #e3edff; color: #005bff; } .match-badge { background: #e4f4e7; color: #247435; } .match-pending { background: #fff4cc; color: #966c00; } .match-failed { background: #fde2e1; color: #b42318; }
.product-name { width: 100%; max-width: 100%; color: #1d3025; font-weight: 600; white-space: normal; overflow-wrap: anywhere; word-break: normal; } small, .muted { color: #7d8a81; } .good-link { display: inline-block; max-width: 100%; color: #1e892f; font-weight: 600; white-space: normal; overflow-wrap: anywhere; word-break: normal; }
.catalog-message { padding: 35px; text-align: center; color: #687a70; } .catalog-pagination { justify-content: center; margin-top: 18px; } .catalog-pagination button { border: 1px solid #cfe0d2; border-radius: 6px; padding: 8px 14px; background: #fff; color: #1e892f; cursor: pointer; } .catalog-pagination button:disabled { cursor: default; opacity: .45; }
@media (max-width: 760px) { .marketplace-catalog { padding: 16px; } .catalog-heading, .catalog-filters { align-items: stretch; flex-direction: column; } .catalog-filters input, .catalog-filters select { width: 100%; } .catalog-stats { align-self: flex-start; } }
</style>
