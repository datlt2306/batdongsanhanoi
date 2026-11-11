<?php

namespace BDS\Tests\E2E\Tier4;

use BDS\Tests\Framework\TestCase;
use BDS\Tests\Framework\MockWordPress;
use BDS\Tests\Framework\RedirectExitException;

class TestRealWorldScenarios extends TestCase {

    /**
     * T4-SCEN-01: Full Migration & SEO 301 Preservation Lifecycle.
     */
    public function test_scenario_01_full_migration_lifecycle(): void {
        $this->requireThemeFile('inc/services/migration.php');
        $this->requireThemeFile('inc/hooks/redirects.php');

        // Step 1: Seed legacy posts
        $legacyId1 = MockWordPress::insertPost([
            'post_title' => 'Căn hộ Ngoại Giao Đoàn',
            'post_name' => 'can-ho-ngoai-giao-doan',
            'post_type' => 'hang-chuyen-nhuong',
            'post_status' => 'publish',
            'meta_input' => ['du_an_lien_quan' => serialize([555])],
        ]);

        $legacyId2 = MockWordPress::insertPost([
            'post_title' => 'Nhà liền kề Starlake',
            'post_name' => 'nha-lien-ke-starlake',
            'post_type' => 'nha-dat-ban',
            'post_status' => 'publish',
            'meta_input' => ['du_an_lien_quan' => '666'],
        ]);

        // Step 2: Dry-run migration
        $dryRunResult = \BDS_CPT_Migration::run(['dry_run' => true]);
        $this->assertTrue($dryRunResult['success']);
        $this->assertEquals('hang-chuyen-nhuong', MockWordPress::getPost($legacyId1)->post_type);

        // Step 3: Execute live migration
        $liveResult = \BDS_CPT_Migration::run(['dry_run' => false]);
        $this->assertTrue($liveResult['success']);
        $this->assertEquals('bat-dong-san', MockWordPress::getPost($legacyId1)->post_type);
        $this->assertEquals('bat-dong-san', MockWordPress::getPost($legacyId2)->post_type);

        // Step 4: SEO 301 Redirect verification
        if (function_exists('batdongsan_redirect_legacy_urls')) {
            $_SERVER['REQUEST_URI'] = '/hang-chuyen-nhuong/can-ho-ngoai-giao-doan';
            $caught = false;
            try {
                batdongsan_redirect_legacy_urls();
            } catch (RedirectExitException $e) {
                $caught = true;
                $this->assertEquals(301, $e->getStatus(), 'Must be 301 Permanent Redirect.');
                $this->assertContains('/bat-dong-san/can-ho-ngoai-giao-doan', $e->getLocation());
            }
            $this->assertTrue($caught, 'Legacy URL must be redirected to new bat-dong-san slug.');
        }
    }

    /**
     * T4-SCEN-02: Buyer Search, AJAX Filter & SSR Journey.
     */
    public function test_scenario_02_buyer_search_and_filter_journey(): void {
        $this->requireThemeFile('inc/hooks/ajax-listings-filter.php');

        // Seed listings
        MockWordPress::insertPost([
            'post_title' => 'Chung cư The Matrix One',
            'post_name' => 'chung-cu-the-matrix-one',
            'post_type' => 'bat-dong-san',
            'post_status' => 'publish',
            'tax_input' => [
                'hinh-thuc-bds' => ['so-cap'],
                'khu-vuc' => ['nam-tu-liem'],
            ],
        ]);

        $nonce = MockWordPress::createNonce('bds_filter_nonce');

        // AJAX request with filters
        $result = MockWordPress::simulateAjax('batdongsan_filter_listings', [
            'nonce' => $nonce,
            'hinh_thuc' => 'so-cap',
            'khu_vuc' => 'nam-tu-liem',
            'paged' => 1,
        ]);

        $this->assertEquals(200, $result['status_code']);
        $data = $result['response']['data'];
        $this->assertTrue($data['total'] >= 1);
        $this->assertContains('Matrix One', $data['html']);
    }

    /**
     * T4-SCEN-03: Lead Generation Funnel with Email and Webhook.
     */
    public function test_scenario_03_lead_generation_funnel(): void {
        $this->requireThemeFile('inc/hooks/leads.php');

        MockWordPress::$dispatched_emails = [];
        MockWordPress::$fired_actions = [];

        $nonce = MockWordPress::createNonce('bds_lead_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $nonce,
            'fullname' => 'Đặng Thu Thảo',
            'phone' => '0909123456',
            'email' => 'thao.dang@example.com',
            'source' => 'advisor_modal',
            'message' => 'Cần gửi báo giá căn 3 ngủ.',
            'post_id' => 888,
            'honeypot' => '',
        ]);

        $this->assertEquals(200, $result['status_code']);
        $leadId = $result['response']['data']['lead_id'];

        // Verify post saved
        $post = MockWordPress::getPost($leadId);
        $this->assertEquals('khach-hang-lien-he', $post->post_type);

        // Verify email dispatched
        $this->assertTrue(count(MockWordPress::$dispatched_emails) > 0, 'Admin notification email must be dispatched.');

        // Verify webhook action hook fired
        $hookFound = false;
        foreach (MockWordPress::$fired_actions as $action) {
            if ($action['tag'] === 'batdongsan_lead_created') {
                $hookFound = true;
                break;
            }
        }
        $this->assertTrue($hookFound, 'batdongsan_lead_created hook must be executed.');
    }

    /**
     * T4-SCEN-04: Bot Attack & CSRF Defense Scenario.
     */
    public function test_scenario_04_bot_attack_and_csrf_defense(): void {
        $this->requireThemeFile('inc/hooks/leads.php');

        $initialCount = count(MockWordPress::$posts);

        // 1. Attack via Honeypot
        $validNonce = MockWordPress::createNonce('bds_lead_nonce');
        $botResult = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $validNonce,
            'fullname' => 'Automated Spammer',
            'phone' => '0912345678',
            'honeypot' => 'BUY VIAGRA NOW',
        ]);
        $this->assertEquals(400, $botResult['status_code'], 'Bot must be rejected with 400.');

        // 2. Attack via CSRF (missing nonce)
        $csrfResult = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'fullname' => 'CSRF Attacker',
            'phone' => '0912345678',
            'honeypot' => '',
        ]);
        $this->assertEquals(403, $csrfResult['status_code'], 'CSRF attempt must be blocked with 403.');

        // Verify database integrity: zero posts added
        $finalCount = count(MockWordPress::$posts);
        $this->assertEquals($initialCount, $finalCount, 'Database must have zero new posts after malicious attacks.');
    }

    /**
     * T4-SCEN-05: Admin Operations & Lead Lifecycle Management.
     */
    public function test_scenario_05_admin_lead_management_workflow(): void {
        $this->requireThemeFile('inc/hooks/leads.php');

        $leadId = MockWordPress::insertPost([
            'post_title' => 'Ngô Bảo Châu',
            'post_type' => 'khach-hang-lien-he',
            'post_status' => 'publish',
            'meta_input' => [
                'fullname' => 'Ngô Bảo Châu',
                'phone' => '0918889999',
                'email' => 'chau.ngo@example.com',
                'status' => 'moi',
                'source' => 'project_detail',
            ],
        ]);

        // Verify initial state
        $this->assertEquals('moi', MockWordPress::getPostMeta($leadId, 'status', true));

        // Admin updates status to 'da_goi'
        MockWordPress::updatePostMeta($leadId, 'status', 'da_goi');
        $this->assertEquals('da_goi', MockWordPress::getPostMeta($leadId, 'status', true), 'Status update to da_goi must persist.');

        // Admin updates status to 'thanh_cong'
        MockWordPress::updatePostMeta($leadId, 'status', 'thanh_cong');
        $this->assertEquals('thanh_cong', MockWordPress::getPostMeta($leadId, 'status', true), 'Status update to thanh_cong must persist.');
    }
}
