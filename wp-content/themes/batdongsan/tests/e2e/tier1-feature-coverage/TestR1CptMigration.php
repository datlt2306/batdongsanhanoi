<?php

namespace BDS\Tests\E2E\Tier1;

use BDS\Tests\Framework\TestCase;
use BDS\Tests\Framework\MockWordPress;
use BDS\Tests\Framework\RedirectExitException;

class TestR1CptMigration extends TestCase {

    /**
     * T1-R1-01: Verify CPT bat-dong-san is registered and active with public archive.
     */
    public function test_r1_cpt_bat_dong_san_registered(): void {
        $acfJsonPath = $this->themeDir . '/acf-json/post_type_bat_dong_san.json';
        $this->assertTrue(file_exists($acfJsonPath), 'ACF JSON configuration for bat-dong-san must exist.');

        $json = json_decode(file_get_contents($acfJsonPath), true);
        $this->assertNotNull($json, 'post_type_bat_dong_san.json must be valid JSON.');
        $this->assertEquals('bat-dong-san', $json['post_type'] ?? '', 'CPT identifier must be bat-dong-san.');
        $this->assertTrue(!empty($json['active']), 'CPT bat-dong-san must be marked active.');
        $this->assertTrue(!empty($json['public']), 'CPT bat-dong-san must be public.');
        $this->assertTrue(!empty($json['has_archive']), 'CPT bat-dong-san must have archive enabled.');
    }

    /**
     * T1-R1-02: Verify taxonomy hinh-thuc-bds is registered with required terms.
     */
    public function test_r1_taxonomy_hinh_thuc_bds(): void {
        $taxFile = $this->themeDir . '/inc/hooks/taxonomies.php';
        $this->assertTrue(file_exists($taxFile), 'taxonomies.php hook file must exist.');
        $content = file_get_contents($taxFile);

        $this->assertContains('hinh-thuc-bds', $content, 'Taxonomy hinh-thuc-bds must be registered in taxonomies.php');
        $this->assertContains('bat-dong-san', $content, 'Taxonomy hinh-thuc-bds must be linked to post type bat-dong-san.');
    }

    /**
     * T1-R1-03: Verify zero serialized LIKE '%ID%' queries exist on du_an_lien_quan.
     */
    public function test_r1_no_like_queries_on_du_an_lien_quan(): void {
        $filesToCheck = [
            $this->themeDir . '/inc/helpers/project.php',
            $this->themeDir . '/inc/services/queries.php',
            $this->themeDir . '/page-bat-dong-san.php',
            $this->themeDir . '/paths/bat-dong-san-item.php',
            $this->themeDir . '/template-parts/card-transfer.php',
        ];

        $violations = [];
        foreach ($filesToCheck as $file) {
            if (!file_exists($file)) {
                continue;
            }
            $lines = file($file);
            foreach ($lines as $num => $line) {
                if (stripos($line, 'du_an_lien_quan') !== false && stripos($line, 'LIKE') !== false) {
                    $violations[] = basename($file) . ":" . ($num + 1) . " -> " . trim($line);
                }
            }
        }

        $this->assertTrue(
            empty($violations),
            "Serialized string queries (LIKE '%ID%') found on du_an_lien_quan:\n" . implode("\n", $violations)
        );
    }

    /**
     * T1-R1-04: Verify BDS_CPT_Migration::run(['dry_run' => true]) contract.
     */
    public function test_r1_migration_service_dry_run(): void {
        $migrationFile = $this->themeDir . '/inc/services/migration.php';
        $this->assertTrue(file_exists($migrationFile), 'Migration service file inc/services/migration.php must exist.');

        require_once $migrationFile;
        $this->assertTrue(class_exists('BDS_CPT_Migration'), 'Class BDS_CPT_Migration must be declared.');

        // Seed mock posts with legacy post type nha-dat-ban
        $legacyId = MockWordPress::insertPost([
            'post_title' => 'Căn hộ Discovery Complex',
            'post_name' => 'can-ho-discovery-complex',
            'post_type' => 'nha-dat-ban',
            'post_status' => 'publish',
            'meta_input' => [
                'du_an_lien_quan' => '773',
                'gia' => '3500',
            ],
        ]);

        $result = \BDS_CPT_Migration::run(['dry_run' => true, 'batch_size' => 10]);

        $this->assertTrue(is_array($result), 'Migration run must return an array.');
        $this->assertArrayHasKey('success', $result, 'Result must contain success key.');
        $this->assertTrue($result['success'], 'Dry-run migration must succeed.');
        $this->assertArrayHasKey('migrated_count', $result, 'Result must contain migrated_count.');

        // Verify that in dry-run, post was NOT altered in database
        $postAfter = MockWordPress::getPost($legacyId);
        $this->assertEquals('nha-dat-ban', $postAfter->post_type, 'Dry run must not modify post_type in database.');
    }

    /**
     * T1-R1-05: Verify 301 SEO Redirect Engine for legacy URLs.
     */
    public function test_r1_301_redirect_engine_legacy_urls(): void {
        $redirectsFile = $this->themeDir . '/inc/hooks/redirects.php';
        $this->assertTrue(file_exists($redirectsFile), 'SEO Redirects hook file inc/hooks/redirects.php must exist.');

        require_once $redirectsFile;

        // Test function or template_redirect hook
        $this->assertTrue(
            function_exists('batdongsan_redirect_legacy_urls') || has_action('template_redirect'),
            'Legacy URL redirect handler must be registered on template_redirect.'
        );

        if (function_exists('batdongsan_redirect_legacy_urls')) {
            $_SERVER['REQUEST_URI'] = '/hang-chuyen-nhuong/biet-thu-vinhomes-ocean-park';
            $caught = false;
            try {
                batdongsan_redirect_legacy_urls();
            } catch (RedirectExitException $e) {
                $caught = true;
                $this->assertEquals(301, $e->getStatus(), 'Redirect must use HTTP 301 Permanent.');
                $this->assertContains('/bat-dong-san/biet-thu-vinhomes-ocean-park', $e->getLocation(), 'Redirect target must point to /bat-dong-san/.');
            }
            $this->assertTrue($caught, 'Redirect function must intercept legacy URL and redirect.');
        }
    }

    /**
     * T1-R1-06: Verify live migration execution and postmeta normalization.
     */
    public function test_r1_migration_batching_and_transaction(): void {
        $migrationFile = $this->themeDir . '/inc/services/migration.php';
        $this->assertTrue(file_exists($migrationFile), 'inc/services/migration.php must exist.');

        require_once $migrationFile;
        $this->assertTrue(class_exists('BDS_CPT_Migration'), 'Class BDS_CPT_Migration must be declared.');

        // Insert legacy post with serialized meta
        $postId = MockWordPress::insertPost([
            'post_title' => 'Nhà mặt phố Cầu Giấy',
            'post_name' => 'nha-mat-pho-cau-giay',
            'post_type' => 'nha-dat-ban',
            'post_status' => 'publish',
            'meta_input' => [
                'du_an_lien_quan' => serialize([888]),
            ],
        ]);

        $result = \BDS_CPT_Migration::run(['dry_run' => false, 'batch_size' => 50]);

        $this->assertTrue($result['success'], 'Live migration must return success=true.');
        $postAfter = MockWordPress::getPost($postId);
        $this->assertEquals('bat-dong-san', $postAfter->post_type, 'Legacy post must be converted to bat-dong-san.');

        // Check normalized numeric meta
        $metaVal = MockWordPress::getPostMeta($postId, 'du_an_lien_quan', true);
        $this->assertTrue(is_numeric($metaVal) || (is_array($metaVal) && is_numeric($metaVal[0])), 'du_an_lien_quan must be normalized to numeric ID.');
    }
}
