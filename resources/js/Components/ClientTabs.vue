<script setup lang="ts">
import RibbonTabs from "./RibbonTabs.vue";
const tabs = [
    {
        label: "Клиенты",
        url: "/clients/clients",
        component: "Clients",
        icon: "company_contacts",
    },
    {
        label: "Юр.лица",
        url: "/clients/companies",
        component: "ClientCompanies",
        icon: "companies",
    },
    {
        label: "Физ.лица",
        url: "/clients/individuals",
        component: "ClientIndividuals",
        icon: "contacts",
    },
    {
        label: "Документы",
        url: "/clients/documents",
        component: "Documents",
        icon: "roles",
    },
    {
        label: "Доступы",
        url: "/clients/accounts",
        component: "ClientAccounts",
        icon: "administration",
    },
    {
        label: "Интеграции",
        url: "/clients/integrations",
        component: "ClientIntegrations",
        icon: "administration",
    },
    {
        label: "Справочники",
        url: "/clients/references",
        component: "",
        icon: "administration",
        children: [
            {
                label: "Типы документов",
                url: "/clients/doc_types",
                component: "DocTypes",
                icon: "roles",
            },
            {
                label: "Сервисы",
                url: "/clients/services",
                component: "ClientServices",
                icon: "administration",
            },
        ],
    },
];
</script>
<template><RibbonTabs :tabs="tabs" label="Разделы клиентов" /></template>
