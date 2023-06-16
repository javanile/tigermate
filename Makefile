
up:
	@docker compose up --build --force-recreate --remove-orphans -d

start: fix-permissions up
	@echo "Visit: http://localhost:8080"

fix-permissions:
	@chmod 777 -R lib/test/templates_c public/layouts

install:
	@docker-compose exec tigermate composer install

require:
	@docker-compose exec tigermate composer require tracy/tracy --prefer-dist --update-no-dev
