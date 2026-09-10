<script setup lang="ts">
import { ref, watch, onUnmounted, useId } from "vue";
import { http } from "../lib/http";
interface Suggestion {
    value: string;
    detail: string;
    fields: Record<string, any>;
}
const props = defineProps<{
    modelValue: string;
    type: "party" | "bank";
    label: string;
    disabled?: boolean;
    required?: boolean;
}>();
const emit = defineEmits<{
    "update:modelValue": [value: string];
    select: [fields: Record<string, any>];
}>();
const listId = useId();
const suggestions = ref<Suggestion[]>([]),
    active = ref(-1),
    loading = ref(false),
    message = ref("");
let timer: ReturnType<typeof setTimeout> | undefined;
let generation = 0;
let typed = "";
function clear() {
    clearTimeout(timer);
    generation++;
    suggestions.value = [];
    active.value = -1;
    loading.value = false;
    message.value = "";
}
function input(event: Event) {
    clear();
    typed = (event.target as HTMLInputElement).value;
    emit("update:modelValue", typed);
    if (props.disabled || typed.trim().length < 2) return;
    const query = typed;
    const request = generation;
    timer = setTimeout(async () => {
        loading.value = true;
        try {
            const response = await http(
                `/web/companies/suggestions/${props.type}`,
                "POST",
                { query },
            );
            if (request !== generation || props.disabled) return;
            suggestions.value = response.suggestions;
            if (!suggestions.value.length) message.value = "Ничего не найдено";
        } catch (error) {
            if (request === generation)
                message.value =
                    error instanceof Error
                        ? error.message
                        : "Подсказки недоступны";
        } finally {
            if (request === generation) loading.value = false;
        }
    }, 350);
}
function choose(index: number) {
    if (props.disabled || !suggestions.value[index]) return;
    const fields = suggestions.value[index].fields;
    clear();
    emit("select", fields);
}
function keydown(event: KeyboardEvent) {
    if (event.key === "Escape") {
        event.preventDefault();
        clear();
    }
    if (
        suggestions.value.length &&
        ["ArrowDown", "ArrowUp"].includes(event.key)
    ) {
        event.preventDefault();
        active.value =
            active.value < 0
                ? event.key === "ArrowDown"
                    ? 0
                    : suggestions.value.length - 1
                : (active.value +
                      (event.key === "ArrowDown" ? 1 : -1) +
                      suggestions.value.length) %
                  suggestions.value.length;
    }
    if (event.key === "Enter" && (suggestions.value.length || loading.value)) {
        event.preventDefault();
        if (active.value >= 0) choose(active.value);
    }
}
watch(
    () => props.disabled,
    (disabled) => {
        if (disabled) clear();
    },
);
watch(
    () => props.modelValue,
    (value) => {
        if (value !== typed) clear();
    },
);
onUnmounted(clear);
</script>
<template>
    <div class="dadata-input">
        <input
            :value="modelValue"
            @input="input"
            @keydown="keydown"
            @blur="clear"
            :disabled="disabled"
            :required="required"
            maxlength="255"
            autocomplete="off"
            :aria-label="label"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="!!suggestions.length"
            :aria-controls="listId"
            :aria-activedescendant="
                active >= 0 ? `${listId}-${active}` : undefined
            "
        />
        <ul
            v-if="suggestions.length"
            :id="listId"
            role="listbox"
            :aria-label="`Подсказки: ${label}`"
        >
            <li
                v-for="(item, index) in suggestions"
                :id="`${listId}-${index}`"
                :key="index"
                role="option"
                :aria-selected="active === index"
                @mousedown.prevent
                @click="choose(index)"
                :class="{ active: active === index }"
            >
                <strong>{{ item.value }}</strong
                ><small>{{ item.detail }}</small>
            </li>
        </ul>
        <small
            v-if="loading || message"
            class="suggestion-status"
            role="status"
            >{{ loading ? "Поиск в DaData…" : message }}</small
        >
    </div>
</template>
<style scoped>
.dadata-input {
    position: relative;
    min-width: 0;
}
.dadata-input input {
    border-color: #a8d4a9;
}
.dadata-input ul {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 5;
    max-height: 280px;
    overflow-y: auto;
    margin: 3px 0 0;
    padding: 0;
    list-style: none;
    border: 1px solid #a8d4a9;
    border-radius: 6px;
    background: white;
    box-shadow: 0 4px 12px #0c18211a;
}
.dadata-input li {
    cursor: pointer;
    padding: 10px 12px;
    overflow-wrap: anywhere;
}
.dadata-input li:hover,
.dadata-input li.active {
    background: #e1f3e7;
}
.dadata-input strong,
.dadata-input small {
    display: block;
}
.dadata-input strong {
    font-size: 12px;
    font-weight: 500;
}
.dadata-input small {
    font-size: 11px;
    color: #858585;
    margin-top: 4px;
}
</style>
