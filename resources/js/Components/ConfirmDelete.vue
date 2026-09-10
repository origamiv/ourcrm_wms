<script setup lang="ts">
import { onMounted, ref } from "vue";

defineProps<{ message: string; disabled?: boolean }>();
const emit = defineEmits<{ cancel: []; confirm: [] }>();
const dialog = ref<HTMLDialogElement | null>(null);
onMounted(() => dialog.value?.showModal());
</script>

<template>
    <dialog
        ref="dialog"
        class="delete-confirmation"
        aria-labelledby="delete-confirmation-message"
        @cancel.prevent="emit('cancel')"
    >
        <button
            class="delete-confirmation-close"
            aria-label="Закрыть подтверждение"
            @click="emit('cancel')"
        >
            <img src="/design/crm/close.svg" alt="" />
        </button>
        <p id="delete-confirmation-message">{{ message }}</p>
        <div class="delete-confirmation-actions">
            <button
                class="delete-confirmation-cancel"
                autofocus
                @click="emit('cancel')"
            >
                Отмена
            </button>
            <button
                class="delete-confirmation-submit"
                :disabled="disabled"
                @click="emit('confirm')"
            >
                Удалить
            </button>
        </div>
    </dialog>
</template>

<style scoped>
.delete-confirmation {
    width: 517px;
    max-width: calc(100vw - 32px);
    max-height: calc(100dvh - 32px);
    min-height: 167px;
    margin: auto;
    padding: 28px 20px 20px 40px;
    border: 1px solid #a8d4a9;
    border-radius: 10px;
    background: #fff;
    color: #0c1821;
    font:
        400 14px/1.3 Manrope,
        sans-serif;
}
.delete-confirmation::backdrop {
    background: rgb(152 157 182 / 40%);
}
.delete-confirmation p {
    margin: 0 28px 0 0;
    overflow-wrap: anywhere;
}
.delete-confirmation-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 16px;
    height: 16px;
    padding: 0;
    border: 0;
    background: transparent;
}
.delete-confirmation-close img {
    display: block;
    width: 16px;
    height: 16px;
}
.delete-confirmation-actions {
    display: flex;
    justify-content: flex-end;
    gap: 24px;
    margin-top: 52px;
}
.delete-confirmation-actions button {
    height: 48px;
    padding: 0 20px;
    border: 1px solid #1e892f;
    border-radius: 6px;
    font: inherit;
}
.delete-confirmation-cancel {
    color: #1e892f;
    background: #fff;
}
.delete-confirmation-submit {
    color: #fff;
    background: #1e892f;
}
@media (max-width: 540px) {
    .delete-confirmation {
        padding-left: 24px;
    }
}
</style>
