<?php
/**
 * Разметка страниц из файлов репозитория.
 *
 * Если для страницы есть файл content/<slug>.php, отдаётся он, а содержимое из базы
 * игнорируется. Файла нет — всё работает как раньше. Так вёрстка живёт в git: её видно
 * в диффе, её можно откатить коммитом и она не зависит от состояния базы.
 */

namespace SiteCore;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

final class Content
{
    /** @var string Каталог с файлами страниц относительно текущего модуля. */
    private const DIR = __DIR__ . '/content';

    /**
     * Подписка на хуки модуля.
     *
     * @return void
     */
    public static function register(): void
    {
        // Приоритет 9 — раньше ядрового do_shortcode (11), чтобы шорткоды внутри файла тоже раскрылись.
        add_filter('the_content', [self::class, 'filterContent'], 9);
    }

    /**
     * Подменяет содержимое страницы разметкой из файла, если такой файл есть.
     *
     * @param string $database_content Содержимое, пришедшее из базы.
     * @return string Разметка из файла либо исходное содержимое без изменений.
     */
    public static function filterContent(string $database_content): string
    {
        // Подмена нужна только для самой страницы в основном запросе: в списках и
        // виджетах the_content вызывается для чужих записей, туда лезть нельзя.
        if (!is_singular('page') || !in_the_loop() || !is_main_query()) {
            return $database_content;
        }

        // Путь к файлу страницы; null означает «файла нет» или «слаг подозрительный».
        $template_path = self::templatePath(get_post_field('post_name', get_the_ID()));

        // Файла нет — возвращаем содержимое базы, поведение остаётся прежним.
        if ($template_path === null) {
            return $database_content;
        }

        // Буферизация: файл печатает разметку, а фильтру её нужно вернуть строкой.
        ob_start();

        // include, а не file_get_contents: внутри файла доступны PHP, Config и хелперы.
        include $template_path;

        return (string) ob_get_clean();
    }

    /**
     * Полный путь к файлу страницы по её слагу.
     *
     * @param mixed $raw_slug Слаг страницы, как его вернул WordPress.
     * @return string|null Путь к существующему файлу или null.
     */
    private static function templatePath(mixed $raw_slug): ?string
    {
        // Приведение к строке: get_post_field может вернуть false для несуществующей записи.
        $slug = (string) $raw_slug;

        // Разрешены только латиница, цифры и дефис — это отсекает любой обход каталогов.
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            return null;
        }

        // Имя файла совпадает со слагом: страница /reviews/ рендерится из content/reviews.php.
        $template_path = self::DIR . '/' . $slug . '.php';

        // is_file, а не file_exists: каталог с таким именем файлом страницы быть не может.
        return is_file($template_path) ? $template_path : null;
    }
}
