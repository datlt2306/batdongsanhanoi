<?php

namespace BDS\Tests\E2E\Tier1;

use BDS\Tests\Framework\TestCase;
use BDS\Tests\Framework\MockWordPress;

class TestR4LeadsProcessing extends TestCase {

    /**
     * T1-R4-01: Verify CPT khach-hang-lien-he is registered with admin menu.
     */
    public function test_r4_leads_cpt_registered(): void {
        $leadsHookFile = $this->themeDir . '/inc/hooks/leads.php';
        $this->assertTrue(file_exists($leadsHookFile), 'inc/hooks/leads.php must exist.');

        require_once $leadsHookFile;

        $content = file_get_contents($leadsHookFile);
        $this->assertContains('khach-hang-lien-he', $content, 'CPT khach-hang-lien-he must be registered in inc/hooks/leads.php');
        $this->assertContains('show_ui', $content, 'show_ui must be true for admin management.');
    }

    /**
     * T1-R4-02: Verify custom admin columns are configured for Leads.
     */
    public function test_r4_leads_admin_columns_configured(): void {
        $leadsHookFile = $this->themeDir . '/inc/hooks/leads.php';
        $this->assertTrue(file_exists($leadsHookFile), 'inc/hooks/leads.php must exist.');

        require_once $leadsHookFile;

        $hasColumnFilter = has_filter('manage_khach-hang-lien-he_posts_columns') || has_filter('manage_edit-khach-hang-lien-he_columns');
        $this->assertTrue($hasColumnFilter, 'manage columns filter must be registered for khach-hang-lien-he.');

        $hasActionCustomCol = has_action('manage_khach-hang-lien-he_posts_custom_column');
        $this->assertTrue($hasActionCustomCol, 'manage custom column action must be registered.');
    }

    /**
     * T1-R4-03: Verify AJAX lead submission succeeds and stores lead post.
     */
    public function test_r4_ajax_lead_submission_success(): void {
        $leadsHookFile = $this->themeDir . '/inc/hooks/leads.php';
        $this->assertTrue(file_exists($leadsHookFile), 'inc/hooks/leads.php must exist.');

        require_once $leadsHookFile;

        $nonce = MockWordPress::createNonce('bds_lead_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $nonce,
            'fullname' => 'Nguyễn Văn An',
            'phone' => '0912345678',
            'email' => 'an.nguyen@example.com',
            'source' => 'advisor_modal',
            'message' => 'Tôi quan tâm căn hộ 2 phòng ngủ.',
            'post_id' => 773,
            'honeypot' => '',
        ]);

        $this->assertEquals(200, $result['status_code'], 'Valid lead submission must return HTTP 200.');
        $response = $result['response'];
        $this->assertTrue(!empty($response['success']), 'Response success must be true.');

        $leadId = $response['data']['lead_id'] ?? 0;
        $this->assertTrue($leadId > 0, 'Returned lead_id must be a positive integer.');

        $post = MockWordPress::getPost($leadId);
        $this->assertNotNull($post, 'Lead post must exist in database.');
        $this->assertEquals('khach-hang-lien-he', $post->post_type, 'Lead post type must be khach-hang-lien-he.');

        // Verify meta
        $phoneMeta = MockWordPress::getPostMeta($leadId, 'phone', true);
        $this->assertEquals('0912345678', $phoneMeta, 'Lead phone meta must match submitted value.');
    }

    /**
     * T1-R4-04: Verify honeypot anti-bot trap rejects bot submission.
     */
    public function test_r4_honeypot_bot_rejection(): void {
        $leadsHookFile = $this->themeDir . '/inc/hooks/leads.php';
        $this->assertTrue(file_exists($leadsHookFile), 'inc/hooks/leads.php must exist.');

        require_once $leadsHookFile;

        $nonce = MockWordPress::createNonce('bds_lead_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $nonce,
            'fullname' => 'Spam Bot',
            'phone' => '0912345678',
            'email' => 'bot@spam.com',
            'source' => 'advisor_modal',
            'honeypot' => 'http://spam-link.com', // Filled honeypot
        ]);

        $this->assertEquals(400, $result['status_code'], 'Honeypot filled submission must return HTTP 400 Bad Request.');
        $this->assertFalse($result['response']['success'] ?? true, 'Response success must be false.');
    }

    /**
     * T1-R4-05: Verify phone and email validation rules.
     */
    public function test_r4_lead_validation_phone_and_email(): void {
        $leadsHookFile = $this->themeDir . '/inc/hooks/leads.php';
        $this->assertTrue(file_exists($leadsHookFile), 'inc/hooks/leads.php must exist.');

        require_once $leadsHookFile;

        $nonce = MockWordPress::createNonce('bds_lead_nonce');

        // Invalid phone number
        $resultPhone = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $nonce,
            'fullname' => 'Test User',
            'phone' => '12345', // Invalid VN phone
            'email' => 'user@example.com',
            'honeypot' => '',
        ]);

        $this->assertEquals(400, $resultPhone['status_code'], 'Invalid phone format must return HTTP 400.');

        // Invalid email format
        $resultEmail = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $nonce,
            'fullname' => 'Test User',
            'phone' => '0912345678',
            'email' => 'not-an-email',
            'honeypot' => '',
        ]);

        $this->assertEquals(400, $resultEmail['status_code'], 'Invalid email format must return HTTP 400.');
    }

    /**
     * T1-R4-06: Verify wp_mail trigger and batdongsan_lead_created action hook.
     */
    public function test_r4_wp_mail_and_lead_created_hook(): void {
        $leadsHookFile = $this->themeDir . '/inc/hooks/leads.php';
        $this->assertTrue(file_exists($leadsHookFile), 'inc/hooks/leads.php must exist.');

        require_once $leadsHookFile;

        $nonce = MockWordPress::createNonce('bds_lead_nonce');

        // Clear spies
        MockWordPress::$dispatched_emails = [];
        MockWordPress::$fired_actions = [];

        $result = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $nonce,
            'fullname' => 'Trần Thị Mai',
            'phone' => '0987654321',
            'email' => 'mai.tran@example.com',
            'source' => 'newsletter',
            'honeypot' => '',
        ]);

        $this->assertEquals(200, $result['status_code']);

        // Check email notification
        $this->assertTrue(!empty(MockWordPress::$dispatched_emails), 'Notification email must be dispatched via wp_mail.');

        // Check batdongsan_lead_created hook
        $hookFired = false;
        foreach (MockWordPress::$fired_actions as $action) {
            if ($action['tag'] === 'batdongsan_lead_created') {
                $hookFired = true;
                break;
            }
        }
        $this->assertTrue($hookFired, 'Action hook batdongsan_lead_created must be fired upon lead creation.');
    }
}
