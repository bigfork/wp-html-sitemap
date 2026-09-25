<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Sitemap;

use Bigfork\HtmlSitemap\Exclusion;
use Bigfork\HtmlSitemap\Language\LanguageProvider;
use Bigfork\HtmlSitemap\Options;
use Bigfork\HtmlSitemap\Seo\NoindexProvider;
use WP_Post;
use WP_Query;
use WP_Term;

defined('ABSPATH') || exit;

final readonly class Builder
{
    public function __construct(
        private Options $options,
        private NoindexProvider $seo,
        private LanguageProvider $language,
    ) {
    }

    /**
     * @param list<string>|null $postTypes Restrict to these post types; null uses settings.
     * @param list<string>|null $taxonomies Restrict to these taxonomies; null uses settings.
     * @param list<int> $excludeIds Additional post IDs to leave out.
     * @return list<Section>
     */
    public function build(?array $postTypes = null, ?array $taxonomies = null, array $excludeIds = []): array
    {
        $excludeIds = $this->translateIds([...$this->options->excludeIds, ...$excludeIds]);
        $currentId = is_singular() ? (int) get_queried_object_id() : 0;

        $plans = [];

        foreach ($this->postTypeConfigs($postTypes) as $config) {
            $plans[] = [$config->position, fn (): ?Section => $this->postTypeSection($config, $excludeIds, $currentId)];
        }

        foreach ($this->taxonomyConfigs($taxonomies) as $config) {
            $plans[] = [$config->position, fn (): ?Section => $this->taxonomySection($config)];
        }

        usort($plans, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $sections = array_values(array_filter(array_map(fn (array $plan): ?Section => $plan[1](), $plans)));

        return apply_filters('bf_html_sitemap_sections', $sections, $this->options);
    }

    /**
     * @return array<string, PostTypeConfig>
     */
    private function postTypeConfigs(?array $names): array
    {
        return $names === null
            ? $this->options->enabledPostTypes()
            : array_intersect_key($this->options->postTypes, array_flip($names));
    }

    /**
     * @return array<string, TaxonomyConfig>
     */
    private function taxonomyConfigs(?array $names): array
    {
        return $names === null
            ? $this->options->enabledTaxonomies()
            : array_intersect_key($this->options->taxonomies, array_flip($names));
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private function translateIds(array $ids): array
    {
        foreach ($ids as $id) {
            $translated = $this->language->translatePostId($id);
            if ($translated !== null) {
                $ids[] = $translated;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * @param list<int> $excludeIds
     */
    private function postTypeSection(PostTypeConfig $config, array $excludeIds, int $currentId): ?Section
    {
        if (!post_type_exists($config->name) || $this->seo->isPostTypeNoindexed($config->name)) {
            return null;
        }

        $args = apply_filters('bf_html_sitemap_post_query_args', [
            'post_type' => $config->name,
            'post_status' => 'publish',
            'has_password' => false,
            'posts_per_page' => $config->limit > 0 ? $config->limit : -1,
            'orderby' => [$config->orderBy => $config->order, 'title' => 'ASC'],
            'post__not_in' => array_values(array_filter([...$excludeIds, $currentId])),
            'meta_query' => Exclusion::notFlaggedMetaQuery(),
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'update_post_term_cache' => false,
            ...$this->language->postQueryArgs(),
        ], $config);

        $items = [];
        $skipped = [];

        /** @var WP_Post $post */
        foreach ((new WP_Query($args))->posts as $post) {
            if ($this->seo->isPostNoindexed($post->ID, $post->post_type) || apply_filters('bf_html_sitemap_exclude_post', false, $post)) {
                $skipped[] = $post->ID;
                continue;
            }

            $items[] = new Item(
                $post->ID,
                $config->hierarchical ? (int) $post->post_parent : 0,
                $this->title(get_the_title($post)),
                (string) get_permalink($post),
            );
        }

        if ($config->hierarchical && $this->options->excludeDescendants) {
            $items = Tree::withoutDescendantsOf($items, [...$excludeIds, ...$skipped, ...$this->flaggedPostIds($config->name)]);
        }

        return $this->section(Section::TYPE_POST_TYPE, $config->name, $config->heading, $config->displayHeading(), $items);
    }

    /**
     * @return list<int>
     */
    private function flaggedPostIds(string $postType): array
    {
        return array_map('intval', (new WP_Query([
            'post_type' => $postType,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => Exclusion::flaggedMetaQuery(),
            'no_found_rows' => true,
            ...$this->language->postQueryArgs(),
        ]))->posts);
    }

    private function taxonomySection(TaxonomyConfig $config): ?Section
    {
        if (!taxonomy_exists($config->name) || $this->seo->isTaxonomyNoindexed($config->name)) {
            return null;
        }

        $terms = get_terms(apply_filters('bf_html_sitemap_term_query_args', [
            'taxonomy' => $config->name,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
            'meta_query' => Exclusion::notFlaggedMetaQuery(),
            'update_term_meta_cache' => true,
            ...$this->language->termQueryArgs(),
        ], $config));

        if (!is_array($terms)) {
            return null;
        }

        $items = [];
        $skipped = [];

        /** @var WP_Term $term */
        foreach ($terms as $term) {
            $url = get_term_link($term);

            if (
                is_wp_error($url)
                || $this->seo->isTermNoindexed($term->term_id, $term->taxonomy)
                || apply_filters('bf_html_sitemap_exclude_term', false, $term)
            ) {
                $skipped[] = $term->term_id;
                continue;
            }

            $items[] = new Item(
                $term->term_id,
                $config->hierarchical ? (int) $term->parent : 0,
                $this->title($term->name),
                $url,
            );
        }

        if ($config->hierarchical && $this->options->excludeDescendants) {
            $items = Tree::withoutDescendantsOf($items, [...$skipped, ...$this->flaggedTermIds($config->name)]);
        }

        return $this->section(Section::TYPE_TAXONOMY, $config->name, $config->heading, $config->displayHeading(), $items);
    }

    /**
     * @return list<int>
     */
    private function flaggedTermIds(string $taxonomy): array
    {
        $ids = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'fields' => 'ids',
            'meta_query' => Exclusion::flaggedMetaQuery(),
            ...$this->language->termQueryArgs(),
        ]);

        return is_array($ids) ? array_map('intval', $ids) : [];
    }

    /**
     * @param list<Item> $items
     */
    private function section(string $type, string $name, string $customHeading, string $heading, array $items): ?Section
    {
        if ($items === []) {
            return null;
        }

        if ($customHeading !== '') {
            $heading = $this->language->translateString(self::headingStringName($type, $name), $customHeading);
        }

        return new Section($type, $name, $heading, Tree::build($items));
    }

    public static function headingStringName(string $type, string $name): string
    {
        return "heading_{$type}_{$name}";
    }

    private function title(string $title): string
    {
        $title = trim(wp_strip_all_tags($title));

        return $title !== '' ? $title : __('(no title)', 'bf-html-sitemap');
    }
}
