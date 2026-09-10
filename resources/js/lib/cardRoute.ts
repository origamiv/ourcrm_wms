import { nextTick, watch, type Ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

/** Keeps card navigation in Inertia history without reloading the cached list. */
export function useCardRoute<T extends { id: string }>(options: {
    base: string;
    rows: Ref<T[]>;
    ready: Ref<boolean>;
    state: () => { id: string; action: string } | null;
    open: (row: T | null, action: string) => void;
    close: () => void;
    missing: () => void;
}) {
    const page = usePage();
    let applying = false;
    let appliedUrl = "";
    watch(
        [() => page.url, options.ready, options.rows],
        async () => {
            const url = page.url;
            if (!options.ready.value) return;
            const path = url.split("?")[0];
            if (path !== options.base && !path.startsWith(options.base + "/"))
                return;
            const [id, action] = path.slice(options.base.length + 1).split("/");
            const row =
                id && id !== "0"
                    ? options.rows.value.find((row) => row.id === id)
                    : null;
            if (appliedUrl === url && (!id || id === "0" || row)) return;
            applying = true;
            options.close();
            if (id && action) {
                if (action === "create" && id === "0")
                    options.open(null, action);
                else if (row) options.open(row, action);
                else options.missing();
            }
            appliedUrl = id && action !== "create" && !row ? "" : url;
            await nextTick();
            applying = false;
        },
        { immediate: true },
    );
    watch(options.state, (state) => {
        if (applying || !options.ready.value) return;
        const path = page.url.split("?")[0];
        if (path !== options.base && !path.startsWith(options.base + "/"))
            return;
        const query = page.url.includes("?")
            ? page.url.slice(page.url.indexOf("?"))
            : "";
        const url =
            options.base +
            (state ? `/${state.id}/${state.action}` : "") +
            query;
        if (url === page.url) return;
        appliedUrl = url;
        router.push({ url, preserveState: true, preserveScroll: true });
    });
}
