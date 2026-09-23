import { ref, shallowRef } from "vue";
import { EntityCache, type EntityRow, type SyncPage } from "./cache";
import { http, HttpError, sessionEnded } from "./http";
export function createEntitySync<T extends EntityRow>(
    scope: string,
    entityType: string,
    options?: { cacheKey?: string; syncUrl?: string },
) {
    const rows = shallowRef<T[]>([]),
        syncing = ref(false),
        ready = ref(false),
        online = ref(navigator.onLine),
        warning = ref(""),
        error = ref("");
    const cache = new EntityCache<T>(
        scope,
        options?.cacheKey ?? entityType,
        () => {
            warning.value =
                "Локальное хранилище недоступно. Данные сохраняются только до закрытия страницы.";
        },
    );
    const channel =
        typeof BroadcastChannel !== "undefined"
            ? new BroadcastChannel(`wms_entities:${cache.scope}`)
            : null;
    let stopped = false;
    const allowed = () => !stopped && !sessionEnded;
    async function refresh() {
        if (!allowed()) return;
        const loaded = await cache.load();
        if (allowed()) {
            rows.value = loaded;
            ready.value = cache.meta.ready;
        }
    }
    async function sync() {
        if (!allowed() || syncing.value || !navigator.onLine) {
            online.value = navigator.onLine;
            return;
        }
        syncing.value = true;
        error.value = "";
        const run = async () => {
            if (!allowed()) return;
            await refresh();
            let restarted = false;
            while (allowed()) {
                if (!allowed()) return;
                const params = new URLSearchParams();
                if (cache.meta.cursor) params.set("cursor", cache.meta.cursor);
                if (cache.meta.continuation)
                    params.set("continuation", cache.meta.continuation);
                let page: SyncPage<T>;
                try {
                    page = await http(
                        `${options?.syncUrl ?? `/web/sync/${encodeURIComponent(entityType)}`}?${params}`,
                    );
                } catch (e) {
                    if (
                        !restarted &&
                        e instanceof HttpError &&
                        [409, 422].includes(e.status)
                    ) {
                        await cache.reset();
                        restarted = true;
                        continue;
                    }
                    throw e;
                }
                if (!allowed()) return;
                if (page.entity_type !== entityType)
                    throw new Error("Неверный тип данных синхронизации");
                rows.value = await cache.apply(page.changes, {
                    cursor: page.cursor ?? cache.meta.cursor,
                    continuation: page.continuation,
                    ready: page.mode === "delta" || !page.continuation,
                });
                if (!allowed()) {
                    await cache.reset();
                    return;
                }
                ready.value = cache.meta.ready;
                online.value = true;
                channel?.postMessage("changed");
                if (!cache.meta.continuation) break;
            }
        };
        try {
            if (navigator.locks)
                await navigator.locks.request(`wms_sync:${cache.scope}`, run);
            else await run();
        } catch (e) {
            if (allowed()) {
                if (e instanceof HttpError) error.value = e.message;
                else {
                    online.value = navigator.onLine;
                    error.value = navigator.onLine
                        ? ""
                        : "Нет связи с сервером. Доступны сохранённые данные.";
                }
            }
        } finally {
            syncing.value = false;
        }
    }
    async function apply(row: T) {
        if (!allowed()) return;
        rows.value = await cache.apply([
            {
                id: row.id,
                version: row.version,
                operation: "upsert",
                data: row,
            },
        ]);
        if (!allowed()) {
            await cache.reset();
            return;
        }
        channel?.postMessage("changed");
    }
    async function remove(row: T) {
        if (!allowed()) return;
        rows.value = await cache.apply([
            {
                id: row.id,
                version: row.version,
                operation: "remove",
                data: null,
            },
        ]);
        if (!allowed()) {
            await cache.reset();
            return;
        }
        channel?.postMessage("changed");
    }
    const offline = () => {
        online.value = false;
    };
    const revoke = () => {
        stopped = true;
        rows.value = [];
    };
    channel?.addEventListener("message", () => {
        void refresh();
    });
    window.addEventListener("focus", sync);
    window.addEventListener("online", sync);
    window.addEventListener("offline", offline);
    window.addEventListener("wms_access_lost", revoke);
    return {
        rows,
        syncing,
        ready,
        online,
        warning,
        error,
        sync,
        apply,
        remove,
        async start() {
            await refresh();
            await sync();
        },
        stop() {
            stopped = true;
            channel?.close();
            window.removeEventListener("focus", sync);
            window.removeEventListener("online", sync);
            window.removeEventListener("offline", offline);
            window.removeEventListener("wms_access_lost", revoke);
        },
    };
}
