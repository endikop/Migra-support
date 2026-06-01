#!/bin/sh
set -e
mkdir -p /usr/local/etc/php/conf.d || true
install -m644 .nixpacks/conf.d/custom.ini /usr/local/etc/php/conf.d/custom.ini || cp .nixpacks/conf.d/custom.ini /tmp/custom.ini || true
mkdir -p /app 
chown -R www-data:www-data /app 
chmod -R 775 /app
