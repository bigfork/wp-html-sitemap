<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Language;

defined('ABSPATH') || exit;

interface LanguageProvider
{
    public function id(): string;

    public function isAvailable(): bool;

    public function currentLanguage(): string;

    /**
     * Extra WP_Query arguments to scope results to the current language.
     */
    public function postQueryArgs(): array;

    /**
     * Extra get_terms() arguments to scope results to the current language.
     */
    public function termQueryArgs(): array;

    /**
     * Map a post ID to its translation in the current language, if any.
     */
    public function translatePostId(int $postId): ?int;

    public function registerString(string $name, string $value): void;

    public function translateString(string $name, string $value): string;
}
