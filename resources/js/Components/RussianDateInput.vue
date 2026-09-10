<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, watch } from "vue";
import flatpickr from "flatpickr";
import type { Instance } from "flatpickr/dist/types/instance";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import "flatpickr/dist/flatpickr.css";
import { isoDate, parseRussianDate, toIsoDate } from "../lib/dates";
const props = defineProps<{ modelValue?: string | null; withTime?: boolean }>();
const emit = defineEmits<{ "update:modelValue": [value: string] }>();
const input = ref<HTMLInputElement>();
let picker: Instance | undefined;
let invalidText: string | null = null;
function validate() {
    const text = input.value!.value;
    invalidText =
        text && !parseRussianDate(text, props.withTime, props.modelValue)
            ? text
            : null;
    const parsed = parseRussianDate(text, props.withTime, props.modelValue);
    if (!text || parsed)
        emit(
            "update:modelValue",
            parsed ? toIsoDate(parsed, props.withTime) : "",
        );
    input.value!.setCustomValidity(
        invalidText
            ? "Введите существующую дату: дд.мм.гг" +
                  (props.withTime ? " чч:мм (24 часа)." : ".")
            : "",
    );
}
onMounted(() => {
    picker = flatpickr(input.value!, {
        locale: {
            ...Russian,
            firstDayOfWeek: 1,
            weekdays: {
                ...Russian.weekdays,
                shorthand: ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
            },
        },
        dateFormat: props.withTime ? "d.m.y H:i" : "d.m.y",
        ariaDateFormat: "d.m.Y",
        enableTime: !!props.withTime,
        time_24hr: true,
        minuteIncrement: 1,
        disableMobile: true,
        allowInput: true,
        defaultDate: isoDate(props.modelValue),
        parseDate: (value) =>
            parseRussianDate(value, props.withTime, props.modelValue)!,
        onOpen: () => {
            if (input.value?.matches(":disabled")) picker?.close();
        },
        onChange: (dates) => {
            if (dates[0]) {
                invalidText = null;
                input.value!.setCustomValidity("");
            }
            if (invalidText === null)
                emit(
                    "update:modelValue",
                    dates[0] ? toIsoDate(dates[0], props.withTime) : "",
                );
        },
        onClose: () => {
            if (invalidText !== null) input.value!.value = invalidText;
        },
    });
});
watch(
    () => props.modelValue,
    (value) => {
        const date = isoDate(value);
        if (
            date &&
            parseRussianDate(
                input.value?.value ?? "",
                props.withTime,
                value,
            )?.getTime() === date.getTime()
        )
            return;
        invalidText = null;
        input.value?.setCustomValidity("");
        if (picker) picker.setDate(date ?? [], false);
    },
);
onBeforeUnmount(() => picker?.destroy());
</script>
<template>
    <input
        ref="input"
        type="text"
        lang="ru-RU"
        autocomplete="off"
        :placeholder="withTime ? 'дд.мм.гг чч:мм' : 'дд.мм.гг'"
        @input="validate"
    />
</template>
<style>
.flatpickr-calendar {
    font-family: Manrope, sans-serif;
}
.flatpickr-day.selected,
.flatpickr-day.selected:hover {
    background: #19882b;
    border-color: #19882b;
}
.flatpickr-calendar .flatpickr-monthDropdown-months {
    padding: 0;
    min-width: 0;
    height: auto;
    display: inline-block;
}
.flatpickr-calendar .numInputWrapper input {
    padding: 0 0 0 5px;
    border: 0;
    min-width: 0;
    border-radius: 0;
}
.flatpickr-time input {
    height: 100%;
}
</style>
