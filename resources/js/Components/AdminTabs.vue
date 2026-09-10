<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
const page = usePage<any>();
const tabs = [
    {
        label: "Роли и права",
        url: "/roles_rights",
        component: "RolesRights",
        icon: "roles_rights",
    },
    {
        label: "Пользователи",
        url: "/users",
        component: "Users",
        icon: "contacts",
    },
    { label: "Роли", url: "/roles", component: "Roles", icon: "roles" },
    {
        label: "Права доступа",
        url: "/permissions",
        component: "Permissions",
        icon: "permissions",
    },
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
            ><img
                :src="`/design/crm/${tab.icon}.svg`"
                alt=""
                width="20"
                height="20"
            /><span>{{ tab.label }}</span></a
        >
    </nav>
</template>
<style scoped>
.module-tabs {
    display: flex;
    width: max-content;
    max-width: 100%;
    flex-wrap: nowrap;
    gap: 0;
    border: 4px solid #a8d4a9;
    border-radius: 10px;
    overflow-x: auto;
    background: #e1f3e7;
}
.module-tab {
    flex: 0 0 auto;
    min-width: 63px;
    padding: 6px;
    gap: 6px;
    border: 0;
    border-right: 4px solid #a8d4a9;
    border-radius: 0;
    color: #0c1821;
    font-size: 12px;
    line-height: 1.3;
    text-decoration: none;
    white-space: nowrap;
}
.module-tab:last-child {
    border-right: 0;
}
.module-tab:hover {
    background: #cdebd7;
}
.module-tab.active {
    background: #1e892f;
    color: #e1f3e7;
}
.module-tab img {
    width: 20px;
    height: 20px;
    object-fit: contain;
    filter: brightness(0) saturate(100%) invert(38%) sepia(69%) saturate(636%)
        hue-rotate(79deg) brightness(94%) contrast(91%);
}
.module-tab.active img {
    filter: brightness(0) invert(1);
}
.module-tab:focus-visible {
    outline: 2px solid #2274a5;
    outline-offset: -2px;
}
@media (max-width: 767px) {
    .module-tabs {
        border-width: 3px;
    }
    .module-tab {
        min-width: 50px;
        font-size: 10px;
        border-right-width: 3px;
    }
}
</style>
