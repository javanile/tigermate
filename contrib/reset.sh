#!/usr/bin/env bash
set -e

if [ -z "$1" ]; then
  echo "ATTENTION: You can lost all data! Type 'YES' to continue:"
  read AGREE

  if [ "${AGREE}" = "YES" ]; then
    echo "Cleaning configuration..."
    rm lib/config.inc.php
    touch lib/config.inc.php
    chmod 777 lib/config.inc.php
    echo "Cleaning database..."
    docker compose down -v mysql
    docker compose run --rm mysql sh -c "rm -rf /var/lib/mysql/{*,.*} && chown -R 999:999 /var/lib/mysql" || true
    docker compose up -d mysql
    echo "Done!"
  fi

  exit 0
fi

echo "Connecting to: $1"

variables=$(grep "^crm=$1 " .hosts | head -1)

if [ -z "$variables" ]; then
    echo "No such host: $1"
    exit 1
fi

for variable in $variables; do
  declare "$variable"
done

sshpass -p "${ssh_password}" \
  ssh -o StrictHostKeyChecking=no \
      -o UserKnownHostsFile=/dev/null \
      "${ssh_user}@${ssh_host}" \
      -p "${ssh_port:-22}" \
      "cd /opt/$crm && make reset"
