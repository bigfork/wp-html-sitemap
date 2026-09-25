<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Language;

defined('ABSPATH') || exit;

final class WpmlProvider implements LanguageProvider
{
    private const string CONTEXT = 'HTML Sitemap';

    public function id(): string
    {
        return 'wpml';
    }

    public function isAvailable(): bool
    {
        return defined('ICL_SITEPRESS_VERSION');
    }

    public function currentLanguage(): string
    {
        return (string) apply_filters('wpml_current_language', null);
    }

    public function postQueryArgs(): array
    {
        return ['suppress_filters' => false];
    }

    public function termQueryArgs(): array
    {
        return [];
    }

    public function translatePostId(int $postId): ?int
    {
        $translated = apply_filters('wpml_object_id', $postId, get_post_type($postId) ?: 'any', false, $this->currentLanguage());

        return $translated ? (int) $translated : null;
    }

    public function registerString(string $name, string $value): void
    {
        do_action('wpml_register_single_string', self::CONTEXT, $name, $value);
    }

    public function translateString(string $name, string $value): string
    {
        return (string) apply_filters('wpml_translate_single_string', $value, self::CONTEXT, $name);
    }
}
