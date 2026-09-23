# WMS

Система управления складом на Laravel 12. Реализованы авторизация, управление общими пользователями и локальный кэш с загрузкой изменений. Frontend — Inertia + Vue 3, вход — Livewire; визуальная основа — Figma и `../front3`.

Целевая основа: стандартные каталоги Laravel внутри `app`, одна БД PostgreSQL со схемами функциональных модулей, изоляция организаций через `tenant_id`, ClickHouse для истории и статистики, Scramble для API. Новые доменные классы в `Modules` не размещаются.

## Документация

- [Правила работы агента](AGENTS.md)
- [Порядок выполнения задач](WORKFLOW.md)
- [Архитектура](docs/rights/ARCHITECTURE.md)
- [Права изменения и Git](docs/rights/RIGHTS.md)
- [Безопасность](docs/rights/SECURITY.md)
- [Стиль и шаблоны сущностей](docs/rights/STYLE.md)
- [Проверки](docs/rights/TESTING.md)
- [Пользователи и синхронизация](docs/features/USERS.md)
- [API](docs/features/API.md)
- [Текущее состояние и следующие этапы](docs/features/FOUNDATION.md)
- [Пользовательская справка](docs/help/INDEX.md)

## Подготовка

### Локальная разработка
```shell
composer install
npm ci
npm run build
php artisan migrate --force
```

### Cursor Cloud Agents
```shell
./start-cloud.sh
```
Автоматическая настройка для работы в облачной среде. Подробности в [docs/CLOUD_AGENTS.md](docs/CLOUD_AGENTS.md).

Перед миграцией задайте `DB_CONNECTION=pgsql`, `DB_SCHEMA=wms` и создайте схему `wms` при её отсутствии. В PostgreSQL должны существовать общие `public.users`, `main.roles` и `main.role_user`. Миграция создаёт техническую инфраструктуру в `wms`, не пересоздавая пользователей. Нужна действующая конфигурация БД и сессий. Вход — `/login`, пользователи — `/users`, документация API — `/docs/api`.

Старые `install.sh` и `deploy.sh` требуют отдельной адаптации; подробности установки и ограничений — в [описании реализации](docs/features/USERS.md).
