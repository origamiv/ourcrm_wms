<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(defineProps<{ value: number | null }>(), { value: null });

const percentage = computed(() => props.value === null ? 0 : Math.max(0, Math.min(100, Math.round(props.value))));
const label = computed(() => props.value === null ? "—" : `${percentage.value}%`);
</script>

<template>
    <span class="progress-bar" role="progressbar" :aria-valuenow="value === null ? undefined : percentage" aria-valuemin="0" aria-valuemax="100" :aria-label="`Прогресс: ${label}`">
        <span class="progress-bar-track"><span class="progress-bar-fill" :style="{ width: `${percentage}%` }"></span></span>
        <b>{{ label }}</b>
    </span>
</template>

<style scoped>
.progress-bar { display: flex; align-items: center; gap: 8px; width: 100%; min-width: 125px; }
.progress-bar-track { display: block; flex: 1 1 auto; width: 76px; min-width: 0; height: 7px; overflow: hidden; border-radius: 5px; background: #e1f3e7; }
.progress-bar-fill { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #1e892f, #65c98a); transition: width .2s ease; }
.progress-bar b { flex: 0 0 auto; color: #1e892f; font-size: 11px; line-height: 1; white-space: nowrap; }

@media (max-width: 900px) {
    .progress-bar { min-width: 0; }
    .progress-bar-track { min-width: 80px; height: 8px; }
}
</style>
