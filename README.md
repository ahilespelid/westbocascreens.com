# westbocascreens.com

WordPress-сайт https://westbocascreens.com. Стек в Docker Compose (проект `westbocascreens`): nginx → WordPress (php-fpm) → MySQL. Наружу его отдаёт Traefik из `/opt/edge` (Let's Encrypt, сеть `edge`).

Главная ветка — `master`. Сервер `apexflowus` (2.25.157.28) держит рабочее дерево в `/var/www/westbocascreens.com` и сам подтягивает `master` в течение ~20 секунд после мержа.

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

## Как вносить правки

Работаем только локально. На сервере в репозитории руками не коммитим: сервер умеет лишь перематывать ветку вперёд, и собственный коммит на нём разведёт историю — деплой встанет с ошибкой.

    git switch -c short-branch-name master
    # правки
    git commit -am "что сделано"
    git push -u origin short-branch-name
    # pull request в master, мерж на GitHub
    git switch master && git pull --ff-only && git branch -d short-branch-name

Через ~20 секунд после мержа правки видно на сайте.

## Автодеплой: служба `deploy`

Универсальная служба на сервере (`/usr/local/sbin/deploy`, юнит `deploy.service`) каждые 20 секунд сравнивает `origin/master` с рабочим деревом и при новом коммите делает `git merge --ff-only`. Никаких `reset --hard`: если история разошлась или на сервере остались незакоммиченные правки, в журнал пишется ERROR, а дерево остаётся нетронутым.

После успешного деплоя выполняется `/etc/deploy/hooks/westbocascreens.com` — он лежит на сервере, вне репозитория, потому что это конфигурация сервера, а не кода:

- `chown -R 33:33 wordpress/` — php-fpm работает от www-data (uid 33);
- сброс FastCGI-кэша nginx, если менялось `wordpress/` или `nginx/`;
- `nginx -t`, затем `nginx -s reload`, если менялось `nginx/conf.d/`;
- проверка, что сайт отвечает 200 — иначе в журнал идёт предупреждение;
- `docker-compose.yml` автоматически не применяется: в журнале остаётся отметка, что нужен `docker compose up -d` руками.

Команды на сервере:

    service deploy status              что отслеживается, как включить и выключить
    deploy status                      подробная картина по всем проектам
    deploy pause  westbocascreens.com  выключить слежение (например, на время ручной отладки)
    deploy resume westbocascreens.com  включить обратно
    deploy log -f                      журнал (/var/log/deploy.log)

Слежение сразу за всеми проектами выключается и включается через `service deploy stop` и `service deploy start`.

## Ручной деплой

Нужен, только если слежение выключено:

    cd /var/www/westbocascreens.com
    git pull --ff-only
    chown -R 33:33 wordpress

- изменился `nginx/conf.d/*.conf`: `docker compose exec nginx nginx -t && docker compose exec nginx nginx -s reload`;
- изменился `docker-compose.yml`: `docker compose up -d`;
- правки ядра WordPress перезапишут автообновления, перед этим их нужно отключить (`WP_AUTO_UPDATE_CORE` в `wp-config.php`).

## Проверка целостности

    ./wp.sh core verify-checksums --exclude=wp-config-docker.php
    ./wp.sh plugin verify-checksums --all
