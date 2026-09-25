<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Admin;

use Bigfork\HtmlSitemap\Exclusion;
use Bigfork\HtmlSitemap\Plugin;
use WP_Term;

defined('ABSPATH') || exit;

final readonly class TermField
{
    public function __construct(private Plugin $plugin)
    {
    }

    public function register(): void
    {
        foreach (array_keys($this->plugin->options()->enabledTaxonomies()) as $taxonomy) {
            add_action("{$taxonomy}_add_form_fields", $this->renderAdd(...));
            add_action("{$taxonomy}_edit_form_fields", $this->renderEdit(...));
            add_action("created_{$taxonomy}", $this->save(...));
            add_action("edited_{$taxonomy}", $this->save(...));
        }
    }

    private function renderAdd(): void
    {
        ?>
        <div class="form-field">
            <?php wp_nonce_field(Exclusion::nonceAction(Exclusion::CONTEXT_TERM), Exclusion::NONCE); ?>
            <label>
                <input type="checkbox" name="<?php echo esc_attr(Exclusion::FIELD); ?>" value="1">
                <?php esc_html_e('Exclude from HTML Sitemap', 'bf-html-sitemap'); ?>
            </label>
        </div>
        <?php
    }

    private function renderEdit(WP_Term $term): void
    {
        $seo = $this->plugin->seo->provider();
        ?>
        <tr class="form-field">
            <th scope="row"><?php esc_html_e('HTML Sitemap', 'bf-html-sitemap'); ?></th>
            <td>
                <?php wp_nonce_field(Exclusion::nonceAction(Exclusion::CONTEXT_TERM), Exclusion::NONCE); ?>
                <label>
                    <input type="checkbox" name="<?php echo esc_attr(Exclusion::FIELD); ?>" value="1" <?php checked(Exclusion::isTermExcluded($term->term_id)); ?>>
                    <?php esc_html_e('Exclude from HTML Sitemap', 'bf-html-sitemap'); ?>
                </label>
                <?php if ($seo->isTermNoindexed($term->term_id, $term->taxonomy)) : ?>
                    <p class="description">
                        <?php
                        /* translators: %s: SEO plugin name */
                        echo esc_html(sprintf(__('Already hidden: set to noindex in %s.', 'bf-html-sitemap'), $seo->label()));
                        ?>
                    </p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    private function save(int $termId): void
    {
        if (!current_user_can('edit_term', $termId)) {
            return;
        }

        $excluded = Exclusion::submitted(Exclusion::CONTEXT_TERM);

        if ($excluded !== null) {
            Exclusion::setTermExcluded($termId, $excluded);
        }
    }
}
