<script setup lang="ts">
import RibbonTabs from "./RibbonTabs.vue";
const tabs = [
    {
        label: "Справочники",
        url: "/fulfillment/references",
        component: "",
        icon: "administration",
        children: [
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
