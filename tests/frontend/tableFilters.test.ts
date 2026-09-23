import { test, after } from "node:test";
import assert from "node:assert/strict";
import {
    applyTableFilter,
    filterOptionsFromRows,
    serializedFilter,
    type FilterField,
    type FilterRules,
} from "../../resources/js/lib/tableFilters";
import { sessionChannel } from "../../resources/js/lib/http";

after(() => sessionChannel?.close());

test("табличный фильтр поддерживает вложенные группы и списки", () => {
    const rules: FilterRules = {
        glue: "and",
        rules: [
            {
                field: "status",
                type: "tuple",
                includes: ["active", "new"],
            },
            {
                glue: "or",
                rules: [
                    {
                        field: "name",
                        type: "text",
                        filter: "contains",
                        value: "склад",
                        includes: [],
                    },
                    {
                        field: "processed",
                        type: "number",
                        filter: "greaterOrEqual",
                        value: 10,
                    },
                ],
            },
        ],
    };
    const rows = [
        { id: 1, status: "active", name: "Главный склад", processed: 2 },
        { id: 2, status: "new", name: "Импорт", processed: 12 },
        { id: 3, status: "disabled", name: "Склад", processed: 20 },
    ];

    assert.deepEqual(
        applyTableFilter(rows, rules).map((row) => row.id),
        [1, 2],
    );
    assert.equal(serializedFilter(rules), JSON.stringify(rules));
});

test("табличный фильтр сравнивает даты и вложенные поля", () => {
    const rules: FilterRules = {
        glue: "and",
        rules: [
            {
                field: "details.created_at",
                type: "date",
                filter: "between",
                value: {
                    start: new Date("2026-09-01T00:00:00Z"),
                    end: new Date("2026-09-30T23:59:59Z"),
                },
            },
        ],
    };

    assert.deepEqual(
        applyTableFilter(
            [
                { id: 1, details: { created_at: "2026-09-15T10:00:00Z" } },
                { id: 2, details: { created_at: "2026-10-01T00:00:00Z" } },
            ],
            rules,
        ).map((row) => row.id),
        [1],
    );
});

test("конструктор получает уникальные значения каждого поля", () => {
    const fields: FilterField[] = [
        { id: "name", label: "Имя", type: "text" },
        { id: "profile.city", label: "Город", type: "text" },
        { id: "status", label: "Статус", type: "tuple" },
    ];
    const options = filterOptionsFromRows(
        [
            { name: "Иван", profile: { city: "Москва" }, status: 1 },
            { name: "Иван", profile: { city: "Москва" }, status: 2 },
            { name: "Анна", profile: { city: "Омск" }, status: 1 },
        ],
        fields,
        { status: [0, 1, 2] },
    );

    assert.deepEqual(options.name, ["Анна", "Иван"]);
    assert.deepEqual(options["profile.city"], ["Москва", "Омск"]);
    assert.deepEqual(options.status, [0, 1, 2]);
});
