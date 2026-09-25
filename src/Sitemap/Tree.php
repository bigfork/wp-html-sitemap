<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Sitemap;

defined('ABSPATH') || exit;

final class Tree
{
    private const int ROOT = 0;

    /**
     * Nest items by parent ID, preserving order. Items whose parent is not
     * present (excluded, unpublished, noindexed) are promoted to the root.
     *
     * @param list<Item> $items
     * @return list<Item>
     */
    public static function build(array $items): array
    {
        $present = [];
        foreach ($items as $item) {
            $present[$item->id] = true;
        }

        $byParent = [];
        foreach ($items as $item) {
            $parent = $item->parentId !== $item->id && isset($present[$item->parentId])
                ? $item->parentId
                : self::ROOT;
            $byParent[$parent][] = $item;
        }

        $visited = [];

        return self::attach(self::ROOT, $byParent, $visited);
    }

    /**
     * Remove items that sit anywhere beneath an excluded ID.
     *
     * @param list<Item> $items
     * @param list<int> $excludedIds
     * @return list<Item>
     */
    public static function withoutDescendantsOf(array $items, array $excludedIds): array
    {
        $excluded = array_fill_keys($excludedIds, true);

        do {
            $count = count($excluded);

            foreach ($items as $item) {
                if ($item->parentId !== $item->id && isset($excluded[$item->parentId])) {
                    $excluded[$item->id] = true;
                }
            }
        } while (count($excluded) !== $count);

        return array_values(array_filter($items, fn (Item $item): bool => !isset($excluded[$item->id])));
    }

    /**
     * @param array<int, list<Item>> $byParent
     * @param array<int, true> $visited
     * @return list<Item>
     */
    private static function attach(int $parentId, array $byParent, array &$visited): array
    {
        $nodes = [];

        foreach ($byParent[$parentId] ?? [] as $item) {
            if (isset($visited[$item->id])) {
                continue;
            }

            $visited[$item->id] = true;
            $nodes[] = $item->withChildren(self::attach($item->id, $byParent, $visited));
        }

        return $nodes;
    }
}
