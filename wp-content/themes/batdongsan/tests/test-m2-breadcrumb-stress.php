<?php

/**
 * Empirical Stress Test Harness: Breadcrumb Schema & Layout Contract
 * 
 * Verifies:
 * 1. Simulates breadcrumb counts: 1 crumb (home), 2 crumbs, 3 crumbs, 5 crumbs.
 * 2. Asserts Schema.org BreadcrumbList attributes (itemscope, itemtype, itemListElement, ListItem, name, item, position).
 * 3. Asserts item positions (sequential 1, 2, 3...) and URL bindings.
 * 4. Asserts mobile breadcrumb does NOT disappear when count < 3 (desktop <ol> lacks max-md:hidden).
 * 5. Asserts delimiter separator '/' is present before current post title.
 * 6. Asserts edge cases: empty title, empty URLs, XSS escaping, mobile_optimized toggle.
 * 
 * @package BatDongSan\Tests
 */

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/inc/helpers/breadcrumb.php';

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string {
        return $text;
    }
}
if (!function_exists('esc_attr__')) {
    function esc_attr__(string $text, string $domain = 'default'): string {
        return esc_attr($text);
    }
}
if (!function_exists('is_singular')) {
    function is_singular(string|array $post_types = ''): bool {
        return false;
    }
}

class BreadcrumbStressHarness {
    private int $passes = 0;
    private int $failures = 0;
    private array $errors = [];

    public function run(): void {
        echo "\n================================================================================\n";
        echo "   MILESTONE 2 EMPIRICAL STRESS TEST: BREADCRUMB SCHEMA & CONTRACT HARNESS\n";
        echo "================================================================================\n\n";

        $this->test1CrumbSimulation();
        $this->test2CrumbsSimulation();
        $this->test3CrumbsSimulation();
        $this->test5CrumbsSimulation();
        $this->testDelimiterBeforeCurrentTitle();
        $this->testMobileDisappearancePreventionWhenLessThan3();
        $this->testEdgeCasesAndSecurity();

        echo "\n================================================================================\n";
        echo sprintf("   EXECUTION SUMMARY: %d Passed, %d Failed\n", $this->passes, $this->failures);
        echo "================================================================================\n";

        if ($this->failures > 0) {
            echo "\nDetailed Failures:\n";
            foreach ($this->errors as $err) {
                echo "  ❌ " . $err . "\n";
            }
            exit(1);
        } else {
            echo "  [PASS] ALL BREADCRUMB EMPIRICAL STRESS TESTS PASSED SUCCESSFULLY (100%)\n\n";
        }
    }

    private function renderBreadcrumb(array $args): string {
        ob_start();
        require dirname(__DIR__) . '/template-parts/breadcrumb.php';
        return ob_get_clean();
    }

    private function parseDOM(string $html): DOMXPath {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        return new DOMXPath($dom);
    }

    private function assert(string $label, bool $condition, string $detail = ''): void {
        if ($condition) {
            $this->passes++;
            echo "  ✔ PASS: " . $label . ($detail ? " ($detail)" : "") . "\n";
        } else {
            $this->failures++;
            $msg = $label . ($detail ? " ($detail)" : "");
            $this->errors[] = $msg;
            echo "  ❌ FAIL: " . $msg . "\n";
        }
    }

    private function test1CrumbSimulation(): void {
        echo "--- Scenario 1: 1 Crumb Simulation (Trang chủ) ---\n";
        $args = [
            'breadcrumb_parts' => [
                ['label' => 'Trang chủ', 'url' => 'https://batdongsanhanoi.net.vn/']
            ],
            'current_label' => 'Penthouse Heritage West Lake',
            'prefix_class' => 'test-bds',
        ];

        $html = $this->renderBreadcrumb($args);
        $xpath = $this->parseDOM($html);

        // 1. Schema.org BreadcrumbList declaration
        $bclLists = $xpath->query('//ol[@itemscope and @itemtype="https://schema.org/BreadcrumbList"]');
        $this->assert('1-Crumb: Schema.org BreadcrumbList container present', $bclLists->length === 1);

        // 2. Total items: 1 crumb + 1 current = 2 items
        $items = $xpath->query('//ol[@itemscope]//li[@itemprop="itemListElement"]');
        $this->assert('1-Crumb: Desktop list has exactly 2 itemListElements', $items->length === 2);

        // 3. Position sequence: 1, 2
        $pos1 = $xpath->query('//ol[@itemscope]//li[1]//meta[@itemprop="position"]/@content')->item(0)?->nodeValue;
        $pos2 = $xpath->query('//ol[@itemscope]//li[2]//meta[@itemprop="position"]/@content')->item(0)?->nodeValue;
        $this->assert('1-Crumb: Item 1 position = 1', $pos1 === '1', "got: $pos1");
        $this->assert('1-Crumb: Item 2 (current) position = 2', $pos2 === '2', "got: $pos2");

        // 4. URL and Name bindings
        $name1 = $xpath->query('//ol[@itemscope]//li[1]//a[@itemprop="item"]//span[@itemprop="name"]')->item(0)?->nodeValue;
        $url1 = $xpath->query('//ol[@itemscope]//li[1]//a[@itemprop="item"]/@href')->item(0)?->nodeValue;
        $name2 = $xpath->query('//ol[@itemscope]//li[2]//span[@itemprop="name"]')->item(0)?->nodeValue;

        $this->assert('1-Crumb: Item 1 label is Trang chủ', $name1 === 'Trang chủ', "got: $name1");
        $this->assert('1-Crumb: Item 1 URL bound accurately', $url1 === 'https://batdongsanhanoi.net.vn/', "got: $url1");
        $this->assert('1-Crumb: Item 2 label is Penthouse Heritage West Lake', $name2 === 'Penthouse Heritage West Lake', "got: $name2");
    }

    private function test2CrumbsSimulation(): void {
        echo "\n--- Scenario 2: 2 Crumbs Simulation (Trang chủ + Bất động sản) ---\n";
        $args = [
            'breadcrumb_parts' => [
                ['label' => 'Trang chủ', 'url' => 'https://batdongsanhanoi.net.vn/'],
                ['label' => 'Bất động sản', 'url' => 'https://batdongsanhanoi.net.vn/bat-dong-san/']
            ],
            'current_label' => 'Biệt thự Starlake Embassy',
            'prefix_class' => 'test-bds',
        ];

        $html = $this->renderBreadcrumb($args);
        $xpath = $this->parseDOM($html);

        $items = $xpath->query('//ol[@itemscope]//li[@itemprop="itemListElement"]');
        $this->assert('2-Crumbs: Desktop list has exactly 3 itemListElements', $items->length === 3);

        $pos1 = $xpath->query('//ol[@itemscope]//li[1]//meta[@itemprop="position"]/@content')->item(0)?->nodeValue;
        $pos2 = $xpath->query('//ol[@itemscope]//li[2]//meta[@itemprop="position"]/@content')->item(0)?->nodeValue;
        $pos3 = $xpath->query('//ol[@itemscope]//li[3]//meta[@itemprop="position"]/@content')->item(0)?->nodeValue;
        $this->assert('2-Crumbs: Sequential positions 1, 2, 3', $pos1 === '1' && $pos2 === '2' && $pos3 === '3', "positions: $pos1, $pos2, $pos3");

        $url2 = $xpath->query('//ol[@itemscope]//li[2]//a[@itemprop="item"]/@href')->item(0)?->nodeValue;
        $name2 = $xpath->query('//ol[@itemscope]//li[2]//a[@itemprop="item"]//span[@itemprop="name"]')->item(0)?->nodeValue;
        $this->assert('2-Crumbs: Item 2 URL bound to /bat-dong-san/', $url2 === 'https://batdongsanhanoi.net.vn/bat-dong-san/');
        $this->assert('2-Crumbs: Item 2 Name is Bất động sản', $name2 === 'Bất động sản');
    }

    private function test3CrumbsSimulation(): void {
        echo "\n--- Scenario 3: 3 Crumbs Simulation (Trang chủ + BĐS + Căn hộ cao cấp) ---\n";
        $args = [
            'breadcrumb_parts' => [
                ['label' => 'Trang chủ', 'url' => 'https://batdongsanhanoi.net.vn/'],
                ['label' => 'Bất động sản', 'url' => 'https://batdongsanhanoi.net.vn/bat-dong-san/'],
                ['label' => 'Căn hộ cao cấp', 'url' => 'https://batdongsanhanoi.net.vn/ban-can-ho-cao-cap/']
            ],
            'current_label' => 'Sky Villa Sunshine Crystal',
            'prefix_class' => 'test-bds',
        ];

        $html = $this->renderBreadcrumb($args);
        $xpath = $this->parseDOM($html);

        $items = $xpath->query('//ol[@itemscope]//li[@itemprop="itemListElement"]');
        $this->assert('3-Crumbs: Desktop list has exactly 4 itemListElements', $items->length === 4);

        for ($i = 1; $i <= 4; $i++) {
            $pos = $xpath->query("//ol[@itemscope]//li[$i]//meta[@itemprop='position']/@content")->item(0)?->nodeValue;
            $this->assert("3-Crumbs: Position $i matches expected $i", $pos === (string)$i);
        }

        // When count >= 3, mobile breadcrumb is activated
        $mobileOl = $xpath->query('//ol[contains(@class, "test-bds__breadcrumb-list--mobile")]');
        $this->assert('3-Crumbs: Mobile breadcrumb <ol> rendered for >= 3 crumbs', $mobileOl->length === 1);
        $this->assert('3-Crumbs: Mobile breadcrumb has aria-hidden="true" to avoid microdata duplicate', $mobileOl->item(0)->getAttribute('aria-hidden') === 'true');

        // Check desktop has max-md:hidden when mobile breadcrumb exists
        $desktopOl = $xpath->query('//ol[contains(@class, "test-bds__breadcrumb-list--desktop")]')->item(0);
        $desktopClasses = $desktopOl ? $desktopOl->getAttribute('class') : '';
        $this->assert('3-Crumbs: Desktop <ol> has max-md:hidden when mobile breadcrumb exists', str_contains($desktopClasses, 'max-md:hidden'));
    }

    private function test5CrumbsSimulation(): void {
        echo "\n--- Scenario 4: 5 Crumbs Simulation (Trang chủ + BĐS + Hà Nội + Tây Hồ + Căn hộ) ---\n";
        $args = [
            'breadcrumb_parts' => [
                ['label' => 'Trang chủ', 'url' => 'https://batdongsanhanoi.net.vn/'],
                ['label' => 'Bất động sản', 'url' => 'https://batdongsanhanoi.net.vn/bat-dong-san/'],
                ['label' => 'Hà Nội', 'url' => 'https://batdongsanhanoi.net.vn/ha-noi/'],
                ['label' => 'Tây Hồ', 'url' => 'https://batdongsanhanoi.net.vn/tay-ho/'],
                ['label' => 'Căn hộ', 'url' => 'https://batdongsanhanoi.net.vn/can-ho/']
            ],
            'current_label' => 'Duplex Ciputra E4',
            'prefix_class' => 'test-bds',
        ];

        $html = $this->renderBreadcrumb($args);
        $xpath = $this->parseDOM($html);

        $items = $xpath->query('//ol[@itemscope]//li[@itemprop="itemListElement"]');
        $this->assert('5-Crumbs: Desktop list has exactly 6 itemListElements', $items->length === 6);

        // Verify sequential positions 1 to 6
        $positions = [];
        for ($i = 1; $i <= 6; $i++) {
            $positions[] = $xpath->query("//ol[@itemscope]//li[$i]//meta[@itemprop='position']/@content")->item(0)?->nodeValue;
        }
        $this->assert('5-Crumbs: Positions 1 through 6 are sequential', $positions === ['1', '2', '3', '4', '5', '6'], json_encode($positions));

        // Verify mobile truncated structure: Trang chủ, '...', Căn hộ, Current
        $mobileItems = $xpath->query('//ol[contains(@class, "test-bds__breadcrumb-list--mobile")]/li');
        $this->assert('5-Crumbs: Mobile list has truncated 4 items (first + ellipsis + last + current)', $mobileItems->length === 4);

        $ellipsis = $xpath->query('//ol[contains(@class, "test-bds__breadcrumb-list--mobile")]/li[2]/span')->item(0)?->nodeValue;
        $this->assert('5-Crumbs: Mobile second item contains ellipsis "..."', $ellipsis === '...');
    }

    private function testDelimiterBeforeCurrentTitle(): void {
        echo "\n--- Scenario 5: Delimiter '/' Separator Verification ---\n";

        // Case A: 1 crumb + current title -> delimiter '/' must be present before current title
        $args1 = [
            'breadcrumb_parts' => [
                ['label' => 'Trang chủ', 'url' => 'https://batdongsanhanoi.net.vn/']
            ],
            'current_label' => 'Penthouse West Lake',
            'prefix_class' => 'test-bds',
        ];
        $html1 = $this->renderBreadcrumb($args1);
        $xpath1 = $this->parseDOM($html1);

        $sep1 = $xpath1->query('//ol[contains(@class, "test-bds__breadcrumb-list--desktop")]/li[1]/span[contains(@class, "breadcrumb-separator")]');
        $this->assert('Delimiter: 1-Crumb desktop has separator after crumb 1 before current title', $sep1->length === 1 && trim($sep1->item(0)->textContent) === '/');

        // Case B: 3 crumbs + current title -> separators after crumb 1, 2, and 3
        $args3 = [
            'breadcrumb_parts' => [
                ['label' => 'Trang chủ', 'url' => '/'],
                ['label' => 'BĐS', 'url' => '/bds/'],
                ['label' => 'Căn hộ', 'url' => '/can-ho/']
            ],
            'current_label' => 'Sky Villa',
            'prefix_class' => 'test-bds',
        ];
        $html3 = $this->renderBreadcrumb($args3);
        $xpath3 = $this->parseDOM($html3);

        $separatorsDesktop = $xpath3->query('//ol[contains(@class, "test-bds__breadcrumb-list--desktop")]//span[contains(@class, "breadcrumb-separator")]');
        $this->assert('Delimiter: Desktop has exactly 3 separators for 3 crumbs + current title', $separatorsDesktop->length === 3);

        $sepLastDesktop = $xpath3->query('//ol[contains(@class, "test-bds__breadcrumb-list--desktop")]/li[3]/span[contains(@class, "breadcrumb-separator")]');
        $this->assert('Delimiter: Desktop crumb 3 has separator "/" immediately before current title', $sepLastDesktop->length === 1 && trim($sepLastDesktop->item(0)->textContent) === '/');

        // Check mobile delimiter before current title
        $separatorsMobile = $xpath3->query('//ol[contains(@class, "test-bds__breadcrumb-list--mobile")]//span[contains(@class, "breadcrumb-separator")]');
        $this->assert('Delimiter: Mobile list has separators between mobile crumbs', $separatorsMobile->length === 3);
        $sepLastMobile = $xpath3->query('//ol[contains(@class, "test-bds__breadcrumb-list--mobile")]/li[3]/span[contains(@class, "breadcrumb-separator")]');
        $this->assert('Delimiter: Mobile crumb 3 has separator "/" immediately before current title', $sepLastMobile->length === 1 && trim($sepLastMobile->item(0)->textContent) === '/');

        // Case C: When current_label is empty, NO trailing separator after the last crumb
        $argsEmptyCurrent = [
            'breadcrumb_parts' => [
                ['label' => 'Trang chủ', 'url' => '/'],
                ['label' => 'Bất động sản', 'url' => '/bds/']
            ],
            'current_label' => '',
            'prefix_class' => 'test-bds',
        ];
        $htmlEmpty = $this->renderBreadcrumb($argsEmptyCurrent);
        $xpathEmpty = $this->parseDOM($htmlEmpty);

        $separatorsEmpty = $xpathEmpty->query('//ol[contains(@class, "test-bds__breadcrumb-list--desktop")]//span[contains(@class, "breadcrumb-separator")]');
        $this->assert('Delimiter: When current_label is empty, exactly 1 separator between crumbs (no trailing separator)', $separatorsEmpty->length === 1);
        $sepTrailing = $xpathEmpty->query('//ol[contains(@class, "test-bds__breadcrumb-list--desktop")]/li[2]/span[contains(@class, "breadcrumb-separator")]');
        $this->assert('Delimiter: No trailing separator on final crumb when current_label is empty', $sepTrailing->length === 0);
    }

    private function testMobileDisappearancePreventionWhenLessThan3(): void {
        echo "\n--- Scenario 6: Mobile Disappearance Prevention (< 3 crumbs) ---\n";

        // When count < 3 (e.g. 1 crumb or 2 crumbs), batdongsan_get_mobile_breadcrumb returns null.
        // Desktop <ol> MUST NOT contain 'max-md:hidden'.
        // If 'max-md:hidden' were present, mobile users would see nothing at all!

        // Test with 1 crumb:
        $args1 = [
            'breadcrumb_parts' => [
                ['label' => 'Trang chủ', 'url' => '/']
            ],
            'current_label' => 'Test Post',
            'prefix_class' => 'test-bds',
        ];
        $html1 = $this->renderBreadcrumb($args1);
        $xpath1 = $this->parseDOM($html1);

        $desktopOl1 = $xpath1->query('//ol[contains(@class, "test-bds__breadcrumb-list--desktop")]')->item(0);
        $classes1 = $desktopOl1 ? $desktopOl1->getAttribute('class') : '';
        $this->assert('Mobile Protection (1 crumb): Desktop <ol> does NOT contain max-md:hidden', !str_contains($classes1, 'max-md:hidden'), "classes: $classes1");

        $mobileOl1 = $xpath1->query('//ol[contains(@class, "test-bds__breadcrumb-list--mobile")]');
        $this->assert('Mobile Protection (1 crumb): Mobile <ol> is NOT rendered when count < 3', $mobileOl1->length === 0);

        // Test with 2 crumbs:
        $args2 = [
            'breadcrumb_parts' => [
                ['label' => 'Trang chủ', 'url' => '/'],
                ['label' => 'Bất động sản', 'url' => '/bds/']
            ],
            'current_label' => 'Test Post 2',
            'prefix_class' => 'test-bds',
        ];
        $html2 = $this->renderBreadcrumb($args2);
        $xpath2 = $this->parseDOM($html2);

        $desktopOl2 = $xpath2->query('//ol[contains(@class, "test-bds__breadcrumb-list--desktop")]')->item(0);
        $classes2 = $desktopOl2 ? $desktopOl2->getAttribute('class') : '';
        $this->assert('Mobile Protection (2 crumbs): Desktop <ol> does NOT contain max-md:hidden', !str_contains($classes2, 'max-md:hidden'), "classes: $classes2");

        $mobileOl2 = $xpath2->query('//ol[contains(@class, "test-bds__breadcrumb-list--mobile")]');
        $this->assert('Mobile Protection (2 crumbs): Mobile <ol> is NOT rendered when count < 3', $mobileOl2->length === 0);
    }

    private function testEdgeCasesAndSecurity(): void {
        echo "\n--- Scenario 7: Edge Cases, Security & Robustness ---\n";

        // 7.1 XSS in crumb label and URL
        $argsXSS = [
            'breadcrumb_parts' => [
                ['label' => 'Trang <script>alert("xss")</script> & Co', 'url' => 'https://batdongsanhanoi.net.vn/?param=1&foo="><script>']
            ],
            'current_label' => 'Penthouse <b onmouseover="alert(1)">Luxury</b>',
            'prefix_class' => 'test-bds',
        ];
        $htmlXSS = $this->renderBreadcrumb($argsXSS);

        $this->assert('Security: Script tags in label escaped via esc_html', !str_contains($htmlXSS, '<script>alert("xss")</script>'));
        $this->assert('Security: XSS in current_label escaped', !str_contains($htmlXSS, '<b onmouseover'));
        $this->assert('Security: HTML entities rendered safely', str_contains($htmlXSS, 'Trang &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt; &amp; Co'));

        // 7.2 Crumb without URL (e.g. intermediate text-only step)
        $argsNoUrl = [
            'breadcrumb_parts' => [
                ['label' => 'Trang chủ', 'url' => '/'],
                ['label' => 'Phân khu A', 'url' => ''], // No URL
            ],
            'current_label' => 'Căn 01',
            'prefix_class' => 'test-bds',
        ];
        $htmlNoUrl = $this->renderBreadcrumb($argsNoUrl);
        $xpathNoUrl = $this->parseDOM($htmlNoUrl);

        $noUrlItem = $xpathNoUrl->query('//ol[@itemscope]//li[2]');
        $hasAnchor = $xpathNoUrl->query('//ol[@itemscope]//li[2]//a')->length > 0;
        $nameSpan = $xpathNoUrl->query('//ol[@itemscope]//li[2]//span[@itemprop="name"]')->item(0)?->nodeValue;

        $this->assert('Robustness: Crumb without URL does not render <a> tag', !$hasAnchor);
        $this->assert('Robustness: Crumb without URL preserves itemprop="name"', $nameSpan === 'Phân khu A');

        // 7.3 Empty breadcrumb_parts returns empty output
        $emptyHtml = $this->renderBreadcrumb(['breadcrumb_parts' => []]);
        $this->assert('Robustness: Empty breadcrumb_parts returns nothing without fatal error', trim($emptyHtml) === '');

        // 7.4 Custom prefix_class and no_order flag
        $customArgs = [
            'breadcrumb_parts' => [['label' => 'Home', 'url' => '/']],
            'current_label' => 'Item',
            'prefix_class' => 'my-custom-prefix',
            'no_order' => true,
        ];
        $customHtml = $this->renderBreadcrumb($customArgs);
        $this->assert('Flexibility: Custom prefix_class applied to classes', str_contains($customHtml, 'my-custom-prefix__breadcrumb-list'));
        $this->assert('Flexibility: no_order suppresses order-1 utility class', !str_contains($customHtml, 'order-1'));
    }
}

$harness = new BreadcrumbStressHarness();
$harness->run();
