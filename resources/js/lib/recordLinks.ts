export type ClientRecordSection = "clients" | "integrations";

export function clientRecordUrl(
    section: ClientRecordSection,
    id: string | number,
): string {
    const params = new URLSearchParams({ record_id: String(id) });
    return `/clients/${section}?${params.toString()}`;
}

export function recordIdFromUrl(url: string): string {
    const query = url.includes("?") ? url.slice(url.indexOf("?") + 1) : "";
    const id = new URLSearchParams(query).get("record_id")?.trim() ?? "";
    return /^[1-9][0-9]*$/.test(id) ? id : "";
}

export function withoutRecordId(url: string): string {
    const [path, query = ""] = url.split("?", 2);
    const params = new URLSearchParams(query);
    params.delete("record_id");
    const rest = params.toString();
    return rest ? `${path}?${rest}` : path;
}
