#!/usr/bin/env bash
set -e

if [ -z "$2" ]; then
  echo "Schedule task: $1"

  exit 0
fi

echo "Schedule: $2"

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
      "cd /opt/$crm && make schedule task=$1"
