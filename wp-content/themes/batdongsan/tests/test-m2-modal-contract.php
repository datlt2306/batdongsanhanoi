<?php

/**
 * Empirical Stress Test Harness: Mobile Header Filter Modal Contracts
 * 
 * Verifies:
 * 1. template-parts/filter-header-modal-khu-vuc.php
 * 2. template-parts/filter-header-modal-loai-hinh.php
 * 3. template-parts/filter-header-modal-khoang-gia.php
 * 
 * Asserts 100% presence and correct wiring of:
 * - data-filter-header-modal
 * - data-filter-header-modal-trigger
 * - data-filter-header-modal-panel
 * - data-filter-header-modal-id
 * - data-value (on .filter-header-modal-option)
 * - data-filter-header-modal-apply
 * - data-filter-header-modal-reset
 * - data-filter-header-modal-backdrop
 * - data-filter-header-modal-close
 * - First <span> child of trigger button holds label text (js/main.js:3319, 3336 DOM contract)
 * - Chevron <svg> is NOT inside first <span>, preventing label overwrite from wiping out the icon
 * - Active state attributes (aria-selected, primary styling tokens)
 * 
 * @package BatDongSan\Tests
 */

require_once __DIR__ . '/bootstrap.php';

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = []) {
        if (is_object($args)) {
            $r = get_object_vars($args);
        } elseif (is_array($args)) {
            $r = &$args;
        } else {
            $r = [];
        }
        if (is_array($defaults)) {
            return array_merge($defaults, $r);
        }
        return $r;
    }
}

class ModalContractStressHarness {
    private int $passes = 0;
    private int $failures = 0;
    private array $errors = [];

    public function run(): void {
        echo "\n================================================================================\n";
        echo "   MILESTONE 2 EMPIRICAL STRESS TEST: FILTER MODALS CONTRACT HARNESS\n";
        echo "================================================================================\n\n";

        $this->testKhuVucModal();
        $this->testLoaiHinhModal();
        $this->testKhoangGiaModal();
        $this->testJsDomTraversalContract();

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
            echo "  [PASS] ALL MODAL CONTRACT EMPIRICAL STRESS TESTS PASSED SUCCESSFULLY (100%)\n\n";
        }
    }

    private function renderTemplate(string $template, array $args = []): string {
        ob_start();
        require dirname(__DIR__) . '/template-parts/' . $template . '.php';
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

    private function testKhuVucModal(): void {
        echo "--- Suite 1: Khu Vực Modal (filter-header-modal-khu-vuc.php) ---\n";

        $sampleOptions = [
            ''            => 'Toàn Hà Nội',
            'cau-giay'    => 'Cầu Giấy',
            'tay-ho'      => 'Tây Hồ',
            'nam-tu-liem' => 'Nam Từ Liêm',
        ];

        // 1. Default render (no filter selected)
        $htmlDefault = $this->renderTemplate('filter-header-modal-khu-vuc', [
            'current_khu_vuc' => '',
            'area_options'    => $sampleOptions,
        ]);
        $xpDefault = $this->parseDOM($htmlDefault);

        // Required attribute 1: data-filter-header-modal="khu-vuc"
        $wrapper = $xpDefault->query('//*[@data-filter-header-modal="khu-vuc"]');
        $this->assert('KhuVuc: data-filter-header-modal="khu-vuc" present on root', $wrapper->length === 1);

        // Required attribute 2: data-filter-header-modal-trigger
        $trigger = $xpDefault->query('//*[@data-filter-header-modal="khu-vuc"]//button[@data-filter-header-modal-trigger]');
        $this->assert('KhuVuc: data-filter-header-modal-trigger button present', $trigger->length === 1);

        // Required contract: first <span> child of trigger button holds label text
        $triggerFirstSpan = $xpDefault->query('//*[@data-filter-header-modal="khu-vuc"]//button[@data-filter-header-modal-trigger]//span[1]')->item(0);
        $this->assert('KhuVuc: Trigger button has first <span> child', $triggerFirstSpan !== null);
        $this->assert('KhuVuc: Trigger first <span> holds default label "Toàn Hà Nội"', trim($triggerFirstSpan?->nodeValue ?? '') === 'Toàn Hà Nội');

        // Required attribute 3: data-filter-header-modal-panel
        $panel = $xpDefault->query('//*[@data-filter-header-modal-panel]');
        $this->assert('KhuVuc: data-filter-header-modal-panel present', $panel->length === 1);

        // Required attribute 4: data-filter-header-modal-id="khu-vuc" on panel
        $panelId = $xpDefault->query('//*[@data-filter-header-modal-panel and @data-filter-header-modal-id="khu-vuc"]');
        $this->assert('KhuVuc: data-filter-header-modal-id="khu-vuc" present on panel', $panelId->length === 1);

        // Required attribute 5: data-value on option buttons
        $optionBtns = $xpDefault->query('//button[contains(@class, "filter-header-modal-option") and @data-value]');
        $this->assert('KhuVuc: Exactly 4 options with data-value present', $optionBtns->length === 4);

        $values = [];
        foreach ($optionBtns as $btn) {
            $values[] = $btn->getAttribute('data-value');
        }
        $this->assert('KhuVuc: Option data-value list matches expected', $values === ['', 'cau-giay', 'tay-ho', 'nam-tu-liem'], json_encode($values));

        // Required attribute 6: data-filter-header-modal-apply
        $apply = $xpDefault->query('//button[@data-filter-header-modal-apply]');
        $this->assert('KhuVuc: data-filter-header-modal-apply present', $apply->length === 1);

        // Required attribute 7: data-filter-header-modal-reset
        $reset = $xpDefault->query('//button[@data-filter-header-modal-reset]');
        $this->assert('KhuVuc: data-filter-header-modal-reset present', $reset->length === 1);

        // Lifecycle controls: backdrop and close button
        $backdrop = $xpDefault->query('//*[@data-filter-header-modal-backdrop]');
        $this->assert('KhuVuc: data-filter-header-modal-backdrop present', $backdrop->length === 1);
        $closeBtn = $xpDefault->query('//button[@data-filter-header-modal-close]');
        $this->assert('KhuVuc: data-filter-header-modal-close button present', $closeBtn->length === 1);

        // 2. Filtered render (e.g. Cầu Giấy selected)
        $htmlFiltered = $this->renderTemplate('filter-header-modal-khu-vuc', [
            'current_khu_vuc' => 'cau-giay',
            'area_options'    => $sampleOptions,
        ]);
        $xpFiltered = $this->parseDOM($htmlFiltered);

        $filteredSpan = $xpFiltered->query('//*[@data-filter-header-modal="khu-vuc"]//button[@data-filter-header-modal-trigger]//span[1]')->item(0);
        $this->assert('KhuVuc [Filtered]: Trigger first <span> holds selected label "Cầu Giấy"', trim($filteredSpan?->nodeValue ?? '') === 'Cầu Giấy');

        $activeOpt = $xpFiltered->query('//button[@data-value="cau-giay" and @aria-selected="true"]');
        $this->assert('KhuVuc [Filtered]: Cầu Giấy option has aria-selected="true"', $activeOpt->length === 1);
        $this->assert('KhuVuc [Filtered]: Cầu Giấy option has active primary color classes', str_contains($activeOpt->item(0)->getAttribute('class'), 'bg-primary/10 text-primary'));
    }

    private function testLoaiHinhModal(): void {
        echo "\n--- Suite 2: Loại Hình Modal (filter-header-modal-loai-hinh.php) ---\n";

        $mockTerms = [
            (object) ['term_id' => 33, 'name' => 'Căn hộ chung cư', 'slug' => 'can-ho-chung-cu'],
            (object) ['term_id' => 34, 'name' => 'Biệt thự liền kề', 'slug' => 'biet-thu-lien-ke'],
            (object) ['term_id' => 35, 'name' => 'Nhà mặt phố', 'slug' => 'nha-mat-pho'],
        ];

        // 1. Default render
        $htmlDefault = $this->renderTemplate('filter-header-modal-loai-hinh', [
            'current_type' => '0',
            'type_terms'   => $mockTerms,
        ]);
        $xpDefault = $this->parseDOM($htmlDefault);

        // Required attribute 1: data-filter-header-modal="loai-hinh"
        $wrapper = $xpDefault->query('//*[@data-filter-header-modal="loai-hinh"]');
        $this->assert('LoaiHinh: data-filter-header-modal="loai-hinh" present on root', $wrapper->length === 1);

        // Required attribute 2: data-filter-header-modal-trigger
        $trigger = $xpDefault->query('//*[@data-filter-header-modal="loai-hinh"]//button[@data-filter-header-modal-trigger]');
        $this->assert('LoaiHinh: data-filter-header-modal-trigger present', $trigger->length === 1);

        // Required contract: first <span> child of trigger button holds label text
        $triggerFirstSpan = $xpDefault->query('//*[@data-filter-header-modal="loai-hinh"]//button[@data-filter-header-modal-trigger]//span[1]')->item(0);
        $this->assert('LoaiHinh: Trigger first <span> holds default label "Tất cả loại hình"', trim($triggerFirstSpan?->nodeValue ?? '') === 'Tất cả loại hình');

        // Required attribute 3 & 4: panel & id="loai-hinh"
        $panel = $xpDefault->query('//*[@data-filter-header-modal-panel and @data-filter-header-modal-id="loai-hinh"]');
        $this->assert('LoaiHinh: data-filter-header-modal-panel with id="loai-hinh" present', $panel->length === 1);

        // Required attribute 5: data-value on option buttons (1 "Tất cả" + 3 terms = 4 options)
        $optionBtns = $xpDefault->query('//button[contains(@class, "filter-header-modal-option") and @data-value]');
        $this->assert('LoaiHinh: Exactly 4 options with data-value present', $optionBtns->length === 4);

        $defaultActiveOpt = $xpDefault->query('//button[@data-value="0" and @aria-selected="true"]');
        $this->assert('LoaiHinh: Option "0" (Tất cả) has aria-selected="true" by default', $defaultActiveOpt->length === 1);

        // Required attribute 6 & 7: apply & reset
        $apply = $xpDefault->query('//button[@data-filter-header-modal-apply]');
        $reset = $xpDefault->query('//button[@data-filter-header-modal-reset]');
        $this->assert('LoaiHinh: data-filter-header-modal-apply present', $apply->length === 1);
        $this->assert('LoaiHinh: data-filter-header-modal-reset present', $reset->length === 1);

        // 2. Filtered render with term_id = 34
        $htmlFiltered = $this->renderTemplate('filter-header-modal-loai-hinh', [
            'current_type' => '34',
            'type_terms'   => $mockTerms,
        ]);
        $xpFiltered = $this->parseDOM($htmlFiltered);

        $filteredSpan = $xpFiltered->query('//*[@data-filter-header-modal="loai-hinh"]//button[@data-filter-header-modal-trigger]//span[1]')->item(0);
        $this->assert('LoaiHinh [Filtered]: Trigger first <span> holds "Biệt thự liền kề"', trim($filteredSpan?->nodeValue ?? '') === 'Biệt thự liền kề');

        $activeTermOpt = $xpFiltered->query('//button[@data-value="34" and @aria-selected="true"]');
        $this->assert('LoaiHinh [Filtered]: Term 34 option has aria-selected="true"', $activeTermOpt->length === 1);

        // 3. Filtered render with term slug resolution fallback
        $htmlSlug = $this->renderTemplate('filter-header-modal-loai-hinh', [
            'current_type'      => '0',
            'current_type_slug' => 'can-ho-chung-cu',
            'type_terms'        => $mockTerms,
        ]);
        $xpSlug = $this->parseDOM($htmlSlug);
        $slugSpan = $xpSlug->query('//*[@data-filter-header-modal="loai-hinh"]//button[@data-filter-header-modal-trigger]//span[1]')->item(0);
        $this->assert('LoaiHinh [Slug Fallback]: Trigger label resolves to "Căn hộ chung cư"', trim($slugSpan?->nodeValue ?? '') === 'Căn hộ chung cư');
    }

    private function testKhoangGiaModal(): void {
        echo "\n--- Suite 3: Khoảng Giá Modal (filter-header-modal-khoang-gia.php) ---\n";

        $mockRanges = [
            '0'         => 'Tất cả khoảng giá',
            '0-2000'    => 'Dưới 2 tỷ',
            '2000-3000' => '2 - 3 tỷ',
            '3000-5000' => '3 - 5 tỷ',
            '10000'     => 'Trên 10 tỷ',
        ];

        // 1. Default render
        $htmlDefault = $this->renderTemplate('filter-header-modal-khoang-gia', [
            'current_price' => '',
            'price_ranges'  => $mockRanges,
        ]);
        $xpDefault = $this->parseDOM($htmlDefault);

        // Required attribute 1: data-filter-header-modal="khoang-gia"
        $wrapper = $xpDefault->query('//*[@data-filter-header-modal="khoang-gia"]');
        $this->assert('KhoangGia: data-filter-header-modal="khoang-gia" present on root', $wrapper->length === 1);

        // Required attribute 2: data-filter-header-modal-trigger
        $trigger = $xpDefault->query('//*[@data-filter-header-modal="khoang-gia"]//button[@data-filter-header-modal-trigger]');
        $this->assert('KhoangGia: data-filter-header-modal-trigger present', $trigger->length === 1);

        // Required contract: first <span> child of trigger button holds label text
        $triggerFirstSpan = $xpDefault->query('//*[@data-filter-header-modal="khoang-gia"]//button[@data-filter-header-modal-trigger]//span[1]')->item(0);
        $this->assert('KhoangGia: Trigger first <span> holds default label "Tất cả khoảng giá"', trim($triggerFirstSpan?->nodeValue ?? '') === 'Tất cả khoảng giá');

        // Required attribute 3 & 4: panel & id="khoang-gia"
        $panel = $xpDefault->query('//*[@data-filter-header-modal-panel and @data-filter-header-modal-id="khoang-gia"]');
        $this->assert('KhoangGia: data-filter-header-modal-panel with id="khoang-gia" present', $panel->length === 1);

        // Required attribute 5: data-value on option buttons
        $optionBtns = $xpDefault->query('//button[contains(@class, "filter-header-modal-option") and @data-value]');
        $this->assert('KhoangGia: Exactly 5 price options with data-value present', $optionBtns->length === 5);

        // Required attribute 6 & 7: apply & reset
        $apply = $xpDefault->query('//button[@data-filter-header-modal-apply]');
        $reset = $xpDefault->query('//button[@data-filter-header-modal-reset]');
        $this->assert('KhoangGia: data-filter-header-modal-apply present', $apply->length === 1);
        $this->assert('KhoangGia: data-filter-header-modal-reset present', $reset->length === 1);

        // 2. Filtered render with predefined range '3000-5000'
        $htmlFiltered = $this->renderTemplate('filter-header-modal-khoang-gia', [
            'current_price' => '3000-5000',
            'price_ranges'  => $mockRanges,
        ]);
        $xpFiltered = $this->parseDOM($htmlFiltered);
        $filteredSpan = $xpFiltered->query('//*[@data-filter-header-modal="khoang-gia"]//button[@data-filter-header-modal-trigger]//span[1]')->item(0);
        $this->assert('KhoangGia [Predefined]: Trigger first <span> holds "3 - 5 tỷ"', trim($filteredSpan?->nodeValue ?? '') === '3 - 5 tỷ');

        $activePriceOpt = $xpFiltered->query('//button[@data-value="3000-5000" and @aria-selected="true"]');
        $this->assert('KhoangGia [Predefined]: Option 3000-5000 has aria-selected="true"', $activePriceOpt->length === 1);

        // 3. Filtered render with dynamic unlisted range (e.g. '1500-4500')
        $htmlDynamic = $this->renderTemplate('filter-header-modal-khoang-gia', [
            'current_price' => '1500-4500',
            'price_ranges'  => $mockRanges,
        ]);
        $xpDynamic = $this->parseDOM($htmlDynamic);
        $dynamicSpan = $xpDynamic->query('//*[@data-filter-header-modal="khoang-gia"]//button[@data-filter-header-modal-trigger]//span[1]')->item(0);
        $this->assert('KhoangGia [Dynamic]: Trigger label formatted to "1,5 tỷ - 4,5 tỷ"', trim($dynamicSpan?->nodeValue ?? '') === '1,5 tỷ - 4,5 tỷ');
    }

    private function testJsDomTraversalContract(): void {
        echo "\n--- Suite 4: JS DOM Traversal & Selector Integrity (js/main.js lines 3319, 3336) ---\n";

        // In js/main.js:
        // Line 3319: $wrapper.find("[data-filter-header-modal-trigger] span").first().text(label);
        // Line 3336: $wrapper.find("[data-filter-header-modal-trigger] span").first().text(label);
        //
        // This test simulates the jQuery selector:
        // 1. $wrapper = $('[data-filter-header-modal="kind"]')
        // 2. $spans = $wrapper.find('[data-filter-header-modal-trigger] span')
        // 3. $first = $spans.first()
        // 4. Must ensure that $first is the label element, AND that any <svg> icon is NOT inside $first.

        $modals = [
            'khu-vuc'    => $this->renderTemplate('filter-header-modal-khu-vuc', ['current_khu_vuc' => 'cau-giay', 'area_options' => ['cau-giay' => 'Cầu Giấy']]),
            'loai-hinh'   => $this->renderTemplate('filter-header-modal-loai-hinh', ['current_type' => '33', 'type_terms' => [(object)['term_id' => 33, 'name' => 'Căn hộ chung cư']]]),
            'khoang-gia' => $this->renderTemplate('filter-header-modal-khoang-gia', ['current_price' => '3000-5000', 'price_ranges' => ['3000-5000' => '3 - 5 tỷ']]),
        ];

        foreach ($modals as $kind => $html) {
            $xp = $this->parseDOM($html);

            // Find all spans inside trigger
            $spans = $xp->query("//*[@data-filter-header-modal='$kind']//*[@data-filter-header-modal-trigger]//span");
            $this->assert("Contract [$kind]: Trigger contains at least 1 <span>", $spans->length >= 1);

            $firstSpan = $spans->item(0);
            $this->assert("Contract [$kind]: First <span> is not null", $firstSpan !== null);

            // Verify firstSpan does NOT contain an SVG child
            $svgInsideFirstSpan = $xp->query('.//svg', $firstSpan);
            $this->assert("Contract [$kind]: First <span> contains ZERO <svg> elements (safe from .text(label) obliteration)", $svgInsideFirstSpan->length === 0);

            // Verify the trigger button contains an SVG chevron sibling
            $triggerSvg = $xp->query("//*[@data-filter-header-modal='$kind']//*[@data-filter-header-modal-trigger]//svg");
            $this->assert("Contract [$kind]: Trigger button has dedicated chevron <svg> icon outside first <span>", $triggerSvg->length === 1);

            // Verify panel has data-filter-header-modal-id="$kind" exactly matching wrapper
            $panel = $xp->query("//*[@data-filter-header-modal-panel and @data-filter-header-modal-id='$kind']");
            $this->assert("Contract [$kind]: Panel data-filter-header-modal-id matches wrapper kind '$kind'", $panel->length === 1);
        }
    }
}

$harness = new ModalContractStressHarness();
$harness->run();
