<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Sitemap;

defined('ABSPATH') || exit;

final readonly class Section
{
    public const string TYPE_POST_TYPE = 'post_type';
    public const string TYPE_TAXONOMY = 'taxonomy';

    /**
     * @param list<Item> $items
     */
    public function __construct(
        public string $type,
        public string $name,
        public string $heading,
        public array $items,
    ) {
    }
}
