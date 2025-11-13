#sudo apt-get update -y
#sudo apt-get full-upgrade -y
#sudo apt-get install -y git make mc nginx
sudo add-apt-repository -y ppa:ondrej/php
#sudo apt-get update -y
sudo apt install -y php8.3 php8.3-cli php8.3-{bz2,curl,mbstring,intl,sodium,bcmath}
sudo apt install -y php8.3-fpm
sudo mv /var/www/html/index.nginx-debian.html /var/www/html/index.old




composer require laravel/pulse
composer require hosmelq/laravel-pulse-schedule
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
composer require livewire/livewire
composer require robsontenorio/mary
php artisan mary:install

composer install
#php artisan project:fresh
php artisan key:generate
#php artisan tools:all_yaml
#php artisan tools:create-module
php artisan l5-swagger:generate
npm install
npm run build
