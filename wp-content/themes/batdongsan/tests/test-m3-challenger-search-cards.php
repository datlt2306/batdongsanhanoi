<?php
/**
 * Milestone 3 Challenger 2: Empirical Stress Test Suite for Search Form & Cards
 * 
 * Verifies:
 * 1. template-parts/search-form-unified.php:
 *    - Container visibility (no 'hidden' class on container)
 *    - 3 transaction tabs ("Bán", "Cho thuê", "Dự án")
 *    - Form action switching & parameter sets (/bat-dong-san/ vs /du-an-bat-dong-san/)
 *    - Location dropdown binding to name="khu_vuc"
 * 2. template-parts/card-transfer.php & card-project-featured.php:
 *    - Valid HTML DOM parsing with zero unclosed tags across diverse state permutations
 *    - Partner logo helper batdongsan_get_partner_logo_by_name() usage and zero raw WP_Query
 *    - Full compatibility with Area 5 of adversarial-r1-r2-stress-test.php
 * 3. Color Token Audit:
 *    - Absolute zero occurrences of legacy red (#b20000, #a60b0b, #8b0000)
 * 4. JS Controller Contract:
 *    - initUnifiedSearchForm() in js/main.js
 * 
 * @package BatDongSan
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../inc/helpers/project.php';
require_once __DIR__ . '/../inc/services/queries.php';
require_once __DIR__ . '/../inc/services/price-ranges.php';

// Polyfills for test runner
if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = []) {
        if (is_object($args)) {
            $r = get_object_vars($args);
        } elseif (is_array($args)) {
            $r = &$args;
        } else {
            parse_str((string) $args, $r);
        }
        if (is_array($defaults)) {
            return array_merge($defaults, $r);
        }
        return $r;
    }
}

if (!function_exists('wp_get_attachment_image_url')) {
    function wp_get_attachment_image_url(int $attachment_id, string $size = 'thumbnail'): string|false {
        return "https://example.com/wp-content/uploads/img-{$attachment_id}-{$size}.jpg";
    }
}

if (!function_exists('wp_get_attachment_image')) {
    function wp_get_attachment_image(int $attachment_id, string $size = 'thumbnail', bool $icon = false, array $attr = []): string {
        $class = esc_attr($attr['class'] ?? '');
        $alt = esc_attr($attr['alt'] ?? 'Image');
        return '<img src="https://example.com/wp-content/uploads/img-' . $attachment_id . '.jpg" class="' . $class . '" alt="' . $alt . '">';
    }
}

if (!function_exists('get_terms')) {
    function get_terms($args = []) {
        return [
            (object)['term_id' => 10, 'name' => 'Căn hộ chung cư', 'slug' => 'can-ho-chung-cu'],
            (object)['term_id' => 11, 'name' => 'Biệt thự liền kề', 'slug' => 'biet-thu-lien-ke'],
            (object)['term_id' => 12, 'name' => 'Nhà mặt phố', 'slug' => 'nha-mat-pho'],
        ];
    }
}

use BDS\Tests\Framework\MockWordPress;

class M3SearchAndCardsEmpiricalStressTest {

    private int $passes = 0;
    private int $failures = 0;
    private array $errors = [];
    private string $themeDir;

    public function __construct() {
        $this->themeDir = dirname(__DIR__);
    }

    private function setPostMeta(int $postId, string $key, mixed $value): void {
        MockWordPress::$postmeta[$postId][$key] = [$value];
    }

    private function assert(bool $condition, string $message, string $details = ''): void {
        if ($condition) {
            $this->passes++;
            echo "  ✔ PASS: {$message}\n";
        } else {
            $this->failures++;
            $err = "  ✖ FAIL: {$message}" . ($details ? " -> {$details}" : '');
            $this->errors[] = $err;
            echo "{$err}\n";
        }
    }

    /**
     * Parse HTML and verify zero unclosed or mismatched tags using strict token stack.
     *
     * @param string $html
     * @param string $contextName
     * @return array [bool $valid, array $errors]
     */
    private function verifyHtmlTagBalance(string $html, string $contextName): array {
        // Strip comments
        $cleanHtml = preg_replace('/<!--.*?-->/s', '', $html);

        // Self-closing / void HTML elements (do not require closing tags)
        $voidElements = [
            'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 
            'link', 'meta', 'param', 'source', 'track', 'wbr'
        ];

        // SVG elements that are frequently self-closing
        $svgElements = [
            'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'stop', 'use'
        ];

        $stack = [];
        $errors = [];

        // Match all tags: <tag ...> or </tag>
        // Note: handles attributes with quotes, multiline attributes
        preg_match_all('/<(\/)?([a-zA-Z0-9\-]+)([^>]*)>/s', $cleanHtml, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            $fullTag   = $match[0][0];
            $isClosing = !empty($match[1][0]);
            $tagName   = strtolower($match[2][0]);
            $attrs     = $match[3][0] ?? '';
            $offset    = $match[0][1];

            // Check if self-closing tag (ends with '/>' or is in void elements)
            $isSelfClosing = (substr(rtrim($attrs), -1) === '/') || 
                             (!$isClosing && in_array($tagName, $voidElements, true)) ||
                             (!$isClosing && in_array($tagName, $svgElements, true) && substr(rtrim($attrs), -1) === '/');

            if ($isSelfClosing) {
                continue;
            }

            if (!$isClosing) {
                // Opening tag
                $stack[] = [
                    'tag'    => $tagName,
                    'offset' => $offset,
                    'raw'    => substr($fullTag, 0, 50)
                ];
            } else {
                // Closing tag
                if (empty($stack)) {
                    $errors[] = "Unexpected closing tag </{$tagName}> at byte {$offset} with empty open stack in {$contextName}";
                    continue;
                }

                $top = array_pop($stack);
                if ($top['tag'] !== $tagName) {
                    $errors[] = "Tag mismatch at byte {$offset} in {$contextName}: closed </{$tagName}> but expected </{$top['tag']}> (opened at byte {$top['offset']})";
                }
            }
        }

        if (!empty($stack)) {
            foreach ($stack as $unclosed) {
                $errors[] = "Unclosed tag <{$unclosed['tag']}> opened at byte {$unclosed['offset']} ({$unclosed['raw']}) in {$contextName}";
            }
        }

        return [empty($errors), $errors];
    }

    public function runAll(): bool {
        echo "================================================================================\n";
        echo "   MILESTONE 3 CHALLENGER 2: EMPIRICAL SEARCH FORM & CARD STRESS TEST           \n";
        echo "================================================================================\n\n";

        $startTime = microtime(true);

        $this->testLegacyRedColorPurge();
        $this->testSearchFormUnifiedStaticCode();
        $this->testSearchFormUnifiedDynamicRendering();
        $this->testSearchFormJsControllerContract();
        $this->testCardTransferHtmlDomAndBadges();
        $this->testCardProjectFeaturedHtmlDomAndPartnerLogo();
        $this->testAdversarialArea5PartnerLogoCaching();

        $duration = round(microtime(true) - $startTime, 4);

        echo "\n================================================================================\n";
        echo " SUMMARY: {$this->passes} Passed, {$this->failures} Failed (Duration: {$duration}s)\n";
        echo "================================================================================\n\n";

        if ($this->failures > 0) {
            echo "FAILED ASSERTIONS:\n";
            foreach ($this->errors as $err) {
                echo "{$err}\n";
            }
            return false;
        }

        echo "  [VERDICT: APPROVE] ALL M3 EMPIRICAL CHALLENGER ASSERTIONS PASSED WITH 100% SUCCESS!\n\n";
        return true;
    }

    /**
     * Test 1: Absolute zero occurrences of legacy red in target files
     */
    private function testLegacyRedColorPurge(): void {
        echo "▶ Suite 1: Legacy Red Color Cleansing Audit (#b20000, #a60b0b, #8b0000)\n";

        $targetFiles = [
            'search-form-unified.php'   => $this->themeDir . '/template-parts/search-form-unified.php',
            'card-transfer.php'         => $this->themeDir . '/template-parts/card-transfer.php',
            'card-project-featured.php' => $this->themeDir . '/template-parts/card-project-featured.php',
        ];

        $redPatterns = ['/#b20000/i', '/#a60b0b/i', '/#8b0000/i', '/text-red-600/i', '/focus:ring-red-100/i'];

        foreach ($targetFiles as $name => $path) {
            $this->assert(file_exists($path), "File exists: {$name}");
            $content = file_get_contents($path);

            $foundRed = false;
            $matchesFound = [];
            foreach ($redPatterns as $pat) {
                if (preg_match_all($pat, $content, $m)) {
                    $foundRed = true;
                    $matchesFound[] = implode(', ', $m[0]);
                }
            }

            $this->assert(!$foundRed, "{$name} contains 0 occurrences of legacy red", implode('; ', $matchesFound));
        }
    }

    /**
     * Test 2: Static Code Verification of template-parts/search-form-unified.php
     */
    private function testSearchFormUnifiedStaticCode(): void {
        echo "\n▶ Suite 2: Search Form Unified Static Structure & Contract Inspection\n";

        $filePath = $this->themeDir . '/template-parts/search-form-unified.php';
        $content = file_get_contents($filePath);

        // 2.1 Assert container does not have 'hidden' class
        // Check root wrapper and container
        $hasContainerHidden = (bool) preg_match('/class="[^"]*unified-search-wrapper[^"]*\bhidden\b/i', $content) ||
                              (bool) preg_match('/class="[^"]*unified-search-container[^"]*\bhidden\b/i', $content) ||
                              (bool) preg_match('/class="[^"]*max-w-\[1200px\][^"]*\bhidden\b/i', $content);
        $this->assert(!$hasContainerHidden, "2.1: Unified search container is NOT suppressed by 'hidden' class");

        // 2.2 Assert all 3 transaction tabs exist
        $hasTabBan     = (bool) preg_match('/data-unified-tab="ban"/i', $content) && str_contains($content, '<span>Bán</span>');
        $hasTabChoThue = (bool) preg_match('/data-unified-tab="cho-thue"/i', $content) && str_contains($content, '<span>Cho thuê</span>');
        $hasTabDuAn    = (bool) preg_match('/data-unified-tab="du-an"/i', $content) && str_contains($content, '<span>Dự án</span>');

        $this->assert($hasTabBan, "2.2a: Transaction tab 'Bán' exists with data-unified-tab='ban'");
        $this->assert($hasTabChoThue, "2.2b: Transaction tab 'Cho thuê' exists with data-unified-tab='cho-thue'");
        $this->assert($hasTabDuAn, "2.2c: Transaction tab 'Dự án' exists with data-unified-tab='du-an'");

        // 2.3 & 2.4 Extract each button block
        preg_match('/<button\b[\s\S]*?data-unified-tab="ban"[\s\S]*?<\/button>/i', $content, $mBan);
        $banBlock = $mBan[0] ?? '';
        $this->assert(
            str_contains($banBlock, 'data-action="<?php echo esc_url(home_url(\'/bat-dong-san\')); ?>"') || str_contains($banBlock, '/bat-dong-san'),
            "2.3a: Tab 'Bán' targets destination endpoint /bat-dong-san"
        );
        $this->assert(
            str_contains($banBlock, 'data-hinh-thuc="chuyen-nhuong"'),
            "2.4a: Tab 'Bán' binds data-hinh-thuc='chuyen-nhuong'"
        );

        preg_match('/<button\b[\s\S]*?data-unified-tab="cho-thue"[\s\S]*?<\/button>/i', $content, $mChoThue);
        $choThueBlock = $mChoThue[0] ?? '';
        $this->assert(
            str_contains($choThueBlock, 'data-action="<?php echo esc_url(home_url(\'/bat-dong-san\')); ?>"') || str_contains($choThueBlock, '/bat-dong-san'),
            "2.3b: Tab 'Cho thuê' targets destination endpoint /bat-dong-san"
        );
        $this->assert(
            str_contains($choThueBlock, 'data-hinh-thuc="cho-thue"'),
            "2.4b: Tab 'Cho thuê' binds data-hinh-thuc='cho-thue'"
        );

        preg_match('/<button\b[\s\S]*?data-unified-tab="du-an"[\s\S]*?<\/button>/i', $content, $mDuAn);
        $duAnBlock = $mDuAn[0] ?? '';
        $this->assert(
            str_contains($duAnBlock, 'data-action="<?php echo esc_url(home_url(\'/du-an-bat-dong-san\')); ?>"') || str_contains($duAnBlock, '/du-an-bat-dong-san'),
            "2.3c: Tab 'Dự án' targets destination endpoint /du-an-bat-dong-san"
        );
        $this->assert(
            str_contains($duAnBlock, 'data-hinh-thuc=""'),
            "2.4c: Tab 'Dự án' binds data-hinh-thuc='' (all project types)"
        );

        // 2.5 Assert location dropdown is bound to name="khu_vuc"
        $hasKhuVucHidden = (bool) preg_match('/<input[^>]+name="khu_vuc"[^>]*>/i', $content);
        $hasAreaCollision= (bool) preg_match('/<select[^>]+name="area"[^>]*>|<input[^>]+type="hidden"[^>]+name="area"[^>]*>/i', $content);

        $this->assert($hasKhuVucHidden, "2.5a: Location dropdown binds to hidden input name='khu_vuc'");
        $this->assert(!$hasAreaCollision, "2.5b: No collision with legacy name='area' for location in search form");
    }

    /**
     * Test 3: Dynamic Rendering & HTML DOM Balance of template-parts/search-form-unified.php
     */
    private function testSearchFormUnifiedDynamicRendering(): void {
        echo "\n▶ Suite 3: Search Form Unified Dynamic SSR Rendering & Tag Balance\n";

        $templatePath = $this->themeDir . '/template-parts/search-form-unified.php';

        $scenarios = [
            'Default' => [],
            'Active Tab Cho Thue' => ['current_tab' => 'cho-thue', 'current_hinh_thuc' => 'cho-thue'],
            'Active Tab Du An'    => ['current_tab' => 'du-an', 'form_action' => home_url('/du-an-bat-dong-san')],
            'With Filters'        => [
                'current_name'    => 'Vinhomes Smart City',
                'current_khu_vuc' => 'nam-tu-liem',
                'current_price'   => '3000-5000',
                'current_type'    => '12',
                'show_reset'      => true,
            ],
            'No Tabs and No Area' => ['show_tabs' => false, 'show_area' => false, 'show_reset' => false],
            'With Title Subtitle' => ['title' => 'Tìm kiếm bất động sản', 'subtitle' => 'Khám phá hơn 1,000+ sản phẩm'],
        ];

        foreach ($scenarios as $desc => $scenarioArgs) {
            // Setup args for template part
            $args = $scenarioArgs;

            ob_start();
            include $templatePath;
            $renderedHtml = ob_get_clean();

            $this->assert(!empty($renderedHtml), "3.1 [{$desc}]: Template renders non-empty HTML (" . strlen($renderedHtml) . " bytes)");

            // Verify HTML tag balance
            [$valid, $tagErrors] = $this->verifyHtmlTagBalance($renderedHtml, "search-form-unified [{$desc}]");
            $this->assert($valid, "3.2 [{$desc}]: HTML DOM has zero unclosed/mismatched tags", implode('; ', $tagErrors));

            // Verify form action attribute
            if (($scenarioArgs['current_tab'] ?? '') === 'du-an') {
                $this->assert(
                    str_contains($renderedHtml, 'action="' . home_url('/du-an-bat-dong-san') . '"'),
                    "3.3 [{$desc}]: Form action targets /du-an-bat-dong-san"
                );
            } else {
                $this->assert(
                    str_contains($renderedHtml, 'action="' . home_url('/bat-dong-san') . '"'),
                    "3.3 [{$desc}]: Form action targets /bat-dong-san"
                );
            }

            // Verify DOM element classes
            $this->assert(
                str_contains($renderedHtml, 'unified-search-wrapper') && !preg_match('/class="[^"]*unified-search-wrapper[^"]*\bhidden\b/', $renderedHtml),
                "3.4 [{$desc}]: unified-search-wrapper exists without hidden class"
            );

            // 3.5 Verify initial SSR value of hinh_thuc matches active tab
            preg_match('/<input[^>]+name="hinh_thuc"[^>]+value="([^"]*)"/i', $renderedHtml, $mHinhThuc);
            $renderedHinhThuc = $mHinhThuc[1] ?? null;

            if (($scenarioArgs['current_tab'] ?? '') === 'du-an' || strpos($scenarioArgs['form_action'] ?? '', 'du-an') !== false) {
                $this->assert(
                    $renderedHinhThuc === '',
                    "3.5 [{$desc}]: Hidden hinh_thuc input value is empty on Tab Dự án (actual: '{$renderedHinhThuc}')"
                );
            } elseif (($scenarioArgs['current_tab'] ?? '') === 'cho-thue') {
                $this->assert(
                    $renderedHinhThuc === 'cho-thue',
                    "3.5 [{$desc}]: Hidden hinh_thuc input initializes to 'cho-thue' on Tab Cho thuê (actual: '{$renderedHinhThuc}')"
                );
            } else {
                $this->assert(
                    $renderedHinhThuc === 'chuyen-nhuong',
                    "3.5 [{$desc}]: Hidden hinh_thuc input initializes to 'chuyen-nhuong' on Tab Bán (actual: '{$renderedHinhThuc}')"
                );
            }
        }
    }

    /**
     * Test 4: Verify JS controller contract in js/main.js
     */
    private function testSearchFormJsControllerContract(): void {
        echo "\n▶ Suite 4: Unified Search Form JS Controller Contract (js/main.js)\n";

        $jsPath = $this->themeDir . '/js/main.js';
        $js = file_get_contents($jsPath);

        // 4.1 Function definition
        $this->assert(str_contains($js, 'function initUnifiedSearchForm()'), "4.1: initUnifiedSearchForm() function defined in js/main.js");

        // 4.2 Tab click handler and action/hinh_thuc synchronization
        $hasTabClick = str_contains($js, '$form.on("click", "[data-unified-tab]"') ||
                       str_contains($js, '$tabs.on("click"') ||
                       str_contains($js, '[data-unified-tab]');
        $this->assert($hasTabClick, "4.2: Tab click listener bound in JS controller");

        // 4.3 Form action updating on tab change
        $this->assert(str_contains($js, '$form.attr("action", actionUrl)'), "4.3: Controller dynamically updates \$form.attr('action', actionUrl)");

        // 4.4 hinh_thuc hidden input updating
        $this->assert(str_contains($js, '$hinhThucInput.val(hinhThuc)'), "4.4: Controller updates hidden input \$hinhThucInput.val(hinhThuc)");

        // 4.5 Dropdown open/close & option click handler
        $hasDropdownHandler = str_contains($js, '[data-unified-trigger]') && str_contains($js, '[data-unified-option]');
        $this->assert($hasDropdownHandler, "4.5: Dropdown trigger and option click handlers implemented");

        // 4.6 Outside click dismissal
        $hasOutsideDismiss = (str_contains($js, '$(document).on("click"') || str_contains($js, '$(document).off("click.unifiedSearch").on("click.unifiedSearch"') || str_contains($js, '$(document).on("click.unifiedSearch"')) && str_contains($js, '[data-unified-dropdown]');
        $this->assert($hasOutsideDismiss, "4.6: Outside click listener dismisses active dropdown panels");

        // 4.7 Escape key dismissal
        $hasEscapeDismiss = str_contains($js, 'e.key === "Escape"');
        $this->assert($hasEscapeDismiss, "4.7: Escape key listener closes dropdown panels");

        // 4.8 Idempotency guard against duplicate event listeners
        $hasIdempotencyGuard = (bool) preg_match('/\$form\.data\(["\']unified-(?:search-)?initialized["\']\)/', $js);
        $this->assert($hasIdempotencyGuard, "4.8: initUnifiedSearchForm() includes idempotency guard via \$form.data('unified-search-initialized')");

        // 4.9 Deduplication of initUnifiedSearchForm() calls on homepage
        $hasHomepageDuplicateCall = false;
        if (preg_match('/if\s*\(\s*pageContext\s*===\s*["\']home["\']\s*\)\s*\{[^}]*?runDeferred\s*\(\s*function\s*\(\s*\)\s*\{([^}]*?)\}/s', $js, $mHome)) {
            $hasHomepageDuplicateCall = str_contains($mHome[1], 'initUnifiedSearchForm');
        }
        $this->assert(!$hasHomepageDuplicateCall, "4.9: initUnifiedSearchForm() is not duplicated inside pageContext === 'home' runDeferred");

        // 4.10 bfcache pageshow event handler exists to re-enable disabled inputs
        $hasBfcachePageshow = str_contains($js, 'pageshow.unifiedSearch') && 
                              str_contains($js, ':disabled') &&
                              str_contains($js, 'prop("disabled", false)');
        $this->assert($hasBfcachePageshow, "4.10: bfcache 'pageshow.unifiedSearch' event handler exists and re-enables disabled form controls");

        // 4.11 Behavioral execution test: initUnifiedSearchForm does not duplicate listeners on repeated calls
        $nodeScript = "
            const fs = require('fs');
            const mainJs = fs.readFileSync('" . addslashes($jsPath) . "', 'utf8');
            const fnMatch = mainJs.match(/function initUnifiedSearchForm\\(\\) \\{([\\s\\S]*?)\\n    \\}/);
            if (!fnMatch) { process.exit(1); }
            let listeners = [];
            let formStore = {};
            let formAttrs = {};
            function createObj(props = {}) {
                return Object.assign({
                    length: 0,
                    find: function() { return createObj(); },
                    closest: function() { return createObj(); },
                    filter: function() { return createObj(); },
                    first: function() { return createObj(); },
                    each: function() { return this; },
                    off: function() { return this; },
                    on: function(event, sel, handler) {
                        listeners.push({ event, sel });
                        return this;
                    },
                    attr: function(k, v) {
                        if (v !== undefined) { formAttrs[k] = v; return this; }
                        return formAttrs[k];
                    },
                    prop: function() { return this; },
                    val: function() { return ''; },
                    addClass: function() { return this; },
                    removeClass: function() { return this; },
                    hasClass: function() { return false; },
                    triggerHandler: function() { return this; },
                    data: function(key, val) {
                        if (val !== undefined) { formStore[key] = val; return this; }
                        return formStore[key];
                    }
                }, props);
            }
            const $ = function(sel) {
                if (sel === '[data-unified-search-form]') return createObj({ length: 1 });
                return createObj();
            };
            $.fn = {};
            const fn = new Function('$', 'jQuery', 'window', 'document', fnMatch[1]);
            fn($, $, { addEventListener: function(){}, on: function(){ return createObj(); }, off: function(){ return createObj(); } }, { on: function(){ return createObj(); }, off: function(){ return createObj(); } });
            const count1 = listeners.length;
            fn($, $, { addEventListener: function(){}, on: function(){ return createObj(); }, off: function(){ return createObj(); } }, { on: function(){ return createObj(); }, off: function(){ return createObj(); } });
            const count2 = listeners.length;
            console.log(JSON.stringify({ count1, count2, pass: count1 > 0 && count1 === count2 }));
        ";
        $nodeOutput = shell_exec('node -e ' . escapeshellarg($nodeScript));
        $res = json_decode(trim($nodeOutput ?? ''), true);
        $this->assert(!empty($res['pass']), "4.11: Behavioral test confirms calling initUnifiedSearchForm() twice produces 0 duplicate listeners (Count 1: " . ($res['count1'] ?? 'null') . ", Count 2: " . ($res['count2'] ?? 'null') . ")");
    }

    /**
     * Test 5: Empirical verify template-parts/card-transfer.php HTML DOM & Badges
     */
    private function testCardTransferHtmlDomAndBadges(): void {
        echo "\n▶ Suite 5: Card Transfer (BĐS) HTML DOM Parsing & Specifications Audit\n";

        $templatePath = $this->themeDir . '/template-parts/card-transfer.php';
        $content = file_get_contents($templatePath);

        // Verify no raw un-cached WP_Query
        $this->assert(!str_contains($content, 'new WP_Query'), "5.1: Zero new WP_Query in card-transfer.php");

        // Test variations of mock post
        $permutations = [
            'Full Listing with Redux Badges' => [
                'post' => (object)[
                    'ID' => 101,
                    'post_title' => 'Biệt thự Đơn lập Vinhomes Riverside 350m2',
                    'post_type' => 'bat-dong-san',
                    'post_status' => 'publish',
                    'post_date' => '2026-10-20 10:00:00',
                ],
                'fields' => [
                    'dia_chi' => 'Khu Hoa Phượng, Vinhomes Riverside, Long Biên',
                    'khoang_gia' => '85 tỷ',
                    'dien_tich' => '350 m²',
                    'gia_tren_met_vuong' => '242 tr/m²',
                    'so_phong_ngu' => '5',
                    'so_phong_ve_sinh' => '6',
                    'phap_ly' => 'so_do',
                    'gallery_nha_dat' => [1001, 1002],
                ],
                'show_badge' => true,
            ],
            'Minimal Listing Null Fields' => [
                'post' => (object)[
                    'ID' => 102,
                    'post_title' => 'Căn hộ Studio Ngoại Giao',
                    'post_type' => 'bat-dong-san',
                    'post_status' => 'publish',
                    'post_date' => '2026-10-21 08:00:00',
                ],
                'fields' => [
                    'dia_chi' => '',
                    'khoang_gia' => '',
                    'dien_tich' => '',
                    'gia_tren_met_vuong' => '',
                    'so_phong_ngu' => '',
                    'so_phong_ve_sinh' => '',
                    'phap_ly' => '',
                    'gallery_nha_dat' => [],
                ],
                'show_badge' => false,
            ],
            'Edge Cases: Zero Bedrooms and WC' => [
                'post' => (object)[
                    'ID' => 103,
                    'post_title' => 'Đất nền phân lô mặt tiền 10m',
                    'post_type' => 'bat-dong-san',
                    'post_status' => 'publish',
                    'post_date' => '2026-10-19 14:00:00',
                ],
                'fields' => [
                    'dia_chi' => 'Đông Anh, Hà Nội',
                    'khoang_gia' => '12.5 tỷ',
                    'dien_tich' => '120 m²',
                    'gia_tren_met_vuong' => '104 tr/m²',
                    'so_phong_ngu' => '0',
                    'so_phong_ve_sinh' => '0',
                    'phap_ly' => 'dang_ky_quyen_su_dung_dat',
                    'gallery_nha_dat' => [1003],
                ],
                'show_badge' => true,
            ],
            'Legal Status Variations: So Hong' => [
                'post' => (object)[
                    'ID' => 104,
                    'post_title' => 'Penthouse Duplex Keangnam Landmark',
                    'post_type' => 'bat-dong-san',
                    'post_status' => 'publish',
                    'post_date' => '2026-10-18 12:00:00',
                ],
                'fields' => [
                    'dia_chi' => 'Phạm Hùng, Nam Từ Liêm',
                    'khoang_gia' => '32 tỷ',
                    'dien_tich' => '280 m²',
                    'gia_tren_met_vuong' => '114 tr/m²',
                    'so_phong_ngu' => '4',
                    'so_phong_ve_sinh' => '4',
                    'phap_ly' => 'so_hong',
                    'gallery_nha_dat' => [1004],
                ],
                'show_badge' => false,
            ],
        ];

        foreach ($permutations as $label => $data) {
            MockWordPress::reset();
            $postObj = $data['post'];
            MockWordPress::$posts[$postObj->ID] = $postObj;
            foreach ($data['fields'] as $k => $v) {
                $this->setPostMeta($postObj->ID, $k, $v);
            }

            $args = [
                'post' => $postObj,
                'show_badge' => $data['show_badge'],
            ];

            ob_start();
            include $templatePath;
            $html = ob_get_clean();

            $this->assert(!empty($html), "5.2 [{$label}]: Card transfer renders HTML (" . strlen($html) . " bytes)");

            // Check zero unclosed tags
            [$valid, $tagErrors] = $this->verifyHtmlTagBalance($html, "card-transfer [{$label}]");
            $this->assert($valid, "5.3 [{$label}]: Zero unclosed tags in card-transfer DOM", implode('; ', $tagErrors));

            // Verify badge state
            if ($data['show_badge']) {
                $this->assert(str_contains($html, 'Nổi bật'), "5.4 [{$label}]: Featured badge rendered when show_badge=true");
            } else {
                $this->assert(!str_contains($html, 'Nổi bật'), "5.4 [{$label}]: Featured badge suppressed when show_badge=false");
            }

            // Verify specs badges
            if (!empty($data['fields']['so_phong_ngu']) && $data['fields']['so_phong_ngu'] !== '0') {
                $this->assert(str_contains($html, $data['fields']['so_phong_ngu'] . ' PN'), "5.5 [{$label}]: Bedroom spec rendered");
            } elseif ($data['fields']['so_phong_ngu'] === '0') {
                $this->assert(!str_contains($html, '0 PN'), "5.5 [{$label}]: '0 PN' suppressed correctly");
            }

            if (!empty($data['fields']['phap_ly'])) {
                $this->assert(
                    str_contains($html, 'Sổ đỏ') || str_contains($html, 'Sổ hồng') || str_contains($html, 'Đang làm sổ'),
                    "5.6 [{$label}]: Legal status badge rendered accurately"
                );
            }
        }
    }

    /**
     * Test 6: Empirical verify template-parts/card-project-featured.php HTML DOM & Partner Logo
     */
    private function testCardProjectFeaturedHtmlDomAndPartnerLogo(): void {
        echo "\n▶ Suite 6: Card Project Featured HTML DOM & Partner Logo Optimization\n";

        $templatePath = $this->themeDir . '/template-parts/card-project-featured.php';
        $content = file_get_contents($templatePath);

        // 6.1 Assert helper function present
        $this->assert(
            str_contains($content, 'batdongsan_get_partner_logo_by_name($chu_dau_tu)'),
            "6.1: batdongsan_get_partner_logo_by_name() helper used in card-project-featured.php"
        );

        // 6.2 Assert zero raw un-cached new WP_Query
        $this->assert(!str_contains($content, 'new WP_Query'), "6.2: Zero raw un-cached new WP_Query in card-project-featured.php");

        $permutations = [
            'Parent Project with Multiple Thumbnails' => [
                'post' => (object)[
                    'ID' => 201,
                    'post_title' => 'Masteri West Heights',
                    'post_type' => 'du-an',
                    'post_status' => 'publish',
                ],
                'fields' => [
                    'trang_thai' => 'dang_mo_ban',
                    'address' => 'Tây Mỗ, Nam Từ Liêm, Hà Nội',
                    'chu_dau_tu' => 'Masterise Homes',
                    'quy_mo' => '4 tòa tháp 38 tầng',
                    'gia_ban_du_kien' => 'Từ 2.5 - 6.5 tỷ',
                    'du_an_cha' => null,
                    'gallery_du_an' => [2001, 2002, 2003, 2004, 2005], // 5 images -> tests +2 overlay
                ],
                'partnerLogoId' => 8888,
            ],
            'Subdivision Project with Fallback Price' => [
                'post' => (object)[
                    'ID' => 202,
                    'post_title' => 'Phân khu The Tonkin',
                    'post_type' => 'du-an',
                    'post_status' => 'publish',
                ],
                'fields' => [
                    'trang_thai' => 'sap_mo_ban',
                    'address' => '',
                    'chu_dau_tu' => '',
                    'quy_mo' => '',
                    'gia_ban_du_kien' => '',
                    'price' => '3.8', // Fallback price
                    'du_an_cha' => (object)['ID' => 201, 'post_title' => 'Masteri West Heights'],
                    'gallery_du_an' => [2001],
                ],
                'partnerLogoId' => null,
            ],
            'Minimal Empty Project' => [
                'post' => (object)[
                    'ID' => 203,
                    'post_title' => 'Dự án Chưa Cập Nhật',
                    'post_type' => 'du-an',
                    'post_status' => 'publish',
                ],
                'fields' => [
                    'trang_thai' => 'dang_cap_nhat',
                    'address' => '',
                    'chu_dau_tu' => '',
                    'quy_mo' => '',
                    'gia_ban_du_kien' => '',
                    'price' => '',
                    'du_an_cha' => null,
                    'gallery_du_an' => [],
                ],
                'partnerLogoId' => null,
            ],
        ];

        foreach ($permutations as $label => $data) {
            MockWordPress::reset();
            $postObj = $data['post'];
            MockWordPress::$posts[$postObj->ID] = $postObj;
            foreach ($data['fields'] as $k => $v) {
                $this->setPostMeta($postObj->ID, $k, $v);
            }

            // Seed partner post if logo requested
            if (!empty($data['fields']['chu_dau_tu']) && !empty($data['partnerLogoId'])) {
                MockWordPress::insertPost([
                    'post_title' => $data['fields']['chu_dau_tu'],
                    'post_type'  => 'doi-tac',
                    'meta_input' => ['logo' => $data['partnerLogoId']],
                ]);
            }

            $args = [
                'post' => $postObj,
                'class' => 'test-card-class',
            ];

            ob_start();
            include $templatePath;
            $html = ob_get_clean();

            $this->assert(!empty($html), "6.3 [{$label}]: Card project featured renders HTML (" . strlen($html) . " bytes)");

            // Check zero unclosed tags
            [$valid, $tagErrors] = $this->verifyHtmlTagBalance($html, "card-project-featured [{$label}]");
            $this->assert($valid, "6.4 [{$label}]: Zero unclosed tags in card-project-featured DOM", implode('; ', $tagErrors));

            // Check custom class applied
            $this->assert(str_contains($html, 'test-card-class'), "6.5 [{$label}]: Custom class 'test-card-class' rendered");

            // Check thumbnail overflow badge (+2) if gallery > 3
            if (count($data['fields']['gallery_du_an']) > 3) {
                $expectedMore = count($data['fields']['gallery_du_an']) - 3;
                $this->assert(str_contains($html, "+{$expectedMore}"), "6.6 [{$label}]: Gallery overflow +{$expectedMore} badge rendered");
            }

            // Check subdivision badge
            if (!empty($data['fields']['du_an_cha'])) {
                $this->assert(str_contains($html, 'Phân khu'), "6.7 [{$label}]: 'Phân khu' badge rendered for subdivision");
                $this->assert(str_contains($html, 'Thuộc dự án:'), "6.8 [{$label}]: 'Thuộc dự án:' breadcrumb rendered");
            }

            // Check status tags
            if ($data['fields']['trang_thai'] === 'dang_mo_ban') {
                $this->assert(str_contains($html, 'Đang mở bán'), "6.9 [{$label}]: Status label 'Đang mở bán' rendered");
            } elseif ($data['fields']['trang_thai'] === 'sap_mo_ban') {
                $this->assert(str_contains($html, 'Sắp mở bán'), "6.9 [{$label}]: Status label 'Sắp mở bán' rendered");
            }
        }
    }

    /**
     * Test 7: Confirm with Area 5 of adversarial-r1-r2-stress-test.php
     */
    private function testAdversarialArea5PartnerLogoCaching(): void {
        echo "\n▶ Suite 7: Area 5 Partner Logo Caching & Multi-Tier In-Memory Verification\n";

        MockWordPress::reset();

        // 7.1 Seed partner post
        $partnerId = MockWordPress::insertPost([
            'post_title' => 'Tập đoàn Sunshine Group',
            'post_type'  => 'doi-tac',
            'meta_input' => ['logo' => 7777],
        ]);

        // 7.2 First call executes query, sets static cache & transient
        $logo1 = batdongsan_get_partner_logo_by_name('Tập đoàn Sunshine Group');
        $this->assert($logo1 === 7777, "7.2: First call resolves partner logo ID 7777");

        // 7.3 Second call hits in-memory static cache
        $logo2 = batdongsan_get_partner_logo_by_name('Tập đoàn Sunshine Group');
        $this->assert($logo2 === 7777, "7.3: Second call in same request returns cached logo ID 7777");

        // 7.4 Case-insensitive call hits normalized cache
        $logo3 = batdongsan_get_partner_logo_by_name('tập đoàn sunshine group');
        $this->assert($logo3 === 7777, "7.4: Normalized lowercase call hits cache");

        // 7.5 Persistent transient cache check
        $cacheKey = md5(mb_strtolower('Tập đoàn Sunshine Group'));
        $transientKey = 'bds_partner_logo_' . substr($cacheKey, 0, 20);
        $cachedTransient = get_transient($transientKey);
        $this->assert($cachedTransient === 7777, "7.5: Logo ID persisted in transient {$transientKey}");

        // 7.6 Loop card templates audit: zero raw new WP_Query for doi-tac
        $cardFiles = [
            $this->themeDir . '/template-parts/card-project-horizontal.php',
            $this->themeDir . '/template-parts/card-project-featured.php',
            $this->themeDir . '/template-parts/card-transfer.php',
        ];
        $cardViolations = [];
        foreach ($cardFiles as $cf) {
            if (!file_exists($cf)) continue;
            $cnt = file_get_contents($cf);
            if (preg_match('/new\s+WP_Query\s*\(\s*\[[^]]*\'post_type\'\s*=>\s*\'doi-tac\'/s', $cnt)) {
                $cardViolations[] = basename($cf);
            }
        }
        $this->assert(
            empty($cardViolations),
            "7.6: Zero N+1 un-cached WP_Query for doi-tac inside card loop templates"
        );
    }
}

// Execution entry point
$runner = new M3SearchAndCardsEmpiricalStressTest();
$success = $runner->runAll();
exit($success ? 0 : 1);
