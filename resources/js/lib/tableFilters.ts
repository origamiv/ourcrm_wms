import { computed, ref, type ComputedRef, type Ref } from "vue";
import {
    type IDataHash,
    type IField,
    type IFilterSet,
} from "@svar-ui/vue-filter";
import { http } from "./http";

export type FilterRules = IFilterSet;
export type FilterField = IField;
export type FilterOptions = IDataHash<Array<string | number | Date>>;

export interface FilterPreset {
    id: string;
    screen_key: string;
    name: string;
    rules: FilterRules;
    is_active: boolean;
    updated_at: string | null;
}

export interface FilterPresetState {
    screenKey: string;
    presets: Ref<FilterPreset[]>;
    draft: Ref<FilterRules | null>;
    loading: Ref<boolean>;
    error: Ref<string>;
    editRequest: Ref<FilterPreset | null | undefined>;
    combined: ComputedRef<FilterRules | null>;
    load: () => Promise<void>;
    create: (name: string, rules: FilterRules) => Promise<void>;
    update: (
        preset: FilterPreset,
        name: string,
        rules: FilterRules,
    ) => Promise<void>;
    toggle: (preset: FilterPreset) => Promise<void>;
    remove: (preset: FilterPreset) => Promise<void>;
}

function reviveDates(node: FilterRules): FilterRules {
    const copy = structuredClone(node) as any;
    const visit = (value: any): void => {
        if (Array.isArray(value?.rules)) {
            value.rules.forEach(visit);
            return;
        }
        if (value?.type !== "date") return;
        if (typeof value.value === "string")
            value.value = new Date(value.value);
        if (value.value && typeof value.value === "object") {
            if (typeof value.value.start === "string")
                value.value.start = new Date(value.value.start);
            if (typeof value.value.end === "string")
                value.value.end = new Date(value.value.end);
        }
        if (Array.isArray(value.includes))
            value.includes = value.includes.map((item: unknown) =>
                typeof item === "string" ? new Date(item) : item,
            );
    };
    visit(copy);
    return copy;
}

export function useFilterPresets(screenKey: string): FilterPresetState {
    const presets = ref<FilterPreset[]>([]);
    const draft = ref<FilterRules | null>(null);
    const loading = ref(false);
    const error = ref("");
    const editRequest = ref<FilterPreset | null | undefined>(undefined);
    const combined = computed<FilterRules | null>(() => {
        const rules = presets.value
            .filter((preset) => preset.is_active)
            .map((preset) => preset.rules);
        if (draft.value?.rules?.length) rules.push(draft.value);
        return rules.length ? { glue: "and", rules } : null;
    });

    async function load(): Promise<void> {
        loading.value = true;
        try {
            const response = await http(
                `/web/filter_presets?screen_key=${encodeURIComponent(screenKey)}`,
            );
            presets.value = (response.data ?? []).map(
                (preset: FilterPreset) => ({
                    ...preset,
                    rules: reviveDates(preset.rules),
                }),
            );
            error.value = "";
        } catch (exception) {
            error.value =
                exception instanceof Error
                    ? exception.message
                    : "Не удалось загрузить сохранённые фильтры.";
        } finally {
            loading.value = false;
        }
    }

    async function create(name: string, rules: FilterRules): Promise<void> {
        const response = await http("/web/filter_presets", "POST", {
            screen_key: screenKey,
            name,
            rules,
            is_active: true,
        });
        presets.value.push(response.data);
        draft.value = null;
    }

    async function update(
        preset: FilterPreset,
        name: string,
        rules: FilterRules,
    ): Promise<void> {
        const response = await http(`/web/filter_presets/${preset.id}`, "PUT", {
            name,
            rules,
            is_active: preset.is_active,
        });
        presets.value = presets.value.map((item) =>
            item.id === preset.id ? response.data : item,
        );
    }

    async function toggle(preset: FilterPreset): Promise<void> {
        const response = await http(`/web/filter_presets/${preset.id}`, "PUT", {
            name: preset.name,
            rules: preset.rules,
            is_active: !preset.is_active,
        });
        presets.value = presets.value.map((item) =>
            item.id === preset.id ? response.data : item,
        );
    }

    async function remove(preset: FilterPreset): Promise<void> {
        await http(`/web/filter_presets/${preset.id}`, "DELETE");
        presets.value = presets.value.filter((item) => item.id !== preset.id);
    }

    return {
        screenKey,
        presets,
        draft,
        loading,
        error,
        editRequest,
        combined,
        load,
        create,
        update,
        toggle,
        remove,
    };
}

export function applyTableFilter<T>(rows: T[], rules: FilterRules | null): T[] {
    if (!rules?.rules?.length) return rows;
    return rows.filter((row) => matchesNode(row, rules));
}

export function serializedFilter(rules: FilterRules | null): string {
    return rules?.rules?.length ? JSON.stringify(rules) : "";
}

function matchesNode(row: unknown, node: any): boolean {
    if (Array.isArray(node?.rules)) {
        const values = node.rules.map((child: unknown) =>
            matchesNode(row, child),
        );
        return node.glue === "or"
            ? values.some(Boolean)
            : values.every(Boolean);
    }
    const actual = fieldValue(row, String(node.field ?? ""));
    const candidates = Array.isArray(actual) ? actual : [actual];
    if (Array.isArray(node.includes))
        return candidates.some((value) =>
            node.includes.some(
                (included: unknown) =>
                    comparable(value, node.type) ===
                    comparable(included, node.type),
            ),
        );
    const expected = node.value;
    const matched = candidates.some((value) =>
        compare(value, expected, node.type, node.filter),
    );
    return matched;
}

function fieldValue(row: unknown, path: string): unknown {
    return path
        .split(".")
        .reduce<unknown>(
            (value, key) =>
                value && typeof value === "object"
                    ? (value as Record<string, unknown>)[key]
                    : null,
            row,
        );
}

function comparable(value: unknown, type: string): string | number {
    if (type === "number") return Number(value);
    if (type === "tuple") {
        if (typeof value === "boolean") return value ? 1 : 0;
        const text = String(value ?? "");
        return text !== "" && Number.isFinite(Number(text))
            ? Number(text)
            : text.toLocaleLowerCase("ru");
    }
    if (type === "date") return new Date(String(value)).getTime();
    return String(value ?? "").toLocaleLowerCase("ru");
}

function compare(
    actual: unknown,
    expected: any,
    type: string,
    filter: string,
): boolean {
    const left = comparable(actual, type);
    const right = comparable(expected, type);
    const rangeStart =
        expected?.start ?? (Array.isArray(expected) ? expected[0] : null);
    const rangeEnd =
        expected?.end ?? (Array.isArray(expected) ? expected[1] : null);
    switch (filter) {
        case "equal":
            return left === right;
        case "notEqual":
            return left !== right;
        case "greater":
            return left > right;
        case "greaterOrEqual":
            return left >= right;
        case "less":
            return left < right;
        case "lessOrEqual":
            return left <= right;
        case "contains":
            return String(left).includes(String(right));
        case "notContains":
            return !String(left).includes(String(right));
        case "beginsWith":
            return String(left).startsWith(String(right));
        case "notBeginsWith":
            return !String(left).startsWith(String(right));
        case "endsWith":
            return String(left).endsWith(String(right));
        case "notEndsWith":
            return !String(left).endsWith(String(right));
        case "between":
            return (
                left >= comparable(rangeStart, type) &&
                left <= comparable(rangeEnd, type)
            );
        case "notBetween":
            return (
                left < comparable(rangeStart, type) ||
                left > comparable(rangeEnd, type)
            );
        default:
            return true;
    }
}
