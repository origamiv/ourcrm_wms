<script setup lang="ts">
import { computed } from "vue";
const props = defineProps<{ modelValue: string; fields: any[] }>();
const emit = defineEmits<{ "update:modelValue": [value: string] }>();
const source = computed(() => {
    try {
        const value = JSON.parse(props.modelValue || "{}");
        return value && !Array.isArray(value) && typeof value === "object"
            ? value
            : null;
    } catch {
        return null;
    }
});
const fields = computed(() =>
    Array.isArray(props.fields)
        ? props.fields.filter(
              (f) =>
                  f &&
                  /^[a-z][a-z0-9_]{0,49}$/.test(f.key) &&
                  ["text", "textarea", "date", "items"].includes(f.type),
          )
        : [],
);
function value(key: string) {
    return source.value?.pdf?.[key] ?? "";
}
function update(key: string, value: any) {
    if (source.value)
        emit(
            "update:modelValue",
            JSON.stringify(
                { ...source.value, pdf: { ...source.value.pdf, [key]: value } },
                null,
                2,
            ),
        );
}
function items(key: string): any[] {
    const v = value(key);
    return Array.isArray(v) ? v : [];
}
function item(key: string, index: number, field: string, value: string) {
    update(
        key,
        items(key).map((row, i) =>
            i === index ? { ...row, [field]: value } : row,
        ),
    );
}
const columns = [
    { key: "name", label: "Наименование" },
    { key: "unit", label: "Единица" },
    { key: "quantity", label: "Количество" },
    { key: "price", label: "Цена без НДС" },
    { key: "vat_rate", label: "НДС, % или none" },
];
</script>
<template>
    <div class="print-fields">
        <h3>Данные для печати</h3>
        <p v-if="!source" role="status">
            Исправьте JSON дополнительных данных, чтобы заполнить печатные поля.
        </p>
        <template v-else v-for="field in fields" :key="field.key">
            <div v-if="field.type === 'items'" class="print-items">
                <strong>{{ field.label }}</strong>
                <p>
                    Цена без НДС, в рублях. НДС начисляется сверху; «none» — без
                    НДС.
                </p>
                <div
                    v-for="(row, index) in items(field.key)"
                    :key="index"
                    class="print-item"
                >
                    <strong>Позиция {{ index + 1 }}</strong>
                    <label v-for="column in columns" :key="column.key"
                        >{{ column.label
                        }}<input
                            :aria-label="`${column.label}: позиция ${index + 1}`"
                            :value="row[column.key]"
                            @input="
                                item(
                                    field.key,
                                    index,
                                    column.key,
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                    /></label>
                    <button
                        type="button"
                        @click="
                            update(
                                field.key,
                                items(field.key).filter((_, i) => i !== index),
                            )
                        "
                    >
                        Удалить позицию
                    </button>
                </div>
                <button
                    type="button"
                    @click="
                        update(field.key, [
                            ...items(field.key),
                            {
                                name: '',
                                unit: 'шт.',
                                quantity: '1',
                                price: '0.00',
                                vat_rate: 'none',
                            },
                        ])
                    "
                >
                    Добавить позицию
                </button>
            </div>
            <label v-else
                >{{ field.label }}{{ field.required ? " *" : "" }}
                <textarea
                    v-if="field.type === 'textarea'"
                    :aria-label="field.label"
                    :value="value(field.key)"
                    rows="4"
                    @input="
                        update(
                            field.key,
                            ($event.target as HTMLTextAreaElement).value,
                        )
                    "
                />
                <input
                    v-else
                    :aria-label="field.label"
                    :type="field.type === 'date' ? 'date' : 'text'"
                    :value="value(field.key)"
                    @input="
                        update(
                            field.key,
                            ($event.target as HTMLInputElement).value,
                        )
                    "
                />
            </label>
        </template>
    </div>
</template>
<style scoped>
.print-fields,
.print-item {
    display: grid;
    gap: 12px;
}
.print-fields {
    border-top: 1px solid #eee;
    padding-top: 16px;
}
.print-fields label {
    display: grid;
    gap: 8px;
}
.print-item {
    border: 1px solid #a8d4a9;
    padding: 12px;
    margin-block: 12px;
}
.print-fields button {
    color: #19882b;
    padding: 8px 0;
    text-align: left;
}
.print-fields textarea {
    border: 1px solid #a8d4a9;
    border-radius: 4px;
    padding: 8px;
    width: 100%;
}
.print-fields p {
    font-size: 11px;
}
</style>
