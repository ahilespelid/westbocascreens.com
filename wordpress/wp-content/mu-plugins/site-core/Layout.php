<?php
/**
 * Каркас страницы: настройки темы GeneratePress, верхняя плашка с гео и CTA,
 * подвал с картой и ZIP-кодами, липкая кнопка звонка на мобильных.
 */

namespace SiteCore;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

final class Layout
{
    /**
     * Подписка на хуки модуля.
     *
     * @return void
     */
    public static function register(): void
    {
        // Название и слоган сайта берутся из кода, а не из настроек в базе: там осталось
        // старое имя, и оно утекало в шапку темы, RSS, REST API и письма WordPress.
        add_filter('pre_option_blogname', static fn (): string => Config::BRAND);
        add_filter('pre_option_blogdescription', static fn (): string => Config::TAGLINE);

        // Сайдбар не нужен ни на одной странице: макет одноколоночный.
        add_filter('generate_sidebar_layout', static fn (): string => 'no-sidebar');

        // Заголовок страницы темой не выводим — в контенте каждой страницы свой H1.
        add_filter('generate_show_title', '__return_false');

        // Контент страниц — это свёрстанный HTML, а не проза: wpautop расставляет
        // лишние <p> и <br> внутри grid- и flex-контейнеров и ломает сетку.
        remove_filter('the_content', 'wpautop');

        // Копирайт в подвале темы: год подставляется автоматически.
        add_filter('generate_copyright', [self::class, 'copyright']);

        // Верхняя плашка печатается сразу после открытия <body>.
        add_action('wp_body_open', [self::class, 'renderTopBar']);

        // Липкая кнопка звонка — в подвал, приоритет по умолчанию.
        add_action('wp_footer', [self::class, 'renderCallButton']);

        // Подвал с картой — на хуке темы перед её собственным подвалом: так строка
        // копирайта остаётся в самом низу, а не висит между контентом и нашим подвалом.
        add_action('generate_before_footer', [self::class, 'renderFooter']);
    }

    /**
     * Строка копирайта в подвале темы.
     *
     * @return string Готовый HTML-текст копирайта.
     */
    public static function copyright(): string
    {
        // date('Y') вычисляется при каждом запросе, поэтому год не протухает первого января.
        return 'Copyright &copy; ' . esc_html(date('Y')) . ' ' . esc_html(Config::BRAND) . '. All rights reserved.';
    }

    /**
     * Верхняя плашка: зона обслуживания, телефон и кнопка запроса сметы.
     *
     * @return void
     */
    public static function renderTopBar(): void
    {
        ?>
        <div class="sc-geo-badge">Serving West Boca Raton &amp; Exclusive Gated Communities &mdash; ZIP <?php echo esc_html(Config::zipList()); ?></div>
        <div class="sc-header-actions">
            <a href="tel:<?php echo esc_attr(Config::PHONE_TEL); ?>" class="sc-header-phone">&#128222; <?php echo esc_html(Config::PHONE_DISPLAY); ?></a>
            <a href="#quote" class="sc-header-cta">Get a Free In-Home Estimate</a>
        </div>
        <?php
    }

    /**
     * Липкая кнопка звонка: видна только на мобильных, прижата к нижней кромке экрана.
     *
     * @return void
     */
    public static function renderCallButton(): void
    {
        ?>
        <a href="tel:<?php echo esc_attr(Config::PHONE_TEL); ?>" class="sc-mobile-call-btn">
            <span class="sc-icon" aria-hidden="true">&#128222;</span> CALL NOW: <?php echo esc_html(Config::PHONE_DISPLAY); ?>
        </a>
        <?php
    }

    /**
     * Подвал: врезка о коммерческих объектах, карта зоны обслуживания, статус компании,
     * ZIP-коды и кредит разработчика.
     *
     * @return void
     */
    public static function renderFooter(): void
    {
        // Врезка о коммерческих объектах — на всех страницах, кроме самой коммерческой.
        if (!is_page('commercial-custom-shade-solutions')) {
            Blocks::renderCommercialTeaser();
        }
        ?>
        <div class="sc-footer">
            <div class="sc-footer-inner">
                <iframe src="https://www.google.com/maps?q=West+Boca+Raton,FL&output=embed" height="280" class="sc-footer-map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="<?php echo esc_attr(Config::BRAND); ?> service area map"></iframe>
                <div class="sc-footer-zips">
                    <p class="sc-footer-status">We are the region&rsquo;s most experienced manufacturer and installer of remote-controlled motorized retractable roll screens and awnings.</p>
                    <strong><?php echo esc_html(Config::BRAND); ?></strong> &mdash; Licensed &amp; Fully Insured | Serving West Boca Raton, FL and surrounding areas: ZIP codes <?php echo esc_html(Config::zipList()); ?>.<br>
                    Call <a href="tel:<?php echo esc_attr(Config::PHONE_TEL); ?>" class="sc-footer-phone"><?php echo esc_html(Config::PHONE_DISPLAY); ?></a> for a Free In-Home Estimate.
                </div>
            </div>
            <div class="sc-dev-credit">
                Site developed by <a href="https://github.com/ahilespelid/" target="_blank" rel="noopener noreferrer">ahilespelid</a>
                &nbsp;&middot;&nbsp;
                Found a bug or have a business inquiry? <a href="https://messenger.apexflowus.com/" target="_blank" rel="noopener noreferrer">Contact the developer or commercial director</a>
            </div>
        </div>
        <?php
    }
}
