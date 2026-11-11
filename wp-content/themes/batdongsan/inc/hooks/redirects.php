<?php
/**
 * 301 SEO Redirect Engine for Legacy Custom Post Types and Taxonomies.
 *
 * Intercepts requests to legacy URLs (/hang-chuyen-nhuong/*, /nha-dat-ban/*,
 * /danh-muc-hang-chuyen-nhuong/*, /danh-muc-nha-dat-ban/*, /bds-cho-thue/*, etc.)
 * and permanently redirects (HTTP 301) to unified /bat-dong-san/* or rewrite endpoints,
 * preserving query arguments, pagination, and Unicode characters.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle 301 redirects for legacy URLs.
 */
function batdongsan_redirect_legacy_urls(): void {
    if ((function_exists('is_admin') && is_admin()) || (defined('DOING_AJAX') && DOING_AJAX) || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (empty($request_uri)) {
        return;
    }

    // Parse path and query string
    $uri_parts = explode('?', $request_uri, 2);
    $path = $uri_parts[0];
    $query = $uri_parts[1] ?? '';

    // Prevent redirect loop if already targeting bat-dong-san
    if ($path === '/bat-dong-san' || str_starts_with($path, '/bat-dong-san/')) {
        return;
    }

    // Decode URL-encoded Unicode characters safely for regex matching
    $decoded_path = rawurldecode($path);

    $target_url = null;

    // 1. Archive & Pagination: /hang-chuyen-nhuong/, /hangchuyenhuong/
    if (preg_match('#^/(?:hang-chuyen-nhuong|hangchuyenhuong)(?:/(page/\d+))?/?$#ui', $decoded_path, $m)) {
        $page_sub = !empty($m[1]) ? trim($m[1], '/') . '/' : '';
        $base = home_url('/bat-dong-san/' . $page_sub);
        $target_url = add_query_arg(['hinh_thuc' => 'chuyen-nhuong'], $base);
    }
    // 2. Archive & Pagination: /nha-dat-ban/
    elseif (preg_match('#^/nha-dat-ban(?:/(page/\d+))?/?$#ui', $decoded_path, $m)) {
        $page_sub = !empty($m[1]) ? trim($m[1], '/') . '/' : '';
        $target_url = home_url('/bat-dong-san/' . $page_sub);
    }
    // 3. Archive & Pagination: /bds-cho-thue/, /nha-dat-cho-thue/, /cho-thue/
    elseif (preg_match('#^/(?:bds-cho-thue|nha-dat-cho-thue|cho-thue)(?:/(page/\d+))?/?$#ui', $decoded_path, $m)) {
        $page_sub = !empty($m[1]) ? trim($m[1], '/') . '/' : '';
        $base = home_url('/bat-dong-san/' . $page_sub);
        $target_url = add_query_arg(['hinh_thuc' => 'cho-thue'], $base);
    }
    // 4. Legacy Taxonomy: /danh-muc-hang-chuyen-nhuong/{slug}/, /danh-muc-nha-dat-ban/{slug}/
    elseif (preg_match('#^/(?:danh-muc-hang-chuyen-nhuong|danh-muc-nha-dat-ban)/([^/]+)(?:/(page/\d+))?/?$#ui', $decoded_path, $m)) {
        $term_slug = $m[1];
        $page_sub = !empty($m[2]) ? trim($m[2], '/') . '/' : '';
        $target_url = home_url('/ban-' . rawurlencode($term_slug) . '/' . $page_sub);
    }
    // 5. Legacy Taxonomy: /danh-muc-nha-dat-cho-thue/{slug}/
    elseif (preg_match('#^/danh-muc-nha-dat-cho-thue/([^/]+)(?:/(page/\d+))?/?$#ui', $decoded_path, $m)) {
        $term_slug = $m[1];
        $target_url = add_query_arg([
            'hinh_thuc' => 'cho-thue',
            'loai_hinh' => $term_slug,
        ], home_url('/bat-dong-san/'));
    }
    // 6. Single Posts: /hang-chuyen-nhuong/{slug}/, /nha-dat-ban/{slug}/
    elseif (preg_match('#^/(?:hang-chuyen-nhuong|nha-dat-ban)/([^/]+)/?$#ui', $decoded_path, $m)) {
        $slug = $m[1];
        if ($slug !== 'page') {
            $target_url = home_url('/bat-dong-san/' . rawurlencode($slug) . '/');
        }
    }
    // 7. Single Posts: /bds-cho-thue/{slug}/, /nha-dat-cho-thue/{slug}/, /cho-thue/{slug}/
    elseif (preg_match('#^/(?:bds-cho-thue|nha-dat-cho-thue|cho-thue)/([^/]+)/?$#ui', $decoded_path, $m)) {
        $slug = $m[1];
        if ($slug !== 'page') {
            $target_url = home_url('/bat-dong-san/' . rawurlencode($slug) . '/');
        }
    }

    if ($target_url !== null) {
        // Preserve original query parameters
        if (!empty($query)) {
            $parsed_query = [];
            if (function_exists('wp_parse_str')) {
                wp_parse_str($query, $parsed_query);
            } else {
                parse_str($query, $parsed_query);
            }
            if (!empty($parsed_query)) {
                $target_url = add_query_arg($parsed_query, $target_url);
            }
        }

        wp_safe_redirect($target_url, 301);
        exit;
    }
}
add_action('template_redirect', 'batdongsan_redirect_legacy_urls', 1);
