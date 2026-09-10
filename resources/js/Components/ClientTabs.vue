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
];
</script>
<template><RibbonTabs :tabs="tabs" label="Разделы клиентов" /></template>
