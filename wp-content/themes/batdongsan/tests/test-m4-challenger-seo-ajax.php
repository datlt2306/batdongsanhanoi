<?php
/**
 * Milestone 4 Challenger Test Suite (Challenger M4_2):
 * Empirical Stress-Test Harness for AJAX Filter Responses & Dynamic SEO Title Generation
 *
 * Verifies:
 * 1. Dynamic SEO Titles (batdongsan_get_bds_filter_seo_title()):
 *    - District only: e.g. khu_vuc=cau-giay -> title includes "Cầu Giấy" and "Hà Nội"
 *    - Type + District: e.g. type=can-ho-chung-cu&khu_vuc=ba-dinh -> title includes "Căn hộ chung cư" and "Ba Đình"
 *    - Price range: e.g. price=3000-5000 -> price representation in title
 *    - Open-ended ranges: price=0-2000 ("Dưới 2 tỷ"), price=10000 ("Trên 10 tỷ"), area ranges
 *    - Transaction types (hinh_thuc: chuyen-nhuong, cho-thue, so-cap)
 *    - Project binding (du_an)
 *    - SEO rewritten query vars fallback (bds_khu_vuc, bds_type_slug, bds_filter_slug, bds_ban_slug)
 *    - Edge cases & falsy parameter suppression (empty array, zero string '0', empty strings)
 *    - WordPress document title filter integration (document_title_parts, pre_get_document_title)
 * 2. AJAX JSON Response Format & Performance Contract (inc/hooks/ajax-listings-filter.php):
 *    - Verbatim presence of update_post_caches($paged_posts, 'bat-dong-san', true, true);
 *    - Verbatim presence of response keys: count, found_posts, total_pages, max_num_pages, title
 *    - Empirical execution via MockWordPress::simulateAjax() asserting exact JSON schema and values
 *    - Empirical runtime interception confirming update_post_caches() execution on paged posts
 *    - Action alias parity: wp_ajax_bds_filter_listings and wp_ajax_batdongsan_filter_listings
 * 3. Client-side Controller Contracts (js/main.js):
 *    - document.title synchronization with response.data.title
 *    - Count updates binding with response.data.total / response.data.found_posts
 *    - Syntax validation
 *
 * @package BatDongSan
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use BDS\Tests\Framework\MockWordPress;

// Polyfills for standalone testing
if (!function_exists('get_term_by')) {
    function get_term_by(string $field, mixed $value, string $taxonomy = '', string $output = OBJECT, string $filter = 'raw'): object|false {
        foreach (MockWordPress::$terms as $term) {
            if ($taxonomy && $term->taxonomy !== $taxonomy) {
                continue;
            }
            if ($field === 'slug' && $term->slug === $value) {
                return $term;
            }
            if ($field === 'id' && (int) $term->term_id === (int) $value) {
                return $term;
            }
            if ($field === 'name' && $term->name === $value) {
                return $term;
            }
        }
        return false;
    }
}

if (!function_exists('get_term')) {
    function get_term(int|object $term, string $taxonomy = '', string $output = OBJECT, string $filter = 'raw'): ?object {
        $id = is_object($term) ? ($term->term_id ?? 0) : (int) $term;
        return MockWordPress::$terms[$id] ?? null;
    }
}

// Global spy tracker for update_post_caches
$GLOBALS['empirical_update_post_caches_invocations'] = [];

if (!function_exists('update_post_caches')) {
    function update_post_caches($posts, $post_type, $update_meta, $update_term): void {
        $GLOBALS['empirical_update_post_caches_invocations'][] = [
            'posts'       => $posts,
            'post_type'   => $post_type,
            'update_meta' => $update_meta,
            'update_term' => $update_term,
        ];
    }
}

// Global query_vars store for get_query_var polyfill
$GLOBALS['mock_query_vars'] = [];
if (!function_exists('get_query_var')) {
    function get_query_var($var, $default = '') {
        return $GLOBALS['mock_query_vars'][$var] ?? $default;
    }
}

require_once __DIR__ . '/../inc/helpers/filter-options.php';
require_once __DIR__ . '/../inc/helpers/filter-slug.php';
require_once __DIR__ . '/../inc/hooks/theme-setup.php';
require_once __DIR__ . '/../inc/hooks/ajax-listings-filter.php';

class M4ChallengerSeoAjaxTest {

    private int $passes = 0;
    private int $failures = 0;
    private array $findings = [];
    private array $failureDetails = [];
    private string $themeDir;

    public function __construct() {
        $this->themeDir = dirname(__DIR__);
    }

    private function pass(string $id, string $desc): void {
        $this->passes++;
        echo "  ✔ PASS: {$id} ({$desc})\n";
    }

    private function fail(string $id, string $reason): void {
        $this->failures++;
        $msg = "{$id}: {$reason}";
        $this->failureDetails[] = $msg;
        echo "  ✖ FAIL: {$msg}\n";
    }

    private function noteFinding(string $id, string $description): void {
        $this->findings[] = "{$id}: {$description}";
        echo "  ℹ FINDING: [{$id}] {$description}\n";
    }

    public function run(): void {
        echo "================================================================================\n";
        echo "  M4 CHALLENGER: AJAX FILTER SYNC & DYNAMIC SEO TITLE STRESS-TEST\n";
        echo "================================================================================\n\n";

        $this->suite1_seo_title_district_only();
        $this->suite2_seo_title_type_and_district();
        $this->suite3_seo_title_price_ranges();
        $this->suite4_seo_title_area_ranges();
        $this->suite5_seo_title_transaction_forms();
        $this->suite6_seo_title_project_binding();
        $this->suite7_seo_title_full_combination();
        $this->suite8_seo_title_query_vars_and_slug_fallbacks();
        $this->suite9_seo_title_edge_cases_and_falsy_inputs();
        $this->suite10_document_title_hooks_integration();
        $this->suite11_ajax_verbatim_code_contracts();
        $this->suite12_ajax_runtime_simulation_and_schema();
        $this->suite13_ajax_runtime_cache_priming_spy();
        $this->suite14_client_side_js_synchronization();

        echo "\n================================================================================\n";
        echo sprintf(" SUMMARY: %d Passed, %d Failed, %d Findings\n", $this->passes, $this->failures, count($this->findings));
        echo "================================================================================\n\n";

        if (!empty($this->findings)) {
            echo "EMPIRICAL FINDINGS & DISCREPANCIES NOTED:\n";
            foreach ($this->findings as $f) {
                echo " * {$f}\n";
            }
            echo "\n";
        }

        if ($this->failures > 0) {
            echo "FAILURES:\n";
            foreach ($this->failureDetails as $detail) {
                echo " - {$detail}\n";
            }
            exit(1);
        } else {
            echo "  [VERDICT: APPROVE] ALL M4 CHALLENGER EMPIRICAL VERIFICATION CRITERIA SATISFIED!\n\n";
        }
    }

    /**
     * Suite 1: District only SEO titles
     */
    private function suite1_seo_title_district_only(): void {
        echo "▶ Suite 1: Dynamic SEO Titles — District Only (Cầu Giấy, Ba Đình, Suburban)\n";

        // Test 1.1: Cầu Giấy
        $title = batdongsan_get_bds_filter_seo_title(['khu_vuc' => 'cau-giay']);
        if ($title !== null && str_contains($title, 'Cầu Giấy') && str_contains($title, 'Hà Nội')) {
            $this->pass('T1.1-District-CauGiay', "Returned: '{$title}'");
        } else {
            $this->fail('T1.1-District-CauGiay', "Expected title containing 'Cầu Giấy' and 'Hà Nội', got: " . var_export($title, true));
        }

        // Test 1.2: Ba Đình
        $title = batdongsan_get_bds_filter_seo_title(['khu_vuc' => 'ba-dinh']);
        if ($title !== null && str_contains($title, 'Ba Đình') && str_contains($title, 'Hà Nội')) {
            $this->pass('T1.2-District-BaDinh', "Returned: '{$title}'");
        } else {
            $this->fail('T1.2-District-BaDinh', "Expected title containing 'Ba Đình' and 'Hà Nội', got: " . var_export($title, true));
        }

        // Test 1.3: Tây Hồ
        $title = batdongsan_get_bds_filter_seo_title(['khu_vuc' => 'tay-ho']);
        if ($title !== null && str_contains($title, 'Tây Hồ') && str_contains($title, 'Hà Nội')) {
            $this->pass('T1.3-District-TayHo', "Returned: '{$title}'");
        } else {
            $this->fail('T1.3-District-TayHo', "Expected title containing 'Tây Hồ' and 'Hà Nội', got: " . var_export($title, true));
        }

        // Test 1.4: Suburban District: Hoài Đức
        $title = batdongsan_get_bds_filter_seo_title(['khu_vuc' => 'hoai-duc']);
        if ($title !== null && str_contains($title, 'Hoài Đức') && str_contains($title, 'Hà Nội')) {
            $this->pass('T1.4-District-HoaiDuc', "Returned: '{$title}'");
        } else {
            $this->fail('T1.4-District-HoaiDuc', "Expected title containing 'Hoài Đức' and 'Hà Nội', got: " . var_export($title, true));
        }

        // Test 1.5: Suburban District: Gia Lâm
        $title = batdongsan_get_bds_filter_seo_title(['khu_vuc' => 'gia-lam']);
        if ($title !== null && str_contains($title, 'Gia Lâm') && str_contains($title, 'Hà Nội')) {
            $this->pass('T1.5-District-GiaLam', "Returned: '{$title}'");
        } else {
            $this->fail('T1.5-District-GiaLam', "Expected title containing 'Gia Lâm' and 'Hà Nội', got: " . var_export($title, true));
        }

        // Test 1.6: Unknown custom district slug fallback
        $title = batdongsan_get_bds_filter_seo_title(['khu_vuc' => 'khu-do-thi-moi']);
        if ($title !== null && str_contains($title, 'Khu Do Thi Moi') && str_contains($title, 'Hà Nội')) {
            $this->pass('T1.6-District-Fallback-Formatting', "Returned: '{$title}'");
        } else {
            $this->fail('T1.6-District-Fallback-Formatting', "Fallback slug formatting failed, got: " . var_export($title, true));
        }
    }

    /**
     * Suite 2: Property Type + District
     */
    private function suite2_seo_title_type_and_district(): void {
        echo "▶ Suite 2: Dynamic SEO Titles — Property Type + District\n";

        // Test 2.1: Căn hộ chung cư + Ba Đình
        $title = batdongsan_get_bds_filter_seo_title([
            'type'    => 'can-ho-chung-cu',
            'khu_vuc' => 'ba-dinh',
        ]);
        if ($title !== null && stripos($title, 'Căn hộ chung cư') !== false && str_contains($title, 'Ba Đình')) {
            $this->pass('T2.1-Type-ChungCu-BaDinh', "Returned: '{$title}'");
        } else {
            $this->fail('T2.1-Type-ChungCu-BaDinh', "Expected 'Căn hộ chung cư' & 'Ba Đình', got: " . var_export($title, true));
        }

        // Test 2.2: Biệt thự liền kề + Tây Hồ
        $title = batdongsan_get_bds_filter_seo_title([
            'type'    => 'biet-thu-lien-ke',
            'khu_vuc' => 'tay-ho',
        ]);
        if ($title !== null && stripos($title, 'Biệt thự liền kề') !== false && str_contains($title, 'Tây Hồ')) {
            $this->pass('T2.2-Type-BietThu-TayHo', "Returned: '{$title}'");
        } else {
            $this->fail('T2.2-Type-BietThu-TayHo', "Expected 'Biệt thự liền kề' & 'Tây Hồ', got: " . var_export($title, true));
        }

        // Test 2.3: Nhà phố shophouse + Cầu Giấy
        $title = batdongsan_get_bds_filter_seo_title([
            'type'    => 'nha-pho-shophouse',
            'khu_vuc' => 'cau-giay',
        ]);
        if ($title !== null && stripos($title, 'Nhà phố shophouse') !== false && str_contains($title, 'Cầu Giấy')) {
            $this->pass('T2.3-Type-Shophouse-CauGiay', "Returned: '{$title}'");
        } else {
            $this->fail('T2.3-Type-Shophouse-CauGiay', "Expected 'Nhà phố shophouse' & 'Cầu Giấy', got: " . var_export($title, true));
        }

        // Test 2.4: Đất nền + Đông Anh
        $title = batdongsan_get_bds_filter_seo_title([
            'type'    => 'dat-nen',
            'khu_vuc' => 'dong-anh',
        ]);
        if ($title !== null && stripos($title, 'Đất nền') !== false && str_contains($title, 'Đông Anh')) {
            $this->pass('T2.4-Type-DatNen-DongAnh', "Returned: '{$title}'");
        } else {
            $this->fail('T2.4-Type-DatNen-DongAnh', "Expected 'Đất nền' & 'Đông Anh', got: " . var_export($title, true));
        }

        // Test 2.5: Type only without district defaults location to 'Hà Nội'
        $title = batdongsan_get_bds_filter_seo_title([
            'type' => 'can-ho-chung-cu',
        ]);
        if ($title !== null && stripos($title, 'Căn hộ chung cư') !== false && str_contains($title, 'Hà Nội')) {
            $this->pass('T2.5-Type-Only-Defaults-Hanoi', "Returned: '{$title}'");
        } else {
            $this->fail('T2.5-Type-Only-Defaults-Hanoi', "Expected 'Căn hộ chung cư' & 'Hà Nội', got: " . var_export($title, true));
        }
    }

    /**
     * Suite 3: Price Ranges
     */
    private function suite3_seo_title_price_ranges(): void {
        echo "▶ Suite 3: Dynamic SEO Titles — Price Ranges\n";

        // Test 3.1: Standard range 3000-5000 -> price representation in title
        $title = batdongsan_get_bds_filter_seo_title(['price' => '3000-5000']);
        if ($title !== null && (str_contains($title, '3 - 5 tỷ') || str_contains($title, '3 tỷ - 5 tỷ'))) {
            if (str_contains($title, '3 tỷ - 5 tỷ') && !str_contains($title, '3 - 5 tỷ')) {
                $this->noteFinding('F-PRICE-REDUNDANT-UNIT', "Price range '3000-5000' rendered as '3 tỷ - 5 tỷ' instead of concise '3 - 5 tỷ'");
            }
            $this->pass('T3.1-Price-3000-5000', "Returned: '{$title}'");
        } else {
            $this->fail('T3.1-Price-3000-5000', "Expected title containing '3 - 5 tỷ' or '3 tỷ - 5 tỷ', got: " . var_export($title, true));
        }

        // Test 3.2: Under threshold 0-2000 -> "Dưới 2 tỷ"
        $title = batdongsan_get_bds_filter_seo_title(['price' => '0-2000']);
        if ($title !== null && str_contains($title, 'Dưới 2 tỷ')) {
            $this->pass('T3.2-Price-Under-2-Billion', "Returned: '{$title}'");
        } else {
            $this->fail('T3.2-Price-Under-2-Billion', "Expected 'Dưới 2 tỷ', got: " . var_export($title, true));
        }

        // Test 3.3: Upper bound open-ended 10000 -> "Trên 10 tỷ"
        $title = batdongsan_get_bds_filter_seo_title(['price' => '10000']);
        if ($title !== null && str_contains($title, 'Trên 10 tỷ')) {
            $this->pass('T3.3-Price-Above-10-Billion', "Returned: '{$title}'");
        } else {
            $this->fail('T3.3-Price-Above-10-Billion', "Expected 'Trên 10 tỷ', got: " . var_export($title, true));
        }

        // Test 3.4: Open-ended hyphen format '10000-0' -> "Trên 10 tỷ"
        $title = batdongsan_get_bds_filter_seo_title(['price' => '10000-0']);
        if ($title !== null && str_contains($title, 'Trên 10 tỷ')) {
            $this->pass('T3.4-Price-Above-Hyphen-Format', "Returned: '{$title}'");
        } else {
            $this->fail('T3.4-Price-Above-Hyphen-Format', "Expected 'Trên 10 tỷ', got: " . var_export($title, true));
        }

        // Test 3.5: Million range 500-800 -> "500 - 800 triệu" or "500 triệu - 800 triệu"
        $title = batdongsan_get_bds_filter_seo_title(['price' => '500-800']);
        if ($title !== null && (str_contains($title, '500 - 800 triệu') || str_contains($title, '500 triệu - 800 triệu'))) {
            $this->pass('T3.5-Price-Million-Range', "Returned: '{$title}'");
        } else {
            $this->fail('T3.5-Price-Million-Range', "Expected million range, got: " . var_export($title, true));
        }

        // Test 3.6: Decimal value 3500-5000 -> "3,5 - 5 tỷ" or "3,5 tỷ - 5 tỷ"
        $title = batdongsan_get_bds_filter_seo_title(['price' => '3500-5000']);
        if ($title !== null && (str_contains($title, '3,5 - 5 tỷ') || str_contains($title, '3,5 tỷ - 5 tỷ') || str_contains($title, '3.5 - 5 tỷ'))) {
            $this->pass('T3.6-Price-Decimal-Billion', "Returned: '{$title}'");
        } else {
            $this->fail('T3.6-Price-Decimal-Billion', "Expected decimal billion range, got: " . var_export($title, true));
        }

        // Test 3.7: Negotiated price "thoa_thuan" -> "Thỏa thuận"
        $title = batdongsan_get_bds_filter_seo_title(['price' => 'thoa_thuan']);
        if ($title !== null && str_contains($title, 'Thỏa thuận')) {
            $this->pass('T3.7-Price-ThoaThuan', "Returned: '{$title}'");
        } else {
            $this->fail('T3.7-Price-ThoaThuan', "Expected 'Thỏa thuận', got: " . var_export($title, true));
        }
    }

    /**
     * Suite 4: Area Ranges
     */
    private function suite4_seo_title_area_ranges(): void {
        echo "▶ Suite 4: Dynamic SEO Titles — Area Ranges\n";

        // Test 4.1: Area range 50-80 -> "50 - 80 m²"
        $title = batdongsan_get_bds_filter_seo_title(['area' => '50-80']);
        if ($title !== null && str_contains($title, '50 - 80 m²')) {
            $this->pass('T4.1-Area-50-80', "Returned: '{$title}'");
        } else {
            $this->fail('T4.1-Area-50-80', "Expected '50 - 80 m²', got: " . var_export($title, true));
        }

        // Test 4.2: Under area 0-50 -> "Dưới 50 m²"
        $title = batdongsan_get_bds_filter_seo_title(['area' => '0-50']);
        if ($title !== null && str_contains($title, 'Dưới 50 m²')) {
            $this->pass('T4.2-Area-Under-50', "Returned: '{$title}'");
        } else {
            $this->fail('T4.2-Area-Under-50', "Expected 'Dưới 50 m²', got: " . var_export($title, true));
        }

        // Test 4.3: Above area 100-0 -> "Trên 100 m²"
        $title = batdongsan_get_bds_filter_seo_title(['area' => '100-0']);
        if ($title !== null && str_contains($title, 'Trên 100 m²')) {
            $this->pass('T4.3-Area-Above-100', "Returned: '{$title}'");
        } else {
            $this->fail('T4.3-Area-Above-100', "Expected 'Trên 100 m²', got: " . var_export($title, true));
        }
    }

    /**
     * Suite 5: Transaction Types (hinh_thuc)
     */
    private function suite5_seo_title_transaction_forms(): void {
        echo "▶ Suite 5: Dynamic SEO Titles — Transaction Forms (hinh_thuc)\n";

        // Test 5.1: Chuyển nhượng + Type + District
        $title = batdongsan_get_bds_filter_seo_title([
            'hinh_thuc' => 'chuyen-nhuong',
            'type'      => 'can-ho-chung-cu',
            'khu_vuc'   => 'cau-giay',
        ]);
        if ($title !== null && str_starts_with($title, 'Chuyển Nhượng Căn Hộ Chung Cư') && str_contains($title, 'Cầu Giấy, Hà Nội')) {
            $this->pass('T5.1-HinhThuc-ChuyenNhuong-Type-District', "Returned: '{$title}'");
        } else {
            $this->fail('T5.1-HinhThuc-ChuyenNhuong-Type-District', "Expected 'Chuyển Nhượng Căn Hộ Chung Cư Cầu Giấy, Hà Nội', got: " . var_export($title, true));
        }

        // Test 5.2: Cho thuê + Type + District
        $title = batdongsan_get_bds_filter_seo_title([
            'hinh_thuc' => 'cho-thue',
            'type'      => 'can-ho-chung-cu',
            'khu_vuc'   => 'ba-dinh',
        ]);
        if ($title !== null && str_starts_with($title, 'Cho Thuê Căn Hộ Chung Cư') && str_contains($title, 'Ba Đình, Hà Nội')) {
            $this->pass('T5.2-HinhThuc-ChoThue-Type-District', "Returned: '{$title}'");
        } else {
            $this->fail('T5.2-HinhThuc-ChoThue-Type-District', "Expected 'Cho Thuê Căn Hộ Chung Cư Ba Đình, Hà Nội', got: " . var_export($title, true));
        }

        // Test 5.3: Sơ cấp (Mở bán) + Type + District
        $title = batdongsan_get_bds_filter_seo_title([
            'hinh_thuc' => 'so-cap',
            'type'      => 'biet-thu-lien-ke',
            'khu_vuc'   => 'tay-ho',
        ]);
        if ($title !== null && str_starts_with($title, 'Mở Bán Biệt Thự Liền Kề') && str_contains($title, 'Tây Hồ, Hà Nội')) {
            $this->pass('T5.3-HinhThuc-SoCap-Type-District', "Returned: '{$title}'");
        } else {
            $this->fail('T5.3-HinhThuc-SoCap-Type-District', "Expected 'Mở Bán Biệt Thự Liền Kề Tây Hồ, Hà Nội', got: " . var_export($title, true));
        }

        // Test 5.4: Chuyển nhượng without Type -> "Hàng Chuyển Nhượng"
        $title = batdongsan_get_bds_filter_seo_title([
            'hinh_thuc' => 'chuyen-nhuong',
            'khu_vuc'   => 'cau-giay',
        ]);
        if ($title !== null && str_starts_with($title, 'Hàng Chuyển Nhượng') && str_contains($title, 'Cầu Giấy, Hà Nội')) {
            $this->pass('T5.4-HinhThuc-ChuyenNhuong-NoType', "Returned: '{$title}'");
        } else {
            $this->fail('T5.4-HinhThuc-ChuyenNhuong-NoType', "Expected 'Hàng Chuyển Nhượng Cầu Giấy, Hà Nội', got: " . var_export($title, true));
        }

        // Test 5.5: Cho thuê without Type -> "Cho Thuê Bất Động Sản"
        $title = batdongsan_get_bds_filter_seo_title([
            'hinh_thuc' => 'cho-thue',
            'khu_vuc'   => 'dong-da',
        ]);
        if ($title !== null && str_starts_with($title, 'Cho Thuê Bất Động Sản') && str_contains($title, 'Đống Đa, Hà Nội')) {
            $this->pass('T5.5-HinhThuc-ChoThue-NoType', "Returned: '{$title}'");
        } else {
            $this->fail('T5.5-HinhThuc-ChoThue-NoType', "Expected 'Cho Thuê Bất Động Sản Đống Đa, Hà Nội', got: " . var_export($title, true));
        }
    }

    /**
     * Suite 6: Project Binding (du_an)
     */
    private function suite6_seo_title_project_binding(): void {
        echo "▶ Suite 6: Dynamic SEO Titles — Project Binding (du_an)\n";

        MockWordPress::reset();
        $projectId = MockWordPress::insertPost([
            'post_title' => 'Vinhomes Metropolis',
            'post_type'  => 'du-an',
            'post_name'  => 'vinhomes-metropolis',
        ]);

        // Test 6.1: du_an numeric ID
        $title = batdongsan_get_bds_filter_seo_title([
            'du_an' => $projectId,
            'type'  => 'can-ho-chung-cu',
        ]);
        if ($title !== null && str_contains($title, 'Vinhomes Metropolis') && str_contains($title, 'Dự Án')) {
            $this->pass('T6.1-Project-NumericId', "Returned: '{$title}'");
        } else {
            $this->fail('T6.1-Project-NumericId', "Expected project title 'Vinhomes Metropolis', got: " . var_export($title, true));
        }
    }

    /**
     * Suite 7: Full Multi-Criteria Combination
     */
    private function suite7_seo_title_full_combination(): void {
        echo "▶ Suite 7: Dynamic SEO Titles — Multi-Criteria Full Combination\n";

        $title = batdongsan_get_bds_filter_seo_title([
            'hinh_thuc' => 'chuyen-nhuong',
            'type'      => 'can-ho-chung-cu',
            'khu_vuc'   => 'cau-giay',
            'price'     => '3000-5000',
            'area'      => '50-80',
        ]);

        $hasHinhThuc = str_contains($title ?? '', 'Chuyển Nhượng');
        $hasType     = stripos($title ?? '', 'Căn Hộ Chung Cư') !== false;
        $hasDistrict = str_contains($title ?? '', 'Cầu Giấy');
        $hasPrice    = str_contains($title ?? '', '3 - 5 tỷ') || str_contains($title ?? '', '3 tỷ - 5 tỷ');
        $hasArea     = str_contains($title ?? '', '50 - 80 m²');

        if ($hasHinhThuc && $hasType && $hasDistrict && $hasPrice && $hasArea) {
            $this->pass('T7.1-Full-Combo-7-Criteria', "Returned: '{$title}'");
        } else {
            $this->fail('T7.1-Full-Combo-7-Criteria', "Missing criteria in full combination: '{$title}'");
        }
    }

    /**
     * Suite 8: Query Vars & Slug Parsing Fallback
     */
    private function suite8_seo_title_query_vars_and_slug_fallbacks(): void {
        echo "▶ Suite 8: Dynamic SEO Titles — Query Vars & SEO Slug Parsing Fallback\n";

        $old_query_vars = $GLOBALS['mock_query_vars'];

        // Test 8.1: bds_khu_vuc query var
        $GLOBALS['mock_query_vars'] = ['bds_khu_vuc' => 'thanh-xuan'];
        $title = batdongsan_get_bds_filter_seo_title([]);
        if ($title !== null && str_contains($title, 'Thanh Xuân') && str_contains($title, 'Hà Nội')) {
            $this->pass('T8.1-QueryVar-District', "Returned: '{$title}'");
        } else {
            $this->fail('T8.1-QueryVar-District', "Failed reading from bds_khu_vuc query var, got: " . var_export($title, true));
        }

        // Test 8.2: bds_filter_slug with price and area (gia-tu-3-ty-den-5-ty-dt-tu-50m2-den-80m2)
        $GLOBALS['mock_query_vars'] = [
            'bds_khu_vuc'     => 'cau-giay',
            'bds_filter_slug' => 'gia-tu-3-ty-den-5-ty-dt-tu-50m2-den-80m2',
        ];
        $title = batdongsan_get_bds_filter_seo_title([]);
        $hasPrice = (str_contains($title ?? '', '3 - 5 tỷ') || str_contains($title ?? '', '3 tỷ - 5 tỷ'));
        $hasArea = str_contains($title ?? '', '50 - 80 m²');
        if ($title !== null && $hasPrice && $hasArea) {
            $this->pass('T8.2-QueryVar-FilterSlug-PriceAndArea', "Returned: '{$title}'");
        } else {
            $this->fail('T8.2-QueryVar-FilterSlug-PriceAndArea', "Failed parsing bds_filter_slug, got: " . var_export($title, true));
        }

        // Test 8.3: bat-dong-san-quan-{district} rule sets bds_type_slug='bat-dong-san', must be sanitized
        $GLOBALS['mock_query_vars'] = [
            'bds_type_slug' => 'bat-dong-san',
            'bds_khu_vuc'   => 'ha-dong',
        ];
        $title = batdongsan_get_bds_filter_seo_title([]);
        if ($title !== null && !str_contains($title, 'Bat Dong San Hà Đông') && str_contains($title, 'Bất Động Sản Hà Đông, Hà Nội')) {
            $this->pass('T8.3-Sanitize-BatDongSan-TypeSlug', "Returned: '{$title}'");
        } else {
            $this->fail('T8.3-Sanitize-BatDongSan-TypeSlug', "bds_type_slug 'bat-dong-san' not sanitized correctly, got: " . var_export($title, true));
        }

        $GLOBALS['mock_query_vars'] = $old_query_vars;
    }

    /**
     * Suite 9: Edge cases & falsy inputs
     */
    private function suite9_seo_title_edge_cases_and_falsy_inputs(): void {
        echo "▶ Suite 9: Dynamic SEO Titles — Edge Cases & Falsy Inputs\n";

        $old_query_vars = $GLOBALS['mock_query_vars'];
        $old_get = $_GET;

        $GLOBALS['mock_query_vars'] = [];
        $_GET = [];

        // Test 9.1: Empty parameters return null
        $title = batdongsan_get_bds_filter_seo_title([]);
        if ($title === null) {
            $this->pass('T9.1-Empty-Params-Returns-Null', "Correctly returned null for unconstrained request");
        } else {
            $this->fail('T9.1-Empty-Params-Returns-Null', "Expected null, got: " . var_export($title, true));
        }

        // Test 9.2: Falsy string '0' parameters return null
        $title = batdongsan_get_bds_filter_seo_title([
            'khu_vuc'   => '0',
            'type'      => '0',
            'price'     => '0',
            'area'      => '0',
            'hinh_thuc' => '',
        ]);
        if ($title === null) {
            $this->pass('T9.2-Falsy-Zero-Returns-Null', "Correctly suppressed '0' placeholders");
        } else {
            $this->fail('T9.2-Falsy-Zero-Returns-Null', "Expected null, got: " . var_export($title, true));
        }

        // Test 9.3: Empty strings return null
        $title = batdongsan_get_bds_filter_seo_title([
            'khu_vuc' => '',
            'type'    => '',
            'price'   => '',
        ]);
        if ($title === null) {
            $this->pass('T9.3-Empty-Strings-Returns-Null', "Correctly handled empty strings");
        } else {
            $this->fail('T9.3-Empty-Strings-Returns-Null', "Expected null, got: " . var_export($title, true));
        }

        $GLOBALS['mock_query_vars'] = $old_query_vars;
        $_GET = $old_get;
    }

    /**
     * Suite 10: WordPress Document Title Hooks Integration
     */
    private function suite10_document_title_hooks_integration(): void {
        echo "▶ Suite 10: WordPress Document Title Hooks Integration\n";

        $old_query_vars = $GLOBALS['mock_query_vars'];
        $GLOBALS['mock_query_vars'] = [
            'post_type'   => 'bat-dong-san',
            'bds_khu_vuc' => 'cau-giay',
        ];

        // Test 10.1: document_title_parts filter
        $parts = ['title' => 'Default BDS Title', 'site' => 'Bất Động Sản Hà Nội'];
        $filtered = batdongsan_filter_document_title_parts($parts);
        if (isset($filtered['title']) && str_contains($filtered['title'], 'Cầu Giấy, Hà Nội')) {
            $this->pass('T10.1-DocumentTitleParts-Filter', "Title parts updated to: '{$filtered['title']}'");
        } else {
            $this->fail('T10.1-DocumentTitleParts-Filter', "document_title_parts did not update title: " . var_export($filtered, true));
        }

        // Test 10.2: pre_get_document_title filter
        $pre_title = batdongsan_filter_pre_get_document_title('');
        if (!empty($pre_title) && str_contains($pre_title, 'Cầu Giấy, Hà Nội')) {
            $this->pass('T10.2-PreGetDocumentTitle-Filter', "Pre-title generated: '{$pre_title}'");
        } else {
            $this->fail('T10.2-PreGetDocumentTitle-Filter', "pre_get_document_title failed: " . var_export($pre_title, true));
        }

        $GLOBALS['mock_query_vars'] = $old_query_vars;
    }

    /**
     * Suite 11: AJAX Verbatim Code Contracts
     */
    private function suite11_ajax_verbatim_code_contracts(): void {
        echo "▶ Suite 11: AJAX Hook Verbatim Code Assertions\n";

        $filePath = $this->themeDir . '/inc/hooks/ajax-listings-filter.php';
        if (!file_exists($filePath)) {
            $this->fail('T11.1-Ajax-File-Exists', "File missing: {$filePath}");
            return;
        }

        $code = file_get_contents($filePath);

        // Test 11.1: update_post_caches verbatim presence
        $targetCacheCall = "update_post_caches(\$paged_posts, 'bat-dong-san', true, true);";
        if (str_contains($code, $targetCacheCall)) {
            $this->pass('T11.1-Verbatim-UpdatePostCaches', "Found verbatim '{$targetCacheCall}'");
        } else {
            $this->fail('T11.1-Verbatim-UpdatePostCaches', "Missing verbatim call '{$targetCacheCall}'");
        }

        // Test 11.2: Check all 5 required JSON keys in wp_send_json_success
        $requiredKeys = ['count', 'found_posts', 'total_pages', 'max_num_pages', 'title'];
        $missingKeys = [];
        foreach ($requiredKeys as $key) {
            $pattern = "/['\"]" . preg_quote($key, '/') . "['\"]\s*=>/";
            if (!preg_match($pattern, $code)) {
                $missingKeys[] = $key;
            }
        }

        if (empty($missingKeys)) {
            $this->pass('T11.2-Verbatim-Response-Keys', "All 5 required keys present: " . implode(', ', $requiredKeys));
        } else {
            $this->fail('T11.2-Verbatim-Response-Keys', "Missing keys in wp_send_json_success: " . implode(', ', $missingKeys));
        }
    }

    /**
     * Suite 12: Empirical AJAX Runtime Simulation & Schema
     */
    private function suite12_ajax_runtime_simulation_and_schema(): void {
        echo "▶ Suite 12: Empirical AJAX Simulation & JSON Response Schema\n";

        MockWordPress::reset();

        // Seed terms
        MockWordPress::insertTerm('Căn hộ chung cư', 'loai_hinh_bat_dong_san', 'can-ho-chung-cu', 33);
        MockWordPress::insertTerm('Cầu Giấy', 'khu-vuc', 'cau-giay', 50);

        // Seed posts
        $p1 = MockWordPress::insertPost([
            'post_title' => 'Chung cư 1 Cầu Giấy',
            'post_type'  => 'bat-dong-san',
            'tax_input'  => [
                'khu-vuc' => [50], // Cầu Giấy
                'loai_hinh_bat_dong_san' => [33], // Căn hộ chung cư
            ],
            'meta_input' => [
                'gia_trieu' => 3500,
                'dien_tich' => 65,
                'vi_tri'    => 'cau-giay',
            ],
        ]);

        $p2 = MockWordPress::insertPost([
            'post_title' => 'Chung cư 2 Cầu Giấy',
            'post_type'  => 'bat-dong-san',
            'tax_input'  => [
                'khu-vuc' => [50],
                'loai_hinh_bat_dong_san' => [33],
            ],
            'meta_input' => [
                'gia_trieu' => 4500,
                'dien_tich' => 75,
                'vi_tri'    => 'cau-giay',
            ],
        ]);

        $nonce = MockWordPress::createNonce('bds_filter_nonce');

        // Test 12.1: Basic AJAX request schema validation
        $res = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => $nonce,
        ]);

        if ($res['status_code'] !== 200 || empty($res['response']['success'])) {
            $this->fail('T12.1-Ajax-Status-200', "Expected status 200 and success=true, got status " . $res['status_code']);
            return;
        }
        $this->pass('T12.1-Ajax-Status-200', "Status 200 and success=true");

        $data = $res['response']['data'] ?? [];

        // Assert all 5 keys exist and have correct types
        $hasCount      = array_key_exists('count', $data) && is_int($data['count']);
        $hasFoundPosts = array_key_exists('found_posts', $data) && is_int($data['found_posts']);
        $hasTotalPages = array_key_exists('total_pages', $data) && is_int($data['total_pages']);
        $hasMaxPages   = array_key_exists('max_num_pages', $data) && is_int($data['max_num_pages']);
        $hasTitleKey   = array_key_exists('title', $data);

        if ($hasCount && $hasFoundPosts && $hasTotalPages && $hasMaxPages && $hasTitleKey) {
            $this->pass('T12.2-Ajax-Schema-Types', "All 5 keys (count, found_posts, total_pages, max_num_pages, title) present with exact types");
        } else {
            $this->fail('T12.2-Ajax-Schema-Types', "Schema keys missing or wrong type: " . json_encode(array_keys($data)));
        }

        // Test 12.3: Equivalence of aliases
        if ($data['count'] === $data['found_posts'] && $data['total_pages'] === $data['max_num_pages']) {
            $this->pass('T12.3-Ajax-Key-Aliases-Equivalence', "count === found_posts ({$data['count']}) & total_pages === max_num_pages ({$data['total_pages']})");
        } else {
            $this->fail('T12.3-Ajax-Key-Aliases-Equivalence', "Alias mismatch: count={$data['count']} vs found_posts={$data['found_posts']}");
        }

        // Test 12.4: Dynamic SEO Title on AJAX response with district filter
        $resDistrict = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce'   => $nonce,
            'khu_vuc' => 'cau-giay',
        ]);
        $districtTitle = $resDistrict['response']['data']['title'] ?? '';
        if (str_contains($districtTitle, 'Cầu Giấy') && str_contains($districtTitle, 'Hà Nội')) {
            $this->pass('T12.4-Ajax-Response-District-Title', "Title: '{$districtTitle}'");
        } else {
            $this->fail('T12.4-Ajax-Response-District-Title', "Expected district title, got: '{$districtTitle}'");
        }

        // Test 12.5: Dynamic SEO Title on AJAX response with type + district
        $resTypeDistrict = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce'   => $nonce,
            'type'    => 'can-ho-chung-cu',
            'khu_vuc' => 'ba-dinh',
        ]);
        $typeDistrictTitle = $resTypeDistrict['response']['data']['title'] ?? '';
        if (stripos($typeDistrictTitle, 'Căn hộ chung cư') !== false && str_contains($typeDistrictTitle, 'Ba Đình')) {
            $this->pass('T12.5-Ajax-Response-Type-District-Title', "Title: '{$typeDistrictTitle}'");
        } else {
            $this->fail('T12.5-Ajax-Response-Type-District-Title', "Expected type + district title, got: '{$typeDistrictTitle}'");
        }

        // Test 12.6: Dynamic SEO Title on AJAX response with price range
        $resPrice = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => $nonce,
            'price' => '3000-5000',
        ]);
        $priceTitle = $resPrice['response']['data']['title'] ?? '';
        if (str_contains($priceTitle, '3 - 5 tỷ') || str_contains($priceTitle, '3 tỷ - 5 tỷ')) {
            $this->pass('T12.6-Ajax-Response-Price-Title', "Title: '{$priceTitle}'");
        } else {
            $this->fail('T12.6-Ajax-Response-Price-Title', "Expected price title, got: '{$priceTitle}'");
        }

        // Test 12.7: Action alias wp_ajax_bds_filter_listings parity
        $resAlias = MockWordPress::simulateAjax('bds_filter_listings', [
            'nonce'   => $nonce,
            'khu_vuc' => 'cau-giay',
        ]);
        if ($resAlias['status_code'] === 200 && !empty($resAlias['response']['data']['title'])) {
            $this->pass('T12.7-Ajax-Alias-Parity', "Alias 'bds_filter_listings' functions identically");
        } else {
            $this->fail('T12.7-Ajax-Alias-Parity', "Alias 'bds_filter_listings' failed");
        }
    }

    /**
     * Suite 13: Empirical Interception of update_post_caches()
     */
    private function suite13_ajax_runtime_cache_priming_spy(): void {
        echo "▶ Suite 13: Empirical Interception of update_post_caches() Execution\n";

        $GLOBALS['empirical_update_post_caches_invocations'] = [];

        $nonce = MockWordPress::createNonce('bds_filter_nonce');
        $res = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => $nonce,
        ]);

        $calls = $GLOBALS['empirical_update_post_caches_invocations'];
        if (!empty($calls)) {
            $lastCall = end($calls);
            $correctType = ($lastCall['post_type'] === 'bat-dong-san');
            $correctMeta = ($lastCall['update_meta'] === true);
            $correctTerm = ($lastCall['update_term'] === true);
            $hasPosts    = !empty($lastCall['posts']);

            if ($correctType && $correctMeta && $correctTerm && $hasPosts) {
                $this->pass('T13.1-Runtime-UpdatePostCaches-Intercepted', sprintf(
                    "Intercepted call for %d posts, post_type='%s', update_meta=true, update_term=true",
                    count($lastCall['posts']),
                    $lastCall['post_type']
                ));
            } else {
                $this->fail('T13.1-Runtime-UpdatePostCaches-Intercepted', "Invalid arguments passed to update_post_caches: " . json_encode($lastCall));
            }
        } else {
            $this->fail('T13.1-Runtime-UpdatePostCaches-Intercepted', "update_post_caches was not invoked during AJAX execution");
        }
    }

    /**
     * Suite 14: Client-side JS Contracts in js/main.js
     */
    private function suite14_client_side_js_synchronization(): void {
        echo "▶ Suite 14: Client-Side JS Synchronization Contracts (js/main.js)\n";

        $mainJsPath = $this->themeDir . '/js/main.js';
        $content = file_get_contents($mainJsPath);

        // Test 14.1: Syntax check with node
        $cmd = "node -c " . escapeshellarg($mainJsPath) . " 2>&1";
        $output = shell_exec($cmd);
        if ($output === null || trim($output) === '') {
            $this->pass('T14.1-MainJs-Syntax-Clean', "node -c passed with 0 errors");
        } else {
            $this->fail('T14.1-MainJs-Syntax-Clean', "Syntax error: {$output}");
        }

        // Test 14.2: document.title sync presence
        if (str_contains($content, 'document.title = response.data.title')) {
            $this->pass('T14.2-Js-DocumentTitle-Sync', "document.title = response.data.title is present");
        } else {
            $this->fail('T14.2-Js-DocumentTitle-Sync', "Missing document.title sync in js/main.js");
        }

        // Test 14.3: total count fallback to response.data.found_posts
        if (str_contains($content, 'response.data.total !== undefined ? response.data.total : response.data.found_posts')
            || str_contains($content, 'response.data.found_posts')
        ) {
            $this->pass('T14.3-Js-Count-Fallback-FoundPosts', "Count controller supports found_posts fallback");
        } else {
            $this->fail('T14.3-Js-Count-Fallback-FoundPosts', "Missing found_posts fallback in count update");
        }

        // Test 14.4: active filter chips update
        if (str_contains($content, 'renderActiveFilterChips(response.data.active_filters)')) {
            $this->pass('T14.4-Js-ActiveFilterChips-Render', "renderActiveFilterChips(response.data.active_filters) is present");
        } else {
            $this->fail('T14.4-Js-ActiveFilterChips-Render', "Missing active filter chip rendering trigger");
        }
    }
}

$tester = new M4ChallengerSeoAjaxTest();
$tester->run();
