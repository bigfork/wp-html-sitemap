<?php

declare(strict_types=1);

use Bigfork\HtmlSitemap\Cache;
use Bigfork\HtmlSitemap\Exclusion;
use Bigfork\HtmlSitemap\Options;
use Bigfork\HtmlSitemap\SitemapPages;

defined('WP_UNINSTALL_PLUGIN') || exit;

if (!class_exists(Options::class) && is_readable(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

$cleanUp = static function (): void {
    global $wpdb;

    foreach ([Options::OPTION, Cache::VERSION_OPTION, SitemapPages::OPTION] as $option) {
        delete_option($option);
    }

    delete_metadata('post', 0, Exclusion::META_KEY, '', true);
    delete_metadata('term', 0, Exclusion::META_KEY, '', true);

    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('_transient_bf_html_sitemap_') . '%',
        $wpdb->esc_like('_transient_timeout_bf_html_sitemap_') . '%',
    ));
};

if (is_multisite()) {
    foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $siteId) {
        switch_to_blog((int) $siteId);
        $cleanUp();
        restore_current_blog();
    }
} else {
    $cleanUp();
}
