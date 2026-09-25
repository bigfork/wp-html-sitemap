<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Tests\Unit;

use Bigfork\HtmlSitemap\Sitemap\Item;
use Bigfork\HtmlSitemap\Sitemap\Tree;
use PHPUnit\Framework\TestCase;

final class TreeTest extends TestCase
{
    public function testNestsChildrenUnderParentsPreservingOrder(): void
    {
        $tree = Tree::build([
            $this->item(1, 0),
            $this->item(2, 1),
            $this->item(3, 0),
            $this->item(4, 1),
            $this->item(5, 2),
        ]);

        self::assertSame([1, 3], $this->ids($tree));
        self::assertSame([2, 4], $this->ids($tree[0]->children));
        self::assertSame([5], $this->ids($tree[0]->children[0]->children));
        self::assertSame([], $tree[1]->children);
    }

    public function testPromotesOrphansToRoot(): void
    {
        $tree = Tree::build([
            $this->item(2, 99),
            $this->item(3, 2),
        ]);

        self::assertSame([2], $this->ids($tree));
        self::assertSame([3], $this->ids($tree[0]->children));
    }

    public function testSelfParentedItemIsTreatedAsRoot(): void
    {
        self::assertSame([7], $this->ids(Tree::build([$this->item(7, 7)])));
    }

    public function testCyclesDoNotRecurseForever(): void
    {
        self::assertSame([], Tree::build([
            $this->item(1, 2),
            $this->item(2, 1),
        ]));
    }

    public function testRemovesAllDescendantsOfExcludedIds(): void
    {
        $items = Tree::withoutDescendantsOf([
            $this->item(1, 0),
            $this->item(4, 3),
            $this->item(3, 2),
            $this->item(5, 1),
            $this->item(6, 0),
        ], [2]);

        self::assertSame([1, 5, 6], $this->ids($items));
    }

    public function testDescendantPruningIgnoresSelfParents(): void
    {
        self::assertSame([7], $this->ids(Tree::withoutDescendantsOf([$this->item(7, 7)], [])));
    }

    public function testEmpty(): void
    {
        self::assertSame([], Tree::build([]));
    }

    private function item(int $id, int $parent): Item
    {
        return new Item($id, $parent, "Item {$id}", "https://example.test/{$id}");
    }

    /**
     * @param list<Item> $items
     * @return list<int>
     */
    private function ids(array $items): array
    {
        return array_map(fn (Item $item): int => $item->id, $items);
    }
}
