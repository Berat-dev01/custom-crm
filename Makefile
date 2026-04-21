DOCKER_COMPOSE ?= docker compose

.PHONY: up down restart build bash composer artisan npm migrate fresh test queue logs ps

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

restart: down up

build:
	$(DOCKER_COMPOSE) build

bash:
	$(DOCKER_COMPOSE) exec app bash

composer:
	$(DOCKER_COMPOSE) exec -e COMPOSER_AUTH app composer $(CMD)

artisan:
	$(DOCKER_COMPOSE) exec app php artisan $(CMD)

npm:
	$(DOCKER_COMPOSE) exec app npm $(CMD)

migrate:
	$(DOCKER_COMPOSE) exec app php artisan migrate

fresh:
	$(DOCKER_COMPOSE) exec app php artisan migrate:fresh --seed

test:
	$(DOCKER_COMPOSE) exec app php artisan test

queue:
	$(DOCKER_COMPOSE) exec app php artisan queue:work redis --sleep=3 --tries=3 --timeout=90

logs:
	$(DOCKER_COMPOSE) logs -f app

ps:
	$(DOCKER_COMPOSE) ps
