<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import ClientTabs from "../Components/ClientTabs.vue";
import { formatDate } from "../lib/dates";
import { http } from "../lib/http";

type DayCount = { date: string; count: number };
type RunRow = {
    id: string;
    created_at: string | null;
    status: string;
    processed_records: number;
};
type CalendarCell = {
    date: string;
    inYear: boolean;
    future: boolean;
    count: number;
    level: number;
};

const page = usePage<any>();
const integration = computed<{ id: string; name: string }>(
    () => page.props.integrationScope,
);
const clientScope = computed<{ id: string; name: string } | null>(
    () => page.props.clientScope ?? null,
);
const listUrl = computed(
    () =>
        `/clients/integrations${clientScope.value ? `?client_id=${encodeURIComponent(clientScope.value.id)}` : ""}`,
);
const today = new Date().toISOString().slice(0, 10);
const year = ref(Number(today.slice(0, 4)));
const selectedDate = ref(today);
const dayCounts = ref<DayCount[]>([]);
const runs = ref<RunRow[]>([]);
const loadingCalendar = ref(false);
const loadingRuns = ref(false);
const error = ref("");
const pageNumber = ref(1);
const lastPage = ref(1);
const total = ref(0);
const mobileMonthsScroll = ref<HTMLElement | null>(null);
let calendarRequest = 0;
let dayRequest = 0;

const countByDate = computed(
    () => new Map(dayCounts.value.map((day) => [day.date, day.count])),
);
const yearStart = computed(() => {
    const first = new Date(Date.UTC(year.value, 0, 1));
    const offset = (first.getUTCDay() + 6) % 7;
    first.setUTCDate(first.getUTCDate() - offset);
    return first;
});
const cells = computed<CalendarCell[]>(() => {
    const result: CalendarCell[] = [];
    const cursor = new Date(yearStart.value);
    const last = new Date(Date.UTC(year.value, 11, 31));
    last.setUTCDate(last.getUTCDate() + ((7 - last.getUTCDay()) % 7));
    while (cursor <= last) {
        const date = cursor.toISOString().slice(0, 10);
        const count = countByDate.value.get(date) ?? 0;
        result.push({
            date,
            inYear: cursor.getUTCFullYear() === year.value,
            future: date > today,
            count,
            level:
                count === 0
                    ? 0
                    : count >= 10
                      ? 4
                      : count >= 5
                        ? 3
                        : count >= 2
                          ? 2
                          : 1,
        });
        cursor.setUTCDate(cursor.getUTCDate() + 1);
    }
    return result;
});
const weeks = computed(() => cells.value.length / 7);
const months = computed(() =>
    Array.from({ length: 12 }, (_, month) => {
        const start = new Date(Date.UTC(year.value, month, 1));
        const week = Math.floor(
            (start.getTime() - yearStart.value.getTime()) / (7 * 86400000),
        );
        return {
            label: new Intl.DateTimeFormat("ru-RU", {
                month: "short",
                timeZone: "UTC",
            }).format(start),
            week,
        };
    }),
);
const mobileMonths = computed(() =>
    months.value.map((month, index) => {
        const firstDay = new Date(Date.UTC(year.value, index, 1));
        const leadingDays = (firstDay.getUTCDay() + 6) % 7;
        const prefix = `${year.value}-${String(index + 1).padStart(2, "0")}-`;
        const monthCells = cells.value.filter((cell) => cell.date.startsWith(prefix));
        return {
            label: month.label,
            cells: [...Array<null>(leadingDays).fill(null), ...monthCells],
        };
    }),
);
const statusLabels: Record<string, string> = {
    queued: "В очереди",
    running: "Выполняется",
    completed: "Завершён",
    failed: "Ошибка",
    cancelled: "Отменён",
};

async function loadCalendar(nextYear: number) {
    const request = ++calendarRequest;
    year.value = nextYear;
    loadingCalendar.value = true;
    error.value = "";
    try {
        const response = await http(
            `/web/clients/integrations/${integration.value.id}/run_logs?year=${nextYear}`,
        );
        if (request === calendarRequest) dayCounts.value = response.data;
    } catch (e) {
        if (request === calendarRequest)
            error.value =
                e instanceof Error
                    ? e.message
                    : "Не удалось загрузить календарь.";
    } finally {
        if (request === calendarRequest) loadingCalendar.value = false;
    }
}
async function loadDay(date: string, nextPage = 1) {
    const request = ++dayRequest;
    selectedDate.value = date;
    loadingRuns.value = true;
    error.value = "";
    try {
        const response = await http(
            `/web/clients/integrations/${integration.value.id}/run_logs/day?date=${date}&page=${nextPage}`,
        );
        if (request !== dayRequest) return;
        runs.value =
            nextPage === 1 ? response.data : [...runs.value, ...response.data];
        pageNumber.value = response.current_page;
        lastPage.value = response.last_page;
        total.value = response.total;
    } catch (e) {
        if (request === dayRequest)
            error.value =
                e instanceof Error
                    ? e.message
                    : "Не удалось загрузить запуски.";
    } finally {
        if (request === dayRequest) loadingRuns.value = false;
    }
}
function changeYear(direction: number) {
    const next = year.value + direction;
    if (next > Number(today.slice(0, 4)) || next < 2000) return;
    void loadCalendar(next);
    void loadDay(next === Number(today.slice(0, 4)) ? today : `${next}-01-01`);
}
function scrollToStartMonth() {
    const container = mobileMonthsScroll.value;
    if (!container) return;
    const monthIndex = year.value === Number(today.slice(0, 4))
        ? Math.max(0, new Date().getUTCMonth() - 3)
        : 0;
    const month = container.querySelector<HTMLElement>(`[data-month-index="${monthIndex}"]`);
    if (month) container.scrollTop = month.offsetTop;
}
watch(year, async () => {
    await nextTick();
    scrollToStartMonth();
});
onMounted(() => {
    void loadCalendar(year.value);
    void loadDay(selectedDate.value);
    void nextTick(scrollToStartMonth);
});
</script>

<template>
    <Head :title="`Логи запуска — ${integration.name}`" />
    <div class="users-workspace run-logs-page">
        <section class="users-list">
            <div class="content-breadcrumb">
                Клиенты › Интеграции › Логи запуска #{{ integration.id }}
            </div>
            <ClientTabs />
            <p v-if="clientScope" class="client-context">
                Клиент: <strong>{{ clientScope.name }}</strong>
            </p>
            <div class="page-heading">
                <div>
                    <h1>Логи запуска #{{ integration.id }}</h1>
                    <p>{{ integration.name }}</p>
                </div>
                <button
                    type="button"
                    class="back-button"
                    @click="router.visit(listUrl)"
                >
                    ← К списку
                </button>
            </div>
            <div v-if="error" class="logs-error" role="alert">{{ error }}</div>
            <div class="logs-content">
                <section
                    class="activity-card"
                    aria-label="Календарь активности"
                >
                    <div class="activity-heading">
                        <div>
                            <h2>Календарь активности</h2>
                            <p>Количество запусков по дням</p>
                        </div>
                        <div class="year-controls">
                            <button
                                type="button"
                                aria-label="Предыдущий год"
                                @click="changeYear(-1)"
                            >
                                ‹
                            </button>
                            <strong>{{ year }}</strong>
                            <button
                                type="button"
                                aria-label="Следующий год"
                                :disabled="year >= Number(today.slice(0, 4))"
                                @click="changeYear(1)"
                            >
                                ›
                            </button>
                        </div>
                    </div>
                    <p v-if="loadingCalendar" class="loading-note">
                        Загружаем календарь…
                    </p>
                    <div class="calendar-scroll">
                        <div class="calendar-layout" :style="{ '--weeks': weeks }">
                            <div class="weekday-labels">
                                <span>Пн</span><span>Ср</span><span>Пт</span>
                            </div>
                            <div class="calendar-main">
                                <div
                                    class="month-labels"
                                >
                                    <span
                                        v-for="month in months"
                                        :key="month.label"
                                        :style="{ left: `${(month.week / weeks) * 100}%` }"
                                        >{{ month.label }}</span
                                    >
                                </div>
                                <div class="activity-grid">
                                    <button
                                        v-for="cell in cells"
                                        :key="cell.date"
                                        type="button"
                                        class="activity-cell"
                                        :class="[
                                            `level-${cell.level}`,
                                            {
                                                selected:
                                                    selectedDate === cell.date,
                                                outside: !cell.inYear,
                                            },
                                        ]"
                                        :disabled="!cell.inYear || cell.future"
                                        :aria-label="`${formatDate(cell.date)}: ${cell.count} запусков`"
                                        :aria-pressed="
                                            selectedDate === cell.date
                                        "
                                        :title="`${formatDate(cell.date)} — ${cell.count} запусков`"
                                        @click="loadDay(cell.date)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div ref="mobileMonthsScroll" class="mobile-months">
                        <section
                            v-for="(month, index) in mobileMonths"
                            :key="index"
                            class="mobile-month"
                            :data-month-index="index"
                            :aria-label="`${month.label} ${year}`"
                        >
                            <h3>{{ month.label }}</h3>
                            <div class="mobile-weekdays" aria-hidden="true">
                                <span v-for="day in ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']" :key="day">{{ day }}</span>
                            </div>
                            <div class="mobile-month-grid">
                                <template v-for="(cell, dayIndex) in month.cells" :key="dayIndex">
                                    <button
                                        v-if="cell"
                                        type="button"
                                        class="activity-cell"
                                        :class="[
                                            `level-${cell.level}`,
                                            { selected: selectedDate === cell.date },
                                        ]"
                                        :disabled="cell.future"
                                        :aria-label="`${formatDate(cell.date)}: ${cell.count} запусков`"
                                        :aria-pressed="selectedDate === cell.date"
                                        :title="`${formatDate(cell.date)} — ${cell.count} запусков`"
                                        @click="loadDay(cell.date)"
                                    >{{ Number(cell.date.slice(-2)) }}</button>
                                    <span v-else aria-hidden="true"></span>
                                </template>
                            </div>
                        </section>
                    </div>
                    <div class="activity-legend">
                        <span>Меньше</span
                        ><i
                            v-for="level in [0, 1, 2, 3, 4]"
                            :key="level"
                            :class="`level-${level}`"
                        /><span>Больше</span>
                    </div>
                </section>
                <section class="day-card" aria-label="Запуски выбранного дня">
                    <div class="day-heading">
                        <h2>Запуски за {{ formatDate(selectedDate) }}</h2>
                        <span>{{ total }}</span>
                    </div>
                    <p
                        v-if="loadingRuns && pageNumber === 1"
                        class="loading-note"
                    >
                        Загружаем запуски…
                    </p>
                    <p v-else-if="!runs.length" class="empty-runs">
                        В этот день запусков не было.
                    </p>
                    <div v-else class="runs-table-scroll">
                        <table class="runs-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Дата</th>
                                    <th>Время</th>
                                    <th>Статус</th>
                                    <th>Обработано строк</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="run in runs" :key="run.id">
                                    <td>{{ run.id }}</td>
                                    <td>
                                        {{
                                            formatDate(run.created_at).split(
                                                " ",
                                            )[0]
                                        }}
                                    </td>
                                    <td>
                                        {{
                                            formatDate(
                                                run.created_at,
                                                true,
                                            ).split(" ")[1] || "—"
                                        }}
                                    </td>
                                    <td>
                                        <span
                                            class="run-status"
                                            :class="`status-${run.status}`"
                                            >{{
                                                statusLabels[run.status] ||
                                                run.status
                                            }}</span
                                        >
                                    </td>
                                    <td>{{ run.processed_records }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button
                        v-if="pageNumber < lastPage"
                        type="button"
                        class="more-button"
                        :disabled="loadingRuns"
                        @click="loadDay(selectedDate, pageNumber + 1)"
                    >
                        Показать ещё
                    </button>
                </section>
            </div>
        </section>
    </div>
</template>

<style scoped>
.run-logs-page {
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior-y: contain;
}
.run-logs-page .users-list {
    display: block;
    align-self: flex-start;
    min-width: 0;
    width: 100%;
}
.page-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}
.page-heading p,
.client-context {
    font-size: 12px;
    color: #858585;
}
.back-button {
    border: 1px solid #bcdfe7;
    border-radius: 6px;
    padding: 9px 13px;
    color: #2274a5;
    background: white;
    white-space: nowrap;
}
.logs-content {
    display: grid;
    gap: 18px;
    padding-bottom: 28px;
}
.activity-card,
.day-card {
    min-width: 0;
    padding: 22px;
    border: 1px solid #bcdfe7;
    border-radius: 8px;
    background: #fff;
}
.activity-heading,
.day-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
h2 {
    font-size: 18px;
    font-weight: 600;
    color: #0c1821;
}
.activity-heading p {
    color: #858585;
    font-size: 12px;
}
.year-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #2274a5;
}
.year-controls button {
    width: 30px;
    height: 30px;
    border: 1px solid #bcdfe7;
    border-radius: 5px;
    background: #fff;
    font-size: 20px;
}
.year-controls button:disabled {
    color: #aab7bf;
}
.calendar-scroll {
    width: 100%;
    margin-top: 20px;
    overflow-x: auto;
    padding-bottom: 8px;
}
.calendar-layout {
    display: flex;
    width: max-content;
    gap: 8px;
}
.mobile-months {
    display: none;
}
.calendar-main {
    width: calc(var(--weeks) * 17px);
}
.weekday-labels {
    display: grid;
    grid-template-rows: repeat(7, 14px);
    gap: 3px;
    padding-top: 23px;
    color: #858585;
    font-size: 10px;
}
.weekday-labels span:nth-child(1) {
    grid-row: 1;
}
.weekday-labels span:nth-child(2) {
    grid-row: 3;
}
.weekday-labels span:nth-child(3) {
    grid-row: 5;
}
.month-labels {
    position: relative;
    height: 23px;
    color: #858585;
    font-size: 11px;
}
.month-labels span {
    position: absolute;
    white-space: nowrap;
}
.activity-grid {
    display: grid;
    grid-auto-flow: column;
    grid-template-columns: repeat(var(--weeks), 14px);
    grid-template-rows: repeat(7, 14px);
    gap: 3px;
}
.activity-cell,
.activity-legend i {
    width: 14px;
    height: 14px;
    border-radius: 3px;
    border: 1px solid #dce7e3;
    background: #ebedf0;
}
.activity-cell.level-1,
.activity-legend .level-1 {
    background: #9be9a8;
    border-color: #9be9a8;
}
.activity-cell.level-2,
.activity-legend .level-2 {
    background: #40c463;
    border-color: #40c463;
}
.activity-cell.level-3,
.activity-legend .level-3 {
    background: #30a14e;
    border-color: #30a14e;
}
.activity-cell.level-4,
.activity-legend .level-4 {
    background: #216e39;
    border-color: #216e39;
}
.activity-cell.outside {
    visibility: hidden;
}
.activity-cell.selected {
    outline: 2px solid #2274a5;
    outline-offset: 2px;
}
.activity-cell:not(:disabled) {
    cursor: pointer;
}
.activity-cell:not(:disabled):hover {
    outline: 2px solid #2274a5;
}
.activity-legend {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    margin-top: 16px;
    color: #858585;
    font-size: 11px;
}
.activity-legend i {
    display: inline-block;
}
.day-heading span {
    color: #858585;
    font-size: 13px;
}
.loading-note,
.empty-runs {
    margin-top: 16px;
    color: #858585;
    font-size: 13px;
}
.runs-table-scroll {
    max-height: min(55dvh, 560px);
    overflow: auto;
    margin-top: 16px;
}
.runs-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}
.runs-table th,
.runs-table td {
    padding: 11px 12px;
    border-bottom: 1px solid #e1f0f3;
    font-size: 13px;
    white-space: nowrap;
}
.runs-table th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #fff;
    color: #858585;
    font-weight: 600;
}
.run-status {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    color: #2274a5;
    background: #e1f0f3;
}
.run-status.status-completed {
    color: #1e892f;
    background: #e1f3e7;
}
.run-status.status-failed {
    color: #a12020;
    background: #fde8e8;
}
.more-button {
    margin-top: 16px;
    padding: 8px 14px;
    border: 1px solid #bcdfe7;
    border-radius: 6px;
    color: #2274a5;
}
.logs-error {
    padding: 10px;
    color: #a12020;
}
@media (max-width: 767px) {
    .activity-card,
    .day-card {
        padding: 14px;
    }
    .calendar-scroll {
        display: none;
    }
    .mobile-months {
        display: grid;
        gap: 24px;
        position: relative;
        max-height: min(60dvh, 650px);
        overflow-y: auto;
        overscroll-behavior-y: contain;
        margin-top: 20px;
        padding-right: 6px;
    }
    .mobile-month h3 {
        margin: 0 0 8px;
        font-size: 14px;
        font-weight: 600;
        text-transform: capitalize;
    }
    .mobile-weekdays,
    .mobile-month-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 3px;
    }
    .mobile-weekdays {
        margin-bottom: 4px;
        color: #858585;
        font-size: 10px;
        text-align: center;
    }
    .mobile-month-grid .activity-cell {
        width: 100%;
        height: auto;
        min-width: 0;
        padding: 0;
        aspect-ratio: 1;
        color: #0c1821;
        font: inherit;
        font-size: 11px;
        font-weight: 600;
    }
    .mobile-month-grid .activity-cell.level-3,
    .mobile-month-grid .activity-cell.level-4 {
        color: #fff;
    }
}
</style>
