<?php

/**
 * Challenger M1_2 Empirical Stress Harness:
 * Functional Preservation of Filter Operations, Client JS, Query Integrity & Security Checks.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../inc/helpers/format.php';
require_once __DIR__ . '/../inc/helpers/filter-slug.php';
require_once __DIR__ . '/../inc/helpers/filter-options.php';
require_once __DIR__ . '/../inc/helpers/project.php';
require_once __DIR__ . '/../inc/services/queries.php';
require_once __DIR__ . '/../inc/services/price-ranges.php';
require_once __DIR__ . '/../inc/hooks/ajax-listings-filter.php';
require_once __DIR__ . '/../inc/hooks/ajax-project-filters.php';
require_once __DIR__ . '/../inc/hooks/leads.php';
if (!function_exists('get_term_by')) {
    function get_term_by(string $field, mixed $value, string $taxonomy = '', string $output = OBJECT, string $filter = 'raw'): object|false {
        foreach (\BDS\Tests\Framework\MockWordPress::$terms as $term) {
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
        return \BDS\Tests\Framework\MockWordPress::$terms[$id] ?? null;
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key(string $key): string {
        $key = strtolower($key);
        return preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}

use BDS\Tests\Framework\MockWordPress;
use BDS\Tests\Framework\TestCase;

class ChallengerM12EmpiricalStressHarness extends TestCase {
    public int $passed = 0;
    public int $failed = 0;

    public function __construct() {
        parent::__construct();
    }

    public function assert(bool $condition, string $description): void {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$description}\n";
        } else {
            $this->failed++;
            echo "  [FAIL] {$description}\n";
        }
    }

    public function runAll(): void {
        echo "================================================================================\n";
        echo "   CHALLENGER M1_2: EMPIRICAL FILTER & FUNCTIONAL STRESS-TEST HARNESS\n";
        echo "================================================================================\n\n";

        $this->suite1_ClientJsSyntaxAndExecution();
        $this->suite2_PopstateUrlParserParity();
        $this->suite3_DomContractIntegrity();
        $this->suite4_AjaxSecurityAndNonceEnforcement();
        $this->suite5_FilterQueryEngineIntegrity();
        $this->suite6_LeadCaptureIntegrity();

        $this->printSummary();
    }

    /**
     * Suite 1: Client JS Syntax & Node.js Execution Verification
     */
    private function suite1_ClientJsSyntaxAndExecution(): void {
        echo "▶ Suite 1: Client JS Syntax & Node.js Execution Verification\n";

        // 1.1 node -c js/main.js
        $mainJs = $this->themeDir . '/js/main.js';
        $cmd = 'node -c ' . escapeshellarg($mainJs) . ' 2>&1';
        $returnVar = 0;
        exec($cmd, $outLines, $returnVar);
        $this->assert($returnVar === 0, "1.1: 'node -c js/main.js' exits with code 0 without syntax errors");

        // 1.2 Check that no legacy red selectors were left in js/main.js that could break logic
        $code = file_get_contents($mainJs);
        $this->assert(
            strpos($code, "find('.bg-[#b20000]')") === false &&
            strpos($code, 'find(".bg-[#b20000]")') === false &&
            strpos($code, "$('.bg-[#b20000]')") === false &&
            strpos($code, '$(".bg-[#b20000]")') === false,
            "1.2: Zero jQuery class queries target legacy red color '.bg-[#b20000]'"
        );

        // 1.3 Check that main.js parseBdsUrlToParams regex block exists and is parsable
        $hasParser = preg_match('/const parseBdsUrlToParams = function\s*\(urlObj\)\s*\{/', $code);
        $this->assert((bool)$hasParser, "1.3: parseBdsUrlToParams function defined and intact in js/main.js");
    }

    /**
     * Suite 2: Popstate URL Parser Parity (JS vs PHP Backend)
     */
    private function suite2_PopstateUrlParserParity(): void {
        echo "\n▶ Suite 2: Popstate URL Parser Parity (JS vs PHP Backend)\n";

        $testUrls = [
            'http://example.com/bat-dong-san/' => [
                'expected' => []
            ],
            'http://example.com/bat-dong-san-quan-cau-giay/' => [
                'expected' => ['khu_vuc' => 'cau-giay']
            ],
            'http://example.com/bat-dong-san/gia-duoi-2-ty/' => [
                'expected' => ['price' => '0-2000']
            ],
            'http://example.com/bat-dong-san/gia-tu-3-ty-den-5-ty/' => [
                'expected' => ['price' => '3000-5000']
            ],
            'http://example.com/bat-dong-san/dt-tu-50m2-den-80m2/' => [
                'expected' => ['area' => '50-80']
            ],
            'http://example.com/bat-dong-san-quan-cau-giay/gia-tu-3-ty-den-5-ty-dt-tu-50m2-den-80m2/' => [
                'expected' => [
                    'khu_vuc' => 'cau-giay',
                    'price' => '3000-5000',
                    'area' => '50-80'
                ]
            ],
            'http://example.com/can-ho-chung-cu-quan-tay-ho/' => [
                'expected' => [
                    'type' => 'can-ho-chung-cu',
                    'khu_vuc' => 'tay-ho'
                ]
            ],
            'http://example.com/bat-dong-san/?so_phong_ngu=3&huong_nha=dong-nam&phap_ly=so-do' => [
                'expected' => [
                    'so_phong_ngu' => '3',
                    'huong_nha' => 'dong-nam',
                    'phap_ly' => 'so-do'
                ]
            ],
            'http://example.com/bat-dong-san/?hinh_thuc=chuyen-nhuong&paged=2' => [
                'expected' => [
                    'hinh_thuc' => 'chuyen-nhuong',
                    'paged' => '2'
                ]
            ]
        ];

        foreach ($testUrls as $urlStr => $spec) {
            $parsedJs = $this->executeMainJsParser($urlStr);
            foreach ($spec['expected'] as $k => $expectedVal) {
                $actual = $parsedJs[$k] ?? null;
                $this->assert(
                    $actual === $expectedVal,
                    "2.x: URL '{$urlStr}' -> key '{$k}' parsed by JS as '{$expectedVal}' (got '" . var_export($actual, true) . "')"
                );
            }
        }
    }

    private function executeMainJsParser(string $urlStr): array {
        $jsFile = $this->themeDir . '/js/main.js';
        $nodeScript = "
            const fs = require('fs');
            const code = fs.readFileSync('" . addslashes($jsFile) . "', 'utf8');
            const m = code.match(/(const parsePriceSlug = function[\\s\\S]*?return params;\\s*};)/);
            if (!m) {
                console.error('Cannot match parse function');
                process.exit(1);
            }
            const fn = new Function('return (function() {' + m[1] + ' return parseBdsUrlToParams; })()')();
            const res = fn(new URL('" . addslashes($urlStr) . "'));
            console.log(JSON.stringify(res));
        ";
        $output = shell_exec('node -e ' . escapeshellarg($nodeScript));
        $res = json_decode($output, true);
        return is_array($res) ? $res : [];
    }

    /**
     * Suite 3: DOM Contract Integrity Verification
     */
    private function suite3_DomContractIntegrity(): void {
        echo "\n▶ Suite 3: DOM Contract Integrity Verification\n";

        // Required DOM Selectors in page-bat-dong-san.php
        $pageBds = file_get_contents($this->themeDir . '/page-bat-dong-san.php');
        $this->assert(strpos($pageBds, 'id="bds-listings-grid"') !== false, "3.1: #bds-listings-grid exists in page-bat-dong-san.php");
        $this->assert(strpos($pageBds, 'id="bds-listings-pagination"') !== false, "3.2: #bds-listings-pagination exists in page-bat-dong-san.php");
        $this->assert(strpos($pageBds, 'id="bds-listings-count"') !== false, "3.3: #bds-listings-count exists in page-bat-dong-san.php");
        $this->assert(strpos($pageBds, 'id="bds-active-filters"') !== false, "3.4: #bds-active-filters exists in page-bat-dong-san.php");
        $this->assert(strpos($pageBds, 'id="bds-transaction-tabs"') !== false, "3.5: #bds-transaction-tabs exists in page-bat-dong-san.php");

        // Required inputs in template-parts/filter-bar-horizontal.php
        $filterBar = file_get_contents($this->themeDir . '/template-parts/filter-bar-horizontal.php');
        $this->assert(strpos($filterBar, 'class="filter-bar-horizontal__form') !== false, "3.6: .filter-bar-horizontal__form exists");
        $this->assert(strpos($filterBar, 'name="type"') !== false, "3.7: input[name='type'] exists in filter-bar-horizontal.php");
        $this->assert(strpos($filterBar, 'name="khu_vuc"') !== false, "3.8: input[name='khu_vuc'] exists in filter-bar-horizontal.php");
        $this->assert(strpos($filterBar, 'name="hinh_thuc"') !== false, "3.9: input[name='hinh_thuc'] exists in filter-bar-horizontal.php");
        $this->assert(strpos($filterBar, 'name="price"') !== false, "3.10: input[name='price'] exists in filter-bar-horizontal.php");
        $this->assert(strpos($filterBar, 'name="area"') !== false, "3.11: input[name='area'] exists in filter-bar-horizontal.php");
    }

    /**
     * Suite 4: AJAX Security & Nonce Enforcement
     */
    private function suite4_AjaxSecurityAndNonceEnforcement(): void {
        echo "\n▶ Suite 4: AJAX Security & Nonce Enforcement\n";
        MockWordPress::reset();

        // 4.1 batdongsan_filter_listings without nonce -> HTTP 403
        $res1 = MockWordPress::simulateAjax('batdongsan_filter_listings', ['paged' => 1]);
        $this->assert($res1['status_code'] === 403, "4.1: batdongsan_filter_listings rejects missing nonce with 403 Forbidden");

        // 4.2 batdongsan_filter_projects without nonce -> HTTP 403
        $res2 = MockWordPress::simulateAjax('batdongsan_filter_projects', []);
        $this->assert($res2['status_code'] === 403, "4.2: batdongsan_filter_projects rejects missing nonce with 403 Forbidden");

        // 4.3 batdongsan_submit_lead without nonce -> HTTP 403
        $res3 = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'fullname' => 'Test Lead',
            'phone'    => '0912345678',
        ]);
        $this->assert($res3['status_code'] === 403, "4.3: batdongsan_submit_lead rejects missing nonce with 403 Forbidden");
    }

    /**
     * Suite 5: Filter Query Engine Integrity
     */
    private function suite5_FilterQueryEngineIntegrity(): void {
        echo "\n▶ Suite 5: Filter Query Engine Integrity\n";
        MockWordPress::reset();
        MockWordPress::insertTerm('Căn hộ chung cư', 'loai_hinh_bat_dong_san', 'can-ho-chung-cu', 33);

        // Setup mock WP_Post with metadata
        $p1 = new \WP_Post();
        $p1->ID = 203;
        $p1->post_title = 'Căn hộ chung cư Cầu Giấy 3.5 tỷ';
        $p1->post_name = 'can-ho-chung-cu-cau-giay-3-5-ty';
        $p1->post_type = 'bat-dong-san';
        $p1->post_status = 'publish';
        $p1->post_date = '2026-10-01 10:00:00';
        MockWordPress::$posts[203] = $p1;

        $meta1 = [
            'dia_chi' => 'Quận Cầu Giấy, Hà Nội',
            'khoang_gia' => '3.5 tỷ',
            'dien_tich' => '65 m²',
            'so_phong_ngu' => 2,
            'so_phong_ve_sinh' => 2,
            'huong_nha' => 'dong_nam',
            'phap_ly' => 'so_do',
        ];
        foreach ($meta1 as $k => $v) {
            MockWordPress::updatePostMeta(203, $k, $v);
        }
        MockWordPress::setObjectTerms(203, [33], 'loai_hinh_bat_dong_san');

        $p2 = new \WP_Post();
        $p2->ID = 204;
        $p2->post_title = 'Biệt thự Tây Hồ 25 tỷ';
        $p2->post_name = 'biet-thu-tay-ho-25-ty';
        $p2->post_type = 'bat-dong-san';
        $p2->post_status = 'publish';
        $p2->post_date = '2026-10-01 10:00:00';
        MockWordPress::$posts[204] = $p2;

        $meta2 = [
            'dia_chi' => 'Quận Tây Hồ, Hà Nội',
            'khoang_gia' => '25 tỷ',
            'dien_tich' => '200 m²',
            'so_phong_ngu' => 5,
            'so_phong_ve_sinh' => 4,
            'huong_nha' => 'tay_bac',
            'phap_ly' => 'so_do',
        ];
        foreach ($meta2 as $k => $v) {
            MockWordPress::updatePostMeta(204, $k, $v);
        }

        // Test filter listings with valid nonce and specific criteria
        $res = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => wp_create_nonce('bds_filter_nonce'),
            'type' => '33',
            'price' => '3000-5000',
            'area' => '50-80',
            'so_phong_ngu' => '2',
            'huong_nha' => 'dong-nam',
            'paged' => 1
        ]);

        $this->assert($res['status_code'] === 200, "5.1: batdongsan_filter_listings returns HTTP 200 for valid request");
        $data = $res['response']['data'] ?? [];
        $this->assert(isset($data['total']) && $data['total'] === 1, "5.2: Multi-criteria filter correctly isolates target post (total=1)");
        $this->assert(strpos($data['html'] ?? '', 'Căn hộ chung cư Cầu Giấy') !== false, "5.3: HTML contains target post title");
        $this->assert(strpos($data['html'] ?? '', 'Biệt thự Tây Hồ') === false, "5.4: HTML excludes non-matching post title");
    }

    /**
     * Suite 6: Lead Capture Integrity
     */
    private function suite6_LeadCaptureIntegrity(): void {
        echo "\n▶ Suite 6: Lead Capture Integrity\n";
        MockWordPress::reset();

        // 6.1 Honeypot bot rejection
        $res1 = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => wp_create_nonce('bds_lead_nonce'),
            'fullname' => 'Spam Bot',
            'phone' => '0912345678',
            'bds_honeypot' => 'bot-filler-data'
        ]);
        $this->assert($res1['status_code'] === 400, "6.1: batdongsan_submit_lead rejects honeypot bot with HTTP 400");

        // 6.2 Valid lead submission
        $res2 = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => wp_create_nonce('bds_lead_nonce'),
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'email' => 'nguyenvana@example.com',
            'message' => 'Cần tư vấn căn hộ',
            'source_form' => 'Advisor Modal',
            'bds_honeypot' => ''
        ]);

        $this->assert($res2['status_code'] === 200, "6.2: Valid lead submission succeeds with HTTP 200");
        $this->assert(count(MockWordPress::$posts) === 1, "6.3: Lead post persisted in database");
        $leadPost = array_values(MockWordPress::$posts)[0];
        $this->assert($leadPost->post_type === 'khach-hang-lien-he', "6.4: Post type is 'khach-hang-lien-he'");
    }

    private function printSummary(): void {
        echo "================================================================================\n";
        echo " SUMMARY: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "================================================================================\n";
        if ($this->failed === 0) {
            echo " [VERDICT: APPROVE] Zero filter attributes, queries, or security checks regressed!\n";
        } else {
            echo " [VERDICT: REJECT] Regressions or defects detected!\n";
        }
    }
}

$harness = new ChallengerM12EmpiricalStressHarness();
$harness->runAll();
exit($harness->failed > 0 ? 1 : 0);
