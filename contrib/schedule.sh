#!/usr/bin/env bash
set -e

if [ -z "$2" ]; then
  echo "Schedule task: $1"

  if [ "$1" -gt "0" ]; then
    echo "Reset timer for task: $1"
    docker compose run --rm mysql sh -c 'mysql -h0.0.0.0 -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "UPDATE vtiger_cron_task SET laststart = 0, lastend = 0, status = 1 WHERE id = '"$1"';"' || true
  exit 0
fi

echo "Schedule: $2"

variables=$(grep "^crm=$2 " .hosts | head -1)

if [ -z "$variables" ]; then
    echo "No such host: $2"
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
      "cd /opt/$crm && make schedule task=$1"
