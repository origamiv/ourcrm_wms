import IntegrationWebhooks from "./Pages/IntegrationWebhooks.vue";
import IntegrationData from "./Pages/IntegrationData.vue";
import IntegrationRules from "./Pages/IntegrationRules.vue";
import IntegrationServices from "./Pages/IntegrationServices.vue";
import IntegrationHookTypes from "./Pages/IntegrationHookTypes.vue";
import IntegrationProcessingTypes from "./Pages/IntegrationProcessingTypes.vue";
import Kizes from "./Pages/Kizes.vue";
import Marketplaces from "./Pages/Marketplaces.vue";
import Warehouses from "./Pages/Warehouses.vue";
import TypeWarehouses from "./Pages/TypeWarehouses.vue";
import TypeStorage from "./Pages/TypeStorage.vue";
import Zones from "./Pages/Zones.vue";
import Cells from "./Pages/Cells.vue";
import Tasks from "./Pages/Tasks.vue";
import TaskTypes from "./Pages/TaskTypes.vue";
import TaskStatuses from "./Pages/TaskStatuses.vue";
import Priorities from "./Pages/Priorities.vue";
import DeliveryServices from "./Pages/DeliveryServices.vue";
import KindKiz from "./Pages/KindKiz.vue";
import GoodTypes from "./Pages/GoodTypes.vue";
import GoodUnits from "./Pages/GoodUnits.vue";
import Goods from "./Pages/Goods.vue";
import GoodDetail from "./Pages/GoodDetail.vue";
import GoodCardDetail from "./Pages/GoodCardDetail.vue";
import DocTypes from "./Pages/DocTypes.vue";
import Documents from "./Pages/Documents.vue";
import ClientIndividuals from "./Pages/ClientIndividuals.vue";
import ClientCompanies from "./Pages/ClientCompanies.vue";
import Files from "./Pages/Files.vue";
import Icons from "./Pages/Icons.vue";
import Features from "./Pages/Features.vue";
import Modules from "./Pages/Modules.vue";
import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import jquery from "jquery";
import "jquery-easyui/css/easyui.css";
import Shell from "./Components/Shell.vue";
import Companies from "./Pages/Companies.vue";
import CompanyContacts from "./Pages/CompanyContacts.vue";
import Clients from "./Pages/Clients.vue";
import Home from "./Pages/Home.vue";
import Users from "./Pages/Users.vue";
import RolesRights from "./Pages/RolesRights.vue";
import Roles from "./Pages/Roles.vue";
import Permissions from "./Pages/Permissions.vue";
import "./lib/http";

// EasyUI widgets are jQuery plugins and expect the jQuery object globally.
(window as any).jQuery = jquery;
(window as any).$ = jquery;
const pages: Record<string, any> = {
    Home,
    IntegrationWebhooks,
    IntegrationData,
    IntegrationRules,
    IntegrationServices,
    IntegrationHookTypes,
    IntegrationProcessingTypes,

    Goods,
    GoodDetail,
    GoodCardDetail,
    GoodTypes,
    KindKiz,
    DeliveryServices,
    Marketplaces,
    Warehouses,
    TypeWarehouses,
    TypeStorage,
    Zones,
    Cells,
    Tasks,
    TaskTypes,
    TaskStatuses,
    Priorities,
    Kizes,
    GoodUnits,
    DocTypes,
    Documents,
    ClientIndividuals,
    ClientCompanies,
    Files,
    Icons,
    Features,
    Modules,
    Clients,
    Companies,
    CompanyContacts,
    Users,
    Roles,
    Permissions,
    RolesRights,
};
createInertiaApp({
    title: (title) => `${title} · WMS`,
    resolve: (name) => {
        const page = pages[name];
        page.layout ??= Shell;
        return page;
    },
    async setup({ el, App, props, plugin }) {
        // Load the actual EasyUI bundle before any page component is mounted.
        await import("jquery-easyui/js/jquery.easyui.min.js");
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: { color: "#1E892F" },
});
