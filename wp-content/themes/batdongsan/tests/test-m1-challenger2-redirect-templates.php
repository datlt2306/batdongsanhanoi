<?php
/**
 * Milestone 1 Challenger 2 Empirical Stress Test Harness.
 *
 * Authored by: m1_challenger_2 (Empirical Challenger: critic, specialist)
 * Mission:
 *  - Empirically challenge and stress-test the 301 Redirect Engine (`batdongsan_redirect_legacy_urls`)
 *  - Edge cases: Unicode Vietnamese URL paths, percent-encoded slugs, query string preservation, pagination subpaths (/page/2/)
 *  - Verify HTTP 301 redirect status on all legacy routes
 *  - Verify loop prevention on target routes
 *  - Verify legacy templates: single-hang-chuyen-nhuong.php, page-hangchuyenhuong.php, taxonomy-danh-muc-*.php, paths/hang-chuyen-nhuong-item.php
 *  - Clean execution with ZERO fatal errors
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

// Stubs for WordPress core functions if not present in bootstrap
if (!function_exists('add_query_arg')) {
    function add_query_arg(array|string $param1, string|false $param2 = false, string|false $url = false): string {
        if (is_array($param1)) {
            $queryArgs = $param1;
            $url = is_string($param2) ? $param2 : ($_SERVER['REQUEST_URI'] ?? '');
        } else {
            $queryArgs = [$param1 => $param2];
            $url = is_string($url) ? $url : ($_SERVER['REQUEST_URI'] ?? '');
        }
        $parts = explode('?', $url, 2);
        $base = $parts[0];
        $existing = [];
        if (!empty($parts[1])) {
            parse_str($parts[1], $existing);
        }
        $merged = array_merge($existing, $queryArgs);
        return $base . (!empty($merged) ? '?' . http_build_query($merged) : '');
    }
}

if (!function_exists('get_queried_object')) {
    function get_queried_object(): ?object {
        return $GLOBALS['wp_test_queried_object'] ?? null;
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type(int|object $post = 0): string {
        $p = get_post($post);
        return $p ? $p->post_type : 'post';
    }
}

if (!function_exists('wp_count_posts')) {
    function wp_count_posts(string $type = 'post'): object {
        $count = 0;
        foreach (\BDS\Tests\Framework\MockWordPress::$posts as $p) {
            if ($p->post_type === $type && $p->post_status === 'publish') {
                $count++;
            }
        }
        return (object)['publish' => $count, 'draft' => 0, 'trash' => 0];
    }
}

if (!function_exists('has_post_thumbnail')) {
    function has_post_thumbnail(int|object|null $post = null): bool {
        return false;
    }
}

if (!function_exists('get_post_thumbnail_id')) {
    function get_post_thumbnail_id(int|object|null $post = null): int {
        return 0;
    }
}

if (!function_exists('get_header')) {
    function get_header(string $name = null): void {}
}

if (!function_exists('get_footer')) {
    function get_footer(string $name = null): void {}
}

if (!function_exists('get_template_part')) {
    function get_template_part(string $slug, string $name = null, array $args = []): void {}
}

if (!function_exists('is_active_sidebar')) {
    function is_active_sidebar(string|int $index): bool {
        return false;
    }
}

if (!function_exists('dynamic_sidebar')) {
    function dynamic_sidebar(string|int $index = 1): bool {
        return true;
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post(string $data): string {
        return $data;
    }
}

if (!function_exists('date_i18n')) {
    function date_i18n(string $format, int|bool $timestamp_with_offset = false, bool $gmt = false): string {
        return date($format, $timestamp_with_offset ?: time());
    }
}

if (!function_exists('get_the_date')) {
    function get_the_date(string $format = '', int|object $post = null): string {
        return date($format ?: 'Y-m-d');
    }
}

if (!function_exists('get_the_content')) {
    function get_the_content(string $more_link_text = null, bool $strip_teaser = false, int|object $post = null): string {
        $p = get_post($post);
        return $p ? $p->post_content : '';
    }
}

if (!function_exists('the_title')) {
    function the_title(string $before = '', string $after = '', bool $display = true): string {
        $title = $before . get_the_title() . $after;
        if ($display) {
            echo $title;
        }
        return $title;
    }
}

if (!function_exists('the_content')) {
    function the_content(): void {
        echo get_the_content();
    }
}

if (!function_exists('get_the_ID')) {
    function get_the_ID(): int {
        return $GLOBALS['post']->ID ?? 0;
    }
}

if (!function_exists('have_posts')) {
    function have_posts(): bool {
        return isset($GLOBALS['wp_test_query']) && $GLOBALS['wp_test_query']->have_posts();
    }
}

if (!function_exists('the_post')) {
    function the_post(): void {
        if (isset($GLOBALS['wp_test_query'])) {
            $GLOBALS['wp_test_query']->the_post();
        }
    }
}

// Load theme redirect engine and helpers
require_once __DIR__ . '/../inc/helpers/format.php';
require_once __DIR__ . '/../inc/helpers/breadcrumb.php';
require_once __DIR__ . '/../inc/helpers/advisor.php';
require_once __DIR__ . '/../inc/helpers/video.php';
require_once __DIR__ . '/../inc/services/queries.php';
require_once __DIR__ . '/../inc/hooks/redirects.php';

use BDS\Tests\Framework\MockWordPress;
use BDS\Tests\Framework\RedirectExitException;

class M1Challenger2RedirectTemplatesHarness {

    private int $passes = 0;
    private int $failures = 0;
    private array $failureDetails = [];

    public function runAll(): bool {
        echo "================================================================================\n";
        echo "   MILESTONE 1 CHALLENGER 2: 301 REDIRECT ENGINE & TEMPLATES STRESS SUITE\n";
        echo "================================================================================\n\n";

        $this->test_01_unicode_vietnamese_paths();
        $this->test_02_percent_encoded_slugs();
        $this->test_03_query_string_preservation();
        $this->test_04_pagination_subpaths();
        $this->test_05_http_301_status_code_invariance();
        $this->test_06_redirect_loop_prevention();
        $this->test_07_unrelated_routes_non_interference();
        $this->test_08_page_hangchuyenhuong_clean_redirect();
        $this->test_09_taxonomy_legacy_templates_clean_redirect();
        $this->test_10_single_hang_chuyen_nhuong_fatal_error_free();
        $this->test_11_item_proxy_template_clean_render();

        echo "\n================================================================================\n";
        echo sprintf(" CHALLENGER 2 VERDICT METRICS: %d Passed, %d Failed\n", $this->passes, $this->failures);
        echo "================================================================================\n";

        if ($this->failures > 0) {
            echo "\n[FAILURES DETECTED]:\n";
            foreach ($this->failureDetails as $idx => $detail) {
                echo sprintf("  [%d] %s\n", $idx + 1, $detail);
            }
            echo "\n[FINAL VERDICT]: REJECT\n\n";
            return false;
        }

        echo "\n[FINAL VERDICT]: APPROVE — All 301 redirects and consolidated templates verified robust.\n\n";
        return true;
    }

    private function assert(bool $condition, string $description): void {
        if ($condition) {
            $this->passes++;
            echo "  ✔ PASS: {$description}\n";
        } else {
            $this->failures++;
            $this->failureDetails[] = $description;
            echo "  ✖ FAIL: {$description}\n";
        }
    }

    private function executeRedirect(string $requestUri): ?array {
        $parsed = parse_url($requestUri);
        $_SERVER['REQUEST_URI'] = $requestUri;
        $_GET = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $_GET);
        }

        try {
            batdongsan_redirect_legacy_urls();
            return null; // No redirect executed
        } catch (RedirectExitException $e) {
            return [
                'location' => $e->getLocation(),
                'status'   => $e->getStatus(),
            ];
        }
    }

    /**
     * TEST 1: Raw Unicode Vietnamese URL paths
     */
    private function test_01_unicode_vietnamese_paths(): void {
        echo "▶ [Group 1] Unicode Vietnamese URL Paths (Raw UTF-8)\n";

        $cases = [
            // Standard Vietnamese diacritics
            [
                'uri'      => '/hang-chuyen-nhuong/nhà-đất-hà-nội/',
                'expected' => '/bat-dong-san/' . rawurlencode('nhà-đất-hà-nội') . '/',
                'desc'     => 'Raw Unicode Vietnamese path: nhà-đất-hà-nội',
            ],
            [
                'uri'      => '/hang-chuyen-nhuong/căn-hộ-cao-cấp-đống-đa/',
                'expected' => '/bat-dong-san/' . rawurlencode('căn-hộ-cao-cấp-đống-đa') . '/',
                'desc'     => 'Raw Unicode Vietnamese path without trailing slash: căn-hộ-cao-cấp-đống-đa',
            ],
            [
                'uri'      => '/nha-dat-ban/biệt-thự-liền-kề-hà-đông/',
                'expected' => '/bat-dong-san/' . rawurlencode('biệt-thự-liền-kề-hà-đông') . '/',
                'desc'     => 'Legacy nha-dat-ban Unicode slug: biệt-thự-liền-kề-hà-đông',
            ],
            [
                'uri'      => '/bds-cho-thue/mặt-bằng-kinh-doanh-hoàn-kiếm/',
                'expected' => '/bat-dong-san/' . rawurlencode('mặt-bằng-kinh-doanh-hoàn-kiếm') . '/',
                'desc'     => 'Legacy bds-cho-thue Unicode slug: mặt-bằng-kinh-doanh-hoàn-kiếm',
            ],
            [
                'uri'      => '/danh-muc-hang-chuyen-nhuong/đất-thổ-cư/',
                'expected' => '/ban-' . rawurlencode('đất-thổ-cư') . '/',
                'desc'     => 'Legacy taxonomy Unicode slug: danh-muc-hang-chuyen-nhuong/đất-thổ-cư',
            ],
            [
                'uri'      => '/danh-muc-nha-dat-ban/nhà-riêng/',
                'expected' => '/ban-' . rawurlencode('nhà-riêng') . '/',
                'desc'     => 'Legacy taxonomy Unicode slug: danh-muc-nha-dat-ban/nhà-riêng',
            ],
            // Uppercase Vietnamese diacritics
            [
                'uri'      => '/hang-chuyen-nhuong/ĐẤT-NỀN-HÒA-LẠC/',
                'expected' => '/bat-dong-san/' . rawurlencode('ĐẤT-NỀN-HÒA-LẠC') . '/',
                'desc'     => 'Uppercase Vietnamese Unicode path: ĐẤT-NỀN-HÒA-LẠC',
            ],
            // Complex vowels & tones (ơ, ư, ẵ, ệ, ỗ, ỹ,...)
            [
                'uri'      => '/hang-chuyen-nhuong/dự-án-mỹ-đình-nguyễn-cơ-thạch/',
                'expected' => '/bat-dong-san/' . rawurlencode('dự-án-mỹ-đình-nguyễn-cơ-thạch') . '/',
                'desc'     => 'Complex Vietnamese vowels with multiple tones',
            ],
        ];

        foreach ($cases as $c) {
            $res = $this->executeRedirect($c['uri']);
            $this->assert($res !== null, "Redirect triggered: {$c['desc']}");
            if ($res !== null) {
                $this->assert($res['status'] === 301, "Status 301 for: {$c['desc']}");
                $this->assert(
                    str_contains($res['location'], $c['expected']),
                    "Location contains '{$c['expected']}' (Got: '{$res['location']}')"
                );
            }
        }
    }

    /**
     * TEST 2: Percent-encoded slugs
     */
    private function test_02_percent_encoded_slugs(): void {
        echo "\n▶ [Group 2] Percent-Encoded Slugs (RFC 3986 compliance)\n";

        $cases = [
            [
                'uri'      => '/hang-chuyen-nhuong/%C4%91%E1%BA%A5t-n%E1%BB%81n-%C4%91%C3%B4ng-anh/',
                'expected' => '/bat-dong-san/%C4%91%E1%BA%A5t-n%E1%BB%81n-%C4%91%C3%B4ng-anh/',
                'desc'     => 'Standard uppercase percent-encoded UTF-8 slug',
            ],
            [
                'uri'      => '/hang-chuyen-nhuong/%c4%91%e1%ba%a5t-n%e1%bb%81n-%c4%91%c3%b4ng-anh/',
                'expected' => '/bat-dong-san/%C4%91%E1%BA%A5t-n%E1%BB%81n-%C4%91%C3%B4ng-anh/',
                'desc'     => 'Lowercase percent-encoded hex slug',
            ],
            [
                'uri'      => '/nha-dat-ban/can-ho-chung-cu-vinhomes%20central%20park/',
                'expected' => '/bat-dong-san/can-ho-chung-cu-vinhomes%20central%20park/',
                'desc'     => 'Percent-encoded space (%20) in slug',
            ],
            [
                'uri'      => '/danh-muc-hang-chuyen-nhuong/%C4%91%E1%BA%A5t-n%E1%BB%81n/',
                'expected' => '/ban-%C4%91%E1%BA%A5t-n%E1%BB%81n/',
                'desc'     => 'Percent-encoded taxonomy slug',
            ],
            [
                'uri'      => '/cho-thue/%C4%91%E1%BA%A5t-n%E1%BB%81n-khu-c%C3%B4ng-nghi%E1%BB%87p/',
                'expected' => '/bat-dong-san/%C4%91%E1%BA%A5t-n%E1%BB%81n-khu-c%C3%B4ng-nghi%E1%BB%87p/',
                'desc'     => 'Rental slug percent-encoded: /cho-thue/đất-nền-khu-công-nghiệp',
            ],
        ];

        foreach ($cases as $c) {
            $res = $this->executeRedirect($c['uri']);
            $this->assert($res !== null, "Redirect triggered: {$c['desc']}");
            if ($res !== null) {
                $this->assert($res['status'] === 301, "Status 301: {$c['desc']}");
                $this->assert(
                    str_contains($res['location'], $c['expected']),
                    "Location contains expected slug (Got: {$res['location']})"
                );
            }
        }
    }

    /**
     * TEST 3: Query string preservation
     */
    private function test_03_query_string_preservation(): void {
        echo "\n▶ [Group 3] Query String Preservation Under All Variations\n";

        $cases = [
            // Single query param
            [
                'uri'          => '/hang-chuyen-nhuong/?utm_source=facebook',
                'expectedBase' => '/bat-dong-san/',
                'expectedQuery'=> ['hinh_thuc' => 'chuyen-nhuong', 'utm_source' => 'facebook'],
                'desc'         => 'Archive with single marketing param',
            ],
            // Multiple query params with filters
            [
                'uri'          => '/nha-dat-ban/?price_min=2000&price_max=5000&area_min=60',
                'expectedBase' => '/bat-dong-san/',
                'expectedQuery'=> ['price_min' => '2000', 'price_max' => '5000', 'area_min' => '60'],
                'desc'         => 'Archive with price and area range filters',
            ],
            // Preserving special characters and encoded strings in query
            [
                'uri'          => '/hang-chuyen-nhuong/biet-thu-ha-dong/?search=nh%C3%A0+ph%E1%BB%91&sort=desc',
                'expectedBase' => '/bat-dong-san/biet-thu-ha-dong/',
                'expectedQuery'=> ['search' => 'nhà phố', 'sort' => 'desc'],
                'desc'         => 'Single post preserving encoded search query',
            ],
            // Multiple tracking tags
            [
                'uri'          => '/bds-cho-thue/?utm_source=google&utm_medium=cpc&utm_campaign=summer&gclid=abc123xyz',
                'expectedBase' => '/bat-dong-san/',
                'expectedQuery'=> ['hinh_thuc' => 'cho-thue', 'utm_source' => 'google', 'utm_medium' => 'cpc', 'gclid' => 'abc123xyz'],
                'desc'         => 'Rental archive with multiple UTM tracking tags',
            ],
            // Legacy taxonomy with query args
            [
                'uri'          => '/danh-muc-hang-chuyen-nhuong/chung-cu/?view=compact&paged=2',
                'expectedBase' => '/ban-chung-cu/',
                'expectedQuery'=> ['view' => 'compact', 'paged' => '2'],
                'desc'         => 'Taxonomy route preserving view mode and paged param',
            ],
        ];

        foreach ($cases as $c) {
            $res = $this->executeRedirect($c['uri']);
            $this->assert($res !== null, "Redirect triggered: {$c['desc']}");
            if ($res !== null) {
                $this->assert($res['status'] === 301, "Status 301: {$c['desc']}");
                $this->assert(str_contains($res['location'], $c['expectedBase']), "Base URL contains {$c['expectedBase']}");

                // Parse query from redirect location
                $locQuery = parse_url($res['location'], PHP_URL_QUERY) ?? '';
                parse_str($locQuery, $actualParams);

                foreach ($c['expectedQuery'] as $qKey => $qVal) {
                    $this->assert(
                        isset($actualParams[$qKey]) && $actualParams[$qKey] === $qVal,
                        "Query arg '{$qKey}={$qVal}' preserved (Actual in query: " . ($actualParams[$qKey] ?? 'MISSING') . ")"
                    );
                }
            }
        }
    }

    /**
     * TEST 4: Pagination subpaths
     */
    private function test_04_pagination_subpaths(): void {
        echo "\n▶ [Group 4] Pagination Subpaths (/page/N/)\n";

        $cases = [
            // hang-chuyen-nhuong pagination
            [
                'uri'      => '/hang-chuyen-nhuong/page/2/',
                'expected' => '/bat-dong-san/page/2/?hinh_thuc=chuyen-nhuong',
                'desc'     => 'hang-chuyen-nhuong page 2 with trailing slash',
            ],
            [
                'uri'      => '/hang-chuyen-nhuong/page/2',
                'expected' => '/bat-dong-san/page/2/?hinh_thuc=chuyen-nhuong',
                'desc'     => 'hang-chuyen-nhuong page 2 without trailing slash',
            ],
            [
                'uri'      => '/hangchuyenhuong/page/10/',
                'expected' => '/bat-dong-san/page/10/?hinh_thuc=chuyen-nhuong',
                'desc'     => 'hangchuyenhuong page 10',
            ],
            // nha-dat-ban pagination
            [
                'uri'      => '/nha-dat-ban/page/3/',
                'expected' => '/bat-dong-san/page/3/',
                'desc'     => 'nha-dat-ban page 3 with trailing slash',
            ],
            [
                'uri'      => '/nha-dat-ban/page/5',
                'expected' => '/bat-dong-san/page/5/',
                'desc'     => 'nha-dat-ban page 5 without trailing slash',
            ],
            // rental pagination
            [
                'uri'      => '/bds-cho-thue/page/4/',
                'expected' => '/bat-dong-san/page/4/?hinh_thuc=cho-thue',
                'desc'     => 'bds-cho-thue page 4',
            ],
            [
                'uri'      => '/cho-thue/page/8/',
                'expected' => '/bat-dong-san/page/8/?hinh_thuc=cho-thue',
                'desc'     => 'cho-thue page 8',
            ],
            // taxonomy pagination
            [
                'uri'      => '/danh-muc-hang-chuyen-nhuong/chung-cu/page/3/',
                'expected' => '/ban-chung-cu/page/3/',
                'desc'     => 'danh-muc-hang-chuyen-nhuong/chung-cu page 3',
            ],
            [
                'uri'      => '/danh-muc-nha-dat-ban/biet-thu/page/7/',
                'expected' => '/ban-biet-thu/page/7/',
                'desc'     => 'danh-muc-nha-dat-ban/biet-thu page 7',
            ],
            // pagination combined with query args
            [
                'uri'      => '/hang-chuyen-nhuong/page/2/?sort=price_asc',
                'expected' => '/bat-dong-san/page/2/?hinh_thuc=chuyen-nhuong&sort=price_asc',
                'desc'     => 'hang-chuyen-nhuong page 2 with query sort=price_asc',
            ],
        ];

        foreach ($cases as $c) {
            $res = $this->executeRedirect($c['uri']);
            $this->assert($res !== null, "Redirect triggered: {$c['desc']}");
            if ($res !== null) {
                $this->assert($res['status'] === 301, "Status 301: {$c['desc']}");
                $this->assert(
                    str_contains($res['location'], $c['expected']),
                    "Location contains '{$c['expected']}' (Got: '{$res['location']}')"
                );
            }
        }
    }

    /**
     * TEST 5: HTTP 301 Status Code Invariance across 20+ legacy endpoints
     */
    private function test_05_http_301_status_code_invariance(): void {
        echo "\n▶ [Group 5] Strict HTTP 301 Status Code Verification\n";

        $endpoints = [
            '/hang-chuyen-nhuong/',
            '/hang-chuyen-nhuong',
            '/hangchuyenhuong/',
            '/nha-dat-ban/',
            '/nha-dat-ban',
            '/bds-cho-thue/',
            '/nha-dat-cho-thue/',
            '/cho-thue/',
            '/hang-chuyen-nhuong/post-1/',
            '/nha-dat-ban/post-2/',
            '/bds-cho-thue/post-3/',
            '/nha-dat-cho-thue/post-4/',
            '/cho-thue/post-5/',
            '/danh-muc-hang-chuyen-nhuong/cat-1/',
            '/danh-muc-nha-dat-ban/cat-2/',
            '/danh-muc-nha-dat-cho-thue/cat-3/',
            '/hang-chuyen-nhuong/page/2/',
            '/nha-dat-ban/page/3/',
            '/bds-cho-thue/page/4/',
        ];

        $all301 = true;
        foreach ($endpoints as $ep) {
            $res = $this->executeRedirect($ep);
            if ($res === null || $res['status'] !== 301) {
                $all301 = false;
                $this->assert(false, "Endpoint {$ep} failed 301 assertion (status: " . ($res['status'] ?? 'NONE') . ")");
            }
        }
        $this->assert($all301, "All " . count($endpoints) . " legacy test endpoints strictly return HTTP 301 Permanent Redirect");
    }

    /**
     * TEST 6: Redirect Loop Prevention
     */
    private function test_06_redirect_loop_prevention(): void {
        echo "\n▶ [Group 6] Redirect Loop Avoidance on Unified Targets\n";

        $targetRoutes = [
            '/bat-dong-san',
            '/bat-dong-san/',
            '/bat-dong-san/can-ho-vinhomes-ocean-park/',
            '/bat-dong-san/biet-thu-ha-dong/',
            '/bat-dong-san/page/2/',
            '/bat-dong-san/page/10/',
            '/bat-dong-san/?hinh_thuc=chuyen-nhuong',
            '/bat-dong-san/?hinh_thuc=cho-thue',
            '/bat-dong-san/?type=can-ho&khu_vuc=ha-dong',
            '/ban-can-ho-chung-cu/',
            '/ban-biet-thu-lien-ke/',
        ];

        $zeroLoops = true;
        foreach ($targetRoutes as $target) {
            $res = $this->executeRedirect($target);
            if ($res !== null) {
                $zeroLoops = false;
                $this->assert(false, "Target route {$target} caused loop redirect to {$res['location']}");
            }
        }
        $this->assert($zeroLoops, "All " . count($targetRoutes) . " target bat-dong-san routes bypassed without redirect loops");
    }

    /**
     * TEST 7: Unrelated Routes Non-Interference
     */
    private function test_07_unrelated_routes_non_interference(): void {
        echo "\n▶ [Group 7] Non-Interference with Normal WordPress Routes\n";

        $normalRoutes = [
            '/',
            '/du-an/',
            '/du-an/vinhomes-ocean-park/',
            '/du-an/page/2/',
            '/du-an-bat-dong-san/',
            '/tin-tuc/',
            '/tin-tuc/bai-viet-hay/',
            '/gioi-thieu/',
            '/lien-he/',
            '/wp-json/wp/v2/posts',
        ];

        $untouched = true;
        foreach ($normalRoutes as $r) {
            $res = $this->executeRedirect($r);
            if ($res !== null) {
                $untouched = false;
                $this->assert(false, "Route {$r} erroneously intercepted to {$res['location']}");
            }
        }
        $this->assert($untouched, "Normal WP routes untouched by legacy redirect engine");
    }

    /**
     * TEST 8: page-hangchuyenhuong.php Clean Redirection
     */
    private function test_08_page_hangchuyenhuong_clean_redirect(): void {
        echo "\n▶ [Group 8] Legacy Page Template: page-hangchuyenhuong.php\n";

        $templateFile = dirname(__DIR__) . '/page-hangchuyenhuong.php';
        $this->assert(file_exists($templateFile), "page-hangchuyenhuong.php exists");

        // 8.1 Direct execution with clean query string
        $_SERVER['QUERY_STRING'] = '';
        $caught = false;
        try {
            // Execute template in isolated closure
            (function() use ($templateFile) {
                include $templateFile;
            })();
        } catch (RedirectExitException $e) {
            $caught = true;
            $this->assert($e->getStatus() === 301, "page-hangchuyenhuong.php returns HTTP 301");
            $this->assert(str_contains($e->getLocation(), '/bat-dong-san/'), "Target points to /bat-dong-san/");
            $this->assert(str_contains($e->getLocation(), 'hinh_thuc=chuyen-nhuong'), "Target contains hinh_thuc=chuyen-nhuong");
        }
        $this->assert($caught, "page-hangchuyenhuong.php executed 301 redirect cleanly");

        // 8.2 Direct execution with existing query string parameters
        $_SERVER['QUERY_STRING'] = 'price=3000&area=70&utm_source=direct';
        $caughtQuery = false;
        try {
            (function() use ($templateFile) {
                include $templateFile;
            })();
        } catch (RedirectExitException $e) {
            $caughtQuery = true;
            $this->assert($e->getStatus() === 301, "page-hangchuyenhuong.php returns HTTP 301 with query");
            $this->assert(str_contains($e->getLocation(), 'price=3000'), "Preserved price=3000");
            $this->assert(str_contains($e->getLocation(), 'area=70'), "Preserved area=70");
            $this->assert(str_contains($e->getLocation(), 'utm_source=direct'), "Preserved utm_source=direct");
        }
        $this->assert($caughtQuery, "page-hangchuyenhuong.php preserved full query string on redirect");
    }

    /**
     * TEST 9: Legacy Taxonomy Templates Clean Redirection
     */
    private function test_09_taxonomy_legacy_templates_clean_redirect(): void {
        echo "\n▶ [Group 9] Legacy Taxonomy Templates Redirection\n";

        $taxHangChuyenNhuong = dirname(__DIR__) . '/taxonomy-danh-muc-hang-chuyen-nhuong.php';
        $taxNhaDatBan = dirname(__DIR__) . '/taxonomy-danh-muc-nha-dat-ban.php';

        $this->assert(file_exists($taxHangChuyenNhuong), "taxonomy-danh-muc-hang-chuyen-nhuong.php exists");
        $this->assert(file_exists($taxNhaDatBan), "taxonomy-danh-muc-nha-dat-ban.php exists");

        // 9.1 Test taxonomy-danh-muc-hang-chuyen-nhuong with queried term
        $GLOBALS['wp_test_queried_object'] = (object)['slug' => 'can-ho-chung-cu'];
        $_SERVER['QUERY_STRING'] = 'sort=price_desc';
        $caught1 = false;
        try {
            (function() use ($taxHangChuyenNhuong) {
                include $taxHangChuyenNhuong;
            })();
        } catch (RedirectExitException $e) {
            $caught1 = true;
            $this->assert($e->getStatus() === 301, "taxonomy-danh-muc-hang-chuyen-nhuong.php returns 301");
            $this->assert(str_contains($e->getLocation(), '/ban-can-ho-chung-cu/'), "Redirect target is /ban-can-ho-chung-cu/");
            $this->assert(str_contains($e->getLocation(), 'sort=price_desc'), "Preserved query string sort=price_desc");
        }
        $this->assert($caught1, "taxonomy-danh-muc-hang-chuyen-nhuong.php redirected cleanly");

        // 9.2 Test fallback when no term is queried
        $GLOBALS['wp_test_queried_object'] = null;
        $_SERVER['QUERY_STRING'] = '';
        $caught2 = false;
        try {
            (function() use ($taxHangChuyenNhuong) {
                include $taxHangChuyenNhuong;
            })();
        } catch (RedirectExitException $e) {
            $caught2 = true;
            $this->assert($e->getStatus() === 301, "Fallback returns 301");
            $this->assert(str_contains($e->getLocation(), '/bat-dong-san/'), "Fallback target is /bat-dong-san/");
        }
        $this->assert($caught2, "taxonomy-danh-muc-hang-chuyen-nhuong.php fallback cleanly redirected");

        // 9.3 Test taxonomy-danh-muc-nha-dat-ban with queried term
        $GLOBALS['wp_test_queried_object'] = (object)['slug' => 'biet-thu-lien-ke'];
        $_SERVER['QUERY_STRING'] = '';
        $caught3 = false;
        try {
            (function() use ($taxNhaDatBan) {
                include $taxNhaDatBan;
            })();
        } catch (RedirectExitException $e) {
            $caught3 = true;
            $this->assert($e->getStatus() === 301, "taxonomy-danh-muc-nha-dat-ban.php returns 301");
            $this->assert(str_contains($e->getLocation(), '/ban-biet-thu-lien-ke/'), "Target is /ban-biet-thu-lien-ke/");
        }
        $this->assert($caught3, "taxonomy-danh-muc-nha-dat-ban.php redirected cleanly");
    }

    /**
     * TEST 10: single-hang-chuyen-nhuong.php Proxy Execution & Zero Fatal Errors
     */
    private function test_10_single_hang_chuyen_nhuong_fatal_error_free(): void {
        echo "\n▶ [Group 10] single-hang-chuyen-nhuong.php Proxy Execution (Zero Fatal Errors)\n";

        MockWordPress::reset();
        $singleFile = dirname(__DIR__) . '/single-hang-chuyen-nhuong.php';
        $this->assert(file_exists($singleFile), "single-hang-chuyen-nhuong.php exists");

        // Create test project
        $projectId = MockWordPress::insertPost([
            'post_title' => 'Vinhomes Smart City',
            'post_name'  => 'vinhomes-smart-city',
            'post_type'  => 'du-an',
        ]);

        // Create test listing post (with normalized du_an_lien_quan integer)
        $listingId = MockWordPress::insertPost([
            'post_title'   => 'Căn hộ Studio Masteri West Heights',
            'post_name'    => 'can-ho-studio-masteri',
            'post_type'    => 'bat-dong-san',
            'post_content' => '<p>Căn hộ cao cấp tại Masteri West Heights, tầng đẹp, view hồ thoáng mát.</p>',
            'meta_input'   => [
                'dia_chi'            => 'Tây Mỗ, Nam Từ Liêm, Hà Nội',
                'khoang_gia'         => '2.5 tỷ',
                'gia_tren_met_vuong' => '~60 triệu/m²',
                'dien_tich'          => '42 m²',
                'du_an_lien_quan'    => (string)$projectId,
                'featured'           => '1',
                'trang_thai'         => 'dang_ban',
            ],
            'tax_input'    => [
                'hinh-thuc-bds'           => ['chuyen-nhuong'],
                'loai_hinh_bat_dong_san'  => ['can-ho-chung-cu'],
            ],
        ]);

        // Setup WP Query loop mock
        $postObj = MockWordPress::getPost($listingId);
        $mockQuery = new \WP_Query();
        $mockQuery->posts = [$postObj];
        $mockQuery->post_count = 1;
        $mockQuery->found_posts = 1;
        $GLOBALS['wp_test_query'] = $mockQuery;
        $GLOBALS['post'] = $postObj;

        // Buffer execution of single-hang-chuyen-nhuong.php
        ob_start();
        $executedCleanly = false;
        try {
            include $singleFile;
            $output = ob_get_clean();
            $executedCleanly = true;
        } catch (\Throwable $t) {
            ob_end_clean();
            $this->assert(false, "Fatal error during single-hang-chuyen-nhuong.php execution: " . $t->getMessage());
            return;
        }

        $this->assert($executedCleanly, "single-hang-chuyen-nhuong.php included and executed without throwing any error");
        $this->assert(str_contains($output, 'Căn hộ Studio Masteri West Heights'), "Output contains post title");
        $this->assert(str_contains($output, '2.5 tỷ'), "Output contains price");
        $this->assert(str_contains($output, '42 m²'), "Output contains area");
        $this->assert(str_contains($output, 'Nổi bật'), "Output contains featured badge");
        $this->assert(str_contains($output, 'Thông tin chi tiết'), "Output contains details section");

        // 10.2 Test regression: du_an_lien_quan is null / invalid post ID
        // In the original single-hang-chuyen-nhuong.php, line 72 threw fatal error on $du_an->name
        $listingId2 = MockWordPress::insertPost([
            'post_title' => 'Nhà đất không có dự án liên quan',
            'post_name'  => 'nha-dat-khong-du-an',
            'post_type'  => 'bat-dong-san',
            'meta_input' => [
                'du_an_lien_quan' => 0, // No project
            ],
        ]);
        $postObj2 = MockWordPress::getPost($listingId2);
        $mockQuery2 = new \WP_Query();
        $mockQuery2->posts = [$postObj2];
        $mockQuery2->post_count = 1;
        $GLOBALS['wp_test_query'] = $mockQuery2;
        $GLOBALS['post'] = $postObj2;

        ob_start();
        $regressionClean = false;
        try {
            include $singleFile;
            ob_end_clean();
            $regressionClean = true;
        } catch (\Throwable $t) {
            ob_end_clean();
            $this->assert(false, "Regression error on unassociated listing: " . $t->getMessage());
        }
        $this->assert($regressionClean, "Regression check: Listing with du_an_lien_quan=0 executes cleanly without error");

        unset($GLOBALS['wp_test_query'], $GLOBALS['post']);
    }

    /**
     * TEST 11: Item proxy template paths/hang-chuyen-nhuong-item.php
     */
    private function test_11_item_proxy_template_clean_render(): void {
        echo "\n▶ [Group 11] Item Proxy Template: paths/hang-chuyen-nhuong-item.php\n";

        $itemFile = dirname(__DIR__) . '/paths/hang-chuyen-nhuong-item.php';
        $this->assert(file_exists($itemFile), "paths/hang-chuyen-nhuong-item.php exists");

        $postId = MockWordPress::insertPost([
            'post_title' => 'Căn hộ Masteri West Heights Card Item',
            'post_name'  => 'can-ho-masteri-card',
            'post_type'  => 'bat-dong-san',
            'meta_input' => [
                'khoang_gia' => '3.2 tỷ',
                'dien_tich'  => '55 m²',
                'dia_chi'    => 'Nam Từ Liêm, Hà Nội',
            ],
        ]);

        $value = MockWordPress::getPost($postId);

        ob_start();
        $clean = false;
        try {
            include $itemFile;
            $output = ob_get_clean();
            $clean = true;
        } catch (\Throwable $t) {
            ob_end_clean();
            $this->assert(false, "Fatal error during item proxy execution: " . $t->getMessage());
            return;
        }

        $this->assert($clean, "paths/hang-chuyen-nhuong-item.php proxy executed without fatal error");
        $this->assert(str_contains($output, 'Căn hộ Masteri West Heights Card Item'), "Item output contains card title");
        $this->assert(str_contains($output, '3.2 tỷ'), "Item output contains price");
        $this->assert(str_contains($output, '55 m²'), "Item output contains area");
    }
}

// Execute harness
$harness = new M1Challenger2RedirectTemplatesHarness();
$success = $harness->runAll();
exit($success ? 0 : 1);
