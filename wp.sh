#!/bin/sh
cd "$(dirname "$(readlink -f "$0")")"
set -a
. ./.env
set +a
docker run --rm --network westbocascreens_internal   -e WORDPRESS_DB_HOST=db -e WORDPRESS_DB_USER="$MYSQL_USER" -e WORDPRESS_DB_PASSWORD="$MYSQL_PASSWORD" -e WORDPRESS_DB_NAME="$MYSQL_DATABASE"   -v "$PWD/wordpress:/var/www/html" --user 33:33 wordpress:cli wp --path=/var/www/html --url=https://westbocascreens.com "$@"
