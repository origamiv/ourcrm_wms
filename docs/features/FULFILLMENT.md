# Фулфилмент

В левом меню «Фулфилмент», верхнее меню «Справочники» с выпадающими пунктами «Маркетплейсы» и «Службы доставки». Стартовая страница `/fulfillment/marketplaces`; службы доставки `/fulfillment/delivery_services`; карточки `/:id/:action`.

- `wms.marketplaces`, модель `App\Models\Marketplace`: id, name, shortname, status, icon.
- `wms.delivery_services`, модель `App\Models\DeliveryService`: те же поля, color, is_order_edit, prefix, folder и nullable marketplace_id → wms.marketplaces.

Обе таблицы имеют tenant_id, created_at, updated_at, deleted_at. Название обязательно, статус 0/1/2, icon — строка до 255 символов, color — до 32, prefix/folder — до 255; is_order_edit — 0/1 или NULL. Значения icon/folder хранятся как реквизиты, не исполняются и не используются для операций с файловой системой. Начальные записи не создаются.

Запись принадлежит организации автора; для NULL tenant_id действует main.tenant_entity. Изменять общие записи может admin с сохранением NULL. Marketplace-связь проверяется через visibleTo; удаление используемого маркетплейса возвращает 422. В БД физическое удаление связанного маркетплейса запрещено внешним ключом.

Общие FulfillmentCatalogController/Service, отдельные CreateFulfillmentCatalogRequest и UpdateFulfillmentCatalogRequest. POST `/api/fulfillment/{catalog}`, PUT/DELETE `/api/fulfillment/{catalog}/{id}`, где catalog = marketplaces или delivery_services. Bearer Token и admin; изменение/удаление требуют version, конфликт возвращает 409. Web-транспорт `/web/fulfillment/...` использует сессию и CSRF.

Дельты `/api/sync/{entity_type}` и `/web/sync/{entity_type}` с типами marketplaces/delivery_services через public.entity_changes. UI хранит записи в IndexedDB, после подтверждения сервера применяет изменение локально; загруженные справочники доступны офлайн для просмотра.

## Начальные службы доставки

Миграция 000024 заполняет 20 общих записей с заданными ID 1–20 и tenant_id NULL. Исходный allow-order-edit соответствует is_order_edit, active=1 → status=1, active=0 → status=2. Добавлено поле from_integration_only (0/1 или NULL), включённое в форму, валидацию и дельты синхронизации. Дефисы в folder заменены на подчёркивания. Счётчик ID продолжает нумерацию после начальных записей; занятые ID миграция не перезаписывает. Marketplace-связи и иконки не заполняются без исходных данных. Автоматический откат заполнения запрещён, чтобы не удалить используемые записи.
