<script setup lang="ts">
import { Head, usePage, router } from "@inertiajs/vue3";
const page = usePage<any>();
const tiles = [
    {
        title: "Клиенты",
        description: "Клиенты и организации",
        url: "/clients/clients",
        component: "Clients",
        icon: "/design/crm/company_contacts.svg",
    },
    {
        title: "Документы",
        description: "Документы клиентов",
        url: "/clients/documents",
        component: "Documents",
        icon: "/design/crm/administration.svg",
    },
    {
        title: "Товары",
        description: "Номенклатура и карточки товаров",
        url: "/goods/goods",
        component: "Goods",
        icon: "/design/crm/goods.svg",
    },
    {
        title: "Склады",
        description: "Склады фулфилмента",
        url: "/fulfillment/warehouses",
        component: "Warehouses",
        icon: "/design/crm/goods.svg",
    },
];
function openTile(tile: (typeof tiles)[number]) {
    if (navigator.onLine) router.visit(tile.url);
    else
        router.push({
            url: tile.url,
            component: tile.component,
            props: page.props,
        });
}
</script>
<template>
    <Head title="Главная" />
    <section class="page-content">
        <div class="eyebrow">РАБОЧЕЕ ПРОСТРАНСТВО</div>
        <h1>Главная</h1>
        <p class="muted">Управление складом и доступом сотрудников</p>
        <div v-if="page.props.auth.is_admin" class="home-modules">
            <button
                v-for="tile in tiles"
                :key="tile.url"
                class="home-module"
                @click="openTile(tile)"
            >
                <img class="module-symbol" :src="tile.icon" alt="" />
                <span>
                    <strong>{{ tile.title }}</strong>
                    <small>{{ tile.description }}</small>
                </span>
                <span>→</span>
            </button>
        </div>
        <p v-else class="notice">
            Вы вошли в систему. Управление пользователями доступно
            администратору организации.
        </p>
    </section>
</template>
<style scoped>
.home-modules {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    max-width: 900px;
    margin-top: 30px;
}
.home-module {
    margin-top: 0;
    max-width: none;
}
@media (max-width: 700px) {
    .home-modules {
        grid-template-columns: 1fr;
    }
}
</style>
