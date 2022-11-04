#!/usr/bin/env sh

# On some systems, 'yarn' is named 'yarnpkg', thus this wrapper.

if which yarn; then
    yarn "$@"
    exit $?
fi
if which yarnpkg; then
    yarnpkg "$@"
    exit $?
fi
echo yarn not installed?
exit 1
