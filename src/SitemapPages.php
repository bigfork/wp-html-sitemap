<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap;

use WP_Post;

defined('ABSPATH') || exit;

/**
 * Tracks which posts contain the shortcode, in post_content or post meta
 * (which covers ACF fields), so their page cache can be purged on change.
 */
final class SitemapPages
{
    public const string OPTION = 'bf_html_sitemap_pages';

    private const string NEEDLE = '[' . Shortcode::TAG;
    private const int LIMIT = 100;

    public function register(): void
    {
        add_action('save_post', $this->sync(...), 99, 2);
        add_action('deleted_post', $this->remove(...));
    }

    /**
     * @return list<int>
     */
    public function all(): array
    {
        $stored = get_option(self::OPTION);
        $ids = is_array($stored) ? Options::parseIds($stored) : $this->rebuild();

        return Options::parseIds(apply_filters('bf_html_sitemap_pages', $ids));
    }

    /**
     * @return list<int>
     */
    public function rebuild(): array
    {
        global $wpdb;

        $like = '%' . $wpdb->esc_like(self::NEEDLE) . '%';

        $ids = array_values(array_filter(Options::parseIds($wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE %s
            UNION
            SELECT DISTINCT p.ID FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_status = 'publish' AND pm.meta_value LIKE %s
            LIMIT %d",
            $like,
            $like,
            self::LIMIT,
        ))), fn (int $id): bool => self::isViewable($id)));

        $this->store($ids);

        return $ids;
    }

    private function sync(int $postId, WP_Post $post): void
    {
        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }

        if ($post->post_status === 'publish' && self::isViewable($postId) && $this->contains($post)) {
            $this->add($postId);
        } else {
            $this->remove($postId);
        }
    }

    private static function isViewable(int $postId): bool
    {
        $postType = get_post_type($postId);

        return $postType !== false && is_post_type_viewable($postType);
    }

    private function contains(WP_Post $post): bool
    {
        if (str_contains($post->post_content, self::NEEDLE)) {
            return true;
        }

        foreach (get_post_meta($post->ID) ?: [] as $values) {
            foreach ((array) $values as $value) {
                if (is_string($value) && str_contains($value, self::NEEDLE)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function add(int $postId): void
    {
        $ids = $this->stored();

        if (!in_array($postId, $ids, true) && count($ids) < self::LIMIT) {
            $this->store([...$ids, $postId]);
        }
    }

    private function remove(int $postId): void
    {
        $ids = $this->stored();

        if (in_array($postId, $ids, true)) {
            $this->store(array_values(array_diff($ids, [$postId])));
        }
    }

    /**
     * @return list<int>
     */
    private function stored(): array
    {
        $stored = get_option(self::OPTION);

        return is_array($stored) ? Options::parseIds($stored) : $this->rebuild();
    }

    /**
     * @param list<int> $ids
     */
    private function store(array $ids): void
    {
        update_option(self::OPTION, $ids, false);
    }
}
