#!/bin/bash

set -x
set -e

SOURCE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
cd "${SOURCE_DIR}"

git fetch --all
git fetch --tags
git checkout .
git checkout master
git pull

# do actual update
docker compose run --rm php --entrypoint="./php-version-audit" --no-update --full-update

COMMIT_MESSAGE="Automatic updates."
LINES_ADDED=$(git diff --numstat docs/rules-v1.json | sed 's/^\([0-9]*\)\(.*\)/\1/g')
if [ "$LINES_ADDED" -gt "1" ]; then
   COMMIT_MESSAGE="${COMMIT_MESSAGE} Changes found @lightswitch05"
fi

git add ./docs/rules-v1.json
git commit -m "${COMMIT_MESSAGE}"

LAST_TAG=$(git tag -l --sort=v:refname | tail -1)
echo "Last tag: ${LAST_TAG}"
MAJOR_VERSION="${LAST_TAG%%.*}"
echo "Major version: ${MAJOR_VERSION}"

OLD_MINOR_VERSION="${LAST_TAG%.*}"
OLD_MINOR_VERSION="${OLD_MINOR_VERSION##*.}"
echo "Old Minor version: ${OLD_MINOR_VERSION}"
MINOR_VERSION=$(date +"%Y%m%d")
echo "New Minor version: ${MINOR_VERSION}"

if [[ "${OLD_MINOR_VERSION}" == "${MINOR_VERSION}" ]]; then
    PATCH_VERSION="${LAST_TAG##*.}"
    PATCH_VERSION="$((PATCH_VERSION+1))"
else
    PATCH_VERSION="0"
fi
echo "Patch version: ${PATCH_VERSION}"

NEW_TAG="${MAJOR_VERSION}.${MINOR_VERSION}.${PATCH_VERSION}"
echo "New tag: ${NEW_TAG}"

git tag "${NEW_TAG}"
git push
git push gitlab
git push origin : "${NEW_TAG}"
git push gitlab : "${NEW_TAG}"


