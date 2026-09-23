<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch } from "vue";
import MaintenanceTabs from "../Components/MaintenanceTabs.vue";
import { formatDateInTimezone } from "../lib/dates";
import { http, HttpError } from "../lib/http";
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
import { clientRecordUrl } from "../lib/recordLinks";

type GroupBy = "clients" | "webhooks";
type MarketplaceFilter =
    "all" | "none" | "wildberries" | "ozon" | "yandex_market";
type Period =
    | "today"
    | "yesterday"
    | "week"
    | "month"
    | "hours_4"
    | "hour"
    | "minutes_15";
type BucketUnit = "day" | "hour" | "minutes_15" | "minute";

interface ActivityPoint {
    start: string;
    count: number;
    successful_count: number;
    failed_count: number;
}

interface StatisticsRow {
    id: string;
    name: string;
    total_runs: number;
    successful_runs: number;
    has_successful_runs: number;
    failed_runs: number;
    running_runs: number;
    without_runs: number;
    activity: ActivityPoint[];
}

interface DisplayPoint extends ActivityPoint {
    state: "empty" | "success" | "mixed" | "failed" | "other";
    level: number;
    title: string;
}

interface RunRow {
    id: string;
    entity_id: string;
    created_at: string | null;
    status: string;
    processed_records: number;
}

const groups: { value: GroupBy; label: string }[] = [
    { value: "clients", label: "Клиенты" },
    { value: "webhooks", label: "Вебхуки" },
];
const periods: { value: Period; label: string }[] = [
    { value: "today", label: "Сегодня" },
    { value: "yesterday", label: "Вчера" },
    { value: "week", label: "Неделя" },
    { value: "month", label: "Месяц" },
    { value: "hours_4", label: "4 часа" },
    { value: "hour", label: "Час" },
    { value: "minutes_15", label: "15 минут" },
];
const marketplaces: { value: MarketplaceFilter; label: string }[] = [
    { value: "all", label: "Все маркетплейсы" },
    { value: "wildberries", label: "Wildberries" },
    { value: "ozon", label: "Ozon" },
    { value: "yandex_market", label: "Яндекс Маркет" },
    { value: "none", label: "Без маркетплейса" },
];

const groupBy = ref<GroupBy>("clients");
const advancedFilters = useFilterPresets("background_processes");
const period = ref<Period>("today");
const marketplace = ref<MarketplaceFilter>("all");
const summary = ref<StatisticsRow | null>(null);
const loading = ref(true);
const error = ref("");
const statisticsFrom = ref("");
const statisticsTo = ref("");
const bucketUnit = ref<BucketUnit>("hour");
const selectedPoint = ref<DisplayPoint | null>(null);
const runs = ref<RunRow[]>([]);
const loadingRuns = ref(false);
const runsPage = ref(1);
const runsLastPage = ref(1);
const runsTotal = ref(0);
let loadRequest = 0;
let runsRequest = 0;

const statusLabels: Record<string, string> = {
    queued: "В очереди",
    running: "Выполняется",
    completed: "Завершён",
    failed: "Ошибка",
};
const advancedFields = computed<FilterField[]>(() => [
    { id: "id", label: "# запуска", type: "number" },
    {
        id: "entity_id",
        label: groupBy.value === "clients" ? "ID клиента" : "ID вебхука",
        type: "number",
    },
    { id: "created_at", label: "Дата и время запуска", type: "date" },
    {
        id: "status",
        label: "Статус",
        type: "tuple",
        format: (value) => statusLabels[String(value)] ?? String(value),
    },
    { id: "processed_records", label: "Обработано записей", type: "number" },
]);
const tableSearch = useTableSearch(
    "background_processes",
    () => advancedFields.value,
);
const advancedOptions: FilterOptions = { status: Object.keys(statusLabels) };
const entityHeading = computed(() =>
    groupBy.value === "clients" ? "ID клиента" : "ID вебхука",
);

const displayActivity = computed(() => {
    if (!summary.value || !statisticsFrom.value || !statisticsTo.value) {
        return [];
    }

    const points = new Map(
        summary.value.activity.map((point) => [
            new Date(point.start).getTime(),
            point,
        ]),
    );
    const max = Math.max(
        0,
        ...summary.value.activity.map((point) => point.count),
    );
    const result: DisplayPoint[] = [];
    const step = unitMilliseconds();

    for (
        let start = new Date(statisticsFrom.value).getTime(),
            end = new Date(statisticsTo.value).getTime();
        start < end;
        start += step
    ) {
        const point = points.get(start);
        const count = point?.count ?? 0;
        const successfulCount = point?.successful_count ?? 0;
        const failedCount = point?.failed_count ?? 0;
        const state =
            count === 0
                ? "empty"
                : successfulCount === count
                  ? "success"
                  : failedCount === count
                    ? "failed"
                    : successfulCount > 0 && failedCount > 0
                      ? "mixed"
                      : "other";
        result.push({
            start: new Date(start).toISOString(),
            count,
            successful_count: successfulCount,
            failed_count: failedCount,
            state,
            level:
                count === 0 || max === 0
                    ? 0
                    : Math.max(1, Math.ceil((count / max) * 4)),
            title: `${pointTitle(start, count)} · успешно: ${successfulCount}, ошибок: ${failedCount}`,
        });
    }

    return result;
});

function unitMilliseconds(): number {
    return bucketUnit.value === "day"
        ? 86_400_000
        : bucketUnit.value === "hour"
          ? 3_600_000
          : bucketUnit.value === "minutes_15"
            ? 900_000
            : 60_000;
}

function runsWord(count: number): string {
    const lastTwo = count % 100;
    const last = count % 10;
    if (lastTwo >= 11 && lastTwo <= 14) return "запусков";
    if (last === 1) return "запуск";
    if (last >= 2 && last <= 4) return "запуска";
    return "запусков";
}

function pointTitle(start: number, count: number): string {
    const withTime = bucketUnit.value !== "day";
    const from = formatDateInTimezone(
        new Date(start).toISOString(),
        "Europe/Moscow",
        withTime,
    );
    const to = formatDateInTimezone(
        new Date(start + unitMilliseconds()).toISOString(),
        "Europe/Moscow",
        withTime,
    );

    return `${from} — ${to}: ${count} ${runsWord(count)}`;
}

async function load(): Promise<void> {
    const request = ++loadRequest;
    runsRequest++;
    loading.value = true;
    selectedPoint.value = null;
    runs.value = [];
    runsPage.value = 1;
    runsLastPage.value = 1;
    runsTotal.value = 0;

    try {
        const query = new URLSearchParams({
            group_by: groupBy.value,
            period: period.value,
            marketplace: marketplace.value,
        });
        appendTableSearch(query, tableSearch);
        const filter = serializedFilter(advancedFilters.combined.value);
        if (filter) query.set("filter", filter);
        const response = await http(
            `/web/background_processes?${query.toString()}`,
        );
        if (request !== loadRequest) return;

        summary.value = response.data?.[0] ?? null;
        statisticsFrom.value = response.statistics_from ?? "";
        statisticsTo.value = response.statistics_to ?? "";
        bucketUnit.value = response.bucket_unit ?? "hour";
        error.value = "";
    } catch (exception) {
        if (request !== loadRequest) return;
        error.value =
            exception instanceof HttpError
                ? exception.message
                : "Не удалось загрузить статистику фоновых процессов.";
    } finally {
        if (request === loadRequest) loading.value = false;
    }
}

async function loadRuns(point: DisplayPoint, page = 1): Promise<void> {
    const request = ++runsRequest;
    selectedPoint.value = point;
    loadingRuns.value = true;
    if (page === 1) {
        runs.value = [];
        runsPage.value = 1;
        runsTotal.value = 0;
    }

    try {
        const query = new URLSearchParams({
            group_by: groupBy.value,
            period: period.value,
            marketplace: marketplace.value,
            bucket_start: point.start,
            page: String(page),
        });
        appendTableSearch(query, tableSearch);
        const filter = serializedFilter(advancedFilters.combined.value);
        if (filter) query.set("filter", filter);
        const response = await http(
            `/web/background_processes/runs?${query.toString()}`,
        );
        if (request !== runsRequest) return;

        runs.value =
            page === 1 ? response.data : [...runs.value, ...response.data];
        runsPage.value = Number(response.current_page ?? page);
        runsLastPage.value = Number(response.last_page ?? 1);
        runsTotal.value = Number(response.total ?? runs.value.length);
        error.value = "";
    } catch (exception) {
        if (request !== runsRequest) return;
        error.value =
            exception instanceof HttpError
                ? exception.message
                : "Не удалось загрузить запуски выбранного интервала.";
    } finally {
        if (request === runsRequest) loadingRuns.value = false;
    }
}

function formatRunDate(value: string | null): string {
    return value ? formatDateInTimezone(value, "Europe/Moscow", true) : "—";
}

function entityUrl(id: string): string {
    return clientRecordUrl(
        groupBy.value === "clients" ? "clients" : "integrations",
        id,
    );
}

function selectGroup(value: GroupBy): void {
    if (groupBy.value === value) return;
    groupBy.value = value;
    void load();
}

function selectPeriod(value: Period): void {
    if (period.value === value) return;
    period.value = value;
    void load();
}

onMounted(() => {
    void load();
});
watch(
    () => advancedFilters.combined.value,
    () => void load(),
    { deep: true },
);
watch(
    [tableSearch.debouncedQuery, tableSearch.mode, tableSearch.selectedFields],
    () => void load(),
    { deep: true },
);
</script>

<template>
    <Head title="Фоновые процессы" />
    <div class="users-workspace background-processes-page">
        <section class="users-list">
            <div class="content-breadcrumb">
                Обслуживание › Фоновые процессы
            </div>
            <MaintenanceTabs />
            <div class="page-heading">
                <h1>Фоновые процессы</h1>
                <TableSearchButton
                    :state="tableSearch"
                    :fields="advancedFields"
                    :rows="runs"
                />
                <FilterPresetButton
                    :state="advancedFilters"
                    :fields="advancedFields"
                    :options="advancedOptions"
                    :rows="runs"
                />
            </div>

            <div class="process-filters">
                <div class="process-control-group">
                    <span class="process-control-label">Группировка</span>
                    <div
                        class="segmented-control"
                        aria-label="Группировка статистики"
                    >
                        <button
                            v-for="item in groups"
                            :key="item.value"
                            type="button"
                            :class="{ active: groupBy === item.value }"
                            :aria-pressed="groupBy === item.value"
                            @click="selectGroup(item.value)"
                        >
                            {{ item.label }}
                        </button>
                    </div>
                    <select
                        v-model="groupBy"
                        class="mobile-process-select"
                        aria-label="Группировка статистики"
                        @change="load()"
                    >
                        <option
                            v-for="item in groups"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </option>
                    </select>
                </div>

                <div class="process-control-group">
                    <span class="process-control-label">Период</span>
                    <div
                        class="segmented-control"
                        aria-label="Период статистики"
                    >
                        <button
                            v-for="item in periods"
                            :key="item.value"
                            type="button"
                            :class="{ active: period === item.value }"
                            :aria-pressed="period === item.value"
                            @click="selectPeriod(item.value)"
                        >
                            {{ item.label }}
                        </button>
                    </div>
                    <select
                        v-model="period"
                        class="mobile-process-select"
                        aria-label="Период статистики"
                        @change="load()"
                    >
                        <option
                            v-for="item in periods"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </option>
                    </select>
                </div>

                <div class="process-control-group">
                    <span class="process-control-label">Маркетплейс</span>
                    <select
                        v-model="marketplace"
                        class="marketplace-filter-select"
                        aria-label="Фильтр маркетплейса"
                        @change="load()"
                    >
                        <option
                            v-for="item in marketplaces"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </option>
                    </select>
                </div>
            </div>
            <FilterPresetTiles :state="advancedFilters" />

            <p v-if="error" class="notice error" role="alert">{{ error }}</p>
            <div v-if="loading" class="process-state" role="status">
                Загрузка статистики…
            </div>
            <div v-else-if="summary" class="process-summary">
                <div class="summary-title">{{ summary.name }}</div>
                <div class="summary-metrics">
                    <article class="summary-metric success">
                        <span>Успешно</span>
                        <strong>{{ summary.successful_runs }}</strong>
                    </article>
                    <article class="summary-metric has-success">
                        <span>Есть успешные</span>
                        <strong>{{ summary.has_successful_runs }}</strong>
                    </article>
                    <article class="summary-metric failed">
                        <span>Неуспешно</span>
                        <strong>{{ summary.failed_runs }}</strong>
                    </article>
                    <article class="summary-metric running">
                        <span>Выполняются</span>
                        <strong>{{ summary.running_runs }}</strong>
                    </article>
                    <article class="summary-metric without-runs">
                        <span>Без запусков</span>
                        <strong>{{ summary.without_runs }}</strong>
                    </article>
                </div>

                <section class="activity-card" aria-labelledby="activity-title">
                    <div class="activity-heading">
                        <h2 id="activity-title">Календарь активности</h2>
                        <span>Запусков всего: {{ summary.total_runs }}</span>
                    </div>
                    <div
                        class="activity-scroll"
                        tabindex="0"
                        aria-label="Активность за выбранный период"
                    >
                        <div class="activity-grid">
                            <button
                                v-for="point in displayActivity"
                                :key="point.start"
                                type="button"
                                class="activity-point"
                                :class="[
                                    `activity-${point.state}`,
                                    `level-${point.level}`,
                                    {
                                        selected:
                                            selectedPoint?.start ===
                                            point.start,
                                    },
                                ]"
                                :title="point.title"
                                :aria-label="point.title"
                                :aria-pressed="
                                    selectedPoint?.start === point.start
                                "
                                @click="loadRuns(point)"
                            />
                        </div>
                    </div>
                    <div class="activity-legend" aria-label="Цвета календаря">
                        <span><i class="activity-success" />Успешные</span>
                        <span><i class="activity-mixed" />Успехи и ошибки</span>
                        <span><i class="activity-failed" />Ошибки</span>
                        <span
                            ><i class="activity-other" />Без итогового
                            результата</span
                        >
                    </div>
                </section>

                <section
                    v-if="selectedPoint"
                    class="runs-card"
                    aria-labelledby="runs-title"
                >
                    <div class="runs-heading">
                        <h2 id="runs-title">Запуски выбранного интервала</h2>
                        <span>{{ runsTotal }}</span>
                    </div>
                    <p class="runs-period">{{ selectedPoint.title }}</p>
                    <p v-if="loadingRuns && runsPage === 1" class="runs-state">
                        Загрузка запусков…
                    </p>
                    <p v-else-if="!runs.length" class="runs-state">
                        В этом интервале запусков не было.
                    </p>
                    <div v-else class="runs-table-scroll">
                        <table class="runs-table">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">{{ entityHeading }}</th>
                                    <th scope="col">Дата и время запуска</th>
                                    <th scope="col">Статус</th>
                                    <th scope="col">Обработано записей</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="run in runs"
                                    :key="run.id"
                                    :data-table-search-id="
                                        tableSearch.identify(run)
                                    "
                                    :class="{
                                        'table-search-match':
                                            tableSearch.mode.value ===
                                                'highlight' &&
                                            tableSearch.matches(run),
                                        'table-search-current':
                                            tableSearch.isCurrent(run),
                                    }"
                                >
                                    <td>{{ run.id }}</td>
                                    <td>
                                        <Link
                                            class="run-entity-link"
                                            :href="entityUrl(run.entity_id)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {{ run.entity_id }}
                                        </Link>
                                    </td>
                                    <td>{{ formatRunDate(run.created_at) }}</td>
                                    <td>
                                        <span
                                            class="run-status"
                                            :class="`status-${run.status}`"
                                        >
                                            {{
                                                statusLabels[run.status] ??
                                                run.status
                                            }}
                                        </span>
                                    </td>
                                    <td>{{ run.processed_records }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button
                        v-if="runsPage < runsLastPage"
                        type="button"
                        class="more-runs"
                        :disabled="loadingRuns"
                        @click="loadRuns(selectedPoint, runsPage + 1)"
                    >
                        Показать ещё
                    </button>
                </section>
            </div>
            <div v-else class="process-state">
                Данные фоновых процессов недоступны
            </div>
        </section>
    </div>
</template>

<style scoped>
.background-processes-page .page-heading {
    padding-bottom: 12px;
}
.process-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 18px;
    padding-bottom: 16px;
}
.process-control-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.process-control-label {
    color: #66756f;
    font-size: 11px;
    font-weight: 600;
}
.segmented-control {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 2px;
    padding: 3px;
    border: 1px solid #dbe3e0;
    border-radius: 9px;
    background: #f4f7f6;
}
.segmented-control button {
    min-height: 32px;
    padding: 6px 12px;
    border: 0;
    border-radius: 6px;
    color: #52615d;
    background: transparent;
    font-size: 12px;
    cursor: pointer;
}
.segmented-control button.active {
    color: #fff;
    background: #1e892f;
    box-shadow: 0 1px 3px rgba(25, 91, 43, 0.2);
}
.mobile-process-select {
    display: none;
}
.marketplace-filter-select {
    min-width: 170px;
    height: 40px;
    padding: 5px 30px 5px 10px;
    border: 1px solid #dbe3e0;
    border-radius: 9px;
    background-color: #f4f7f6;
    color: #18251f;
    font: inherit;
    font-size: 12px;
}
.process-state,
.process-summary {
    border: 1px solid #dce7e2;
    border-radius: 12px;
    background: #fff;
}
.process-state {
    display: grid;
    min-height: 180px;
    place-items: center;
    color: #66756f;
}
.process-summary {
    overflow: hidden;
}
.summary-title {
    padding: 12px 16px;
    border-bottom: 1px solid #e6eeea;
    color: #18251f;
    font-size: 15px;
    font-weight: 700;
}
.summary-metrics {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 12px;
    padding: 16px;
}
.summary-metric {
    display: flex;
    min-width: 0;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 14px 16px;
    border: 1px solid #dce7e2;
    border-radius: 10px;
    background: #f8faf9;
}
.summary-metric span {
    color: #66756f;
    font-size: 12px;
    font-weight: 600;
}
.summary-metric strong {
    font-size: 22px;
    font-variant-numeric: tabular-nums;
}
.summary-metric.success strong {
    color: #1e892f;
}
.summary-metric.has-success strong {
    color: #2274a5;
}
.summary-metric.failed strong {
    color: #b42318;
}
.summary-metric.running strong {
    color: #a15c00;
}
.summary-metric.without-runs strong {
    color: #66756f;
}
.activity-card {
    padding: 16px;
    border-top: 1px solid #e6eeea;
}
.activity-heading {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}
.activity-heading h2 {
    margin: 0;
    color: #18251f;
    font-size: 14px;
}
.activity-heading span {
    color: #66756f;
    font-size: 11px;
}
.activity-scroll {
    width: 100%;
    max-width: 100%;
    overflow: visible;
    padding: 3px 1px 7px;
    outline-offset: 2px;
    scrollbar-width: thin;
}
.activity-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 3px;
    width: 100%;
}
.activity-point {
    flex: 0 0 10px;
    width: 10px;
    height: 10px;
    padding: 0;
    border: 1px solid #e0e6e3;
    border-radius: 2px;
    background: #eef2f0;
    cursor: pointer;
}
.activity-point.selected {
    outline: 2px solid #2274a5;
    outline-offset: 2px;
}
.activity-success {
    border-color: #1e892f;
    background: #238f36;
}
.activity-success.level-1 {
    border-color: #c5e5ca;
    background: #ccebd1;
}
.activity-success.level-2 {
    border-color: #8fcf99;
    background: #99d7a3;
}
.activity-success.level-3 {
    border-color: #51ad62;
    background: #5fba70;
}
.activity-success.level-4 {
    border-color: #1e892f;
    background: #238f36;
}
.activity-mixed {
    border-color: #d69e00;
    background: #f4c542;
}
.activity-failed {
    border-color: #b42318;
    background: #d92d20;
}
.activity-other {
    border-color: #8da1aa;
    background: #aab9bf;
}
.activity-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 7px 14px;
    margin-top: 8px;
    color: #66756f;
    font-size: 10px;
}
.activity-legend span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.activity-legend i {
    width: 10px;
    height: 10px;
    border: 1px solid;
    border-radius: 2px;
}
.runs-card {
    padding: 16px;
    border-top: 1px solid #e6eeea;
}
.runs-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.runs-heading h2 {
    margin: 0;
    color: #18251f;
    font-size: 14px;
}
.runs-heading span,
.runs-period {
    color: #66756f;
    font-size: 11px;
}
.runs-period {
    margin: 4px 0 12px;
}
.runs-state {
    margin: 14px 0 0;
    color: #66756f;
    font-size: 12px;
}
.runs-table-scroll {
    max-width: 100%;
    overflow-x: auto;
}
.runs-table {
    width: 100%;
    min-width: 620px;
    border-collapse: collapse;
}
.runs-table th,
.runs-table td {
    padding: 9px 10px;
    border-bottom: 1px solid #e6eeea;
    text-align: left;
    white-space: nowrap;
}
.runs-table th {
    color: #66756f;
    font-size: 11px;
    font-weight: 600;
}
.runs-table td {
    color: #18251f;
    font-size: 12px;
}
.run-entity-link {
    color: #2274a5;
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.run-entity-link:hover {
    color: #1e892f;
}
.run-status {
    display: inline-flex;
    padding: 3px 7px;
    border-radius: 999px;
    background: #eef2f0;
}
.run-status.status-completed {
    color: #1e892f;
    background: #e1f3e7;
}
.run-status.status-failed {
    color: #b42318;
    background: #fdecea;
}
.run-status.status-queued,
.run-status.status-running {
    color: #a15c00;
    background: #fff3dc;
}
.more-runs {
    margin-top: 12px;
    padding: 7px 12px;
    border: 1px solid #bcdfe7;
    border-radius: 7px;
    color: #2274a5;
    background: #fff;
}

@media (max-width: 900px) {
    .process-filters {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }
    .process-control-group {
        min-width: 0;
        gap: 3px;
    }
    .process-control-label {
        font-size: 9px;
    }
    .segmented-control {
        display: none;
    }
    .mobile-process-select {
        display: block;
        width: 100%;
        min-width: 0;
        height: 34px;
        padding: 5px 28px 5px 9px;
        border: 1px solid #dbe3e0;
        border-radius: 8px;
        background-color: #f4f7f6;
        color: #18251f;
        font: inherit;
        font-size: 12px;
    }
    .marketplace-filter-select {
        display: block;
        width: 100%;
        min-width: 0;
        height: 34px;
        padding: 5px 24px 5px 7px;
        border-radius: 8px;
        font-size: 11px;
    }
    .summary-title {
        padding: 10px 12px;
        font-size: 13px;
    }
    .summary-metrics {
        gap: 4px;
        padding: 8px;
    }
    .summary-metric {
        min-height: 62px;
        flex-direction: column;
        justify-content: center;
        gap: 3px;
        padding: 7px 2px;
        text-align: center;
    }
    .summary-metric span {
        font-size: 8px;
        line-height: 1.15;
    }
    .summary-metric strong {
        font-size: 18px;
    }
    .activity-card {
        padding: 12px 10px;
    }
    .activity-heading {
        margin-bottom: 9px;
    }
    .activity-heading h2 {
        font-size: 12px;
    }
    .activity-heading span {
        font-size: 9px;
    }
}
</style>
