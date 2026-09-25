<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Tests\Unit;

use Bigfork\HtmlSitemap\Seo\SlimSeoProvider;

final class SlimSeoProviderTest extends TestCase
{
    private SlimSeoProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new SlimSeoProvider();
        $this->stubOptions(['slim_seo' => [
            'product' => ['noindex' => 1],
            'post_tag' => ['noindex' => 1],
            'page' => ['noindex' => 0],
        ]]);
        $this->stubPostMeta([
            10 => ['slim_seo' => ['noindex' => 1]],
            11 => ['slim_seo' => ['noindex' => 0, 'title' => 'x']],
        ]);
        $this->stubTermMeta([
            20 => ['slim_seo' => ['noindex' => 1]],
        ]);
    }

    public function testPostTypeNoindex(): void
    {
        self::assertTrue($this->provider->isPostTypeNoindexed('product'));
        self::assertFalse($this->provider->isPostTypeNoindexed('page'));
        self::assertFalse($this->provider->isPostTypeNoindexed('post'));
    }

    public function testPostNoindexFromMeta(): void
    {
        self::assertTrue($this->provider->isPostNoindexed(10, 'page'));
        self::assertFalse($this->provider->isPostNoindexed(11, 'page'));
        self::assertFalse($this->provider->isPostNoindexed(12, 'page'));
    }

    public function testPostNoindexInheritsFromPostType(): void
    {
        self::assertTrue($this->provider->isPostNoindexed(11, 'product'));
    }

    public function testTermNoindex(): void
    {
        self::assertTrue($this->provider->isTermNoindexed(20, 'category'));
        self::assertFalse($this->provider->isTermNoindexed(21, 'category'));
        self::assertTrue($this->provider->isTermNoindexed(21, 'post_tag'));
    }

    public function testHandlesMissingOption(): void
    {
        $this->stubOptions([]);

        self::assertFalse($this->provider->isPostTypeNoindexed('product'));
    }
}
