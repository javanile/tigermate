#!/usr/bin/env bash
set -e

if [ -z "$1" ]; then
  echo "ATTENTION: You can lost all data! Type 'YES' to continue:"
  read AGREE

  if [ "${AGREE}" = "YES" ]; then
    echo '' > lib/config.inc.php
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

sshpass -p "${ssh_password}" ssh -t "${ssh_user}@${ssh_host}" -p "${ssh_port:-22}" "cd /opt/$crm && make reset"
