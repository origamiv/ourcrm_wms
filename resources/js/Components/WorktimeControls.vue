<script setup lang="ts">
import { onMounted, ref } from "vue";
import { http } from "../lib/http";
const state = ref("not_started"), loading = ref(false);
async function refresh() { try { state.value = (await http("/web/worktime/state")).state; } catch {} }
async function action(name?: string) { name = name || (state.value === "not_started" ? "start" : "pause"); loading.value = true; try { state.value = (await http(`/web/worktime/${name}`, "POST")).state; } finally { loading.value = false; } }
onMounted(refresh);
</script>
<template><div class="worktime-controls"><button v-if="state === 'not_started'" class="start" :disabled="loading" @click="action()">Начать день</button><button v-else class="pause" :disabled="loading" @click="action('pause')">{{ state === 'paused' ? 'Продолжить' : 'Пауза' }}</button><button v-if="state === 'working'" class="finish" :disabled="loading" @click="action('finish')">Завершить день</button></div></template>
<style scoped>.worktime-controls{display:flex;gap:8px;align-items:center;margin-left:auto;margin-right:24px}.worktime-controls button{border:0;border-radius:8px;padding:10px 14px;font:600 12px Manrope,sans-serif;cursor:pointer}.worktime-controls button:disabled{opacity:.5}.start{background:#2274a5;color:#fff}.pause{background:#e1f0f3;color:#0c1821}.finish{background:#fff0f0;color:#c43e3e}</style>
