<?php

/**
 * Override by copying to your-theme/html-sitemap/list.php
 *
 * @var list<\Bigfork\HtmlSitemap\Sitemap\Item> $items
 * @var int $depth
 * @var \Bigfork\HtmlSitemap\Sitemap\Renderer $renderer
 */

declare(strict_types=1);

defined('ABSPATH') || exit;
?>
<ul class="html-sitemap__list html-sitemap__list--depth-<?php echo (int) $depth; ?>">
    <?php foreach ($items as $item) : ?>
        <li class="html-sitemap__item">
            <a class="html-sitemap__link" href="<?php echo esc_url($item->url); ?>"><?php echo esc_html($item->title); ?></a>
            <?php echo $renderer->list($item->children, $depth + 1); ?>
        </li>
    <?php endforeach; ?>
</ul>
