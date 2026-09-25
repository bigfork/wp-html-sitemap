<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap;

use Bigfork\HtmlSitemap\Admin\ListTable;
use Bigfork\HtmlSitemap\Admin\PostMetaBox;
use Bigfork\HtmlSitemap\Admin\SettingsPage;
use Bigfork\HtmlSitemap\Admin\TermField;
use Bigfork\HtmlSitemap\Cli\Command;
use Bigfork\HtmlSitemap\Language\LanguageRegistry;
use Bigfork\HtmlSitemap\Seo\ProviderRegistry;
use Bigfork\HtmlSitemap\Sitemap\Builder;
use Bigfork\HtmlSitemap\Sitemap\Renderer;
use WP_CLI;

defined('ABSPATH') || exit;

final class Plugin
{
    public const string TEXT_DOMAIN = 'bf-html-sitemap';

    private static ?self $instance = null;

    private ?Options $options = null;

    private function __construct(
        public readonly string $file,
        public readonly ProviderRegistry $seo,
        public readonly LanguageRegistry $languages,
        public readonly Cache $cache,
        public readonly SitemapPages $pages,
    ) {
    }

    public static function boot(string $file): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$instance = new self($file, new ProviderRegistry(), new LanguageRegistry(), new Cache(), new SitemapPages());
        self::$instance->register();

        return self::$instance;
    }

    public static function instance(): self
    {
        return self::$instance ?? throw new \LogicException('HTML Sitemap has not been booted.');
    }

    public function options(): Options
    {
        return $this->options ??= Options::load($this->seo->provider());
    }

    public function refreshOptions(): void
    {
        $this->options = null;
    }

    public function builder(): Builder
    {
        return new Builder($this->options(), $this->seo->provider(), $this->languages->provider());
    }

    public function renderer(): Renderer
    {
        return new Renderer(dirname($this->file) . '/templates');
    }

    public function path(string $relative = ''): string
    {
        return dirname($this->file) . '/' . ltrim($relative, '/');
    }

    private function register(): void
    {
        add_action('init', $this->loadTextDomain(...));
        add_action('init', fn () => (new Shortcode($this))->register());
        add_action('init', fn () => (new Translations($this))->register(), 20);

        $this->cache->register($this->seo);
        $this->pages->register();
        (new PageCache($this->pages))->register();

        if (is_admin()) {
            add_action('init', function (): void {
                (new SettingsPage($this))->register();
                (new PostMetaBox($this))->register();
                (new TermField($this))->register();
                (new ListTable($this))->register();
            }, 99);
        }

        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('html-sitemap', new Command($this));
        }
    }

    private function loadTextDomain(): void
    {
        load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname(plugin_basename($this->file)) . '/languages');
    }
}
