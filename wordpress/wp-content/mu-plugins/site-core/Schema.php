<?php
/**
 * Разметка Schema.org (JSON-LD): карточка бизнеса, услуга страницы, хлебные крошки
 * и средняя оценка на странице отзывов. Всё печатается одним общим методом.
 */

namespace SiteCore;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

final class Schema
{
    /** @var string Тип бизнеса по словарю Schema.org — строительство и работы по дому. */
    private const BUSINESS_TYPE = 'HomeAndConstructionBusiness';

    /** @var string Мета-поле с названием услуги, которой посвящена страница. */
    private const META_SERVICE = '_sc_service_name';

    /** @var string Мета-поле с описанием страницы — переиспользуется в описании услуги. */
    private const META_DESCRIPTION = '_sc_meta_description';

    /**
     * Подписка на хуки модуля. Приоритеты сохраняют прежний порядок блоков в <head>.
     *
     * @return void
     */
    public static function register(): void
    {
        // 5 — карточка бизнеса, она нужна на каждой странице.
        add_action('wp_head', [self::class, 'renderBusiness'], 5);

        // 6 — услуга, только для страниц с заполненным мета-полем.
        add_action('wp_head', [self::class, 'renderService'], 6);

        // 7 — средняя оценка, только на странице отзывов.
        add_action('wp_head', [self::class, 'renderAggregateRating'], 7);

        // 8 — хлебные крошки на внутренних страницах.
        add_action('wp_head', [self::class, 'renderBreadcrumbs'], 8);
    }

    /**
     * Карточка бизнеса: название, телефон, адрес, зона обслуживания.
     *
     * @return void
     */
    public static function renderBusiness(): void
    {
        // Адрес без улицы: у компании выездной формат работы, точки продаж нет.
        self::printJsonLd([
            '@context'   => 'https://schema.org',
            '@type'      => self::BUSINESS_TYPE,
            'name'       => Config::BRAND,
            'image'      => Config::contentUrl(Config::HERO_IMAGE),
            'telephone'  => Config::PHONE_DISPLAY,
            'address'    => [
                '@type'           => 'PostalAddress',
                'addressLocality' => Config::CITY,
                'addressRegion'   => Config::REGION,
                'addressCountry'  => Config::COUNTRY,
            ],
            'areaServed' => Config::areaServed(),
            'priceRange' => '$$$',
            'url'        => home_url('/'),
        ]);
    }

    /**
     * Услуга страницы — печатается, только если у страницы задано мета-поле с названием.
     *
     * @return void
     */
    public static function renderService(): void
    {
        // Схема услуги осмысленна только на отдельной странице услуги.
        if (!is_singular('page')) {
            return;
        }

        // Название услуги — оно же признак того, что страница посвящена услуге.
        $service_name = (string) get_post_meta(get_the_ID(), self::META_SERVICE, true);

        // Поле не заполнено — страница не про услугу, схема не нужна.
        if ($service_name === '') {
            return;
        }

        // Описание берём из того же мета-поля, что и SEO-description: дублировать текст незачем.
        self::printJsonLd([
            '@context'    => 'https://schema.org',
            '@type'       => 'Service',
            'name'        => $service_name,
            'serviceType' => $service_name,
            'description' => (string) get_post_meta(get_the_ID(), self::META_DESCRIPTION, true),
            'provider'    => ['@type' => self::BUSINESS_TYPE, 'name' => Config::BRAND],
            'areaServed'  => Config::areaServed(),
            'url'         => get_permalink(),
        ]);
    }

    /**
     * Хлебные крошки «Главная → Страница»: помогают поисковику показать путь в выдаче.
     *
     * @return void
     */
    public static function renderBreadcrumbs(): void
    {
        // На главной крошки вырождаются в одну ссылку на саму себя — печатать нечего.
        if (!is_singular('page') || is_front_page()) {
            return;
        }

        // Всего два уровня: сайт плоский, вложенных разделов нет.
        self::printJsonLd([
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => get_the_title(), 'item' => get_permalink()],
            ],
        ]);
    }

    /**
     * Средняя оценка по опубликованным отзывам — только на странице отзывов.
     *
     * @return void
     */
    public static function renderAggregateRating(): void
    {
        // Звёзды в выдаче показываются для страницы отзывов, на остальных схема была бы спамом.
        if (!is_page('reviews')) {
            return;
        }

        // Считаем по опубликованным отзывам; черновики и отзывы на модерации не участвуют.
        $rating = Reviews::averageRating();

        // Ни одного отзыва — оценки нет, схему не печатаем.
        if ($rating === null) {
            return;
        }

        // ratingValue и reviewCount — минимально достаточный набор для AggregateRating.
        self::printJsonLd([
            '@context'        => 'https://schema.org',
            '@type'           => self::BUSINESS_TYPE,
            'name'            => Config::BRAND,
            'aggregateRating' => [
                '@type'       => 'AggregateRating',
                'ratingValue' => $rating['value'],
                'reviewCount' => $rating['count'],
            ],
        ]);
    }

    /**
     * Печатает готовую структуру как JSON-LD — единственное место в коде, где это делается.
     *
     * @param array<string, mixed> $schema Дерево свойств Schema.org.
     * @return void
     */
    public static function printJsonLd(array $schema): void
    {
        // wp_json_encode экранирует по правилам WordPress и не сломает вывод кавычками.
        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
    }
}
