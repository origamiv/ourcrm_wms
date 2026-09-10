<script setup lang="ts">
import RibbonTabs from "./RibbonTabs.vue";
const tabs = [
    {
        label: "Вебхуки",
        url: "/integration/webhooks",
        component: "IntegrationWebhooks",
        icon: "administration",
    },
    {
        label: "Данные",
        url: "/integration/data",
        component: "IntegrationData",
        icon: "administration",
    },
    {
        label: "Справочники",
        url: "/integration/references",
        component: "",
        icon: "administration",
        children: [
            {
                label: "Правила",
                url: "/integration/rules",
                component: "IntegrationRules",
                icon: "administration",
            },
            {
                label: "Сервисы",
                url: "/integration/services",
                component: "IntegrationServices",
                icon: "administration",
            },
            {
                label: "Типы хуков",
                url: "/integration/type_hook",
                component: "IntegrationHookTypes",
                icon: "administration",
            },
            {
                label: "Типы обработки",
                url: "/integration/type_processing",
                component: "IntegrationProcessingTypes",
                icon: "administration",
            },
        ],
    },
];
</script>
<template><RibbonTabs :tabs="tabs" label="Разделы интеграций" /></template>
