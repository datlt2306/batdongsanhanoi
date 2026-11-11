<?php

/**
 * Bootstraps BatDongSan theme.
 */

if (!defined('ABSPATH')) {
    exit;
}

const BDS_VERSION = '1.0.4';

// Load theme files
$theme_dir = get_template_directory();

// Template Parts - SVG Icons library
require_once $theme_dir . '/template-parts/svg-icons.php';

// Helpers
require_once $theme_dir . '/inc/helpers/format.php';
require_once $theme_dir . '/inc/helpers/filter-slug.php';
require_once $theme_dir . '/inc/helpers/image.php';
require_once $theme_dir . '/inc/helpers/video.php';
require_once $theme_dir . '/inc/helpers/breadcrumb.php';
require_once $theme_dir . '/inc/helpers/advisor.php';
require_once $theme_dir . '/inc/helpers/project.php';
require_once $theme_dir . '/inc/helpers/location-amenities.php';
require_once $theme_dir . '/inc/helpers/filter-options.php';
require_once $theme_dir . '/inc/helpers/schema.php';

// Services
require_once $theme_dir . '/inc/services/price-ranges.php';
require_once $theme_dir . '/inc/services/queries.php';
require_once $theme_dir . '/inc/services/migration.php';
require_once $theme_dir . '/inc/services/filter/bootstrap.php';
require_once $theme_dir . '/inc/services/class-bds-performance.php';

// Hooks
require_once $theme_dir . '/inc/hooks/theme-setup.php';
require_once $theme_dir . '/inc/hooks/sidebars.php';
require_once $theme_dir . '/inc/hooks/assets.php';
require_once $theme_dir . '/inc/hooks/acf-filters.php';
require_once $theme_dir . '/inc/hooks/images.php';
require_once $theme_dir . '/inc/hooks/cdn-rewrite.php';
require_once $theme_dir . '/inc/hooks/taxonomies.php';
require_once $theme_dir . '/inc/hooks/ajax-project-filters.php';
require_once $theme_dir . '/inc/hooks/ajax-listings-filter.php';
require_once $theme_dir . '/inc/hooks/admin-columns.php';
require_once $theme_dir . '/inc/hooks/redirects.php';
require_once $theme_dir . '/inc/hooks/leads.php';
require_once $theme_dir . '/inc/hooks/seo.php';
require_once $theme_dir . '/inc/hooks/options-page.php';

// Backward compatibility: Giữ lại các function cũ với prefix bds_ để không break code hiện tại
// Các function này sẽ gọi function mới với prefix batdongsan_

/**
 * @deprecated Sử dụng batdongsan_get_price_ranges() thay thế
 */
function bds_get_price_ranges(): array {
    return batdongsan_get_price_ranges();
}

/**
 * @deprecated Sử dụng batdongsan_get_video_thumbnail() thay thế
 */
function bds_get_video_thumbnail(string $video_url): string {
    return batdongsan_get_video_thumbnail($video_url);
}

/**
 * @deprecated Sử dụng batdongsan_get_optimized_image() thay thế
 */
function bds_get_optimized_image($image_id, string $size = 'large', array $attr = [], bool $lazy_load = true): string {
    return batdongsan_get_optimized_image($image_id, $size, $attr, $lazy_load);
}

/**
 * Localize script với data cho advisor modal
 */
add_action('wp_enqueue_scripts', 'batdongsan_advisor_modal_data');
function batdongsan_advisor_modal_data(): void {
    if (!is_singular('du-an') && !is_singular('hang-chuyen-nhuong') && !is_singular('bat-dong-san') && !is_singular('nha-dat-ban')) {
        return;
    }

    $project_name = get_the_title();
    $project_logo = '';

    if (has_post_thumbnail()) {
        $project_logo = get_the_post_thumbnail_url(get_the_ID(), 'medium');
    }

    wp_localize_script('bds-main', 'bdsAdvisor', [
        'projectName' => $project_name,
        'projectLogo' => $project_logo,
        'policyUrl' => home_url('/chinh-sach-bao-mat'),
    ]);
}

/**
 * SECURITY HARDENING
 */

// 1. Remove WP Version
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');

// 2. Obfuscate login errors
add_filter('login_errors', function () {
    return 'Sai thông tin đăng nhập. Vui lòng kiểm tra lại.';
});

// 3. Disable XML-RPC logic (headers and functionality)
add_filter('xmlrpc_enabled', '__return_false');
add_filter('wp_headers', function ($headers) {
    unset($headers['X-Pingback']);
    return $headers;
});
