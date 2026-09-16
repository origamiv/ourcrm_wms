<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { http, endSession } from "../lib/http";
import WorktimeControls from "./WorktimeControls.vue";
const page = usePage<any>();
const currentSection = computed(() => {
    const section = page.url.split("/")[1] || "";
    return ["main", "clients", "goods", "integration", "fulfillment", "maintenance"].includes(section) && !page.url.startsWith("/main/instructions")
        ? section
        : "";
});
const tenantBlocked = computed(() => Number(page.props.auth?.tenant_status ?? 1) !== 1 && page.url !== "/");
const collapsed = ref(false),
    mobileMenuOpen = ref(false),
    leaving = ref(false),
    message = ref(""),
    connected = ref(navigator.onLine);
function go(
    component: "Home" | "Users" | "Clients" | "Goods" | "IntegrationWebhooks" | "Marketplaces" | "Imports" | "Instructions" | "Worktime",
    url: string,
) {
    mobileMenuOpen.value = false;
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
    <div class="workspace" :class="{ collapsed, mobileMenuOpen, tenantBlocked }">
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
                <a
                    v-if="page.props.auth.is_admin"
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
                <a
                    v-if="page.props.auth.is_admin"
                    href="/maintenance/imports"
                    aria-label="Обслуживание"
                    :class="{ active: page.url.startsWith('/maintenance/') }"
                    @click.prevent="go('Imports', '/maintenance/imports')"
                >
                    <img class="nav-icon" src="/design/crm/administration.svg" alt="" />
                    <span class="nav-label">Обслуживание</span>
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
                <button class="mobile-menu-button" type="button" aria-label="Открыть меню" @click="mobileMenuOpen = !mobileMenuOpen">☰</button>
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
                              : page.url.startsWith("/maintenance/")
                                ? "Обслуживание"
                              : "Рабочий стол"
                }}</span>
                <button
                    v-if="currentSection"
                    class="instructions-button"
                    type="button"
                    aria-label="Инструкции"
                    title="Инструкции"
                    @click="go('Instructions', `/instructions?section=${currentSection}`)"
                >
                    <img src="/design/crm/documents.svg" alt="" />
                </button>
                <WorktimeControls />
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
            <div v-if="tenantBlocked" class="tenant-blocked"><div class="tenant-blocked-art">🔒</div><h1>У вас нет доступа</h1><p>Ваш аккаунт заблокирован. Обратитесь к администратору организации.</p></div>
            <slot v-else />
        </main>
    </div>
</template>

<style scoped>
.instructions-button { display: inline-grid; place-items: center; width: 34px; height: 34px; margin-left: auto; border: 0; border-radius: 8px; background: transparent; cursor: pointer; }
.instructions-button:hover { background: #e1f3e7; }
.instructions-button img { width: 21px; height: 21px; }
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
