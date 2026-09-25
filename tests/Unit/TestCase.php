<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Tests\Unit;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function stubOptions(array $options): void
    {
        Monkey\Functions\when('get_option')->alias(fn (string $name, mixed $default = false): mixed => $options[$name] ?? $default);
    }

    /**
     * @param array<int, array<string, mixed>> $meta
     */
    protected function stubPostMeta(array $meta): void
    {
        Monkey\Functions\when('get_post_meta')->alias(fn (int $id, string $key, bool $single = false): mixed => $meta[$id][$key] ?? '');
    }

    /**
     * @param array<int, array<string, mixed>> $meta
     */
    protected function stubTermMeta(array $meta): void
    {
        Monkey\Functions\when('get_term_meta')->alias(fn (int $id, string $key, bool $single = false): mixed => $meta[$id][$key] ?? '');
    }
}
