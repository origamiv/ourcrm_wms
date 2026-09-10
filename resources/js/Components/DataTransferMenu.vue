<script setup lang="ts">
import { ref } from "vue";
import { http } from "../lib/http";

const props = withDefaults(defineProps<{
    rows?: any[];
    columns?: { key: string; label: string }[];
    filename?: string;
}>(), { rows: () => [], columns: () => [], filename: "export" });
const emit = defineEmits<{
    export: [format: string];
    import: [format: string, file?: File, text?: string];
}>();
const fileInput = ref<HTMLInputElement | null>(null);
const pendingFormat = ref("");
const exportFormats = ["XLS", "CSV", "TXT", "PDF"];
const importFormats = ["XLS", "CSV", "TXT", "PDF", "JPG", "PNG"];
function formatIconPath(format: string) {
    return ["XLS", "CSV", "PDF"].includes(format)
        ? `/design/formats/${format.toLowerCase()}.png`
        : `/design/formats/${format.toLowerCase()}.svg`;
}
async function exportData(format: string) {
    emit("export", format);
    const columns = props.columns.length
        ? props.columns
        : Object.keys(props.rows[0] ?? {}).map((key) => ({ key, label: key }));
    const rowValue = (row: any, key: string) =>
        key === "__id" ? row.id : key === "__name" ? (row.name ?? row.shortname) : key === "__actions" ? "" : row[key];
    if (format === "PDF") {
        const blob = await http("/web/export/pdf", "POST", {
            title: props.filename,
            columns,
            rows: props.rows.map((row) => Object.fromEntries(columns.map((column) => [column.key, rowValue(row, column.key)]))),
        }, "blob");
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `${props.filename}.pdf`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(link.href), 1000);
        return;
    }
    const separator = format === "CSV" ? ";" : "\t";
    const content = [columns.map((column) => column.label), ...props.rows.map((row) => columns.map((column) => String(rowValue(row, column.key) ?? "")))].map((line) => line.map((value) => `"${value.replaceAll('"', '""')}"`).join(separator)).join("\n");
    const blob = new Blob([content], { type: format === "CSV" ? "text/csv;charset=utf-8" : "application/vnd.ms-excel;charset=utf-8" });
    const link = document.createElement("a"); link.href = URL.createObjectURL(blob); link.download = `${props.filename}.${format === "TXT" ? "txt" : format === "XLS" ? "xls" : "csv"}`; document.body.appendChild(link); link.click(); link.remove(); setTimeout(() => URL.revokeObjectURL(link.href), 1000);
}
function chooseImport(format: string) {
    if (format === "Буфер обмена") {
        navigator.clipboard?.readText().then((text) => emit("import", format, undefined, text));
        return;
    }
    pendingFormat.value = format;
    fileInput.value?.click();
}
function closeMenu(event: Event) {
    (event.currentTarget as HTMLElement).closest("details")?.removeAttribute("open");
}
function receiveFile(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) emit("import", pendingFormat.value, file);
    (event.target as HTMLInputElement).value = "";
}
</script>

<template>
    <div class="data-transfer-menu">
        <details>
            <summary title="Экспорт" aria-label="Экспорт"><img src="/design/formats/export.svg" alt="" /></summary>
            <div class="data-transfer-dropdown">
                <button type="button" class="data-transfer-close" aria-label="Закрыть" @click="closeMenu">×</button>
                <button v-for="format in exportFormats" :key="format" type="button" @click="exportData(format); closeMenu($event)"><img class="format-icon" :src="formatIconPath(format)" alt="" />{{ format }}</button>
            </div>
        </details>
        <details>
            <summary title="Импорт" aria-label="Импорт"><img src="/design/formats/import.svg" alt="" /></summary>
            <div class="data-transfer-dropdown">
                <button type="button" class="data-transfer-close" aria-label="Закрыть" @click="closeMenu">×</button>
                <button v-for="format in importFormats" :key="format" type="button" @click="chooseImport(format); closeMenu($event)"><img class="format-icon" :src="formatIconPath(format)" alt="" />{{ format }}</button>
                <button type="button" @click="chooseImport('Буфер обмена'); closeMenu($event)"><img class="format-icon" src="/design/formats/clipboard.svg" alt="" />Буфер обмена</button>
            </div>
        </details>
        <input ref="fileInput" class="data-transfer-file" type="file" accept=".xls,.xlsx,.csv,.txt,.pdf,.jpg,.jpeg,.png" @change="receiveFile" />
    </div>
</template>

<style scoped>
.data-transfer-menu { display: inline-flex; align-items: center; gap: 4px; margin-right: 8px; }
.data-transfer-menu details { position: relative; }
.data-transfer-menu summary { display: grid; place-items: center; width: 36px; height: 36px; list-style: none; border: 1px solid #d7dce3; border-radius: 6px; background: #fff; color: #2274a5; font-size: 20px; line-height: 1; cursor: pointer; }
.data-transfer-menu summary img { width: 26px; height: 26px; object-fit: contain; }
.data-transfer-menu summary::-webkit-details-marker { display: none; }
.data-transfer-dropdown { position: absolute; z-index: 25; top: 35px; right: 0; display: grid; min-width: 150px; padding: 28px 6px 6px; border: 1px solid #d7e5db; border-radius: 7px; background: #fff; box-shadow: 0 8px 20px rgb(16 24 40 / 14%); }
.data-transfer-close { position: absolute; top: 4px; right: 5px; width: 22px; padding: 2px !important; font-size: 18px; line-height: 1; text-align: center !important; }
.data-transfer-dropdown button { border: 0; background: transparent; padding: 7px 10px; text-align: left; color: #344054; cursor: pointer; }
.data-transfer-dropdown button:hover { background: #eef7f0; color: #2274a5; }
.format-icon { display: inline-block; width: 30px; height: 30px; margin-right: 9px; object-fit: contain; vertical-align: middle; }
.data-transfer-file { display: none; }
</style>
