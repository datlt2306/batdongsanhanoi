<?php

/**
 * Card Project Template Part
 *
 * Card dự án dùng chung
 *
 * @package BatDongSan
 *
 * @param array $args {
 *     @type WP_Post|int $post    Post object hoặc post ID (required)
 *     @type string      $class   Class bổ sung (optional)
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

// Lấy post object
$post = null;
if (isset($args['post'])) {
    $post = is_object($args['post']) ? $args['post'] : get_post($args['post']);
}

if (!$post) {
    return;
}

$post_id = $post->ID;
$post_title = $post->post_title;
$post_permalink = get_permalink($post_id);
$custom_class = $args['class'] ?? '';

// Lấy dữ liệu ACF dựa trên logic mới (Gộp Phân khu vào Dự án)
$du_an_cha = get_field('du_an_cha', $post_id);
$is_subdivision = !empty($du_an_cha);

$trang_thai = get_field('trang_thai', $post_id);

// Thừa hưởng dữ liệu ĐỆ QUY từ cha/ông
$address = batdongsan_get_project_meta_recursive('address', $post_id);
$quy_mo = batdongsan_get_project_meta_recursive('quy_mo', $post_id);

// Mapping trạng thái
$trang_thai_labels = [
    'dang_mo_ban' => 'Đang mở bán',
    'sap_mo_ban' => 'Sắp mở bán',
    'da_ban_het' => 'Đã bán hết',
    'tam_dung' => 'Tạm dừng',
    'dang_cap_nhat' => 'Đang cập nhật',
];
$trang_thai_label = isset($trang_thai_labels[$trang_thai]) ? $trang_thai_labels[$trang_thai] : '';

// Mapping status badge classes
$status_classes = [
    'dang_mo_ban' => 'bg-green-50 text-green-600',
    'sap_mo_ban' => 'bg-red-50 text-red-500',
    'da_ban_het' => 'bg-gray-100 text-gray-500',
    'tam_dung' => 'bg-gray-100 text-gray-500',
    'dang_cap_nhat' => 'bg-gray-100 text-gray-500',
];
$status_class = isset($status_classes[$trang_thai]) ? $status_classes[$trang_thai] : '';

// Include icons library
if (!function_exists('batdongsan_icon')) {
    require_once get_template_directory() . '/template-parts/svg-icons.php';
}
?>

<article class="bg-surface rounded-2xl flex flex-col gap-0 border border-black/[0.06] shadow-card transition-all duration-300 hover:scale-[1.01] hover:shadow-card-hover overflow-hidden <?php echo esc_attr($custom_class); ?>">
    <div class="relative w-full shrink-0 h-60 bg-stone overflow-hidden flex max-md:h-auto max-md:bg-surface max-md:flex-col max-md:p-3 max-md:gap-2">
        <a
            class="block w-full h-full relative overflow-hidden group max-md:h-auto max-md:aspect-video max-md:rounded-xl max-md:relative"
            href="<?php echo esc_url($post_permalink); ?>"
            aria-label="Xem chi tiết dự án <?php echo esc_attr($post_title); ?>"
            title="Xem chi tiết dự án <?php echo esc_attr($post_title); ?>">
            <?php
            if (function_exists('bds_get_optimized_image')) {
                echo bds_get_optimized_image(get_post_thumbnail_id($post_id), 'bds-card', ['class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105'], true);
            } elseif (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, 'medium', ['class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105']);
            } else {
                echo '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/placeholder-real-estate.jpg') . '" alt="' . esc_attr($post_title) . '" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">';
            }
            ?>
            <span class="absolute bottom-0 left-0 w-full h-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 transition-all duration-300 group-hover:h-[50%] group-hover:opacity-100 pointer-events-none max-md:hidden"></span>
        </a>
    </div>

    <div class="p-5 flex flex-col gap-3 flex-1 min-w-0">
        <h3 class="font-semibold text-base text-charcoal leading-snug m-0">
            <a href="<?php echo esc_url($post_permalink); ?>" class="text-inherit no-underline hover:text-primary transition-colors"><?php echo esc_html($post_title); ?></a>
        </h3>

        <?php if ($is_subdivision && !empty($du_an_cha)) : ?>
            <div class="flex items-center gap-1.5 text-xs text-charcoal-muted -mt-1 mb-1">
                <span>Thuộc dự án:</span>
                <a href="<?php echo esc_url(get_permalink($du_an_cha->ID)); ?>" class="font-medium text-primary hover:underline"><?php echo esc_html($du_an_cha->post_title); ?></a>
            </div>
        <?php endif; ?>

        <?php if ($address) : ?>
            <div class="flex items-center gap-2 text-charcoal-muted text-xs">
                <span aria-hidden="true" class="inline-flex text-charcoal-muted shrink-0">
                    <?php echo batdongsan_icon('location'); ?>
                </span>
                <span class="line-clamp-1"><?php echo esc_html($address); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($quy_mo) : ?>
            <div class="flex items-center gap-2 text-charcoal-muted text-xs">
                <span aria-hidden="true" class="inline-flex text-charcoal-muted shrink-0">
                    <?php echo batdongsan_icon('building'); ?>
                </span>
                <span><?php echo esc_html($quy_mo); ?></span>
            </div>
        <?php endif; ?>

        <div class="my-2 h-px bg-border/60 w-full"></div>

        <div class="mt-auto flex items-center justify-between">
            <?php if ($trang_thai_label) : ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?php echo esc_attr($status_class); ?>"><?php echo esc_html($trang_thai_label); ?></span>
            <?php endif; ?>
            <a class="inline-flex items-center gap-1 text-primary text-xs font-semibold hover:underline transition-colors" href="<?php echo esc_url($post_permalink); ?>">Chi tiết <span aria-hidden="true">→</span></a>
        </div>
    </div>
</article>