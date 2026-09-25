<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Tests\Unit;

use Bigfork\HtmlSitemap\Options;
use Bigfork\HtmlSitemap\Sitemap\PostTypeConfig;
use Brain\Monkey\Functions;

final class ConfigTest extends TestCase
{
    public function testParseIdsFromString(): void
    {
        Functions\when('absint')->alias(fn (mixed $value): int => abs((int) $value));

        self::assertSame([12, 4, 9], Options::parseIds('12, 4,,9 12 abc -0'));
        self::assertSame([3], Options::parseIds(['3', 3, 0]));
        self::assertSame([], Options::parseIds(''));
    }

    public function testPostTypeConfigDefaultsByHierarchy(): void
    {
        $page = PostTypeConfig::fromArray('page', [], 'Pages', true);
        $post = PostTypeConfig::fromArray('post', [], 'Posts', false);

        self::assertSame(['menu_order', 'ASC'], [$page->orderBy, $page->order]);
        self::assertSame(['date', 'DESC'], [$post->orderBy, $post->order]);
        self::assertFalse($page->enabled);
    }

    public function testPostTypeConfigRejectsInvalidValues(): void
    {
        $config = PostTypeConfig::fromArray('post', [
            'enabled' => '1',
            'order_by' => 'rand; DROP TABLE',
            'order' => 'sideways',
            'limit' => '-5',
            'heading' => '  News  ',
        ], 'Posts', false);

        self::assertTrue($config->enabled);
        self::assertSame('date', $config->orderBy);
        self::assertSame('DESC', $config->order);
        self::assertSame(0, $config->limit);
        self::assertSame('News', $config->displayHeading());
    }

    public function testPositionFallsBackToDefault(): void
    {
        self::assertSame(30, PostTypeConfig::fromArray('post', [], 'Posts', false, 30)->position);
        self::assertSame(5, PostTypeConfig::fromArray('post', ['position' => '5'], 'Posts', false, 30)->position);
    }

    public function testHeadingFallsBackToLabel(): void
    {
        self::assertSame('Posts', PostTypeConfig::fromArray('post', [], 'Posts', false)->displayHeading());
    }
}
