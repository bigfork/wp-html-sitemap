<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Sitemap;

defined('ABSPATH') || exit;

final readonly class PostTypeConfig
{
    public const array ORDER_BY = ['menu_order', 'title', 'date', 'modified'];
    public const array ORDER = ['ASC', 'DESC'];

    public function __construct(
        public string $name,
        public bool $enabled,
        public string $label,
        public string $heading,
        public string $orderBy,
        public string $order,
        public int $limit,
        public bool $hierarchical,
        public int $position,
    ) {
    }

    public static function fromArray(string $name, array $data, string $label, bool $hierarchical, int $defaultPosition = 0): self
    {
        $orderBy = (string) ($data['order_by'] ?? ($hierarchical ? 'menu_order' : 'date'));
        $order = strtoupper((string) ($data['order'] ?? ($hierarchical ? 'ASC' : 'DESC')));

        return new self(
            name: $name,
            enabled: !empty($data['enabled']),
            label: $label,
            heading: trim((string) ($data['heading'] ?? '')),
            orderBy: in_array($orderBy, self::ORDER_BY, true) ? $orderBy : 'date',
            order: in_array($order, self::ORDER, true) ? $order : 'DESC',
            limit: max(0, (int) ($data['limit'] ?? 0)),
            hierarchical: $hierarchical,
            position: (int) ($data['position'] ?? $defaultPosition),
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'heading' => $this->heading,
            'order_by' => $this->orderBy,
            'order' => $this->order,
            'limit' => $this->limit,
            'position' => $this->position,
        ];
    }

    public function displayHeading(): string
    {
        return $this->heading !== '' ? $this->heading : $this->label;
    }
}
