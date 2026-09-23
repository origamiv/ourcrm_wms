# WMS в Cursor Cloud Agents

Этот проект настроен для работы в Cursor Cloud Agents. Все необходимые зависимости и конфигурации определены в `environment.yml`.

## Быстрый старт

После инициализации среды проект автоматически:
1. Установит все PHP зависимости через Composer
2. Установит Node.js зависимости через npm
3. Создаст файл `.env` на основе `.env.example`
4. Выполнит миграции базы данных
5. Соберет frontend активы
6. Запустит Laravel development server на порту 8000

## Доступные команды

### Основные команды Laravel
```bash
# Миграции
php artisan migrate --force

# Очистка кэша
php artisan optimize:clear

# Создание нового контроллера
php artisan make:controller ExampleController

# Просмотр маршрутов
php artisan route:list
```

### Frontend разработка
```bash
# Запуск Vite dev server
npm run dev

# Сборка для продакшена  
npm run build

# Проверка типов TypeScript
npm run typecheck
```

### Тестирование
```bash
# Frontend тесты
npm run test:cache

# Browser тесты
npm run test:browser
```

## Структура проекта

- `app/` - Основная логика Laravel
- `resources/js/` - Vue 3 + TypeScript frontend
- `resources/css/` - Стили
- `database/migrations/` - Миграции БД
- `docs/` - Документация проекта

## Особенности для Cloud Agents

1. **База данных**: Используется SQLite для простоты разработки в облаке
2. **Кэш и сессии**: Используются файловые драйверы  
3. **Очереди**: Работают в синхронном режиме
4. **Mail**: Логируется в файлы

## Порты

- `8000` - Laravel application
- `5173` - Vite development server (если запущен)

## Документация API

После запуска доступна по адресу: http://localhost:8000/docs/api

## Правила разработки

Смотрите файл `AGENTS.md` в корне проекта для полного описания правил работы с репозиторием.