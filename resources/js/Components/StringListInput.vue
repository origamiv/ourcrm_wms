<script setup lang="ts">
import { computed, nextTick, ref } from "vue";

const props = defineProps<{ modelValue: string[]; label: string }>();
const emit = defineEmits<{ "update:modelValue": [value: string[]] }>();
const container = ref<HTMLElement>();
const rows = computed(() =>
    props.modelValue.length ? props.modelValue : [""],
);

function update(index: number, event: Event) {
    const values = [...rows.value];
    values[index] = (event.target as HTMLInputElement).value;
    emit("update:modelValue", values);
}
async function add(index: number) {
    const values = [...rows.value];
    values.splice(index + 1, 0, "");
    emit("update:modelValue", values);
    await nextTick();
    container.value?.querySelectorAll("input")[index + 1]?.focus();
}
function remove(index: number) {
    const values = [...rows.value];
    values.splice(index, 1);
    emit("update:modelValue", values);
}
</script>

<template>
    <div ref="container" class="string-list" role="group" :aria-label="label">
        <span>{{ label }}</span>
        <div
            v-for="(value, index) in rows"
            :key="index"
            class="string-list-row"
        >
            <input
                type="text"
                :value="value"
                :aria-label="`${label}: ${index + 1}`"
                maxlength="255"
                @input="update(index, $event)"
            />
            <button
                type="button"
                :aria-label="`Добавить строку: ${label}, ${index + 1}`"
                title="Добавить строку"
                @click="add(index)"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M12 5v14M5 12h14"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />
                </svg>
            </button>
            <button
                type="button"
                :aria-label="`Удалить строку: ${label}, ${index + 1}`"
                title="Удалить строку"
                @click="remove(index)"
            >
                <img src="/design/crm/delete.svg" alt="" />
            </button>
        </div>
    </div>
</template>

<style scoped>
.string-list {
    display: grid;
    gap: 6px;
}
.string-list-row {
    display: flex;
    align-items: center;
    gap: 8px;
}
.string-list-row input {
    flex: 1;
    min-width: 0;
    width: 0;
    border-color: #a8d4a9;
}
.string-list-row button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 30px;
    width: 30px;
    height: 32px;
    padding: 4px;
    border: 0;
    background: transparent;
    color: #2274a5;
    cursor: pointer;
}
.string-list-row button:disabled {
    opacity: 0.45;
    cursor: default;
}
.string-list-row button:not(:disabled):hover {
    background: #edf5fa;
    border-radius: 4px;
}
.string-list-row img,
.string-list-row svg {
    width: 20px;
    height: 20px;
}
</style>
