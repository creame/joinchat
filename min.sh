#!/bin/bash

# Supersimple minify script
# required:
# npm install uglify-js -g
# npm install uglifycss -g

echo "- Minify admin assets"
uglifyjs admin/js/joinchat.js --compress --mangle -o admin/js/joinchat.min.js
uglifyjs admin/js/joinchat-page.js --compress --mangle -o admin/js/joinchat-page.min.js
uglifyjs admin/js/joinchat-onboard.js --compress --mangle -o admin/js/joinchat-onboard.min.js
uglifycss admin/css/joinchat.css > admin/css/joinchat.min.css
uglifycss admin/css/joinchat-onboard.css > admin/css/joinchat-onboard.min.css
echo "- Minify public javascript"
uglifyjs public/js/joinchat.js --compress --mangle -o public/js/joinchat.min.js
uglifyjs public/js/joinchat-lite.js --compress --mangle -o public/js/joinchat-lite.min.js
echo
echo "OK"
echo
exit 0
