<?php

namespace BDS\Tests\E2E\Tier2;

use BDS\Tests\Framework\TestCase;
use BDS\Tests\Framework\MockWordPress;

class TestR4BoundaryCases extends TestCase {

    /**
     * T2-R4-01: Vietnamese phone format boundaries.
     */
    public function test_r4_b01_boundary_vietnamese_phone_formats(): void {
        $this->requireThemeFile('inc/hooks/leads.php');

        $nonce = MockWordPress::createNonce('bds_lead_nonce');

        $validPhones = [
            '0912345678',
            '+84912345678',
            '84912345678',
            '0381234567',
            '0771234567',
            '0561234567',
        ];

        foreach ($validPhones as $phone) {
            $result = MockWordPress::simulateAjax('batdongsan_submit_lead', [
                'nonce' => $nonce,
                'fullname' => 'Khách hàng Test',
                'phone' => $phone,
                'email' => 'test@example.com',
                'honeypot' => '',
            ]);
            $this->assertEquals(200, $result['status_code'], "Valid phone $phone must be accepted with HTTP 200.");
        }

        $invalidPhones = [
            '091234567', // too short
            '091234567899', // too long
            '0123456789', // invalid prefix
            'not_a_phone',
        ];

        foreach ($invalidPhones as $phone) {
            $result = MockWordPress::simulateAjax('batdongsan_submit_lead', [
                'nonce' => $nonce,
                'fullname' => 'Khách hàng Test',
                'phone' => $phone,
                'email' => 'test@example.com',
                'honeypot' => '',
            ]);
            $this->assertEquals(400, $result['status_code'], "Invalid phone $phone must be rejected with HTTP 400.");
        }
    }

    /**
     * T2-R4-02: Whitespace-padded input fields are automatically trimmed.
     */
    public function test_r4_b02_whitespace_padded_inputs(): void {
        $this->requireThemeFile('inc/hooks/leads.php');

        $nonce = MockWordPress::createNonce('bds_lead_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $nonce,
            'fullname' => "   Nguyễn Văn B   \n",
            'phone' => "  0988776655  ",
            'email' => "  b.nguyen@example.com  ",
            'honeypot' => '',
        ]);

        $this->assertEquals(200, $result['status_code']);
        $leadId = $result['response']['data']['lead_id'] ?? 0;

        $phoneMeta = MockWordPress::getPostMeta($leadId, 'phone', true);
        $this->assertEquals('0988776655', $phoneMeta, 'Phone meta must be trimmed.');
    }

    /**
     * T2-R4-03: Stored XSS payload in lead message is stripped or sanitized.
     */
    public function test_r4_b03_xss_payload_in_lead_message(): void {
        $this->requireThemeFile('inc/hooks/leads.php');

        $nonce = MockWordPress::createNonce('bds_lead_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $nonce,
            'fullname' => '<script>alert("xss")</script>Lê Văn C',
            'phone' => '0912999888',
            'email' => 'c@example.com',
            'message' => '<img src=x onerror=alert(1)>Cần tư vấn.',
            'honeypot' => '',
        ]);

        $this->assertEquals(200, $result['status_code']);
        $leadId = $result['response']['data']['lead_id'] ?? 0;

        $post = MockWordPress::getPost($leadId);
        $this->assertDoesNotMatchRegularExpression('/<script>/i', $post->post_title, 'XSS script tags must be stripped from post title.');
    }

    /**
     * T2-R4-04: Honeypot field filled with whitespace is treated as filled bot trap.
     */
    public function test_r4_b04_honeypot_with_whitespace_treated_as_bot(): void {
        $this->requireThemeFile('inc/hooks/leads.php');

        $nonce = MockWordPress::createNonce('bds_lead_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $nonce,
            'fullname' => 'Bot Hidden',
            'phone' => '0912345678',
            'email' => 'bot@hidden.com',
            'honeypot' => "   \t  ", // whitespace only
        ]);

        $this->assertEquals(400, $result['status_code'], 'Whitespace-filled honeypot must be rejected as bot (HTTP 400).');
    }

    /**
     * T2-R4-05: Missing optional fields (email and message) succeeds.
     */
    public function test_r4_b05_missing_optional_fields_succeeds(): void {
        $this->requireThemeFile('inc/hooks/leads.php');

        $nonce = MockWordPress::createNonce('bds_lead_nonce');

        $result = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce' => $nonce,
            'fullname' => 'Vũ Thị Lan',
            'phone' => '0977112233',
            'email' => '', // optional
            'message' => '', // optional
            'honeypot' => '',
        ]);

        $this->assertEquals(200, $result['status_code'], 'Submission with only required fields must succeed.');
        $leadId = $result['response']['data']['lead_id'] ?? 0;
        $this->assertTrue($leadId > 0);
    }
}
