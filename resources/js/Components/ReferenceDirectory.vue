<script setup lang="ts">
import { useCardRoute } from "../lib/cardRoute";
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
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
const isIndividual = props.entity === "client_individuals";
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
        lookupStores[entity]?.rows.value.filter((row) => !row.deleted_at) ?? []
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
                [row.name, row.shortname, row.category, row.path]
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
watch(
    () => clientScope.value?.id,
    () => {
        editing.value = false;
        deleting.value = null;
        currentPage.value = 1;
    },
);
function displayName(row: ReferenceRow) {
    return row.name || row.shortname || `Запись №${row.id}`;
}
function open(row: ReferenceRow | null, readOnly = false) {
    if (
        saving.value ||
        (clientScope.value &&
            row &&
            String(row.client_id) !== clientScope.value.id)
    )
        return;
    selected.value = row;
    form.value = {
        name: row?.name ?? "",
        ...Object.fromEntries(
            definition.fields.map((field) => [
                field.key,
                row?.[field.key] ??
                    (field.kind === "number" ||
                    field.kind === "flag" ||
                    field.kind === "lookup"
                        ? null
                        : ""),
            ]),
        ),
        status: row ? row.status : 1,
        ...(clientScope.value ? { client_id: clientScope.value.id } : {}),
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
            `/web/${isIndividual ? `clients/${clientScope.value ? clientScope.value.id + "/" : ""}individuals` : props.entity}${selected.value ? "/" + selected.value.id : ""}`,
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
    base: isIndividual ? "/clients/individuals" : `/main/${props.entity}`,
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
                    isIndividual ? "Клиенты" : "Администрирование › Справочники"
                }}
                › {{ definition.title }}
            </div>
            <ClientTabs v-if="isIndividual" /><AdminTabs v-else />
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
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>
                                <button @click="descending = !descending">
                                    {{ isIndividual ? "ФИО" : "Название" }}
                                    {{ descending ? "▴" : "▾" }}
                                </button>
                            </th>
                            <th>
                                {{
                                    [
                                        "modules",
                                        "features",
                                        "client_individuals",
                                    ].includes(props.entity)
                                        ? isIndividual
                                            ? "Краткое имя"
                                            : "Краткое название"
                                        : "Категория"
                                }}
                            </th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                        <tr class="filter-row">
                            <th>
                                <input
                                    v-model="query"
                                    :aria-label="`Поиск: ${definition.title}`"
                                    placeholder="Поиск"
                                />
                            </th>
                            <th>
                                <input
                                    v-model="shortQuery"
                                    :aria-label="
                                        [
                                            'modules',
                                            'features',
                                            'client_individuals',
                                        ].includes(props.entity)
                                            ? 'Поиск по краткому названию'
                                            : 'Поиск по категории'
                                    "
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
                                        ? "Записи не найдены"
                                        : "Загрузка записей…"
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
                        <label
                            >{{ isIndividual ? "ФИО *" : "Название *"
                            }}<input
                                v-model="form.name"
                                required
                                maxlength="255"
                        /></label>
                        <label
                            v-for="field in definition.fields"
                            :key="field.key"
                            >{{ field.label }}
                            <textarea
                                v-if="field.kind === 'textarea'"
                                v-model="form[field.key]"
                                :aria-label="field.label"
                                maxlength="10000"
                                rows="4"
                            />
                            <select
                                v-else-if="field.kind === 'lookup'"
                                :disabled="
                                    field.key === 'client_id' && !!clientScope
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
                                    Недоступная запись №{{ form[field.key] }}
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
                                v-else-if="field.kind === 'flag'"
                                v-model="form[field.key]"
                                :aria-label="field.label"
                            >
                                <option :value="null">Не указан</option>
                                <option :value="1">Да</option>
                                <option :value="0">Нет</option>
                            </select>
                            <input
                                v-else-if="field.kind === 'date'"
                                v-model="form[field.key]"
                                :aria-label="field.label"
                                type="date"
                            />
                            <input
                                v-else-if="field.kind === 'number'"
                                v-model="form[field.key]"
                                :aria-label="field.label"
                                type="number"
                                min="0"
                                max="2147483647"
                            />
                            <input
                                v-else
                                v-model="form[field.key]"
                                :aria-label="field.label"
                                maxlength="255"
                            /> </label
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
                                <option
                                    v-if="props.entity !== 'files'"
                                    :value="null"
                                >
                                    Не указан
                                </option>
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
