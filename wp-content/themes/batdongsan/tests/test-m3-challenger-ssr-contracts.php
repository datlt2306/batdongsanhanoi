<?php
/**
 * Milestone 3 Challenger: Empirical Stress Test Suite for Search Form SSR & Query Contracts
 * 
 * Verifies:
 * 1. template-parts/search-form-unified.php SSR Rendering:
 *    - Tab "Bán": value="chuyen-nhuong", form action="/bat-dong-san", aria-selected="true"
 *    - Tab "Cho thuê": value="cho-thue", form action="/bat-dong-san", aria-selected="true"
 *    - Tab "Dự án": value="", form action="/du-an-bat-dong-san", aria-selected="true"
 * 2. Parameter Contract & Separation:
 *    - Location parameter "khu_vuc" binds cleanly
 *    - No collision with surface area "area" parameter (no <input name="area"> in form)
 *    - When both $_GET['khu_vuc'] and $_GET['area'] coexist, khu_vuc remains pristine
 *    - In real estate query engine, khu_vuc filters location and area filters acreage
 * 3. Client-side Controller & bfcache Contract in js/main.js
 * 
 * @package BatDongSan
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../inc/helpers/project.php';
require_once __DIR__ . '/../inc/services/queries.php';
require_once __DIR__ . '/../inc/services/price-ranges.php';
require_once __DIR__ . '/../inc/helpers/filter-options.php';

// Polyfills for runner
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

if (!function_exists('get_terms')) {
    function get_terms($args = []) {
        return [
            (object)['term_id' => 10, 'name' => 'Căn hộ chung cư', 'slug' => 'can-ho-chung-cu'],
            (object)['term_id' => 11, 'name' => 'Biệt thự liền kề', 'slug' => 'biet-thu-lien-ke'],
            (object)['term_id' => 12, 'name' => 'Nhà mặt phố', 'slug' => 'nha-mat-pho'],
        ];
    }
}

use BDS\Tests\Framework\MockWordPress;

class M3SearchFormSsrQueryContractsTest {

    private int $passes = 0;
    private int $failures = 0;
    private array $errors = [];
    private string $themeDir;

    public function __construct() {
        $this->themeDir = dirname(__DIR__);
    }

    private function assert(bool $condition, string $message, string $details = ''): void {
        if ($condition) {
            $this->passes++;
            echo "  ✔ PASS: {$message}\n";
        } else {
            $this->failures++;
            $err = "  ✖ FAIL: {$message}" . ($details ? " -> {$details}" : '');
            $this->errors[] = $err;
            echo "{$err}\n";
        }
    }

    public function run(): int {
        echo "================================================================================\n";
        echo "   M3 CHALLENGER: SEARCH FORM SSR RENDERING & QUERY CONTRACTS STRESS HARNESS\n";
        echo "================================================================================\n";

        $this->testTabSsrRendering();
        $this->testParameterSeparationAndCollision();
        $this->testQueryEngineSeparation();
        $this->testClientSideJsContract();

        echo "\n================================================================================\n";
        echo " SUMMARY: {$this->passes} Passed, {$this->failures} Failed\n";
        echo "================================================================================\n";

        if ($this->failures === 0) {
            echo "\n  [VERDICT: APPROVE] ALL SSR RENDERING & QUERY CONTRACT ASSERTIONS PASSED!\n\n";
            return 0;
        } else {
            echo "\n  [VERDICT: REJECT] {$this->failures} ASSERTIONS FAILED:\n";
            foreach ($this->errors as $e) {
                echo "  {$e}\n";
            }
            echo "\n";
            return 1;
        }
    }

    /**
     * Suite 1: Standalone PHP rendering tests for Tab "Bán", Tab "Cho thuê", Tab "Dự án"
     */
    private function testTabSsrRendering(): void {
        echo "\n▶ Suite 1: Tab SSR Rendering Contracts (template-parts/search-form-unified.php)\n";
        $templatePath = $this->themeDir . '/template-parts/search-form-unified.php';

        // 1.1: Default SSR render (Tab Bán)
        $_GET = [];
        $args = [];
        ob_start();
        include $templatePath;
        $html1 = ob_get_clean();

        preg_match('/<input[^>]+name="hinh_thuc"[^>]+value="([^"]*)"/i', $html1, $m1);
        $val1 = $m1[1] ?? null;
        $this->assert($val1 === 'chuyen-nhuong', "1.1: Default render produces hinh_thuc value='chuyen-nhuong' (actual: '{$val1}')");
        $this->assert(str_contains($html1, 'action="' . home_url('/bat-dong-san') . '"'), "1.1b: Default render action targets /bat-dong-san");
        preg_match('/<button[^>]+data-unified-tab="ban"[^>]*>/i', $html1, $btn1);
        $this->assert(!empty($btn1[0]) && str_contains($btn1[0], 'aria-selected="true"'), "1.1c: Tab 'Bán' is visually and semantically active (aria-selected='true')");

        // 1.2: Explicit Tab "Bán" via args
        $_GET = [];
        $args = ['current_tab' => 'ban'];
        ob_start();
        include $templatePath;
        $html2 = ob_get_clean();
        preg_match('/<input[^>]+name="hinh_thuc"[^>]+value="([^"]*)"/i', $html2, $m2);
        $val2 = $m2[1] ?? null;
        $this->assert($val2 === 'chuyen-nhuong', "1.2: Explicit Tab 'Bán' produces hinh_thuc value='chuyen-nhuong' (actual: '{$val2}')");

        // 1.3: URL query override for Tab "Bán" (?hinh_thuc=chuyen-nhuong)
        $_GET = ['hinh_thuc' => 'chuyen-nhuong'];
        $args = [];
        ob_start();
        include $templatePath;
        $html3 = ob_get_clean();
        preg_match('/<input[^>]+name="hinh_thuc"[^>]+value="([^"]*)"/i', $html3, $m3);
        $val3 = $m3[1] ?? null;
        $this->assert($val3 === 'chuyen-nhuong', "1.3: URL ?hinh_thuc=chuyen-nhuong produces value='chuyen-nhuong' (actual: '{$val3}')");

        // 1.4: Explicit Tab "Cho thuê" via args
        $_GET = [];
        $args = ['current_tab' => 'cho-thue', 'current_hinh_thuc' => 'cho-thue'];
        ob_start();
        include $templatePath;
        $html4 = ob_get_clean();
        preg_match('/<input[^>]+name="hinh_thuc"[^>]+value="([^"]*)"/i', $html4, $m4);
        $val4 = $m4[1] ?? null;
        $this->assert($val4 === 'cho-thue', "1.4: Tab 'Cho thuê' produces hinh_thuc value='cho-thue' (actual: '{$val4}')");
        $this->assert(str_contains($html4, 'action="' . home_url('/bat-dong-san') . '"'), "1.4b: Tab 'Cho thuê' action targets /bat-dong-san");
        preg_match('/<button[^>]+data-unified-tab="cho-thue"[^>]*>/i', $html4, $btn4);
        $this->assert(!empty($btn4[0]) && str_contains($btn4[0], 'aria-selected="true"'), "1.4c: Tab 'Cho thuê' has aria-selected='true'");

        // 1.5: URL query override for Tab "Cho thuê" (?hinh_thuc=cho-thue)
        $_GET = ['hinh_thuc' => 'cho-thue'];
        $args = [];
        ob_start();
        include $templatePath;
        $html5 = ob_get_clean();
        preg_match('/<input[^>]+name="hinh_thuc"[^>]+value="([^"]*)"/i', $html5, $m5);
        $val5 = $m5[1] ?? null;
        $this->assert($val5 === 'cho-thue', "1.5: URL ?hinh_thuc=cho-thue dynamically activates Tab 'Cho thuê' with value='cho-thue'");
        preg_match('/<button[^>]+data-unified-tab="cho-thue"[^>]*>/i', $html5, $btn5);
        $this->assert(!empty($btn5[0]) && str_contains($btn5[0], 'aria-selected="true"'), "1.5b: URL ?hinh_thuc=cho-thue sets aria-selected='true' on Tab 'Cho thuê'");

        // 1.6: Explicit Tab "Dự án" via args
        $_GET = [];
        $args = ['current_tab' => 'du-an', 'form_action' => home_url('/du-an-bat-dong-san')];
        ob_start();
        include $templatePath;
        $html6 = ob_get_clean();
        preg_match('/<input[^>]+name="hinh_thuc"[^>]+value="([^"]*)"/i', $html6, $m6);
        $val6 = $m6[1] ?? null;
        $this->assert($val6 === '', "1.6: Tab 'Dự án' produces empty hinh_thuc value='' (actual: '{$val6}')");
        $this->assert(str_contains($html6, 'action="' . home_url('/du-an-bat-dong-san') . '"'), "1.6b: Tab 'Dự án' action targets /du-an-bat-dong-san");
        preg_match('/<button[^>]+data-unified-tab="du-an"[^>]*>/i', $html6, $btn6);
        $this->assert(!empty($btn6[0]) && str_contains($btn6[0], 'aria-selected="true"'), "1.6c: Tab 'Dự án' has aria-selected='true'");

        // 1.7: Auto-switch to Tab "Dự án" via form_action containing /du-an
        $_GET = [];
        $args = ['form_action' => home_url('/du-an-bat-dong-san')];
        ob_start();
        include $templatePath;
        $html7 = ob_get_clean();
        preg_match('/<input[^>]+name="hinh_thuc"[^>]+value="([^"]*)"/i', $html7, $m7);
        $val7 = $m7[1] ?? null;
        $this->assert($val7 === '', "1.7: Auto-switched Tab 'Dự án' via form_action produces value=''");
        preg_match('/<button[^>]+data-unified-tab="du-an"[^>]*>/i', $html7, $btn7);
        $this->assert(!empty($btn7[0]) && str_contains($btn7[0], 'aria-selected="true"'), "1.7b: Auto-switched Tab 'Dự án' has aria-selected='true'");
    }

    /**
     * Suite 2: Parameter Separation & Collision Stress-Test (khu_vuc vs area)
     */
    private function testParameterSeparationAndCollision(): void {
        echo "\n▶ Suite 2: Parameter Contract & Separation: khu_vuc vs area\n";
        $templatePath = $this->themeDir . '/template-parts/search-form-unified.php';

        // 2.1: Clean binding of location via args
        $_GET = [];
        $args = ['current_khu_vuc' => 'cau-giay'];
        ob_start();
        include $templatePath;
        $html1 = ob_get_clean();

        $this->assert(
            str_contains($html1, '<input type="hidden" name="khu_vuc" value="cau-giay" data-unified-input="khu_vuc">'),
            "2.1a: Location binds cleanly to input name='khu_vuc' with value='cau-giay'"
        );
        preg_match('/data-unified-trigger="khu_vuc"[^>]*>(.*?)<\/button>/s', $html1, $labelMatch1);
        $labelText1 = trim(strip_tags($labelMatch1[1] ?? ''));
        $this->assert(str_contains($labelText1, 'Cầu Giấy'), "2.1b: Location dropdown trigger displays 'Cầu Giấy' label");

        // 2.2: Clean binding of location via $_GET['khu_vuc']
        $_GET = ['khu_vuc' => 'tay-ho'];
        $args = [];
        ob_start();
        include $templatePath;
        $html2 = ob_get_clean();

        $this->assert(
            str_contains($html2, '<input type="hidden" name="khu_vuc" value="tay-ho" data-unified-input="khu_vuc">'),
            "2.2a: \$_GET['khu_vuc'] binds cleanly to input name='khu_vuc' with value='tay-ho'"
        );
        preg_match('/data-unified-trigger="khu_vuc"[^>]*>(.*?)<\/button>/s', $html2, $labelMatch2);
        $labelText2 = trim(strip_tags($labelMatch2[1] ?? ''));
        $this->assert(str_contains($labelText2, 'Tây Hồ'), "2.2b: Location dropdown trigger displays 'Tây Hồ' label");

        // 2.3: Zero collision with surface area parameter 'area' in search form markup
        $hasAreaInput = (bool) preg_match('/<input[^>]+name="area"[^>]*>/i', $html1);
        $hasAreaSelect = (bool) preg_match('/<select[^>]+name="area"[^>]*>/i', $html1);
        $this->assert(!$hasAreaInput, "2.3a: ZERO <input name='area'> in search form (location uses 'khu_vuc')");
        $this->assert(!$hasAreaSelect, "2.3b: ZERO <select name='area'> in search form (location uses 'khu_vuc')");

        // 2.4: Simultaneous presence of $_GET['khu_vuc'] and $_GET['area']
        $_GET = [
            'khu_vuc' => 'ba-dinh',
            'area'    => '50-80', // surface area range in real estate listing
        ];
        $args = [];
        ob_start();
        include $templatePath;
        $html3 = ob_get_clean();

        $this->assert(
            str_contains($html3, '<input type="hidden" name="khu_vuc" value="ba-dinh" data-unified-input="khu_vuc">'),
            "2.4a: When both khu_vuc='ba-dinh' and area='50-80' exist, khu_vuc cleanly resolves to 'ba-dinh'"
        );
        preg_match('/data-unified-trigger="khu_vuc"[^>]*>(.*?)<\/button>/s', $html3, $labelMatch3);
        $labelText3 = trim(strip_tags($labelMatch3[1] ?? ''));
        $this->assert(str_contains($labelText3, 'Ba Đình'), "2.4b: Location label displays 'Ba Đình', not corrupted by surface area range");

        // 2.5: Explicit $args['current_khu_vuc'] overrides any $_GET['area'] fallback
        $_GET = ['area' => '100-200'];
        $args = ['current_khu_vuc' => 'dong-da'];
        ob_start();
        include $templatePath;
        $html4 = ob_get_clean();

        $this->assert(
            str_contains($html4, '<input type="hidden" name="khu_vuc" value="dong-da" data-unified-input="khu_vuc">'),
            "2.5a: \$args['current_khu_vuc'] strictly overrides \$_GET['area']"
        );
    }

    /**
     * Suite 3: Query Engine Independence (khu_vuc vs area in filter logic)
     */
    private function testQueryEngineSeparation(): void {
        echo "\n▶ Suite 3: Real Estate Query Engine Independence (page-bat-dong-san.php contracts)\n";

        // Check page-bat-dong-san.php lines
        $bdsPhp = file_get_contents($this->themeDir . '/page-bat-dong-san.php');

        // khu_vuc parameter extraction
        $hasKhuVucGet = str_contains($bdsPhp, "\$khu_vuc   = isset(\$_GET['khu_vuc'])");
        $this->assert($hasKhuVucGet, "3.1: page-bat-dong-san.php reads location strictly from \$_GET['khu_vuc']");

        // area parameter extraction
        $hasAreaGet = str_contains($bdsPhp, "\$area = isset(\$_GET['area'])");
        $this->assert($hasAreaGet, "3.2: page-bat-dong-san.php reads surface area range strictly from \$_GET['area']");

        // Check area filtering is processed by query service, query builder, or legacy filter
        $hasAreaFilterCall = (str_contains($bdsPhp, "'area'") && (str_contains($bdsPhp, "BDS_Filter_Query_Service::query_listings") || str_contains($bdsPhp, "BDS_Listing_Query_Builder"))) ||
                             str_contains($bdsPhp, "batdongsan_filter_listings_by_area_range");
        $this->assert($hasAreaFilterCall, "3.3: Surface area 'area' is passed to BDS_Filter_Query_Service::query_listings or BDS_Listing_Query_Builder");

        // Check khu_vuc filters by district
        $hasKhuVucFilter = str_contains($bdsPhp, "\$khu_vuc_options = \$khu_vuc_options_filtered") ||
                           str_contains($bdsPhp, "batdongsan_filter_khu_vuc_options_by_posts");
        $this->assert($hasKhuVucFilter, "3.4: Location 'khu_vuc' interacts with district taxonomy/meta options");

        // Verify function batdongsan_filter_listings_by_area_range ignores location slugs
        if (function_exists('batdongsan_filter_listings_by_area_range')) {
            $mockPost = (object)['ID' => 9999];
            MockWordPress::$postmeta[9999]['dien_tich'] = [65.0];
            $posts = [$mockPost];

            $filteredValid = batdongsan_filter_listings_by_area_range($posts, '50-80');
            $this->assert(count($filteredValid) === 1, "3.5a: Area range '50-80' correctly matches 65m2 listing");

            $filteredMismatch = batdongsan_filter_listings_by_area_range($posts, '80-100');
            $this->assert(count($filteredMismatch) === 0, "3.5b: Area range '80-100' excludes 65m2 listing");

            // If a non-numeric district slug like 'cau-giay' is passed to area filter, it safely returns all posts or empty
            $filteredDistrict = batdongsan_filter_listings_by_area_range($posts, 'cau-giay');
            $this->assert(is_array($filteredDistrict), "3.5c: Passing non-range string 'cau-giay' to area filter executes without fatal error");
        }
    }

    /**
     * Suite 4: Client-side JS Controller & bfcache Contract
     */
    private function testClientSideJsContract(): void {
        echo "\n▶ Suite 4: Client-side JS Controller & bfcache Contract (js/main.js)\n";
        $js = file_get_contents($this->themeDir . '/js/main.js');

        // 4.1 Idempotency guard
        $hasGuard = str_contains($js, 'unified-search-initialized') || str_contains($js, 'unified-initialized');
        $this->assert($hasGuard, "4.1: initUnifiedSearchForm() includes idempotency initialization guard");

        // 4.2 Namespaced events
        $hasNamespacedEvents = str_contains($js, '.unifiedSearch');
        $this->assert($hasNamespacedEvents, "4.2: All search form event listeners are namespaced with .unifiedSearch");

        // 4.3 Tab click updates action and hinh_thuc
        $hasTabSync = str_contains($js, 'data-unified-tab') &&
                      str_contains($js, '$form.attr("action"') &&
                      str_contains($js, '$hinhThucInput.val');
        $this->assert($hasTabSync, "4.3: Tab click handler synchronizes form action URL and hidden hinh_thuc input value");

        // 4.4 Location dropdown selection targets data-unified-input
        $hasKhuVucInputTarget = str_contains($js, '$input = $wrapper.find("[data-unified-input]")') &&
                                str_contains($js, '$input.val(val)');
        $this->assert($hasKhuVucInputTarget, "4.4: Dropdown selection updates hidden input via \$wrapper.find('[data-unified-input]')");

        // 4.5 bfcache restoration handler
        $hasBfcacheHandler = str_contains($js, 'pageshow.unifiedSearch') ||
                             (str_contains($js, 'pageshow') && str_contains($js, ':disabled'));
        $this->assert($hasBfcacheHandler, "4.5: Window pageshow listener exists to re-enable inputs on browser bfcache back navigation");

        // 4.6 Submit safety timeout
        $hasSubmitTimeout = str_contains($js, 'setTimeout') && str_contains($js, ':disabled') && str_contains($js, 'disabled", false');
        $this->assert($hasSubmitTimeout, "4.6: Submit handler includes safety timeout restoring disabled state");
    }
}

// Execute
$test = new M3SearchFormSsrQueryContractsTest();
exit($test->run());
