<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Seo;

defined('ABSPATH') || exit;

interface NoindexProvider
{
    public function id(): string;

    public function label(): string;

    public function isAvailable(): bool;

    public function isPostNoindexed(int $postId, string $postType): bool;

    public function isTermNoindexed(int $termId, string $taxonomy): bool;

    public function isPostTypeNoindexed(string $postType): bool;

    public function isTaxonomyNoindexed(string $taxonomy): bool;


    /**
     * Options whose change should invalidate the sitemap cache.
     *
     * @return list<string>
     */
    public function watchedOptions(): array;
}
