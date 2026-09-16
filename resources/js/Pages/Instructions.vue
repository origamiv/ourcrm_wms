<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import { http, HttpError } from "../lib/http";
import AdminTabs from "../Components/AdminTabs.vue";
import ClientTabs from "../Components/ClientTabs.vue";
import GoodsTabs from "../Components/GoodsTabs.vue";
import IntegrationTabs from "../Components/IntegrationTabs.vue";
import FulfillmentTabs from "../Components/FulfillmentTabs.vue";
import MaintenanceTabs from "../Components/MaintenanceTabs.vue";
import LogisticsTabs from "../Components/LogisticsTabs.vue";
import FileDropzone from "../Components/FileDropzone.vue";
import { formatDate } from "../lib/dates";
import { marked } from "marked";
import DOMPurify from "dompurify";

interface Instruction { id: string; name: string; shortname: string; section_key: string; content_type: string; original_filename: string; size: number; status: number; updated_at: string | null; content_url: string; download_url: string; }
const props = defineProps<{ instructionId?: string; sectionKey?: string }>();
const page = usePage<any>();
const rows = ref<Instruction[]>([]), loading = ref(true), error = ref(""), saving = ref(false), editingId = ref<string | null>(null);
const selectedInstruction = ref<Instruction | null>(null), selectedContent = ref("");
const openedImage = ref("");
const isAdminPage = computed(() => page.url.startsWith("/main/instructions"));
const section = computed(() => {
    const pathSection = page.url.split("?")[0].split("/")[1] || "";
    if (["main", "clients", "goods", "integration", "fulfillment", "maintenance", "logistics"].includes(pathSection) && page.url.split("?")[0].split("/")[2] === "help") return pathSection;
    return new URLSearchParams(page.url.split("?")[1] || "").get("section") || "";
});
const form = ref({ name: "", shortname: "", section_key: "main", sort_order: "0", status: "1", file: null as File | null });
const sections = [{ key: "main", name: "Администрирование" }, { key: "clients", name: "Клиенты" }, { key: "goods", name: "Товары" }, { key: "integration", name: "Интеграции" }, { key: "fulfillment", name: "Фулфилмент" }, { key: "maintenance", name: "Обслуживание" }, { key: "logistics", name: "Логистика" }];
const types: Record<string, string> = { pdf: "PDF", markdown: "Markdown", html: "HTML", video: "Видео" };
const isViewing = computed(() => Boolean(props.instructionId));
const renderedMarkdown = computed(() => DOMPurify.sanitize(marked.parse(selectedContent.value) as string, { ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto|tel|#|\/))/i }));

async function load() {
    loading.value = true;
    try {
        const query = isAdminPage.value ? "" : (section.value ? `?section=${encodeURIComponent(section.value)}` : "");
        rows.value = (await http(`/web/instructions${query}`)).data ?? [];
        if (props.instructionId) {
            selectedInstruction.value = (await http(`/web/instructions/${props.instructionId}`)).data;
            if (selectedInstruction.value?.content_type === "markdown") selectedContent.value = await (await fetch(selectedInstruction.value.content_url, { credentials: "same-origin" })).text();
        }
        error.value = "";
    }
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
function openInstruction(row: Instruction) { window.location.href = section.value ? `/${section.value}/help/${row.id}` : `/instructions/${row.id}`; }
function backToList() { window.location.href = section.value ? `/${section.value}/help` : "/instructions"; }
function openImage(event: MouseEvent) {
    const image = (event.target as HTMLElement).closest("img");
    if (image instanceof HTMLImageElement) openedImage.value = image.currentSrc || image.src;
}
function closeImage() { openedImage.value = ""; }
function handleEscape(event: KeyboardEvent) { if (event.key === "Escape") closeImage(); }
onMounted(load);
onMounted(() => window.addEventListener("keydown", handleEscape));
onUnmounted(() => window.removeEventListener("keydown", handleEscape));
</script>

<template>
    <Head :title="selectedInstruction?.name || 'Инструкции'" />
    <section class="page-content instructions-page">
        <div class="content-breadcrumb">{{ isAdminPage ? "Администрирование › Инструкции" : isViewing ? `Инструкции › ${selectedInstruction?.name || "Загрузка"}` : (sections.find((item) => item.key === section)?.name || "Инструкции") + " › Инструкции" }}</div>
        <AdminTabs v-if="isAdminPage || section === 'main'" />
        <ClientTabs v-else-if="section === 'clients'" />
        <GoodsTabs v-else-if="section === 'goods'" />
        <IntegrationTabs v-else-if="section === 'integration'" />
        <FulfillmentTabs v-else-if="section === 'fulfillment'" />
        <MaintenanceTabs v-else-if="section === 'maintenance'" />
        <LogisticsTabs v-else-if="section === 'logistics'" />
        <div v-else class="instructions-heading"><h1>Инструкции</h1><p class="muted">Инструкции по процессам текущего раздела</p></div>
        <p v-if="error" class="notice error">{{ error }}</p>
        <div v-if="isViewing" class="instruction-reader">
            <div v-if="error" class="notice error">{{ error }}</div>
            <div v-else-if="!selectedInstruction" class="instruction-loading">Загрузка…</div>
            <template v-else>
                <div class="instruction-reader-toolbar">
                    <button type="button" class="instruction-back" @click="backToList">← Инструкции</button>
                    <a class="instruction-download" :href="selectedInstruction.download_url" aria-label="Скачать инструкцию" title="Скачать инструкцию">↓</a>
                </div>
                <article v-if="selectedInstruction.content_type === 'markdown'" class="instruction-markdown" v-html="renderedMarkdown" @click="openImage"></article>
                <iframe v-else-if="selectedInstruction.content_type === 'html'" class="instruction-frame" :src="selectedInstruction.content_url" sandbox=""></iframe>
                <iframe v-else-if="selectedInstruction.content_type === 'pdf'" class="instruction-frame" :src="selectedInstruction.content_url"></iframe>
                <video v-else-if="selectedInstruction.content_type === 'video'" class="instruction-video" controls :src="selectedInstruction.content_url"></video>
            </template>
        </div>
        <div v-else class="instructions-layout">
            <div class="instructions-list">
                <div v-if="isAdminPage" class="page-heading"><div><h1>Инструкции</h1><p class="muted">Справочник инструкций по процессам разделов</p></div></div>
                <div v-else class="instructions-heading"><h1>Инструкции</h1><p class="muted">Инструкции по процессам текущего раздела</p></div>
                <div class="table-scroll instructions-table-wrap"><table class="instructions-table"><thead><tr><th>#</th><th>Название</th><th>Формат</th><th>Изменено</th><th>Действия</th></tr></thead><tbody><tr v-if="loading"><td colspan="5" class="empty-cell">Загрузка…</td></tr><tr v-else-if="!rows.length"><td colspan="5" class="empty-cell">Инструкций пока нет</td></tr><tr v-for="row in rows" :key="row.id" class="instruction-row instruction-mobile-card"><td data-label="#">{{ row.id }}</td><td data-label="Название"><strong>{{ row.name }}</strong><small>{{ row.original_filename }}</small></td><td data-label="Формат">{{ types[row.content_type] || row.content_type }}</td><td data-label="Изменено">{{ formatDate(row.updated_at, true) }}</td><td data-label="Действия" class="instruction-actions"><a class="instruction-open" :href="section ? `/${section}/help/${row.id}` : `/instructions/${row.id}`">Открыть инструкцию</a><button type="button" @click="download(row)">Скачать</button><template v-if="isAdminPage"><button type="button" @click="edit(row)">Изменить</button><button type="button" @click="remove(row)">Удалить</button></template></td></tr></tbody></table></div>
                <div class="instructions-mobile-cards">
                    <div v-if="loading" class="instructions-mobile-empty">Загрузка…</div>
                    <div v-else-if="!rows.length" class="instructions-mobile-empty">Инструкций пока нет</div>
                    <article v-for="row in rows" v-else :key="row.id" class="instructions-mobile-card" role="link" tabindex="0" @click="openInstruction(row)" @keydown.enter="openInstruction(row)">
                        <span class="instructions-mobile-card__id">{{ row.id }}</span>
                        <strong class="instructions-mobile-card__name">{{ row.name }}</strong>
                        <div v-if="isAdminPage" class="instructions-mobile-card__actions">
                            <template>
                                <button type="button" aria-label="Изменить инструкцию" title="Изменить" @click.stop="edit(row)">✎</button>
                                <button type="button" aria-label="Удалить инструкцию" title="Удалить" @click.stop="remove(row)">×</button>
                            </template>
                        </div>
                    </article>
                </div>
            </div>
            <aside v-if="isAdminPage" class="instruction-editor" aria-label="Создание и редактирование инструкции">
                <h1>{{ editingId ? "Изменить инструкцию" : "Добавить инструкцию" }}</h1>
                <div class="instruction-form-grid"><label>Название<input v-model="form.name" type="text" /></label><label>Краткое имя<input v-model="form.shortname" type="text" /></label><label>Раздел<select v-model="form.section_key"><option v-for="item in sections" :key="item.key" :value="item.key">{{ item.name }}</option></select></label><label>Порядок<input v-model="form.sort_order" type="number" min="0" /></label><label class="instruction-file">Файл<FileDropzone v-model="form.file" accept=".pdf,.md,.markdown,.html,.htm,.mp4" hint="PDF, Markdown, HTML или видео" /></label><div class="instruction-form-actions"><button class="primary" type="button" :disabled="saving" @click="save">{{ saving ? "Сохраняем…" : (editingId ? "Сохранить" : "Добавить") }}</button><button v-if="editingId" class="secondary" type="button" @click="resetForm">Отмена</button></div></div>
            </aside>
        </div>
        <div v-if="openedImage" class="image-modal" role="dialog" aria-modal="true" aria-label="Полноразмерное изображение" @click.self="closeImage">
            <button type="button" class="image-modal-close" aria-label="Закрыть изображение" title="Закрыть" @click="closeImage">×</button>
            <img :src="openedImage" alt="Полноразмерная иллюстрация инструкции" @click.stop />
        </div>
    </section>
</template>

<style scoped>
.instructions-page { min-width: 0; min-height: 0; overflow-y: auto; }
.instruction-reader { min-width: 0; }
.instruction-reader-toolbar { display:flex; align-items:center; justify-content:space-between; width:100%; max-width:960px; margin:0 0 12px; }
.instruction-back { padding:0; border:0; background:transparent; color:#2274a5; font:inherit; font-size:13px; cursor:pointer; }
.instruction-download { display:inline-grid; place-items:center; width:34px; height:34px; border:1px solid #a8d4a9; border-radius:7px; background:#e1f3e7; color:#176b2a; font-size:22px; line-height:1; text-decoration:none; }
.instruction-download:hover { background:#cdebd7; }
.instruction-frame { display:block; width:100%; min-height:70vh; border:1px solid #dcecef; border-radius:10px; background:#fff; }
.instruction-markdown { display:flow-root; max-width:960px; box-sizing:border-box; margin:0; padding:48px 64px 56px; overflow-wrap:anywhere; border:1px solid #e3e9e5; border-radius:3px; background:#fffdf8; box-shadow:0 10px 28px rgb(35 67 48 / 8%); color:#253237; font:16px/1.8 Manrope, sans-serif; }
.instruction-markdown :deep(h1) { margin:0 0 22px; color:#172126; font-size:32px; line-height:1.2; }.instruction-markdown :deep(h2) { margin:30px 0 10px; padding-bottom:6px; border-bottom:1px solid #dce9df; color:#1e6d35; font-size:21px; line-height:1.3; }.instruction-markdown :deep(h3) { margin:24px 0 8px; color:#26547c; font-size:17px; }.instruction-markdown :deep(h2:first-child),.instruction-markdown :deep(h3:first-child) { margin-top:0; }.instruction-markdown :deep(p) { margin:0 0 17px; text-wrap:pretty; }.instruction-markdown :deep(strong) { color:#173e27; font-weight:750; }.instruction-markdown :deep(ol),.instruction-markdown :deep(ul) { margin:10px 0 20px; padding:14px 22px 14px 42px; border-left:3px solid #a8d4a9; background:#f4faf5; }.instruction-markdown :deep(li) { margin:5px 0; padding-left:4px; }.instruction-markdown :deep(code) { padding:2px 6px; border-radius:4px; background:#e8f3eb; color:#176b2a; font-size:.9em; }.instruction-markdown :deep(pre) { clear:both; overflow-x:auto; margin:18px 0; padding:16px; border-radius:7px; background:#172126; color:#fff; }.instruction-markdown :deep(img) { float:right; display:block; width:min(55%, 520px); height:auto; max-height:360px; margin:4px 0 22px 30px; border:1px solid #cfe0d3; border-radius:8px; box-shadow:0 5px 14px rgb(35 67 48 / 10%); object-fit:cover; object-position:top; cursor:zoom-in; }.instruction-markdown :deep(img + img) { clear:right; }.instruction-markdown :deep(a) { color:#2274a5; text-decoration:underline; text-underline-offset:2px; }
.image-modal { position:fixed; inset:0; z-index:1000; display:grid; place-items:center; padding:32px; background:rgb(12 24 33 / 78%); cursor:zoom-out; }.image-modal img { max-width:92vw; max-height:92vh; width:auto; height:auto; border-radius:8px; background:#fff; box-shadow:0 16px 48px rgb(0 0 0 / 35%); cursor:default; }.image-modal-close { position:absolute; top:18px; right:24px; width:42px; height:42px; border:0; border-radius:50%; background:#fff; color:#172126; font-size:30px; line-height:1; cursor:pointer; }
.instruction-video { display:block; width:min(100%, 1000px); max-height:75vh; border-radius:10px; background:#111; }
.instructions-layout { display:grid; grid-template-columns:minmax(0,1fr) 320px; align-items:start; gap:24px; }
.instructions-list { min-width:0; }
.instructions-heading { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 24px; }.instructions-heading h1,.instruction-editor h1 { margin: 0; }.instructions-heading p { margin: 0; }
.instruction-editor { padding: 18px; border: 1px solid #dcecef; border-radius: 10px; background: #f8fbfa; }.instruction-editor h1 { font-size: 18px; margin: 0 0 16px; }.instruction-form-grid { display: grid; gap: 12px; align-items: end; }.instruction-form-grid label { display: grid; gap: 5px; color: #667085; font-size: 12px; }.instruction-form-grid input,.instruction-form-grid select { min-width: 0; padding: 9px 10px; border: 1px solid #cbdfe2; border-radius: 6px; background: #fff; }.instruction-file { grid-column: auto; }.instruction-form-actions { display:flex; flex-wrap:wrap; gap:8px; }
.instructions-table-wrap { overflow-x: auto; }.instructions-table { width: 100%; border-collapse: collapse; }.instructions-table th,.instructions-table td { padding: 12px 10px; text-align: left; vertical-align: middle; }.instructions-table th { white-space: nowrap; }.instructions-table td { border-top: 1px solid #edf3f4; }.instructions-table td small { display: block; margin-top: 3px; color: #667085; overflow-wrap: anywhere; }.empty-cell { padding: 36px 16px !important; text-align: center !important; color: #667085; }.instruction-actions { display: flex; gap: 8px; white-space: nowrap; }.instruction-actions a,.instruction-actions button { padding: 7px 10px; border: 0; border-radius: 6px; background: #e1f3e7; color: #176b2a; cursor: pointer; text-decoration: none; font: inherit; font-size: 12px; }
@media (max-width: 1000px) { .instructions-layout { grid-template-columns: 1fr; }.instruction-editor { order:-1; } }
@media (max-width: 800px) { .instructions-heading { display: block; }.instructions-heading p { margin-top: 6px; } }
@media (max-width: 700px) { .instruction-markdown { padding:28px 22px 34px; font-size:14px; line-height:1.7; }.instruction-markdown :deep(h1) { font-size:25px; }.instruction-markdown :deep(h2) { font-size:19px; }.instruction-markdown :deep(img) { float:none; width:100%; max-height:none; margin:12px 0 20px; } }
.instructions-mobile-cards { display: none; }
@media (max-width: 900px) {
    .instructions-table-wrap { display: none !important; }
    .instructions-mobile-cards { display: grid; gap: 10px; }
    .instructions-mobile-card { display: grid; grid-template-columns: max-content minmax(0, 1fr) max-content; align-items: center; min-width: 0; min-height: 48px; padding: 0; overflow: hidden; border: 1px solid #dcecef; border-radius: 10px; background: #fff; box-shadow: 0 2px 8px rgb(16 24 40 / 5%); cursor: pointer; }
    .instructions-mobile-card:not(:has(.instructions-mobile-card__actions)) { grid-template-columns: max-content minmax(0, 1fr); }
    .instructions-mobile-card:focus-visible { outline: 2px solid #2274a5; outline-offset: 2px; }
    .instructions-mobile-card__id { padding: 10px; color: #71828a; font-size: 11px; }
    .instructions-mobile-card__name { min-width: 0; padding: 10px 4px; overflow: hidden; color: #0c1821; font-size: 14px; text-overflow: ellipsis; white-space: nowrap; }
    .instructions-mobile-card__actions { display: flex; align-items: center; gap: 4px; padding: 6px 8px; }
    .instructions-mobile-card__actions a,
    .instructions-mobile-card__actions button { display: inline-grid; place-items: center; width: 32px; height: 32px; padding: 0; border: 0; border-radius: 6px; background: #e1f3e7; color: #176b2a; font: inherit; font-size: 18px; text-decoration: none; cursor: pointer; }
    .instructions-mobile-card__actions a:hover,
    .instructions-mobile-card__actions button:hover { background: #cdebd7; }
    .instructions-mobile-empty { padding: 28px 16px; border: 1px solid #dcecef; border-radius: 10px; background: #fff; color: #667085; text-align: center; }
}
</style>
