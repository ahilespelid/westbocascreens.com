<?php
/**
 * SEO без плагина: заголовок и description из мета-полей страницы плюс карточки
 * Open Graph и Twitter, по которым мессенджеры строят превью ссылки.
 */

namespace ApexFlow;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

final class Seo
{
    /** @var string Мета-поле с собственным заголовком страницы. */
    private const META_TITLE = '_afn_meta_title';

    /** @var string Мета-поле с описанием страницы. */
    private const META_DESCRIPTION = '_afn_meta_description';

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

        // Заголовок вкладки: собственный, если он задан в мета-поле страницы.
        add_filter('document_title_parts', [self::class, 'filterTitle']);
    }

    /**
     * Мета-описание текущей страницы.
     *
     * @return void
     */
    public static function renderDescription(): void
    {
        // Описание берётся из мета-поля; пустое — тег не печатаем вовсе.
        $description = self::metaValue(self::META_DESCRIPTION);

        // Пустой description хуже отсутствующего: поисковик решит, что страница пустая.
        if ($description === '') {
            return;
        }

        // esc_attr обязателен: значение приходит из базы и попадает в атрибут.
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    /**
     * Карточки Open Graph и Twitter — то, что читают WhatsApp, Telegram, iMessage и Slack.
     *
     * @return void
     */
    public static function renderSocialCards(): void
    {
        // Заголовок: сначала собственный из мета-поля, иначе — тот, что собрал WordPress.
        $title = self::metaValue(self::META_TITLE) ?: wp_get_document_title();

        // Описание: собственное, иначе — общее описание сайта из настроек.
        $description = self::metaValue(self::META_DESCRIPTION) ?: get_bloginfo('description');

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
     * Подменяет заголовок вкладки, если у страницы задан собственный.
     *
     * @param array<string, string> $title_parts Части заголовка, собранные ядром.
     * @return array<string, string> Изменённые или исходные части.
     */
    public static function filterTitle(array $title_parts): array
    {
        // Собственный заголовок страницы; пустой — оставляем сборку ядра как есть.
        $custom_title = self::metaValue(self::META_TITLE);

        // Возврат без изменений — самый частый случай, поэтому проверяем первым.
        if ($custom_title === '') {
            return $title_parts;
        }

        // Заменяем целиком: собственный заголовок уже содержит всё, что нужно, без хвостов.
        return ['title' => $custom_title];
    }

    /**
     * Значение мета-поля текущей страницы.
     *
     * @param string $meta_key Ключ мета-поля.
     * @return string Значение или пустая строка, если страницы нет или поле не заполнено.
     */
    private static function metaValue(string $meta_key): string
    {
        // Идентификатор того, что сейчас показывается: страница, запись, архив.
        $page_id = get_queried_object_id();

        // Без объекта запроса мета-полей не существует.
        if (!$page_id) {
            return '';
        }

        // Приведение к строке: get_post_meta может вернуть false или массив при кривых данных.
        return (string) get_post_meta($page_id, $meta_key, true);
    }
}
