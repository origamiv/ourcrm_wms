<script setup lang="ts">
import { ref } from "vue";
import { http, HttpError } from "../lib/http";
const props = defineProps<{ id: string; name: string; disabled: boolean }>();
const busy = ref(false),
    error = ref("");
async function download() {
    if (busy.value || props.disabled) return;
    busy.value = true;
    error.value = "";
    try {
        const blob = await http(
            `/web/clients/documents/${props.id}/download`,
            "GET",
            undefined,
            "blob",
        );
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `document_${props.id}.pdf`;
        document.body.append(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch (e) {
        error.value =
            e instanceof HttpError && e.body.errors
                ? Object.values(e.body.errors).flat().join(" ")
                : e instanceof Error
                  ? e.message
                  : "Не удалось скачать PDF.";
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <button
        :aria-label="`Скачать: ${name}`"
        :title="busy ? 'Генерация PDF…' : 'Скачать PDF'"
        :disabled="disabled || busy"
        :aria-busy="busy"
        @click.stop="download"
    >
        <svg
            viewBox="0 0 24 24"
            width="16"
            height="16"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
            aria-hidden="true"
        >
            <path d="M12 3v12m-5-5 5 5 5-5M4 15v6h16v-6" />
        </svg>
    </button>
    <Teleport to="body"
        ><div v-if="error" role="alert" class="pdf-error">
            <span>{{ error }}</span
            ><button aria-label="Закрыть ошибку скачивания" @click="error = ''">
                ×
            </button>
        </div></Teleport
    >
</template>
<style scoped>
.pdf-error {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 100;
    background: white;
    border: 1px solid #d77;
    border-radius: 8px;
    padding: 16px;
    max-width: min(520px, calc(100vw - 48px));
    box-shadow: 0 4px 20px #0002;
    display: flex;
    gap: 12px;
    font-size: 13px;
}
.pdf-error button {
    align-self: start;
}
</style>
