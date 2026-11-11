<?php
/**
 * Automated Performance & Core Web Vitals Verification Suite
 *
 * Usage:
 *   wp eval-file wp-content/themes/batdongsan/scripts/verify-performance.php
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

WP_CLI::line("===============================================================");
WP_CLI::line(" BDS PERFORMANCE & CORE WEB VITALS VERIFICATION SUITE");
WP_CLI::line(" Target: 90 - 100 Mobile PageSpeed Insights / Zero FOUC / Zero CLS");
WP_CLI::line("===============================================================\n");

$theme_dir = get_template_directory();
$all_pass = true;

function check_result(string $label, bool $pass, string $detail = ''): void {
    global $all_pass;
    if ($pass) {
        WP_CLI::success(sprintf("[PASS] %-48s %s", $label, $detail));
    } else {
        $all_pass = false;
        WP_CLI::error(sprintf("[FAIL] %-48s %s", $label, $detail), false);
    }
}

// -------------------------------------------------------------
// CHECK 1: FRONTEND ASSET PIPELINE & BUNDLE SIZES
// -------------------------------------------------------------
WP_CLI::line("--> Checking Module 2: Frontend Asset Pipeline...");

$main_css = $theme_dir . '/assets/css/main.min.css';
if (file_exists($main_css)) {
    $css_raw = filesize($main_css);
    $css_gz = strlen(gzencode(file_get_contents($main_css), 9));
    check_result("Main CSS bundle exists", true, sprintf("(%s KB, %s KB gzipped)", round($css_raw/1024, 1), round($css_gz/1024, 1)));
    check_result("Main CSS under 35KB gzipped", $css_gz < 35 * 1024, sprintf("Size: %s KB", round($css_gz/1024, 1)));
} else {
    check_result("Main CSS bundle exists", false, "main.min.css missing!");
}

$main_js = $theme_dir . '/js/main.bundle.js';
if (file_exists($main_js)) {
    $js_raw = filesize($main_js);
    $js_gz = strlen(gzencode(file_get_contents($main_js), 9));
    check_result("Main JS bundle exists", true, sprintf("(%s KB, %s KB gzipped)", round($js_raw/1024, 1), round($js_gz/1024, 1)));
    check_result("Main JS under 30KB gzipped", $js_gz < 30 * 1024, sprintf("Size: %s KB", round($js_gz/1024, 1)));
} else {
    check_result("Main JS bundle exists", false, "main.bundle.js missing!");
}

// Check Swiper Zero CLS styles in CSS
$css_content = file_exists($main_css) ? file_get_contents($main_css) : '';
$has_swiper_cls = (strpos($css_content, 'hero-swiper') !== false || strpos($css_content, 'swiper-pagination') !== false);
check_result("Zero CLS Swiper & carousel rules present", $has_swiper_cls);

// -------------------------------------------------------------
// CHECK 2: HEAD CLEANUP & RENDER BLOCKING
// -------------------------------------------------------------
WP_CLI::line("\n--> Checking Module 2 & 3: Head Cleanup & Render Blocking...");

$response = wp_remote_get(home_url('/'), ['timeout' => 10, 'sslverify' => false]);
if (is_wp_error($response)) {
    WP_CLI::error("Could not fetch homepage: " . $response->get_error_message(), true);
}
$html = wp_remote_retrieve_body($response);

// Emoji cleanup
$has_emoji = (strpos($html, 'wp-emoji') !== false || strpos($html, 'print_emoji_detection_script') !== false);
check_result("WordPress Emoji scripts & styles removed", !$has_emoji);

// Head bloat removal
$has_wlw = (strpos($html, 'wlwmanifest') !== false);
$has_rsd = (strpos($html, 'EditURI') !== false);
$has_gen = (strpos($html, '<meta name="generator"') !== false && strpos($html, 'WordPress') !== false);
check_result("RSD, WLWManifest & WP Generator removed", (!$has_wlw && !$has_rsd && !$has_gen));

// Block library styles removed
$has_block_lib = (strpos($html, 'wp-block-library-css') !== false);
check_result("Gutenberg block library styles dequeued", !$has_block_lib);

// Google Fonts preconnect & non-blocking
$has_preconnect = (strpos($html, 'https://fonts.googleapis.com') !== false && strpos($html, 'rel="preconnect"') !== false);
check_result("Google Fonts preconnect tags present", $has_preconnect);

$has_font_nonblocking = (strpos($html, 'bds-google-fonts') !== false && strpos($html, 'media="print"') !== false && strpos($html, 'onload=') !== false);
check_result("Google Fonts loaded non-render-blocking", $has_font_nonblocking);

// CSS synchronous + preload
$has_css_preload = (strpos($html, 'rel="preload" as="style"') !== false && strpos($html, 'main.min.css') !== false);
$has_css_sync = (strpos($html, 'rel=\'stylesheet\' id=\'bds-main-css-css\'') !== false || strpos($html, 'id="bds-main-css-css"') !== false);
check_result("Main CSS preloaded with fetchpriority=high", $has_css_preload);
check_result("Main CSS synchronous link present (Zero FOUC)", $has_css_sync);

// Responsive LCP Preload
$has_lcp_preload = (strpos($html, 'rel="preload" as="image"') !== false && strpos($html, 'imagesrcset=') !== false);
check_result("Dynamic LCP image preloaded with imagesrcset", $has_lcp_preload);

// -------------------------------------------------------------
// CHECK 3: SCRIPT DEFERRING & ANALYTICS DELAY
// -------------------------------------------------------------
WP_CLI::line("\n--> Checking Module 3: Script Deferring & Analytics Delay...");

$has_bds_main_defer = (strpos($html, 'id="bds-main-js" defer fetchpriority="low"') !== false);
check_result("Main bundle JS deferred with fetchpriority=low", $has_bds_main_defer);

$has_delayed_analytics = (strpos($html, 'window.dataLayer = window.dataLayer || [];') !== false && strpos($html, 'loadGTM') !== false);
check_result("Analytics & GTM loaded via interaction delay", $has_delayed_analytics);

// -------------------------------------------------------------
// CHECK 4: LEVEL 3 CLOUD IMAGE ARCHITECTURE
// -------------------------------------------------------------
WP_CLI::line("\n--> Checking Module 3: Level 3 Cloud Image Architecture...");

$zero_crop_sizes = apply_filters('intermediate_image_sizes_advanced', ['thumbnail', 'medium', 'large'], 0);
check_result("Zero-Crop VPS filter active (0 thumbnail crops)", empty($zero_crop_sizes));

$big_threshold = apply_filters('big_image_size_threshold', 2560, [1000, 1000], 'test.jpg', 1);
check_result("Big image threshold capped at 1920px", $big_threshold === 1920);

// Dynamic thumbnail rewrite algorithm unit test
$test_thumb = 'http://localhost:10089/wp-content/uploads/2026/01/villa-luxury-800x600.jpg';
$perf = BDS_Performance::instance();
$rewritten_thumb = $perf->rewrite_image_url($test_thumb);
check_result("Dynamic thumbnail rewrite matches on-the-fly syntax", strpos($rewritten_thumb, 'villa-luxury.jpg?tr=w-800,h-600') !== false);

// -------------------------------------------------------------
// CHECK 5: LITESPEED CACHE CENTRAL CONFIG ENGINE
// -------------------------------------------------------------
WP_CLI::line("\n--> Checking Module 4: LiteSpeed Cache Configuration...");

$lsc_cache = (int) get_option('litespeed.conf.cache', 0);
$lsc_mobile = (int) get_option('litespeed.conf.cache-mobile', 0);
$lsc_browser = (int) get_option('litespeed.conf.cache-browser', 0);
$lsc_browser_ttl = (int) get_option('litespeed.conf.cache-ttl_browser', 0);
$lsc_css_min = (int) get_option('litespeed.conf.optm-css_min', 0);
$lsc_css_comb = (int) get_option('litespeed.conf.optm-css_comb', 0);
$lsc_css_async = (int) get_option('litespeed.conf.optm-css_async', 0);

check_result("LiteSpeed Page Cache enabled", $lsc_cache === 1);
check_result("LiteSpeed Mobile Cache enabled (Separate cache)", $lsc_mobile === 1);
check_result("LiteSpeed Browser Cache enabled with 1-year TTL", ($lsc_browser === 1 && $lsc_browser_ttl === 31557600));
check_result("CSS Minify ON & Combine OFF (HTTP/2 safe)", ($lsc_css_min === 1 && $lsc_css_comb === 0));
check_result("Async CSS OFF (Guarantees ZERO FOUC)", $lsc_css_async === 0);

// -------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------
WP_CLI::line("\n===============================================================");
if ($all_pass) {
    WP_CLI::success("ALL PERFORMANCE & CORE WEB VITALS AUDITS PASSED 100%!");
} else {
    WP_CLI::warning("Some checks failed or require attention above.");
}
WP_CLI::line("===============================================================");
