# westbocascreens.com

WordPress-сайт https://westbocascreens.com. Стек в Docker Compose (проект `westbocascreens`): nginx → WordPress (php-fpm) → MySQL. Наружу его отдаёт Traefik из `/opt/edge` (Let's Encrypt, сеть `edge`).

## Структура

- `wordpress/`: вся файловая система WordPress (ядро, темы, плагины, mu-plugins, загрузки). Монтируется в контейнеры как `/var/www/html`. Не хранятся в git только `wp-config.php` (в нём соли) и кэш.
- `wordpress/wp-content/mu-plugins/apexflow-core.php`: кастомная логика сайта (шорткоды, разметка Schema.org, отзывы, футер).
- `docker-compose.yml`: стек. Тома `westbocascreens_db_data` (MySQL) и `westbocascreens_fastcgi_cache`, сеть `westbocascreens_internal`.
- `nginx/conf.d/default.conf`: конфиг nginx и fastcgi-кэш.
- `wp.sh`: обёртка над wp-cli, например `./wp.sh option get siteurl`.
- `.env.example`: список переменных окружения. Настоящий `.env` в git не хранится.

Корень репозитория не является docroot: nginx видит только `wordpress/`, поэтому `.env` и `.git` снаружи недоступны.

## Чего нет в git

- База (страницы, отзывы, настройки) лежит в томе `westbocascreens_db_data`.
- `wordpress/wp-config.php` создаёт образ WordPress при первом запуске из переменных окружения.

## Деплой на сервере

    cd /var/www/westbocascreens.com
    git pull --ff-only
    chown -R 33:33 wordpress    # php-fpm работает от www-data (uid 33)

- изменился `nginx/conf.d/*.conf`: `docker compose exec nginx nginx -t && docker compose exec nginx nginx -s reload`;
- изменился `docker-compose.yml`: `docker compose up -d`;
- правки ядра WordPress перезапишут автообновления, перед этим их нужно отключить (`WP_AUTO_UPDATE_CORE` в `wp-config.php`).

## Проверка целостности

    ./wp.sh core verify-checksums --exclude=wp-config-docker.php
    ./wp.sh plugin verify-checksums --all
