<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap;

use Bigfork\HtmlSitemap\Seo\NoindexProvider;
use Bigfork\HtmlSitemap\Seo\ProviderRegistry;
use Bigfork\HtmlSitemap\Sitemap\PostTypeConfig;
use Bigfork\HtmlSitemap\Sitemap\TaxonomyConfig;

defined('ABSPATH') || exit;

final readonly class Options
{
    public const string OPTION = 'bf_html_sitemap';

    private const array EXCLUDED_POST_TYPES = ['attachment'];
    private const array EXCLUDED_TAXONOMIES = ['post_format'];
    private const int POSITION_STEP = 10;
    private const int TAXONOMY_POSITION_OFFSET = 500;

    /**
     * @param array<string, PostTypeConfig> $postTypes
     * @param array<string, TaxonomyConfig> $taxonomies
     * @param list<int> $excludeIds
     */
    public function __construct(
        public array $postTypes,
        public array $taxonomies,
        public array $excludeIds,
        public bool $excludeDescendants,
        public bool $schema,
        public string $seoProvider,
    ) {
    }

    public static function raw(): array
    {
        $raw = get_option(self::OPTION);

        return is_array($raw) ? $raw : [];
    }

    public static function load(NoindexProvider $seo): self
    {
        $raw = self::raw();

        $postTypes = [];
        $position = 0;
        foreach (self::availablePostTypes() as $name => $object) {
            $stored = $raw['post_types'][$name] ?? null;
            $postTypes[$name] = PostTypeConfig::fromArray(
                $name,
                is_array($stored) ? $stored : ['enabled' => !isset($raw['post_types']) && !$seo->isPostTypeNoindexed($name)],
                (string) $object->labels->name,
                is_post_type_hierarchical($name),
                $position += self::POSITION_STEP,
            );
        }

        $taxonomies = [];
        $position = self::TAXONOMY_POSITION_OFFSET;
        foreach (self::availableTaxonomies() as $name => $object) {
            $stored = $raw['taxonomies'][$name] ?? null;
            $taxonomies[$name] = TaxonomyConfig::fromArray(
                $name,
                is_array($stored) ? $stored : ['enabled' => !isset($raw['taxonomies']) && !$seo->isTaxonomyNoindexed($name)],
                (string) $object->labels->name,
                is_taxonomy_hierarchical($name),
                $position += self::POSITION_STEP,
            );
        }

        return new self(
            $postTypes,
            $taxonomies,
            self::parseIds($raw['exclude_ids'] ?? []),
            (bool) ($raw['exclude_descendants'] ?? true),
            (bool) ($raw['schema'] ?? false),
            (string) ($raw['seo_provider'] ?? ProviderRegistry::AUTO),
        );
    }

    /**
     * @param list<string> $validProviders
     */
    public static function sanitize(mixed $input, array $validProviders): array
    {
        $input = is_array($input) ? $input : [];
        $row = static function (mixed $data): array {
            $data = is_array($data) ? $data : [];
            $data['heading'] = sanitize_text_field((string) ($data['heading'] ?? ''));

            return $data;
        };

        $postTypes = [];
        foreach (self::availablePostTypes() as $name => $object) {
            $postTypes[$name] = PostTypeConfig::fromArray(
                $name,
                $row($input['post_types'][$name] ?? []),
                (string) $object->labels->name,
                is_post_type_hierarchical($name),
            )->toArray();
        }

        $taxonomies = [];
        foreach (self::availableTaxonomies() as $name => $object) {
            $taxonomies[$name] = TaxonomyConfig::fromArray(
                $name,
                $row($input['taxonomies'][$name] ?? []),
                (string) $object->labels->name,
                is_taxonomy_hierarchical($name),
            )->toArray();
        }

        $provider = sanitize_key((string) ($input['seo_provider'] ?? ProviderRegistry::AUTO));

        return [
            'post_types' => $postTypes,
            'taxonomies' => $taxonomies,
            'exclude_ids' => self::parseIds($input['exclude_ids'] ?? []),
            'exclude_descendants' => !empty($input['exclude_descendants']),
            'schema' => !empty($input['schema']),
            'seo_provider' => in_array($provider, $validProviders, true) ? $provider : ProviderRegistry::AUTO,
        ];
    }

    /**
     * @return array<string, \WP_Post_Type>
     */
    public static function availablePostTypes(): array
    {
        $postTypes = array_filter(
            array_diff_key(get_post_types(['public' => true], 'objects'), array_flip(self::EXCLUDED_POST_TYPES)),
            fn (\WP_Post_Type $postType): bool => is_post_type_viewable($postType),
        );

        return array_filter(
            (array) apply_filters('bf_html_sitemap_available_post_types', $postTypes),
            fn (mixed $postType): bool => $postType instanceof \WP_Post_Type,
        );
    }

    /**
     * @return array<string, \WP_Taxonomy>
     */
    public static function availableTaxonomies(): array
    {
        $taxonomies = array_filter(
            array_diff_key(get_taxonomies(['public' => true], 'objects'), array_flip(self::EXCLUDED_TAXONOMIES)),
            fn (\WP_Taxonomy $taxonomy): bool => is_taxonomy_viewable($taxonomy),
        );

        return array_filter(
            (array) apply_filters('bf_html_sitemap_available_taxonomies', $taxonomies),
            fn (mixed $taxonomy): bool => $taxonomy instanceof \WP_Taxonomy,
        );
    }

    /**
     * @return list<int>
     */
    public static function parseIds(mixed $ids): array
    {
        $ids = is_array($ids) ? $ids : preg_split('/[\s,]+/', (string) $ids);

        return array_values(array_unique(array_filter(array_map('absint', $ids))));
    }

    /**
     * @return array<string, PostTypeConfig>
     */
    public function enabledPostTypes(): array
    {
        return array_filter($this->postTypes, fn (PostTypeConfig $config): bool => $config->enabled);
    }

    /**
     * @return array<string, TaxonomyConfig>
     */
    public function enabledTaxonomies(): array
    {
        return array_filter($this->taxonomies, fn (TaxonomyConfig $config): bool => $config->enabled);
    }
}
