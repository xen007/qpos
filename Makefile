setup:
	@make docker-up-build
	@make composer-install
	@make set-permissions
	@make setup-env
	@make generate-key
	@make migrate-seed
	@make qpos-admin-create
	@make npm-install-build
	@make npm-run-dev

docker-stop:
	docker compose stop

docker-up-build:
	docker compose up -d --build

composer-install:
	docker exec qpos-app bash -c "composer install"

composer-update:
	docker exec qpos-app bash -c "composer update"

set-permissions:
	docker exec qpos-app bash -c "chmod -R 777 /var/www/storage"
	docker exec qpos-app bash -c "chmod -R 777 /var/www/bootstrap/cache"

setup-env:
	docker exec qpos-app sh -c "test -f .env || cp .env.docker .env"

npm-install-build:
	docker exec qpos-node bash -c "npm ci"
	docker exec qpos-node bash -c "npm run build:docker"

npm-run-dev:
	docker exec qpos-node bash -c "npm run dev:docker"

npm-run-build:
	docker exec qpos-node bash -c "npm run build:docker"

generate-key:
	docker exec qpos-app sh -c "grep -qE '^APP_KEY=base64:.+' .env || php artisan key:generate"

migrate-seed:
	docker exec qpos-app bash -c "php artisan migrate --seed"

qpos-admin-create:
	docker exec -it qpos-app php artisan qpos:admin:create

migrate-fresh-seed:
	@echo "WARNING: This deletes all database tables and data. Use only for a disposable development database."
	docker exec qpos-app bash -c "php artisan migrate:fresh --seed"
