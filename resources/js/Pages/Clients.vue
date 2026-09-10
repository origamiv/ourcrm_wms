<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import RibbonTabs from "../Components/RibbonTabs.vue";
import ConfirmDelete from "../Components/ConfirmDelete.vue";
import { createEntitySync } from "../lib/entitySync";
import { http, HttpError } from "../lib/http";
import type { EntityRow } from "../lib/cache";
interface ClientRow extends EntityRow {
    name: string | null;
    shortname: string | null;
    status: number | null;
    deleted_at: string | null;
}
const page = usePage<any>();
const store = createEntitySync<ClientRow>(
    `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,
    "clients",
);
const { rows, ready, syncing, online, error, warning } = store;
const tabs = [
    {
        label: "Клиенты",
        url: "/clients",
        component: "Clients",
        icon: "company_contacts",
    },
];
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
const filtered = computed(() =>
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
const pages = computed(() =>
    Math.max(1, Math.ceil(filtered.value.length / 25)),
);
const visible = computed(() =>
    filtered.value.slice((currentPage.value - 1) * 25, currentPage.value * 25),
);
watch([query, shortQuery, statusFilter], () => (currentPage.value = 1));
watch(
    pages,
    (count) => (currentPage.value = Math.min(currentPage.value, count)),
);
function displayName(row: ClientRow) {
    return row.name || row.shortname || `Клиент №${row.id}`;
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
        selected.value = response.data;
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
onMounted(store.start);
onUnmounted(store.stop);
</script>
<template>
    <Head title="Клиенты" />
    <div class="users-workspace" :class="{ 'has-editor': editing }">
        <section class="users-list">
            <div class="content-breadcrumb">Клиенты › Клиенты</div>
            <RibbonTabs :tabs="tabs" label="Разделы клиентов" />
            <div class="page-heading">
                <h1>Клиенты</h1>
                <button
                    class="primary"
                    :disabled="!online || !ready || saving"
                    @click="open(null)"
                >
                    Добавить клиента
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
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>
                                <button @click="descending = !descending">
                                    Название {{ descending ? "▴" : "▾" }}
                                </button>
                            </th>
                            <th>Краткое название</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                        <tr class="filter-row">
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
                            @dblclick="open(row)"
                        >
                            <td>
                                <button
                                    class="name-button"
                                    @click="open(row, true)"
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
                                <div class="row-actions">
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
                            <td colspan="4" class="empty-state">
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
td,
.editor h2 {
    overflow-wrap: anywhere;
}
</style>
