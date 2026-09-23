<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import MaintenanceTabs from "../Components/MaintenanceTabs.vue";
import ProgressBar from "../Components/ProgressBar.vue";
import { http, HttpError } from "../lib/http";
import { formatDateInTimezone } from "../lib/dates";
import FilterPresetButton from "../Components/FilterPresetButton.vue";
import FilterPresetTiles from "../Components/FilterPresetTiles.vue";
import TableSearchButton from "../Components/TableSearchButton.vue";
import {
    serializedFilter,
    useFilterPresets,
    type FilterField,
    type FilterOptions,
} from "../lib/tableFilters";
import { appendTableSearch, useTableSearch } from "../lib/tableSearch";

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
const advancedFilters = useFilterPresets("imports");
const advancedFields: FilterField[] = [
    { id: "id", label: "#", type: "number" },
    { id: "name", label: "Название импорта", type: "text" },
    { id: "client_id", label: "ID клиента", type: "number" },
    { id: "client_name", label: "Клиент", type: "text" },
    { id: "marketplace", label: "Маркетплейс", type: "tuple" },
    { id: "webhook_id", label: "ID интеграции", type: "number" },
    { id: "started_at", label: "Дата и время старта", type: "date" },
    { id: "updated_at", label: "Изменено", type: "date" },
    { id: "current_stage", label: "Текущий этап", type: "text" },
    { id: "processed_records", label: "Обработано записей", type: "number" },
    {
        id: "status",
        label: "Статус",
        type: "tuple",
        format: (value) => statusLabel(String(value)),
    },
    { id: "error_message", label: "Ошибка", type: "text" },
];
const tableSearch = useTableSearch("imports", advancedFields);
const advancedOptions = computed<FilterOptions>(() => ({
    marketplace: [
        ...new Set(
            rows.value
                .filter((row) => row.row_type === "run")
                .map((row) => row.marketplace)
                .filter((value): value is string => Boolean(value)),
        ),
    ],
    status: [...new Set(rows.value.map((row) => row.status))],
}));
const loading = ref(true);
const syncing = ref(false);
const error = ref("");
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const expandedRuns = ref<Set<string>>(new Set());
const expandedMobileRows = ref<Set<string>>(new Set());
let timer: number | undefined;

const searchBaseRows = computed(() => rows.value);
const searchedRows = computed(() => tableSearch.apply(searchBaseRows.value));
const visibleRows = computed(() =>
    searchedRows.value.filter(
        (row) =>
            row.row_type === "run" ||
            expandedRuns.value.has(row.parent_id ?? ""),
    ),
);

function progress(row: ImportRun): number | null {
    if (row.row_type === "run")
        return row.total_stages > 0
            ? Math.min(
                  100,
                  Math.round((row.completed_stages / row.total_stages) * 100),
              )
            : 0;
    if (row.total_records === 0) return row.status === "completed" ? 100 : null;
    return Math.min(
        100,
        Math.round((row.processed_records / row.total_records) * 100),
    );
}

function recordsText(row: ImportRun): string {
    if (row.total_records > 0)
        return `${row.processed_records} из ${row.total_records}`;
    return `${row.processed_records}`;
}

function updatedTime(row: ImportRun): string {
    const value = formatDateInTimezone(row.updated_at, "Europe/Moscow", true);
    return value === "—" ? value : value.slice(-5);
}

function marketplaceCode(marketplace: string): string {
    const value = marketplace.trim().toLowerCase();
    if (value === "wildberries" || value === "wb") return "wb";
    if (value === "ozon") return "ozon";
    if (value === "yandex_market" || value === "ym" || value.includes("yandex"))
        return "ym";
    return "other";
}

function marketplaceLabel(marketplace: string): string {
    return (
        ({ wb: "WB", ozon: "Ozon", ym: "YM" }[marketplaceCode(marketplace)] ??
            marketplace) ||
        "—"
    );
}

function statusLabel(status: string): string {
    return (
        {
            queued: "В очереди",
            running: "Выполняется",
            completed: "Завершён",
            failed: "Ошибка",
        }[status] ?? status
    );
}

function statusClass(status: string): string {
    return (
        {
            queued: "status-0",
            running: "status-3",
            completed: "status-1",
            failed: "status-2",
        }[status] ?? ""
    );
}

function hasChildren(row: ImportRun): boolean {
    return (
        row.row_type === "run" &&
        rows.value.some(
            (child) => child.row_type === "stage" && child.parent_id === row.id,
        )
    );
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
    event?.stopPropagation();
    if (
        !window.matchMedia("(max-width: 900px)").matches ||
        (event?.detail ?? 0) > 1
    )
        return;
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
    if (window.matchMedia("(max-width: 900px)").matches)
        toggleMobileRow(row, event);
}

async function load(page = currentPage.value): Promise<void> {
    loading.value = rows.value.length === 0;
    try {
        const query = new URLSearchParams({ page: String(page) });
        appendTableSearch(query, tableSearch);
        const filter = serializedFilter(advancedFilters.combined.value);
        if (filter) query.set("filter", filter);
        const response = await http(`/web/imports?${query}`);
        rows.value = response.data ?? [];
        currentPage.value = Number(response.current_page ?? page);
        lastPage.value = Math.max(1, Number(response.last_page ?? 1));
        total.value = Number(
            response.total ??
                rows.value.filter((row: ImportRun) => row.row_type === "run")
                    .length,
        );
        error.value = "";
    } catch (exception) {
        error.value =
            exception instanceof HttpError
                ? exception.message
                : "Не удалось загрузить историю импортов.";
    } finally {
        loading.value = false;
    }
}

async function refresh(): Promise<void> {
    if (syncing.value) return;
    syncing.value = true;
    try {
        await load();
    } finally {
        syncing.value = false;
    }
}

function changePage(page: number): void {
    if (page < 1 || page > lastPage.value || page === currentPage.value) return;
    load(page);
}

onMounted(() => {
    load();
    timer = window.setInterval(() => load(), 7000);
});
watch(
    () => advancedFilters.combined.value,
    () => void load(1),
    { deep: true },
);
watch(
    [tableSearch.debouncedQuery, tableSearch.mode, tableSearch.selectedFields],
    () => void load(1),
    { deep: true },
);
onUnmounted(() => {
    if (timer !== undefined) window.clearInterval(timer);
});
</script>

<template>
    <Head title="Импорты" />
    <div class="users-workspace imports-page">
        <section class="users-list">
            <div class="content-breadcrumb">Обслуживание › Импорты</div>
            <MaintenanceTabs />
            <div class="page-heading">
                <h1>Импорты</h1>
                <TableSearchButton
                    :state="tableSearch"
                    :fields="advancedFields"
                    :rows="searchBaseRows"
                />
                <FilterPresetButton
                    :state="advancedFilters"
                    :fields="advancedFields"
                    :options="advancedOptions"
                    :rows="rows"
                />
            </div>
            <div class="sync-line" role="status">
                {{
                    syncing
                        ? "Обновляем состояние…"
                        : "Состояние импортов обновляется автоматически"
                }}
            </div>
            <p v-if="error" class="notice error" role="alert">{{ error }}</p>
            <FilterPresetTiles :state="advancedFilters" />
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
                            <th scope="col">Детализация ошибки</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="11" class="empty-state">
                                Загрузка импортов…
                            </td>
                        </tr>
                        <tr v-else-if="!visibleRows.length">
                            <td colspan="11" class="empty-state">
                                Импорты ещё не запускались
                            </td>
                        </tr>
                        <tr
                            v-for="row in visibleRows"
                            v-else
                            :key="`${row.row_type}-${row.id}`"
                            :data-table-search-id="tableSearch.identify(row)"
                            :class="{
                                'table-search-match':
                                    tableSearch.mode.value === 'highlight' &&
                                    tableSearch.matches(row),
                                'table-search-current':
                                    tableSearch.isCurrent(row),
                                'mobile-card-expanded': isMobileExpanded(row),
                                'stage-row': row.row_type === 'stage',
                            }"
                            @click="toggleMobileRow(row, $event)"
                        >
                            <td class="id-column">
                                <nobr>{{ row.id }}</nobr>
                            </td>
                            <td class="import-name-cell">
                                <div class="import-mobile-summary">
                                    <button
                                        v-if="hasChildren(row)"
                                        type="button"
                                        class="row-arrow"
                                        :aria-label="
                                            isRunExpanded(row)
                                                ? 'Свернуть этапы'
                                                : 'Развернуть этапы'
                                        "
                                        @click="toggleRun(row, $event)"
                                    >
                                        {{ isRunExpanded(row) ? "⌄" : "›" }}
                                    </button>
                                    <button
                                        type="button"
                                        class="name-button"
                                        @click="openName(row, $event)"
                                    >
                                        {{ row.row_type === "stage" ? "↳ " : ""
                                        }}{{ row.name }}
                                    </button>
                                    <span
                                        class="import-mobile-summary-updated"
                                        >{{ updatedTime(row) }}</span
                                    >
                                    <span class="import-mobile-summary-status"
                                        ><span
                                            class="badge"
                                            :class="statusClass(row.status)"
                                            >{{ statusLabel(row.status) }}</span
                                        ></span
                                    >
                                </div>
                            </td>
                            <td class="import-detail-cell" data-label="Клиент">
                                <span class="import-mobile-value">{{
                                    row.client_name ||
                                    (row.client_id ? `#${row.client_id}` : "—")
                                }}</span>
                            </td>
                            <td
                                class="import-detail-cell import-marketplace-cell"
                                data-label="Маркетплейс"
                            >
                                <span
                                    class="import-mobile-value import-marketplace-badge"
                                    :class="`marketplace-${marketplaceCode(row.marketplace)}`"
                                    >{{
                                        marketplaceLabel(row.marketplace)
                                    }}</span
                                >
                            </td>
                            <td
                                class="import-detail-cell"
                                data-label="Интеграция"
                            >
                                <span class="import-mobile-value"
                                    >#{{ row.webhook_id || "—"
                                    }}<small v-if="row.account_id"
                                        >Аккаунт #{{ row.account_id }}</small
                                    ></span
                                >
                            </td>
                            <td
                                class="import-detail-cell"
                                data-label="Дата и время старта"
                            >
                                <span class="import-mobile-value">{{
                                    formatDateInTimezone(
                                        row.started_at,
                                        "Europe/Moscow",
                                        true,
                                    )
                                }}</span>
                            </td>
                            <td
                                class="import-detail-cell"
                                data-label="Текущий этап"
                            >
                                <span class="import-mobile-value">{{
                                    row.current_stage_number
                                        ? `${row.current_stage_number}. ${row.current_stage_name}`
                                        : "—"
                                }}</span>
                            </td>
                            <td
                                class="import-detail-cell"
                                data-label="Прогресс"
                            >
                                <span class="import-mobile-value"
                                    ><ProgressBar :value="progress(row)"
                                /></span>
                            </td>
                            <td
                                class="import-detail-cell"
                                data-label="Обработано записей"
                            >
                                <span class="import-mobile-value">{{
                                    recordsText(row)
                                }}</span>
                            </td>
                            <td
                                class="import-detail-cell import-status-cell"
                                data-label="Статус"
                            >
                                <span class="import-mobile-value"
                                    ><span
                                        class="badge"
                                        :class="statusClass(row.status)"
                                        >{{ statusLabel(row.status) }}</span
                                    ></span
                                >
                            </td>
                            <td
                                class="import-detail-cell import-error-cell"
                                data-label="Детализация ошибки"
                            >
                                <span class="import-mobile-value">{{
                                    row.error_message || "—"
                                }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <footer class="list-footer">
                <span>Найдено: {{ total }}</span>
                <div>
                    <button
                        class="refresh-button"
                        :disabled="syncing"
                        @click="refresh"
                    >
                        Обновить
                    </button>
                    <button
                        :disabled="currentPage === 1"
                        aria-label="Предыдущая страница"
                        @click="changePage(currentPage - 1)"
                    >
                        ‹
                    </button>
                    <span>{{ currentPage }} / {{ lastPage }}</span>
                    <button
                        :disabled="currentPage === lastPage"
                        aria-label="Следующая страница"
                        @click="changePage(currentPage + 1)"
                    >
                        ›
                    </button>
                </div>
            </footer>
        </section>
    </div>
</template>

<style scoped>
.imports-page .page-heading {
    padding-bottom: 12px;
}
.imports-page .sync-line {
    padding-bottom: 14px;
}
.imports-page .table-scroll {
    flex: 1;
}
.imports-page .row-arrow {
    width: 24px;
    margin-right: 4px;
    border: 0;
    background: transparent;
    color: #1e892f;
    font-size: 19px;
    vertical-align: middle;
    cursor: pointer;
}
.imports-page .name-button {
    max-width: 280px;
    overflow: hidden;
    text-align: left;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.imports-page td small,
.imports-page .error-detail {
    display: block;
    margin-top: 4px;
    color: #667085;
    font-size: 10px;
    overflow-wrap: anywhere;
}
.imports-page .error-detail {
    color: #a32626;
}
.imports-page .list-footer .refresh-button {
    width: auto;
    padding: 0 8px;
    font-size: 12px;
}

@media (max-width: 900px) {
    .imports-workspace .table-scroll table tbody tr {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) !important;
        align-items: stretch !important;
        min-height: 0 !important;
        overflow: hidden !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr:not(.mobile-card-expanded)
        td {
        display: none !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr:not(.mobile-card-expanded)
        td.id-column,
    .imports-workspace
        .table-scroll
        table
        tbody
        tr:not(.mobile-card-expanded)
        td.import-name-cell,
    .imports-workspace
        .table-scroll
        table
        tbody
        tr:not(.mobile-card-expanded)
        td.import-marketplace-cell,
    .imports-workspace
        .table-scroll
        table
        tbody
        tr:not(.mobile-card-expanded)
        td.import-status-cell {
        display: flex !important;
        align-items: center !important;
        min-width: 0 !important;
        min-height: 44px !important;
        padding: 8px 10px !important;
        border: 0 !important;
        background: #fff !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr:not(.mobile-card-expanded)
        td.id-column {
        grid-column: 1 !important;
        grid-row: 1 !important;
        width: auto !important;
        padding-right: 4px !important;
        color: #0c1821 !important;
        font-size: 11px !important;
        font-weight: 600 !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr:not(.mobile-card-expanded)
        td.import-name-cell {
        grid-column: 1 / -1 !important;
        grid-row: 1 !important;
        width: auto !important;
        margin-left: 34px !important;
        padding-left: 4px !important;
        padding-right: 104px !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr:not(.mobile-card-expanded)
        td.import-name-cell
        .name-button {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
        max-width: none !important;
        overflow: hidden !important;
        padding: 0 !important;
        color: #0c1821 !important;
        font-weight: 600 !important;
        text-align: left !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr:not(.mobile-card-expanded)
        td.import-marketplace-cell {
        grid-column: 1 !important;
        grid-row: 2 !important;
        width: auto !important;
        justify-content: flex-start !important;
        padding-top: 0 !important;
        padding-left: 48px !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr:not(.mobile-card-expanded)
        td.import-status-cell {
        grid-column: 2 !important;
        grid-row: 2 !important;
        width: auto !important;
        justify-content: flex-end !important;
        padding-top: 0 !important;
    }

    .imports-workspace .table-scroll table tbody tr.mobile-card-expanded {
        background: #e1f3e7 !important;
        border-color: #95c59d !important;
    }

    .imports-workspace .table-scroll table tbody tr.mobile-card-expanded td {
        display: flex !important;
        width: auto !important;
        min-width: 0 !important;
        min-height: 58px !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        justify-content: center !important;
        gap: 5px !important;
        padding: 9px 10px !important;
        background: #e1f3e7 !important;
        border-color: #a8d4a9 !important;
        box-sizing: border-box !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr.mobile-card-expanded
        td.id-column,
    .imports-workspace
        .table-scroll
        table
        tbody
        tr.mobile-card-expanded
        td.import-name-cell {
        background: #fff !important;
        border-color: #95c59d !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr.mobile-card-expanded
        td.id-column {
        grid-column: 1 !important;
        grid-row: 1 !important;
        width: auto !important;
        min-height: 44px !important;
        align-items: center !important;
        justify-content: flex-start !important;
        padding-left: 10px !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr.mobile-card-expanded
        td.import-name-cell {
        grid-column: 2 !important;
        grid-row: 1 !important;
        width: auto !important;
        min-height: 44px !important;
        align-items: center !important;
        justify-content: flex-start !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr.mobile-card-expanded
        td.import-marketplace-cell {
        grid-column: 1 !important;
        grid-row: 2 !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr.mobile-card-expanded
        td.import-status-cell {
        grid-column: 2 !important;
        grid-row: 2 !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr.mobile-card-expanded
        td.import-detail-cell {
        width: auto !important;
        grid-column: auto !important;
        grid-row: auto !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr.mobile-card-expanded
        td.import-detail-cell::before {
        display: block !important;
        color: #1e892f !important;
        content: attr(data-label) !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        line-height: 1.3 !important;
    }

    .imports-workspace
        .table-scroll
        table
        tbody
        tr.mobile-card-expanded
        td.import-error-cell {
        grid-column: 1 / -1 !important;
        min-height: 72px !important;
    }

    .imports-workspace .import-mobile-value {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        overflow-wrap: anywhere !important;
    }

    .imports-workspace .import-marketplace-badge {
        width: auto !important;
        padding: 3px 8px !important;
        border-radius: 999px !important;
        background: #d3eedb !important;
        color: #176b2a !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: capitalize !important;
    }

    .imports-workspace .import-status-cell .import-mobile-value {
        width: auto !important;
    }

    .imports-workspace .import-error-cell .import-mobile-value {
        color: #a32626 !important;
        font-size: 12px !important;
        line-height: 1.4 !important;
    }
}
</style>
