<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Seo;

defined('ABSPATH') || exit;

final class NullProvider implements NoindexProvider
{
    public const string ID = 'none';

    public function id(): string
    {
        return self::ID;
    }

    public function label(): string
    {
        return __('None', 'bf-html-sitemap');
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function isPostNoindexed(int $postId, string $postType): bool
    {
        return false;
    }

    public function isTermNoindexed(int $termId, string $taxonomy): bool
    {
        return false;
    }

    public function isPostTypeNoindexed(string $postType): bool
    {
        return false;
    }

    public function isTaxonomyNoindexed(string $taxonomy): bool
    {
        return false;
    }

    public function watchedOptions(): array
    {
        return [];
    }
}
