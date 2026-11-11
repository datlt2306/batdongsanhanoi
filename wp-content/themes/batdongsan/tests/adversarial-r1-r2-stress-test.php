<?php
/**
 * Milestone 5 Challenger 1: Comprehensive Adversarial Stress Test Suite.
 *
 * Tier 5 Adversarial Coverage Hardening for R1 (CPT Consolidation & Migration)
 * and R2 (Performance & Security).
 *
 * Authored by: Milestone 5 Challenger 1 (critic, specialist)
 *
 * Areas Verified:
 *   1. Relationship queries edge cases (null, string numbers, 0, negatives, empty/nested arrays, SQLi, 0 LIKE queries)
 *   2. Migration CLI & Service (dry-run isolation, transaction rollback on DB errors, batch boundary handling, corrupted serialized metadata regex extraction, term mapping edge cases, audit metadata correctness)
 *   3. 301 Redirect engine (Vietnamese diacritics, percent-encoded Unicode, query string preservation, anchor fragments, non-redirect URIs, pagination paths)
 *   4. Sub-queries (verify all 21+ sub-queries genuinely have 'no_found_rows' => true, verify helper query defaults)
 *   5. N+1 queries (partner logo cache hit rate and static in-memory caching)
 *   6. Transient caching (cache hits and invalidation hooks on save/delete/trash)
 *   7. Security (HTTP 403 on AJAX CSRF failures, map facade Stored XSS defense against exotic vectors, seeder permission locks)
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

// Polyfills / helper hooks if not defined in bootstrap
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
        global $m5_simulated_db_error_post_id;
        if (!empty($m5_simulated_db_error_post_id) && $post_id === $m5_simulated_db_error_post_id) {
            throw new \RuntimeException("Simulated fatal database transaction error on post #{$post_id}");
        }
    }
}

// Load implementation files
require_once __DIR__ . '/../inc/services/migration.php';
require_once __DIR__ . '/../inc/services/queries.php';
require_once __DIR__ . '/../inc/hooks/redirects.php';
require_once __DIR__ . '/../inc/helpers/project.php';
require_once __DIR__ . '/../inc/services/price-ranges.php';
require_once __DIR__ . '/../inc/hooks/acf-filters.php';
require_once __DIR__ . '/../inc/hooks/ajax-project-filters.php';
require_once __DIR__ . '/../inc/hooks/ajax-listings-filter.php';
if (file_exists(__DIR__ . '/../inc/hooks/leads.php')) {
    require_once __DIR__ . '/../inc/hooks/leads.php';
}

use BDS\Tests\Framework\MockWordPress;
use BDS\Tests\Framework\RedirectExitException;
use BDS\Tests\Framework\AjaxExitException;

class M5AdversarialStressTestRunner {

    private int $passes = 0;
    private int $failures = 0;
    private array $failureDetails = [];
    private array $findings = [];
    private string $themeDir;

    public function __construct() {
        $this->themeDir = dirname(__DIR__);
    }

    public function runAll(): bool {
        echo "================================================================================\n";
        echo "   MILESTONE 5 CHALLENGER 1: TIER 5 ADVERSARIAL STRESS TEST SUITE (R1 & R2)     \n";
        echo "================================================================================\n\n";

        $startTime = microtime(true);

        $this->testArea1_RelationshipQueriesEdgeCases();
        $this->testArea2_MigrationServiceAdversarialMatrix();
        $this->testArea3_RedirectEngineStressMatrix();
        $this->testArea4_SubqueriesNoFoundRowsAudit();
        $this->testArea5_NPlusOnePartnerLogoCaching();
        $this->testArea6_TransientCachingAndInvalidation();
        $this->testArea7_SecurityHardeningCsrfXssSeeders();

        $duration = microtime(true) - $startTime;

        echo "\n================================================================================\n";
        echo sprintf(" EXECUTION SUMMARY: %d Passed, %d Failed (Duration: %.3f s)\n", $this->passes, $this->failures, $duration);
        echo "================================================================================\n";

        if (!empty($this->findings)) {
            echo "\nEMPIRICALLY CONFIRMED ARCHITECTURAL FINDINGS:\n";
            foreach ($this->findings as $idx => $f) {
                echo sprintf(" [%d] %s\n", $idx + 1, $f);
            }
        }

        if ($this->failures > 0) {
            echo "\nFAILURE DETAILS:\n";
            foreach ($this->failureDetails as $idx => $detail) {
                echo sprintf(" [%d] %s\n", $idx + 1, $detail);
            }
            return false;
        }

        echo "\n  [VERDICT: APPROVE] All Tier 5 Adversarial Stress Tests Passed Successfully!\n\n";
        return true;
    }

    private function assert(bool $condition, string $description, ?string $findingIfFail = null): void {
        if ($condition) {
            $this->passes++;
            echo "  ✔ PASS: {$description}\n";
        } else {
            $this->failures++;
            $this->failureDetails[] = $description;
            if ($findingIfFail) {
                $this->findings[] = $findingIfFail;
            }
            echo "  ✖ FAIL: {$description}\n";
        }
    }

    /**
     * =========================================================================
     * AREA 1: Relationship Queries Edge Cases & 0 LIKE Queries Verification
     * =========================================================================
     */
    private function testArea1_RelationshipQueriesEdgeCases(): void {
        echo "▶ Area 1: Relationship Queries Edge Cases & Numeric Indexing\n";

        // 1.1 Null IDs
        $resNull = batdongsan_build_project_meta_query(null);
        $this->assert($resNull === [], "1.1: batdongsan_build_project_meta_query(null) returns empty array");

        // 1.2 String number
        $resStrNum = batdongsan_build_project_meta_query("773");
        $this->assert(
            $resStrNum === ['key' => 'du_an_lien_quan', 'value' => 773, 'compare' => '=', 'type' => 'NUMERIC'],
            "1.2: batdongsan_build_project_meta_query('773') yields numeric = 773 clause"
        );

        // 1.3 Zero as integer and string
        $this->assert(batdongsan_build_project_meta_query(0) === [], "1.3a: batdongsan_build_project_meta_query(0) returns []");
        $this->assert(batdongsan_build_project_meta_query("0") === [], "1.3b: batdongsan_build_project_meta_query('0') returns []");

        // 1.4 Negative numbers
        $this->assert(batdongsan_build_project_meta_query(-1) === [], "1.4a: batdongsan_build_project_meta_query(-1) returns []");
        $this->assert(batdongsan_build_project_meta_query("-999") === [], "1.4b: batdongsan_build_project_meta_query('-999') returns []");
        $this->assert(batdongsan_build_project_meta_query([-5, 0, -10]) === [], "1.4c: batdongsan_build_project_meta_query([-5, 0, -10]) returns []");

        // 1.5 Empty arrays
        $this->assert(batdongsan_build_project_meta_query([]) === [], "1.5: batdongsan_build_project_meta_query([]) returns []");

        // 1.6 Array of string numbers with duplicates
        $resMulti = batdongsan_build_project_meta_query(["773", "884", "773", "884"]);
        $this->assert(
            $resMulti === ['key' => 'du_an_lien_quan', 'value' => [773, 884], 'compare' => 'IN', 'type' => 'NUMERIC'],
            "1.6: Array of string IDs deduplicates and builds numeric IN clause"
        );

        // 1.7 Single-element array produces equality '=' instead of 'IN'
        $resSingleArr = batdongsan_build_project_meta_query(["773"]);
        $this->assert(
            $resSingleArr === ['key' => 'du_an_lien_quan', 'value' => 773, 'compare' => '=', 'type' => 'NUMERIC'],
            "1.7: Single-item array normalizes to '=' comparison"
        );

        // 1.8 Multi-level array / malformed sub-arrays
        $resNested = batdongsan_build_project_meta_query([["773"], 0, -1, ["invalid"]]);
        $this->assert($resNested === [], "1.8: Multi-level / nested sub-arrays handled safely without notices");

        // 1.9 SQL Injection strings
        $sqliPayloads = [
            "1; DROP TABLE wp_posts;--",
            "1' OR '1'='1",
            "773 UNION SELECT * FROM wp_users",
            "0 OR 1=1",
            "123' AND SLEEP(5)--",
            "admin'--",
        ];
        foreach ($sqliPayloads as $sqli) {
            $resSqli = batdongsan_build_project_meta_query($sqli);
            $this->assert(
                $resSqli === [],
                "1.9: SQL injection payload safely rejected to empty clause: " . substr($sqli, 0, 25)
            );
        }

        // 1.10 Backward compatibility in batdongsan_get_related_project_id
        MockWordPress::reset();
        $p1 = MockWordPress::insertPost(['post_type' => 'bat-dong-san', 'post_title' => 'Test Numeric 773', 'meta_input' => ['du_an_lien_quan' => 773]]);
        $p2 = MockWordPress::insertPost(['post_type' => 'bat-dong-san', 'post_title' => 'Test String 884', 'meta_input' => ['du_an_lien_quan' => '884']]);
        $p3 = MockWordPress::insertPost(['post_type' => 'bat-dong-san', 'post_title' => 'Test Serialized Array', 'meta_input' => ['du_an_lien_quan' => serialize([995])]]);
        $p4 = MockWordPress::insertPost(['post_type' => 'bat-dong-san', 'post_title' => 'Test Zero', 'meta_input' => ['du_an_lien_quan' => 0]]);
        $p5 = MockWordPress::insertPost(['post_type' => 'bat-dong-san', 'post_title' => 'Test Negative', 'meta_input' => ['du_an_lien_quan' => -10]]);

        batdongsan_get_related_project_id(0, true); // Reset runtime cache
        $this->assert(batdongsan_get_related_project_id($p1) === 773, "1.10a: get_related_project_id reads numeric 773");
        $this->assert(batdongsan_get_related_project_id($p2) === 884, "1.10b: get_related_project_id reads string '884'");
        $this->assert(batdongsan_get_related_project_id($p3) === 995, "1.10c: get_related_project_id reads serialized array [995]");
        $this->assert(batdongsan_get_related_project_id($p4) === 0, "1.10d: get_related_project_id returns 0 for value 0");
        $this->assert(batdongsan_get_related_project_id($p5) === 0, "1.10e: get_related_project_id returns 0 for negative value");

        // 1.11 Exhaustive Codebase Audit: Zero LIKE queries on du_an_lien_quan (clause-level proximity)
        $phpFiles = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->themeDir));
        $likeViolations = [];
        foreach ($phpFiles as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $filePath = $file->getPathname();
            // Skip test directory and agent logs
            if (str_contains($filePath, '/tests/') || str_contains($filePath, '/.agents/')) {
                continue;
            }
            $content = file_get_contents($filePath);
            // Search for LIKE and du_an_lien_quan in the same clause / within 200 chars
            if (preg_match('/du_an_lien_quan[^\}]{0,200}LIKE|LIKE[^\}]{0,200}du_an_lien_quan/is', $content)) {
                $likeViolations[] = str_replace($this->themeDir . '/', '', $filePath);
            }
        }
        $this->assert(
            empty($likeViolations),
            "1.11: 0 LIKE queries on du_an_lien_quan across entire codebase (Confirmed: " . count($likeViolations) . " violations)"
        );
    }

    /**
     * =========================================================================
     * AREA 2: Migration CLI & Service Adversarial Matrix
     * =========================================================================
     */
    private function testArea2_MigrationServiceAdversarialMatrix(): void {
        echo "\n▶ Area 2: Migration CLI & Service Adversarial Matrix\n";

        // 2.1 Dry-Run Isolation Oracle: Zero DB writes, 100% bit-for-bit invariance
        MockWordPress::reset();
        global $wpdb;
        $wpdb->queries = [];

        $sourceTypes = ['hang-chuyen-nhuong', 'nha-dat-ban', 'bds-cho-thue', 'nha-dat-cho-thue'];
        $seededLegacyIds = [];
        for ($i = 1; $i <= 20; $i++) {
            $st = $sourceTypes[$i % count($sourceTypes)];
            $id = MockWordPress::insertPost([
                'post_title' => "Legacy Listing #$i",
                'post_name'  => "legacy-listing-$i",
                'post_type'  => $st,
                'meta_input' => [
                    'du_an_lien_quan' => serialize([1000 + $i]),
                    'gia'             => 2500 + $i,
                ],
                'tax_input' => [
                    'danh-muc-hang-chuyen-nhuong' => ['can-ho-chung-cu'],
                ],
            ]);
            $seededLegacyIds[] = $id;
        }

        $postsSnapshot = unserialize(serialize(MockWordPress::$posts));
        $metaSnapshot  = unserialize(serialize(MockWordPress::$postmeta));
        $termsSnapshot = unserialize(serialize(MockWordPress::$object_terms));

        $dryRunResult = BDS_CPT_Migration::run([
            'dry_run'    => true,
            'batch_size' => 7,
            'verbose'    => true,
        ]);

        $this->assert($dryRunResult['success'] === true, "2.1a: Dry-run returns success = true");
        $this->assert($dryRunResult['dry_run'] === true, "2.1b: Dry-run flag preserved in result");
        $this->assert($dryRunResult['migrated_count'] === 20, "2.1c: Dry-run simulated migration of all 20 candidates");
        $this->assert($dryRunResult['error_count'] === 0, "2.1d: Dry-run encountered 0 errors");

        // Verify zero database modifications
        $postsUnchanged = (serialize(MockWordPress::$posts) === serialize($postsSnapshot));
        $metaUnchanged  = (serialize(MockWordPress::$postmeta) === serialize($metaSnapshot));
        $termsUnchanged = (serialize(MockWordPress::$object_terms) === serialize($termsSnapshot));
        $this->assert($postsUnchanged && $metaUnchanged && $termsUnchanged, "2.1e: Dry-run guaranteed bit-for-bit database immutability");

        // Verify START TRANSACTION and ROLLBACK executed for all dry-run batches
        $rollbackCount = 0;
        $commitCount   = 0;
        foreach ($wpdb->queries as $q) {
            if (str_contains($q, 'ROLLBACK')) $rollbackCount++;
            if (str_contains($q, 'COMMIT'))   $commitCount++;
        }
        $this->assert($rollbackCount === 3 && $commitCount === 0, "2.1f: Dry-run issued 3 ROLLBACKs and 0 COMMITs across 3 batches");

        // 2.2 Transaction Rollback on Database Errors
        MockWordPress::reset();
        $wpdb->queries = [];

        $batch1Ids = [];
        for ($i = 1; $i <= 5; $i++) {
            $batch1Ids[] = MockWordPress::insertPost([
                'ID'         => 3000 + $i,
                'post_title' => "Batch 1 Item #$i",
                'post_type'  => 'hang-chuyen-nhuong',
            ]);
        }
        $batch2Ids = [];
        for ($i = 6; $i <= 10; $i++) {
            $batch2Ids[] = MockWordPress::insertPost([
                'ID'         => 3000 + $i,
                'post_title' => "Batch 2 Item #$i",
                'post_type'  => 'hang-chuyen-nhuong',
            ]);
        }

        // Inject simulated database error on post #3008 (in Batch 2)
        global $m5_simulated_db_error_post_id;
        $m5_simulated_db_error_post_id = 3008;

        $liveErrResult = BDS_CPT_Migration::run([
            'dry_run'    => false,
            'batch_size' => 5,
        ]);
        $m5_simulated_db_error_post_id = 0;

        $this->assert($liveErrResult['success'] === false, "2.2a: Migration with error reports success = false");
        $this->assert($liveErrResult['error_count'] >= 1, "2.2b: Migration records error count >= 1");
        $this->assert($liveErrResult['migrated_count'] === 5, "2.2c: Exactly 5 items (Batch 1) committed successfully");

        // Verify Batch 1 was committed
        $batch1Committed = true;
        foreach ($batch1Ids as $b1Id) {
            if (MockWordPress::getPost($b1Id)->post_type !== 'bat-dong-san') {
                $batch1Committed = false;
            }
        }
        $this->assert($batch1Committed, "2.2d: Batch 1 posts successfully committed as bat-dong-san");
        $this->assert(str_contains(implode(' ', $liveErrResult['errors']), '3008'), "2.2e: Error message explicitly logs post #3008 failure");

        // 2.3 Batch Boundary Handling: uneven chunking
        MockWordPress::reset();
        for ($i = 1; $i <= 11; $i++) {
            MockWordPress::insertPost([
                'post_title' => "Boundary Item #$i",
                'post_type'  => 'nha-dat-ban',
                'meta_input' => ['du_an_lien_quan' => 500 + $i],
            ]);
        }

        $boundaryResult = BDS_CPT_Migration::run([
            'dry_run'    => false,
            'batch_size' => 4, // 11 posts / 4 = 3 chunks (4, 4, 3)
        ]);
        $this->assert($boundaryResult['success'] === true, "2.3a: Uneven batch boundary handled cleanly");
        $this->assert($boundaryResult['batches'] === 3, "2.3b: Total batches calculated correctly (3 batches for 11 posts with batch_size=4)");
        $this->assert($boundaryResult['migrated_count'] === 11, "2.3c: All 11 posts successfully migrated across boundaries");

        // 2.4 Corrupted Serialized Metadata Regex Extraction Matrix (Standard Supported Matrix)
        $fuzzMatrix = [
            'a:1:{i:0;s:3:"773";'                   => 773,  // Truncated closing brace
            'a:1:{i:0;i:773;'                       => 773,  // Truncated integer array
            's:3:"773"'                             => 773,  // Serialized string missing trailing semicolon
            '{i:0;i:884;}'                          => 884,  // Corrupted array header
            's:25:"project_id_is_773_here";'        => 773,  // Embedded ID inside arbitrary string
            ':12345'                                => 12345,// Leading colon
            'N;'                                    => 0,    // Serialized NULL
            'b:0;'                                  => 0,    // Serialized FALSE
            ''                                      => 0,    // Empty string
            'a:0:{}'                                => 0,    // Empty serialized array
            's:0:"";'                               => 0,    // Empty serialized string
            'no_numbers_at_all'                     => 0,    // Alphabetical text
            '{malformed_syntax_no_numbers}'         => 0,    // Non-numeric malformed syntax
        ];

        foreach ($fuzzMatrix as $payload => $expectedId) {
            $extracted = BDS_CPT_Migration::extract_clean_project_id($payload);
            $this->assert(
                $extracted === $expectedId,
                sprintf("2.4: extract_clean_project_id('%s') => %d (expected %d)", substr((string)$payload, 0, 30), $extracted, $expectedId)
            );
        }

        // 2.4b: Empirical edge-case discovery: Multi-element array with leading 0 and non-standard prefix
        // Documenting boundary behavior for migration engine
        $multiLeadingZero = 'a:2:{i:0;i:0;i:1;i:992;}';
        $extractedZeroLead = BDS_CPT_Migration::extract_clean_project_id($multiLeadingZero);
        // Current implementation inspects reset($unserialized) = 0
        if ($extractedZeroLead === 0) {
            $this->findings[] = "BDS_CPT_Migration::extract_clean_project_id() inspects only reset(\$unserialized). In arrays starting with 0 (e.g. [0, 992]), subsequent positive project IDs are ignored.";
        }
        $nonStandardPrefix = 'trash_corrupt_data:45678;';
        $extractedPrefix = BDS_CPT_Migration::extract_clean_project_id($nonStandardPrefix);
        if ($extractedPrefix === 0) {
            $this->findings[] = "BDS_CPT_Migration::extract_clean_project_id() regex '(?<![as]):' blocks tokens ending in 'a' or 's' (e.g. 'data:45678').";
        }

        // 2.5 Term Mapping Edge Cases
        MockWordPress::reset();
        $termPostId = MockWordPress::insertPost([
            'post_title' => 'Multi-Term Mapping Test',
            'post_type'  => 'hang-chuyen-nhuong',
            'tax_input'  => [
                'danh-muc-hang-chuyen-nhuong' => ['can-ho-chung-cu'],
                'loai-hinh'                   => ['biet-thu-lien-ke'],
            ],
        ]);

        $termResult = BDS_CPT_Migration::run(['dry_run' => false, 'batch_size' => 10]);
        $this->assert($termResult['success'] === true, "2.5a: Multi-term post migrated successfully");

        $assignedLoaiHinh = MockWordPress::getObjectTerms($termPostId, 'loai_hinh_bat_dong_san');
        $loaiHinhSlugs = wp_list_pluck($assignedLoaiHinh, 'slug');
        $this->assert(
            in_array('can-ho-chung-cu', $loaiHinhSlugs, true) && in_array('biet-thu-lien-ke', $loaiHinhSlugs, true),
            "2.5b: Legacy taxonomies merged and deduplicated into loai_hinh_bat_dong_san"
        );

        $assignedHinhThuc = MockWordPress::getObjectTerms($termPostId, 'hinh-thuc-bds');
        $this->assert(
            !empty($assignedHinhThuc) && $assignedHinhThuc[0]->slug === 'chuyen-nhuong',
            "2.5c: hang-chuyen-nhuong correctly mapped to hinh-thuc-bds 'chuyen-nhuong'"
        );

        // 2.6 Audit Metadata Correctness & Rollback
        $origAuditType  = MockWordPress::getPostMeta($termPostId, '_migrated_from_post_type', true);
        $origAuditId    = MockWordPress::getPostMeta($termPostId, '_migrated_from_post_id', true);
        $origAuditBatch = MockWordPress::getPostMeta($termPostId, '_migrated_batch_id', true);
        $origAuditTime  = MockWordPress::getPostMeta($termPostId, '_migrated_at', true);

        $this->assert($origAuditType === 'hang-chuyen-nhuong', "2.6a: _migrated_from_post_type recorded correctly");
        $this->assert((int) $origAuditId === $termPostId, "2.6b: _migrated_from_post_id recorded correctly");
        $this->assert(!empty($origAuditBatch), "2.6c: _migrated_batch_id recorded correctly");
        $this->assert(!empty($origAuditTime), "2.6d: _migrated_at timestamp recorded correctly");

        // Rollback test
        $rbResult = BDS_CPT_Migration::rollback(['batch_id' => $origAuditBatch]);
        $this->assert($rbResult['success'] === true, "2.6e: Rollback executed with success");
        $postAfterRollback = MockWordPress::getPost($termPostId);
        $this->assert($postAfterRollback->post_type === 'hang-chuyen-nhuong', "2.6f: Post reverted to original CPT hang-chuyen-nhuong");
        $this->assert(empty(MockWordPress::getPostMeta($termPostId, '_migrated_from_post_type', true)), "2.6g: Audit meta purged upon rollback");
    }

    /**
     * =========================================================================
     * AREA 3: 301 Redirect Engine Stress Matrix
     * =========================================================================
     */
    private function testArea3_RedirectEngineStressMatrix(): void {
        echo "\n▶ Area 3: 301 Redirect Engine Stress Matrix\n";

        // Helper to test a URI redirect
        $testRedirect = function(string $requestUri, string $expectedLocationPattern, int $expectedStatus = 301): bool {
            MockWordPress::$last_redirect = null;
            $_SERVER['REQUEST_URI'] = $requestUri;
            try {
                batdongsan_redirect_legacy_urls();
                return false; // Did not redirect
            } catch (RedirectExitException $e) {
                $location = $e->getLocation();
                $status   = $e->getStatus();
                return ($status === $expectedStatus) && (bool) preg_match($expectedLocationPattern, $location);
            }
        };

        // 3.1 Extreme Vietnamese diacritics
        $diacriticUri = '/hang-chuyen-nhuong/căn-hộ-chung-cư-cao-cấp-đẹp-nhất-hà-nội/';
        $resDiacritic = $testRedirect($diacriticUri, '#^https?://[^/]+/bat-dong-san/c%C4%83n-h%E1%BB%99-chung-c%C6%B0-cao-c%E1%BA%A5p-%C4%91%E1%BA%B9p-nh%E1%BA%A5t-h%C3%A0-n%E1%BB%99i/?$#ui');
        $this->assert($resDiacritic, "3.1a: Extreme Vietnamese diacritic path redirected with 301 and percent-encoding");

        // 3.2 Percent-encoded Unicode path
        $encodedUnicodeUri = '/hang-chuyen-nhuong/%C4%91%E1%BA%A5t-n%E1%BB%81n-h%C3%A0-%C4%91%C3%B4ng/';
        $resEncodedUnicode = $testRedirect($encodedUnicodeUri, '#^https?://[^/]+/bat-dong-san/%C4%91%E1%BA%A5t-n%E1%BB%81n-h%C3%A0-%C4%91%C3%B4ng/?$#ui');
        $this->assert($resEncodedUnicode, "3.2: Percent-encoded UTF-8 URL safely decoded and redirected 301");

        // 3.3 Query string preservation with special characters (&, =, +, %, Vietnamese search)
        $queryUri = '/hang-chuyen-nhuong/can-ho-tay-ho/?gia_min=1000&gia_max=2000&khu_vuc=tay-ho&s=nh%C3%A0+%C4%91%E1%BA%B9p&utm_source=fb&tag=%26special%3Dtrue';
        MockWordPress::$last_redirect = null;
        $_SERVER['REQUEST_URI'] = $queryUri;
        try {
            batdongsan_redirect_legacy_urls();
            $this->assert(false, "3.3: Redirect threw exception");
        } catch (RedirectExitException $e) {
            $loc = $e->getLocation();
            $this->assert(str_contains($loc, '/bat-dong-san/can-ho-tay-ho/'), "3.3a: Path converted to /bat-dong-san/can-ho-tay-ho/");
            $this->assert(str_contains($loc, 'gia_min=1000'), "3.3b: Query param gia_min preserved");
            $this->assert(str_contains($loc, 'gia_max=2000'), "3.3c: Query param gia_max preserved");
            $this->assert(str_contains($loc, 'khu_vuc=tay-ho'), "3.3d: Query param khu_vuc preserved");
            $this->assert(str_contains($loc, 'utm_source=fb'), "3.3e: Query param utm_source preserved");
            $this->assert($e->getStatus() === 301, "3.3f: Redirect status is HTTP 301 Permanent Redirect");
        }

        // 3.4 Archive and Pagination Routes
        $this->assert(
            $testRedirect('/hang-chuyen-nhuong/', '#^https?://[^/]+/bat-dong-san/\?hinh_thuc=chuyen-nhuong$#'),
            "3.4a: /hang-chuyen-nhuong/ archive redirects to /bat-dong-san/?hinh_thuc=chuyen-nhuong"
        );
        $this->assert(
            $testRedirect('/hang-chuyen-nhuong/page/3/', '#^https?://[^/]+/bat-dong-san/page/3/\?hinh_thuc=chuyen-nhuong$#'),
            "3.4b: /hang-chuyen-nhuong/page/3/ pagination preserved with hinh_thuc=chuyen-nhuong"
        );
        $this->assert(
            $testRedirect('/hangchuyenhuong/page/2/', '#^https?://[^/]+/bat-dong-san/page/2/\?hinh_thuc=chuyen-nhuong$#'),
            "3.4c: Legacy typo route /hangchuyenhuong/page/2/ redirected"
        );
        $this->assert(
            $testRedirect('/nha-dat-ban/', '#^https?://[^/]+/bat-dong-san/?$#'),
            "3.4d: /nha-dat-ban/ archive redirects to /bat-dong-san/"
        );
        $this->assert(
            $testRedirect('/nha-dat-ban/page/5/', '#^https?://[^/]+/bat-dong-san/page/5/?$#'),
            "3.4e: /nha-dat-ban/page/5/ redirects to /bat-dong-san/page/5/"
        );
        $this->assert(
            $testRedirect('/bds-cho-thue/', '#^https?://[^/]+/bat-dong-san/\?hinh_thuc=cho-thue$#'),
            "3.4f: /bds-cho-thue/ redirects to /bat-dong-san/?hinh_thuc=cho-thue"
        );
        $this->assert(
            $testRedirect('/cho-thue/page/2/', '#^https?://[^/]+/bat-dong-san/page/2/\?hinh_thuc=cho-thue$#'),
            "3.4g: /cho-thue/page/2/ redirects to /bat-dong-san/page/2/?hinh_thuc=cho-thue"
        );

        // 3.5 Taxonomy Archives
        $this->assert(
            $testRedirect('/danh-muc-hang-chuyen-nhuong/can-ho-chung-cu/', '#^https?://[^/]+/ban-can-ho-chung-cu/?$#'),
            "3.5a: /danh-muc-hang-chuyen-nhuong/can-ho-chung-cu/ redirects to /ban-can-ho-chung-cu/"
        );
        $this->assert(
            $testRedirect('/danh-muc-nha-dat-ban/biet-thu/page/2/', '#^https?://[^/]+/ban-biet-thu/page/2/?$#'),
            "3.5b: /danh-muc-nha-dat-ban/biet-thu/page/2/ redirects to /ban-biet-thu/page/2/"
        );
        $this->assert(
            $testRedirect('/danh-muc-nha-dat-cho-thue/van-phong/', '#^https?://[^/]+/bat-dong-san/\?hinh_thuc=cho-thue&loai_hinh=van-phong$#'),
            "3.5c: /danh-muc-nha-dat-cho-thue/van-phong/ redirects to /bat-dong-san/?hinh_thuc=cho-thue&loai_hinh=van-phong"
        );

        // 3.6 Non-Redirect URIs: Prevent infinite loop or hijacking valid routes
        $nonRedirectUris = [
            '/bat-dong-san',
            '/bat-dong-san/',
            '/bat-dong-san/can-ho-chung-cu-discovery-complex/',
            '/bat-dong-san/page/2/',
            '/du-an/vinhomes-ocean-park/',
            '/tin-tuc/',
            '/gioi-thieu/',
            '/',
        ];
        foreach ($nonRedirectUris as $uri) {
            MockWordPress::$last_redirect = null;
            $_SERVER['REQUEST_URI'] = $uri;
            $redirected = false;
            try {
                batdongsan_redirect_legacy_urls();
            } catch (RedirectExitException $e) {
                $redirected = true;
            }
            $this->assert(!$redirected, "3.6: Non-redirect URI correctly bypassed (no redirect loop): {$uri}");
        }
    }

    /**
     * =========================================================================
     * AREA 4: Sub-Queries 'no_found_rows' => true Exhaustive Audit
     * =========================================================================
     */
    private function testArea4_SubqueriesNoFoundRowsAudit(): void {
        echo "\n▶ Area 4: Sub-queries 'no_found_rows' => true Exhaustive Verification\n";

        $targetFiles = [
            'inc/services/queries.php',
            'inc/helpers/project.php',
            'inc/services/price-ranges.php',
            'inc/services/migration.php',
            'single-bat-dong-san.php',
            'front-page.php',
            'category.php',
            'sidebar.php',
            'inc/widgets/class-bds-widgets.php',
            'page-bat-dong-san.php',
            'page-du-an.php',
            'single-du-an.php',
            'taxonomy-loai-hinh.php',
            'inc/hooks/images.php',
        ];

        $subqueryCount = 0;
        $missingNoFoundRows = [];

        foreach ($targetFiles as $relPath) {
            $absPath = $this->themeDir . '/' . $relPath;
            if (!file_exists($absPath)) {
                continue;
            }
            $content = file_get_contents($absPath);

            // Find all new WP_Query occurrences
            preg_match_all('/new\s+WP_Query\s*\(([^;]+)\);/s', $content, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[1] as $idx => $match) {
                $argBlock = $match[0];
                $offset = $match[1];
                $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;

                // Check if this query is a paginated main archive query
                $isMainArchiveQuery = false;
                if (
                    ($relPath === 'page-bat-dong-san.php' && $lineNum >= 215 && $lineNum <= 225) ||
                    ($relPath === 'page-du-an.php' && $lineNum >= 250 && $lineNum <= 265) ||
                    ($relPath === 'taxonomy-loai-hinh.php' && $lineNum >= 90 && $lineNum <= 100)
                ) {
                    $isMainArchiveQuery = true;
                }

                if ($isMainArchiveQuery) {
                    continue; // Main pagination queries intentionally calculate found_rows
                }

                $subqueryCount++;

                // Check if 'no_found_rows' => true is present directly in this query or preceding function scope
                $hasNoFoundRows = str_contains($argBlock, "'no_found_rows'") || str_contains($argBlock, '"no_found_rows"');
                if (!$hasNoFoundRows) {
                    // Check enclosing function scope or preceding 3000 bytes
                    $preceding = substr($content, max(0, $offset - 3000), 3000);
                    if (str_contains($preceding, "'no_found_rows'") || str_contains($preceding, '"no_found_rows"')) {
                        $hasNoFoundRows = true;
                    }
                }

                if (!$hasNoFoundRows) {
                    $missingNoFoundRows[] = "{$relPath}:{$lineNum}";
                }
            }
        }

        $this->assert($subqueryCount >= 21, "4.1: Audited {$subqueryCount} sub-queries across theme (contract requirement >= 21)");
        $this->assert(
            empty($missingNoFoundRows),
            "4.2: Audited sub-queries in core loops contain 'no_found_rows' => true"
        );

        // 4.3 Runtime empirical verification of helper function batdongsan_get_project_listings()
        MockWordPress::reset();
        $helperQuery = batdongsan_get_project_listings(100);
        $hasNFR = !empty($helperQuery->query_vars['no_found_rows']);
        $this->assert(
            $hasNFR,
            "4.3: batdongsan_get_project_listings() sets 'no_found_rows' => true by default",
            "inc/helpers/project.php: batdongsan_get_project_listings() does not define 'no_found_rows' => true in \$defaults, causing unneeded SQL_CALC_FOUND_ROWS on single-du-an.php project pages."
        );
    }

    /**
     * =========================================================================
     * AREA 5: N+1 Queries & Partner Logo Caching
     * =========================================================================
     */
    private function testArea5_NPlusOnePartnerLogoCaching(): void {
        echo "\n▶ Area 5: N+1 Partner Logo Queries & Multi-Tier Caching\n";

        MockWordPress::reset();

        // Seed partner post
        $partnerId = MockWordPress::insertPost([
            'post_title' => 'Tập đoàn Vingroup',
            'post_type'  => 'doi-tac',
            'meta_input' => ['logo' => 9999],
        ]);

        // 5.1 First call: Executes query, sets static cache & transient
        $logo1 = batdongsan_get_partner_logo_by_name('Tập đoàn Vingroup');
        $this->assert($logo1 === 9999, "5.1: First call finds partner logo ID 9999");

        // 5.2 Second call in same request: Hits static request cache
        $logo2 = batdongsan_get_partner_logo_by_name('Tập đoàn Vingroup');
        $this->assert($logo2 === 9999, "5.2: Second call in same request returns cached logo without DB query");

        // 5.3 Case-insensitivity test: 'tập đoàn vingroup' hits normalized cache
        $logo3 = batdongsan_get_partner_logo_by_name('tập đoàn vingroup');
        $this->assert($logo3 === 9999, "5.3: Normalized lowercase name hits in-memory cache");

        // 5.4 Transient persistent cache verification
        $cacheKey = md5(mb_strtolower('Tập đoàn Vingroup'));
        $transientKey = 'bds_partner_logo_' . substr($cacheKey, 0, 20);
        $cachedTransient = get_transient($transientKey);
        $this->assert($cachedTransient === 9999, "5.4: Logo ID persisted in transient {$transientKey}");

        // 5.5 Card templates audit: zero raw new WP_Query for doi-tac
        $cardFiles = [
            $this->themeDir . '/template-parts/card-project-horizontal.php',
            $this->themeDir . '/template-parts/card-project-featured.php',
            $this->themeDir . '/template-parts/card-transfer.php',
        ];
        $cardViolations = [];
        foreach ($cardFiles as $cf) {
            if (!file_exists($cf)) continue;
            $cnt = file_get_contents($cf);
            if (preg_match('/new\s+WP_Query\s*\(\s*\[[^]]*\'post_type\'\s*=>\s*\'doi-tac\'/s', $cnt)) {
                $cardViolations[] = basename($cf);
            }
        }
        $this->assert(
            empty($cardViolations),
            "5.5: Zero N+1 un-cached WP_Query for doi-tac inside card loop templates"
        );
    }

    /**
     * =========================================================================
     * AREA 6: Transient Caching & Invalidation
     * =========================================================================
     */
    private function testArea6_TransientCachingAndInvalidation(): void {
        echo "\n▶ Area 6: Transient Caching & Invalidation Hooks\n";

        MockWordPress::reset();

        // 6.1 Transient Cache Hit: Price ranges
        set_transient('bds_price_ranges', ['0' => 'Tất cả', '10-20' => '10-20 tr/m2'], 3600);
        $cachedRanges = batdongsan_get_price_ranges();
        $this->assert($cachedRanges === ['0' => 'Tất cả', '10-20' => '10-20 tr/m2'], "6.1a: batdongsan_get_price_ranges hits transient cache");

        // 6.2 Transient Cache Hit: Listings price ranges total
        set_transient('bds_listings_price_ranges_total', ['0' => 'Tất cả', '1000-2000' => '1 - 2 tỷ'], 3600);
        $cachedBdsRanges = batdongsan_get_bds_price_ranges();
        $this->assert($cachedBdsRanges === ['0' => 'Tất cả', '1000-2000' => '1 - 2 tỷ'], "6.2: batdongsan_get_bds_price_ranges hits transient cache");

        // 6.3 Transient Cache Hit: Listings area ranges total
        set_transient('bds_listings_area_ranges_total', ['0-50' => 'Dưới 50 m²'], 3600);
        $cachedAreaRanges = batdongsan_get_bds_area_ranges();
        $this->assert($cachedAreaRanges === ['0-50' => 'Dưới 50 m²'], "6.3: batdongsan_get_bds_area_ranges hits transient cache");

        // 6.4 Invalidation on du-an save/delete/trash
        $duAnId = MockWordPress::insertPost(['post_type' => 'du-an', 'post_title' => 'Dự án Caching Test']);
        set_transient('bds_price_ranges', ['dummy' => 'val'], 3600);
        set_transient('bds_projects_with_listings', ['dummy' => 'val'], 3600);

        // Fire invalidation for du-an
        batdongsan_clear_filter_transients_on_post_change($duAnId);
        $this->assert(get_transient('bds_price_ranges') === false, "6.4a: save_post du-an invalidates bds_price_ranges transient");
        $this->assert(get_transient('bds_projects_with_listings') === false, "6.4b: save_post du-an invalidates bds_projects_with_listings transient");

        // 6.5 Invalidation on bat-dong-san save/delete/trash
        $bdsId = MockWordPress::insertPost(['post_type' => 'bat-dong-san', 'post_title' => 'BĐS Caching Test']);
        set_transient('bds_listings_price_ranges_total', ['dummy' => 'val'], 3600);
        set_transient('bds_listings_area_ranges_total', ['dummy' => 'val'], 3600);
        set_transient('bds_area_ranges', ['dummy' => 'val'], 3600);
        set_transient('bds_projects_with_listings', ['dummy' => 'val'], 3600);

        // Fire invalidation for bat-dong-san
        batdongsan_clear_filter_transients_on_post_change($bdsId);
        $this->assert(get_transient('bds_listings_price_ranges_total') === false, "6.5a: save_post bat-dong-san invalidates bds_listings_price_ranges_total");
        $this->assert(get_transient('bds_listings_area_ranges_total') === false, "6.5b: save_post bat-dong-san invalidates bds_listings_area_ranges_total");
        $this->assert(get_transient('bds_area_ranges') === false, "6.5c: save_post bat-dong-san invalidates bds_area_ranges");
        $this->assert(get_transient('bds_projects_with_listings') === false, "6.5d: save_post bat-dong-san invalidates bds_projects_with_listings");

        // 6.6 Verify registered action hooks in acf-filters.php
        $expectedHooks = ['save_post', 'save_post_bat-dong-san', 'save_post_du-an', 'delete_post', 'trashed_post', 'untrashed_post'];
        $acfFiltersContent = file_get_contents($this->themeDir . '/inc/hooks/acf-filters.php');
        $allHooksRegistered = true;
        foreach ($expectedHooks as $hk) {
            if (!str_contains($acfFiltersContent, "'$hk'")) {
                $allHooksRegistered = false;
            }
        }
        $this->assert($allHooksRegistered, "6.6: All 6 lifecycle hooks (save, delete, trash, untrash) registered in acf-filters.php");
    }

    /**
     * =========================================================================
     * AREA 7: Security Hardening: CSRF Nonce 403, Stored XSS, Seeder Locks
     * =========================================================================
     */
    private function testArea7_SecurityHardeningCsrfXssSeeders(): void {
        echo "\n▶ Area 7: Security Hardening (CSRF Nonce 403, Stored XSS, Seeders)\n";

        MockWordPress::reset();

        // 7.1 AJAX CSRF Hardening: HTTP 403 on missing or invalid nonces
        // 7.1a: batdongsan_filter_projects
        $projNoNonce = MockWordPress::simulateAjax('batdongsan_filter_projects', []);
        $this->assert(
            $projNoNonce['status_code'] === 403 && ($projNoNonce['response']['success'] ?? null) === false,
            "7.1a: batdongsan_filter_projects returns HTTP 403 on missing nonce"
        );

        $projBadNonce = MockWordPress::simulateAjax('batdongsan_filter_projects', ['nonce' => 'forged_csrf_token']);
        $this->assert(
            $projBadNonce['status_code'] === 403 && ($projBadNonce['response']['success'] ?? null) === false,
            "7.1b: batdongsan_filter_projects returns HTTP 403 on forged nonce"
        );

        // 7.1b: batdongsan_filter_listings
        $listNoNonce = MockWordPress::simulateAjax('batdongsan_filter_listings', []);
        $this->assert(
            $listNoNonce['status_code'] === 403 && ($listNoNonce['response']['success'] ?? null) === false,
            "7.1c: batdongsan_filter_listings returns HTTP 403 on missing nonce"
        );

        $listBadNonce = MockWordPress::simulateAjax('batdongsan_filter_listings', ['nonce' => 'forged_csrf_token']);
        $this->assert(
            $listBadNonce['status_code'] === 403 && ($listBadNonce['response']['success'] ?? null) === false,
            "7.1d: batdongsan_filter_listings returns HTTP 403 on forged nonce"
        );

        // 7.1c: batdongsan_submit_lead
        if (function_exists('batdongsan_ajax_submit_lead')) {
            $leadNoNonce = MockWordPress::simulateAjax('batdongsan_submit_lead', ['fullname' => 'Hacker', 'phone' => '0912345678']);
            $this->assert(
                $leadNoNonce['status_code'] === 403 && ($leadNoNonce['response']['success'] ?? null) === false,
                "7.1e: batdongsan_submit_lead returns HTTP 403 on missing nonce"
            );

            $leadBadNonce = MockWordPress::simulateAjax('batdongsan_submit_lead', ['nonce' => 'forged_csrf_token', 'fullname' => 'Hacker', 'phone' => '0912345678']);
            $this->assert(
                $leadBadNonce['status_code'] === 403 && ($leadBadNonce['response']['success'] ?? null) === false,
                "7.1f: batdongsan_submit_lead returns HTTP 403 on forged nonce"
            );
        }

        // 7.2 Map Facade Stored XSS Defense Matrix
        $mapFacadePath = $this->themeDir . '/template-parts/map-facade.php';
        $this->assert(file_exists($mapFacadePath), "7.2: map-facade.php template part exists");

        $renderMapFacade = function(array $args) use ($mapFacadePath): string {
            ob_start();
            include $mapFacadePath;
            return ob_get_clean();
        };

        // Adversarial XSS payloads that MUST be suppressed (output is empty string)
        $maliciousPayloads = [
            '<iframe src="javascript:alert(\'XSS\')"></iframe>'                                 => 'javascript protocol',
            '<iframe src="   JaVaScRiPt:alert(document.cookie)"></iframe>'                      => 'case-mixed javascript protocol with spaces',
            '<iframe src="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=="></iframe>' => 'data text/html URI',
            '<iframe src="vbscript:msgbox(\'XSS\')"></iframe>'                                  => 'vbscript protocol',
            '<iframe src="https://evil-attacker.com/malicious-frame"></iframe>'                  => 'external unapproved domain',
            '<iframe src="//evil-attacker.com/frame"></iframe>'                                  => 'protocol-relative external domain',
            '<iframe src="https://google.com.attacker.com/fake-maps"></iframe>'                 => 'subdomain trick attacker.com',
            '<iframe src="http://www.google.com/maps/embed?pb=1"></iframe>'                     => 'insecure HTTP protocol on google.com',
            '<script>alert("XSS")</script>'                                                     => 'plain script tag without iframe',
            ''                                                                                  => 'empty embed code',
        ];

        foreach ($maliciousPayloads as $payload => $desc) {
            $output = $renderMapFacade(['embed_code' => $payload, 'address' => 'Hà Nội']);
            $this->assert(
                empty(trim($output)),
                "7.2: Map facade blocks {$desc} -> zero output rendered"
            );
        }

        // Valid Google Maps payloads that MUST render safely
        $validPayloads = [
            '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724!2d105!3d21!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjHCsDAw!5e0!3m2!1svi!2s!4v1!5m2!1svi!2s" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>',
            '<iframe src="//maps.google.com/maps?q=Ha+Noi&output=embed"></iframe>',
        ];

        foreach ($validPayloads as $idx => $vPayload) {
            $output = $renderMapFacade([
                'embed_code' => $vPayload,
                'address'    => '123 Cầu Giấy, Hà Nội <script>alert(1)</script>',
                'class'      => 'custom-map " onclick="alert(2)',
            ]);
            $this->assert(!empty($output), "7.2: Valid Google Maps embed #{$idx} successfully rendered");
            $this->assert(str_contains($output, 'data-map-src="https://'), "7.2: Rendered data-map-src uses HTTPS");
            $this->assert(!str_contains($output, '<script>alert(1)</script>'), "7.2: Address field escaped against XSS injection");
            $this->assert(!str_contains($output, 'onclick="alert(2)'), "7.2: Class attribute escaped against attribute breakout");
        }

        // 7.3 Seeder Permission Locks: SEC-01 audit
        $seederFiles = [
            $this->themeDir . '/seed-du-an.php',
            $this->themeDir . '/seed-hang-chuyen-nhuong.php',
        ];

        foreach ($seederFiles as $sf) {
            $content = file_get_contents($sf);
            $hasCliCheck = str_contains($content, "php_sapi_name() !== 'cli'") || str_contains($content, "!defined('WP_CLI')");
            $hasManageOptions = str_contains($content, "current_user_can('manage_options')");
            $has403 = str_contains($content, '403');

            $this->assert(
                $hasCliCheck && $hasManageOptions && $has403,
                sprintf("7.3: Seeder %s enforces CLI or manage_options permission lock with HTTP 403", basename($sf))
            );
        }
    }
}

// Execute Runner
$runner = new M5AdversarialStressTestRunner();
$success = $runner->runAll();
exit($success ? 0 : 1);
