export interface EntityRow {
    id: string;
    version: string;
}
export interface UserRow extends EntityRow {
    id: string;
    name: string | null;
    last_name: string | null;
    middle_name: string | null;
    nick: string | null;
    email: string | null;
    phone: string | null;
    status: number | null;
    tenant_id: string | null;
    deleted_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    roles: Array<{ id: string; name: string | null; slug: string | null; status: number | null }>;
    version: string;
}
export interface Change<T extends EntityRow = UserRow> {
    id: string;
    version: string;
    operation: "upsert" | "remove";
    data: T | null;
}
export interface SyncPage<T extends EntityRow = UserRow> {
    entity_type?: string;
    mode: "snapshot" | "delta";
    changes: Change<T>[];
    cursor: string | null;
    continuation: string | null;
}
export interface Meta {
    cursor: string | null;
    continuation: string | null;
    ready: boolean;
}
interface Entry<T extends EntityRow> extends Change<T> {
    key: string;
    scope: string;
}
const request = <T>(r: IDBRequest<T>) =>
    new Promise<T>((resolve, reject) => {
        r.onsuccess = () => resolve(r.result);
        r.onerror = () => reject(r.error);
    });
const complete = (t: IDBTransaction) =>
    new Promise<void>((resolve, reject) => {
        t.oncomplete = () => resolve();
        t.onabort = t.onerror = () =>
            reject(t.error ?? new Error("Хранилище недоступно"));
    });
let database: Promise<IDBDatabase> | undefined;
function open() {
    return (database ??= new Promise<IDBDatabase>((resolve, reject) => {
        const r = indexedDB.open("wms_cache", 3);
        r.onupgradeneeded = () => {
            for (const name of Array.from(r.result.objectStoreNames))
                r.result.deleteObjectStore(name);
            r.result
                .createObjectStore("entries", { keyPath: "key" })
                .createIndex("scope", "scope");
            r.result.createObjectStore("meta");
        };
        r.onsuccess = () => {
            r.result.onversionchange = () => {
                r.result.close();
                database = undefined;
            };
            resolve(r.result);
        };
        r.onerror = () => {
            database = undefined;
            reject(r.error);
        };
        r.onblocked = () => {
            database = undefined;
            reject(new Error("Хранилище занято другой вкладкой"));
        };
    }));
}
export class EntityCache<T extends EntityRow> {
    private memory = new Map<string, Change<T>>();
    meta: Meta = { cursor: null, continuation: null, ready: false };
    persistent = true;
    public scope: string;
    constructor(
        scope: string,
        entityType: string,
        private onFailure: () => void = () => {},
    ) {
        this.scope = JSON.stringify([scope, entityType]);
    }
    private fail() {
        this.persistent = false;
        this.onFailure();
    }
    async load(): Promise<T[]> {
        if (this.persistent)
            try {
                const db = await open();
                const tx = db.transaction(["entries", "meta"]);
                const done = complete(tx);
                const [entries, meta] = await Promise.all([
                    request(
                        tx
                            .objectStore("entries")
                            .index("scope")
                            .getAll(this.scope),
                    ),
                    request(tx.objectStore("meta").get(this.scope)),
                ]);
                await done;
                this.memory = new Map(
                    (entries as Entry<T>[]).map((e) => [
                        String(e.id),
                        { ...e, id: String(e.id) },
                    ]),
                );
                this.meta = meta ?? {
                    cursor: null,
                    continuation: null,
                    ready: false,
                };
            } catch {
                this.fail();
            }
        return this.rows();
    }
    rows() {
        return [...this.memory.values()]
            .filter((e) => e.operation === "upsert" && e.data)
            .map((e) => e.data!);
    }
    async apply(changes: Change<T>[], meta?: Meta): Promise<T[]> {
        if (this.persistent)
            try {
                const db = await open();
                const tx = db.transaction(["entries", "meta"], "readwrite");
                const done = complete(tx);
                for (const change of changes) {
                    const store = tx.objectStore("entries");
                    const key = `${this.scope}:${change.id}`;
                    const old = (await request(store.get(key))) as
                        Entry<T> | undefined;
                    const version = String(change.version ?? "0");
                    if (!old || BigInt(version) >= BigInt(old.version ?? "0"))
                        store.put({ ...change, key, scope: this.scope });
                }
                if (meta) tx.objectStore("meta").put(meta, this.scope);
                await done;
            } catch {
                this.fail();
            }
        for (const c of changes) {
            const id = String(c.id);
            const normalized = { ...c, id } as Change<T>;
            // Older in-memory data may contain both numeric and string keys
            // for the same entity. Remove those aliases before upserting.
            for (const key of this.memory.keys()) {
                if (String(key) === id && key !== id) this.memory.delete(key);
            }
            const old = this.memory.get(id);
            const version = String(c.version ?? "0");
            if (!old || BigInt(version) >= BigInt(old.version ?? "0"))
                this.memory.set(id, normalized);
        }
        if (meta) this.meta = meta;
        return this.rows();
    }
    async reset() {
        this.memory.clear();
        this.meta = { cursor: null, continuation: null, ready: false };
        if (this.persistent)
            try {
                const db = await open();
                const tx = db.transaction(["entries", "meta"], "readwrite");
                const done = complete(tx);
                const keys = await request(
                    tx
                        .objectStore("entries")
                        .index("scope")
                        .getAllKeys(this.scope),
                );
                keys.forEach((key) => tx.objectStore("entries").delete(key));
                tx.objectStore("meta").delete(this.scope);
                await done;
            } catch {
                this.fail();
            }
    }
}
export async function clearCaches() {
    try {
        const db = await open();
        const tx = db.transaction(["entries", "meta"], "readwrite");
        const done = complete(tx);
        tx.objectStore("entries").clear();
        tx.objectStore("meta").clear();
        await done;
    } catch {
        /* При недоступной IndexedDB данные остаются только в памяти. */
    }
}

export class UserCache extends EntityCache<UserRow> {
    constructor(scope: string, onFailure: () => void = () => {}) {
        super(scope, "users", onFailure);
    }
}
