<?php

/**
 * Related list template part
 * 
 * @package BatDongSan
 * 
 * @param array $args {
 *     @type WP_Query $query Query object
 *     @type string   $title Tiêu đề section
 *     @type string   $prefix_class CSS prefix class (default: 'hang-chuyen-nhuong-detail')
 *     @type string   $type Loại list: 'projects', 'listings', 'posts' (default: 'listings')
 *     @type string   $more_link_url URL cho link "Xem thêm" (optional)
 *     @type string   $more_link_text Text cho link "Xem thêm" (default: 'Xem thêm')
 *     @type callable $item_callback Callback để render từng item (optional)
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

$query = $args['query'] ?? null;
$title = $args['title'] ?? '';
$prefix_class = $args['prefix_class'] ?? 'hang-chuyen-nhuong-detail';
$type = $args['type'] ?? 'listings';
$more_link_url = $args['more_link_url'] ?? '';
$more_link_text = $args['more_link_text'] ?? 'Xem thêm';
$item_callback = $args['item_callback'] ?? null;

if (!$query || !$query->have_posts()) {
    return;
}

$section_class = $prefix_class . '__related-section';
if ($type === 'featured') {
    $section_class = $prefix_class . '__featured-section';
}
?>

<div class="<?= esc_attr($section_class); ?>">
    <?php if ($title) : ?>
        <h3 class="<?= esc_attr($prefix_class); ?>__<?= $type === 'featured' ? 'featured' : 'related'; ?>-title"><?= esc_html($title); ?></h3>
    <?php endif; ?>

    <ul class="<?= esc_attr($prefix_class); ?>__<?= $type === 'featured' ? 'featured' : 'related'; ?>-list">
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <?php if ($item_callback && is_callable($item_callback)) : ?>
                <?php call_user_func($item_callback, get_the_ID()); ?>
            <?php else : ?>
                <li class="<?= esc_attr($prefix_class); ?>__<?= $type === 'featured' ? 'featured' : 'related'; ?>-item">
                    <a href="<?= get_permalink(); ?>" class="<?= esc_attr($prefix_class); ?>__<?= $type === 'featured' ? 'featured' : 'related'; ?>-link">
                        <?php the_title(); ?>
                        <?php if ($type === 'projects') : ?>
                            <span class="<?= esc_attr($prefix_class); ?>__related-count">(<?= wp_count_posts('bat-dong-san')->publish; ?>)</span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endwhile; ?>
    </ul>

    <?php if ($more_link_url) : ?>
        <a href="<?= esc_url($more_link_url); ?>" class="<?= esc_attr($prefix_class); ?>__related-more"><?= esc_html($more_link_text); ?></a>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>
</div>