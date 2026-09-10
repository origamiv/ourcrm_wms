<script setup lang="ts">
import { goodsTree, flattenGoods } from "../lib/goodsTree";
import { useCardRoute } from "../lib/cardRoute";
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import RussianDateInput from "./RussianDateInput.vue";
import { formatDate } from "../lib/dates";
import DocumentPrintFields from "./DocumentPrintFields.vue";
import DocumentDownload from "./DocumentDownload.vue";
import SearchableSelect from "./SearchableSelect.vue";
import StringListInput from "./StringListInput.vue";
import GoodsTabs from "./GoodsTabs.vue";
import AdminTabs from "./AdminTabs.vue";
import ClientTabs from "./ClientTabs.vue";
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
const props = defineProps<{ entity: keyof typeof references }>();
const definition = references[props.entity];
const isKiz = props.entity === "kizes";
const kizColumns = computed(() =>
    isKiz ? definition.fields.filter((field) => field.key !== "code") : [],
);
const isGood = props.entity === "goods";
const isGoodsSection =
    isGood ||
    ["type_goods", "unit_goods", "kind_kiz", "kizes"].includes(props.entity);
const isIndividual = props.entity === "client_individuals";
const isDocument = props.entity === "client_documents";
const isDocType = props.entity === "client_doc_types";
const isClientSection = isIndividual || isDocument || isDocType;
const basePath = isGoodsSection
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
    isIndividual ? (page.props.clientScope ?? null) : null,
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
const editing = ref(false),
    viewing = ref(false),
    saving = ref(false),
    notice = ref("");
const form = ref<Record<string, any>>({ name: "", status: 1 });
const statusLabels: Record<string, string> = isDocument
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
function changeLookup(field: string) {
    pendingDocumentDefaults.delete(field);
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
function open(row: ReferenceRow | null, readOnly = false) {
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
    };
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
    if (!online.value || saving.value || (!remove && viewing.value)) return;
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
        selected.value = response.data;
        if (isGood) form.value.is_category = response.data.is_category;
        conflict.value = null;
        notice.value = "Изменения сохранены";
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
async function confirmDelete() {
    if (!deleting.value || !online.value || saving.value) return;
    open(deleting.value, true);
    deleting.value = null;
    await save(true);
}
onMounted(async () => {
    await Promise.all([
        store.start(),
        ...Object.values(lookupStores).map((store) => store.start()),
    ]);
});
onUnmounted(() => {
    store.stop();
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
    <div class="users-workspace" :class="{ 'has-editor': editing }">
        <section class="users-list">
            <div class="content-breadcrumb">
                {{
                    isGoodsSection
                        ? "Товары"
                        : isClientSection
                          ? "Клиенты"
                          : "Администрирование › Справочники"
                }}
                › {{ definition.title }}
            </div>
            <GoodsTabs v-if="isGoodsSection" /><ClientTabs
                v-else-if="isClientSection"
            /><AdminTabs v-else />
            <p v-if="clientScope" class="notice">
                Клиент: <strong>{{ clientScope.name }}</strong>
            </p>
            <div class="page-heading">
                <h1>{{ definition.title }}</h1>
                <button
                    class="primary"
                    :disabled="!online || !ready || saving"
                    @click="open(null)"
                >
                    Добавить запись
                </button>
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
            <div class="table-scroll">
                <table
                    :role="isGood ? 'treegrid' : undefined"
                    :aria-label="isGood ? 'Дерево товаров' : undefined"
                >
                    <thead>
                        <tr>
                            <th scope="col" class="id-column">#</th>
                            <th>
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
                            <th v-if="!isKiz">
                                {{
                                    [
                                        "goods",
                                        "type_goods",
                                        "unit_goods",
                                        "kind_kiz",
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
                            <th v-if="isGood">Код</th>
                            <template v-if="isDocument"
                                ><th>Клиент</th>
                                <th>Тип документа</th>
                                <th>Дата документа</th>
                                <th>Сумма</th></template
                            >
                            <th v-for="field in kizColumns" :key="field.key">
                                {{ field.label }}
                            </th>
                            <th>{{ isKiz ? "Состояние" : "Статус" }}</th>
                            <th>Действия</th>
                        </tr>
                        <tr class="filter-row">
                            <th class="id-column"></th>
                            <th>
                                <input
                                    v-model="query"
                                    :aria-label="`Поиск: ${definition.title}`"
                                    placeholder="Поиск"
                                />
                            </th>
                            <th v-if="!isKiz">
                                <input
                                    v-model="shortQuery"
                                    :aria-label="
                                        [
                                            'goods',
                                            'type_goods',
                                            'unit_goods',
                                            'kind_kiz',
                                            'modules',
                                            'features',
                                            'client_individuals',
                                            'client_documents',
                                            'client_doc_types',
                                        ].includes(props.entity)
                                            ? 'Поиск по краткому названию'
                                            : 'Поиск по категории'
                                    "
                                />
                            </th>
                            <th v-if="isGood"></th>
                            <template v-if="isDocument">
                                <th>
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
                                <th>
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
                                <th>
                                    <RussianDateInput
                                        v-model="dateFilter"
                                        aria-label="Фильтр даты документа"
                                    />
                                </th>
                                <th></th>
                            </template>
                            <th
                                v-for="field in kizColumns"
                                :key="field.key"
                            ></th>
                            <th>
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
                            @dblclick="open(row)"
                        >
                            <td class="id-column">{{ row.id }}</td>
                            <td>
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
                                        @click="open(row, true)"
                                    >
                                        {{ displayName(row) }}
                                    </button>
                                </div>
                                <button
                                    v-else
                                    class="name-button"
                                    @click="open(row, true)"
                                >
                                    {{ displayName(row) }}
                                </button>
                            </td>
                            <td v-if="!isKiz">{{ row.shortname || "—" }}</td>
                            <td v-for="field in kizColumns" :key="field.key">
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
                                        : formatDate(row[field.key], true)
                                }}
                            </td>
                            <td v-if="isGood">{{ row.code || "—" }}</td>
                            <template v-if="isDocument">
                                <td>
                                    {{
                                        lookupStores.clients?.rows.value.find(
                                            (item) =>
                                                String(item.id) ===
                                                String(row.client_id),
                                        )?.name || `Клиент №${row.client_id}`
                                    }}
                                </td>
                                <td>
                                    {{
                                        lookupStores.client_doc_types?.rows.value.find(
                                            (item) =>
                                                String(item.id) ===
                                                String(row.doc_type_id),
                                        )?.name || `Тип №${row.doc_type_id}`
                                    }}
                                </td>
                                <td>{{ formatDate(row.doc_date) }}</td>
                                <td>
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
                            <td>
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
                            </td>
                            <td>
                                <div class="row-actions">
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
                                :colspan="isDocument ? 9 : isGood ? 6 : 5"
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
            <footer class="list-footer">
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
                        Обновить</button
                    ><button
                        :disabled="currentPage === 1"
                        @click="currentPage--"
                        aria-label="Предыдущая страница"
                    >
                        <img src="/design/crm/arrow_left.svg" alt="" /></button
                    ><span>{{ currentPage }} / {{ pages }}</span
                    ><button
                        :disabled="currentPage === pages"
                        @click="currentPage++"
                        aria-label="Следующая страница"
                    >
                        <img src="/design/crm/arrow_right.svg" alt="" />
                    </button>
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
                <button v-if="conflict" @click="open(conflict, viewing)">
                    Загрузить актуальные данные
                </button>
                <form @submit.prevent="save()">
                    <fieldset
                        class="client-form"
                        :disabled="viewing || saving || !online || !!conflict"
                    >
                        <label v-if="!isKiz"
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
                                    <option :value="null">Не выбрано</option>
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
                        <label v-if="!isKiz"
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
.filter-row input,
.filter-row select {
    border-color: #a8d4a9;
}
.list-footer {
    flex-wrap: wrap;
}
.list-footer .refresh-button {
    width: auto;
    font-size: 12px;
    padding: 0 6px;
}
.name-button {
    text-align: left;
}
td,
.editor h2 {
    overflow-wrap: anywhere;
}
</style>
