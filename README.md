# HTML Sitemap

A configurable HTML sitemap for WordPress. Respects noindex settings from your SEO plugin without depending on it, and renders per language on WPML and Polylang sites.

## Installation

```bash
composer require bigfork/wp-html-sitemap
```

Activate **HTML Sitemap**, configure it under **Settings → HTML Sitemap**, then add the shortcode to a page:

```
[html_sitemap]
```

## Shortcode

| Attribute       | Example                     | Description                                                              |
|-----------------|-----------------------------|--------------------------------------------------------------------------|
| `types`         | `types="page,post"`         | Only these post types. Uses their settings even if disabled.             |
| `taxonomies`    | `taxonomies="category"`     | Only these taxonomies.                                                   |
| `exclude`       | `exclude="12,34"`           | Extra post IDs to leave out.                                             |
| `heading_level` | `heading_level="3"`         | Section heading level, `1`–`6`. Defaults to `2`.                         |

Passing either `types` or `taxonomies` switches to explicit mode: only what you list is shown, e.g. `[html_sitemap types="page"]` shows pages and no taxonomies.

## Sections

Each post type and taxonomy forms a section. Give each one a heading and a **Position** in settings; lower positions come first.

## What gets left out

An item is excluded when any of these apply:

- Its post type or taxonomy is not enabled (or is noindexed in the SEO plugin)
- It is noindexed in the SEO plugin
- **Exclude from HTML Sitemap** is ticked in its sidebar meta box, quick/bulk edit (posts) or edit screen (terms)
- Its ID is in **Excluded post IDs** in settings, or the shortcode `exclude` attribute
- It is not published, or is password protected
- It is the page the sitemap is displayed on
- It is an empty term

Children of an excluded page or term are hidden too. Untick **Also hide children of excluded pages and terms** to promote them a level instead.

The **Sitemap** column on post list screens shows whether each post is listed, and why not.

## SEO plugins

Only noindex detection touches the SEO plugin. Titles and URLs come straight from WordPress, so the sitemap keeps working if you switch or remove SEO plugins.

Built in: **Slim SEO**, **Yoast SEO**, **Rank Math**. Auto-detected, or pick one (or ignore them) in settings.

Add your own by implementing `Bigfork\HtmlSitemap\Seo\NoindexProvider`:

```php
add_filter('bf_html_sitemap_noindex_providers', fn (array $providers) => [...$providers, new MySeoProvider()]);
```

## Multilingual

WPML and Polylang are detected automatically:

- The sitemap renders in the current language, and is cached per language
- IDs in **Excluded post IDs** also exclude their translations
- Custom section headings are registered for string translation
- The exclude flag is copied between translations

## Post types and taxonomies

Any public post type or taxonomy with front-end URLs (`is_post_type_viewable()` / `is_taxonomy_viewable()`) is offered in settings. Hide ones that are only data models, for example a third-party plugin's CPT that is registered as public for no reason:

```php
add_filter('bf_html_sitemap_available_post_types', fn (array $types) => array_diff_key($types, ['team_member' => true]));
```

## Caching

Rendered output is cached in a transient (object cache when available) for a day, and invalidated whenever posts, terms, sitemap settings, permalinks or SEO settings change.

Pages containing the shortcode are purged from **WP Rocket**, **LiteSpeed Cache**, **W3 Total Cache** and **WP Super Cache** on invalidation. Hook other page caches via `bf_html_sitemap_purge_post`.

Those pages are found when posts are saved, by looking for `[html_sitemap` in post content and post meta, so shortcodes in ACF fields (including flexible content) are picked up. If the shortcode lives somewhere else, such as an ACF options page or a `do_shortcode()` call in a template, add the page IDs yourself:

```php
add_filter('bf_html_sitemap_pages', fn (array $ids) => [...$ids, 42]);
```

## Structured data

Tick **Output schema.org SiteNavigationElement JSON-LD** to append an `ItemList` of `SiteNavigationElement`s to the sitemap. It is kept separate from your SEO plugin's schema graph. Adjust it with `bf_html_sitemap_schema`.

## Templates

Copy `templates/sitemap.php` and/or `templates/list.php` to `your-theme/html-sitemap/` to override the markup. No CSS is included; style the BEM classes (`.html-sitemap`, `__section`, `__heading`, `__list`, `__item`, `__link`).

## Hooks

| Hook                                  | Type   | Purpose                                            |
|---------------------------------------|--------|----------------------------------------------------|
| `bf_html_sitemap_available_post_types`| filter | Post types offered in settings, keyed by name      |
| `bf_html_sitemap_available_taxonomies`| filter | Taxonomies offered in settings, keyed by name      |
| `bf_html_sitemap_post_query_args`     | filter | Modify the `WP_Query` args per post type           |
| `bf_html_sitemap_term_query_args`     | filter | Modify the `get_terms()` args per taxonomy         |
| `bf_html_sitemap_exclude_post`        | filter | Return `true` to exclude a `WP_Post`               |
| `bf_html_sitemap_exclude_term`        | filter | Return `true` to exclude a `WP_Term`               |
| `bf_html_sitemap_schema`              | filter | Modify the JSON-LD data                            |
| `bf_html_sitemap_sections`            | filter | Modify, reorder or add sections before rendering   |
| `bf_html_sitemap_template`            | filter | Change the template file path                      |
| `bf_html_sitemap_noindex_providers`   | filter | Register SEO providers                             |
| `bf_html_sitemap_noindex_provider`    | filter | Override the resolved SEO provider                 |
| `bf_html_sitemap_language_providers`  | filter | Register multilingual providers                    |
| `bf_html_sitemap_pages`               | filter | Page IDs to purge from page caches on invalidation |
| `bf_html_sitemap_cache_enabled`       | filter | Return `false` to disable caching                  |
| `bf_html_sitemap_cache_ttl`           | filter | Cache lifetime in seconds                          |
| `bf_html_sitemap_flushed`             | action | Fired after the cache is invalidated               |
| `bf_html_sitemap_purge_post`          | action | Purge a sitemap page from a custom page cache      |

## WP-CLI

```bash
wp html-sitemap status   # active providers and settings
wp html-sitemap render   # output markup, bypassing the cache
wp html-sitemap flush    # invalidate the cache
wp html-sitemap pages    # pages containing the shortcode (--rebuild to rescan)
```

## Uninstalling

Deleting the plugin removes its settings, cached output and every exclude flag on posts and terms (across all sites on multisite).

## Development

```bash
composer install
composer test
composer lint
```
