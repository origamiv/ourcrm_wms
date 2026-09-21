<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import { Head } from "@inertiajs/vue3";
import MaintenanceTabs from "../Components/MaintenanceTabs.vue";
import { http, HttpError } from "../lib/http";
import { formatDateInTimezone } from "../lib/dates";

interface ImportRun {
    id: string;
    row_type: "run" | "stage";
    parent_id: string | null;
    name: string;
    project: string;
    updated_at: string | null;
    started_at: string | null;
    total_records: number;
    total_chunks: number;
    processed_records: number;
    processed_chunks: number;
    total_stages: number;
    completed_stages: number;
    status: string;
    current_stage: string | null;
    current_stage_number: number | null;
    current_stage_name: string | null;
    error_message: string | null;
    webhook_id: number | null;
    client_id: number | null;
    client_name: string | null;
    account_id: number | null;
    marketplace: string;
}

const rows = ref<ImportRun[]>([]);
const loading = ref(true);
const error = ref("");
const expandedRuns = ref<Set<string>>(new Set());
const expandedCards = ref<Set<string>>(new Set());
let timer: number | undefined;

const visibleRows = computed(() => rows.value.filter((row) => row.row_type === "run" || expandedRuns.value.has(row.parent_id ?? "")));

function progress(row: ImportRun): number | null {
    if (row.row_type === "run") {
        return row.total_stages > 0 ? Math.min(100, Math.round(row.completed_stages / row.total_stages * 100)) : 0;
    }
    if (row.total_records === 0) return row.status === "completed" ? 100 : null;
    return Math.min(100, Math.round(row.processed_records / row.total_records * 100));
}

function progressText(row: ImportRun): string {
    const value = progress(row);
    return value === null ? "—" : value + "%";
}

function recordsText(row: ImportRun): string {
    if (row.total_records > 0 || row.processed_records > 0) {
        return `${row.processed_records} / ${row.total_records} записей`;
    }
    if (row.status === "queued") return "Ожидает запуска";
    if (row.status === "running") return "Ожидание ответа API";
    return "Записей нет";
}

function statusLabel(status: string): string {
    return { queued: "В очереди", running: "Выполняется", completed: "Завершён", failed: "Ошибка" }[status] ?? status;
}

function cardKey(row: ImportRun): string {
    return row.row_type + "-" + row.id;
}

function toggleCard(row: ImportRun): void {
    const key = cardKey(row);
    const next = new Set(expandedCards.value);
    if (next.has(key)) next.delete(key);
    else next.add(key);
    expandedCards.value = next;
}

function hasChildren(row: ImportRun): boolean {
    return row.row_type === "run" && rows.value.some((child) => child.row_type === "stage" && child.parent_id === row.id);
}

function isRunExpanded(row: ImportRun): boolean {
    return expandedRuns.value.has(row.id);
}

function toggleRun(row: ImportRun): void {
    if (!hasChildren(row)) return;
    const next = new Set(expandedRuns.value);
    if (next.has(row.id)) next.delete(row.id);
    else next.add(row.id);
    expandedRuns.value = next;
}

function cardDate(value: string | null): string {
    const formatted = formatDateInTimezone(value, "Europe/Moscow", true);
    return formatted === "—" ? formatted : formatted.slice(0, 5) + formatted.slice(8);
}

async function load() {
    try {
        const response = await http("/web/imports");
        rows.value = response.data ?? [];
        error.value = "";
    } catch (exception) {
        error.value = exception instanceof HttpError ? exception.message : "Не удалось загрузить историю импортов.";
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    load();
    timer = window.setInterval(load, 7000);
});
onUnmounted(() => { if (timer !== undefined) window.clearInterval(timer); });
</script>

<template>
    <Head title="Импорты" />
    <section class="page-content imports-page">
        <div class="content-breadcrumb">Обслуживание › Импорты</div>
        <MaintenanceTabs />
        <div class="page-heading">
            <div><h1>Импорты</h1><p class="muted">Запуски импорта данных и состояние обработки очередей</p></div>
        </div>
        <p v-if="error" class="notice error" role="alert">{{ error }}</p>
        <div class="table-scroll imports-table-wrap">
            <table class="imports-table">
                <thead><tr><th>#</th><th>Название импорта</th><th>Клиент</th><th>Маркетплейс</th><th>Интеграция</th><th>Дата и время старта</th><th>Текущий этап</th><th>Прогресс</th><th>Обработано записей</th><th>Статус</th></tr></thead>
                <tbody>
                    <tr v-if="loading"><td colspan="10" class="empty-cell">Загрузка…</td></tr>
                    <tr v-else-if="rows.length === 0"><td colspan="10" class="empty-cell">Импорты ещё не запускались</td></tr>
                    <tr v-for="row in visibleRows" :key="row.row_type + '-' + row.id" :class="{ 'stage-row': row.row_type === 'stage' }">
                        <td data-label="#"><span class="row-id">{{ row.id }}</span></td>
                        <td data-label="Название импорта" class="wrap-cell">
                            <button v-if="hasChildren(row)" type="button" class="import-run-toggle" :aria-expanded="isRunExpanded(row)" :aria-label="isRunExpanded(row) ? 'Свернуть этапы' : 'Развернуть этапы'" @click="toggleRun(row)">{{ isRunExpanded(row) ? '⌄' : '›' }}</button>
                            <strong>{{ row.row_type === 'stage' ? '↳ ' : '' }}{{ row.name }}</strong>
                        </td>
                        <td data-label="Клиент">{{ row.client_name || (row.client_id ? '#' + row.client_id : '—') }}</td>
                        <td data-label="Маркетплейс">{{ row.marketplace }}</td>
                        <td data-label="Интеграция">#{{ row.webhook_id || '—' }}<small v-if="row.account_id" class="wrap-cell">Аккаунт #{{ row.account_id }}</small></td>
                        <td data-label="Дата и время старта">{{ formatDateInTimezone(row.started_at, "Europe/Moscow", true) }}</td>
                        <td data-label="Текущий этап" class="wrap-cell">{{ row.current_stage_number ? row.current_stage_number + ". " + row.current_stage_name : "—" }}</td>
                        <td data-label="Прогресс"><div class="import-progress" :aria-label="'Прогресс: ' + progressText(row)"><span class="import-progress-track"><i :style="{ width: (progress(row) ?? 0) + '%' }"></i></span><b>{{ progressText(row) }}</b></div></td>
                        <td data-label="Обработано записей">{{ row.processed_records }} / {{ row.total_records }}</td>
                        <td data-label="Статус"><span class="status-badge" :class="'status-' + row.status">{{ statusLabel(row.status) }}</span><small v-if="row.error_message" class="error-detail">{{ row.error_message }}</small></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="imports-mobile-list" aria-label="Импорты">
            <div v-if="loading" class="imports-mobile-empty">Загрузка…</div>
            <div v-else-if="rows.length === 0" class="imports-mobile-empty">Импорты ещё не запускались</div>
            <article v-for="row in visibleRows" v-else :key="'mobile-' + row.row_type + '-' + row.id" class="import-mobile-card" :class="{ 'stage-mobile-card': row.row_type === 'stage' }">
                <button type="button" class="import-mobile-card-head" :aria-expanded="expandedCards.has(cardKey(row))" @click="toggleCard(row)">
                    <div class="import-mobile-card-head-content">
                        <div class="import-mobile-title">
                            <span class="import-mobile-id">#{{ row.id }}</span>
                            <strong>{{ row.row_type === 'stage' ? '↳ ' : '' }}{{ row.name }}</strong>
                        </div>
                        <span class="import-mobile-date">{{ cardDate(row.started_at) }}</span>
                    </div>
                        <span class="import-mobile-card-head-actions">
                        <span class="import-mobile-records">{{ recordsText(row) }}</span>
                        <span class="status-badge" :class="'status-' + row.status">{{ statusLabel(row.status) }}</span>
                        <button v-if="hasChildren(row)" type="button" class="import-run-toggle-mobile-button" :aria-expanded="isRunExpanded(row)" :aria-label="isRunExpanded(row) ? 'Свернуть этапы' : 'Развернуть этапы'" @click.stop="toggleRun(row)">{{ isRunExpanded(row) ? '⌄' : '›' }}</button>
                        <span class="import-mobile-card-chevron" aria-hidden="true">⌄</span>
                    </span>
                </button>
                <div v-if="expandedCards.has(cardKey(row))" class="import-mobile-card-body">
                    <div class="import-mobile-meta">
                        <div><span>Клиент</span><strong>{{ row.client_name || (row.client_id ? '#' + row.client_id : '—') }}</strong></div>
                        <div><span>Маркетплейс</span><strong>{{ row.marketplace }}</strong></div>
                        <div><span>Интеграция</span><strong>#{{ row.webhook_id || '—' }}<template v-if="row.account_id"> / аккаунт #{{ row.account_id }}</template></strong></div>
                        <div><span>Текущий этап</span><strong>{{ row.current_stage_number ? row.current_stage_number + '. ' + row.current_stage_name : '—' }}</strong></div>
                    </div>
                    <div class="import-mobile-progress">
                        <div class="import-mobile-progress-label"><span>Прогресс</span><b>{{ progressText(row) }}</b></div>
                        <span class="import-progress-track"><i :style="{ width: (progress(row) ?? 0) + '%' }"></i></span>
                    </div>
                    <div class="import-mobile-stats">
                        <div><span>Обработано записей</span><strong>{{ recordsText(row) }}</strong></div>
                        <div><span>Последнее обновление</span><strong>{{ formatDateInTimezone(row.updated_at, "Europe/Moscow", true) }}</strong></div>
                    </div>
                    <p v-if="row.error_message" class="error-detail">{{ row.error_message }}</p>
                </div>
            </article>
        </div>
    </section>
</template>

<style scoped>
.imports-page { min-width: 0; min-height: 0; overflow-y: auto; }
.imports-table-wrap { overflow-x: auto; }
.imports-table { width: 100%; min-width: 980px; border-collapse: collapse; }
.imports-table th, .imports-table td { padding: 12px 10px; text-align: left; vertical-align: middle; }
.imports-table th { white-space: nowrap; }
.imports-table td { overflow-wrap: anywhere; }
.stage-row td { background: #f8fbfa; }
.stage-row td:first-child, .stage-row td:nth-child(2) { color: #667085; }
.wrap-cell { max-width: 220px; white-space: normal; }
.wrap-cell small, .error-detail { display: block; margin-top: 4px; color: #667085; font-size: 11px; overflow-wrap: anywhere; }
.row-id { color: #667085; font-variant-numeric: tabular-nums; }
.import-run-toggle, .import-run-toggle-mobile-button { display: inline-grid; place-items: center; width: 22px; height: 22px; margin-right: 5px; padding: 0; border: 1px solid #b7d8be; border-radius: 5px; background: #f3fbf4; color: #1e892f; font-size: 17px; line-height: 1; cursor: pointer; vertical-align: middle; }
.import-run-toggle-mobile-button { flex: 0 0 22px; margin: 0; }
.import-run-toggle:hover, .import-run-toggle-mobile-button:hover { background: #e1f3e7; }
.empty-cell { padding: 36px 16px !important; text-align: center !important; color: #667085; }
.import-progress { display: flex; align-items: center; gap: 8px; min-width: 130px; }
.import-progress-track { width: 88px; height: 7px; border-radius: 5px; background: #e1f3e7; overflow: hidden; }
.import-progress-track i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #1e892f, #65c98a); transition: width .2s ease; }
.import-progress b { color: #1e892f; font-size: 11px; }
.status-badge { display: inline-block; padding: 4px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.status-queued { color: #946200; background: #fff1bf; }
.status-running { color: #1459a6; background: #dcecff; }
.status-completed { color: #176b2a; background: #dff3e4; }
.status-failed { color: #a32626; background: #ffe1e1; }
.imports-mobile-list { display: none; }
@media (max-width: 900px) {
    .imports-table-wrap { display: none; }
    .imports-mobile-list { display: grid; gap: 10px; }
    .import-mobile-card { overflow: hidden; border: 1px solid #dcecef; border-radius: 10px; background: #fff; box-shadow: 0 2px 8px rgb(16 24 40 / 5%); }
    .stage-mobile-card { border-color: #c9e3d1; background: #f8fbfa; }
    .import-mobile-card-head { display: flex; width: 100%; align-items: flex-start; justify-content: space-between; gap: 10px; padding: 12px; border: 0; border-bottom: 1px solid #edf3f4; background: transparent; color: inherit; text-align: left; cursor: pointer; }
    .import-mobile-card-head-content { display: grid; min-width: 0; gap: 4px; }
    .import-mobile-title { display: flex; min-width: 0; align-items: flex-start; gap: 8px; }
    .import-mobile-title strong { min-width: 0; color: #0c1821; font-size: 14px; line-height: 1.35; overflow-wrap: anywhere; }
    .import-mobile-id { flex: 0 0 auto; color: #667085; font-size: 11px; font-variant-numeric: tabular-nums; }
    .import-mobile-date { color: #667085; font-size: 12px; font-variant-numeric: tabular-nums; }
    .import-mobile-card-head-actions { display: flex; flex: 0 0 auto; align-items: center; gap: 8px; }
    .import-mobile-card-head .status-badge { flex: 0 0 auto; }
    .import-mobile-card-chevron { color: #1e892f; font-size: 18px; line-height: 1; transform: rotate(0deg); transition: transform .15s ease; }
    .import-mobile-card-head[aria-expanded="true"] .import-mobile-card-chevron { transform: rotate(180deg); }
    .import-mobile-card-body { display: block; }
    .import-mobile-meta, .import-mobile-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; padding: 12px; }
    .import-mobile-meta div, .import-mobile-stats div { min-width: 0; }
    .import-mobile-meta div:last-child { grid-column: 1 / -1; }
    .import-mobile-meta span, .import-mobile-stats span, .import-mobile-progress-label span { display: block; margin-bottom: 3px; color: #667085; font-size: 11px; }
    .import-mobile-meta strong, .import-mobile-stats strong { display: block; color: #0c1821; font-size: 12px; line-height: 1.35; overflow-wrap: anywhere; }
    .import-mobile-progress { padding: 0 12px; }
    .import-mobile-progress-label { display: flex; justify-content: space-between; align-items: baseline; }
    .import-mobile-progress-label b { color: #1e892f; font-size: 12px; }
    .import-mobile-progress .import-progress-track { display: block; width: 100%; height: 8px; }
    .import-mobile-stats { padding-top: 14px; border-top: 1px solid #edf3f4; }
    .import-mobile-card .error-detail { margin: 0; padding: 0 12px 12px; color: #a32626; }
    .imports-mobile-empty { padding: 32px 16px; border: 1px solid #dcecef; border-radius: 10px; color: #667085; text-align: center; }
}
</style>
