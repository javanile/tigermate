
up:
	@docker compose up --build --force-recreate --remove-orphans -d

init:
	@find lib/init -maxdepth 2 -type f | while read f; do \
		dest="lib/$$(basename $$f)"; \
		if [ ! -f "$$dest" ]; then cp "$$f" "$$dest"; fi; \
	done

start: init fix-permissions up watch-assets
	@echo "Visit: http://localhost:8080"

restart: fix-permissions
	@echo "Cleaning cached files..."
	@docker compose run --rm tigermate bash -c "rm -fr public/layouts/* public/libraries/* public/resources/* public/modules/*"
	@echo "Restarting containers..."
	@docker compose up --build --force-recreate --remove-orphans -d || true
	@echo "Refreshing containers..."
	@docker compose up -d && sleep 10
	@bash contrib/watch.sh restart

stop:
	@docker compose stop
	@bash contrib/watch.sh stop

watch-assets:
	@bash contrib/watch.sh start

watch-assets-stop:
	@bash contrib/watch.sh stop

watch-assets-status:
	@bash contrib/watch.sh status

fix-permissions:
	@touch lib/config.inc.php
	@docker compose run --rm tigermate bash -c "chmod 777 -R lib/modules"
	@docker compose run --rm tigermate bash -c "\
		mkdir -p lib/languages/custom && \
		chown -R www-data:www-data lib/languages/custom \
	"
	@docker compose run --rm tigermate bash -c "\
		chmod 777 \
			lib/tabdata.php lib/config.inc.php lib/parent_tabdata.php \
			lib/cache lib/storage lib/user_privileges/ lib/test lib/modules lib/cron/modules lib/logs \
			public/layouts public/libraries public/resources public/test public/modules \
			lib/cache/images/ lib/cache/import/ lib/test/vtlib/ lib/test/vtlib/HTML lib/test/wordtemplatedownload/ \
			lib/test/product/ lib/test/user/ lib/test/contact/ lib/test/logo/ \
		"

deep-reset:
	@read -p "Sei sicuro di voler fare il DEEP RESET? Cancella tutte le tabelle e svuota config.inc.php [s/N]: " confirm; \
	if [ "$$confirm" = "s" ] || [ "$$confirm" = "S" ]; then \
		echo "Dropping all tables..."; \
		docker compose run --rm tigermate bash -c " \
			mysql -h mysql -uroot -psecret tigermate -e 'SET FOREIGN_KEY_CHECKS=0;' && \
			mysql -h mysql -uroot -psecret tigermate -sNe \
				\"SELECT CONCAT('DROP TABLE IF EXISTS \\\`',table_name,'\\\`;') \
				FROM information_schema.tables WHERE table_schema='tigermate';\" \
			| mysql -h mysql -uroot -psecret tigermate && \
			mysql -h mysql -uroot -psecret tigermate -e 'SET FOREIGN_KEY_CHECKS=1;'"; \
		echo "Svuoto config.inc.php..."; \
		> lib/config.inc.php; \
		echo "Deep reset completato."; \
	else \
		echo "Operazione annullata."; \
	fi

install:
	@docker compose run --rm --no-deps tigermate composer install && true

require:
	@docker compose exec tigermate composer require tracy/tracy --prefer-dist --update-no-dev

clean: fix-permissions
	@rm -fr public/layouts/* public/libraries/* public/resources/* public/test/* public/modules/*
	@rm -fr lib/test/templates_c/*

apply: restart migrate
	@echo "System is up and running."

release:
	@bash contrib/release.sh

deploy: release
	@bash contrib/deploy.sh $(crm)

shell:
	@bash contrib/shell.sh $(crm)

mysql:
	@bash contrib/mysql.sh $(crm)

reset:
	@bash contrib/reset.sh $(crm)

schedule:
	@bash contrib/schedule.sh "$(task)" "$(crm)"

prepare:
	@bash contrib/prepare.sh

migrate:
	@docker compose exec tigermate bash -c " \
		cd lib; XDEBUG_CONFIG='remote_enable=1' \
		  PHP_IDE_CONFIG='serverName=localhost' php -f migrate.php" || true

dev-push:
	@git add .
	@git commit -m "$$(date +'%Y-%m-%d %H:%M:%S') - dev push" || true
	@git push

## =====
## Tests
## =====

test-deploy:
	@bash contrib/deploy.sh test

test-phpinfo:
	@docker compose up --build --force-recreate --remove-orphans -d
	@echo "<?php phpinfo();" > public/phpinfo.php
	@echo "Visit: http://localhost:8080/phpinfo.php"

test-crontab:
	@docker compose up -d
	@docker compose up --force-recreate --remove-orphans -d crontab
	@docker compose logs -f crontab
