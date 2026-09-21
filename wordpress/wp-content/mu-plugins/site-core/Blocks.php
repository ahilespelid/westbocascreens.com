<?php
/**
 * Повторяющиеся блоки разметки. Всё, что встречается больше чем на одной странице,
 * живёт здесь: герой, полоса призыва к действию, видео, блок «фото плюс текст»,
 * сетка преимуществ, отзывы соседей и врезка о коммерческих объектах.
 */

namespace SiteCore;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

final class Blocks
{
    /**
     * Герой страницы: заголовок H1, подзаголовок и необязательная кнопка.
     *
     * @param string $heading Заголовок первого уровня — на странице он единственный.
     * @param string $subheading Подзаголовок под ним.
     * @param bool $is_compact true — невысокий вариант для внутренних страниц.
     * @param string $cta_label Текст кнопки; пустая строка — кнопки не будет.
     * @return void
     */
    public static function renderHero(string $heading, string $subheading, bool $is_compact = true, string $cta_label = ''): void
    {
        // Модификатор класса вместо инлайн-стилей: внешний вид задаётся в site.css.
        $hero_class = 'sc-hero' . ($is_compact ? ' sc-hero--compact' : '');

        // Фон передаётся CSS-переменной с версией файла: новое фото видно сразу, без ожидания кэша.
        $hero_image = Config::assetUrl(Config::HERO_IMAGE);
        ?>
        <div class="<?php echo esc_attr($hero_class); ?>"<?php if ($hero_image !== ''): ?> style="--sc-hero-image:url('<?php echo esc_url($hero_image); ?>')"<?php endif; ?>>
            <h1><?php echo esc_html($heading); ?></h1>
            <p><?php echo esc_html($subheading); ?></p>
            <?php if ($cta_label !== ''): ?>
                <a href="#quote" class="sc-header-cta sc-hero-cta"><?php echo esc_html($cta_label); ?></a>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Полоса призыва к действию с кнопкой звонка. Телефон берётся из конфигурации,
     * поэтому при смене номера править страницы не придётся.
     *
     * @param string $heading Заголовок полосы.
     * @param string $lede Пояснение под заголовком; пустая строка — абзаца не будет.
     * @return void
     */
    public static function renderCtaBand(string $heading, string $lede = ''): void
    {
        ?>
        <div id="quote" class="sc-cta-band">
            <h2><?php echo esc_html($heading); ?></h2>
            <?php if ($lede !== ''): ?>
                <p><?php echo esc_html($lede); ?></p>
            <?php endif; ?>
            <a href="tel:<?php echo esc_attr(Config::PHONE_TEL); ?>" class="sc-call-btn">Call <?php echo esc_html(Config::PHONE_DISPLAY); ?></a>
        </div>
        <?php
    }

    /**
     * Демонстрационное видео. Если в конфигурации задан ролик YouTube, выводится
     * его превью-заглушка: тяжёлый плеер подгружается только по клику, поэтому
     * скорость первой отрисовки не страдает. Пока ролик не выбран — играет
     * собственное видео из uploads.
     *
     * @param string $heading Заголовок блока.
     * @param string $lede Подзаголовок.
     * @return void
     */
    public static function renderVideo(string $heading, string $lede): void
    {
        ?>
        <div class="sc-video-section">
            <h2><?php echo esc_html($heading); ?></h2>
            <p class="sc-section-lede"><?php echo esc_html($lede); ?></p>
            <div class="sc-video-wrap">
                <?php Config::YOUTUBE_ID === '' ? self::renderSelfHostedVideo() : self::renderYoutubeFacade(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Блок «картинка плюс текст». Картинки на диске нет — выводится только текст,
     * поэтому блок можно ставить в разметку до того, как фото загружено.
     *
     * @param string $image_path Путь к картинке от корня wp-content.
     * @param string $image_alt Описание картинки для скринридеров и поисковиков.
     * @param string $heading Заголовок блока.
     * @param string $text Текст блока; допускается простая HTML-разметка.
     * @return void
     */
    public static function renderMediaSplit(string $image_path, string $image_alt, string $heading, string $text): void
    {
        // URL с версией или пустая строка, если фото ещё не загружено.
        $image_url = Config::assetUrl($image_path);
        ?>
        <div class="sc-media-split<?php echo $image_url === '' ? ' sc-media-split--text' : ''; ?>">
            <?php if ($image_url !== ''): ?>
                <img class="sc-media-split-image" src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>" loading="lazy" width="1200" height="800">
            <?php endif; ?>
            <div class="sc-media-split-body">
                <h2><?php echo esc_html($heading); ?></h2>
                <p><?php echo wp_kses_post($text); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Сетка преимуществ: заголовок плюс текст в карточке.
     *
     * @param array<int, array{title: string, text: string}> $features Список преимуществ.
     * @return void
     */
    public static function renderFeatureGrid(array $features): void
    {
        ?>
        <div class="sc-trust-grid">
            <?php foreach ($features as $feature): ?>
                <div class="sc-trust-card">
                    <h3><?php echo wp_kses_post($feature['title']); ?></h3>
                    <p><?php echo wp_kses_post($feature['text']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Отзывы соседей: имя, район и текст. Район важнее фамилии — он и создаёт доверие.
     *
     * @param array<int, array{name: string, place: string, text: string}> $testimonials Отзывы.
     * @return void
     */
    public static function renderTestimonials(array $testimonials): void
    {
        ?>
        <div class="sc-testimonials">
            <?php foreach ($testimonials as $testimonial): ?>
                <figure class="sc-testimonial">
                    <?php echo self::stars(5); ?>
                    <blockquote><?php echo esc_html($testimonial['text']); ?></blockquote>
                    <figcaption>
                        <span class="sc-testimonial-name"><?php echo esc_html($testimonial['name']); ?></span>
                        <span class="sc-testimonial-place"><?php echo esc_html($testimonial['place']); ?></span>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Звёзды оценки. Для скринридера это одна картинка с текстом «4 out of 5 stars»:
     * aria-label на div без роли не читается, поэтому роль img обязательна.
     *
     * @param int $rating Оценка от 1 до 5.
     * @return string Готовый HTML блока со звёздами.
     */
    public static function stars(int $rating): string
    {
        // &#9733; — закрашенная звезда, &#9734; — контурная; сумма всегда пять.
        $glyphs = str_repeat('&#9733;', $rating) . str_repeat('&#9734;', 5 - $rating);

        return '<div class="sc-review-stars" role="img" aria-label="' . $rating . ' out of 5 stars">' . $glyphs . '</div>';
    }

    /**
     * Короткая врезка о коммерческих объектах перед подвалом.
     *
     * @return void
     */
    public static function renderCommercialTeaser(): void
    {
        ?>
        <div class="sc-commercial">
            <div class="sc-commercial-inner">
                <h2>Commercial Shading Solutions</h2>
                <p>We custom design and install heavy-duty motorized screens and awnings for West Boca restaurants, country clubs, and commercial outdoor dining spaces.</p>
                <a href="/commercial-custom-shade-solutions/" class="sc-commercial-link">See commercial projects &rarr;</a>
            </div>
        </div>
        <?php
    }

    /**
     * Собственное видео из uploads: грузится лениво скриптом video.js.
     *
     * @return void
     */
    private static function renderSelfHostedVideo(): void
    {
        ?>
        <?php // Адреса файлов в data-атрибутах: <source> без src невалиден, а с src браузер начал бы качать сразу. ?>
        <video id="sc-demo-video" muted loop playsinline preload="none" poster="<?php echo esc_url(Config::assetUrl(Config::HERO_IMAGE)); ?>"
               data-webm="<?php echo esc_url(Config::assetUrl('/uploads/site/hero-demo.webm')); ?>"
               data-mp4="<?php echo esc_url(Config::assetUrl('/uploads/site/hero-demo.mp4')); ?>"></video>
        <button type="button" class="sc-sound-toggle" aria-label="Toggle sound">&#128264;</button>
        <?php
    }

    /**
     * Заглушка ролика YouTube: картинка плюс кнопка. Настоящий плеер подставляется
     * скриптом только после клика, поэтому сторонние скрипты не тормозят загрузку
     * страницы и не ставят куки до согласия посетителя.
     *
     * @return void
     */
    private static function renderYoutubeFacade(): void
    {
        // Превью с серверов YouTube: hqdefault есть у любого ролика, в отличие от maxresdefault.
        $poster_url = 'https://i.ytimg.com/vi/' . Config::YOUTUBE_ID . '/hqdefault.jpg';
        ?>
        <div class="sc-youtube" data-video-id="<?php echo esc_attr(Config::YOUTUBE_ID); ?>">
            <img class="sc-youtube-poster" src="<?php echo esc_url($poster_url); ?>" alt="Motorized retractable awning in action" loading="lazy" width="480" height="360">
            <button type="button" class="sc-youtube-play" aria-label="Play video">&#9654;</button>
        </div>
        <?php
    }
}
