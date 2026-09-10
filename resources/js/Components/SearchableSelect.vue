<script setup lang="ts">
import { computed, nextTick, ref, useId } from "vue";

const props = defineProps<{
    modelValue: string | number | null;
    options: { id: string; label: string; search?: string }[];
    label: string;
    disabled?: boolean;
}>();
const emit = defineEmits<{ "update:modelValue": [value: string | null] }>();
const id = useId();
const root = ref<HTMLElement>();
const opened = ref(false);
const query = ref("");
const active = ref(0);
const selected = computed(() =>
    props.options.find((row) => row.id === String(props.modelValue)),
);
const display = computed(
    () =>
        selected.value?.label ??
        (props.modelValue ? `Недоступная запись №${props.modelValue}` : ""),
);
const matches = computed(() => {
    const search = query.value.trim().toLocaleLowerCase("ru");
    return props.options.filter((row) =>
        (row.label + " " + (row.search ?? ""))
            .toLocaleLowerCase("ru")
            .includes(search),
    );
});
const choices = computed(() => [
    { id: null, label: "Не выбрано" },
    ...matches.value.slice(0, 100),
]);
function open() {
    if (props.disabled) return;
    if (!opened.value) {
        query.value = "";
        active.value = 0;
        opened.value = true;
    }
}
function select(value: string | null) {
    if (props.disabled) return;
    emit("update:modelValue", value);
    opened.value = false;
}
function search(event: Event) {
    open();
    query.value = (event.target as HTMLInputElement).value;
    active.value = 0;
}
async function keydown(event: KeyboardEvent) {
    if (props.disabled) return;
    if (event.key === "Escape") {
        event.preventDefault();
        opened.value = false;
    } else if (event.key === "ArrowDown" || event.key === "ArrowUp") {
        event.preventDefault();
        open();
        active.value = Math.max(
            0,
            Math.min(
                choices.value.length - 1,
                active.value + (event.key === "ArrowDown" ? 1 : -1),
            ),
        );
        await nextTick();
        document
            .getElementById(`${id}-option-${active.value}`)
            ?.scrollIntoView({ block: "nearest" });
    } else if (event.key === "Enter" && opened.value) {
        event.preventDefault();
        select(choices.value[active.value]?.id ?? null);
    }
}
function blur(event: FocusEvent) {
    if (!root.value?.contains(event.relatedTarget as Node | null))
        opened.value = false;
}
</script>

<template>
    <div ref="root" class="searchable-select" @focusout="blur">
        <label :for="id">{{ label }}</label>
        <div class="select-control">
            <input
                :id="id"
                role="combobox"
                type="text"
                autocomplete="off"
                :disabled="disabled"
                :value="opened ? query : display"
                :placeholder="
                    opened ? 'Введите название, код или ID' : 'Не выбрано'
                "
                :aria-expanded="opened"
                :aria-controls="id + '-list'"
                :aria-activedescendant="
                    opened ? id + '-option-' + active : undefined
                "
                aria-autocomplete="list"
                @focus="open"
                @click="open"
                @input="search"
                @keydown="keydown"
            />
            <span class="select-arrow" aria-hidden="true">⌄</span>
        </div>
        <div v-if="opened && !disabled" class="select-dropdown">
            <ul :id="id + '-list'" role="listbox" :aria-label="label">
                <li
                    v-for="(option, index) in choices"
                    :id="id + '-option-' + index"
                    :key="option.id ?? 'empty'"
                    role="option"
                    :aria-selected="
                        String(modelValue ?? '') === String(option.id ?? '')
                    "
                    :class="{ active: active === index }"
                    @mousedown.prevent
                    @click="select(option.id)"
                    @mousemove="active = index"
                >
                    {{ option.label }}
                </li>
            </ul>
            <p v-if="!matches.length">Ничего не найдено</p>
            <p v-else-if="matches.length > 100">
                Показаны первые 100 записей. Уточните поиск.
            </p>
        </div>
    </div>
</template>

<style scoped>
.searchable-select {
    position: relative;
    display: grid;
    gap: 6px;
}
.select-control {
    position: relative;
}
.select-control input {
    width: 100%;
    border-color: #a8d4a9;
    padding-right: 30px;
}
.select-arrow {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
}
.select-dropdown {
    position: absolute;
    z-index: 20;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #a8d4a9;
    border-radius: 4px;
    box-shadow: 0 4px 12px #0002;
}
ul {
    max-height: 240px;
    overflow-y: auto;
    list-style: none;
    margin: 0;
    padding: 4px;
}
li {
    padding: 8px 10px;
    cursor: pointer;
    overflow-wrap: anywhere;
}
li.active {
    background: #edf5fa;
}
li[aria-selected="true"] {
    font-weight: 600;
}
p {
    padding: 8px 10px;
    margin: 0;
    color: #667085;
    font-size: 12px;
}
</style>
