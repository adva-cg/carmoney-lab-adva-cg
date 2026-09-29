COMPOSE := docker compose

# Локальный PHP, если доступен; иначе — контейнер backend.
# $(shell php ...) работает и в cmd (Windows make), и в bash.
LOCAL_PHP := $(shell php -r "echo 'yes';" 2>NUL || php -r "echo 'yes';" 2>/dev/null)
HAS_VENDOR := $(wildcard vendor/bin/phpunit)

.DEFAULT_GOAL := help

.PHONY: help up down logs test lint install seed ps

help: ## Показать список команд
	@echo "  up         Поднять сервис и базу (http://localhost:8080)"
	@echo "  down       Остановить сервис (данные в томе остаются)"
	@echo "  ps         Показать состояние контейнеров"
	@echo "  logs       Логи сервиса"
	@echo "  install    Установить PHP-зависимости локально (нужен composer)"
	@echo "  test       Прогнать тесты PHPUnit"
	@echo "  lint       Проверить синтаксис PHP"
	@echo "  seed       Перезалить учебные данные в уже поднятую базу"

up: ## Поднять сервис и базу (http://localhost:8080)
	$(COMPOSE) up -d --build
	@echo "Сервис: http://localhost:$${APP_PORT:-8080}/  ·  health: http://localhost:$${APP_PORT:-8080}/health"

down: ## Остановить сервис (данные в томе остаются)
	$(COMPOSE) down

ps: ## Показать состояние контейнеров
	$(COMPOSE) ps

logs: ## Логи сервиса
	$(COMPOSE) logs -f backend

install: ## Установить PHP-зависимости локально (нужен composer)
	composer install

test: ## Прогнать тесты PHPUnit
ifeq ($(LOCAL_PHP),yes)
ifneq ($(HAS_VENDOR),)
	php vendor/bin/phpunit --colors=always
else
	@echo "==> зависимостей нет, ставлю их локально"
	composer install --no-interaction --no-progress
	php vendor/bin/phpunit --colors=always
endif
else
	$(COMPOSE) run --rm --no-deps backend vendor/bin/phpunit --colors=always
endif

lint: ## Проверить синтаксис PHP во всех исходниках и тестах
ifeq ($(LOCAL_PHP),yes)
	php -r "$$dirs=['backend','tests']; foreach($$dirs as $$d){ $$ri=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($$d)); foreach($$ri as $$f){ if($$f->isFile()&&substr($$f,-4)==='.php'){ $$out=[]; exec('php -l '.escapeshellarg($$f->getPathname()), $$out, $$c); if($$c!==0){ fwrite(STDERR, implode(PHP_EOL, $$out).PHP_EOL); exit(1);} } } } echo 'php -l: ошибок нет'.PHP_EOL;"
else
	$(COMPOSE) run --rm --no-deps backend bash -lc "find backend tests -name '*.php' -print0 | xargs -0 -n1 php -l > /dev/null && echo 'php -l: ошибок нет'"
endif

seed: ## Перезалить учебные данные в уже поднятую базу
	$(COMPOSE) exec -T db mysql -ulab -plab carmoney_lab < db/seed.sql
