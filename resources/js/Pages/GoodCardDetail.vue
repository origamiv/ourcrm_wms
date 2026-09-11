<script setup lang="ts">
import { computed, onMounted, onUnmounted } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { createEntitySync } from "../lib/entitySync";
import type { EntityRow } from "../lib/cache";

interface CardRow extends EntityRow { good_id?: string | number | null; name?: string | null; status?: number | null; shortname?: string | null; }
interface GoodRow extends EntityRow { name?: string | null; shortname?: string | null; }
const page = usePage<any>();
const scope = `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`;
const cardId = String(page.url.split("/").at(-2) ?? "");
const cards = createEntitySync<CardRow>(scope, "good_cards");
const goods = createEntitySync<GoodRow>(scope, "goods");
const card = computed(() => cards.rows.value.find((row) => String(row.id) === cardId) ?? null);
const good = computed(() => goods.rows.value.find((row) => String(row.id) === String(card.value?.good_id)) ?? null);
const title = computed(() => card.value?.name || card.value?.shortname || `Карточка товара №${cardId}`);
function back() { router.visit(card.value?.good_id ? `/goods/goods/${card.value.good_id}/view` : "/goods/goods"); }
onMounted(() => Promise.all([cards.start(), goods.start()]));
onUnmounted(() => { cards.stop(); goods.stop(); });
</script>

<template>
    <Head :title="title" />
    <div class="good-card-detail-page">
        <div class="content-breadcrumb">Товары › Товары › {{ good?.name || 'Товар' }} › Карточка товара</div>
        <button class="good-card-back" type="button" @click="back">← Вернуться к товару</button>
        <div class="good-card-detail-heading"><div class="good-card-large-avatar">{{ title.slice(0, 2).toUpperCase() }}</div><div><h1>{{ title }}</h1><p>Карточка товара №{{ cardId }} · {{ good?.name || 'Товар не найден' }}</p></div></div>
        <section class="good-card-panel"><h2>Данные карточки</h2><div class="good-card-fields"><div><span>Название</span><strong>{{ title }}</strong></div><div><span>Товар</span><strong>{{ good?.name || good?.shortname || '—' }}</strong></div><div><span>Статус</span><strong>{{ card?.status === 1 ? 'Активна' : 'Не указан' }}</strong></div><div><span>ID</span><strong>#{{ cardId }}</strong></div></div></section>
        <section class="good-card-panel"><h2>Характеристики карточки</h2><p class="good-card-muted">Дополнительные поля карточки товара будут добавлены после утверждения структуры. Сейчас экран подготовлен для подключения этих данных.</p></section>
    </div>
</template>

<style scoped>
.good-card-detail-page { min-height:calc(100vh - 40px); padding:24px 30px; background:#f7f8fa; color:#172126; }.good-card-back { border:0; background:none; color:#238348; padding:0; cursor:pointer; font:inherit; margin:20px 0; }.good-card-detail-heading { display:flex; align-items:center; gap:16px; margin-bottom:24px; }.good-card-detail-heading h1 { margin:0 0 5px; font-size:30px; }.good-card-detail-heading p,.good-card-muted { margin:0; color:#748187; }.good-card-large-avatar { width:72px; height:72px; border-radius:20px; display:grid; place-items:center; background:#dcefe3; color:#238348; font-size:24px; font-weight:700; }.good-card-panel { max-width:920px; background:#fff; border:1px solid #e2e8ea; border-radius:12px; padding:22px; margin-bottom:12px; }.good-card-panel h2 { margin:0 0 18px; font-size:18px; }.good-card-fields { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }.good-card-fields div { background:#f7f9f9; border-radius:8px; padding:13px; }.good-card-fields span { display:block; color:#7c898e; font-size:12px; margin-bottom:5px; }.good-card-fields strong { display:block; }@media(max-width:700px){.good-card-fields{grid-template-columns:1fr}.good-card-detail-heading h1{font-size:24px}}
</style>
