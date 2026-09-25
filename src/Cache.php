<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap;

use Bigfork\HtmlSitemap\Seo\ProviderRegistry;

defined('ABSPATH') || exit;

final class Cache
{
    public const string VERSION_OPTION = 'bf_html_sitemap_cache_version';

    private const string PREFIX = 'bf_html_sitemap_';

    private const array POST_HOOKS = ['save_post', 'deleted_post', 'trashed_post', 'untrashed_post'];
    private const array TERM_HOOKS = ['created_term', 'edited_term', 'delete_term'];
    private const array OPTIONS = [Options::OPTION, 'permalink_structure', 'show_on_front', 'page_on_front'];

    private bool $flushed = false;

    public function register(ProviderRegistry $seo): void
    {
        foreach (self::POST_HOOKS as $hook) {
            add_action($hook, $this->flushForPost(...));
        }

        foreach (self::TERM_HOOKS as $hook) {
            add_action($hook, $this->flush(...));
        }

        add_action('init', function () use ($seo): void {
            foreach ([...self::OPTIONS, ...$seo->provider()->watchedOptions()] as $option) {
                add_action("add_option_{$option}", $this->flush(...));
                add_action("update_option_{$option}", $this->flush(...));
                add_action("delete_option_{$option}", $this->flush(...));
            }
        });
    }

    public function remember(array $context, callable $callback): string
    {
        if (!apply_filters('bf_html_sitemap_cache_enabled', true)) {
            return $callback();
        }

        $key = self::PREFIX . md5((string) wp_json_encode([$this->version(), $context]));
        $cached = get_transient($key);

        if (is_string($cached)) {
            return $cached;
        }

        $html = $callback();
        set_transient($key, $html, (int) apply_filters('bf_html_sitemap_cache_ttl', DAY_IN_SECONDS));

        return $html;
    }

    public function flush(): void
    {
        if ($this->flushed) {
            return;
        }

        $this->flushed = true;
        update_option(self::VERSION_OPTION, uniqid('', true), true);

        do_action('bf_html_sitemap_flushed');
    }

    private function flushForPost(int $postId): void
    {
        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }

        $postType = get_post_type($postId);

        if ($postType !== false && is_post_type_viewable($postType)) {
            $this->flush();
        }
    }

    private function version(): string
    {
        return (string) get_option(self::VERSION_OPTION, '0');
    }
}
