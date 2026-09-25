<?php

/**
 * Plugin Name:       HTML Sitemap
 * Description:       Configurable HTML sitemap via the [html_sitemap] shortcode, with SEO plugin aware noindex exclusion and multilingual support.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.3
 * Author:            Bigfork
 * Author URI:        https://www.bigfork.co.uk
 * License:           BSD-3-Clause
 * Text Domain:       bf-html-sitemap
 * Domain Path:       /languages
 */

declare(strict_types=1);

use Bigfork\HtmlSitemap\Plugin;

defined('ABSPATH') || exit;

if (!class_exists(Plugin::class) && is_readable(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

Plugin::boot(__FILE__);
