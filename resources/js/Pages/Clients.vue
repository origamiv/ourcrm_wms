<script setup lang="ts">
import { useCardRoute } from "../lib/cardRoute";
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import ClientTabs from "../Components/ClientTabs.vue";
import ConfirmDelete from "../Components/ConfirmDelete.vue";
import { createEntitySync } from "../lib/entitySync";
import { http, HttpError } from "../lib/http";
import type { EntityRow } from "../lib/cache";
import TableColumnSettings from "../Components/TableColumnSettings.vue";
import DataTransferMenu from "../Components/DataTransferMenu.vue";
import FilterPresetButton from "../Components/FilterPresetButton.vue";
import FilterPresetTiles from "../Components/FilterPresetTiles.vue";
import {
    applyTableFilter,
    useFilterPresets,
    type FilterField,
    type FilterOptions,
} from "../lib/tableFilters";
interface ClientRow extends EntityRow {
    name: string | null;
    shortname: string | null;
    status: number | null;
    deleted_at: string | null;
}
type Relation =
    "documents" | "accounts" | "integrations" | "companies" | "individuals";
type RelationFlags = Record<Relation, boolean>;
type ClientAction = Relation | "view" | "edit" | "delete";
const clientActions: { key: ClientAction; label: string; icon: string }[] = [
    { key: "documents", label: "Документы", icon: "/design/crm/documents.svg" },
    { key: "accounts", label: "Доступы", icon: "/design/crm/key.svg" },
    {
        key: "integrations",
        label: "Интеграции",
        icon: "/design/crm/integrations.svg",
    },
    {
        key: "companies",
        label: "Юрлица",
        icon: "/design/crm/client_companies.svg",
    },
    { key: "individuals", label: "Физлица", icon: "/design/crm/contacts.svg" },
    { key: "view", label: "Просмотр", icon: "/design/crm/view.svg" },
    { key: "edit", label: "Редактировать", icon: "/design/crm/edit.svg" },
    { key: "delete", label: "Удалить", icon: "/design/crm/delete.svg" },
];
const page = usePage<any>();
const columnFields = [
    { key: "name", label: "Название" },
    { key: "shortname", label: "Краткое название" },
    { key: "status", label: "Статус" },
];
const advancedFilters = useFilterPresets("clients");
const advancedFields: FilterField[] = [
    { id: "id", label: "#", type: "number" },
    { id: "name", label: "Название", type: "text" },
    { id: "shortname", label: "Краткое название", type: "text" },
    {
        id: "status",
        label: "Статус",
        type: "tuple",
        format: (value) => statusLabels[String(value)] ?? String(value),
    },
    { id: "deleted_at", label: "Удалён", type: "text" },
];
const store = createEntitySync<ClientRow>(
    `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,
    "clients",
);
const { rows, ready, syncing, online, error, warning } = store;
const relations = ref<Record<string, RelationFlags>>({});
const relationError = ref("");
let relationRequest = 0;
const query = ref(""),
    shortQuery = ref(""),
    statusFilter = ref("all"),
    currentPage = ref(1),
    descending = ref(false);
const selected = ref<ClientRow | null>(null),
    deleting = ref<ClientRow | null>(null),
    conflict = ref<ClientRow | null>(null);
const editing = ref(false),
    viewing = ref(false),
    saving = ref(false),
    notice = ref("");
const form = ref<{ name: string; shortname: string; status: number | null }>({
    name: "",
    shortname: "",
    status: 1,
});
const statusLabels: Record<string, string> = {
    "0": "Новый",
    "1": "Активен",
    "2": "Отключен",
    null: "Не указан",
};
const advancedOptions: FilterOptions = { status: [0, 1, 2] };
const quickFiltered = computed(() =>
    rows.value
        .filter((row) => {
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
                [row.name, row.shortname]
                    .join(" ")
                    .toLocaleLowerCase("ru")
                    .includes(query.value.toLocaleLowerCase("ru")) &&
                (row.shortname ?? "")
                    .toLocaleLowerCase("ru")
                    .includes(shortQuery.value.toLocaleLowerCase("ru"))
            );
        })
        .sort(
            (a, b) =>
                (a.name ?? "").localeCompare(b.name ?? "", "ru") *
                (descending.value ? -1 : 1),
        ),
);
const filtered = computed(() =>
    applyTableFilter(quickFiltered.value, advancedFilters.combined.value),
);
const pages = computed(() =>
    Math.max(1, Math.ceil(filtered.value.length / 25)),
);
const visible = computed(() =>
    filtered.value.slice((currentPage.value - 1) * 25, currentPage.value * 25),
);
async function refreshRelations(): Promise<void> {
    const ids = visible.value.map((row) => String(row.id));
    const request = ++relationRequest;
    if (!ids.length) return;
    const params = new URLSearchParams();
    ids.forEach((id) => params.append("ids[]", id));
    relationError.value = "";
    try {
        const response = await http(`/web/clients/relations?${params}`);
        if (request === relationRequest)
            relations.value = { ...relations.value, ...response.data };
    } catch {
        if (request === relationRequest)
            relationError.value =
                "Не удалось проверить связанные записи клиентов.";
    }
}
function relationDisabled(row: ClientRow, relation: Relation): boolean {
    if (saving.value || !!row.deleted_at) return true;
    const flags = relations.value[String(row.id)];
    return flags ? !flags[relation] : !relationError.value;
}
function actionDisabled(row: ClientRow, action: ClientAction): boolean {
    if (action === "view") return false;
    if (action === "edit" || action === "delete")
        return !online.value || saving.value || !!row.deleted_at;
    return relationDisabled(row, action);
}
function runAction(row: ClientRow, action: ClientAction): void {
    if (actionDisabled(row, action)) return;
    closeActionMenu();
    switch (action) {
        case "documents":
            openDocuments(row);
            break;
        case "accounts":
            openAccounts(row);
            break;
        case "integrations":
            openIntegrations(row);
            break;
        case "companies":
        case "individuals":
            openParties(row, action);
            break;
        case "view":
            open(row, true);
            break;
        case "edit":
            open(row);
            break;
        case "delete":
            deleting.value = row;
            break;
    }
}
const actionMenuRow = ref<ClientRow | null>(null);
const actionMenuPosition = ref({ top: 0, left: 0 });
function closeActionMenu(): void {
    actionMenuRow.value = null;
}
function closeActionMenuOnEscape(event: KeyboardEvent): void {
    if (event.key === "Escape") closeActionMenu();
}
function toggleActionMenu(row: ClientRow, event: MouseEvent): void {
    if (actionMenuRow.value?.id === row.id) {
        closeActionMenu();
        return;
    }
    const trigger = (
        event.currentTarget as HTMLElement
    ).getBoundingClientRect();
    const menuWidth = 208;
    const menuHeight = clientActions.length * 40 + 12;
    actionMenuPosition.value = {
        top:
            trigger.bottom + menuHeight + 8 <= window.innerHeight
                ? trigger.bottom + 4
                : Math.max(8, trigger.top - menuHeight - 4),
        left: Math.max(
            8,
            Math.min(
                trigger.right - menuWidth,
                window.innerWidth - menuWidth - 8,
            ),
        ),
    };
    actionMenuRow.value = row;
}
watch(
    visible,
    () => {
        void refreshRelations();
    },
    { immediate: true },
);
watch([query, shortQuery, statusFilter], () => (currentPage.value = 1));
watch(
    pages,
    (count) => (currentPage.value = Math.min(currentPage.value, count)),
);
function displayName(row: ClientRow) {
    return row.name || row.shortname || `Клиент №${row.id}`;
}
const expandedMobileRows = ref<Set<string>>(new Set());
function toggleMobileRow(id: string | number, event?: MouseEvent) {
    if (
        !window.matchMedia("(max-width: 900px)").matches ||
        (event?.detail ?? 0) > 1
    )
        return;
    const key = String(id);
    const next = new Set(expandedMobileRows.value);
    if (next.has(key)) next.delete(key);
    else next.add(key);
    expandedMobileRows.value = next;
}
function openName(row: ClientRow, event: MouseEvent) {
    if (window.matchMedia("(max-width: 900px)").matches) {
        event.stopPropagation();
        toggleMobileRow(row.id, event);
        return;
    }
    open(row, true);
}
function openParties(row: ClientRow, party: "companies" | "individuals") {
    if (saving.value || row.deleted_at) return;
    const url = `/clients/${party}?client_id=${encodeURIComponent(row.id)}`;
    if (online.value) router.visit(url);
    else
        router.push({
            url,
            component:
                party === "companies" ? "ClientCompanies" : "ClientIndividuals",
            props: {
                ...page.props,
                companyScope: null,
                clientScope: { id: String(row.id), name: displayName(row) },
            },
        });
}
function openDocuments(row: ClientRow) {
    if (saving.value || row.deleted_at) return;
    const url = `/clients/documents?client_id=${encodeURIComponent(row.id)}`;
    router.visit(url);
}
function openAccounts(row: ClientRow) {
    if (saving.value || row.deleted_at) return;
    const url = `/clients/accounts?client_id=${encodeURIComponent(row.id)}`;
    router.visit(url);
}
function openIntegrations(row: ClientRow) {
    if (saving.value || row.deleted_at) return;
    const url = `/clients/integrations?client_id=${encodeURIComponent(row.id)}`;
    router.visit(url);
}
function open(row: ClientRow | null, readOnly = false) {
    if (saving.value) return;
    selected.value = row;
    form.value = {
        name: row?.name ?? "",
        shortname: row?.shortname ?? "",
        status: row ? row.status : 1,
    };
    viewing.value = readOnly || !!row?.deleted_at;
    notice.value = "";
    conflict.value = null;
    editing.value = true;
}
async function save(remove = false) {
    if (!online.value || saving.value || (!remove && viewing.value)) return;
    const creating = !selected.value && !remove;
    saving.value = true;
    notice.value = "";
    try {
        const response = await http(
            `/web/clients${selected.value ? "/" + selected.value.id : ""}`,
            remove ? "DELETE" : selected.value ? "PUT" : "POST",
            {
                ...(!remove ? form.value : {}),
                ...(selected.value ? { version: selected.value.version } : {}),
            },
        );
        await store.apply(response.data);
        if (creating) {
            saving.value = false;
            open(null);
            notice.value = "Запись создана. Можно добавить следующую.";
        } else {
            selected.value = response.data;
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
async function confirmDelete() {
    if (!deleting.value || !online.value || saving.value) return;
    open(deleting.value, true);
    deleting.value = null;
    await save(true);
}
onMounted(() => {
    void store.start();
    window.addEventListener("focus", refreshRelations);
    window.addEventListener("scroll", closeActionMenu);
    window.addEventListener("resize", closeActionMenu);
    window.addEventListener("keydown", closeActionMenuOnEscape);
});
onUnmounted(() => {
    store.stop();
    window.removeEventListener("focus", refreshRelations);
    window.removeEventListener("scroll", closeActionMenu);
    window.removeEventListener("resize", closeActionMenu);
    window.removeEventListener("keydown", closeActionMenuOnEscape);
});

useCardRoute<ClientRow>({
    base: "/clients/clients",
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
    <Head title="Клиенты" />
    <div class="users-workspace" :class="{ 'has-editor': editing }">
        <section class="users-list">
            <div class="content-breadcrumb">Клиенты › Клиенты</div>
            <ClientTabs />
            <div class="page-heading">
                <h1>Клиенты</h1>
                <div class="page-heading-actions">
                    <FilterPresetButton
                        :state="advancedFilters"
                        :fields="advancedFields"
                        :options="advancedOptions"
                    />
                    <DataTransferMenu
                        :rows="filtered"
                        :columns="columnFields"
                        filename="clients"
                    />
                    <button
                        class="primary"
                        :disabled="!online || !ready || saving"
                        @click="open(null)"
                    >
                        Добавить клиента
                    </button>
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
            <FilterPresetTiles :state="advancedFilters" />
            <p v-if="error || warning" class="notice" role="alert">
                {{ error || warning }}
            </p>
            <p v-if="relationError" class="notice" role="alert">
                {{ relationError }}
            </p>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th scope="col" class="id-column">#</th>
                            <th>
                                <button @click="descending = !descending">
                                    Название {{ descending ? "▴" : "▾" }}
                                </button>
                            </th>
                            <th>Краткое название</th>
                            <th>Статус</th>
                            <th>
                                Действия
                                <TableColumnSettings
                                    :columns="columnFields"
                                    storage-key="clients-columns"
                                />
                            </th>
                        </tr>
                        <tr class="filter-row">
                            <th class="id-column"></th>
                            <th>
                                <input
                                    v-model="query"
                                    aria-label="Поиск клиентов"
                                    placeholder="Поиск"
                                />
                            </th>
                            <th>
                                <input
                                    v-model="shortQuery"
                                    aria-label="Поиск по краткому названию"
                                />
                            </th>
                            <th>
                                <select
                                    v-model="statusFilter"
                                    aria-label="Фильтр статуса"
                                >
                                    <option value="all">Все</option>
                                    <option value="0">Новые</option>
                                    <option value="1">Активные</option>
                                    <option value="2">Отключенные</option>
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
                                'mobile-card-expanded': expandedMobileRows.has(
                                    String(row.id),
                                ),
                            }"
                            @click="toggleMobileRow(row.id, $event)"
                            @dblclick="open(row)"
                        >
                            <td class="id-column">{{ row.id }}</td>
                            <td>
                                <button
                                    class="name-button"
                                    @click="openName(row, $event)"
                                >
                                    {{ displayName(row) }}
                                </button>
                            </td>
                            <td>{{ row.shortname || "—" }}</td>
                            <td>
                                <span
                                    class="badge"
                                    :class="`status-${row.deleted_at ? 'deleted' : row.status}`"
                                    >{{
                                        row.deleted_at
                                            ? "Удалён"
                                            : (statusLabels[
                                                  String(row.status)
                                              ] ?? String(row.status))
                                    }}</span
                                >
                            </td>
                            <td>
                                <div class="row-actions client-desktop-actions">
                                    <button
                                        v-for="action in clientActions"
                                        :key="action.key"
                                        type="button"
                                        :aria-label="`${action.label}: ${displayName(row)}`"
                                        :title="action.label"
                                        :disabled="
                                            actionDisabled(row, action.key)
                                        "
                                        @click="runAction(row, action.key)"
                                    >
                                        <img :src="action.icon" alt="" />
                                    </button>
                                </div>
                                <button
                                    v-if="clientActions.length > 4"
                                    class="client-mobile-action-trigger"
                                    type="button"
                                    :aria-label="`Действия: ${displayName(row)}`"
                                    aria-haspopup="menu"
                                    :aria-expanded="
                                        actionMenuRow?.id === row.id
                                    "
                                    @click.stop="toggleActionMenu(row, $event)"
                                >
                                    ⋮
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!visible.length">
                            <td colspan="5" class="empty-state">
                                {{
                                    ready
                                        ? "Клиенты не найдены"
                                        : "Загрузка клиентов…"
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <footer class="list-footer">
                <span>Найдено: {{ filtered.length }}</span>
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
        <Teleport to="body">
            <template v-if="actionMenuRow">
                <div
                    class="client-action-menu-backdrop"
                    @click="closeActionMenu"
                ></div>
                <div
                    class="client-action-menu"
                    role="menu"
                    :aria-label="`Действия: ${displayName(actionMenuRow)}`"
                    :style="{
                        top: `${actionMenuPosition.top}px`,
                        left: `${actionMenuPosition.left}px`,
                    }"
                    @keydown.esc.stop="closeActionMenu"
                >
                    <button
                        v-for="action in clientActions"
                        :key="action.key"
                        type="button"
                        role="menuitem"
                        :disabled="actionDisabled(actionMenuRow, action.key)"
                        @click.stop="runAction(actionMenuRow, action.key)"
                    >
                        <img :src="action.icon" alt="" />
                        {{ action.label }}
                    </button>
                </div>
            </template>
        </Teleport>
        <ConfirmDelete
            v-if="deleting"
            :message="`Удалить клиента ${displayName(deleting)}?`"
            :disabled="!online || saving"
            @cancel="deleting = null"
            @confirm="confirmDelete"
        />
        <aside v-if="editing" class="editor" aria-label="Карточка клиента">
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
                            selected
                                ? displayName(selected)
                                : "Добавить клиента"
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
                        <label
                            >Название *<input
                                v-model="form.name"
                                required
                                maxlength="255" /></label
                        ><label
                            >Краткое название<input
                                v-model="form.shortname"
                                maxlength="255" /></label
                        ><label
                            >Статус<select
                                v-model="form.status"
                                aria-label="Статус"
                            >
                                <option
                                    v-if="
                                        form.status !== null &&
                                        ![0, 1, 2].includes(form.status)
                                    "
                                    :value="form.status"
                                    disabled
                                >
                                    Выберите статус
                                </option>
                                <option :value="null">Не указан</option>
                                <option :value="0">Новый</option>
                                <option :value="1">Активен</option>
                                <option :value="2">Отключен</option>
                            </select></label
                        ><button v-if="!viewing" type="submit" class="primary">
                            {{
                                saving
                                    ? "Сохраняем…"
                                    : selected
                                      ? "Сохранить изменения"
                                      : "Создать клиента"
                            }}
                        </button>
                    </fieldset>
                </form>
            </div>
        </aside>
    </div>
</template>
<style scoped>
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
.row-actions button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}
.row-actions button:disabled img {
    filter: grayscale(1);
}
.client-mobile-action-trigger {
    display: none;
}
.client-action-menu-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1000;
}
.client-action-menu {
    position: fixed;
    z-index: 1001;
    display: grid;
    width: 208px;
    max-height: calc(100dvh - 16px);
    overflow-y: auto;
    padding: 6px;
    border: 1px solid #dcecef;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 12px 32px rgb(16 24 40 / 18%);
}
.client-action-menu button {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 40px;
    padding: 8px 10px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: #0c1821;
    font: inherit;
    font-size: 13px;
    text-align: left;
    cursor: pointer;
}
.client-action-menu button:hover,
.client-action-menu button:focus-visible {
    background: #e1f3e7;
}
.client-action-menu button:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
.client-action-menu img {
    width: 16px;
    height: 16px;
    object-fit: contain;
}
@media (max-width: 900px) {
    html
        body
        .main-panel
        .table-scroll
        table
        tbody
        tr
        td:last-child
        .row-actions.client-desktop-actions {
        display: none !important;
    }
    html
        body
        .main-panel
        .table-scroll
        table
        tbody
        tr
        td:last-child
        .client-mobile-action-trigger {
        display: inline-grid !important;
        place-items: center;
        width: 28px !important;
        height: 28px !important;
        padding: 0;
        border: 0;
        border-radius: 6px;
        background: #e1f3e7;
        color: #176b2a;
        font: inherit;
        font-size: 23px;
        line-height: 1;
        cursor: pointer;
    }
}
td,
.editor h2 {
    overflow-wrap: anywhere;
}
</style>
