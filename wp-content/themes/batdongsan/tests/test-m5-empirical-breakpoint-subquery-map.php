<?php

/**
 * Milestone 5 Challenger 1 - Breakpoint line 456, Subquery no_found_rows, Map Facade XSS Defense Stress-Test
 *
 * Scope:
 *  1. Breakpoint line 456 in page-du-an.php strict match <div class="hidden lg:block mb-6">.
 *  2. Query Contracts:
 *     - page-du-an.php:255 WP_Query in [250, 265].
 *     - single-bat-dong-san.php & single-du-an.php: all subqueries have 'no_found_rows' => true.
 *     - single-du-an.php: 0 occurrences of LIKE '%ID%' in relationship queries.
 *  3. Map Facade XSS Defenses Stress-Test:
 *     - Empty string, whitespace, javascript:, data:text/html, vbscript:, external domains, HTTP protocols.
 *     - Valid Google Maps embed URLs and HTML escaping of attributes.
 *  4. Legacy Red Tokens Elimination:
 *     - Zero occurrences of #b20000, #8b0000, #e31837 in M5 files.
 *
 * @package BatDongSan
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/inc/helpers/project.php';

use BDS\Tests\Framework\MockWordPress;

class M5Challenger1ContractsHarness {

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
        echo "   M5 CHALLENGER 1: BREAKPOINT 456, SUBQUERY, MAP FACADE XSS HARNESS           \n";
        echo "================================================================================\n\n";

        $startTime = microtime(true);

        $this->suite1_breakpoint_line_456();
        $this->suite2_query_contracts();
        $this->suite3_map_facade_xss_defense();
        $this->suite4_legacy_red_tokens();

        $duration = microtime(true) - $startTime;

        echo "\n================================================================================\n";
        echo "   M5 CHALLENGER 1 EXECUTION SUMMARY                                            \n";
        echo "================================================================================\n";
        echo sprintf(" Total Assertions: %d\n", $this->passes + $this->failures);
        echo sprintf(" Passed:           %d\n", $this->passes);
        echo sprintf(" Failed:           %d\n", $this->failures);
        echo sprintf(" Duration:         %.4f seconds\n", $duration);
        echo "================================================================================\n\n";

        if ($this->failures === 0) {
            echo " [VERDICT: APPROVE] ALL ARCHITECTURAL CONTRACTS VERIFIED EMPIRICALLY!\n\n";
            return true;
        } else {
            echo " [VERDICT: REJECT] FAILURES DETECTED IN ARCHITECTURAL CONTRACTS:\n";
            foreach ($this->failureDetails as $fail) {
                echo "   {$fail}\n";
            }
            echo "\n";
            return false;
        }
    }

    /**
     * Suite 1: Breakpoint Line 456 in page-du-an.php
     */
    private function suite1_breakpoint_line_456(): void {
        echo "--- Suite 1: Breakpoint line 456 in page-du-an.php ---\n";

        $pageDuAnPath = $this->themeDir . '/page-du-an.php';
        $this->assert(file_exists($pageDuAnPath), "1.1: page-du-an.php exists");

        $lines = file($pageDuAnPath, FILE_IGNORE_NEW_LINES);
        $totalLines = count($lines);
        $this->assert($totalLines >= 460, "1.2: page-du-an.php has at least 460 lines (actual: {$totalLines})");

        // Locate the line with <div class="hidden lg:block mb-6">
        $breakpointLineIdx = -1;
        $expectedBreakpointMarkup = '<div class="hidden lg:block mb-6">';
        foreach ($lines as $idx => $line) {
            if (trim($line) === $expectedBreakpointMarkup) {
                $breakpointLineIdx = $idx;
                break;
            }
        }

        $this->assert(
            $breakpointLineIdx >= 0,
            "1.3: page-du-an.php contains '{$expectedBreakpointMarkup}' (found at line " . ($breakpointLineIdx + 1) . ")"
        );

        if ($breakpointLineIdx >= 0) {
            $lineBreakpoint = $lines[$breakpointLineIdx];
            $this->assert(
                str_contains($lineBreakpoint, 'hidden lg:block'),
                "1.4: Breakpoint line contains responsive classes 'hidden lg:block'"
            );

            // Check context around breakpoint line
            $linePrev = trim($lines[$breakpointLineIdx - 1] ?? '');
            $this->assert(
                str_contains($linePrev, 'max-w-7xl mx-auto px-4'),
                "1.5: Preceding line is max-w-7xl container wrapper"
            );

            $lineNext = trim($lines[$breakpointLineIdx + 1] ?? '');
            $this->assert(
                str_contains($lineNext, '<?php'),
                "1.6: Succeeding line opens PHP block for horizontal filter bar"
            );
        }
    }

    /**
     * Suite 2: Query Contracts Verification
     */
    private function suite2_query_contracts(): void {
        echo "\n--- Suite 2: Query Contracts Verification ---\n";

        // 2.1 Check line 255 in page-du-an.php
        $pageDuAnPath = $this->themeDir . '/page-du-an.php';
        $lines = file($pageDuAnPath, FILE_IGNORE_NEW_LINES);

        $queryLineNum = -1;
        foreach ($lines as $idx => $line) {
            $lineNum = $idx + 1;
            if (str_contains($line, '$query = new WP_Query($args);')) {
                $queryLineNum = $lineNum;
                break;
            }
        }

        $this->assert(
            $queryLineNum >= 250 && $queryLineNum <= 265,
            "2.1a: \$query = new WP_Query(\$args); exists in range [250, 265] (found at line {$queryLineNum})"
        );

        $this->assert(
            $queryLineNum === 255,
            "2.1b: \$query = new WP_Query(\$args); is exactly at line 255"
        );

        // Check surrounding query integrity
        $line256 = trim($lines[255] ?? '');
        $this->assert(
            str_contains($line256, '$posts = $query->posts;'),
            "2.1c: Post extraction \$posts = \$query->posts follows query instantiation"
        );

        $line257 = trim($lines[256] ?? '');
        $this->assert(
            str_contains($line257, 'wp_reset_postdata();'),
            "2.1d: Post data reset wp_reset_postdata() immediately executed"
        );

        // 2.2 Subqueries in single-bat-dong-san.php: all must have 'no_found_rows' => true
        $singleBdsPath = $this->themeDir . '/single-bat-dong-san.php';
        $singleBdsContent = file_get_contents($singleBdsPath);

        preg_match_all('/(?:new\s+WP_Query\s*\(([^;]+)\)|([a-zA-Z0-9_\$]+)\s*=\s*\[[^;]+\];[\s\S]*?new\s+WP_Query\s*\(\s*\2\s*\))/i', $singleBdsContent, $matches, PREG_OFFSET_CAPTURE);

        // Alternatively check each subquery block directly
        // Sub-query 1: $related_projects (lines ~210-224)
        $hasRelatedProjectsNoFoundRows = (bool) preg_match('/\$related_projects\s*=\s*new\s+WP_Query\s*\(\s*\[[\s\S]*?\'no_found_rows\'\s*=>\s*true[\s\S]*?\]\s*\);/', $singleBdsContent);
        $this->assert(
            $hasRelatedProjectsNoFoundRows,
            "2.2a: single-bat-dong-san.php \$related_projects contains 'no_found_rows' => true"
        );

        // Sub-query 2: $featured_listings (lines ~228-244)
        $hasFeaturedListingsNoFoundRows = (bool) preg_match('/\$featured_listings\s*=\s*new\s+WP_Query\s*\(\s*\[[\s\S]*?\'no_found_rows\'\s*=>\s*true[\s\S]*?\]\s*\);/', $singleBdsContent);
        $this->assert(
            $hasFeaturedListingsNoFoundRows,
            "2.2b: single-bat-dong-san.php \$featured_listings contains 'no_found_rows' => true"
        );

        // Sub-query 3: $related_listings (lines ~247-272)
        $hasRelatedListingsNoFoundRows = (bool) preg_match('/\$related_args\s*=\s*\[[\s\S]*?\'no_found_rows\'\s*=>\s*true[\s\S]*?\];[\s\S]*?\$related_listings\s*=\s*new\s+WP_Query\(\$related_args\);/', $singleBdsContent);
        $this->assert(
            $hasRelatedListingsNoFoundRows,
            "2.2c: single-bat-dong-san.php \$related_listings args contain 'no_found_rows' => true"
        );

        // 2.3 Subqueries in single-du-an.php: all must have 'no_found_rows' => true
        $singleDuAnPath = $this->themeDir . '/single-du-an.php';
        $singleDuAnContent = file_get_contents($singleDuAnPath);

        $hasNewsQueryNoFoundRows = (bool) preg_match('/\$news_query\s*=\s*new\s+WP_Query\s*\(\s*\[[\s\S]*?\'no_found_rows\'\s*=>\s*true[\s\S]*?\]\s*\);/', $singleDuAnContent);
        $this->assert(
            $hasNewsQueryNoFoundRows,
            "2.3a: single-du-an.php \$news_query contains 'no_found_rows' => true"
        );

        // 2.4 Relationship query in single-du-an.php: ZERO occurrences of LIKE '%ID%'
        $hasLikeIdInSingleDuAn = (bool) preg_match('/LIKE\s*[\'"][^\'"]*%ID%[^\'"]*[\'"]/i', $singleDuAnContent);
        $this->assert(
            !$hasLikeIdInSingleDuAn,
            "2.4a: single-du-an.php has ZERO occurrences of LIKE '%ID%'"
        );

        $hasAnyLikeInSingleDuAn = (bool) preg_match('/[\'"]compare[\'"]\s*=>\s*[\'"]LIKE[\'"]/i', $singleDuAnContent);
        $this->assert(
            !$hasAnyLikeInSingleDuAn,
            "2.4b: single-du-an.php has ZERO occurrences of 'compare' => 'LIKE' in meta queries"
        );

        // Verify helper function batdongsan_get_project_listings implementation
        $this->assert(
            function_exists('batdongsan_get_project_listings'),
            "2.4c: batdongsan_get_project_listings() helper function exists"
        );

        // Empirical check: batdongsan_get_project_listings default args
        $queryResult = batdongsan_get_project_listings(123);
        $this->assert(
            ($queryResult->query_vars['no_found_rows'] ?? false) === true,
            "2.4d: batdongsan_get_project_listings() enforces 'no_found_rows' => true by default"
        );

        // Check helper source file for numeric type comparison
        $projectHelperPath = $this->themeDir . '/inc/helpers/project.php';
        $projectHelperContent = file_get_contents($projectHelperPath);
        $this->assert(
            str_contains($projectHelperContent, "'type'    => 'NUMERIC'") || str_contains($projectHelperContent, "'type' => 'NUMERIC'"),
            "2.4e: batdongsan_get_project_listings() uses NUMERIC casting for project IDs"
        );
        $this->assert(
            !str_contains($projectHelperContent, "LIKE '%"),
            "2.4f: inc/helpers/project.php has ZERO occurrences of LIKE '%'"
        );
    }

    /**
     * Suite 3: Map Facade XSS Defense Stress-Test
     */
    private function suite3_map_facade_xss_defense(): void {
        echo "\n--- Suite 3: Map Facade XSS Defense Stress-Test ---\n";

        $mapFacadePath = $this->themeDir . '/template-parts/map-facade.php';
        $this->assert(file_exists($mapFacadePath), "3.1: template-parts/map-facade.php exists");

        $renderMapFacade = function(array $args) use ($mapFacadePath): string {
            ob_start();
            include $mapFacadePath;
            return ob_get_clean();
        };

        // Adversarial Payload Test Cases
        $adversarialPayloads = [
            // 1. Empty string & whitespace
            ['payload' => '', 'name' => 'Empty string'],
            ['payload' => '   ', 'name' => 'Whitespace only (spaces)'],
            ['payload' => "\t\r\n   \t", 'name' => 'Whitespace only (tabs and newlines)'],

            // 2. javascript: protocol variants
            ['payload' => '<iframe src="javascript:alert(1)"></iframe>', 'name' => 'javascript:alert(1) standard'],
            ['payload' => '<iframe src="javascript:alert(\'XSS\')"></iframe>', 'name' => 'javascript:alert(XSS) single quotes'],
            ['payload' => '<iframe src="JAVASCRIPT:alert(1)"></iframe>', 'name' => 'JAVASCRIPT: uppercase'],
            ['payload' => '<iframe src="  javascript:alert(1)  "></iframe>', 'name' => 'javascript: with leading/trailing spaces'],
            ['payload' => '<iframe src="JaVaScRiPt:alert(document.domain)"></iframe>', 'name' => 'JaVaScRiPt: mixed case'],
            ['payload' => '<iframe src="javascript&#x3A;alert(1)"></iframe>', 'name' => 'javascript HTML entity encoded'],
            ['payload' => 'javascript:alert(1)', 'name' => 'Raw javascript: URI without iframe wrapper'],

            // 3. data: URI payloads
            ['payload' => '<iframe src="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=="></iframe>', 'name' => 'data:text/html base64 XSS'],
            ['payload' => '<iframe src="DATA:text/html,<script>alert(1)</script>"></iframe>', 'name' => 'DATA: uppercase raw html'],
            ['payload' => '<iframe src="data:image/svg+xml,<svg onload=alert(1)>"></iframe>', 'name' => 'data:image/svg+xml onload'],

            // 4. vbscript: payloads
            ['payload' => '<iframe src="vbscript:msgbox(1)"></iframe>', 'name' => 'vbscript:msgbox(1)'],
            ['payload' => '<iframe src="VBSCRIPT:alert(1)"></iframe>', 'name' => 'VBSCRIPT: uppercase'],

            // 5. Unknown external / malicious domains
            ['payload' => '<iframe src="https://malicious-site.com/map"></iframe>', 'name' => 'External domain https://malicious-site.com/map'],
            ['payload' => '<iframe src="https://evil-attacker.com/google.com/map"></iframe>', 'name' => 'External domain path confusion'],
            ['payload' => '<iframe src="https://google.com.malicious.com/map"></iframe>', 'name' => 'Subdomain spoofing google.com.malicious.com'],
            ['payload' => '<iframe src="https://phishing-google.com/map"></iframe>', 'name' => 'Typosquatting phishing-google.com'],
            ['payload' => '<iframe src="//malicious-site.com/map"></iframe>', 'name' => 'Protocol-relative external domain //malicious-site.com/map'],
            ['payload' => '<iframe src="http://www.google.com/maps/embed"></iframe>', 'name' => 'Insecure HTTP protocol on google.com'],

            // 6. Non-iframe HTML / XSS injection
            ['payload' => '<script>alert("XSS")</script>', 'name' => 'Raw <script> tag'],
            ['payload' => '<img src=x onerror=alert(1)>', 'name' => 'Raw <img> onerror tag'],
            ['payload' => '<svg onload=alert(1)>', 'name' => 'Raw <svg> onload tag'],
            ['payload' => '"><script>alert(1)</script>', 'name' => 'Quote breakout attempt'],
        ];

        foreach ($adversarialPayloads as $idx => $testCase) {
            $output = $renderMapFacade([
                'embed_code' => $testCase['payload'],
                'address'    => 'Hà Nội',
                'class'      => 'test-class',
            ]);

            $this->assert(
                empty(trim($output)),
                "3.2 [{$testCase['name']}]: Produced 0 output, blocked safely"
            );
            $this->assert(
                !str_contains($output, '<iframe'),
                "3.2 [{$testCase['name']}]: Output contains NO iframe tag"
            );
            $this->assert(
                !str_contains($output, 'alert('),
                "3.2 [{$testCase['name']}]: Output contains NO alert execution payload"
            );
        }

        // 3.3 Valid Google Maps URL test cases
        $validPayloads = [
            [
                'payload' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724!2d105!3d21!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjHCsDAw!5e0!3m2!1svi!2s!4v1!5m2!1svi!2s" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>',
                'name' => 'Official Google Maps Embed iframe with pb params',
            ],
            [
                'payload' => '<iframe src="//maps.google.com/maps?q=Ha+Noi&output=embed"></iframe>',
                'name' => 'Protocol-relative maps.google.com embed',
            ],
            [
                'payload' => '<iframe src="https://google.com/maps/embed?pb=test"></iframe>',
                'name' => 'Standard google.com maps embed',
            ],
        ];

        foreach ($validPayloads as $idx => $vCase) {
            $output = $renderMapFacade([
                'embed_code' => $vCase['payload'],
                'address'    => 'Số 1 Cầu Giấy, Hà Nội <script>alert("Address XSS")</script>',
                'class'      => 'custom-map " onclick="alert(\'Class breakout\')',
            ]);

            $this->assert(
                !empty($output),
                "3.3 [{$vCase['name']}]: Valid payload successfully rendered"
            );
            $this->assert(
                str_contains($output, 'class="map-facade'),
                "3.3 [{$vCase['name']}]: Contains map-facade root container class"
            );
            $this->assert(
                str_contains($output, 'data-map-src="https://'),
                "3.3 [{$vCase['name']}]: Enforces HTTPS in data-map-src attribute"
            );
            $this->assert(
                !str_contains($output, '<script>alert("Address XSS")</script>'),
                "3.3 [{$vCase['name']}]: Address field cleanly escaped against script execution"
            );
            $this->assert(
                !str_contains($output, 'onclick="alert(\'Class breakout\')'),
                "3.3 [{$vCase['name']}]: Class attribute cleanly escaped against quote breakout"
            );
            $this->assert(
                str_contains($output, 'map-facade__iframe-container'),
                "3.3 [{$vCase['name']}]: Contains map-facade__iframe-container for dynamic lazy loading"
            );
        }
    }

    /**
     * Suite 4: Legacy Red Tokens Elimination
     */
    private function suite4_legacy_red_tokens(): void {
        echo "\n--- Suite 4: Legacy Red Tokens Elimination ---\n";

        $m5Files = [
            'single-bat-dong-san.php',
            'page-du-an.php',
            'single-du-an.php',
            'template-parts/card-project-horizontal.php',
            'template-parts/map-facade.php',
            'template-parts/loan-calculator.php',
            'template-parts/advisor-card.php',
            'template-parts/newsletter-form.php',
        ];

        $redPattern = '/#(?:b20000|8b0000|e31837)/i';

        foreach ($m5Files as $relPath) {
            $fullPath = $this->themeDir . '/' . $relPath;
            $this->assert(file_exists($fullPath), "4.1: File exists: {$relPath}");

            $content = file_get_contents($fullPath);
            $hasRed = (bool) preg_match($redPattern, $content, $matches);
            $this->assert(
                !$hasRed,
                "4.2: {$relPath} contains ZERO legacy red tokens (#b20000, #8b0000, #e31837)",
                $hasRed ? "Found token: " . ($matches[0] ?? '') : ''
            );
        }
    }
}

// Execute harness
$harness = new M5Challenger1ContractsHarness();
$success = $harness->run();
exit($success ? 0 : 1);
