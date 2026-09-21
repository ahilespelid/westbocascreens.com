<?php
/**
 * Разметка Schema.org (JSON-LD): сайт, карточка бизнеса, услуга страницы и хлебные
 * крошки. Бизнес — одна сущность с постоянным @id: услуга ссылается на неё, а не
 * описывает компанию заново, и поисковик не видит на странице двух разных фирм.
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

    /**
     * Подписка на хуки модуля. Приоритеты задают порядок блоков в <head>.
     *
     * @return void
     */
    public static function register(): void
    {
        // 4 — сайт целиком: только на главной, по нему поисковик берёт название сайта.
        add_action('wp_head', [self::class, 'renderWebsite'], 4);

        // 5 — карточка бизнеса, она нужна на каждой странице.
        add_action('wp_head', [self::class, 'renderBusiness'], 5);

        // 6 — услуга, только на страницах услуг.
        add_action('wp_head', [self::class, 'renderService'], 6);

        // 8 — хлебные крошки на внутренних страницах.
        add_action('wp_head', [self::class, 'renderBreadcrumbs'], 8);
    }

    /**
     * Сайт: название для выдачи поисковика и ссылка на владельца.
     *
     * @return void
     */
    public static function renderWebsite(): void
    {
        // Google читает WebSite только с главной — на остальных страницах он лишний.
        if (!is_front_page()) {
            return;
        }

        self::printJsonLd([
            '@context'  => 'https://schema.org',
            '@type'     => 'WebSite',
            '@id'       => self::id('website'),
            'name'      => Config::BRAND,
            'url'       => home_url('/'),
            'publisher' => ['@id' => self::id('business')],
        ]);
    }

    /**
     * Карточка бизнеса: название, телефон, адрес, зона обслуживания. На странице
     * отзывов в неё же встраивается средняя оценка — отдельная копия бизнеса не нужна.
     *
     * @return void
     */
    public static function renderBusiness(): void
    {
        // Адрес без улицы: у компании выездной формат работы, точки продаж нет.
        $business = [
            '@context'    => 'https://schema.org',
            '@type'       => self::BUSINESS_TYPE,
            '@id'         => self::id('business'),
            'name'        => Config::BRAND,
            'description' => Config::TAGLINE,
            'url'         => home_url('/'),
            'image'       => Config::imageUrl(Config::HERO_IMAGE) ?: Config::contentUrl(Config::HERO_IMAGE),
            'telephone'   => Config::PHONE_TEL,
            'address'     => [
                '@type'           => 'PostalAddress',
                'addressLocality' => Config::CITY,
                'addressRegion'   => Config::REGION,
                'addressCountry'  => Config::COUNTRY,
            ],
            'areaServed'  => Config::areaServed(),
            'priceRange'  => '$$$',
        ];

        // Средняя оценка по опубликованным отзывам — только там, где эти отзывы видны.
        $rating = is_page('reviews') ? Reviews::averageRating() : null;

        // Ни одного отзыва или не та страница — карточка без оценки.
        if ($rating !== null) {
            $business['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => $rating['value'],
                'reviewCount' => $rating['count'],
                'bestRating'  => 5,
                'worstRating' => 1,
            ];
        }

        self::printJsonLd($business);
    }

    /**
     * Услуга страницы — только если для страницы задано название услуги.
     *
     * @return void
     */
    public static function renderService(): void
    {
        // Схема услуги осмысленна только на отдельной странице.
        if (!is_singular('page')) {
            return;
        }

        // Название услуги — оно же признак того, что страница посвящена услуге.
        $service_name = Seo::field('service');

        // Не страница услуги — схема не нужна.
        if ($service_name === '') {
            return;
        }

        // Описание — то же, что в meta description: один текст на страницу, без расхождений.
        self::printJsonLd([
            '@context'    => 'https://schema.org',
            '@type'       => 'Service',
            'name'        => $service_name,
            'serviceType' => $service_name,
            'description' => Seo::field('description'),
            'provider'    => ['@id' => self::id('business')],
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
     * Печатает готовую структуру как JSON-LD — единственное место в коде, где это делается.
     *
     * @param array<string, mixed> $schema Дерево свойств Schema.org.
     * @return void
     */
    public static function printJsonLd(array $schema): void
    {
        // wp_json_encode экранирует по правилам WordPress и не сломает вывод кавычками.
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }

    /**
     * Постоянный идентификатор сущности: адрес главной плюс якорь. По нему блоки
     * на разных страницах ссылаются на одну и ту же сущность.
     *
     * @param string $entity Имя сущности: business, website.
     * @return string Например: https://westbocascreens.com/#business.
     */
    private static function id(string $entity): string
    {
        // home_url учитывает протокол и домен из настроек — ID одинаков на всех страницах.
        return home_url('/#' . $entity);
    }
}
