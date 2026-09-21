<?php
/**
 * Подключение статики. CSS и JS вынесены в файлы рядом с кодом: их кэширует браузер,
 * их видно в диффе и их можно править, не трогая PHP.
 */

namespace ApexFlow;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

final class Assets
{
    /** @var string Хэндл основной таблицы стилей — им же пользуется WordPress при дедупликации. */
    private const STYLE_HANDLE = 'site-styles';

    /** @var string Хэндл скрипта ленивой загрузки видео на главной. */
    private const VIDEO_HANDLE = 'site-video';

    /**
     * Подписка на хуки модуля.
     *
     * @return void
     */
    public static function register(): void
    {
        // wp_enqueue_scripts — единственный правильный хук для регистрации статики фронтенда.
        add_action('wp_enqueue_scripts', [self::class, 'enqueue']);
    }

    /**
     * Ставит в очередь стили сайта и — только на главной — скрипт демо-видео.
     *
     * @return void
     */
    public static function enqueue(): void
    {
        // Общие стили нужны на каждой странице, поэтому подключаются безусловно.
        wp_enqueue_style(self::STYLE_HANDLE, self::url('site.css'), [], self::version('site.css'));

        // Скрипт видео нужен исключительно на главной — на остальных страницах видео нет.
        if (!is_front_page()) {
            return;
        }

        // true в последнем аргументе — скрипт уезжает в подвал и не блокирует отрисовку.
        wp_enqueue_script(self::VIDEO_HANDLE, self::url('video.js'), [], self::version('video.js'), true);
    }

    /**
     * URL файла из каталога assets текущего модуля.
     *
     * @param string $file_name Имя файла, например «site.css».
     * @return string Абсолютный URL файла.
     */
    private static function url(string $file_name): string
    {
        // WPMU_PLUGIN_URL задаётся ядром и учитывает нестандартное расположение wp-content.
        return WPMU_PLUGIN_URL . '/site-core/assets/' . $file_name;
    }

    /**
     * Версия файла для обхода кэша браузера — время последнего изменения.
     *
     * @param string $file_name Имя файла в каталоге assets.
     * @return string Метка версии; пустая строка, если файла почему-то нет.
     */
    private static function version(string $file_name): string
    {
        // Полный путь к файлу на диске: filemtime() работает с путями, а не с URL.
        $absolute_path = __DIR__ . '/assets/' . $file_name;

        // Файла нет — отдаём пустую версию, WordPress подставит версию ядра и не сломается.
        return is_file($absolute_path) ? (string) filemtime($absolute_path) : '';
    }
}
