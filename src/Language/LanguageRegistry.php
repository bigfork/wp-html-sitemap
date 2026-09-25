<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Language;

defined('ABSPATH') || exit;

final class LanguageRegistry
{
    private ?LanguageProvider $resolved = null;

    public function provider(): LanguageProvider
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $providers = apply_filters('bf_html_sitemap_language_providers', [
            new WpmlProvider(),
            new PolylangProvider(),
        ]);

        foreach ($providers as $provider) {
            if ($provider instanceof LanguageProvider && $provider->isAvailable()) {
                return $this->resolved = $provider;
            }
        }

        return $this->resolved = new NullLanguageProvider();
    }
}
