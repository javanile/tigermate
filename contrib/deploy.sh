#!/usr/bin/env bash

echo "Deploy: $1"

crm=$(grep "^crm=$1 " .hosts | head -1)

if [ -z "$crm" ]; then
    echo "No such host: $1"
    exit 1
fi

for pair in $crm; do
  declare "$pair"
done

