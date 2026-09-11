<script setup lang="ts">
import RibbonTabs from "./RibbonTabs.vue";
const tabs = [
    {
        label: "Склады",
        url: "/fulfillment/warehouses",
        component: "Warehouses",
        icon: "goods",
    },
    {
        label: "Ячейки",
        url: "/fulfillment/cells",
        component: "Cells",
        icon: "goods",
    },
    {
        label: "Размещения",
        url: "/fulfillment/cell_goods",
        component: "CellGoods",
        icon: "goods",
    },
    {
        label: "Задачи",
        url: "/fulfillment/tasks",
        component: "Tasks",
        icon: "roles_rights",
    },
    {
        label: "Справочники",
        url: "/fulfillment/references",
        component: "",
        icon: "administration",
        children: [
            {
                label: "Типы складов",
                url: "/fulfillment/type_warehouses",
                component: "TypeWarehouses",
                icon: "goods",
            },
            {
                label: "Типы хранения",
                url: "/fulfillment/type_storage",
                component: "TypeStorage",
                icon: "goods",
            },
            {
                label: "Зоны",
                url: "/fulfillment/zones",
                component: "Zones",
                icon: "goods",
            },
            { label: "Типы задач", url: "/fulfillment/task_types", component: "TaskTypes", icon: "goods" },
            { label: "Статусы задач", url: "/fulfillment/task_statuses", component: "TaskStatuses", icon: "goods" },
            { label: "Приоритеты", url: "/fulfillment/priorities", component: "Priorities", icon: "goods" },
            {
                label: "Маркетплейсы",
                url: "/fulfillment/marketplaces",
                component: "Marketplaces",
                icon: "companies",
            },
            {
                label: "Службы доставки",
                url: "/fulfillment/delivery_services",
                component: "DeliveryServices",
                icon: "goods",
            },
        ],
    },
];
</script>
<template><RibbonTabs :tabs="tabs" label="Разделы фулфилмента" /></template>
