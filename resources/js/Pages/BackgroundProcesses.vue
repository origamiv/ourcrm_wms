<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { computed, onMounted, ref } from "vue";
import MaintenanceTabs from "../Components/MaintenanceTabs.vue";
import { formatDateInTimezone } from "../lib/dates";
import { http, HttpError } from "../lib/http";

type GroupBy = "clients" | "webhooks";
type Period =
    | "today"
    | "yesterday"
    | "week"
    | "month"
    | "hours_4"
    | "hour"
    | "minutes_15";
type BucketUnit = "day" | "hour" | "minute";

interface ActivityPoint {
    start: string;
    count: number;
}

interface StatisticsRow {
    id: string;
    name: string;
    total_runs: number;
    successful_runs: number;
    failed_runs: number;
    running_runs: number;
    without_runs: number;
    activity: ActivityPoint[];
}

interface DisplayPoint extends ActivityPoint {
    level: number;
    title: string;
}

const groups: { value: GroupBy; label: string }[] = [
    { value: "clients", label: "По клиентам" },
    { value: "webhooks", label: "По вебхукам" },
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

const groupBy = ref<GroupBy>("clients");
const period = ref<Period>("today");
const rows = ref<StatisticsRow[]>([]);
const loading = ref(true);
const error = ref("");
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const statisticsFrom = ref("");
const statisticsTo = ref("");
const bucketUnit = ref<BucketUnit>("hour");
const expandedRows = ref<Set<string>>(new Set());
let loadRequest = 0;

const groupHeading = computed(() =>
    groupBy.value === "clients" ? "Клиент" : "Вебхук",
);
const gridRows = computed(() =>
    bucketUnit.value === "day" ? 7 : bucketUnit.value === "hour" ? 24 : 15,
);

function unitMilliseconds(): number {
    return bucketUnit.value === "day"
        ? 86_400_000
        : bucketUnit.value === "hour"
          ? 3_600_000
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

function activityFor(row: StatisticsRow): DisplayPoint[] {
    if (!statisticsFrom.value || !statisticsTo.value) return [];
    const counts = new Map(
        row.activity.map((point) => [
            new Date(point.start).getTime(),
            point.count,
        ]),
    );
    const max = Math.max(0, ...row.activity.map((point) => point.count));
    const result: DisplayPoint[] = [];
    const step = unitMilliseconds();
    for (
        let start = new Date(statisticsFrom.value).getTime(),
            end = new Date(statisticsTo.value).getTime();
        start < end;
        start += step
    ) {
        const count = counts.get(start) ?? 0;
        result.push({
            start: new Date(start).toISOString(),
            count,
            level:
                count === 0 || max === 0
                    ? 0
                    : Math.max(1, Math.ceil((count / max) * 4)),
            title: pointTitle(start, count),
        });
    }
    return result;
}

async function load(page = 1): Promise<void> {
    const request = ++loadRequest;
    loading.value = true;
    try {
        const query = new URLSearchParams({
            group_by: groupBy.value,
            period: period.value,
            page: String(page),
        });
        const response = await http(
            `/web/background_processes?${query.toString()}`,
        );
        if (request !== loadRequest) return;
        rows.value = response.data ?? [];
        statisticsFrom.value = response.statistics_from ?? "";
        statisticsTo.value = response.statistics_to ?? "";
        bucketUnit.value = response.bucket_unit ?? "hour";
        currentPage.value = Number(response.current_page ?? page);
        lastPage.value = Math.max(1, Number(response.last_page ?? 1));
        total.value = Number(response.total ?? rows.value.length);
        expandedRows.value = new Set();
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

function selectGroup(value: GroupBy): void {
    if (groupBy.value === value) return;
    groupBy.value = value;
    void load(1);
}

function selectPeriod(value: Period): void {
    if (period.value === value) return;
    period.value = value;
    void load(1);
}

function changePage(page: number): void {
    if (page < 1 || page > lastPage.value || page === currentPage.value) return;
    void load(page);
}

function toggleRow(id: string): void {
    if (!window.matchMedia("(max-width: 900px)").matches) return;
    const next = new Set(expandedRows.value);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    expandedRows.value = next;
}

onMounted(() => {
    void load();
});
</script>

<template>
    <Head title="Фоновые процессы" />
    <div class="users-workspace background-processes-page">
        <section class="users-list">
            <div class="content-breadcrumb">
                Обслуживание › Фоновые процессы
            </div>
            <MaintenanceTabs />
            <div class="page-heading"><h1>Фоновые процессы</h1></div>

            <div class="process-filters">
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
                <div
                    class="segmented-control period-control"
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
            </div>

            <p v-if="error" class="notice error" role="alert">{{ error }}</p>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th scope="col" class="id-column">#</th>
                            <th scope="col">{{ groupHeading }}</th>
                            <th scope="col">Всего запусков</th>
                            <th scope="col">Успешных</th>
                            <th scope="col">Неуспешных</th>
                            <th scope="col">Выполняются</th>
                            <th scope="col">Без запусков</th>
                            <th scope="col" class="statistics-heading">
                                Статистика
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="8" class="empty-state">
                                Загрузка статистики…
                            </td>
                        </tr>
                        <tr v-else-if="!rows.length">
                            <td colspan="8" class="empty-state">
                                Активные фоновые процессы не найдены
                            </td>
                        </tr>
                        <tr
                            v-for="row in rows"
                            v-else
                            :key="row.id"
                            :class="{
                                'mobile-card-expanded': expandedRows.has(
                                    row.id,
                                ),
                            }"
                            @click="toggleRow(row.id)"
                        >
                            <td class="id-column">
                                <nobr>{{ row.id }}</nobr>
                            </td>
                            <td class="process-name" :data-label="groupHeading">
                                <button
                                    type="button"
                                    @click.stop="toggleRow(row.id)"
                                >
                                    {{ row.name }}
                                </button>
                            </td>
                            <td class="metric-cell" data-label="Всего запусков">
                                {{ row.total_runs }}
                            </td>
                            <td
                                class="metric-cell success"
                                data-label="Успешных"
                            >
                                {{ row.successful_runs }}
                            </td>
                            <td
                                class="metric-cell failed"
                                data-label="Неуспешных"
                            >
                                {{ row.failed_runs }}
                            </td>
                            <td
                                class="metric-cell running"
                                data-label="Выполняются"
                            >
                                {{ row.running_runs }}
                            </td>
                            <td
                                class="metric-cell muted-count"
                                data-label="Без запусков"
                            >
                                {{ row.without_runs }}
                            </td>
                            <td class="statistics-cell" data-label="Статистика">
                                <div
                                    class="activity-scroll"
                                    tabindex="0"
                                    aria-label="Активность за три периода"
                                >
                                    <div
                                        class="activity-grid"
                                        :style="{
                                            gridTemplateRows: `repeat(${gridRows}, 10px)`,
                                        }"
                                    >
                                        <span
                                            v-for="point in activityFor(row)"
                                            :key="point.start"
                                            class="activity-point"
                                            :class="`level-${point.level}`"
                                            :title="point.title"
                                            :aria-label="point.title"
                                        />
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <footer class="list-footer">
                <span>Найдено: {{ total }}</span>
                <div>
                    <button
                        :disabled="currentPage === 1 || loading"
                        aria-label="Предыдущая страница"
                        @click="changePage(currentPage - 1)"
                    >
                        ‹
                    </button>
                    <span>{{ currentPage }} / {{ lastPage }}</span>
                    <button
                        :disabled="currentPage === lastPage || loading"
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
.background-processes-page .page-heading {
    padding-bottom: 12px;
}
.process-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 18px;
    padding: 0 0 16px;
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
.process-name button {
    max-width: 260px;
    overflow: hidden;
    padding: 0;
    border: 0;
    background: transparent;
    color: #18251f;
    font-weight: 600;
    text-align: left;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.metric-cell {
    text-align: center;
    font-variant-numeric: tabular-nums;
    font-weight: 650;
}
.metric-cell.success {
    color: #1e892f;
}
.metric-cell.failed {
    color: #b42318;
}
.metric-cell.running {
    color: #a15c00;
}
.metric-cell.muted-count {
    color: #66756f;
}
.statistics-heading {
    min-width: 210px;
}
.statistics-cell {
    width: 36%;
    min-width: 210px;
}
.activity-scroll {
    max-width: 100%;
    overflow-x: auto;
    padding: 3px 1px 7px;
    outline-offset: 2px;
    scrollbar-width: thin;
}
.activity-grid {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: 10px;
    gap: 3px;
    width: max-content;
}
.activity-point {
    width: 10px;
    height: 10px;
    border: 1px solid #e0e6e3;
    border-radius: 2px;
    background: #eef2f0;
}
.activity-point.level-1 {
    border-color: #c5e5ca;
    background: #ccebd1;
}
.activity-point.level-2 {
    border-color: #8fcf99;
    background: #99d7a3;
}
.activity-point.level-3 {
    border-color: #51ad62;
    background: #5fba70;
}
.activity-point.level-4 {
    border-color: #1e892f;
    background: #238f36;
}

@media (max-width: 900px) {
    .process-filters {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
    .segmented-control {
        width: max-content;
        flex-wrap: nowrap;
    }
    .period-control {
        margin-top: 8px;
    }
    .background-processes-page .table-scroll {
        overflow-x: visible;
    }
    .background-processes-page table,
    .background-processes-page tbody {
        display: block;
        width: 100%;
    }
    .background-processes-page thead {
        display: none;
    }
    .background-processes-page tbody tr {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        margin-bottom: 8px;
        overflow: hidden;
        border: 1px solid #e0e7e4;
        border-radius: 9px;
        background: #fff;
    }
    .background-processes-page tbody tr > td {
        display: none;
        width: auto;
        border: 0;
    }
    .background-processes-page tbody tr > td.empty-state {
        display: block;
        grid-column: 1 / -1;
    }
    .background-processes-page tbody tr > .id-column,
    .background-processes-page tbody tr > .process-name {
        display: flex;
        align-items: center;
        min-width: 0;
        padding: 11px 8px;
    }
    .background-processes-page tbody tr > .process-name button {
        width: 100%;
        max-width: none;
    }
    .background-processes-page tbody tr.mobile-card-expanded {
        border-color: #95c59d;
    }
    .background-processes-page tbody tr.mobile-card-expanded > td {
        display: flex;
        grid-column: 1 / -1;
        align-items: center;
        justify-content: space-between;
        min-width: 0;
        padding: 10px 12px;
        border-top: 1px solid #d8eadc;
        background: #edf8f0;
    }
    .background-processes-page tbody tr.mobile-card-expanded > .id-column {
        grid-column: 1;
        border-top: 0;
        background: #fff;
    }
    .background-processes-page tbody tr.mobile-card-expanded > .process-name {
        grid-column: 2;
        border-top: 0;
        background: #fff;
    }
    .background-processes-page
        tbody
        tr.mobile-card-expanded
        > td[data-label]::before {
        flex: 0 0 132px;
        color: #397047;
        font-size: 12px;
        font-weight: 500;
        content: attr(data-label);
    }
    .background-processes-page
        tbody
        tr.mobile-card-expanded
        > .process-name::before {
        display: none;
    }
    .background-processes-page
        tbody
        tr.mobile-card-expanded
        > .statistics-cell {
        display: block;
    }
    .background-processes-page
        tbody
        tr.mobile-card-expanded
        > .statistics-cell::before {
        display: block;
        margin-bottom: 9px;
    }
    .activity-scroll {
        max-width: 100%;
    }
}
</style>
