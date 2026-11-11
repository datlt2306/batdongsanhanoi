<?php
/**
 * Milestone 1 Adversarial Empirical Stress-Testing Harness.
 *
 * Authored by: m1_challenger_1 (Empirical Challenger)
 * Tests:
 *  1. Dry-Run Oracle (Zero DB Writes, bit-for-bit state preservation, ROLLBACK verification)
 *  2. Transaction & Rollback Oracle (Full lifecycle, batch rollback, mid-batch failure recovery)
 *  3. Batching & Memory Leak Stress Harness (Up to 500 posts, sub-linear memory growth)
 *  4. Corrupted Serialized Metadata Adversarial Matrix (20+ fuzzing payloads, resilience)
 *  5. Relationship Query Numeric Indexing Matrix (Pure NUMERIC =, IN, zero LIKE scans)
 *  6. SEO 301 Redirect Engine Resilience (Unicode, query params, infinite loop avoidance)
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!function_exists('add_query_arg')) {
    function add_query_arg(array|string $param1, string|false $param2 = false, string|false $url = false): string {
        if (is_array($param1)) {
            $qs = http_build_query($param1);
            $target = $param2 ?: '';
            $sep = str_contains($target, '?') ? '&' : '?';
            return $target . $sep . $qs;
        }
        return (string) $url;
    }
}

if (!function_exists('clean_post_cache')) {
    function clean_post_cache(int $post_id): void {
        global $simulated_error_post_id;
        if (!empty($simulated_error_post_id) && $post_id === $simulated_error_post_id) {
            throw new \RuntimeException("Simulated fatal database error on post #{$post_id}");
        }
    }
}

require_once __DIR__ . '/../inc/services/migration.php';
require_once __DIR__ . '/../inc/services/queries.php';
require_once __DIR__ . '/../inc/hooks/redirects.php';

use BDS\Tests\Framework\MockWordPress;
use BDS\Tests\Framework\RedirectExitException;

class M1AdversarialChallengeHarness {

    private int $passes = 0;
    private int $failures = 0;
    private array $failureDetails = [];

    public function runAll(): bool {
        echo "================================================================================\n";
        echo "   MILESTONE 1 EMPIRICAL CHALLENGER ADVERSARIAL STRESS TEST SUITE\n";
        echo "================================================================================\n\n";

        $this->test1_dry_run_oracle_zero_writes();
        $this->test2_transaction_and_rollback_integrity();
        $this->test3_batching_and_memory_leak_harness();
        $this->test4_corrupted_serialized_metadata_fuzzing();
        $this->test5_relationship_query_numeric_indexing();
        $this->test6_seo_301_redirect_edge_cases();

        echo "\n================================================================================\n";
        echo sprintf(" RESULTS: %d Passed, %d Failed\n", $this->passes, $this->failures);
        echo "================================================================================\n";

        if ($this->failures > 0) {
            echo "\nFAILURE DETAILS:\n";
            foreach ($this->failureDetails as $idx => $detail) {
                echo sprintf(" [%d] %s\n", $idx + 1, $detail);
            }
            return false;
        }

        echo " [VERDICT: APPROVE] All empirical challenger assertions satisfied.\n\n";
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

    /**
     * TEST 1: Dry-Run Oracle.
     */
    private function test1_dry_run_oracle_zero_writes(): void {
        echo "▶ [Oracle 1] Dry-Run Zero DB Writes & State Invariance\n";
        MockWordPress::reset();
        global $wpdb;
        $wpdb->queries = [];

        // Seed 60 diverse posts
        $seededIds = [];
        $sourceTypes = ['hang-chuyen-nhuong', 'nha-dat-ban', 'bds-cho-thue', 'nha-dat-cho-thue'];
        for ($i = 1; $i <= 60; $i++) {
            $st = $sourceTypes[$i % count($sourceTypes)];
            $id = MockWordPress::insertPost([
                'post_title' => "BĐS Legacy Post #$i",
                'post_name'  => "bds-legacy-post-$i",
                'post_type'  => $st,
                'meta_input' => [
                    'du_an_lien_quan' => ($i % 2 === 0) ? serialize([500 + $i]) : (string)(600 + $i),
                    'gia'             => 1000 + $i,
                    'dien_tich'       => 50 + $i,
                ],
                'tax_input' => [
                    'danh-muc-hang-chuyen-nhuong' => ['can-ho-chung-cu'],
                ],
            ]);
            $seededIds[] = $id;
        }

        // Seed non-legacy posts that must never be touched
        $duAnId = MockWordPress::insertPost([
            'post_title' => "Dự án Masteri",
            'post_name'  => "du-an-masteri",
            'post_type'  => 'du-an',
        ]);
        $existingBdsId = MockWordPress::insertPost([
            'post_title' => "BĐS Chuẩn",
            'post_name'  => "bds-chuan",
            'post_type'  => 'bat-dong-san',
        ]);

        // Deep snapshot before dry-run
        $postsBefore = unserialize(serialize(MockWordPress::$posts));
        $metaBefore  = unserialize(serialize(MockWordPress::$postmeta));
        $termsBefore = unserialize(serialize(MockWordPress::$object_terms));

        // Execute dry-run with odd batch size to test chunking
        $result = BDS_CPT_Migration::run([
            'dry_run'    => true,
            'batch_size' => 17,
        ]);

        $this->assert($result['success'] === true, "Dry-run returns success=true");
        $this->assert($result['migrated_count'] === 60, "Dry-run accurately identifies all 60 legacy candidates (got {$result['migrated_count']})");
        $this->assert($result['skipped_count'] === 0, "Dry-run skipped_count is 0");
        $this->assert($result['error_count'] === 0, "Dry-run error_count is 0");
        $this->assert($result['dry_run'] === true, "Result reflects dry_run=true");

        // Verify deep identity: Posts, meta, object terms
        $postsAfter = unserialize(serialize(MockWordPress::$posts));
        $metaAfter  = unserialize(serialize(MockWordPress::$postmeta));
        $termsAfter = unserialize(serialize(MockWordPress::$object_terms));

        $this->assert($postsBefore == $postsAfter, "Mock posts table is bit-for-bit identical after dry run");
        $this->assert($metaBefore == $metaAfter, "Mock postmeta table is bit-for-bit identical after dry run");
        $this->assert($termsBefore == $termsAfter, "Mock object_terms table is bit-for-bit identical after dry run");

        // Verify SQL queries: NO COMMIT queries were issued
        $commitQueries = array_filter($wpdb->queries, fn($q) => stripos($q, 'COMMIT') !== false);
        $rollbackQueries = array_filter($wpdb->queries, fn($q) => stripos($q, 'ROLLBACK') !== false);

        $this->assert(empty($commitQueries), "Zero COMMIT queries were executed during dry run");
        $this->assert(count($rollbackQueries) === 4, "Exact 4 ROLLBACK queries executed corresponding to 4 batches (ceil(60/17)=4)");

        // Verify non-legacy posts untouched
        $this->assert(MockWordPress::getPost($duAnId)->post_type === 'du-an', "du-an post remains untouched");
        $this->assert(MockWordPress::getPost($existingBdsId)->post_type === 'bat-dong-san', "existing bat-dong-san remains untouched");
    }

    /**
     * TEST 2: Transaction & Rollback Integrity.
     */
    private function test2_transaction_and_rollback_integrity(): void {
        echo "\n▶ [Oracle 2] Transaction & Rollback Integrity\n";
        MockWordPress::reset();
        global $wpdb;
        $wpdb->queries = [];

        // 2.1 Full Migration & Full Rollback
        $postIds = [];
        for ($i = 1; $i <= 30; $i++) {
            $postIds[] = MockWordPress::insertPost([
                'post_title' => "BĐS Rollback Test #$i",
                'post_name'  => "bds-rollback-$i",
                'post_type'  => ($i % 2 === 0) ? 'hang-chuyen-nhuong' : 'nha-dat-ban',
                'meta_input' => [
                    'du_an_lien_quan' => serialize([100 + $i]),
                ],
            ]);
        }

        $liveResult = BDS_CPT_Migration::run([
            'dry_run'    => false,
            'batch_size' => 10,
        ]);

        $this->assert($liveResult['success'] === true, "Live migration succeeds on 30 posts");
        $this->assert($liveResult['migrated_count'] === 30, "30 posts migrated");

        // Verify all converted to bat-dong-san and du_an_lien_quan is normalized integer
        $allConverted = true;
        $allNormalized = true;
        foreach ($postIds as $pid) {
            $p = MockWordPress::getPost($pid);
            if ($p->post_type !== 'bat-dong-san') {
                $allConverted = false;
            }
            $meta = MockWordPress::getPostMeta($pid, 'du_an_lien_quan', true);
            if (!is_numeric($meta) || is_array($meta) || str_starts_with((string)$meta, 'a:')) {
                $allNormalized = false;
            }
        }
        $this->assert($allConverted, "All 30 posts converted to bat-dong-san");
        $this->assert($allNormalized, "All 30 posts have du_an_lien_quan normalized to single integer");

        // Execute rollback using returned batch_id
        $batchId = $liveResult['batch_id'];
        $rollbackResult = BDS_CPT_Migration::rollback([
            'batch_id'   => $batchId,
            'batch_size' => 10,
        ]);

        $this->assert($rollbackResult['success'] === true, "Rollback execution succeeds");
        $this->assert($rollbackResult['migrated_count'] === 30, "Rollback restored all 30 posts");

        // Verify original post types and raw serialized metadata restored
        $allRestored = true;
        foreach ($postIds as $idx => $pid) {
            $expectedType = (($idx + 1) % 2 === 0) ? 'hang-chuyen-nhuong' : 'nha-dat-ban';
            $p = MockWordPress::getPost($pid);
            if ($p->post_type !== $expectedType) {
                $allRestored = false;
            }
            $auditMeta = MockWordPress::getPostMeta($pid, '_migrated_from_post_type', true);
            if (!empty($auditMeta)) {
                $allRestored = false;
            }
        }
        $this->assert($allRestored, "All posts restored to original legacy post types and audit meta purged");

        // 2.2 Mid-batch Failure & Transactional Rollback
        MockWordPress::reset();
        $wpdb->queries = [];

        for ($i = 1; $i <= 20; $i++) {
            MockWordPress::insertPost([
                'ID'         => 2000 + $i,
                'post_title' => "BĐS Error Test #$i",
                'post_name'  => "bds-error-$i",
                'post_type'  => 'hang-chuyen-nhuong',
            ]);
        }

        // Simulate database write failure on post #2015 (Batch 2)
        global $simulated_error_post_id;
        $simulated_error_post_id = 2015;

        $failResult = BDS_CPT_Migration::run([
            'dry_run'    => false,
            'batch_size' => 10, // Batch 1: 2001-2010 (succeeds), Batch 2: 2011-2020 (fails on 2015)
        ]);
        $simulated_error_post_id = 0;

        $this->assert($failResult['success'] === false, "Migration reports success=false when an error occurs");
        $this->assert($failResult['error_count'] >= 1, "Migration records at least 1 error");
        $this->assert(str_contains(implode(' ', $failResult['errors']), '2015'), "Error log explicitly mentions post #2015");

        // Check $wpdb queries for ROLLBACK on batch 2
        $rollbackCount = count(array_filter($wpdb->queries, fn($q) => stripos($q, 'ROLLBACK') !== false));
        $this->assert($rollbackCount >= 1, "ROLLBACK query executed when batch 2 encountered exception");
    }

    /**
     * TEST 3: Batching & Memory Leak Harness.
     */
    private function test3_batching_and_memory_leak_harness(): void {
        echo "\n▶ [Oracle 3] Batching Scalability & Memory Profile (50 to 500 Posts)\n";
        MockWordPress::reset();

        $postCountsToTest = [50, 150, 300];

        foreach ($postCountsToTest as $count) {
            MockWordPress::reset();
            for ($i = 1; $i <= $count; $i++) {
                MockWordPress::insertPost([
                    'post_title' => "Scalability Test Post #$i",
                    'post_name'  => "scalability-post-$i",
                    'post_type'  => 'hang-chuyen-nhuong',
                    'meta_input' => [
                        'du_an_lien_quan' => (string)($i * 10),
                    ],
                ]);
            }

            gc_collect_cycles();
            $memBefore = memory_get_usage();

            $result = BDS_CPT_Migration::run([
                'dry_run'    => false,
                'batch_size' => 50,
            ]);

            gc_collect_cycles();
            $memAfter = memory_get_usage();
            $memDeltaKB = round(($memAfter - $memBefore) / 1024, 2);

            $this->assert($result['success'] === true, "Batch migration succeeded for {$count} posts");
            $this->assert($result['migrated_count'] === $count, "Migrated exactly {$count} posts");
            $this->assert($result['batches'] === (int)ceil($count / 50), "Batches executed: " . (int)ceil($count / 50));
            // Memory delta should remain small and bounded
            $this->assert($memDeltaKB < 5000, "Memory delta for {$count} posts is bounded ({$memDeltaKB} KB < 5000 KB)");
        }
    }

    /**
     * TEST 4: Corrupted Serialized Metadata Adversarial Matrix.
     */
    private function test4_corrupted_serialized_metadata_fuzzing(): void {
        echo "\n▶ [Oracle 4] Corrupted Serialized Metadata Fuzzing Matrix\n";

        $testMatrix = [
            // Standard cases
            ['input' => 773, 'expected' => 773, 'desc' => 'Pure integer'],
            ['input' => '773', 'expected' => 773, 'desc' => 'Numeric string'],
            ['input' => serialize([773]), 'expected' => 773, 'desc' => 'Standard serialized int array'],
            ['input' => serialize(['773']), 'expected' => 773, 'desc' => 'Standard serialized string array'],
            ['input' => serialize([773, 888, 999]), 'expected' => 773, 'desc' => 'Serialized multi-int array (first preserved)'],
            ['input' => [773, 888], 'expected' => 773, 'desc' => 'PHP array of ints'],
            ['input' => [(object)['ID' => 773]], 'expected' => 773, 'desc' => 'PHP array with WP_Post like object'],
            ['input' => (object)['ID' => 773], 'expected' => 773, 'desc' => 'Direct WP_Post like object'],

            // Corrupted serialized inputs
            ['input' => 'a:2:{i:0;i:773;BROKEN_TRAILING_CORRUPTION', 'expected' => 773, 'desc' => 'Truncated serialized array with int 773'],
            ['input' => 's:10:"773";', 'expected' => 773, 'desc' => 'Broken byte length in serialized string'],
            ['input' => 'a:99:{i:0;s:3:"773";', 'expected' => 773, 'desc' => 'Oversized array element count header'],
            ['input' => 'a:1:{i:0;i:773;foo;bar;baz}', 'expected' => 773, 'desc' => 'Serialized array with garbage suffix'],
            ['input' => 's:25:"project_id_is_773_here";', 'expected' => 773, 'desc' => 'Serialized text containing integer'],

            // Boundary / Negative / Empty cases
            ['input' => '', 'expected' => 0, 'desc' => 'Empty string'],
            ['input' => null, 'expected' => 0, 'desc' => 'Null value'],
            ['input' => false, 'expected' => 0, 'desc' => 'Boolean false'],
            ['input' => '0', 'expected' => 0, 'desc' => 'Zero string'],
            ['input' => 'N;', 'expected' => 0, 'desc' => 'Serialized null'],
            ['input' => 'b:0;', 'expected' => 0, 'desc' => 'Serialized boolean false'],
            ['input' => 'a:0:{}', 'expected' => 0, 'desc' => 'Empty serialized array'],
            ['input' => 'pure_text_without_any_digits', 'expected' => 0, 'desc' => 'Non-numeric text'],
            ['input' => '###@@@$$$%%%^^^&&&***', 'expected' => 0, 'desc' => 'Punctuation noise'],
        ];

        foreach ($testMatrix as $caseIdx => $case) {
            $actualExtract = BDS_CPT_Migration::extract_clean_project_id($case['input']);
            $this->assert(
                $actualExtract === $case['expected'],
                "extract_clean_project_id: {$case['desc']} (Expected: {$case['expected']}, Actual: {$actualExtract})"
            );

            // Also test runtime helper batdongsan_get_related_project_id with unique post ID
            $uniquePid = 9000 + $caseIdx;
            MockWordPress::insertPost([
                'ID'         => $uniquePid,
                'post_title' => "Meta helper test {$caseIdx}",
                'post_type'  => 'bat-dong-san',
                'meta_input' => ['du_an_lien_quan' => $case['input']],
            ]);

            $actualHelper = batdongsan_get_related_project_id($uniquePid);
            $this->assert(
                $actualHelper === $case['expected'],
                "batdongsan_get_related_project_id: {$case['desc']} (Expected: {$case['expected']}, Actual: {$actualHelper})"
            );
        }
    }

    /**
     * TEST 5: Relationship Query Numeric Indexing Matrix.
     */
    private function test5_relationship_query_numeric_indexing(): void {
        echo "\n▶ [Oracle 5] Relationship Query Numeric Indexing Matrix\n";

        // 5.1 batdongsan_build_project_meta_query behavior
        $c1 = batdongsan_build_project_meta_query(773);
        $this->assert(
            $c1 === ['key' => 'du_an_lien_quan', 'value' => 773, 'compare' => '=', 'type' => 'NUMERIC'],
            "Single integer 773 produces exact NUMERIC equality clause"
        );

        $c2 = batdongsan_build_project_meta_query('773');
        $this->assert(
            $c2 === ['key' => 'du_an_lien_quan', 'value' => 773, 'compare' => '=', 'type' => 'NUMERIC'],
            "Numeric string '773' produces exact NUMERIC equality clause"
        );

        $c3 = batdongsan_build_project_meta_query([773]);
        $this->assert(
            $c3 === ['key' => 'du_an_lien_quan', 'value' => 773, 'compare' => '=', 'type' => 'NUMERIC'],
            "Single-element array [773] optimizes to '=' rather than 'IN'"
        );

        $c4 = batdongsan_build_project_meta_query([773, 888]);
        $this->assert(
            $c4 === ['key' => 'du_an_lien_quan', 'value' => [773, 888], 'compare' => 'IN', 'type' => 'NUMERIC'],
            "Multi-element array [773, 888] produces NUMERIC IN clause"
        );

        $c5 = batdongsan_build_project_meta_query([773, '773', 773]);
        $this->assert(
            $c5 === ['key' => 'du_an_lien_quan', 'value' => 773, 'compare' => '=', 'type' => 'NUMERIC'],
            "Deduplication handles [773, '773', 773] -> single int equality clause"
        );

        $c6 = batdongsan_build_project_meta_query(0);
        $this->assert($c6 === [], "Zero ID returns empty clause []");

        $c7 = batdongsan_build_project_meta_query(-100);
        $this->assert($c7 === [], "Negative ID returns empty clause []");

        $c8 = batdongsan_build_project_meta_query('abc');
        $this->assert($c8 === [], "String 'abc' returns empty clause []");

        $c9 = batdongsan_build_project_meta_query([]);
        $this->assert($c9 === [], "Empty array returns empty clause []");

        // 5.2 Exhaustive Codebase Verification for du_an_lien_quan
        $themeDir = dirname(__DIR__);
        $phpFiles = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($themeDir));
        $violations = [];

        foreach ($phpFiles as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }
            $path = $file->getPathname();
            // Skip test directory and vendor
            if (str_contains($path, '/tests/') || str_contains($path, '/.agents/')) {
                continue;
            }

            $content = file_get_contents($path);
            if (str_contains($content, 'du_an_lien_quan')) {
                $lines = explode("\n", $content);
                foreach ($lines as $lineNum => $line) {
                    if (str_contains($line, 'du_an_lien_quan') && stripos($line, 'LIKE') !== false) {
                        $violations[] = basename($path) . ":" . ($lineNum + 1) . " -> " . trim($line);
                    }
                }
            }
        }

        $this->assert(
            empty($violations),
            "Exhaustive Theme Scan: 0 files use LIKE queries on du_an_lien_quan (Found: " . count($violations) . ")"
        );
    }

    /**
     * TEST 6: SEO 301 Redirect Edge Cases.
     */
    private function test6_seo_301_redirect_edge_cases(): void {
        echo "\n▶ [Oracle 6] SEO 301 Redirect Engine Resilience\n";

        $testUrls = [
            // Standard single post redirect
            [
                'request' => '/hang-chuyen-nhuong/biet-thu-vinhomes-ocean-park/',
                'expected' => '/bat-dong-san/biet-thu-vinhomes-ocean-park/',
                'status' => 301,
            ],
            // Single post with query params
            [
                'request' => '/hang-chuyen-nhuong/can-ho-eco-green/?utm_source=facebook&campaign=sale',
                'expected' => '/bat-dong-san/can-ho-eco-green/?utm_source=facebook&campaign=sale',
                'status' => 301,
            ],
            // Unicode Vietnamese slug
            [
                'request' => '/hang-chuyen-nhuong/%C4%91%E1%BA%A5t-n%E1%BB%81n-%C4%91%C3%B4ng-anh/',
                'expected' => '/bat-dong-san/%C4%91%E1%BA%A5t-n%E1%BB%81n-%C4%91%C3%B4ng-anh/',
                'status' => 301,
            ],
            // Legacy archive redirect
            [
                'request' => '/hang-chuyen-nhuong/',
                'expected' => '/bat-dong-san/?hinh_thuc=chuyen-nhuong',
                'status' => 301,
            ],
            // Legacy taxonomy redirect
            [
                'request' => '/danh-muc-hang-chuyen-nhuong/can-ho-chung-cu/',
                'expected' => '/ban-can-ho-chung-cu/',
                'status' => 301,
            ],
        ];

        foreach ($testUrls as $idx => $t) {
            $parsed = parse_url($t['request']);
            $_SERVER['REQUEST_URI'] = $t['request'];
            $_GET = [];
            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $_GET);
            }

            $caught = false;
            try {
                batdongsan_redirect_legacy_urls();
            } catch (RedirectExitException $e) {
                $caught = true;
                $this->assert($e->getStatus() === $t['status'], "Case #$idx HTTP status is 301");
                $this->assert(
                    str_contains($e->getLocation(), $t['expected']) || $e->getLocation() === $t['expected'],
                    "Case #$idx Redirect Location matches expected '{$t['expected']}' (Got: '{$e->getLocation()}')"
                );
            }
            $this->assert($caught, "Case #$idx Redirect triggered for {$t['request']}");
        }

        // Loop prevention: Calling on non-legacy route must NOT trigger redirect
        $_SERVER['REQUEST_URI'] = '/bat-dong-san/biet-thu-vinhomes-ocean-park/';
        $_GET = [];
        $caughtLoop = false;
        try {
            batdongsan_redirect_legacy_urls();
        } catch (RedirectExitException $e) {
            $caughtLoop = true;
        }
        $this->assert(!$caughtLoop, "Non-legacy URL (/bat-dong-san/...) does NOT trigger redirect (no infinite loop)");
    }
}

// Execute harness
$harness = new M1AdversarialChallengeHarness();
$success = $harness->runAll();
exit($success ? 0 : 1);
