#!/usr/bin/env bash

cp dist/styles.min.css ../web/css/styles.min.css
cp dist/bundle.web.js ../web/js/bundle.web.js

DIRECTORY=../../../../../public/extensions/vendor/cnd/relationlist/

if [ -d "$DIRECTORY" ]; then
    cp -r ../web/* $DIRECTORY
fi
