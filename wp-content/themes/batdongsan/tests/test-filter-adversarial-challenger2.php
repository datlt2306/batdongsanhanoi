<?php
/**
 * Empirical Adversarial Test Harness — Challenger Iteration 2.
 *
 * Verifies that all 16 defects from Iteration 1 are genuinely resolved and
 * performs rigorous stress-testing of filter boundary conditions, state persistence,
 * edge cases, and client-side contracts.
 *
 * Usage:
 *   php tests/test-filter-adversarial-challenger2.php
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

require_once __DIR__ . '/framework/MockWordPress.php';

use BDS\Tests\Framework\MockWordPress;
use BDS\Tests\Framework\AjaxExitException;

if (!class_exists('WP_Query')) {
    class WP_Query {
        public static ?WP_Query $last_query = null;
        public array $query_vars = [];
        public array $posts = [];
        public int $post_count = 0;
        public int $found_posts = 0;
        public int $max_num_pages = 0;
        protected int $current_post = -1;

        public function __construct(array $args = []) {
            $this->init($args);
            self::$last_query = $this;
        }

        public function init(array $args = []): void {
            $this->query_vars = $args;
            $postType = $args['post_type'] ?? 'post';
            $postTypes = is_array($postType) ? $postType : [$postType];
            $postsPerPage = $args['posts_per_page'] ?? 10;
            $paged = $args['paged'] ?? 1;

            $matched = [];
            foreach (MockWordPress::$posts as $post) {
                if (!in_array($post->post_type, $postTypes, true) && !in_array('any', $postTypes, true)) {
                    continue;
                }
                if (!empty($args['post_status']) && $args['post_status'] !== 'any' && $post->post_status !== $args['post_status']) {
                    continue;
                }
                if (!empty($args['post__in']) && !in_array($post->ID, $args['post__in'], true)) {
                    continue;
                }
                if (!empty($args['post__not_in']) && in_array($post->ID, $args['post__not_in'], true)) {
                    continue;
                }

                // Tax query
                if (!empty($args['tax_query'])) {
                    $taxPass = true;
                    foreach ($args['tax_query'] as $k => $tq) {
                        if ($k === 'relation' || !is_array($tq)) continue;
                        $tax = $tq['taxonomy'] ?? '';
                        $field = $tq['field'] ?? 'term_id';
                        $terms = (array) ($tq['terms'] ?? []);
                        $postTermIds = MockWordPress::$object_terms[$post->ID][$tax] ?? [];
                        if ($field === 'term_id') {
                            $intersect = array_intersect($terms, $postTermIds);
                            if (empty($intersect)) { $taxPass = false; break; }
                        } elseif ($field === 'slug') {
                            $postSlugs = [];
                            foreach ($postTermIds as $tid) {
                                $t = MockWordPress::$terms[$tid] ?? null;
                                if ($t) $postSlugs[] = $t->slug;
                            }
                            $intersect = array_intersect($terms, $postSlugs);
                            if (empty($intersect)) { $taxPass = false; break; }
                        }
                    }
                    if (!$taxPass) continue;
                }

                // Meta query
                if (!empty($args['meta_query'])) {
                    $metaPass = true;
                    foreach ($args['meta_query'] as $k => $mq) {
                        if ($k === 'relation' || !is_array($mq)) continue;
                        $mKey = $mq['key'] ?? '';
                        $mVal = $mq['value'] ?? null;
                        $mCompare = strtoupper($mq['compare'] ?? '=');

                        $actualVal = MockWordPress::getPostMeta($post->ID, $mKey, true);

                        if ($mCompare === '=') {
                            if ((string)$actualVal !== (string)$mVal) { $metaPass = false; break; }
                        } elseif ($mCompare === '>=') {
                            $actNum = (float) $actualVal;
                            $expNum = (float) $mVal;
                            if ($actNum < $expNum) { $metaPass = false; break; }
                        } elseif ($mCompare === '<=') {
                            $actNum = (float) $actualVal;
                            $expNum = (float) $mVal;
                            if ($actNum > $expNum) { $metaPass = false; break; }
                        } elseif ($mCompare === 'LIKE') {
                            if (strpos((string)$actualVal, (string)$mVal) === false) { $metaPass = false; break; }
                        }
                    }
                    if (!$metaPass) continue;
                }

                $matched[] = $post;
            }

            $this->found_posts = count($matched);

            if ($postsPerPage === -1) {
                $this->posts = !empty($args['fields']) && $args['fields'] === 'ids' ? array_column($matched, 'ID') : $matched;
                $this->max_num_pages = 1;
            } else {
                $offset = ($paged - 1) * $postsPerPage;
                $sliced = array_slice($matched, $offset, $postsPerPage);
                $this->posts = !empty($args['fields']) && $args['fields'] === 'ids' ? array_column($sliced, 'ID') : $sliced;
                $this->max_num_pages = (int) ceil($this->found_posts / $postsPerPage);
            }

            $this->post_count = count($this->posts);
            $this->current_post = -1;
        }

        public function have_posts(): bool { return $this->current_post + 1 < $this->post_count; }
        public function the_post(): object {
            $this->current_post++;
            $p = $this->posts[$this->current_post];
            $GLOBALS['post'] = $p;
            return $p;
        }
        public function rewind_posts(): void { $this->current_post = -1; }
    }
}

require_once __DIR__ . '/bootstrap.php';

if (!function_exists('add_query_arg')) {
    function add_query_arg(array|string $param1, string|false $param2 = false, string|false $url = false): string {
        if (is_array($param1)) {
            $qs = http_build_query($param1);
            $target = $param2 ?: '';
            $sep = str_contains($target, '?') ? '&' : '?';
            return $target . $sep . $qs;
        }
        if (is_string($param1) && is_scalar($param2)) {
            $target = is_string($url) ? $url : '';
            $sep = str_contains($target, '?') ? '&' : '?';
            return $target . $sep . urlencode($param1) . '=' . urlencode((string) $param2);
        }
        return (string) $url;
    }
}

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

if (!function_exists('sanitize_key')) {
    function sanitize_key(string $key): string {
        $key = strtolower($key);
        return preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}

if (!function_exists('get_template_part')) {
    function get_template_part($slug, $name = null, $args = []) {
        $p = $args['post'] ?? null;
        $title = $p ? (is_object($p) ? ($p->post_title ?? '') : get_the_title($p)) : 'Mock Card';
        echo '<div class="bds-card-mock"><h3>' . esc_html((string)$title) . '</h3></div>';
    }
}

require_once __DIR__ . '/../inc/helpers/format.php';
require_once __DIR__ . '/../inc/helpers/filter-slug.php';
require_once __DIR__ . '/../inc/helpers/filter-options.php';
require_once __DIR__ . '/../inc/helpers/project.php';
require_once __DIR__ . '/../inc/services/price-ranges.php';
require_once __DIR__ . '/../inc/services/queries.php';
require_once __DIR__ . '/../inc/hooks/theme-setup.php';
require_once __DIR__ . '/../inc/hooks/acf-filters.php';
require_once __DIR__ . '/../inc/hooks/ajax-listings-filter.php';
require_once __DIR__ . '/../inc/hooks/ajax-project-filters.php';

class BDSAdversarialChallenger2 {
    private int $totalTests = 0;
    private int $passed = 0;
    private int $failed = 0;
    private array $defects = [];
    private int $autoPostId = 1000;

    private function createPostWithMeta(string $title, string $type, array $meta = [], ?int $customId = null): WP_Post {
        $id = $customId ?? ++$this->autoPostId;
        $p = new WP_Post();
        $p->ID = $id;
        $p->post_title = $title;
        $p->post_name = sanitize_title($title);
        $p->post_type = $type;
        $p->post_status = 'publish';
        $p->post_date = '2026-10-01 10:00:00';

        MockWordPress::$posts[$id] = $p;
        foreach ($meta as $k => $v) {
            MockWordPress::updatePostMeta($id, $k, $v);
        }
        return $p;
    }

    private function assert(bool $condition, string $id, string $description, string $failureDetails = ''): void {
        $this->totalTests++;
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$id}: {$description}\n";
        } else {
            $this->failed++;
            $msg = "  [DEFECT] {$id}: {$description}" . ($failureDetails ? " -> {$failureDetails}" : '');
            $this->defects[] = [
                'id' => $id,
                'desc' => $description,
                'details' => $failureDetails
            ];
            echo "{$msg}\n";
        }
    }

    public function runAll(): void {
        echo "====================================================================\n";
        echo "  BDS ADVERSARIAL CHALLENGER TEST HARNESS (CHALLENGER ITERATION 2)  \n";
        echo "====================================================================\n\n";

        $this->testSection1_Defects16_Verification();
        $this->testSection2_BoundaryAndRangeStress();
        $this->testSection3_AreaAndDecimalsStress();
        $this->testSection4_DiacriticsAndPricingStress();
        $this->testSection5_SuburbanDistrictsExhaustive();
        $this->testSection6_FrontendPopstateParserNode();
        $this->testSection7_ProjectPaginationExhaustive();
        $this->testSection8_SSRPaginationAndStatePersistence();
        $this->testSection9_TransientLifecycleCache();

        echo "\n--------------------------------------------------------------------\n";
        echo "Adversarial Stress Test Summary: {$this->passed} Passed, {$this->failed} Defect(s) Found\n";
        echo "Total Assertions: {$this->totalTests}\n";
        echo "--------------------------------------------------------------------\n";
    }

    /**
     * Section 1: Verification of the 16 Iteration 1 Defects.
     */
    private function testSection1_Defects16_Verification(): void {
        echo "--- 1. Verification of 16 Iteration 1 Defect Points ---\n";

        // Defect 1: Open-ended price filter '2000-0'
        $p1 = $this->createPostWithMeta('P1 - 3 tỷ', 'bat-dong-san', ['khoang_gia' => '3 tỷ']);
        $p2 = $this->createPostWithMeta('P2 - 1.5 tỷ', 'bat-dong-san', ['khoang_gia' => '1.5 tỷ']);
        $res = batdongsan_filter_listings_by_price_range([$p1, $p2], '2000-0');
        $this->assert(
            count($res) === 1 && $res[0]->ID === $p1->ID,
            'DEF-01',
            "Open-ended min price '2000-0' retains properties >= 2 tỷ and excludes < 2 tỷ",
            "Expected 1 post, got " . count($res)
        );

        // Defect 2: Open-ended area filter '50-0'
        $a1 = $this->createPostWithMeta('A1 - 80m2', 'bat-dong-san', ['dien_tich' => '80']);
        $a2 = $this->createPostWithMeta('A2 - 35m2', 'bat-dong-san', ['dien_tich' => '35']);
        $resArea = batdongsan_filter_listings_by_area_range([$a1, $a2], '50-0');
        $this->assert(
            count($resArea) === 1 && $resArea[0]->ID === $a1->ID,
            'DEF-02',
            "Open-ended min area '50-0' retains properties >= 50m2 and excludes < 50m2",
            "Expected 1 post, got " . count($resArea)
        );

        // Defect 3: Price slug formatting for '2000-0'
        $slugPrice = batdongsan_price_key_to_slug('2000-0');
        $this->assert(
            $slugPrice === 'gia-tren-2-ty',
            'DEF-03',
            "Price key '2000-0' formats to slug 'gia-tren-2-ty'",
            "Got '{$slugPrice}'"
        );

        // Defect 4: Area slug formatting for '50-0'
        $slugArea = batdongsan_area_key_to_slug('50-0');
        $this->assert(
            $slugArea === 'dt-tren-50m2',
            'DEF-04',
            "Area key '50-0' formats to slug 'dt-tren-50m2'",
            "Got '{$slugArea}'"
        );

        // Defect 5: Diacritic variants "Thoả thuận" / "Thỏa thuận"
        $pThoa = $this->createPostWithMeta('Thoả thuận post', 'bat-dong-san', ['khoang_gia' => 'Thoả thuận']);
        $isThoa = batdongsan_is_thoa_thuan_price($pThoa->ID);
        $parseThoa = batdongsan_parse_total_price_million('Thoả thuận');
        $this->assert(
            $isThoa === true && $parseThoa === null,
            'DEF-05',
            "Diacritic 'Thoả thuận' recognized as agreed price and total price is null",
            "isThoa=" . var_export($isThoa, true) . ", parseThoa=" . var_export($parseThoa, true)
        );

        // Defect 6: Decimal area '45.5'
        $pDecArea = $this->createPostWithMeta('Dec Area post', 'bat-dong-san', ['dien_tich' => '45.5 m²']);
        $areaNum = batdongsan_get_area_number($pDecArea->ID);
        $this->assert(
            $areaNum !== null && abs($areaNum - 45.5) < 0.001,
            'DEF-06',
            "batdongsan_get_area_number parses decimal '45.5 m²' as 45.5 (not 455)",
            "Got " . var_export($areaNum, true)
        );

        // Defect 7: String type slug in batdongsan_bds_filter_url()
        $urlSlugType = batdongsan_bds_filter_url(
            'http://example.com/bat-dong-san',
            '3000-5000',
            null,
            ['type' => 'can-ho-chung-cu', 'khu_vuc' => 'cau-giay']
        );
        $this->assert(
            str_contains($urlSlugType, 'can-ho-chung-cu-quan-cau-giay'),
            'DEF-07',
            "batdongsan_bds_filter_url preserves string type 'can-ho-chung-cu' in path",
            "Got '{$urlSlugType}'"
        );

        // Defects 8-11: Popstate paths
        $popTestCases = [
            'DEF-08' => ['url' => 'http://example.com/can-ho-chung-cu-quan-cau-giay/', 'check' => ['type' => 'can-ho-chung-cu', 'khu_vuc' => 'cau-giay']],
            'DEF-09' => ['url' => 'http://example.com/bat-dong-san/dt-tu-50m2-den-80m2/', 'check' => ['area' => '50-80']],
            'DEF-10' => ['url' => 'http://example.com/bat-dong-san/gia-duoi-2-ty/', 'check' => ['price' => '0-2000']],
            'DEF-11' => ['url' => 'http://example.com/bat-dong-san-quan-cau-giay/gia-tu-3-ty-den-5-ty-dt-tu-50m2-den-80m2/', 'check' => ['khu_vuc' => 'cau-giay', 'price' => '3000-5000', 'area' => '50-80']],
        ];
        foreach ($popTestCases as $dId => $tc) {
            $parsed = $this->runNodePopstateParser($tc['url']);
            $pass = true;
            foreach ($tc['check'] as $k => $v) {
                if (!isset($parsed[$k]) || $parsed[$k] !== $v) {
                    $pass = false;
                    break;
                }
            }
            $this->assert(
                $pass,
                $dId,
                "Node popstate parser decodes '{$tc['url']}' accurately",
                "Expected " . json_encode($tc['check']) . ", got " . json_encode($parsed)
            );
        }

        // Defect 12: Project AJAX suburban district 'hoai-duc' filtering
        MockWordPress::reset();
        $this->autoPostId = 2000;
        $proj1 = $this->createPostWithMeta('Dự án Hoài Đức', 'du-an', [
            'address' => 'Khu đô thị mới Kim Chung - Di Trạch, Huyện Hoài Đức, Hà Nội'
        ]);
        $proj2 = $this->createPostWithMeta('Dự án Cầu Giấy', 'du-an', [
            'address' => 'Trần Thái Tông, Cầu Giấy, Hà Nội'
        ]);

        $_POST = [
            'khu_vuc' => 'hoai-duc',
            'nonce'   => wp_create_nonce('batdongsan_filter_projects'),
        ];
        $ajaxProjData = $this->runAjaxProjectFilters();
        $this->assert(
            ($ajaxProjData['total'] ?? 0) === 1 && str_contains($ajaxProjData['html'] ?? '', 'Dự án Hoài Đức') && !str_contains($ajaxProjData['html'] ?? '', 'Dự án Cầu Giấy'),
            'DEF-12',
            "Project AJAX filter isolates suburban district 'hoai-duc' (1 result, not 2)",
            "Total: " . ($ajaxProjData['total'] ?? 'null')
        );

        // Defect 13: Project AJAX searchResults suburban label 'Hoài Đức'
        $this->assert(
            str_contains($ajaxProjData['searchResults'] ?? '', 'Hoài Đức'),
            'DEF-13',
            "Project AJAX searchResults contains suburban label 'Hoài Đức'",
            "Got: " . ($ajaxProjData['searchResults'] ?? '')
        );

        // Defect 14: Theme rewrite rules regex $du_an_districts_re contains 'thanh-tri'
        $setupContent = file_get_contents(dirname(__DIR__) . '/inc/hooks/theme-setup.php');
        $hasThanhTri = preg_match('/\$du_an_districts_re\s*=\s*\'[^\']*thanh-tri[^\']*\';/', $setupContent);
        $this->assert(
            (bool) $hasThanhTri,
            'DEF-14',
            "theme-setup.php \$du_an_districts_re includes 'thanh-tri'",
            "thanh-tri missing in regex"
        );

        // Defect 15: Underscore status 'dang_mo_ban' renders Vietnamese label
        MockWordPress::reset();
        $this->autoPostId = 3000;
        $projStatus = $this->createPostWithMeta('Dự án Đang Mở Bán', 'du-an', [
            'trang_thai' => 'dang_mo_ban'
        ]);
        $_POST = [
            'status' => 'dang_mo_ban',
            'nonce'  => wp_create_nonce('batdongsan_filter_projects'),
        ];
        $ajaxStatusData = $this->runAjaxProjectFilters();
        $this->assert(
            str_contains($ajaxStatusData['searchResults'] ?? '', 'Đang mở bán') && !str_contains($ajaxStatusData['searchResults'] ?? '', 'dang_mo_ban'),
            'DEF-15',
            "Project searchResults renders Vietnamese label 'Đang mở bán' for status 'dang_mo_ban'",
            "Got: " . ($ajaxStatusData['searchResults'] ?? '')
        );

        // Defect 16: SSR pagination in page-bat-dong-san.php passes extended criteria
        $pageBdsContent = file_get_contents(dirname(__DIR__) . '/page-bat-dong-san.php');
        $hasExtended = str_contains($pageBdsContent, "'so_phong_ngu'") &&
                       str_contains($pageBdsContent, "'so_phong_ve_sinh'") &&
                       str_contains($pageBdsContent, "'huong_nha'") &&
                       str_contains($pageBdsContent, "'phap_ly'");
        $this->assert(
            $hasExtended,
            'DEF-16',
            "page-bat-dong-san.php includes all 4 extended criteria in pagination query_args",
            "Missing extended criteria in page-bat-dong-san.php"
        );
    }

    /**
     * Section 2: Boundary & Range Stress-Testing.
     */
    private function testSection2_BoundaryAndRangeStress(): void {
        echo "--- 2. Boundary and Price Range Stress-Testing ---\n";

        MockWordPress::reset();
        $this->autoPostId = 4000;
        $post2000 = $this->createPostWithMeta('Post Exactly 2 tỷ', 'bat-dong-san', ['khoang_gia' => '2 tỷ']); // 2000 million
        $post1000 = $this->createPostWithMeta('Post 1 tỷ', 'bat-dong-san', ['khoang_gia' => '1 tỷ']); // 1000 million
        $post3000 = $this->createPostWithMeta('Post 3 tỷ', 'bat-dong-san', ['khoang_gia' => '3 tỷ']); // 3000 million

        $catalog = [$post1000, $post2000, $post3000];

        // Exact boundary matching on min
        $resMinMatch = batdongsan_filter_listings_by_price_range($catalog, '2000-0');
        $this->assert(
            count($resMinMatch) === 2, // 2000 and 3000
            'BND-PR-1',
            "Exact boundary min: '2000-0' matches property with exactly 2000 million (inclusive)",
            "Expected 2 posts, got " . count($resMinMatch)
        );

        // Exact boundary matching on max
        $resMaxMatch = batdongsan_filter_listings_by_price_range($catalog, '0-2000');
        $this->assert(
            count($resMaxMatch) === 2, // 1000 and 2000
            'BND-PR-2',
            "Exact boundary max: '0-2000' matches property with exactly 2000 million (inclusive)",
            "Expected 2 posts, got " . count($resMaxMatch)
        );

        // Exact closed interval
        $resClosed = batdongsan_filter_listings_by_price_range($catalog, '2000-2000');
        $this->assert(
            count($resClosed) === 1 && $resClosed[0]->ID === $post2000->ID,
            'BND-PR-3',
            "Exact range '2000-2000' matches only property with exactly 2000 million",
            "Expected 1 post, got " . count($resClosed)
        );

        // Inverted range
        $resInverted = batdongsan_filter_listings_by_price_range($catalog, '3000-1000');
        $this->assert(
            count($resInverted) === 0,
            'BND-PR-4',
            "Inverted range '3000-1000' safely yields 0 matches without fatal error",
            "Expected 0 posts, got " . count($resInverted)
        );

        // Falsy / empty bounds
        $resEmpty = batdongsan_filter_listings_by_price_range($catalog, '0-0');
        $this->assert(
            count($resEmpty) === 3,
            'BND-PR-5',
            "Zero bound '0-0' bypasses filtering and preserves all 3 posts",
            "Expected 3 posts, got " . count($resEmpty)
        );

        // Single number without dash represents lower bound
        $resSingle = batdongsan_filter_listings_by_price_range($catalog, '2500');
        $this->assert(
            count($resSingle) === 1 && $resSingle[0]->ID === $post3000->ID,
            'BND-PR-6',
            "Single number key '2500' behaves as open-ended lower bound (>= 2500)",
            "Expected 1 post, got " . count($resSingle)
        );

        // Price slug round-trips for high values
        $slugHigh = batdongsan_price_key_to_slug('60000');
        $restoredHigh = batdongsan_slug_to_price_key($slugHigh);
        $this->assert(
            $slugHigh === 'gia-tren-60-ty' && $restoredHigh === '60000',
            'BND-PR-7',
            "High price key '60000' formats to 'gia-tren-60-ty' and round-trips back to '60000'",
            "slug: '{$slugHigh}', restored: '{$restoredHigh}'"
        );
    }

    /**
     * Section 3: Area and Decimals Stress-Testing.
     */
    private function testSection3_AreaAndDecimalsStress(): void {
        echo "--- 3. Area and Decimals Stress-Testing ---\n";

        MockWordPress::reset();
        $this->autoPostId = 5000;
        $pA45_5 = $this->createPostWithMeta('45.5 m2', 'bat-dong-san', ['dien_tich' => '45.5']);
        $pA50 = $this->createPostWithMeta('50 m2', 'bat-dong-san', ['dien_tich' => '50']);
        $pA80 = $this->createPostWithMeta('80 m2', 'bat-dong-san', ['dien_tich' => '80 m²']);
        $pA120_5 = $this->createPostWithMeta('120.5 m2', 'bat-dong-san', ['dien_tich' => '120,5 m²']);

        $catalog = [$pA45_5, $pA50, $pA80, $pA120_5];

        // Decimal range filter 40-50 m2
        $res40_50 = batdongsan_filter_listings_by_area_range($catalog, '40-50');
        $this->assert(
            count($res40_50) === 2, // 45.5 and 50
            'BND-AR-1',
            "Area range '40-50' correctly includes 45.5m2 and 50m2",
            "Expected 2 posts, got " . count($res40_50)
        );

        // Exact boundary area 50 m2
        $resExact50 = batdongsan_filter_listings_by_area_range($catalog, '50-50');
        $this->assert(
            count($resExact50) === 1 && $resExact50[0]->ID === $pA50->ID,
            'BND-AR-2',
            "Exact area range '50-50' matches only property with exactly 50m2",
            "Expected 1 post, got " . count($resExact50)
        );

        // Vietnamese comma decimal in area parser
        $num120_5 = batdongsan_get_area_number($pA120_5->ID);
        $this->assert(
            $num120_5 !== null && abs($num120_5 - 120.5) < 0.001,
            'BND-AR-3',
            "batdongsan_get_area_number parses comma decimal '120,5 m²' as 120.5",
            "Got " . var_export($num120_5, true)
        );

        // Inverted area range
        $resInverted = batdongsan_filter_listings_by_area_range($catalog, '80-50');
        $this->assert(
            count($resInverted) === 0,
            'BND-AR-4',
            "Inverted area range '80-50' returns empty array without error",
            "Expected 0 posts, got " . count($resInverted)
        );

        // Area slug round-trip
        $slugArea = batdongsan_area_key_to_slug('80-150');
        $restoredArea = batdongsan_slug_to_area_key($slugArea);
        $this->assert(
            $slugArea === 'dt-tu-80m2-den-150m2' && $restoredArea === '80-150',
            'BND-AR-5',
            "Area slug 'dt-tu-80m2-den-150m2' round-trips to '80-150'",
            "Got slug '{$slugArea}', restored '{$restoredArea}'"
        );
    }

    /**
     * Section 4: Diacritics and Pricing Text Parsing Stress.
     */
    private function testSection4_DiacriticsAndPricingStress(): void {
        echo "--- 4. Diacritics and Pricing Text Parsing Stress ---\n";

        $variations = [
            'Thoả thuận'      => true,
            'Thỏa thuận'      => true,
            'THOẢ THUẬN'      => true,
            'thoa thuan'      => true,
            'Liên hệ'         => true,
            'lien he'         => true,
            'Call Zalo'       => true,
            '3 tỷ'            => false,
            '500 triệu'       => false,
        ];

        foreach ($variations as $text => $isAgreed) {
            $p = $this->createPostWithMeta("Price Test: {$text}", 'bat-dong-san', ['khoang_gia' => $text]);
            $res = batdongsan_is_thoa_thuan_price($p->ID);
            $this->assert(
                $res === $isAgreed,
                "DIAC-" . md5($text),
                "batdongsan_is_thoa_thuan_price('{$text}') returns " . ($isAgreed ? 'true' : 'false'),
                "Got " . var_export($res, true)
            );
        }

        // Price parsing variations
        $priceCases = [
            '3,5 tỷ'               => 3500.0,
            '3.5 tỷ'               => 3500.0,
            '500 - 800 triệu'      => 500.0,
            '3 - 5 tỷ'             => 3000.0,
            '800 triệu - 1 tỷ'     => 800.0,
            '1 tỷ đến 2 tỷ'        => 1000.0,
            '45 triệu/m²'          => null, // Unit rate should not be parsed as total price
            '15 tr/tháng'          => null, // Rental rate per month should not be parsed as total price
        ];

        foreach ($priceCases as $text => $expected) {
            $parsed = batdongsan_parse_total_price_million($text);
            if ($expected === null) {
                $this->assert(
                    $parsed === null,
                    "PARSE-" . md5($text),
                    "batdongsan_parse_total_price_million('{$text}') yields null (not total price)",
                    "Got " . var_export($parsed, true)
                );
            } else {
                $this->assert(
                    $parsed !== null && abs($parsed - $expected) < 0.01,
                    "PARSE-" . md5($text),
                    "batdongsan_parse_total_price_million('{$text}') yields {$expected} triệu",
                    "Got " . var_export($parsed, true)
                );
            }
        }
    }

    /**
     * Section 5: Suburban Districts Exhaustive Coverage.
     */
    private function testSection5_SuburbanDistrictsExhaustive(): void {
        echo "--- 5. Suburban Districts Exhaustive Coverage (All 30 Districts) ---\n";

        $expected30 = [
            'ba-dinh', 'hoan-kiem', 'tay-ho', 'long-bien', 'cau-giay', 'dong-da',
            'hai-ba-trung', 'hoang-mai', 'thanh-xuan', 'ha-dong', 'nam-tu-liem', 'bac-tu-liem',
            'hoai-duc', 'dan-phuong', 'gia-lam', 'dong-anh', 'soc-son', 'thanh-tri',
            'me-linh', 'quoc-oai', 'thach-that', 'chuong-my', 'thanh-oai', 'thuong-tin',
            'phu-xuyen', 'ung-hoa', 'my-duc', 'son-tay', 'ba-vi', 'phuc-tho'
        ];

        // 1. Check batdongsan_get_area_options(false)
        $strictAreas = batdongsan_get_area_options(false);
        $this->assert(
            count($strictAreas) === 30,
            'DIST-01',
            "batdongsan_get_area_options(false) contains exactly 30 administrative divisions",
            "Count: " . count($strictAreas)
        );

        $missingInOptions = array_diff($expected30, array_keys($strictAreas));
        $this->assert(
            empty($missingInOptions),
            'DIST-02',
            "All 30 Hanoi districts present in batdongsan_get_area_options(false)",
            "Missing: " . implode(', ', $missingInOptions)
        );

        // 2. Check rewrite regex in theme-setup.php matches all 30 districts
        $setupContent = file_get_contents(dirname(__DIR__) . '/inc/hooks/theme-setup.php');
        if (preg_match('/\$du_an_districts_re\s*=\s*\'\(([^\)]+)\)\';/', $setupContent, $m)) {
            $regexDistricts = explode('|', $m[1]);
            $missingInRegex = array_diff($expected30, $regexDistricts);
            $this->assert(
                empty($missingInRegex),
                'DIST-03',
                "\$du_an_districts_re contains all 30 districts (0 missing)",
                "Missing in regex: " . implode(', ', $missingInRegex)
            );
        } else {
            $this->assert(false, 'DIST-03', "Could not extract \$du_an_districts_re regex from theme-setup.php");
        }

        // 3. Test project AJAX filtering across multiple suburban districts
        $suburbanSamples = ['dan-phuong' => 'Đan Phượng', 'dong-anh' => 'Đông Anh', 'thanh-tri' => 'Thanh Trì', 'soc-son' => 'Sóc Sơn'];
        foreach ($suburbanSamples as $slug => $label) {
            MockWordPress::reset();
            $this->autoPostId = 6000;
            $targetProj = $this->createPostWithMeta("Dự án {$label}", 'du-an', [
                'address' => "Địa chỉ tại Huyện {$label}, Hà Nội"
            ]);
            $otherProj = $this->createPostWithMeta("Dự án Quận Khác", 'du-an', [
                'address' => "Trần Hưng Đạo, Hoàn Kiếm, Hà Nội"
            ]);

            $_POST = [
                'khu_vuc' => $slug,
                'nonce'   => wp_create_nonce('batdongsan_filter_projects'),
            ];
            $res = $this->runAjaxProjectFilters();
            $this->assert(
                ($res['total'] ?? 0) === 1 && str_contains($res['html'] ?? '', "Dự án {$label}"),
                "DIST-AJAX-{$slug}",
                "Project AJAX endpoint successfully filters suburban district '{$slug}' ({$label})",
                "Total: " . ($res['total'] ?? 'null')
            );
        }
    }

    /**
     * Section 6: Node.js Execution of Frontend Popstate Parser from main.js.
     */
    private function testSection6_FrontendPopstateParserNode(): void {
        echo "--- 6. Client-Side Popstate Parser Matrix (Node.js Execution) ---\n";

        $matrix = [
            'http://example.com/ban-can-ho-chung-cu-dien-tich-tu-50m2-den-80m2-gia-tu-3-ty-den-5-ty/' => [
                'type'  => 'can-ho-chung-cu',
                'area'  => '50-80',
                'price' => '3000-5000',
            ],
            'http://example.com/ban-nha-rieng-gia-duoi-2-ty/' => [
                'type'  => 'nha-rieng',
                'price' => '0-2000',
            ],
            'http://example.com/ban-dat-nen-dien-tich-tren-100m2/' => [
                'type' => 'dat-nen',
                'area' => '100',
            ],
            'http://example.com/bat-dong-san-quan-thanh-tri/gia-tren-5-ty-dt-tu-50m2-den-80m2/' => [
                'khu_vuc' => 'thanh-tri',
                'price'   => '5000',
                'area'    => '50-80',
            ],
            'http://example.com/can-ho-chung-cu-quan-nam-tu-liem/page/3/' => [
                'type'    => 'can-ho-chung-cu',
                'khu_vuc' => 'nam-tu-liem',
                'paged'   => 3,
            ],
            'http://example.com/bat-dong-san/?so_phong_ngu=3&huong_nha=dong-nam&phap_ly=so-do' => [
                'so_phong_ngu' => '3',
                'huong_nha'    => 'dong-nam',
                'phap_ly'      => 'so-do',
            ],
        ];

        foreach ($matrix as $url => $expected) {
            $parsed = $this->runNodePopstateParser($url);
            $pass = true;
            foreach ($expected as $k => $v) {
                if (!isset($parsed[$k]) || (string)$parsed[$k] !== (string)$v) {
                    $pass = false;
                    break;
                }
            }
            $this->assert(
                $pass,
                "POP-URL-" . md5($url),
                "parseBdsUrlToParams decodes '{$url}' correctly",
                "Expected " . json_encode($expected) . ", got " . json_encode($parsed)
            );
        }
    }

    /**
     * Section 7: Project Pagination Exhaustive Verification.
     */
    private function testSection7_ProjectPaginationExhaustive(): void {
        echo "--- 7. Project Pagination Exhaustive Verification ---\n";

        // Create 25 projects in database
        MockWordPress::reset();
        $this->autoPostId = 7000;
        for ($i = 1; $i <= 25; $i++) {
            $this->createPostWithMeta("Project #{$i}", 'du-an', ['address' => 'Địa chỉ chung Hà Nội']);
        }

        // Test Page 1 (should have 9 items, total=25, total_pages=3, page 1 active, link to page 2)
        $_POST = [
            'paged' => 1,
            'nonce' => wp_create_nonce('batdongsan_filter_projects'),
        ];
        $resP1 = $this->runAjaxProjectFilters();
        $this->assert(
            ($resP1['total'] ?? 0) === 25 && ($resP1['total_pages'] ?? 0) === 3,
            'PAG-PROJ-1',
            "Project AJAX Page 1: total=25, total_pages=3 (not capped to 9)",
            "total: " . ($resP1['total'] ?? 'null') . ", total_pages: " . ($resP1['total_pages'] ?? 'null')
        );
        $this->assert(
            str_contains($resP1['pagination'] ?? '', 'data-page="2"'),
            'PAG-PROJ-1b',
            "Project AJAX Page 1 contains link to page 2",
            "Pagination HTML: " . ($resP1['pagination'] ?? '')
        );

        // Test Page 2 (should have 9 items, active page 2, links to page 1 and 3)
        $_POST = [
            'paged' => 2,
            'nonce' => wp_create_nonce('batdongsan_filter_projects'),
        ];
        $resP2 = $this->runAjaxProjectFilters();
        $this->assert(
            str_contains($resP2['pagination'] ?? '', 'aria-current="page">2</span>') &&
            str_contains($resP2['pagination'] ?? '', 'data-page="1"') &&
            str_contains($resP2['pagination'] ?? '', 'data-page="3"'),
            'PAG-PROJ-2',
            "Project AJAX Page 2 renders page 2 active with links to page 1 and page 3",
            "Pagination HTML: " . ($resP2['pagination'] ?? '')
        );

        // Test Page 3 (should have 7 items, active page 3, link to page 2, no next link)
        $_POST = [
            'paged' => 3,
            'nonce' => wp_create_nonce('batdongsan_filter_projects'),
        ];
        $resP3 = $this->runAjaxProjectFilters();
        $this->assert(
            str_contains($resP3['pagination'] ?? '', 'aria-current="page">3</span>') &&
            !str_contains($resP3['pagination'] ?? '', 'aria-label="Trang sau"'),
            'PAG-PROJ-3',
            "Project AJAX Page 3 renders page 3 active without next page arrow",
            "Pagination HTML: " . ($resP3['pagination'] ?? '')
        );
    }

    /**
     * Section 8: SSR Pagination and State Persistence.
     */
    private function testSection8_SSRPaginationAndStatePersistence(): void {
        echo "--- 8. SSR Pagination and State Persistence ---\n";

        $url = batdongsan_bds_filter_url(
            'http://batdongsanhanoi.local/bat-dong-san',
            '3000-5000',
            '50-80',
            [
                'khu_vuc'          => 'cau-giay',
                'so_phong_ngu'     => '3',
                'so_phong_ve_sinh' => '2',
                'huong_nha'        => 'dong-nam',
                'phap_ly'          => 'so-do',
            ],
            2
        );

        $this->assert(
            str_contains($url, 'bat-dong-san-quan-cau-giay') &&
            str_contains($url, 'gia-tu-3-ty-den-5-ty-dt-tu-50m2-den-80m2') &&
            str_contains($url, '/page/2/') &&
            str_contains($url, 'so_phong_ngu=3') &&
            str_contains($url, 'so_phong_ve_sinh=2') &&
            str_contains($url, 'huong_nha=dong-nam') &&
            str_contains($url, 'phap_ly=so-do'),
            'SSR-PAG-01',
            "batdongsan_bds_filter_url generates complete SEO pagination URL retaining all extended criteria",
            "Generated URL: '{$url}'"
        );
    }

    /**
     * Section 9: Transient Lifecycle Cache Invalidation.
     */
    private function testSection9_TransientLifecycleCache(): void {
        echo "--- 9. Transient Lifecycle Cache Invalidation ---\n";

        // Seed header transients
        set_transient('bds_hdr_bds_abc123', ['test' => 1], 3600);
        set_transient('bds_hdr_duan_xyz789', ['test' => 2], 3600);
        $this->assert(
            get_transient('bds_hdr_bds_abc123') !== false && get_transient('bds_hdr_duan_xyz789') !== false,
            'CACHE-01',
            "Dynamic header transients successfully seeded in cache"
        );

        // Execute invalidation function
        if (function_exists('batdongsan_clear_dynamic_header_transients')) {
            batdongsan_clear_dynamic_header_transients();
            $this->assert(
                get_transient('bds_hdr_bds_abc123') === false && get_transient('bds_hdr_duan_xyz789') === false,
                'CACHE-02',
                "batdongsan_clear_dynamic_header_transients() completely purges all bds_hdr_* transients",
                "Transient still found!"
            );
        } else {
            $this->assert(false, 'CACHE-02', "batdongsan_clear_dynamic_header_transients() function not defined");
        }
    }

    private function runAjaxProjectFilters(): array {
        try {
            batdongsan_ajax_filter_projects();
            return [];
        } catch (AjaxExitException $e) {
            $resp = $e->getResponse();
            return $resp['data'] ?? [];
        }
    }

    private function runNodePopstateParser(string $urlStr): array {
        $jsFile = dirname(__DIR__) . '/js/main.js';
        $nodeScript = "
            const fs = require('fs');
            const code = fs.readFileSync('" . addslashes($jsFile) . "', 'utf8');
            const m = code.match(/(const parsePriceSlug = function[\\s\\S]*?return params;\\s*};)/);
            if (!m) process.exit(1);
            const fn = new Function('return (function() {' + m[1] + ' return parseBdsUrlToParams; })()')();
            const res = fn(new URL('" . addslashes($urlStr) . "'));
            console.log(JSON.stringify(res));
        ";
        $output = shell_exec('node -e ' . escapeshellarg($nodeScript));
        $res = json_decode($output, true);
        return is_array($res) ? $res : [];
    }
}

$challenger2 = new BDSAdversarialChallenger2();
$challenger2->runAll();
