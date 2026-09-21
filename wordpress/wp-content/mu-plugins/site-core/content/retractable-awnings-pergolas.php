<?php
/**
 * Страница услуги: раздвижные маркизы и перголы.
 */

use SiteCore\Blocks;
use SiteCore\Config;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

// Компактный герой: на внутренних страницах он ниже, чем на главной.
Blocks::renderHero(
    'Retractable Awnings & Pergolas in West Boca Raton, FL',
    'Motorized shade for your patio, engineered for Florida sun and Florida storms.'
);

// Фото маркизы с пультом — тот же кадр, что и на главной; без файла выводится только текст.
Blocks::renderMediaSplit(
    Config::AWNING_IMAGE,
    'Striped Sunbrella retractable awning extended over a patio, with the motorized remote control in hand',
    'Sunbrella&reg; Awnings at the Touch of a Button',
    'Custom striped or solid Sunbrella&reg; fabric, a powder-coated frame and a handheld remote: extend full shade over your outdoor living space in seconds, and let the wind sensor put it away when the weather turns.'
);
?>

<div class="sc-section-narrow">
    <p>Extend your living space outdoors with a custom retractable awning or motorized pergola from <?php echo esc_html(Config::BRAND); ?>. Whether you want shade over a pool deck, dining patio, or entertainment area, our systems open and close with the push of a button — no manual cranking, no wrestling with a hand-crank in 95° heat.</p>

    <h2 class="sc-h2-left">Motorized, Weather-Aware Shade</h2>
    <p>Every awning we install includes smart wind sensors that automatically retract the fabric when storm-force winds hit, protecting the system from damage — the single biggest worry we hear from Florida homeowners, solved before you even notice the wind picked up. Pair that with Alexa and Google Home integration for full one-touch control.</p>

    <h2 class="sc-h2-left">Hurricane-Resistant Construction</h2>
    <p>Our aluminum pergola frames and awning arms are engineered to meet Florida's strict wind-load building codes, so your shade structure is built to last through the region's toughest weather — not just look good until the first named storm.</p>

    [sc_trust_grid]
    [sc_trust title="Smart Wind Sensors"]Auto-retract before storms hit — no manual intervention needed.[/sc_trust]
    [sc_trust title="Alexa &amp; Google Home"]Voice-controlled operation for total convenience.[/sc_trust]
    [sc_trust title="Licensed &amp; Insured"]Full Florida state licensing and liability insurance on every install.[/sc_trust]
    [/sc_trust_grid]

    <h2 class="sc-h2-left">Best in the Industry</h2>
    <?php Blocks::renderFeatureGrid(Config::PREMIUM_FEATURES); ?>
</div>

<?php
// Одинаковая для всех страниц услуг полоса с телефоном из конфигурации.
Blocks::renderCtaBand('Get a Free In-Home Estimate');
