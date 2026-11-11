<?php

/**
 * Assets hooks – load theo trang để tối ưu performance (Core Web Vitals).
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trang có gallery (lightGallery): chi tiết dự án, mặt bằng, chi tiết BĐS/hàng chuyển nhượng.
 */
function batdongsan_needs_lightgallery(): bool {
    return is_singular(['du-an', 'phan-khu', 'bat-dong-san', 'hang-chuyen-nhuong', 'nha-dat-ban'])
        || is_post_type_archive('bat-dong-san');
}

/**
 * Trang có slider (Swiper): trang chủ, page dự án, chi tiết dự án, chi tiết BĐS.
 */
function batdongsan_needs_swiper(): bool {
    return is_front_page()
        || is_page_template('page-du-an.php')
        || is_page_template('page-duan.php')
        || is_page('du-an')
        || is_page('du-an-bat-dong-san')
        || is_post_type_archive('du-an')
        || is_singular(['du-an', 'phan-khu', 'bat-dong-san', 'hang-chuyen-nhuong', 'nha-dat-ban']);
}

/**
 * Context trang để JS chỉ chạy init cần thiết (giảm TBT, tốt cho INP).
 *
 * @return string 'home'|'project-detail'|'nha-dat-detail'|'default'
 */
function batdongsan_get_page_context(): string {
    if (is_front_page() || is_page_template('page-du-an.php') || is_page_template('page-duan.php') || is_page('du-an') || is_page('du-an-bat-dong-san') || is_post_type_archive('du-an')) {
        return 'home';
    }
    if (is_singular(['du-an', 'phan-khu'])) {
        return 'project-detail';
    }
    if (is_singular(['bat-dong-san', 'hang-chuyen-nhuong', 'nha-dat-ban'])) {
        return 'nha-dat-detail';
    }
    if (is_page_template('page-bat-dong-san.php') || is_post_type_archive('bat-dong-san') || is_page('bat-dong-san')) {
        return 'bds';
    }
    return 'default';
}

/**
 * Resource hints: Preconnect to Google Fonts domains for performance.
 */
function batdongsan_google_fonts_resource_hints(array $urls, string $relation_type): array {
    if ('preconnect' === $relation_type) {
        $urls[] = [
            'href' => 'https://fonts.googleapis.com',
        ];
        $urls[] = [
            'href' => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous',
        ];
    }
    return $urls;
}
add_filter('wp_resource_hints', 'batdongsan_google_fonts_resource_hints', 10, 2);

/**
 * Direct preconnect link tags in <head> at early priority 1.
 */
function batdongsan_google_fonts_preconnect_head(): void {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";

    $uri = get_template_directory_uri();
    $dir = get_template_directory();
    if (file_exists($dir . '/assets/css/main.min.css')) {
        echo '<link rel="preload" as="style" href="' . esc_url($uri . '/assets/css/main.min.css?ver=' . BDS_VERSION) . '" fetchpriority="high">' . "\n";
    }
}
add_action('wp_head', 'batdongsan_google_fonts_preconnect_head', 1);

/**
 * Google Fonts đã có preconnect ở wp_head priority 1 và display=swap trong URL,
 * nên load trực tiếp thay vì dùng print/onload media swap để tránh FOUC gây chớp giật layout header.
 */

/**
 * Nạp assets: CSS luôn; JS (lightGallery, Swiper, main) load có điều kiện + defer.
 */
function batdongsan_enqueue_assets(): void {
    $dir = get_template_directory();
    $uri = get_template_directory_uri();
    $v   = file_exists($dir . '/assets/css/main.min.css') ? (string) filemtime($dir . '/assets/css/main.min.css') : BDS_VERSION;

    // --- Google Fonts with Vietnamese diacritics support (Optimized 400, 500, 600, 700) ---
    $google_fonts_url = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&subset=latin,vietnamese&display=swap';
    wp_enqueue_style('bds-google-fonts', $google_fonts_url, [], null);

    // --- Styles: Main minified CSS if available, fallback to individual files ---
    if (file_exists($dir . '/assets/css/main.min.css')) {
        wp_enqueue_style('bds-main-css', $uri . '/assets/css/main.min.css', ['bds-google-fonts'], $v);
    } else {
        wp_enqueue_style('bds-fonts', $uri . '/assets/css/fonts.css', ['bds-google-fonts'], $v);
        wp_enqueue_style('bds-style', get_stylesheet_uri(), ['bds-google-fonts', 'bds-fonts'], $v);
        wp_enqueue_style('bds-components', $uri . '/assets/css/components.css', ['bds-style'], $v);

        if (file_exists($dir . '/assets/css/tailwind.css')) {
            wp_enqueue_style('bds-tailwind', $uri . '/assets/css/tailwind.css', ['bds-components'], $v);
        }
    }

    $main_deps = ['jquery'];

    // --- lightGallery: chỉ trang có gallery ---
    if (batdongsan_needs_lightgallery()) {
        wp_enqueue_style('bds-lightgallery', $uri . '/assets/css/lightgallery-bundle.min.css', [], $v);

        wp_enqueue_script('bds-lightgallery', $uri . '/assets/js/lightgallery.min.js', ['jquery'], $v, true);
        wp_script_add_data('bds-lightgallery', 'defer', true);

        wp_enqueue_script('bds-lightgallery-zoom', $uri . '/assets/js/lg-zoom.min.js', ['jquery', 'bds-lightgallery'], $v, true);
        wp_script_add_data('bds-lightgallery-zoom', 'defer', true);

        wp_enqueue_script('bds-lightgallery-thumbnail', $uri . '/assets/js/lg-thumbnail.min.js', ['jquery', 'bds-lightgallery'], $v, true);
        wp_script_add_data('bds-lightgallery-thumbnail', 'defer', true);

        $main_deps[] = 'bds-lightgallery';
        $main_deps[] = 'bds-lightgallery-zoom';
        $main_deps[] = 'bds-lightgallery-thumbnail';
    }

    // --- Swiper: chỉ trang có slider ---
    if (batdongsan_needs_swiper()) {
        wp_enqueue_style('bds-swiper', $uri . '/assets/css/swiper-bundle.min.css', [], $v);

        wp_enqueue_script('bds-swiper', $uri . '/assets/js/swiper-bundle.min.js', [], $v, true);
        wp_script_add_data('bds-swiper', 'defer', true);

        $main_deps[] = 'bds-swiper';
    }

    // --- Comparison Slider (2D vs 3D Before/After) ---
    if (is_singular(['du-an', 'bat-dong-san']) || is_page_template('page-du-an.php')) {
        wp_enqueue_script('bds-comparison-slider', $uri . '/assets/js/comparison-slider.js', [], $v, true);
        wp_script_add_data('bds-comparison-slider', 'defer', true);
        $main_deps[] = 'bds-comparison-slider';
    }

    // --- Main JS: Bundle if available, fallback to unbundled ---
    $main_js_file = file_exists($dir . '/js/main.bundle.js') ? '/js/main.bundle.js' : '/js/main.js';
    wp_enqueue_script('bds-main', $uri . $main_js_file, $main_deps, $v, true);
    wp_script_add_data('bds-main', 'defer', true);
}
add_action('wp_enqueue_scripts', 'batdongsan_enqueue_assets');

/**
 * Thêm defer/async attributes cho các enqueued script tags để cải thiện Core Web Vitals (INP, LCP)
 */
function batdongsan_modify_script_tags(string $tag, string $handle): string {
    if (is_admin()) {
        return $tag;
    }
    // Defer non-critical scripts
    $defer_handles = ['bds-main', 'bds-swiper', 'bds-lightgallery', 'bds-lightgallery-zoom', 'bds-lightgallery-thumbnail', 'bds-comparison-slider'];
    if (in_array($handle, $defer_handles, true)) {
        if (strpos($tag, ' defer') === false) {
            $tag = str_replace(' src=', ' defer src=', $tag);
        }
    }
    return $tag;
}
add_filter('script_loader_tag', 'batdongsan_modify_script_tags', 10, 2);

/**
 * Truyền dữ liệu cho JS: homeUrl, ajaxUrl, nonce, pageContext.
 */
function batdongsan_localize_script(): void {
    $ajax_url = admin_url('admin-ajax.php');
    $lead_nonce = wp_create_nonce('bds_lead_nonce');

    wp_localize_script('bds-main', 'bdsTheme', [
        'homeUrl'        => esc_url(home_url('/')),
        'ajaxUrl'        => esc_url($ajax_url),
        'filterNonce'    => wp_create_nonce('batdongsan_filter_projects'),
        'bdsFilterNonce' => wp_create_nonce('bds_filter_nonce'),
        'bdsLeadNonce'   => $lead_nonce,
        'pageContext'    => batdongsan_get_page_context(),
    ]);

    wp_localize_script('bds-main', 'bds_vars', [
        'ajax_url'     => esc_url($ajax_url),
        'bdsLeadNonce' => $lead_nonce,
    ]);
}
add_action('wp_enqueue_scripts', 'batdongsan_localize_script', 20);
