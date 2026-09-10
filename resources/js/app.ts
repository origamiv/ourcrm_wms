import ClientIndividuals from "./Pages/ClientIndividuals.vue";
import ClientCompanies from "./Pages/ClientCompanies.vue";
import Files from "./Pages/Files.vue";
import Icons from "./Pages/Icons.vue";
import Features from "./Pages/Features.vue";
import Modules from "./Pages/Modules.vue";
import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
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
const pages: Record<string, any> = {
    Home,
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
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: { color: "#1E892F" },
});
