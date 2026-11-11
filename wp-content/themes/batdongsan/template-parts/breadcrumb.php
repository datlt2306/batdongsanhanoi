<?php

/**
 * Breadcrumb template part with Schema.org Microdata
 * 
 * Supports both standalone execution and clean fallback when Rank Math is absent.
 * 
 * @package BatDongSan
 * 
 * @param array $args {
 *     @type array  $breadcrumb_parts Mảng các breadcrumb items với 'label' và 'url'
 *     @type string $prefix_class CSS prefix class (default: 'hang-chuyen-nhuong-detail' hoặc 'project-detail')
 *     @type string $current_label Label cho item hiện tại (default: get_the_title())
 *     @type bool   $mobile_optimized Rút gọn breadcrumb cho mobile (default: true)
 *     @type bool   $no_order Không thêm order utility class
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

$breadcrumb_parts = $args['breadcrumb_parts'] ?? [];
$prefix_class = $args['prefix_class'] ?? 'hang-chuyen-nhuong-detail';
$current_label = $args['current_label'] ?? get_the_title();
$mobile_optimized = $args['mobile_optimized'] ?? true;
$no_order = $args['no_order'] ?? false;

// Fallback: Tự động phân giải breadcrumbs nếu chưa được truyền và đang ở trang chi tiết
if (empty($breadcrumb_parts)) {
    if (is_singular('bat-dong-san') && function_exists('batdongsan_get_bat_dong_san_breadcrumb')) {
        $breadcrumb_parts = batdongsan_get_bat_dong_san_breadcrumb(get_the_ID());
    } elseif (is_singular('du-an') && function_exists('batdongsan_get_du_an_breadcrumb')) {
        $breadcrumb_parts = batdongsan_get_du_an_breadcrumb(get_the_ID());
    }
}

if (empty($breadcrumb_parts)) {
    return;
}

// Tạo breadcrumb cho mobile (rút gọn)
$mobile_breadcrumb = ($mobile_optimized && function_exists('batdongsan_get_mobile_breadcrumb'))
    ? batdongsan_get_mobile_breadcrumb($breadcrumb_parts)
    : null;
$has_mobile_breadcrumb = $mobile_breadcrumb !== null;

$nav_classes = $prefix_class . '__breadcrumb text-[13px] text-charcoal-muted mb-3 max-lg:text-xs max-lg:px-1 max-lg:mx-4 max-lg:mb-3';
if (!$no_order) {
    $nav_classes .= ' order-1 max-lg:order-2';
}
?>

<nav class="<?= esc_attr($nav_classes); ?>" aria-label="<?= esc_attr__('Breadcrumb', 'batdongsan'); ?>">
    <!-- Desktop: Breadcrumb đầy đủ chuẩn Schema.org BreadcrumbList Microdata -->
    <ol itemscope itemtype="https://schema.org/BreadcrumbList" class="<?= esc_attr($prefix_class); ?>__breadcrumb-list <?= esc_attr($prefix_class); ?>__breadcrumb-list--desktop flex flex-wrap items-center gap-1 list-none m-0 mb-1 p-0<?= $has_mobile_breadcrumb ? ' max-md:hidden' : ''; ?>">
        <?php
        $position = 1;
        foreach ($breadcrumb_parts as $index => $crumb) :
        ?>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="<?= esc_attr($prefix_class); ?>__breadcrumb-item shrink-0 whitespace-nowrap inline-flex items-center">
                <?php if (!empty($crumb['url'])) : ?>
                    <a itemprop="item" href="<?= esc_url($crumb['url']); ?>" class="text-charcoal-muted no-underline hover:text-primary transition-colors">
                        <span itemprop="name"><?= esc_html($crumb['label']); ?></span>
                    </a>
                <?php else : ?>
                    <span itemprop="name" class="text-charcoal-muted"><?= esc_html($crumb['label']); ?></span>
                <?php endif; ?>
                <meta itemprop="position" content="<?= (int) $position; ?>">
                <?php if (!empty($current_label) || $index < count($breadcrumb_parts) - 1) : ?>
                    <span class="<?= esc_attr($prefix_class); ?>__breadcrumb-separator mx-1 text-stone" aria-hidden="true">/</span>
                <?php endif; ?>
            </li>
        <?php
            $position++;
        endforeach;
        ?>
        <?php if (!empty($current_label)) : ?>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="<?= esc_attr($prefix_class); ?>__breadcrumb-item <?= esc_attr($prefix_class); ?>__breadcrumb-item--current shrink-0 inline-flex items-center">
                <span itemprop="name" class="text-charcoal font-medium"><?= esc_html($current_label); ?></span>
                <meta itemprop="position" content="<?= (int) $position; ?>">
            </li>
        <?php endif; ?>
    </ol>

    <!-- Mobile: Breadcrumb rút gọn giao diện -->
    <?php if ($has_mobile_breadcrumb) : ?>
        <ol class="<?= esc_attr($prefix_class); ?>__breadcrumb-list <?= esc_attr($prefix_class); ?>__breadcrumb-list--mobile hidden max-md:flex max-md:flex-wrap max-md:items-center max-md:gap-1 list-none m-0 mb-1 p-0" aria-hidden="true">
            <?php foreach ($mobile_breadcrumb as $index => $crumb) : ?>
                <li class="<?= esc_attr($prefix_class); ?>__breadcrumb-item shrink-0 whitespace-nowrap inline-flex items-center">
                    <?php if (!empty($crumb['url'])) : ?>
                        <a href="<?= esc_url($crumb['url']); ?>" class="text-charcoal-muted no-underline hover:text-primary transition-colors"><?= esc_html($crumb['label']); ?></a>
                    <?php else : ?>
                        <span class="text-charcoal-muted"><?= esc_html($crumb['label']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($current_label) || $index < count($mobile_breadcrumb) - 1) : ?>
                        <span class="<?= esc_attr($prefix_class); ?>__breadcrumb-separator mx-1 text-stone" aria-hidden="true">/</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if (!empty($current_label)) : ?>
                <li class="<?= esc_attr($prefix_class); ?>__breadcrumb-item <?= esc_attr($prefix_class); ?>__breadcrumb-item--current shrink-0 break-words inline-flex items-center">
                    <span class="text-charcoal font-medium inline break-words"><?= esc_html($current_label); ?></span>
                </li>
            <?php endif; ?>
        </ol>
    <?php endif; ?>
</nav>