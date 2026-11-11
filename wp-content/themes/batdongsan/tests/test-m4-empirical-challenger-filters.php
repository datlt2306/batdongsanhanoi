<?php
/**
 * Milestone 4 Empirical Challenger Stress Test:
 * Filter Bar Contract, Breakpoints, and Color Token Verification
 *
 * Validates:
 * 1. Breakpoint Contract: Line 565 of page-bat-dong-san.php (index 564) strictly contains 'lg:block'.
 * 2. Filter Bar Container Contract: template-parts/filter-bar-horizontal.php includes '.bg-white' alongside '.bg-surface'.
 * 3. Reset Button Contract: Reset anchor with aria-label='Đặt lại bộ lọc' exists inside ancestor with '.bg-white'.
 * 4. Hidden Inputs Contract: Verification of hidden inputs (type, khu_vuc, hinh_thuc, status, so_phong_ngu, so_phong_ve_sinh, huong_nha, phap_ly).
 * 5. Legacy Red Color Token Audit across all M4 modified files.
 * 6. Responsive and multi-criteria filter state permutations.
 *
 * @package BatDongSan
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../inc/helpers/filter-options.php';
require_once __DIR__ . '/../inc/helpers/filter-slug.php';
require_once __DIR__ . '/../inc/helpers/format.php';
require_once __DIR__ . '/../inc/services/price-ranges.php';

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = []) {
        if (is_object($args)) {
            $r = get_object_vars($args);
        } elseif (is_array($args)) {
            $r = &$args;
        } else {
            parse_str((string) $args, $r);
        }
        if (is_array($defaults)) {
            return array_merge($defaults, $r);
        }
        return $r;
    }
}

if (!function_exists('home_url')) {
    function home_url(string $path = ''): string {
        return 'https://example.com' . $path;
    }
}

if (!function_exists('remove_query_arg')) {
    function remove_query_arg($keys, $url = '') {
        $keys = (array) $keys;
        $parts = parse_url($url ?: 'https://example.com/bat-dong-san');
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            foreach ($keys as $k) {
                unset($query[$k]);
            }
        }
        $res = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'example.com') . ($parts['path'] ?? '');
        if (!empty($query)) {
            $res .= '?' . http_build_query($query);
        }
        return $res;
    }
}

if (!function_exists('wp_rand')) {
    function wp_rand(int $min = 0, int $max = 0): int {
        return ($max > $min) ? mt_rand($min, $max) : mt_rand();
    }
}

if (!function_exists('get_template_part')) {
    function get_template_part(string $slug, ?string $name = null, array $args = []): void {
        global $theme_dir;
        $file = $theme_dir . '/' . $slug . ($name ? "-{$name}" : '') . '.php';
        if (file_exists($file)) {
            include $file;
        }
    }
}

$theme_dir = dirname(__DIR__);
$passed = 0;
$failed = 0;
$findings = [];

function assert_m4(bool $condition, string $test_name, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  ✔ PASS: {$test_name}\n";
    } else {
        $failed++;
        echo "  ❌ FAIL: {$test_name}" . ($details ? " ({$details})" : "") . "\n";
    }
}

function log_m4_finding(string $severity, string $id, string $title, string $details): void {
    global $findings;
    $findings[] = compact('severity', 'id', 'title', 'details');
    echo "  ⚠️ FINDING [{$severity}] {$id}: {$title}\n";
}

echo "================================================================================\n";
echo "   CHALLENGER M4: FILTER CONTRACT & BREAKPOINT EMPIRICAL STRESS TEST\n";
echo "================================================================================\n\n";

// -----------------------------------------------------------------------------
// Suite 1: Breakpoint Contract Verification (Line 565 of page-bat-dong-san.php)
// -----------------------------------------------------------------------------
echo "▶ Suite 1: Breakpoint Contract Verification\n";

$bds_file = $theme_dir . '/page-bat-dong-san.php';
assert_m4(file_exists($bds_file), '1.1 page-bat-dong-san.php exists');

$bds_content = file_get_contents($bds_file);
$bds_lines = explode("\n", $bds_content);

$line_565 = $bds_lines[564] ?? ''; // 0-indexed position 564 is line 565
assert_m4(str_contains($line_565, 'lg:block'), '1.2 Line 565 strictly contains lg:block', "Found at line 565: " . trim($line_565));
assert_m4(str_contains($line_565, 'hidden'), '1.3 Line 565 strictly contains hidden (hidden on mobile, block on lg)', "Line 565: " . trim($line_565));

// Verify total lines stability
assert_m4(count($bds_lines) >= 800, '1.4 page-bat-dong-san.php has expected file depth', 'Total lines: ' . count($bds_lines));

// Verify absence of obsolete 900px / 901px breakpoints in page-bat-dong-san.php
assert_m4(!str_contains($bds_content, '900px') && !str_contains($bds_content, '901px'), '1.5 Zero 900px/901px breakpoints in page-bat-dong-san.php');


// -----------------------------------------------------------------------------
// Suite 2: Filter Bar Container & Reset Button Contract
// -----------------------------------------------------------------------------
echo "\n▶ Suite 2: Filter Bar Container & Reset Button Contract\n";

$filter_bar_file = $theme_dir . '/template-parts/filter-bar-horizontal.php';
assert_m4(file_exists($filter_bar_file), '2.1 template-parts/filter-bar-horizontal.php exists');
$filter_bar_code = file_get_contents($filter_bar_file);

// Check container markup in source code
assert_m4(str_contains($filter_bar_code, "bg-surface bg-white"), '2.2 Filter bar desktop container has both bg-surface and bg-white classes');
assert_m4(str_contains($filter_bar_code, "\$layout_mobile ? 'bg-white' : 'bg-surface bg-white"), '2.3 Filter bar container conditionally preserves bg-white in both mobile and desktop layouts');

// Check reset button anchor in source code
assert_m4(str_contains($filter_bar_code, "aria-label=\"Đặt lại bộ lọc\""), '2.4 Reset button contains aria-label="Đặt lại bộ lọc"');
assert_m4(str_contains($filter_bar_code, "remove_query_arg("), '2.5 Reset button generates clean reset URL via remove_query_arg');

// Verify that remove_query_arg strips all filter parameters
$reset_keys_expected = ['project-name', 'price', 'type', 'status', 'khu_vuc', 'area', 'du_an', 'paged', 'order', 'so_phong_ngu', 'so_phong_ve_sinh', 'huong_nha', 'phap_ly', 'hinh_thuc'];
foreach ($reset_keys_expected as $k) {
    assert_m4(str_contains($filter_bar_code, "'{$k}'"), "2.6 Reset url strips parameter '{$k}'");
}

// -----------------------------------------------------------------------------
// Suite 3: Hidden Inputs Contract Verification & Empirical Rendering
// -----------------------------------------------------------------------------
echo "\n▶ Suite 3: Hidden Inputs Contract Verification & Empirical Rendering\n";

// Function to render filter bar template with custom arguments
function render_filter_bar(array $custom_args): string {
    global $theme_dir;
    $args = $custom_args;
    ob_start();
    include $theme_dir . '/template-parts/filter-bar-horizontal.php';
    return ob_get_clean() ?: '';
}

// Test Case 3A: Render with all active criteria populated
$term_chung_cu = (object)['term_id' => 12, 'name' => 'Căn hộ chung cư', 'slug' => 'can-ho-chung-cu'];
$term_biet_thu = (object)['term_id' => 15, 'name' => 'Biệt thự', 'slug' => 'biet-thu'];

$active_args = [
    'form_action'             => 'https://example.com/bat-dong-san',
    'placeholder'             => 'Tìm kiếm...',
    'current_name'            => 'Vinhomes',
    'current_price'           => '3000-5000',
    'current_type'            => '12',
    'current_status'          => 'dang-ban',
    'current_khu_vuc'         => 'cau-giay',
    'current_area'            => '50-80',
    'current_order'           => 2,
    'show_status'             => true,
    'current_hinh_thuc'       => 'chuyen-nhuong',
    'current_so_phong_ngu'    => '3',
    'current_so_phong_ve_sinh'=> '2',
    'current_huong_nha'       => 'dong-nam',
    'current_phap_ly'         => 'so-do',
    'type_terms'              => [$term_chung_cu, $term_biet_thu],
    'show_advanced'           => true,
    'layout'                  => 'default',
];

$rendered_active = render_filter_bar($active_args);

assert_m4(str_contains($rendered_active, 'name="type"'), '3.1 [Active State] input[name="type"] rendered');
assert_m4(str_contains($rendered_active, 'value="12"'), '3.1b [Active State] input[name="type"] has correct value 12');
assert_m4(str_contains($rendered_active, 'name="khu_vuc"'), '3.2 [Active State] input[name="khu_vuc"] rendered');
assert_m4(str_contains($rendered_active, 'value="cau-giay"'), '3.2b [Active State] input[name="khu_vuc"] has correct value');
assert_m4(str_contains($rendered_active, 'name="hinh_thuc"'), '3.3 [Active State] input[name="hinh_thuc"] rendered');
assert_m4(str_contains($rendered_active, 'value="chuyen-nhuong"'), '3.3b [Active State] input[name="hinh_thuc"] has correct value');
assert_m4(str_contains($rendered_active, 'name="status"'), '3.4 [Active State] input[name="status"] rendered');
assert_m4(str_contains($rendered_active, 'value="dang-ban"'), '3.4b [Active State] input[name="status"] has correct value');
assert_m4(str_contains($rendered_active, 'name="so_phong_ngu"'), '3.5 [Active State] input[name="so_phong_ngu"] rendered');
assert_m4(str_contains($rendered_active, 'value="3"'), '3.5b [Active State] input[name="so_phong_ngu"] has correct value');
assert_m4(str_contains($rendered_active, 'name="so_phong_ve_sinh"'), '3.6 [Active State] input[name="so_phong_ve_sinh"] rendered');
assert_m4(str_contains($rendered_active, 'value="2"'), '3.6b [Active State] input[name="so_phong_ve_sinh"] has correct value');
assert_m4(str_contains($rendered_active, 'name="huong_nha"'), '3.7 [Active State] input[name="huong_nha"] rendered');
assert_m4(str_contains($rendered_active, 'value="dong-nam"'), '3.7b [Active State] input[name="huong_nha"] has correct value');
assert_m4(str_contains($rendered_active, 'name="phap_ly"'), '3.8 [Active State] input[name="phap_ly"] rendered');
assert_m4(str_contains($rendered_active, 'value="so-do"'), '3.8b [Active State] input[name="phap_ly"] has correct value');

// Test Case 3B: Render with DEFAULT / EMPTY state (initial page visit)
$empty_args = [
    'form_action'             => 'https://example.com/bat-dong-san',
    'placeholder'             => 'Tìm kiếm...',
    'current_name'            => '',
    'current_price'           => '',
    'current_type'            => '0',
    'current_status'          => '0',
    'current_khu_vuc'         => '',
    'current_area'            => '',
    'current_order'           => 1,
    'show_status'             => true,
    'current_hinh_thuc'       => '',
    'current_so_phong_ngu'    => '',
    'current_so_phong_ve_sinh'=> '',
    'current_huong_nha'       => '',
    'current_phap_ly'         => '',
    'type_terms'              => [$term_chung_cu],
    'show_advanced'           => true,
    'layout'                  => 'default',
];

$rendered_empty = render_filter_bar($empty_args);

assert_m4(str_contains($rendered_empty, 'name="khu_vuc"'), '3.9 [Empty State] input[name="khu_vuc"] unconditionally present');
assert_m4(str_contains($rendered_empty, 'name="hinh_thuc"'), '3.10 [Empty State] input[name="hinh_thuc"] unconditionally present');
assert_m4(str_contains($rendered_empty, 'name="status"'), '3.11 [Empty State] input[name="status"] present when show_status is true');

// Empirical Investigation on Conditional Hidden Inputs:
$has_empty_spn = str_contains($rendered_empty, 'name="so_phong_ngu"');
$has_empty_spvs = str_contains($rendered_empty, 'name="so_phong_ve_sinh"');
$has_empty_hn = str_contains($rendered_empty, 'name="huong_nha"');
$has_empty_pl = str_contains($rendered_empty, 'name="phap_ly"');

if (!$has_empty_spn || !$has_empty_spvs || !$has_empty_hn || !$has_empty_pl) {
    log_m4_finding(
        'MEDIUM',
        'CHALLENGE-M4-FINDING-01',
        'Conditional Hidden Inputs in template-parts/filter-bar-horizontal.php Omitted on Initial Page Render',
        'Lines 114-125 wrap so_phong_ngu, so_phong_ve_sinh, huong_nha, phap_ly in if (!empty(...)) guards. ' .
        'When landing on the property archive with no active parameters, these hidden inputs are absent from the DOM. ' .
        'While js/main.js dynamically handles form parameters and popstate, client-side scripts targeting $filterForm.find("input[name=...]") ' .
        'will not find existing input nodes unless created dynamically or rendered unconditionally with empty values.'
    );
}

// -----------------------------------------------------------------------------
// Suite 4: DOM Contract & js/main.js Selector Parity
// -----------------------------------------------------------------------------
echo "\n▶ Suite 4: DOM Contract & js/main.js Selector Parity\n";

$main_js = file_get_contents($theme_dir . '/js/main.js');

// 1. Selector for reset button finder: $filterForm.closest(".bg-white").find("a[aria-label='Đặt lại bộ lọc']")
assert_m4(str_contains($main_js, 'a[aria-label=\'Đặt lại bộ lọc\']'), '4.1 js/main.js binds to a[aria-label=\'Đặt lại bộ lọc\']');
assert_m4(str_contains($main_js, '$filterForm.closest(".bg-white")'), '4.2 js/main.js navigates via closest(".bg-white")');

// 2. Filter bar form class contract
assert_m4(str_contains($filter_bar_code, 'filter-bar-horizontal__form'), '4.3 Form has class .filter-bar-horizontal__form');
assert_m4(str_contains($main_js, '.filter-bar-horizontal__form'), '4.4 js/main.js listens to .filter-bar-horizontal__form');

// 3. Grid, pagination and active filters selectors
assert_m4(str_contains($bds_content, 'id="bds-listings-grid"'), '4.5 page-bat-dong-san.php contains #bds-listings-grid');
assert_m4(str_contains($bds_content, 'id="bds-listings-pagination"'), '4.6 page-bat-dong-san.php contains #bds-listings-pagination');
assert_m4(str_contains($bds_content, 'id="bds-listings-count"'), '4.7 page-bat-dong-san.php contains #bds-listings-count');
assert_m4(str_contains($bds_content, 'id="bds-active-filters"'), '4.8 page-bat-dong-san.php contains #bds-active-filters');
assert_m4(str_contains($bds_content, 'id="bds-transaction-tabs"'), '4.9 page-bat-dong-san.php contains #bds-transaction-tabs');

// 4. Advanced Filter Popover Interaction Verification
$has_adv_trigger_js = str_contains($main_js, 'data-filter-advanced-trigger');
if (!$has_adv_trigger_js) {
    log_m4_finding(
        'LOW',
        'CHALLENGE-M4-FINDING-02',
        'Advanced Filter Popover Trigger ([data-filter-advanced-trigger]) Not Bound in js/main.js',
        'template-parts/filter-bar-horizontal.php defines data-filter-advanced-trigger, data-filter-advanced-panel, and data-filter-advanced-apply. ' .
        'However, js/main.js initFilterDropdowns() does not bind an event listener for data-filter-advanced-trigger, leaving the popover toggle inactive on desktop UI.'
    );
}

// -----------------------------------------------------------------------------
// Suite 5: Legacy Red Color Cleansing Audit across Milestone 4 Files
// -----------------------------------------------------------------------------
echo "\n▶ Suite 5: Legacy Red Color Cleansing Audit\n";

$m4_files = [
    'template-parts/filter-bar-horizontal.php',
    'template-parts/filter-sort-bds-dropdown.php',
    'template-parts/filter-price-dropdown.php',
    'template-parts/filter-area-range-dropdown.php',
    'template-parts/filter-sort-dropdown.php',
    'page-bat-dong-san.php',
    'paths/bat-dong-san-item.php',
    'inc/hooks/theme-setup.php',
    'inc/hooks/ajax-listings-filter.php',
];

$red_pattern = '/#(b20000|8b0000|e31837|a60b0b)|bg-red-50|ring-red-100/i';

foreach ($m4_files as $file) {
    $full_path = $theme_dir . '/' . $file;
    if (!file_exists($full_path)) {
        assert_m4(false, "5.x File exists: {$file}");
        continue;
    }
    $content = file_get_contents($full_path);
    $matches = [];
    if (preg_match_all($red_pattern, $content, $matches)) {
        assert_m4(false, "5.x Clean of red tokens: {$file}", "Found tokens: " . implode(', ', array_unique($matches[0])));
    } else {
        assert_m4(true, "5.x Clean of red tokens: {$file}");
    }
}

// Check js/main.js for ACTIVE addition of red classes (excluding removeClass / classList.remove)
$js_lines = explode("\n", $main_js);
$active_red_in_js = [];
foreach ($js_lines as $idx => $line) {
    if (preg_match($red_pattern, $line)) {
        // Exclude lines where it is only removed
        if (!str_contains($line, 'removeClass') && !str_contains($line, 'classList.remove')) {
            $active_red_in_js[] = ($idx + 1) . ': ' . trim($line);
        }
    }
}
assert_m4(empty($active_red_in_js), '5.10 js/main.js has zero active additions of red tokens', empty($active_red_in_js) ? '' : implode('; ', $active_red_in_js));


// -----------------------------------------------------------------------------
// Suite 6: Pagination & SSR Query Arguments Integrity
// -----------------------------------------------------------------------------
echo "\n▶ Suite 6: Pagination & SSR Query Arguments Integrity\n";

// Ensure page-bat-dong-san.php passes extended parameters to query_args for paginate_links
assert_m4(str_contains($bds_content, "'so_phong_ngu'"), '6.1 page-bat-dong-san.php query_args preserves so_phong_ngu');
assert_m4(str_contains($bds_content, "'so_phong_ve_sinh'"), '6.2 page-bat-dong-san.php query_args preserves so_phong_ve_sinh');
assert_m4(str_contains($bds_content, "'huong_nha'"), '6.3 page-bat-dong-san.php query_args preserves huong_nha');
assert_m4(str_contains($bds_content, "'phap_ly'"), '6.4 page-bat-dong-san.php query_args preserves phap_ly');

// Check update_post_caches() in ajax-listings-filter.php
$ajax_listings = file_get_contents($theme_dir . '/inc/hooks/ajax-listings-filter.php');
assert_m4(str_contains($ajax_listings, "update_post_caches(\$paged_posts, 'bat-dong-san', true, true);"), '6.5 ajax-listings-filter.php preserves update_post_caches batch priming');


// -----------------------------------------------------------------------------
// Summary & Verdict
// -----------------------------------------------------------------------------
echo "\n================================================================================\n";
echo "   EXECUTION SUMMARY: CHALLENGER M4 FILTER & BREAKPOINT HARNESS\n";
echo "================================================================================\n";
echo "  Total Tests: " . ($passed + $failed) . "\n";
echo "  Passed:      {$passed}\n";
echo "  Failed:      {$failed}\n";
echo "  Findings:    " . count($findings) . "\n";

$pass_rate = round(($passed / ($passed + $failed)) * 100, 1);
echo "  Pass Rate:   {$pass_rate}%\n\n";

if ($failed === 0) {
    echo "  [FINAL VERDICT: APPROVE WITH FINDINGS]\n";
    echo "  All contract assertions passed. Findings logged for team consideration.\n";
} else {
    echo "  [FINAL VERDICT: REJECT]\n";
    echo "  Contract regressions detected.\n";
    exit(1);
}
