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
        | "lookup_list"
        | "date"
        | "datetime"
        | "json"
        | "string_list";
    required?: boolean;
    detail?: boolean;
    lookup?:
        | "integration_services"
        | "integration_webhooks"
        | "integration_rules"
        | "integration_type_hook"
        | "integration_type_processing"
        | "modules"
        | "companies"
        | "users"
        | "clients"
        | "client_doc_types"
        | "client_companies"
        | "client_services"
        | "client_accounts"
        | "goods"
        | "good_cards"
        | "type_goods"
        | "unit_goods"
        | "kind_kiz"
        | "type_warehouses"
        | "kind_warehouses"
        | "type_storage"
        | "zones"
        | "cells"
        | "cell_goods"
        | "tasks"
        | "acceptances"
        | "type_acceptance"
        | "type_services"
        | "services_ff"
        | "warehouses"
        | "marketplaces"
        | "task_types"
        | "task_statuses"
        | "task_stages"
        | "priorities"
        | "order_statuses"
        | "order_sources"
        | "order_cancel_statuses"
        | "logistic_companies"
        | "shipment_statuses";
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
    orders: {
        title: "Заказы",
        fields: [
            { key: "code", label: "Внешний код" }, { key: "number", label: "Номер" },
            { key: "client_id", label: "Клиент", kind: "lookup", lookup: "clients" },
            { key: "warehouse_id", label: "Склад", kind: "lookup", lookup: "warehouses" },
            { key: "order_status_id", label: "Статус заказа", kind: "lookup", lookup: "order_statuses" },
            { key: "order_source_id", label: "Источник", kind: "lookup", lookup: "order_sources" },
            { key: "delivery_service_id", label: "Служба доставки", kind: "lookup", lookup: "delivery_services" },
            { key: "delivery_track", label: "Трек-номер" }, { key: "delivery_date", label: "Дата доставки", kind: "date" },
            { key: "created_date", label: "Дата создания", kind: "datetime" }, { key: "goods_total_price", label: "Сумма", kind: "money" },
            { key: "goods_count", label: "Количество", kind: "number" }, { key: "comment_partner", label: "Комментарий клиента", kind: "textarea", detail: true },
            { key: "comment_internal", label: "Внутренний комментарий", kind: "textarea", detail: true }, { key: "src", label: "Источник", kind: "json", detail: true },
        ] as ReferenceField[],
    },
    shipments: {
        title: "Отгрузки",
        fields: [
            { key: "code", label: "Внешний код" },
            { key: "order_id", label: "Заказ", kind: "lookup", lookup: "orders" },
            { key: "client_id", label: "Клиент", kind: "lookup", lookup: "clients" },
            { key: "warehouse_id", label: "Склад", kind: "lookup", lookup: "warehouses" },
            { key: "shipment_status_id", label: "Статус", kind: "lookup", lookup: "shipment_statuses" },
            { key: "created_date", label: "Создана", kind: "datetime" },
            { key: "checked_at", label: "Проверена", kind: "datetime" },
            { key: "sent_at", label: "Отправлена", kind: "datetime" },
            { key: "src", label: "Источник", kind: "json", detail: true },
        ] as ReferenceField[],
    },
    order_statuses: { title: "Статусы заказов", fields: [{ key: "shortname", label: "Краткое название" }, { key: "code", label: "Внешний код" }] as ReferenceField[] },
    order_sources: { title: "Источники заказов", fields: [{ key: "shortname", label: "Краткое название" }, { key: "code", label: "Внешний код" }] as ReferenceField[] },
    order_cancel_statuses: { title: "Статусы отмены заказов", fields: [{ key: "shortname", label: "Краткое название" }, { key: "code", label: "Внешний код" }] as ReferenceField[] },
    logistic_companies: { title: "Логистические компании", fields: [{ key: "shortname", label: "Краткое название" }, { key: "code", label: "Внешний код" }] as ReferenceField[] },
    shipment_statuses: { title: "Статусы отправлений", fields: [{ key: "shortname", label: "Краткое название" }, { key: "code", label: "Внешний код" }] as ReferenceField[] },
    integration_webhooks: {
        title: "Вебхуки",
        fields: [
            { key: "code", label: "Код", detail: true },
            { key: "shortname", label: "Краткое название" },
            {
                key: "service_id",
                label: "Сервис",
                kind: "lookup",
                lookup: "integration_services",
            },
            {
                key: "type_hook_id",
                label: "Тип хука",
                kind: "lookup",
                lookup: "integration_type_hook",
            },
            { key: "url", label: "Адрес вебхука", detail: true },
            {
                key: "rules_id",
                label: "Правила",
                kind: "lookup_list",
                lookup: "integration_rules",
            },
            { key: "cnt", label: "Количество запусков", kind: "number" },
            {
                key: "dat_last_run",
                label: "Последний запуск",
                kind: "datetime",
            },
            { key: "params", label: "Параметры", kind: "json", detail: true },
        ] as ReferenceField[],
    },
    integration_data: {
        title: "Данные",
        fields: [
            { key: "shortname", label: "Краткое название" },
            {
                key: "webhook_id",
                label: "Вебхук",
                kind: "lookup",
                lookup: "integration_webhooks",
            },
            {
                key: "service_id",
                label: "Сервис",
                kind: "lookup",
                lookup: "integration_services",
            },
            {
                key: "raw",
                label: "Исходные данные",
                kind: "textarea",
                detail: true,
            },
            { key: "src", label: "Источник", kind: "json", detail: true },
            { key: "data", label: "Данные", kind: "json", detail: true },
            {
                key: "progress_processing",
                label: "Ход обработки",
                kind: "json",
                detail: true,
            },
            {
                key: "status_processing",
                label: "Статус обработки",
                kind: "number",
            },
        ] as ReferenceField[],
    },
    integration_rules: {
        title: "Правила",
        fields: [
            { key: "shortname", label: "Краткое название" },
            {
                key: "type_processing_id",
                label: "Тип обработки",
                kind: "lookup",
                lookup: "integration_type_processing",
            },
            { key: "val", label: "Значение", detail: true },
            { key: "params", label: "Параметры", kind: "json", detail: true },
        ] as ReferenceField[],
    },
    integration_services: {
        title: "Сервисы",
        fields: [
            { key: "shortname", label: "Краткое название" },
        ] as ReferenceField[],
    },
    integration_type_hook: {
        title: "Типы хуков",
        fields: [
            { key: "shortname", label: "Краткое название" },
        ] as ReferenceField[],
    },
    integration_type_processing: {
        title: "Типы обработки",
        fields: [
            { key: "shortname", label: "Краткое название" },
        ] as ReferenceField[],
    },

    warehouses: {
        title: "Склады",
        fields: [
            { key: "shortname", label: "Краткое название" },
            { key: "code", label: "Код" },
            { key: "type_warehouse_id", label: "Тип склада", kind: "lookup", lookup: "type_warehouses" },
            { key: "kind_warehouse_id", label: "Вид склада", kind: "lookup", lookup: "kind_warehouses" },
            { key: "address", label: "Адрес" },
            { key: "timezone", label: "Часовой пояс" },
            { key: "contact_name", label: "Контактное лицо" },
            { key: "contact_phone", label: "Телефон" },
            { key: "working_hours", label: "Рабочие часы" },
            { key: "width", label: "Ширина", kind: "number" },
            { key: "height", label: "Высота", kind: "number" },
            { key: "scheme_json", label: "Схема", kind: "json", detail: true },
        ] as ReferenceField[],
    },
    type_warehouses: {
        title: "Типы складов",
        fields: [{ key: "shortname", label: "Краткое название" }] as ReferenceField[],
    },
    kind_warehouses: {
        title: "Виды складов",
        fields: [{ key: "shortname", label: "Краткое название" }] as ReferenceField[],
    },
    type_storage: {
        title: "Типы хранения",
        fields: [{ key: "shortname", label: "Краткое название" }] as ReferenceField[],
    },
    zones: {
        title: "Зоны",
        fields: [{ key: "shortname", label: "Краткое название" }] as ReferenceField[],
    },
    cells: {
        title: "Ячейки",
        fields: [
            { key: "warehouse_id", label: "Склад", kind: "lookup", lookup: "warehouses", required: true },
            { key: "zone_id", label: "Зона", kind: "lookup", lookup: "zones", required: true },
            { key: "shortname", label: "Краткое название" },
            { key: "row", label: "Ряд", kind: "number" },
            { key: "level", label: "Уровень", kind: "number" },
            { key: "number", label: "Номер", kind: "number" },
            { key: "priority", label: "Приоритет", kind: "number" },
            { key: "type_storage_id", label: "Тип хранения", kind: "lookup", lookup: "type_storage", required: true },
        ] as ReferenceField[],
    },
    cell_goods: {
        title: "Размещения",
        fields: [
            { key: "warehouse_id", label: "Склад", kind: "lookup", lookup: "warehouses", required: true },
            { key: "cell_id", label: "Ячейка", kind: "lookup", lookup: "cells", required: true },
            { key: "good_id", label: "Товар", kind: "lookup", lookup: "goods", required: true },
            { key: "user_id", label: "Разместил пользователь", kind: "lookup", lookup: "users" },
            { key: "cnt", label: "Количество", kind: "number", required: true },
            { key: "put_at", label: "Размещено", kind: "datetime" },
            { key: "leave_at", label: "Выбыло", kind: "datetime" },
            { key: "src", label: "Источник", kind: "json", detail: true },
        ] as ReferenceField[],
    },
    acceptances: {
        title: "Приемки",
        fields: [
            { key: "client_id", label: "Клиент", kind: "lookup", lookup: "clients" },
            { key: "warehouse_id", label: "Склад", kind: "lookup", lookup: "warehouses" },
            { key: "task_id", label: "Задача", kind: "lookup", lookup: "tasks" },
            { key: "type_acceptance_id", label: "Тип приемки", kind: "lookup", lookup: "type_acceptance" },
            { key: "plan_count", label: "Плановое количество", kind: "number" },
            { key: "fact_count", label: "Фактическое количество", kind: "number" },
            { key: "progress", label: "Прогресс", kind: "number" },
            { key: "started_at", label: "Начата", kind: "datetime" },
            { key: "finished_at", label: "Завершена", kind: "datetime" },
        ] as ReferenceField[],
    },
    type_acceptance: {
        title: "Типы приемки",
        fields: [{ key: "shortname", label: "Краткое название" }] as ReferenceField[],
    },
    type_services: {
        title: "Типы услуг",
        fields: [{ key: "shortname", label: "Краткое название" }] as ReferenceField[],
    },
    services_ff: {
        title: "Услуги фулфилмента",
        fields: [
            { key: "shortname", label: "Краткое название" },
            { key: "unit_id", label: "Единица измерения", kind: "lookup", lookup: "unit_goods" },
            { key: "price", label: "Цена", kind: "money" },
            { key: "type_service_ff", label: "Тип услуги", kind: "lookup", lookup: "type_services" },
            { key: "is_visible", label: "Видима", kind: "flag" },
        ] as ReferenceField[],
    },
    task_types: { title: "Типы задач", fields: [{ key: "shortname", label: "Краткое название" }] as ReferenceField[] },
    task_statuses: { title: "Статусы задач", fields: [{ key: "shortname", label: "Краткое название" }] as ReferenceField[] },
    task_stages: { title: "Этапы задач", fields: [{ key: "shortname", label: "Краткое название" }, { key: "icon", label: "Иконка" }] as ReferenceField[] },
    priorities: { title: "Приоритеты", fields: [{ key: "shortname", label: "Краткое название" }, { key: "icon", label: "Иконка" }] as ReferenceField[] },
    tasks: {
        title: "Задачи",
        fields: [
            { key: "shortname", label: "Краткое название" },
            { key: "client_id", label: "Клиент", kind: "lookup", lookup: "clients", required: true },
            { key: "service_id", label: "Сервис", kind: "lookup", lookup: "client_services" },
            { key: "task_type_id", label: "Тип задачи", kind: "lookup", lookup: "task_types", required: true },
            { key: "task_stage_id", label: "Этап задачи", kind: "lookup", lookup: "task_stages" },
            { key: "status_id", label: "Статус задачи", kind: "lookup", lookup: "task_statuses", required: true },
            { key: "priority_id", label: "Приоритет", kind: "lookup", lookup: "priorities", required: true },
            { key: "warehouse_id", label: "Склад", kind: "lookup", lookup: "warehouses", required: true },
            { key: "user_id", label: "Ответственный", kind: "lookup", lookup: "users" },
            { key: "planned_at", label: "Запланировано", kind: "datetime" },
            { key: "started_at", label: "Начато", kind: "datetime" },
            { key: "completed_at", label: "Завершено", kind: "datetime" },
            { key: "charged_at", label: "Начислено", kind: "datetime" },
            { key: "charged_sum", label: "Сумма начисления", kind: "money" },
            { key: "confirmed_at", label: "Подтверждено", kind: "datetime" },
            { key: "fact_count", label: "Фактическое количество", kind: "number" },
            { key: "comment", label: "Комментарий", kind: "textarea", detail: true },
            { key: "internal_comment", label: "Внутренний комментарий", kind: "textarea", detail: true },
            { key: "order_id", label: "Заказ", kind: "number" },
            { key: "src", label: "Источник", kind: "json", detail: true },
        ] as ReferenceField[],
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
    client_services: {
        title: "Сервисы",
        fields: [
            { key: "shortname", label: "Краткое название" },
        ] as ReferenceField[],
    },
    client_accounts: {
        title: "Доступы",
        fields: [
            { key: "shortname", label: "Краткое название" },
            { key: "client_id", label: "Клиент", kind: "lookup", lookup: "clients", required: true },
            { key: "service_id", label: "Сервис", kind: "lookup", lookup: "client_services" },
            { key: "host", label: "Host" },
            { key: "login", label: "Имя пользователя" },
            { key: "pass", label: "Пароль", detail: true },
            { key: "token", label: "Токен", kind: "textarea", detail: true },
            { key: "descr", label: "Описание", kind: "textarea", detail: true },
            { key: "group_id", label: "Группа", kind: "number" },
            { key: "server_id", label: "Сервер", kind: "number" },
            { key: "src", label: "API", kind: "json", detail: true },
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
