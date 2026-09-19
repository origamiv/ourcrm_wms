<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Head } from "@inertiajs/vue3";
import MaintenanceTabs from "../Components/MaintenanceTabs.vue";
import { http, HttpError } from "../lib/http";
import { formatDate } from "../lib/dates";

interface Task { key: string; label: string; type: string }
interface Schedule { kind: "interval" | "weekly" | "monthly"; interval: number; unit: "minute" | "hour" | "day"; week_interval: number; weekdays: number[]; month_days: number[]; start_date: string; end_date: string; time: string }
interface Row { id: number; name: string; task_key: string; task_label: string; task_type: string; params: Record<string, unknown>; schedule: Schedule; schedule_label: string; status: number; next_run_at: string | null; last_run_at: string | null }
interface Run { id: number; status: string; queued_at: string; started_at: string | null; finished_at: string | null; error_message: string | null }

const rows = ref<Row[]>([]); const tasks = ref<Task[]>([]); const loading = ref(true); const error = ref("");
const editing = ref<Row | null>(null); const editorOpen = ref(false); const history = ref<{ row: Row; runs: Run[] } | null>(null); const saving = ref(false);
const form = ref({ name: "", task_key: "", params: "{}", status: 1, schedule: defaultSchedule() });
const weekdays = [[1, "Пн"], [2, "Вт"], [3, "Ср"], [4, "Чт"], [5, "Пт"], [6, "Сб"], [7, "Вс"]] as [number, string][];

function defaultSchedule(): Schedule { return { kind: "interval", interval: 1, unit: "hour", week_interval: 1, weekdays: [1], month_days: [1], start_date: new Date().toISOString().slice(0, 10), end_date: "", time: "09:00" }; }
function reset(row?: Row) { editing.value = row ?? null; editorOpen.value = true; form.value = row ? { name: row.name, task_key: row.task_key, params: JSON.stringify(row.params ?? {}, null, 2), status: row.status, schedule: { ...defaultSchedule(), ...row.schedule, weekdays: [...(row.schedule.weekdays ?? [])], month_days: [...(row.schedule.month_days ?? [])] } } : { name: "", task_key: tasks.value[0]?.key ?? "", params: "{}", status: 1, schedule: defaultSchedule() }; }
function format(value: string | null) { return value ? formatDate(value, true) : "—"; }
function taskLabel(key: string) { return tasks.value.find((task) => task.key === key)?.label ?? key; }
function toggle(list: number[], value: number) { const i = list.indexOf(value); i >= 0 ? list.splice(i, 1) : list.push(value); }
async function load() { try { const [a, b] = await Promise.all([http("/web/scheduler"), http("/web/scheduler/tasks")]); rows.value = a.data ?? []; tasks.value = b.data ?? []; if (!form.value.task_key) form.value.task_key = tasks.value[0]?.key ?? ""; } catch (e) { error.value = e instanceof HttpError ? e.message : "Не удалось загрузить планировщик."; } finally { loading.value = false; } }
async function save() { saving.value = true; error.value = ""; try { let params; try { params = JSON.parse(form.value.params || "{}"); } catch { throw new Error("Параметры должны быть корректным JSON."); } const payload = { name: form.value.name, task_key: form.value.task_key, params, status: form.value.status, schedule: form.value.schedule }; await http(editing.value ? `/web/scheduler/${editing.value.id}` : "/web/scheduler", editing.value ? "PUT" : "POST", payload); editorOpen.value = false; editing.value = null; await load(); } catch (e) { error.value = e instanceof HttpError ? e.message : (e as Error).message; } finally { saving.value = false; } }
async function remove(row: Row) { if (!confirm(`Удалить расписание «${row.name}»?`)) return; await http(`/web/scheduler/${row.id}`, "DELETE"); await load(); }
async function showHistory(row: Row) { const response = await http(`/web/scheduler/${row.id}/runs`); history.value = { row, runs: response.data ?? [] }; }
const title = computed(() => editing.value ? "Изменение расписания" : "Новое расписание");
onMounted(load);
</script>

<template>
    <Head title="Планировщик" />
    <section class="page-content imports-page">
        <div class="content-breadcrumb">Обслуживание › Планировщик</div><MaintenanceTabs />
        <div class="page-heading"><div><h1>Планировщик</h1><p class="muted">Фоновые задачи модуля WMS, расписания и журнал запусков</p></div><button class="button primary" @click="reset()">Добавить расписание</button></div>
        <p v-if="error" class="notice error" role="alert">{{ error }}</p>
        <div class="table-scroll imports-table-wrap"><table class="imports-table"><thead><tr><th>#</th><th>Название</th><th>Задача</th><th>Расписание</th><th>Статус</th><th>Следующий запуск</th><th>Последний запуск</th><th></th></tr></thead>
            <tbody><tr v-if="loading"><td colspan="8" class="empty-cell">Загрузка…</td></tr><tr v-else-if="!rows.length"><td colspan="8" class="empty-cell">Расписания ещё не созданы</td></tr>
                <tr v-for="row in rows" :key="row.id"><td><span class="row-id">{{ row.id }}</span></td><td><strong>{{ row.name }}</strong></td><td>{{ row.task_label }}</td><td>{{ row.schedule_label }}</td><td><button class="status-badge" :class="row.status ? 'status-completed' : 'status-failed'" @click="reset(row)">{{ row.status ? 'Включено' : 'Выключено' }}</button></td><td>{{ format(row.next_run_at) }}</td><td>{{ format(row.last_run_at) }}</td><td class="row-actions"><button class="link-button" @click="showHistory(row)">История</button><button class="link-button" @click="reset(row)">Изменить</button><button class="link-button danger" @click="remove(row)">Удалить</button></td></tr>
            </tbody></table></div>
        <div v-if="editorOpen" class="scheduler-editor"><div class="editor-card"><h2>{{ title }}</h2><label>Название<input v-model="form.name" type="text"></label><label>Задача<select v-model="form.task_key"><option v-for="task in tasks" :key="task.key" :value="task.key">{{ task.label }}</option></select></label><label>Параметры JSON<textarea v-model="form.params" rows="5"></textarea></label><label class="check"><input v-model="form.status" type="checkbox" :true-value="1" :false-value="0"> Расписание включено</label>
            <h3>Повторение</h3><div class="form-grid"><label>Тип<select v-model="form.schedule.kind"><option value="interval">Интервал</option><option value="weekly">Дни недели</option><option value="monthly">Числа месяца</option></select></label><label>Начало<input v-model="form.schedule.start_date" type="date"></label><label>Время<input v-model="form.schedule.time" type="time"></label><label>До (необязательно)<input v-model="form.schedule.end_date" type="date"></label></div>
            <div v-if="form.schedule.kind === 'interval'" class="form-grid"><label>Каждые<input v-model.number="form.schedule.interval" min="1" type="number"></label><label>Единица<select v-model="form.schedule.unit"><option value="minute">минуты</option><option value="hour">часы</option><option value="day">дни</option></select></label></div>
            <div v-else-if="form.schedule.kind === 'weekly'"><label>Раз в<select v-model.number="form.schedule.week_interval"><option :value="1">неделю</option><option :value="2">2 недели</option></select></label><div class="weekday-list"><label v-for="day in weekdays" :key="day[0]" class="check"><input type="checkbox" :checked="form.schedule.weekdays.includes(day[0])" @change="toggle(form.schedule.weekdays, day[0])">{{ day[1] }}</label></div></div>
            <div v-else><label>Дни месяца (через запятую)<input :value="form.schedule.month_days.join(', ')" @input="form.schedule.month_days = ($event.target as HTMLInputElement).value.split(',').map(Number).filter(Boolean)"></label></div>
            <div class="editor-actions"><button class="button primary" :disabled="saving" @click="save">Сохранить</button><button class="button" @click="editorOpen = false">Отмена</button></div>
        </div></div>
        <div v-if="history" class="modal-backdrop" @click.self="history = null"><div class="editor-card history-card"><h2>История: {{ history.row.name }}</h2><table class="imports-table"><thead><tr><th>#</th><th>Статус</th><th>Поставлено</th><th>Начало</th><th>Завершение</th><th>Ошибка</th></tr></thead><tbody><tr v-for="run in history.runs" :key="run.id"><td>{{ run.id }}</td><td>{{ run.status }}</td><td>{{ format(run.queued_at) }}</td><td>{{ format(run.started_at) }}</td><td>{{ format(run.finished_at) }}</td><td>{{ run.error_message || '—' }}</td></tr><tr v-if="!history.runs.length"><td colspan="6" class="empty-cell">Запусков ещё не было</td></tr></tbody></table><button class="button" @click="history = null">Закрыть</button></div></div>
    </section>
</template>
