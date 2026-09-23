import test from "node:test";
import assert from "node:assert/strict";
import {
    rowMatchesSearch,
    valueAtPath,
} from "../../resources/js/lib/tableSearch";
import type { FilterField } from "../../resources/js/lib/tableFilters";

const fields: FilterField[] = [
    { id: "id", label: "#", type: "number" },
    { id: "name", label: "Название", type: "text" },
    { id: "roles.name", label: "Роли", type: "text" },
    {
        id: "status",
        label: "Статус",
        type: "tuple",
        format: (value) =>
            ({ 1: "Активен", 2: "Отключен" })[Number(value)] ?? String(value),
    },
];

test("поиск начинается с трёх символов и учитывает выбранные поля", () => {
    const row = { id: 15, name: "Иван", status: 1 };
    assert.equal(rowMatchesSearch(row, "ив", fields, ["name"]), false);
    assert.equal(rowMatchesSearch(row, "ива", fields, ["name"]), true);
    assert.equal(rowMatchesSearch(row, "ива", fields, ["id"]), false);
});

test("поиск учитывает отображаемое значение и регистр", () => {
    assert.equal(
        rowMatchesSearch({ id: 1, status: 1 }, "АКТИВ", fields, ["status"]),
        true,
    );
});

test("поиск раскрывает вложенные массивы", () => {
    const row = { roles: [{ name: "Администратор" }, { name: "Кладовщик" }] };
    assert.deepEqual(valueAtPath(row, "roles.name"), [
        "Администратор",
        "Кладовщик",
    ]);
    assert.equal(rowMatchesSearch(row, "клад", fields, ["roles.name"]), true);
});
