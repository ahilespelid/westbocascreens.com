<?php
/**
 * Plugin Name: Site Core
 * Description: Ядро сайта: разметка страниц из файлов, SEO-мета, Schema.org, отзывы, шорткоды. Без зависимости от page-builder'ов.
 * Version:     2.0.0
 */

// Прямой вызов файла мимо WordPress запрещён: без ABSPATH дальше идти не за чем.
if (!defined('ABSPATH')) {
    exit;
}

// Каталог с модулями ядра — один раз, чтобы ниже не повторять путь.
const SITE_CORE_DIR = __DIR__ . '/site-core';

// Порядок загрузки значения не имеет: каждый модуль только вешает свои хуки.
require_once SITE_CORE_DIR . '/Config.php';
require_once SITE_CORE_DIR . '/Assets.php';
require_once SITE_CORE_DIR . '/Blocks.php';
require_once SITE_CORE_DIR . '/Layout.php';
require_once SITE_CORE_DIR . '/Seo.php';
require_once SITE_CORE_DIR . '/Schema.php';
require_once SITE_CORE_DIR . '/Shortcodes.php';
require_once SITE_CORE_DIR . '/Reviews.php';
require_once SITE_CORE_DIR . '/Content.php';
require_once SITE_CORE_DIR . '/Sitemap.php';

// Единая точка включения: каждый модуль сам решает, на какие хуки подписаться.
ApexFlow\Assets::register();
ApexFlow\Layout::register();
ApexFlow\Seo::register();
ApexFlow\Schema::register();
ApexFlow\Shortcodes::register();
ApexFlow\Reviews::register();
ApexFlow\Content::register();
ApexFlow\Sitemap::register();
