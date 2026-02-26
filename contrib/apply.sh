#!/bin/bash
set -e

trap 'echo "❌ ERRORE in linea $LINENO"
echo "   Comando: $BASH_COMMAND"
echo "   Exit code: $?"
' ERR

for variable in $*; do declare "$variable"; done

grant() {
  # echo "$1" | sudo -S git pull
  return 0
}

if [ -z "$(command -v make)" ]; then
  apt install -y make
fi

if [ -z "$(command -v docker)" ]; then
  curl get.javanile.org/docker | bash
fi

if [ ! -d "/opt/$crm" ]; then
  git clone --branch main --single-branch "https://github.com/javanile/tigermate.git" "/opt/$crm"
fi

cd "/opt/$crm"

echo "==> Install"
if [ ! -d "vendor" ]; then
  make install || true
fi

echo "==> Update"
make prepare
git pull --force --no-rebase

if [ ! -f .env ]; then
  cp .env.examples .env
fi

## Prepare .env
cp .env.examples .env
sed -i "s/CRM_HOST=.*/CRM_HOST=$crm_host/g" .env
sed -i "s/GOOGLE_CLIENT_ID=.*/GOOGLE_CLIENT_ID=$google_client_id/g" .env
sed -i "s/GOOGLE_CLIENT_SECRET=.*/GOOGLE_CLIENT_SECRET=$google_client_secret/g" .env
touch lib/config.inc.php

## Prepare Google connector
cp lib/modules/Google/connectors/Config.php.examples lib/modules/Google/connectors/Config.php
sed -i \
  -e "s|static \$clientId = ''|static \$clientId = '${google_client_id}'|" \
  -e "s|static \$clientSecret = ''|static \$clientSecret = '${google_client_secret}'|" \
  lib/modules/Google/connectors/Config.php

echo "==> Restart services"
make apply

echo "==> Visit https://$crm_host/"
