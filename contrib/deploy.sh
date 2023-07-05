#!/usr/bin/env bash

curl -sL git.io/dist.sh | bash -

git add .
git commit -am "release"
git push
