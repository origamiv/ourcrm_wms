<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { http } from "../lib/http";
import RussianDateInput from "../Components/RussianDateInput.vue";
import AdminTabs from "../Components/AdminTabs.vue";
import { isoDate, toIsoDate } from "../lib/dates";
const today = new Date(), from = ref(toIsoDate(new Date(today.getFullYear(), today.getMonth(), 1))), to = ref(toIsoDate(new Date(today.getFullYear(), today.getMonth() + 1, 0))), report = ref<any>({ days: [], rows: [] }), loading = ref(false), error = ref(""), calendarRef = ref<HTMLElement | null>(null);
const weekdays = ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"];
const info = (row: any, day: string) => row.days?.[day] || { state: "empty", worked_minutes: 0 };
const employeeName = (user: any) => [user.last_name, user.name].filter(Boolean).join(" ") || user.email || `№${user.id}`;
const employeeInitials = (user: any) => [user.last_name, user.name].filter(Boolean).map((value: string) => value.trim().slice(0, 1)).join("").slice(0, 2).toUpperCase() || "?";
const employeeImage = (user: any) => user.avatar_url || user.avatar || user.photo_url || user.photo || user.src?.avatar || null;
const hours = (minutes: number) => minutes ? `${Math.ceil(minutes / 60)}ч` : "";
const label = (day: string) => { const d = isoDate(day)!; return `${String(d.getDate()).padStart(2, "0")} ${weekdays[d.getDay()]}`; };
const isWeekend = (day: string) => { const index = isoDate(day)!.getDay(); return index === 0 || index === 6; };
const demoAbsenceState = (day: string) => { const pastWeekdays = report.value.days.filter((item: string) => !isWeekend(item) && item < new Date().toISOString().slice(0, 10)); const index = pastWeekdays.indexOf(day); return index === 0 ? "vacation" : index === 1 ? "sick" : null; };
const stateFor = (row: any, day: string) => { const value = info(row, day); const demoAbsence = demoAbsenceState(day); if (demoAbsence && value.state === "empty") return demoAbsence; const minutes = Number(value.worked_minutes || 0); if (minutes > 480) return "overtime"; if (minutes >= 480) return "full"; if (minutes > 0 && minutes < 240) return "short"; if (minutes > 0) return "partial"; return value.state === "empty" && !isWeekend(day) && day < new Date().toISOString().slice(0, 10) ? "absence" : value.state; };
const segments = (row: any) => { const result: any[] = []; report.value.days.forEach((day: string, index: number) => { const state = stateFor(row, day); const mergeable = ["vacation", "sick"].includes(state); const previous = result.at(-1); if (mergeable && previous?.state === state && previous.endIndex === index) { previous.days += 1; previous.endIndex = index + 1; } else result.push({ day, state, days: 1, startIndex: index, endIndex: index + 1 }); }); return result; };
const monthGroups = computed(() => { const groups: { key: string; label: string; count: number }[] = []; for (const day of report.value.days) { const date = isoDate(day)!; const key = `${date.getFullYear()}-${date.getMonth()}`; const current = groups.at(-1); if (current?.key === key) current.count += 1; else groups.push({ key, label: date.toLocaleDateString("ru-RU", { month: "long", year: "numeric" }), count: 1 }); } return groups; });
const total = (row: any) => Object.values(row.days || {}).reduce((sum: number, d: any) => sum + (d.worked_minutes || 0), 0);
async function scrollToToday() { await nextTick(); const calendar = calendarRef.value; if (!calendar || !report.value.days.length || !window.matchMedia("(max-width: 900px)").matches) return; const currentDay = new Date().toISOString().slice(0, 10); const target = calendar.querySelector<HTMLElement>(`[data-day="${currentDay}"]`) || calendar.querySelector<HTMLElement>("[data-day]"); if (!target) return; calendar.scrollLeft = Math.max(0, target.offsetLeft - (calendar.clientWidth - target.offsetWidth) / 2); }
async function load() { if (!from.value || !to.value) return; loading.value = true; try { report.value = await http(`/web/worktime?from=${from.value}&to=${to.value}`); await scrollToToday(); } catch (e: any) { error.value = e?.message || "Не удалось загрузить график."; } finally { loading.value = false; } }
onMounted(load); watch([from, to], load);
</script>
<template>
    <div class="users-workspace worktime-page">
        <section class="users-list">
            <div class="content-breadcrumb">Администрирование › Рабочий график</div>
            <AdminTabs />
            <div class="page-heading worktime-heading">
                <div>
                    <h1>График присутствий и отсутствий</h1>
                    <p>Рабочее время сотрудников</p>
                </div>
                <div class="range">
                    <RussianDateInput v-model="from" />
                    <span>—</span>
                    <RussianDateInput v-model="to" />
                </div>
            </div>
            <p v-if="error" class="notice error">{{ error }}</p>
            <div ref="calendarRef" class="table-scroll calendar" :class="{ busy: loading }">
                <table>
                    <thead>
                        <tr class="month-row">
                            <th class="employee" rowspan="2">Сотрудник</th>
                            <th v-for="group in monthGroups" :key="group.key" :colspan="group.count">{{ group.label }}</th>
                            <th class="total" rowspan="2">Итого</th>
                        </tr>
                        <tr class="day-row">
                            <th v-for="day in report.days" :key="day" :data-day="day">{{ label(day) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in report.rows" :key="row.user.id">
                            <td class="employee">
                                <span class="employee-avatar" :title="employeeName(row.user)">
                                    <img v-if="employeeImage(row.user)" :src="employeeImage(row.user)" alt="" />
                                    <span v-else>{{ employeeInitials(row.user) }}</span>
                                </span>
                                <strong class="employee-name">{{ employeeName(row.user) }}</strong>
                            </td>
                            <td v-for="segment in segments(row)" :key="segment.day" :data-day="segment.day" :colspan="segment.days" :class="['day', segment.state, { weekend: segment.days === 1 && isWeekend(segment.day) }]">{{ segment.state === 'vacation' ? 'Отпуск' : segment.state === 'sick' ? 'Больничный' : segment.days === 1 ? hours(info(row, segment.day).worked_minutes) : '' }}</td>
                            <td class="total">{{ hours(total(row)) }}</td>
                        </tr>
                        <tr v-if="!report.rows.length"><td :colspan="report.days.length + 2" class="empty">Нет данных</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="legend">
                <span><i class="overtime"></i>Переработка</span><span><i class="full"></i>8 часов</span><span><i class="partial"></i>Неполный день</span><span><i class="short"></i>Менее 4 часов</span><span><i class="leave"></i>Отпуск / больничный</span>
            </div>
        </section>
    </div>
</template>
<style scoped>
.worktime-page{padding:0;min-width:0}.card{background:#fff;border:1px solid #dcecef;border-radius:12px;padding:24px;overflow:hidden}.worktime-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}h1{margin:0;color:#0c1821;font-size:24px}.worktime-heading p{color:#71828a;margin:6px 0 0}.range{display:flex;align-items:center;gap:8px}.range :deep(input){width:115px;padding:9px 10px;border:1px solid #bcdfe7;border-radius:7px}.calendar{overflow:auto}.busy{opacity:.55}table{border-collapse:collapse;min-width:100%;font-size:12px}th,td{border-bottom:1px solid #edf3f4;padding:10px 8px;text-align:center;white-space:nowrap}th{color:#71828a}.month-row th{height:30px;background:#f5f8f9;color:#536168;font-weight:700;text-transform:capitalize}.day-row th{height:42px;background:#fff;color:#71828a}.employee{text-align:left;min-width:180px;position:sticky;left:0;background:#fff;z-index:1}.employee-avatar{display:none}.employee-avatar img{width:100%;height:100%;object-fit:cover}.employee-name{display:inline}.employee small{display:block;color:#71828a;margin-top:3px}.day{min-width:62px;height:42px}.day.working{background:#d8f3dc;color:#237a36;font-weight:600}.day.finished,.day.overtime{background:#197a35;color:#fff;font-weight:700}.day.full{background:#8fdda0;color:#185c28;font-weight:700}.day.partial{background:#fff3bf;color:#9a7b00;font-weight:600}.day.short,.day.absence{background:#f8d7da;color:#a44955;font-weight:600}.day.vacation,.day.sick{background:#f4b183;color:#8a3f0a;font-weight:700}.day.empty{background:#fff3bf;color:#9a7b00}.day.weekend{background:#e5e9ea;color:#8d999e}.total{font-weight:700;min-width:80px}.empty{padding:32px;color:#71828a}.legend{display:flex;gap:20px;margin-top:20px;color:#71828a;font-size:12px}.legend span{display:flex;align-items:center;gap:6px}.legend i{width:10px;height:10px;border-radius:3px;display:inline-block}.overtime{background:#197a35}.full{background:#8fdda0}.partial{background:#fff3bf}.short{background:#f8d7da}.leave{background:#f4b183}.error{color:#c43e3e}
@media (max-width:900px){.worktime-page{overflow:hidden}.worktime-page :deep(.ribbon-group){width:100%;margin-bottom:0}.worktime-page :deep(.module-tabs){width:100%;max-width:100%}.worktime-heading{flex-direction:column;align-items:flex-start;gap:14px;margin-bottom:16px}.worktime-heading p{font-size:12px}h1{font-size:20px;line-height:1.25}.range{width:100%;justify-content:space-between;gap:6px}.range :deep(input){flex:1 1 0;width:auto;min-width:0;padding:8px;font-size:12px}.calendar{max-width:100%;overflow-x:auto}.calendar table{min-width:720px}.calendar .employee{min-width:52px}.calendar .employee-avatar{display:grid;place-items:center;width:32px;height:32px;margin:auto;border-radius:50%;overflow:hidden;background:linear-gradient(135deg,#1e892f,#66c98a);color:#fff;font-size:12px;font-weight:700}.calendar .employee-name{display:none}.legend{flex-wrap:wrap;gap:10px 16px;margin-top:16px;font-size:11px}}
</style>
