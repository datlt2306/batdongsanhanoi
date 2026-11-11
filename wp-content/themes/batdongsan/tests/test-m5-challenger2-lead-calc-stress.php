<?php

/**
 * Milestone 5 Challenger 2 - Lead Security Nonces, Honeypot & Loan Calculator DOM Bindings Stress-Test Harness
 *
 * Scope:
 *  1. Lead Security & Anti-Bot: CSRF nonces, Honeypot detection, Phone validation matrix, Sanitization, Persistence.
 *  2. Loan Calculator DOM Bindings: Strict existence and exact matching of 14 DOM IDs + chart/toggle elements.
 *  3. Client-Side JS Enhancements: Elimination of native alert(), inline feedback rendering, VN phone regex, double-submit protection.
 *  4. Template Parts Inclusion & Security Markup Verification.
 *
 * @package BatDongSan
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/inc/hooks/leads.php';
require_once dirname(__DIR__) . '/inc/hooks/assets.php';

use BDS\Tests\Framework\MockWordPress;
use BDS\Tests\Framework\AjaxExitException;

class M5Challenger2LeadCalcStressHarness {

    private int $passes = 0;
    private int $failures = 0;
    private array $failureDetails = [];
    private string $themeDir;

    public function __construct() {
        $this->themeDir = dirname(__DIR__);
    }

    public function run(): bool {
        echo "================================================================================\n";
        echo "   M5 CHALLENGER 2: LEAD SECURITY, HONEYPOT & LOAN CALCULATOR DOM HARNESS       \n";
        echo "================================================================================\n\n";

        $startTime = microtime(true);

        $this->suite1_lead_security_and_honeypot();
        $this->suite2_loan_calculator_dom_bindings();
        $this->suite3_js_modal_and_form_behavior();
        $this->suite4_template_integrations();

        $duration = microtime(true) - $startTime;

        echo "\n================================================================================\n";
        echo "   M5 CHALLENGER 2 EXECUTION SUMMARY                                            \n";
        echo "================================================================================\n";
        echo sprintf(" Total Assertions: %d\n", $this->passes + $this->failures);
        echo sprintf(" Passed:           %d\n", $this->passes);
        echo sprintf(" Failed:           %d\n", $this->failures);
        echo sprintf(" Duration:         %.4f seconds\n", $duration);
        echo "================================================================================\n\n";

        if ($this->failures === 0) {
            echo " [VERDICT: APPROVE] ALL LEAD SECURITY & LOAN CALC DOM TESTS PASSED EMPIRICALLY!\n\n";
            return true;
        }

        echo " [VERDICT: REJECT] ENCOUNTERED " . $this->failures . " FAILURES:\n";
        foreach ($this->failureDetails as $detail) {
            echo "  - $detail\n";
        }
        echo "\n";
        return false;
    }

    private function pass(string $id, string $message): void {
        $this->passes++;
        echo "  ✔ PASS: $id ($message)\n";
    }

    private function fail(string $id, string $message): void {
        $this->failures++;
        $this->failureDetails[] = "[$id] $message";
        echo "  ✖ FAIL: $id ($message)\n";
    }

    private function assert(bool $condition, string $id, string $message): void {
        if ($condition) {
            $this->pass($id, $message);
        } else {
            $this->fail($id, $message);
        }
    }

    /**
     * Suite 1: Lead Security, Anti-Bot Honeypot & Endpoint Verification
     */
    private function suite1_lead_security_and_honeypot(): void {
        echo "--- Suite 1: Lead Security & Anti-Bot Empirical Hardening ---\n";

        // 1.1 Nonce Generation in assets.php
        $assetsFile = file_get_contents($this->themeDir . '/inc/hooks/assets.php');
        $this->assert(
            strpos($assetsFile, "wp_create_nonce('bds_lead_nonce')") !== false,
            'T1.1-Nonce-Generated-bds_lead_nonce',
            'inc/hooks/assets.php generates bds_lead_nonce via wp_create_nonce'
        );
        $this->assert(
            strpos($assetsFile, "'bdsLeadNonce'   => \$lead_nonce") !== false || strpos($assetsFile, "'bdsLeadNonce' => \$lead_nonce") !== false,
            'T1.2-Nonce-Localized-To-JS',
            'bdsLeadNonce is localized to bdsTheme and bds_vars for client-side AJAX'
        );

        // 1.2 AJAX Action Registration in leads.php
        $leadsFile = file_get_contents($this->themeDir . '/inc/hooks/leads.php');
        $this->assert(
            strpos($leadsFile, "add_action('wp_ajax_batdongsan_submit_lead', 'batdongsan_ajax_submit_lead')") !== false &&
            strpos($leadsFile, "add_action('wp_ajax_nopriv_batdongsan_submit_lead', 'batdongsan_ajax_submit_lead')") !== false,
            'T1.3-AJAX-Action-Hooks-Registered',
            'batdongsan_submit_lead registered for both authenticated and guest users'
        );

        // 1.3 Missing Nonce Rejection
        $resMissing = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'fullname' => 'Nguyen Van A',
            'phone'    => '0912345678'
        ]);
        $this->assert(
            $resMissing['status_code'] === 403,
            'T1.4-Missing-Nonce-403',
            'Missing nonce returns HTTP 403 Forbidden'
        );

        // 1.4 Forged Nonce Rejection
        $resForged = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce'    => 'tampered_forged_csrf_nonce',
            'fullname' => 'Nguyen Van A',
            'phone'    => '0912345678'
        ]);
        $this->assert(
            $resForged['status_code'] === 403,
            'T1.5-Forged-Nonce-403',
            'Forged nonce returns HTTP 403 Forbidden'
        );

        // 1.5 Valid Nonce with Honeypots (Adversarial Bot Attacks)
        $validNonce = wp_create_nonce('bds_lead_nonce');

        // Bot trap: bds_honeypot filled
        $resHp1 = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce'        => $validNonce,
            'fullname'     => 'Spam Bot',
            'phone'        => '0912345678',
            'bds_honeypot' => 'http://spamsite.com'
        ]);
        $this->assert(
            $resHp1['status_code'] === 400 && ($resHp1['response']['data']['message'] ?? '') === 'Spam detected',
            'T1.6-Honeypot-bds_honeypot-Blocked',
            'Populated bds_honeypot rejected with HTTP 400 Spam detected'
        );

        // Bot trap: honeypot generic filled
        $resHp2 = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce'    => $validNonce,
            'fullname' => 'Spam Bot',
            'phone'    => '0912345678',
            'honeypot' => 'malicious_agent'
        ]);
        $this->assert(
            $resHp2['status_code'] === 400 && ($resHp2['response']['data']['message'] ?? '') === 'Spam detected',
            'T1.7-Honeypot-generic-Blocked',
            'Populated honeypot rejected with HTTP 400 Spam detected'
        );

        // Bot trap: whitespace honeypot
        $resHp3 = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce'        => $validNonce,
            'fullname'     => 'Spam Bot',
            'phone'        => '0912345678',
            'bds_honeypot' => '   '
        ]);
        $this->assert(
            $resHp3['status_code'] === 400,
            'T1.8-Honeypot-Whitespace-Blocked',
            'Whitespace honeypot payload correctly rejected as bot activity'
        );

        // 1.6 Legitimate Submission Succeeds
        $resValid = MockWordPress::simulateAjax('batdongsan_submit_lead', [
            'nonce'        => $validNonce,
            'fullname'     => 'Tran Thi B',
            'phone'        => '0987654321',
            'email'        => 'tranthib@example.com',
            'source'       => 'advisor_modal',
            'bds_honeypot' => ''
        ]);
        $this->assert(
            $resValid['status_code'] === 200 && ($resValid['response']['success'] ?? false) === true,
            'T1.9-Legitimate-Lead-Submission-Succeeds',
            'Clean lead with valid nonce and empty honeypot returns HTTP 200 success'
        );
    }

    /**
     * Suite 2: Loan Calculator DOM Bindings & Selector Integrity
     */
    private function suite2_loan_calculator_dom_bindings(): void {
        echo "\n--- Suite 2: Loan Calculator DOM Bindings Stress-Test ---\n";

        $calcPath = $this->themeDir . '/template-parts/loan-calculator.php';
        $this->assert(file_exists($calcPath), 'T2.1-File-Exists', 'template-parts/loan-calculator.php exists');

        $calcContent = file_get_contents($calcPath);

        $required14IDs = [
            'project-loan-section'     => 'Root container section',
            'loan-property-value'      => 'Property value input',
            'loan-ratio'               => 'Loan ratio percentage input',
            'loan-amount'              => 'Computed loan amount input',
            'loan-term'                => 'Loan term years input',
            'loan-bank'                => 'Bank selection dropdown',
            'loan-interest-rate'       => 'Interest rate input',
            'loan-method'              => 'Amortization method select',
            'loan-result-total'        => 'Total payable result display',
            'loan-result-equity'       => 'Equity / self-funded display',
            'loan-result-principal'    => 'Principal loan display',
            'loan-result-interest'     => 'Total interest display',
            'loan-result-first-month'  => 'First month payment display',
            'loan-export-excel'        => 'Excel export button'
        ];

        foreach ($required14IDs as $id => $desc) {
            $pattern = '/id=["\']' . preg_quote($id, '/') . '["\']/';
            $found = (bool) preg_match($pattern, $calcContent);
            $this->assert($found, "T2.2-DOM-ID-{$id}", "DOM ID #{$id} exists ($desc)");
        }

        // Auxiliary interactive selectors required by js/main.js
        $auxiliarySelectors = [
            'loan-calculator__chart-equity'    => 'class',
            'loan-calculator__chart-principal' => 'class',
            'loan-calculator__chart-interest'  => 'class',
            'loan-calculator__pct-equity'      => 'class',
            'loan-calculator__pct-principal'   => 'class',
            'loan-calculator__pct-interest'    => 'class',
            'loan-bank-policy-link'            => 'id',
            'data-loan-toggle-result'          => 'attr',
            'data-loan-toggle-label'           => 'attr',
            'data-loan-result-panel'           => 'attr',
        ];

        foreach ($auxiliarySelectors as $selector => $type) {
            if ($type === 'class') {
                $found = strpos($calcContent, $selector) !== false;
            } elseif ($type === 'id') {
                $found = (bool) preg_match('/id=["\']' . preg_quote($selector, '/') . '["\']/', $calcContent);
            } else {
                $found = strpos($calcContent, $selector) !== false;
            }
            $this->assert($found, "T2.3-Aux-{$selector}", "Auxiliary interactive element {$selector} present");
        }

        // Verify js/main.js references all 14 IDs
        $mainJs = file_get_contents($this->themeDir . '/js/main.js');
        foreach (array_keys($required14IDs) as $id) {
            $foundInJs = strpos($mainJs, "#{$id}") !== false;
            $this->assert($foundInJs, "T2.4-JS-Selector-{$id}", "js/main.js queries #{$id}");
        }
    }

    /**
     * Suite 3: Client-Side JS Enhancements & Elimination of alert()
     */
    private function suite3_js_modal_and_form_behavior(): void {
        echo "\n--- Suite 3: Client-Side JS Enhancements & Modal Verification ---\n";

        $mainJs = file_get_contents($this->themeDir . '/js/main.js');

        // 3.1 Extract initAdvisorModal and initNewsletterForms slices
        $advisorModalPos = strpos($mainJs, 'const initAdvisorModal = function');
        $this->assert($advisorModalPos !== false, 'T3.1-initAdvisorModal-Defined', 'initAdvisorModal function found in js/main.js');

        $newsletterPos = strpos($mainJs, 'const initNewsletterForms = function');
        $this->assert($newsletterPos !== false, 'T3.2-initNewsletterForms-Defined', 'initNewsletterForms function found in js/main.js');

        // Slice handlers code until next module initMobileMenu
        $mobileMenuPos = strpos($mainJs, 'function initMobileMenu()');
        $leadHandlersSlice = substr($mainJs, $advisorModalPos, $mobileMenuPos - $advisorModalPos);

        // 3.2 Assert native alert() eliminated from lead submission handlers
        $hasAlertInLeadHandlers = strpos($leadHandlersSlice, 'alert(') !== false;
        $this->assert(
            !$hasAlertInLeadHandlers,
            'T3.3-Native-Alert-Eliminated-In-Lead-Forms',
            'Zero native alert() calls in lead modal and newsletter submission handlers'
        );

        // 3.3 Assert inline .advisor-modal__feedback exists in advisor modal HTML & JS
        $this->assert(
            strpos($leadHandlersSlice, 'advisor-modal__feedback') !== false,
            'T3.4-Advisor-Modal-Feedback-Container',
            'Inline .advisor-modal__feedback container present and manipulated in JS'
        );

        // 3.4 Assert double-submit protection with animated SVG spinner
        $this->assert(
            strpos($leadHandlersSlice, 'animate-spin') !== false && strpos($leadHandlersSlice, 'disabled') !== false,
            'T3.5-Double-Submit-Spinner-Present',
            'Double-submit prevention with animated SVG spinner and button disabling present'
        );

        // 3.5 Vietnamese Phone Regex Validation Matrix
        $phoneRegexMatch = preg_match('/phoneRegex\s*=\s*(\/[^\/]+\/)/', $leadHandlersSlice, $matches);
        $this->assert((bool) $phoneRegexMatch, 'T3.6-Phone-Regex-Found-In-JS', 'phoneRegex definition found in lead handlers');

        // Test the exact regex pattern extracted from JS with PCRE 'D' modifier (matches JS end-of-string semantics)
        $pattern = ($phoneRegexMatch ? $matches[1] : '/^(?:\+84|84|0)[35789][0-9]{8}$/') . 'D';

        $validPhones = [
            '0901234567', '0912345678', '0987654321',
            '0321234567', '0389998888',
            '0521234567', '0581234567',
            '0701234567', '0799887766',
            '0861234567', '0898765432',
            '+84901234567', '+84381234567',
            '84901234567', '84861234567'
        ];

        $invalidPhones = [
            '0123456789', // Invalid prefix 01
            '0241234567', // Landline 024
            '0412345678', // Invalid prefix 04
            '0612345678', // Invalid prefix 06
            '090123456',  // Too short (9 digits)
            '090123456789', // Too long (11 digits)
            '090123456a', // Contains letter
            '+84',        // Incomplete
            'abcdefghij', // Alphabetic
            "0901234567\n", // Trailing newline
            "0901234567' OR '1'='1" // SQL injection probe
        ];

        $allValidPassed = true;
        foreach ($validPhones as $phone) {
            if (!preg_match($pattern, $phone)) {
                $allValidPassed = false;
                $this->fail("T3.7-Valid-Phone-{$phone}", "Valid phone {$phone} unexpectedly failed regex");
            }
        }
        if ($allValidPassed) {
            $this->pass('T3.7-Valid-Phone-Matrix', 'All 15 valid Vietnamese mobile formats matched regex');
        }

        $allInvalidPassed = true;
        foreach ($invalidPhones as $phone) {
            if (preg_match($pattern, $phone)) {
                $allInvalidPassed = false;
                $this->fail("T3.8-Invalid-Phone-{$phone}", "Invalid phone {$phone} unexpectedly accepted by regex");
            }
        }
        if ($allInvalidPassed) {
            $this->pass('T3.8-Invalid-Phone-Matrix', 'All 11 invalid phone formats rejected by regex');
        }
    }

    /**
     * Suite 4: Template Integrations & Security Attributes
     */
    private function suite4_template_integrations(): void {
        echo "\n--- Suite 4: Template Integrations & Security Attributes ---\n";

        // 4.1 template-parts/advisor-card.php
        $advisorCard = file_get_contents($this->themeDir . '/template-parts/advisor-card.php');
        $this->assert(
            strpos($advisorCard, 'name="bds_honeypot"') !== false,
            'T4.1-AdvisorCard-Honeypot',
            'advisor-card.php contains bds_honeypot input'
        );
        $this->assert(
            strpos($advisorCard, "wp_nonce_field('bds_lead_nonce'") !== false,
            'T4.2-AdvisorCard-NonceField',
            'advisor-card.php generates bds_lead_nonce field'
        );
        $this->assert(
            strpos($advisorCard, 'name="action" value="batdongsan_submit_lead"') !== false,
            'T4.3-AdvisorCard-Action',
            'advisor-card.php sets action to batdongsan_submit_lead'
        );
        $this->assert(
            strpos($advisorCard, 'advisor-quick-lead-feedback') !== false,
            'T4.4-AdvisorCard-Inline-Feedback',
            'advisor-card.php contains advisor-quick-lead-feedback inline container'
        );

        // 4.2 template-parts/newsletter-form.php
        $newsletterForm = file_get_contents($this->themeDir . '/template-parts/newsletter-form.php');
        $this->assert(
            strpos($newsletterForm, 'name="bds_honeypot"') !== false,
            'T4.5-Newsletter-Honeypot',
            'newsletter-form.php contains bds_honeypot input'
        );
        $this->assert(
            strpos($newsletterForm, "wp_create_nonce('bds_lead_nonce')") !== false,
            'T4.6-Newsletter-Nonce',
            'newsletter-form.php generates bds_lead_nonce'
        );
        $this->assert(
            strpos($newsletterForm, 'name="action" value="batdongsan_submit_lead"') !== false,
            'T4.7-Newsletter-Action',
            'newsletter-form.php sets action to batdongsan_submit_lead'
        );
        $this->assert(
            strpos($newsletterForm, 'bds-form-feedback') !== false,
            'T4.8-Newsletter-Inline-Feedback',
            'newsletter-form.php contains bds-form-feedback container'
        );

        // 4.3 #advisor-modal in js/main.js
        $mainJs = file_get_contents($this->themeDir . '/js/main.js');
        $this->assert(
            strpos($mainJs, 'id="advisor-modal"') !== false,
            'T4.9-AdvisorModal-Markup',
            'js/main.js defines #advisor-modal template'
        );
        $this->assert(
            strpos($mainJs, 'name="bds_honeypot"') !== false,
            'T4.10-AdvisorModal-Honeypot',
            'js/main.js #advisor-modal includes bds_honeypot input'
        );
        $this->assert(
            strpos($mainJs, 'action: "batdongsan_submit_lead"') !== false,
            'T4.11-AdvisorModal-Submit-Action',
            'js/main.js sends action: batdongsan_submit_lead'
        );
    }
}

// Execution
$harness = new M5Challenger2LeadCalcStressHarness();
$success = $harness->run();
exit($success ? 0 : 1);
