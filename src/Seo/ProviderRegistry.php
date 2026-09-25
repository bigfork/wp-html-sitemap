<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Seo;

use Bigfork\HtmlSitemap\Options;

defined('ABSPATH') || exit;

final class ProviderRegistry
{
    public const string AUTO = 'auto';

    /** @var array<string, NoindexProvider>|null */
    private ?array $providers = null;

    private ?NoindexProvider $resolved = null;

    /**
     * @return array<string, NoindexProvider>
     */
    public function all(): array
    {
        if ($this->providers !== null) {
            return $this->providers;
        }

        $providers = apply_filters('bf_html_sitemap_noindex_providers', [
            new SlimSeoProvider(),
            new YoastProvider(),
            new RankMathProvider(),
        ]);

        $this->providers = [];

        foreach ($providers as $provider) {
            if ($provider instanceof NoindexProvider) {
                $this->providers[$provider->id()] = $provider;
            }
        }

        return $this->providers;
    }

    public function detected(): NoindexProvider
    {
        foreach ($this->all() as $provider) {
            if ($provider->isAvailable()) {
                return $provider;
            }
        }

        return new NullProvider();
    }

    public function provider(): NoindexProvider
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $choice = Options::raw()['seo_provider'] ?? self::AUTO;

        $provider = match (true) {
            $choice === NullProvider::ID => new NullProvider(),
            isset($this->all()[$choice]) && $this->all()[$choice]->isAvailable() => $this->all()[$choice],
            default => $this->detected(),
        };

        return $this->resolved = apply_filters('bf_html_sitemap_noindex_provider', $provider);
    }

    public function reset(): void
    {
        $this->resolved = null;
    }
}
