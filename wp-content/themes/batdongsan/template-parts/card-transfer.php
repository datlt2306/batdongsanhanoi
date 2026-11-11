<?php

/**
 * Card Transfer (Bất động sản) Template Part - Luxury Real Estate Redesign
 *
 * Card bất động sản dùng chung cho trang chủ và các danh sách listing
 *
 * @package BatDongSan
 *
 * @param array $args {
 *     @type WP_Post|int $post       Post object hoặc post ID (required)
 *     @type string      $class      Class bổ sung (optional)
 *     @type bool        $show_badge Hiển thị badge nổi bật (default: true)
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
$show_badge = $args['show_badge'] ?? true;

// Lấy dữ liệu ACF
$dia_chi = get_field('dia_chi', $post_id);
$khoang_gia = get_field('khoang_gia', $post_id);
$dien_tich = get_field('dien_tich', $post_id);
$gia_tren_met = get_field('gia_tren_met_vuong', $post_id);
$du_an_id = function_exists('batdongsan_get_related_project_id')
    ? batdongsan_get_related_project_id($post_id)
    : absint(get_post_meta($post_id, 'du_an_lien_quan', true));
$gallery_nha_dat = get_field('gallery_nha_dat', $post_id);

// Thông số kỹ thuật bổ sung
$so_phong_ngu = get_field('so_phong_ngu', $post_id);
$so_phong_ve_sinh = get_field('so_phong_ve_sinh', $post_id);
$phap_ly = get_field('phap_ly', $post_id);
$phap_ly_labels = [
    'so_do'                     => 'Sổ đỏ',
    'so_hong'                    => 'Sổ hồng',
    'so_do_so_hong'              => 'Sổ đỏ/hồng',
    'dang_ky_quyen_su_dung_dat' => 'Đang làm sổ',
    'giay_chung_nhan'           => 'GCN',
    'khac'                       => 'Pháp lý khác',
];
$phap_ly_label = isset($phap_ly_labels[$phap_ly]) ? $phap_ly_labels[$phap_ly] : '';

// Lấy ảnh từ gallery hoặc featured image
$image_url = '';
$image_id = null;

if ($gallery_nha_dat && is_array($gallery_nha_dat) && !empty($gallery_nha_dat)) {
    $first_image = $gallery_nha_dat[0];
    $image_id = is_array($first_image) && isset($first_image['ID']) ? (int) $first_image['ID'] : (is_numeric($first_image) ? (int) $first_image : 0);
    if ($image_id > 0) {
        $image_url = wp_get_attachment_image_url($image_id, 'bds-medium');
    }
}

if (empty($image_url) && has_post_thumbnail($post_id)) {
    $image_id = get_post_thumbnail_id($post_id);
    $image_url = get_the_post_thumbnail_url($post_id, 'bds-medium');
}

if (empty($image_url)) {
    $image_url = get_template_directory_uri() . '/assets/images/placeholder-real-estate-2.jpg';
}

// Format giá và địa chỉ
$display_price = $khoang_gia ?: 'Giá liên hệ';
$display_location = $dia_chi ?: 'Địa chỉ đang cập nhật';

// Include icons library
if (!function_exists('batdongsan_icon')) {
    require_once get_template_directory() . '/template-parts/svg-icons.php';
}
?>

<article class="group bg-surface rounded-2xl overflow-hidden relative border border-black/[0.06] shadow-card transition-all duration-300 ease-out hover:scale-[1.01] hover:shadow-card-hover flex flex-col h-full <?php echo esc_attr($custom_class); ?>">
    <div class="block aspect-[4/3] bg-stone relative overflow-hidden shrink-0">
        <?php if ($show_badge) : ?>
            <span class="absolute top-2.5 left-2.5 z-10 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold text-white bg-gold shadow-sm tracking-normal">
                <svg class="w-2.5 h-2.5 fill-white" viewBox="0 0 24 24"><path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5-5.9-3.1-5.9 3.1 1.2-6.5-4.8-4.6 6.6-.9 2.9-6z"/></svg>
                <span>Nổi bật</span>
            </span>
        <?php endif; ?>

        <a href="<?php echo esc_url($post_permalink); ?>" aria-label="Xem chi tiết <?php echo esc_attr($post_title); ?>" class="block w-full h-full">
            <?php if ($image_id && function_exists('bds_get_optimized_image')) : ?>
                <?php echo bds_get_optimized_image($image_id, 'bds-medium', ['class' => 'w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105', 'alt' => esc_attr($post_title)], true); ?>
            <?php else : ?>
                <img
                    src="<?php echo esc_url($image_url); ?>"
                    alt="<?php echo esc_attr($post_title); ?>"
                    loading="lazy"
                    decoding="async"
                    class="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105">
            <?php endif; ?>
        </a>
        <div class="absolute inset-0 bg-gradient-to-t from-charcoal/40 via-transparent to-transparent opacity-0 group-hover:opacity-20 transition-opacity duration-300 pointer-events-none"></div>
    </div>

    <div class="p-4 flex flex-col flex-1 gap-2.5">
        <div class="flex items-baseline justify-between gap-2">
            <span class="font-bold text-primary text-lg tracking-tight"><?php echo esc_html($display_price); ?></span>
            <?php if ($gia_tren_met) : ?>
                <span class="text-xs text-charcoal-muted">~<?php echo esc_html($gia_tren_met); ?></span>
            <?php endif; ?>
        </div>

        <h3 class="font-semibold text-base text-charcoal leading-snug m-0 line-clamp-2 min-h-[2.75rem]">
            <a href="<?php echo esc_url($post_permalink); ?>" class="hover:text-primary transition-colors duration-200"><?php echo esc_html($post_title); ?></a>
        </h3>

        <div class="flex items-center gap-1.5 text-charcoal-muted text-xs min-w-0">
            <span aria-hidden="true" class="inline-flex text-charcoal-muted shrink-0">
                <?php echo batdongsan_icon('location', ['width' => 12, 'height' => 12]); ?>
            </span>
            <span class="truncate block flex-1 min-w-0"><?php echo esc_html($display_location); ?></span>
        </div>

        <!-- Specs Badges Row (Apple Pills) -->
        <div class="flex items-center flex-wrap gap-1.5 text-xs text-charcoal-muted pt-1">
            <?php if ($dien_tich) : ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-stone/50 border border-black/[0.04] text-charcoal/80 font-medium">
                    <?php echo esc_html($dien_tich); ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($so_phong_ngu) && $so_phong_ngu !== '0') : ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-stone/50 border border-black/[0.04] text-charcoal/80">
                    <span class="text-primary"><?php echo batdongsan_icon('bed', ['width' => 11, 'height' => 11]); ?></span>
                    <?php echo esc_html($so_phong_ngu); ?> PN
                </span>
            <?php endif; ?>

            <?php if (!empty($so_phong_ve_sinh) && $so_phong_ve_sinh !== '0') : ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-stone/50 border border-black/[0.04] text-charcoal/80">
                    <span class="text-primary"><?php echo batdongsan_icon('bath', ['width' => 11, 'height' => 11]); ?></span>
                    <?php echo esc_html($so_phong_ve_sinh); ?> WC
                </span>
            <?php endif; ?>

            <?php if ($phap_ly_label) : ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-stone/70 border border-black/[0.04] text-[11px] text-charcoal/80">
                    <?php echo esc_html($phap_ly_label); ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-between pt-2.5 mt-auto border-t border-border/60 text-xs text-charcoal-muted">
            <span>
                <?php echo batdongsan_icon('calendar', ['width' => 12, 'height' => 12, 'class' => 'inline-block align-middle mr-1 text-charcoal-muted']); ?>
                <?php echo esc_html(get_the_date('d/m/Y', $post_id)); ?>
            </span>
            <a class="inline-flex items-center gap-1 text-primary font-semibold text-xs no-underline hover:underline transition-all" href="<?php echo esc_url($post_permalink); ?>">
                Chi tiết <span aria-hidden="true">→</span>
            </a>
        </div>
    </div>
</article>