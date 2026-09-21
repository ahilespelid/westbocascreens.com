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
        // Подмена нужна только для страниц: записи, отзывы и прочие типы не трогаем.
        if (get_post_type() !== 'page') {
            return $database_content;
        }

        // Сама страница в основном цикле — или REST API: он строит content.rendered вне
        // цикла, и без этого условия отдавал бы наружу устаревший текст из базы.
        $is_page_view = is_singular('page') && in_the_loop() && is_main_query();
        $is_rest_request = defined('REST_REQUEST') && REST_REQUEST;

        // В списках и виджетах the_content вызывается для чужих записей — туда не лезем.
        if (!$is_page_view && !$is_rest_request) {
            return $database_content;
        }

        // Разметка из файла; файла нет — остаётся содержимое базы, как раньше.
        return self::render((string) get_post_field('post_name', get_the_ID())) ?? $database_content;
    }

    /**
     * Разметка страницы из её файла, до раскрытия шорткодов.
     *
     * @param string $slug Слаг страницы.
     * @return string|null Разметка или null, если файла для этого слага нет.
     */
    public static function render(string $slug): ?string
    {
        // Путь к файлу страницы; null означает «файла нет» или «слаг подозрительный».
        $template_path = self::templatePath($slug);

        // Нечего рендерить — вызывающий код сам решает, чем это заменить.
        if ($template_path === null) {
            return null;
        }

        // Буферизация: файл печатает разметку, а вернуть её нужно строкой.
        ob_start();

        // include, а не file_get_contents: внутри файла доступны PHP, Config и хелперы.
        include $template_path;

        return (string) ob_get_clean();
    }

    /**
     * Слаги всех страниц, у которых есть файл разметки.
     *
     * @return string[] Например: ['home', 'reviews', …].
     */
    public static function slugs(): array
    {
        // Имя файла без расширения и есть слаг страницы.
        return array_map(static fn (string $file_path): string => basename($file_path, '.php'), glob(self::DIR . '/*.php') ?: []);
    }

    /**
     * Полный путь к файлу страницы по её слагу.
     *
     * @param string $slug Слаг страницы.
     * @return string|null Путь к существующему файлу или null.
     */
    private static function templatePath(string $slug): ?string
    {
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
