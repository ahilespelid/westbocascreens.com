<?php
/**
 * Единственный источник правды по данным бизнеса: телефон, бренд, зона обслуживания.
 * Любое из этих значений меняется здесь и только здесь — во всём остальном коде
 * оно берётся отсюда, поэтому расхождений «в футере один телефон, в схеме другой» не бывает.
 */

namespace ApexFlow;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

final class Config
{
    /** @var string Название компании — идёт в Schema.org, Open Graph и копирайт. */
    public const BRAND = 'Wow Apex Flow';

    /** @var string Телефон в человекочитаемом виде — для текста на странице. */
    public const PHONE_DISPLAY = '(800) 555-0199';

    /** @var string Тот же телефон в формате E.164 — для ссылок tel: и Schema.org. */
    public const PHONE_TEL = '+18005550199';

    /** @var string Город и штат — идут в почтовый адрес Schema.org. */
    public const CITY = 'Boca Raton';
    public const REGION = 'FL';
    public const COUNTRY = 'US';

    /** @var string[] Обслуживаемые ZIP-коды: подставляются и в текст, и в areaServed. */
    public const ZIP_CODES = ['33428', '33433', '33498'];

    /** @var string Путь к фоновой фотографии героя относительно wp-content. */
    public const HERO_IMAGE = '/uploads/site/hero-patio.jpg';

    /** @var string Картинка для превью ссылки в мессенджерах (Open Graph). */
    public const OG_IMAGE = '/uploads/site/og-image.jpg';

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
