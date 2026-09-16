<script setup lang="ts">
import { onMounted, onUnmounted, ref } from "vue";
import { Head } from "@inertiajs/vue3";
import MaintenanceTabs from "../Components/MaintenanceTabs.vue";
import { http, HttpError } from "../lib/http";
import { formatDate } from "../lib/dates";

interface ImportRun {
    id: string;
    name: string;
    project: string;
    started_at: string | null;
    total_records: number;
    total_chunks: number;
    processed_records: number;
    processed_chunks: number;
    total_stages: number;
    status: string;
    current_stage: string | null;
    current_stage_number: number | null;
    current_stage_name: string | null;
    error_message: string | null;
}

const rows = ref<ImportRun[]>([]);
const loading = ref(true);
const error = ref("");
let timer: number | undefined;

function progress(row: ImportRun): number {
    if (row.total_chunks > 0) {
        return Math.min(100, Math.round(row.processed_chunks / row.total_chunks * 100));
    }
    return row.total_records > 0 ? Math.min(100, Math.round(row.processed_records / row.total_records * 100)) : 0;
}

function statusLabel(status: string): string {
    return { queued: "В очереди", running: "Выполняется", completed: "Завершён", failed: "Ошибка" }[status] ?? status;
}

function hasActiveRuns(): boolean {
    return rows.value.some((row) => ["queued", "running"].includes(row.status));
}

async function load() {
    try {
        const response = await http("/web/imports");
        rows.value = response.data ?? [];
        error.value = "";
        if (hasActiveRuns() && timer === undefined) timer = window.setInterval(load, 7000);
        if (!hasActiveRuns() && timer !== undefined) {
            window.clearInterval(timer);
            timer = undefined;
        }
    } catch (exception) {
        error.value = exception instanceof HttpError ? exception.message : "Не удалось загрузить историю импортов.";
    } finally {
        loading.value = false;
    }
}

onMounted(load);
onUnmounted(() => { if (timer !== undefined) window.clearInterval(timer); });
</script>

<template>
    <Head title="Импорты" />
    <section class="page-content imports-page">
        <div class="content-breadcrumb">Обслуживание › Импорты</div>
        <MaintenanceTabs />
        <div class="page-heading">
            <div><h1>Импорты</h1><p class="muted">Запуски импорта данных и состояние обработки очередей</p></div>
            <button type="button" class="secondary" @click="load" :disabled="loading">Обновить</button>
        </div>
        <p v-if="error" class="notice error" role="alert">{{ error }}</p>
        <div class="table-scroll imports-table-wrap">
            <table class="imports-table">
                <thead><tr><th>#</th><th>Название импорта</th><th>Проект</th><th>Дата и время старта</th><th>Текущий этап</th><th>Число этапов</th><th>Прогресс</th><th>Число записей</th><th>Число чанков</th><th>Обработано записей / чанков</th><th>Статус</th></tr></thead>
                <tbody>
                    <tr v-if="loading"><td colspan="11" class="empty-cell">Загрузка…</td></tr>
                    <tr v-else-if="rows.length === 0"><td colspan="11" class="empty-cell">Импорты ещё не запускались</td></tr>
                    <tr v-for="row in rows" :key="row.id">
                        <td data-label="#"><span class="row-id">{{ row.id }}</span></td>
                        <td data-label="Название импорта" class="wrap-cell"><strong>{{ row.name }}</strong><small v-if="row.current_stage">{{ row.current_stage }}</small></td>
                        <td data-label="Проект">{{ row.project }}</td>
                        <td data-label="Дата и время старта">{{ formatDate(row.started_at, true) }}</td>
                        <td data-label="Текущий этап" class="wrap-cell">{{ row.current_stage_number ? row.current_stage_number + ". " + row.current_stage_name : "—" }}</td>
                        <td data-label="Число этапов">{{ row.total_stages }}</td>
                        <td data-label="Прогресс"><div class="import-progress" :aria-label="'Прогресс: ' + progress(row) + '%'"><span class="import-progress-track"><i :style="{ width: progress(row) + '%' }"></i></span><b>{{ progress(row) }}%</b></div></td>
                        <td data-label="Число записей">{{ row.total_records }}</td>
                        <td data-label="Число чанков">{{ row.total_chunks }}</td>
                        <td data-label="Обработано записей / чанков">{{ row.processed_records }} / {{ row.processed_chunks }}</td>
                        <td data-label="Статус"><span class="status-badge" :class="'status-' + row.status">{{ statusLabel(row.status) }}</span><small v-if="row.error_message" class="error-detail">{{ row.error_message }}</small></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<style scoped>
.imports-page { min-width: 0; }
.imports-table-wrap { overflow-x: auto; }
.imports-table { width: 100%; min-width: 980px; border-collapse: collapse; }
.imports-table th, .imports-table td { padding: 12px 10px; text-align: left; vertical-align: middle; }
.imports-table th { white-space: nowrap; }
.imports-table td { overflow-wrap: anywhere; }
.wrap-cell { max-width: 220px; white-space: normal; }
.wrap-cell small, .error-detail { display: block; margin-top: 4px; color: #667085; font-size: 11px; overflow-wrap: anywhere; }
.row-id { color: #667085; font-variant-numeric: tabular-nums; }
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
@media (max-width: 900px) {
    .imports-table { min-width: 0; }
    .imports-table thead { display: none; }
    .imports-table, .imports-table tbody, .imports-table tr, .imports-table td { display: block; width: 100%; }
    .imports-table tr { margin-bottom: 12px; border: 1px solid #dcecef; border-radius: 10px; background: #fff; }
    .imports-table td { display: flex; justify-content: space-between; gap: 12px; border-bottom: 1px solid #edf3f4; }
    .imports-table td::before { flex: 0 0 42%; color: #667085; content: attr(data-label); }
    .imports-table td:last-child { border-bottom: 0; }
    .imports-table .empty-cell { display: block; }
    .imports-table .empty-cell::before { content: none; }
}
</style>
