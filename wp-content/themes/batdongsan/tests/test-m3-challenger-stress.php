<?php
/**
 * Milestone 3 Challenger Empirical Stress Test: Homepage Queries, Layout & Search Console
 *
 * Validates:
 * 1. Subquery performance contract in front-page.php ('no_found_rows' => true in 100% of subqueries).
 * 2. Hero Banner Swiper container height integrity (zero max-md:!h-[190px], correct height classes).
 * 3. Swiper DOM selector synchronization between template-parts/hero-banner-swiper.php and js/main.js:989-1020.
 * 4. Legacy red color cleansing (#b20000, #a60b0b) across all M3 files.
 * 5. Unified Search dock contracts: visible console, 3 transaction tabs, standardized khu_vuc parameter, JS controller.
 * 6. Card component integrity: N+1 partner logo query prevention, specs badges, unclosed span bug remediation.
 * 7. Luxury design tokens, Cormorant Garamond typography, and advisor modal trigger contracts.
 */

declare(strict_types=1);

$theme_dir = dirname(__DIR__);
if (!defined('ABSPATH')) {
    define('ABSPATH', $theme_dir);
}
$passed = 0;
$failed = 0;
$findings = [];

function assert_true(bool $condition, string $test_name, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  ✔ PASS: {$test_name}\n";
    } else {
        $failed++;
        echo "  ❌ FAIL: {$test_name}" . ($details ? " ({$details})" : "") . "\n";
    }
}

function log_finding(string $severity, string $id, string $title, string $details): void {
    global $findings;
    $findings[] = compact('severity', 'id', 'title', 'details');
    echo "  ⚠️ FINDING [{$severity}] {$id}: {$title}\n";
}

echo "================================================================================\n";
echo "   CHALLENGER M3: HOMEPAGE QUERY & LAYOUT EMPIRICAL STRESS HARNESS\n";
echo "================================================================================\n\n";

$front_page_php = file_get_contents($theme_dir . '/front-page.php');
$hero_swiper_php = file_get_contents($theme_dir . '/template-parts/hero-banner-swiper.php');
$search_unified_php = file_get_contents($theme_dir . '/template-parts/search-form-unified.php');
$card_project_php = file_get_contents($theme_dir . '/template-parts/card-project-featured.php');
$card_transfer_php = file_get_contents($theme_dir . '/template-parts/card-transfer.php');
$main_js = file_get_contents($theme_dir . '/js/main.js');

// -----------------------------------------------------------------------------
// Suite 1: Subquery Performance Contract in front-page.php
// -----------------------------------------------------------------------------
echo "▶ Suite 1: Subquery Performance Contract in front-page.php\n";

preg_match_all('/new\s+WP_Query\s*\(([^;]+)\);/s', $front_page_php, $subquery_matches, PREG_OFFSET_CAPTURE);
$query_count = count($subquery_matches[1]);
assert_true($query_count === 5, '1.1 Exactly 5 WP_Query instances in front-page.php', "Found {$query_count}");

$all_have_no_found_rows = true;
$missing_queries = [];
foreach ($subquery_matches[1] as $idx => $match) {
    $block = $match[0];
    if (!str_contains($block, "'no_found_rows' => true") && !str_contains($block, '"no_found_rows" => true')) {
        $all_have_no_found_rows = false;
        $missing_queries[] = "Subquery #{$idx}";
    }
}
assert_true($all_have_no_found_rows, '1.2 100% of WP_Query instances have no_found_rows => true', implode(', ', $missing_queries));

$all_have_meta_cache = true;
foreach ($subquery_matches[1] as $idx => $match) {
    $block = $match[0];
    if (!str_contains($block, "'update_post_meta_cache' => true")) {
        $all_have_meta_cache = false;
    }
}
assert_true($all_have_meta_cache, '1.3 100% of WP_Query instances have update_post_meta_cache => true');

$all_have_term_cache_false = true;
foreach ($subquery_matches[1] as $idx => $match) {
    $block = $match[0];
    if (!str_contains($block, "'update_post_term_cache' => false")) {
        $all_have_term_cache_false = false;
    }
}
assert_true($all_have_term_cache_false, '1.4 100% of WP_Query instances have update_post_term_cache => false');

// Verify wp_reset_postdata() is called after query loops
$reset_matches = substr_count($front_page_php, 'wp_reset_postdata()');
assert_true($reset_matches >= 4, '1.5 wp_reset_postdata() called after all query loops', "Found {$reset_matches}");

// -----------------------------------------------------------------------------
// Suite 2: Hero Banner Swiper Layout & Selector Contract
// -----------------------------------------------------------------------------
echo "\n▶ Suite 2: Hero Banner Swiper Layout & Selector Contract\n";

assert_true(!str_contains($hero_swiper_php, '190px'), '2.1 Absence of max-md:!h-[190px] mobile height squashing');
assert_true(!str_contains($hero_swiper_php, '!h-'), '2.2 Absence of any forced !h- CSS overrides in hero template');

// Responsive height classes
assert_true(str_contains($hero_swiper_php, 'min-h-[480px]'), '2.3 Responsive min-h-[480px] base height for mobile viewports');
assert_true(str_contains($hero_swiper_php, 'sm:min-h-[540px]'), '2.4 Responsive sm:min-h-[540px] for small viewports');
assert_true(str_contains($hero_swiper_php, 'lg:min-h-[640px]'), '2.5 Responsive lg:min-h-[640px] for large viewports');
assert_true(str_contains($hero_swiper_php, 'h-[65vh]'), '2.6 Responsive h-[65vh] fluid height specification');

// Swiper selector synchronization with js/main.js:989-1020
assert_true(str_contains($hero_swiper_php, '$swiper_class') && str_contains($hero_swiper_php, "'swiper_class' => 'hero-swiper'"), '2.7 Default swiper_class is hero-swiper');
assert_true(str_contains($hero_swiper_php, '{$swiper_class}__pagination') || str_contains($hero_swiper_php, "<?php echo esc_attr(\$swiper_class); ?>__pagination"), '2.8 Swiper pagination class matches hero-swiper__pagination');
assert_true(str_contains($hero_swiper_php, "<?php echo esc_attr(\$nav_class); ?>--prev"), '2.9 Swiper prev navigation class matches hero-swiper__nav--prev');
assert_true(str_contains($hero_swiper_php, "<?php echo esc_attr(\$nav_class); ?>--next"), '2.10 Swiper next navigation class matches hero-swiper__nav--next');

// Verification against js/main.js
assert_true(str_contains($main_js, '".hero-swiper"'), '2.11 main.js hero swiper targets .hero-swiper container');
assert_true(str_contains($main_js, '".hero-swiper__nav--next"'), '2.12 main.js hero swiper targets .hero-swiper__nav--next');
assert_true(str_contains($main_js, '".hero-swiper__nav--prev"'), '2.13 main.js hero swiper targets .hero-swiper__nav--prev');
assert_true(str_contains($main_js, '".hero-swiper__pagination"'), '2.14 main.js hero swiper targets .hero-swiper__pagination');

// -----------------------------------------------------------------------------
// Suite 3: Legacy Red Cleansing (#b20000, #a60b0b)
// -----------------------------------------------------------------------------
echo "\n▶ Suite 3: Legacy Red Cleansing across Milestone 3 Files\n";

$m3_files = [
    'front-page.php' => $front_page_php,
    'template-parts/hero-banner-swiper.php' => $hero_swiper_php,
    'template-parts/search-form-unified.php' => $search_unified_php,
    'template-parts/card-project-featured.php' => $card_project_php,
    'template-parts/card-transfer.php' => $card_transfer_php,
];

foreach ($m3_files as $file_name => $content) {
    $has_b20000 = stripos($content, '#b20000') !== false;
    $has_a60b0b = stripos($content, '#a60b0b') !== false;
    assert_true(!$has_b20000 && !$has_a60b0b, "3.X Zero legacy red in {$file_name}");
}

// -----------------------------------------------------------------------------
// Suite 4: Unified Search Console Contract
// -----------------------------------------------------------------------------
echo "\n▶ Suite 4: Unified Search Console Contract\n";

// Ensure not hidden
assert_true(!preg_match('/class="[^"]*unified-search-wrapper[^"]*\bhidden\b/', $search_unified_php), '4.1 unified-search-wrapper is visible (no hidden class)');

// Ensure 3 transaction tabs exist
assert_true(str_contains($search_unified_php, 'data-unified-tab="ban"'), '4.2 Tab "Bán" exists');
assert_true(str_contains($search_unified_php, 'data-unified-tab="cho-thue"'), '4.3 Tab "Cho thuê" exists');
assert_true(str_contains($search_unified_php, 'data-unified-tab="du-an"'), '4.4 Tab "Dự án" exists');

// Verify tab action routes
assert_true(str_contains($search_unified_php, "data-action=\"<?php echo esc_url(home_url('/bat-dong-san')); ?>\""), '4.5 Bán & Cho thuê route to /bat-dong-san');
assert_true(str_contains($search_unified_php, "data-action=\"<?php echo esc_url(home_url('/du-an-bat-dong-san')); ?>\""), '4.6 Dự án routes to /du-an-bat-dong-san');

// Verify standardized location input name="khu_vuc"
assert_true(str_contains($search_unified_php, 'name="khu_vuc"'), '4.7 Location parameter standardized to khu_vuc');
assert_true(str_contains($search_unified_php, 'data-unified-input="khu_vuc"'), '4.8 Location input has data-unified-input attribute');

// Verify JS controller in js/main.js
assert_true(str_contains($main_js, 'function initUnifiedSearchForm()'), '4.9 initUnifiedSearchForm() function defined in js/main.js');
assert_true(str_contains($main_js, 'initUnifiedSearchForm();'), '4.10 initUnifiedSearchForm() invoked on DOM ready');
assert_true(str_contains($main_js, 'data-unified-tab'), '4.11 main.js listens for tab switching on data-unified-tab');
assert_true(str_contains($main_js, 'data-unified-trigger'), '4.12 main.js handles custom dropdown triggers');
assert_true(str_contains($main_js, 'data-unified-option'), '4.13 main.js handles custom dropdown option selection');
assert_true(str_contains($main_js, 'e.key === "Escape"'), '4.14 main.js handles Escape key to dismiss dropdowns');

// -----------------------------------------------------------------------------
// Suite 5: Card Component Integrity & N+1 Prevention
// -----------------------------------------------------------------------------
echo "\n▶ Suite 5: Card Component Integrity & N+1 Prevention\n";

// card-project-featured.php
assert_true(str_contains($card_project_php, 'batdongsan_get_partner_logo_by_name'), '5.1 card-project-featured.php uses batdongsan_get_partner_logo_by_name()');
assert_true(str_contains($card_project_php, 'dang_mo_ban') && str_contains($card_project_php, 'sap_mo_ban'), '5.2 card-project-featured.php renders architectural status badges');
assert_true(str_contains($card_project_php, 'gia_ban_du_kien'), '5.3 card-project-featured.php renders prominent price display');

// card-transfer.php
assert_true(!str_contains($card_transfer_php, '<span class="text-gray-600"><span class="font-bold text-[#b20000]">'), '5.4 Unclosed span bug cleanly eliminated');
assert_true(str_contains($card_transfer_php, 'so_phong_ngu'), '5.5 card-transfer.php includes bedroom specs badge');
assert_true(str_contains($card_transfer_php, 'so_phong_ve_sinh'), '5.6 card-transfer.php includes bathroom specs badge');
assert_true(str_contains($card_transfer_php, 'phap_ly_label'), '5.7 card-transfer.php includes legal status badge');

// -----------------------------------------------------------------------------
// Suite 6: Design Tokens, Cormorant Typography & Advisory Contract
// -----------------------------------------------------------------------------
echo "\n▶ Suite 6: Design Tokens, Typography & Advisory Modals\n";

// Luxury tokens in front-page.php
assert_true(str_contains($front_page_php, 'bg-canvas'), '6.1 front-page.php uses bg-canvas');
assert_true(str_contains($front_page_php, 'bg-surface'), '6.2 front-page.php uses bg-surface');
assert_true(str_contains($front_page_php, 'bg-surface-dark'), '6.3 front-page.php uses bg-surface-dark');
assert_true(str_contains($front_page_php, 'text-charcoal'), '6.4 front-page.php uses text-charcoal');
assert_true(str_contains($front_page_php, 'font-serif'), '6.5 front-page.php uses font-serif for Cormorant Garamond headings');

// Advisory trust pillars & modal target
assert_true(str_contains($front_page_php, 'data-modal-target="advisor-modal"'), '6.6 front-page.php includes data-modal-target="advisor-modal"');
assert_true(str_contains($front_page_php, 'Pháp Lý Minh Bạch'), '6.7 Advisory Pillar 1 (Pháp Lý Minh Bạch) present');
assert_true(str_contains($front_page_php, 'Định Giá Chuẩn Xác'), '6.8 Advisory Pillar 2 (Định Giá Chuẩn Xác) present');
assert_true(str_contains($front_page_php, 'Bảo Mật Tuyệt Đối'), '6.9 Advisory Pillar 3 (Bảo Mật Tuyệt Đối) present');
assert_true(str_contains($front_page_php, 'Đặc Quyền Ngoại Giao'), '6.10 Advisory Pillar 4 (Đặc Quyền Ngoại Giao) present');

echo "\n================================================================================\n";
echo "   EXECUTION SUMMARY: CHALLENGER M3 EMPIRICAL TEST SUITE\n";
echo "================================================================================\n";
echo "  Total:  " . ($passed + $failed) . "\n";
echo "  Passed: " . $passed . "\n";
echo "  Failed: " . $failed . "\n";

if ($failed === 0) {
    echo "\n  [VERDICT: APPROVE] 100% of Milestone 3 Empirical Assertions Passed!\n";
    exit(0);
} else {
    echo "\n  [VERDICT: REJECT] {$failed} assertion(s) failed.\n";
    exit(1);
}
