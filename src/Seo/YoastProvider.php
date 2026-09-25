<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Seo;

defined('ABSPATH') || exit;

final class YoastProvider implements NoindexProvider
{
    private const string POST_META = '_yoast_wpseo_meta-robots-noindex';
    private const string POST_NOINDEX = '1';
    private const string POST_INDEX = '2';
    private const string TITLES_OPTION = 'wpseo_titles';
    private const string TAXONOMY_META_OPTION = 'wpseo_taxonomy_meta';

    public function id(): string
    {
        return 'yoast';
    }

    public function label(): string
    {
        return 'Yoast SEO';
    }

    public function isAvailable(): bool
    {
        return defined('WPSEO_VERSION');
    }

    public function isPostNoindexed(int $postId, string $postType): bool
    {
        return match ((string) get_post_meta($postId, self::POST_META, true)) {
            self::POST_NOINDEX => true,
            self::POST_INDEX => false,
            default => $this->isPostTypeNoindexed($postType),
        };
    }

    public function isTermNoindexed(int $termId, string $taxonomy): bool
    {
        $meta = get_option(self::TAXONOMY_META_OPTION);
        $value = is_array($meta) ? ($meta[$taxonomy][$termId]['wpseo_noindex'] ?? 'default') : 'default';

        return match ($value) {
            'noindex' => true,
            'index' => false,
            default => $this->isTaxonomyNoindexed($taxonomy),
        };
    }

    public function isPostTypeNoindexed(string $postType): bool
    {
        return !empty($this->titles()["noindex-{$postType}"]);
    }

    public function isTaxonomyNoindexed(string $taxonomy): bool
    {
        return !empty($this->titles()["noindex-tax-{$taxonomy}"]);
    }

    public function watchedOptions(): array
    {
        return [self::TITLES_OPTION, self::TAXONOMY_META_OPTION];
    }

    private function titles(): array
    {
        $titles = get_option(self::TITLES_OPTION);

        return is_array($titles) ? $titles : [];
    }
}
