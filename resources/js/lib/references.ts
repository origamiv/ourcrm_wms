export interface ReferenceField {
    key: string;
    label: string;
    kind?:
        | "text"
        | "textarea"
        | "number"
        | "flag"
        | "lookup"
        | "date"
        | "datetime"
        | "json";
    required?: boolean;
    lookup?: "modules" | "companies" | "users" | "clients" | "client_doc_types";
}
const assets: ReferenceField[] = [
    { key: "path", label: "Путь" },
    { key: "category", label: "Категория" },
    { key: "size", label: "Размер, байт", kind: "number" },
    { key: "ext", label: "Расширение" },
    {
        key: "company_id",
        label: "Компания",
        kind: "lookup",
        lookup: "companies",
    },
    { key: "user_id", label: "Пользователь", kind: "lookup", lookup: "users" },
];
export const references = {
    client_documents: {
        title: "Документы",
        fields: [
            { key: "shortname", label: "Краткое название" },
            {
                key: "client_id",
                label: "Клиент",
                kind: "lookup",
                lookup: "clients",
                required: true,
            },
            {
                key: "doc_type_id",
                label: "Тип документа",
                kind: "lookup",
                lookup: "client_doc_types",
                required: true,
            },
            { key: "comment", label: "Комментарий", kind: "textarea" },
            {
                key: "internal_comment",
                label: "Внутренний комментарий",
                kind: "textarea",
            },
            { key: "src", label: "Дополнительные данные (JSON)", kind: "json" },
            { key: "doc_date", label: "Дата документа", kind: "date" },
            { key: "accepted_at", label: "Дата подписания", kind: "datetime" },
            { key: "payed_at", label: "Дата оплаты", kind: "datetime" },
            { key: "canceled_at", label: "Дата отмены", kind: "datetime" },
        ] as ReferenceField[],
    },
    client_doc_types: {
        title: "Типы документов",
        fields: [
            { key: "shortname", label: "Краткое название" },
        ] as ReferenceField[],
    },
    client_individuals: {
        title: "Физ.лица",
        fields: [
            { key: "shortname", label: "Краткое имя" },
            { key: "lastname", label: "Фамилия" },
            { key: "firstname", label: "Имя" },
            { key: "middlename", label: "Отчество" },
            { key: "phone", label: "Телефон" },
            { key: "email", label: "Email" },
            { key: "birthday", label: "Дата рождения", kind: "date" },
            { key: "passport_seria", label: "Серия паспорта" },
            { key: "passport_number", label: "Номер паспорта" },
            {
                key: "passport_date",
                label: "Дата выдачи паспорта",
                kind: "date",
            },
            { key: "passport_kem", label: "Кем выдан паспорт" },
            { key: "passport_code", label: "Код подразделения" },
            { key: "address_reg", label: "Адрес регистрации" },
            {
                key: "vodud_date",
                label: "Дата выдачи водительского удостоверения",
                kind: "date",
            },
            { key: "vodud_nomer", label: "Номер водительского удостоверения" },
            {
                key: "client_id",
                label: "Клиент",
                kind: "lookup",
                lookup: "clients",
            },
            {
                key: "user_id",
                label: "Пользователь",
                kind: "lookup",
                lookup: "users",
            },
            {
                key: "manager_id",
                label: "Менеджер",
                kind: "lookup",
                lookup: "users",
            },
        ] as ReferenceField[],
    },
    modules: {
        title: "Модули",
        fields: [
            { key: "shortname", label: "Краткое название" },
            { key: "descr", label: "Описание", kind: "textarea" },
            { key: "fn", label: "Функция" },
            { key: "domain", label: "Домен" },
        ] as ReferenceField[],
    },
    features: {
        title: "Возможности",
        fields: [
            { key: "shortname", label: "Краткое название" },
            {
                key: "module_id",
                label: "Модуль",
                kind: "lookup",
                lookup: "modules",
            },
            { key: "is_resource", label: "Ресурс", kind: "flag" },
        ] as ReferenceField[],
    },
    icons: { title: "Иконки", fields: assets },
    files: {
        title: "Файлы",
        fields: [
            ...assets,
            { key: "is_s3", label: "Хранится в S3", kind: "flag" },
        ] as ReferenceField[],
    },
};
