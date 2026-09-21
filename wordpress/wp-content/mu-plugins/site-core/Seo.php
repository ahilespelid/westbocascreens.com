<?php
/**
 * SEO без плагина: заголовок вкладки и description, карточки Open Graph и Twitter,
 * по которым мессенджеры строят превью ссылки.
 *
 * Тексты лежат в этом файле, а не в базе: сайт рендерится из репозитория, и мета
 * должна меняться тем же коммитом, что и сама страница. Значения из базы остаются
 * запасным вариантом для страниц, которых здесь нет.
 */

namespace SiteCore;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

final class Seo
{
    /** @var string Мета-поле базы с собственным заголовком страницы. */
    private const META_TITLE = '_sc_meta_title';

    /** @var string Мета-поле базы с описанием страницы. */
    private const META_DESCRIPTION = '_sc_meta_description';

    /**
     * @var array<string, array{title: string, description: string}> Мета по слагу страницы.
     * Заголовок держим в пределах ~60 символов, описание — ~155: дальше поиск обрезает.
     */
    private const PAGES = [
        'home' => [
            'title'       => 'Motorized Screens & Retractable Awnings | West Boca Raton FL',
            'description' => 'Premium motorized patio screens and retractable awnings for West Boca Raton homes. Hurricane-rated, rain-blocking, HOA compliant. Free in-home estimate.',
        ],
        'motorized-retractable-screens' => [
            'title'       => 'Motorized Retractable Screens | West Boca Raton, FL',
            'description' => 'Motorized retractable screens for patios, garages and outdoor living spaces in West Boca Raton. Smart-home ready, hurricane-rated, full insect and UV protection.',
        ],
        'retractable-awnings-pergolas' => [
            'title'       => 'Retractable Awnings & Pergolas | West Boca Raton, FL',
            'description' => 'Custom motorized retractable awnings and pergolas in West Boca Raton. Sunbrella fabrics, smart wind sensors, dimmable lighting, hurricane-rated frames.',
        ],
        'pool-patio-screen-enclosures' => [
            'title'       => 'Pool & Patio Screen Enclosures | Boca Raton, FL',
            'description' => 'Custom-engineered pool cage and patio screen enclosures in Boca Raton. Full aluminum framing, hurricane-code engineered, complete insect protection.',
        ],
        'commercial-custom-shade-solutions' => [
            'title'       => 'Commercial Shade & Screen Systems | Boca Raton, FL',
            'description' => 'Heavy-duty motorized screens and awnings for Boca Raton restaurants, country clubs and commercial outdoor dining. Code-engineered, smart wind sensors.',
        ],
        'reviews' => [
            'title'       => 'Customer Reviews | West Boca Screens & Awnings',
            'description' => 'Reviews from West Boca Raton homeowners about our motorized screens and retractable awnings. Read what your neighbors say, then leave your own.',
        ],
    ];

    /**
     * Подписка на хуки модуля.
     *
     * @return void
     */
    public static function register(): void
    {
        // Приоритет 1 — description идёт первым в <head>, до всего прочего.
        add_action('wp_head', [self::class, 'renderDescription'], 1);

        // Приоритет 2 — сразу следом карточки для соцсетей и мессенджеров.
        add_action('wp_head', [self::class, 'renderSocialCards'], 2);

        // Заголовок вкладки: собственный, если он задан для этой страницы.
        add_filter('document_title_parts', [self::class, 'filterTitle']);
    }

    /**
     * Мета-описание текущей страницы.
     *
     * @return void
     */
    public static function renderDescription(): void
    {
        // Описание страницы; пустое — тег не печатаем вовсе.
        $description = self::value('description', self::META_DESCRIPTION);

        // Пустой description хуже отсутствующего: поисковик решит, что страница пустая.
        if ($description === '') {
            return;
        }

        // esc_attr обязателен: значение попадает в атрибут тега.
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    /**
     * Карточки Open Graph и Twitter — то, что читают WhatsApp, Telegram, iMessage и Slack.
     *
     * @return void
     */
    public static function renderSocialCards(): void
    {
        // Заголовок: собственный для страницы, иначе — тот, что собрал WordPress.
        $title = self::value('title', self::META_TITLE) ?: wp_get_document_title();

        // Описание: собственное, иначе — общее описание сайта из настроек.
        $description = self::value('description', self::META_DESCRIPTION) ?: get_bloginfo('description');

        // Канонический адрес страницы; для архивов и главной — корень сайта.
        $page_id = get_queried_object_id();
        $url = $page_id ? get_permalink($page_id) : home_url('/');

        // Картинка превью одна на весь сайт: 1200×630 — размер, который ждут соцсети.
        $image = Config::contentUrl(Config::OG_IMAGE);
        ?>
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="<?php echo esc_attr(Config::BRAND); ?>">
        <meta property="og:title" content="<?php echo esc_attr($title); ?>">
        <meta property="og:description" content="<?php echo esc_attr($description); ?>">
        <meta property="og:url" content="<?php echo esc_url($url); ?>">
        <meta property="og:image" content="<?php echo esc_url($image); ?>">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:locale" content="en_US">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo esc_attr($title); ?>">
        <meta name="twitter:description" content="<?php echo esc_attr($description); ?>">
        <meta name="twitter:image" content="<?php echo esc_url($image); ?>">
        <?php
    }

    /**
     * Подменяет заголовок вкладки, если для страницы задан собственный.
     *
     * @param array<string, string> $title_parts Части заголовка, собранные ядром.
     * @return array<string, string> Изменённые или исходные части.
     */
    public static function filterTitle(array $title_parts): array
    {
        // Собственный заголовок страницы; пустой — оставляем сборку ядра как есть.
        $custom_title = self::value('title', self::META_TITLE);

        // Возврат без изменений — самый частый случай, поэтому проверяем первым.
        if ($custom_title === '') {
            return $title_parts;
        }

        // Заменяем целиком: собственный заголовок уже содержит всё, что нужно, без хвостов.
        return ['title' => $custom_title];
    }

    /**
     * Значение меты: сначала из кода по слагу страницы, затем из базы.
     *
     * @param string $key Ключ в таблице PAGES: title или description.
     * @param string $meta_key Имя мета-поля базы для запасного варианта.
     * @return string Значение или пустая строка.
     */
    private static function value(string $key, string $meta_key): string
    {
        // Идентификатор того, что сейчас показывается: страница, запись, архив.
        $page_id = get_queried_object_id();

        // Без объекта запроса меты не существует.
        if (!$page_id) {
            return '';
        }

        // Слаг страницы — ключ таблицы PAGES.
        $slug = (string) get_post_field('post_name', $page_id);

        // Значение из кода имеет приоритет: оно версионируется вместе со страницей.
        if (isset(self::PAGES[$slug][$key])) {
            return self::PAGES[$slug][$key];
        }

        // Приведение к строке: get_post_meta может вернуть false или массив при кривых данных.
        return (string) get_post_meta($page_id, $meta_key, true);
    }
}
