<?php

/**
 * Card BDS Listing Template Part
 *
 * Card bất động sản thuộc dự án (căn hộ, biệt thự) với design giống du-an-item
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

// Lấy post object từ $post global (đã được thiết lập bởi the_post() trong query)
global $post;

if (!$post instanceof WP_Post) {
    return;
}

$post_id = $post->ID;
$post_title = $post->post_title;
$post_permalink = get_permalink($post_id);
$custom_class = $args['class'] ?? '';

// Lấy dữ liệu ACF
$khoang_gia = get_field('khoang_gia', $post_id);
$gia_tren_met = get_field('gia_tren_met_vuong', $post_id);
$dia_chi = get_field('dia_chi', $post_id);
$dien_tich = get_field('dien_tich', $post_id);
$gallery_nha_dat = get_field('gallery_nha_dat', $post_id);

// Chuẩn hóa gallery: lấy tối đa 4 ID (1 ảnh chính + 3 thumb)
$gallery_ids = [];
if ($gallery_nha_dat && is_array($gallery_nha_dat) && !empty($gallery_nha_dat)) {
    foreach ($gallery_nha_dat as $image) {
        $image_id = is_array($image) && isset($image['ID']) ? (int) $image['ID'] : (is_numeric($image) ? (int) $image : 0);
        if ($image_id > 0) {
            $gallery_ids[] = $image_id;
        }
    }
}
$total_images = count($gallery_ids);
$main_id = $total_images > 0 ? $gallery_ids[0] : get_post_thumbnail_id($post_id);
$thumb_ids = array_slice($gallery_ids, 1, 3);
$placeholder_url = get_template_directory_uri() . '/assets/images/placeholder-real-estate.jpg';

if (!function_exists('batdongsan_icon')) {
    require_once get_template_directory() . '/template-parts/svg-icons.php';
}
?>

<article class="bg-surface rounded-2xl overflow-hidden relative border border-black/[0.06] shadow-card transition-all duration-300 hover:scale-[1.01] hover:shadow-card-hover <?php echo esc_attr($custom_class); ?>">
    <a
        class="block h-60 bg-stone relative overflow-hidden group <?php echo $total_images > 1 ? 'flex' : ''; ?>"
        href="<?php echo esc_url($post_permalink); ?>"
        aria-label="Xem chi tiết <?php echo esc_attr($post_title); ?>"
        title="Chi tiết <?php echo esc_attr($post_title); ?>">
        <?php if ($total_images > 1) : ?>
            <!-- Ảnh lớn trái + tối đa 3 ảnh nhỏ phải -->
            <span class="block w-2/3 h-full shrink-0 overflow-hidden">
                <?php
                if ($main_id && function_exists('bds_get_optimized_image')) {
                    echo bds_get_optimized_image($main_id, 'bds-card', ['class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105'], true);
                } elseif ($main_id) {
                    echo wp_get_attachment_image($main_id, 'medium', false, ['class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105']);
                } else {
                    echo '<img src="' . esc_url($placeholder_url) . '" alt="' . esc_attr($post_title) . '" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">';
                }
                ?>
            </span>
            <span class="w-1/3 h-full flex flex-col gap-0.5 p-0.5 shrink-0">
                <?php foreach ($thumb_ids as $tid) : ?>
                    <span class="flex-1 min-h-0 overflow-hidden">
                        <?php
                        if (function_exists('bds_get_optimized_image')) {
                            echo bds_get_optimized_image($tid, 'thumbnail', ['class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105'], true);
                        } else {
                            echo wp_get_attachment_image($tid, 'thumbnail', false, ['class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105']);
                        }
                        ?>
                    </span>
                <?php endforeach; ?>
            </span>
        <?php else : ?>
            <?php
            if ($main_id && function_exists('bds_get_optimized_image')) {
                echo bds_get_optimized_image($main_id, 'bds-card', ['class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105'], true);
            } elseif ($main_id) {
                echo get_the_post_thumbnail($post_id, 'medium', ['class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105']);
            } else {
                echo '<img src="' . esc_url($placeholder_url) . '" alt="' . esc_attr($post_title) . '" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">';
            }
            ?>
        <?php endif; ?>
        <span class="absolute bottom-0 left-0 w-full h-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 transition-all duration-300 group-hover:h-[50%] group-hover:opacity-100 pointer-events-none"></span>
        <?php if ($total_images > 1) : ?>
            <span class="absolute bottom-2 right-2 z-10 flex items-center gap-1 px-2.5 py-1 bg-black/60 backdrop-blur-md text-white text-xs font-medium rounded-full" aria-hidden="true">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="3" />
                    <circle cx="8.5" cy="8.5" r="1.5" />
                    <path d="m21 15-5-5L5 21" />
                </svg>
                <span><?php echo (int) $total_images; ?></span>
            </span>
        <?php endif; ?>
    </a>

    <div class="p-5 flex flex-col gap-3">
        <h3 class="font-semibold text-sm sm:text-base text-charcoal leading-snug m-0 line-clamp-2" title="<?php echo esc_attr($post_title); ?>">
            <a href="<?php echo esc_url($post_permalink); ?>" class="text-inherit no-underline hover:text-primary transition-colors"><?php echo esc_html($post_title); ?></a>
        </h3>

        <?php if ($dia_chi) : ?>
            <div class="flex items-center gap-2 text-charcoal-muted text-xs">
                <span aria-hidden="true" class="inline-flex text-charcoal-muted shrink-0">
                    <?php echo batdongsan_icon('location'); ?>
                </span>
                <span class="line-clamp-1"><?php echo esc_html($dia_chi); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($dien_tich) : ?>
            <div class="flex items-center gap-2 text-charcoal-muted text-xs">
                <span aria-hidden="true" class="inline-flex text-charcoal-muted shrink-0">
                    <?php echo batdongsan_icon('building'); ?>
                </span>
                <span><?php echo esc_html($dien_tich); ?></span>
            </div>
        <?php endif; ?>

        <div class="my-2 h-px bg-border/60 w-full"></div>

        <div class="mt-auto flex items-center justify-between">
            <span class="text-primary font-bold text-lg">
                <?php
                if ($gia_tren_met) {
                    echo esc_html($gia_tren_met);
                } elseif ($khoang_gia) {
                    echo esc_html($khoang_gia);
                } else {
                    echo 'Liên hệ';
                }
                ?>
            </span>
            <a class="inline-flex items-center gap-1 text-primary text-xs font-semibold hover:underline transition-colors" href="<?php echo esc_url($post_permalink); ?>">Chi tiết <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg></a>
        </div>
    </div>
</article>