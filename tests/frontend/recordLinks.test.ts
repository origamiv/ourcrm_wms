import { test } from "node:test";
import assert from "node:assert/strict";
import {
    clientRecordUrl,
    recordIdFromUrl,
    withoutRecordId,
} from "../../resources/js/lib/recordLinks";

test("builds links to exact client and webhook records", () => {
    assert.equal(
        clientRecordUrl("clients", 517),
        "/clients/clients?record_id=517",
    );
    assert.equal(
        clientRecordUrl("integrations", "18"),
        "/clients/integrations?record_id=18",
    );
});

test("reads and removes a valid record filter from URL", () => {
    assert.equal(recordIdFromUrl("/clients/clients?record_id=517"), "517");
    assert.equal(recordIdFromUrl("/clients/clients?record_id=undefined"), "");
    assert.equal(
        withoutRecordId("/clients/integrations?client_id=517&record_id=18"),
        "/clients/integrations?client_id=517",
    );
});
