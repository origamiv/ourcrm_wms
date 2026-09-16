<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import { http, HttpError } from "../lib/http";
import { marked } from "marked";
import DOMPurify from "dompurify";

const props = defineProps<{ instructionId: string; section?: string }>();
const page = usePage<any>();
const row = ref<any>(null), error = ref(""), content = ref("");
const isText = computed(() => row.value?.content_type === "markdown");
const renderedMarkdown = computed(() => DOMPurify.sanitize(marked.parse(content.value) as string, { ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto|tel|#|\/))/i }));
async function load() { try { row.value = (await http(`/web/instructions/${props.instructionId}`)).data; if (isText.value) content.value = await (await fetch(row.value.content_url, { credentials: "same-origin" })).text(); } catch (e) { error.value = e instanceof HttpError ? e.message : "Не удалось загрузить инструкцию."; } }
onMounted(load);
</script>
<template>
    <Head :title="row?.name || 'Инструкция'" />
    <section class="page-content instruction-view-page">
        <div class="content-breadcrumb"><a :href="props.section ? `/${props.section}/help` : '/instructions'">Инструкции</a> › {{ row?.name || "Просмотр" }}</div>
        <div v-if="error" class="notice error">{{ error }}</div>
        <div v-else-if="!row" class="instruction-loading">Загрузка…</div>
        <template v-else><div class="instruction-view-heading"><div><h1>{{ row.name }}</h1><p>{{ row.original_filename }}</p></div><a class="secondary" :href="row.download_url">Скачать</a></div><article v-if="row.content_type === 'markdown'" class="instruction-markdown" v-html="renderedMarkdown"></article><iframe v-else-if="row.content_type === 'html'" class="instruction-frame" :src="row.content_url" sandbox=""></iframe><iframe v-else-if="row.content_type === 'pdf'" class="instruction-frame" :src="row.content_url"></iframe><video v-else-if="row.content_type === 'video'" class="instruction-video" controls :src="row.content_url"></video></template>
    </section>
</template>
<style scoped>
.instruction-view-page { min-width: 0; min-height: 0; overflow-y: auto; }.content-breadcrumb a { color: #2274a5; }.instruction-loading { color: #667085; }.instruction-view-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; max-width:960px; margin:0 auto 22px; }.instruction-view-heading h1 { margin:0; font-size:30px; line-height:1.2; }.instruction-view-heading p { margin:6px 0 0; color:#667085; }.instruction-frame { display:block; width:100%; min-height:70vh; border:1px solid #dcecef; border-radius:10px; background:#fff; }.instruction-markdown { display:flow-root; max-width:960px; box-sizing:border-box; margin:0 auto; padding:48px 64px 56px; overflow-wrap:anywhere; border:1px solid #e3e9e5; border-radius:3px; background:#fffdf8; box-shadow:0 10px 28px rgb(35 67 48 / 8%); color:#253237; font:16px/1.8 Manrope, sans-serif; }.instruction-markdown :deep(h1) { margin:0 0 22px; color:#172126; font-size:32px; line-height:1.2; }.instruction-markdown :deep(h2) { margin:30px 0 10px; padding-bottom:6px; border-bottom:1px solid #dce9df; color:#1e6d35; font-size:21px; line-height:1.3; }.instruction-markdown :deep(h3) { margin:24px 0 8px; color:#26547c; font-size:17px; }.instruction-markdown :deep(h2:first-child),.instruction-markdown :deep(h3:first-child) { margin-top:0; }.instruction-markdown :deep(p) { margin:0 0 17px; text-wrap:pretty; }.instruction-markdown :deep(strong) { color:#173e27; font-weight:750; }.instruction-markdown :deep(ol),.instruction-markdown :deep(ul) { margin:10px 0 20px; padding:14px 22px 14px 42px; border-left:3px solid #a8d4a9; background:#f4faf5; }.instruction-markdown :deep(li) { margin:5px 0; padding-left:4px; }.instruction-markdown :deep(code) { padding:2px 6px; border-radius:4px; background:#e8f3eb; color:#176b2a; font-size:.9em; }.instruction-markdown :deep(pre) { clear:both; overflow-x:auto; margin:18px 0; padding:16px; border-radius:7px; background:#172126; color:#fff; }.instruction-markdown :deep(img) { float:right; display:block; width:min(42%, 360px); height:auto; max-height:240px; margin:4px 0 22px 30px; border:1px solid #cfe0d3; border-radius:8px; box-shadow:0 5px 14px rgb(35 67 48 / 10%); object-fit:cover; object-position:top; }.instruction-markdown :deep(a) { color:#2274a5; text-decoration:underline; text-underline-offset:2px; }.instruction-video { display:block; width:min(100%, 1000px); max-height:75vh; border-radius:10px; background:#111; }
@media (max-width: 700px) { .instruction-view-heading { display:block; }.instruction-view-heading .secondary { display:inline-block; margin-top:12px; }.instruction-frame { min-height:65vh; }.instruction-markdown { padding:28px 22px 34px; font-size:14px; line-height:1.7; }.instruction-markdown :deep(h1) { font-size:25px; }.instruction-markdown :deep(h2) { font-size:19px; }.instruction-markdown :deep(img) { float:none; width:100%; max-height:none; margin:12px 0 20px; } }
</style>
