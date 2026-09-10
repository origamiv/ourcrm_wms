<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { http, endSession } from "../lib/http";
const page = usePage<any>();
const collapsed = ref(false),
    leaving = ref(false),
    message = ref(""),
    connected = ref(navigator.onLine);
function go(component: "Home" | "Users", url: string) {
    if (navigator.onLine) router.visit(url);
    else router.push({ url, component, props: page.props });
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
                    href="/users"
                    aria-label="Администрирование"
                    :class="{
                        active: [
                            '/users',
                            '/roles',
                            '/permissions',
                            '/companies',
                            '/company_contacts',
                        ].some((url) => page.url.startsWith(url)),
                    }"
                    @click.prevent="go('Users', '/users')"
                    ><img
                        class="nav-icon"
                        src="/design/crm/administration.svg"
                        alt=""
                    /><span class="nav-label">Администрирование</span></a
                >
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
                    [
                        "/users",
                        "/roles",
                        "/permissions",
                        "/companies",
                        "/company_contacts",
                    ].some((url) => page.url.startsWith(url))
                        ? "Администрирование"
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
