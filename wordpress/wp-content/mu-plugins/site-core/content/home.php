<?php
/**
 * Главная страница. Файл подключается модулем Content вместо содержимого из базы.
 * Шорткоды в разметке раскрываются ядром после этого фильтра, как и раньше.
 */

use SiteCore\Blocks;
use SiteCore\Config;

// Прямой вызов файла мимо WordPress запрещён.
if (!defined('ABSPATH')) {
    exit;
}

// Отзывы соседей: тексты предоставлены владельцем сайта, выводятся дословно.
$testimonials = [
    ['name' => 'Robert K.', 'place' => 'Boca Falls (Gated Community)', 'text' => 'We wanted to enclose our covered patio to create a bug-free outdoor dining area, but our HOA has incredibly strict rules regarding exterior modifications. The team at West Boca Screens handled everything perfectly. They helped us choose custom-colored motorized roll-down screens that match our home’s trim beautifully and flew through the HOA approval process. The rain-blocking fabric is a game-changer - we just lower them from our iPhones when a summer storm hits, and our outdoor furniture stays completely dry. Highly recommend!'],
    ['name' => 'Amanda M.', 'place' => 'Mission Bay (West Boca)', 'text' => 'We recently had a premium motorized retractable awning installed over our pool deck, and the quality is absolutely top-tier. The Sunbrella fabric looks amazing and blocks the brutal afternoon sun completely. What really sold me was the integrated dimmable LED lighting for nighttime and the smart wind sensors. Last week, a sudden storm rolled in while we were away from home, and the awning automatically retracted itself before the heavy winds hit. Professional crew, premium fit and finish, and zero sales pressure during the estimate.'],
    ['name' => 'David L.', 'place' => 'Boca Greens (Country Club Community)', 'text' => 'If you want cheap, basic mesh patches, call someone else. But if you want a premium, high-quality outdoor living space built to last, these are the guys to call. They engineered and built a stunning new patio screen enclosure for us. The heavy-duty aluminum framing is clearly designed to meet Florida’s toughest hurricane codes, and the structural warranty gives us total peace of mind. True service professionals who treat your property with absolute respect. Best in the industry!'],
];

// Полноразмерный герой с кнопкой: единственная страница, где он такой.
Blocks::renderHero(
    'Premium Motorized Screens & Retractable Awnings in West Boca Raton, FL',
    'Turn your outdoor living space into the room you never want to leave - an all-weather retreat that shuts out sun, storms, and mosquitoes at the touch of a button.',
    false,
    'Get a Free In-Home Estimate'
);

// Первый блок под героем: фото маркизы с пультом и короткий рассказ о продукте.
Blocks::renderMediaSplit(
    Config::AWNING_IMAGE,
    'Striped Sunbrella retractable awning extended over a patio, with the motorized remote control in hand',
    'Retractable Awnings, Controlled From Your Chair',
    'One press of the remote extends a custom Sunbrella&reg; awning over your outdoor living space, and one more tucks it away. Smart wind sensors retract it on their own when a storm moves in.'
);

// Видео: ролик YouTube из конфигурации или, пока он не выбран, собственное видео.
Blocks::renderVideo(
    'See It In Action',
    'Watch motorized retractable screens and awnings transform an outdoor living space in seconds - rain, wind, or shine.'
);
?>

<div class="sc-section">
    <h2>How It Works</h2>
    <p class="sc-section-lede">From first call to finished install, we make it effortless.</p>
    [sc_steps]
    [sc_step num="1" title="Call or Request a Quote"]Reach a real person, not a call center. Tell us about your space and we'll schedule your free in-home visit.[/sc_step]
    [sc_step num="2" title="Custom Design Consultation &amp; Free Estimate"]A design specialist comes to your home with product catalogs and physical Sunbrella&reg; fabric samples, measures every opening, and gives you an exact written price.[/sc_step]
    [sc_step num="3" title="Professional Installation"]Made in the USA and installed by factory-trained technicians. Factory warranty, an apples-to-apples price guarantee, and no sales pressure. Your custom order will be installed by our service professionals.[/sc_step]
    [/sc_steps]
</div>

<div class="sc-section">
    <h2>Best in the Industry</h2>
    <p class="sc-section-lede">Premium features that come standard on every system we build.</p>
    <?php Blocks::renderFeatureGrid(Config::PREMIUM_FEATURES); ?>
</div>

<div class="sc-section">
    <h2>Why West Boca Raton Homeowners Choose <?php echo esc_html(Config::BRAND); ?></h2>
    <p class="sc-section-lede">Because "good enough" doesn't survive hurricane season.</p>
    [sc_trust_grid]
    [sc_trust title="All-Weather &amp; Rain Protection"]Motorized panels create a sealed barrier that blocks up to 90% of driving rain and wind during tropical storms, keeping your furniture, TVs, and outdoor living space dry and mold-free.[/sc_trust]
    [sc_trust title="Smart Home &amp; Alexa Integration"]One touch from your remote, phone, or voice - "Alexa, close the screens" - and your outdoor room seals itself up.[/sc_trust]
    [sc_trust title="Smart Wind Sensors"]Built-in sensors auto-retract your awning the moment storm-force winds hit. The #1 worry for Florida homeowners, handled while you're not even home.[/sc_trust]
    [sc_trust title="UV Ray &amp; Insect Barrier"]Real shade from Florida's brutal sun, and a mosquito- and no-see-um-free zone your family will actually want to spend evenings in.[/sc_trust]
    [sc_trust title="Hurricane-Resistant Engineering"]Heavy-duty aluminum frames engineered to meet Florida's strict hurricane building codes - built to outlast the storm, not just survive it.[/sc_trust]
    [sc_trust title="Licensed &amp; Fully Insured"]Fully licensed by the State of Florida and fully insured, so every install is backed by real accountability, not just a handshake.[/sc_trust]
    [/sc_trust_grid]
</div>

<div class="sc-section">
    <h2>Our Services</h2>
    <ul class="sc-services-list">
        <li><a href="/motorized-retractable-screens/">Motorized Retractable Screens →</a></li>
        <li><a href="/retractable-awnings-pergolas/">Retractable Awnings &amp; Pergolas →</a></li>
        <li><a href="/pool-patio-screen-enclosures/">Pool &amp; Patio Screen Enclosures →</a></li>
        <li><a href="/commercial-custom-shade-solutions/">Commercial Custom Shade Solutions →</a></li>
    </ul>
</div>

<div class="sc-section">
    <h2>What Your Neighbors Say</h2>
    <p class="sc-section-lede">Homeowners across West Boca's gated and country club communities.</p>
    <?php Blocks::renderTestimonials($testimonials); ?>
</div>

<div id="faq" class="sc-section-narrow">
    <h2 class="sc-h2-left">Frequently Asked Questions</h2>
    [sc_faq q="Who installs my system?" a="Your custom order will be installed by our service professionals: factory-trained technicians who walk you through your new controls before they leave."]
    [sc_faq q="Will my design be approved by my HOA?" a="Our profiles and color palette are specified to meet HOA architectural review in West Boca's gated and country club communities, and we prepare the approval package for you."]
    [sc_faq q="Are your systems built for Florida hurricanes?" a="Yes. Our aluminum frames are engineered to meet Florida's strict wind-load building codes, and every system includes smart wind sensors that auto-retract in high winds."]
    [sc_faq q="Do you offer financing?" a="Yes, we offer flexible financing options for qualified homeowners. Ask your design specialist about current plans."]
    [sc_faq q="What areas do you serve?" a="We proudly serve West Boca Raton and surrounding communities, including ZIP codes <?php echo esc_html(Config::zipList()); ?>."]
    [sc_faq q="Are you licensed and insured?" a="Yes, <?php echo esc_html(Config::BRAND); ?> is fully licensed by the State of Florida and carries complete liability insurance."]
    [sc_faq q="Can I control my screens with Alexa or Google Home?" a="Yes. All our motorized systems support smart-home integration, including Alexa and Google Home voice control."]
</div>

<?php
// Финальный призыв к действию: единственный блок с якорем #quote, на него ведут кнопки шапки и героя.
Blocks::renderCtaBand(
    'Your Outdoor Living Space Could Be Ready Before the Next Storm Rolls In',
    'Free in-home estimate. No obligation, no pressure - just a real plan for your space.'
);
