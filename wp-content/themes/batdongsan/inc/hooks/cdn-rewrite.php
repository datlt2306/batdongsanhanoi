<?php
/**
 * Cloudflare R2 CDN Rewriter & Output Buffer
 *
 * Automatically offloads theme static images and upload URLs to Cloudflare R2 CDN.
 * Equipped with a master switch (BATDONGSAN_ENABLE_R2_CDN) to allow safe local development.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BATDONGSAN_ENABLE_R2_CDN')) {
    define('BATDONGSAN_ENABLE_R2_CDN', false);
}

if (!defined('BATDONGSAN_R2_CDN_URL')) {
    define('BATDONGSAN_R2_CDN_URL', 'https://pub-df1b5df747f143958eeaaea51f7b6282.r2.dev');
}

/**
 * Check if R2/ImageKit CDN rewriting is currently enabled
 */
function batdongsan_is_r2_cdn_enabled(): bool {
    $enabled = (defined('BATDONGSAN_ENABLE_R2_CDN') && BATDONGSAN_ENABLE_R2_CDN)
        || (defined('BATDONGSAN_IMAGEKIT_URL') && !empty(BATDONGSAN_IMAGEKIT_URL));
    return (bool) apply_filters('batdongsan_enable_r2_cdn', $enabled);
}

/**
 * Get normalized CDN domain (prefers ImageKit if defined, fallback to Cloudflare R2)
 */
function batdongsan_get_r2_cdn_url(): string {
    $default_url = defined('BATDONGSAN_IMAGEKIT_URL') && !empty(BATDONGSAN_IMAGEKIT_URL)
        ? BATDONGSAN_IMAGEKIT_URL
        : BATDONGSAN_R2_CDN_URL;
    return untrailingslashit(apply_filters('batdongsan_r2_cdn_url', $default_url));
}

/**
 * Helper to get CDN URL for theme static image
 */
function batdongsan_theme_image_url(string $file): string {
    if (!batdongsan_is_r2_cdn_enabled()) {
        return get_template_directory_uri() . '/assets/images/' . ltrim($file, '/');
    }
    $cdn = batdongsan_get_r2_cdn_url();
    return $cdn . '/assets/images/' . ltrim($file, '/');
}

/**
 * Filter theme_file_uri for assets/images
 */
add_filter('theme_file_uri', function (string $url, string $file): string {
    if (!batdongsan_is_r2_cdn_enabled()) {
        return $url;
    }
    if (strpos($file, 'assets/images/') === 0) {
        $cdn = batdongsan_get_r2_cdn_url();
        return $cdn . '/' . ltrim($file, '/');
    }
    return $url;
}, 10, 2);

/**
 * Output buffer rewriter to rewrite all theme images and uploads to Cloudflare R2 CDN
 */
function batdongsan_r2_output_buffer_rewrite(string $html): string {
    if (!batdongsan_is_r2_cdn_enabled() || empty($html) || !is_string($html)) {
        return $html;
    }

    $cdn_url = batdongsan_get_r2_cdn_url();
    $cdn_json = str_replace('/', '\\/', $cdn_url);

    $theme_slug = get_template();
    $site_url = untrailingslashit(site_url());
    $home_url = untrailingslashit(home_url());
    $prod_url = 'https://batdongsanhanoi.net.vn';

    // Build list of target origins to replace
    $origins = array_unique(array_filter([
        $site_url,
        $home_url,
        $prod_url,
        'http://batdongsanhanoi.local',
        'https://batdongsanhanoi.local',
    ]));

    $search = [];
    $replace = [];

    // 1. Theme assets/images rewrites
    foreach ($origins as $origin) {
        $theme_base = $origin . '/wp-content/themes/' . $theme_slug . '/assets/images';
        $search[] = $theme_base;
        $replace[] = $cdn_url . '/assets/images';

        // JSON-escaped variant (e.g., in inline scripts / AJAX responses)
        $escaped_base = str_replace('/', '\\/', $theme_base);
        $search[] = $escaped_base;
        $replace[] = $cdn_json . '\\/assets\\/images';
    }

    // 2. Uploads rewrites (offloaded files are stored at bucket root without /uploads prefix)
    foreach ($origins as $origin) {
        $upload_base = $origin . '/wp-content/uploads';
        $search[] = $upload_base;
        $replace[] = $cdn_url;

        // JSON-escaped variant (e.g., in inline scripts / AJAX responses)
        $escaped_upload = str_replace('/', '\\/', $upload_base);
        $search[] = $escaped_upload;
        $replace[] = $cdn_json;
    }

    // Root-relative fallbacks
    $search[] = '/wp-content/themes/' . $theme_slug . '/assets/images';
    $replace[] = $cdn_url . '/assets/images';
    $search[] = '/wp-content/uploads';
    $replace[] = $cdn_url;

    // 3. Rewrite direct R2 URLs to ImageKit if ImageKit is active
    if (defined('BATDONGSAN_IMAGEKIT_URL') && !empty(BATDONGSAN_IMAGEKIT_URL) && defined('BATDONGSAN_R2_CDN_URL')) {
        $r2_base = untrailingslashit(BATDONGSAN_R2_CDN_URL);
        if ($r2_base !== $cdn_url) {
            $search[] = $r2_base;
            $replace[] = $cdn_url;
            $search[] = str_replace('/', '\\/', $r2_base);
            $replace[] = $cdn_json;
        }
    }

    return str_replace($search, $replace, $html);
}

/**
 * Start output buffering on front-end requests only if CDN mode is active
 */
add_action('template_redirect', function (): void {
    if (!batdongsan_is_r2_cdn_enabled()) {
        return;
    }
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    ob_start('batdongsan_r2_output_buffer_rewrite');
}, 1);

/**
 * Flush buffer on shutdown if active
 */
add_action('shutdown', function (): void {
    if (ob_get_level() > 0 && !is_admin()) {
        $handlers = ob_list_handlers();
        if (in_array('batdongsan_r2_output_buffer_rewrite', $handlers, true)) {
            @ob_end_flush();
        }
    }
}, 0);
