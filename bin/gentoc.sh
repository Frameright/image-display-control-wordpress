#!/usr/bin/env sh

set -e

if [ "$npm_execpath" = "" ]; then
    echo "Run me with 'yarn gentoc' instead"
    exit 1
fi

for md in $(git grep -l "<\\!-- toc -->"); do
    echo "$md"
    ./node_modules/.bin/markdown-toc -i "$md";
done
