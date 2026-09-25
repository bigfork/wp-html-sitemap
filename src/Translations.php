<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap;

use Bigfork\HtmlSitemap\Sitemap\Builder;
use Bigfork\HtmlSitemap\Sitemap\Section;

defined('ABSPATH') || exit;

final readonly class Translations
{
    public function __construct(private Plugin $plugin)
    {
    }

    public function register(): void
    {
        add_filter('pll_copy_post_metas', $this->copyMeta(...));
        add_filter('pll_copy_term_metas', $this->copyMeta(...));

        add_action('admin_init', $this->registerHeadings(...));
    }

    /**
     * @param list<string> $keys
     * @return list<string>
     */
    private function copyMeta(array $keys): array
    {
        return [...$keys, Exclusion::META_KEY];
    }

    private function registerHeadings(): void
    {
        $language = $this->plugin->languages->provider();
        $options = $this->plugin->options();

        foreach ($options->postTypes as $config) {
            if ($config->heading !== '') {
                $language->registerString(Builder::headingStringName(Section::TYPE_POST_TYPE, $config->name), $config->heading);
            }
        }

        foreach ($options->taxonomies as $config) {
            if ($config->heading !== '') {
                $language->registerString(Builder::headingStringName(Section::TYPE_TAXONOMY, $config->name), $config->heading);
            }
        }
    }
}
