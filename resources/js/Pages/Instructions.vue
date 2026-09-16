<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import { http, HttpError } from "../lib/http";
import AdminTabs from "../Components/AdminTabs.vue";
import ClientTabs from "../Components/ClientTabs.vue";
import GoodsTabs from "../Components/GoodsTabs.vue";
import IntegrationTabs from "../Components/IntegrationTabs.vue";
import FulfillmentTabs from "../Components/FulfillmentTabs.vue";
import MaintenanceTabs from "../Components/MaintenanceTabs.vue";
import FileDropzone from "../Components/FileDropzone.vue";
import { formatDate } from "../lib/dates";

interface Instruction { id: string; name: string; shortname: string; section_key: string; content_type: string; original_filename: string; size: number; status: number; updated_at: string | null; content_url: string; download_url: string; }
const page = usePage<any>();
const rows = ref<Instruction[]>([]), loading = ref(true), error = ref(""), saving = ref(false), editingId = ref<string | null>(null);
const isAdminPage = computed(() => page.url.startsWith("/main/instructions"));
const section = computed(() => {
    const pathSection = page.url.split("?")[0].split("/")[1] || "";
    if (["main", "clients", "goods", "integration", "fulfillment", "maintenance"].includes(pathSection) && page.url.split("?")[0].split("/")[2] === "help") return pathSection;
    return new URLSearchParams(page.url.split("?")[1] || "").get("section") || "";
});
const form = ref({ name: "", shortname: "", section_key: "main", sort_order: "0", status: "1", file: null as File | null });
const sections = [{ key: "main", name: "Администрирование" }, { key: "clients", name: "Клиенты" }, { key: "goods", name: "Товары" }, { key: "integration", name: "Интеграции" }, { key: "fulfillment", name: "Фулфилмент" }, { key: "maintenance", name: "Обслуживание" }];
const types: Record<string, string> = { pdf: "PDF", markdown: "Markdown", html: "HTML", video: "Видео" };

async function load() {
    loading.value = true;
    try { const query = isAdminPage.value ? "" : (section.value ? `?section=${encodeURIComponent(section.value)}` : ""); rows.value = (await http(`/web/instructions${query}`)).data ?? []; error.value = ""; }
    catch (e) { error.value = e instanceof HttpError ? e.message : "Не удалось загрузить инструкции."; }
    finally { loading.value = false; }
}
async function save() {
    if (!editingId.value && !form.value.file) { error.value = "Выберите файл инструкции."; return; }
    saving.value = true;
    const data = new FormData(); Object.entries(form.value).forEach(([key, value]) => { if (value !== null) data.append(key, value instanceof File ? value : String(value)); });
    try { await http(editingId.value ? `/web/instructions/${editingId.value}` : "/web/instructions", "POST", data); resetForm(); await load(); }
    catch (e) { error.value = e instanceof HttpError ? e.message : "Не удалось сохранить инструкцию."; }
    finally { saving.value = false; }
}
function resetForm() { editingId.value = null; form.value = { name: "", shortname: "", section_key: "main", sort_order: "0", status: "1", file: null }; }
function edit(row: Instruction) { editingId.value = row.id; form.value = { name: row.name, shortname: row.shortname, section_key: row.section_key, sort_order: "0", status: String(row.status), file: null }; }
async function remove(row: Instruction) { if (!window.confirm(`Удалить инструкцию «${row.name}»?`)) return; try { await http(`/web/instructions/${row.id}`, "DELETE"); await load(); } catch (e) { error.value = e instanceof HttpError ? e.message : "Не удалось удалить инструкцию."; } }
function download(row: Instruction) { window.location.href = row.download_url; }
onMounted(load);
</script>

<template>
    <Head title="Инструкции" />
    <section class="page-content instructions-page">
        <div class="content-breadcrumb">{{ isAdminPage ? "Администрирование › Инструкции" : (sections.find((item) => item.key === section)?.name || "Инструкции") + " › Инструкции" }}</div>
        <AdminTabs v-if="isAdminPage || section === 'main'" />
        <ClientTabs v-else-if="section === 'clients'" />
        <GoodsTabs v-else-if="section === 'goods'" />
        <IntegrationTabs v-else-if="section === 'integration'" />
        <FulfillmentTabs v-else-if="section === 'fulfillment'" />
        <MaintenanceTabs v-else-if="section === 'maintenance'" />
        <div v-else class="instructions-heading"><h1>Инструкции</h1><p class="muted">Инструкции по процессам текущего раздела</p></div>
        <p v-if="error" class="notice error">{{ error }}</p>
        <div class="instructions-layout">
            <div class="instructions-list">
                <div v-if="isAdminPage" class="page-heading"><div><h1>Инструкции</h1><p class="muted">Справочник инструкций по процессам разделов</p></div></div>
                <div v-else class="instructions-heading"><h1>Инструкции</h1><p class="muted">Инструкции по процессам текущего раздела</p></div>
                <div class="table-scroll instructions-table-wrap"><table class="instructions-table"><thead><tr><th>#</th><th>Название</th><th>Формат</th><th>Изменено</th><th>Действия</th></tr></thead><tbody><tr v-if="loading"><td colspan="5" class="empty-cell">Загрузка…</td></tr><tr v-else-if="!rows.length"><td colspan="5" class="empty-cell">Инструкций пока нет</td></tr><tr v-for="row in rows" :key="row.id"><td>{{ row.id }}</td><td><strong>{{ row.name }}</strong><small>{{ row.original_filename }}</small></td><td>{{ types[row.content_type] || row.content_type }}</td><td>{{ formatDate(row.updated_at, true) }}</td><td class="instruction-actions"><a :href="section ? `/${section}/help/${row.id}` : `/instructions/${row.id}`">Просмотреть</a><button type="button" @click="download(row)">Скачать</button><template v-if="isAdminPage"><button type="button" @click="edit(row)">Изменить</button><button type="button" @click="remove(row)">Удалить</button></template></td></tr></tbody></table></div>
            </div>
            <aside v-if="isAdminPage" class="instruction-editor" aria-label="Создание и редактирование инструкции">
                <h1>{{ editingId ? "Изменить инструкцию" : "Добавить инструкцию" }}</h1>
                <div class="instruction-form-grid"><label>Название<input v-model="form.name" type="text" /></label><label>Краткое имя<input v-model="form.shortname" type="text" /></label><label>Раздел<select v-model="form.section_key"><option v-for="item in sections" :key="item.key" :value="item.key">{{ item.name }}</option></select></label><label>Порядок<input v-model="form.sort_order" type="number" min="0" /></label><label class="instruction-file">Файл<FileDropzone v-model="form.file" accept=".pdf,.md,.markdown,.html,.htm,.mp4" hint="PDF, Markdown, HTML или видео" /></label><div class="instruction-form-actions"><button class="primary" type="button" :disabled="saving" @click="save">{{ saving ? "Сохраняем…" : (editingId ? "Сохранить" : "Добавить") }}</button><button v-if="editingId" class="secondary" type="button" @click="resetForm">Отмена</button></div></div>
            </aside>
        </div>
    </section>
</template>

<style scoped>
.instructions-page { min-width: 0; min-height: 0; overflow-y: auto; }
.instructions-layout { display:grid; grid-template-columns:minmax(0,1fr) 320px; align-items:start; gap:24px; }
.instructions-list { min-width:0; }
.instructions-heading { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 24px; }.instructions-heading h1,.instruction-editor h1 { margin: 0; }.instructions-heading p { margin: 0; }
.instruction-editor { padding: 18px; border: 1px solid #dcecef; border-radius: 10px; background: #f8fbfa; }.instruction-editor h1 { font-size: 18px; margin: 0 0 16px; }.instruction-form-grid { display: grid; gap: 12px; align-items: end; }.instruction-form-grid label { display: grid; gap: 5px; color: #667085; font-size: 12px; }.instruction-form-grid input,.instruction-form-grid select { min-width: 0; padding: 9px 10px; border: 1px solid #cbdfe2; border-radius: 6px; background: #fff; }.instruction-file { grid-column: auto; }.instruction-form-actions { display:flex; flex-wrap:wrap; gap:8px; }
.instructions-table-wrap { overflow-x: auto; }.instructions-table { width: 100%; border-collapse: collapse; }.instructions-table th,.instructions-table td { padding: 12px 10px; text-align: left; vertical-align: middle; }.instructions-table th { white-space: nowrap; }.instructions-table td { border-top: 1px solid #edf3f4; }.instructions-table td small { display: block; margin-top: 3px; color: #667085; overflow-wrap: anywhere; }.empty-cell { padding: 36px 16px !important; text-align: center !important; color: #667085; }.instruction-actions { display: flex; gap: 8px; white-space: nowrap; }.instruction-actions a,.instruction-actions button { padding: 7px 10px; border: 0; border-radius: 6px; background: #e1f3e7; color: #176b2a; cursor: pointer; text-decoration: none; font: inherit; font-size: 12px; }
@media (max-width: 1000px) { .instructions-layout { grid-template-columns: 1fr; }.instruction-editor { order:-1; } }
@media (max-width: 800px) { .instructions-heading { display: block; }.instructions-heading p { margin-top: 6px; } }
</style>
