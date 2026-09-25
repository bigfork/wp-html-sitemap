<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Language;

defined('ABSPATH') || exit;

final class PolylangProvider implements LanguageProvider
{
    private const string GROUP = 'HTML Sitemap';

    public function id(): string
    {
        return 'polylang';
    }

    public function isAvailable(): bool
    {
        return function_exists('pll_current_language');
    }

    public function currentLanguage(): string
    {
        return (string) (pll_current_language() ?: pll_default_language());
    }

    public function postQueryArgs(): array
    {
        return ['lang' => $this->currentLanguage()];
    }

    public function termQueryArgs(): array
    {
        return ['lang' => $this->currentLanguage()];
    }

    public function translatePostId(int $postId): ?int
    {
        $translated = pll_get_post($postId, $this->currentLanguage());

        return $translated ? (int) $translated : null;
    }

    public function registerString(string $name, string $value): void
    {
        pll_register_string($name, $value, self::GROUP);
    }

    public function translateString(string $name, string $value): string
    {
        return pll__($value);
    }
}
