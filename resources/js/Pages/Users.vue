<script setup lang="ts">
import { useCardRoute } from "../lib/cardRoute";
import { computed, ref, onMounted, onUnmounted, watch } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import ConfirmDelete from "../Components/ConfirmDelete.vue";
import AdminTabs from "../Components/AdminTabs.vue";
import { createUsers } from "../lib/users";
import type { UserRow } from "../lib/cache";
import { http, HttpError, endSession } from "../lib/http";
import TableColumnSettings from "../Components/TableColumnSettings.vue";
import DataTransferMenu from "../Components/DataTransferMenu.vue";
import { createEntitySync } from "../lib/entitySync";
const page = usePage<any>();
const columnSettingsOpen = ref(false);
const columnFields = [
    { key: "name", label: "ФИО" },
    { key: "email", label: "Email" },
    { key: "phone", label: "Телефон" },
    { key: "roles", label: "Роли" },
    { key: "status", label: "Статус" },
];
const store = createUsers(
    `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,
);
const { rows, syncing, ready, online, warning, error } = store;
const rolesStore = createEntitySync<any>(
    `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,
    "roles",
);
const roleRows = rolesStore.rows;
const roleDraft = ref<string[]>([]);
const emailQuery = ref(""),
    phoneQuery = ref("");
const query = ref(""),
    filter = ref("all"),
    currentPage = ref(1),
    sort = ref("name");
const selected = ref<UserRow | null>(null),
    editing = ref(false),
    creating = ref(false),
    saving = ref(false),
    notice = ref(""),
    conflict = ref<UserRow | null>(null),
    passwordMode = ref(false);
const viewing = ref(false);
const expandedMobileRows = ref<Set<string>>(new Set());
function toggleMobileRow(id: string | number, event?: MouseEvent) {
    if (!window.matchMedia('(max-width: 900px)').matches || (event?.detail ?? 0) > 1) return;
    const key = String(id);
    const next = new Set(expandedMobileRows.value);
    if (next.has(key)) next.delete(key); else next.add(key);
    expandedMobileRows.value = next;
}
function handleRowClick(row: UserRow, event: MouseEvent) {
    if (window.matchMedia('(max-width: 900px)').matches) {
        toggleMobileRow(row.id, event);
        return;
    }
    open(row);
}
function handleNameClick(row: UserRow, event: MouseEvent) {
    if (window.matchMedia('(max-width: 900px)').matches) {
        event.stopPropagation();
        toggleMobileRow(row.id, event);
        return;
    }
    open(row);
}
const deleting = ref<UserRow | null>(null);
const deleteMessage = computed(() => {
    const row = deleting.value;
    const name =
        row &&
        [row.last_name, row.name, row.middle_name].filter(Boolean).join(" ");
    return `Удалить пользователя ${name || row?.email || row?.id}?`;
});
const fields = {
    status: 0,
    name: "",
    last_name: "",
    middle_name: "",
    nick: "",
    email: "",
    phone: "",
    password: "",
    password_confirmation: "",
};
const form = ref({ ...fields });
const labels: Record<string, string> = {
    name: "Имя",
    last_name: "Фамилия",
    middle_name: "Отчество",
    nick: "Ник",
    email: "Email",
    phone: "Телефон",
};
const statuses: Record<number, string> = {
    0: "Новый",
    1: "Активен",
    2: "Отключен",
    3: "Выполняется действие",
};
const filtered = computed(() => {
    const q = query.value.toLocaleLowerCase("ru");
    return rows.value
        .filter((row) => {
            if (filter.value === "deleted" ? !row.deleted_at : !!row.deleted_at)
                return false;
            if (
                ["0", "1", "2", "3"].includes(filter.value) &&
                row.status !== Number(filter.value)
            )
                return false;
            if (
                !(row.email ?? "")
                    .toLowerCase()
                    .includes(emailQuery.value.toLowerCase()) ||
                !(row.phone ?? "").includes(phoneQuery.value)
            )
                return false;
            return [
                row.name,
                row.last_name,
                row.middle_name,
                row.nick,
                row.email,
                row.phone,
            ].some((v) => (v ?? "").toLocaleLowerCase("ru").includes(q));
        })
        .sort((a, b) =>
            String(
                sort.value === "email"
                    ? a.email
                    : `${a.last_name ?? ""} ${a.name ?? ""}`,
            ).localeCompare(
                String(
                    sort.value === "email"
                        ? b.email
                        : `${b.last_name ?? ""} ${b.name ?? ""}`,
                ),
                "ru",
            ),
        );
});
const pages = computed(() =>
    Math.max(1, Math.ceil(filtered.value.length / 25)),
);
const visible = computed(() =>
    filtered.value.slice((currentPage.value - 1) * 25, currentPage.value * 25),
);
watch([query, emailQuery, phoneQuery, filter], () => {
    currentPage.value = 1;
});
watch(pages, (n) => {
    currentPage.value = Math.min(currentPage.value, n);
});
function open(row: UserRow | null, forPassword = false, readOnly = false) {
    viewing.value = readOnly;
    selected.value = row;
    creating.value = !row;
    editing.value = true;
    conflict.value = null;
    notice.value = "";
    passwordMode.value = forPassword;
    form.value = {
        ...fields,
        status: row?.status ?? 0,
        ...Object.fromEntries(
            Object.keys(labels).map((k) => [
                k,
                row?.[k as keyof UserRow] ?? "",
            ]),
        ),
    };
    roleDraft.value = (row?.roles ?? []).map((role) => String(role.id));
}
const roleOptions = computed(() =>
    roleRows.value
        .filter((role: any) => role.status === 1 && !role.deleted_at)
        .map((role: any) => ({
            id: String(role.id),
            name: role.name || role.slug || `Роль №${role.id}`,
        })),
);
function close() {
    if (!saving.value) {
        editing.value = false;
        form.value = { ...fields };
        conflict.value = null;
    }
}
function deleteUser(row: UserRow) {
    if (
        !online.value ||
        saving.value ||
        row.deleted_at ||
        row.id === page.props.auth.id
    )
        return;
    deleting.value = row;
}
async function confirmDelete() {
    if (!deleting.value || !online.value || saving.value) return;
    const row = deleting.value;
    deleting.value = null;
    open(row);
    await save("delete");
}
async function save(action = "update") {
    if (!online.value || saving.value || viewing.value) return;
    const wasCreating = creating.value && action === "update";
    saving.value = true;
    notice.value = "";
    conflict.value = null;
    try {
        let body: Record<string, unknown>;
        if (creating.value) body = { ...form.value };
        else if (action === "update")
            body = {
                ...Object.fromEntries(
                    Object.keys(labels).map((k) => [
                        k,
                        form.value[k as keyof typeof fields],
                    ]),
                ),
                version: selected.value!.version,
                status: form.value.status,
            };
        else
            body = {
                version: selected.value!.version,
                ...(action === "password"
                    ? {
                          password: form.value.password,
                          password_confirmation:
                              form.value.password_confirmation,
                      }
                    : {}),
            };
        const url = creating.value
            ? "/web/users"
            : `/web/users/${selected.value!.id}${action === "update" ? "" : `/${action}`}`;
        const result = await http(
            url,
            creating.value || action !== "update" ? "POST" : "PUT",
            body,
        );
        let savedData = result.data;
        await store.apply(savedData);
        if (action === "update" || creating.value) {
            const rolesResult = await http(
                `/web/users/${savedData.id}/roles`,
                "POST",
                {
                    role_ids: roleDraft.value.map(Number),
                    version: savedData.version,
                },
            );
            savedData = rolesResult.data;
            await store.apply(savedData);
        }
        if (wasCreating) {
            saving.value = false;
            open(null);
            notice.value = "Пользователь создан. Можно добавить следующего.";
            return;
        }
        selected.value = savedData;
        form.value = {
            ...fields,
            status: savedData.status,
            ...Object.fromEntries(
                Object.keys(labels).map((key) => [key, savedData[key] ?? ""]),
            ),
        };
        creating.value = false;
        form.value.password = "";
        form.value.password_confirmation = "";
        notice.value = "Изменения сохранены";
        if (action === "password" && savedData.id === page.props.auth.id) {
            await endSession();
        }
        if (action === "delete") editing.value = false;
    } catch (e) {
        if (e instanceof HttpError) {
            notice.value = e.body.errors
                ? Object.values(e.body.errors).flat().join(" ")
                : e.message;
            if (e.status === 409 && e.body.current) {
                conflict.value = e.body.current;
                await store.apply(e.body.current);
            }
        } else {
            notice.value =
                "Не получено подтверждение сервера. Обновите данные перед повторной попыткой.";
        }
    } finally {
        saving.value = false;
    }
}
function reviewConflict() {
    if (!conflict.value) return;
    selected.value = conflict.value;
    conflict.value = null;
    notice.value =
        "Версия обновлена. Ваши поля сохранены в форме — проверьте их перед повторным сохранением.";
}
onMounted(() => {
    void store.start();
    void rolesStore.start();
});
onUnmounted(() => {
    store.stop();
    rolesStore.stop();
});

useCardRoute<UserRow>({
    base: "/main/users",
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
                        : passwordMode.value
                          ? "password"
                          : viewing.value
                            ? "view"
                            : "edit",
                }
              : null,
    open: (row, action) => {
        if (action === "delete" && row) deleting.value = row;
        else open(row, action === "password", action === "view");
    },
    close: () => {
        close();
        deleting.value = null;
    },
    missing: () => {
        error.value = "Запись недоступна или ещё не загружена.";
    },
});
</script>
<template>
    <Head title="Пользователи" />
    <div class="users-workspace" :class="{ 'has-editor': editing }">
        <section class="users-list">
            <div class="content-breadcrumb">
                Администрирование › Пользователи
            </div>
            <AdminTabs />
            <div class="page-heading">
                <h1>Пользователи</h1>
                <div class="page-heading-actions">
                    <DataTransferMenu
                        :rows="visible"
                        :columns="columnFields"
                        filename="users"
                    />
                    <button
                        class="primary"
                        :disabled="!online || !ready"
                        @click="open(null)"
                    >
                        ＋ <span>Добавить пользователя</span>
                    </button>
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
                                    ФИО <span class="sort-arrow">▾</span>
                                </button>
                            </th>
                            <th>
                                <button @click="sort = 'email'">
                                    Email <span class="sort-arrow">▾</span>
                                </button>
                            </th>
                            <th>Телефон</th>
                            <th>Роли</th>
                            <th>Статус</th>
                            <th>
                                Действия
                                <TableColumnSettings
                                    v-model:open="columnSettingsOpen"
                                    :columns="columnFields"
                                    storage-key="users-columns"
                                />
                            </th>
                        </tr>
                        <tr class="column-filters">
                            <th class="id-column"></th>
                            <th>
                                <input
                                    aria-label="Поиск пользователей"
                                    v-model="query"
                                />
                            </th>
                            <th>
                                <input
                                    aria-label="Поиск по email"
                                    v-model="emailQuery"
                                />
                            </th>
                            <th>
                                <input
                                    aria-label="Поиск по телефону"
                                    v-model="phoneQuery"
                                />
                            </th>
                            <th>
                                <span></span>
                            </th>
                            <th>
                                <select
                                    aria-label="Фильтр статуса"
                                    v-model="filter"
                                >
                                    <option value="all">Все</option>
                                    <option value="0">Новые</option>
                                    <option value="1">Активные</option>
                                    <option value="2">Отключенные</option>
                                    <option value="3">Действие</option>
                                    <option value="deleted">Удалённые</option>
                                </select>
                            </th>
                            <th>
                                <button
                                    class="table-refresh"
                                    @click="store.sync"
                                    :disabled="syncing || !online"
                                    aria-label="Обновить данные"
                                >
                                    ↻
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in visible"
                            :key="row.id"
                            :class="{
                                'mobile-card-expanded': expandedMobileRows.has(String(row.id)),
                                selected: selected?.id === row.id && editing,
                            }"
                            @click="handleRowClick(row, $event)"
                        >
                            <td class="id-column">{{ row.id }}</td>
                            <td>
                                <button
                                    class="user-cell name-button"
                                    @click.stop="handleNameClick(row, $event)"
                                >
                                    {{
                                        [
                                            row.last_name,
                                            row.name,
                                            row.middle_name,
                                        ]
                                            .filter(Boolean)
                                            .join(" ") || row.email
                                    }}
                                </button>
                            </td>
                            <td data-label="Почта">{{ row.email || "—" }}</td>
                            <td data-label="Телефон">{{ row.phone || "—" }}</td>
                            <td
                                class="user-roles-cell"
                                data-label="Роли"
                                @click.stop
                            >
                                <div class="role-tags">
                                    <span
                                        v-for="role in row.roles ?? []"
                                        :key="role.id"
                                        class="role-tag"
                                    >
                                        {{
                                            role.name ||
                                            role.slug ||
                                            `Роль №${role.id}`
                                        }}
                                    </span>
                                    <span
                                        v-if="!(row.roles ?? []).length"
                                        class="roles-placeholder"
                                        >—</span
                                    >
                                </div>
                            </td>
                            <td data-label="Статус">
                                <span
                                    class="badge"
                                    :class="`status-${row.deleted_at ? 'deleted' : row.status}`"
                                    >{{
                                        row.deleted_at
                                            ? "Удалён"
                                            : (statuses[row.status ?? -1] ??
                                              "Неизвестен")
                                    }}</span
                                >
                            </td>
                            <td>
                                <div class="row-actions">
                                    <button
                                        @click.stop="open(row, false, true)"
                                        aria-label="Открыть пользователя"
                                    >
                                        <img
                                            src="/design/crm/view.svg"
                                            alt=""
                                        /></button
                                    ><button
                                        @click.stop="open(row)"
                                        aria-label="Редактировать пользователя"
                                    >
                                        <img
                                            src="/design/crm/edit.svg"
                                            alt=""
                                        />
                                    </button>
                                    <button
                                        @click.stop="open(row, true)"
                                        aria-label="Установить пароль"
                                        title="Установить пароль"
                                        :disabled="
                                            !online ||
                                            saving ||
                                            !!row.deleted_at
                                        "
                                    >
                                        <img src="/design/crm/key.svg" alt="" />
                                    </button>
                                    <button
                                        @click.stop="deleteUser(row)"
                                        aria-label="Удалить пользователя"
                                        title="Удалить пользователя"
                                        :disabled="
                                            !online ||
                                            saving ||
                                            !!row.deleted_at ||
                                            row.id === page.props.auth.id
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
                    </tbody>
                </table>
                <div v-if="!visible.length" class="empty-state">
                    <h2>
                        {{
                            syncing
                                ? "Загружаем пользователей"
                                : "Пользователи не найдены"
                        }}
                    </h2>
                    <p>
                        {{
                            query
                                ? "Измените поисковый запрос или фильтр"
                                : "Здесь появятся пользователи вашей организации"
                        }}
                    </p>
                </div>
            </div>
            <footer class="list-footer">
                <span>Найдено: {{ filtered.length }}</span>
                <div>
                    <button
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
            :message="deleteMessage"
            :disabled="!online || saving"
            @cancel="deleting = null"
            @confirm="confirmDelete"
        />
        <aside
            v-if="editing"
            class="editor"
            :aria-label="
                passwordMode ? 'Установка пароля' : 'Карточка пользователя'
            "
        >
            <header>
                <div>
                    <small>{{
                        creating
                            ? "НОВАЯ ЗАПИСЬ"
                            : `ПОЛЬЗОВАТЕЛЬ · ${selected?.id}`
                    }}</small>
                    <h2>
                        {{
                            creating
                                ? "Добавить пользователя"
                                : passwordMode
                                  ? "Установить пароль"
                                  : "Карточка пользователя"
                        }}
                    </h2>
                </div>
                <button
                    @click="close"
                    :disabled="saving"
                    aria-label="Закрыть карточку"
                >
                    ×
                </button>
            </header>
            <div class="editor-content">
                <div v-if="selected" class="profile-summary">
                    <span class="avatar large">{{
                        (selected.name || "?").slice(0, 1)
                    }}</span>
                    <div>
                        <strong>{{ selected.name }}</strong
                        ><span
                            class="badge"
                            :class="`status-${selected.deleted_at ? 'deleted' : selected.status}`"
                            >{{
                                selected.deleted_at
                                    ? "Удалён"
                                    : statuses[selected.status ?? -1]
                            }}</span
                        >
                    </div>
                </div>
                <p v-if="!online" class="notice">
                    Нет связи. Изменения недоступны.
                </p>
                <p v-if="notice" class="notice" role="status">{{ notice }}</p>
                <div v-if="conflict" class="notice error">
                    <strong>Запись изменена другим пользователем</strong>
                    <p>
                        На сервере: {{ conflict.name }}, {{ conflict.email }},
                        {{ conflict.phone || "без телефона" }}.
                    </p>
                    <button @click="reviewConflict">
                        Использовать актуальную версию</button
                    ><button @click="open(conflict, passwordMode)">
                        Загрузить поля с сервера
                    </button>
                </div>
                <form
                    @submit.prevent="save(passwordMode ? 'password' : 'update')"
                >
                    <fieldset
                        :disabled="
                            viewing ||
                            !online ||
                            saving ||
                            !!conflict ||
                            !!selected?.deleted_at
                        "
                    >
                        <div v-if="!passwordMode" class="form-grid">
                            <label v-for="(label, key) in labels" :key="key"
                                >{{ label
                                }}<span
                                    v-if="['name', 'email'].includes(key)"
                                    class="required"
                                >
                                    *</span
                                ><input
                                    v-model="form[key as keyof typeof fields]"
                                    :type="key === 'email' ? 'email' : 'text'"
                                    :required="['name', 'email'].includes(key)"
                                    maxlength="255"
                            /></label>
                            <label
                                >Статус<select
                                    v-model="form.status"
                                    aria-label="Статус пользователя"
                                    required
                                >
                                    <option
                                        v-if="![0, 1, 2].includes(form.status)"
                                        :value="form.status"
                                        disabled
                                    >
                                        Выберите статус
                                    </option>
                                    <option :value="0">Новый</option>
                                    <option :value="1">Активен</option>
                                    <option :value="2">Отключен</option>
                                </select></label
                            >
                            <div class="roles-form-field">
                                <span class="roles-form-label">Роли</span>
                                <VSelect
                                    class="roles-tagbox-input"
                                    v-model="roleDraft"
                                    :items="roleOptions"
                                    item-title="name"
                                    item-value="id"
                                    multiple
                                    chips
                                    closable-chips
                                    density="compact"
                                    variant="outlined"
                                    hide-details
                                    placeholder="Выберите роли"
                                />
                            </div>
                        </div>
                        <template v-if="creating || passwordMode"
                            ><label
                                >Пароль<input
                                    v-model="form.password"
                                    type="password"
                                    minlength="12"
                                    maxlength="1024"
                                    required
                                    autocomplete="new-password" /></label
                            ><label
                                >Подтверждение пароля<input
                                    v-model="form.password_confirmation"
                                    type="password"
                                    required
                                    autocomplete="new-password" /></label
                            ><small>Не менее 12 символов</small></template
                        >
                        <p v-if="creating && form.status !== 1" class="muted">
                            Для входа пользователю потребуется статус «Активен».
                        </p>
                        <button
                            v-if="!viewing"
                            type="submit"
                            class="primary save-button"
                        >
                            {{
                                saving
                                    ? "Сохраняем…"
                                    : creating
                                      ? "Создать пользователя"
                                      : passwordMode
                                        ? "Установить пароль"
                                        : "Сохранить изменения"
                            }}
                        </button>
                    </fieldset>
                </form>
                <div
                    v-if="selected && !creating && !passwordMode && !viewing"
                    class="user-actions"
                >
                    <h3>Действия</h3>
                    <template v-if="!selected.deleted_at"
                        ><button
                            v-if="selected.status !== 1"
                            @click="save('activate')"
                            :disabled="!online || saving || !!conflict"
                        >
                            Активировать
                        </button></template
                    ><button
                        v-else
                        @click="save('restore')"
                        :disabled="!online || saving || !!conflict"
                    >
                        Восстановить пользователя
                    </button>
                </div>
            </div>
        </aside>
    </div>
</template>

<style scoped>
.user-roles-cell {
    min-width: 220px;
    vertical-align: top;
    cursor: default;
}
.role-tags {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    min-height: 28px;
}
.role-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 7px;
    border: 1px solid #a8d4a9;
    border-radius: 999px;
    background: #e1f3e7;
    color: #176b27;
    font-size: 12px;
    line-height: 1.2;
}
.role-tag button {
    padding: 0;
    border: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
    font-size: 15px;
    line-height: 1;
}
.roles-placeholder {
    color: #79848d;
}
.roles-form-field {
    min-width: 240px;
}
.roles-form-label {
    display: block;
    margin-bottom: 6px;
    font-size: 12px;
    font-weight: 500;
}
.roles-form-field .roles-tagbox-input {
    width: 100%;
}
.roles-form-field .roles-tagbox-input :deep(.v-field) {
    border-radius: 5px;
    background: #fff;
}
.roles-form-field .roles-tagbox-input :deep(.v-chip) {
    background: #e1f3e7;
    color: #176b27;
    font-size: 12px;
}
</style>
