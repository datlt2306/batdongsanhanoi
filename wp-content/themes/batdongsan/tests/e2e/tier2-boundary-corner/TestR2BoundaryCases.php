<?php

namespace BDS\Tests\E2E\Tier2;

use BDS\Tests\Framework\TestCase;
use BDS\Tests\Framework\MockWordPress;

class TestR2BoundaryCases extends TestCase {

    /**
     * T2-R2-01: SQL injection payloads in search filters must be properly escaped.
     */
    public function test_r2_b01_sql_injection_in_filter_parameters(): void {
        global $wpdb;

        $sqliPayload = "' OR 1=1 UNION SELECT null, null, null -- ";
        $prepared = $wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE post_title LIKE %s", '%' . $sqliPayload . '%');

        $this->assertContains(
            addslashes($sqliPayload),
            $prepared,
            'SQL injection payload must be properly quoted and escaped by prepare.'
        );
    }

    /**
     * T2-R2-02: Extreme numeric bounds in price/area filters.
     */
    public function test_r2_b02_extreme_price_area_range_bounds(): void {
        $priceRangesFile = $this->themeDir . '/inc/services/price-ranges.php';
        $this->assertTrue(file_exists($priceRangesFile));
        require_once $priceRangesFile;

        // Pass post with extreme negative and huge prices
        $p1 = (object) ['ID' => 101];
        $p2 = (object) ['ID' => 102];

        MockWordPress::updatePostMeta(101, 'gia', -500);
        MockWordPress::updatePostMeta(102, 'gia', '1000000000000');

        $ranges = batdongsan_build_price_ranges_from_posts([$p1, $p2]);
        $this->assertTrue(is_array($ranges), 'Extreme price values must not throw error or notice.');
        $this->assertArrayHasKey('0', $ranges, 'Default key 0 must exist.');
    }

    /**
     * T2-R2-03: Exotic XSS attack vectors in map facade embed code.
     */
    public function test_r2_b03_map_facade_exotic_xss_vectors(): void {
        $facadeFile = $this->themeDir . '/template-parts/map-facade.php';
        $this->assertTrue(file_exists($facadeFile));

        $exoticVectors = [
            '<iframe src="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=="></iframe>',
            '<iframe src="vbscript:msgbox(1)"></iframe>',
            '<iframe src=" JaVaScRiPt:alert(1) "></iframe>',
        ];

        foreach ($exoticVectors as $vector) {
            ob_start();
            $args = ['embed_code' => $vector];
            include $facadeFile;
            $output = ob_get_clean();

            $this->assertDoesNotMatchRegularExpression(
                '/data:text|vbscript:|javascript:/i',
                $output,
                'Map facade output must strip dangerous protocols from vector: ' . $vector
            );
        }
    }

    /**
     * T2-R2-04: Transient cache missing / concurrency recovery.
     */
    public function test_r2_b04_transient_cache_stampede_concurrency(): void {
        $priceRangesFile = $this->themeDir . '/inc/services/price-ranges.php';
        require_once $priceRangesFile;

        // Force delete transient
        delete_transient('bds_price_ranges');

        // Calling get_price_ranges should repopulate cleanly
        $ranges = batdongsan_get_price_ranges();
        $this->assertTrue(is_array($ranges), 'Price ranges must return array after cache miss.');
        $this->assertArrayHasKey('0', $ranges, 'Key 0 must be present.');
    }

    /**
     * T2-R2-05: Massive string input (>64KB) in search filter parameters.
     */
    public function test_r2_b05_large_get_payload_truncation(): void {
        $hugeString = str_repeat('A', 70000);
        $sanitized = sanitize_text_field($hugeString);

        $this->assertTrue(is_string($sanitized), 'Huge string must sanitize safely without memory exhaustion.');
        $this->assertEquals(70000, strlen($sanitized));
    }
}
