# westbocascreens.com

WordPress-сайт https://westbocascreens.com. Стек в Docker Compose: nginx → WordPress (php-fpm) → MySQL. Наружу его отдаёт Traefik из `/opt/edge` (Let's Encrypt, сеть `edge`).

## Что в репозитории

- `docker-compose.yml`: сам стек. Имя проекта зафиксировано (`name: apexflow`), поэтому тома `apexflow_*` и сеть `apexflow_apexflow` не меняются при переносе каталога.
- `nginx/conf.d/default.conf`: конфиг nginx и fastcgi-кэш.
- `wp-content/mu-plugins/apexflow-core.php`: вся кастомная логика сайта (шорткоды, разметка Schema.org, отзывы, футер). Монтируется в контейнеры read-only; копия этого файла внутри тома `apexflow_wp_data` затенена и не используется.
- `wp.sh`: обёртка над wp-cli, например `./wp.sh option get siteurl`.
- `.env.example`: список переменных окружения. Настоящий `.env` в git не хранится.

## Чего в репозитории нет

Это данные, а не код, они живут в docker-томах на сервере:

- ядро WordPress, плагины, тема GeneratePress, загрузки: том `apexflow_wp_data`;
- база (страницы, отзывы, настройки, сниппеты WPCode): том `apexflow_db_data`.

## Деплой

    cd /var/www/westbocascreens.com
    git pull --ff-only

- правка `wp-content/mu-plugins/*.php` применяется сразу после `git pull`;
- правка `nginx/conf.d/*.conf`: `docker compose exec nginx nginx -t && docker compose exec nginx nginx -s reload`;
- правка `docker-compose.yml`: `docker compose up -d`.
