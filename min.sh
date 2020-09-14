#!/bin/bash

# Supersimple minify script

echo "- Minify admin assets"
curl -X POST -s --data-urlencode 'input@admin/css/joinchat.css' https://cssminifier.com/raw > admin/css/joinchat.min.css
curl -X POST -s --data-urlencode 'input@admin/js/joinchat.js' https://javascript-minifier.com/raw > admin/js/joinchat.min.js
echo "- Minify public javascript"
# curl -X POST -s --data-urlencode 'input@public/css/joinchat.css' https://cssminifier.com/raw > public/css/joinchat.min.css
curl -X POST -s --data-urlencode 'input@public/js/joinchat.js' https://javascript-minifier.com/raw > public/js/joinchat.min.js
echo
echo "OK"
echo
exit 0