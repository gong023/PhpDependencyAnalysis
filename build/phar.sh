#!/bin/sh

composer install --no-dev
`dirname "$0"`/box build -vv
mv ./download/phpda.phar ./download/phpda
mv ./download/phpda.phar.pubkey ./download/phpda.pubkey
sha1sum ./download/phpda > ./download/phpda.version
chmod +x ./download/phpda
composer install
