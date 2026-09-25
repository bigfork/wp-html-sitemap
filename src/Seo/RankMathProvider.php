<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Seo;

defined('ABSPATH') || exit;

final class RankMathProvider implements NoindexProvider
{
    private const string META = 'rank_math_robots';
    private const string TITLES_OPTION = 'rank-math-options-titles';

    public function id(): string
    {
        return 'rank-math';
    }

    public function label(): string
    {
        return 'Rank Math';
    }

    public function isAvailable(): bool
    {
        return defined('RANK_MATH_VERSION');
    }

    public function isPostNoindexed(int $postId, string $postType): bool
    {
        $robots = get_post_meta($postId, self::META, true);

        return is_array($robots) && $robots !== []
            ? in_array('noindex', $robots, true)
            : $this->isPostTypeNoindexed($postType);
    }

    public function isTermNoindexed(int $termId, string $taxonomy): bool
    {
        $robots = get_term_meta($termId, self::META, true);

        return is_array($robots) && $robots !== []
            ? in_array('noindex', $robots, true)
            : $this->isTaxonomyNoindexed($taxonomy);
    }

    public function isPostTypeNoindexed(string $postType): bool
    {
        return $this->defaultNoindex("pt_{$postType}");
    }

    public function isTaxonomyNoindexed(string $taxonomy): bool
    {
        return $this->defaultNoindex("tax_{$taxonomy}");
    }

    public function watchedOptions(): array
    {
        return [self::TITLES_OPTION];
    }

    private function defaultNoindex(string $prefix): bool
    {
        $titles = get_option(self::TITLES_OPTION);
        $titles = is_array($titles) ? $titles : [];

        $robots = ($titles["{$prefix}_custom_robots"] ?? 'off') === 'on'
            ? ($titles["{$prefix}_robots"] ?? [])
            : ($titles['robots_global'] ?? []);

        return is_array($robots) && in_array('noindex', $robots, true);
    }
}
