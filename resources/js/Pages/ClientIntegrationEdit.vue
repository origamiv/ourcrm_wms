<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import ClientTabs from "../Components/ClientTabs.vue";
import SearchableSelect from "../Components/SearchableSelect.vue";
import { createEntitySync } from "../lib/entitySync";
import { http, HttpError } from "../lib/http";
import { formatDate } from "../lib/dates";
import type { EntityRow } from "../lib/cache";

type RuleType =
    "catalog_sync" | "stock_export" | "orders_import" | "shipment" | "other";
type Mapping = { crm: string; marketplace: string };
type RuleNode = {
    id: string;
    type: RuleType;
    settings: {
        schedule_hours?: number;
        marketplace_id?: string | number | null;
        rule_id?: string | number | null;
        json?: string;
        export_stocks?: boolean;
        stock_formula?: "real-fbs-fbo" | "real-fbs" | "fixed";
        stock_fixed?: number | null;
        trigger_mode?: "events" | "schedule";
        trigger_hours?: number;
        sync_prices?: boolean;
        discount?: boolean;
        category_mappings?: Mapping[];
    };
};
type LookupRow = EntityRow & {
    name?: string | null;
    shortname?: string | null;
    client_id?: number | null;
    status?: number | null;
};
type WebhookRow = EntityRow & {
    name: string | null;
    shortname?: string | null;
    code?: string | null;
    status: number | null;
    client_id?: number | null;
    service_id?: number | null;
    type_hook_id?: number | null;
    rules_id?: number[] | null;
    cnt?: number | null;
    dat_last_run?: string | null;
    deleted_at?: string | null;
};

const page = usePage<any>();
const scope = `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`;
const id = String(page.url.split("?")[0].split("/").at(-2) ?? "");
const clientScope = computed<{ id: string; name: string } | null>(
    () => page.props.clientScope ?? null,
);
const listUrl = computed(
    () =>
        `/clients/integrations${clientScope.value ? `?client_id=${encodeURIComponent(clientScope.value.id)}` : ""}`,
);
const services = createEntitySync<LookupRow>(scope, "integration_services");
const hookTypes = createEntitySync<LookupRow>(scope, "integration_type_hook");
const clients = createEntitySync<LookupRow>(scope, "clients");
const rules = createEntitySync<LookupRow>(scope, "integration_rules");
const marketplaces = createEntitySync<LookupRow>(scope, "marketplaces");
const stores = [services, hookTypes, clients, rules, marketplaces];
const accountRows = ref<LookupRow[]>([]);
const loading = ref(true);
const saving = ref(false);
const error = ref("");
const notice = ref("");
const conflict = ref(false);
const version = ref("");
const form = ref({
    name: "",
    shortname: "",
    status: 1,
    client_id: null as string | null,
    service_id: null as string | null,
    type_hook_id: null as string | null,
    url: "",
    account_id: null as string | null,
    cnt: null as number | null,
    dat_last_run: "",
    rules_id: [] as number[],
    extra_params: "{}",
});
const nodes = ref<RuleNode[]>([]);
const selectedId = ref<string | null>(null);
const mobileInspectorOpen = ref(false);
const advanced = ref(false);
const baseline = ref("");
const dragged = ref<string | null>(null);

const ruleTypes: { type: RuleType; title: string; description: string }[] = [
    {
        type: "catalog_sync",
        title: "Товары МП",
        description: "Импорт карточек товаров",
    },
    {
        type: "stock_export",
        title: "Отправка остатков",
        description: "Обновление складов по API",
    },
    {
        type: "orders_import",
        title: "Выгрузка заказов",
        description: "Получение новых отправлений",
    },
    {
        type: "shipment",
        title: "Отгрузка",
        description: "Статусы логистики и курьеров",
    },
    {
        type: "other",
        title: "Прочее",
        description: "Правило и настройки JSON",
    },
];
const selected = computed(
    () => nodes.value.find((node) => node.id === selectedId.value) ?? null,
);
const selectedType = computed(() =>
    ruleTypes.find((item) => item.type === selected.value?.type),
);
const service = computed(() =>
    services.rows.value.find((row) => String(row.id) === form.value.service_id),
);
const marketplace = computed(() => {
    const name = String(
        service.value?.shortname || service.value?.name || "",
    ).toLowerCase();
    if (name.includes("ozon")) return "Ozon";
    if (name.includes("yandex") && name.includes("market"))
        return "Яндекс Маркет";
    if (name.includes("wildberries") || /(^|_)wb($|_)/.test(name))
        return "Wildberries";
    return null;
});
const marketplaceOptions = computed(() =>
    marketplaces.rows.value
        .filter((row) => row.status === 1)
        .map((row) => {
            const code = String(
                row.shortname || row.name || "МП",
            ).toLowerCase();
            const brand =
                code === "wb"
                    ? { badge: "WB", badgeColor: "#8b2bb7" }
                    : code === "oz"
                      ? { badge: "OZ", badgeColor: "#005bff" }
                      : code === "ym"
                        ? { badge: "ЯМ", badgeColor: "#e51c23" }
                        : {
                              badge: code.slice(0, 2).toUpperCase(),
                              badgeColor: "#64748b",
                          };
            return {
                id: String(row.id),
                label: `#${row.id} ${row.name || row.shortname || "Маркетплейс"}`,
                ...brand,
            };
        }),
);
const defaultMarketplaceId = computed(() => {
    const code =
        marketplace.value === "Wildberries"
            ? "wb"
            : marketplace.value === "Ozon"
              ? "oz"
              : marketplace.value === "Яндекс Маркет"
                ? "ym"
                : "";
    return (
        marketplaces.rows.value.find((row) => row.shortname === code)?.id ??
        null
    );
});
const availableRuleTypes = computed(() =>
    ruleTypes.filter((rule) => marketplace.value || rule.type === "other"),
);
function nodeDescription(node: RuleNode): string {
    if (node.type === "catalog_sync") {
        return (
            marketplaces.rows.value.find(
                (row) =>
                    String(row.id) === String(node.settings.marketplace_id),
            )?.name ||
            marketplace.value ||
            "Маркетплейс"
        );
    }
    if (node.type === "other") {
        return (
            rules.rows.value.find(
                (row) => String(row.id) === String(node.settings.rule_id),
            )?.name || "Правило не выбрано"
        );
    }
    return marketplace.value || "Интеграция";
}
function marketplaceBadge(node: RuleNode): { text: string; color: string } {
    const row = marketplaces.rows.value.find(
        (item) => String(item.id) === String(node.settings.marketplace_id),
    );
    const code = String(row?.shortname || "").toLowerCase();
    if (code === "wb") return { text: "WB", color: "#8b2bb7" };
    if (code === "oz") return { text: "OZ", color: "#005bff" };
    if (code === "ym") return { text: "ЯМ", color: "#e51c23" };
    return {
        text: String(row?.shortname || "МП")
            .slice(0, 2)
            .toUpperCase(),
        color: "#64748b",
    };
}
const lookupOptions = (rows: LookupRow[]) =>
    rows
        .filter((row) => row.status !== 2)
        .map((row) => ({
            id: String(row.id),
            label: row.name || row.shortname || `№${row.id}`,
        }));
const accountOptions = computed(() =>
    lookupOptions(
        accountRows.value.filter(
            (row) =>
                !form.value.client_id ||
                String(row.client_id) === form.value.client_id,
        ),
    ).map((row) => ({
        ...row,
        label:
            row.label === `№${row.id}`
                ? `#${row.id}`
                : `#${row.id} ${row.label}`,
    })),
);
const snapshot = computed(() =>
    JSON.stringify({ form: form.value, nodes: nodes.value }),
);
const dirty = computed(
    () =>
        !loading.value &&
        baseline.value !== "" &&
        snapshot.value !== baseline.value,
);
const canSave = computed(
    () =>
        !loading.value && !saving.value && navigator.onLine && !conflict.value,
);

function parseNodes(value: unknown): RuleNode[] {
    if (
        !value ||
        typeof value !== "object" ||
        !Array.isArray((value as any).nodes)
    )
        return [];
    const allowed = new Set(ruleTypes.map((item) => item.type));
    return (value as any).nodes
        .filter((node: any) => node && allowed.has(node.type))
        .map((node: any) => ({
            id: String(node.id),
            type: node.type as RuleType,
            settings: {
                ...(node.type === "catalog_sync" ? { schedule_hours: 4 } : {}),
                ...(node.settings && typeof node.settings === "object"
                    ? structuredClone(node.settings)
                    : {}),
            },
        }));
}
function loadForm(
    data: WebhookRow,
    details: { url?: string | null; params?: Record<string, unknown> | null },
) {
    version.value = data.version;
    const params =
        details.params && typeof details.params === "object"
            ? structuredClone(details.params)
            : {};
    const builder = params.builder;
    const accountId = params.account_id;
    delete params.builder;
    delete params.account_id;
    form.value = {
        name: data.name ?? "",
        shortname: data.shortname ?? "",
        status: data.status ?? 1,
        client_id: data.client_id == null ? null : String(data.client_id),
        service_id: data.service_id == null ? null : String(data.service_id),
        type_hook_id:
            data.type_hook_id == null ? null : String(data.type_hook_id),
        url: details.url ?? "",
        account_id: accountId == null ? null : String(accountId),
        cnt: data.cnt ?? null,
        dat_last_run: data.dat_last_run ?? "",
        rules_id: Array.isArray(data.rules_id) ? data.rules_id.map(Number) : [],
        extra_params: JSON.stringify(params, null, 2),
    };
    nodes.value = parseNodes(builder);
    for (const node of nodes.value) {
        if (node.type === "catalog_sync" && !node.settings.marketplace_id)
            node.settings.marketplace_id = defaultMarketplaceId.value;
    }
    // До появления схемы у маркетплейсного вебхука действует прежняя синхронизация каталога.
    if (!builder && marketplace.value)
        nodes.value = [
            {
                id: "catalog_sync",
                type: "catalog_sync",
                settings: {
                    schedule_hours: 4,
                    marketplace_id: defaultMarketplaceId.value,
                },
            },
        ];
    selectedId.value = nodes.value[0]?.id ?? "access";
    baseline.value = snapshot.value;
}
async function load() {
    loading.value = true;
    error.value = "";
    if (!navigator.onLine) {
        error.value = "Полная карточка доступна только при подключении к сети.";
        loading.value = false;
        return;
    }
    try {
        const response = await http(
            `/web/integration/webhooks/${encodeURIComponent(id)}`,
        );
        loadForm(response.data, response.details);
        conflict.value = false;
    } catch (e) {
        error.value =
            e instanceof Error ? e.message : "Не удалось загрузить интеграцию.";
    } finally {
        loading.value = false;
    }
}
function addNode(type: RuleType) {
    if (
        (type !== "other" && !marketplace.value) ||
        nodes.value.some((node) => node.type === type)
    )
        return;
    nodes.value.push({
        id: type,
        type,
        settings:
            type === "catalog_sync"
                ? {
                      schedule_hours: 4,
                      marketplace_id: defaultMarketplaceId.value,
                      sync_prices: false,
                      discount: false,
                      category_mappings: [],
                  }
                : type === "stock_export"
                  ? {
                        export_stocks: false,
                        stock_formula: "real-fbs-fbo",
                        stock_fixed: null,
                        trigger_mode: "events",
                        trigger_hours: 4,
                    }
                  : type === "other"
                    ? { rule_id: null, json: "{}" }
                    : {},
    });
    selectedId.value = type;
}
function selectNode(id: string) {
    selectedId.value = id;
    if (window.matchMedia("(max-width: 600px)").matches)
        mobileInspectorOpen.value = true;
}
function removeNode(node: RuleNode) {
    nodes.value = nodes.value.filter((item) => item.id !== node.id);
    if (selectedId.value === node.id)
        selectedId.value = nodes.value[0]?.id ?? "access";
}
function moveNode(index: number, direction: number) {
    const target = index + direction;
    if (target < 0 || target >= nodes.value.length) return;
    const list = [...nodes.value];
    [list[index], list[target]] = [list[target], list[index]];
    nodes.value = list;
}
function dragStart(value: string, event: DragEvent) {
    dragged.value = value;
    event.dataTransfer?.setData("text/plain", value);
    if (event.dataTransfer) event.dataTransfer.effectAllowed = "move";
}
function pointerDrop(index: number) {
    if (dragged.value) dropAt(index);
}
function dropAt(index: number, event?: DragEvent) {
    const value = dragged.value || event?.dataTransfer?.getData("text/plain");
    dragged.value = null;
    if (!value) return;
    if (value.startsWith("new:")) {
        const type = value.slice(4) as RuleType;
        if (
            (type !== "other" && !marketplace.value) ||
            nodes.value.some((node) => node.type === type)
        )
            return;
        addNode(type);
        const list = [...nodes.value];
        const [node] = list.splice(list.length - 1, 1);
        list.splice(Math.min(index, list.length), 0, node);
        nodes.value = list;
        return;
    }
    const from = nodes.value.findIndex((node) => node.id === value);
    if (from < 0) return;
    const list = [...nodes.value];
    const [node] = list.splice(from, 1);
    list.splice(Math.min(index, list.length), 0, node);
    nodes.value = list;
}
function addMapping() {
    if (!selected.value) return;
    (selected.value.settings.category_mappings ??= []).push({
        crm: "",
        marketplace: "",
    });
}
function setStockFixed(event: Event) {
    if (!selected.value || selected.value.type !== "stock_export") return;
    const value = (event.target as HTMLInputElement).value;
    selected.value.settings.stock_fixed = value === "" ? null : Number(value);
}
function back() {
    router.visit(listUrl.value);
}
async function save() {
    if (!canSave.value) return;
    error.value = "";
    notice.value = "";
    let params: Record<string, unknown>;
    try {
        const parsed = JSON.parse(form.value.extra_params || "{}");
        if (!parsed || Array.isArray(parsed) || typeof parsed !== "object")
            throw new Error("Ожидается JSON-объект.");
        params = parsed;
    } catch {
        error.value =
            "Прочие параметры должны содержать корректный JSON-объект.";
        return;
    }
    if (form.value.account_id)
        params.account_id = Number(form.value.account_id);
    else delete params.account_id;
    for (const node of nodes.value) {
        if (node.type !== "other") continue;
        try {
            const value = JSON.parse(node.settings.json || "{}");
            if (!value || typeof value !== "object" || Array.isArray(value))
                throw new Error();
        } catch {
            error.value =
                "Настройки JSON блока «Прочее» должны содержать объект.";
            selectedId.value = node.id;
            return;
        }
    }
    params.builder = { version: 1, nodes: nodes.value };
    saving.value = true;
    try {
        const response = await http(
            `/web/integration/webhooks/${encodeURIComponent(id)}`,
            "PUT",
            {
                name: form.value.name,
                shortname: form.value.shortname || null,
                status: form.value.status,
                client_id: form.value.client_id
                    ? Number(form.value.client_id)
                    : null,
                service_id: form.value.service_id
                    ? Number(form.value.service_id)
                    : null,
                type_hook_id: form.value.type_hook_id
                    ? Number(form.value.type_hook_id)
                    : null,
                url: form.value.url || null,
                rules_id: form.value.rules_id,
                cnt: form.value.cnt,
                dat_last_run: form.value.dat_last_run || null,
                params,
                version: version.value,
            },
        );
        version.value = response.data.version;
        baseline.value = snapshot.value;
        notice.value = "Изменения сохранены";
        conflict.value = false;
    } catch (e) {
        if (e instanceof HttpError && e.status === 409) conflict.value = true;
        error.value =
            e instanceof HttpError && e.body.errors
                ? Object.values(e.body.errors).flat().join(" ")
                : e instanceof Error
                  ? e.message
                  : "Не удалось сохранить интеграцию.";
    } finally {
        saving.value = false;
    }
}
function beforeUnload(event: BeforeUnloadEvent) {
    if (dirty.value) event.preventDefault();
}
let unsubscribe: (() => void) | undefined;
onMounted(() => {
    void Promise.all([
        ...stores.map((store) => store.start()),
        http("/web/integration/account_options")
            .then((response) => {
                accountRows.value = response.data;
            })
            .catch(() => {
                accountRows.value = [];
            }),
    ]).then(load);
    window.addEventListener("beforeunload", beforeUnload);
    unsubscribe = router.on("before", (event) => {
        if (
            dirty.value &&
            !window.confirm("Есть несохранённые изменения. Покинуть редактор?")
        )
            event.preventDefault();
    });
});
onUnmounted(() => {
    stores.forEach((store) => store.stop());
    window.removeEventListener("beforeunload", beforeUnload);
    unsubscribe?.();
});
</script>

<template>
    <Head title="Редактирование интеграции" />
    <div class="users-workspace integration-builder-page">
        <section class="users-list">
            <div class="content-breadcrumb">
                Клиенты › Интеграции › Редактирование #{{ id }}
            </div>
            <ClientTabs />
            <p v-if="clientScope" class="client-context">
                Клиент: <strong>{{ clientScope.name }}</strong>
            </p>
            <div class="page-heading">
                <div>
                    <h1>Редактирование интеграции #{{ id }}</h1>
                    <p>{{ form.name || "Интеграция" }}</p>
                </div>
                <button type="button" class="back-button" @click="back">
                    ← К списку
                </button>
            </div>
            <div v-if="loading" class="builder-message">
                Загружаем интеграцию…
            </div>
            <div v-else-if="error && !version" class="builder-message error">
                {{ error }}
                <button type="button" @click="load">Повторить</button>
            </div>
            <template v-else>
                <div v-if="error" class="builder-notice error" role="alert">
                    {{ error }}
                    <button v-if="conflict" type="button" @click="load">
                        Загрузить актуальную версию
                    </button>
                </div>
                <div v-if="notice" class="builder-notice" role="status">
                    {{ notice }}
                </div>
                <div class="builder-area">
                    <div
                        class="builder-canvas"
                        @dragover.prevent
                        @drop.prevent="dropAt(nodes.length, $event)"
                        @pointerup="pointerDrop(nodes.length)"
                    >
                        <p class="canvas-hint">
                            Перетащите правила из правой панели и свяжите их
                        </p>
                        <div
                            class="node-chain"
                            @dragover.prevent.stop
                            @drop.prevent.stop="dropAt(nodes.length, $event)"
                            @pointerup.stop="pointerDrop(nodes.length)"
                        >
                            <div
                                class="rule-node start-node"
                                :class="{ selected: selectedId === 'access' }"
                                @click="selectNode('access')"
                            >
                                <div class="node-title">
                                    <img
                                        src="/design/integration_builder/lock.svg"
                                        alt=""
                                    />Доступы (Start)
                                </div>
                                <span>Учетные данные CRM</span
                                ><small>Подключено</small>
                            </div>
                            <template
                                v-for="(node, index) in nodes"
                                :key="node.id"
                            >
                                <div class="node-connector" aria-hidden="true">
                                    <img
                                        src="/design/integration_builder/connector.svg"
                                        alt=""
                                    />
                                </div>
                                <div
                                    class="rule-node"
                                    :class="{
                                        selected: selectedId === node.id,
                                    }"
                                    draggable="true"
                                    @dragstart="dragStart(node.id, $event)"
                                    @dragover.prevent.stop
                                    @drop.prevent.stop="dropAt(index, $event)"
                                    @pointerup.stop="pointerDrop(index)"
                                    @click="selectNode(node.id)"
                                >
                                    <div class="node-title">
                                        <span
                                            v-if="node.type === 'catalog_sync'"
                                            class="marketplace-avatar"
                                            :style="{
                                                backgroundColor:
                                                    marketplaceBadge(node)
                                                        .color,
                                            }"
                                            aria-hidden="true"
                                            >{{
                                                marketplaceBadge(node).text
                                            }}</span
                                        >
                                        <img
                                            v-else
                                            :src="`/design/integration_builder/${node.type === 'other' ? 'settings' : node.type}.svg`"
                                            alt=""
                                        />{{
                                            ruleTypes.find(
                                                (item) =>
                                                    item.type === node.type,
                                            )?.title
                                        }}
                                    </div>
                                    <span>{{ nodeDescription(node) }}</span
                                    ><small v-if="selectedId === node.id"
                                        >Выделено</small
                                    >
                                    <div class="node-controls">
                                        <button
                                            type="button"
                                            :disabled="index === 0"
                                            :aria-label="`Поднять ${node.type}`"
                                            @click.stop="moveNode(index, -1)"
                                        >
                                            ↑
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="
                                                index === nodes.length - 1
                                            "
                                            :aria-label="`Опустить ${node.type}`"
                                            @click.stop="moveNode(index, 1)"
                                        >
                                            ↓
                                        </button>
                                        <button
                                            type="button"
                                            :aria-label="`Удалить ${node.type}`"
                                            @click.stop="removeNode(node)"
                                        >
                                            ×
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <p v-if="!nodes.length" class="empty-chain">
                                Добавьте правило из панели справа
                            </p>
                        </div>
                    </div>
                    <aside
                        class="builder-inspector"
                        :class="{ 'mobile-open': mobileInspectorOpen }"
                    >
                        <div class="inspector-head">
                            <button
                                type="button"
                                class="mobile-inspector-back"
                                aria-label="Вернуться к схеме интеграции"
                                @click="mobileInspectorOpen = false"
                            >←</button>
                            <h2>Конфигурация</h2>
                            <button
                                type="button"
                                title="Свойства интеграции"
                                aria-label="Свойства интеграции"
                                @click="advanced = !advanced"
                            >
                                <img
                                    src="/design/integration_builder/settings.svg"
                                    alt=""
                                />
                            </button>
                        </div>
                        <div class="palette">
                            <h3>Доступные правила</h3>
                            <p>Выберите правило для редактирования</p>
                            <div class="palette-grid">
                                <button
                                    v-for="rule in availableRuleTypes"
                                    :key="rule.type"
                                    type="button"
                                    class="palette-rule"
                                    :disabled="
                                        nodes.some(
                                            (node) => node.type === rule.type,
                                        )
                                    "
                                    draggable="true"
                                    @pointerdown="dragged = `new:${rule.type}`"
                                    @pointerup="dragged = null"
                                    @dragstart="
                                        dragStart(`new:${rule.type}`, $event)
                                    "
                                    @click="addNode(rule.type)"
                                >
                                    <span
                                        ><img
                                            :src="`/design/integration_builder/${rule.type === 'other' ? 'settings' : rule.type}.svg`"
                                            alt=""
                                        />{{ rule.title }}</span
                                    ><small>{{ rule.description }}</small>
                                </button>
                            </div>
                        </div>
                        <div class="inspector-fields">
                            <template v-if="selectedId === 'access'">
                                <SearchableSelect
                                    v-model="form.account_id"
                                    :options="accountOptions"
                                    label="Доступ"
                                />
                            </template>
                            <template
                                v-else-if="
                                    selected && selected.type === 'catalog_sync'
                                "
                            >
                                <SearchableSelect
                                    :model-value="
                                        selected.settings.marketplace_id ?? null
                                    "
                                    @update:model-value="
                                        selected.settings.marketplace_id =
                                            $event
                                    "
                                    :options="marketplaceOptions"
                                    label="Маркетплейс"
                                />
                                <label class="switch-line"
                                    >Синхронизировать цены
                                    <input
                                        v-model="selected.settings.sync_prices"
                                        type="checkbox"
                                    /><span>{{
                                        selected.settings.sync_prices
                                            ? "Да"
                                            : "Нет"
                                    }}</span></label
                                >
                                <div class="mapping-editor">
                                    <div class="mapping-head">
                                        Сопоставление категорий
                                        <button
                                            type="button"
                                            @click="addMapping"
                                        >
                                            + Строка
                                        </button>
                                    </div>
                                    <div
                                        v-for="(mapping, index) in selected
                                            .settings.category_mappings ?? []"
                                        :key="index"
                                        class="mapping-row"
                                    >
                                        <input
                                            v-model="mapping.crm"
                                            aria-label="Категория CRM"
                                            placeholder="CRM категория"
                                        /><input
                                            v-model="mapping.marketplace"
                                            :aria-label="`Категория ${marketplace}`"
                                            placeholder="Категория маркетплейса"
                                        /><button
                                            type="button"
                                            :aria-label="`Удалить сопоставление ${index + 1}`"
                                            @click="
                                                selected.settings.category_mappings?.splice(
                                                    index,
                                                    1,
                                                )
                                            "
                                        >
                                            ×
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template
                                v-else-if="
                                    selected && selected.type === 'stock_export'
                                "
                            >
                                <label
                                    >Выгружать остатки
                                    <select
                                        v-model="
                                            selected.settings.export_stocks
                                        "
                                    >
                                        <option :value="false">
                                            Не выгружать
                                        </option>
                                        <option :value="true">Выгружать</option>
                                    </select>
                                </label>
                                <template
                                    v-if="selected.settings.export_stocks"
                                >
                                    <label
                                        >Формула расчёта
                                        <select
                                            v-model="
                                                selected.settings.stock_formula
                                            "
                                        >
                                            <option value="real-fbs-fbo">
                                                Реальный остаток с резервами FBS
                                                и FBO
                                            </option>
                                            <option value="real-fbs">
                                                Реальный остаток с резервами FBS
                                            </option>
                                            <option value="fixed">
                                                Фиксированный остаток
                                            </option>
                                        </select>
                                    </label>
                                    <label
                                        v-if="
                                            selected.settings.stock_formula ===
                                            'fixed'
                                        "
                                        >Выгружать фикс.
                                        <input
                                            type="number"
                                            min="0"
                                            max="2000000000"
                                            step="1"
                                            :value="
                                                selected.settings.stock_fixed ??
                                                ''
                                            "
                                            @input="setStockFixed"
                                        />
                                    </label>
                                    <label
                                        >Запуск выгрузки
                                        <select
                                            v-model="
                                                selected.settings.trigger_mode
                                            "
                                        >
                                            <option value="events">
                                                По событиям
                                            </option>
                                            <option value="schedule">
                                                По расписанию
                                            </option>
                                        </select>
                                    </label>
                                    <label
                                        v-if="
                                            selected.settings.trigger_mode ===
                                            'schedule'
                                        "
                                        >Расписание
                                        <select
                                            v-model.number="
                                                selected.settings.trigger_hours
                                            "
                                        >
                                            <option
                                                v-for="hours in [
                                                    1, 2, 4, 6, 12, 24,
                                                ]"
                                                :key="hours"
                                                :value="hours"
                                            >
                                                Каждые {{ hours }} ч
                                            </option>
                                        </select>
                                    </label>
                                </template>
                            </template>
                            <template
                                v-else-if="
                                    selected && selected.type === 'other'
                                "
                            >
                                <SearchableSelect
                                    :model-value="
                                        selected.settings.rule_id ?? null
                                    "
                                    @update:model-value="
                                        selected.settings.rule_id = $event
                                    "
                                    :options="lookupOptions(rules.rows.value)"
                                    label="Правило"
                                />
                                <label
                                    >Настройки JSON
                                    <textarea
                                        v-model="selected.settings.json"
                                        rows="8"
                                        spellcheck="false"
                                    />
                                </label>
                            </template>
                            <template v-else-if="selected"
                                ><h3>{{ selectedType?.title }}</h3>
                                <p>
                                    Настройки блока будут сохранены вместе со
                                    схемой интеграции.
                                </p></template
                            >
                            <template v-else
                                ><p>
                                    Выберите правило, чтобы настроить его
                                    свойства.
                                </p></template
                            >
                            <div v-if="advanced" class="advanced-fields">
                                <h3>Свойства интеграции</h3>
                                <label
                                    >Название *<input
                                        v-model="form.name"
                                        required
                                /></label>
                                <label
                                    >Краткое название<input
                                        v-model="form.shortname"
                                /></label>
                                <label
                                    >Клиент<SearchableSelect
                                        v-model="form.client_id"
                                        :options="
                                            lookupOptions(clients.rows.value)
                                        "
                                        label="Клиент"
                                        :disabled="!!clientScope"
                                /></label>
                                <label
                                    >Сервис<SearchableSelect
                                        v-model="form.service_id"
                                        :options="
                                            lookupOptions(services.rows.value)
                                        "
                                        label="Сервис"
                                /></label>
                                <label
                                    >Тип хука<SearchableSelect
                                        v-model="form.type_hook_id"
                                        :options="
                                            lookupOptions(hookTypes.rows.value)
                                        "
                                        label="Тип хука"
                                /></label>
                                <label
                                    >Статус<select v-model.number="form.status">
                                        <option :value="0">Новый</option>
                                        <option :value="1">Активен</option>
                                        <option :value="2">Отключен</option>
                                        <option
                                            v-if="form.status === 3"
                                            :value="3"
                                        >
                                            Выполняется
                                        </option>
                                    </select></label
                                >
                                <label
                                    >Адрес вебхука<input v-model="form.url"
                                /></label>
                                <label
                                    >Количество запусков<input
                                        v-model.number="form.cnt"
                                        type="number"
                                        min="0"
                                /></label>
                                <label
                                    >Последний запуск<span>{{
                                        form.dat_last_run
                                            ? formatDate(
                                                  form.dat_last_run,
                                                  true,
                                              )
                                            : "—"
                                    }}</span></label
                                >
                                <label
                                    >Прочие параметры (JSON)<textarea
                                        v-model="form.extra_params"
                                        rows="5"
                                    />
                                </label>
                            </div>
                        </div>
                        <div class="inspector-footer">
                            <button
                                type="button"
                                :disabled="!canSave"
                                @click="save"
                            >
                                {{ saving ? "Сохраняем…" : "Сохранить" }}
                            </button>
                        </div>
                    </aside>
                </div>
            </template>
        </section>
    </div>
</template>

<style scoped>
.integration-builder-page {
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior-y: contain;
}
.integration-builder-page .users-list {
    display: block;
    align-self: flex-start;
    min-width: 0;
    width: 100%;
}
.integration-builder-page .page-heading {
    display: flex;
    justify-content: space-between;
}
.integration-builder-page .page-heading p,
.client-context {
    font-size: 12px;
    color: #858585;
}
.back-button {
    border: 1px solid #bcdfe7;
    border-radius: 6px;
    padding: 9px 13px;
    color: #2274a5;
    background: white;
}
.builder-area {
    display: grid;
    grid-template-columns: minmax(320px, 1fr) 380px;
    min-height: 600px;
    background: #fafcfd;
    border: 1px solid #bcdfe7;
    border-radius: 8px;
    overflow: hidden;
}
.builder-canvas {
    padding: 20px;
    min-height: 600px;
    display: flex;
    flex-direction: column;
}
.canvas-hint {
    color: #858585;
    font-size: 12px;
}
.node-chain {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
    min-height: 500px;
}
.rule-node {
    width: 180px;
    padding: 16px;
    background: #fff;
    border: 2px solid #bcdfe7;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 3px 7px #0c182110;
    color: #0c1821;
}
.rule-node.selected {
    border-color: #1e892f;
    box-shadow: 0 4px 7px #1e892f33;
}
.start-node {
    border-color: #2274a5;
}
.node-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #2274a5;
    font-size: 14px;
    white-space: nowrap;
}
.selected .node-title {
    color: #1e892f;
}
.node-title img,
.palette-rule img {
    width: 20px;
    height: 20px;
    flex: none;
}
.marketplace-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
}
.rule-node > span {
    display: block;
    color: #858585;
    font-size: 12px;
    margin: 10px 0;
}
.rule-node small {
    display: inline-block;
    padding: 4px 8px;
    background: #e1f0f3;
    color: #2274a5;
    border-radius: 4px;
    font-size: 10px;
}
.rule-node.selected small {
    background: #e1f3e7;
    color: #1e892f;
}
.node-controls {
    display: flex;
    justify-content: flex-end;
    gap: 5px;
    margin-top: 8px;
}
.node-controls button {
    border: 1px solid #bcdfe7;
    border-radius: 4px;
    width: 24px;
    height: 24px;
    background: #fff;
}
.node-controls button:disabled {
    opacity: 0.4;
}
.node-connector {
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.node-connector img {
    width: 44px;
    height: 12px;
    transform: rotate(90deg);
}
.empty-chain,
.empty-palette {
    font-size: 12px;
    color: #858585;
}
.builder-inspector {
    display: flex;
    flex-direction: column;
    min-width: 0;
    padding: 16px;
    gap: 12px;
    background: #e1f0f3;
    border-left: 1px solid #bcdfe7;
}
.inspector-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #2274a5;
}
.mobile-inspector-back {
    display: none;
}
.inspector-head h2 {
    font-size: 18px;
    font-weight: 500;
}
.inspector-head img {
    width: 16px;
    height: 16px;
}
.palette {
    padding: 10px;
    background: #fff;
    border: 1px solid #bcdfe7;
    border-radius: 8px;
}
.palette h3 {
    font-size: 14px;
}
.palette p,
.inspector-fields p {
    color: #858585;
    font-size: 12px;
}
.palette-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
    margin-top: 8px;
}
.palette-rule {
    text-align: left;
    min-width: 0;
    background: white;
    border: 1px solid #bcdfe7;
    border-radius: 8px;
    padding: 8px;
    box-shadow: 0 2px 2px #0000000f;
}
.palette-rule:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.palette-rule span {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.palette-rule small {
    display: block;
    color: #858585;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.inspector-fields {
    flex: none;
    min-height: auto;
    overflow: visible;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.inspector-fields label {
    display: flex;
    flex-direction: column;
    gap: 6px;
    color: #858585;
    font-size: 12px;
}
.inspector-fields input:not([type="checkbox"]),
.inspector-fields select,
.inspector-fields textarea {
    width: 100%;
    min-height: 36px;
    padding: 9px;
    border: 1px solid #bcdfe7;
    border-radius: 6px;
    background: white;
    color: #0c1821;
    font: inherit;
}
.inspector-fields .switch-line {
    flex-direction: row;
    align-items: center;
    color: #0c1821;
}
.switch-line input {
    accent-color: #1e892f;
}
.mapping-head {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #858585;
}
.mapping-head button {
    color: #2274a5;
}
.mapping-row {
    display: grid;
    grid-template-columns: 1fr 1fr 20px;
    gap: 4px;
    margin-top: 5px;
}
.advanced-fields {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-top: 12px;
    border-top: 1px solid #bcdfe7;
}
.advanced-fields h3 {
    font-size: 14px;
}
.rules-choice {
    border: 1px solid #bcdfe7;
    padding: 8px;
    border-radius: 6px;
}
.rules-choice legend {
    font-size: 12px;
}
.rules-choice label {
    display: flex;
    flex-direction: row;
    align-items: center;
}
.inspector-footer {
    padding-top: 12px;
    border-top: 1px solid #bcdfe7;
}
.inspector-footer button {
    width: 100%;
    padding: 17px;
    background: #1e892f;
    border-radius: 6px;
    color: white;
    font-size: 14px;
}
.inspector-footer button:disabled {
    opacity: 0.45;
}
.builder-notice,
.builder-message {
    padding: 10px;
    color: #1e892f;
}
.builder-notice.error,
.builder-message.error {
    color: #a12020;
}
.builder-notice button,
.builder-message button {
    text-decoration: underline;
    margin-left: 8px;
}
@media (max-width: 980px) {
    .builder-area {
        grid-template-columns: 1fr;
    }
    .builder-inspector {
        border-left: 0;
        border-top: 1px solid #bcdfe7;
    }
    .builder-canvas {
        min-height: 450px;
    }
    .node-chain {
        min-height: 390px;
    }
}
@media (max-width: 600px) {
    .integration-builder-page {
        flex: none;
        height: auto;
        overflow: visible;
    }
    .integration-builder-page .users-list {
        height: auto;
        overflow: visible;
    }
    .builder-area {
        display: block;
        min-height: 0;
        overflow: visible;
    }
    .builder-canvas {
        min-height: 0;
        padding: 12px;
    }
    .node-chain {
        min-height: 0;
        padding: 20px 8px 28px;
    }
    .builder-inspector {
        display: none;
    }
    .builder-inspector.mobile-open {
        position: fixed;
        inset: 0;
        z-index: 80;
        display: flex;
        max-height: 100dvh;
        overflow-y: auto;
        overscroll-behavior-y: contain;
        padding: 12px;
        border: 0;
        background: #e1f0f3;
    }
    .builder-inspector.mobile-open .inspector-head {
        position: sticky;
        top: -12px;
        z-index: 2;
        margin: -12px -12px 0;
        padding: 14px 12px;
        border-bottom: 1px solid #bcdfe7;
        background: #e1f0f3;
    }
    .mobile-inspector-back {
        display: inline-grid;
        place-items: center;
        flex: 0 0 36px;
        width: 36px;
        height: 36px;
        border: 1px solid #bcdfe7;
        border-radius: 6px;
        background: #fff;
        color: #2274a5;
        font-size: 20px;
    }
    .inspector-head h2 {
        flex: 1 1 auto;
        margin-left: 8px;
    }
    .palette-grid {
        grid-template-columns: 1fr 1fr;
    }
    .integration-builder-page .page-heading {
        align-items: flex-start;
        gap: 10px;
    }
}
</style>
