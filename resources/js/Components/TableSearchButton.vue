<script setup lang="ts">
import { computed, nextTick, ref } from "vue";
import type { FilterField } from "../lib/tableFilters";
import type { TableSearchState } from "../lib/tableSearch";

const props = withDefaults(
    defineProps<{
        state: TableSearchState;
        fields: FilterField[];
        rows?: unknown[];
    }>(),
    { rows: () => [] },
);
const emit = defineEmits<{ navigate: [row: unknown] }>();
const root = ref<HTMLElement | null>(null);
const input = ref<HTMLInputElement | null>(null);
const fieldsOpen = ref(false);
const matches = computed(() => props.state.matching(props.rows));
const currentIndex = computed(() =>
    matches.value.findIndex((row) => props.state.isCurrent(row)),
);

async function open(): Promise<void> {
    props.state.expanded.value = true;
    await nextTick();
    input.value?.focus();
}

function closeIfEmpty(event?: FocusEvent): void {
    window.setTimeout(() => {
        if (
            !props.state.query.value.trim() &&
            (!event || !root.value?.contains(document.activeElement))
        ) {
            fieldsOpen.value = false;
            props.state.expanded.value = false;
        }
    });
}

function toggleField(id: string): void {
    const selected = props.state.selectedFields.value;
    if (selected.includes(id)) {
        if (selected.length === 1) return;
        props.state.selectFields(selected.filter((field) => field !== id));
    } else props.state.selectFields([...selected, id]);
}

async function navigate(direction: -1 | 1): Promise<void> {
    const row = props.state.navigate(props.rows, direction);
    if (!row) return;
    emit("navigate", row);
    await nextTick();
    const id = CSS.escape(props.state.identify(row));
    document
        .querySelector(`[data-table-search-id="${id}"]`)
        ?.scrollIntoView({ behavior: "smooth", block: "center" });
}

function keydown(event: KeyboardEvent): void {
    if (event.key === "Escape") {
        fieldsOpen.value = false;
        if (!props.state.query.value.trim()) props.state.expanded.value = false;
        return;
    }
    if (
        event.key === "Enter" &&
        props.state.mode.value === "highlight" &&
        props.state.active.value
    ) {
        event.preventDefault();
        navigate(event.shiftKey ? -1 : 1);
    }
}
</script>

<template>
    <div ref="root" class="table-search" @focusout="closeIfEmpty">
        <button
            v-if="!state.expanded.value"
            type="button"
            class="table-search-icon"
            title="Поиск"
            aria-label="Открыть поиск"
            @click="open"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="10.8" cy="10.8" r="6.3" />
                <path d="m15.5 15.5 4.2 4.2" />
            </svg>
        </button>
        <div v-else class="table-search-control">
            <input
                ref="input"
                v-model="state.query.value"
                type="search"
                aria-label="Поиск по таблице"
                placeholder="Поиск от 3 букв"
                @keydown="keydown"
            />
            <span
                v-if="
                    state.query.value.trim().length > 0 &&
                    state.query.value.trim().length < 3
                "
                class="table-search-hint"
                >Ещё {{ 3 - state.query.value.trim().length }}</span
            >
            <div class="table-search-fields">
                <button
                    type="button"
                    title="Поля поиска"
                    aria-label="Выбрать поля поиска"
                    :aria-expanded="fieldsOpen"
                    @click="fieldsOpen = !fieldsOpen"
                >
                    ☷
                </button>
                <div v-if="fieldsOpen" class="table-search-field-menu">
                    <label v-for="field in fields" :key="field.id">
                        <input
                            type="checkbox"
                            :checked="
                                state.selectedFields.value.includes(field.id)
                            "
                            :disabled="
                                state.selectedFields.value.length === 1 &&
                                state.selectedFields.value.includes(field.id)
                            "
                            @change="toggleField(field.id)"
                        />
                        <span>{{ field.label }}</span>
                    </label>
                </div>
            </div>
            <button
                type="button"
                class="table-search-mode"
                :class="{ highlight: state.mode.value === 'highlight' }"
                :title="
                    state.mode.value === 'filter'
                        ? 'Режим: фильтровать таблицу'
                        : 'Режим: подсвечивать совпадения'
                "
                @click="
                    state.mode.value =
                        state.mode.value === 'filter' ? 'highlight' : 'filter'
                "
            >
                {{ state.mode.value === "filter" ? "≡" : "◉" }}
            </button>
            <template
                v-if="state.mode.value === 'highlight' && state.active.value"
            >
                <span class="table-search-counter">
                    {{ matches.length ? currentIndex + 1 : 0 }}/{{
                        matches.length
                    }}
                </span>
                <button
                    type="button"
                    :disabled="!matches.length || currentIndex === 0"
                    aria-label="Предыдущее совпадение"
                    @click="navigate(-1)"
                >
                    ↑
                </button>
                <button
                    type="button"
                    :disabled="
                        !matches.length || currentIndex === matches.length - 1
                    "
                    aria-label="Следующее совпадение"
                    @click="navigate(1)"
                >
                    ↓
                </button>
            </template>
        </div>
    </div>
</template>

<style scoped>
.table-search {
    position: relative;
    max-width: 100%;
}
.table-search-icon {
    width: 38px;
    height: 38px;
    display: inline-grid;
    place-items: center;
    border: 1px solid #d9dee7;
    border-radius: 10px;
    background: #fff;
    color: #344054;
    cursor: pointer;
}
.table-search-icon svg {
    width: 20px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
}
.table-search-control {
    min-height: 38px;
    display: flex;
    align-items: center;
    gap: 3px;
    padding: 3px 4px 3px 10px;
    border: 1px solid #cfd6e2;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 1px 2px rgb(16 24 40 / 5%);
}
.table-search-control > input[type="search"] {
    width: clamp(120px, 18vw, 240px);
    min-width: 80px;
    border: 0;
    outline: 0;
    background: transparent;
}
.table-search-control button {
    min-width: 30px;
    height: 30px;
    padding: 0 6px;
    border: 0;
    border-radius: 7px;
    background: transparent;
    color: #475467;
    cursor: pointer;
}
.table-search-control button:hover {
    background: #f2f4f7;
}
.table-search-control button:disabled {
    opacity: 0.35;
    cursor: default;
}
.table-search-mode.highlight {
    color: #175cd3;
    background: #eff8ff;
}
.table-search-hint {
    color: #b54708;
    font-size: 11px;
    white-space: nowrap;
}
.table-search-counter {
    min-width: 38px;
    color: #667085;
    font-size: 11px;
    text-align: center;
    white-space: nowrap;
}
.table-search-fields {
    position: relative;
}
.table-search-field-menu {
    position: absolute;
    z-index: 80;
    top: calc(100% + 7px);
    right: 0;
    width: max-content;
    max-width: min(300px, 85vw);
    max-height: 300px;
    overflow: auto;
    padding: 8px;
    border: 1px solid #d0d5dd;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 12px 32px rgb(16 24 40 / 16%);
}
.table-search-field-menu label {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 32px;
    padding: 3px 6px;
    font-size: 13px;
    white-space: nowrap;
    cursor: pointer;
}
:global(tr.table-search-match),
:global(.table-search-match) {
    background: #fff6cc !important;
    box-shadow: inset 3px 0 #eaaa08;
}
:global(tr.table-search-current),
:global(.table-search-current) {
    background: #ffd98a !important;
    box-shadow:
        inset 4px 0 #dc6803,
        0 0 0 2px #fdb022;
}
@media (max-width: 700px) {
    :global(.page-heading-actions) {
        flex-wrap: wrap;
    }
    .table-search {
        flex: 1 1 auto;
    }
    .table-search-control {
        width: 100%;
        flex-wrap: wrap;
    }
    .table-search-control > input[type="search"] {
        flex: 1 1 100px;
        width: auto;
    }
}
</style>
