<script setup lang="ts">
import { useCardRoute } from "../lib/cardRoute";
import { computed, ref, onMounted, onUnmounted, watch } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import { http, HttpError } from "../lib/http";
import ConfirmDelete from "./ConfirmDelete.vue";
import AdminTabs from "./AdminTabs.vue";
import { createEntitySync } from "../lib/entitySync";
import type { EntityRow } from "../lib/cache";
import TableColumnSettings from "./TableColumnSettings.vue";
import DataTransferMenu from "./DataTransferMenu.vue";
interface CatalogRow extends EntityRow {
    name: string;
    slug: string | null;
    description?: string | null;
    resource?: string;
    system: boolean;
    status: number | null;
    deleted_at: string | null;
}
const props = defineProps<{ entity: "roles" | "permissions"; title: string }>();
const page = usePage<any>();
const expandedMobileRows = ref<Set<string>>(new Set());
function toggleMobileRow(id: string | number, event?: MouseEvent) {
    if (!window.matchMedia('(max-width: 900px)').matches || (event?.detail ?? 0) > 1) return;
    const key = String(id);
    const next = new Set(expandedMobileRows.value);
    if (next.has(key)) next.delete(key); else next.add(key);
    expandedMobileRows.value = next;
}
function openName(row: CatalogRow, event: MouseEvent) {
    if (window.matchMedia('(max-width: 900px)').matches) {
        event.stopPropagation();
        toggleMobileRow(row.id, event);
        return;
    }
    open(row);
}
const columnFields = computed(() => [
    { key: "name", label: "Название" },
    { key: "slug", label: "Код" },
    { key: "details", label: props.entity === "roles" ? "Описание" : "Ресурс" },
    { key: "system", label: "Системная запись" },
    { key: "status", label: "Статус" },
]);
const store = createEntitySync<CatalogRow>(
    `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,
    props.entity,
);
const { rows, ready, syncing, online, error, warning } = store;
const viewing = ref(false);
const deleting = ref<CatalogRow | null>(null);
const editing = ref(false),
    saving = ref(false),
    selected = ref<CatalogRow | null>(null),
    conflict = ref<CatalogRow | null>(null),
    formError = ref(""),
    notice = ref("");
const form = ref<{
    name: string;
    slug: string;
    description: string;
    resource: string;
    status: number | null;
}>({ name: "", slug: "", description: "", resource: "", status: 1 });
const protectedRecord = computed(
    () =>
        !!selected.value &&
        (selected.value.system ||
            (props.entity === "roles" && selected.value.slug === "admin")),
);
function open(row: CatalogRow | null, readOnly = false) {
    if (saving.value) return;
    viewing.value = readOnly || !!row?.deleted_at;
    selected.value = row;
    form.value = {
        name: row?.name ?? "",
        slug: row?.slug ?? "",
        description: row?.description ?? "",
        resource: row?.resource ?? "",
        status: row ? row.status : 1,
    };
    conflict.value = null;
    formError.value = "";
    notice.value = "";
    editing.value = true;
}
async function confirmDelete() {
    const row = deleting.value;
    if (!row || !online.value || saving.value) return;
    deleting.value = null;
    open(row, true);
    saving.value = true;
    try {
        const response = await http(`/web/roles/${row.id}`, "DELETE", {
            version: row.version,
        });
        await store.apply(response.data);
        editing.value = false;
    } catch (error) {
        if (error instanceof HttpError && error.status === 409)
            conflict.value = error.body.current;
        formError.value =
            error instanceof Error ? error.message : "Не удалось удалить роль";
    } finally {
        saving.value = false;
    }
}
async function save() {
    if (!online.value || saving.value || viewing.value) return;
    const creating = !selected.value;
    saving.value = true;
    formError.value = "";
    notice.value = "";
    try {
        const payload = {
            name: form.value.name,
            slug: form.value.slug,
            status: form.value.status,
            ...(props.entity === "roles"
                ? { description: form.value.description }
                : { resource: form.value.resource }),
            ...(selected.value ? { version: selected.value.version } : {}),
        };
        const response = await http(
            `/web/${props.entity}${selected.value ? "/" + selected.value.id : ""}`,
            selected.value ? "PUT" : "POST",
            payload,
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
    } catch (error) {
        if (error instanceof HttpError && error.status === 409)
            conflict.value = error.body.current;
        formError.value =
            error instanceof HttpError && error.body.errors
                ? Object.values(error.body.errors).flat().join(" ")
                : error instanceof Error
                  ? error.message
                  : "Не удалось сохранить запись";
    } finally {
        saving.value = false;
    }
}
const query = ref(""),
    status = ref("all"),
    system = ref("all"),
    currentPage = ref(1),
    sort = ref<"name" | "slug">("name");
const filtered = computed(() =>
    rows.value
        .filter((row) => {
            if (status.value === "deleted" ? !row.deleted_at : !!row.deleted_at)
                return false;
            if (
                !["all", "deleted"].includes(status.value) &&
                String(row.status) !== status.value
            )
                return false;
            if (
                system.value !== "all" &&
                row.system !== (system.value === "yes")
            )
                return false;
            const q = query.value.trim().toLocaleLowerCase("ru");
            return [row.name, row.slug, row.description, row.resource].some(
                (value) => (value ?? "").toLocaleLowerCase("ru").includes(q),
            );
        })
        .sort(
            (a, b) =>
                (a[sort.value] ?? "").localeCompare(
                    b[sort.value] ?? "",
                    "ru",
                ) || a.id.localeCompare(b.id),
        ),
);
const pageCount = computed(() =>
    Math.max(1, Math.ceil(filtered.value.length / 25)),
);
const visible = computed(() =>
    filtered.value.slice((currentPage.value - 1) * 25, currentPage.value * 25),
);
const statusOptions = computed(() =>
    [
        ...new Set(
            rows.value
                .map((row) => row.status)
                .filter((value) => value !== null),
        ),
    ].sort((a, b) => a! - b!),
);
function statusLabel(value: number | null) {
    if (props.entity === "roles")
        return value === 1 ? "Активен" : value === 2 ? "Отключен" : "Не указан";
    return value === 1
        ? "Активен"
        : value === 0
          ? "Неактивен"
          : value === null
            ? "Не указан"
            : String(value);
}
watch([query, status, system], () => (currentPage.value = 1));
watch(
    pageCount,
    (count) => (currentPage.value = Math.min(currentPage.value, count)),
);
onMounted(async () => {
    await store.start();
});
onUnmounted(() => store.stop());

useCardRoute<CatalogRow>({
    base: `/main/${props.entity}`,
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
    <Head :title="title" />
    <div class="users-workspace" :class="{ 'has-editor': editing }">
        <section class="users-list">
            <div class="content-breadcrumb">
                Администрирование › {{ title }}
            </div>
            <AdminTabs />
            <div class="page-heading">
                <h1>{{ title }}</h1>
                <div class="page-heading-actions">
                    <DataTransferMenu :rows="visible" :columns="columnFields" :filename="entity" />
                    <button class="primary" :disabled="!online || !ready || saving" @click="open(null)">{{ entity === "roles" ? "Добавить роль" : "Добавить право" }}</button>
                </div>
            </div>
            <div class="sync-line" role="status">
                <span :class="{ 'offline-text': !online }">{{
                    syncing
                        ? "Загружаем изменения…"
                        : !online
                          ? "Нет связи · сохранённые данные"
                          : ready
                            ? "Данные синхронизированы"
                            : "Первичная загрузка…"
                }}</span
                ><span v-if="!ready">Список ещё не загружен полностью</span>
            </div>
            <p v-if="warning" class="notice">{{ warning }}</p>
            <p v-if="error" class="notice error" role="alert">{{ error }}</p>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th scope="col" class="id-column">#</th>
                            <th>
                                <button @click="sort = 'name'">
                                    Название ▾
                                </button>
                            </th>
                            <th>
                                <button @click="sort = 'slug'">Код ▾</button>
                            </th>
                            <th>
                                {{ entity === "roles" ? "Описание" : "Ресурс" }}
                            </th>
                            <th>Системная запись</th>
                            <th>Статус</th>
                            <th>Действия <TableColumnSettings :columns="columnFields" :storage-key="`${entity}-columns`" /></th>
                        </tr>
                        <tr class="column-filters">
                            <th class="id-column"></th>
                            <th colspan="3">
                                <input
                                    v-model="query"
                                    :aria-label="`Поиск: ${title}`"
                                    placeholder="Поиск по названию, коду или описанию"
                                />
                            </th>
                            <th>
                                <select
                                    v-model="system"
                                    aria-label="Системные записи"
                                >
                                    <option value="all">Все</option>
                                    <option value="yes">Да</option>
                                    <option value="no">Нет</option>
                                </select>
                            </th>
                            <th>
                                <select
                                    v-model="status"
                                    aria-label="Фильтр статуса"
                                >
                                    <option value="all">Все</option>
                                    <option
                                        v-for="value in statusOptions"
                                        :key="value!"
                                        :value="String(value)"
                                    >
                                        {{ statusLabel(value) }}
                                    </option>
                                    <option value="deleted">Удалённые</option>
                                </select>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in visible" :key="row.id"
                            :class="{ 'mobile-card-expanded': expandedMobileRows.has(String(row.id)) }"
                            @click="toggleMobileRow(row.id, $event)">
                            <td class="id-column">{{ row.id }}</td>
                            <td>
                                <button
                                    class="text-button name-button"
                                    :disabled="saving || !!row.deleted_at"
                                    @click="openName(row, $event)"
                                >
                                    {{ row.name || "—" }}
                                </button>
                            </td>
                            <td data-label="Код">{{ row.slug || "—" }}</td>
                            <td :data-label="entity === 'roles' ? 'Описание' : 'Ресурс'">
                                {{
                                    (entity === "roles"
                                        ? row.description
                                        : row.resource) || "—"
                                }}
                            </td>
                            <td data-label="Системная запись">{{ row.system ? "Да" : "Нет" }}</td>
                            <td data-label="Статус">
                                <span class="badge">{{
                                    row.deleted_at
                                        ? "Удалена"
                                        : statusLabel(row.status)
                                }}</span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <button
                                        v-if="entity === 'roles'"
                                        @click.stop="open(row, true)"
                                        :aria-label="`Просмотр: ${row.name}`"
                                        title="Просмотр"
                                    >
                                        <img
                                            src="/design/crm/view.svg"
                                            alt=""
                                        />
                                    </button>
                                    <button
                                        :disabled="saving || !!row.deleted_at"
                                        :aria-label="`Редактировать: ${row.name}`"
                                        title="Редактировать"
                                        @click.stop="open(row)"
                                    >
                                        <img
                                            src="/design/crm/edit.svg"
                                            alt=""
                                        />
                                    </button>
                                    <button
                                        v-if="entity === 'roles'"
                                        @click.stop="deleting = row"
                                        :aria-label="`Удалить: ${row.name}`"
                                        title="Удалить"
                                        :disabled="
                                            !online ||
                                            saving ||
                                            !!row.deleted_at ||
                                            row.system ||
                                            row.slug === 'admin'
                                        "
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
                            <td colspan="7" class="empty-state">
                                {{
                                    !ready
                                        ? "Загрузка записей…"
                                        : query ||
                                            status !== "all" ||
                                            system !== "all"
                                          ? "По выбранным условиям ничего не найдено"
                                          : "Записей пока нет"
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="catalog-pagination">
                <span>Найдено: {{ filtered.length }}</span
                ><button
                    class="text-button"
                    :disabled="!online || syncing"
                    @click="store.sync"
                >
                    Обновить</button
                ><button
                    class="text-button"
                    aria-label="Предыдущая страница"
                    :disabled="currentPage <= 1"
                    @click="currentPage--"
                >
                    ←</button
                ><span>{{ currentPage }} / {{ pageCount }}</span
                ><button
                    class="text-button"
                    aria-label="Следующая страница"
                    :disabled="currentPage >= pageCount"
                    @click="currentPage++"
                >
                    →
                </button>
            </div>
        </section>
        <ConfirmDelete
            v-if="deleting"
            :message="`Удалить роль ${deleting.name}?`"
            :disabled="!online || !ready || saving"
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
                    <h2>{{ entity === "roles" ? "Роль" : "Право доступа" }}</h2>
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
                <p v-if="formError" class="notice error" role="alert">
                    {{ formError }}
                </p>
                <button
                    v-if="conflict"
                    class="text-button"
                    :disabled="saving"
                    @click="open(conflict)"
                >
                    Загрузить актуальные данные
                </button>
                <p v-if="protectedRecord" class="notice">
                    Код системной записи и роли admin защищён. У системного
                    права также защищены ресурс и статус.
                </p>
                <form @submit.prevent="save">
                    <fieldset class="catalog-form" :disabled="viewing">
                        <label
                            >Название *<input
                                v-model="form.name"
                                required
                                maxlength="255"
                                :disabled="saving || !online"
                        /></label>
                        <label
                            >Код *<input
                                v-model="form.slug"
                                required
                                maxlength="255"
                                :disabled="
                                    saving || !online || protectedRecord
                                "
                        /></label>
                        <label v-if="entity === 'roles'"
                            >Описание<textarea
                                v-model="form.description"
                                maxlength="10000"
                                :disabled="saving || !online"
                            />
                        </label>
                        <label v-else
                            >Ресурс *<input
                                v-model="form.resource"
                                required
                                maxlength="255"
                                :disabled="
                                    saving || !online || protectedRecord
                                "
                        /></label>
                        <label
                            >Статус<select
                                aria-label="Статус"
                                v-model="form.status"
                                :disabled="
                                    saving ||
                                    !online ||
                                    (entity === 'permissions' &&
                                        protectedRecord)
                                "
                            >
                                <template v-if="entity === 'roles'">
                                    <option
                                        v-if="
                                            ![1, 2].includes(form.status ?? -1)
                                        "
                                        :value="form.status"
                                        disabled
                                    >
                                        Выберите статус
                                    </option>
                                    <option :value="1">Активен</option>
                                    <option :value="2">Отключен</option>
                                </template>
                                <template v-else>
                                    <option :value="null">Не указан</option>
                                    <option :value="0">Неактивен</option>
                                    <option :value="1">Активен</option>
                                    <option :value="2">2</option>
                                    <option :value="3">3</option>
                                </template>
                            </select></label
                        >
                        <button
                            v-if="!viewing"
                            class="primary"
                            :disabled="saving || !online || !!conflict"
                        >
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
.catalog-form {
    display: grid;
    gap: 18px;
}
.catalog-form label {
    margin-top: 0;
    display: grid;
    gap: 6px;
}
.catalog-form textarea {
    min-height: 100px;
    width: 100%;
    resize: vertical;
    border: 1px solid #d8e0e3;
    border-radius: 6px;
    padding: 10px;
}
.catalog-pagination {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 16px;
    padding: 20px 24px;
}
.catalog-pagination > span:first-child {
    margin-right: auto;
}
td {
    overflow-wrap: anywhere;
}
</style>
