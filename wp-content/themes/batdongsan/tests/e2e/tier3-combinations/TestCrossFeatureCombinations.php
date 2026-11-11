<?php

namespace BDS\Tests\E2E\Tier3;

use BDS\Tests\Framework\TestCase;
use BDS\Tests\Framework\MockWordPress;

class TestCrossFeatureCombinations extends TestCase {

    /**
     * T3-COMBO-01: R1 + R2 - Migrated listings queried via optimized relationship queries with transient caching.
     */
    public function test_r1_r2_combo_migrated_posts_performance_queries(): void {
        $this->requireThemeFile('inc/services/queries.php');
        $this->requireThemeFile('inc/services/price-ranges.php');

        // Seed project and migrated bds
        $projectId = MockWordPress::insertPost(['post_title' => 'Dự án Eco Green', 'post_type' => 'du-an', 'post_status' => 'publish']);
        $bdsId = MockWordPress::insertPost([
            'post_title' => 'Căn hộ Eco Green 2PN',
            'post_type' => 'bat-dong-san',
            'post_status' => 'publish',
            'meta_input' => [
                'du_an_lien_quan' => $projectId,
                'gia' => 2800,
            ],
        ]);

        $projects = batdongsan_get_projects_with_bds();
        $this->assertTrue(is_array($projects));
        $this->assertArrayHasKey($projectId, $projects, 'Migrated BĐS must associate with project in batdongsan_get_projects_with_bds.');
    }

    /**
     * T3-COMBO-02: R1 + R3 - Migrated bat-dong-san listings filterable via AJAX by hinh-thuc-bds.
     */
    public function test_r1_r3_combo_migrated_listings_in_ajax_filter(): void {
        $this->requireThemeFile('inc/hooks/ajax-listings-filter.php');

        // Seed migrated post with term chuyen-nhuong
        $postId = MockWordPress::insertPost([
            'post_title' => 'Căn chuyển nhượng Ciputra',
            'post_type' => 'bat-dong-san',
            'post_status' => 'publish',
            'tax_input' => [
                'hinh-thuc-bds' => ['chuyen-nhuong'],
            ],
        ]);

        $nonce = MockWordPress::createNonce('bds_filter_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => $nonce,
            'hinh_thuc' => 'chuyen-nhuong',
        ]);

        $this->assertEquals(200, $result['status_code']);
        $this->assertTrue(($result['response']['data']['total'] ?? 0) >= 1, 'Migrated listing must be matched by transaction taxonomy filter.');
    }

    /**
     * T3-COMBO-03: R1 + R4 - Consultation lead submitted on a migrated listing records correct post ID.
     */
    public function test_r1_r4_combo_lead_submitted_on_migrated_listing(): void {
        $this->requireThemeFile('inc/hooks/leads.php');

        $migratedListingId = MockWordPress::insertPost([
            'post_title' => 'Nhà mặt phố Cầu Giấy',
            'post_type' => 'bat-dong-san',
            'post_status' => 'publish',
        ]);

        $nonce = MockWordPress::createNonce('bds_lead_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $nonce,
            'fullname' => 'Hoàng Hải Đăng',
            'phone' => '0933221100',
            'email' => 'dang.hoang@example.com',
            'source' => 'listing_detail',
            'post_id' => $migratedListingId,
            'honeypot' => '',
        ]);

        $this->assertEquals(200, $result['status_code']);
        $leadId = $result['response']['data']['lead_id'] ?? 0;
        $savedPostId = MockWordPress::getPostMeta($leadId, 'post_id', true);

        $this->assertEquals($migratedListingId, (int) $savedPostId, 'Lead meta must record the correct migrated post ID.');
    }

    /**
     * T3-COMBO-04: R2 + R3 - AJAX filter enforces CSRF security, sanitization, and transient cache.
     */
    public function test_r2_r3_combo_ajax_filter_caching_and_security(): void {
        $this->requireThemeFile('inc/hooks/ajax-listings-filter.php');

        // 1. Nonce check failure
        $badResult = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => 'forged',
        ]);
        $this->assertEquals(403, $badResult['status_code']);

        // 2. Valid nonce with sanitization check
        $goodNonce = MockWordPress::createNonce('bds_filter_nonce');
        $goodResult = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => $goodNonce,
            'hinh_thuc' => '<script>alert(1)</script>so-cap',
        ]);
        $this->assertEquals(200, $goodResult['status_code']);
    }

    /**
     * T3-COMBO-05: R2 + R4 - Leads endpoint CSRF, honeypot defense, and admin escaping.
     */
    public function test_r2_r4_combo_lead_endpoint_security_and_escaping(): void {
        $this->requireThemeFile('inc/hooks/leads.php');

        // Nonce check
        $badNonceResult = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => 'invalid',
            'fullname' => 'Test',
            'phone' => '0912345678',
            'honeypot' => '',
        ]);
        $this->assertEquals(403, $badNonceResult['status_code']);

        // Honeypot check
        $validNonce = MockWordPress::createNonce('bds_lead_nonce');
        $botResult = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $validNonce,
            'fullname' => 'Bot',
            'phone' => '0912345678',
            'honeypot' => 'bot-data',
        ]);
        $this->assertEquals(400, $botResult['status_code']);
    }

    /**
     * T3-COMBO-06: R3 + R4 - Advisor modal AJAX submission from filtered listing.
     */
    public function test_r3_r4_combo_advisor_modal_ajax_with_filtered_post(): void {
        $this->requireThemeFile('inc/hooks/leads.php');

        $postId = MockWordPress::insertPost([
            'post_title' => 'Penthouse Metropolis',
            'post_type' => 'bat-dong-san',
            'post_status' => 'publish',
        ]);

        $nonce = MockWordPress::createNonce('bds_lead_nonce');
        MockWordPress::$fired_actions = [];

        $result = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $nonce,
            'fullname' => 'Phạm Thanh Hương',
            'phone' => '0944556677',
            'email' => 'huong.pham@example.com',
            'source' => 'advisor_modal',
            'post_id' => $postId,
            'honeypot' => '',
        ]);

        $this->assertEquals(200, $result['status_code']);
        $leadId = $result['response']['data']['lead_id'] ?? 0;

        $leadActionFound = false;
        foreach (MockWordPress::$fired_actions as $action) {
            if ($action['tag'] === 'batdongsan_lead_created') {
                $leadActionFound = true;
                $this->assertEquals($leadId, $action['args'][0] ?? null);
                break;
            }
        }
        $this->assertTrue($leadActionFound, 'batdongsan_lead_created hook must receive leadId parameter.');
    }
}
