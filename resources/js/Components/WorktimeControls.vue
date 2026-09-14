<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import { http } from "../lib/http";

const state = ref("not_started");
const loading = ref(false);
const startedAt = ref<number | null>(null);
const elapsed = ref(0);
let timer: number | undefined;

const elapsedLabel = computed(() => {
    const total = Math.max(0, elapsed.value);
    const hours = Math.floor(total / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const seconds = total % 60;
    return [hours, minutes, seconds].map((value) => String(value).padStart(2, "0")).join(":");
});

function updateElapsed() {
    elapsed.value = startedAt.value ? Math.floor((Date.now() - startedAt.value) / 1000) : 0;
}
async function refresh() {
    try {
        const response = await http("/web/worktime/state");
        state.value = response.state || "not_started";
        startedAt.value = response.started_at ? new Date(response.started_at).getTime() : startedAt.value;
        updateElapsed();
    } catch {}
}
async function action(name: "start" | "pause" | "finish") {
    loading.value = true;
    try {
        const response = await http(`/web/worktime/${name}`, "POST");
        state.value = response.state || state.value;
        if (name === "start") startedAt.value = Date.now();
        if (name === "finish") startedAt.value = null;
        updateElapsed();
    } finally { loading.value = false; }
}
onMounted(async () => { await refresh(); timer = window.setInterval(updateElapsed, 1000); });
onUnmounted(() => { if (timer) window.clearInterval(timer); });
</script>

<template>
    <div class="worktimeControls" aria-label="Рабочий график">
        <span v-if="state === 'working' || state === 'paused'" class="worktimeTimer">{{ elapsedLabel }}</span>
        <button v-if="state === 'not_started'" class="worktimeButton worktimeStart" aria-label="Начать день" title="Начать день" :disabled="loading" @click="action('start')"><span class="worktimeIcon">▶</span></button>
        <template v-else>
            <button class="worktimeButton worktimePause" :aria-label="state === 'paused' ? 'Продолжить' : 'Пауза'" :title="state === 'paused' ? 'Продолжить' : 'Пауза'" :disabled="loading" @click="action('pause')"><span class="worktimeIcon">{{ state === 'paused' ? '▶' : 'Ⅱ' }}</span></button>
            <button v-if="state === 'working' || state === 'paused'" class="worktimeButton worktimeFinish" aria-label="Завершить день" title="Завершить день" :disabled="loading" @click="action('finish')"><span class="worktimeIcon">■</span></button>
        </template>
    </div>
</template>

<style scoped>
.worktimeControls{display:flex;align-items:center;gap:8px;margin-left:auto;margin-right:24px;white-space:nowrap}.worktimeTimer{color:#0c1821;font:600 22px/1.3 Manrope,sans-serif;letter-spacing:.2px}.worktimeButton{display:inline-grid;place-items:center;width:28px;height:28px;border:0;border-radius:50%;padding:0;background:transparent;color:#2274a5;font:500 16px/1 Manrope,sans-serif;cursor:pointer}.worktimeButton:hover{background:#e1f0f3}.worktimeButton:disabled{opacity:.5;cursor:default}.worktimeStart{color:#2274a5}.worktimePause{color:#2274a5}.worktimeFinish{color:#c04b4b}.worktimeIcon{display:grid;place-items:center;width:24px;height:24px;font-size:18px;font-weight:700}.worktimeFinish .worktimeIcon{font-size:13px}@media(max-width:900px){.worktimeControls{margin-right:0}.worktimeTimer{font-size:16px}}
</style>
