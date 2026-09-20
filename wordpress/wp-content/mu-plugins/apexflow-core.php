<?php
/**
 * Plugin Name: Wow Apex Flow — Core
 * Description: Ядро сайта: разметка страниц из файлов, SEO-мета, Schema.org, отзывы, шорткоды. Без зависимости от page-builder'ов.
 * Version:     2.0.0
 */

// Прямой вызов файла мимо WordPress запрещён: без ABSPATH дальше идти не за чем.
if (!defined('ABSPATH')) {
    exit;
}

// Каталог с модулями ядра — один раз, чтобы ниже не повторять путь.
const APEXFLOW_DIR = __DIR__ . '/apexflow';

// Порядок загрузки значения не имеет: каждый модуль только вешает свои хуки.
require_once APEXFLOW_DIR . '/Config.php';
require_once APEXFLOW_DIR . '/Assets.php';
require_once APEXFLOW_DIR . '/Blocks.php';
require_once APEXFLOW_DIR . '/Layout.php';
require_once APEXFLOW_DIR . '/Seo.php';
require_once APEXFLOW_DIR . '/Schema.php';
require_once APEXFLOW_DIR . '/Shortcodes.php';
require_once APEXFLOW_DIR . '/Reviews.php';
require_once APEXFLOW_DIR . '/Content.php';
require_once APEXFLOW_DIR . '/Sitemap.php';

// Единая точка включения: каждый модуль сам решает, на какие хуки подписаться.
ApexFlow\Assets::register();
ApexFlow\Layout::register();
ApexFlow\Seo::register();
ApexFlow\Schema::register();
ApexFlow\Shortcodes::register();
ApexFlow\Reviews::register();
ApexFlow\Content::register();
ApexFlow\Sitemap::register();
