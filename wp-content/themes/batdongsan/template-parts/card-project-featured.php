<?php

/**
 * Card Project Featured Template Part - Luxury Real Estate Redesign
 *
 * Card dự án nổi bật phong cách kiến trúc luxury (porcelain surface, diffuse elevation, champagne bronze price, memoized partner logo)
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

// Lấy dữ liệu ACF (đệ quy từ cha/ông nếu là phân khu)
$du_an_cha = get_field('du_an_cha', $post_id);
$is_subdivision = !empty($du_an_cha);

$trang_thai = get_field('trang_thai', $post_id);
$featured = get_field('featured', $post_id);
$gallery_raw = batdongsan_get_project_meta_recursive('gallery_du_an', $post_id);

$address = batdongsan_get_project_meta_recursive('address', $post_id);
$chu_dau_tu = batdongsan_get_project_meta_recursive('chu_dau_tu', $post_id);
$quy_mo = batdongsan_get_project_meta_recursive('quy_mo', $post_id);

// Giá dự án (ưu tiên gia_ban_du_kien, fallback price)
$gia_ban_du_kien = batdongsan_get_project_meta_recursive('gia_ban_du_kien', $post_id);
if (!$gia_ban_du_kien) {
    $price_val = batdongsan_get_project_meta_recursive('price', $post_id);
    if ($price_val) {
        $price_str = trim((string) $price_val);
        $gia_ban_du_kien = preg_match('/tỷ|triệu|m2/iu', $price_str) ? $price_str : 'Từ ' . $price_str . ' tỷ';
    }
}

// Mapping trạng thái sang architectural tags
$trang_thai_labels = [
    'dang_mo_ban'   => 'Đang mở bán',
    'sap_mo_ban'    => 'Sắp mở bán',
    'da_ban_het'    => 'Đã bàn giao',
    'tam_dung'      => 'Tạm dừng',
    'dang_cap_nhat' => 'Đang cập nhật',
];

$trang_thai_classes = [
    'dang_mo_ban'   => 'bg-emerald-600/90 text-white backdrop-blur-md',
    'sap_mo_ban'    => 'bg-primary/90 text-white backdrop-blur-md',
    'da_ban_het'    => 'bg-black/60 text-white backdrop-blur-md',
    'tam_dung'      => 'bg-black/50 text-white/90 backdrop-blur-md',
    'dang_cap_nhat' => 'bg-black/50 text-white/90 backdrop-blur-md',
];

$trang_thai_label = isset($trang_thai_labels[$trang_thai]) ? $trang_thai_labels[$trang_thai] : '';
$trang_thai_class = isset($trang_thai_classes[$trang_thai]) ? $trang_thai_classes[$trang_thai] : '';

// Lấy ảnh chính (featured image hoặc ảnh đầu tiên từ gallery)
$main_image_id = get_post_thumbnail_id($post_id);
if (!$main_image_id && !empty($gallery_raw)) {
    $first_img = $gallery_raw[0];
    $main_image_id = is_array($first_img) ? ($first_img['ID'] ?? $first_img['id'] ?? 0) : $first_img;
}

// Lấy 3 ảnh thumbnails từ gallery
$thumbnails = [];
if (!empty($gallery_raw) && is_array($gallery_raw)) {
    $count = 0;
    foreach ($gallery_raw as $image) {
        if ($count >= 3) break;
        $thumbnails[] = is_array($image) ? ($image['ID'] ?? $image['id'] ?? $image) : $image;
        $count++;
    }
}

// Tìm logo chủ đầu tư từ post type doi-tac (cached helper to eliminate N+1 query)
$investor_logo_id = function_exists('batdongsan_get_partner_logo_by_name')
    ? batdongsan_get_partner_logo_by_name($chu_dau_tu)
    : null;

// Include icons library
if (!function_exists('batdongsan_icon')) {
    require_once get_template_directory() . '/template-parts/svg-icons.php';
}
?>

<article class="group bg-surface rounded-2xl flex flex-col h-full w-full overflow-hidden relative border border-black/[0.06] shadow-card transition-all duration-300 ease-out hover:scale-[1.01] hover:shadow-card-hover <?php echo esc_attr($custom_class); ?>">
    <a
        class="block relative group/link flex flex-col gap-0 shrink-0"
        href="<?php echo esc_url($post_permalink); ?>"
        aria-label="Xem chi tiết dự án <?php echo esc_attr($post_title); ?>"
        title="Chi tiết dự án <?php echo esc_attr($post_title); ?>">

        <!-- Main Image with Subtle Hover Zoom -->
        <div class="w-full aspect-[16/10] overflow-hidden bg-stone relative shrink-0">
            <?php
            if ($main_image_id) {
                if (function_exists('bds_get_optimized_image')) {
                    echo bds_get_optimized_image($main_image_id, 'large', ['class' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105'], true);
                } else {
                    echo wp_get_attachment_image($main_image_id, 'large', false, ['class' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105']);
                }
            } else {
                echo '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/placeholder-real-estate.jpg') . '" alt="' . esc_attr($post_title) . '" class="absolute inset-0 w-full h-full object-cover">';
            }
            ?>
            <div class="absolute inset-0 bg-gradient-to-t from-charcoal/40 via-transparent to-transparent opacity-60 group-hover:opacity-40 transition-opacity duration-300 pointer-events-none"></div>

            <!-- Apple Pill Status Badges -->
            <div class="absolute top-3 left-3 z-10 flex flex-col gap-1.5 items-start pointer-events-none">
                <?php if ($trang_thai_label) : ?>
                    <span class="px-3 py-1 rounded-full text-[11px] font-medium tracking-normal <?php echo esc_attr($trang_thai_class); ?>"><?php echo esc_html($trang_thai_label); ?></span>
                <?php endif; ?>
                <?php if ($featured) : ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-gold text-white shadow-sm">
                        <svg class="w-2.5 h-2.5 fill-white" viewBox="0 0 24 24"><path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5-5.9-3.1-5.9 3.1 1.2-6.5-4.8-4.6 6.6-.9 2.9-6z"/></svg>
                        <span>Nổi bật</span>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($is_subdivision && !empty($du_an_cha)) : ?>
                <div class="absolute top-3 right-3 z-10 pointer-events-none">
                    <span class="px-2.5 py-1 bg-black/60 text-white backdrop-blur-md text-[10px] font-medium rounded-full">Phân khu</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Thumbnails Gallery -->
        <?php if (!empty($thumbnails)) : ?>
            <div class="grid grid-cols-3 gap-1.5 p-2 pb-0 bg-surface shrink-0">
                <?php foreach ($thumbnails as $index => $thumb) : ?>
                    <?php
                    $thumb_id = is_array($thumb) ? (int) ($thumb['ID'] ?? $thumb['id'] ?? 0) : (int) $thumb;
                    if (!$thumb_id) continue;
                    ?>
                    <div class="aspect-[4/3] overflow-hidden bg-stone rounded-lg relative group/thumb">
                        <?php
                        if (function_exists('bds_get_optimized_image')) {
                            echo bds_get_optimized_image($thumb_id, 'thumbnail', ['class' => 'w-full h-full object-cover transition-transform duration-300 group-hover/thumb:scale-105'], true);
                        } else {
                            echo wp_get_attachment_image($thumb_id, 'thumbnail', false, ['class' => 'w-full h-full object-cover transition-transform duration-300 group-hover/thumb:scale-105']);
                        }
                        ?>
                        <?php if ($index === 2 && count($gallery_raw) > 3) : ?>
                            <div class="absolute inset-0 bg-charcoal/70 backdrop-blur-[2px] flex items-center justify-center text-white font-medium text-xs tracking-wide">
                                <span>+<?php echo esc_html(count($gallery_raw) - 3); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </a>

    <!-- Content / Metadata Body -->
    <div class="p-4 sm:p-5 flex flex-col flex-1 justify-between gap-3 bg-surface">
        <div class="flex-1 min-w-0 flex flex-col">
            <?php if (!empty($gia_ban_du_kien)) : ?>
                <div class="text-primary font-bold text-lg tracking-tight mb-1">
                    <?php echo esc_html($gia_ban_du_kien); ?>
                </div>
            <?php endif; ?>

            <h3 class="font-semibold text-base text-charcoal leading-snug m-0 mb-2 line-clamp-2 min-h-[2.75rem]">
                <a href="<?php echo esc_url($post_permalink); ?>" class="hover:text-primary transition-colors duration-200"><?php echo esc_html($post_title); ?></a>
            </h3>

            <?php if ($is_subdivision && !empty($du_an_cha)) : ?>
                <div class="flex items-center gap-1.5 text-xs text-charcoal-muted mb-2">
                    <span>Thuộc dự án:</span>
                    <a href="<?php echo esc_url(get_permalink($du_an_cha->ID)); ?>" class="font-medium text-primary hover:underline"><?php echo esc_html($du_an_cha->post_title); ?></a>
                </div>
            <?php endif; ?>

            <?php if ($address) : ?>
                <div class="flex items-center gap-1.5 text-charcoal-muted text-xs mb-1.5 min-h-[1.25rem]">
                    <span aria-hidden="true" class="inline-flex text-charcoal-muted shrink-0">
                        <?php echo batdongsan_icon('location', ['width' => 13, 'height' => 13]); ?>
                    </span>
                    <span class="line-clamp-1"><?php echo esc_html($address); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($quy_mo) : ?>
                <div class="flex items-center gap-1.5 text-charcoal-muted text-xs min-h-[1.25rem]">
                    <span aria-hidden="true" class="inline-flex text-charcoal-muted shrink-0">
                        <?php echo batdongsan_icon('building', ['width' => 13, 'height' => 13]); ?>
                    </span>
                    <span class="line-clamp-1">Quy mô: <?php echo esc_html($quy_mo); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer Row: Partner Logo / Investor & Action Link -->
        <div class="flex items-center justify-between gap-3 pt-3 border-t border-border/60 mt-auto shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                <?php if ($investor_logo_id) : ?>
                    <div class="w-8 h-8 rounded-lg overflow-hidden bg-white shrink-0 border border-border p-0.5 flex items-center justify-center">
                        <?php
                        if (function_exists('bds_get_optimized_image')) {
                            echo bds_get_optimized_image($investor_logo_id, 'thumbnail', ['class' => 'max-w-full max-h-full object-contain', 'alt' => esc_attr($chu_dau_tu)], true);
                        } else {
                            echo wp_get_attachment_image($investor_logo_id, 'thumbnail', false, ['class' => 'max-w-full max-h-full object-contain', 'alt' => esc_attr($chu_dau_tu)]);
                        }
                        ?>
                    </div>
                <?php endif; ?>
                <?php if ($chu_dau_tu) : ?>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] text-charcoal-muted">Chủ đầu tư</span>
                        <span class="text-xs font-medium text-charcoal truncate"><?php echo esc_html($chu_dau_tu); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <a class="inline-flex items-center gap-1 text-primary text-xs font-semibold hover:underline shrink-0" href="<?php echo esc_url($post_permalink); ?>">
                Chi tiết <span aria-hidden="true">→</span>
            </a>
        </div>
    </div>
</article>