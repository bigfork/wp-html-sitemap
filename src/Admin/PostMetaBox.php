<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Admin;

use Bigfork\HtmlSitemap\Exclusion;
use Bigfork\HtmlSitemap\Plugin;
use WP_Post;

defined('ABSPATH') || exit;

final readonly class PostMetaBox
{
    private const string ID = 'bf-html-sitemap';

    public function __construct(private Plugin $plugin)
    {
    }

    public function register(): void
    {
        add_action('add_meta_boxes', $this->add(...));
        add_action('save_post', $this->save(...), 10, 2);
    }

    /**
     * @return list<string>
     */
    private function postTypes(): array
    {
        return array_keys($this->plugin->options()->enabledPostTypes());
    }

    private function add(): void
    {
        add_meta_box(
            self::ID,
            __('HTML Sitemap', 'bf-html-sitemap'),
            $this->render(...),
            $this->postTypes(),
            'side',
            'low',
        );
    }

    private function render(WP_Post $post): void
    {
        $seo = $this->plugin->seo->provider();
        $inSettings = in_array($post->ID, $this->plugin->options()->excludeIds, true);

        wp_nonce_field(Exclusion::nonceAction(Exclusion::CONTEXT_POST), Exclusion::NONCE);
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(Exclusion::FIELD); ?>" value="1" <?php checked(Exclusion::isPostExcluded($post->ID)); ?>>
            <?php esc_html_e('Exclude from HTML Sitemap', 'bf-html-sitemap'); ?>
        </label>
        <?php if ($seo->isPostNoindexed($post->ID, $post->post_type)) : ?>
            <p class="description">
                <?php
                /* translators: %s: SEO plugin name */
                echo esc_html(sprintf(__('Already hidden: set to noindex in %s.', 'bf-html-sitemap'), $seo->label()));
                ?>
            </p>
        <?php elseif ($inSettings) : ?>
            <p class="description"><?php esc_html_e('Already hidden: excluded in HTML Sitemap settings.', 'bf-html-sitemap'); ?></p>
        <?php endif;
    }

    private function save(int $postId, WP_Post $post): void
    {
        if (wp_is_post_autosave($postId) || wp_is_post_revision($postId) || isset($_REQUEST['bulk_edit'])) {
            return;
        }

        if (!in_array($post->post_type, $this->postTypes(), true) || !current_user_can('edit_post', $postId)) {
            return;
        }

        $excluded = Exclusion::submitted(Exclusion::CONTEXT_POST);

        if ($excluded !== null) {
            Exclusion::setPostExcluded($postId, $excluded);
        }
    }
}
