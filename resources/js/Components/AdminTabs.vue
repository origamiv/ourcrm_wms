<script setup lang="ts">
import RibbonTabs from "./RibbonTabs.vue";
const tabs = [
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
    {
        label: "График",
        url: "/main/worktime",
        component: "Worktime",
        icon: "view",
    },
    {
        label: "Справочники",
        url: "/main/references",
        component: "",
        icon: "administration",
        children: [
            {
                label: "Права доступа",
                url: "/main/permissions",
                component: "Permissions",
                icon: "permissions",
            },
            {
                label: "Роли и права",
                url: "/main/roles_rights",
                component: "RolesRights",
                icon: "roles_rights",
            },
            {
                label: "Модули",
                url: "/main/modules",
                component: "Modules",
                icon: "workspace",
            },
            {
                label: "Возможности",
                url: "/main/features",
                component: "Features",
                icon: "permissions",
            },
            {
                label: "Иконки",
                url: "/main/icons",
                component: "Icons",
                icon: "view",
            },
            {
                label: "Файлы",
                url: "/main/files",
                component: "Files",
                icon: "roles",
            },
        ],
    },
];
</script>
<template>
    <RibbonTabs :tabs="tabs" label="Разделы администрирования" />
</template>
