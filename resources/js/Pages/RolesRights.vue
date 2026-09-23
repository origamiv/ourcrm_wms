<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import AdminTabs from "../Components/AdminTabs.vue";
import { createEntitySync } from "../lib/entitySync";
import { http, HttpError } from "../lib/http";
import type { EntityRow } from "../lib/cache";
import FilterPresetButton from "../Components/FilterPresetButton.vue";
import FilterPresetTiles from "../Components/FilterPresetTiles.vue";
import {
    applyTableFilter,
    useFilterPresets,
    type FilterField,
} from "../lib/tableFilters";
interface RoleRow extends EntityRow {
    name: string;
    slug: string;
    status: number | null;
    deleted_at: string | null;
}
interface PermissionRow extends RoleRow {
    resource: string;
}
interface Assignment extends EntityRow {
    role_id: number | string;
    permission_id: number | string;
    status: number | null;
    deleted_at: string | null;
}
const page = usePage<any>();
const advancedFilters = useFilterPresets("roles_rights");
const advancedFields: FilterField[] = [
    { id: "id", label: "#", type: "number" },
    { id: "name", label: "Право", type: "text" },
    { id: "slug", label: "Код", type: "text" },
    { id: "resource", label: "Ресурс", type: "text" },
];
const scope = `${page.props.cacheVersion}:${page.props.auth.id}:${page.props.auth.tenant_id}`;
const roles = createEntitySync<RoleRow>(scope, "roles");
const permissions = createEntitySync<PermissionRow>(scope, "permissions");
const assignments = createEntitySync<Assignment>(scope, "permission_roles");
const filterOptionRows = permissions.rows;
const stores = [roles, permissions, assignments];
const ready = computed(() => stores.every((store) => store.ready.value));
const online = computed(() => stores.every((store) => store.online.value));
const syncing = computed(() => stores.some((store) => store.syncing.value));
const notices = computed(() => [
    ...new Set(
        stores
            .flatMap((store) => [store.error.value, store.warning.value])
            .filter(Boolean),
    ),
]);
const query = ref(""),
    message = ref(""),
    failed = ref(false),
    saving = ref<string | null>(null);
const expanded = ref(new Set<string>());
const activeRoles = computed(() =>
    roles.rows.value
        .filter((row) => row.status === 1 && !row.deleted_at)
        .sort((a, b) => a.name.localeCompare(b.name, "ru")),
);
const filteredPermissions = computed(() =>
    applyTableFilter(permissions.rows.value, advancedFilters.combined.value),
);
const groups = computed(() => {
    const map = new Map<string, PermissionRow[]>();
    const q = query.value.trim().toLocaleLowerCase("ru");
    for (const row of filteredPermissions.value) {
        if (row.deleted_at || row.status !== 1) continue;
        if (
            q &&
            ![row.name, row.slug, row.resource].some((value) =>
                (value ?? "").toLocaleLowerCase("ru").includes(q),
            )
        )
            continue;
        const group = row.resource || "Общие права";
        if (!map.has(group)) map.set(group, []);
        map.get(group)!.push(row);
    }
    return [...map.entries()]
        .sort(([a], [b]) => a.localeCompare(b, "ru"))
        .map(([name, rows]) => ({
            name,
            rows: rows.sort((a, b) => a.name.localeCompare(b.name, "ru")),
        }));
});
const cells = computed(() => {
    const result = new Map<string, { enabled: boolean; version: string }>();
    for (const row of assignments.rows.value) {
        const key = `${row.role_id}:${row.permission_id}`;
        const cell = result.get(key) ?? { enabled: false, version: "0" };
        cell.enabled ||= row.status === 1 && !row.deleted_at;
        if (BigInt(row.version) > BigInt(cell.version))
            cell.version = row.version;
        result.set(key, cell);
    }
    return result;
});
const cell = (role: string, permission: string) =>
    cells.value.get(`${role}:${permission}`) ?? {
        enabled: false,
        version: "0",
    };
function toggleGroup(name: string) {
    const next = new Set(expanded.value);
    if (next.has(name)) next.delete(name);
    else next.add(name);
    expanded.value = next;
}
async function update(role: RoleRow, permission: PermissionRow, event: Event) {
    const input = event.target as HTMLInputElement;
    const current = cell(role.id, permission.id);
    const enabled = input.checked;
    input.checked = current.enabled;
    if (!ready.value || !online.value || saving.value) return;
    saving.value = `${role.id}:${permission.id}`;
    message.value = "";
    failed.value = false;
    try {
        const response = await http(
            `/web/roles/${role.id}/permissions/${permission.id}`,
            "PUT",
            { enabled, version: current.version },
        );
        for (const row of response.data) await assignments.apply(row);
        message.value = "Изменения сохранены";
    } catch (error) {
        failed.value = true;
        message.value =
            error instanceof Error
                ? error.message
                : "Не удалось сохранить назначение";
        if (error instanceof HttpError && error.status === 409)
            await assignments.sync();
    } finally {
        saving.value = null;
    }
}
async function refresh() {
    await Promise.all(stores.map((store) => store.sync()));
}
onMounted(async () => {
    await Promise.all(stores.map((store) => store.start()));
    if (groups.value[0]) expanded.value = new Set([groups.value[0].name]);
});
onUnmounted(() => stores.forEach((store) => store.stop()));
</script>
<template>
    <Head title="Роли и права" />
    <div class="users-workspace rights-workspace">
        <section class="users-list">
            <div class="content-breadcrumb">
                Администрирование › Роли и права
            </div>
            <AdminTabs />
            <div class="page-heading">
                <h1>Роли и права</h1>
                <FilterPresetButton
                    :state="advancedFilters"
                    :fields="advancedFields"
                    :rows="filterOptionRows"
                />
                <button
                    class="primary"
                    :disabled="!online || !!saving"
                    @click="router.visit('/main/roles/0/create')"
                >
                    Добавить роль
                </button>
            </div>
            <div class="sync-line" role="status">
                {{
                    syncing
                        ? "Загружаем изменения…"
                        : !online
                          ? "Нет связи · сохранённые данные"
                          : ready
                            ? "Данные синхронизированы"
                            : "Первичная загрузка…"
                }}
            </div>
            <p
                v-for="notice in notices"
                :key="notice"
                class="notice error"
                role="alert"
            >
                {{ notice }}
            </p>
            <p
                v-if="message"
                class="notice"
                :class="{ error: failed }"
                :role="failed ? 'alert' : 'status'"
            >
                {{ message }}
            </p>
            <FilterPresetTiles :state="advancedFilters" />
            <div class="matrix-tools">
                <input
                    v-model="query"
                    aria-label="Поиск прав в матрице"
                    placeholder="Поиск по правам и ресурсам"
                /><button
                    class="text-button"
                    :disabled="!online || syncing || !!saving"
                    @click="refresh"
                >
                    Обновить
                </button>
            </div>
            <div v-if="ready && !activeRoles.length" class="notice">
                Нет активных ролей. Создайте или активируйте роль в разделе
                «Роли».
            </div>
            <div v-else class="table-scroll matrix-scroll">
                <table class="rights-matrix">
                    <thead>
                        <tr>
                            <th scope="col" class="id-column">#</th>
                            <th scope="col">Право / Роль</th>
                            <th
                                v-for="role in activeRoles"
                                :key="role.id"
                                scope="col"
                            >
                                {{ role.name }}
                            </th>
                        </tr>
                    </thead>
                    <tbody v-for="group in groups" :key="group.name">
                        <tr class="matrix-group">
                            <th :colspan="activeRoles.length + 2">
                                <button
                                    :aria-expanded="
                                        query ? true : expanded.has(group.name)
                                    "
                                    @click="toggleGroup(group.name)"
                                >
                                    <span
                                        class="group-toggle"
                                        aria-hidden="true"
                                        >{{
                                            query || expanded.has(group.name)
                                                ? "−"
                                                : "+"
                                        }}</span
                                    >{{ group.name
                                    }}<small>{{ group.rows.length }}</small>
                                </button>
                            </th>
                        </tr>
                        <template v-if="query || expanded.has(group.name)">
                            <tr
                                v-for="permission in group.rows"
                                :key="permission.id"
                            >
                                <td class="id-column">{{ permission.id }}</td>
                                <th scope="row">{{ permission.name }}</th>
                                <td v-for="role in activeRoles" :key="role.id">
                                    <input
                                        type="checkbox"
                                        :aria-label="`${role.name}: ${permission.name}`"
                                        :checked="
                                            cell(role.id, permission.id).enabled
                                        "
                                        :disabled="
                                            !ready || !online || !!saving
                                        "
                                        :aria-busy="
                                            saving ===
                                            `${role.id}:${permission.id}`
                                        "
                                        @change="
                                            update(role, permission, $event)
                                        "
                                    />
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <p v-if="ready && !groups.length" class="notice">
                    {{
                        query ? "Права не найдены" : "Нет активных прав доступа"
                    }}
                </p>
            </div>
        </section>
    </div>
</template>
<style scoped>
.matrix-tools {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 8px 0 16px;
}
.matrix-tools input {
    max-width: 360px;
}
.matrix-scroll {
    overflow: auto;
}
.rights-matrix {
    border-collapse: collapse;
    min-width: 600px;
    width: 100%;
}
.rights-matrix thead th {
    padding: 14px 18px;
    color: #0c1821;
    background: white;
    font-weight: 500;
    position: sticky;
    top: 0;
    z-index: 1;
}
.rights-matrix th:nth-child(2) {
    min-width: 230px;
    width: 30%;
    text-align: left;
}
.rights-matrix td {
    min-width: 150px;
    text-align: center;
    padding: 18px;
}
.rights-matrix thead th:nth-child(n + 3) {
    text-align: center;
    min-width: 150px;
}
.rights-matrix tbody th[scope="row"] {
    padding: 18px 18px 18px 39px;
    font-size: 12px;
    line-height: 1.5;
    font-weight: 400;
    white-space: normal;
}
.matrix-group th {
    background: #ececec;
    border-block: 1px solid #eee;
    padding: 9px 8px;
}
.matrix-group button {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 18px;
    text-align: left;
    font-weight: 600;
}
.matrix-group small {
    margin-left: auto;
}
.group-toggle {
    display: grid;
    place-items: center;
    width: 16px;
    height: 16px;
    border: 1px solid #2274a5;
    border-radius: 2px;
    color: #2274a5;
    font-weight: 400;
}
.rights-matrix input[type="checkbox"] {
    appearance: auto;
    width: 23px;
    height: 23px;
    padding: 0;
    margin: 0;
    accent-color: #2274a5;
    cursor: pointer;
}
.rights-matrix input:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}
</style>
