<script setup lang="ts">
import { Head, usePage, router } from "@inertiajs/vue3";
const page = usePage<any>();
function openUsers() {
    if (navigator.onLine) router.visit("/users");
    else router.push({ url: "/users", component: "Users", props: page.props });
}
</script>
<template>
    <Head title="Главная" />
    <section class="page-content">
        <div class="eyebrow">РАБОЧЕЕ ПРОСТРАНСТВО</div>
        <h1>Главная</h1>
        <p class="muted">Управление складом и доступом сотрудников</p>
        <button
            v-if="page.props.auth.is_admin"
            class="home-module"
            @click="openUsers"
        >
            <img
                class="module-symbol"
                src="/design/crm/contacts.svg"
                alt=""
            /><span
                ><strong>Пользователи</strong
                ><small>Учётные записи и доступ в систему</small></span
            ><span>→</span>
        </button>
        <p v-else class="notice">
            Вы вошли в систему. Управление пользователями доступно
            администратору организации.
        </p>
    </section>
</template>
