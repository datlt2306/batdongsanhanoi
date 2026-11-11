<?php

namespace BDS\Tests\E2E\Tier1;

use BDS\Tests\Framework\TestCase;
use BDS\Tests\Framework\MockWordPress;

class TestR3AjaxSearchFilter extends TestCase {

    /**
     * T1-R3-01: Verify AJAX action batdongsan_filter_listings is registered.
     */
    public function test_r3_ajax_listings_filter_registered(): void {
        $filterHookFile = $this->themeDir . '/inc/hooks/ajax-listings-filter.php';
        $this->assertTrue(file_exists($filterHookFile), 'inc/hooks/ajax-listings-filter.php must exist.');

        require_once $filterHookFile;

        $hasAction = has_action('wp_ajax_batdongsan_filter_listings') && has_action('wp_ajax_nopriv_batdongsan_filter_listings');
        $this->assertTrue($hasAction, 'batdongsan_filter_listings action must be registered for wp_ajax and wp_ajax_nopriv.');
    }

    /**
     * T1-R3-02: Verify valid AJAX filter response matches interface contract schema.
     */
    public function test_r3_ajax_listings_filter_valid_response(): void {
        $filterHookFile = $this->themeDir . '/inc/hooks/ajax-listings-filter.php';
        $this->assertTrue(file_exists($filterHookFile), 'inc/hooks/ajax-listings-filter.php must exist.');

        require_once $filterHookFile;

        // Seed mock listings
        MockWordPress::insertPost([
            'post_title' => 'Biệt thự Đan Phượng',
            'post_name' => 'biet-thu-dan-phuong',
            'post_type' => 'bat-dong-san',
            'post_status' => 'publish',
        ]);

        $nonce = MockWordPress::createNonce('bds_filter_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => $nonce,
            'hinh_thuc' => 'so-cap',
            'paged' => 1,
        ]);

        $this->assertEquals(200, $result['status_code'], 'Valid filter request must return HTTP 200.');
        $response = $result['response'];
        $this->assertTrue(!empty($response['success']), 'Response success must be true.');

        $data = $response['data'] ?? [];
        $this->assertArrayHasKey('html', $data, 'Response data must contain html.');
        $this->assertArrayHasKey('pagination', $data, 'Response data must contain pagination.');
        $this->assertArrayHasKey('total', $data, 'Response data must contain total count.');
        $this->assertArrayHasKey('total_pages', $data, 'Response data must contain total_pages.');
        $this->assertArrayHasKey('current_page', $data, 'Response data must contain current_page.');
        $this->assertArrayHasKey('url', $data, 'Response data must contain url.');
    }

    /**
     * T1-R3-03: Verify missing or invalid nonce returns HTTP 403 Forbidden.
     */
    public function test_r3_ajax_listings_filter_nonce_403(): void {
        $filterHookFile = $this->themeDir . '/inc/hooks/ajax-listings-filter.php';
        $this->assertTrue(file_exists($filterHookFile), 'inc/hooks/ajax-listings-filter.php must exist.');

        require_once $filterHookFile;

        $result = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => 'forged_or_missing_nonce',
            'hinh_thuc' => 'so-cap',
        ]);

        $this->assertEquals(403, $result['status_code'], 'Invalid nonce on batdongsan_filter_listings must return HTTP 403.');
        $this->assertFalse($result['response']['success'] ?? true, 'Response success must be false on 403.');
    }

    /**
     * T1-R3-04: Verify SEO rewrite rules eliminate greedy regex collision on multi-hyphen slugs.
     */
    public function test_r3_seo_rewrite_rule_regex_fix(): void {
        $themeSetup = $this->themeDir . '/inc/hooks/theme-setup.php';
        $this->assertTrue(file_exists($themeSetup), 'theme-setup.php must exist.');

        $content = file_get_contents($themeSetup);

        // Check the old colliding greedy pattern:
        // 'du-an-bat-dong-san-([a-z0-9-]+)-([a-z0-9-]+)/?$'
        $hasCollidingGreedyRegex = preg_match(
            '/add_rewrite_rule\s*\(\s*[\'"]du-an-bat-dong-san-\(\[a-z0-9-\]\+\)-\(\[a-z0-9-\]\+\)\/\?\$/',
            $content
        );

        $this->assertFalse(
            (bool) $hasCollidingGreedyRegex,
            'Colliding greedy regex in theme-setup.php must be replaced with robust or delimited rewrite pattern.'
        );
    }

    /**
     * T1-R3-05: Verify direct URL query vars correctly hydrate server-side rendering (SSR).
     */
    public function test_r3_ssr_query_vars_handling(): void {
        $bdsPage = $this->themeDir . '/page-bat-dong-san.php';
        $this->assertTrue(file_exists($bdsPage), 'page-bat-dong-san.php must exist.');

        $content = file_get_contents($bdsPage);

        // Ensure sanitized GET parameters are accepted for SSR
        $this->assertContains('hinh-thuc', $content, 'page-bat-dong-san.php must read hinh-thuc for SSR.');
        $this->assertContains('sanitize_text_field', $content, 'Parameters must be sanitized with sanitize_text_field.');
    }

    /**
     * T1-R3-06: Verify pagination and count calculation accuracy.
     */
    public function test_r3_pagination_and_count_accuracy(): void {
        $filterHookFile = $this->themeDir . '/inc/hooks/ajax-listings-filter.php';
        $this->assertTrue(file_exists($filterHookFile), 'inc/hooks/ajax-listings-filter.php must exist.');

        require_once $filterHookFile;

        // Seed 25 items
        for ($i = 1; $i <= 25; $i++) {
            MockWordPress::insertPost([
                'post_title' => "Bất động sản số $i",
                'post_name' => "bds-$i",
                'post_type' => 'bat-dong-san',
                'post_status' => 'publish',
            ]);
        }

        $nonce = MockWordPress::createNonce('bds_filter_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => $nonce,
            'paged' => 2,
            'posts_per_page' => 10,
        ]);

        $this->assertEquals(200, $result['status_code']);
        $data = $result['response']['data'] ?? [];

        $this->assertEquals(25, $data['total'] ?? 0, 'Total count must reflect all matching items (25).');
        $this->assertEquals(3, $data['total_pages'] ?? 0, 'Total pages must be ceil(25 / 10) = 3.');
        $this->assertEquals(2, $data['current_page'] ?? 0, 'Current page must match requested page (2).');
    }
}
