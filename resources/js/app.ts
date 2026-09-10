import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import Shell from "./Components/Shell.vue";
import Home from "./Pages/Home.vue";
import Users from "./Pages/Users.vue";
import Roles from "./Pages/Roles.vue";
import Permissions from "./Pages/Permissions.vue";
import "./lib/http";
const pages: Record<string, any> = { Home, Users, Roles, Permissions };
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
