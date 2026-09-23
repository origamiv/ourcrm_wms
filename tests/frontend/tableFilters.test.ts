import { test, after } from "node:test";
import assert from "node:assert/strict";
import {
    applyTableFilter,
    serializedFilter,
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
