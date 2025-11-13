$(eval env=$(shell sh -c "cat ./.env | grep -v ^# | xargs -0"))
$(eval user=$(shell sh -c "echo $$(id -u)"))

.PHONY: start stop update app nginx es exec

COMPOSE_FILE=docker-compose.yml
FPM_CONTAINER=fpm_chats

build:
	docker compose -f $(COMPOSE_FILE) build

start:
	docker compose -f $(COMPOSE_FILE) up -d

run:
	docker compose -f $(COMPOSE_FILE) up --build

stop:
	docker compose -f $(COMPOSE_FILE) down

update:
	docker compose -f $(COMPOSE_FILE) build --no-cache

install:
	git checkout master
	cp .env.example .env
	docker compose -f $(COMPOSE_FILE) up -d
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) composer install
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) npm install
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) npm run build
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan key:generate
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan storage:link --relative
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan project:install
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan project:fresh
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan project:menu
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) sh front_link.sh
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan project:menu
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan project:sync_permissions
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan optimize:clear

refresh:
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan cache:clear
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan view:clear
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan config:clear
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan route:clear
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan project:fresh
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan project:menu
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan project:sync_permissions

db:
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan migrate --seed

fresh:
	docker compose -f $(COMPOSE_FILE) exec -T db psql database < database/init/shems.sql
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan fresh
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan l5-swagger:generate --all

fresh_test:
	docker compose -f $(COMPOSE_FILE) exec -T db psql testing < database/init/test.sql
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan fresh --env=testing

test:
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan test

app:
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) sh

app_root:
	docker compose -f $(COMPOSE_FILE) exec -u 0 $(FPM_CONTAINER) sh

fix:
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php ./vendor/bin/php-cs-fixer fix ./

nginx:
	docker compose -f $(COMPOSE_FILE) exec nginx sh

rundb:
	docker compose -f $(COMPOSE_FILE) exec db bash

initdb:
	docker compose -f $(COMPOSE_FILE) exec -T db psql database < database/init/shems.sql

exec:
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) $(command)

cron:
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) php artisan schedule:run >&1 2>&1

pint:
	docker compose -f $(COMPOSE_FILE) exec $(FPM_CONTAINER) ./vendor/bin/pint
