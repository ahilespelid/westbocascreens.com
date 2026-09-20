<?php
/**
 * Страница услуги: раздвижные маркизы и перголы.
 */

use ApexFlow\Blocks;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

// Компактный герой: на внутренних страницах он ниже, чем на главной.
Blocks::renderHero(
    'Retractable Awnings & Pergolas in West Boca Raton, FL',
    'Motorized shade for your patio, engineered for Florida sun and Florida storms.'
);
?>

<div class="afn-section-narrow">
    <p>Extend your living space outdoors with a custom retractable awning or motorized pergola from Wow Apex Flow. Whether you want shade over a pool deck, dining patio, or entertainment area, our systems open and close with the push of a button — no manual cranking, no wrestling with a hand-crank in 95° heat.</p>

    <h2 class="afn-h2-left">Motorized, Weather-Aware Shade</h2>
    <p>Every awning we install includes smart wind sensors that automatically retract the fabric when storm-force winds hit, protecting the system from damage — the single biggest worry we hear from Florida homeowners, solved before you even notice the wind picked up. Pair that with Alexa and Google Home integration for full one-touch control.</p>

    <h2 class="afn-h2-left">Hurricane-Resistant Construction</h2>
    <p>Our aluminum pergola frames and awning arms are engineered to meet Florida's strict wind-load building codes, so your shade structure is built to last through the region's toughest weather — not just look good until the first named storm.</p>

    [afn_trust_grid]
    [afn_trust title="Smart Wind Sensors"]Auto-retract before storms hit — no manual intervention needed.[/afn_trust]
    [afn_trust title="Alexa &amp; Google Home"]Voice-controlled operation for total convenience.[/afn_trust]
    [afn_trust title="Licensed &amp; Insured"]Full Florida state licensing and liability insurance on every install.[/afn_trust]
    [/afn_trust_grid]
</div>

<?php
// Одинаковая для всех страниц услуг полоса с телефоном из конфигурации.
Blocks::renderCtaBand('Get a Free In-Home Estimate');
