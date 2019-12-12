#!/bin/sh
set -eu

cat <<- EOF > $HOME/.netrc
        machine github.com
        login $GITHUB_ACTOR
        password $GITHUB_TOKEN
        machine api.github.com
        login $GITHUB_ACTOR
        password $GITHUB_TOKEN
EOF
chmod 600 $HOME/.netrc
git config --global user.email "daniel@developerdan.com"
git config --global user.name "Auto Updates"
git add ./docs/rules-v1.json
git commit -m "Automatic github actions updates"
LAST_TAG=$(git tag -l --sort=v:refname | tail -1)
echo "Last tag: ${LAST_TAG}"
NEW_TAG="${LAST_TAG%.*}.$((${LAST_TAG##*.}+1))"
echo "Last tag: ${NEW_TAG}"
git tag $NEW_TAG
git push
git push origin $NEW_TAG
