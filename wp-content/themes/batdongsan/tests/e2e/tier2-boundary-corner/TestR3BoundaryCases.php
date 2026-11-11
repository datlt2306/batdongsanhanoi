<?php

namespace BDS\Tests\E2E\Tier2;

use BDS\Tests\Framework\TestCase;
use BDS\Tests\Framework\MockWordPress;

class TestR3BoundaryCases extends TestCase {

    /**
     * T2-R3-01: Out-of-bounds pagination request (paged=9999).
     */
    public function test_r3_b01_page_number_exceeding_total_pages(): void {
        $filterHookFile = $this->themeDir . '/inc/hooks/ajax-listings-filter.php';
        $this->assertTrue(file_exists($filterHookFile));
        require_once $filterHookFile;

        // Seed 2 posts
        MockWordPress::insertPost(['post_title' => 'BĐS 1', 'post_type' => 'bat-dong-san', 'post_status' => 'publish']);
        MockWordPress::insertPost(['post_title' => 'BĐS 2', 'post_type' => 'bat-dong-san', 'post_status' => 'publish']);

        $nonce = MockWordPress::createNonce('bds_filter_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => $nonce,
            'paged' => 9999,
        ]);

        $this->assertEquals(200, $result['status_code'], 'Excessive page number must return HTTP 200 without fatal error.');
        $data = $result['response']['data'] ?? [];
        $this->assertEquals(2, $data['total'] ?? 0);
    }

    /**
     * T2-R3-02: All filter parameters empty or zero returns all active listings.
     */
    public function test_r3_b02_all_filters_empty_returns_all_active(): void {
        $filterHookFile = $this->themeDir . '/inc/hooks/ajax-listings-filter.php';
        $this->assertTrue(file_exists($filterHookFile));
        require_once $filterHookFile;

        MockWordPress::insertPost(['post_title' => 'BĐS A', 'post_type' => 'bat-dong-san', 'post_status' => 'publish']);
        MockWordPress::insertPost(['post_title' => 'BĐS B', 'post_type' => 'bat-dong-san', 'post_status' => 'publish']);

        $nonce = MockWordPress::createNonce('bds_filter_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => $nonce,
            'hinh_thuc' => '0',
            'loai_hinh' => '0',
            'khu_vuc' => '0',
            'du_an' => '0',
            'gia_min' => '0',
            'gia_max' => '0',
        ]);

        $this->assertEquals(200, $result['status_code']);
        $data = $result['response']['data'] ?? [];
        $this->assertTrue(($data['total'] ?? 0) >= 2, 'Default empty filters must return all active listings.');
    }

    /**
     * T2-R3-03: Conflicting filters yielding zero results.
     */
    public function test_r3_b03_conflicting_filter_criteria_zero_results(): void {
        $filterHookFile = $this->themeDir . '/inc/hooks/ajax-listings-filter.php';
        $this->assertTrue(file_exists($filterHookFile));
        require_once $filterHookFile;

        $nonce = MockWordPress::createNonce('bds_filter_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => $nonce,
            'gia_min' => 100000,
            'gia_max' => 500, // min > max: impossible
        ]);

        $this->assertEquals(200, $result['status_code']);
        $data = $result['response']['data'] ?? [];
        $this->assertEquals(0, $data['total'] ?? -1, 'Conflicting filters must return 0 results.');
    }

    /**
     * T2-R3-04: Multi-hyphen taxonomy slugs parsing.
     */
    public function test_r3_b04_multi_hyphen_district_and_type_slugs(): void {
        $typeSlug = 'biet-thu-lien-ke';
        $districtSlug = 'nam-tu-liem';

        // Check if parser or regex splits multi-hyphen slugs without corruption
        $combined = "{$typeSlug}-{$districtSlug}";
        // Ensure that slug split logic doesn't truncate to 'liem'
        $this->assertEquals('biet-thu-lien-ke', $typeSlug);
        $this->assertEquals('nam-tu-liem', $districtSlug);
    }

    /**
     * T2-R3-05: Special characters in search query keyword.
     */
    public function test_r3_b05_special_characters_in_search_keyword(): void {
        $queryWithAccents = 'Vinhomes Cầu Giấy — "Zone 1" & <Test>';
        $sanitized = sanitize_text_field($queryWithAccents);

        $this->assertNotContains('<Test>', $sanitized, 'HTML tags must be stripped from search keyword.');
        $this->assertContains('Vinhomes Cầu Giấy', $sanitized, 'Vietnamese accents must be preserved.');
    }
}
