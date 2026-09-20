<?php
/**
 * Повторяющиеся блоки разметки страниц. Герой и полоса призыва к действию
 * встречаются почти на каждой странице, поэтому живут здесь, а не копируются по файлам.
 */

namespace ApexFlow;

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
        $hero_class = 'afn-hero' . ($is_compact ? ' afn-hero--compact' : '');
        ?>
        <div class="<?php echo esc_attr($hero_class); ?>">
            <h1><?php echo esc_html($heading); ?></h1>
            <p><?php echo esc_html($subheading); ?></p>
            <?php if ($cta_label !== ''): ?>
                <a href="#quote" class="afn-header-cta afn-hero-cta"><?php echo esc_html($cta_label); ?></a>
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
        <div id="quote" class="afn-cta-band">
            <h2><?php echo esc_html($heading); ?></h2>
            <?php if ($lede !== ''): ?>
                <p><?php echo esc_html($lede); ?></p>
            <?php endif; ?>
            <a href="tel:<?php echo esc_attr(Config::PHONE_TEL); ?>" class="afn-call-btn">Call <?php echo esc_html(Config::PHONE_DISPLAY); ?></a>
        </div>
        <?php
    }
}
