<?php
/**
 * Страница услуги: коммерческие системы затенения.
 */

use SiteCore\Blocks;
use SiteCore\Config;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

// Компактный герой: на внутренних страницах он ниже, чем на главной.
Blocks::renderHero(
    'Commercial Custom Shade Solutions - Boca Raton, FL',
    'Motorized shade and screen systems for restaurants, golf clubs, and commercial properties.'
);
?>

<div class="sc-section-narrow">
    <p><?php echo esc_html(Config::BRAND); ?> partners with restaurants, golf clubs, hotels, and commercial property owners across Boca Raton to design and install large-scale motorized shade and retractable screen systems. From outdoor dining patios to clubhouse terraces, we engineer solutions that extend usable, revenue-generating space and keep guests comfortable through sun, wind, and rain.</p>

    <h2 class="sc-h2-left">Built for High-Traffic Commercial Use</h2>
    <p>Our commercial-grade aluminum framing and motorized drive systems are engineered for durability and daily use, meeting Florida's strict wind-load building codes. Every system includes smart wind sensors for automatic retraction during storms, protecting your investment without staff having to run outside mid-service.</p>

    <h2 class="sc-h2-left">Custom-Engineered for Your Property</h2>
    <p>No two commercial spaces are the same. We design custom shade and screen layouts for your specific footprint, from large patio enclosures to multi-panel retractable walls that open your dining room to the outdoors on a good-weather day.</p>

    [sc_trust_grid]
    [sc_trust title="Commercial-Grade Durability"]Engineered for daily high-traffic use at restaurants, clubs, and hotels.[/sc_trust]
    [sc_trust title="Smart Wind Sensors"]Automatic retraction protects your system during storms - no staff intervention required.[/sc_trust]
    [sc_trust title="Licensed &amp; Insured"]Fully licensed by the State of Florida with complete liability coverage.[/sc_trust]
    [/sc_trust_grid]
</div>

<?php
// У коммерческой страницы другой призыв: здесь не смета на дом, а консультация.
Blocks::renderCtaBand('Request a Commercial Consultation');
