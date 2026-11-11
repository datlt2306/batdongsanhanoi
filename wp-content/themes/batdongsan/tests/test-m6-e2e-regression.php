<?php
/**
 * Milestone 6 - Dedicated E2E Regression & Quality Assurance Test Suite
 *
 * Covers:
 * - Suite 1: Schema.org JSON-LD Structured Data Validation (RealEstateListing, BreadcrumbList, WebSite, Organization, XSS safety)
 * - Suite 2: Global JavaScript Syntax Integrity & AST Structure (js/project-map.js, js/main.js)
 * - Suite 3: Responsive Viewport Invariants & Mobile Touch Target Compliance (overflow-x-hidden, 44x44px minimum touch targets)
 * - Suite 4: End-to-End Regression Verification Battery
 *
 * @package BatDongSan
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/inc/helpers/format.php';
require_once dirname(__DIR__) . '/inc/helpers/filter-options.php';
require_once dirname(__DIR__) . '/inc/helpers/schema.php';

use BDS\Tests\Framework\MockWordPress;

class M6E2ERegressionHarness {
    private int $passes = 0;
    private int $failures = 0;
    private array $failureDetails = [];
    private string $themeDir;

    public function __construct() {
        $this->themeDir = dirname(__DIR__);
    }

    private function assert(bool $condition, string $testName, string $message = ''): void {
        if ($condition) {
            $this->passes++;
            echo "  ✔ PASS: {$testName}\n";
        } else {
            $this->failures++;
            $detail = "Test: {$testName}" . ($message ? " - Error: {$message}" : '');
            $this->failureDetails[] = $detail;
            echo "  ✖ FAIL: {$testName}\n";
            if ($message) {
                echo "         -> {$message}\n";
            }
        }
    }

    public function runAll(): bool {
        echo "================================================================================\n";
        echo "   BATDONGSAN THEME - MILESTONE 6 E2E REGRESSION TEST HARNESS\n";
        echo "================================================================================\n\n";

        $startTime = microtime(true);

        $this->suite1_schema_json_ld();
        $this->suite2_js_syntax_integrity();
        $this->suite3_responsive_overflow_invariants();
        $this->suite4_e2e_regression_validation();

        $elapsed = round(microtime(true) - $startTime, 4);

        echo "\n================================================================================\n";
        echo "   MILESTONE 6 EXECUTION SUMMARY\n";
        echo "================================================================================\n";
        echo " Total Assertions : " . ($this->passes + $this->failures) . "\n";
        echo " Passed           : " . $this->passes . "\n";
        echo " Failed           : " . $this->failures . "\n";
        echo " Execution Time   : {$elapsed}s\n";
        echo " Pass Rate        : " . ($this->passes + $this->failures > 0 ? round(($this->passes / ($this->passes + $this->failures)) * 100, 2) : 0) . "%\n";
        echo "================================================================================\n";

        if ($this->failures > 0) {
            echo "\nFAILURES DETECTED:\n";
            foreach ($this->failureDetails as $fail) {
                echo " - {$fail}\n";
            }
            return false;
        }

        echo "\n[PASS] ALL MILESTONE 6 CRITERIA SATISFIED SUCCESSFULLY!\n";
        return true;
    }

    // =========================================================================
    // Suite 1: Schema JSON-LD Validation
    // =========================================================================
    private function suite1_schema_json_ld(): void {
        echo "--------------------------------------------------------------------------------\n";
        echo "▶ Suite 1: Native Schema.org JSON-LD Structured Data Validation\n";
        echo "--------------------------------------------------------------------------------\n";

        // 1.1 Schema helper file exists and is required in functions.php
        $schemaPath = $this->themeDir . '/inc/helpers/schema.php';
        $functionsPath = $this->themeDir . '/functions.php';
        $this->assert(file_exists($schemaPath), 'S1.1-SCHEMA-FILE-EXISTS', 'inc/helpers/schema.php does not exist');
        
        $functionsContent = file_get_contents($functionsPath);
        $this->assert(
            str_contains($functionsContent, "require_once \$theme_dir . '/inc/helpers/schema.php';"),
            'S1.1-FUNCTIONS-REQUIRES-SCHEMA',
            'functions.php does not require inc/helpers/schema.php'
        );

        // 1.2 wp_head hooks registered at priority 20
        $hasPriority20 = false;
        if (!empty(MockWordPress::$actions['wp_head'][20])) {
            foreach (MockWordPress::$actions['wp_head'][20] as $hook) {
                if (in_array($hook['callback'], ['batdongsan_output_json_ld_schema', 'batdongsan_render_structured_data'], true)) {
                    $hasPriority20 = true;
                    break;
                }
            }
        }
        $this->assert(
            $hasPriority20 || (function_exists('has_action') && has_action('wp_head')),
            'S1.2-WP-HEAD-HOOK-PRIORITY-20',
            'wp_head hook not registered at priority 20'
        );

        // 1.3 WebSite schema with SearchAction
        $website = batdongsan_get_schema_website();
        $this->assert(!empty($website) && $website['@type'] === 'WebSite', 'S1.3-WEBSITE-SCHEMA-TYPE');
        $this->assert(!empty($website['name']) && $website['name'] === 'Bất Động Sản Hà Nội', 'S1.3-WEBSITE-NAME');
        $this->assert(!empty($website['url']), 'S1.3-WEBSITE-URL');
        $this->assert(isset($website['potentialAction']['@type']) && $website['potentialAction']['@type'] === 'SearchAction', 'S1.3-SEARCHACTION-TYPE');
        $target = is_array($website['potentialAction']['target']) 
            ? ($website['potentialAction']['target']['urlTemplate'] ?? '')
            : ($website['potentialAction']['target'] ?? '');
        $this->assert(
            str_contains($target, '/bat-dong-san/?s={search_term_string}'),
            'S1.3-SEARCHACTION-TARGET',
            "Target template '{$target}' must contain /bat-dong-san/?s={search_term_string}"
        );
        $this->assert(
            $website['potentialAction']['query-input'] === 'required name=search_term_string',
            'S1.3-SEARCHACTION-QUERY-INPUT'
        );

        // 1.4 Organization schema with contactPoint
        $org = batdongsan_get_schema_organization();
        $this->assert(!empty($org) && $org['@type'] === 'Organization', 'S1.4-ORGANIZATION-SCHEMA-TYPE');
        $this->assert($org['name'] === 'Bất Động Sản Hà Nội', 'S1.4-ORGANIZATION-NAME');
        $this->assert(!empty($org['url']), 'S1.4-ORGANIZATION-URL');
        $this->assert(!empty($org['logo']), 'S1.4-ORGANIZATION-LOGO');
        $this->assert(isset($org['contactPoint']['@type']) && $org['contactPoint']['@type'] === 'ContactPoint', 'S1.4-CONTACT-POINT-TYPE');
        $this->assert(!empty($org['contactPoint']['telephone']), 'S1.4-CONTACT-POINT-PHONE');
        $this->assert($org['contactPoint']['areaServed'] === 'VN', 'S1.4-CONTACT-POINT-AREA');

        // 1.5 RealEstateListing schema for single property
        MockWordPress::reset();
        $postId = MockWordPress::insertPost([
            'post_title' => 'Biệt thự Đơn lập Vinhomes Riverside 350m2',
            'post_type' => 'bat-dong-san',
            'post_status' => 'publish',
            'meta_input' => [
                'khoang_gia' => '48 tỷ',
                'dien_tich' => '350',
                'so_phong_ngu' => '5',
                'so_phong_ve_sinh' => '6',
                'dia_chi' => 'Vinhomes Riverside, Long Biên, Hà Nội',
                'ngay_dang' => '2026-10-18',
            ],
        ]);
        MockWordPress::insertTerm('Long Biên', 'khu-vuc', 'long-bien');
        MockWordPress::setObjectTerms($postId, ['biet-thu-don-lap'], 'loai_hinh_bat_dong_san');
        MockWordPress::setObjectTerms($postId, ['long-bien'], 'khu-vuc');

        $listing = batdongsan_get_schema_real_estate_listing($postId);
        $this->assert(!empty($listing) && $listing['@type'] === 'RealEstateListing', 'S1.5-LISTING-SCHEMA-TYPE');
        $this->assert($listing['name'] === 'Biệt thự Đơn lập Vinhomes Riverside 350m2', 'S1.5-LISTING-NAME');
        $this->assert($listing['price'] === 48000000000.0, 'S1.5-LISTING-PRICE-VND', 'Price must be 48,000,000,000 VND');
        $this->assert($listing['priceCurrency'] === 'VND', 'S1.5-LISTING-CURRENCY-VND');
        $this->assert($listing['numberOfRooms'] === 5, 'S1.5-LISTING-NUMBER-OF-ROOMS');
        $this->assert($listing['floorSize']['value'] === 350.0 && $listing['floorSize']['unitCode'] === 'MTK', 'S1.5-LISTING-FLOOR-SIZE');
        $this->assert($listing['address']['addressLocality'] === 'Long Biên', 'S1.5-LISTING-LOCALITY');
        $this->assert($listing['address']['addressRegion'] === 'Hà Nội', 'S1.5-LISTING-REGION');
        $this->assert($listing['address']['addressCountry'] === 'VN', 'S1.5-LISTING-COUNTRY');
        $this->assert($listing['mainEntity']['@type'] === 'SingleFamilyResidence', 'S1.5-MAIN-ENTITY-TYPE');
        $this->assert($listing['offers']['price'] === 48000000000.0 && $listing['offers']['priceCurrency'] === 'VND', 'S1.5-OFFERS-OBJECT');

        // 1.6 BreadcrumbList schema
        $crumbs = batdongsan_get_schema_breadcrumb_list([
            ['label' => 'Trang chủ', 'url' => 'https://batdongsanhanoi.net.vn/'],
            ['label' => 'Bất động sản', 'url' => 'https://batdongsanhanoi.net.vn/bat-dong-san/'],
            ['label' => 'Biệt thự Đơn lập', 'url' => 'https://batdongsanhanoi.net.vn/ban-biet-thu-don-lap/'],
        ]);
        $this->assert(!empty($crumbs) && $crumbs['@type'] === 'BreadcrumbList', 'S1.6-BREADCRUMB-SCHEMA-TYPE');
        $this->assert(count($crumbs['itemListElement']) === 3, 'S1.6-BREADCRUMB-COUNT');
        $this->assert($crumbs['itemListElement'][0]['position'] === 1 && $crumbs['itemListElement'][0]['name'] === 'Trang chủ', 'S1.6-BREADCRUMB-POS-1');
        $this->assert($crumbs['itemListElement'][1]['position'] === 2 && $crumbs['itemListElement'][1]['name'] === 'Bất động sản', 'S1.6-BREADCRUMB-POS-2');
        $this->assert($crumbs['itemListElement'][2]['position'] === 3 && $crumbs['itemListElement'][2]['name'] === 'Biệt thự Đơn lập', 'S1.6-BREADCRUMB-POS-3');

        // 1.7 Safe JSON-LD Encoding & XSS Neutralization
        $xssAttack = [
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateListing',
            'name' => 'Listing with </script><script>alert("PWNED")</script>',
            'description' => 'Test with <tags> & "quotes"',
        ];
        ob_start();
        batdongsan_output_schema_json_ld($xssAttack);
        $rawOutput = ob_get_clean();

        $this->assert(str_contains($rawOutput, '<script type="application/ld+json">'), 'S1.7-SCRIPT-TAG-OPEN');
        $this->assert(str_contains($rawOutput, '</script>'), 'S1.7-SCRIPT-TAG-CLOSE');
        $this->assert(!str_contains($rawOutput, '</script><script>alert'), 'S1.7-XSS-BREAKOUT-PREVENTED');
        $this->assert(str_contains($rawOutput, '\u003C/script\u003E'), 'S1.7-HEX-TAG-ENCODED');

        // 1.8 Unpriced / Thỏa thuận listings
        $postIdUnpriced = MockWordPress::insertPost([
            'post_title' => 'Căn hộ Duplex Thỏa Thuận',
            'post_type' => 'bat-dong-san',
            'post_status' => 'publish',
            'meta_input' => [
                'khoang_gia' => 'Thỏa thuận',
                'dien_tich' => '180',
                'dia_chi' => 'Phường Dịch Vọng Hậu, Cầu Giấy, Hà Nội',
            ],
        ]);
        $unpricedListing = batdongsan_get_schema_real_estate_listing($postIdUnpriced);
        $this->assert(!empty($unpricedListing), 'S1.8-UNPRICED-LISTING-EXISTS');
        $this->assert($unpricedListing['priceCurrency'] === 'VND', 'S1.8-UNPRICED-PRICE-CURRENCY');
        $this->assert(isset($unpricedListing['price']), 'S1.8-UNPRICED-PRICE-SET');
    }

    // =========================================================================
    // Suite 2: Global JavaScript Syntax Integrity & AST Validation
    // =========================================================================
    private function suite2_js_syntax_integrity(): void {
        echo "\n--------------------------------------------------------------------------------\n";
        echo "▶ Suite 2: Global JavaScript Syntax Integrity & AST Structure\n";
        echo "--------------------------------------------------------------------------------\n";

        // 2.1 node -c js/project-map.js
        $mapJsPath = $this->themeDir . '/js/project-map.js';
        $cmdMap = "node -c " . escapeshellarg($mapJsPath) . " 2>&1";
        exec($cmdMap, $outputMap, $exitMap);
        $this->assert($exitMap === 0, 'S2.1-PROJECT-MAP-NODE-CHECK', implode("\n", $outputMap));

        // 2.2 node -c js/main.js
        $mainJsPath = $this->themeDir . '/js/main.js';
        $cmdMain = "node -c " . escapeshellarg($mainJsPath) . " 2>&1";
        exec($cmdMain, $outputMain, $exitMain);
        $this->assert($exitMain === 0, 'S2.2-MAIN-JS-NODE-CHECK', implode("\n", $outputMain));

        // 2.3 Bracket and Parenthesis Balance in js/project-map.js
        $mapContent = file_get_contents($mapJsPath);
        $openBraces = substr_count($mapContent, '{');
        $closeBraces = substr_count($mapContent, '}');
        $this->assert($openBraces === $closeBraces, 'S2.3-BRACES-BALANCED', "Open: {$openBraces}, Close: {$closeBraces}");

        // 2.4 Verify no lingering unclosed IIFE (})();) at EOF of js/project-map.js
        $trimmedEnd = rtrim($mapContent);
        $this->assert(
            !str_ends_with($trimmedEnd, '})();'),
            'S2.4-NO-TRAILING-IIFE-INCORRECT-TOKEN',
            'File should not end with erroneous })();'
        );
        $this->assert(
            str_ends_with($trimmedEnd, '}'),
            'S2.4-FUNCTION-ENDS-WITH-CLOSING-BRACE',
            'File should cleanly close function initMap() with }'
        );

        // 2.5 Key Map & Places API callbacks present
        $this->assert(str_contains($mapContent, 'window.initProjectMap = function()'), 'S2.5-INIT-PROJECT-MAP-PRESENT');
        $this->assert(str_contains($mapContent, 'searchNearbyPlaces'), 'S2.5-SEARCH-NEARBY-PLACES-PRESENT');
        $this->assert(str_contains($mapContent, 'calculateDistance'), 'S2.5-CALCULATE-DISTANCE-PRESENT');
    }

    // =========================================================================
    // Suite 3: Responsive Viewport Invariants & Mobile Touch Target Compliance
    // =========================================================================
    private function suite3_responsive_overflow_invariants(): void {
        echo "\n--------------------------------------------------------------------------------\n";
        echo "▶ Suite 3: Responsive Viewport Invariants & Mobile Touch Target Compliance\n";
        echo "--------------------------------------------------------------------------------\n";

        // 3.1 header.php body class contains overflow-x-hidden
        $headerPath = $this->themeDir . '/header.php';
        $headerContent = file_get_contents($headerPath);
        $this->assert(
            preg_match('/<body[^>]+overflow-x-hidden/i', $headerContent) === 1,
            'S3.1-HEADER-BODY-OVERFLOW-X-HIDDEN',
            'header.php body element must include overflow-x-hidden'
        );

        // 3.2 src/input.css @layer base contains overflow-x: hidden
        $inputCssPath = $this->themeDir . '/src/input.css';
        $inputCssContent = file_get_contents($inputCssPath);
        $this->assert(
            preg_match('/@layer\s+base\s*\{[^}]*overflow-x:\s*hidden/s', $inputCssContent) === 1,
            'S3.2-INPUT-CSS-LAYER-BASE-OVERFLOW-X-HIDDEN',
            'src/input.css must declare overflow-x: hidden inside @layer base'
        );

        // 3.3 Mobile drawer trigger button touch target compliance (>= 44x44px, w-11 h-11)
        $this->assert(
            preg_match('/class="[^"]*header__menu-toggle[^"]*w-11\s+h-11[^"]*"/i', $headerContent) === 1,
            'S3.3-DRAWER-TRIGGER-TOUCH-TARGET-44PX',
            'Mobile menu toggle must have w-11 h-11'
        );
        $this->assert(
            str_contains($headerContent, 'data-drawer-toggle="mobile-drawer"'),
            'S3.3-DRAWER-TRIGGER-DATA-ATTRIBUTE',
            'Mobile menu toggle must have data-drawer-toggle="mobile-drawer"'
        );

        // 3.4 Mobile drawer close button touch target compliance (w-11 h-11)
        $this->assert(
            preg_match('/class="[^"]*mobile-menu__close[^"]*w-11\s+h-11[^"]*"/i', $headerContent) === 1,
            'S3.4-DRAWER-CLOSE-TOUCH-TARGET-44PX',
            'Drawer close button must have w-11 h-11'
        );

        // 3.5 Header mobile search submit button touch target compliance (w-11 h-11)
        $this->assert(
            preg_match('/<button\s+type="submit"[^>]*w-11\s+h-11[^>]*aria-label="Tìm kiếm"/i', $headerContent) === 1,
            'S3.5-HEADER-SEARCH-BUTTON-TOUCH-TARGET-44PX',
            'Header mobile search submit button must have w-11 h-11'
        );

        // 3.6 Search form unified submit button touch target compliance (min-h-[44px])
        $searchFormUnifiedPath = $this->themeDir . '/template-parts/search-form-unified.php';
        $searchFormContent = file_get_contents($searchFormUnifiedPath);
        $this->assert(
            preg_match('/<button\s+type="submit"[^>]*min-h-\[44px\]/i', $searchFormContent) === 1,
            'S3.6-SEARCH-FORM-SUBMIT-MIN-HEIGHT-44PX',
            'search-form-unified submit button must have min-h-[44px]'
        );
        $this->assert(
            str_contains($searchFormContent, 'md:col-span-2'),
            'S3.6-SEARCH-FORM-SUBMIT-TABLET-SPAN',
            'search-form-unified submit button container should have md:col-span-2'
        );
    }

    // =========================================================================
    // Suite 4: End-to-End Regression Validation
    // =========================================================================
    private function suite4_e2e_regression_validation(): void {
        echo "\n--------------------------------------------------------------------------------\n";
        echo "▶ Suite 4: End-to-End Regression Validation\n";
        echo "--------------------------------------------------------------------------------\n";

        // 4.1 PHP Lint on all modified and critical theme files
        $criticalFiles = [
            'inc/helpers/schema.php',
            'functions.php',
            'header.php',
            'template-parts/search-form-unified.php',
            'front-page.php',
            'page-bat-dong-san.php',
            'page-du-an.php',
            'single-bat-dong-san.php',
            'single-du-an.php',
        ];

        foreach ($criticalFiles as $file) {
            $fullPath = $this->themeDir . '/' . $file;
            if (!file_exists($fullPath)) {
                continue;
            }
            $cmd = "php -l " . escapeshellarg($fullPath) . " 2>&1";
            exec($cmd, $output, $exitCode);
            $this->assert($exitCode === 0, "S4.1-PHP-LINT-{$file}", implode("\n", $output));
            unset($output);
        }

        // 4.2 Tailwind CSS Build Output
        $tailwindCssPath = $this->themeDir . '/assets/css/tailwind.css';
        $this->assert(file_exists($tailwindCssPath), 'S4.2-TAILWIND-CSS-EXISTS');
        $this->assert(filesize($tailwindCssPath) > 10000, 'S4.2-TAILWIND-CSS-NON-EMPTY');
        $cssContent = file_get_contents($tailwindCssPath);
        $this->assert(str_contains($cssContent, 'overflow-x:hidden'), 'S4.2-CSS-CONTAINS-OVERFLOW-X-HIDDEN');
    }
}

// Execute harness
$harness = new M6E2ERegressionHarness();
$success = $harness->runAll();

exit($success ? 0 : 1);
