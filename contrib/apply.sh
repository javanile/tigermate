#!/bin/bash
set -e

for variable in $*; do
  declare "$variable"
done

echo "crm: $crm"

grant() {
  # echo "$1" | sudo -S git pull
  return 0
}

if [ ! -d "/opt/$crm" ]; then
  git clone git clone --branch main --single-branch "https://github.com/javanile/tigermate.git" "/opt/$crm"
fi

cd "/opt/$crm"

echo "==> Update"
git pull

if [ ! -f .env ]; then
  cp .env.examples .env
fi

echo "==> Restart"
make restart
