<script setup lang="ts">
const props = withDefaults(defineProps<{
    modelValue: File | null;
    accept?: string;
    hint?: string;
}>(), { accept: "", hint: "Перетащите файл сюда или выберите его" });
const emit = defineEmits<{ "update:modelValue": [File | null] }>();
function select(event: Event) {
    emit("update:modelValue", (event.target as HTMLInputElement).files?.[0] ?? null);
}
function drop(event: DragEvent) {
    event.preventDefault();
    emit("update:modelValue", event.dataTransfer?.files?.[0] ?? null);
}
</script>
<template>
    <label class="file-dropzone" @dragover.prevent @drop="drop">
        <input type="file" :accept="props.accept" @change="select" />
        <span class="file-dropzone-icon">↑</span>
        <strong>{{ props.modelValue?.name || "Загрузить файл" }}</strong>
        <small>{{ props.modelValue ? "Файл выбран" : props.hint }}</small>
    </label>
</template>
<style scoped>
.file-dropzone { display:grid; justify-items:center; gap:5px; min-height:118px; box-sizing:border-box; padding:16px 12px; border:1px dashed #8bc79a; border-radius:9px; background:#f8fbfa; color:#26547c; text-align:center; cursor:pointer; }
.file-dropzone:hover { border-color:#1e892f; background:#f0faf3; }
.file-dropzone input { display:none; }
.file-dropzone-icon { display:grid; place-items:center; width:28px; height:28px; border-radius:50%; background:#e1f3e7; color:#1e892f; font-size:20px; line-height:1; }
.file-dropzone strong { max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px; }
.file-dropzone small { color:#667085; font-size:11px; }
</style>
