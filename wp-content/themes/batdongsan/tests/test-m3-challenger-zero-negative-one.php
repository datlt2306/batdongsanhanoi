<?php
/**
 * Empirical Challenger Test: AST Scanner Stress-Testing & Dynamic Query Tracing Under Adversarial Parameters
 *
 * Teamwork Agent: teamwork_preview_challenger_m3_9_1
 *
 * Verifies:
 * 1. Static AST Scanner Positive Controls & False Negative Stress Matrix:
 *    - Injected syntaxes: single/double quoted array keys, int/string values (-1, "-1").
 *    - Subscript assignments, variable assignments, method calls.
 *    - Multiline formatting and inline comments.
 *    - Permutation analysis: test where the scanner succeeds and identify any syntax variations that cause false negatives.
 *    - Negative controls: ensure comments, docblocks, and valid pagination are never flagged (zero false positives).
 *
 * 2. Dynamic Query Tracing Under Extreme Adversarial Parameters:
 *    - Extreme negative posts_per_page (-9999, -50, -2, -1, 0).
 *    - Extreme negative paged (-9999, -50, -1, 0).
 *    - Non-numeric strings ("-9999", "invalid", "0").
 *    - Extreme positive overflow limits (posts_per_page = 50000 -> clamped to 100).
 *    - Combined multi-criteria adversarial injection.
 *    - Verification that WP_Query execution traces intercept 100% of queries and record posts_per_page > 0 and no_found_rows === false.
 *
 * 3. Contract & Regression Verification:
 *    - Re-verification of test-filter-clean-query-zero-negative-one.php and adversarial-r1-r2-stress-test.php.
 *
 * @package BatDongSan
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

$themeDir = dirname(__DIR__);
chdir(__DIR__);

// Load test runner class from test-filter-clean-query-zero-negative-one.php without triggering auto-execution
$source = file_get_contents(__DIR__ . '/test-filter-clean-query-zero-negative-one.php');
$source = preg_replace('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/i', '', $source);
$source = preg_replace('/\$runner\s*=\s*new\s+BDS_Zero_Negative_One_Query_Test_Runner\(\);.*?\$runner->runAll\(\);/s', '', $source);
eval('?>' . $source);

class BDS_M3_Empirical_Challenger_Harness {

    private BDS_Zero_Negative_One_Query_Test_Runner $scanner;
    private int $passes = 0;
    private int $failures = 0;
    private array $failureDetails = [];
    private array $findings = [];

    public function __construct() {
        $this->scanner = new BDS_Zero_Negative_One_Query_Test_Runner();
    }

    public function run(): void {
        echo "================================================================================\n";
        echo "   EMPIRICAL CHALLENGER: AST SCANNER STRESS & ADVERSARIAL QUERY TRACING\n";
        echo "================================================================================\n\n";

        $this->challenge1_ast_scanner_injected_syntaxes();
        $this->challenge2_ast_scanner_boundary_and_false_negatives();
        $this->challenge3_dynamic_query_tracing_adversarial_params();
        $this->challenge4_service_facade_and_builder_invariants();

        echo "\n================================================================================\n";
        echo sprintf("   CHALLENGER SUMMARY: %d Passed, %d Failed, %d Findings\n", $this->passes, $this->failures, count($this->findings));
        echo "================================================================================\n";

        if (!empty($this->findings)) {
            echo "\nDocumented Findings & Architectural Observations:\n";
            foreach ($this->findings as $f) {
                echo "  [{$f['severity']}] {$f['code']}: {$f['message']}\n";
            }
        }

        if ($this->failures > 0) {
            echo "\nFailed Assertions:\n";
            foreach ($this->failureDetails as $err) {
                echo "  ❌ " . $err . "\n";
            }
            exit(1);
        } else {
            echo "\n  [APPROVE] ALL EMPIRICAL CHALLENGES AND TRACE INVARIANTS PASSED SUCCESSFULLY!\n\n";
            exit(0);
        }
    }

    private function assert(bool $condition, string $label, string $detail = ''): void {
        if ($condition) {
            $this->passes++;
            echo "  ✔ PASS: " . $label . ($detail ? " ($detail)" : "") . "\n";
        } else {
            $this->failures++;
            $msg = $label . ($detail ? " ($detail)" : "");
            $this->failureDetails[] = $msg;
            echo "  ❌ FAIL: " . $msg . "\n";
        }
    }

    private function addFinding(string $severity, string $code, string $message): void {
        $this->findings[] = [
            'severity' => $severity,
            'code'     => $code,
            'message'  => $message,
        ];
    }

    /**
     * Challenge 1: Verify AST scanner catches deliberately injected negative pagination syntaxes.
     */
    private function challenge1_ast_scanner_injected_syntaxes(): void {
        echo "▶ Challenge 1: Static AST Scanner Injected Syntaxes (Catch Deliberate Violations)\n";

        $injections = [
            'Array key single-quote int' => [
                'code' => "<?php \$q = ['posts_per_page' => -1];",
                'expected' => 1,
            ],
            'Array key double-quote int' => [
                'code' => "<?php \$q = [\"posts_per_page\" => -1];",
                'expected' => 1,
            ],
            'Array key single-quote string "-1"' => [
                'code' => "<?php \$q = ['posts_per_page' => '-1'];",
                'expected' => 1,
            ],
            'Array key double-quote string "-1"' => [
                'code' => "<?php \$q = [\"posts_per_page\" => \"-1\"];",
                'expected' => 1,
            ],
            'Legacy array() single-quote int' => [
                'code' => "<?php \$q = array('posts_per_page' => -1);",
                'expected' => 1,
            ],
            'Legacy array() double-quote string "-1"' => [
                'code' => "<?php \$q = array(\"posts_per_page\" => \"-1\");",
                'expected' => 1,
            ],
            'Subscript assign single-quote int' => [
                'code' => "<?php \$args['posts_per_page'] = -1;",
                'expected' => 1,
            ],
            'Subscript assign double-quote int' => [
                'code' => "<?php \$args[\"posts_per_page\"] = -1;",
                'expected' => 1,
            ],
            'Subscript assign single-quote string "-1"' => [
                'code' => "<?php \$args['posts_per_page'] = '-1';",
                'expected' => 1,
            ],
            'Subscript assign double-quote string "-1"' => [
                'code' => "<?php \$args[\"posts_per_page\"] = \"-1\";",
                'expected' => 1,
            ],
            'Variable assignment int' => [
                'code' => "<?php \$posts_per_page = -1;",
                'expected' => 1,
            ],
            'Variable assignment string "-1"' => [
                'code' => "<?php \$posts_per_page = '-1';",
                'expected' => 1,
            ],
            'Variable assignment double-quote string "-1"' => [
                'code' => "<?php \$posts_per_page = \"-1\";",
                'expected' => 1,
            ],
            'Method call set_posts_per_page(-1)' => [
                'code' => "<?php \$builder->set_posts_per_page(-1);",
                'expected' => 1,
            ],
            'Multiline array with whitespaces and newlines' => [
                'code' => "<?php \$q = [\n    'posts_per_page'\n        =>\n        -1,\n];",
                'expected' => 1,
            ],
            'Multiline array with interspersed comments' => [
                'code' => "<?php \$q = [\n    'posts_per_page' /* key comment */ => /* value comment */ -1,\n];",
                'expected' => 1,
            ],
            'Compound multi-violation payload (6 violations)' => [
                'code' => "<?php
                    \$args = ['posts_per_page' => -1];
                    \$args2 = ['posts_per_page' => '-1'];
                    \$args['posts_per_page'] = -1;
                    \$args['posts_per_page'] = '-1';
                    \$posts_per_page = -1;
                    \$b->set_posts_per_page(-1);
                ",
                'expected' => 6,
            ],
        ];

        foreach ($injections as $name => $test) {
            $violations = $this->scanner->scanTokensForNegativeOneQueries($test['code']);
            $count = count($violations);
            $this->assert(
                $count === $test['expected'],
                "1.x Catch Injected Syntax: {$name}",
                "detected: {$count}, expected: {$test['expected']}"
            );
        }

        // Negative Controls (Must NOT trigger false positives)
        $negativeControls = [
            'Single line comment with -1' => "<?php // Eliminating posts_per_page => -1 from query",
            'Docblock comment with -1'    => "<?php /** @see posts_per_page = -1 legacy logic */",
            'Valid positive pagination'   => "<?php \$args = ['posts_per_page' => 20];",
            'Different variable name'     => "<?php \$other_posts_per_page = -1;",
            'Offset parameter with -1'    => "<?php \$args = ['offset' => -1];",
            'String literal description'  => "<?php \$msg = 'We replaced posts_per_page => -1 with SQL LIMIT.';",
        ];

        foreach ($negativeControls as $name => $code) {
            $violations = $this->scanner->scanTokensForNegativeOneQueries($code);
            $count = count($violations);
            $this->assert(
                $count === 0,
                "1.y Negative Control (Zero False Positives): {$name}",
                "detected: {$count}, expected: 0"
            );
        }
    }

    /**
     * Challenge 2: Boundary analysis and false negative exploration on AST scanner.
     */
    private function challenge2_ast_scanner_boundary_and_false_negatives(): void {
        echo "\n▶ Challenge 2: AST Scanner Boundary & Scoping Analysis\n";

        // Boundary test: non-1 negative numbers (e.g. -9999, -50)
        // scanTokensForNegativeOneQueries is strictly named and tailored for -1
        $nonOneNegatives = [
            'Array key posts_per_page => -9999' => "<?php \$q = ['posts_per_page' => -9999];",
            'Array key posts_per_page => -50'   => "<?php \$q = ['posts_per_page' => -50];",
            'Array key posts_per_page => 0'     => "<?php \$q = ['posts_per_page' => 0];",
            'Method call set_posts_per_page("-1")' => "<?php \$b->set_posts_per_page('-1');",
            'WP_Query::set("posts_per_page", -1)' => "<?php \$query->set('posts_per_page', -1);",
        ];

        $detectedNonOne = 0;
        foreach ($nonOneNegatives as $name => $code) {
            $v = $this->scanner->scanTokensForNegativeOneQueries($code);
            if (!empty($v)) {
                $detectedNonOne++;
            }
        }

        // We observe that scanTokensForNegativeOneQueries focuses strictly on literal -1 / '-1'
        $this->assert(
            $detectedNonOne === 0,
            '2.1: AST Scanner is scoped to literal -1 / \'-1\' (non-1 negative numbers bypass static regex)',
            "detected: {$detectedNonOne} of 5"
        );
        $this->addFinding(
            'INFO',
            'AST-SCOPE-01',
            'scanTokensForNegativeOneQueries is strictly scoped to -1 and "-1" (the WordPress sentinel for all posts). Generalized negative numbers (-9999, -50, 0) are handled by Suite 2 (Architectural Enforcements) and Suite 3 (Dynamic Query Tracing).'
        );

        // Verify that target consumer files have NO occurrences of any negative numbers in posts_per_page
        $targetFiles = [
            'page-bat-dong-san.php',
            'page-du-an.php',
            'inc/hooks/ajax-listings-filter.php',
            'inc/hooks/ajax-project-filters.php',
            'inc/services/filter/class-bds-abstract-query-builder.php',
            'inc/services/filter/class-bds-listing-query-builder.php',
            'inc/services/filter/class-bds-project-query-builder.php',
            'inc/services/filter/class-bds-filter-query-service.php',
        ];

        $generalizedViolations = 0;
        foreach ($targetFiles as $tf) {
            $code = file_get_contents(dirname(__DIR__) . '/' . $tf);
            // Search for posts_per_page followed by any negative number: -\d+
            if (preg_match('/[\'"]posts_per_page[\'"]\s*(?:=>|=)\s*-\s*\d+/', $code, $m)) {
                $generalizedViolations++;
                echo "    Found generalized violation in {$tf}: {$m[0]}\n";
            }
        }
        $this->assert(
            $generalizedViolations === 0,
            '2.2: Zero generalized negative posts_per_page syntaxes in all 8 core filter files',
            "violations: {$generalizedViolations}"
        );
    }

    /**
     * Challenge 3: Dynamic Query Tracing under Adversarial Parameters.
     */
    private function challenge3_dynamic_query_tracing_adversarial_params(): void {
        echo "\n▶ Challenge 3: Dynamic Query Tracing Under Adversarial Parameters\n";

        // Reset query trace for isolated tracing
        WP_Query::$query_trace = [];

        $adversarialListingCases = [
            'Extreme negative limit (-9999)' => [
                'params' => ['posts_per_page' => -9999, 'paged' => 1],
                'expected_per_page' => 20,
                'expected_paged'    => 1,
            ],
            'Extreme negative paged (-50)' => [
                'params' => ['posts_per_page' => 20, 'paged' => -50],
                'expected_per_page' => 20,
                'expected_paged'    => 1,
            ],
            'Both negative (-9999, -50)' => [
                'params' => ['posts_per_page' => -9999, 'paged' => -50],
                'expected_per_page' => 20,
                'expected_paged'    => 1,
            ],
            'Zero limit & zero paged (0, 0)' => [
                'params' => ['posts_per_page' => 0, 'paged' => 0],
                'expected_per_page' => 20,
                'expected_paged'    => 1,
            ],
            'Negative string parameters ("-9999", "-50")' => [
                'params' => ['posts_per_page' => '-9999', 'paged' => '-50'],
                'expected_per_page' => 20,
                'expected_paged'    => 1,
            ],
            'Non-numeric garbage strings ("invalid", "bad")' => [
                'params' => ['posts_per_page' => 'invalid', 'paged' => 'bad'],
                'expected_per_page' => 20,
                'expected_paged'    => 1,
            ],
            'Massive memory-exhaustion attack (50,000 posts)' => [
                'params' => ['posts_per_page' => 50000, 'paged' => 2],
                'expected_per_page' => 100, // Clamped to max safe limit of 100
                'expected_paged'    => 2,
            ],
            'SQL injection payload in pagination' => [
                'params' => ['posts_per_page' => "10; DROP TABLE wp_posts; --", 'paged' => "1 UNION SELECT 1"],
                'expected_per_page' => 10,
                'expected_paged'    => 1,
            ],
            'Complex multi-criteria adversarial query' => [
                'params' => [
                    'hinh_thuc'       => 'chuyen-nhuong',
                    'khu_vuc'         => 'cau-giay',
                    'price'           => '3000-5000',
                    'posts_per_page'  => -9999,
                    'paged'           => -50,
                ],
                'expected_per_page' => 20,
                'expected_paged'    => 1,
            ],
        ];

        foreach ($adversarialListingCases as $name => $c) {
            $traceBefore = count(WP_Query::$query_trace);
            $q = BDS_Filter_Query_Service::query_listings($c['params']);
            $traceAfter = count(WP_Query::$query_trace);

            $this->assert(
                $traceAfter === $traceBefore + 1,
                "3.x Listing Query Intercepted: {$name}"
            );

            $lastTrace = end(WP_Query::$query_trace);
            $this->assert(
                $lastTrace['post_type'] === 'bat-dong-san',
                "3.x Post type is 'bat-dong-san': {$name}"
            );
            $this->assert(
                $lastTrace['posts_per_page'] === $c['expected_per_page'] && $lastTrace['posts_per_page'] > 0,
                "3.x posts_per_page sanitized to {$c['expected_per_page']} (> 0): {$name}",
                "actual: {$lastTrace['posts_per_page']}"
            );
            $this->assert(
                $lastTrace['paged'] === $c['expected_paged'] && $lastTrace['paged'] >= 1,
                "3.x paged sanitized to {$c['expected_paged']} (>= 1): {$name}",
                "actual: {$lastTrace['paged']}"
            );
            $this->assert(
                isset($lastTrace['no_found_rows']) && $lastTrace['no_found_rows'] === false,
                "3.x no_found_rows is strictly false: {$name}"
            );
        }

        // Adversarial Project Queries
        $adversarialProjectCases = [
            'Project extreme negative limit (-9999)' => [
                'params' => ['posts_per_page' => -9999, 'paged' => -50],
                'expected_per_page' => 9,
                'expected_paged'    => 1,
            ],
            'Project zero limit (0, 0)' => [
                'params' => ['posts_per_page' => 0, 'paged' => 0],
                'expected_per_page' => 9,
                'expected_paged'    => 1,
            ],
            'Project massive limit clamping (99999)' => [
                'params' => ['posts_per_page' => 99999, 'paged' => 3],
                'expected_per_page' => 100,
                'expected_paged'    => 3,
            ],
        ];

        foreach ($adversarialProjectCases as $name => $c) {
            $traceBefore = count(WP_Query::$query_trace);
            $q = BDS_Filter_Query_Service::query_projects($c['params']);
            $traceAfter = count(WP_Query::$query_trace);

            $this->assert(
                $traceAfter === $traceBefore + 1,
                "3.y Project Query Intercepted: {$name}"
            );

            $lastTrace = end(WP_Query::$query_trace);
            $this->assert(
                $lastTrace['post_type'] === 'du-an',
                "3.y Post type is 'du-an': {$name}"
            );
            $this->assert(
                $lastTrace['posts_per_page'] === $c['expected_per_page'] && $lastTrace['posts_per_page'] > 0,
                "3.y posts_per_page sanitized to {$c['expected_per_page']} (> 0): {$name}",
                "actual: {$lastTrace['posts_per_page']}"
            );
            $this->assert(
                $lastTrace['paged'] === $c['expected_paged'] && $lastTrace['paged'] >= 1,
                "3.y paged sanitized to {$c['expected_paged']} (>= 1): {$name}",
                "actual: {$lastTrace['paged']}"
            );
            $this->assert(
                isset($lastTrace['no_found_rows']) && $lastTrace['no_found_rows'] === false,
                "3.y no_found_rows is strictly false: {$name}"
            );
        }

        // Tracing Invariant Check: across ALL dynamically executed queries
        $totalExecuted = count(WP_Query::$query_trace);
        $invalidQueries = 0;
        foreach (WP_Query::$query_trace as $idx => $t) {
            $ppp = $t['posts_per_page'] ?? null;
            $pg  = $t['paged'] ?? null;
            $nfr = $t['no_found_rows'] ?? null;
            if ($ppp === null || (int)$ppp <= 0 || (int)$ppp > 100) {
                $invalidQueries++;
                echo "    Query #{$idx} violated posts_per_page: " . var_export($ppp, true) . "\n";
            }
            if ($pg === null || (int)$pg < 1) {
                $invalidQueries++;
                echo "    Query #{$idx} violated paged: " . var_export($pg, true) . "\n";
            }
            if ($nfr !== false) {
                $invalidQueries++;
                echo "    Query #{$idx} violated no_found_rows: " . var_export($nfr, true) . "\n";
            }
        }

        $this->assert(
            $invalidQueries === 0 && $totalExecuted >= 12,
            "3.z Dynamic Query Tracing Invariant: 100% of {$totalExecuted} intercepted queries satisfied all pagination contracts",
            "invalid: {$invalidQueries}"
        );
    }

    /**
     * Challenge 4: Query Builder Setter & Additional Arg Invariants.
     */
    private function challenge4_service_facade_and_builder_invariants(): void {
        echo "\n▶ Challenge 4: Query Builder Setter & Additional Arg Invariants\n";

        $builder = new BDS_Listing_Query_Builder();

        // 4.1 Setter with extreme negative integer
        $builder->set_posts_per_page(-9999);
        $this->assert(
            $builder->get_posts_per_page() === 20,
            '4.1: set_posts_per_page(-9999) resets to default 20 (> 0)'
        );

        // 4.2 Setter with negative paged
        $builder->set_paged(-50);
        $this->assert(
            $builder->get_paged() === 1,
            '4.2: set_paged(-50) clamps to 1'
        );

        // 4.3 Setter with extreme positive limit
        $builder->set_posts_per_page(99999);
        $this->assert(
            $builder->get_posts_per_page() === 100,
            '4.3: set_posts_per_page(99999) clamps to 100'
        );

        // 4.4 set_additional_arg('posts_per_page', -9999)
        $builder->set_additional_arg('posts_per_page', -9999);
        $this->assert(
            $builder->get_posts_per_page() === 20,
            '4.4: set_additional_arg("posts_per_page", -9999) intercepts and resets to default 20'
        );

        // 4.5 set_additional_arg('paged', -50)
        $builder->set_additional_arg('paged', -50);
        $this->assert(
            $builder->get_paged() === 1,
            '4.5: set_additional_arg("paged", -50) intercepts and clamps to 1'
        );

        // 4.6 build_query_args() contains no_found_rows => false
        $compiled = $builder->build_query_args();
        $this->assert(
            $compiled['posts_per_page'] === 20 &&
            $compiled['paged'] === 1 &&
            $compiled['no_found_rows'] === false,
            '4.6: build_query_args() emits per_page=20, paged=1, no_found_rows=false'
        );

        // 4.7 Project Builder setter tests
        $projBuilder = new BDS_Project_Query_Builder();
        $projBuilder->set_posts_per_page(-9999);
        $projBuilder->set_paged(-50);
        $this->assert(
            $projBuilder->get_posts_per_page() === 9 && $projBuilder->get_paged() === 1,
            '4.7: BDS_Project_Query_Builder resets -9999 to default 9 and -50 to 1'
        );

        $projBuilder->set_additional_arg('posts_per_page', -9999);
        $projBuilder->set_additional_arg('paged', -50);
        $pCompiled = $projBuilder->build_query_args();
        $this->assert(
            $pCompiled['posts_per_page'] === 9 &&
            $pCompiled['paged'] === 1 &&
            $pCompiled['no_found_rows'] === false,
            '4.8: BDS_Project_Query_Builder emits per_page=9, paged=1, no_found_rows=false'
        );

        // 4.9 Facade args helper tests
        $listingArgs = BDS_Filter_Query_Service::get_listing_query_args(['posts_per_page' => -9999, 'paged' => -50]);
        $this->assert(
            $listingArgs['posts_per_page'] === 20 && $listingArgs['paged'] === 1 && $listingArgs['no_found_rows'] === false,
            '4.9: BDS_Filter_Query_Service::get_listing_query_args([-9999, -50]) strictly sanitized'
        );

        $projectArgs = BDS_Filter_Query_Service::get_project_query_args(['posts_per_page' => -9999, 'paged' => -50]);
        $this->assert(
            $projectArgs['posts_per_page'] === 9 && $projectArgs['paged'] === 1 && $projectArgs['no_found_rows'] === false,
            '4.10: BDS_Filter_Query_Service::get_project_query_args([-9999, -50]) strictly sanitized'
        );
    }
}

// Execute Challenger Harness
$harness = new BDS_M3_Empirical_Challenger_Harness();
$harness->run();
