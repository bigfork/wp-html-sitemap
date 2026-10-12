<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Admin;

use Bigfork\HtmlSitemap\Exclusion;
use Bigfork\HtmlSitemap\Plugin;

defined('ABSPATH') || exit;

final readonly class ListTable
{
    public const string COLUMN = 'bf_html_sitemap';

    private const string BULK_FIELD = 'bf_html_sitemap_bulk';
    private const string BULK_NONCE = 'bf_html_sitemap_bulk_nonce';
    private const string BULK_NONCE_ACTION = 'bf_html_sitemap_bulk';
    private const string BULK_EXCLUDE = 'exclude';
    private const string BULK_INCLUDE = 'include';

    private const string QUICK_EDIT_SCRIPT = <<<'JS'
        (() => {
            const original = inlineEditPost.edit;

            inlineEditPost.edit = (id, ...rest) => {
                const result = original.call(inlineEditPost, id, ...rest);
                const postId = typeof id === 'object' ? inlineEditPost.getId(id) : id;
                const flag = document.querySelector(`#post-${postId} .bf-html-sitemap-flag`);
                const checkbox = document.querySelector(`#edit-${postId} input[name="bf_html_sitemap_exclude"]`);

                if (flag && checkbox) {
                    checkbox.checked = flag.dataset.excluded === '1';
                }

                return result;
            };
        })();
        JS;

    public function __construct(private Plugin $plugin)
    {
    }

    public function register(): void
    {
        foreach ($this->postTypes() as $postType) {
            add_filter("manage_{$postType}_posts_columns", $this->addColumn(...));
            add_action("manage_{$postType}_posts_custom_column", $this->renderColumn(...), 10, 2);
        }

        add_action('quick_edit_custom_box', $this->renderQuickEdit(...), 10, 2);
        add_action('bulk_edit_custom_box', $this->renderBulkEdit(...), 10, 2);
        add_action('bulk_edit_posts', $this->saveBulkEdit(...));
        add_action('admin_enqueue_scripts', $this->enqueue(...));
    }

    /**
     * @return list<string>
     */
    private function postTypes(): array
    {
        return array_keys($this->plugin->options()->enabledPostTypes());
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    private function addColumn(array $columns): array
    {
        return [...$columns, self::COLUMN => __('Sitemap', 'bf-html-sitemap')];
    }

    private function renderColumn(string $column, int $postId): void
    {
        if ($column !== self::COLUMN) {
            return;
        }

        $excluded = Exclusion::isPostExcluded($postId);
        $reason = $this->hiddenReason($postId, $excluded);

        printf(
            '<span class="bf-html-sitemap-flag" data-excluded="%d"></span><span class="dashicons %s" aria-hidden="true"></span> %s',
            (int) $excluded,
            $reason === null ? 'dashicons-yes' : 'dashicons-hidden',
            esc_html($reason ?? __('Included', 'bf-html-sitemap')),
        );
    }

    private function hiddenReason(int $postId, bool $excluded): ?string
    {
        $seo = $this->plugin->seo->provider();

        return match (true) {
            $excluded => __('Excluded', 'bf-html-sitemap'),
            in_array($postId, $this->plugin->options()->excludeIds, true) => __('Excluded in settings', 'bf-html-sitemap'),
            $seo->isPostNoindexed($postId, (string) get_post_type($postId)) => sprintf(
                /* translators: %s: SEO plugin name */
                __('Noindex (%s)', 'bf-html-sitemap'),
                $seo->label(),
            ),
            get_post_field('post_password', $postId) !== '' => __('Password protected', 'bf-html-sitemap'),
            default => null,
        };
    }

    private function renderQuickEdit(string $column, string $postType): void
    {
        if ($column !== self::COLUMN || !in_array($postType, $this->postTypes(), true)) {
            return;
        }
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <?php wp_nonce_field(Exclusion::nonceAction(Exclusion::CONTEXT_POST), Exclusion::NONCE, false); ?>
                <label class="alignleft">
                    <input type="checkbox" name="<?php echo esc_attr(Exclusion::FIELD); ?>" value="1">
                    <span class="checkbox-title"><?php esc_html_e('Exclude from HTML Sitemap', 'bf-html-sitemap'); ?></span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    private function renderBulkEdit(string $column, string $postType): void
    {
        if ($column !== self::COLUMN || !in_array($postType, $this->postTypes(), true)) {
            return;
        }
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <?php wp_nonce_field(self::BULK_NONCE_ACTION, self::BULK_NONCE, false); ?>
                <label class="inline-edit-status alignleft">
                    <span class="title"><?php esc_html_e('HTML Sitemap', 'bf-html-sitemap'); ?></span>
                    <select name="<?php echo esc_attr(self::BULK_FIELD); ?>">
                        <option value=""><?php esc_html_e('— No Change —', 'bf-html-sitemap'); ?></option>
                        <option value="<?php echo esc_attr(self::BULK_EXCLUDE); ?>"><?php esc_html_e('Exclude', 'bf-html-sitemap'); ?></option>
                        <option value="<?php echo esc_attr(self::BULK_INCLUDE); ?>"><?php esc_html_e('Include', 'bf-html-sitemap'); ?></option>
                    </select>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /**
     * @param list<int> $postIds
     */
    private function saveBulkEdit(array $postIds): void
    {
        $nonce = isset($_REQUEST[self::BULK_NONCE]) ? sanitize_text_field(wp_unslash($_REQUEST[self::BULK_NONCE])) : '';
        $action = isset($_REQUEST[self::BULK_FIELD]) ? sanitize_key(wp_unslash($_REQUEST[self::BULK_FIELD])) : '';

        if (!wp_verify_nonce($nonce, self::BULK_NONCE_ACTION) || !in_array($action, [self::BULK_EXCLUDE, self::BULK_INCLUDE], true)) {
            return;
        }

        foreach ($postIds as $postId) {
            if (in_array(get_post_type($postId), $this->postTypes(), true) && current_user_can('edit_post', $postId)) {
                Exclusion::setPostExcluded((int) $postId, $action === self::BULK_EXCLUDE);
            }
        }

        $this->plugin->cache->flush();
    }

    private function enqueue(string $hook): void
    {
        $screen = get_current_screen();

        if ($hook === 'edit.php' && $screen !== null && in_array($screen->post_type, $this->postTypes(), true)) {
            wp_add_inline_script('inline-edit-post', self::QUICK_EDIT_SCRIPT);
        }
    }
}
