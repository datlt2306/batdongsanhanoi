<?php
/**
 * CLI Audit & Crawler Script for Cloudflare R2 Migration
 *
 * Usage:
 *   wp eval-file wp-content/themes/batdongsan/scripts/audit-r2-cdn.php
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$cdn_domain = defined('BATDONGSAN_IMAGEKIT_URL') && !empty(BATDONGSAN_IMAGEKIT_URL)
    ? parse_url(BATDONGSAN_IMAGEKIT_URL, PHP_URL_HOST)
    : 'pub-df1b5df747f143958eeaaea51f7b6282.r2.dev';

WP_CLI::line("===============================================================");
WP_CLI::line(" Cloudflare R2 CDN Migration Audit & URL Crawler");
WP_CLI::line(" CDN Domain: https://{$cdn_domain}");
WP_CLI::line("===============================================================");

// 1. Gather all public URLs
$urls = [];

// Front page & Blog
$urls[] = home_url('/');
if (get_option('page_for_posts')) {
    $urls[] = get_permalink(get_option('page_for_posts'));
}

// Pages
$pages = get_posts([
    'post_type' => 'page',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);
foreach ($pages as $p_id) {
    $urls[] = get_permalink($p_id);
}

// Posts
$posts = get_posts([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 10,
    'fields' => 'ids',
]);
foreach ($posts as $p_id) {
    $urls[] = get_permalink($p_id);
}

// Custom Post Types
$cpts = ['du-an', 'bat-dong-san', 'hang-chuyen-nhuong'];
foreach ($cpts as $cpt) {
    $cpt_posts = get_posts([
        'post_type' => $cpt,
        'post_status' => 'publish',
        'posts_per_page' => 15,
        'fields' => 'ids',
    ]);
    foreach ($cpt_posts as $c_id) {
        $urls[] = get_permalink($c_id);
    }
}

// Taxonomies
$taxonomies = ['category', 'loai-hinh', 'danh-muc-du-an', 'danh-muc-nha-dat-ban', 'danh-muc-hang-chuyen-nhuong'];
foreach ($taxonomies as $tax) {
    if (taxonomy_exists($tax)) {
        $terms = get_terms([
            'taxonomy' => $tax,
            'hide_empty' => true,
            'number' => 10,
        ]);
        if (!is_wp_error($terms) && is_array($terms)) {
            foreach ($terms as $term) {
                $urls[] = get_term_link($term);
            }
        }
    }
}

$urls = array_values(array_unique(array_filter($urls)));
$total_urls = count($urls);
WP_CLI::line("Discovered {$total_urls} unique public URLs to audit.");
WP_CLI::line("Starting crawl...");

$total_images_all = 0;
$total_r2_all = 0;
$total_local_uploads_all = 0;
$total_local_theme_all = 0;
$total_external_all = 0;

$failed_urls = [];
$local_image_samples = [];

$index = 0;
foreach ($urls as $url) {
    $index++;
    $response = wp_remote_get($url, [
        'timeout' => 15,
        'sslverify' => false,
        'headers' => [
            'User-Agent' => 'BatDongSan-R2-Auditor/1.0',
        ],
    ]);

    if (is_wp_error($response)) {
        WP_CLI::warning("[{$index}/{$total_urls}] Error fetching {$url}: " . $response->get_error_message());
        continue;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        WP_CLI::warning("[{$index}/{$total_urls}] HTTP {$code} for {$url}");
        continue;
    }

    $body = wp_remote_retrieve_body($response);

    // Extract images from src, srcset, data-src, background-image
    $found_images = [];

    // <img src="...">
    if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $body, $m)) {
        foreach ($m[1] as $src) {
            $found_images[] = $src;
        }
    }

    // data-src="..."
    if (preg_match_all('/<img[^>]+data-src=["\']([^"\']+)["\']/i', $body, $m)) {
        foreach ($m[1] as $src) {
            $found_images[] = $src;
        }
    }

    // srcset="..."
    if (preg_match_all('/srcset=["\']([^"\']+)["\']/i', $body, $m)) {
        foreach ($m[1] as $srcset) {
            $parts = explode(',', $srcset);
            foreach ($parts as $part) {
                $tokens = preg_split('/\s+/', trim($part));
                if (!empty($tokens[0])) {
                    $found_images[] = $tokens[0];
                }
            }
        }
    }

    // background-image: url(...)
    if (preg_match_all('/background(?:-image)?\s*:\s*url\((["\']?)([^)"\']+)\1\)/i', $body, $m)) {
        foreach ($m[2] as $bg) {
            if (strpos($bg, 'data:image') === false) {
                $found_images[] = $bg;
            }
        }
    }

    $found_images = array_unique($found_images);

    $page_r2 = 0;
    $page_local_uploads = 0;
    $page_local_theme = 0;
    $page_external = 0;

    foreach ($found_images as $img) {
        // Skip svg data URIs
        if (strpos($img, 'data:image') === 0) {
            continue;
        }

        if (strpos($img, $cdn_domain) !== false) {
            $page_r2++;
            $total_r2_all++;
        } elseif (strpos($img, '/wp-content/uploads') !== false) {
            $page_local_uploads++;
            $total_local_uploads_all++;
            $local_image_samples[] = "[Upload] {$img} on {$url}";
        } elseif (strpos($img, 'assets/images') !== false) {
            $page_local_theme++;
            $total_local_theme_all++;
            $local_image_samples[] = "[Theme] {$img} on {$url}";
        } else {
            $page_external++;
            $total_external_all++;
        }
        $total_images_all++;
    }

    $total_page_images = $page_r2 + $page_local_uploads + $page_local_theme;
    $page_compliance = $total_page_images > 0 ? round(($page_r2 / $total_page_images) * 100, 1) : 100;

    if ($page_local_uploads > 0 || $page_local_theme > 0) {
        WP_CLI::warning(sprintf(
            "[%d/%d] %s => %d images (R2: %d, Local Uploads: %d, Local Theme: %d) - %.1f%% CDN",
            $index,
            $total_urls,
            $url,
            $total_page_images,
            $page_r2,
            $page_local_uploads,
            $page_local_theme,
            $page_compliance
        ));
        $failed_urls[] = $url;
    } else {
        WP_CLI::line(sprintf(
            "[%d/%d] %s => %d images (100%% R2 CDN)",
            $index,
            $total_urls,
            $url,
            $page_r2
        ));
    }
}

WP_CLI::line("\n================ AUDIT SUMMARY ================");
WP_CLI::line("Total URLs Scanned:       {$total_urls}");
WP_CLI::line("Total Site Images:        " . ($total_r2_all + $total_local_uploads_all + $total_local_theme_all));
WP_CLI::line("Served via Cloudflare R2: {$total_r2_all}");
WP_CLI::line("Local Uploads Remaining:  {$total_local_uploads_all}");
WP_CLI::line("Local Theme Remaining:    {$total_local_theme_all}");
WP_CLI::line("External / Third-party:   {$total_external_all}");

$site_images = $total_r2_all + $total_local_uploads_all + $total_local_theme_all;
$compliance = $site_images > 0 ? round(($total_r2_all / $site_images) * 100, 2) : 100;

WP_CLI::line("-----------------------------------------------");
if ($compliance == 100 && $total_local_uploads_all === 0 && $total_local_theme_all === 0) {
    WP_CLI::success("R2 CDN COMPLIANCE: 100.0% - ZERO local images detected on website!");
} else {
    WP_CLI::error("R2 CDN COMPLIANCE: {$compliance}% ({$total_local_uploads_all} local uploads, {$total_local_theme_all} local theme images remaining).", false);
    if (!empty($local_image_samples)) {
        WP_CLI::line("\nSample remaining local images:");
        foreach (array_slice($local_image_samples, 0, 10) as $sample) {
            WP_CLI::line("  - {$sample}");
        }
    }
}

// 5. Purge Caches
WP_CLI::line("\nPurging Caches...");
wp_cache_flush();
if (function_exists('do_action')) {
    do_action('litespeed_purge_all');
}
WP_CLI::success("Object and Page Caches purged successfully.");
