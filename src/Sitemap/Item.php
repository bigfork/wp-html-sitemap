<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Sitemap;

defined('ABSPATH') || exit;

final readonly class Item
{
    /**
     * @param list<Item> $children
     */
    public function __construct(
        public int $id,
        public int $parentId,
        public string $title,
        public string $url,
        public array $children = [],
    ) {
    }

    /**
     * @param list<Item> $children
     */
    public function withChildren(array $children): self
    {
        return new self($this->id, $this->parentId, $this->title, $this->url, $children);
    }
}
