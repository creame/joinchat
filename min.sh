#!/bin/bash

# Supersimple minify script
# required:
# npm install uglify-js -g
# npm install uglifycss -g

echo "- Minify admin assets"
uglifyjs admin/js/joinchat.js --compress --mangle -o admin/js/joinchat.min.js
uglifycss admin/css/joinchat.css > admin/css/joinchat.min.css
echo "- Minify public javascript"
uglifyjs public/js/joinchat.js --compress --mangle -o public/js/joinchat.min.js
echo
echo "OK"
echo
exit 0