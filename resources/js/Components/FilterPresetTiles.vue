<script setup lang="ts">
import type { FilterPreset, FilterPresetState } from "../lib/tableFilters";

const props = defineProps<{ state: FilterPresetState }>();

async function remove(preset: FilterPreset): Promise<void> {
    if (!window.confirm(`Удалить фильтр «${preset.name}»?`)) return;
    try {
        await props.state.remove(preset);
    } catch (exception) {
        props.state.error.value =
            exception instanceof Error
                ? exception.message
                : "Не удалось удалить фильтр.";
    }
}
async function toggle(preset: FilterPreset): Promise<void> {
    try {
        await props.state.toggle(preset);
        props.state.error.value = "";
    } catch (exception) {
        props.state.error.value =
            exception instanceof Error
                ? exception.message
                : "Не удалось изменить активность фильтра.";
    }
}
</script>

<template>
    <div
        v-if="
            state.loading.value ||
            state.presets.value.length ||
            state.draft.value
        "
        class="filter-preset-area"
    >
        <span v-if="state.loading.value" class="filter-preset-loading"
            >Загрузка фильтров…</span
        >
        <div
            v-if="state.draft.value"
            class="filter-preset-tile active temporary"
        >
            <button type="button" @click="state.draft.value = null">
                Несохранённый фильтр
            </button>
            <button
                type="button"
                title="Изменить"
                aria-label="Изменить несохранённый фильтр"
                @click="state.editRequest.value = null"
            >
                ✎
            </button>
            <button
                type="button"
                title="Убрать"
                aria-label="Убрать несохранённый фильтр"
                @click="state.draft.value = null"
            >
                ×
            </button>
        </div>
        <div
            v-for="preset in state.presets.value"
            :key="preset.id"
            class="filter-preset-tile"
            :class="{ active: preset.is_active }"
        >
            <button
                type="button"
                :aria-pressed="preset.is_active"
                @click="toggle(preset)"
            >
                {{ preset.name }}
            </button>
            <button
                type="button"
                title="Изменить"
                :aria-label="`Изменить фильтр ${preset.name}`"
                @click="state.editRequest.value = preset"
            >
                ✎
            </button>
            <button
                type="button"
                title="Удалить"
                :aria-label="`Удалить фильтр ${preset.name}`"
                @click="remove(preset)"
            >
                ×
            </button>
        </div>
    </div>
    <p v-if="state.error.value" class="filter-preset-error" role="alert">
        {{ state.error.value }}
    </p>
</template>

<style scoped>
.filter-preset-area {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 7px;
    width: 100%;
    padding: 8px 0;
}
.filter-preset-tile {
    display: flex;
    align-items: center;
    overflow: hidden;
    border: 1px solid #cad8d0;
    border-radius: 8px;
    background: #fff;
}
.filter-preset-tile.active {
    border-color: #1e892f;
    background: #eaf6ed;
    box-shadow: inset 0 0 0 1px #1e892f;
}
.filter-preset-tile.temporary {
    border-style: dashed;
}
.filter-preset-tile button {
    min-height: 30px;
    padding: 5px 9px;
    border: 0;
    background: transparent;
    color: #46584f;
    font-size: 11px;
    cursor: pointer;
}
.filter-preset-tile button + button {
    padding-inline: 6px;
    border-left: 1px solid rgb(30 137 47 / 18%);
}
.filter-preset-tile.active button:first-child {
    color: #176d26;
    font-weight: 700;
}
.filter-preset-loading,
.filter-preset-error {
    color: #687a70;
    font-size: 11px;
}
.filter-preset-error {
    margin: 0 0 7px;
    color: #b42318;
}
</style>
