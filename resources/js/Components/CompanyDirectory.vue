<script setup lang="ts">
import { useCardRoute } from "../lib/cardRoute";
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import DadataInput from "./DadataInput.vue";
import AdminTabs from "./AdminTabs.vue";
import ClientTabs from "./ClientTabs.vue";
import TableColumnSettings from "./TableColumnSettings.vue";
import DataTransferMenu from "./DataTransferMenu.vue";
import ConfirmDelete from "./ConfirmDelete.vue";
import { createEntitySync } from "../lib/entitySync";
import { http, HttpError } from "../lib/http";
import type { EntityRow } from "../lib/cache";
interface DirectoryRow extends EntityRow {
    name: string;
    shortname: string;
    status: number | null;
    deleted_at: string | null;
    company_id?: string | number;
    [key: string]: any;
}
const props = defineProps<{
    entity: "companies" | "company_contacts" | "client_companies";
    title: string;
}>();
const page = usePage<any>();
const namespace = `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`;
const store = createEntitySync<DirectoryRow>(namespace, props.entity);
const companies =
    props.entity !== "company_contacts"
        ? store
        : createEntitySync<DirectoryRow>(namespace, "companies");
const { rows, ready, syncing, online, error, warning } = store;
const isCompany = props.entity !== "company_contacts";
const isClientCompany = props.entity === "client_companies";
const clients = isClientCompany
    ? createEntitySync<DirectoryRow>(namespace, "clients")
    : null;
const scope = computed<{ id: string; name: string } | null>(() =>
    !isCompany ? (page.props.companyScope ?? null) : null,
);
const clientScope = computed<{ id: string; name: string } | null>(() =>
    isClientCompany ? (page.props.clientScope ?? null) : null,
);
const singular = isCompany ? "компанию" : "контактное лицо";
const labels: Record<string, string> = isCompany
    ? {
          name: "Название компании",
          shortname: "Краткое название",
          fullname: "Полное название",
          ...(isClientCompany ? { okpo: "ОКПО" } : {}),
          inn: "ИНН",
          kpp: "КПП",
          ogrn: "ОГРН",
          phone: "Телефон",
          email: "Email",
          site: "Сайт",
          director_position: "Должность директора",
          director_fio: "ФИО директора",
          bik: "БИК",
          bank: "Банк",
          rasch_schet: "Расчётный счёт",
          korr_schet: "Корреспондентский счёт",
      }
    : { name: "ФИО", shortname: "Краткое имя", val: "Контактное значение" };
const srcLabels: Record<string, string> = {
    telegram: "Telegram",
    opf: "ОПФ",
    legal_address: "Юридический адрес",
    accountant_position: "Должность бухгалтера",
    accountant_fio: "ФИО бухгалтера",
};
const flags: Record<string, string> = isClientCompany
    ? {}
    : {
          is_own: "Наша",
          is_client: "Клиент",
          is_partner: "Партнёр",
      };
const columnSettingsOpen = ref(false);
const hiddenColumns = ref<string[]>([]);
const columnFields = computed(() => [
    { key: "name", label: isCompany ? "Название компании" : "ФИО" },
    ...(!isCompany ? [{ key: "company", label: "Компания" }] : []),
    { key: "detail", label: isCompany ? "ИНН" : "Контактное значение" },
    { key: "status", label: "Статус" },
    ...(isCompany
        ? Object.entries(flags).map(([key, label]) => ({ key, label }))
        : []),
]);
const columnStorageKey = computed(() => `company-columns:${props.entity}`);
function isColumnVisible(key: string) {
    return !hiddenColumns.value.includes(key);
}
function toggleColumn(key: string) {
    hiddenColumns.value = isColumnVisible(key)
        ? [...hiddenColumns.value, key]
        : hiddenColumns.value.filter((item) => item !== key);
    localStorage.setItem(columnStorageKey.value, JSON.stringify(hiddenColumns.value));
}
const flagFilter = ref("");
const query = ref(""),
    detailQuery = ref(""),
    statusFilter = ref("all"),
    companyFilter = ref("");
const currentPage = ref(1),
    descending = ref(false);
const editing = ref(false),
    viewing = ref(false),
    saving = ref(false);
const selected = ref<DirectoryRow | null>(null),
    deleting = ref<DirectoryRow | null>(null),
    conflict = ref<DirectoryRow | null>(null);
const expandedMobileRows = ref<Set<string>>(new Set());
function toggleMobileRow(id: string | number, event?: MouseEvent) {
    if (!window.matchMedia('(max-width: 900px)').matches || (event?.detail ?? 0) > 1) return;
    const key = String(id);
    const next = new Set(expandedMobileRows.value);
    if (next.has(key)) next.delete(key); else next.add(key);
    expandedMobileRows.value = next;
}
function openName(row: DirectoryRow, event: MouseEvent) {
    if (window.matchMedia('(max-width: 900px)').matches) {
        event.stopPropagation();
        toggleMobileRow(row.id, event);
        return;
    }
    open(row, true);
}
const form = ref<Record<string, any>>({});
const notice = ref("");
const companyOptions = computed(() =>
    companies.rows.value
        .filter(
            (row) =>
                !row.deleted_at &&
                (!scope.value || String(row.id) === scope.value.id),
        )
        .sort((a, b) => a.name.localeCompare(b.name, "ru")),
);
function companyName(id: unknown) {
    return (
        companies.rows.value.find((row) => String(row.id) === String(id))
            ?.name ?? "Компания недоступна"
    );
}
const filtered = computed(() =>
    rows.value
        .filter((row) => {
            if (
                clientScope.value &&
                String(row.client_id) !== clientScope.value.id
            )
                return false;
            if (scope.value && String(row.company_id) !== scope.value.id)
                return false;
            if (flagFilter.value && !row.src?.[flagFilter.value]) return false;
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
            if (
                companyFilter.value &&
                String(row.company_id) !== companyFilter.value
            )
                return false;
            if (
                !String(isCompany ? (row.inn ?? "") : (row.val ?? ""))
                    .toLocaleLowerCase("ru")
                    .includes(detailQuery.value.toLocaleLowerCase("ru"))
            )
                return false;
            return [
                row.name,
                row.shortname,
                row.email,
                row.phone,
                row.inn,
                row.val,
                isCompany ? "" : companyName(row.company_id),
            ]
                .join(" ")
                .toLocaleLowerCase("ru")
                .includes(query.value.toLocaleLowerCase("ru"));
        })
        .sort(
            (a, b) =>
                a.name.localeCompare(b.name, "ru") *
                (descending.value ? -1 : 1),
        ),
);
const pages = computed(() =>
    Math.max(1, Math.ceil(filtered.value.length / 25)),
);
const visible = computed(() =>
    filtered.value.slice((currentPage.value - 1) * 25, currentPage.value * 25),
);
const statuses: Record<string, string> = {
    "0": "Новый",
    "1": "Активен",
    "2": "Отключен",
};
watch(
    [query, detailQuery, statusFilter, companyFilter, flagFilter],
    () => (currentPage.value = 1),
);
watch(
    pages,
    (count) => (currentPage.value = Math.min(currentPage.value, count)),
);
function open(row: DirectoryRow | null, readOnly = false) {
    if (
        saving.value ||
        (clientScope.value &&
            row &&
            String(row.client_id) !== clientScope.value.id) ||
        (scope.value && row && String(row.company_id) !== scope.value.id)
    )
        return;
    selected.value = row;
    viewing.value = readOnly || !!row?.deleted_at;
    form.value = {
        ...Object.fromEntries(
            Object.keys(labels).map((key) => [key, row?.[key] ?? ""]),
        ),
        status: row ? row.status : 1,
        ...(isClientCompany
            ? {
                  client_id:
                      clientScope.value?.id ??
                      (row?.client_id ? String(row.client_id) : null),
              }
            : {}),
        ...(isCompany
            ? {
                  src: {
                      ...Object.fromEntries(
                          Object.keys(srcLabels).map((key) => [
                              key,
                              row?.src?.[key] ?? "",
                          ]),
                      ),
                      ...Object.fromEntries(
                          Object.keys(flags).map((key) => [
                              key,
                              row?.src?.[key] === true || row?.src?.[key] === 1,
                          ]),
                      ),
                  },
              }
            : {}),
        ...(!isCompany
            ? {
                  company_id:
                      scope.value?.id ??
                      (row?.company_id ? String(row.company_id) : ""),
              }
            : {}),
    };
    notice.value = "";
    conflict.value = null;
    editing.value = true;
}
function openContacts(row: DirectoryRow) {
    if (saving.value || row.deleted_at) return;
    const url = `/main/company_contacts?company_id=${encodeURIComponent(row.id)}`;
    if (online.value) router.visit(url);
    else
        router.push({
            url,
            component: "CompanyContacts",
            props: {
                ...page.props,
                companyScope: { id: String(row.id), name: row.name },
            },
        });
}
watch(
    () => scope.value?.id,
    () => {
        editing.value = false;
        deleting.value = null;
        companyFilter.value = "";
        currentPage.value = 1;
    },
);
watch(
    () => clientScope.value?.id,
    () => {
        editing.value = false;
        deleting.value = null;
        currentPage.value = 1;
    },
);
function applySuggestion(fields: Record<string, any>) {
    if (viewing.value || saving.value || !online.value || conflict.value)
        return;
    const { src, ...values } = fields;
    Object.assign(form.value, values);
    if (src) Object.assign(form.value.src, src);
    notice.value =
        "Реквизиты заполнены. Проверьте данные и сохраните карточку.";
}
async function save(remove = false) {
    if (!online.value || saving.value || (!remove && viewing.value)) return;
    const creating = !selected.value && !remove;
    saving.value = true;
    notice.value = "";
    try {
        const response = await http(
            `${scope.value ? `/web/companies/${scope.value.id}/contacts` : `/web/${isClientCompany ? `clients/${clientScope.value ? clientScope.value.id + "/" : ""}companies` : props.entity}`}${selected.value ? "/" + selected.value.id : ""}`,
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
onMounted(async () => {
    try {
        const saved = JSON.parse(localStorage.getItem(columnStorageKey.value) ?? "[]");
        if (Array.isArray(saved)) hiddenColumns.value = saved.map(String);
    } catch {}
    await Promise.all([
        store.start(),
        ...(!isCompany ? [companies.start()] : []),
        ...(clients ? [clients.start()] : []),
    ]);
});
onUnmounted(() => {
    store.stop();
    if (!isCompany) companies.stop();
    clients?.stop();
});

useCardRoute<DirectoryRow>({
    base: isClientCompany ? "/clients/companies" : `/main/${props.entity}`,
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
            <div class="list-heading">
                <div class="content-breadcrumb">
                    {{ isClientCompany ? "Клиенты" : "Администрирование" }} ›
                    {{ title }}
                </div>
                <ClientTabs v-if="isClientCompany" /><AdminTabs v-else />
                <p v-if="clientScope" class="company-scope">
                    Клиент: <strong>{{ clientScope.name }}</strong>
                </p>
                <p v-if="scope" class="company-scope">
                    Компания:
                    <strong>{{
                        companyName(scope.id) === "Компания недоступна"
                            ? scope.name
                            : companyName(scope.id)
                    }}</strong>
                </p>
                <div class="page-heading">
                    <h1>{{ clientScope ? `${title} для клиента ${clientScope.name}` : scope ? `${title} для компании ${scope.name}` : title }}</h1>
                    <div class="page-heading-actions">
                        <DataTransferMenu :rows="filtered" :columns="columnFields" :filename="props.entity" />
                        <button class="primary" :disabled="!online || !ready || saving" @click="open(null)">+ Добавить {{ singular }}</button>
                    </div>
                </div>
                <p class="sync-line" role="status">
                    {{
                        error ||
                        warning ||
                        (!online
                            ? "Нет связи. Доступны сохранённые данные"
                            : syncing
                              ? "Синхронизация…"
                              : "Данные синхронизированы")
                    }}
                </p>
                <p v-if="!isCompany && companies.error.value" class="notice">
                    {{ companies.error.value }}
                </p>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th scope="col" class="id-column">#</th>
                            <th v-if="isColumnVisible('name')">
                                <button
                                    class="sort-button"
                                    @click="descending = !descending"
                                >
                                    {{
                                        isCompany ? "Название компании" : "ФИО"
                                    }}
                                    {{ descending ? "▴" : "▾" }}
                                </button>
                            </th>
                            <th v-if="!isCompany && isColumnVisible('company')">Компания</th>
                            <th v-if="isColumnVisible('detail')">
                                {{ isCompany ? "ИНН" : "Контактное значение" }}
                            </th>
                            <th v-if="isColumnVisible('status')">Статус</th>
                            <template v-if="isCompany">
                                <template v-for="(label, key) in flags" :key="key">
                                    <th v-if="isColumnVisible(key)">{{ label }}</th>
                                </template>
                            </template>
                            <th>Действия <TableColumnSettings v-model:open="columnSettingsOpen" :columns="columnFields" :storage-key="columnStorageKey" /></th>
                        </tr>
                        <tr class="filter-row">
                            <th class="id-column"></th>
                            <th v-if="isColumnVisible('name')">
                                <input
                                    v-model="query"
                                    :aria-label="`Поиск: ${title}`"
                                    placeholder="Поиск"
                                />
                            </th>
                            <th v-if="!isCompany && isColumnVisible('company')">
                                <span v-if="scope">{{ scope.name }}</span>
                                <select
                                    v-else
                                    v-model="companyFilter"
                                    aria-label="Фильтр компании"
                                >
                                    <option value="">Все компании</option>
                                    <option
                                        v-for="company in companyOptions"
                                        :key="company.id"
                                        :value="String(company.id)"
                                    >
                                        {{ company.name }}
                                    </option>
                                </select>
                            </th>
                            <th v-if="isColumnVisible('detail')">
                                <input
                                    v-model="detailQuery"
                                    :aria-label="
                                        isCompany
                                            ? 'Поиск по ИНН'
                                            : 'Поиск по контакту'
                                    "
                                />
                            </th>
                            <th v-if="isColumnVisible('status')">
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
                            <th
                                v-if="isCompany && !isClientCompany"
                                v-show="isColumnVisible('is_own') && isColumnVisible('is_client') && isColumnVisible('is_partner')"
                                colspan="3"
                            >
                                <select
                                    v-model="flagFilter"
                                    aria-label="Тип компании"
                                >
                                    <option value="">Все типы</option>
                                    <option
                                        v-for="(label, key) in flags"
                                        :key="key"
                                        :value="key"
                                    >
                                        {{ label }}
                                    </option>
                                </select>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in visible"
                            :key="row.id"
                            :class="{ 'mobile-card-expanded': expandedMobileRows.has(String(row.id)) }"
                            @click="toggleMobileRow(row.id, $event)"
                            @dblclick="open(row)"
                        >
                            <td class="id-column">{{ row.id }}</td>
                            <td v-if="isColumnVisible('name')">
                                <button
                                    class="name-button"
                                    @click="openName(row, $event)"
                                >
                                    {{ row.name }}
                                </button>
                            </td>
                            <td v-if="!isCompany && isColumnVisible('company')" data-label="Компания">
                                {{ companyName(row.company_id) }}
                            </td>
                            <td v-if="isColumnVisible('detail')" :data-label="isCompany ? 'ИНН' : 'Контактное значение'">
                                {{ (isCompany ? row.inn : row.val) || "—" }}
                            </td>
                            <td v-if="isColumnVisible('status')" data-label="Статус">
                                <span
                                    class="badge"
                                    :class="`status-${row.deleted_at ? 'deleted' : row.status}`"
                                    >{{
                                        row.deleted_at
                                            ? "Удалён"
                                            : (statuses[String(row.status)] ??
                                              "Не указан")
                                    }}</span
                                >
                            </td>
                            <template v-if="isCompany"
                                ><template v-for="(label, key) in flags" :key="key"><td v-if="isColumnVisible(key)">
                                    <img
                                        v-if="
                                            row.src?.[key] === true ||
                                            row.src?.[key] === 1
                                        "
                                        class="flag-tick"
                                        src="/design/crm/tick.svg"
                                        :alt="label"
                                    /></td></template
                            ></template>
                            <td>
                                <div class="row-actions">
                                    <button
                                        v-if="isCompany && !isClientCompany"
                                        :aria-label="`Контактные лица: ${row.name}`"
                                        title="Контактные лица"
                                        :disabled="saving || !!row.deleted_at"
                                        @click="openContacts(row)"
                                    >
                                        <img
                                            src="/design/crm/company_contacts.svg"
                                            alt=""
                                        />
                                    </button>
                                    <button
                                        :aria-label="`Просмотр: ${row.name}`"
                                        title="Просмотр"
                                        @click="open(row, true)"
                                    >
                                        <img
                                            src="/design/crm/view.svg"
                                            alt=""
                                        /></button
                                    ><button
                                        :aria-label="`Редактировать: ${row.name}`"
                                        title="Редактировать"
                                        :disabled="
                                            !!row.deleted_at ||
                                            saving ||
                                            !online
                                        "
                                        @click="open(row)"
                                    >
                                        <img
                                            src="/design/crm/edit.svg"
                                            alt=""
                                        /></button
                                    ><button
                                        :aria-label="`Удалить: ${row.name}`"
                                        title="Удалить"
                                        :disabled="
                                            !!row.deleted_at ||
                                            saving ||
                                            !online
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
                                    isCompany
                                        ? 5 + Object.keys(flags).length
                                        : 6
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
            :message="`Удалить ${singular} ${deleting.name}?`"
            :disabled="!online || !ready || saving"
            @cancel="deleting = null"
            @confirm="confirmDelete"
        />
        <aside v-if="editing" class="editor" aria-label="Карточка записи">
            <header>
                <h2>
                    {{
                        viewing
                            ? "Просмотр"
                            : selected
                              ? "Редактирование"
                              : "Добавление"
                    }}
                    {{ isCompany ? "компании" : "контактного лица"
                    }}<br v-if="selected" />{{ selected?.name }}
                </h2>
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
                        class="directory-form"
                        :disabled="viewing || saving || !online || !!conflict"
                    >
                        <label v-for="(label, key) in labels" :key="key"
                            >{{ label
                            }}<span v-if="['name', 'shortname'].includes(key)">
                                *</span
                            ><DadataInput
                                v-if="
                                    isCompany &&
                                    [
                                        'name',
                                        'inn',
                                        'ogrn',
                                        'bank',
                                        'bik',
                                    ].includes(key)
                                "
                                :key="`${selected?.id ?? 'new'}:${key}`"
                                v-model="form[key]"
                                :type="
                                    ['bank', 'bik'].includes(key)
                                        ? 'bank'
                                        : 'party'
                                "
                                :label="label + (key === 'name' ? ' *' : '')"
                                :required="key === 'name'"
                                :disabled="
                                    viewing || saving || !online || !!conflict
                                "
                                @select="applySuggestion" /><input
                                v-else
                                v-model="form[key]"
                                :type="key === 'email' ? 'email' : 'text'"
                                :required="['name', 'shortname'].includes(key)"
                                maxlength="255"
                        /></label>
                        <label v-if="clients"
                            >Клиент<select
                                v-model="form.client_id"
                                aria-label="Клиент"
                                :disabled="!!clientScope"
                            >
                                <option
                                    v-if="clientScope"
                                    :value="clientScope.id"
                                >
                                    {{ clientScope.name }}
                                </option>
                                <option v-else :value="null">Не выбран</option>
                                <option
                                    v-if="
                                        !clientScope &&
                                        form.client_id &&
                                        !clients.rows.value.some(
                                            (row) =>
                                                !row.deleted_at &&
                                                String(row.id) ===
                                                    String(form.client_id),
                                        )
                                    "
                                    :value="form.client_id"
                                    disabled
                                >
                                    Недоступный клиент №{{ form.client_id }}
                                </option>
                                <option
                                    v-for="row in (clientScope
                                        ? []
                                        : clients.rows.value
                                    ).filter((row) => !row.deleted_at)"
                                    :key="row.id"
                                    :value="row.id"
                                >
                                    {{
                                        row.name ||
                                        row.shortname ||
                                        `Клиент №${row.id}`
                                    }}
                                </option>
                            </select></label
                        >
                        <label v-if="!isCompany"
                            >Компания *<select
                                v-model="form.company_id"
                                aria-label="Компания"
                                :disabled="!!scope"
                                required
                            >
                                <option value="" disabled>
                                    Выберите компанию
                                </option>
                                <option
                                    v-if="
                                        form.company_id &&
                                        !companyOptions.some(
                                            (row) =>
                                                String(row.id) ===
                                                form.company_id,
                                        )
                                    "
                                    :value="form.company_id"
                                    disabled
                                >
                                    {{ companyName(form.company_id) }}
                                </option>
                                <option
                                    v-for="company in companyOptions"
                                    :key="company.id"
                                    :value="String(company.id)"
                                >
                                    {{ company.name }}
                                </option>
                            </select></label
                        >
                        <template v-if="isCompany"
                            ><label v-for="(label, key) in srcLabels" :key="key"
                                >{{ label
                                }}<input
                                    v-model="form.src[key]"
                                    maxlength="255" /></label
                        ></template>
                        <label
                            >Статус<select
                                v-model="form.status"
                                aria-label="Статус"
                            >
                                <option :value="null">Не указан</option>
                                <option :value="0">Новый</option>
                                <option :value="1">Активен</option>
                                <option :value="2">Отключен</option>
                            </select></label
                        >
                        <template v-if="isCompany"
                            ><label
                                v-for="(label, key) in flags"
                                :key="key"
                                class="flag-field"
                                >{{ label
                                }}<input
                                    type="checkbox"
                                    v-model="form.src[key]" /></label
                        ></template>
                        <button v-if="!viewing" class="primary" type="submit">
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
.table-scroll {
    position: relative;
    flex: 1 1 auto;
    min-height: 0;
}
.column-settings-panel {
    position: absolute;
    z-index: 20;
    top: 42px;
    right: 8px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, max-content));
    gap: 8px;
    width: max-content;
    min-width: 220px;
    max-width: min(600px, calc(100vw - 32px));
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
    justify-content: space-between;
    font-size: 12px;
    font-weight: 700;
}
.column-settings-panel--compact {
    grid-template-columns: 1fr;
    width: 260px;
}
.column-settings-close,
.column-settings-button {
    border: 0;
    background: transparent;
    color: #667085;
    cursor: pointer;
}
.column-settings-close {
    font-size: 20px;
    line-height: 1;
}
.column-settings-control {
    display: flex;
    align-items: center;
    gap: 6px;
    min-height: 30px;
    margin: 0;
    cursor: pointer;
}
.column-settings-control input {
    width: 16px;
    height: 16px;
    margin: 0;
    padding: 0;
    accent-color: #2274a5;
}
.column-settings-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    margin-left: 6px;
    font-size: 19px;
    line-height: 1;
}
.column-settings-button:hover,
.column-settings-close:hover {
    color: #2274a5;
}
.company-scope {
    color: #1e892f;
    margin: -12px 0 20px;
    overflow-wrap: anywhere;
}
.list-footer {
    flex-wrap: wrap;
}
.list-footer .refresh-button {
    width: auto;
    font-size: 12px;
    padding: 0 6px;
}
.flag-tick {
    width: 16px;
    height: 16px;
}
.directory-form .flag-field {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.flag-field input {
    width: 24px;
    height: 24px;
    accent-color: #1e892f;
}
.directory-form {
    display: grid;
    gap: 18px;
}
.directory-form label {
    display: grid;
    gap: 6px;
    margin: 0;
}
.directory-form input,
.directory-form select {
    border-color: #a8d4a9;
}
.directory-form label > span {
    display: contents;
}
.editor h2 {
    font-size: 18px;
    font-weight: 500;
    overflow-wrap: anywhere;
}
.name-button {
    text-align: left;
}
.filter-row input,
.filter-row select {
    height: 30px;
    padding: 4px 8px;
    border-color: #a8d4a9;
}
td {
    overflow-wrap: anywhere;
}
</style>
