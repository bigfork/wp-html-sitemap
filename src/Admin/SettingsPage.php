<?php

declare(strict_types=1);

namespace Bigfork\HtmlSitemap\Admin;

use Bigfork\HtmlSitemap\Options;
use Bigfork\HtmlSitemap\Plugin;
use Bigfork\HtmlSitemap\Seo\NullProvider;
use Bigfork\HtmlSitemap\Seo\ProviderRegistry;
use Bigfork\HtmlSitemap\Shortcode;

defined('ABSPATH') || exit;

final readonly class SettingsPage
{
    private const string SLUG = 'html-sitemap';
    private const string GROUP = 'bf_html_sitemap';
    private const string CAPABILITY = 'manage_options';

    public function __construct(private Plugin $plugin)
    {
    }

    public function register(): void
    {
        add_action('admin_menu', $this->addPage(...));
        add_action('admin_init', $this->registerSetting(...));
        add_filter('plugin_action_links_' . plugin_basename($this->plugin->file), $this->actionLinks(...));
    }

    private function addPage(): void
    {
        add_options_page(
            __('HTML Sitemap', 'bf-html-sitemap'),
            __('HTML Sitemap', 'bf-html-sitemap'),
            self::CAPABILITY,
            self::SLUG,
            $this->render(...),
        );
    }

    private function registerSetting(): void
    {
        register_setting(self::GROUP, Options::OPTION, [
            'type' => 'array',
            'sanitize_callback' => fn (mixed $input): array => Options::sanitize($input, [
                ProviderRegistry::AUTO,
                NullProvider::ID,
                ...array_keys($this->plugin->seo->all()),
            ]),
            'default' => [],
        ]);
    }

    /**
     * @param array<string, string> $links
     * @return array<string, string>
     */
    private function actionLinks(array $links): array
    {
        $url = admin_url('options-general.php?page=' . self::SLUG);

        return ['settings' => sprintf('<a href="%s">%s</a>', esc_url($url), esc_html__('Settings', 'bf-html-sitemap'))] + $links;
    }

    private function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }

        $options = $this->plugin->options();
        $name = Options::OPTION;
        $detected = $this->plugin->seo->detected();
        $active = $this->plugin->seo->provider();
        $language = $this->plugin->languages->provider();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('HTML Sitemap', 'bf-html-sitemap'); ?></h1>
            <p>
                <?php
                printf(
                    /* translators: %s: shortcode */
                    esc_html__('Add %s to any page to output the sitemap.', 'bf-html-sitemap'),
                    '<code>[' . esc_html(Shortcode::TAG) . ']</code>',
                );
                ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields(self::GROUP); ?>

                <h2><?php esc_html_e('Post types', 'bf-html-sitemap'); ?></h2>
                <table class="widefat striped" style="max-width: 1040px">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Include', 'bf-html-sitemap'); ?></th>
                            <th scope="col"><?php esc_html_e('Heading', 'bf-html-sitemap'); ?></th>
                            <th scope="col"><?php esc_html_e('Order by', 'bf-html-sitemap'); ?></th>
                            <th scope="col"><?php esc_html_e('Order', 'bf-html-sitemap'); ?></th>
                            <th scope="col"><?php esc_html_e('Limit', 'bf-html-sitemap'); ?></th>
                            <th scope="col"><?php esc_html_e('Position', 'bf-html-sitemap'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($options->postTypes as $config) :
                            $field = "{$name}[post_types][{$config->name}]";
                            ?>
                            <tr>
                                <td>
                                    <?php $this->includeCheckbox($field, $config->enabled, $config->label, $config->name); ?>
                                    <?php $this->noindexNote($active->isPostTypeNoindexed($config->name), $active->label()); ?>
                                </td>
                                <td><?php $this->headingInput($field, $config->heading, $config->label); ?></td>
                                <td>
                                    <select name="<?php echo esc_attr($field); ?>[order_by]">
                                        <?php foreach ($this->orderByLabels() as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($config->orderBy, $value); ?>><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="<?php echo esc_attr($field); ?>[order]">
                                        <option value="ASC" <?php selected($config->order, 'ASC'); ?>><?php esc_html_e('Ascending', 'bf-html-sitemap'); ?></option>
                                        <option value="DESC" <?php selected($config->order, 'DESC'); ?>><?php esc_html_e('Descending', 'bf-html-sitemap'); ?></option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="small-text" min="0" step="1" name="<?php echo esc_attr($field); ?>[limit]" value="<?php echo esc_attr((string) $config->limit); ?>">
                                </td>
                                <td><?php $this->positionInput($field, $config->position); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="description"><?php esc_html_e('Hierarchical post types (e.g. pages) are nested under their parents. A limit of 0 shows everything.', 'bf-html-sitemap'); ?></p>

                <h2><?php esc_html_e('Taxonomies', 'bf-html-sitemap'); ?></h2>
                <table class="widefat striped" style="max-width: 1040px">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Include', 'bf-html-sitemap'); ?></th>
                            <th scope="col"><?php esc_html_e('Heading', 'bf-html-sitemap'); ?></th>
                            <th scope="col"><?php esc_html_e('Position', 'bf-html-sitemap'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($options->taxonomies as $config) :
                            $field = "{$name}[taxonomies][{$config->name}]";
                            ?>
                            <tr>
                                <td>
                                    <?php $this->includeCheckbox($field, $config->enabled, $config->label, $config->name); ?>
                                    <?php $this->noindexNote($active->isTaxonomyNoindexed($config->name), $active->label()); ?>
                                </td>
                                <td><?php $this->headingInput($field, $config->heading, $config->label); ?></td>
                                <td><?php $this->positionInput($field, $config->position); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="description"><?php esc_html_e('Empty terms are always hidden. Sections are ordered by position, lowest first.', 'bf-html-sitemap'); ?></p>

                <h2><?php esc_html_e('Exclusions', 'bf-html-sitemap'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="bf-html-sitemap-exclude-ids"><?php esc_html_e('Excluded post IDs', 'bf-html-sitemap'); ?></label></th>
                        <td>
                            <input type="text" id="bf-html-sitemap-exclude-ids" class="regular-text" name="<?php echo esc_attr($name); ?>[exclude_ids]" value="<?php echo esc_attr(implode(', ', $options->excludeIds)); ?>">
                            <p class="description"><?php esc_html_e('Comma separated. Translations of these posts are excluded too. Posts and terms can also be excluded from their edit screens.', 'bf-html-sitemap'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Child items', 'bf-html-sitemap'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($name); ?>[exclude_descendants]" value="1" <?php checked($options->excludeDescendants); ?>>
                                <?php esc_html_e('Also hide children of excluded pages and terms', 'bf-html-sitemap'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('When unticked, children of an excluded item move up a level instead.', 'bf-html-sitemap'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bf-html-sitemap-seo-provider"><?php esc_html_e('Respect noindex from', 'bf-html-sitemap'); ?></label></th>
                        <td>
                            <select id="bf-html-sitemap-seo-provider" name="<?php echo esc_attr($name); ?>[seo_provider]">
                                <option value="<?php echo esc_attr(ProviderRegistry::AUTO); ?>" <?php selected($options->seoProvider, ProviderRegistry::AUTO); ?>>
                                    <?php
                                    /* translators: %s: detected SEO plugin name */
                                    printf(esc_html__('Auto-detect (%s)', 'bf-html-sitemap'), esc_html($detected->label()));
                                    ?>
                                </option>
                                <?php foreach ($this->plugin->seo->all() as $provider) : ?>
                                    <option value="<?php echo esc_attr($provider->id()); ?>" <?php selected($options->seoProvider, $provider->id()); ?> <?php disabled(!$provider->isAvailable()); ?>>
                                        <?php echo esc_html($provider->label()); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="<?php echo esc_attr(NullProvider::ID); ?>" <?php selected($options->seoProvider, NullProvider::ID); ?>><?php esc_html_e('Ignore SEO plugin', 'bf-html-sitemap'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Posts and terms set to noindex in your SEO plugin are left out of the sitemap.', 'bf-html-sitemap'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Output', 'bf-html-sitemap'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Structured data', 'bf-html-sitemap'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($name); ?>[schema]" value="1" <?php checked($options->schema); ?>>
                                <?php esc_html_e('Output schema.org SiteNavigationElement JSON-LD', 'bf-html-sitemap'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Multilingual', 'bf-html-sitemap'); ?></th>
                        <td>
                            <p>
                                <?php
                                echo $language->id() === 'none'
                                    ? esc_html__('No multilingual plugin detected.', 'bf-html-sitemap')
                                    : esc_html(sprintf(
                                        /* translators: %s: multilingual plugin id */
                                        __('Detected %s. The sitemap renders in the current language and custom headings can be translated via string translation.', 'bf-html-sitemap'),
                                        strtoupper($language->id()),
                                    ));
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function includeCheckbox(string $field, bool $enabled, string $label, string $name): void
    {
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr($field); ?>[enabled]" value="1" <?php checked($enabled); ?>>
            <strong><?php echo esc_html($label); ?></strong>
            <code><?php echo esc_html($name); ?></code>
        </label>
        <?php
    }

    private function headingInput(string $field, string $heading, string $placeholder): void
    {
        printf(
            '<input type="text" class="regular-text" name="%s[heading]" value="%s" placeholder="%s">',
            esc_attr($field),
            esc_attr($heading),
            esc_attr($placeholder),
        );
    }

    private function positionInput(string $field, int $position): void
    {
        printf(
            '<input type="number" class="small-text" step="1" name="%s[position]" value="%s">',
            esc_attr($field),
            esc_attr((string) $position),
        );
    }

    private function noindexNote(bool $noindexed, string $provider): void
    {
        if (!$noindexed) {
            return;
        }

        printf(
            '<p class="description">%s</p>',
            /* translators: %s: SEO plugin name */
            esc_html(sprintf(__('Set to noindex in %s, so it will be skipped.', 'bf-html-sitemap'), $provider)),
        );
    }

    /**
     * @return array<string, string>
     */
    private function orderByLabels(): array
    {
        return [
            'menu_order' => __('Menu order', 'bf-html-sitemap'),
            'title' => __('Title', 'bf-html-sitemap'),
            'date' => __('Published date', 'bf-html-sitemap'),
            'modified' => __('Modified date', 'bf-html-sitemap'),
        ];
    }
}
