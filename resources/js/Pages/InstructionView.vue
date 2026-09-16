<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import { http, HttpError } from "../lib/http";

const props = defineProps<{ instructionId: string; section?: string }>();
const page = usePage<any>();
const row = ref<any>(null), error = ref(""), content = ref("");
const isText = computed(() => row.value?.content_type === "markdown");
async function load() { try { row.value = (await http(`/web/instructions/${props.instructionId}`)).data; if (isText.value) content.value = await (await fetch(row.value.content_url, { credentials: "same-origin" })).text(); } catch (e) { error.value = e instanceof HttpError ? e.message : "Не удалось загрузить инструкцию."; } }
onMounted(load);
</script>
<template>
    <Head :title="row?.name || 'Инструкция'" />
    <section class="page-content instruction-view-page">
        <div class="content-breadcrumb"><a :href="props.section ? `/${props.section}/help` : '/instructions'">Инструкции</a> › {{ row?.name || "Просмотр" }}</div>
        <div v-if="error" class="notice error">{{ error }}</div>
        <div v-else-if="!row" class="instruction-loading">Загрузка…</div>
        <template v-else><div class="instruction-view-heading"><div><h1>{{ row.name }}</h1><p>{{ row.original_filename }}</p></div><a class="secondary" :href="row.download_url">Скачать</a></div><pre v-if="row.content_type === 'markdown'" class="instruction-markdown">{{ content }}</pre><iframe v-else-if="row.content_type === 'html'" class="instruction-frame" :src="row.content_url" sandbox=""></iframe><iframe v-else-if="row.content_type === 'pdf'" class="instruction-frame" :src="row.content_url"></iframe><video v-else-if="row.content_type === 'video'" class="instruction-video" controls :src="row.content_url"></video></template>
    </section>
</template>
<style scoped>
.instruction-view-page { min-width: 0; min-height: 0; overflow-y: auto; }.content-breadcrumb a { color: #2274a5; }.instruction-loading { color: #667085; }.instruction-view-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:20px; }.instruction-view-heading h1 { margin:0; }.instruction-view-heading p { margin:6px 0 0; color:#667085; }.instruction-frame { display:block; width:100%; min-height:70vh; border:1px solid #dcecef; border-radius:10px; background:#fff; }.instruction-markdown { min-height:60vh; margin:0; padding:24px; white-space:pre-wrap; overflow-wrap:anywhere; border:1px solid #dcecef; border-radius:10px; background:#fff; font:14px/1.65 Manrope, sans-serif; }.instruction-video { display:block; width:min(100%, 1000px); max-height:75vh; border-radius:10px; background:#111; }
@media (max-width: 700px) { .instruction-view-heading { display:block; }.instruction-view-heading .secondary { display:inline-block; margin-top:12px; }.instruction-frame { min-height:65vh; } }
</style>
