<script setup lang="ts">
import { computed, onMounted, ref } from "vue";

interface Column {
    key: string;
    label: string;
}

export interface ColumnSettings {
    order: string[];
    hidden: string[];
}

const props = withDefaults(
    defineProps<{
        columns: Column[];
        storageKey: string;
        fixed?: string[];
    }>(),
    { fixed: () => [] },
);
const settings = defineModel<ColumnSettings>({ default: () => ({ order: [], hidden: [] }) });
const open = ref(false);
const fixed = computed(() => new Set(props.fixed));
const ordered = computed(() => {
    const keys = new Set(props.columns.map((column) => column.key));
    const saved = settings.value.order.filter((key) => keys.has(key));
    return [
        ...saved,
        ...props.columns.map((column) => column.key).filter((key) => !saved.includes(key)),
    ].map((key) => props.columns.find((column) => column.key === key)!);
});
function isVisible(key: string) {
    return fixed.value.has(key) || !settings.value.hidden.includes(key);
}
function persist() {
    localStorage.setItem(props.storageKey, JSON.stringify(settings.value));
}
function toggle(key: string) {
    if (fixed.value.has(key)) return;
    settings.value = {
        ...settings.value,
        hidden: isVisible(key)
            ? [...settings.value.hidden, key]
            : settings.value.hidden.filter((item) => item !== key),
    };
    persist();
}
const dragged = ref<string | null>(null);
function drop(target: string) {
    if (!dragged.value || dragged.value === target) return;
    const keys = ordered.value.map((column) => column.key);
    const from = keys.indexOf(dragged.value);
    const to = keys.indexOf(target);
    if (from < 0 || to < 0) return;
    keys.splice(from, 1);
    keys.splice(to, 0, dragged.value);
    settings.value = { ...settings.value, order: keys };
    persist();
    dragged.value = null;
}
function toggleOpen() {
    open.value = !open.value;
}
onMounted(() => {
    try {
        const saved = JSON.parse(localStorage.getItem(props.storageKey) ?? "null");
        if (Array.isArray(saved)) settings.value = { order: saved.map(String), hidden: [] };
        else if (saved && typeof saved === "object") {
            settings.value = {
                order: Array.isArray(saved.order) ? saved.order.map(String) : [],
                hidden: Array.isArray(saved.hidden) ? saved.hidden.map(String) : [],
            };
        }
    } catch {
        // Повреждённые настройки заменяются значениями по умолчанию.
    }
});
</script>

<template>
    <button
        type="button"
        class="column-settings-button"
        title="Настроить колонки"
        aria-label="Настроить колонки"
        @click.stop="toggleOpen"
    >⚙</button>
    <div v-if="open" class="column-settings-panel" role="dialog" aria-label="Настройка колонок" @click.stop>
        <div class="column-settings-title">
            <span>Показывать колонки</span>
            <button type="button" class="column-settings-close" aria-label="Закрыть" @click="open = false">×</button>
        </div>
        <div
            v-for="column in ordered"
            :key="column.key"
            class="column-settings-item"
            draggable="true"
            @dragstart="dragged = column.key"
            @dragover.prevent
            @drop="drop(column.key)"
        >
            <label class="column-settings-control">
                <input type="checkbox" :checked="isVisible(column.key)" :disabled="fixed.has(column.key)" @change="toggle(column.key)" />
                <span class="column-drag-handle" aria-hidden="true">⠿</span>
                <span class="column-settings-label">{{ column.label }}</span>
            </label>
        </div>
    </div>
</template>

<style scoped>
.column-settings-button { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; margin-left:6px; border:0; border-radius:4px; background:transparent; color:#667085; font-size:19px; line-height:1; cursor:pointer; }
.column-settings-button:hover { background:#eef7f0; color:#2274a5; }
.column-settings-panel { position:absolute; z-index:99999; top:42px; right:8px; display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,max-content)); gap:8px; width:max-content; min-width:220px; max-width:min(720px,calc(100vw - 32px)); max-height:min(70vh,520px); overflow-y:auto; padding:10px; border:1px solid #d7e5db; border-radius:8px; background:#fff; color:#344054; box-shadow:0 10px 24px rgb(16 24 40 / 14%); }
.column-settings-title { grid-column:1/-1; display:flex; align-items:center; justify-content:space-between; font-size:12px; font-weight:700; }
.column-settings-close { border:0; background:transparent; color:#667085; font-size:20px; line-height:1; cursor:pointer; }
.column-settings-item { min-width:0; border:1px solid transparent; border-radius:5px; }
.column-settings-item:hover { border-color:#d7e5db; background:#f8fafc; }
.column-settings-control { display:flex; align-items:center; gap:6px; min-height:30px; padding:4px 6px; margin:0; cursor:grab; }
.column-settings-control input { width:16px; height:16px; margin:0; padding:0; accent-color:#2274a5; }
.column-drag-handle { color:#98a2b3; font-size:15px; }
.column-settings-label { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
</style>
