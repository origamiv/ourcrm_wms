export interface UserRow {
    id: string;
    name: string | null;
    last_name: string | null;
    middle_name: string | null;
    nick: string | null;
    email: string | null;
    phone: string | null;
    status: number | null;
    tenant_id: string;
    deleted_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    version: string;
}
export interface Change {
    id: string;
    version: string;
    operation: "upsert" | "remove";
    data: UserRow | null;
}
export interface SyncPage {
    mode: "snapshot" | "delta";
    changes: Change[];
    cursor: string | null;
    continuation: string | null;
}
export interface Meta {
    cursor: string | null;
    continuation: string | null;
    ready: boolean;
}
interface Entry extends Change {
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
        const r = indexedDB.open("wms_cache", 1);
        r.onupgradeneeded = () => {
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
export class UserCache {
    private memory = new Map<string, Change>();
    meta: Meta = { cursor: null, continuation: null, ready: false };
    persistent = true;
    constructor(
        public scope: string,
        private onFailure: () => void = () => {},
    ) {}
    private fail() {
        this.persistent = false;
        this.onFailure();
    }
    async load(): Promise<UserRow[]> {
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
                    (entries as Entry[]).map((e) => [e.id, e]),
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
    async apply(changes: Change[], meta?: Meta): Promise<UserRow[]> {
        if (this.persistent)
            try {
                const db = await open();
                const tx = db.transaction(["entries", "meta"], "readwrite");
                const done = complete(tx);
                for (const change of changes) {
                    const store = tx.objectStore("entries");
                    const key = `${this.scope}:${change.id}`;
                    const old = (await request(store.get(key))) as
                        Entry | undefined;
                    if (!old || BigInt(change.version) >= BigInt(old.version))
                        store.put({ ...change, key, scope: this.scope });
                }
                if (meta) tx.objectStore("meta").put(meta, this.scope);
                await done;
            } catch {
                this.fail();
            }
        for (const c of changes) {
            const old = this.memory.get(c.id);
            if (!old || BigInt(c.version) >= BigInt(old.version))
                this.memory.set(c.id, c);
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
