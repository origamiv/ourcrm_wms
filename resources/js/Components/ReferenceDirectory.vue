<script setup lang="ts">
import { goodsTree, flattenGoods } from "../lib/goodsTree";
import { useCardRoute } from "../lib/cardRoute";
import { computed, ref, watch, onMounted, onUnmounted, nextTick } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import RussianDateInput from "./RussianDateInput.vue";
import { formatDate } from "../lib/dates";
import DocumentPrintFields from "./DocumentPrintFields.vue";
import DocumentDownload from "./DocumentDownload.vue";
import SearchableSelect from "./SearchableSelect.vue";
import StringListInput from "./StringListInput.vue";
import IntegrationTabs from "./IntegrationTabs.vue";
import FulfillmentTabs from "./FulfillmentTabs.vue";
import GoodsTabs from "./GoodsTabs.vue";
import AdminTabs from "./AdminTabs.vue";
import DataTransferMenu from "./DataTransferMenu.vue";
import ClientTabs from "./ClientTabs.vue";
import LogisticsTabs from "./LogisticsTabs.vue";
import { references } from "../lib/references";
import ConfirmDelete from "../Components/ConfirmDelete.vue";
import { createEntitySync } from "../lib/entitySync";
import { http, HttpError } from "../lib/http";
import type { EntityRow } from "../lib/cache";
interface ReferenceRow extends EntityRow {
    name: string | null;
    shortname?: string | null;
    category?: string | null;
    [key: string]: any;
    status: number | null;
    deleted_at: string | null;
}
const props = defineProps<{ entity: keyof typeof references; taskView?: "table" | "kanban" }>();
const emit = defineEmits<{ toggleTaskView: [] }>();
const definition = references[props.entity];
const columnSettingsOpen = ref(false);
const expandedMobileRows = ref<Set<string>>(new Set());
function toggleMobileRow(id: string | number, event?: MouseEvent) { if (event && event.detail > 1) return; const key = String(id); const next = new Set(expandedMobileRows.value); if (next.has(key)) next.delete(key); else next.add(key); expandedMobileRows.value = next; }
function openName(row: ReferenceRow, event: MouseEvent) { if (window.matchMedia('(max-width: 900px)').matches) { event.stopPropagation(); toggleMobileRow(row.id, event); return; } open(row, true); }
const columnOrder = ref<string[]>([]);
const hiddenColumns = ref<string[]>([]);
const draggedColumn = ref<string | null>(null);
const isCellGood = props.entity === "cell_goods";
const isAcceptance = props.entity === "acceptances";
const isTask = props.entity === "tasks";
const isClients = props.entity === "clients";
const columnStorageKey = computed(
    () => `reference-columns:${String(props.entity)}`,
);
const fixedColumnKeys = new Set(["__id", "__actions"]);
function isFixedColumn(key: string): boolean {
    return fixedColumnKeys.has(key) || (isTask && ["client_id", "task_type_id", "task_stage_id", "__sku_count"].includes(key)) || (["client_services", "client_accounts"].includes(props.entity) && ["__name", "shortname", "status"].includes(key));
}
const configurableColumns = computed(() => [
    {
        key: "__id",
        label: "#",
    },
    ...(!isCellGood && !isAcceptance ? [{
        key: "__name",
        label:
            props.entity === "kizes"
                ? "Код маркировки"
                : props.entity === "client_individuals"
                  ? "ФИО"
                  : "Название",
    }] : []),
    ...definition.fields,
    ...(isTask ? [{ key: "__sku_count", label: "Количество SKU/товара" }] : []),
    ...(!isCellGood ? [{
        key: "status",
        label: props.entity === "kizes" ? "Состояние" : "Статус",
    }] : []),
    {
        key: "__actions",
        label: "Действия",
    },
]);
const allColumns = computed(() => {
    const known = new Map(
        configurableColumns.value.map((field) => [field.key, field]),
    );
    const saved = columnOrder.value.filter((key) => known.has(key));
    const fresh = configurableColumns.value
        .map((field) => field.key)
        .filter((key) => !saved.includes(key));
    return [...saved, ...fresh].map((key) => known.get(key)!);
});
const orderedColumns = computed(() =>
    allColumns.value.filter((field) => isFixedColumn(field.key) || !hiddenColumns.value.includes(field.key)),
);
const renderedSpecialColumns = new Set([
    "__id",
    "__name",
    "__actions",
    "shortname",
    "code",
    "client_id",
    "task_type_id",
    "task_stage_id",
    "doc_type_id",
    "doc_date",
    "amount",
    "__sku_count",
    "status",
]);
const extraColumns = computed(() =>
    !isKiz && !isIntegration
        ? orderedColumns.value.filter(
              (field) =>
                  !renderedSpecialColumns.has(field.key) &&
                  field.kind !== "json",
          )
        : [],
);
function isColumnVisible(key: string): boolean {
    if (isFixedColumn(key)) return true;
    return !hiddenColumns.value.includes(key) && allColumns.value.some((field) => field.key === key);
}
function loadColumnSettings() {
    try {
        const saved = JSON.parse(
            localStorage.getItem(columnStorageKey.value) ?? "null",
        );
        if (Array.isArray(saved)) {
            columnOrder.value = saved.map(String);
        } else if (saved && typeof saved === "object") {
            columnOrder.value = Array.isArray(saved.order)
                ? saved.order.map(String)
                : configurableColumns.value.map((field) => field.key);
            hiddenColumns.value = Array.isArray(saved.hidden)
                ? saved.hidden.map(String)
                : [];
        } else {
            columnOrder.value = configurableColumns.value.map((field) => field.key);
        }
    } catch {
        columnOrder.value = configurableColumns.value.map((field) => field.key);
    }
}
function saveColumnSettings() {
    localStorage.setItem(
        columnStorageKey.value,
        JSON.stringify({ order: columnOrder.value, hidden: hiddenColumns.value }),
    );
}
function toggleColumn(key: string) {
    if (isFixedColumn(key)) return;
    hiddenColumns.value = hiddenColumns.value.includes(key)
        ? hiddenColumns.value.filter((item) => item !== key)
        : [...hiddenColumns.value, key];
    saveColumnSettings();
}
function startColumnDrag(key: string) {
    draggedColumn.value = key;
}
function dropColumn(target: string) {
    const source = draggedColumn.value;
    if (!source || source === target) return;
    const current = allColumns.value.map((field) => field.key);
    const from = current.indexOf(source);
    const to = current.indexOf(target);
    if (from < 0 || to < 0) return;
    current.splice(from, 1);
    current.splice(to, 0, source);
    columnOrder.value = current;
    saveColumnSettings();
    draggedColumn.value = null;
}
function columnValue(row: ReferenceRow, field: (typeof definition.fields)[number]) {
    const value = row[field.key];
    if (value == null || value === "") return "—";
    if (field.lookup) {
        return (
            choices(field.lookup).find(
                (item) => String(item.id) === String(value),
            )?.name ?? `№${value}`
        );
    }
    if (field.kind === "date" || field.kind === "datetime") {
        return formatDate(value, field.kind === "datetime");
    }
    if (field.kind === "json") {
        return typeof value === "string" ? value : JSON.stringify(value);
    }
    if (Array.isArray(value)) return value.join(", ");
    return String(value);
}
function taskLookupValue(row: ReferenceRow, key: string, lookup: string): string {
    const value = row[key];
    if (value == null || value === "") return "—";
    const found = choices(lookup).find((item) => String(item.id) === String(value));
    if (found?.name) return String(found.name);
    const src = row.src && typeof row.src === "object" ? row.src as Record<string, any> : {};
    return String(row[`${key.replace(/_id$/, "")}_name`] ?? src[`${key.replace(/_id$/, "")}_name`] ?? `№${value}`);
}
function lookupOption(entity: string, value: unknown): ReferenceRow | undefined {
    return choices(entity).find((item) => String(item.id) === String(value));
}
function lookupInitial(entity: string, value: unknown): string {
    const name = lookupOption(entity, value)?.name;
    return name ? String(name).trim().slice(0, 1).toUpperCase() : "?";
}
function taskSkuCount(row: ReferenceRow): string {
    const src = row.src && typeof row.src === "object" ? row.src : {};
    const sku = Array.isArray(src.goods)
        ? src.goods.length
        : Number(src.goods_count ?? src.products_count ?? 0);
    const count = Number(src.pieces_count ?? src.items_count ?? src.total_pieces ?? src.planned_pieces ?? row.fact_count ?? 0);
    return `${Number.isFinite(sku) ? sku : 0} / ${Number.isFinite(count) ? count : 0}`;
}
const isIntegration = props.entity.startsWith("integration_");
const detailLoading = ref(false);
const detailReady = ref(false);
let detailRequest = 0;
const isFulfillment = ["warehouses", "type_warehouses", "kind_warehouses", "type_storage", "zones", "cells", "cell_goods", "acceptances", "type_acceptance", "type_services", "services_ff", "tasks", "task_types", "task_statuses", "task_stages", "priorities", "marketplaces", "delivery_services"].includes(
    props.entity,
);
const isLogistics = ["orders", "shipments", "order_statuses", "order_sources", "order_cancel_statuses", "logistic_companies", "shipment_statuses"].includes(props.entity);
const isKiz = props.entity === "kizes";
const kizColumns = computed(() =>
    isKiz
        ? orderedColumns.value.filter(
              (field) =>
                  !field.key.startsWith("__") &&
                  field.key !== "code" &&
                  field.key !== "status",
          )
        : isIntegration
          ? definition.fields.filter((field) =>
                ["lookup", "number", "datetime"].includes(field.kind ?? ""),
            )
          : [],
);
const isGood = props.entity === "goods";
const isGoodsSection =
    isGood ||
    ["type_goods", "unit_goods", "kind_kiz", "kizes"].includes(props.entity);
const isIndividual = props.entity === "client_individuals";
const isClientScoped = ["client_individuals", "client_companies", "client_documents", "client_accounts"].includes(props.entity);
const isDocument = props.entity === "client_documents";
const isDocType = props.entity === "client_doc_types";
const isClientCatalog = ["client_services", "client_accounts"].includes(props.entity);
const isClientSection = isIndividual || isDocument || isDocType || isClientCatalog;
const basePath = isIntegration
    ? `/integration/${props.entity.replace("integration_", "")}`
    : isFulfillment
      ? `/fulfillment/${props.entity}`
    : isLogistics
      ? `/logistics/${props.entity}`
    : isGoodsSection
      ? `/goods/${props.entity}`
      : isClientSection
        ? `/clients/${props.entity.replace("client_", "")}`
        : `/main/${props.entity}`;
const endpoint = isIndividual ? null : basePath.replace("/main/", "/");
const clientFilter = ref("");
const docTypeFilter = ref("");
const dateFilter = ref("");

const page = usePage<any>();
const clientScope = computed<{ id: string; name: string } | null>(() =>
    isClientScoped ? (page.props.clientScope ?? null) : null,
);
const warehouseScope = computed<{ id: string; name: string } | null>(() =>
    props.entity === "cells" ? (page.props.warehouseScope ?? null) : null,
);
const store = createEntitySync<ReferenceRow>(
    `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,
    props.entity,
);
const { rows, ready, syncing, online, error, warning } = store;
const lookupStores = Object.fromEntries(
    [
        ...new Set(
            definition.fields.flatMap((field) =>
                field.lookup ? [field.lookup] : [],
            ),
        ),
    ].map((entity) => [
        entity,
        createEntitySync<ReferenceRow>(
            `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,
            entity,
        ),
    ]),
);
function choices(entity: string) {
    if (entity === "clients" && clientScope.value)
        return [
            {
                id: clientScope.value.id,
                name: clientScope.value.name,
            } as ReferenceRow,
        ];
    return (
        lookupStores[entity]?.rows.value.filter(
            (row) =>
                !row.deleted_at &&
                (!isGood ||
                    entity !== "good_cards" ||
                    (!!selected.value &&
                        String(row.good_id) === String(selected.value.id))) &&
                (!isDocument ||
                    ((entity !== "companies" ||
                        row.src?.is_own === true ||
                        row.src?.is_own === 1) &&
                        (entity !== "client_companies" ||
                            String(row.client_id) ===
                                String(form.value.client_id)))),
        ) ?? []
    );
}
const query = ref(""),
    shortQuery = ref(""),
    statusFilter = ref("all"),
    currentPage = ref(1),
    descending = ref(false);
const selected = ref<ReferenceRow | null>(null),
    deleting = ref<ReferenceRow | null>(null),
    conflict = ref<ReferenceRow | null>(null);
const conductingAcceptance = ref<ReferenceRow | null>(null);
const acceptanceBarcode = ref("");
const acceptanceSaving = ref(false);
const acceptanceNotice = ref("");
const acceptanceInput = ref<HTMLInputElement | null>(null);
function openAcceptance(row: ReferenceRow) {
    if (row.deleted_at || !online.value) return;
    editing.value = false;
    conductingAcceptance.value = row;
    acceptanceBarcode.value = "";
    acceptanceNotice.value = "";
    void nextTick(() => acceptanceInput.value?.focus());
}
function closeAcceptance() {
    if (!acceptanceSaving.value) conductingAcceptance.value = null;
}
async function pickAcceptance() {
    if (!conductingAcceptance.value || !acceptanceBarcode.value.trim() || acceptanceSaving.value || !online.value) return;
    acceptanceSaving.value = true;
    acceptanceNotice.value = "";
    try {
        const response = await http(`/web/acceptances/${conductingAcceptance.value.id}/pick`, "POST", { barcode: acceptanceBarcode.value.trim() });
        if (response.acceptance) {
            await store.apply(response.acceptance);
            conductingAcceptance.value = response.acceptance;
        }
        acceptanceBarcode.value = "";
        if (response.acceptance && Number(response.acceptance.status) === 1) {
            conductingAcceptance.value = null;
            return;
        }
        acceptanceNotice.value = "ШК принят";
        await nextTick();
        acceptanceInput.value?.focus();
    } catch (e) {
        acceptanceNotice.value = e instanceof HttpError ? e.message : e instanceof Error ? e.message : "Не удалось провести приемку";
    } finally {
        acceptanceSaving.value = false;
    }
}
// Selection is keyed by id and intentionally lives outside the paginated slice,
// so moving between pages does not clear previously selected records.
const checkedIds = ref<Set<string>>(new Set());
const pageChecked = computed(() => visible.value.length > 0 && visible.value.every((row) => checkedIds.value.has(String(row.id))));
const somePageChecked = computed(() => visible.value.some((row) => checkedIds.value.has(String(row.id))));
function toggleChecked(id: string | number) {
    const next = new Set(checkedIds.value);
    const key = String(id);
    if (next.has(key)) next.delete(key); else next.add(key);
    checkedIds.value = next;
}
function togglePageChecked() {
    const next = new Set(checkedIds.value);
    if (pageChecked.value) visible.value.forEach((row) => next.delete(String(row.id)));
    else visible.value.forEach((row) => next.add(String(row.id)));
    checkedIds.value = next;
}
const editing = ref(false),
    viewing = ref(false),
    saving = ref(false),
    notice = ref("");
const form = ref<Record<string, any>>({ name: "", status: 1 });
const statusLabels: Record<string, string> = isAcceptance
    ? { "0": "Новая", "1": "Завершена", "2": "Отменена", "3": "В процессе" }
    : isDocument
    ? { "0": "Новый", "1": "Активен", "2": "Отменен", "3": "Отправлен" }
    : isDocType
      ? { "1": "Активен", "2": "Отключен" }
      : {
            "0": "Новый",
            "1": "Активен",
            "2": "Отключен",
            null: "Не указан",
        };
const filtered = computed(() =>
    rows.value
        .filter((row) => {
            if (
                isDocument &&
                ((clientFilter.value &&
                    String(row.client_id) !== clientFilter.value) ||
                    (docTypeFilter.value &&
                        String(row.doc_type_id) !== docTypeFilter.value) ||
                    (dateFilter.value && row.doc_date !== dateFilter.value))
            )
                return false;
            if (
                clientScope.value &&
                String(row.client_id) !== clientScope.value.id
            )
                return false;
            if (
                warehouseScope.value &&
                String(row.warehouse_id) !== warehouseScope.value.id
            )
                return false;
            if (
                statusFilter.value === "deleted"
                    ? !row.deleted_at
                    : !!row.deleted_at
            )
                return false;
            if (
                !["all", "deleted"].includes(statusFilter.value) &&
                String(row.status) !== statusFilter.value
            )
                return false;
            return (
                [
                    row.name,
                    ...(isKiz ? [row.code] : []),
                    row.shortname,
                    row.category,
                    row.path,
                    ...(isGood
                        ? [
                              row.code,
                              ...(Array.isArray(row.articul)
                                  ? row.articul
                                  : []),
                          ]
                        : []),
                ]
                    .join(" ")
                    .toLocaleLowerCase("ru")
                    .includes(query.value.toLocaleLowerCase("ru")) &&
                (row.shortname ?? row.category ?? "")
                    .toLocaleLowerCase("ru")
                    .includes(shortQuery.value.toLocaleLowerCase("ru"))
            );
        })
        .sort(
            (a, b) =>
                (isKiz ? (a.code ?? "") : (a.name ?? "")).localeCompare(
                    isKiz ? (b.code ?? "") : (b.name ?? ""),
                    "ru",
                ) * (descending.value ? -1 : 1),
        ),
);
const expandedGoods = ref(new Set<string>());
const goodsFiltered = computed(
    () => !!query.value || !!shortQuery.value || statusFilter.value !== "all",
);
const goodsForest = computed(() =>
    goodsTree(
        rows.value.filter(
            (row) => statusFilter.value === "deleted" || !row.deleted_at,
        ),
        new Set(filtered.value.map((row) => row.id)),
        descending.value,
    ),
);
const pages = computed(() =>
    Math.max(
        1,
        Math.ceil(
            (isGood ? goodsForest.value.roots.length : filtered.value.length) /
                25,
        ),
    ),
);
const pageItems = computed<(number | string)[]>(() => {
    const total = pages.value;
    if (total <= 7) return Array.from({ length: total }, (_, index) => index + 1);
    const current = currentPage.value;
    if (current <= 4) return [1, 2, 3, 4, 5, "…", total];
    if (current >= total - 3) return [1, "…", total - 4, total - 3, total - 2, total - 1, total];
    return [1, "…", current - 1, current, current + 1, "…", total];
});
const visible = computed(() =>
    isGood
        ? flattenGoods(
              goodsForest.value.roots.slice(
                  (currentPage.value - 1) * 25,
                  currentPage.value * 25,
              ),
              expandedGoods.value,
              goodsFiltered.value,
          )
        : filtered.value.slice(
              (currentPage.value - 1) * 25,
              currentPage.value * 25,
          ),
);
function toggleGood(id: string) {
    const next = new Set(expandedGoods.value);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    expandedGoods.value = next;
}
function revealGood(row: ReferenceRow) {
    const byId = new Map(rows.value.map((item) => [item.id, item]));
    const next = new Set(expandedGoods.value);
    const seen = new Set<string>([row.id]);
    let current = row;
    while (
        current.parent_id &&
        byId.has(String(current.parent_id)) &&
        !seen.has(String(current.parent_id))
    ) {
        const id = String(current.parent_id);
        seen.add(id);
        next.add(id);
        current = byId.get(id)!;
    }
    expandedGoods.value = next;
    const index = goodsForest.value.roots.findIndex(
        (node) => node.row.id === current.id,
    );
    if (index >= 0) currentPage.value = Math.floor(index / 25) + 1;
}
watch(
    [query, shortQuery, statusFilter, clientFilter, docTypeFilter, dateFilter],
    () => (currentPage.value = 1),
);
watch(
    pages,
    (count) => (currentPage.value = Math.min(currentPage.value, count)),
);
watch(
    () => clientScope.value?.id,
    () => {
        editing.value = false;
        deleting.value = null;
        currentPage.value = 1;
    },
);
const pendingDocumentDefaults = new Set<string>();
const pendingTaskDefaults = new Set<string>();
function applyTaskDefaults() {
    if (!isTask || !editing.value || viewing.value || selected.value) return;
    const defaults: Record<string, [string, (row: ReferenceRow) => boolean]> = {
        client_id: ["clients", () => true],
        task_type_id: ["task_types", () => true],
        status_id: ["task_statuses", (row) => String(row.id) === "1" || row.shortname === "new"],
        priority_id: ["priorities", (row) => row.shortname === "medium" || /средн/i.test(String(row.name ?? ""))],
        warehouse_id: ["warehouses", () => true],
    };
    for (const [field, [entity, predicate]] of Object.entries(defaults)) {
        if (!pendingTaskDefaults.has(field) || (form.value[field] != null && form.value[field] !== "")) { pendingTaskDefaults.delete(field); continue; }
        if (!lookupStores[entity]?.ready.value) continue;
        const available = choices(entity); const found = available.find(predicate) ?? available[0];
        if (found) { form.value[field] = found.id; pendingTaskDefaults.delete(field); }
    }
}
function applyDocumentDefaults() {
    if (!isDocument || !editing.value || viewing.value) return;
    for (const [field, entity] of [
        ["client_id", "clients"],
        ["executor_id", "companies"],
    ]) {
        if (!pendingDocumentDefaults.has(field)) continue;
        if (form.value[field] != null && form.value[field] !== "") {
            pendingDocumentDefaults.delete(field);
            continue;
        }
        if (!lookupStores[entity]?.ready.value) continue;
        const first = choices(entity)[0];
        if (first) {
            form.value[field] = first.id;
            pendingDocumentDefaults.delete(field);
        }
    }
}
watch(
    () => [
        editing.value,
        viewing.value,
        lookupStores.clients?.ready.value,
        lookupStores.clients?.rows.value,
        lookupStores.companies?.ready.value,
        lookupStores.companies?.rows.value,
    ],
    applyDocumentDefaults,
);
watch(() => [editing.value, viewing.value, selected.value, ...Object.values(lookupStores).flatMap((s) => [s.ready.value, s.rows.value])], applyTaskDefaults);
function changeLookup(field: string) {
    pendingDocumentDefaults.delete(field);
    pendingTaskDefaults.delete(field);
    if (isDocument && field === "client_id") form.value.customer_id = null;
}
function displayName(row: ReferenceRow) {
    return (
        (isKiz && "kind_kiz_id" in row ? row.code : null) ||
        row.name ||
        row.shortname ||
        `Запись №${row.id}`
    );
}
function openGoodDetail(row: ReferenceRow) {
    const url = `/goods/goods/${encodeURIComponent(row.id)}/view`;
    if (online.value) router.visit(url);
    else router.push({ url, component: "GoodDetail", props: { ...page.props, goodId: row.id } });
}
function openTaskDetail(row: ReferenceRow) {
    const url = `/fulfillment/tasks/${encodeURIComponent(row.id)}/view`;
    if (online.value) router.visit(url);
    else router.push({ url, component: "TaskDetail", props: { ...page.props, taskId: row.id } });
}
async function open(row: ReferenceRow | null, readOnly = false) {
    if (saving.value) return;
    const request = ++detailRequest;
    fillForm(row, readOnly);
    detailReady.value = !row;
    detailLoading.value = false;
    if (!isIntegration || !row) return;
    if (!online.value) {
        notice.value = "Полная карточка доступна при подключении к сети.";
        return;
    }
    detailLoading.value = true;
    try {
        const response = await http(`/web${basePath}/${row.id}`);
        if (
            request !== detailRequest ||
            !editing.value ||
            selected.value?.id !== row.id
        )
            return;
        await store.apply(response.data);
        if (
            request !== detailRequest ||
            !editing.value ||
            selected.value?.id !== row.id
        )
            return;
        fillForm({ ...response.data, ...response.details }, readOnly);
        // Параметры остаются только в форме, не в IndexedDB или истории Inertia.
        selected.value = response.data;
        detailReady.value = true;
    } catch (e) {
        if (request === detailRequest)
            notice.value =
                e instanceof Error
                    ? e.message
                    : "Не удалось загрузить карточку.";
    } finally {
        if (request === detailRequest) detailLoading.value = false;
    }
}
function fillForm(row: ReferenceRow | null, readOnly = false) {
    if (
        saving.value ||
        (clientScope.value &&
            row &&
            String(row.client_id) !== clientScope.value.id)
    )
        return;
    if (isGood) {
        if (row) revealGood(row);
        // Refresh catalog deltas when reopening a card in an already loaded section.
        void lookupStores.type_goods.sync();
        void lookupStores.unit_goods.sync();
    }
    selected.value = row;
    form.value = {
        name: row?.name ?? "",
        ...Object.fromEntries(
            definition.fields.map((field) => [
                field.key,
                (field.kind === "datetime" && row?.[field.key]
                    ? String(row[field.key]).replace(" ", "T").slice(0, 16)
                    : row?.[field.key]) ??
                    (field.kind === "number" ||
                    field.kind === "flag" ||
                    field.kind === "lookup"
                        ? null
                        : ""),
            ]),
        ),
        ...(isGood ? { is_category: row?.is_category ?? 2 } : {}),
        status: row ? row.status : isDocument ? 0 : 1,
        ...(clientScope.value ? { client_id: clientScope.value.id } : {}),
        ...(warehouseScope.value ? { warehouse_id: warehouseScope.value.id } : {}),
        ...(isTask && !row
            ? { created_by_user_id: page.props.auth.id }
            : {}),
    };
    pendingTaskDefaults.clear();
    if (isTask && !row) ["client_id", "task_type_id", "status_id", "priority_id", "warehouse_id"].forEach((field) => pendingTaskDefaults.add(field));
    for (const field of definition.fields)
        if (field.kind === "json")
            form.value[field.key] =
                row?.[field.key] == null
                    ? ""
                    : JSON.stringify(row[field.key], null, 2);
    for (const field of definition.fields)
        if (field.kind === "string_list")
            form.value[field.key] = Array.isArray(row?.[field.key])
                ? row[field.key].map(String)
                : [];
    for (const field of definition.fields)
        if (field.kind === "lookup_list")
            form.value[field.key] = Array.isArray(row?.[field.key])
                ? row[field.key].map(String)
                : [];
    if (isDocument && !row)
        form.value.src = JSON.stringify(
            { pdf: { number: "", basis: "", items: [], terms: "" } },
            null,
            2,
        );
    if (isGood && !row) form.value.is_from_external = 2;
    if (isGood)
        for (const field of [
            "parent_id",
            "goodcard_id",
            "type_good",
            "type_unit",
        ]) {
            if (String(form.value[field]) === "0") form.value[field] = null;
        }
    viewing.value = readOnly || !!row?.deleted_at;
    notice.value = "";
    conflict.value = null;
    editing.value = true;
    pendingDocumentDefaults.clear();
    if (isDocument) {
        for (const field of ["client_id", "executor_id"]) {
            if (form.value[field] == null || form.value[field] === "")
                pendingDocumentDefaults.add(field);
        }
        applyDocumentDefaults();
    }
}
async function save(remove = false) {
    if (
        !online.value ||
        saving.value ||
        (!remove && viewing.value) ||
        (isIntegration && !detailReady.value && !remove)
    )
        return;
    const creating = !selected.value && !remove;
    saving.value = true;
    notice.value = "";
    try {
        const payload = { ...form.value };
        if (isDocument)
            payload.amount =
                payload.amount === "" || payload.amount == null
                    ? null
                    : String(payload.amount).replace(",", ".");
        if (!remove)
            for (const field of definition.fields)
                if (field.kind === "json") {
                    try {
                        payload[field.key] = String(
                            form.value[field.key] ?? "",
                        ).trim()
                            ? JSON.parse(form.value[field.key])
                            : null;
                    } catch {
                        throw new Error(
                            `Поле «${field.label}» содержит некорректный JSON.`,
                        );
                    }
                    if (
                        payload[field.key] !== null &&
                        typeof payload[field.key] !== "object"
                    )
                        throw new Error(
                            `Поле «${field.label}» должно содержать JSON-объект или массив.`,
                        );
                }
        if (!remove)
            for (const field of definition.fields)
                if (field.kind === "string_list")
                    payload[field.key] = form.value[field.key]
                        .map((value: string) => value.trim())
                        .filter((value: string) => value !== "");
        const response = await http(
            `/web/${isIndividual ? `clients/${clientScope.value ? clientScope.value.id + "/" : ""}individuals` : endpoint!.replace(/^\//, "")}${selected.value ? "/" + selected.value.id : ""}`,
            remove ? "DELETE" : selected.value ? "PUT" : "POST",
            {
                ...(!remove ? payload : {}),
                ...(selected.value ? { version: selected.value.version } : {}),
            },
        );
        await store.apply(response.data);
        if (isGood) {
            await store.sync();
            if (!remove) revealGood(response.data);
        }
        if (creating) {
            // Keep the editor open for rapid consecutive entry creation.
            saving.value = false;
            fillForm(null, false);
            notice.value = "Запись создана. Можно добавить следующую.";
        } else {
            selected.value = response.data;
            if (isGood) form.value.is_category = response.data.is_category;
        }
        conflict.value = null;
        if (!creating) notice.value = "Изменения сохранены";
        if (remove) editing.value = false;
    } catch (e) {
        if (e instanceof HttpError && e.status === 409)
            conflict.value = e.body.current;
        notice.value =
            e instanceof HttpError && e.body.errors
                ? Object.values(e.body.errors).flat().join(" ")
                : e instanceof Error
                  ? e.message
                  : "Не получено подтверждение сервера";
    } finally {
        saving.value = false;
    }
}
function normalizedHeader(value: unknown) {
    return String(value ?? "").trim().toLocaleLowerCase("ru").replace(/[\s_#№.-]+/g, "");
}
async function importData(format: string, file?: File, text?: string) {
    if (!file && !text) return;
    if (!online.value || saving.value) {
        notice.value = "Импорт доступен только при подключении к серверу.";
        return;
    }
    saving.value = true;
    notice.value = "Читаем файл…";
    try {
        const upload = new FormData();
        if (file) upload.append("file", file);
        else upload.append("file", new Blob([text ?? ""], { type: "text/plain" }), "clipboard.txt");
        upload.append("format", format);
        if (warehouseScope.value) upload.append("warehouse_id", warehouseScope.value.id);
        upload.append("columns", JSON.stringify(definition.fields.map((field) => ({ key: field.key, label: field.label }))));
        const response = await http(`/web/import/${encodeURIComponent(props.entity)}`, "POST", upload);
        for (const row of response.data ?? []) await store.apply(row);
        notice.value = response.imported ? `Импортировано записей: ${response.imported}` : "В файле не найдено строк для импорта.";
    } catch (e) {
        notice.value = e instanceof Error ? `Импорт не выполнен: ${e.message}` : "Импорт не выполнен.";
    } finally {
        saving.value = false;
    }
}
async function confirmDelete() {
    if (!deleting.value || !online.value || saving.value) return;
    ++detailRequest;
    detailLoading.value = false;
    fillForm(deleting.value, true);
    deleting.value = null;
    await save(true);
}
onMounted(async () => {
    loadColumnSettings();
    await Promise.all([
        store.start(),
        ...Object.values(lookupStores).map((store) => store.start()),
    ]);
});
onUnmounted(() => {
    store.stop();
    ++detailRequest;
    Object.values(lookupStores).forEach((store) => store.stop());
});

useCardRoute<ReferenceRow>({
    base: basePath,
    rows,
    ready,
    state: () =>
        deleting.value
            ? { id: deleting.value.id, action: "delete" }
            : editing.value
              ? {
                    id: selected.value?.id ?? "0",
                    action: !selected.value
                        ? "create"
                        : viewing.value
                          ? "view"
                          : "edit",
                }
              : null,
    open: (row, action) => {
        if (action === "delete" && row) deleting.value = row;
        else open(row, action === "view");
    },
    close: () => {
        editing.value = false;
        deleting.value = null;
    },
    missing: () => {
        error.value = "Запись недоступна или ещё не загружена.";
    },
});
</script>
<template>
    <Head :title="definition.title" />
    <div class="users-workspace" :class="{ 'has-editor': editing || conductingAcceptance }">
        <section class="users-list">
            <div class="content-breadcrumb">
                {{
                    isIntegration
                        ? "Интеграции"
                        : isFulfillment
                        ? "Фулфилмент › Справочники"
                        : isLogistics
                          ? (props.entity === "orders" ? "Логистика" : "Логистика › Справочники")
                        : isGoodsSection
                          ? "Товары"
                          : isClientSection
                            ? "Клиенты"
                            : "Администрирование › Справочники"
                }}
                › {{ definition.title }}
            </div>
            <IntegrationTabs v-if="isIntegration" /><FulfillmentTabs
                v-else-if="isFulfillment"
            /><LogisticsTabs v-else-if="isLogistics" /><GoodsTabs
                v-else-if="isGoodsSection"
            /><ClientTabs v-else-if="isClientSection" /><AdminTabs v-else />
            <p v-if="clientScope || warehouseScope" class="notice">
                <template v-if="clientScope">Клиент: <strong>{{ clientScope.name }}</strong></template>
                <template v-else>Склад: <strong>{{ warehouseScope?.name }}</strong></template>
            </p>
            <div class="page-heading">
                <div class="heading-title-group">
                    <h1>{{ warehouseScope ? `${definition.title} для склада ${warehouseScope.name}` : clientScope ? `${definition.title} для клиента ${clientScope.name}` : definition.title }}</h1>
                    <button v-if="props.entity === 'tasks'" type="button" class="task-view-toggle" @click="emit('toggleTaskView')">
                        <img :src="props.taskView === 'kanban' ? '/design/crm/table.svg' : '/design/crm/kanban.svg'" alt="" />
                        <span>{{ props.taskView === 'kanban' ? 'Таблица' : 'Канбан' }}</span>
                    </button>
                </div>
                <div class="page-heading-actions">
                    <DataTransferMenu :rows="filtered" :columns="orderedColumns" :filename="String(props.entity)" @import="importData" />
                    <button class="primary" :disabled="!online || !ready || saving" @click="open(null)">Добавить запись</button>
                </div>
            </div>
            <div class="sync-line" role="status">
                {{
                    !online
                        ? "Нет связи · сохранённые данные"
                        : syncing
                          ? "Загружаем изменения…"
                          : ready
                            ? "Данные синхронизированы"
                            : "Первичная загрузка…"
                }}
            </div>
            <p v-if="error || warning" class="notice" role="alert">
                {{ error || warning }}
            </p>
            <slot v-if="props.entity === 'tasks' && props.taskView === 'kanban'" name="task-kanban" />
            <div v-if="isGood" class="goods-tree-controls">
                <button
                    type="button"
                    :disabled="goodsFiltered"
                    @click="expandedGoods = new Set(goodsForest.nodes.keys())"
                >
                    Развернуть всё
                </button>
                <button
                    type="button"
                    :disabled="goodsFiltered"
                    @click="expandedGoods = new Set()"
                >
                    Свернуть всё
                </button>
                <span>Категории — папки, товары — коробки</span>
            </div>
            <div v-if="props.entity !== 'tasks' || props.taskView !== 'kanban'" class="table-scroll">
                <div v-if="columnSettingsOpen" class="column-settings-panel" role="dialog" aria-label="Настройка колонок">
                    <div class="column-settings-title">
                        <span>Показывать колонки</span>
                        <button
                            type="button"
                            class="column-settings-close"
                            aria-label="Закрыть настройки колонок"
                            title="Закрыть"
                            @click.stop="columnSettingsOpen = false"
                        >
                            ×
                        </button>
                    </div>
                    <div
                        v-for="field in allColumns"
                        :key="field.key"
                        class="column-settings-item"
                        draggable="true"
                        @dragstart="startColumnDrag(field.key)"
                        @dragover.prevent
                        @drop="dropColumn(field.key)"
                    >
                        <label class="column-settings-control">
                            <input
                                type="checkbox"
                                :checked="isColumnVisible(field.key)"
                                @change="toggleColumn(field.key)"
                            />
                            <span class="column-drag-handle" aria-hidden="true">⠿</span>
                            <span class="column-settings-label">{{ field.label }}</span>
                        </label>
                    </div>
                </div>
                <table
                    :role="isGood ? 'treegrid' : undefined"
                    :aria-label="isGood ? 'Дерево товаров' : undefined"
                >
                    <thead>
                        <tr>
                            <th scope="col" class="check-column">
                                <input
                                    type="checkbox"
                                    aria-label="Выбрать все записи на странице"
                                    :checked="pageChecked"
                                    :indeterminate="somePageChecked && !pageChecked"
                                    @change="togglePageChecked"
                                />
                            </th>
                            <th scope="col" class="id-column">#</th>
                            <th v-if="isColumnVisible('__name')">
                                <button @click="descending = !descending">
                                    {{
                                        isKiz
                                            ? "Код маркировки"
                                            : isIndividual
                                              ? "ФИО"
                                              : "Название"
                                    }}
                                    {{ descending ? "▴" : "▾" }}
                                </button>
                            </th>
                            <th v-if="!isKiz && isColumnVisible('shortname')">
                                {{
                                    [
                                        "goods",
                                        "type_goods",
                                        "unit_goods",
                                        "kind_kiz",
                                        "marketplaces",
                                        "delivery_services",
                                        "warehouses",
                                        "type_warehouses",
                                        "kind_warehouses",
                                        "type_storage",
                                        "zones",
                                        "cells",
                                        "modules",
                                        "features",
                                        "client_individuals",
                                        "client_documents",
                                        "client_doc_types",
                                    ].includes(props.entity)
                                        ? isIndividual
                                            ? "Краткое имя"
                                            : "Краткое название"
                                        : "Категория"
                                }}
                            </th>
                            <th v-if="isGood && isColumnVisible('code')">Код</th>
                            <template v-if="isDocument"
                                ><th v-if="isColumnVisible('client_id')">Клиент</th>
                                <th v-if="isColumnVisible('doc_type_id')">Тип документа</th>
                                <th v-if="isColumnVisible('doc_date')">Дата документа</th>
                                <th v-if="isColumnVisible('amount')">Сумма</th></template
                            >
                            <th v-if="isTask && isColumnVisible('client_id')">Клиент</th>
                            <th v-if="isTask && isColumnVisible('task_type_id')">Тип задачи</th>
                            <th v-if="isTask && isColumnVisible('task_stage_id')">Этап задачи</th>
                            <th v-if="isTask && isColumnVisible('__sku_count')">Количество SKU/товара</th>
                            <th v-for="field in extraColumns" :key="field.key">
                                {{ field.label }}
                            </th>
                            <th v-for="field in kizColumns" :key="field.key">
                                {{ field.label }}
                            </th>
                            <th v-if="isColumnVisible('status')">{{ isKiz ? "Состояние" : "Статус" }}</th>
                            <th v-if="isColumnVisible('__actions')">
                                Действия
                                <button
                                    type="button"
                                    class="column-settings-button"
                                    title="Настроить колонки"
                                    aria-label="Настроить колонки"
                                    @click.stop="columnSettingsOpen = !columnSettingsOpen"
                                >⚙</button>
                            </th>
                        </tr>
                        <tr class="filter-row">
                            <th class="check-column"></th>
                            <th class="id-column"></th>
                            <th v-if="isColumnVisible('__name')">
                                <input
                                    v-model="query"
                                    :aria-label="`Поиск: ${definition.title}`"
                                    placeholder="Поиск"
                                />
                            </th>
                            <th v-if="!isKiz && isColumnVisible('shortname')">
                                <input
                                    v-model="shortQuery"
                                    :aria-label="
                                        [
                                            'goods',
                                            'type_goods',
                                            'unit_goods',
                                            'kind_kiz',
                                            'marketplaces',
                                            'delivery_services',
                                            'warehouses',
                                            'type_warehouses',
                                            'kind_warehouses',
                                            'type_storage',
                                            'zones',
                                            'cells',
                                            'modules',
                                            'features',
                                            'client_individuals',
                                            'client_documents',
                                            'client_doc_types',
                                            'client_services',
                                            'client_accounts',
                                        ].includes(props.entity)
                                            ? 'Поиск по краткому названию'
                                            : 'Поиск по категории'
                                    "
                                />
                            </th>
                            <th v-if="isGood && isColumnVisible('code')"></th>
                            <template v-if="isDocument">
                                <th v-if="isColumnVisible('client_id')">
                                    <select
                                        v-model="clientFilter"
                                        aria-label="Фильтр клиента"
                                    >
                                        <option value="">Все клиенты</option>
                                        <option
                                            v-for="row in choices('clients')"
                                            :key="row.id"
                                            :value="String(row.id)"
                                        >
                                            {{ displayName(row) }}
                                        </option>
                                    </select>
                                </th>
                                <th v-if="isColumnVisible('doc_type_id')">
                                    <select
                                        v-model="docTypeFilter"
                                        aria-label="Фильтр типа документа"
                                    >
                                        <option value="">Все типы</option>
                                        <option
                                            v-for="row in choices(
                                                'client_doc_types',
                                            )"
                                            :key="row.id"
                                            :value="String(row.id)"
                                        >
                                            {{ displayName(row) }}
                                        </option>
                                    </select>
                                </th>
                                <th v-if="isColumnVisible('doc_date')">
                                    <RussianDateInput
                                        v-model="dateFilter"
                                        aria-label="Фильтр даты документа"
                                    />
                                </th>
                                <th v-if="isColumnVisible('amount')"></th>
                            </template>
                            <th v-if="isTask && isColumnVisible('client_id')"></th>
                            <th v-if="isTask && isColumnVisible('task_type_id')"></th>
                            <th v-if="isTask && isColumnVisible('task_stage_id')"></th>
                            <th v-if="isTask && isColumnVisible('__sku_count')"></th>
                            <th v-for="field in extraColumns" :key="field.key"></th>
                            <th
                                v-for="field in kizColumns"
                                :key="field.key"
                            ></th>
                            <th v-if="isColumnVisible('status')">
                                <select
                                    v-model="statusFilter"
                                    aria-label="Фильтр статуса"
                                >
                                    <option value="all">Все</option>
                                    <option
                                        v-for="[value, label] in Object.entries(
                                            statusLabels,
                                        ).filter(
                                            ([key]) => !isKiz && key !== 'null',
                                        )"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{ label }}
                                    </option>
                                    <option value="deleted">Удалённые</option>
                                </select>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in visible"
                            :key="row.id"
                            :class="{
                                'goods-category-row':
                                    isGood &&
                                    (row.is_category === 1 ||
                                        !!goodsForest.nodes.get(row.id)
                                            ?.children.length),
                                'mobile-card-expanded': expandedMobileRows.has(String(row.id)),
                            }"
                            :aria-level="
                                isGood
                                    ? (goodsForest.nodes.get(row.id)?.depth ??
                                          0) + 1
                                    : undefined
                            "
                            :aria-expanded="
                                isGood &&
                                goodsForest.nodes.get(row.id)?.children.length
                                    ? goodsFiltered || expandedGoods.has(row.id)
                                    : undefined
                            "
                            @click="toggleMobileRow(row.id, $event)"
                            @dblclick="isGood ? openGoodDetail(row) : isTask ? openTaskDetail(row) : open(row, true)"
                        >
                            <td class="check-column" @dblclick.stop>
                                <input
                                    type="checkbox"
                                    :aria-label="`Выбрать запись №${row.id}`"
                                    :checked="checkedIds.has(String(row.id))"
                                    @change="toggleChecked(row.id)"
                                />
                            </td>
                            <td class="id-column">{{ row.id }}</td>
                            <td v-if="isColumnVisible('__name')">
                                <div
                                    v-if="isGood"
                                    class="goods-tree-name"
                                    :style="{
                                        paddingLeft:
                                            (goodsForest.nodes.get(row.id)
                                                ?.depth ?? 0) *
                                                24 +
                                            'px',
                                    }"
                                >
                                    <button
                                        v-if="
                                            goodsForest.nodes.get(row.id)
                                                ?.children.length
                                        "
                                        class="goods-tree-toggle"
                                        :disabled="goodsFiltered"
                                        :aria-label="
                                            (expandedGoods.has(row.id) ||
                                            goodsFiltered
                                                ? 'Свернуть: '
                                                : 'Развернуть: ') +
                                            displayName(row)
                                        "
                                        :aria-expanded="
                                            goodsFiltered ||
                                            expandedGoods.has(row.id)
                                        "
                                        @click.stop="toggleGood(row.id)"
                                        @dblclick.stop
                                    >
                                        {{
                                            expandedGoods.has(row.id) ||
                                            goodsFiltered
                                                ? "▾"
                                                : "▸"
                                        }}
                                    </button>
                                    <span
                                        v-else
                                        class="goods-tree-spacer"
                                    ></span>
                                    <svg
                                        v-if="
                                            row.is_category === 1 ||
                                            goodsForest.nodes.get(row.id)
                                                ?.children.length
                                        "
                                        class="goods-category-icon"
                                        viewBox="0 0 24 24"
                                        role="img"
                                        aria-label="Категория"
                                    >
                                        <path
                                            d="M3 5h7l2 3h9v12H3z"
                                            fill="currentColor"
                                            stroke="currentColor"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                    <img
                                        v-else
                                        src="/design/crm/goods.svg"
                                        alt="Товар"
                                        class="goods-item-icon"
                                    />
                                    <button
                                        class="name-button"
                                        @click="openName(row, $event)"
                                    >
                                        {{ displayName(row) }}
                                    </button>
                                </div>
                                <button
                                    v-else
                                    class="name-button"
                                    @click="openName(row, $event)"
                                >
                                    {{ displayName(row) }}
                                </button>
                            </td>
                            <td v-if="!isKiz && isColumnVisible('shortname')" data-label="Краткое название">
                                {{ row.shortname || "—" }}
                            </td>
                            <td v-for="field in kizColumns" :key="field.key" :data-label="field.label">
                                {{
                                    field.lookup
                                        ? (choices(field.lookup).find(
                                              (item) =>
                                                  String(item.id) ===
                                                  String(row[field.key]),
                                          )?.name ??
                                          (row[field.key]
                                              ? "№" + row[field.key]
                                              : "—"))
                                        : field.kind === "datetime"
                                          ? formatDate(row[field.key], true)
                                          : (row[field.key] ?? "—")
                                }}
                            </td>
                            <td v-if="isGood && isColumnVisible('code')" data-label="Код">
                                {{ row.code || "—" }}
                            </td>
                            <template v-if="isDocument">
                                <td v-if="isColumnVisible('client_id')">
                                    {{
                                        lookupStores.clients?.rows.value.find(
                                            (item) =>
                                                String(item.id) ===
                                                String(row.client_id),
                                        )?.name || `Клиент №${row.client_id}`
                                    }}
                                </td>
                                <td v-if="isColumnVisible('doc_type_id')">
                                    {{
                                        lookupStores.client_doc_types?.rows.value.find(
                                            (item) =>
                                                String(item.id) ===
                                                String(row.doc_type_id),
                                        )?.name || `Тип №${row.doc_type_id}`
                                    }}
                                </td>
                                <td v-if="isColumnVisible('doc_date')">
                                    {{ formatDate(row.doc_date) }}
                                </td>
                                <td v-if="isColumnVisible('amount')">
                                    {{
                                        row.amount == null
                                            ? "—"
                                            : String(row.amount).replace(
                                                  ".",
                                                  ",",
                                              )
                                    }}
                                </td>
                            </template>
                            <td v-if="isTask && isColumnVisible('client_id')">
                                {{ columnValue(row, { key: 'client_id', label: 'Клиент', kind: 'lookup', lookup: 'clients' }) }}
                            </td>
                            <td v-if="isTask && isColumnVisible('task_type_id')"><div class="lookup-avatar-cell"><span class="lookup-avatar"><img v-if="lookupOption('task_types', row.task_type_id)?.icon" :src="String(lookupOption('task_types', row.task_type_id)?.icon)" alt="" /><span v-else>{{ lookupInitial('task_types', row.task_type_id) }}</span></span><span>{{ taskLookupValue(row, 'task_type_id', 'task_types') }}</span></div></td>
                            <td v-if="isTask && isColumnVisible('task_stage_id')">{{ taskLookupValue(row, 'task_stage_id', 'task_stages') }}</td>
                            <td v-if="isTask && isColumnVisible('__sku_count')">
                                {{ taskSkuCount(row) }}
                            </td>
                            <td v-for="field in extraColumns" :key="field.key">
                                <div v-if="isTask && (field.key === 'task_type_id' || field.key === 'priority_id')" class="lookup-avatar-cell">
                                    <span class="lookup-avatar" :title="lookupOption(field.lookup!, row[field.key])?.name || field.label">
                                        <img v-if="lookupOption(field.lookup!, row[field.key])?.icon" :src="String(lookupOption(field.lookup!, row[field.key])?.icon)" alt="" />
                                        <span v-else>{{ lookupInitial(field.lookup!, row[field.key]) }}</span>
                                    </span>
                                    <span>{{ columnValue(row, field) }}</span>
                                </div>
                                <div v-else-if="isAcceptance && field.key === 'progress'" class="table-progress" :aria-label="`Прогресс: ${Number(row.progress ?? 0)}%`">
                                    <span class="table-progress-track"><i :style="{ width: `${Math.max(0, Math.min(100, Number(row.progress ?? 0)))}%` }"></i></span>
                                    <b>{{ Math.max(0, Math.min(100, Number(row.progress ?? 0))) }}%</b>
                                </div>
                                <template v-else>{{ columnValue(row, field) }}</template>
                            </td>
                            <td v-if="isColumnVisible('status')">
                                <span
                                    class="badge"
                                    :class="`status-${row.deleted_at ? 'deleted' : row.status}`"
                                    >{{
                                        row.deleted_at
                                            ? "Удалён"
                                            : isKiz
                                              ? "Действующая"
                                              : (statusLabels[
                                                    String(row.status)
                                                ] ?? String(row.status))
                                    }}</span
                                >
                                <span v-if="isTask" class="task-stage-inline">{{ taskLookupValue(row, "task_stage_id", "task_stages") }}</span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <template v-if="isClients">
                                        <button :aria-label="`Документы клиента: ${displayName(row)}`" title="Документы" @click.stop="router.visit(`/clients/documents?client_id=${row.id}`)"><img src="/design/crm/documents.svg" alt="" /></button>
                                        <button :aria-label="`Доступы клиента: ${displayName(row)}`" title="Доступы" @click.stop="router.visit(`/clients/accounts?client_id=${row.id}`)"><img src="/design/crm/administration.svg" alt="" /></button>
                                    </template>
                                    <button
                                        v-if="isAcceptance"
                                        :aria-label="`Провести приемку: ${displayName(row)}`"
                                        title="Провести приемку"
                                        :disabled="!online || saving || !!row.deleted_at || Number(row.status) === 1"
                                        @click.stop="openAcceptance(row)"
                                    ><span class="acceptance-play-icon" aria-hidden="true">▶</span></button>
                                    <DocumentDownload
                                        v-if="isDocument"
                                        :id="row.id"
                                        :name="displayName(row)"
                                        :disabled="!online || !!row.deleted_at"
                                    />
                                    <button
                                        :aria-label="`Просмотр: ${displayName(row)}`"
                                        title="Просмотр"
                                        @click="open(row, true)"
                                    >
                                        <img
                                            src="/design/crm/view.svg"
                                            alt=""
                                        /></button
                                    ><button
                                        v-if="props.entity === 'warehouses'"
                                        :aria-label="`Ячейки склада: ${displayName(row)}`"
                                        title="Ячейки"
                                        @click.stop="router.visit(`/fulfillment/cells?warehouse_id=${row.id}`)"
                                    >
                                        <img src="/design/crm/cells.svg" alt="" />
                                    </button><button
                                        :aria-label="`Редактировать: ${displayName(row)}`"
                                        title="Редактировать"
                                        :disabled="
                                            !online ||
                                            saving ||
                                            !!row.deleted_at
                                        "
                                        @click="open(row)"
                                    >
                                        <img
                                            src="/design/crm/edit.svg"
                                            alt=""
                                        /></button
                                    ><button
                                        :aria-label="`Удалить: ${displayName(row)}`"
                                        title="Удалить"
                                        :disabled="
                                            !online ||
                                            saving ||
                                            !!row.deleted_at
                                        "
                                        @click="deleting = row"
                                    >
                                        <img
                                            src="/design/crm/delete.svg"
                                            alt=""
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!visible.length">
                            <td
                                :colspan="
                                    isIntegration
                                        ? 5 + kizColumns.length
                                        : isDocument
                                          ? 9
                                          : isGood
                                            ? 6
                                            : 5
                                "
                                class="empty-state"
                            >
                                {{
                                    ready
                                        ? "Записи не найдены"
                                        : "Загрузка записей…"
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <footer v-if="props.entity !== 'tasks' || props.taskView !== 'kanban'" class="list-footer">
                <span
                    >Найдено: {{ filtered.length
                    }}<template v-if="isGood">
                        · Корневых веток:
                        {{ goodsForest.roots.length }}</template
                    ></span
                >
                <div>
                    <button
                        class="refresh-button"
                        :disabled="!online || syncing"
                        @click="store.sync"
                    >
                        Обновить
                    </button>
                    <button
                        :disabled="currentPage === 1"
                        @click="currentPage = 1"
                        aria-label="Первая страница"
                    >«</button>
                    <button
                        :disabled="currentPage === 1"
                        @click="currentPage--"
                        aria-label="Предыдущая страница"
                    >‹</button>
                    <template v-for="item in pageItems" :key="item">
                        <span v-if="item === '…'">…</span>
                        <button v-else :class="{ active: currentPage === item }" @click="currentPage = Number(item)">{{ item }}</button>
                    </template>
                    <button
                        :disabled="currentPage === pages"
                        @click="currentPage++"
                        aria-label="Следующая страница"
                    >›</button>
                    <button
                        :disabled="currentPage === pages"
                        @click="currentPage = pages"
                        aria-label="Последняя страница"
                    >»</button>
                </div>
            </footer>
        </section>
        <ConfirmDelete
            v-if="deleting"
            :message="`Удалить запись ${displayName(deleting)}?`"
            :disabled="!online || saving"
            @cancel="deleting = null"
            @confirm="confirmDelete"
        />
        <aside v-if="conductingAcceptance" class="editor acceptance-editor" aria-label="Провести приемку">
            <header>
                <div>
                    <small>ПРОВЕДЕНИЕ ПРИЕМКИ</small>
                    <h2>Провести приемку N {{ conductingAcceptance.id }}</h2>
                </div>
                <button type="button" aria-label="Закрыть проведение приемки" :disabled="acceptanceSaving" @click="closeAcceptance">×</button>
            </header>
            <div class="editor-content acceptance-content">
                <div class="acceptance-progress">
                    <div class="acceptance-progress-label"><span>Прогресс</span><b>{{ conductingAcceptance.fact_count ?? 0 }} из {{ conductingAcceptance.plan_count ?? 0 }}</b></div>
                    <div class="acceptance-progress-track"><i :style="{ width: `${Math.min(100, Number(conductingAcceptance.plan_count) > 0 ? Number(conductingAcceptance.fact_count ?? 0) / Number(conductingAcceptance.plan_count) * 100 : 0)}%` }"></i></div>
                </div>
                <p v-if="acceptanceNotice" class="notice" role="status">{{ acceptanceNotice }}</p>
                <form class="acceptance-pick-form" @submit.prevent="pickAcceptance">
                    <label>Штрихкод товара<input ref="acceptanceInput" v-model="acceptanceBarcode" inputmode="numeric" autocomplete="off" autofocus :disabled="acceptanceSaving" placeholder="Введите или отсканируйте ШК" /></label>
                    <button type="submit" class="primary" :disabled="acceptanceSaving || !acceptanceBarcode.trim()">{{ acceptanceSaving ? "Сохраняем…" : "Сохранить" }}</button>
                </form>
            </div>
        </aside>
        <aside v-if="editing" class="editor" aria-label="Карточка записи">
            <header>
                <div>
                    <small>{{
                        viewing
                            ? "ПРОСМОТР"
                            : selected
                              ? "РЕДАКТИРОВАНИЕ"
                              : "НОВАЯ ЗАПИСЬ"
                    }}</small>
                    <h2>
                        {{
                            selected ? displayName(selected) : "Добавить запись"
                        }}
                    </h2>
                </div>
                <button
                    aria-label="Закрыть карточку"
                    :disabled="saving"
                    @click="editing = false"
                >
                    ×
                </button>
            </header>
            <div class="editor-content">
                <p v-if="notice" class="notice" role="status">{{ notice }}</p>
                <p v-if="detailLoading" class="notice" role="status">
                    Загружаем карточку…
                </p>
                <button v-if="conflict" @click="open(conflict, viewing)">
                    Загрузить актуальные данные
                </button>
                <form @submit.prevent="save()">
                    <input
                        v-if="isTask"
                        v-model="form.created_by_user_id"
                        type="hidden"
                        name="created_by_user_id"
                    />
                    <fieldset
                        class="client-form"
                        :disabled="
                            viewing ||
                            saving ||
                            !online ||
                            !!conflict ||
                            (isIntegration && !detailReady)
                        "
                    >
                        <label v-if="!isKiz && !isCellGood && !isAcceptance"
                            >{{ isIndividual ? "ФИО *" : "Название *"
                            }}<input
                                v-model="form.name"
                                required
                                maxlength="255"
                        /></label>
                        <template
                            v-for="field in definition.fields"
                            :key="field.key"
                        >
                            <StringListInput
                                v-if="field.kind === 'string_list'"
                                v-model="form[field.key]"
                                :label="field.label"
                            />
                            <SearchableSelect
                                v-else-if="
                                    (isGood && field.key === 'parent_id') ||
                                    (isCellGood && field.key === 'good_id') ||
                                    (isKiz &&
                                        ['client_id', 'good_id'].includes(
                                            field.key,
                                        ))
                                "
                                v-model="form[field.key]"
                                :label="field.label"
                                :disabled="
                                    viewing || saving || !online || !!conflict
                                "
                                :options="
                                    choices(field.lookup!)
                                        .filter(
                                            (row) =>
                                                !isGood ||
                                                row.id !== selected?.id,
                                        )
                                        .map((row) => ({
                                            id: row.id,
                                            label: displayName(row),
                                            search: [
                                                row.id,
                                                row.code,
                                                row.shortname,
                                            ]
                                                .filter(Boolean)
                                                .join(' '),
                                        }))
                                "
                            />
                            <label v-else :key="field.key"
                                >{{ field.label }}
                                <textarea
                                    v-if="field.kind === 'json'"
                                    v-model="form[field.key]"
                                    :aria-label="field.label"
                                    rows="6"
                                    spellcheck="false"
                                />
                                <textarea
                                    v-else-if="field.kind === 'textarea'"
                                    v-model="form[field.key]"
                                    :aria-label="field.label"
                                    maxlength="10000"
                                    rows="4"
                                />
                                <select
                                    v-else-if="field.kind === 'lookup_list'"
                                    v-model="form[field.key]"
                                    multiple
                                >
                                    <option
                                        v-for="id in form[field.key].filter(
                                            (id: string) =>
                                                !choices(field.lookup!).some(
                                                    (row) =>
                                                        String(row.id) ===
                                                        String(id),
                                                ),
                                        )"
                                        :key="id"
                                        :value="id"
                                    >
                                        №{{ id }} · недоступно
                                    </option>
                                    <option
                                        v-for="row in choices(field.lookup!)"
                                        :key="row.id"
                                        :value="String(row.id)"
                                    >
                                        {{
                                            row.name ||
                                            row.shortname ||
                                            `№${row.id}`
                                        }}
                                    </option>
                                </select>
                                <select
                                    v-else-if="field.kind === 'lookup'"
                                    :required="field.required"
                                    @change="changeLookup(field.key)"
                                    :disabled="
                                        field.key === 'client_id' &&
                                        !!clientScope
                                    "
                                    v-model="form[field.key]"
                                    :aria-label="field.label"
                                >
                                    <option v-if="!field.required" :value="null">Не выбрано</option>
                                    <option
                                        v-if="
                                            form[field.key] &&
                                            !choices(field.lookup!).some(
                                                (row) =>
                                                    String(row.id) ===
                                                    String(form[field.key]),
                                            )
                                        "
                                        :value="form[field.key]"
                                        disabled
                                    >
                                        Недоступная запись №{{
                                            form[field.key]
                                        }}
                                    </option>
                                    <option
                                        v-for="row in choices(field.lookup!)"
                                        :key="row.id"
                                        :value="row.id"
                                    >
                                        {{ displayName(row) }}
                                    </option>
                                </select>
                                <select
                                    v-else-if="field.kind === 'flag12'"
                                    v-model="form[field.key]"
                                    :aria-label="field.label"
                                >
                                    <option :value="null">Не указан</option>
                                    <option :value="1">Да</option>
                                    <option :value="2">Нет</option>
                                </select>
                                <select
                                    v-else-if="field.kind === 'flag'"
                                    v-model="form[field.key]"
                                    :aria-label="field.label"
                                >
                                    <option :value="null">Не указан</option>
                                    <option :value="1">Да</option>
                                    <option :value="0">Нет</option>
                                </select>
                                <RussianDateInput
                                    v-else-if="field.kind === 'datetime'"
                                    v-model="form[field.key]"
                                    :aria-label="field.label"
                                    :with-time="true"
                                />
                                <RussianDateInput
                                    v-else-if="field.kind === 'date'"
                                    v-model="form[field.key]"
                                    :aria-label="field.label"
                                />
                                <input
                                    v-else-if="field.kind === 'money'"
                                    v-model="form[field.key]"
                                    :aria-label="field.label"
                                    inputmode="decimal"
                                    placeholder="0,00"
                                    pattern="[0-9]{1,16}([.,][0-9]{1,2})?"
                                />
                                <input
                                    v-else-if="field.kind === 'number'"
                                    v-model="form[field.key]"
                                    :aria-label="field.label"
                                    type="number"
                                    :required="field.required"
                                    min="0"
                                    max="2147483647"
                                />
                                <input
                                    v-else
                                    v-model="form[field.key]"
                                    :aria-label="field.label"
                                    maxlength="255"
                                />
                            </label>
                        </template>
                        <label
                            v-if="isGood && !selected?.has_children"
                            class="category-switch"
                        >
                            <span>Является категорией</span>
                            <input
                                type="checkbox"
                                role="switch"
                                aria-label="Является категорией"
                                :checked="Number(form.is_category) === 1"
                                @change="
                                    form.is_category = (
                                        $event.target as HTMLInputElement
                                    ).checked
                                        ? 1
                                        : 2
                                "
                            />
                            <span
                                class="category-switch-track"
                                aria-hidden="true"
                            ></span>
                        </label>
                        <p v-if="isGood" class="goods-derived">
                            Уровень:
                            {{
                                selected?.level ??
                                "рассчитывается при сохранении"
                            }}
                            ·
                            {{
                                Number(form.is_category) === 1
                                    ? "Категория"
                                    : "Товар"
                            }}
                        </p>
                        <DocumentPrintFields
                            v-if="isDocument"
                            v-model="form.src"
                            :fields="
                                lookupStores.client_doc_types?.rows.value.find(
                                    (row) =>
                                        String(row.id) ===
                                        String(form.doc_type_id),
                                )?.settings?.print?.fields ?? []
                            "
                        />
                                <label v-if="!isKiz && !isCellGood && !isAcceptance"
                            >Статус<select
                                v-model="form.status"
                                aria-label="Статус"
                            >
                                <option
                                    v-if="
                                        form.status !== null &&
                                        !Object.keys(statusLabels).includes(
                                            String(form.status),
                                        )
                                    "
                                    :value="form.status"
                                    disabled
                                >
                                    Выберите статус
                                </option>
                                <option
                                    v-if="
                                        props.entity !== 'files' &&
                                        !isDocument &&
                                        !isDocType
                                    "
                                    :value="null"
                                >
                                    Не указан
                                </option>
                                <option
                                    v-for="[value, label] in Object.entries(
                                        statusLabels,
                                    ).filter(
                                        ([key]) => !isKiz && key !== 'null',
                                    )"
                                    :key="value"
                                    :value="Number(value)"
                                >
                                    {{ label }}
                                </option>
                            </select></label
                        ><button v-if="!viewing" type="submit" class="primary">
                            {{
                                saving
                                    ? "Сохраняем…"
                                    : selected
                                      ? "Сохранить изменения"
                                      : "Создать запись"
                            }}
                        </button>
                    </fieldset>
                </form>
            </div>
        </aside>
    </div>
</template>
<style scoped>
.client-form .category-switch {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    cursor: pointer;
}
.category-switch input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
}
.category-switch-track {
    position: relative;
    width: 38px;
    height: 22px;
    flex: 0 0 38px;
    border-radius: 12px;
    background: #b5bec5;
    transition: background 0.15s;
}
.category-switch-track::after {
    content: "";
    position: absolute;
    left: 3px;
    top: 3px;
    width: 16px;
    height: 16px;
    background: white;
    border-radius: 50%;
    transition: transform 0.15s;
}
.category-switch input:checked + .category-switch-track {
    background: #208b35;
}
.category-switch input:checked + .category-switch-track::after {
    transform: translateX(16px);
}
.category-switch input:focus-visible + .category-switch-track {
    outline: 2px solid #2274a5;
    outline-offset: 3px;
}
.category-switch input:disabled + .category-switch-track {
    opacity: 0.5;
}

.goods-tree-controls {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 0 0 12px;
}
.goods-tree-controls button {
    color: #2274a5;
    background: transparent;
    border: 0;
    padding: 4px;
    cursor: pointer;
}
.goods-tree-controls button:disabled {
    opacity: 0.5;
    cursor: default;
}
.goods-tree-controls span,
.goods-derived {
    font-size: 12px;
    color: #667085;
}
.goods-tree-name {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 180px;
}
.goods-tree-toggle,
.goods-tree-spacer {
    flex: 0 0 20px;
    width: 20px;
}
.goods-tree-toggle {
    border: 0;
    background: transparent;
    padding: 0;
    color: #2274a5;
    font-size: 18px;
    cursor: pointer;
}
.goods-category-row {
    background: #f3f8f4;
}
.goods-category-row .name-button {
    font-weight: 700;
}
.goods-category-icon,
.goods-item-icon {
    flex: 0 0 20px;
    width: 20px;
    height: 20px;
}
.goods-category-icon {
    color: #d8a32c;
}

.client-form {
    display: grid;
    gap: 18px;
}
.client-form label {
    display: grid;
    gap: 6px;
    margin: 0;
}
.client-form input,
.client-form select,
.client-form textarea,
.filter-row input,
.filter-row select,
.filter-row textarea {
    border: 1px solid #a8d4a9;
}
.list-footer {
    flex-wrap: wrap;
}
.list-footer .refresh-button {
    width: auto;
    font-size: 12px;
    padding: 0 6px;
}
.table-scroll {
    position: relative;
    flex: 1 1 auto;
    min-height: 0;
}
.task-view-toggle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    align-self: center;
    margin-left: 12px;
    padding: 7px 12px;
    border: 1px solid #2274a5;
    border-radius: 6px;
    background: #fff;
    color: #2274a5;
    cursor: pointer;
}
.task-view-toggle img { width: 18px; height: 18px; object-fit: contain; }
.heading-title-group {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.heading-title-group h1 {
    margin: 0;
}
.check-column {
    width: 34px;
    min-width: 34px;
    text-align: center;
}
.check-column input {
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: #2274a5;
    cursor: pointer;
}
.column-settings-panel {
    position: absolute;
    z-index: 9999;
    top: 42px;
    right: 8px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, max-content));
    gap: 8px;
    width: max-content;
    min-width: 220px;
    max-width: min(720px, calc(100vw - 32px));
    max-height: min(70vh, 520px);
    overflow-y: auto;
    padding: 10px;
    border: 1px solid #d7e5db;
    border-radius: 8px;
    background: #fff;
    color: #344054;
    box-shadow: 0 10px 24px rgb(16 24 40 / 14%);
}
.column-settings-title {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 700;
}
.column-settings-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border: 0;
    border-radius: 4px;
    background: transparent;
    color: #667085;
    font-size: 20px;
    line-height: 1;
    cursor: pointer;
}
.column-settings-close:hover {
    background: #f2f4f7;
    color: #344054;
}
.column-settings-item {
    min-width: 0;
    border: 1px solid transparent;
    border-radius: 5px;
    cursor: grab;
}
.column-settings-item:hover {
    border-color: #d7e5db;
    background: #f8fafc;
}
.column-settings-control {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 6px;
    width: 100%;
    min-height: 30px;
    padding: 4px 6px;
    margin: 0;
    cursor: grab;
}
.column-settings-control input {
    flex: 0 0 auto;
    width: 16px;
    height: 16px;
    margin: 0;
    padding: 0;
}
.column-drag-handle {
    flex: 0 0 auto;
    color: #98a2b3;
    font-size: 15px;
    cursor: grab;
}
.column-settings-label {
    display: block;
    min-width: 0;
    flex: 1 1 auto;
    color: #344054;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.column-settings-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    margin-left: 6px;
    border: 0;
    border-radius: 4px;
    padding: 2px 4px;
    background: transparent;
    color: #667085;
    font-size: 19px;
    line-height: 1;
    cursor: pointer;
}
.column-settings-button:hover {
    background: #eef7f0;
    color: #2274a5;
}
.name-button {
    text-align: left;
}
td,
.editor h2 {
    overflow-wrap: anywhere;
}
.acceptance-play-icon {
    display: inline-grid;
    place-items: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #e1f3e7;
    color: #1e892f;
    font-size: 10px;
    line-height: 1;
}
.table-progress { display: flex; align-items: center; gap: 8px; min-width: 130px; }
.table-progress-track { display: block; width: 88px; height: 7px; border-radius: 5px; background: #e1f3e7; overflow: hidden; }
.table-progress-track i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #1e892f, #65c98a); transition: width .2s ease; }
.table-progress b { color: #1e892f; font-size: 11px; font-weight: 700; }
.lookup-avatar-cell { display: inline-flex; align-items: center; gap: 8px; min-width: 145px; }
.lookup-avatar { display: inline-grid; place-items: center; flex: 0 0 28px; width: 28px; height: 28px; overflow: hidden; border-radius: 7px; background: transparent; color: #1e892f; font-size: 12px; font-weight: 700; }
.lookup-avatar img { width: 100%; height: 100%; object-fit: cover; }
.acceptance-content { display: grid; gap: 18px; }
.acceptance-progress { display: grid; gap: 8px; }
.acceptance-progress-label { display: flex; justify-content: space-between; color: #667085; font-size: 12px; }
.acceptance-progress-label b { color: #1e892f; font-weight: 700; }
.acceptance-progress-track { height: 9px; border-radius: 6px; background: #e1f3e7; overflow: hidden; }
.acceptance-progress-track i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #1e892f, #65c98a); transition: width .2s ease; }
.acceptance-pick-form { display: grid; gap: 14px; }
.acceptance-pick-form label { display: grid; gap: 7px; }
</style>
