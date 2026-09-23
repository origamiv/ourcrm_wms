#!/bin/bash

echo "🚀 Запуск WMS проекта в Cursor Cloud Agents..."

# Проверяем наличие .env файла
if [ ! -f .env ]; then
    echo "📝 Создание .env файла из шаблона для облака..."
    cp .env.cloud .env
    php artisan key:generate --force
fi

# Проверяем наличие базы данных
if [ ! -f database/database.sqlite ]; then
    echo "🗄️ Создание SQLite базы данных..."
    mkdir -p database
    touch database/database.sqlite
fi

# Устанавливаем зависимости если нужно
if [ ! -d vendor ]; then
    echo "📦 Установка PHP зависимостей..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -d node_modules ]; then
    echo "🔧 Установка Node.js зависимостей..."
    npm ci
fi

# Выполняем миграции
echo "⚡ Выполнение миграций базы данных..."
php artisan migrate --force

# Собираем фронтенд
echo "🎨 Сборка frontend активов..."
npm run build

# Очищаем кэши
echo "🧹 Очистка кэшей Laravel..."
php artisan optimize:clear

echo "✅ Проект готов к работе!"
echo ""
echo "🌐 Доступные команды:"
echo "  php artisan serve --host=0.0.0.0 --port=8000  # Запуск Laravel сервера"
echo "  npm run dev                                    # Запуск Vite dev сервера"
echo "  npm run cloud:dev                              # Запуск обоих серверов"
echo ""
echo "📚 Документация API будет доступна на: http://localhost:8000/docs/api"