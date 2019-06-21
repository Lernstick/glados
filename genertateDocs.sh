#!/bin/sh

# set these paths to match your environment
GLADOS_PATH=/usr/share/glados
APIDOC_PATH=/usr/share/yii2/bin
OUTPUT=${GLADOS_PATH}

cd $APIDOC_PATH
./apidoc api $GLADOS_PATH $OUTPUT/web/docs/api --guide=../en --guidePrefix= --interactive=0
./apidoc guide $GLADOS_PATH/howtos    $GLADOS_PATH/web/docs/en --apiDocs=../api --guidePrefix= --interactive=0

# repeat this line for more languages
# ./apidoc guide $GLADOS_PATH/howtos/de $GLADOS_PATH/web/docs/de --apiDocs=../api --guidePrefix= --interactive=0

chown -R www-data:www-data "${OUTPUT}/web/docs"