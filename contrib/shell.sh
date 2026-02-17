#!/usr/bin/env bash
set -e

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
      -p "${ssh_port:-22}"