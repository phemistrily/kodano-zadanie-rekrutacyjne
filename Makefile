up:
	docker compose up -d --build
down:
	docker compose down
sh:
	docker compose exec app bash
console:
	docker compose exec app php bin/console $(filter-out $@,$(MAKECMDGOALS))
%:
	@:
