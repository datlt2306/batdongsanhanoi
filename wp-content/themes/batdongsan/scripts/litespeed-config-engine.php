<?php
/**
 * LiteSpeed Cache Central Configuration Engine (CLI Tool)
 *
 * Usage:
 *   wp eval-file wp-content/themes/batdongsan/scripts/litespeed-config-engine.php
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

WP_CLI::line("===============================================================");
WP_CLI::line(" LiteSpeed Cache Central Configuration Engine (CLI)");
WP_CLI::line("===============================================================");

$theme_dir = get_template_directory();
$data_file = $theme_dir . '/inc/data/litespeed-cache-settings.data';
$json_file = $theme_dir . '/inc/data/litespeed-cache-settings.json';

$settings = [];
if (file_exists($data_file)) {
    $raw = file_get_contents($data_file);
    $settings = maybe_unserialize($raw);
    WP_CLI::line("Loaded settings from: {$data_file}");
} elseif (file_exists($json_file)) {
    $raw = file_get_contents($json_file);
    $settings = json_decode($raw, true);
    WP_CLI::line("Loaded settings from fallback JSON: {$json_file}");
} else {
    WP_CLI::error("Configuration file not found in inc/data/.", true);
}

if (!is_array($settings) || empty($settings)) {
    WP_CLI::error("Invalid or empty configuration dataset.", true);
}

$count = count($settings);
WP_CLI::line("Total options to synchronize: {$count}");

// Enforce critical CWV optimization parameters
$settings['cache'] = 1;              // Enable Cache = ON
$settings['cache-mobile'] = 1;       // Cache Mobile = ON
$settings['cache-priv'] = 0;         // Cache Logged-in = OFF
$settings['cache-ttl_pub'] = 604800; // Cache TTL = 7 days
$settings['cache-browser'] = 1;      // Browser Cache = ON
$settings['cache-ttl_browser'] = 31557600; // Browser TTL = 1 year
$settings['optm-css_min'] = 1;       // CSS Minify = ON
$settings['optm-css_comb'] = 0;      // CSS Combine = OFF (HTTP/2 & HTTP/3)
$settings['optm-css_async'] = 0;     // Async CSS = OFF (Zero FOUC)
$settings['optm-js_min'] = 1;        // JS Minify = ON
$settings['optm-js_comb'] = 0;       // JS Combine = OFF
$settings['media-lazy'] = 1;         // Lazy Load = ON
$settings['media-lazy_exc'] = ["custom-logo", "hero-banner", "hero-swiper", "swiper-slide-active", "bds-hero"];

$updated = 0;
foreach ($settings as $key => $val) {
    $option_name = 'litespeed.conf.' . $key;
    update_option($option_name, $val);
    $updated++;
}

WP_CLI::success("Successfully imported and synchronized {$updated} LiteSpeed configuration options!");

WP_CLI::line("\nCritical Settings Audit:");
WP_CLI::line(" - Page Cache:            " . (get_option('litespeed.conf.cache') ? 'ENABLED (ON)' : 'DISABLED'));
WP_CLI::line(" - Mobile Cache:          " . (get_option('litespeed.conf.cache-mobile') ? 'ENABLED (ON)' : 'DISABLED'));
WP_CLI::line(" - Logged-in Cache:       " . (get_option('litespeed.conf.cache-priv') ? 'ENABLED' : 'DISABLED (OFF)'));
WP_CLI::line(" - Public Cache TTL:      " . get_option('litespeed.conf.cache-ttl_pub') . "s (7 days)");
WP_CLI::line(" - Browser Cache:         " . (get_option('litespeed.conf.cache-browser') ? 'ENABLED (ON)' : 'DISABLED'));
WP_CLI::line(" - Browser Cache TTL:     " . get_option('litespeed.conf.cache-ttl_browser') . "s (1 year)");
WP_CLI::line(" - CSS Minify / Combine:  " . (get_option('litespeed.conf.optm-css_min') ? 'Minify ON' : 'Minify OFF') . " / " . (get_option('litespeed.conf.optm-css_comb') ? 'Combine ON' : 'Combine OFF (Safe)'));
WP_CLI::line(" - JS Minify / Combine:   " . (get_option('litespeed.conf.optm-js_min') ? 'Minify ON' : 'Minify OFF') . " / " . (get_option('litespeed.conf.optm-js_comb') ? 'Combine ON' : 'Combine OFF (Safe)'));
WP_CLI::line(" - Async CSS:             " . (get_option('litespeed.conf.optm-css_async') ? 'ON' : 'OFF (Zero FOUC Guaranteed)'));
WP_CLI::line(" - Media Lazy Load:       " . (get_option('litespeed.conf.media-lazy') ? 'ENABLED (ON)' : 'DISABLED'));

// Purge LiteSpeed Cache
WP_CLI::line("\nPurging LiteSpeed Cache...");
if (function_exists('do_action')) {
    do_action('litespeed_purge_all');
}
wp_cache_flush();
WP_CLI::success("All LiteSpeed Caches purged successfully!");
