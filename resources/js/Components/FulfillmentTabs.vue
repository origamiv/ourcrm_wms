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
