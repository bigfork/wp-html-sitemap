<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap;

defined('ABSPATH') || exit;

final readonly class Shortcode
{
    public const string TAG = 'html_sitemap';

    private const array DEFAULTS = [
        'types' => null,
        'taxonomies' => null,
        'exclude' => '',
        'heading_level' => 2,
    ];

    public function __construct(private Plugin $plugin)
    {
    }

    public function register(): void
    {
        add_shortcode(self::TAG, $this->render(...));
    }

    public function render(array|string $atts = []): string
    {
        $atts = shortcode_atts(self::DEFAULTS, is_array($atts) ? $atts : [], self::TAG);

        $postTypes = self::list($atts['types']);
        $taxonomies = self::list($atts['taxonomies']);

        if ($postTypes !== null || $taxonomies !== null) {
            $postTypes ??= [];
            $taxonomies ??= [];
        }

        $excludeIds = Options::parseIds($atts['exclude']);
        $headingLevel = (int) $atts['heading_level'];

        $context = [
            'language' => $this->plugin->languages->provider()->currentLanguage(),
            'queried' => is_singular() ? get_queried_object_id() : 0,
            'types' => $postTypes,
            'taxonomies' => $taxonomies,
            'exclude' => $excludeIds,
            'heading_level' => $headingLevel,
        ];

        return $this->plugin->cache->remember($context, fn (): string => $this->plugin->renderer()->sitemap(
            $this->plugin->builder()->build($postTypes, $taxonomies, $excludeIds),
            $headingLevel,
            $this->plugin->options()->schema,
        ));
    }

    /**
     * @return list<string>|null
     */
    private static function list(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        return array_values(array_filter(array_map('sanitize_key', explode(',', (string) $value))));
    }
}
