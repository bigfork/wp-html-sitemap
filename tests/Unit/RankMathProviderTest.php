<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Tests\Unit;

use Bigfork\HtmlSitemap\Seo\RankMathProvider;

final class RankMathProviderTest extends TestCase
{
    private RankMathProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new RankMathProvider();
        $this->stubOptions(['rank-math-options-titles' => [
            'robots_global' => ['index'],
            'pt_product_custom_robots' => 'on',
            'pt_product_robots' => ['noindex'],
            'pt_page_custom_robots' => 'off',
            'pt_page_robots' => ['noindex'],
            'tax_post_tag_custom_robots' => 'on',
            'tax_post_tag_robots' => ['noindex', 'nofollow'],
        ]]);
        $this->stubPostMeta([
            10 => ['rank_math_robots' => ['noindex']],
            11 => ['rank_math_robots' => ['index']],
        ]);
        $this->stubTermMeta([
            20 => ['rank_math_robots' => ['noindex']],
        ]);
    }

    public function testPostMetaWins(): void
    {
        self::assertTrue($this->provider->isPostNoindexed(10, 'page'));
        self::assertFalse($this->provider->isPostNoindexed(11, 'product'));
    }

    public function testCustomPostTypeRobotsOnlyApplyWhenEnabled(): void
    {
        self::assertTrue($this->provider->isPostNoindexed(12, 'product'));
        self::assertFalse($this->provider->isPostNoindexed(12, 'page'));
    }

    public function testGlobalRobotsFallback(): void
    {
        $this->stubOptions(['rank-math-options-titles' => ['robots_global' => ['noindex']]]);

        self::assertTrue($this->provider->isPostTypeNoindexed('post'));
    }

    public function testTerms(): void
    {
        self::assertTrue($this->provider->isTermNoindexed(20, 'category'));
        self::assertTrue($this->provider->isTermNoindexed(21, 'post_tag'));
        self::assertFalse($this->provider->isTermNoindexed(21, 'category'));
    }
}
