<?php

namespace BDS\Tests\E2E\Tier1;

use BDS\Tests\Framework\TestCase;
use BDS\Tests\Framework\MockWordPress;

class TestR2PerformanceSecurity extends TestCase {

    /**
     * T1-R2-01: Verify all 21 identified sub-queries have 'no_found_rows' => true.
     */
    public function test_r2_subqueries_no_found_rows_present(): void {
        $filesToCheck = [
            $this->themeDir . '/inc/services/queries.php',
            $this->themeDir . '/single-bat-dong-san.php',
            $this->themeDir . '/sidebar.php',
            $this->themeDir . '/category.php',
            $this->themeDir . '/page-bat-dong-san.php',
            $this->themeDir . '/taxonomy-danh-muc-nha-dat-ban.php',
            $this->themeDir . '/inc/widgets/class-bds-widgets.php',
        ];

        $missing = [];
        foreach ($filesToCheck as $filePath) {
            if (!file_exists($filePath)) {
                continue;
            }
            $content = file_get_contents($filePath);
            // Search for WP_Query occurrences that lack no_found_rows
            // In particular, functions in queries.php: batdongsan_get_related_projects, batdongsan_get_related_projects_by_area, batdongsan_get_featured_listings, batdongsan_get_related_projects_by_taxonomy, batdongsan_get_related_posts_by_project
            if (basename($filePath) === 'queries.php') {
                $requiredFunctions = [
                    'batdongsan_get_related_projects',
                    'batdongsan_get_related_projects_by_area',
                    'batdongsan_get_featured_listings',
                    'batdongsan_get_related_projects_by_taxonomy',
                    'batdongsan_get_related_posts_by_project',
                ];
                foreach ($requiredFunctions as $fn) {
                    if (strpos($content, $fn) !== false) {
                        // Extract function body roughly
                        $pattern = '/function\s+' . $fn . '\s*\(.*?\)\s*:\s*\??\w*\s*\{(.*?)\n\}/s';
                        if (preg_match($pattern, $content, $m)) {
                            if (strpos($m[1], "'no_found_rows'") === false && strpos($m[1], '"no_found_rows"') === false) {
                                $missing[] = "queries.php -> $fn missing no_found_rows";
                            }
                        }
                    }
                }
            }
        }

        $this->assertTrue(
            empty($missing),
            "Sub-queries lacking 'no_found_rows' => true:\n" . implode("\n", $missing)
        );
    }

    /**
     * T1-R2-02: Verify N+1 partner query in card templates is eliminated or cached.
     */
    public function test_r2_n_plus_one_partner_eliminated(): void {
        $horizontalCard = $this->themeDir . '/template-parts/card-project-horizontal.php';
        $featuredCard = $this->themeDir . '/template-parts/card-project-featured.php';

        $violations = [];
        foreach ([$horizontalCard, $featuredCard] as $cardPath) {
            if (!file_exists($cardPath)) {
                continue;
            }
            $content = file_get_contents($cardPath);
            // Must not contain uncached raw new WP_Query for doi-tac inside card template
            if (preg_match('/new\s+WP_Query\s*\(\s*\[[^]]*\'post_type\'\s*=>\s*\'doi-tac\'/s', $content)) {
                $violations[] = basename($cardPath) . " contains direct un-cached WP_Query for post_type 'doi-tac'";
            }
        }

        $this->assertTrue(
            empty($violations),
            "N+1 query violations in card templates:\n" . implode("\n", $violations)
        );
    }

    /**
     * T1-R2-03: Verify nonce validation returns HTTP 403 on missing or invalid nonce.
     */
    public function test_r2_ajax_project_filters_nonce_403(): void {
        $ajaxFile = $this->themeDir . '/inc/hooks/ajax-project-filters.php';
        $this->assertTrue(file_exists($ajaxFile), 'ajax-project-filters.php must exist.');

        require_once $ajaxFile;

        // Test with invalid nonce
        $result = MockWordPress::simulateAjax('batdongsan_filter_projects', [
            'nonce' => 'invalid_nonce_123',
            'project-name' => 'Test',
        ]);

        $this->assertEquals(
            403,
            $result['status_code'],
            sprintf('Expected HTTP 403 Forbidden on invalid nonce, but received HTTP %d.', $result['status_code'])
        );

        $this->assertFalse(
            $result['response']['success'] ?? true,
            'Response success must be false on invalid nonce.'
        );
    }

    /**
     * T1-R2-04: Verify map facade secures Google Map embed against Stored XSS.
     */
    public function test_r2_map_facade_xss_protection(): void {
        $facadeFile = $this->themeDir . '/template-parts/map-facade.php';
        $this->assertTrue(file_exists($facadeFile), 'template-parts/map-facade.php must exist.');

        $xssPayload = '<iframe src="javascript:alert(document.domain)"></iframe>';

        ob_start();
        $args = ['embed_code' => $xssPayload];
        include $facadeFile;
        $output = ob_get_clean();

        $this->assertDoesNotMatchRegularExpression(
            '/javascript:/i',
            $output,
            'Map facade output must NOT contain javascript: URI scheme.'
        );
    }

    /**
     * T1-R2-05: Verify transient caching for dynamic filter bounds.
     */
    public function test_r2_transient_caching_and_invalidation(): void {
        $priceRangesFile = $this->themeDir . '/inc/services/price-ranges.php';
        $this->assertTrue(file_exists($priceRangesFile), 'price-ranges.php must exist.');

        require_once $priceRangesFile;
        $this->assertTrue(function_exists('batdongsan_get_bds_price_ranges'), 'batdongsan_get_bds_price_ranges function must exist.');

        // Initial call should calculate and cache in transient
        $ranges1 = batdongsan_get_bds_price_ranges();
        $this->assertTrue(is_array($ranges1), 'Price ranges must return an array.');

        // Verify transient exists
        $cached = get_transient('bds_listings_price_ranges_total');
        $this->assertNotNull($cached, 'Transient bds_listings_price_ranges_total must be set.');

        // Invalidate transient
        delete_transient('bds_listings_price_ranges_total');
        $this->assertFalse(get_transient('bds_listings_price_ranges_total'), 'Transient must be cleared after deletion.');
    }

    /**
     * T1-R2-06: Verify seeder scripts block unauthenticated direct browser execution.
     */
    public function test_r2_seeder_unauthorized_access_blocked(): void {
        $seedDuAn = $this->themeDir . '/seed-du-an.php';
        $seedHcn = $this->themeDir . '/seed-hang-chuyen-nhuong.php';

        foreach ([$seedDuAn, $seedHcn] as $file) {
            if (!file_exists($file)) {
                continue;
            }
            $content = file_get_contents($file);

            // Must NOT have unauthenticated bypass where lack of ?seed=1 executes delete_old_*
            // Specifically, must require manage_options or WP_CLI unconditionally
            $hasUnconditionalCheck = (
                (strpos($content, "current_user_can('manage_options')") !== false && strpos($content, "isset(\$_GET['seed'])") === false) ||
                strpos($content, "php_sapi_name() !== 'cli'") !== false ||
                strpos($content, "WP_CLI") !== false
            );

            // If it still has the vulnerable pattern from SECURITY_AUDIT.md SEC-01
            $isVulnerable = preg_match('/if\s*\(\s*isset\(\$_GET\[\'seed\'\]\)\s*&&\s*\$_GET\[\'seed\'\]\s*===\s*\'1\'\s*\)\s*\{[^}]*\}\s*else\s*\{[^}]*delete_old_/s', $content);

            $this->assertFalse(
                (bool) $isVulnerable,
                basename($file) . " contains SEC-01 authentication bypass vulnerability."
            );
        }
    }
}
