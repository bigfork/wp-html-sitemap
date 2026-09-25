<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Tests\Unit;

use Bigfork\HtmlSitemap\Seo\YoastProvider;

final class YoastProviderTest extends TestCase
{
    private YoastProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new YoastProvider();
        $this->stubOptions([
            'wpseo_titles' => ['noindex-product' => true, 'noindex-page' => false, 'noindex-tax-post_tag' => true],
            'wpseo_taxonomy_meta' => [
                'category' => [20 => ['wpseo_noindex' => 'noindex']],
                'post_tag' => [21 => ['wpseo_noindex' => 'index']],
            ],
        ]);
        $this->stubPostMeta([
            10 => ['_yoast_wpseo_meta-robots-noindex' => '1'],
            11 => ['_yoast_wpseo_meta-robots-noindex' => '2'],
        ]);
    }

    public function testExplicitPostValuesWin(): void
    {
        self::assertTrue($this->provider->isPostNoindexed(10, 'page'));
        self::assertFalse($this->provider->isPostNoindexed(11, 'product'));
    }

    public function testDefaultFallsBackToPostType(): void
    {
        self::assertTrue($this->provider->isPostNoindexed(12, 'product'));
        self::assertFalse($this->provider->isPostNoindexed(12, 'page'));
    }

    public function testTerms(): void
    {
        self::assertTrue($this->provider->isTermNoindexed(20, 'category'));
        self::assertFalse($this->provider->isTermNoindexed(21, 'post_tag'));
        self::assertTrue($this->provider->isTermNoindexed(22, 'post_tag'));
        self::assertFalse($this->provider->isTermNoindexed(23, 'category'));
    }
}
