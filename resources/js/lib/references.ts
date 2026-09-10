export interface ReferenceField {
    key: string;
    label: string;
    kind?:
        | "text"
        | "textarea"
        | "money"
        | "number"
        | "flag12"
        | "flag"
        | "lookup"
        | "date"
        | "datetime"
        | "json"
        | "string_list";
    required?: boolean;
    lookup?:
        | "modules"
        | "companies"
        | "users"
        | "clients"
        | "client_doc_types"
        | "client_companies"
        | "goods"
        | "good_cards"
        | "type_goods"
        | "unit_goods"
        | "kind_kiz"
        | "type_warehouses"
        | "warehouses"
        | "marketplaces";
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
    warehouses: {
        title: "Склады",
        fields: [
            { key: "shortname", label: "Краткое название" },
            { key: "type_warehouse_id", label: "Тип склада", kind: "lookup", lookup: "type_warehouses" },
        ] as ReferenceField[],
    },
    type_warehouses: {
        title: "Типы складов",
        fields: [{ key: "shortname", label: "Краткое название" }] as ReferenceField[],
    },
    marketplaces: {
        title: "Маркетплейсы",
        fields: [
            { key: "shortname", label: "Краткое название" },
            { key: "icon", label: "Иконка" },
        ] as ReferenceField[],
    },
    delivery_services: {
        title: "Службы доставки",
        fields: [
            {
                key: "marketplace_id",
                label: "Маркетплейс",
                kind: "lookup",
                lookup: "marketplaces",
            },
            { key: "shortname", label: "Краткое название" },
            { key: "icon", label: "Иконка" },
            { key: "color", label: "Цвет" },
            {
                key: "is_order_edit",
                label: "Разрешено редактирование заказа",
                kind: "flag",
            },
            { key: "from_integration_only", label: "Только из интеграции", kind: "flag" },
            { key: "prefix", label: "Префикс" },
            { key: "folder", label: "Папка" },
        ] as ReferenceField[],
    },
    kizes: {
        title: "Маркировка",
        fields: [
            { key: "code", label: "Код маркировки" },
            {
                key: "client_id",
                label: "Клиент",
                kind: "lookup",
                lookup: "clients",
            },
            {
                key: "kind_kiz_id",
                label: "Вид кода маркировки",
                kind: "lookup",
                lookup: "kind_kiz",
            },
            { key: "good_id", label: "Товар", kind: "lookup", lookup: "goods" },
            {
                key: "entranced_at",
                label: "Дата поступления",
                kind: "datetime",
            },
            { key: "leaving_at", label: "Дата выбытия", kind: "datetime" },
            { key: "printed_at", label: "Дата печати", kind: "datetime" },
        ] as ReferenceField[],
    },
    kind_kiz: {
        title: "Виды кодов маркировки",
        fields: [
            { key: "shortname", label: "Краткое название" },
        ] as ReferenceField[],
    },
    type_goods: {
        title: "Типы товаров",
        fields: [
            { key: "shortname", label: "Краткое название" },
        ] as ReferenceField[],
    },
    unit_goods: {
        title: "Единицы измерения",
        fields: [
            { key: "shortname", label: "Краткое название" },
        ] as ReferenceField[],
    },
    goods: {
        title: "Товары",
        fields: [
            { key: "shortname", label: "Краткое название" },
            { key: "code", label: "Код" },
            { key: "articul", label: "Артикулы", kind: "string_list" },
            {
                key: "parent_id",
                label: "Родительская запись",
                kind: "lookup",
                lookup: "goods",
            },
            { key: "parent_code", label: "Код родителя" },
            {
                key: "type_good",
                label: "Тип товара",
                kind: "lookup",
                lookup: "type_goods",
            },
            {
                key: "type_unit",
                label: "Единица измерения",
                kind: "lookup",
                lookup: "unit_goods",
            },
            {
                key: "goodcard_id",
                label: "Основная карточка товара",
                kind: "lookup",
                lookup: "good_cards",
            },
            {
                key: "is_from_external",
                label: "Из внешней системы",
                kind: "flag12",
            },
            { key: "barcodes", label: "Штрихкоды", kind: "string_list" },
        ] as ReferenceField[],
    },
    client_documents: {
        title: "Документы",
        fields: [
            { key: "shortname", label: "Краткое название" },
            { key: "amount", label: "Сумма", kind: "money" },
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
            {
                key: "executor_id",
                label: "Исполнитель",
                kind: "lookup",
                lookup: "companies",
            },
            {
                key: "customer_id",
                label: "Заказчик",
                kind: "lookup",
                lookup: "client_companies",
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
            { key: "settings", label: "Настройки (JSON)", kind: "json" },
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
