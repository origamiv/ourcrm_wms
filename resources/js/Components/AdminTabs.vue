<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
const page = usePage<any>();
const tabs = [
    { label: "Пользователи", url: "/users", component: "Users" },
    { label: "Роли", url: "/roles", component: "Roles" },
    { label: "Права доступа", url: "/permissions", component: "Permissions" },
];
function open(tab: (typeof tabs)[number]) {
    if (navigator.onLine) router.visit(tab.url);
    else
        router.push({
            url: tab.url,
            component: tab.component,
            props: page.props,
        });
}
</script>
<template>
    <nav class="module-tabs" aria-label="Разделы администрирования">
        <a
            v-for="tab in tabs"
            :key="tab.url"
            :href="tab.url"
            class="module-tab"
            :class="{ active: page.url.split('?')[0] === tab.url }"
            :aria-current="
                page.url.split('?')[0] === tab.url ? 'page' : undefined
            "
            @click.prevent="open(tab)"
            >{{ tab.label }}</a
        >
    </nav>
</template>
<style scoped>
.module-tabs {
    gap: 8px;
    flex-wrap: wrap;
}
.module-tab {
    text-decoration: none;
    padding-inline: 12px;
}
.module-tab:focus-visible {
    outline: 2px solid #1e892f;
    outline-offset: 3px;
}
</style>
