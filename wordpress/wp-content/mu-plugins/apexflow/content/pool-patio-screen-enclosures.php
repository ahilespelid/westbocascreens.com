<?php
/**
 * Страница услуги: экранные павильоны для бассейнов и патио.
 */

use ApexFlow\Blocks;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

// Компактный герой: на внутренних страницах он ниже, чем на главной.
Blocks::renderHero(
    'Pool & Patio Screen Enclosures — Boca Raton, FL',
    'Custom-engineered aluminum enclosures, built to last — not a patch-and-repatch job.'
);
?>

<div class="afn-section-narrow">
    <p>Wow Apex Flow designs and builds custom pool cage and patio screen enclosures from the ground up. We specialize in full new-construction aluminum framing — not small rescreening jobs — giving your outdoor space a complete, durable, insect-free upgrade that actually adds value to your home.</p>

    <h2 class="afn-h2-left">Engineered for Florida Code</h2>
    <p>Every enclosure is engineered to meet Florida's strict building and wind-load codes, using premium aluminum framing rated for hurricane conditions. Your pool cage or patio enclosure is built to protect your family and your investment for decades, not just a few seasons.</p>

    <h2 class="afn-h2-left">Complete Insect &amp; Sun Protection</h2>
    <p>A fully screened enclosure gives you 100% protection from mosquitoes and no-see-ums while filtering harsh UV rays — so the pool actually gets used on a random Tuesday evening, not just for guests.</p>

    [afn_trust_grid]
    [afn_trust title="Custom-Engineered"]Full aluminum framing designed and built for your exact space — not a generic patch job.[/afn_trust]
    [afn_trust title="Hurricane-Resistant"]Framing engineered to meet Florida's strict wind-load building codes.[/afn_trust]
    [afn_trust title="Licensed &amp; Insured"]Fully licensed by the State of Florida with complete liability coverage.[/afn_trust]
    [/afn_trust_grid]
</div>

<?php
// Одинаковая для всех страниц услуг полоса с телефоном из конфигурации.
Blocks::renderCtaBand('Get a Free In-Home Estimate');
