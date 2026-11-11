<?php

namespace BDS\Tests\E2E\Tier2;

use BDS\Tests\Framework\TestCase;
use BDS\Tests\Framework\MockWordPress;
use BDS\Tests\Framework\RedirectExitException;

class TestR1BoundaryCases extends TestCase {

    /**
     * T2-R1-01: Empty database migration boundary.
     */
    public function test_r1_b01_empty_database_migration(): void {
        $migrationFile = $this->themeDir . '/inc/services/migration.php';
        $this->assertTrue(file_exists($migrationFile), 'inc/services/migration.php must exist.');

        require_once $migrationFile;

        // Ensure database has zero legacy posts
        MockWordPress::$posts = [];

        $result = \BDS_CPT_Migration::run(['dry_run' => false, 'batch_size' => 50]);

        $this->assertTrue($result['success'] ?? false, 'Empty migration must succeed gracefully.');
        $this->assertEquals(0, $result['migrated_count'] ?? -1, 'Migrated count must be 0.');
    }

    /**
     * T2-R1-02: Corrupted serialized metadata handling.
     */
    public function test_r1_b02_corrupted_serialized_metadata(): void {
        $migrationFile = $this->themeDir . '/inc/services/migration.php';
        $this->assertTrue(file_exists($migrationFile), 'inc/services/migration.php must exist.');

        require_once $migrationFile;

        // Insert legacy post with broken serialized string
        $postId = MockWordPress::insertPost([
            'post_title' => 'Căn hộ hỏng meta',
            'post_name' => 'can-ho-hong-meta',
            'post_type' => 'hang-chuyen-nhuong',
            'post_status' => 'publish',
            'meta_input' => [
                'du_an_lien_quan' => 'a:2:{i:0;i:123;BROKEN_STRING',
            ],
        ]);

        $result = \BDS_CPT_Migration::run(['dry_run' => false, 'batch_size' => 10]);

        $this->assertTrue($result['success'], 'Migration must not crash on malformed serialized string.');
        $post = MockWordPress::getPost($postId);
        $this->assertEquals('bat-dong-san', $post->post_type);
    }

    /**
     * T2-R1-03: Unicode and percent-encoded legacy slug redirects.
     */
    public function test_r1_b03_unicode_slug_redirects(): void {
        $redirectsFile = $this->themeDir . '/inc/hooks/redirects.php';
        $this->assertTrue(file_exists($redirectsFile), 'inc/hooks/redirects.php must exist.');

        require_once $redirectsFile;

        if (function_exists('batdongsan_redirect_legacy_urls')) {
            $_SERVER['REQUEST_URI'] = '/hang-chuyen-nhuong/%C4%91%E1%BA%A5t-n%E1%BB%81n-%C4%91%C3%B4ng-anh';
            $caught = false;
            try {
                batdongsan_redirect_legacy_urls();
            } catch (RedirectExitException $e) {
                $caught = true;
                $this->assertEquals(301, $e->getStatus());
                $this->assertContains('/bat-dong-san/', $e->getLocation());
            }
            $this->assertTrue($caught, 'Unicode legacy URL must be safely redirected with HTTP 301.');
        }
    }

    /**
     * T2-R1-04: Post slug collision handling during migration.
     */
    public function test_r1_b04_migration_duplicate_slug_handling(): void {
        $migrationFile = $this->themeDir . '/inc/services/migration.php';
        $this->assertTrue(file_exists($migrationFile), 'inc/services/migration.php must exist.');

        require_once $migrationFile;

        // Existing post in bat-dong-san with slug 'vinhomes-ocean-park'
        MockWordPress::insertPost([
            'post_title' => 'Vinhomes Ocean Park Cũ',
            'post_name' => 'vinhomes-ocean-park',
            'post_type' => 'bat-dong-san',
            'post_status' => 'publish',
        ]);

        // Legacy post with identical slug
        $legacyId = MockWordPress::insertPost([
            'post_title' => 'Vinhomes Ocean Park Mới',
            'post_name' => 'vinhomes-ocean-park',
            'post_type' => 'nha-dat-ban',
            'post_status' => 'publish',
        ]);

        $result = \BDS_CPT_Migration::run(['dry_run' => false]);
        $this->assertTrue($result['success']);

        $migratedPost = MockWordPress::getPost($legacyId);
        $this->assertEquals('bat-dong-san', $migratedPost->post_type);
        // Slugs must be preserved or uniquely suffixed
        $this->assertTrue(!empty($migratedPost->post_name), 'Post name must not be empty.');
    }

    /**
     * T2-R1-05: Orphan term cleanup during migration.
     */
    public function test_r1_b05_orphan_term_cleanup(): void {
        $migrationFile = $this->themeDir . '/inc/services/migration.php';
        $this->assertTrue(file_exists($migrationFile), 'inc/services/migration.php must exist.');

        require_once $migrationFile;

        // Legacy post with non-existent term ID
        $postId = MockWordPress::insertPost([
            'post_title' => 'BĐS orphan term',
            'post_name' => 'bds-orphan-term',
            'post_type' => 'nha-dat-ban',
            'tax_input' => [
                'danh-muc-nha-dat-ban' => [999999], // non-existent term
            ],
        ]);

        $result = \BDS_CPT_Migration::run(['dry_run' => false]);
        $this->assertTrue($result['success'], 'Migration must complete cleanly when posts have orphan terms.');
    }
}
