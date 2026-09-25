<?php

/**
 * Override by copying to your-theme/html-sitemap/sitemap.php
 *
 * @var list<\Bigfork\HtmlSitemap\Sitemap\Section> $sections
 * @var int $headingLevel
 * @var \Bigfork\HtmlSitemap\Sitemap\Renderer $renderer
 */

declare(strict_types=1);

defined('ABSPATH') || exit;
?>
<nav class="html-sitemap" aria-label="<?php esc_attr_e('Sitemap', 'bf-html-sitemap'); ?>">
    <?php foreach ($sections as $section) : ?>
        <section class="html-sitemap__section html-sitemap__section--<?php echo esc_attr(sanitize_html_class($section->name)); ?>">
            <h<?php echo $headingLevel; ?> class="html-sitemap__heading"><?php echo esc_html($section->heading); ?></h<?php echo $headingLevel; ?>>
            <?php echo $renderer->list($section->items); ?>
        </section>
    <?php endforeach; ?>
</nav>
