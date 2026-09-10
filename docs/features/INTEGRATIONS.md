# Интеграции

Левое меню «Интеграции» открывает вебхуки. Верхняя лента: «Вебхуки»,
«Данные», «Справочники» → «Правила», «Сервисы», «Типы хуков», «Типы обработки».
Разделы доступны администратору. Первая колонка таблиц — ID с заголовком `#`.

| Раздел | Адрес | Существующая таблица | Модель / псевдоним синхронизации |
| --- | --- | --- | --- |
| Вебхуки | `/integration/webhooks` | `integration.webhooks` | `App\Models\IntegrationWebhook` / `integration_webhooks` |
| Данные | `/integration/data` | `integration.data` | `App\Models\IntegrationData` / `integration_data` |
| Правила | `/integration/rules` | `integration.rules` | `App\Models\IntegrationRule` / `integration_rules` |
| Сервисы | `/integration/services` | `integration.services` | `App\Models\IntegrationService` / `integration_services` |
| Типы хуков | `/integration/type_hook` | `integration.type_hook` | `App\Models\IntegrationHookType` / `integration_type_hook` |
| Типы обработки | `/integration/type_processing` | `integration.type_processing` | `App\Models\IntegrationProcessingType` / `integration_type_processing` |

Имена существующих таблиц сохраняются для совместимости. Миграция `000024`
устанавливает триггеры и начальные события в `public.entity_changes`, не меняя
записи интеграций. Откат удаляет только подключение к синхронизации и сбрасывает
затронутые поколения; предметные таблицы и их данные сохраняются.

## Поля и API

Общие поля формы: `name`, `shortname`, `status`. Дополнительные поля:

- Вебхуки: `service_id`, `type_hook_id`, `url`, `rules_id` (массив ID правил),
  `cnt`, `dat_last_run`, `params` (JSON).
- Данные: `webhook_id`, `service_id`, `raw`, `src`, `data`,
  `progress_processing` (JSON), `status_processing` (целое число).
- Правила: `type_processing_id`, `val`, `params` (JSON).

`GET /api/integration/{catalog}/{id}` возвращает `{data, details}`.
`data` содержит поля списка и строковую `version`; `details` — полное содержимое
параметров. `POST /api/integration/{catalog}` создаёт запись (201),
`PUT /api/integration/{catalog}/{id}` изменяет (200),
`DELETE /api/integration/{catalog}/{id}` мягко удаляет (200).
POST/PUT принимают поля соответствующей формы, PUT/DELETE требуют `version`.
Ответ записи — `{data}`. Конфликт — 409 с `{message, current}`; ошибки полей — 422,
отсутствие доступа — 403, недоступная запись — 404.

Публичный API защищён Sanctum Bearer Token. Сессионные маршруты имеют префикс
`/web/integration`, используют CSRF и тот же сервис. Контракт включён в Scramble.
Карточки открываются по `/{id}/{view|edit|delete}`, создание — `/0/create`.
Сохранение не запускает вебхук и не выполняет правило обработки.

## Видимость и хранение

Чтение, карточки и связи проверяются через `visibleTo`. Новые записи получают
организацию пользователя; `tenant_id` из запроса запрещён. Общие записи остаются
общими после редактирования. Назначения используют полный класс модели
в `main.tenant_entity`. Используемые справочники и вебхуки защищены от удаления.

Списки синхронизируются дельтами через `/web/sync/{alias}` или `/api/sync/{alias}`
и сохраняются в IndexedDB. `url`, `val`, `params`, `raw`, `src`, `data`,
`progress_processing` не включаются в журнал и кэш: они могут содержать
параметры доступа или содержимое внешних сообщений. Полная карточка загружается
отдельным GET с `Cache-Control: private, no-store`. Изменения этих полей также
увеличивают версию записи. Без сети доступен сохранённый список, полные поля
карточки требуют подключения. При ошибке загрузки сохранение блокируется.
