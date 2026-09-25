<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap;

defined('ABSPATH') || exit;

final readonly class PageCache
{
    public function __construct(private SitemapPages $pages)
    {
    }

    public function register(): void
    {
        add_action('bf_html_sitemap_flushed', $this->purge(...));
    }

    private function purge(): void
    {
        foreach ($this->pages->all() as $postId) {
            if (get_post_status($postId) !== 'publish') {
                continue;
            }

            if (function_exists('rocket_clean_files')) {
                rocket_clean_files([(string) get_permalink($postId)]);
            }

            if (function_exists('w3tc_flush_post')) {
                w3tc_flush_post($postId);
            }

            if (function_exists('wp_cache_post_change')) {
                wp_cache_post_change($postId);
            }

            do_action('litespeed_purge_post', $postId);
            do_action('bf_html_sitemap_purge_post', $postId);
        }
    }
}
