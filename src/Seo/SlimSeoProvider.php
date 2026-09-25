<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Seo;

defined('ABSPATH') || exit;

final class SlimSeoProvider implements NoindexProvider
{
    private const string KEY = 'slim_seo';

    public function id(): string
    {
        return 'slim-seo';
    }

    public function label(): string
    {
        return 'Slim SEO';
    }

    public function isAvailable(): bool
    {
        return defined('SLIM_SEO_VER');
    }

    public function isPostNoindexed(int $postId, string $postType): bool
    {
        return $this->isPostTypeNoindexed($postType)
            || $this->flagged(get_post_meta($postId, self::KEY, true));
    }

    public function isTermNoindexed(int $termId, string $taxonomy): bool
    {
        return $this->isTaxonomyNoindexed($taxonomy)
            || $this->flagged(get_term_meta($termId, self::KEY, true));
    }

    public function isPostTypeNoindexed(string $postType): bool
    {
        return $this->flagged($this->settings()[$postType] ?? null);
    }

    public function isTaxonomyNoindexed(string $taxonomy): bool
    {
        return $this->flagged($this->settings()[$taxonomy] ?? null);
    }

    public function watchedOptions(): array
    {
        return [self::KEY];
    }

    private function settings(): array
    {
        $settings = get_option(self::KEY);

        return is_array($settings) ? $settings : [];
    }

    private function flagged(mixed $data): bool
    {
        return is_array($data) && !empty($data['noindex']);
    }
}
