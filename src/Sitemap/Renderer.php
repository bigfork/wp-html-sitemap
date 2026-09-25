<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Sitemap;

defined('ABSPATH') || exit;

final readonly class Renderer
{
    private const string THEME_DIRECTORY = 'html-sitemap';

    public function __construct(private string $templateDirectory)
    {
    }

    /**
     * @param list<Section> $sections
     */
    public function sitemap(array $sections, int $headingLevel = 2, bool $schema = false): string
    {
        if ($sections === []) {
            return '';
        }

        $html = $this->render('sitemap', [
            'sections' => $sections,
            'headingLevel' => min(6, max(1, $headingLevel)),
        ]);

        return $schema ? $html . $this->schema($sections) : $html;
    }

    /**
     * @param list<Section> $sections
     */
    public function schema(array $sections): string
    {
        $elements = [];

        foreach ($sections as $section) {
            foreach (self::flatten($section->items) as $item) {
                $elements[] = [
                    '@type' => 'SiteNavigationElement',
                    'position' => count($elements) + 1,
                    'name' => html_entity_decode($item->title, ENT_QUOTES, 'UTF-8'),
                    'url' => $item->url,
                ];
            }
        }

        $data = apply_filters('bf_html_sitemap_schema', [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $elements,
        ], $sections);

        return sprintf(
            '<script type="application/ld+json">%s</script>',
            wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP),
        );
    }

    /**
     * @param list<Item> $items
     * @return \Generator<Item>
     */
    private static function flatten(array $items): \Generator
    {
        foreach ($items as $item) {
            yield $item;
            yield from self::flatten($item->children);
        }
    }

    /**
     * @param list<Item> $items
     */
    public function list(array $items, int $depth = 0): string
    {
        return $items === [] ? '' : $this->render('list', ['items' => $items, 'depth' => $depth]);
    }

    public function render(string $template, array $vars = []): string
    {
        $file = locate_template(self::THEME_DIRECTORY . "/{$template}.php")
            ?: "{$this->templateDirectory}/{$template}.php";

        $file = (string) apply_filters('bf_html_sitemap_template', $file, $template);

        $renderer = $this;
        extract($vars, EXTR_SKIP);

        ob_start();
        include $file;

        return (string) ob_get_clean();
    }
}
