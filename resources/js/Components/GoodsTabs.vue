<script setup lang="ts">
import RibbonTabs from "./RibbonTabs.vue";
const tabs = [
    { label: "Товары", url: "/goods/goods", component: "Goods", icon: "goods" },
    {
        label: "Справочники",
        url: "/goods/references",
        component: "",
        icon: "administration",
        children: [
            {
                label: "Типы товаров",
                url: "/goods/type_goods",
                component: "GoodTypes",
                icon: "goods",
            },
            {
                label: "Единицы измерения",
                url: "/goods/unit_goods",
                component: "GoodUnits",
                icon: "goods",
            },
        ],
    },
];
</script>
<template><RibbonTabs :tabs="tabs" label="Разделы товаров" /></template>
