import "fake-indexeddb/auto";
import { test } from "node:test";
import assert from "node:assert/strict";
import {
    UserCache,
    clearCaches,
    type Change,
    type UserRow,
} from "../../resources/js/lib/cache";
const row = (id: string, version: string): Change => ({
    id,
    version,
    operation: "upsert",
    data: { id, version, name: "Тест", tenant_id: "a" } as UserRow,
});
test("upgrades old IndexedDB by dropping obsolete rows and cursors", async () => {
    await new Promise<void>((resolve, reject) => {
        const r = indexedDB.open("wms_cache", 1);
        r.onupgradeneeded = () => {
            r.result
                .createObjectStore("entries", { keyPath: "key" })
                .createIndex("scope", "scope");
            r.result.createObjectStore("meta");
        };
        r.onsuccess = () => {
            const db = r.result;
            const tx = db.transaction(["entries", "meta"], "readwrite");
            tx.objectStore("entries").put({
                ...row("1", "1"),
                key: "old:1",
                scope: "old",
            });
            tx.objectStore("meta").put({ cursor: "old_cursor" }, "old");
            tx.oncomplete = () => {
                db.close();
                resolve();
            };
            tx.onerror = () => reject(tx.error);
        };
        r.onerror = () => reject(r.error);
    });
    const cache = new UserCache("old");
    assert.equal((await cache.load()).length, 0);
    assert.equal(cache.meta.cursor, null);
    await new Promise<void>((resolve, reject) => {
        const r = indexedDB.open("wms_cache");
        r.onsuccess = () => {
            const db = r.result;
            assert.equal(db.version, 2);
            const q = db.transaction("entries").objectStore("entries").count();
            q.onsuccess = () => {
                assert.equal(q.result, 0);
                db.close();
                resolve();
            };
            q.onerror = () => reject(q.error);
        };
        r.onerror = () => reject(r.error);
    });
});
test("persists rows and cursor together and rejects stale writes across tabs", async () => {
    await clearCaches();
    const a = new UserCache("a"),
        b = new UserCache("a");
    await a.apply([row("1", "5")], {
        cursor: "cursor5",
        continuation: null,
        ready: true,
    });
    assert.equal((await b.load())[0].version, "5");
    assert.equal(b.meta.cursor, "cursor5");
    await b.apply([row("1", "4")]);
    await a.load();
    assert.equal(a.rows()[0].version, "5");
    await a.apply([{ id: "1", version: "6", operation: "remove", data: null }]);
    await b.apply([row("1", "5")]);
    assert.equal((await a.load()).length, 0);
});
test("isolates namespaces and clears all browser data on logout", async () => {
    await clearCaches();
    const a = new UserCache("user_a:tenant_a"),
        b = new UserCache("user_b:tenant_b");
    await a.apply([row("1", "1")]);
    assert.equal((await b.load()).length, 0);
    await clearCaches();
    assert.equal((await a.load()).length, 0);
    assert.equal(a.meta.cursor, null);
});
test("falls back to memory if IndexedDB is unavailable", async () => {
    const original = globalThis.indexedDB;
    Object.defineProperty(globalThis, "indexedDB", {
        configurable: true,
        value: undefined,
    });
    // Используем уже закрытую соединением версию в отдельном импорте модуля.
    const fresh = await import("../../resources/js/lib/cache.ts?fallback");
    let failed = false;
    const cache = new fresh.UserCache("fallback", () => {
        failed = true;
    });
    await cache.load();
    await cache.apply([row("1", "1")]);
    assert.equal(cache.rows().length, 1);
    assert.equal(failed, true);
    Object.defineProperty(globalThis, "indexedDB", {
        configurable: true,
        value: original,
    });
});

test("isolates entity IDs and cursors and clears all entity types", async () => {
    const { EntityCache } = await import("../../resources/js/lib/cache");
    await clearCaches();
    const users = new EntityCache<UserRow>("same_user:tenant", "users");
    const items = new EntityCache<UserRow>("same_user:tenant", "items");
    await users.apply([row("1", "10")], {
        cursor: "users10",
        continuation: null,
        ready: true,
    });
    await items.apply([row("1", "20")], {
        cursor: "items20",
        continuation: null,
        ready: true,
    });
    assert.equal((await users.load())[0].version, "10");
    assert.equal((await items.load())[0].version, "20");
    assert.equal(users.meta.cursor, "users10");
    assert.equal(items.meta.cursor, "items20");
    await clearCaches();
    assert.equal((await users.load()).length, 0);
    assert.equal((await items.load()).length, 0);
    assert.equal(items.meta.cursor, null);
});
