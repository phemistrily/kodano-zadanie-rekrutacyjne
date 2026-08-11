up:
	docker compose up -d --build
down:
	docker compose down
sh:
	docker compose exec app bash
console:
	docker compose exec app php bin/console $(filter-out $@,$(MAKECMDGOALS))
install:
	docker compose exec app composer install
migrate:
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
jwt-keys:
	docker compose exec -T app php bin/console lexik:jwt:generate-keypair --skip-if-exists
logs:
	docker compose logs -f app
test-db:
	docker compose exec -T database mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS app_test; GRANT ALL PRIVILEGES ON app_test.* TO 'app'@'%'; FLUSH PRIVILEGES;"
test: jwt-keys test-db
	docker compose exec -T app php vendor/bin/phpunit
%:
	@:
