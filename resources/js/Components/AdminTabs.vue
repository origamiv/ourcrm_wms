<script setup lang="ts">
import RibbonTabs from "./RibbonTabs.vue";
const tabs = [
    {
        label: "Роли и права",
        url: "/main/roles_rights",
        component: "RolesRights",
        icon: "roles_rights",
    },
    {
        label: "Пользователи",
        url: "/main/users",
        component: "Users",
        icon: "contacts",
    },
    {
        label: "Роли",
        url: "/main/roles",
        component: "Roles",
        icon: "roles",
    },
    {
        label: "Права доступа",
        url: "/main/permissions",
        component: "Permissions",
        icon: "permissions",
    },
    {
        label: "Компании",
        url: "/main/companies",
        component: "Companies",
        icon: "companies",
    },
    {
        label: "Контактные лица",
        url: "/main/company_contacts",
        component: "CompanyContacts",
        icon: "company_contacts",
    },
];
</script>
<template>
    <RibbonTabs :tabs="tabs" label="Разделы администрирования" />
</template>
