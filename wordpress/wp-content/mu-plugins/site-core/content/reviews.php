<?php
/**
 * Страница отзывов: список опубликованных отзывов и форма отправки нового.
 * Оба блока рисует модуль Reviews, здесь только каркас страницы.
 */

use ApexFlow\Blocks;
use ApexFlow\Config;

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

<div id="reviews" class="afn-section">
    [afn_reviews_list]
</div>

<div class="afn-section-narrow">
    <h2 class="afn-h2-left">Leave a Review</h2>
    <p class="afn-note">Had work done by <?php echo esc_html(Config::BRAND); ?>? We'd love to hear about it. Reviews are checked before publishing.</p>
    [afn_review_form]
</div>
