import { clearCaches } from "./cache";
export const sessionChannel =
    typeof BroadcastChannel !== "undefined"
        ? new BroadcastChannel("wms_session")
        : null;
export let sessionEnded = false;
export async function endSession(broadcast = true) {
    if (sessionEnded) return;
    sessionEnded = true;
    window.dispatchEvent(new Event("wms_access_lost"));
    if (broadcast) sessionChannel?.postMessage("logout");
    await clearCaches();
    window.location.assign("/login");
}
sessionChannel?.addEventListener("message", (e) => {
    if (e.data === "logout") void endSession(false);
});
export class HttpError extends Error {
    constructor(
        public status: number,
        public body: any,
    ) {
        super(body.message ?? "Не удалось выполнить запрос.");
    }
}
export async function http(
    url: string,
    method = "GET",
    data?: unknown,
    responseType: "json" | "blob" = "json",
): Promise<any> {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 20000);
    try {
        const r = await fetch(url, {
            method,
            credentials: "same-origin",
            signal: controller.signal,
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector<HTMLMetaElement>(
                        'meta[name="csrf-token"]',
                    )?.content ?? "",
                "X-WMS-User":
                    document.querySelector<HTMLMetaElement>(
                        'meta[name="wms-user"]',
                    )?.content ?? "",
                "X-WMS-Tenant": encodeURIComponent(
                    document.querySelector<HTMLMetaElement>(
                        'meta[name="wms-tenant"]',
                    )?.content ?? "",
                ),
            },
            body: data === undefined ? undefined : JSON.stringify(data),
        });
        if (r.ok && responseType === "blob") {
            if (sessionEnded) throw new Error("Сеанс завершён");
            return await r.blob();
        }
        const body = await r
            .json()
            .catch(() => ({ message: "Неожиданный ответ сервера." }));
        if ([401, 419].includes(r.status)) {
            void endSession();
            throw new HttpError(r.status, body);
        }
        if (r.status === 403) {
            void endSession();
            throw new HttpError(r.status, body);
        }
        if (!r.ok) throw new HttpError(r.status, body);
        if (sessionEnded) throw new Error("Сеанс завершён");
        return body;
    } finally {
        clearTimeout(timer);
    }
}
