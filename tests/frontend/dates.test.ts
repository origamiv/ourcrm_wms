import { test } from "node:test";
import assert from "node:assert/strict";
import {
    formatDate,
    formatDateInTimezone,
    parseRussianDate,
    toIsoDate,
} from "../../resources/js/lib/dates";
test("Russian dates preserve wall time and reject calendar rollovers", () => {
    assert.equal(formatDate("2026-09-10T23:45:00", true), "10.09.26 23:45");
    assert.equal(
        formatDateInTimezone("2026-09-19T09:47:09.000000Z", "Europe/Moscow", true),
        "19.09.26 12:47",
    );
    assert.equal(toIsoDate(parseRussianDate("02.01.1990")!), "1990-01-02");
    assert.equal(toIsoDate(parseRussianDate("02.01.90")!), "1990-01-02");
    assert.equal(
        toIsoDate(parseRussianDate("02.01.26", false, "1926-01-02")!),
        "1926-01-02",
    );
    assert.equal(parseRussianDate("31.02.26"), undefined);
    assert.equal(parseRussianDate("29.02.25"), undefined);
    assert.equal(toIsoDate(parseRussianDate("29.02.24")!), "2024-02-29");
    assert.equal(parseRussianDate("10.09.26 24:00", true), undefined);
});
