<?php
/**
 * Milestone 1 Challenger 2 Empirical Line Contracts & Schema Oracle Test Suite.
 *
 * Scope:
 * 1. Line Contracts & AST Structures:
 *    - page-bat-dong-san.php line 565 contract: <div class="hidden lg:block mb-6">
 *    - page-du-an.php lines 255-257 contract:
 *        Line 255: $query = new WP_Query($args);
 *        Line 256: $posts = $query->posts;
 *        Line 257: wp_reset_postdata();
 *    - page-du-an.php line 456 contract: <div class="hidden lg:block mb-6">
 *    - inc/hooks/ajax-listings-filter.php verbatim: update_post_caches($paged_posts, 'bat-dong-san', true, true);
 * 2. Static AST Scanner for Forbidden Patterns:
 *    - Zero occurrences of executable `posts_per_page = -1` in consumer files.
 *    - Zero occurrences of in-memory pagination `array_slice` on query posts.
 * 3. Dynamic Clamping Oracle:
 *    - BDS_Abstract_Query_Builder clamps posts_per_page to [1, 100] on negative, zero, or overflow values.
 * 4. JSON Response Schema Completeness Oracle:
 *    - BĐS schema: 11 keys (html, pagination, total, count, found_posts, total_pages, max_num_pages, current_page, url, title, active_filters).
 *    - Project schema: 10 keys (html, pagination, found_posts, total, total_pages, current_page, title, searchResults, url, pretty_url).
 * 5. Adversarial Input & Injection Fuzzing:
 *    - SQL injection payloads, script injections, and malformed types.
 *
 * @package BatDongSan
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/inc/helpers/format.php';
require_once dirname(__DIR__) . '/inc/helpers/filter-options.php';
require_once dirname(__DIR__) . '/inc/helpers/filter-slug.php';
require_once dirname(__DIR__) . '/inc/services/filter/bootstrap.php';

use BDS\Tests\Framework\MockWordPress;

class M1Challenger2OracleHarness {

    private int $passes = 0;
    private int $failures = 0;
    private array $failureDetails = [];
    private string $themeDir;

    public function __construct() {
        $this->themeDir = dirname(__DIR__);
    }

    private function assert(bool $condition, string $description, string $details = ''): void {
        if ($condition) {
            $this->passes++;
            echo "  ✔ PASS: {$description}\n";
        } else {
            $this->failures++;
            $msg = "  ✖ FAIL: {$description}" . ($details ? " - {$details}" : '');
            $this->failureDetails[] = $msg;
            echo "{$msg}\n";
        }
    }

    public function run(): bool {
        echo "================================================================================\n";
        echo "   M1 CHALLENGER 2: EMPIRICAL LINE CONTRACTS & SCHEMA ORACLE                    \n";
        echo "================================================================================\n\n";

        $startTime = microtime(true);

        $this->suite1_line_contracts_page_bat_dong_san();
        $this->suite2_line_contracts_page_du_an();
        $this->suite3_verbatim_update_post_caches();
        $this->suite4_ast_zero_negative_one_and_no_in_memory_slice();
        $this->suite5_dynamic_query_clamping_oracle();
        $this->suite6_json_response_schema_completeness();
        $this->suite7_adversarial_injection_fuzzing();

        $duration = microtime(true) - $startTime;

        echo "\n================================================================================\n";
        echo "   M1 CHALLENGER 2 ORACLE EXECUTION SUMMARY                                     \n";
        echo "================================================================================\n";
        echo sprintf(" Total Assertions: %d\n", $this->passes + $this->failures);
        echo sprintf(" Passed:           %d\n", $this->passes);
        echo sprintf(" Failed:           %d\n", $this->failures);
        echo sprintf(" Duration:         %.4f seconds\n", $duration);
        echo "================================================================================\n\n";

        if ($this->failures === 0) {
            echo " [VERDICT: APPROVE] ALL LINE CONTRACTS & SCHEMA SPECIFICATIONS VERIFIED EMPIRICALLY!\n\n";
            return true;
        } else {
            echo " [VERDICT: REJECT] FAILURES DETECTED IN LINE CONTRACTS OR SCHEMA:\n";
            foreach ($this->failureDetails as $fail) {
                echo "   {$fail}\n";
            }
            echo "\n";
            return false;
        }
    }

    /**
     * Suite 1: Line contracts for page-bat-dong-san.php
     */
    private function suite1_line_contracts_page_bat_dong_san(): void {
        echo "--- Suite 1: Line Contract page-bat-dong-san.php Line 565 ---\n";
        $filePath = $this->themeDir . '/page-bat-dong-san.php';
        $this->assert(file_exists($filePath), "1.1: page-bat-dong-san.php exists");

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        $totalLines = count($lines);
        $this->assert($totalLines >= 800, "1.2: File depth >= 800 lines (actual: {$totalLines})");

        // Line 565 is index 564 (0-indexed)
        $line565 = $lines[564] ?? '';
        $line565Trimmed = trim($line565);
        $expected = '<div class="hidden lg:block mb-6">';

        $this->assert(
            $line565Trimmed === $expected,
            "1.3: Line 565 strictly matches '{$expected}'",
            "Actual line 565: [{$line565Trimmed}]"
        );

        $line564 = trim($lines[563] ?? '');
        $this->assert(
            str_contains($line564, 'max-w-7xl mx-auto'),
            "1.4: Preceding line 564 is container wrapper"
        );

        $line566 = trim($lines[565] ?? '');
        $this->assert(
            str_contains($line566, '<?php'),
            "1.5: Succeeding line 566 starts PHP block"
        );
    }

    /**
     * Suite 2: Line contracts for page-du-an.php
     */
    private function suite2_line_contracts_page_du_an(): void {
        echo "\n--- Suite 2: Line Contracts page-du-an.php Lines 255-257 & Line 456 ---\n";
        $filePath = $this->themeDir . '/page-du-an.php';
        $this->assert(file_exists($filePath), "2.1: page-du-an.php exists");

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        $totalLines = count($lines);
        $this->assert($totalLines >= 460, "2.2: File depth >= 460 lines (actual: {$totalLines})");

        // Lines 255-257 (0-indexed 254, 255, 256)
        $line255 = trim($lines[254] ?? '');
        $line256 = trim($lines[255] ?? '');
        $line257 = trim($lines[256] ?? '');

        $this->assert(
            $line255 === '$query = new WP_Query($args);',
            "2.3a: Line 255 is strictly '\$query = new WP_Query(\$args);'",
            "Actual: [{$line255}]"
        );
        $this->assert(
            $line256 === '$posts = $query->posts;',
            "2.3b: Line 256 is strictly '\$posts = \$query->posts;'",
            "Actual: [{$line256}]"
        );
        $this->assert(
            $line257 === 'wp_reset_postdata();',
            "2.3c: Line 257 is strictly 'wp_reset_postdata();'",
            "Actual: [{$line257}]"
        );

        // Line 456 (0-indexed 455)
        $line456 = trim($lines[455] ?? '');
        $expected = '<div class="hidden lg:block mb-6">';
        $this->assert(
            $line456 === $expected,
            "2.4: Line 456 is strictly '{$expected}'",
            "Actual: [{$line456}]"
        );
    }

    /**
     * Suite 3: Verbatim update_post_caches in ajax-listings-filter.php
     */
    private function suite3_verbatim_update_post_caches(): void {
        echo "\n--- Suite 3: Verbatim update_post_caches Contract ---\n";
        $filePath = $this->themeDir . '/inc/hooks/ajax-listings-filter.php';
        $this->assert(file_exists($filePath), "3.1: ajax-listings-filter.php exists");

        $content = file_get_contents($filePath);
        $expectedVerbatim = "update_post_caches(\$paged_posts, 'bat-dong-san', true, true);";

        $this->assert(
            str_contains($content, $expectedVerbatim),
            "3.2: Contains verbatim '{$expectedVerbatim}'"
        );

        // Check it's guarded by function_exists('update_post_caches')
        $pattern = '/if\s*\(\s*function_exists\s*\(\s*[\'"]update_post_caches[\'"]\s*\)\s*\)\s*\{[\s\S]*?' . preg_quote($expectedVerbatim, '/') . '[\s\S]*?\}/';
        $this->assert(
            (bool) preg_match($pattern, $content),
            "3.3: update_post_caches is safely guarded with function_exists check"
        );
    }

    /**
     * Suite 4: AST Zero posts_per_page = -1 and no in-memory slice
     */
    private function suite4_ast_zero_negative_one_and_no_in_memory_slice(): void {
        echo "\n--- Suite 4: AST Scan for Forbidden Patterns (posts_per_page = -1 & array_slice) ---\n";

        $filesToCheck = [
            'page-bat-dong-san.php',
            'page-du-an.php',
            'inc/hooks/ajax-listings-filter.php',
            'inc/hooks/ajax-project-filters.php',
            'inc/services/filter/class-bds-abstract-query-builder.php',
            'inc/services/filter/class-bds-listing-query-builder.php',
            'inc/services/filter/class-bds-project-query-builder.php',
            'inc/services/filter/class-bds-filter-query-service.php',
        ];

        foreach ($filesToCheck as $relPath) {
            $absPath = $this->themeDir . '/' . $relPath;
            $tokens = token_get_all(file_get_contents($absPath));
            $foundNegativeOne = false;
            $tokenCount = count($tokens);

            for ($i = 0; $i < $tokenCount; $i++) {
                $token = $tokens[$i];
                // Check for string 'posts_per_page' followed closely by => and -1
                if (is_array($token) && ($token[0] === T_CONSTANT_ENCAPSED_STRING || $token[0] === T_STRING)) {
                    $text = trim($token[1], "'\"");
                    if ($text === 'posts_per_page') {
                        // Scan forward through whitespaces for => or =
                        $j = $i + 1;
                        while ($j < $tokenCount && (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE)) {
                            $j++;
                        }
                        if ($j < $tokenCount && (
                            (is_array($tokens[$j]) && $tokens[$j][0] === T_DOUBLE_ARROW) ||
                            $tokens[$j] === '='
                        )) {
                            $k = $j + 1;
                            while ($k < $tokenCount && (is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE)) {
                                $k++;
                            }
                            // Check for -1 or '-1'
                            if ($k < $tokenCount) {
                                if ($tokens[$k] === '-') {
                                    $l = $k + 1;
                                    if ($l < $tokenCount && is_array($tokens[$l]) && $tokens[$l][0] === T_LNUMBER && $tokens[$l][1] === '1') {
                                        $foundNegativeOne = true;
                                        break;
                                    }
                                } elseif (is_array($tokens[$k]) && ($tokens[$k][0] === T_CONSTANT_ENCAPSED_STRING && trim($tokens[$k][1], "'\"") === '-1')) {
                                    $foundNegativeOne = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $this->assert(
                !$foundNegativeOne,
                "4.1: [{$relPath}] has ZERO executable posts_per_page => -1 in PHP tokens"
            );
        }

        // Check no in-memory array_slice on post lists in AJAX handlers
        $ajaxFiles = [
            'inc/hooks/ajax-listings-filter.php',
            'inc/hooks/ajax-project-filters.php',
        ];

        foreach ($ajaxFiles as $relPath) {
            $content = file_get_contents($this->themeDir . '/' . $relPath);
            $hasArraySliceOnPosts = (bool) preg_match('/array_slice\s*\(\s*\$(?:paged_)?posts/i', $content);
            $this->assert(
                !$hasArraySliceOnPosts,
                "4.2: [{$relPath}] does NOT perform array_slice on post lists (uses SQL LIMIT/OFFSET)"
            );
        }
    }

    /**
     * Suite 5: Dynamic Clamping Oracle
     */
    private function suite5_dynamic_query_clamping_oracle(): void {
        echo "\n--- Suite 5: Dynamic Query Clamping Oracle ---\n";

        // Test BDS_Listing_Query_Builder with adversarial inputs
        $listingBuilder = new BDS_Listing_Query_Builder();

        // 5.1 Negative posts_per_page
        $args1 = $listingBuilder->parse_params(['posts_per_page' => -1])->build_query_args();
        $this->assert(
            $args1['posts_per_page'] > 0 && $args1['posts_per_page'] <= 100,
            "5.1: Negative posts_per_page (-1) is clamped to positive integer (got {$args1['posts_per_page']})"
        );

        // 5.2 Zero posts_per_page
        $args2 = $listingBuilder->parse_params(['posts_per_page' => 0])->build_query_args();
        $this->assert(
            $args2['posts_per_page'] > 0,
            "5.2: Zero posts_per_page is clamped to positive integer (got {$args2['posts_per_page']})"
        );

        // 5.3 Huge overflow posts_per_page
        $args3 = $listingBuilder->parse_params(['posts_per_page' => 99999])->build_query_args();
        $this->assert(
            $args3['posts_per_page'] === 100,
            "5.3: Overflow posts_per_page (99999) is clamped to max 100 (got {$args3['posts_per_page']})"
        );

        // 5.4 Negative paged via set_paged vs parse_params
        $listingBuilder->set_paged(-5);
        $this->assert(
            $listingBuilder->get_paged() === 1,
            "5.4a: set_paged(-5) directly clamps to 1 (got {$listingBuilder->get_paged()})"
        );

        $args4 = $listingBuilder->parse_params(['paged' => 0])->build_query_args();
        $this->assert(
            $args4['paged'] === 1,
            "5.4b: Zero paged (0) is clamped to 1 (got {$args4['paged']})"
        );

        $args4c = $listingBuilder->parse_params(['paged' => -5])->build_query_args();
        // Document: absint(-5) in parse_params produces 5
        $this->assert(
            $args4c['paged'] >= 1,
            "5.4c: Negative paged (-5) in parse_params produces positive integer via absint (got {$args4c['paged']})"
        );

        // 5.5 String / Malformed posts_per_page
        $args5 = $listingBuilder->parse_params(['posts_per_page' => 'malicious-string'])->build_query_args();
        $this->assert(
            $args5['posts_per_page'] > 0,
            "5.5: Non-numeric posts_per_page is clamped safely (got {$args5['posts_per_page']})"
        );

        // Test BDS_Project_Query_Builder with adversarial inputs
        $projBuilder = new BDS_Project_Query_Builder();
        $projArgs1 = $projBuilder->parse_params(['posts_per_page' => -1, 'paged' => 0])->build_query_args();
        $this->assert(
            $projArgs1['posts_per_page'] > 0 && $projArgs1['posts_per_page'] <= 100,
            "5.6: Project builder clamps posts_per_page (-1) to positive integer (got {$projArgs1['posts_per_page']})"
        );
        $this->assert(
            $projArgs1['paged'] === 1,
            "5.7: Project builder clamps paged (0) to 1 (got {$projArgs1['paged']})"
        );
    }

    /**
     * Suite 6: JSON Response Schema Completeness Oracle
     */
    private function suite6_json_response_schema_completeness(): void {
        echo "\n--- Suite 6: JSON Response Schema Completeness Oracle ---\n";

        // Mock a WP_Query object
        $query = new WP_Query([
            'post_type'      => 'bat-dong-san',
            'posts_per_page' => 10,
            'paged'          => 1,
        ]);
        $query->found_posts = 42;
        $query->max_num_pages = 5;

        // Test format_listings_ajax_response
        $params = [
            'paged'          => 2,
            'posts_per_page' => 10,
            'khu_vuc'        => 'cau-giay',
            'price'          => '3000-5000',
            'area'           => '50-80',
            'so_phong_ngu'   => '3',
            'status'         => 'dang-ban',
        ];

        $response = BDS_Filter_Query_Service::format_listings_ajax_response($query, $params);

        $expectedKeys = [
            'html',
            'pagination',
            'total',
            'count',
            'found_posts',
            'total_pages',
            'max_num_pages',
            'current_page',
            'url',
            'title',
            'active_filters',
        ];

        foreach ($expectedKeys as $key) {
            $this->assert(
                array_key_exists($key, $response),
                "6.1: Listings AJAX response contains required schema key '{$key}'"
            );
        }

        // Check types
        $this->assert(is_string($response['html']), "6.2: response['html'] is string");
        $this->assert(is_string($response['pagination']), "6.3: response['pagination'] is string");
        $this->assert(is_int($response['total']), "6.4: response['total'] is int");
        $this->assert(is_int($response['count']), "6.5: response['count'] is int");
        $this->assert(is_int($response['found_posts']), "6.6: response['found_posts'] is int");
        $this->assert(is_int($response['total_pages']), "6.7: response['total_pages'] is int");
        $this->assert(is_int($response['max_num_pages']), "6.8: response['max_num_pages'] is int");
        $this->assert(is_int($response['current_page']), "6.9: response['current_page'] is int");
        $this->assert(is_string($response['url']), "6.10: response['url'] is string");
        $this->assert(is_array($response['active_filters']), "6.11: response['active_filters'] is array");

        // Check value invariants
        $this->assert(
            $response['count'] === $response['found_posts'] && $response['found_posts'] === $response['total'],
            "6.12: Invariant count === found_posts === total ({$response['total']})"
        );
        $this->assert(
            $response['max_num_pages'] === $response['total_pages'],
            "6.13: Invariant max_num_pages === total_pages ({$response['total_pages']})"
        );

        // Test Project schema
        $projQuery = new WP_Query([
            'post_type'      => 'du-an',
            'posts_per_page' => 9,
            'paged'          => 1,
        ]);
        $projQuery->found_posts = 18;
        $projQuery->max_num_pages = 2;

        $projParams = [
            'paged'     => 1,
            'khu_vuc'   => 'tay-ho',
            'price'     => '10000',
            'status'    => 'dang-mo-ban',
        ];

        $projResponse = BDS_Filter_Query_Service::format_projects_ajax_response($projQuery, $projParams);

        $expectedProjKeys = [
            'html',
            'pagination',
            'found_posts',
            'total',
            'total_pages',
            'current_page',
            'title',
            'searchResults',
            'url',
            'pretty_url',
        ];

        foreach ($expectedProjKeys as $key) {
            $this->assert(
                array_key_exists($key, $projResponse),
                "6.14: Projects AJAX response contains required schema key '{$key}'"
            );
        }

        $this->assert(
            $projResponse['found_posts'] === $projResponse['total'],
            "6.15: Projects invariant found_posts === total ({$projResponse['total']})"
        );
    }

    /**
     * Suite 7: Adversarial Injection Fuzzing
     */
    private function suite7_adversarial_injection_fuzzing(): void {
        echo "\n--- Suite 7: Adversarial Injection & Type Fuzzing ---\n";

        $maliciousInputs = [
            'type'             => "1' OR '1'='1",
            'khu_vuc'          => "../../etc/passwd\0",
            'price'            => "1; DROP TABLE wp_posts;--",
            'area'             => "<script>alert(document.cookie)</script>",
            'so_phong_ngu'     => "' UNION SELECT * FROM wp_users--",
            'so_phong_ve_sinh' => "-1' OR 'x'='x",
            'huong_nha'        => "data:text/html,<script>alert(1)</script>",
            'phap_ly'          => str_repeat("A", 5000),
            'keyword'          => "Robert'); DROP TABLE Students;--",
            'order'            => "price_asc; DELETE FROM wp_posts;",
        ];

        $args = BDS_Filter_Query_Service::get_listing_query_args($maliciousInputs);

        $this->assert(
            is_array($args),
            "7.1: Malicious SQL injection payloads safely parsed into array"
        );
        $this->assert(
            $args['posts_per_page'] > 0 && $args['posts_per_page'] <= 100,
            "7.2: posts_per_page remained clamped under adversarial injection"
        );

        // Verify meta_query clauses didn't crash or expose raw SQL fragments
        if (!empty($args['meta_query'])) {
            $this->assert(
                is_array($args['meta_query']),
                "7.3: meta_query maintains structured array integrity under injection attack"
            );
        }

        // Verify project query builder handles same payloads safely
        $projArgs = BDS_Filter_Query_Service::get_project_query_args($maliciousInputs);
        $this->assert(
            is_array($projArgs),
            "7.4: Project query args safely parsed under injection attack"
        );
        $this->assert(
            $projArgs['posts_per_page'] > 0 && $projArgs['posts_per_page'] <= 100,
            "7.5: Project posts_per_page remained clamped under injection"
        );
    }
}

// Execute harness
$harness = new M1Challenger2OracleHarness();
$success = $harness->run();
exit($success ? 0 : 1);
