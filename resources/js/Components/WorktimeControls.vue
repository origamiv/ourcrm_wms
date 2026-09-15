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
    return [hours, minutes].map((value) => String(value).padStart(2, "0")).join(":");
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
        <button v-if="state === 'not_started'" class="worktimeButton worktimeStart" aria-label="Начать день" title="Начать день" :disabled="loading" @click="action('start')"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.5 19 12 8 18.5V5.5Z" fill="currentColor"/></svg></button>
        <template v-else>
            <button class="worktimeButton worktimePause" :aria-label="state === 'paused' ? 'Продолжить' : 'Пауза'" :title="state === 'paused' ? 'Продолжить' : 'Пауза'" :disabled="loading" @click="action('pause')"><svg v-if="state !== 'paused'" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 5h3v14H7V5Zm7 0h3v14h-3V5Z" fill="currentColor"/></svg><svg v-else viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.5 19 12 8 18.5V5.5Z" fill="currentColor"/></svg></button>
            <button v-if="state === 'working' || state === 'paused'" class="worktimeButton worktimeFinish" aria-label="Завершить день" title="Завершить день" :disabled="loading" @click="action('finish')"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5.5" y="5.5" width="13" height="13" rx="1.5" fill="currentColor"/></svg></button>
        </template>
    </div>
</template>

<style scoped>
.worktimeControls{display:flex;align-items:center;gap:16px;margin-left:auto;margin-right:24px;white-space:nowrap}.worktimeTimer{color:#0c1821;font:600 22px/1.3 Manrope,sans-serif;letter-spacing:.2px}.worktimeButton{display:inline-grid;place-items:center;width:23px;height:23px;border:0;border-radius:0;padding:0;background:transparent;color:#2274a5;cursor:pointer}.worktimeButton:hover{opacity:.7}.worktimeButton:disabled{opacity:.5;cursor:default}.worktimeButton svg{display:block;width:23px;height:23px}.worktimeStart{color:#2274a5}.worktimePause{color:#2274a5}.worktimeFinish{color:#ff5c5c}@media(max-width:900px){.worktimeControls{margin-right:0}.worktimeTimer{font-size:16px}}
</style>
