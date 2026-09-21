<?php
/**
 * Единственный источник правды по данным бизнеса: бренд, телефон, зона обслуживания,
 * изображения и список преимуществ. Любое из этих значений меняется здесь и только здесь —
 * во всём остальном коде оно берётся отсюда, поэтому расхождений между шапкой, подвалом
 * и разметкой Schema.org не бывает.
 */

namespace SiteCore;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

final class Config
{
    /** @var string Публичное имя компании. Для клиентов сайт — местная профильная фирма. */
    public const BRAND = 'West Boca Screens & Awnings';

    /** @var string Слоган сайта: RSS, REST API и запасное описание для превью ссылок. */
    public const TAGLINE = 'Motorized Screens & Retractable Awnings in West Boca Raton, FL';

    /** @var string Телефон в человекочитаемом виде — для текста на странице. */
    public const PHONE_DISPLAY = '(561) 555-0100';

    /** @var string Тот же телефон в формате E.164 — для ссылок tel: и Schema.org. */
    public const PHONE_TEL = '+15615550100';

    /** @var string Город и штат — идут в почтовый адрес Schema.org. */
    public const CITY = 'Boca Raton';
    public const REGION = 'FL';
    public const COUNTRY = 'US';

    /** @var string[] Обслуживаемые ZIP-коды: подставляются и в текст, и в areaServed. */
    public const ZIP_CODES = ['33428', '33433', '33498'];

    /** @var string Фон героя: современный дом во флоридском стиле. */
    public const HERO_IMAGE = '/uploads/site/hero-patio.jpg';

    /** @var string Фото выдвижной маркизы для блока о навесах. */
    public const AWNING_IMAGE = '/uploads/site/awning-feature.jpg';

    /** @var string Картинка для превью ссылки в мессенджерах (Open Graph). */
    public const OG_IMAGE = '/uploads/site/og-image.jpg';

    /**
     * @var string Идентификатор ролика YouTube для блока «See It In Action».
     * Пустая строка — показывается собственное видео из uploads. Заполнить, когда
     * ролик выбран: из ссылки youtube.com/watch?v=XXXXXXXXXXX нужна часть после v=.
     */
    public const YOUTUBE_ID = '';

    /**
     * @var array<int, array{title: string, text: string}> Преимущества систем.
     * Один список на весь сайт: используется и на главной, и на страницах услуг.
     */
    public const PREMIUM_FEATURES = [
        ['title' => 'HOA Compliant Designs', 'text' => 'Our profiles, housings and color palette are specified to clear architectural review in West Boca\'s gated and country club communities. We prepare the submittal package and handle the approval process for you.'],
        ['title' => 'Integrated Dimmable Lights', 'text' => 'Built-in LED lighting with full dimming control turns the space into an evening room, with no fixtures to mount and no cords to run.'],
        ['title' => 'Sunbrella&reg; Fabrics', 'text' => 'Solution-dyed acrylic that holds its color through Florida sun, salt air and afternoon storms — the fabric the industry measures itself against.'],
        ['title' => 'EZ-Pitch Adjustment', 'text' => 'Change the pitch of the awning on demand: steeper to shed a downpour, flatter to hold back low afternoon sun.'],
        ['title' => 'Premium Fit &amp; Finish', 'text' => 'Powder-coated frames, concealed fasteners and factory-matched hardware. Close up, it reads as part of the house, not as equipment bolted onto it.'],
        ['title' => '10-Year Standard Warranty*', 'text' => 'Ten years on the system as standard, backed by the factory rather than by a handshake. *Full terms provided with your written estimate.'],
    ];

    /**
     * Абсолютный URL файла внутри wp-content.
     *
     * @param string $relative_path Путь от корня wp-content, начиная со слэша.
     * @return string Готовый абсолютный URL.
     */
    public static function contentUrl(string $relative_path): string
    {
        // content_url() сам подставит домен и учтёт нестандартное расположение wp-content.
        return content_url($relative_path);
    }

    /**
     * URL файла из wp-content (фото, видео) с версией по времени изменения. Файл заменили
     * под тем же именем — меняется и адрес, поэтому браузеры не показывают старую версию
     * из кэша. Файла нет — пустая строка, и блок с ним просто не выводится.
     *
     * @param string $relative_path Путь от корня wp-content, начиная со слэша.
     * @return string URL с параметром ?v= или пустая строка.
     */
    public static function assetUrl(string $relative_path): string
    {
        // Полный путь на диске: WP_CONTENT_DIR задаёт ядро.
        $file_path = WP_CONTENT_DIR . $relative_path;

        // Нет файла — нечего показывать; вызывающий код сам решает, что делать с пустотой.
        if (!is_file($file_path)) {
            return '';
        }

        // add_query_arg корректно добавит параметр, даже если в URL уже есть свои.
        return add_query_arg('v', (string) filemtime($file_path), self::contentUrl($relative_path));
    }

    /**
     * ZIP-коды одной строкой через запятую — для текстовых блоков шапки и подвала.
     *
     * @return string Например: «33428, 33433, 33498».
     */
    public static function zipList(): string
    {
        // implode дешевле конкатенации в цикле и читается однозначно.
        return implode(', ', self::ZIP_CODES);
    }

    /**
     * Зона обслуживания в формате Schema.org — один и тот же блок нужен в трёх схемах.
     *
     * @return array<int, array<string, string>> Массив PostalCodeSpecification.
     */
    public static function areaServed(): array
    {
        // array_map вместо foreach: результат — чистое преобразование списка, без побочных эффектов.
        return array_map(
            static fn (string $zip_code): array => [
                '@type'          => 'PostalCodeSpecification',
                'postalCode'     => $zip_code,
                'addressCountry' => self::COUNTRY,
            ],
            self::ZIP_CODES
        );
    }
}
