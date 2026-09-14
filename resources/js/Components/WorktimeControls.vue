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
        <button v-if="state === 'not_started'" class="worktimeButton worktimeStart" :disabled="loading" @click="action('start')"><span class="worktimeIcon">▶</span><span>Начать день</span></button>
        <template v-else>
            <button class="worktimeButton worktimePause" :disabled="loading" @click="action('pause')"><span class="worktimeIcon">{{ state === 'paused' ? '▶' : 'Ⅱ' }}</span><span>{{ state === 'paused' ? 'Продолжить' : 'Пауза' }}</span></button>
            <button v-if="state === 'working' || state === 'paused'" class="worktimeButton worktimeFinish" :disabled="loading" @click="action('finish')"><span class="worktimeIcon">■</span><span>Завершить день</span></button>
        </template>
    </div>
</template>

<style scoped>
.worktimeControls{display:flex;align-items:center;gap:8px;margin-left:auto;margin-right:24px;white-space:nowrap}.worktimeTimer{color:#0c1821;font:600 22px/1.3 Manrope,sans-serif;letter-spacing:.2px}.worktimeButton{display:inline-flex;align-items:center;gap:7px;border:1px solid #bcdfe7;border-radius:8px;padding:9px 12px;background:#fff;color:#0c1821;font:500 12px/1.3 Manrope,sans-serif;cursor:pointer}.worktimeButton:disabled{opacity:.5;cursor:default}.worktimeStart{background:#2274a5;border-color:#2274a5;color:#fff}.worktimePause{background:#e1f0f3;border-color:#bcdfe7}.worktimeFinish{background:#fff0f0;border-color:#f0c5c5;color:#b33b3b}.worktimeIcon{display:inline-grid;place-items:center;width:18px;height:18px;font-size:13px;font-weight:700}.worktimeFinish .worktimeIcon{font-size:11px}@media(max-width:900px){.worktimeControls{margin-right:0}.worktimeButton span:last-child{display:none}.worktimeTimer{font-size:16px}}
</style>
