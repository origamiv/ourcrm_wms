<script setup lang="ts">
import RibbonTabs from "./RibbonTabs.vue";

const tabs = [
    {
        label: "Фоновые процессы",
        url: "/maintenance/background_processes",
        component: "BackgroundProcesses",
        icon: "administration",
    },
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
        children: [
            {
                label: "Задачи",
                url: "/maintenance/tasks",
                component: "SchedulerTasks",
                icon: "administration",
            },
        ],
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
