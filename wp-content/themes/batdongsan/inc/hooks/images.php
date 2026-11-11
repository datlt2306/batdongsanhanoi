<?php
/**
 * Image hooks
 * 
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Thêm image sizes tối ưu
 */
function batdongsan_add_image_sizes(): void {
    // Hero banner size
    add_image_size('bds-hero', 1920, 800, true);
    // Card thumbnail size
    add_image_size('bds-card', 600, 400, true);
    // Medium optimized
    add_image_size('bds-medium', 768, 512, true);
}
add_action('after_setup_theme', 'batdongsan_add_image_sizes');

/**
 * Bật lazy loading mặc định cho tất cả hình ảnh
 * 
 * @param string $content Post content
 * @return string Content với lazy loading
 */
function batdongsan_add_lazy_loading_to_images(string $content): string {
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return $content;
    }

    // Thêm loading="lazy" cho các img không có thuộc tính loading
    $content = preg_replace_callback(
        '/<img([^>]*?)(?:\s+loading\s*=\s*["\'](?:lazy|eager)["\'])?([^>]*?)>/i',
        function ($matches) {
            $before_loading = $matches[1];
            $after_loading = $matches[2];

            // Nếu đã có loading attribute, giữ nguyên
            if (preg_match('/loading\s*=\s*["\'](?:lazy|eager)["\']/i', $matches[0])) {
                return $matches[0];
            }

            // Thêm loading="lazy" nếu chưa có
            return '<img' . $before_loading . ' loading="lazy"' . $after_loading . '>';
        },
        $content
    );

    return $content;
}
add_filter('the_content', 'batdongsan_add_lazy_loading_to_images', 10);

// LCP preload is now dynamically managed by BDS_Performance with responsive imagesrcset


