<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap;

defined('ABSPATH') || exit;

final class Exclusion
{
    public const string META_KEY = '_bf_html_sitemap_exclude';
    public const string FIELD = 'bf_html_sitemap_exclude';
    public const string NONCE = 'bf_html_sitemap_exclude_nonce';
    public const string CONTEXT_POST = 'post';
    public const string CONTEXT_TERM = 'term';

    public static function isPostExcluded(int $postId): bool
    {
        return (bool) get_post_meta($postId, self::META_KEY, true);
    }

    public static function isTermExcluded(int $termId): bool
    {
        return (bool) get_term_meta($termId, self::META_KEY, true);
    }

    public static function setPostExcluded(int $postId, bool $excluded): void
    {
        $excluded
            ? update_post_meta($postId, self::META_KEY, '1')
            : delete_post_meta($postId, self::META_KEY);
    }

    public static function setTermExcluded(int $termId, bool $excluded): void
    {
        $excluded
            ? update_term_meta($termId, self::META_KEY, '1')
            : delete_term_meta($termId, self::META_KEY);
    }

    public static function notFlaggedMetaQuery(): array
    {
        return [
            [
                'key' => self::META_KEY,
                'compare' => 'NOT EXISTS',
            ],
        ];
    }

    public static function flaggedMetaQuery(): array
    {
        return [
            [
                'key' => self::META_KEY,
                'compare' => 'EXISTS',
            ],
        ];
    }

    public static function nonceAction(string $context): string
    {
        return "bf_html_sitemap_exclude_{$context}";
    }

    public static function submitted(string $context): ?bool
    {
        $nonce = isset($_POST[self::NONCE]) ? sanitize_text_field(wp_unslash($_POST[self::NONCE])) : '';

        if (!wp_verify_nonce($nonce, self::nonceAction($context))) {
            return null;
        }

        return !empty($_POST[self::FIELD]);
    }
}
