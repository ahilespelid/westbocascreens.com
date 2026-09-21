<?php
/**
 * Шорткоды разметки: вопрос-ответ, шаги работы и карточки доверия.
 * Каждый парный шорткод — просто контейнер вокруг вложенных, поэтому обёртки собраны одним методом.
 */

namespace SiteCore;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

final class Shortcodes
{
    /** @var array<int, array{q: string, a: string}> Собранные на странице пары вопрос-ответ для схемы FAQPage. */
    private static array $faq_items = [];

    /**
     * Подписка на хуки модуля.
     *
     * @return void
     */
    public static function register(): void
    {
        // Одиночный вопрос-ответ.
        add_shortcode('sc_faq', [self::class, 'renderFaqItem']);

        // Один шаг инструкции «как это работает».
        add_shortcode('sc_step', [self::class, 'renderStep']);

        // Сетка шагов вокруг вложенных sc_step.
        add_shortcode('sc_steps', self::wrapper('sc-steps'));

        // Карточка доверия: лицензия, страховка, гарантия.
        add_shortcode('sc_trust', [self::class, 'renderTrustCard']);

        // Сетка карточек доверия вокруг вложенных sc_trust.
        add_shortcode('sc_trust_grid', self::wrapper('sc-trust-grid'));

        // Схема FAQPage печатается в подвале — к этому моменту все вопросы уже собраны.
        add_action('wp_footer', [self::class, 'renderFaqSchema'], 20);
    }

    /**
     * Вопрос-ответ: выводит блок и запоминает пару для схемы FAQPage.
     *
     * @param array<string, string>|string $raw_atts Атрибуты шорткода.
     * @return string HTML блока.
     */
    public static function renderFaqItem(array|string $raw_atts): string
    {
        // shortcode_atts задаёт значения по умолчанию и отсекает посторонние атрибуты.
        $atts = shortcode_atts(['q' => '', 'a' => ''], $raw_atts, 'sc_faq');

        // В схему попадают только полные пары: вопрос без ответа Google считает ошибкой разметки.
        if ($atts['q'] !== '' && $atts['a'] !== '') {
            self::$faq_items[] = $atts;
        }

        // Буферизация — единственный способ вернуть разметку из шорткода, не печатая её сразу.
        ob_start();
        ?>
        <div class="sc-faq-item">
            <h3 class="sc-faq-q"><?php echo esc_html($atts['q']); ?></h3>
            <div class="sc-faq-a"><?php echo wp_kses_post($atts['a']); ?></div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Один шаг инструкции: номер в кружке, заголовок и пояснение.
     *
     * @param array<string, string>|string $raw_atts Атрибуты шорткода.
     * @param string|null $content Текст пояснения между тегами шорткода.
     * @return string HTML шага.
     */
    public static function renderStep(array|string $raw_atts, ?string $content = ''): string
    {
        // Номер и заголовок приходят атрибутами, текст — телом шорткода.
        $atts = shortcode_atts(['num' => '', 'title' => ''], $raw_atts, 'sc_step');

        ob_start();
        ?>
        <div class="sc-step">
            <div class="sc-step-num"><?php echo esc_html($atts['num']); ?></div>
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <p><?php echo wp_kses_post((string) $content); ?></p>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Карточка доверия: заголовок и произвольный HTML внутри.
     *
     * @param array<string, string>|string $raw_atts Атрибуты шорткода.
     * @param string|null $content Содержимое карточки.
     * @return string HTML карточки.
     */
    public static function renderTrustCard(array|string $raw_atts, ?string $content = ''): string
    {
        // У карточки единственный атрибут — заголовок.
        $atts = shortcode_atts(['title' => ''], $raw_atts, 'sc_trust');

        ob_start();
        ?>
        <div class="sc-trust-card">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div><?php echo wp_kses_post((string) $content); ?></div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Схема FAQPage по всем вопросам, собранным на странице.
     *
     * @return void
     */
    public static function renderFaqSchema(): void
    {
        // На странице не было ни одного вопроса — схема не нужна.
        if (self::$faq_items === []) {
            return;
        }

        // Ответ в схеме идёт текстом без тегов: Google показывает его как обычный абзац.
        $entities = array_map(
            static fn (array $item): array => [
                '@type'          => 'Question',
                'name'           => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => wp_strip_all_tags($item['a'])],
            ],
            self::$faq_items
        );

        // Печать через общий метод схемы: формат вывода задаётся в одном месте.
        Schema::printJsonLd([
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $entities,
        ]);
    }

    /**
     * Фабрика парных шорткодов-контейнеров: <div class="…">вложенные шорткоды</div>.
     *
     * @param string $css_class Класс контейнера.
     * @return callable(array<string, string>|string, string|null): string Обработчик шорткода.
     */
    private static function wrapper(string $css_class): callable
    {
        // Замыкание захватывает только класс — остальное одинаково для всех контейнеров.
        return static function (array|string $raw_atts, ?string $content = '') use ($css_class): string {
            // do_shortcode обязателен: вложенные шорткоды сами по себе не раскрываются.
            return '<div class="' . esc_attr($css_class) . '">' . do_shortcode((string) $content) . '</div>';
        };
    }
}
