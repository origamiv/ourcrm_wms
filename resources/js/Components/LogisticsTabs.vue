<script setup lang="ts">
import RibbonTabs from "./RibbonTabs.vue";
const tabs = [
    { label: "Заказы", url: "/logistics/orders", component: "Orders", icon: "goods" },
    { label: "Справочники", url: "/logistics/references", component: "LogisticsReferences", icon: "administration", children: [
        { label: "Статусы заказов", url: "/logistics/order_statuses", component: "OrderStatuses", icon: "goods" },
        { label: "Источники заказов", url: "/logistics/order_sources", component: "OrderSources", icon: "goods" },
        { label: "Статусы отмены", url: "/logistics/order_cancel_statuses", component: "OrderCancelStatuses", icon: "goods" },
        { label: "Логистические компании", url: "/logistics/logistic_companies", component: "LogisticCompanies", icon: "goods" },
        { label: "Статусы отправлений", url: "/logistics/shipment_statuses", component: "ShipmentStatuses", icon: "goods" },
    ]},
];
</script>
<template><RibbonTabs :tabs="tabs" label="Разделы логистики" /></template>
