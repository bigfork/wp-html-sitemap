<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Sitemap;

defined('ABSPATH') || exit;

final readonly class TaxonomyConfig
{
    public function __construct(
        public string $name,
        public bool $enabled,
        public string $label,
        public string $heading,
        public bool $hierarchical,
        public int $position,
    ) {
    }

    public static function fromArray(string $name, array $data, string $label, bool $hierarchical, int $defaultPosition = 0): self
    {
        return new self(
            name: $name,
            enabled: !empty($data['enabled']),
            label: $label,
            heading: trim((string) ($data['heading'] ?? '')),
            hierarchical: $hierarchical,
            position: (int) ($data['position'] ?? $defaultPosition),
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'heading' => $this->heading,
            'position' => $this->position,
        ];
    }

    public function displayHeading(): string
    {
        return $this->heading !== '' ? $this->heading : $this->label;
    }
}
