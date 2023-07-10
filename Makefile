
up:
	@docker compose up --build --force-recreate --remove-orphans -d

start: fix-permissions up
	@echo "Visit: http://localhost:8080"

restart: fix-permissions
	@docker compose up --build --force-recreate --remove-orphans -d

stop:
	@docker compose stop

fix-permissions:
	@touch lib/config.inc.php
	@chmod 777 lib/tabdata.php lib/config.inc.php lib/parent_tabdata.php
	@chmod 777 -R lib/cache lib/storage lib/user_privileges/ lib/test lib/modules lib/cron/modules lib/logs
	@chmod 777 -R public/layouts public/libraries public/resources public/test public/modules

install:
	@docker compose run --rm --no-deps tigermate composer install

require:
	@docker compose exec tigermate composer require tracy/tracy --prefer-dist --update-no-dev

clean: fix-permissions
	@rm -fr public/layouts/* public/libraries/* public/resources/* public/test/* public/modules/*
	@rm -fr lib/test/templates_c/*

release:
	@bash contrib/release.sh

deploy: release
	@bash contrib/deploy.sh $(crm)

shell:
	@bash contrib/shell.sh $(crm)

prepare:
	@bash contrib/prepare.sh

## =====
## Tests
## =====

test-deploy:
	@bash contrib/deploy.sh test
