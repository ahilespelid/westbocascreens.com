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
     * скорость первой отрисовки не страдает. Пока ролик не выбран — один за другим
     * играют собственные видео из uploads, их список задаёт Config::VIDEOS.
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
            <div class="sc-video-grid">
                <?php if (Config::YOUTUBE_ID !== ''): ?>
                    <div class="sc-video-wrap">
                        <?php self::renderYoutubeFacade(); ?>
                    </div>
                <?php else: ?>
                    <?php foreach (Config::VIDEOS as $index => $video): ?>
                        <?php self::renderSelfHostedVideo($video, $index + 1); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
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
     * Короткая врезка перед подвалом с предложением бесплатного замера для частного
     * дома. Стоит на всех страницах, кроме коммерческой, — там свой оффер, и упоминать
     * рестораны на страницах для домовладельцев только сбивает с толку.
     *
     * @return void
     */
    public static function renderResidentialTeaser(): void
    {
        ?>
        <div class="sc-footer-teaser">
            <div class="sc-footer-teaser-inner">
                <h2>Free Measurement for Your Patio, Pool or Backyard</h2>
                <p>Not sure where to start? Our design team measures your patio, pool cage, or backyard on-site and hands you an exact price - no obligation, no pressure.</p>
                <a href="#quote" class="sc-footer-teaser-link">Get a Free Estimate &rarr;</a>
            </div>
        </div>
        <?php
    }

    /**
     * Одно собственное видео из uploads: грузится лениво скриптом video.js. Роликов
     * в блоке может быть несколько — каждый в своей рамке с собственным аспектом
     * и, если у ролика вшиты чёрные полосы (crop_width задан), своей обрезкой.
     *
     * @param array{webm: string, mp4: string, poster: string, width: int, height: int, crop_width: int|null, crop_shift: int|null, has_audio: bool} $video Описание ролика из Config::VIDEOS.
     * @param int $number Порядковый номер ролика — только для уникального id элемента.
     * @return void
     */
    private static function renderSelfHostedVideo(array $video, int $number): void
    {
        // Обрезка нужна не всем роликам: у чистого 16:9 crop_width не задан.
        $is_cropped = $video['crop_width'] !== null;
        ?>
        <?php // Аспект рамки и, если нужно, параметры обрезки — переменными CSS в style, а не отдельным классом на каждый размер. ?>
        <div class="sc-video-wrap" style="--video-width:<?php echo esc_attr((string) $video['width']); ?>;--video-ratio:<?php echo esc_attr($video['width'] . '/' . $video['height']); ?>;<?php echo $is_cropped ? '--crop-width:' . esc_attr((string) $video['crop_width']) . ';--crop-shift:' . esc_attr((string) $video['crop_shift']) . ';' : ''; ?>">
            <?php // Адреса файлов в data-атрибутах: <source> без src невалиден, а с src браузер начал бы качать сразу. ?>
            <video id="sc-demo-video-<?php echo esc_attr((string) $number); ?>" class="sc-demo-video<?php echo $is_cropped ? ' sc-video-cropped' : ''; ?>" muted loop playsinline preload="none" poster="<?php echo esc_url(Config::assetUrl($video['poster'])); ?>"
                   data-webm="<?php echo esc_url(Config::assetUrl($video['webm'])); ?>"
                   data-mp4="<?php echo esc_url(Config::assetUrl($video['mp4'])); ?>"
                   width="<?php echo esc_attr((string) $video['width']); ?>" height="<?php echo esc_attr((string) $video['height']); ?>"></video>
            <?php if ($video['has_audio']): ?>
                <button type="button" class="sc-sound-toggle" aria-label="Toggle sound">&#128264;</button>
            <?php endif; ?>
        </div>
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
