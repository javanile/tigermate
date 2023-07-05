#!/bin/bash
set -e

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

echo "==> Update"
git pull

if [ ! -f .env ]; then
  cp .env.examples .env
fi

echo "==> Restart"
make restart
