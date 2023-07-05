
up:
	@docker compose up --build --force-recreate --remove-orphans -d

start: fix-permissions up
	@echo "Visit: http://localhost:8080"

fix-permissions:
	@chmod 777 lib/tabdata.php lib/config.inc.php lib/parent_tabdata.php
	@chmod 777 -R lib/cache lib/storage lib/user_privileges/ lib/test lib/modules lib/cron/modules lib/logs
	@chmod 777 -R public/layouts public/libraries public/resources public/test public/modules

install:
	@docker-compose exec tigermate composer install

require:
	@docker-compose exec tigermate composer require tracy/tracy --prefer-dist --update-no-dev

clean: fix-permissions
	@rm -fr public/layouts/* public/libraries/* public/resources/* public/test/* public/modules/*
	@rm -fr lib/test/templates_c/*

deploy:
	@git add .
	@git commit -am "deploy"
	@git push
	@git push heroku main
