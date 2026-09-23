# Работа с WMS в Cursor Cloud Agents

Этот документ описывает настройку и работу с проектом WMS в среде Cursor Cloud Agents.

## Автоматическая настройка

При первом запуске в Cloud Agents выполняются следующие действия:

1. Установка PHP 8.2 и необходимых расширений
2. Установка Composer
3. Установка Node.js 20
4. Установка всех зависимостей проекта
5. Настройка базы данных SQLite
6. Выполнение миграций
7. Сборка frontend активов
8. Запуск Laravel development server

## Структура конфигурации

```
.cursor/
├── environment.yml    # Основная конфигурация среды
├── settings.json      # Настройки Cloud Agents
└── README.md         # Документация для облачной среды
```

## Особенности облачной среды

### База данных
- Используется SQLite вместо PostgreSQL для упрощения
- Файл базы: `database/database.sqlite`
- Все миграции написаны с учетом PostgreSQL синтаксиса

### Кэширование и сессии
- Используются файловые драйверы вместо Redis
- Очереди работают в синхронном режиме
- Логи пишутся в файлы

### Порты
- `8000` - Laravel application server
- `5173` - Vite development server (при необходимости)

## Быстрые команды

### Первый запуск
```bash
./start-cloud.sh
```

### Ежедневная работа
```bash
# Запуск только Laravel сервера
php artisan serve --host=0.0.0.0 --port=8000

# Запуск Laravel + Vite для frontend разработки
npm run cloud:dev

# Только сборка frontend
npm run build
```

### Работа с базой данных
```bash
# Новые миграции
php artisan migrate --force

# Откат миграций
php artisan migrate:rollback --force

# Пересоздание базы
rm database/database.sqlite
touch database/database.sqlite
php artisan migrate --force
```

### Отладка и очистка
```bash
# Очистка всех кэшей
php artisan optimize:clear

# Просмотр логов
php artisan pail

# Просмотр маршрутов
php artisan route:list
```

## Разработка

### Создание новых сущностей
```bash
# Создание модели с миграцией
php artisan make:model Example --migration

# Создание контроллера
php artisan make:controller --resource ExampleController

# Создание FormRequest для валидации
php artisan make:request ExampleRequest
```

### Frontend разработка
```bash
# Запуск Vite dev server
npm run dev

# Проверка типов TypeScript
npm run typecheck

# Тестирование frontend
npm run test:cache
```

### API разработка
- Документация автоматически генерируется через Scramble
- Доступна по адресу: `http://localhost:8000/docs/api`
- Используйте `auth:sanctum` middleware для защиты API

## Отличия от локальной разработки

| Аспект | Локально | Cloud Agents |
|--------|----------|--------------|
| БД | PostgreSQL + схемы | SQLite |
| Кэш | Redis | File |
| Сессии | Redis | File |
| Очереди | Database/Redis | Sync |
| Mail | SMTP/Log | Log |
| Внешние API | Реальные | Заглушки |

## Синхронизация с основным репозиторием

Все изменения, сделанные в Cloud Agents, можно коммитить и пушить в основной репозиторий. При переносе в продакшн потребуется:

1. Настройка PostgreSQL базы данных
2. Настройка Redis для кэша и сессий  
3. Настройка очередей (database/rabbitmq)
4. Настройка mail сервера
5. Настройка внешних API (токены и ключи)

## Ограничения

- Нет доступа к внешним сервисам (Redis, PostgreSQL)
- Ограниченные ресурсы (память, CPU)
- Временное хранилище (данные не сохраняются между сессиями)
- Нет доступа к email отправке
- Нет интеграций с внешними API

## Полезные ссылки

- [Основные правила работы](../AGENTS.md)
- [Архитектура проекта](rights/ARCHITECTURE.md)
- [Стиль разработки](rights/STYLE.md)
- [API документация](features/API.md)