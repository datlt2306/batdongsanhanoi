<?php
/**
 * Milestone 6 Challenger Stress-Test Suite
 *
 * EMPIRICAL ADVERSARIAL CHALLENGE FOR:
 * 1. Schema.org JSON-LD generation & specifications (RealEstateListing, BreadcrumbList, WebSite, Organization)
 * 2. Adversarial XSS injections into JSON-LD encoders (script breakouts, hex tag escaping)
 * 3. JavaScript syntax, AST structure, bracket balancing, and mock execution (js/project-map.js, js/main.js)
 * 4. Multi-criteria corner cases for schema extraction
 *
 * @package BatDongSan
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/inc/helpers/format.php';
require_once dirname(__DIR__) . '/inc/helpers/filter-options.php';
require_once dirname(__DIR__) . '/inc/helpers/schema.php';

use BDS\Tests\Framework\MockWordPress;

class M6ChallengerStressHarness {
    private int $passes = 0;
    private int $failures = 0;
    private array $failureDetails = [];
    private string $themeDir;

    public function __construct() {
        $this->themeDir = dirname(__DIR__);
    }

    private function setMeta(int $postId, string $key, mixed $value): void {
        MockWordPress::$postmeta[$postId][$key] = [$value];
    }

    private function assert(bool $condition, string $id, string $message = ''): void {
        if ($condition) {
            $this->passes++;
            echo "  ✔ PASS: {$id}\n";
        } else {
            $this->failures++;
            $detail = "[{$id}] " . ($message ?: 'Assertion failed');
            $this->failureDetails[] = $detail;
            echo "  ✖ FAIL: {$id}\n";
            if ($message) {
                echo "         -> {$message}\n";
            }
        }
    }

    public function run(): bool {
        echo "================================================================================\n";
        echo "   BATDONGSAN M6 EMPIRICAL CHALLENGER STRESS HARNESS\n";
        echo "================================================================================\n\n";

        $start = microtime(true);

        $this->challengeRealEstateListingSchema();
        $this->challengeBreadcrumbListSchema();
        $this->challengeWebSiteSchema();
        $this->challengeOrganizationSchema();
        $this->challengeAdversarialXssEscaping();
        $this->challengeJavaScriptSyntaxAndAst();

        $elapsed = round(microtime(true) - $start, 4);

        echo "\n================================================================================\n";
        echo "   CHALLENGER STRESS TEST SUMMARY\n";
        echo "================================================================================\n";
        echo " Total Assertions : " . ($this->passes + $this->failures) . "\n";
        echo " Passed           : " . $this->passes . "\n";
        echo " Failed           : " . $this->failures . "\n";
        echo " Execution Time   : {$elapsed}s\n";
        echo " Pass Rate        : " . ($this->passes + $this->failures > 0 ? round(($this->passes / ($this->passes + $this->failures)) * 100, 2) : 0) . "%\n";
        echo "================================================================================\n";

        if ($this->failures > 0) {
            echo "\nFAILURES ENCOUNTERED:\n";
            foreach ($this->failureDetails as $fail) {
                echo " - {$fail}\n";
            }
            return false;
        }

        echo "\n[PASS] ALL EMPIRICAL CHALLENGER ASSERTIONS PASSED WITH ZERO DEFECTS!\n";
        return true;
    }

    /**
     * Challenge 1: RealEstateListing schema generation & data parsing
     */
    private function challengeRealEstateListingSchema(): void {
        echo "--------------------------------------------------------------------------------\n";
        echo "▶ Challenge 1: Schema.org RealEstateListing Validation & Conversions\n";
        echo "--------------------------------------------------------------------------------\n";

        // 1.1 Non-existent or invalid post ID
        $this->assert(batdongsan_get_schema_real_estate_listing(0) === [], 'C1.1-INVALID-ID-ZERO', 'Post ID 0 must return empty array');
        $this->assert(batdongsan_get_schema_real_estate_listing(-5) === [], 'C1.1-INVALID-ID-NEGATIVE', 'Negative post ID must return empty array');

        // 1.2 Price conversion tests (tỷ, triệu, raw numeric, decimals, ranges)
        $testCases = [
            ['input' => '48 tỷ', 'expected' => 48000000000.0, 'desc' => '48 tỷ to VND'],
            ['input' => '5.5 tỷ', 'expected' => 5500000000.0, 'desc' => '5.5 tỷ to VND'],
            ['input' => '850 triệu', 'expected' => 850000000.0, 'desc' => '850 triệu to VND'],
            ['input' => '1.25 tỷ', 'expected' => 1250000000.0, 'desc' => '1.25 tỷ to VND'],
            ['input' => '3 - 5 tỷ', 'expected' => 3000000000.0, 'desc' => 'Range 3 - 5 tỷ takes min 3 tỷ to VND'],
            ['input' => 'Thỏa thuận', 'expected' => 0.0, 'desc' => 'Negotiable price to 0.0'],
            ['input' => '0', 'expected' => 0.0, 'desc' => 'Zero price to 0.0'],
        ];

        foreach ($testCases as $idx => $tc) {
            $pid = MockWordPress::insertPost([
                'post_title' => "BĐS Test Giá {$tc['input']} #{$idx}",
                'post_type'  => 'bat-dong-san',
            ]);
            $this->setMeta($pid, 'khoang_gia', $tc['input']);
            $this->setMeta($pid, 'dien_tich', '100');

            $schema = batdongsan_get_schema_real_estate_listing($pid);
            $this->assert(
                isset($schema['price']) && (float) $schema['price'] === $tc['expected'],
                "C1.2-PRICE-CONV-{$idx}",
                "Expected {$tc['expected']} for '{$tc['input']}', got " . ($schema['price'] ?? 'null')
            );
            $this->assert(
                isset($schema['priceCurrency']) && $schema['priceCurrency'] === 'VND',
                "C1.2-CURRENCY-{$idx}",
                "Currency must be VND"
            );
            $this->assert(
                isset($schema['offers']['price']) && (float) $schema['offers']['price'] === $tc['expected'],
                "C1.2-OFFER-PRICE-{$idx}",
                "Offer price must match listing price"
            );
            $this->assert(
                isset($schema['offers']['priceCurrency']) && $schema['offers']['priceCurrency'] === 'VND',
                "C1.2-OFFER-CURRENCY-{$idx}",
                "Offer currency must be VND"
            );
            $this->assert(
                isset($schema['offers']['availability']) && $schema['offers']['availability'] === 'https://schema.org/InStock',
                "C1.2-OFFER-AVAILABILITY-{$idx}",
                "Offer availability must be InStock"
            );
        }

        // 1.3 Area / Floor size parsing (integers, decimals, comma vs dot, string m2)
        $areaCases = [
            ['input' => '120', 'expected' => 120.0],
            ['input' => '85.5', 'expected' => 85.5],
            ['input' => '95,5', 'expected' => 95.5],
            ['input' => '150 m2', 'expected' => 150.0],
            ['input' => '200m²', 'expected' => 200.0],
        ];

        foreach ($areaCases as $idx => $ac) {
            $pid = MockWordPress::insertPost([
                'post_title' => "BĐS Test Diện Tích {$ac['input']} #{$idx}",
                'post_type'  => 'bat-dong-san',
            ]);
            $this->setMeta($pid, 'dien_tich', $ac['input']);

            $schema = batdongsan_get_schema_real_estate_listing($pid);
            $this->assert(
                isset($schema['floorSize']['value']) && (float) $schema['floorSize']['value'] === $ac['expected'],
                "C1.3-AREA-PARSING-{$idx}",
                "Expected {$ac['expected']} for '{$ac['input']}', got " . ($schema['floorSize']['value'] ?? 'null')
            );
            $this->assert(
                $schema['floorSize']['unitCode'] === 'MTK',
                "C1.3-AREA-UNITCODE-{$idx}",
                "FloorSize unitCode must be MTK (square meters)"
            );
        }

        // 1.4 Taxonomy-to-Schema Entity Type mapping
        $typeCases = [
            ['slug' => 'chung-cu-cao-cap', 'expected' => 'Apartment'],
            ['slug' => 'can-ho-dich-vu', 'expected' => 'Apartment'],
            ['slug' => 'biet-thu-don-lap', 'expected' => 'SingleFamilyResidence'],
            ['slug' => 'nha-pho-thuong-mai', 'expected' => 'SingleFamilyResidence'],
            ['slug' => 'nha-rieng-chinh-chu', 'expected' => 'SingleFamilyResidence'],
            ['slug' => 'nha-mat-pho', 'expected' => 'SingleFamilyResidence'],
            ['slug' => 'dat-nen-du-an', 'expected' => 'Accommodation'],
            ['slug' => 'mat-bang-kinh-doanh', 'expected' => 'Accommodation'],
        ];

        foreach ($typeCases as $idx => $tc) {
            $pid = MockWordPress::insertPost([
                'post_title' => "BĐS Test Loại Hình {$tc['slug']} #{$idx}",
                'post_type'  => 'bat-dong-san',
            ]);
            MockWordPress::insertTerm($tc['slug'], 'loai_hinh_bat_dong_san', $tc['slug']);
            MockWordPress::setObjectTerms($pid, [$tc['slug']], 'loai_hinh_bat_dong_san');
            $schema = batdongsan_get_schema_real_estate_listing($pid);
            $this->assert(
                isset($schema['mainEntity']['@type']) && $schema['mainEntity']['@type'] === $tc['expected'],
                "C1.4-ENTITY-TYPE-{$idx}",
                "Expected {$tc['expected']} for slug '{$tc['slug']}', got " . ($schema['mainEntity']['@type'] ?? 'null')
            );
        }

        // 1.5 Address extraction: Locality, Region, Country, StreetAddress
        $pidAddr1 = MockWordPress::insertPost([
            'post_title' => 'BĐS Test Địa Chỉ 1',
            'post_type'  => 'bat-dong-san',
        ]);
        $this->setMeta($pidAddr1, 'dia_chi', 'Số 12 ngõ 34 Phố Huế, Quận Hai Bà Trưng, Hà Nội');
        MockWordPress::insertTerm('Hai Bà Trưng', 'khu-vuc', 'hai-ba-trung');
        MockWordPress::setObjectTerms($pidAddr1, ['hai-ba-trung'], 'khu-vuc');

        $schemaAddr1 = batdongsan_get_schema_real_estate_listing($pidAddr1);
        $this->assert($schemaAddr1['address']['addressLocality'] === 'Hai Bà Trưng', 'C1.5-ADDR-LOCALITY-TAXONOMY');
        $this->assert($schemaAddr1['address']['addressRegion'] === 'Hà Nội', 'C1.5-ADDR-REGION');
        $this->assert($schemaAddr1['address']['addressCountry'] === 'VN', 'C1.5-ADDR-COUNTRY');
        $this->assert($schemaAddr1['address']['streetAddress'] === 'Số 12 ngõ 34 Phố Huế, Quận Hai Bà Trưng, Hà Nội', 'C1.5-ADDR-STREET');

        // Address fallback from string when no taxonomy term
        $pidAddr2 = MockWordPress::insertPost([
            'post_title' => 'BĐS Test Địa Chỉ Không Term',
            'post_type'  => 'bat-dong-san',
        ]);
        $this->setMeta($pidAddr2, 'dia_chi', 'Đường Trần Duy Hưng, Cầu Giấy, Hà Nội');
        $schemaAddr2 = batdongsan_get_schema_real_estate_listing($pidAddr2);
        $this->assert($schemaAddr2['address']['addressLocality'] === 'Cầu Giấy', 'C1.5-ADDR-LOCALITY-PARSED-STRING', 'Must parse Cầu Giấy from address parts');

        // 1.6 Geo Coordinates mapping
        $pidGeo = MockWordPress::insertPost([
            'post_title' => 'BĐS Test Geo Coordinates',
            'post_type'  => 'bat-dong-san',
        ]);
        $this->setMeta($pidGeo, 'google_map', [
            'lat' => '21.028511',
            'lng' => '105.854444',
        ]);
        $schemaGeo = batdongsan_get_schema_real_estate_listing($pidGeo);
        $this->assert(isset($schemaGeo['mainEntity']['geo']), 'C1.6-GEO-EXISTS');
        $this->assert($schemaGeo['mainEntity']['geo']['@type'] === 'GeoCoordinates', 'C1.6-GEO-TYPE');
        $this->assert($schemaGeo['mainEntity']['geo']['latitude'] === 21.028511, 'C1.6-GEO-LAT');
        $this->assert($schemaGeo['mainEntity']['geo']['longitude'] === 105.854444, 'C1.6-GEO-LNG');

        // 1.7 Image collection (thumbnail + gallery + deduplication)
        $pidImg = MockWordPress::insertPost([
            'post_title' => 'BĐS Test Images',
            'post_type'  => 'bat-dong-san',
        ]);
        $this->setMeta($pidImg, 'gallery_nha_dat', [
            'https://batdongsanhanoi.net.vn/wp-content/uploads/img1.jpg',
            'https://batdongsanhanoi.net.vn/wp-content/uploads/img2.jpg',
            'https://batdongsanhanoi.net.vn/wp-content/uploads/img1.jpg', // Duplicate
        ]);
        $schemaImg = batdongsan_get_schema_real_estate_listing($pidImg);
        $this->assert(isset($schemaImg['image']) && is_array($schemaImg['image']), 'C1.7-IMAGES-PRESENT');
        $this->assert(count($schemaImg['image']) === 2, 'C1.7-IMAGES-DEDUPLICATED', 'Duplicates should be pruned');
        $this->assert($schemaImg['mainEntity']['image'] === $schemaImg['image'], 'C1.7-MAINENTITY-IMAGES-SYNC');
    }

    /**
     * Challenge 2: BreadcrumbList schema validation
     */
    private function challengeBreadcrumbListSchema(): void {
        echo "\n--------------------------------------------------------------------------------\n";
        echo "▶ Challenge 2: Schema.org BreadcrumbList Validation & Sequential Indexing\n";
        echo "--------------------------------------------------------------------------------\n";

        // 2.1 1-based sequential position index
        $crumbs = [
            ['label' => 'Trang chủ', 'url' => 'https://batdongsanhanoi.net.vn/'],
            ['label' => 'Bất động sản', 'url' => 'https://batdongsanhanoi.net.vn/bat-dong-san/'],
            ['label' => 'Cầu Giấy', 'url' => 'https://batdongsanhanoi.net.vn/bat-dong-san-quan-cau-giay/'],
            ['label' => 'Bán biệt thự', 'url' => 'https://batdongsanhanoi.net.vn/ban-biet-thu/'],
            ['label' => 'Biệt thự The Manor 250m2', 'url' => 'https://batdongsanhanoi.net.vn/biet-thu-the-manor/'],
        ];

        $schema = batdongsan_get_schema_breadcrumb_list($crumbs);
        $this->assert(!empty($schema) && $schema['@type'] === 'BreadcrumbList', 'C2.1-BREADCRUMB-TYPE');
        $this->assert(count($schema['itemListElement']) === 5, 'C2.1-BREADCRUMB-COUNT');

        $isSequential = true;
        foreach ($schema['itemListElement'] as $i => $item) {
            $expectedPos = $i + 1;
            if ($item['position'] !== $expectedPos) {
                $isSequential = false;
                break;
            }
        }
        $this->assert($isSequential, 'C2.1-SEQUENTIAL-1-BASED-POSITION', 'Positions must strictly increment from 1..N');
        $this->assert($schema['itemListElement'][0]['position'] === 1, 'C2.1-FIRST-ELEMENT-POS-1');
        $this->assert($schema['itemListElement'][4]['position'] === 5, 'C2.1-LAST-ELEMENT-POS-5');

        // 2.2 Graceful omission of empty labels without breaking index sequence
        $crumbsWithEmpty = [
            ['label' => 'Trang chủ', 'url' => 'https://batdongsanhanoi.net.vn/'],
            ['label' => '', 'url' => 'https://batdongsanhanoi.net.vn/ignore-me/'],
            ['label' => 'Bất động sản', 'url' => 'https://batdongsanhanoi.net.vn/bat-dong-san/'],
        ];
        $schemaFiltered = batdongsan_get_schema_breadcrumb_list($crumbsWithEmpty);
        $this->assert(count($schemaFiltered['itemListElement']) === 2, 'C2.2-EMPTY-LABEL-OMITTED');
        $this->assert($schemaFiltered['itemListElement'][0]['position'] === 1, 'C2.2-POS-1-INTACT');
        $this->assert($schemaFiltered['itemListElement'][1]['position'] === 2, 'C2.2-POS-2-INTACT');
        $this->assert($schemaFiltered['itemListElement'][1]['name'] === 'Bất động sản', 'C2.2-CORRECT-SECOND-ITEM');

        // 2.3 HTML stripping in crumb labels
        $crumbsWithHtml = [
            ['label' => 'Trang chủ <b>VIP</b>', 'url' => 'https://batdongsanhanoi.net.vn/'],
            ['label' => '<span class="gold">Căn hộ</span>', 'url' => 'https://batdongsanhanoi.net.vn/can-ho/'],
        ];
        $schemaClean = batdongsan_get_schema_breadcrumb_list($crumbsWithHtml);
        $this->assert($schemaClean['itemListElement'][0]['name'] === 'Trang chủ VIP', 'C2.3-STRIP-HTML-B');
        $this->assert($schemaClean['itemListElement'][1]['name'] === 'Căn hộ', 'C2.3-STRIP-HTML-SPAN');

        // 2.4 Empty crumbs array
        $this->assert(batdongsan_get_schema_breadcrumb_list([]) !== null, 'C2.4-EMPTY-CRUMBS-HANDLING');
    }

    /**
     * Challenge 3: WebSite schema validation
     */
    private function challengeWebSiteSchema(): void {
        echo "\n--------------------------------------------------------------------------------\n";
        echo "▶ Challenge 3: Schema.org WebSite & SearchAction Validation\n";
        echo "--------------------------------------------------------------------------------\n";

        $schema = batdongsan_get_schema_website();
        $this->assert(!empty($schema) && $schema['@type'] === 'WebSite', 'C3.1-WEBSITE-TYPE');
        $this->assert($schema['name'] === 'Bất Động Sản Hà Nội', 'C3.1-WEBSITE-NAME');
        $this->assert(!empty($schema['url']), 'C3.1-WEBSITE-URL');

        // SearchAction check
        $this->assert(isset($schema['potentialAction']['@type']), 'C3.2-SEARCHACTION-EXISTS');
        $this->assert($schema['potentialAction']['@type'] === 'SearchAction', 'C3.2-SEARCHACTION-TYPE');

        // Url template check
        $target = $schema['potentialAction']['target'];
        $template = is_array($target) ? ($target['urlTemplate'] ?? '') : $target;
        $this->assert(
            str_contains($template, '/bat-dong-san/?s={search_term_string}'),
            'C3.2-SEARCHACTION-TARGET-URL',
            "Target template '{$template}' must match /bat-dong-san/?s={search_term_string}"
        );
        $this->assert(
            isset($schema['potentialAction']['query-input']) && $schema['potentialAction']['query-input'] === 'required name=search_term_string',
            'C3.2-QUERY-INPUT-DIRECTIVE',
            "Query input must be 'required name=search_term_string'"
        );
    }

    /**
     * Challenge 4: Organization schema validation
     */
    private function challengeOrganizationSchema(): void {
        echo "\n--------------------------------------------------------------------------------\n";
        echo "▶ Challenge 4: Schema.org Organization & ContactPoint Validation\n";
        echo "--------------------------------------------------------------------------------\n";

        $schema = batdongsan_get_schema_organization();
        $this->assert(!empty($schema) && $schema['@type'] === 'Organization', 'C4.1-ORG-TYPE');
        $this->assert($schema['name'] === 'Bất Động Sản Hà Nội', 'C4.1-ORG-NAME');
        $this->assert(!empty($schema['url']), 'C4.1-ORG-URL');
        $this->assert(!empty($schema['logo']), 'C4.1-ORG-LOGO');

        // ContactPoint check
        $this->assert(isset($schema['contactPoint']['@type']), 'C4.2-CONTACTPOINT-EXISTS');
        $this->assert($schema['contactPoint']['@type'] === 'ContactPoint', 'C4.2-CONTACTPOINT-TYPE');
        $this->assert(!empty($schema['contactPoint']['telephone']), 'C4.2-CONTACTPOINT-PHONE');
        $this->assert($schema['contactPoint']['areaServed'] === 'VN', 'C4.2-CONTACTPOINT-AREA-VN');
        $this->assert(
            is_array($schema['contactPoint']['availableLanguage']) && in_array('vi', $schema['contactPoint']['availableLanguage'], true),
            'C4.2-CONTACTPOINT-LANG-VI'
        );
    }

    /**
     * Challenge 5: Adversarial XSS injections & Safe JSON-LD Escaping
     */
    private function challengeAdversarialXssEscaping(): void {
        echo "\n--------------------------------------------------------------------------------\n";
        echo "▶ Challenge 5: Adversarial XSS Injections & JSON-LD Escaping Hardening\n";
        echo "--------------------------------------------------------------------------------\n";

        $xssVectors = [
            '</script><script>alert(1)</script>',
            '</script><script src="https://attacker.com/evil.js"></script>',
            '</script><img src=x onerror=alert(document.cookie)>',
            '<!-- <script> --> <script>alert("nested")</script>',
            '"><script>alert(1)</script><input type="hidden" value="',
            "';alert(1);//",
            '</script><svg onload=alert(1)>',
            '\\"></script><script>alert(1)</script>',
        ];

        foreach ($xssVectors as $idx => $payload) {
            // Test 1: Direct encoding through batdongsan_encode_json_ld()
            $testObj = [
                '@context'    => 'https://schema.org',
                '@type'       => 'RealEstateListing',
                'name'        => $payload,
                'description' => "Description with {$payload}",
            ];

            $encoded = batdongsan_encode_json_ld($testObj);

            // Assert no raw unescaped </script> inside JSON output
            $this->assert(
                !str_contains($encoded, '</script>'),
                "C5.1-NO-RAW-SCRIPT-TAG-{$idx}",
                "JSON output must not contain raw </script>"
            );

            // Assert JSON_HEX_TAG turned < into \u003C and > into \u003E
            $this->assert(
                str_contains($encoded, '\u003C') || !str_contains($payload, '<'),
                "C5.1-HEX-TAG-LESS-THAN-{$idx}",
                "Must hex encode '<'"
            );

            // Test 2: Wrap in HTML script tag and verify parser safety
            ob_start();
            batdongsan_output_schema_json_ld($testObj);
            $htmlOutput = ob_get_clean();

            // Check that the only script tags are the outer wrapper: <script type="application/ld+json"> and closing </script>
            $scriptOpenCount = substr_count(strtolower($htmlOutput), '<script');
            $scriptCloseCount = substr_count(strtolower($htmlOutput), '</script>');
            $this->assert($scriptOpenCount === 1, "C5.2-SINGLE-SCRIPT-OPEN-{$idx}", "Must only have exactly 1 <script opening tag, found {$scriptOpenCount}");
            $this->assert($scriptCloseCount === 1, "C5.2-SINGLE-SCRIPT-CLOSE-{$idx}", "Must only have exactly 1 </script closing tag, found {$scriptCloseCount}");

            // Test 3: Must still decode validly via json_decode
            $decoded = json_decode($encoded, true);
            $this->assert(
                json_last_error() === JSON_ERROR_NONE,
                "C5.3-VALID-JSON-DECODABLE-{$idx}",
                "JSON must parse back without errors: " . json_last_error_msg()
            );
            $this->assert(
                isset($decoded['name']),
                "C5.3-DECODED-NAME-EXISTS-{$idx}",
                "Decoded object must preserve properties"
            );
        }

        // Test 4: End-to-end injection through batdongsan_get_schema_real_estate_listing()
        $maliciousPostId = MockWordPress::insertPost([
            'post_title' => 'Biệt thự </script><script>alert("XSS")</script> CIPUTRA',
            'post_type'  => 'bat-dong-san',
        ]);
        $this->setMeta($maliciousPostId, 'dia_chi', '</script><script>alert("ADDR")</script> Tây Hồ, Hà Nội');
        $this->setMeta($maliciousPostId, 'khoang_gia', '50 tỷ');
        $this->setMeta($maliciousPostId, 'dien_tich', '300');

        $maliciousListing = batdongsan_get_schema_real_estate_listing($maliciousPostId);

        ob_start();
        batdongsan_output_schema_json_ld($maliciousListing);
        $fullOutput = ob_get_clean();

        $this->assert(!str_contains($fullOutput, '<script>alert'), 'C5.4-MALICIOUS-POST-NO-EXECUTABLE-SCRIPT');
        $this->assert(!str_contains($fullOutput, '</script><script>'), 'C5.4-MALICIOUS-POST-NO-BREAKOUT');
        $scriptTagOpens = substr_count(strtolower($fullOutput), '<script');
        $scriptTagCloses = substr_count(strtolower($fullOutput), '</script>');
        $this->assert($scriptTagOpens === 1 && $scriptTagCloses === 1, 'C5.4-MALICIOUS-POST-SCRIPT-TAGS-BALANCED');
    }

    /**
     * Challenge 6: JavaScript syntax, AST structure, bracket balancing, and mock execution
     */
    private function challengeJavaScriptSyntaxAndAst(): void {
        echo "\n--------------------------------------------------------------------------------\n";
        echo "▶ Challenge 6: Global JavaScript Syntax, AST & Execution Stress-Testing\n";
        echo "--------------------------------------------------------------------------------\n";

        $jsFiles = [
            'js/project-map.js',
            'js/main.js',
        ];

        foreach ($jsFiles as $file) {
            $path = $this->themeDir . '/' . $file;
            $this->assert(file_exists($path), "C6.1-FILE-EXISTS-{$file}", "{$file} must exist");

            // 6.1 Node syntax compilation check
            $cmd = "node -c " . escapeshellarg($path) . " 2>&1";
            exec($cmd, $output, $code);
            $this->assert($code === 0, "C6.1-NODE-C-SYNTAX-{$file}", implode("\n", $output));

            $content = file_get_contents($path);

            // 6.2 Balanced brackets check
            $openCurly = substr_count($content, '{');
            $closeCurly = substr_count($content, '}');
            $this->assert($openCurly === $closeCurly, "C6.2-BALANCED-CURLY-BRACES-{$file}", "Curly braces: {$openCurly} vs {$closeCurly}");

            $openParen = substr_count($content, '(');
            $closeParen = substr_count($content, ')');
            $this->assert($openParen === $closeParen, "C6.2-BALANCED-PARENS-{$file}", "Parentheses: {$openParen} vs {$closeParen}");

            $openBracket = substr_count($content, '[');
            $closeBracket = substr_count($content, ']');
            $this->assert($openBracket === $closeBracket, "C6.2-BALANCED-SQUARE-BRACKETS-{$file}", "Square brackets: {$openBracket} vs {$closeBracket}");
        }

        // 6.3 Dedicated check on js/project-map.js legacy IIFE fix
        $mapContent = file_get_contents($this->themeDir . '/js/project-map.js');
        $this->assert(!str_contains($mapContent, '})();'), 'C6.3-PROJECT-MAP-NO-IIFE-ANOMALY', 'Should not contain erroneous })(); at EOF');
        $this->assert(preg_match('/function\s+initMap\s*\(\)\s*\{/', $mapContent) === 1, 'C6.3-PROJECT-MAP-INITMAP-EXISTS');

        // 6.4 Node.js runtime mock execution test
        $nodeTestScript = <<<'NODE'
const vm = require('vm');
const fs = require('fs');

const mockWindow = {
    addEventListener: () => {},
    initProjectMap: null,
    google: {
        maps: {
            Map: class { setCenter() {} setZoom() {} fitBounds() {} },
            Marker: class { setMap() {} addListener() {} },
            InfoWindow: class { open() {} close() {} setContent() {} },
            LatLngBounds: class { extend() {} },
            places: {
                PlacesService: class { nearbySearch(req, cb) { cb([], 'OK'); } },
                PlacesServiceStatus: { OK: 'OK' }
            }
        }
    },
    document: {
        getElementById: () => ({ innerHTML: '', addEventListener: () => {} }),
        querySelector: () => ({ innerHTML: '', addEventListener: () => {} }),
        querySelectorAll: () => []
    }
};
mockWindow.window = mockWindow;
mockWindow.global = mockWindow;

const mapJsCode = fs.readFileSync('js/project-map.js', 'utf8');

try {
    const context = vm.createContext(mockWindow);
    vm.runInContext(mapJsCode, context);
    console.log('VM_RUN_SUCCESS');
} catch (err) {
    console.error('VM_RUN_ERROR:', err.message);
    process.exit(1);
}
NODE;

        $tempScript = $this->themeDir . '/tests/temp-test-vm-map.js';
        file_put_contents($tempScript, $nodeTestScript);

        $vmCmd = "node " . escapeshellarg($tempScript) . " 2>&1";
        exec($vmCmd, $vmOutput, $vmExit);
        @unlink($tempScript);

        $this->assert($vmExit === 0, 'C6.4-VM-MOCK-EXECUTION-PROJECT-MAP', implode("\n", $vmOutput));
        $this->assert(in_array('VM_RUN_SUCCESS', $vmOutput, true), 'C6.4-VM-MOCK-OUTPUT-SUCCESS');
    }
}

// Instantiate and run
$challenger = new M6ChallengerStressHarness();
$passed = $challenger->run();

exit($passed ? 0 : 1);
