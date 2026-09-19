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
const repeatPreset = ref("hour_1");
const weekdays = [[1, "Пн"], [2, "Вт"], [3, "Ср"], [4, "Чт"], [5, "Пт"], [6, "Сб"], [7, "Вс"]] as [number, string][];

function defaultSchedule(): Schedule { return { kind: "interval", interval: 1, unit: "hour", week_interval: 1, weekdays: [1], month_days: [1], start_date: new Date().toISOString().slice(0, 10), end_date: "", time: "09:00" }; }
function reset(row?: Row) { editing.value = row ?? null; editorOpen.value = true; form.value = row ? { name: row.name, task_key: row.task_key, params: JSON.stringify(row.params ?? {}, null, 2), status: row.status, schedule: { ...defaultSchedule(), ...row.schedule, weekdays: [...(row.schedule.weekdays ?? [])], month_days: [...(row.schedule.month_days ?? [])] } } : { name: "", task_key: tasks.value[0]?.key ?? "", params: "{}", status: 1, schedule: defaultSchedule() }; repeatPreset.value = row ? presetFor(row.schedule) : "hour_1"; }
function presetFor(schedule: Schedule): string { if (schedule.kind === "weekly") return schedule.week_interval === 2 ? "biweekly" : (schedule.weekdays.join(",") === "1,2,3,4,5" ? "weekdays" : "weekly"); if (schedule.kind === "monthly") return "monthly"; if (schedule.unit === "minute") return `minute_${schedule.interval}`; if (schedule.unit === "day") return "daily"; return `hour_${schedule.interval}`; }
function applyRepeatPreset(value: string) { repeatPreset.value = value; const schedule = form.value.schedule; if (value === "weekly" || value === "biweekly" || value === "weekdays") { schedule.kind = "weekly"; schedule.week_interval = value === "biweekly" ? 2 : 1; schedule.weekdays = value === "weekdays" ? [1, 2, 3, 4, 5] : [1]; return; } if (value === "monthly") { schedule.kind = "monthly"; return; } schedule.kind = "interval"; if (value === "daily") { schedule.interval = 1; schedule.unit = "day"; return; } const [unit, amount] = value.split("_"); schedule.interval = Number(amount) || 1; schedule.unit = unit === "minute" ? "minute" : "hour"; }
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
    <div class="scheduler-workspace" :class="{ 'has-editor': editorOpen }">
        <section class="scheduler-main">
            <div class="content-breadcrumb">Обслуживание › Планировщик</div><MaintenanceTabs />
            <div class="page-heading"><div><h1>Планировщик</h1><p class="muted">Фоновые задачи модуля WMS, расписания и журнал запусков</p></div><button class="button primary" type="button" @click="reset()">Добавить расписание</button></div>
            <p v-if="error" class="notice error" role="alert">{{ error }}</p>
            <div class="table-scroll imports-table-wrap"><table class="imports-table"><thead><tr><th>#</th><th>Название</th><th>Задача</th><th>Расписание</th><th>Статус</th><th>Следующий запуск</th><th>Последний запуск</th><th></th></tr></thead>
                <tbody><tr v-if="loading"><td colspan="8" class="empty-cell">Загрузка…</td></tr><tr v-else-if="!rows.length"><td colspan="8" class="empty-cell">Расписания ещё не созданы</td></tr>
                    <tr v-for="row in rows" :key="row.id"><td><span class="row-id">{{ row.id }}</span></td><td><strong>{{ row.name }}</strong></td><td>{{ row.task_label }}</td><td>{{ row.schedule_label }}</td><td><button class="status-badge" :class="row.status ? 'status-completed' : 'status-failed'" type="button" @click="reset(row)">{{ row.status ? 'Включено' : 'Выключено' }}</button></td><td>{{ format(row.next_run_at) }}</td><td>{{ format(row.last_run_at) }}</td><td class="row-actions"><button class="link-button" type="button" @click="showHistory(row)">История</button><button class="link-button" type="button" @click="reset(row)">Изменить</button><button class="link-button danger" type="button" @click="remove(row)">Удалить</button></td></tr>
                </tbody></table></div>
        </section>
        <aside v-if="editorOpen" class="editor scheduler-editor" aria-label="Расписание">
            <header><div><small>{{ editing ? 'РЕДАКТИРОВАНИЕ' : 'НОВОЕ РАСПИСАНИЕ' }}</small><h2>{{ title }}</h2></div><button type="button" aria-label="Закрыть карточку" :disabled="saving" @click="editorOpen = false">×</button></header>
            <div class="editor-content"><p v-if="error" class="notice error" role="alert">{{ error }}</p><form @submit.prevent="save"><label>Название<input v-model="form.name" required type="text"></label><label>Задача<select v-model="form.task_key"><option v-for="task in tasks" :key="task.key" :value="task.key">{{ task.label }}</option></select></label><label>Параметры JSON<textarea v-model="form.params" rows="5"></textarea></label><label class="check"><input v-model="form.status" type="checkbox" :true-value="1" :false-value="0"> Расписание включено</label>
                <div class="repeat-card"><h3>Повторение</h3><div class="repeat-line"><span>Повторять</span><select v-model="repeatPreset" @change="applyRepeatPreset(repeatPreset)"><option value="hour_1">Каждый час</option><option value="hour_2">Каждые 2 часа</option><option value="hour_4">Каждые 4 часа</option><option value="hour_6">Каждые 6 часов</option><option value="hour_8">Каждые 8 часов</option><option value="hour_12">Каждые 12 часов</option><option value="minute_1">Каждую минуту</option><option value="minute_5">Каждые 5 минут</option><option value="minute_10">Каждые 10 минут</option><option value="minute_15">Каждые 15 минут</option><option value="minute_30">Каждые 30 минут</option><option value="daily">Каждый день</option><option value="weekdays">По будням</option><option value="weekly">Каждую неделю</option><option value="biweekly">Каждые 2 недели</option><option value="monthly">Каждый месяц</option></select></div>
                    <div class="form-grid repeat-dates"><label>Начало<input v-model="form.schedule.start_date" type="date"></label><label>Время<input v-model="form.schedule.time" type="time"></label><label>До (необязательно)<input v-model="form.schedule.end_date" type="date"></label></div>
                    <div v-if="form.schedule.kind === 'interval' && !['daily'].includes(repeatPreset)" class="repeat-custom"><span>Настроить интервал</span><div class="form-grid"><label>Каждые<input v-model.number="form.schedule.interval" min="1" type="number"></label><label>Единица<select v-model="form.schedule.unit"><option value="minute">минуты</option><option value="hour">часы</option><option value="day">дни</option></select></label></div></div>
                    <div v-if="form.schedule.kind === 'weekly'" class="repeat-week"><label>Дни недели</label><div class="weekday-list"><label v-for="day in weekdays" :key="day[0]" class="check"><input type="checkbox" :checked="form.schedule.weekdays.includes(day[0])" @change="toggle(form.schedule.weekdays, day[0])">{{ day[1] }}</label></div></div>
                    <div v-if="form.schedule.kind === 'monthly'" class="repeat-month"><label>Дни месяца (через запятую)<input :value="form.schedule.month_days.join(', ')" @input="form.schedule.month_days = ($event.target as HTMLInputElement).value.split(',').map(Number).filter(Boolean)"></label></div>
                </div>
                <button class="primary" type="submit" :disabled="saving">{{ saving ? 'Сохраняем…' : (editing ? 'Сохранить изменения' : 'Создать расписание') }}</button>
            </form></div>
        </aside>
        <div v-if="history" class="modal-backdrop" @click.self="history = null"><div class="editor-card history-card" role="dialog" aria-modal="true"><button class="editor-close" type="button" aria-label="Закрыть" @click="history = null">×</button><h2>История: {{ history.row.name }}</h2><div class="history-table"><table class="imports-table"><thead><tr><th>#</th><th>Статус</th><th>Поставлено</th><th>Начало</th><th>Завершение</th><th>Ошибка</th></tr></thead><tbody><tr v-for="run in history.runs" :key="run.id"><td>{{ run.id }}</td><td>{{ run.status }}</td><td>{{ format(run.queued_at) }}</td><td>{{ format(run.started_at) }}</td><td>{{ format(run.finished_at) }}</td><td>{{ run.error_message || '—' }}</td></tr><tr v-if="!history.runs.length"><td colspan="6" class="empty-cell">Запусков ещё не было</td></tr></tbody></table></div><button class="button" type="button" @click="history = null">Закрыть</button></div></div>
    </div>
</template>

<style scoped>
.scheduler-page { min-width: 0; }
.scheduler-workspace { display: flex; min-height: 0; flex: 1; overflow: hidden; background: #fff; border: 1px solid var(--crm-border); border-radius: 10px; }
.scheduler-main { display: flex; flex: 1; min-width: 0; flex-direction: column; overflow: auto; padding: 30px; }
.scheduler-editor { width: 390px; }
.scheduler-editor h3 { margin: 24px 0 12px; font-size: 15px; }
.scheduler-editor form { display: grid; gap: 16px; }
.scheduler-editor form > label, .scheduler-editor .form-grid label { display: grid; gap: 6px; color: #52636a; font-size: 12px; }
.scheduler-editor input:not([type="checkbox"]), .scheduler-editor select, .scheduler-editor textarea { width: 100%; box-sizing: border-box; padding: 9px 10px; border: 1px solid #d8e0e3; border-radius: 6px; background: #fff; font: inherit; }
.scheduler-editor textarea { resize: vertical; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; }
.scheduler-editor .check { display: flex; align-items: center; gap: 8px; color: #263b43; }
.scheduler-editor .check input { accent-color: #1e892f; }
.scheduler-editor .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.scheduler-editor .weekday-list { display: flex; flex-wrap: wrap; gap: 8px 12px; }
.scheduler-editor .weekday-list .check { font-size: 12px; }
.scheduler-editor form > .primary { justify-self: start; }
.repeat-card { padding: 14px; border: 1px solid #dcecef; border-radius: 8px; background: #f8fbfb; }
.repeat-card h3 { margin: 0 0 12px; }
.repeat-line { display: grid; grid-template-columns: 92px 1fr; align-items: center; gap: 10px; color: #52636a; font-size: 12px; }
.repeat-line select { font-weight: 500; }
.repeat-dates { margin-top: 14px; }
.repeat-custom, .repeat-week, .repeat-month { margin-top: 14px; padding-top: 14px; border-top: 1px solid #e3ecee; }
.repeat-custom > span, .repeat-week > label { display: block; margin-bottom: 8px; color: #52636a; font-size: 12px; font-weight: 600; }
.row-actions { white-space: nowrap; }
.link-button { border: 0; background: transparent; color: #1e892f; cursor: pointer; margin-right: 10px; padding: 3px 0; }
.link-button.danger { color: #a32626; }
.modal-backdrop { position: fixed; inset: 0; z-index: 30; display: grid; place-items: center; padding: 20px; background: rgb(26 38 43 / 42%); }
.editor-card { position: relative; width: min(720px, 100%); max-height: calc(100vh - 40px); overflow-y: auto; padding: 28px; border: 1px solid #dcecef; border-radius: 14px; background: #fff; box-shadow: 0 20px 60px rgb(0 0 0 / 20%); }
.editor-card h2 { margin: 0 0 22px; color: #0c1821; font-size: 21px; }
.editor-card h3 { margin: 24px 0 12px; color: #0c1821; font-size: 16px; }
.editor-card > label, .form-grid label { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; color: #52636a; font-size: 12px; font-weight: 600; }
.editor-card input:not([type="checkbox"]), .editor-card select, .editor-card textarea { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #cbdadd; border-radius: 8px; background: #fff; color: #0c1821; font: inherit; font-size: 14px; font-weight: 400; }
.editor-card textarea { resize: vertical; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; }
.editor-card .check { display: flex; flex-direction: row; align-items: center; gap: 8px; color: #263b43; font-size: 13px; font-weight: 500; }
.editor-card .check input { accent-color: #1e892f; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 14px; }
.weekday-list { display: flex; flex-wrap: wrap; gap: 8px 14px; margin: 8px 0 14px; }
.weekday-list .check { margin: 0; }
.editor-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; }
.button { padding: 10px 16px; border: 1px solid #cbdadd; border-radius: 8px; background: #fff; color: #263b43; cursor: pointer; font: inherit; font-size: 13px; }
.button.primary { border-color: #1e892f; background: #1e892f; color: #fff; }
.button:disabled { cursor: wait; opacity: .6; }
.editor-close { position: absolute; top: 12px; right: 14px; border: 0; background: transparent; color: #718087; cursor: pointer; font-size: 25px; line-height: 1; }
.history-card { width: min(1100px, 100%); }
.history-table { overflow-x: auto; margin-bottom: 20px; }
.history-table .imports-table { min-width: 760px; }
@media (max-width: 640px) { .editor-card { padding: 22px 16px; } .form-grid { grid-template-columns: 1fr; } }
@media (max-width: 900px) { .scheduler-workspace { display: block; overflow: auto; } .scheduler-main { padding: 18px 12px; } .scheduler-editor { width: auto; border-top: 1px solid #e8ecf0; } }
</style>
