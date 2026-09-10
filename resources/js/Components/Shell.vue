<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { http, endSession } from "../lib/http";
const page = usePage<any>();
const collapsed = ref(false),
    leaving = ref(false),
    message = ref(""),
    connected = ref(navigator.onLine);
function go(
    component: "Home" | "Users" | "Clients" | "Goods" | "IntegrationWebhooks" | "Marketplaces",
    url: string,
) {
    if (navigator.onLine) router.visit(url);
    else
        router.push({
            url,
            component,
            props: { ...page.props, clientScope: null, companyScope: null },
        });
}
function connection() {
    connected.value = navigator.onLine;
}
async function logout() {
    leaving.value = true;
    try {
        await http("/logout", "POST");
        await endSession();
    } catch {
        message.value =
            "Не удалось завершить серверный сеанс. Проверьте соединение и повторите выход.";
        leaving.value = false;
    }
}
onMounted(() => {
    window.addEventListener("online", connection);
    window.addEventListener("offline", connection);
});
onUnmounted(() => {
    window.removeEventListener("online", connection);
    window.removeEventListener("offline", connection);
});
</script>
<template>
    <div class="workspace" :class="{ collapsed }">
        <aside class="sidebar">
            <a href="/" class="brand" @click.prevent="go('Home', '/')"
                ><img
                    class="brand-logo"
                    src="/design/crm/logo.png"
                    alt=""
                /><span class="nav-label"
                    >WMS<span class="brand-caption"
                        >Рабочее пространство</span
                    ></span
                ></a
            >
            <button
                class="collapse-button"
                @click="collapsed = !collapsed"
                :aria-label="collapsed ? 'Развернуть меню' : 'Свернуть меню'"
            >
                {{ collapsed ? "›" : "‹" }}
            </button>

            <nav>
                <a
                    href="/"
                    aria-label="Главная"
                    :class="{ active: page.url === '/' }"
                    @click.prevent="go('Home', '/')"
                    ><img
                        class="nav-icon"
                        src="/design/crm/workspace.svg"
                        alt=""
                    /><span class="nav-label">Главная</span></a
                >
                <a
                    v-if="page.props.auth.is_admin"
                    href="/main/users"
                    aria-label="Администрирование"
                    :class="{
                        active: page.url.startsWith('/main/'),
                    }"
                    @click.prevent="go('Users', '/main/users')"
                    ><img
                        class="nav-icon"
                        src="/design/crm/administration.svg"
                        alt=""
                    /><span class="nav-label">Администрирование</span></a
                >
                <a
                    v-if="page.props.auth.is_admin"
                    href="/clients/clients"
                    aria-label="Клиенты"
                    :class="{ active: page.url.startsWith('/clients/') }"
                    @click.prevent="go('Clients', '/clients/clients')"
                    ><img
                        class="nav-icon clients-icon"
                        src="/design/crm/company_contacts.svg"
                        alt=""
                    /><span class="nav-label">Клиенты</span></a
                >
                <a
                    v-if="page.props.auth.is_admin"
                    href="/goods/goods"
                    aria-label="Товары"
                    :class="{ active: page.url.startsWith('/goods/') }"
                    @click.prevent="go('Goods', '/goods/goods')"
                    ><img
                        class="nav-icon goods-icon"
                        src="/design/crm/goods.svg"
                        alt=""
                    /><span class="nav-label">Товары</span></a
                >
                <a
                    v-if="page.props.auth.is_admin"
                    href="/integration/webhooks"
                    aria-label="Интеграции"
                    :class="{ active: page.url.startsWith('/integration/') }"
                    @click.prevent="
                        go('IntegrationWebhooks', '/integration/webhooks')
                    "
                    ><img
                        class="nav-icon"
                        src="/design/crm/administration.svg"
                        alt=""
                    /><span class="nav-label">Интеграции</span></a
                >
                    href="/fulfillment/marketplaces"
                    aria-label="Фулфилмент"
                    :class="{ active: page.url.startsWith('/fulfillment/') }"
                    @click.prevent="
                        go('Marketplaces', '/fulfillment/marketplaces')
                    "
                >
                    <img
                        class="nav-icon goods-icon"
                        src="/design/crm/goods.svg"
                        alt=""
                    />
                    <span class="nav-label">Фулфилмент</span>
                </a>
            </nav>
            <div class="sidebar-bottom">
                <span
                    class="connection-dot"
                    :class="{ disconnected: !connected }"
                ></span
                ><span class="nav-label">{{
                    connected ? "Подключено" : "Нет сети"
                }}</span>
            </div>
        </aside>
        <main class="main-panel">
            <header class="topbar">
                <span class="section-title">{{
                    page.url.startsWith("/main/")
                        ? "Администрирование"
                        : page.url.startsWith("/clients/")
                          ? "Клиенты"
                          : page.url.startsWith("/goods/")
                            ? "Товары"
                            : page.url.startsWith("/integration/")
                              ? "Интеграции"
                            : page.url.startsWith("/fulfillment/")
                              ? "Фулфилмент"
                              : "Рабочий стол"
                }}</span>
                <div class="account">
                    <span class="header-avatar"
                        ><img src="/design/crm/profile.svg" alt=""
                    /></span>
                    <div>
                        <strong>{{
                            page.props.auth.name || "Пользователь"
                        }}</strong
                        ><small>{{
                            page.props.auth.is_admin
                                ? "Администратор"
                                : "Пользователь"
                        }}</small>
                    </div>
                    <button
                        @click="logout"
                        :disabled="leaving"
                        class="text-button"
                        aria-label="Выйти"
                    >
                        <img
                            class="logout-icon"
                            src="/design/crm/logout.svg"
                            alt=""
                        />
                    </button>
                </div>
            </header>
            <p v-if="message" role="alert" class="notice error">
                {{ message }}
            </p>
            <slot />
        </main>
    </div>
</template>

<style scoped>
.sidebar nav a .clients-icon {
    filter: brightness(0) saturate(100%) invert(36%) sepia(43%) saturate(1113%)
        hue-rotate(161deg);
}
.sidebar nav a.active .clients-icon {
    filter: brightness(0) invert(1);
}
</style>

<style scoped>
.sidebar a.active .goods-icon {
    filter: brightness(0) invert(1);
}
</style>
