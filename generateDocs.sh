#!/bin/bash

# set these paths to match your environment
GLADOS_PATH=/usr/share/glados
APIDOC_PATH=/usr/share/yii2/bin
DATE="$(date -R)"
PWD="$(pwd)"

# remove the worktree by deleting it
rm -R "${GLADOS_PATH}/web/docs" 2>/dev/null
# remove the worktree from .git 
git worktree prune
# add the worktree again (downloads the current version of it)
git worktree add "web/docs" gh-pages
# remove all contents of the folder (will be generated again, see below)
# this will remain the .git directory in web/docs
rm -R ${GLADOS_PATH}/web/docs/* 2>/dev/null

# generate the api and guide documentation
cd $APIDOC_PATH
./apidoc api   "$GLADOS_PATH"        "${GLADOS_PATH}/web/docs/api" --guide=../    --guidePrefix= --interactive=0 --page-title="GLaDOS Documentation"
./apidoc guide "$GLADOS_PATH/howtos" "${GLADOS_PATH}/web/docs"  --apiDocs=api --guidePrefix= --interactive=0 --page-title="GLaDOS Guide"

# repeat those lines for more languages (de)
# ./apidoc guide "$GLADOS_PATH/howtos/de" "${GLADOS_PATH}/web/docs/de"  --apiDocs=../api --guidePrefix= --interactive=0 --page-title="GLaDOS Dokumentation"
# cp -R "${GLADOS_PATH}/howtos/de/img" "${GLADOS_PATH}/web/docs/de/img"

# copy the images over to web/docs
cp -R "${GLADOS_PATH}/howtos/img" "${GLADOS_PATH}/web/docs/img"

# adjust the permissions of the docs directory
chown -R www-data:www-data "${GLADOS_PATH}/web/docs"

# pblish the web/docs dir to gh-pages it desired
while true; do
    read -p "Do you wish to publish the subtree web/docs to gh-pages branch?
* cd web/docs
* git add --all;
* git commit -m \"Deploy to gh-pages (${DATE})\"
* git push origin gh-pages
* cd -
[yes/no]" yn
    case $yn in
        [Yy]* ) cd "${GLADOS_PATH}/web/docs"; git add --all; git commit -m "Deploy to gh-pages (${DATE})"; git push origin gh-pages; break;;
        [Nn]* ) cd "${PWD}"; exit;;
        * ) echo "Please answer yes or no.";;
    esac
done

cd "${PWD}"