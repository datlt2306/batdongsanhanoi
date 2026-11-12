<?php

/**
 * Card Project Horizontal Template Part - Luxury Real Estate Redesign
 *
 * Card dự án layout ngang (ảnh trái, nội dung phải) phong cách kiến trúc luxury
 * (porcelain surface, diffuse elevation, champagne bronze price & badges, memoized partner logo)
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
$post_excerpt = get_the_excerpt($post_id);
$custom_class = $args['class'] ?? '';

// Lấy dữ liệu ACF (đệ quy từ cha/ông nếu là phân khu)
$du_an_cha = get_field('du_an_cha', $post_id);
$is_subdivision = !empty($du_an_cha);

$trang_thai = get_field('trang_thai', $post_id);
$featured = get_field('featured', $post_id);
$gallery_raw = batdongsan_get_project_meta_recursive('gallery_du_an', $post_id);

// Thừa hưởng dữ liệu ĐỆ QUY từ cha/ông
$address = batdongsan_get_project_meta_recursive('address', $post_id);
$chu_dau_tu = batdongsan_get_project_meta_recursive('chu_dau_tu', $post_id);
$quy_mo = batdongsan_get_project_meta_recursive('quy_mo', $post_id);

// Giá dự án (ưu tiên gia_ban_du_kien, fallback price)
$gia_ban_du_kien = batdongsan_get_project_meta_recursive('gia_ban_du_kien', $post_id);
if (!$gia_ban_du_kien) {
    $price_val = batdongsan_get_project_meta_recursive('price', $post_id);
    if ($price_val) {
        $price_str = trim((string) $price_val);
        if (preg_match('/cập nhật|liên hệ|thỏa thuận|chưa có/iu', $price_str)) {
            $gia_ban_du_kien = 'Liên hệ báo giá';
        } elseif (is_numeric(str_replace([',', ' '], ['.', ''], $price_str))) {
            $gia_ban_du_kien = 'Từ ' . $price_str . ' tỷ';
        } elseif (preg_match('/tỷ|triệu|m2|m²/iu', $price_str)) {
            $gia_ban_du_kien = preg_match('/^(từ|chỉ từ|khoảng)/iu', $price_str) ? $price_str : 'Từ ' . $price_str;
        } else {
            $gia_ban_du_kien = $price_str;
        }
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
$gallery_count = 0;
if (!empty($gallery_raw) && is_array($gallery_raw)) {
    $gallery_count = count($gallery_raw);
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

<?php
$card_featured_classes = $featured
    ? "relative bg-gradient-to-br from-amber-500/[0.03] via-surface to-surface border-amber-400/40 shadow-[0_4px_24px_rgba(217,119,6,0.08)] ring-1 ring-amber-400/25 hover:border-amber-500/60 hover:shadow-[0_8px_30px_rgba(217,119,6,0.15)]"
    : "bg-surface border-black/[0.06] shadow-card hover:shadow-card-hover";
?>
<article class="group <?php echo esc_attr($card_featured_classes); ?> rounded-2xl overflow-hidden border hover:scale-[1.005] transition-all duration-300 flex flex-col md:flex-row items-stretch <?php echo esc_attr($custom_class); ?>" <?php if ($featured) echo "data-featured=\"true\""; ?>>
    <?php if ($featured) : ?>
        <!-- Luxury Top Accent Bar for Featured Project -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 via-amber-400 to-amber-600 z-20 pointer-events-none"></div>
    <?php endif; ?>
    <!-- Phần ảnh bên trái -->
    <div class="w-full md:w-[340px] shrink-0 flex flex-col">
        <a
            class="block relative h-full flex flex-col group/link"
            href="<?php echo esc_url($post_permalink); ?>"
            aria-label="Chi tiết dự án <?php echo esc_attr($post_title); ?>"
            title="Chi tiết dự án <?php echo esc_attr($post_title); ?>">

            <!-- Main Image -->
            <div class="h-[220px] md:h-auto md:flex-1 min-h-[200px] overflow-hidden bg-stone relative">
                <?php
                if ($main_image_id) {
                    if (function_exists('bds_get_optimized_image')) {
                        echo bds_get_optimized_image($main_image_id, 'large', ['class' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105'], true);
                    } elseif (function_exists('batdongsan_get_optimized_image')) {
                        echo batdongsan_get_optimized_image($main_image_id, 'large', ['class' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105'], true);
                    } else {
                        echo wp_get_attachment_image($main_image_id, 'large', false, ['class' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105']);
                    }
                } else {
                    echo '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/placeholder-real-estate.jpg') . '" alt="' . esc_attr($post_title) . '" class="absolute inset-0 w-full h-full object-cover">';
                }
                ?>
                <div class="absolute inset-0 bg-gradient-to-t from-charcoal/50 via-charcoal/10 to-transparent opacity-60 group-hover:opacity-30 transition-opacity duration-300 pointer-events-none"></div>

                <!-- Status Badge Overlay -->
                <div class="absolute top-3 left-3 z-10 flex flex-col gap-1.5 items-start pointer-events-none">
                    <?php if ($featured) : ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600 text-white shadow-md border border-amber-300/40 backdrop-blur-sm">
                            <svg class="w-3 h-3 fill-amber-200 animate-pulse" viewBox="0 0 24 24"><path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5-5.9-3.1-5.9 3.1 1.2-6.5-4.8-4.6 6.6-.9 2.9-6z"/></svg>
                            <span>Dự án nổi bật</span>
                        </span>
                    <?php endif; ?>
                    <?php if ($trang_thai_label) : ?>
                        <span class="px-3 py-1 rounded-full text-[11px] font-medium tracking-normal <?php echo esc_attr($trang_thai_class); ?>">
                            <?php echo esc_html($trang_thai_label); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($is_subdivision && !empty($du_an_cha)) : ?>
                    <div class="absolute top-3 right-3 z-10 pointer-events-none">
                        <span class="px-2 py-1 bg-charcoal/80 text-white backdrop-blur-sm text-[10px] font-semibold tracking-wider rounded-md uppercase">Phân khu</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Thumbnails Gallery -->
            <?php if (!empty($thumbnails)) : ?>
                <div class="hidden md:grid grid-cols-3 gap-1 p-1 bg-surface border-t border-border/60">
                    <?php foreach ($thumbnails as $index => $thumb) :
                        $thumb_id = is_array($thumb) ? (int) ($thumb['ID'] ?? $thumb['id'] ?? 0) : (int) $thumb;
                        if (!$thumb_id) continue;
                    ?>
                        <div class="h-[60px] overflow-hidden bg-stone rounded-sm relative group/thumb">
                            <?php
                            if (function_exists('bds_get_optimized_image')) {
                                echo bds_get_optimized_image($thumb_id, 'thumbnail', ['class' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover/thumb:scale-105'], true);
                            } elseif (function_exists('batdongsan_get_optimized_image')) {
                                echo batdongsan_get_optimized_image($thumb_id, 'thumbnail', ['class' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover/thumb:scale-105'], true);
                            } else {
                                echo wp_get_attachment_image($thumb_id, 'thumbnail', false, ['class' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover/thumb:scale-105']);
                            }
                            ?>
                            <?php if ($index === count($thumbnails) - 1 && $gallery_count > 3) : ?>
                                <div class="absolute inset-0 bg-charcoal/70 backdrop-blur-[2px] flex items-center justify-center gap-1 text-white text-xs font-semibold">
                                    <span>+<?php echo esc_html($gallery_count - 3); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </a>
    </div>

    <!-- Phần nội dung bên phải -->
    <div class="flex-1 p-5 md:p-6 flex flex-col justify-between gap-3.5 bg-surface">
        <div class="flex flex-col gap-2">
            <!-- Top Meta Row: Price & Featured Inline Indicator -->
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <?php if (!empty($gia_ban_du_kien)) : ?>
                    <div class="text-primary font-bold text-lg md:text-xl tracking-tight">
                        <?php echo esc_html($gia_ban_du_kien); ?>
                    </div>
                <?php endif; ?>
                <?php if ($featured) : ?>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-700 text-xs font-semibold">
                        <svg class="w-3.5 h-3.5 fill-amber-500" viewBox="0 0 24 24"><path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5-5.9-3.1-5.9 3.1 1.2-6.5-4.8-4.6 6.6-.9 2.9-6z"/></svg>
                        <span>Dự án tâm điểm</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Title -->
            <h3 class="font-semibold text-lg md:text-xl text-charcoal leading-snug m-0">
                <a href="<?php echo esc_url($post_permalink); ?>" class="hover:text-primary transition-colors duration-200"><?php echo esc_html($post_title); ?></a>
            </h3>

            <!-- Subdivision Parent Link -->
            <?php if ($is_subdivision && !empty($du_an_cha)) : ?>
                <div class="flex items-center gap-1.5 text-xs text-charcoal-muted">
                    <span>Thuộc dự án:</span>
                    <a href="<?php echo esc_url(get_permalink($du_an_cha->ID)); ?>" class="font-semibold text-primary hover:underline"><?php echo esc_html($du_an_cha->post_title); ?></a>
                </div>
            <?php endif; ?>

            <!-- Location -->
            <?php if ($address) : ?>
                <div class="flex items-center gap-2 text-charcoal-muted text-xs md:text-sm">
                    <span aria-hidden="true" class="inline-flex text-primary shrink-0">
                        <?php echo batdongsan_icon('location', ['width' => 15, 'height' => 15]); ?>
                    </span>
                    <span class="line-clamp-1"><?php echo esc_html($address); ?></span>
                </div>
            <?php endif; ?>

            <!-- Scale / Quy mô -->
            <?php if ($quy_mo) : ?>
                <div class="flex items-center gap-2 text-charcoal-muted text-xs md:text-sm">
                    <span aria-hidden="true" class="inline-flex text-primary/80 shrink-0">
                        <?php echo batdongsan_icon('building', ['width' => 15, 'height' => 15]); ?>
                    </span>
                    <span class="line-clamp-1">Quy mô: <?php echo esc_html($quy_mo); ?></span>
                </div>
            <?php endif; ?>

            <!-- Excerpt -->
            <?php if ($post_excerpt) : ?>
                <div class="text-charcoal-muted text-xs md:text-sm leading-relaxed line-clamp-2 mt-1">
                    <?php echo wp_trim_words($post_excerpt, 32, '...'); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer Row: Developer Logo & CTA Link -->
        <div class="flex items-center justify-between gap-3 pt-3.5 border-t border-border/80 mt-auto">
            <div class="flex items-center gap-2.5 min-w-0">
                <?php if ($investor_logo_id) : ?>
                    <div class="w-9 h-9 rounded-md overflow-hidden bg-white shrink-0 border border-border/80 p-0.5 flex items-center justify-center shadow-subtle">
                        <?php
                        if (function_exists('bds_get_optimized_image')) {
                            echo bds_get_optimized_image($investor_logo_id, 'thumbnail', ['class' => 'max-w-full max-h-full object-contain', 'alt' => esc_attr($chu_dau_tu)], true);
                        } elseif (function_exists('batdongsan_get_optimized_image')) {
                            echo batdongsan_get_optimized_image($investor_logo_id, 'thumbnail', ['class' => 'max-w-full max-h-full object-contain', 'alt' => esc_attr($chu_dau_tu)], true);
                        } else {
                            echo wp_get_attachment_image($investor_logo_id, 'thumbnail', false, ['class' => 'max-w-full max-h-full object-contain', 'alt' => esc_attr($chu_dau_tu)]);
                        }
                        ?>
                    </div>
                <?php endif; ?>
                <?php if ($chu_dau_tu) : ?>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] text-charcoal-muted uppercase tracking-wider">Chủ đầu tư</span>
                        <span class="text-xs font-semibold text-charcoal truncate"><?php echo esc_html($chu_dau_tu); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <a class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-stone/50 hover:bg-primary hover:text-white text-charcoal text-xs font-semibold border border-border hover:border-primary transition-all duration-200 shadow-sm shrink-0 no-underline" href="<?php echo esc_url($post_permalink); ?>">
                <span>Chi tiết</span>
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </div>
</article>
