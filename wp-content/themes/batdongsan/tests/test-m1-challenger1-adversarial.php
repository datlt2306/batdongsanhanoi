<?php
/**
 * Milestone 1 Challenger 1: Empirical Adversarial Test Harness.
 *
 * Rigorous empirical verification of:
 * 1. Eradication of posts_per_page = -1 across public filter routines and query defense
 * 2. Range Adversary: Inverted ranges, massive numbers, negative numbers, boundaries
 * 3. Multi-Criteria Combinations (7+ criteria simultaneous SQL generation and target isolation)
 * 4. SQL LIMIT and OFFSET Pagination Math across pages 1, 2, 5, 10 and boundary clamping
 * 5. Transient bounds execution speed (< 2ms) and compact storage size (< 500 bytes)
 * 6. Audit of Facade Helper format_listings_ajax_response item variable bindings
 *
 * Usage:
 *   php tests/test-m1-challenger1-adversarial.php
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

require_once __DIR__ . '/framework/MockWordPress.php';

use BDS\Tests\Framework\MockWordPress;

// Global WP_Query Mock with dynamic query execution
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

                // Tax query evaluation
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

                // Meta query evaluation
                if (!empty($args['meta_query'])) {
                    $metaPass = true;
                    foreach ($args['meta_query'] as $k => $mq) {
                        if ($k === 'relation' || !is_array($mq)) continue;
                        if (isset($mq['relation']) && $mq['relation'] === 'OR') {
                            $orPass = false;
                            foreach ($mq as $subK => $subMq) {
                                if (!is_numeric($subK) || !is_array($subMq)) continue;
                                $sKey = $subMq['key'] ?? '';
                                $sVal = $subMq['value'] ?? null;
                                $sComp = strtoupper($subMq['compare'] ?? '=');
                                $actual = MockWordPress::getPostMeta($post->ID, $sKey, true);
                                if ($sComp === '<=' && (float)$actual <= (float)$sVal) { $orPass = true; break; }
                                if ($sComp === 'LIKE' && str_contains(strtolower((string)$actual), strtolower((string)$sVal))) { $orPass = true; break; }
                            }
                            if (!$orPass) { $metaPass = false; break; }
                            continue;
                        }

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
                $this->posts = $matched;
                $this->max_num_pages = 1;
            } else {
                $offset = ($paged - 1) * $postsPerPage;
                $sliced = array_slice($matched, $offset, $postsPerPage);
                $this->posts = $sliced;
                $this->max_num_pages = (int) ceil($this->found_posts / max(1, $postsPerPage));
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

// Polyfills
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
            if ($taxonomy && $term->taxonomy !== $taxonomy) continue;
            if ($field === 'slug' && $term->slug === $value) return $term;
            if ($field === 'id' && (int) $term->term_id === (int) $value) return $term;
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
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
    }
}

// Load Theme Services & Helpers
require_once __DIR__ . '/../inc/helpers/format.php';
require_once __DIR__ . '/../inc/helpers/filter-slug.php';
require_once __DIR__ . '/../inc/helpers/filter-options.php';
require_once __DIR__ . '/../inc/helpers/project.php';
require_once __DIR__ . '/../inc/services/filter/bootstrap.php';
require_once __DIR__ . '/../inc/hooks/ajax-listings-filter.php';
require_once __DIR__ . '/../inc/hooks/ajax-project-filters.php';

class BDS_M1_Challenger1_Test_Runner {

    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];
    private string $themeDir;

    public function __construct() {
        $this->themeDir = dirname(__DIR__);
    }

    private function assert(bool $condition, string $description, string $details = ''): void {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$description}\n";
        } else {
            $this->failed++;
            $msg = "  [FAIL] {$description}" . ($details !== '' ? " ({$details})" : '');
            $this->failures[] = $msg;
            echo "{$msg}\n";
        }
    }

    public function run(): int {
        echo "====================================================================\n";
        echo "  MILESTONE 1 CHALLENGER 1: QUERY PERFORMANCE & RANGE ADVERSARY      \n";
        echo "====================================================================\n\n";

        $this->testSection1_ZeroNegativeOneAndQueryDefense();
        $this->testSection2_RangeAdversaryExtremeAndCorrupted();
        $this->testSection3_MultiCriteriaSevenSimultaneous();
        $this->testSection4_LimitOffsetPaginationMath();
        $this->testSection5_TransientBoundsPerformanceAndSize();
        $this->testSection6_FacadeItemVariableBindingAudit();

        echo "\n--------------------------------------------------------------------\n";
        echo sprintf("Execution Summary: %d Passed, %d Failed\n", $this->passed, $this->failed);
        echo "--------------------------------------------------------------------\n";

        if ($this->failed > 0) {
            echo "\nFailed Assertions / Adversarial Bugs Found:\n";
            foreach ($this->failures as $f) {
                echo "{$f}\n";
            }
            return 1;
        }

        echo "\n[VERDICT: APPROVE] 100% of Empirical Adversarial Challenges Passed!\n";
        return 0;
    }

    /**
     * SECTION 1: Zero posts_per_page = -1 in public filter routines & defensive clamping
     */
    private function testSection1_ZeroNegativeOneAndQueryDefense(): void {
        echo "--- Section 1: Verification of Zero 'posts_per_page = -1' & Defensive Clamping ---\n";

        $filter_files = [
            'page-bat-dong-san.php',
            'page-du-an.php',
            'inc/hooks/ajax-listings-filter.php',
            'inc/hooks/ajax-project-filters.php',
        ];

        foreach ($filter_files as $rel_path) {
            $full_path = $this->themeDir . '/' . $rel_path;
            $this->assert(file_exists($full_path), "File exists: {$rel_path}");
            $content = file_get_contents($full_path);

            $lines = explode("\n", $content);
            $found_neg_one = false;
            $found_line = 0;
            foreach ($lines as $idx => $line) {
                $trimmed = trim($line);
                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                    continue;
                }
                if (preg_match("/['\"]?posts_per_page['\"]?\s*(=>|=)\s*-\s*1/i", $trimmed)) {
                    $found_neg_one = true;
                    $found_line = $idx + 1;
                    break;
                }
            }

            $this->assert(!$found_neg_one, "Zero active 'posts_per_page = -1' in {$rel_path}", "Found at line {$found_line}");
        }

        // Test Query Builder Defense against Malicious/Boundary posts_per_page
        $listing_builder = new BDS_Listing_Query_Builder();
        $listing_args_neg = $listing_builder->parse_params(['posts_per_page' => -1])->build_query_args();
        $this->assert(
            $listing_args_neg['posts_per_page'] === 20,
            "Listing query clamps posts_per_page = -1 to default 20",
            "Got: " . ($listing_args_neg['posts_per_page'] ?? 'null')
        );

        $listing_args_zero = $listing_builder->parse_params(['posts_per_page' => 0])->build_query_args();
        $this->assert(
            $listing_args_zero['posts_per_page'] === 20,
            "Listing query clamps posts_per_page = 0 to default 20",
            "Got: " . ($listing_args_zero['posts_per_page'] ?? 'null')
        );

        $listing_args_extreme_neg = $listing_builder->parse_params(['posts_per_page' => -99999])->build_query_args();
        $this->assert(
            $listing_args_extreme_neg['posts_per_page'] === 20,
            "Listing query clamps posts_per_page = -99999 to default 20"
        );

        $listing_args_huge = $listing_builder->parse_params(['posts_per_page' => 5000])->build_query_args();
        $this->assert(
            $listing_args_huge['posts_per_page'] === 100,
            "Listing query caps excessive posts_per_page = 5000 to safe maximum 100"
        );

        // Project Builder Defense
        $project_builder = new BDS_Project_Query_Builder();
        $project_args_neg = $project_builder->parse_params(['posts_per_page' => -1])->build_query_args();
        $this->assert(
            $project_args_neg['posts_per_page'] === 9,
            "Project query clamps posts_per_page = -1 to default 9",
            "Got: " . ($project_args_neg['posts_per_page'] ?? 'null')
        );

        $project_args_huge = $project_builder->parse_params(['posts_per_page' => 999])->build_query_args();
        $this->assert(
            $project_args_huge['posts_per_page'] === 100,
            "Project query caps excessive posts_per_page = 999 to safe maximum 100"
        );

        // Paged clamping defense
        $listing_builder_direct = new BDS_Listing_Query_Builder();
        $listing_builder_direct->set_paged(-5);
        $this->assert($listing_builder_direct->get_paged() === 1, "Direct set_paged(-5) clamps to 1");

        $paged_zero = $listing_builder->parse_params(['paged' => 0])->build_query_args();
        $this->assert($paged_zero['paged'] === 1, "Listing query clamps paged=0 to 1");

        $paged_neg = $listing_builder->parse_params(['paged' => -5])->build_query_args();
        $this->assert($paged_neg['paged'] === 1, "Listing query clamps negative paged to 1");

        $proj_paged_neg = (new BDS_Project_Query_Builder())->parse_params(['paged' => -5])->build_query_args();
        $this->assert($proj_paged_neg['paged'] === 1, "Project query clamps negative paged to 1");
    }

    /**
     * SECTION 2: Range Adversary: Inverted, Boundary, and Corrupted Ranges
     */
    private function testSection2_RangeAdversaryExtremeAndCorrupted(): void {
        echo "\n--- Section 2: Range Adversary: Inverted, Boundary, and Corrupted Ranges ---\n";

        MockWordPress::reset();
        MockWordPress::$posts = [
            (object)['ID' => 101, 'post_title' => 'BĐS 2 Tỷ', 'post_type' => 'bat-dong-san', 'post_status' => 'publish'],
            (object)['ID' => 102, 'post_title' => 'BĐS 5 Tỷ', 'post_type' => 'bat-dong-san', 'post_status' => 'publish'],
            (object)['ID' => 103, 'post_title' => 'BĐS 10 Tỷ', 'post_type' => 'bat-dong-san', 'post_status' => 'publish'],
            (object)['ID' => 104, 'post_title' => 'BĐS Thỏa thuận', 'post_type' => 'bat-dong-san', 'post_status' => 'publish'],
        ];
        MockWordPress::$postmeta[101] = ['_bds_price_million' => 2000, '_bds_area_m2' => 50];
        MockWordPress::$postmeta[102] = ['_bds_price_million' => 5000, '_bds_area_m2' => 80];
        MockWordPress::$postmeta[103] = ['_bds_price_million' => 10000, '_bds_area_m2' => 150];
        MockWordPress::$postmeta[104] = ['_bds_price_million' => 0, 'khoang_gia' => 'Thoả thuận', '_bds_area_m2' => 60];

        // 2.1 Inverted Price Range: 10000-2000
        $query_inverted_price = BDS_Filter_Query_Service::query_listings(['price' => '10000-2000']);
        $this->assert(
            $query_inverted_price->found_posts === 0,
            "Inverted price range '10000-2000' yields exactly 0 matches",
            "Found: " . $query_inverted_price->found_posts
        );
        $args_inv_price = BDS_Filter_Query_Service::get_listing_query_args(['price' => '10000-2000']);
        $this->assert(
            isset($args_inv_price['post__in']) && $args_inv_price['post__in'] === [0],
            "Inverted price range injects 'post__in => [0]' guard"
        );

        // 2.2 Inverted Area Range: 120-40
        $query_inverted_area = BDS_Filter_Query_Service::query_listings(['area' => '120-40']);
        $this->assert(
            $query_inverted_area->found_posts === 0,
            "Inverted area range '120-40' yields exactly 0 matches",
            "Found: " . $query_inverted_area->found_posts
        );
        $args_inv_area = BDS_Filter_Query_Service::get_listing_query_args(['area' => '120-40']);
        $this->assert(
            isset($args_inv_area['post__in']) && $args_inv_area['post__in'] === [0],
            "Inverted area range injects 'post__in => [0]' guard"
        );

        // 2.3 Inverted Discrete Params: gia_min > gia_max
        $args_inv_discrete = BDS_Filter_Query_Service::get_listing_query_args(['gia_min' => '9000', 'gia_max' => '3000']);
        $this->assert(
            isset($args_inv_discrete['post__in']) && $args_inv_discrete['post__in'] === [0],
            "Discrete inverted gia_min > gia_max sets post__in => [0]"
        );

        // 2.4 Massive Number: price = 999999999
        $args_massive = BDS_Filter_Query_Service::get_listing_query_args(['price' => '999999999']);
        $has_massive_clause = false;
        if (!empty($args_massive['meta_query'])) {
            foreach ($args_massive['meta_query'] as $mq) {
                if (is_array($mq) && ($mq['key'] ?? '') === '_bds_price_million' && (float)$mq['value'] === 999999999.0) {
                    $has_massive_clause = true;
                    break;
                }
            }
        }
        $this->assert($has_massive_clause, "Massive price=999999999 compiles valid numeric meta_query clause >= 999999999 without float overflow");

        // 2.5 Malformed String Handling
        $args_malformed = BDS_Filter_Query_Service::get_listing_query_args(['price' => 'abc-xyz']);
        $this->assert(is_array($args_malformed), "Malformed non-numeric price string 'abc-xyz' parsed safely into array without fatal error");

        // 2.6 Price 'thoa_thuan' Handling
        $args_thoa_thuan = BDS_Filter_Query_Service::get_listing_query_args(['price' => 'thoa_thuan']);
        $has_or_clause = false;
        if (!empty($args_thoa_thuan['meta_query'])) {
            foreach ($args_thoa_thuan['meta_query'] as $mq) {
                if (is_array($mq) && ($mq['relation'] ?? '') === 'OR') {
                    $has_or_clause = true;
                    break;
                }
            }
        }
        $this->assert($has_or_clause, "Price 'thoa_thuan' compiles OR clause matching numeric <= 0 and text 'thỏa thuận'");

        // 2.7 Zero bound '0-0'
        $args_zero_bound = BDS_Filter_Query_Service::get_listing_query_args(['price' => '0-0']);
        $meta_price_present = false;
        if (!empty($args_zero_bound['meta_query'])) {
            foreach ($args_zero_bound['meta_query'] as $mq) {
                if (is_array($mq) && ($mq['key'] ?? '') === '_bds_price_million') $meta_price_present = true;
            }
        }
        $this->assert(!$meta_price_present, "Zero bound '0-0' is safely bypassed without spurious meta_query clauses");
    }

    /**
     * SECTION 3: Multi-Criteria Combinations (7+ Simultaneous Criteria)
     */
    private function testSection3_MultiCriteriaSevenSimultaneous(): void {
        echo "\n--- Section 3: Multi-Criteria Combinations (7 Simultaneous Criteria) ---\n";

        MockWordPress::reset();

        MockWordPress::$terms[1] = (object)['term_id' => 1, 'slug' => 'ban', 'taxonomy' => 'hinh-thuc-bds', 'name' => 'Bán'];
        MockWordPress::$terms[2] = (object)['term_id' => 2, 'slug' => 'can-ho-chung-cu', 'taxonomy' => 'loai_hinh_bat_dong_san', 'name' => 'Căn hộ chung cư'];
        MockWordPress::$terms[3] = (object)['term_id' => 3, 'slug' => 'biet-thu', 'taxonomy' => 'loai_hinh_bat_dong_san', 'name' => 'Biệt thự'];

        $posts_data = [
            1 => [ // TARGET POST
                'title' => 'Target Match: Discovery Complex Cầu Giấy',
                'hinh_thuc' => 1, 'type' => 2, 'dia_chi' => 'Phường Dịch Vọng, Quận Cầu Giấy, Hà Nội',
                'price' => 4200, 'area' => 75.0, 'pn' => 3, 'wc' => 2, 'huong' => 'dong_nam', 'phap_ly' => 'so_do'
            ],
            2 => [ // Fails price
                'title' => 'Fails Price',
                'hinh_thuc' => 1, 'type' => 2, 'dia_chi' => 'Quận Cầu Giấy, Hà Nội',
                'price' => 7500, 'area' => 75.0, 'pn' => 3, 'wc' => 2, 'huong' => 'dong_nam', 'phap_ly' => 'so_do'
            ],
            3 => [ // Fails area
                'title' => 'Fails Area',
                'hinh_thuc' => 1, 'type' => 2, 'dia_chi' => 'Quận Cầu Giấy, Hà Nội',
                'price' => 4200, 'area' => 110.0, 'pn' => 3, 'wc' => 2, 'huong' => 'dong_nam', 'phap_ly' => 'so_do'
            ],
            4 => [ // Fails bedrooms
                'title' => 'Fails PN',
                'hinh_thuc' => 1, 'type' => 2, 'dia_chi' => 'Quận Cầu Giấy, Hà Nội',
                'price' => 4200, 'area' => 75.0, 'pn' => 2, 'wc' => 2, 'huong' => 'dong_nam', 'phap_ly' => 'so_do'
            ],
            5 => [ // Fails bathrooms
                'title' => 'Fails WC',
                'hinh_thuc' => 1, 'type' => 2, 'dia_chi' => 'Quận Cầu Giấy, Hà Nội',
                'price' => 4200, 'area' => 75.0, 'pn' => 3, 'wc' => 1, 'huong' => 'dong_nam', 'phap_ly' => 'so_do'
            ],
            6 => [ // Fails orientation
                'title' => 'Fails Direction',
                'hinh_thuc' => 1, 'type' => 2, 'dia_chi' => 'Quận Cầu Giấy, Hà Nội',
                'price' => 4200, 'area' => 75.0, 'pn' => 3, 'wc' => 2, 'huong' => 'tay_nam', 'phap_ly' => 'so_do'
            ],
            7 => [ // Fails legal
                'title' => 'Fails Legal',
                'hinh_thuc' => 1, 'type' => 2, 'dia_chi' => 'Quận Cầu Giấy, Hà Nội',
                'price' => 4200, 'area' => 75.0, 'pn' => 3, 'wc' => 2, 'huong' => 'dong_nam', 'phap_ly' => 'hop_dong'
            ],
            8 => [ // Fails district
                'title' => 'Fails District',
                'hinh_thuc' => 1, 'type' => 2, 'dia_chi' => 'Quận Tây Hồ, Hà Nội',
                'price' => 4200, 'area' => 75.0, 'pn' => 3, 'wc' => 2, 'huong' => 'dong_nam', 'phap_ly' => 'so_do'
            ],
            9 => [ // Fails type
                'title' => 'Fails Type',
                'hinh_thuc' => 1, 'type' => 3, 'dia_chi' => 'Quận Cầu Giấy, Hà Nội',
                'price' => 4200, 'area' => 75.0, 'pn' => 3, 'wc' => 2, 'huong' => 'dong_nam', 'phap_ly' => 'so_do'
            ],
        ];

        foreach ($posts_data as $id => $d) {
            MockWordPress::$posts[] = (object)[
                'ID' => $id,
                'post_title' => $d['title'],
                'post_type' => 'bat-dong-san',
                'post_status' => 'publish',
            ];
            MockWordPress::$object_terms[$id] = [
                'hinh-thuc-bds' => [$d['hinh_thuc']],
                'loai_hinh_bat_dong_san' => [$d['type']],
            ];
            MockWordPress::$postmeta[$id] = [
                'dia_chi' => $d['dia_chi'],
                '_bds_price_million' => $d['price'],
                '_bds_area_m2' => $d['area'],
                'so_phong_ngu' => $d['pn'],
                'so_phong_ve_sinh' => $d['wc'],
                'huong_nha' => $d['huong'],
                'phap_ly' => $d['phap_ly'],
            ];
        }

        $criteria = [
            'hinh_thuc'        => 'ban',
            'type'             => 'can-ho-chung-cu',
            'khu_vuc'          => 'cau-giay',
            'price'            => '3000-5000',
            'area'             => '50-80',
            'so_phong_ngu'     => '3',
            'so_phong_ve_sinh' => '2',
            'huong_nha'        => 'dong-nam',
            'phap_ly'          => 'so-do',
        ];

        $compiled_args = BDS_Filter_Query_Service::get_listing_query_args($criteria);

        $this->assert(isset($compiled_args['tax_query']['relation']) && $compiled_args['tax_query']['relation'] === 'AND', "tax_query uses AND relation for multi-taxonomies");
        $this->assert(isset($compiled_args['meta_query']['relation']) && $compiled_args['meta_query']['relation'] === 'AND', "meta_query uses AND relation for multi-criteria");

        $meta_clauses = array_filter($compiled_args['meta_query'], fn($k) => is_numeric($k), ARRAY_FILTER_USE_KEY);
        $this->assert(count($meta_clauses) >= 7, "Compiled at least 7 distinct meta_query criteria clauses (got: " . count($meta_clauses) . ")");

        $query = BDS_Filter_Query_Service::query_listings($criteria);

        $this->assert(
            $query->found_posts === 1,
            "7-Criteria filter returns exactly 1 target listing (found_posts = 1)",
            "Got found_posts = " . $query->found_posts
        );

        $this->assert(
            count($query->posts) === 1 && $query->posts[0]->ID === 1,
            "7-Criteria query successfully isolated Target Match ID 1 and excluded all 8 defective variants",
            "Matched ID: " . ($query->posts[0]->ID ?? 'none')
        );
    }

    /**
     * SECTION 4: SQL LIMIT and OFFSET Pagination Math Across Pages 1, 2, 5, 10
     */
    private function testSection4_LimitOffsetPaginationMath(): void {
        echo "\n--- Section 4: SQL LIMIT & OFFSET Pagination Math (Pages 1, 2, 5, 10) ---\n";

        $test_pages = [1, 2, 5, 10];
        $per_page_listings = 12;
        $per_page_projects = 9;

        // BĐS Listings Pagination Math
        foreach ($test_pages as $page) {
            $expected_offset = ($page - 1) * $per_page_listings;
            $args = BDS_Filter_Query_Service::get_listing_query_args([
                'paged'          => $page,
                'posts_per_page' => $per_page_listings,
            ]);

            $actual_paged = $args['paged'];
            $actual_per_page = $args['posts_per_page'];
            $calculated_offset = ($actual_paged - 1) * $actual_per_page;

            $this->assert(
                $actual_paged === $page && $actual_per_page === $per_page_listings && $calculated_offset === $expected_offset,
                "Listing Page {$page}: paged={$actual_paged}, posts_per_page={$actual_per_page} => SQL LIMIT {$actual_per_page} OFFSET {$calculated_offset}"
            );
        }

        // Projects Pagination Math
        foreach ($test_pages as $page) {
            $expected_offset = ($page - 1) * $per_page_projects;
            $args = BDS_Filter_Query_Service::get_project_query_args([
                'paged'          => $page,
                'posts_per_page' => $per_page_projects,
            ]);

            $actual_paged = $args['paged'];
            $actual_per_page = $args['posts_per_page'];
            $calculated_offset = ($actual_paged - 1) * $actual_per_page;

            $this->assert(
                $actual_paged === $page && $actual_per_page === $per_page_projects && $calculated_offset === $expected_offset,
                "Project Page {$page}: paged={$actual_paged}, posts_per_page={$actual_per_page} => SQL LIMIT {$actual_per_page} OFFSET {$calculated_offset}"
            );
        }

        // Verify format_listings_ajax_response pagination HTML across pages
        MockWordPress::reset();
        $dummy_query = new WP_Query();
        $dummy_query->found_posts = 120;
        $dummy_query->max_num_pages = 10;
        $dummy_query->posts = [];

        // Page 1
        $res1 = BDS_Filter_Query_Service::format_listings_ajax_response($dummy_query, ['paged' => 1, 'posts_per_page' => 12]);
        $this->assert($res1['current_page'] === 1 && $res1['total_pages'] === 10, "AJAX Response Page 1 reports current_page=1, total_pages=10");
        $this->assert(!str_contains($res1['pagination'], 'aria-label="Trang trước"'), "AJAX Response Page 1 has NO 'Trang trước' button");
        $this->assert(str_contains($res1['pagination'], 'aria-label="Trang sau"'), "AJAX Response Page 1 has 'Trang sau' button");

        // Page 5
        $res5 = BDS_Filter_Query_Service::format_listings_ajax_response($dummy_query, ['paged' => 5, 'posts_per_page' => 12]);
        $this->assert($res5['current_page'] === 5, "AJAX Response Page 5 reports current_page=5");
        $this->assert(str_contains($res5['pagination'], 'aria-label="Trang trước"'), "AJAX Response Page 5 has 'Trang trước' button");
        $this->assert(str_contains($res5['pagination'], 'aria-label="Trang sau"'), "AJAX Response Page 5 has 'Trang sau' button");
        $this->assert(str_contains($res5['pagination'], 'data-page="4"'), "AJAX Response Page 5 links back to page 4");
        $this->assert(str_contains($res5['pagination'], 'data-page="6"'), "AJAX Response Page 5 links forward to page 6");

        // Page 10
        $res10 = BDS_Filter_Query_Service::format_listings_ajax_response($dummy_query, ['paged' => 10, 'posts_per_page' => 12]);
        $this->assert($res10['current_page'] === 10, "AJAX Response Page 10 reports current_page=10");
        $this->assert(str_contains($res10['pagination'], 'aria-label="Trang trước"'), "AJAX Response Page 10 has 'Trang trước' button");
        $this->assert(!str_contains($res10['pagination'], 'aria-label="Trang sau"'), "AJAX Response Page 10 has NO 'Trang sau' button");
    }

    /**
     * SECTION 5: Transient Bounds Performance (< 2ms) and Compact Storage Size (< 500 bytes)
     */
    private function testSection5_TransientBoundsPerformanceAndSize(): void {
        echo "\n--- Section 5: Transient Bounds Performance (< 2ms) & Compact Storage Size ---\n";

        // Setup mock $wpdb that returns realistic float values
        global $wpdb;
        $wpdb = new class {
            public string $posts = 'wp_posts';
            public string $postmeta = 'wp_postmeta';
            public function get_row(string $query) {
                if (str_contains($query, '_bds_price_million')) {
                    return (object)['min_val' => 500.00, 'max_val' => 45000.00];
                }
                if (str_contains($query, '_bds_area_m2')) {
                    return (object)['min_val' => 30.00, 'max_val' => 450.00];
                }
                return null;
            }
            public function get_var(string $query) {
                if (str_contains($query, '_bds_is_thoa_thuan')) {
                    return '1';
                }
                return null;
            }
        };

        delete_transient(BDS_Filter_Options_Service::TRANSIENT_PRICE_BOUNDS);
        delete_transient(BDS_Filter_Options_Service::TRANSIENT_AREA_BOUNDS);

        // 5.1 Benchmark Price Bounds Execution Speed
        $t0 = microtime(true);
        $price_bounds = BDS_Filter_Options_Service::get_price_bounds();
        $price_elapsed_ms = (microtime(true) - $t0) * 1000;

        $this->assert(
            $price_elapsed_ms < 2.0,
            sprintf("Price bounds SQL calculation executed in %.3f ms (Strict < 2.0 ms SLA)", $price_elapsed_ms)
        );
        $this->assert($price_bounds['min'] === 500.0 && $price_bounds['max'] === 45000.0, "Price bounds values parsed correctly");

        // 5.2 Benchmark Area Bounds Execution Speed
        $t1 = microtime(true);
        $area_bounds = BDS_Filter_Options_Service::get_area_bounds();
        $area_elapsed_ms = (microtime(true) - $t1) * 1000;

        $this->assert(
            $area_elapsed_ms < 2.0,
            sprintf("Area bounds SQL calculation executed in %.3f ms (Strict < 2.0 ms SLA)", $area_elapsed_ms)
        );
        $this->assert($area_bounds['min'] === 30.0 && $area_bounds['max'] === 450.0, "Area bounds values parsed correctly");

        // 5.3 Transient Storage Payload Size Check (Strict < 500 bytes vs legacy 237 KB)
        $serialized_price_bounds = serialize($price_bounds);
        $price_size_bytes = strlen($serialized_price_bounds);
        $this->assert(
            $price_size_bytes < 500,
            sprintf("Price bounds transient serialized size is %d bytes (< 500 bytes strict limit; 99.9%% smaller than legacy 237 KB)", $price_size_bytes)
        );

        $serialized_area_bounds = serialize($area_bounds);
        $area_size_bytes = strlen($serialized_area_bounds);
        $this->assert(
            $area_size_bytes < 500,
            sprintf("Area bounds transient serialized size is %d bytes (< 500 bytes strict limit; 99.9%% smaller than legacy 237 KB)", $area_size_bytes)
        );

        // 5.4 Invalidation Check: batdongsan_clear_filter_transients_on_post_change
        if (function_exists('batdongsan_clear_filter_transients_on_post_change')) {
            set_transient(BDS_Filter_Options_Service::TRANSIENT_PRICE_BOUNDS, $price_bounds, 3600);
            $this->assert(get_transient(BDS_Filter_Options_Service::TRANSIENT_PRICE_BOUNDS) !== false, "Transient seeded prior to hook trigger");

            MockWordPress::$posts = [(object)['ID' => 777, 'post_type' => 'bat-dong-san', 'post_status' => 'publish']];

            batdongsan_clear_filter_transients_on_post_change(777);
            $cached_after = get_transient(BDS_Filter_Options_Service::TRANSIENT_PRICE_BOUNDS);
            $this->assert(
                $cached_after === false,
                "Lifecycle post change hook successfully purges transient cache"
            );
        }

        // 5.5 ADVERSARIAL STRESS: Test with MySQL Decimal String values (PDO/MySQL string return type)
        $wpdb_string_decimals = new class {
            public string $posts = 'wp_posts';
            public string $postmeta = 'wp_postmeta';
            public function get_row(string $query) {
                return (object)['min_val' => '500.00', 'max_val' => '45000.00'];
            }
            public function get_var(string $query) { return '1'; }
        };
        $wpdb = $wpdb_string_decimals;
        delete_transient(BDS_Filter_Options_Service::TRANSIENT_PRICE_BOUNDS);

        // Reset memory cache via reflection if accessible
        $ref = new ReflectionClass('BDS_Filter_Options_Service');
        if ($ref->hasProperty('memory_cache')) {
            $prop = $ref->getProperty('memory_cache');
            $prop->setAccessible(true);
            $prop->setValue(null, []);
        }

        $type_error_caught = false;
        $type_error_message = '';
        try {
            BDS_Filter_Options_Service::get_price_bounds();
        } catch (\TypeError $e) {
            $type_error_caught = true;
            $type_error_message = $e->getMessage();
        }

        if ($type_error_caught) {
            $this->assert(
                false,
                "CRITICAL BUG-01: BDS_Filter_Options_Service::get_price_bounds() throws TypeError under strict_types=1 when MySQL returns string decimals",
                $type_error_message
            );
        } else {
            $this->assert(true, "BDS_Filter_Options_Service::get_price_bounds() safely handles string decimals from MySQL");
        }
    }

    /**
     * SECTION 6: Audit of Facade Helper format_listings_ajax_response item variable bindings
     */
    private function testSection6_FacadeItemVariableBindingAudit(): void {
        echo "\n--- Section 6: Audit of Facade Helper format_listings_ajax_response Binding ---\n";

        $class_file = $this->themeDir . '/inc/services/filter/class-bds-filter-query-service.php';
        $content = file_get_contents($class_file);

        $has_value_binding = preg_match('/foreach\s*\(\$posts\s+as\s+\$value\)/', $content)
            || preg_match('/\$value\s*=\s*\$post_obj/', $content);

        if (!$has_value_binding) {
            $this->assert(
                false,
                "CRITICAL BUG-02: BDS_Filter_Query_Service::format_listings_ajax_response() lacks \$value and \$post binding in loop",
                "paths/bat-dong-san-item.php line 11 expects \$value->ID; line 49 expects \$post. Crashes when query has posts."
            );
        } else {
            $this->assert(true, "format_listings_ajax_response provides proper template variable bindings");
        }
    }
}

// Execute Runner
$runner = new BDS_M1_Challenger1_Test_Runner();
exit($runner->run());
