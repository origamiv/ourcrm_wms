<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import { createUsers } from "../lib/users";
import type { UserRow } from "../lib/cache";
import { http, HttpError, endSession } from "../lib/http";
const page = usePage<any>();
const store = createUsers(
    `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`,
);
const { rows, syncing, ready, online, warning, error } = store;
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
const fields = {
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
    2: "Блокирован",
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
function open(row: UserRow | null) {
    selected.value = row;
    creating.value = !row;
    editing.value = true;
    conflict.value = null;
    notice.value = "";
    passwordMode.value = false;
    form.value = {
        ...fields,
        ...Object.fromEntries(
            Object.keys(labels).map((k) => [
                k,
                row?.[k as keyof UserRow] ?? "",
            ]),
        ),
    };
}
function close() {
    if (!saving.value) {
        editing.value = false;
        form.value = { ...fields };
        conflict.value = null;
    }
}
async function save(action = "update") {
    if (!online.value || saving.value) return;
    if (
        ["delete", "block"].includes(action) &&
        !window.confirm(
            action === "delete"
                ? "Удалить пользователя? Его можно будет восстановить."
                : "Заблокировать вход пользователя?",
        )
    )
        return;
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
        await store.apply(result.data);
        selected.value = result.data;
        form.value = {
            ...fields,
            ...Object.fromEntries(
                Object.keys(labels).map((key) => [key, result.data[key] ?? ""]),
            ),
        };
        creating.value = false;
        passwordMode.value = false;
        form.value.password = "";
        form.value.password_confirmation = "";
        notice.value = "Изменения сохранены";
        if (action === "password" && result.data.id === page.props.auth.id) {
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
onMounted(store.start);
onUnmounted(store.stop);
</script>
<template>
    <Head title="Пользователи" />
    <div class="users-workspace" :class="{ 'has-editor': editing }">
        <section class="users-list">
            <div class="content-breadcrumb">
                Администрирование › Пользователи
            </div>
            <div class="module-tabs">
                <span class="module-tab active"
                    ><img
                        src="/design/crm/contacts.svg"
                        alt=""
                    />Пользователи</span
                >
            </div>
            <div class="page-heading">
                <h1>Пользователи</h1>
                <button
                    class="primary"
                    :disabled="!online || !ready"
                    @click="open(null)"
                >
                    ＋ <span>Добавить пользователя</span>
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
                                    ФИО <span class="sort-arrow">▾</span>
                                </button>
                            </th>
                            <th>
                                <button @click="sort = 'email'">
                                    Email <span class="sort-arrow">▾</span>
                                </button>
                            </th>
                            <th>Телефон</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                        <tr class="column-filters">
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
                                <select
                                    aria-label="Фильтр статуса"
                                    v-model="filter"
                                >
                                    <option value="all">Все</option>
                                    <option value="0">Новые</option>
                                    <option value="1">Активные</option>
                                    <option value="2">Блокированные</option>
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
                                selected: selected?.id === row.id && editing,
                            }"
                            @click="open(row)"
                        >
                            <td>
                                <button
                                    class="user-cell"
                                    @click.stop="open(row)"
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
                            <td>{{ row.email || "—" }}</td>
                            <td>{{ row.phone || "—" }}</td>
                            <td>
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
                                        @click.stop="open(row)"
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
        <aside v-if="editing" class="editor" aria-label="Карточка пользователя">
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
                    ><button @click="open(conflict)">
                        Загрузить поля с сервера
                    </button>
                </div>
                <form
                    @submit.prevent="save(passwordMode ? 'password' : 'update')"
                >
                    <fieldset
                        :disabled="
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
                        <p v-if="creating" class="muted">
                            Пользователь будет создан в статусе «Новый». Для
                            входа потребуется активация.
                        </p>
                        <button type="submit" class="primary save-button">
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
                <div v-if="selected && !creating" class="user-actions">
                    <h3>Действия</h3>
                    <template v-if="!selected.deleted_at"
                        ><button
                            @click="passwordMode = !passwordMode"
                            :disabled="!online || saving"
                        >
                            {{
                                passwordMode
                                    ? "Вернуться к профилю"
                                    : "Установить пароль"
                            }}</button
                        ><button
                            v-if="selected.status !== 1"
                            @click="save('activate')"
                            :disabled="!online || saving || !!conflict"
                        >
                            Активировать</button
                        ><button
                            v-if="selected.status !== 2"
                            @click="save('block')"
                            :disabled="
                                !online ||
                                saving ||
                                selected.id === page.props.auth.id ||
                                !!conflict
                            "
                        >
                            Блокировать</button
                        ><button
                            class="danger"
                            @click="save('delete')"
                            :disabled="
                                !online ||
                                saving ||
                                selected.id === page.props.auth.id ||
                                !!conflict
                            "
                        >
                            <img
                                class="action-icon"
                                src="/design/crm/delete.svg"
                                alt=""
                            />Удалить пользователя
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
