<?php
/**
 * Страница отзывов: список опубликованных отзывов и форма отправки нового.
 * Оба блока рисует модуль Reviews, здесь только каркас страницы.
 */

use SiteCore\Blocks;
use SiteCore\Config;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

// Компактный герой: на внутренних страницах он ниже, чем на главной.
Blocks::renderHero(
    'What Our Customers Say',
    'Real feedback from homeowners across West Boca Raton.'
);
?>

<div id="reviews" class="sc-section">
    [sc_reviews_list]
</div>

<div class="sc-section-narrow">
    <h2 class="sc-h2-left">Leave a Review</h2>
    <p class="sc-note">Had work done by <?php echo esc_html(Config::BRAND); ?>? We'd love to hear about it. Reviews are checked before publishing.</p>
    [sc_review_form]
</div>
