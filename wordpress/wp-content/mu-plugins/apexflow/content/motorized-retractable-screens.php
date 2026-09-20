<?php
/**
 * Страница услуги: моторизованные раздвижные экраны.
 */

use ApexFlow\Blocks;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

// Компактный герой: на внутренних страницах он ниже, чем на главной.
Blocks::renderHero(
    'Motorized Retractable Screens in West Boca Raton, FL',
    'Seal off your lanai, patio, or garage at the touch of a button — full protection from sun, rain, and insects, without lifting a finger.'
);
?>

<div class="afn-section-narrow">
    <p>Wow Apex Flow designs and installs heavy-duty motorized retractable screens for lanais, patios, garages, and outdoor kitchens throughout West Boca Raton. Unlike manual roll-down screens, our motorized systems glide open and closed with one touch of a remote, smartphone app, or Alexa/Google Home voice command — because your evening on the lanai shouldn't start with a workout.</p>

    <h2 class="afn-h2-left">Built for Florida's Climate</h2>
    <p>Every screen system uses hurricane-rated aluminum housings and tracks engineered to withstand South Florida's wind loads. When a storm rolls in, motorized panels create a sealed barrier that blocks up to 90% of wind-driven rain, keeping your furniture, TVs, and flooring dry.</p>

    <h2 class="afn-h2-left">100% Insect &amp; UV Protection</h2>
    <p>Our screen mesh blocks mosquitoes and no-see-ums completely while filtering harsh UV rays, so evenings on the lanai become something your family actually looks forward to — not something you brace for.</p>

    [afn_trust_grid]
    [afn_trust title="Smart Home Ready"]Operate your screens from your phone or with Alexa and Google Home voice commands.[/afn_trust]
    [afn_trust title="Smart Wind Sensors"]Automatic retraction during high-wind events protects your investment.[/afn_trust]
    [afn_trust title="Licensed &amp; Insured"]Fully licensed by the State of Florida with complete liability coverage.[/afn_trust]
    [/afn_trust_grid]
</div>

<?php
// Одинаковая для всех страниц услуг полоса с телефоном из конфигурации.
Blocks::renderCtaBand('Get a Free In-Home Estimate');
