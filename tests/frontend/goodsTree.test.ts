import { test } from "node:test";
import assert from "node:assert/strict";
import { goodsTree, flattenGoods } from "../../resources/js/lib/goodsTree";

test("keeps branches together and reveals matching descendants with ancestor context", () => {
    const rows = [
        { id: "1", name: "Категория" },
        { id: "2", name: "Вложенная", parent_id: "1" },
        { id: "3", name: "Искомый товар", parent_id: "2" },
        { id: "4", name: "Другой товар" },
    ];
    const tree = goodsTree(rows, new Set(rows.map((row) => row.id)));
    assert.deepEqual(
        flattenGoods(tree.roots, new Set()).map((row) => row.id),
        ["1", "4"],
    );
    assert.deepEqual(
        flattenGoods(tree.roots, new Set(["1", "2"])).map((row) => row.id),
        ["1", "2", "3", "4"],
    );
    const filtered = goodsTree(rows, new Set(["3"]));
    assert.deepEqual(
        flattenGoods(filtered.roots, new Set(), true).map((row) => row.id),
        ["1", "2", "3"],
    );
    assert.equal(filtered.nodes.get("3")?.depth, 2);
});
test("preserves reachable records with inaccessible parents and old cached cycles", () => {
    const rows = [
        { id: "1", name: "A", parent_id: "2" },
        { id: "2", name: "B", parent_id: "1" },
        { id: "3", name: "C", parent_id: "private-parent" },
    ];
    const tree = goodsTree(rows, new Set(rows.map((row) => row.id)));
    const visible = flattenGoods(tree.roots, new Set(), true);
    assert.equal(visible.length, 3);
    assert.equal(new Set(visible.map((row) => row.id)).size, 3);
    assert.equal(tree.nodes.get("3")?.depth, 0);
});
