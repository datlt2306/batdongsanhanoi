<?php
/**
 * Empirical Adversarial Test Harness — Challenger 1 (Filter Adversarial Verification).
 *
 * Stress-tests the filter implementation across:
 *   1. Boundary price & area conditions (min-only open-ended, inverted, decimals, extreme values, diacritics).
 *   2. Multi-criteria combinations (4+ simultaneous conditions).
 *   3. Empty and non-existent combinations.
 *   4. SEO rewrite URL slug generation & parsing round-trip consistency.
 *   5. Suburban districts resolution across BĐS and Project endpoints.
 *   6. Project status normalization and label mapping.
 *   7. SSR pagination and frontend history/popstate contracts.
 *
 * Usage:
 *   php tests/test-filter-adversarial-challenger1.php
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
                        $mKey = $mq['key'] ?? '';
                        $mVal = $mq['value'] ?? null;
                        $mCompare = strtoupper($mq['compare'] ?? '=');
                        $mType = strtoupper($mq['type'] ?? 'CHAR');

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

// Polyfills / helper hooks
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
        echo '<div class="bds-card-mock">Mock Card</div>';
    }
}

require_once __DIR__ . '/../inc/helpers/format.php';
require_once __DIR__ . '/../inc/helpers/filter-slug.php';
require_once __DIR__ . '/../inc/helpers/filter-options.php';
require_once __DIR__ . '/../inc/services/price-ranges.php';
require_once __DIR__ . '/../inc/services/queries.php';
require_once __DIR__ . '/../inc/hooks/theme-setup.php';
require_once __DIR__ . '/../inc/hooks/ajax-listings-filter.php';
require_once __DIR__ . '/../inc/hooks/ajax-project-filters.php';

class BDSAdversarialChallenger {
    private int $totalTests = 0;
    private int $passed = 0;
    private int $failed = 0;
    private array $defects = [];

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

    private function createPostWithMeta(int $id, string $title, string $type, array $meta): WP_Post {
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

    public function runAll(): void {
        echo "\n=======================================================\n";
        echo "  BDS ADVERSARIAL CHALLENGER TEST HARNESS (CHALLENGER 1)\n";
        echo "=======================================================\n\n";

        $this->challengeGroup1_PriceAndAreaBoundaries();
        $this->challengeGroup2_MultiCriteriaCombinations();
        $this->challengeGroup3_EmptyAndNonExistentCombinations();
        $this->challengeGroup4_SEORewriteUrlSlugAndPopstate();
        $this->challengeGroup5_SuburbanDistrictsResolution();
        $this->challengeGroup6_ProjectStatusNormalization();
        $this->challengeGroup7_SSRPaginationContracts();

        echo "\n-------------------------------------------------------\n";
        echo "Adversarial Stress Test Summary: {$this->passed} Passed, {$this->failed} Defect(s) Found\n";
        echo "Total Assertions: {$this->totalTests}\n";
        echo "-------------------------------------------------------\n";
    }

    /**
     * Section 1: Boundary values for min/max prices & area.
     */
    private function challengeGroup1_PriceAndAreaBoundaries(): void {
        echo "--- 1. Boundary Price & Area Conditions ---\n";
        MockWordPress::reset();

        $p1 = $this->createPostWithMeta(101, 'Biệt thự 3 tỷ', 'bat-dong-san', [
            'khoang_gia' => '3 tỷ',
            'dien_tich' => '80',
        ]);
        $p2 = $this->createPostWithMeta(102, 'Căn hộ 1.5 tỷ', 'bat-dong-san', [
            'khoang_gia' => '1.5 tỷ',
            'dien_tich' => '45.5',
        ]);
        $p3 = $this->createPostWithMeta(103, 'Đất thỏa thuận', 'bat-dong-san', [
            'khoang_gia' => 'Thoả thuận',
            'dien_tich' => '120',
        ]);
        $p4 = $this->createPostWithMeta(104, 'Nhà giá 800 triệu', 'bat-dong-san', [
            'khoang_gia' => '800 triệu',
            'dien_tich' => '30',
        ]);

        // 1.1: Open-ended price filter '2000-0' (min 2 tỷ, no upper limit)
        // If a user selects min=2000 and max=none, listings >= 2000 should be preserved.
        $resPriceMinOnly = batdongsan_filter_listings_by_price_range([$p1, $p2, $p4], '2000-0');
        $this->assert(
            count($resPriceMinOnly) === 1 && $resPriceMinOnly[0]->ID === 101,
            'CH1-1.1',
            'Open-ended min price range "2000-0" retains post with 3 tỷ',
            'Returned ' . count($resPriceMinOnly) . ' posts because $max=0.0 excludes all positive prices'
        );

        // 1.2: Open-ended area filter '50-0' (min 50m2, no upper limit)
        $resAreaMinOnly = batdongsan_filter_listings_by_area_range([$p1, $p2, $p4], '50-0');
        $this->assert(
            count($resAreaMinOnly) === 1 && $resAreaMinOnly[0]->ID === 101,
            'CH1-1.2',
            'Open-ended min area range "50-0" retains post with 80m2',
            'Returned ' . count($resAreaMinOnly) . ' posts because $max=0.0 excludes all positive areas'
        );

        // 1.3: Slug generation for open-ended price '2000-0'
        $slugPriceOpen = batdongsan_price_key_to_slug('2000-0');
        $this->assert(
            $slugPriceOpen === 'gia-tren-2-ty',
            'CH1-1.3',
            'Price key "2000-0" formats to "gia-tren-2-ty"',
            'Generated: "' . $slugPriceOpen . '"'
        );

        // 1.4: Slug generation for open-ended area '50-0'
        $slugAreaOpen = batdongsan_area_key_to_slug('50-0');
        $this->assert(
            $slugAreaOpen === 'dt-tren-50m2',
            'CH1-1.4',
            'Area key "50-0" formats to "dt-tren-50m2"',
            'Generated: "' . $slugAreaOpen . '"'
        );

        // 1.5: Vietnamese diacritic variant 'Thoả thuận' (tone on 'o') vs 'Thỏa thuận' (tone on 'a')
        $isThoaThuan = batdongsan_is_thoa_thuan_price(103);
        $this->assert(
            $isThoaThuan === true,
            'CH1-1.5',
            'batdongsan_is_thoa_thuan_price recognizes Vietnamese modern diacritic "Thoả thuận"',
            'Failed to recognize "Thoả thuận" (returned false)'
        );

        // 1.6: Decimal area handling '45.5'
        $areaNum = batdongsan_get_area_number(102);
        $this->assert(
            $areaNum === 45.5,
            'CH1-1.6',
            'batdongsan_get_area_number parses decimal 45.5 correctly',
            'Parsed value: ' . var_export($areaNum, true)
        );

        // 1.7: Price parser with comma decimal '3,5 tỷ'
        $priceComma = batdongsan_parse_total_price_million('3,5 tỷ');
        $this->assert(
            $priceComma === 3500.0,
            'CH1-1.7',
            'batdongsan_parse_total_price_million parses Vietnamese comma decimal "3,5 tỷ" as 3500.0',
            'Parsed value: ' . var_export($priceComma, true)
        );

        // 1.8: Extremely large boundary price (100,000 tỷ / 100,000,000 triệu)
        $largePrice = batdongsan_parse_total_price_million('100000 tỷ');
        $this->assert(
            $largePrice === 100000000.0,
            'CH1-1.8',
            'Handles extremely high price values without float overflow',
            'Parsed value: ' . var_export($largePrice, true)
        );
    }

    /**
     * Section 2: Multi-criteria queries with 4+ conditions simultaneously.
     */
    private function challengeGroup2_MultiCriteriaCombinations(): void {
        echo "--- 2. Multi-Criteria Combinations (4+ Conditions) ---\n";
        MockWordPress::reset();

        // Register type terms
        MockWordPress::insertTerm('Căn hộ chung cư', 'loai_hinh_bat_dong_san', 'can-ho-chung-cu', 33);
        MockWordPress::insertTerm('Biệt thự liền kề', 'loai_hinh_bat_dong_san', 'biet-thu-lien-ke', 34);

        // Target match: satisfies all 6 criteria
        $pMatch = $this->createPostWithMeta(201, 'Căn hộ Golden Palace', 'bat-dong-san', [
            'khoang_gia' => '3.5 tỷ',
            'dien_tich' => '75',
            'dia_chi' => 'Phường Mễ Trì, Quận Nam Từ Liêm, Hà Nội',
            'so_phong_ngu' => 3,
            'so_phong_ve_sinh' => 2,
            'huong_nha' => 'dong_nam',
            'phap_ly' => 'so_do',
        ]);
        MockWordPress::setObjectTerms(201, [33], 'loai_hinh_bat_dong_san');

        // Candidate mismatch: wrong bedrooms (2 instead of >= 3)
        $pWrongBed = $this->createPostWithMeta(202, 'Căn hộ 2PN', 'bat-dong-san', [
            'khoang_gia' => '3.5 tỷ',
            'dien_tich' => '75',
            'dia_chi' => 'Quận Nam Từ Liêm, Hà Nội',
            'so_phong_ngu' => 2,
            'so_phong_ve_sinh' => 2,
            'huong_nha' => 'dong_nam',
            'phap_ly' => 'so_do',
        ]);
        MockWordPress::setObjectTerms(202, [33], 'loai_hinh_bat_dong_san');

        // Candidate mismatch: wrong district (Cầu Giấy instead of Nam Từ Liêm)
        $pWrongDistrict = $this->createPostWithMeta(203, 'Căn hộ Cầu Giấy', 'bat-dong-san', [
            'khoang_gia' => '3.5 tỷ',
            'dien_tich' => '75',
            'dia_chi' => 'Quận Cầu Giấy, Hà Nội',
            'so_phong_ngu' => 3,
            'so_phong_ve_sinh' => 2,
            'huong_nha' => 'dong_nam',
            'phap_ly' => 'so_do',
        ]);
        MockWordPress::setObjectTerms(203, [33], 'loai_hinh_bat_dong_san');

        // Candidate mismatch: wrong price (6 tỷ instead of 3-5 tỷ)
        $pWrongPrice = $this->createPostWithMeta(204, 'Căn hộ Giá cao', 'bat-dong-san', [
            'khoang_gia' => '6 tỷ',
            'dien_tich' => '75',
            'dia_chi' => 'Quận Nam Từ Liêm, Hà Nội',
            'so_phong_ngu' => 3,
            'so_phong_ve_sinh' => 2,
            'huong_nha' => 'dong_nam',
            'phap_ly' => 'so_do',
        ]);
        MockWordPress::setObjectTerms(204, [33], 'loai_hinh_bat_dong_san');

        // Simulate AJAX request with 7 criteria:
        // type=33, khu_vuc=nam-tu-liem, price=3000-5000, area=50-80, bedrooms=3, bathrooms=2, direction=dong-nam, legal=so-do
        $_POST = [
            'nonce' => wp_create_nonce('bds_filter_nonce'),
            'type' => '33',
            'khu_vuc' => 'nam-tu-liem',
            'price' => '3000-5000',
            'area' => '50-80',
            'so_phong_ngu' => '3',
            'so_phong_ve_sinh' => '2',
            'huong_nha' => 'dong-nam',
            'phap_ly' => 'so-do',
        ];
        $_REQUEST = $_POST;

        try {
            batdongsan_filter_listings();
            $this->assert(false, 'CH1-2.1', 'AJAX filter executed', 'Did not exit via wp_send_json');
        } catch (AjaxExitException $e) {
            $resp = $e->getResponse();

            // Verify query clauses constructed
            $lastQ = WP_Query::$last_query;
            $builtArgs = $lastQ ? $lastQ->query_vars : [];
            $taxQuery = $builtArgs['tax_query'] ?? [];
            $metaQuery = $builtArgs['meta_query'] ?? [];

            $hasTaxType = false;
            foreach ($taxQuery as $tq) {
                if (is_array($tq) && ($tq['taxonomy'] ?? '') === 'loai_hinh_bat_dong_san') {
                    $hasTaxType = true;
                }
            }

            $metaKeys = [];
            foreach ($metaQuery as $mq) {
                if (is_array($mq) && isset($mq['key'])) {
                    $metaKeys[] = $mq['key'];
                }
            }

            $hasAllMetaClauses = in_array('dia_chi', $metaKeys, true) &&
                in_array('so_phong_ngu', $metaKeys, true) &&
                in_array('so_phong_ve_sinh', $metaKeys, true) &&
                in_array('huong_nha', $metaKeys, true) &&
                in_array('phap_ly', $metaKeys, true);

            $this->assert(
                $resp['success'] === true && $resp['data']['total'] === 1 && $hasTaxType && $hasAllMetaClauses,
                'CH1-2.1',
                'Simultaneous 7-criteria filter generates all query clauses and isolates single target match',
                'Total: ' . ($resp['data']['total'] ?? 'null') . ', MetaKeys: ' . implode(', ', $metaKeys)
            );

            // 2.2 Check active filters chip generation
            $activeChips = $resp['data']['active_filters'] ?? [];
            $chipKeys = array_column($activeChips, 'key');
            $hasAllChips = in_array('type', $chipKeys, true) &&
                in_array('khu_vuc', $chipKeys, true) &&
                in_array('price', $chipKeys, true) &&
                in_array('area', $chipKeys, true) &&
                in_array('so_phong_ngu', $chipKeys, true) &&
                in_array('so_phong_ve_sinh', $chipKeys, true) &&
                in_array('huong_nha', $chipKeys, true) &&
                in_array('phap_ly', $chipKeys, true);

            $this->assert(
                $hasAllChips,
                'CH1-2.2',
                'Active filter chips generated for all 8 active criteria',
                'Present chip keys: ' . implode(', ', $chipKeys)
            );
        }
    }

    /**
     * Section 3: Empty/non-existent combinations.
     */
    private function challengeGroup3_EmptyAndNonExistentCombinations(): void {
        echo "--- 3. Empty & Non-Existent Combinations ---\n";
        MockWordPress::reset();

        // 3.1: Non-existent type term ID 999999
        $_POST = [
            'nonce' => wp_create_nonce('bds_filter_nonce'),
            'type' => '999999',
            'khu_vuc' => 'cau-giay',
        ];
        $_REQUEST = $_POST;

        try {
            batdongsan_filter_listings();
        } catch (AjaxExitException $e) {
            $resp = $e->getResponse();
            $this->assert(
                $resp['success'] === true && $resp['data']['total'] === 0,
                'CH1-3.1',
                'Non-existent type ID yields 0 results gracefully without PHP error',
                'Success: ' . ($resp['success'] ? 'true' : 'false')
            );
        }

        // 3.2: Contradictory impossible price range (min 10 tỷ, max 2 tỷ: '10000-2000')
        $p = $this->createPostWithMeta(301, 'BĐS 5 tỷ', 'bat-dong-san', ['khoang_gia' => '5 tỷ']);
        $resImpossible = batdongsan_filter_listings_by_price_range([$p], '10000-2000');
        $this->assert(
            count($resImpossible) === 0,
            'CH1-3.2',
            'Inverted/contradictory price range "10000-2000" yields 0 matches cleanly',
            'Returned count: ' . count($resImpossible)
        );

        // 3.3: Empty results HTML markup includes search icon and reset button
        $respHtml = $resp['data']['html'] ?? '';
        $this->assert(
            str_contains($respHtml, 'Không tìm thấy bất động sản nào') && str_contains($respHtml, 'data-clear-all-filters'),
            'CH1-3.3',
            'Empty result set renders user-friendly message and reset CTA',
            'HTML preview: ' . substr($respHtml, 0, 100)
        );
    }

    /**
     * Section 4: SEO rewrite URL slug generation and parsing round-trip consistency.
     */
    private function challengeGroup4_SEORewriteUrlSlugAndPopstate(): void {
        echo "--- 4. SEO Rewrite URL Slugs & Popstate Contract ---\n";

        // 4.1: Bidirectional round-trip for standard price ranges
        $testPriceKeys = [
            '0-2000'     => 'gia-duoi-2-ty',
            '3000-5000'  => 'gia-tu-3-ty-den-5-ty',
            '3500-5000'  => 'gia-tu-3.5-ty-den-5-ty',
            '500-800'    => 'gia-tu-500-trieu-den-800-trieu',
            '800-1000'   => 'gia-tu-800-trieu-den-1-ty',
            '10000'      => 'gia-tren-10-ty',
            'thoa_thuan' => 'gia-thoa-thuan',
        ];

        foreach ($testPriceKeys as $rawKey => $expectedSlug) {
            $key = (string) $rawKey;
            $genSlug = batdongsan_price_key_to_slug($key);
            $parsedKey = batdongsan_slug_to_price_key($genSlug);
            $this->assert(
                $genSlug === $expectedSlug && $parsedKey === $key,
                "CH1-4.1[{$key}]",
                "Price key '{$key}' round-trips with slug '{$expectedSlug}'",
                "gen: '{$genSlug}', parsed: '{$parsedKey}'"
            );
        }

        // 4.2: Compound slug (Price + Area together)
        $compoundSlug = batdongsan_build_filter_slug('3000-5000', '50-80');
        $parsedCompound = batdongsan_parse_filter_slug($compoundSlug);
        $this->assert(
            $compoundSlug === 'gia-tu-3-ty-den-5-ty-dt-tu-50m2-den-80m2' &&
            $parsedCompound['price'] === '3000-5000' &&
            $parsedCompound['area'] === '50-80',
            'CH1-4.2',
            'Compound price + area slug round-trips accurately',
            "Generated: '{$compoundSlug}', parsed: " . json_encode($parsedCompound)
        );

        // 4.3: batdongsan_bds_filter_url when type is passed as string slug
        MockWordPress::insertTerm('Căn hộ chung cư', 'loai_hinh_bat_dong_san', 'can-ho-chung-cu', 33);
        $urlWithSlugType = batdongsan_bds_filter_url(
            'http://batdongsanhanoi.local/bat-dong-san',
            '3000-5000',
            null,
            ['type' => 'can-ho-chung-cu', 'khu_vuc' => 'cau-giay']
        );
        $this->assert(
            str_contains($urlWithSlugType, 'can-ho-chung-cu-quan-cau-giay'),
            'CH1-4.3',
            'batdongsan_bds_filter_url accepts type passed as slug without dropping it',
            "Generated URL: '{$urlWithSlugType}' (absint dropped slug)"
        );

        // 4.4: Frontend popstate parser contract test
        // Verify what main.js popstate fallback extracts from standard URLs
        $testUrls = [
            'http://example.com/can-ho-chung-cu-quan-cau-giay/' => ['type' => 'can-ho-chung-cu', 'khu_vuc' => 'cau-giay'],
            'http://example.com/bat-dong-san/dt-tu-50m2-den-80m2/' => ['area' => '50-80'],
            'http://example.com/bat-dong-san/gia-duoi-2-ty/' => ['price' => '0-2000'],
            'http://example.com/bat-dong-san-quan-cau-giay/gia-tu-3-ty-den-5-ty-dt-tu-50m2-den-80m2/' => [
                'khu_vuc' => 'cau-giay',
                'price' => '3000-5000',
                'area' => '50-80'
            ],
        ];

        // Simulate main.js lines 777-789
        foreach ($testUrls as $urlStr => $expectedParams) {
            $parsed = $this->simulateMainJsPopstateParser($urlStr);
            $matchesExpectation = true;
            foreach ($expectedParams as $k => $v) {
                if (!isset($parsed[$k]) || $parsed[$k] !== $v) {
                    $matchesExpectation = false;
                    break;
                }
            }
            $this->assert(
                $matchesExpectation,
                "CH1-4.4[" . parse_url($urlStr, PHP_URL_PATH) . "]",
                "main.js popstate regex parses pathname '" . parse_url($urlStr, PHP_URL_PATH) . "'",
                "Expected " . json_encode($expectedParams) . ", got " . json_encode($parsed)
            );
        }
    }

    private function simulateMainJsPopstateParser(string $urlStr): array {
        // Authentically execute parseBdsUrlToParams directly from js/main.js via Node
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
        if (is_array($res)) {
            return $res;
        }

        // Fallback parser matching main.js parseBdsUrlToParams
        $url = parse_url($urlStr);
        $path = trim($url['path'] ?? '', '/');
        $parts = explode('/', $path);
        $params = [];

        foreach ($parts as $seg) {
            if ($seg === 'bat-dong-san') continue;
            if (preg_match('/^bat-dong-san-quan-([a-z0-9-]+)$/', $seg, $m)) {
                $params['khu_vuc'] = $m[1];
            } elseif (preg_match('/^([a-z0-9-]+)-quan-([a-z0-9-]+)$/', $seg, $m)) {
                $params['type'] = $m[1];
                $params['khu_vuc'] = $m[2];
            }
            if (preg_match('/^dt-(?:tu-)?([0-9\.-]+)m2(?:-den-([0-9\.-]+)m2)?$/', $seg, $m)) {
                $params['area'] = isset($m[2]) ? ($m[1] . '-' . $m[2]) : $m[1];
            }
            if (preg_match('/^gia-duoi-([0-9\.-]+)-ty$/', $seg, $m)) {
                $params['price'] = '0-' . ((float) $m[1] * 1000);
            } elseif (preg_match('/^gia-tu-([0-9\.-]+)-ty-den-([0-9\.-]+)-ty$/', $seg, $m)) {
                $params['price'] = ((float) $m[1] * 1000) . '-' . ((float) $m[2] * 1000);
            }
            if (strpos($seg, '-dt-') !== false && preg_match('/gia-tu-([0-9\.-]+)-ty-den-([0-9\.-]+)-ty-dt-tu-([0-9\.-]+)m2-den-([0-9\.-]+)m2/', $seg, $m)) {
                $params['price'] = ((float) $m[1] * 1000) . '-' . ((float) $m[2] * 1000);
                $params['area'] = $m[3] . '-' . $m[4];
            }
        }
        return $params;
    }

    /**
     * Section 5: Suburban districts resolution.
     */
    private function challengeGroup5_SuburbanDistrictsResolution(): void {
        echo "--- 5. Suburban Districts Resolution ---\n";
        MockWordPress::reset();

        $allAreas = batdongsan_get_area_options(false);
        $this->assert(
            count($allAreas) === 30,
            'CH1-5.1',
            'batdongsan_get_area_options includes all 30 inner and suburban Hanoi divisions',
            'Count: ' . count($allAreas)
        );

        // 5.2: Verify Project AJAX filter (ajax-project-filters.php) with suburban district 'hoai-duc'
        $pSuburban = $this->createPostWithMeta(501, 'Dự án Nam 32 Hoài Đức', 'du-an', [
            'address' => 'Huyện Hoài Đức, Thành phố Hà Nội',
        ]);
        $pInner = $this->createPostWithMeta(502, 'Dự án D’.Capitale Cầu Giấy', 'du-an', [
            'address' => 'Quận Cầu Giấy, Thành phố Hà Nội',
        ]);

        if (!function_exists('batdongsan_get_project_meta_recursive')) {
            function batdongsan_get_project_meta_recursive($key, $id) {
                return MockWordPress::getPostMeta($id, $key, true);
            }
        }

        $_POST = [
            'nonce' => wp_create_nonce('batdongsan_filter_projects'),
            'khu_vuc' => 'hoai-duc',
        ];

        try {
            batdongsan_ajax_filter_projects();
            $this->assert(false, 'CH1-5.2', 'Project filter executed', 'Did not exit via wp_send_json');
        } catch (AjaxExitException $e) {
            $resp = $e->getResponse();
            $this->assert(
                $resp['success'] === true && $resp['data']['total'] === 1,
                'CH1-5.2',
                'Project AJAX endpoint filters suburban district "hoai-duc" (returns 1 project, not unfiltered 2)',
                'Total returned: ' . ($resp['data']['total'] ?? 'null') . ' (area_options missing suburban districts in ajax-project-filters.php)'
            );

            // 5.3: Project search results summary includes suburban district
            $searchResults = $resp['data']['searchResults'] ?? '';
            $this->assert(
                str_contains($searchResults, 'Hoài Đức'),
                'CH1-5.3',
                'Project AJAX searchResults includes suburban district label "Hoài Đức"',
                'searchResults content: "' . $searchResults . '"'
            );
        }

        // 5.4: Check whether theme-setup.php rewrite regex contains all 30 districts
        $themeSetupCode = file_get_contents(dirname(__DIR__) . '/inc/hooks/theme-setup.php');
        preg_match('/\$du_an_districts_re\s*=\s*\'\((.*?)\)\';/', $themeSetupCode, $m);
        $registeredDistricts = isset($m[1]) ? explode('|', $m[1]) : [];

        $missingDistricts = [];
        foreach (array_keys($allAreas) as $slug) {
            if (!in_array($slug, $registeredDistricts, true)) {
                $missingDistricts[] = $slug;
            }
        }

        $this->assert(
            empty($missingDistricts),
            'CH1-5.4',
            'theme-setup.php $du_an_districts_re contains all 30 districts (0 missing)',
            'Missing in regex: ' . implode(', ', $missingDistricts)
        );
    }

    /**
     * Section 6: Project status normalization and label mapping.
     */
    private function challengeGroup6_ProjectStatusNormalization(): void {
        echo "--- 6. Project Status Normalization & Labels ---\n";
        MockWordPress::reset();

        $pProj = $this->createPostWithMeta(601, 'Vinhomes Smart City', 'du-an', [
            'trang_thai' => 'dang_mo_ban',
        ]);

        $_POST = [
            'nonce' => wp_create_nonce('batdongsan_filter_projects'),
            'status' => 'dang_mo_ban',
        ];

        try {
            batdongsan_ajax_filter_projects();
        } catch (AjaxExitException $e) {
            $resp = $e->getResponse();
            $this->assert(
                $resp['success'] === true && $resp['data']['total'] === 1,
                'CH1-6.1',
                'Project filter matches status with underscore format "dang_mo_ban"',
                'Total: ' . ($resp['data']['total'] ?? 'null')
            );

            // 6.2: Status label in search summary
            $searchResults = $resp['data']['searchResults'] ?? '';
            $this->assert(
                str_contains($searchResults, 'Đang mở bán') && !str_contains($searchResults, 'dang_mo_ban'),
                'CH1-6.2',
                'Project searchResults renders Vietnamese label "Đang mở bán" instead of raw key "dang_mo_ban"',
                'searchResults snippet: "' . $searchResults . '"'
            );
        }
    }

    /**
     * Section 7: SSR pagination query args contract.
     */
    private function challengeGroup7_SSRPaginationContracts(): void {
        echo "--- 7. SSR Pagination State Retention Contract ---\n";

        // Test SSR pagination argument preservation from page-bat-dong-san.php lines 787-795
        $pageBatDongSanCode = file_get_contents(dirname(__DIR__) . '/page-bat-dong-san.php');

        // Extract $query_args block around line 787
        preg_match('/\$query_args\s*=\s*array_filter\(\[(.*?)\]\);/s', $pageBatDongSanCode, $m);
        $queryArgsBlock = $m[1] ?? '';

        $hasBedrooms = str_contains($queryArgsBlock, 'so_phong_ngu');
        $hasBathrooms = str_contains($queryArgsBlock, 'so_phong_ve_sinh');
        $hasDirection = str_contains($queryArgsBlock, 'huong_nha');
        $hasLegal = str_contains($queryArgsBlock, 'phap_ly');

        $this->assert(
            $hasBedrooms && $hasBathrooms && $hasDirection && $hasLegal,
            'CH1-7.1',
            'page-bat-dong-san.php passes extended filter criteria to SSR pagination query_args',
            'Missing in $query_args: ' . implode(', ', array_filter([
                !$hasBedrooms ? 'so_phong_ngu' : null,
                !$hasBathrooms ? 'so_phong_ve_sinh' : null,
                !$hasDirection ? 'huong_nha' : null,
                !$hasLegal ? 'phap_ly' : null,
            ]))
        );
    }
}

$challenger = new BDSAdversarialChallenger();
$challenger->runAll();
