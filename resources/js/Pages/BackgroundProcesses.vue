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

const groupBy = ref<GroupBy>("clients");
const period = ref<Period>("today");
const summary = ref<StatisticsRow | null>(null);
const loading = ref(true);
const error = ref("");
const statisticsFrom = ref("");
const statisticsTo = ref("");
const bucketUnit = ref<BucketUnit>("hour");
let loadRequest = 0;

const displayActivity = computed(() => {
    if (!summary.value || !statisticsFrom.value || !statisticsTo.value) {
        return [];
    }

    const counts = new Map(
        summary.value.activity.map((point) => [
            new Date(point.start).getTime(),
            point.count,
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
});

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

async function load(): Promise<void> {
    const request = ++loadRequest;
    loading.value = true;

    try {
        const query = new URLSearchParams({
            group_by: groupBy.value,
            period: period.value,
        });
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
            </div>

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
                    <article class="summary-metric failed">
                        <span>Неуспешно</span>
                        <strong>{{ summary.failed_runs }}</strong>
                    </article>
                    <article class="summary-metric running">
                        <span>Выполняются</span>
                        <strong>{{ summary.running_runs }}</strong>
                    </article>
                    <article class="summary-metric without-runs">
                        <span>Без запуска</span>
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
                            <span
                                v-for="point in displayActivity"
                                :key="point.start"
                                class="activity-point"
                                :class="`level-${point.level}`"
                                :title="point.title"
                                :aria-label="point.title"
                            />
                        </div>
                    </div>
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
    grid-template-columns: repeat(4, minmax(0, 1fr));
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
    max-width: 100%;
    overflow-x: auto;
    padding: 3px 1px 7px;
    outline-offset: 2px;
    scrollbar-width: thin;
}
.activity-grid {
    display: flex;
    gap: 3px;
    width: max-content;
}
.activity-point {
    flex: 0 0 10px;
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
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
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
