<?php
/**
 * Главная страница. Файл подключается модулем Content вместо содержимого из базы.
 * Шорткоды в разметке раскрываются ядром после этого фильтра, как и раньше.
 */

use ApexFlow\Blocks;
use ApexFlow\Config;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

// Полноразмерный герой с кнопкой: единственная страница, где он такой.
Blocks::renderHero(
    'Premium Motorized Screens & Retractable Awnings in West Boca Raton, FL',
    'Turn your lanai into the room you never want to leave — an all-weather retreat that shuts out sun, storms, and mosquitoes at the touch of a button.',
    false,
    'Get a Free In-Home Estimate'
);
?>

<div class="afn-video-section">
    <h2>See It In Action</h2>
    <p class="afn-section-lede">Watch our motorized screens seal off the lanai in seconds &mdash; rain, wind, or shine.</p>
    <div class="afn-video-wrap">
        <video id="afn-demo-video" muted loop playsinline preload="none" poster="<?php echo esc_url(Config::contentUrl(Config::HERO_IMAGE)); ?>">
            <source data-src="<?php echo esc_url(Config::contentUrl('/uploads/site/hero-demo.webm')); ?>" type="video/webm">
            <source data-src="<?php echo esc_url(Config::contentUrl('/uploads/site/hero-demo.mp4')); ?>" type="video/mp4">
        </video>
        <button type="button" class="afn-sound-toggle" aria-label="Toggle sound">&#128264;</button>
    </div>
</div>

<div class="afn-section">
    <h2>How It Works</h2>
    <p class="afn-section-lede">From first call to finished install, we make it effortless.</p>
    [afn_steps]
    [afn_step num="1" title="Call or Request a Quote"]Reach a real person, not a call center. Tell us about your space and we'll schedule your free in-home visit.[/afn_step]
    [afn_step num="2" title="Free Design &amp; In-Home Estimate"]We measure, walk you through options, and give you an exact price — no surprises, no pressure.[/afn_step]
    [afn_step num="3" title="Professional Install, Enjoy Same Week"]Our licensed crew installs your system, usually within 1-2 days, and walks you through your new smart controls.[/afn_step]
    [/afn_steps]
</div>

<div class="afn-section">
    <h2>Why West Boca Raton Homeowners Choose <?php echo esc_html(Config::BRAND); ?></h2>
    <p class="afn-section-lede">Because "good enough" doesn't survive hurricane season.</p>
    [afn_trust_grid]
    [afn_trust title="All-Weather &amp; Rain Protection"]Motorized panels create a sealed barrier that blocks up to 90% of driving rain and wind during tropical storms, keeping your furniture, TVs, and lanai dry and mold-free.[/afn_trust]
    [afn_trust title="Smart Home &amp; Alexa Integration"]One touch from your remote, phone, or voice — "Alexa, close the lanai" — and your outdoor room seals itself up.[/afn_trust]
    [afn_trust title="Smart Wind Sensors"]Built-in sensors auto-retract your awning the moment storm-force winds hit. The #1 worry for Florida homeowners, handled while you're not even home.[/afn_trust]
    [afn_trust title="UV Ray &amp; Insect Barrier"]Real shade from Florida's brutal sun, and a mosquito- and no-see-um-free zone your family will actually want to spend evenings in.[/afn_trust]
    [afn_trust title="Hurricane-Resistant Engineering"]Heavy-duty aluminum frames engineered to meet Florida's strict hurricane building codes — built to outlast the storm, not just survive it.[/afn_trust]
    [afn_trust title="Licensed &amp; Fully Insured"]Fully licensed by the State of Florida and fully insured, so every install is backed by real accountability, not just a handshake.[/afn_trust]
    [/afn_trust_grid]
</div>

<div class="afn-section">
    <h2>Our Services</h2>
    <ul class="afn-services-list">
        <li><a href="/motorized-retractable-screens/">Motorized Retractable Screens →</a></li>
        <li><a href="/retractable-awnings-pergolas/">Retractable Awnings &amp; Pergolas →</a></li>
        <li><a href="/pool-patio-screen-enclosures/">Pool &amp; Patio Screen Enclosures →</a></li>
        <li><a href="/commercial-custom-shade-solutions/">Commercial Custom Shade Solutions →</a></li>
    </ul>
</div>

<div id="faq" class="afn-section-narrow">
    <h2 class="afn-h2-left">Frequently Asked Questions</h2>
    [afn_faq q="How long does installation take?" a="Most residential motorized screen and awning installations are completed in 1-2 days, depending on the size and number of openings."]
    [afn_faq q="Are your systems built for Florida hurricanes?" a="Yes. Our aluminum frames are engineered to meet Florida's strict wind-load building codes, and every system includes smart wind sensors that auto-retract in high winds."]
    [afn_faq q="Do you offer financing?" a="Yes, we offer flexible financing options for qualified homeowners. Ask your estimator about current plans."]
    [afn_faq q="What areas do you serve?" a="We proudly serve West Boca Raton and surrounding communities, including ZIP codes <?php echo esc_html(Config::zipList()); ?>."]
    [afn_faq q="Are you licensed and insured?" a="Yes, <?php echo esc_html(Config::BRAND); ?> is fully licensed by the State of Florida and carries complete liability insurance."]
    [afn_faq q="Can I control my screens with Alexa or Google Home?" a="Yes. All our motorized systems support smart-home integration, including Alexa and Google Home voice control."]
</div>

<?php
// Финальный призыв к действию: единственный блок с якорем #quote, на него ведут кнопки шапки и героя.
Blocks::renderCtaBand(
    'Your Lanai Could Be Ready Before the Next Storm Rolls In',
    'Free in-home estimate. No obligation, no pressure — just a real plan for your space.'
);
