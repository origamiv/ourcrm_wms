#!/bin/bash
set -e
# ✅ Задаём алиас на PHP 8.2
# alias php="/opt/php82/bin/php"

# или вариант с приоритетом в PATH:
# export PATH="/opt/php82/bin:$PATH"

echo "------------ whoami"
whoami
ls -la

echo "------------ php"
php -v

echo "------------ composer"
#php /usr/local/bin/composer install
composer install

echo "------------ migrate"
php artisan migrate
php artisan project:menu

echo "------------ swagger"
php artisan l5-swagger:generate

echo "------------ npm"
npm install
npm run build
