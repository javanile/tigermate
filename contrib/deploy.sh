#!/usr/bin/env bash
set -e

echo "Deploy: $1"

variables=$(grep "^crm=$1 " .hosts | head -1)

if [ -z "$variables" ]; then
    echo "No such host: $1"
    exit 1
fi

for variable in $variables; do
  declare "$variable"
done

sshpass -p "${password}" \
    ssh "${user}@${host}" \
        -p "${port:-22}" bash -s -- "${variables}" < contrib/apply.sh
