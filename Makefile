.PHONY: install up down restart shell db-shell logs db-import fresh composer

install:
	@mkdir -p src/app src/public docker/mysql/init
	@if [ -f src/composer.json ] && [ ! -d src/vendor ]; then \
		echo "==> composer.json sin vendor/: instalando dependencias..."; \
		docker run --rm --user "$$(id -u):$$(id -g)" -v "$(PWD)/src":/app -w /app composer:latest install; \
	elif [ -d src/vendor ]; then \
		echo "==> vendor/ ya instalado, solo levantando."; \
	else \
		echo "==> no hay composer.json, nada que instalar."; \
	fi
	HOST_UID=$$(id -u) HOST_GID=$$(id -g) docker compose up -d --build
	@echo "App     -> http://localhost:$${APP_PORT:-8080}"
	@echo "Adminer -> http://localhost:$${ADMINER_PORT:-8081}"

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

shell:
	docker compose exec app bash

db-shell:
	docker compose exec mysql sh -c 'mysql -u"$$MYSQL_USER" -p"$$MYSQL_PASSWORD" "$$MYSQL_DATABASE"'

logs:
	docker compose logs -f

db-import:
	@test -n "$(FILE)" || (echo "uso: make db-import FILE=dump.sql" && exit 1)
	@test -f "$(FILE)" || (echo "no existe el archivo: $(FILE)" && exit 1)
	docker compose exec -T mysql sh -c 'mysql -u"$$MYSQL_USER" -p"$$MYSQL_PASSWORD" "$$MYSQL_DATABASE"' < "$(FILE)"

fresh:
	@read -p "Esto borra TODOS los datos de mysql-data. Escribi 'yes' para continuar: " confirm; \
	if [ "$$confirm" = "yes" ]; then \
		docker compose down -v; \
		HOST_UID=$$(id -u) HOST_GID=$$(id -g) docker compose up -d --build; \
	else \
		echo "Cancelado."; \
	fi

composer:
	@test -n "$(CMD)" || (echo "uso: make composer CMD=\"require x/y\"" && exit 1)
	docker compose exec app composer $(CMD)
