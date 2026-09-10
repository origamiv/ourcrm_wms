<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import { http, HttpError } from "../lib/http";
import AdminTabs from "./AdminTabs.vue";
import { createEntitySync } from "../lib/entitySync";
import type { EntityRow } from "../lib/cache";
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
const store = createEntitySync<CatalogRow>(
    `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,
    props.entity,
);
const { rows, ready, syncing, online, error, warning } = store;
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
function open(row: CatalogRow | null) {
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
async function save() {
    if (!online.value || saving.value) return;
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
        selected.value = response.data;
        conflict.value = null;
        notice.value = "Изменения сохранены";
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
    if (new URLSearchParams(page.url.split("?")[1]).get("create") === "1")
        open(null);
});
onUnmounted(() => store.stop());
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
                <button
                    class="primary"
                    :disabled="!online || !ready || saving"
                    @click="open(null)"
                >
                    {{
                        entity === "roles" ? "Добавить роль" : "Добавить право"
                    }}
                </button>
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
                            <th>Действия</th>
                        </tr>
                        <tr class="column-filters">
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
                        <tr v-for="row in visible" :key="row.id">
                            <td>
                                <button
                                    class="text-button"
                                    :disabled="saving || !!row.deleted_at"
                                    @click="open(row)"
                                >
                                    {{ row.name || "—" }}
                                </button>
                            </td>
                            <td>{{ row.slug || "—" }}</td>
                            <td>
                                {{
                                    (entity === "roles"
                                        ? row.description
                                        : row.resource) || "—"
                                }}
                            </td>
                            <td>{{ row.system ? "Да" : "Нет" }}</td>
                            <td>
                                <span class="badge">{{
                                    row.deleted_at
                                        ? "Удалена"
                                        : statusLabel(row.status)
                                }}</span>
                            </td>
                            <td>
                                <div class="row-actions">
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
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!visible.length">
                            <td colspan="6" class="empty-state">
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
        <aside v-if="editing" class="editor" aria-label="Карточка записи">
            <header>
                <div>
                    <small>{{
                        selected ? "РЕДАКТИРОВАНИЕ" : "НОВАЯ ЗАПИСЬ"
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
                    Код, ресурс и статус системной записи или роли admin
                    защищены от изменения.
                </p>
                <form class="catalog-form" @submit.prevent="save">
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
                            :disabled="saving || !online || protectedRecord"
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
                            :disabled="saving || !online || protectedRecord"
                    /></label>
                    <label
                        >Статус<select
                            v-model="form.status"
                            :disabled="saving || !online || protectedRecord"
                        >
                            <option :value="null">Не указан</option>
                            <option :value="0">Неактивен</option>
                            <option :value="1">Активен</option>
                            <option :value="2">2</option>
                            <option :value="3">3</option>
                        </select></label
                    >
                    <button
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
