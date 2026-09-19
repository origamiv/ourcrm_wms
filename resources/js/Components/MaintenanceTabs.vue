<script setup lang="ts">
import RibbonTabs from "./RibbonTabs.vue";

const tabs = [
    {
        label: "Импорты",
        url: "/maintenance/imports",
        component: "Imports",
        icon: "administration",
    },
    {
        label: "Справочники",
        url: "/maintenance/references",
        component: "MaintenanceReferences",
        icon: "administration",
        children: [{ label: "Задачи", url: "/maintenance/references/tasks", component: "SchedulerTasks", icon: "administration" }],
    },
    {
        label: "Планировщик",
        url: "/maintenance/scheduler",
        component: "Scheduler",
        icon: "administration",
    },
];
</script>

<template><RibbonTabs :tabs="tabs" label="Разделы обслуживания" /></template>
