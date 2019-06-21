#!/bin/bash

# set these paths to match your environment
GLADOS_PATH=/usr/share/glados
APIDOC_PATH=/usr/share/yii2/bin

cd $APIDOC_PATH
./apidoc api   "$GLADOS_PATH"        "${GLADOS_PATH}/web/docs/api" --guide=../en    --guidePrefix= --interactive=0
./apidoc guide "$GLADOS_PATH/howtos" "${GLADOS_PATH}/web/docs/en"  --apiDocs=../api --guidePrefix= --interactive=0

# repeat those lines for more languages
# ./apidoc guide $GLADOS_PATH/howtos/de $GLADOS_PATH/web/docs/de --apiDocs=../api --guidePrefix= --interactive=0
# cp -R "${GLADOS_PATH}/howtos/de/img" "${GLADOS_PATH}/web/docs/de/img"

# copy the images over to web/docs
cp -R "${GLADOS_PATH}/howtos/img" "${GLADOS_PATH}/web/docs/en/img"

# adjust the permissions of the docs directory
chown -R www-data:www-data "${GLADOS_PATH}/web/docs"

while true; do
    read -p "Do you wish to publish the subtree web/docs to gh-pages branch?
(git subtree push --prefix web/docs origin gh-pages) [yes/no]" yn
    case $yn in
        [Yy]* ) cd "${GLADOS_PATH}"; git subtree push --prefix "web/docs" origin gh-pages; break;;
        [Nn]* ) exit;;
        * ) echo "Please answer yes or no.";;
    esac
done