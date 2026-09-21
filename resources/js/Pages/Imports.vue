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
    processed_records: number;
    total_stages: number;
    completed_stages: number;
    status: string;
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
const syncing = ref(false);
const error = ref("");
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const expandedRuns = ref<Set<string>>(new Set());
const expandedMobileRows = ref<Set<string>>(new Set());
let timer: number | undefined;

const visibleRows = computed(() => rows.value.filter((row) => row.row_type === "run" || expandedRuns.value.has(row.parent_id ?? "")));

function progress(row: ImportRun): number | null {
    if (row.row_type === "run") return row.total_stages > 0 ? Math.min(100, Math.round(row.completed_stages / row.total_stages * 100)) : 0;
    if (row.total_records === 0) return row.status === "completed" ? 100 : null;
    return Math.min(100, Math.round(row.processed_records / row.total_records * 100));
}

function progressText(row: ImportRun): string {
    const value = progress(row);
    return value === null ? "—" : `${value}%`;
}

function recordsText(row: ImportRun): string {
    if (row.total_records > 0 || row.processed_records > 0) return `${row.processed_records} / ${row.total_records}`;
    if (row.status === "queued") return "Ожидает запуска";
    if (row.status === "running") return "Ожидание ответа API";
    return "Записей нет";
}

function statusLabel(status: string): string {
    return { queued: "В очереди", running: "Выполняется", completed: "Завершён", failed: "Ошибка" }[status] ?? status;
}

function statusClass(status: string): string {
    return { queued: "status-0", running: "status-3", completed: "status-1", failed: "status-2" }[status] ?? "";
}

function hasChildren(row: ImportRun): boolean {
    return row.row_type === "run" && rows.value.some((child) => child.row_type === "stage" && child.parent_id === row.id);
}

function isRunExpanded(row: ImportRun): boolean {
    return expandedRuns.value.has(row.id);
}

function toggleRun(row: ImportRun, event?: MouseEvent): void {
    event?.stopPropagation();
    if (!hasChildren(row)) return;
    const next = new Set(expandedRuns.value);
    if (next.has(row.id)) next.delete(row.id);
    else next.add(row.id);
    expandedRuns.value = next;
}

function toggleMobileRow(row: ImportRun, event?: MouseEvent): void {
    if (!window.matchMedia("(max-width: 900px)").matches || (event?.detail ?? 0) > 1 || hasChildren(row)) return;
    const key = `${row.row_type}-${row.id}`;
    const next = new Set(expandedMobileRows.value);
    if (next.has(key)) next.delete(key);
    else next.add(key);
    expandedMobileRows.value = next;
}

function isMobileExpanded(row: ImportRun): boolean {
    return expandedMobileRows.value.has(`${row.row_type}-${row.id}`);
}

function openName(row: ImportRun, event: MouseEvent): void {
    if (window.matchMedia("(max-width: 900px)").matches) toggleMobileRow(row, event);
}

async function load(page = currentPage.value): Promise<void> {
    loading.value = rows.value.length === 0;
    try {
        const response = await http(`/web/imports?page=${page}`);
        rows.value = response.data ?? [];
        currentPage.value = Number(response.current_page ?? page);
        lastPage.value = Math.max(1, Number(response.last_page ?? 1));
        total.value = Number(response.total ?? rows.value.filter((row: ImportRun) => row.row_type === "run").length);
        error.value = "";
    } catch (exception) {
        error.value = exception instanceof HttpError ? exception.message : "Не удалось загрузить историю импортов.";
    } finally {
        loading.value = false;
    }
}

async function refresh(): Promise<void> {
    if (syncing.value) return;
    syncing.value = true;
    try { await load(); } finally { syncing.value = false; }
}

function changePage(page: number): void {
    if (page < 1 || page > lastPage.value || page === currentPage.value) return;
    load(page);
}

onMounted(() => {
    load();
    timer = window.setInterval(() => load(), 7000);
});
onUnmounted(() => { if (timer !== undefined) window.clearInterval(timer); });
</script>

<template>
    <Head title="Импорты" />
    <div class="users-workspace imports-workspace">
        <section class="users-list">
            <div class="content-breadcrumb">Обслуживание › Импорты</div>
            <MaintenanceTabs />
            <div class="page-heading"><h1>Импорты</h1></div>
            <div class="sync-line" role="status">{{ syncing ? "Обновляем состояние…" : "Состояние импортов обновляется автоматически" }}</div>
            <p v-if="error" class="notice error" role="alert">{{ error }}</p>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th scope="col" class="id-column">#</th>
                            <th scope="col">Название импорта</th>
                            <th scope="col">Клиент</th>
                            <th scope="col">Маркетплейс</th>
                            <th scope="col">Интеграция</th>
                            <th scope="col">Дата и время старта</th>
                            <th scope="col">Текущий этап</th>
                            <th scope="col">Прогресс</th>
                            <th scope="col">Обработано записей</th>
                            <th scope="col">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading"><td colspan="10" class="empty-state">Загрузка импортов…</td></tr>
                        <tr v-else-if="!visibleRows.length"><td colspan="10" class="empty-state">Импорты ещё не запускались</td></tr>
                        <tr v-for="row in visibleRows" v-else :key="`${row.row_type}-${row.id}`" :class="{ 'mobile-card-expanded': isMobileExpanded(row), 'stage-row': row.row_type === 'stage' }" @click="toggleMobileRow(row, $event)">
                            <td class="id-column" data-label="#">{{ row.id }}</td>
                            <td data-label="Название импорта">
                                <button v-if="hasChildren(row)" type="button" class="row-arrow" :aria-label="isRunExpanded(row) ? 'Свернуть этапы' : 'Развернуть этапы'" @click="toggleRun(row, $event)">{{ isRunExpanded(row) ? "⌄" : "›" }}</button>
                                <button type="button" class="name-button" @click="openName(row, $event)">{{ row.row_type === "stage" ? "↳ " : "" }}{{ row.name }}</button>
                            </td>
                            <td data-label="Клиент">{{ row.client_name || (row.client_id ? `#${row.client_id}` : "—") }}</td>
                            <td data-label="Маркетплейс">{{ row.marketplace || "—" }}</td>
                            <td data-label="Интеграция">#{{ row.webhook_id || "—" }}<small v-if="row.account_id">Аккаунт #{{ row.account_id }}</small></td>
                            <td data-label="Дата и время старта">{{ formatDateInTimezone(row.started_at, "Europe/Moscow", true) }}</td>
                            <td data-label="Текущий этап">{{ row.current_stage_number ? `${row.current_stage_number}. ${row.current_stage_name}` : "—" }}</td>
                            <td data-label="Прогресс"><div class="import-progress"><span class="import-progress-track"><i :style="{ width: `${progress(row) ?? 0}%` }"></i></span><b>{{ progressText(row) }}</b></div></td>
                            <td data-label="Обработано записей">{{ recordsText(row) }}</td>
                            <td data-label="Статус"><span class="badge" :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span><small v-if="row.error_message" class="error-detail">{{ row.error_message }}</small></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <footer class="list-footer">
                <span>Найдено: {{ total }}</span>
                <div>
                    <button class="refresh-button" :disabled="syncing" @click="refresh">Обновить</button>
                    <button :disabled="currentPage === 1" aria-label="Предыдущая страница" @click="changePage(currentPage - 1)">‹</button>
                    <span>{{ currentPage }} / {{ lastPage }}</span>
                    <button :disabled="currentPage === lastPage" aria-label="Следующая страница" @click="changePage(currentPage + 1)">›</button>
                </div>
            </footer>
        </section>
    </div>
</template>

<style scoped>
.imports-workspace .page-heading { padding-bottom: 12px; }
.imports-workspace .sync-line { padding-bottom: 14px; }
.imports-workspace .table-scroll { flex: 1; }
.imports-workspace .row-arrow { width: 24px; margin-right: 4px; border: 0; background: transparent; color: #1e892f; font-size: 19px; vertical-align: middle; cursor: pointer; }
.imports-workspace .name-button { max-width: 280px; overflow: hidden; text-align: left; text-overflow: ellipsis; white-space: nowrap; }
.imports-workspace td small, .imports-workspace .error-detail { display: block; margin-top: 4px; color: #667085; font-size: 10px; overflow-wrap: anywhere; }
.imports-workspace .error-detail { color: #a32626; }
.import-progress { display: flex; align-items: center; gap: 8px; min-width: 125px; }
.import-progress-track { display: block; width: 76px; height: 7px; overflow: hidden; border-radius: 5px; background: #e1f3e7; }
.import-progress-track i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #1e892f, #65c98a); transition: width .2s ease; }
.import-progress b { color: #1e892f; font-size: 11px; }
.imports-workspace .list-footer .refresh-button { width: auto; padding: 0 8px; font-size: 12px; }
</style>
