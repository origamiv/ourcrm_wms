import { usePage } from "@inertiajs/vue3";
import {
    computed,
    onBeforeUnmount,
    ref,
    toValue,
    watch,
    type MaybeRefOrGetter,
    type Ref,
} from "vue";
import type { FilterField } from "./tableFilters";

export type TableSearchMode = "filter" | "highlight";

export interface TableSearchState {
    query: Ref<string>;
    debouncedQuery: Ref<string>;
    expanded: Ref<boolean>;
    mode: Ref<TableSearchMode>;
    selectedFields: Ref<string[]>;
    currentId: Ref<string | null>;
    active: Readonly<Ref<boolean>>;
    availableFields: Readonly<Ref<FilterField[]>>;
    matches: (row: unknown) => boolean;
    apply: <T>(rows: T[]) => T[];
    matching: <T>(rows: T[]) => T[];
    isCurrent: (row: unknown) => boolean;
    identify: (row: unknown) => string;
    selectFields: (ids: string[]) => void;
    navigate: <T>(rows: T[], direction: -1 | 1) => T | null;
    resetNavigation: () => void;
}

export function appendTableSearch(
    params: URLSearchParams,
    state: TableSearchState,
): URLSearchParams {
    if (!state.active.value) return params;
    params.set("search", state.debouncedQuery.value);
    params.set("search_mode", state.mode.value);
    for (const field of state.selectedFields.value)
        params.append("search_fields[]", field);
    return params;
}

export function valueAtPath(row: unknown, path: string): unknown {
    return path.split(".").reduce<unknown>((value, key) => {
        if (Array.isArray(value))
            return value.flatMap((item) => {
                const nested = valueAtPath(item, key);
                return Array.isArray(nested) ? nested : [nested];
            });
        return value && typeof value === "object"
            ? (value as Record<string, unknown>)[key]
            : null;
    }, row);
}

function searchableValues(value: unknown): unknown[] {
    if (Array.isArray(value)) return value.flatMap(searchableValues);
    if (value && typeof value === "object")
        return Object.values(value).flatMap(searchableValues);
    return [value];
}

export function rowMatchesSearch(
    row: unknown,
    query: string,
    fields: FilterField[],
    selectedFields: string[],
): boolean {
    const needle = query.trim().toLocaleLowerCase("ru");
    if (needle.length < 3) return false;
    const selected = new Set(selectedFields);
    return fields.some((field) => {
        if (!selected.has(field.id)) return false;
        return searchableValues(valueAtPath(row, field.id)).some((value) => {
            const raw = value == null ? "" : String(value);
            let formatted = raw;
            if (typeof field.format === "function") {
                try {
                    formatted = String(field.format(value as never));
                } catch {
                    formatted = raw;
                }
            } else if (field.type === "date" && raw) {
                const date = new Date(raw);
                if (!Number.isNaN(date.getTime()))
                    formatted = new Intl.DateTimeFormat("ru-RU", {
                        day: "2-digit",
                        month: "2-digit",
                        year: "2-digit",
                        hour: "2-digit",
                        minute: "2-digit",
                        hour12: false,
                    }).format(date);
            }
            return `${raw}\n${formatted}`
                .toLocaleLowerCase("ru")
                .includes(needle);
        });
    });
}

export function useTableSearch(
    screenKey: string,
    fields: MaybeRefOrGetter<FilterField[]>,
): TableSearchState {
    const page = usePage<any>();
    const query = ref("");
    const debouncedQuery = ref("");
    const expanded = ref(false);
    const mode = ref<TableSearchMode>("filter");
    const currentId = ref<string | null>(null);
    const availableFields = computed(() => toValue(fields));
    const storageKey = `table-search-fields:${page.props.auth?.id ?? "guest"}:${page.props.auth?.tenant_id ?? "global"}:${screenKey}`;
    const defaults = () => {
        const ids = availableFields.value.map((field) => field.id);
        return ["name", "shortname", "id"].filter((id) => ids.includes(id));
    };
    const selectedFields = ref<string[]>(defaults());
    let timer: number | undefined;

    try {
        const stored = JSON.parse(localStorage.getItem(storageKey) ?? "[]");
        if (Array.isArray(stored)) {
            const ids = new Set(availableFields.value.map((field) => field.id));
            const valid = stored.filter(
                (id): id is string => typeof id === "string" && ids.has(id),
            );
            if (valid.length) selectedFields.value = valid;
        }
    } catch {
        // Повреждённая локальная настройка не должна ломать таблицу.
    }
    if (!selectedFields.value.length && availableFields.value.length)
        selectedFields.value = [availableFields.value[0].id];

    watch(query, (value) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => {
            debouncedQuery.value = value.trim();
        }, 300);
    });
    watch([debouncedQuery, mode, selectedFields], () => {
        currentId.value = null;
    });
    watch(
        availableFields,
        (next) => {
            const ids = new Set(next.map((field) => field.id));
            const valid = selectedFields.value.filter((id) => ids.has(id));
            if (!valid.length && next.length) valid.push(next[0].id);
            if (valid.join("|") !== selectedFields.value.join("|"))
                selectedFields.value = valid;
        },
        { deep: true },
    );
    onBeforeUnmount(() => window.clearTimeout(timer));

    const active = computed(() => debouncedQuery.value.length >= 3);
    const matches = (row: unknown) =>
        active.value &&
        rowMatchesSearch(
            row,
            debouncedQuery.value,
            availableFields.value,
            selectedFields.value,
        );
    const identify = (row: unknown) => {
        const id = valueAtPath(row, "id") ?? valueAtPath(row, "user_id");
        return String(id ?? "");
    };

    return {
        query,
        debouncedQuery,
        expanded,
        mode,
        selectedFields,
        currentId,
        active,
        availableFields,
        matches,
        apply: <T>(rows: T[]) =>
            mode.value === "filter" && active.value
                ? rows.filter(matches)
                : rows,
        matching: <T>(rows: T[]) => (active.value ? rows.filter(matches) : []),
        isCurrent: (row) => currentId.value === identify(row),
        identify,
        selectFields(ids) {
            if (!ids.length) return;
            selectedFields.value = [...ids];
            try {
                localStorage.setItem(storageKey, JSON.stringify(ids));
            } catch {
                // Поиск продолжает работать, даже если хранилище запрещено.
            }
        },
        navigate<T>(rows: T[], direction: -1 | 1): T | null {
            const matches = rows.filter((row) => this.matches(row));
            if (!matches.length) return null;
            const current = matches.findIndex(
                (row) => this.identify(row) === currentId.value,
            );
            const target =
                current < 0
                    ? direction > 0
                        ? 0
                        : matches.length - 1
                    : current + direction;
            if (target < 0 || target >= matches.length) return null;
            const row = matches[target];
            currentId.value = this.identify(row);
            return row;
        },
        resetNavigation: () => (currentId.value = null),
    };
}
