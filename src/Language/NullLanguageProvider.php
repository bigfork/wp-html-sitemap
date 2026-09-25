<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Language;

defined('ABSPATH') || exit;

final class NullLanguageProvider implements LanguageProvider
{
    public function id(): string
    {
        return 'none';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function currentLanguage(): string
    {
        return determine_locale();
    }

    public function postQueryArgs(): array
    {
        return [];
    }

    public function termQueryArgs(): array
    {
        return [];
    }

    public function translatePostId(int $postId): ?int
    {
        return $postId;
    }

    public function registerString(string $name, string $value): void
    {
    }

    public function translateString(string $name, string $value): string
    {
        return $value;
    }
}
