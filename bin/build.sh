#!/usr/bin/env bash

set -e

if [ "$COMPOSER_BINARY" = "" ]; then
    echo "Run me with 'composer build' instead"
    exit 1
fi

mkdir -p build/
tmpdir="$(mktemp -d -p ./build/)"
echo "Building in $tmpdir ..."

cp -r *.php languages/ license.txt readme.txt src/ "$tmpdir"

target="$(pwd)/image-display-control.zip"
rm -f "$target"

cd "$tmpdir"
zip -r "$target" .
