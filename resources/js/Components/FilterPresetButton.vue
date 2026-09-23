<script setup lang="ts">
import { computed, nextTick, onMounted, ref, toRaw, watch } from "vue";
import { FilterBuilder, Willow, type IApi } from "@svar-ui/vue-filter";
import { Locale } from "@svar-ui/vue-core";
import ruCore from "@svar-ui/core-locales/locales/ru";
import type {
    FilterField,
    FilterOptions,
    FilterPreset,
    FilterPresetState,
    FilterRules,
} from "../lib/tableFilters";
import { filterOptionsFromRows } from "../lib/tableFilters";

const props = withDefaults(
    defineProps<{
        state: FilterPresetState;
        fields: FilterField[];
        options?: FilterOptions;
        rows?: unknown[];
    }>(),
    { options: () => ({}), rows: () => [] },
);

const open = ref(false);
const api = ref<IApi | null>(null);
const initial = ref<FilterRules>({ glue: "and", rules: [] });
const builderKey = ref(0);
const editing = ref<FilterPreset | null>(null);
const name = ref("");
const saving = ref(false);
const error = ref("");
const ru = {
    ...ruCore,
    filter: {
        "Add filter": "Добавить условие",
        "Add group": "Добавить группу",
        Edit: "Изменить",
        Delete: "Удалить",
        "Select all": "Выбрать все",
        "Unselect all": "Снять выбор",
        Cancel: "Отмена",
        Apply: "Применить",
        and: "и",
        or: "или",
        in: "в списке",
        equal: "равно",
        "not equal": "не равно",
        contains: "содержит",
        "not contains": "не содержит",
        "begins with": "начинается с",
        "not begins with": "не начинается с",
        "ends with": "заканчивается на",
        "not ends with": "не заканчивается на",
        greater: "больше",
        "greater or equal": "больше или равно",
        less: "меньше",
        "less or equal": "меньше или равно",
        between: "между",
        "not between": "не между",
        "Click to select": "Нажмите для выбора",
        None: "Не выбрано",
        "filter by": "фильтровать по",
    },
};

const activeCount = computed(
    () =>
        props.state.presets.value.filter((preset) => preset.is_active).length +
        (props.state.draft.value?.rules?.length ? 1 : 0),
);
const resolvedOptions = computed(() =>
    filterOptionsFromRows(props.rows, props.fields, props.options),
);

function show(preset?: FilterPreset | null): void {
    editing.value = preset ?? null;
    name.value = preset?.name ?? "";
    const rules =
        preset?.rules ??
        props.state.draft.value ??
        ({ glue: "and", rules: [] } as FilterRules);
    initial.value = structuredClone(toRaw(rules));
    error.value = "";
    builderKey.value++;
    open.value = true;
}

function value(): FilterRules {
    const rules = structuredClone(
        api.value?.getValue() ?? initial.value,
    ) as any;
    const types = new Map(
        props.fields.map((field) => [field.id, field.type] as const),
    );
    const enrich = (node: any): void => {
        if (Array.isArray(node?.rules)) {
            node.rules.forEach(enrich);
            return;
        }
        if (typeof node?.field === "string" && !node.type)
            node.type = types.get(node.field);
    };
    enrich(rules);
    return rules;
}

function valid(rules: FilterRules): boolean {
    if (rules.rules?.length) return true;
    error.value = "Добавьте хотя бы одно условие.";
    return false;
}

function apply(): void {
    const rules = value();
    if (!valid(rules)) return;
    props.state.draft.value = structuredClone(rules);
    open.value = false;
}

async function save(): Promise<void> {
    const rules = value();
    if (!valid(rules)) return;
    if (!name.value.trim()) {
        error.value = "Введите название фильтра.";
        return;
    }
    saving.value = true;
    try {
        if (editing.value)
            await props.state.update(editing.value, name.value.trim(), rules);
        else await props.state.create(name.value.trim(), rules);
        error.value = "";
        open.value = false;
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : "Не удалось сохранить фильтр.";
    } finally {
        saving.value = false;
    }
}

watch(
    () => props.state.editRequest.value,
    async (preset) => {
        if (preset === undefined) return;
        props.state.editRequest.value = undefined;
        await nextTick();
        show(preset);
    },
);

onMounted(() => void props.state.load());
</script>

<template>
    <button
        type="button"
        class="filter-builder-button"
        title="Фильтры"
        aria-label="Открыть конструктор фильтров"
        @click="show()"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 5h16l-6.3 7.2v5.3l-3.4 1.7v-7z" />
        </svg>
        <span v-if="activeCount" class="filter-builder-count">{{
            activeCount
        }}</span>
    </button>

    <Teleport to="body">
        <div
            v-if="open"
            class="filter-builder-overlay"
            @mousedown.self="open = false"
        >
            <section
                class="filter-builder-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="filter-builder-title"
            >
                <header>
                    <h2 id="filter-builder-title">
                        {{
                            editing
                                ? "Изменение фильтра"
                                : "Конструктор фильтров"
                        }}
                    </h2>
                    <button
                        type="button"
                        aria-label="Закрыть"
                        @click="open = false"
                    >
                        ×
                    </button>
                </header>
                <div class="filter-builder-body">
                    <Locale :words="ru">
                        <Willow :fonts="false">
                            <FilterBuilder
                                :key="builderKey"
                                :fields="fields"
                                :options="resolvedOptions"
                                :value="initial"
                                type="list"
                                :init="(value: IApi) => (api = value)"
                            />
                        </Willow>
                    </Locale>
                </div>
                <label class="filter-builder-name">
                    Название сохранённого варианта
                    <input
                        v-model="name"
                        maxlength="80"
                        placeholder="Например, Активные клиенты"
                    />
                </label>
                <p v-if="error" class="filter-builder-error" role="alert">
                    {{ error }}
                </p>
                <footer>
                    <button
                        type="button"
                        class="secondary"
                        @click="open = false"
                    >
                        Отмена
                    </button>
                    <button
                        v-if="!editing"
                        type="button"
                        class="secondary"
                        @click="apply"
                    >
                        Применить без сохранения
                    </button>
                    <button
                        type="button"
                        class="primary"
                        :disabled="saving"
                        @click="save"
                    >
                        {{ saving ? "Сохранение…" : "Сохранить" }}
                    </button>
                </footer>
            </section>
        </div>
    </Teleport>
</template>

<style scoped>
.filter-builder-button {
    position: relative;
    display: inline-grid;
    place-items: center;
    width: 32px;
    height: 32px;
    padding: 5px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: #60746a;
    cursor: pointer;
}
.filter-builder-button:hover {
    background: #eef7f0;
    color: #1e892f;
}
.filter-builder-button svg {
    width: 22px;
    height: 22px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linejoin: round;
}
.filter-builder-count {
    position: absolute;
    top: -2px;
    right: -3px;
    display: grid;
    place-items: center;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    border-radius: 999px;
    background: #1e892f;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
}
.filter-builder-overlay {
    position: fixed;
    /* SVAR переносит списки выбора в body с z-index: 1001. */
    z-index: 1000;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 20px;
    background: rgb(16 24 40 / 42%);
}
.filter-builder-modal {
    display: flex;
    flex-direction: column;
    width: min(820px, 100%);
    max-height: min(760px, calc(100vh - 40px));
    overflow: hidden;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 24px 70px rgb(16 24 40 / 28%);
}
.filter-builder-modal header,
.filter-builder-modal footer {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    border-bottom: 1px solid #e4ece7;
}
.filter-builder-modal header h2 {
    flex: 1;
    margin: 0;
    color: #183526;
    font-size: 18px;
}
.filter-builder-modal header button {
    border: 0;
    background: transparent;
    color: #66756f;
    font-size: 25px;
    cursor: pointer;
}
.filter-builder-body {
    min-height: 220px;
    overflow: auto;
    padding: 18px;
}
.filter-builder-name {
    display: grid;
    gap: 5px;
    padding: 0 18px 12px;
    color: #52645b;
    font-size: 12px;
}
.filter-builder-name input {
    height: 38px;
    padding: 7px 10px;
    border: 1px solid #d5e3d8;
    border-radius: 7px;
    font: inherit;
}
.filter-builder-error {
    margin: 0 18px 10px;
    color: #b42318;
    font-size: 12px;
}
.filter-builder-modal footer {
    justify-content: flex-end;
    border-top: 1px solid #e4ece7;
    border-bottom: 0;
}
@media (max-width: 900px) {
    .filter-builder-overlay {
        padding: 0;
    }
    .filter-builder-modal {
        width: 100%;
        height: 100%;
        max-height: none;
        border-radius: 0;
    }
    .filter-builder-body {
        flex: 1;
    }
    .filter-builder-modal footer {
        flex-wrap: wrap;
    }
    .filter-builder-modal footer button {
        flex: 1 1 auto;
    }
}
</style>
