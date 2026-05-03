COMPOSE = LOCAL_UID=$$(id -u) LOCAL_GID=$$(id -g) docker compose
APP_SERVICE = app

.PHONY: build up down logs ps shell composer artisan init-laravel migrate passport-install filament-install filament-user mkcert-auth

build:
	$(COMPOSE) build

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

logs:
	$(COMPOSE) logs -f --tail=150

ps:
	$(COMPOSE) ps

shell:
	$(COMPOSE) exec $(APP_SERVICE) sh

composer:
	$(COMPOSE) exec $(APP_SERVICE) composer $(CMD)

artisan:
	$(COMPOSE) exec $(APP_SERVICE) php artisan $(CMD)

init-laravel:
	$(COMPOSE) run --rm $(APP_SERVICE) composer create-project laravel/laravel .

migrate:
	$(COMPOSE) run --rm $(APP_SERVICE) php artisan migrate --force

passport-install:
	$(COMPOSE) run --rm $(APP_SERVICE) php artisan passport:install

filament-install:
	$(COMPOSE) run --rm $(APP_SERVICE) php artisan filament:install --panels --no-interaction

filament-user:
	$(COMPOSE) run --rm $(APP_SERVICE) php artisan make:filament-user --name="Admin" --email="admin@auth.test" --password="Admin123!" --no-interaction

mkcert-auth:
	mkdir -p docker/nginx/certs
	mkcert -cert-file docker/nginx/certs/auth.test.pem -key-file docker/nginx/certs/auth.test-key.pem auth.test
