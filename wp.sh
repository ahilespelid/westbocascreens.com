#!/bin/sh
cd "$(dirname "$(readlink -f "$0")")"
set -a
. ./.env
set +a
docker run --rm --network apexflow_apexflow   -e WORDPRESS_DB_HOST=db -e WORDPRESS_DB_USER="$MYSQL_USER" -e WORDPRESS_DB_PASSWORD="$MYSQL_PASSWORD" -e WORDPRESS_DB_NAME="$MYSQL_DATABASE"   -v apexflow_wp_data:/var/www/html -v "$PWD/wp-content/mu-plugins:/var/www/html/wp-content/mu-plugins:ro" --user 33:33 wordpress:cli wp --path=/var/www/html --url=https://westbocascreens.com "$@"
