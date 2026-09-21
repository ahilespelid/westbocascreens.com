<?php
/**
 * Обход двух особенностей этой установки, из-за которых штатная карта сайта
 * wp-sitemap.xml отдавалась поисковикам как 404 и уходила в бесконечный редирект.
 */

namespace SiteCore;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

final class Sitemap
{
    /**
     * Подписка на хуки модуля.
     *
     * @return void
     */
    public static function register(): void
    {
        // pre_handle_404 — штатная точка, где можно сказать ядру «404 здесь не нужен».
        add_filter('pre_handle_404', [self::class, 'skipNotFound'], 10, 2);

        // Когда запрос перестаёт считаться 404, канонический редирект начинает зацикливать карту.
        add_filter('redirect_canonical', [self::class, 'skipCanonicalRedirect']);
    }

    /**
     * Не даёт ядру пометить запрос карты сайта как 404.
     *
     * @param bool $preempt Текущее решение ядра.
     * @param \WP_Query $wp_query Запрос, который сейчас обрабатывается.
     * @return bool true — обработка 404 пропускается.
     */
    public static function skipNotFound(bool $preempt, \WP_Query $wp_query): bool
    {
        // Переменная sitemap заполнена только у запросов к wp-sitemap.xml и его частям.
        return $wp_query->get('sitemap') ? true : $preempt;
    }

    /**
     * Отключает канонический редирект для запросов карты сайта.
     *
     * @param string|false $redirect_url Адрес, на который ядро собирается увести запрос.
     * @return string|false false — редиректа не будет.
     */
    public static function skipCanonicalRedirect(string|false $redirect_url): string|false
    {
        // Для всех остальных запросов канонический редирект полезен и остаётся включённым.
        return get_query_var('sitemap') ? false : $redirect_url;
    }
}
