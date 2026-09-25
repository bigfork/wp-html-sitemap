<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Cli;

use Bigfork\HtmlSitemap\Plugin;
use Bigfork\HtmlSitemap\Shortcode;
use WP_CLI;

defined('ABSPATH') || exit;

/**
 * Manage the HTML sitemap.
 */
final readonly class Command
{
    public function __construct(private Plugin $plugin)
    {
    }

    /**
     * Flush the cached sitemap output.
     *
     * ## EXAMPLES
     *
     *     wp html-sitemap flush
     */
    public function flush(): void
    {
        $this->plugin->cache->flush();
        WP_CLI::success('HTML sitemap cache flushed.');
    }

    /**
     * Render the sitemap markup, bypassing the cache.
     *
     * ## OPTIONS
     *
     * [--types=<types>]
     * : Comma separated post types.
     *
     * [--taxonomies=<taxonomies>]
     * : Comma separated taxonomies.
     *
     * [--exclude=<ids>]
     * : Comma separated post IDs.

     *
     * ## EXAMPLES
     *
     *     wp html-sitemap render --types=page
     */
    public function render(array $args, array $assoc): void
    {
        add_filter('bf_html_sitemap_cache_enabled', '__return_false');

        WP_CLI::line((new Shortcode($this->plugin))->render(array_intersect_key(
            $assoc,
            array_flip(['types', 'taxonomies', 'exclude']),
        )));
    }

    /**
     * List the pages containing the shortcode, whose page cache is purged on change.
     *
     * ## OPTIONS
     *
     * [--rebuild]
     * : Rescan post content and post meta (including ACF fields) first.
     *
     * ## EXAMPLES
     *
     *     wp html-sitemap pages --rebuild
     */
    public function pages(array $args, array $assoc): void
    {
        if (!empty($assoc['rebuild'])) {
            $this->plugin->pages->rebuild();
        }

        $rows = array_map(fn (int $id): array => [
            'ID' => $id,
            'title' => get_the_title($id),
            'url' => get_permalink($id),
        ], $this->plugin->pages->all());

        $rows === []
            ? WP_CLI::line('No pages contain the shortcode.')
            : WP_CLI\Utils\format_items('table', $rows, ['ID', 'title', 'url']);
    }

    /**
     * Show which SEO and multilingual integrations are active.
     *
     * ## EXAMPLES
     *
     *     wp html-sitemap status
     *
     * @subcommand status
     */
    public function status(): void
    {
        $options = $this->plugin->options();

        WP_CLI\Utils\format_items('table', [
            ['setting' => 'SEO provider', 'value' => $this->plugin->seo->provider()->label()],
            ['setting' => 'Language provider', 'value' => $this->plugin->languages->provider()->id()],
            ['setting' => 'Post types', 'value' => implode(', ', array_keys($options->enabledPostTypes())) ?: '-'],
            ['setting' => 'Taxonomies', 'value' => implode(', ', array_keys($options->enabledTaxonomies())) ?: '-'],
            ['setting' => 'Exclude descendants', 'value' => $options->excludeDescendants ? 'yes' : 'no'],
            ['setting' => 'Schema', 'value' => $options->schema ? 'yes' : 'no'],
            ['setting' => 'Excluded IDs', 'value' => implode(', ', $options->excludeIds) ?: '-'],
        ], ['setting', 'value']);
    }
}
