<?php
/**
 * BDS Meta Sync Service
 *
 * Automatic calculation and persistence of normalized numeric postmeta:
 * - `_bds_price_million` (float)
 * - `_bds_area_m2` (float)
 * - `_bds_is_thoa_thuan` (1 or 0)
 * - Subproject `address` inheritance from parent `du_an_cha`
 *
 * @package BatDongSan
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class BDS_Meta_Sync_Service {

    const DB_VERSION_OPTION = 'bds_meta_sync_db_version';
    const CURRENT_VERSION   = '1.1.0';

    /**
     * Reentrancy guard to prevent recursive hook firing on update_post_meta.
     *
     * @var array<int, bool>
     */
    private static array $syncing = [];

    /**
     * Initialize service hooks.
     */
    public static function init(): void {
        // High priority 25 ensures ACF has already persisted fields on save
        add_action('save_post_bat-dong-san', [__CLASS__, 'on_save_bat_dong_san'], 25, 3);
        add_action('save_post_du-an', [__CLASS__, 'on_save_du_an'], 25, 3);
        add_action('acf/save_post', [__CLASS__, 'on_acf_save_post'], 25, 1);

        // Lifecycle deletion and status changes
        add_action('delete_post', [__CLASS__, 'on_post_lifecycle_change'], 20, 1);
        add_action('trashed_post', [__CLASS__, 'on_post_lifecycle_change'], 20, 1);
        add_action('untrashed_post', [__CLASS__, 'on_post_lifecycle_change'], 20, 1);

        // Runtime self-healing migration on admin init
        add_action('admin_init', [__CLASS__, 'maybe_run_runtime_migration']);

        // Register WP-CLI command if running in CLI environment
        if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
            WP_CLI::add_command('bds sync-meta', [__CLASS__, 'cli_sync_meta']);
        }
    }

    /**
     * Hook: save_post_bat-dong-san
     */
    public static function on_save_bat_dong_san($post_id, $post = null, $update = null): void {
        if (self::should_skip_sync($post_id)) {
            return;
        }
        self::sync_listing_meta((int) $post_id);
    }

    /**
     * Hook: save_post_du-an
     */
    public static function on_save_du_an($post_id, $post = null, $update = null): void {
        if (self::should_skip_sync($post_id)) {
            return;
        }
        self::sync_project_meta((int) $post_id);
    }

    /**
     * Hook: acf/save_post
     */
    public static function on_acf_save_post($post_id): void {
        if (!is_numeric($post_id) || (int) $post_id <= 0) {
            return;
        }
        $post_id = (int) $post_id;
        if (self::should_skip_sync($post_id)) {
            return;
        }

        $post_type = get_post_type($post_id);
        if ($post_type === 'bat-dong-san' || $post_type === 'hang-chuyen-nhuong' || $post_type === 'nha-dat-ban') {
            self::sync_listing_meta($post_id);
        } elseif ($post_type === 'du-an') {
            self::sync_project_meta($post_id);
        }
    }

    /**
     * Hook: delete_post, trashed_post, untrashed_post
     */
    public static function on_post_lifecycle_change($post_id): void {
        if (!is_numeric($post_id) || (int) $post_id <= 0) {
            return;
        }
        $post_type = get_post_type($post_id);
        if (in_array($post_type, ['bat-dong-san', 'du-an', 'hang-chuyen-nhuong', 'nha-dat-ban'], true)) {
            if (class_exists('BDS_Filter_Options_Service')) {
                BDS_Filter_Options_Service::flush_all_caches();
            }
        }
    }

    /**
     * Synchronize and persist numeric metadata for a BĐS listing.
     *
     * @param int $post_id
     * @return array [ '_bds_price_million' => float, '_bds_area_m2' => float, '_bds_is_thoa_thuan' => int ]
     */
    public static function sync_listing_meta(int $post_id): array {
        if (isset(self::$syncing[$post_id])) {
            return [];
        }
        self::$syncing[$post_id] = true;

        try {
            // 1. Fetch raw ACF and postmeta values
            $raw_khoang_gia   = get_post_meta($post_id, 'khoang_gia', true);
            $raw_gia_cho_thue = get_post_meta($post_id, 'gia_cho_thue', true);
            $raw_gia_trieu    = get_post_meta($post_id, 'gia_trieu', true);
            $raw_dien_tich    = get_post_meta($post_id, 'dien_tich', true);
            $existing_price   = get_post_meta($post_id, '_bds_price_million', true);
            $existing_area    = get_post_meta($post_id, '_bds_area_m2', true);

            if ($raw_khoang_gia === '' && function_exists('get_field')) {
                $raw_khoang_gia = get_field('khoang_gia', $post_id);
            }
            if ($raw_gia_cho_thue === '' && function_exists('get_field')) {
                $raw_gia_cho_thue = get_field('gia_cho_thue', $post_id);
            }
            if ($raw_dien_tich === '' && function_exists('get_field')) {
                $raw_dien_tich = get_field('dien_tich', $post_id);
            }

            // 2. Evaluate Thỏa Thuận price
            $is_thoa_thuan = 0;
            if (function_exists('batdongsan_is_thoa_thuan_price') && batdongsan_is_thoa_thuan_price($post_id)) {
                $is_thoa_thuan = 1;
            } else {
                $combined_price_text = trim((string) $raw_khoang_gia) . ' ' . trim((string) $raw_gia_cho_thue);
                if (preg_match('/th[oỏ][aả]\s*thu[aậ]n|thoa\s*thuan|liên hệ|lien he|call|call\s*zalo/iu', $combined_price_text)) {
                    $is_thoa_thuan = 1;
                }
            }

            // 3. Compute price in million VND
            $price_million = 0.0;
            if ($is_thoa_thuan !== 1) {
                if ($raw_gia_trieu !== '' && is_numeric($raw_gia_trieu)) {
                    $price_million = (float) $raw_gia_trieu;
                } elseif (function_exists('batdongsan_get_total_price_million')) {
                    $parsed = batdongsan_get_total_price_million($post_id);
                    if ($parsed !== null && $parsed > 0) {
                        $price_million = (float) $parsed;
                    }
                }
                if ($price_million <= 0 && function_exists('batdongsan_parse_total_price_million')) {
                    $text = trim((string) $raw_khoang_gia) ?: trim((string) $raw_gia_cho_thue);
                    if ($text !== '') {
                        $parsed = batdongsan_parse_total_price_million($text);
                        if ($parsed !== null && $parsed > 0) {
                            $price_million = (float) $parsed;
                        }
                    }
                }
                if ($price_million <= 0 && $existing_price !== '' && is_numeric($existing_price) && (float) $existing_price > 0) {
                    $price_million = (float) $existing_price;
                }
            }

            // 4. Compute area in m²
            $area_m2 = 0.0;
            if (function_exists('batdongsan_get_area_number')) {
                $parsed_area = batdongsan_get_area_number($post_id);
                if ($parsed_area !== null && $parsed_area > 0) {
                    $area_m2 = (float) $parsed_area;
                }
            }
            if ($area_m2 <= 0 && is_numeric($raw_dien_tich)) {
                $area_m2 = (float) $raw_dien_tich;
            } elseif ($area_m2 <= 0 && is_string($raw_dien_tich) && trim($raw_dien_tich) !== '') {
                if (preg_match('/([\d\.,]+)/u', $raw_dien_tich, $matches) && !empty($matches[1])) {
                    if (function_exists('batdongsan_normalize_price_number')) {
                        $area_m2 = (float) batdongsan_normalize_price_number($matches[1]);
                    } else {
                        $normalized_str = str_replace(['.', ','], ['', '.'], $matches[1]);
                        $area_m2 = (float) $normalized_str;
                    }
                }
            }
            if ($area_m2 <= 0 && $existing_area !== '' && is_numeric($existing_area) && (float) $existing_area > 0) {
                $area_m2 = (float) $existing_area;
            }

            // 5. Atomic database persistence
            update_post_meta($post_id, '_bds_price_million', (float) $price_million);
            update_post_meta($post_id, '_bds_area_m2', (float) $area_m2);
            update_post_meta($post_id, '_bds_is_thoa_thuan', (int) $is_thoa_thuan);
            update_post_meta($post_id, 'gia', (float) $price_million); // Backwards compatibility

            // 6. Invalidate options cache
            if (class_exists('BDS_Filter_Options_Service')) {
                BDS_Filter_Options_Service::flush_all_caches();
            }

            return [
                '_bds_price_million' => (float) $price_million,
                '_bds_area_m2'       => (float) $area_m2,
                '_bds_is_thoa_thuan' => (int) $is_thoa_thuan,
            ];
        } finally {
            unset(self::$syncing[$post_id]);
        }
    }

    /**
     * Alias for sync_listing_meta.
     *
     * @param int $post_id
     * @return array
     */
    public static function sync_bds_meta(int $post_id): array {
        return self::sync_listing_meta($post_id);
    }

    /**
     * Synchronize metadata and inherit address for a Project.
     *
     * @param int $post_id
     * @return array [ 'address' => string ]
     */
    public static function sync_project_meta(int $post_id): array {
        if (isset(self::$syncing[$post_id])) {
            return [];
        }
        self::$syncing[$post_id] = true;

        try {
            $address = get_post_meta($post_id, 'address', true);
            if (empty($address) && function_exists('get_field')) {
                $address = get_field('address', $post_id);
            }

            // Inherit from parent project if child address is empty
            if (empty($address)) {
                $parent_raw = get_post_meta($post_id, 'du_an_cha', true);
                if (empty($parent_raw) && function_exists('get_field')) {
                    $parent_raw = get_field('du_an_cha', $post_id);
                }

                $parent_id = is_object($parent_raw) ? (int) $parent_raw->ID : (int) $parent_raw;
                if ($parent_id > 0) {
                    $parent_address = function_exists('batdongsan_get_project_meta_recursive')
                        ? batdongsan_get_project_meta_recursive('address', $parent_id)
                        : get_post_meta($parent_id, 'address', true);

                    if (!empty($parent_address)) {
                        $address = (string) $parent_address;
                        update_post_meta($post_id, 'address', $address);
                        if (function_exists('update_field')) {
                            update_field('address', $address, $post_id);
                        }
                    }
                }
            } else {
                // If this is a parent project with an address, cascade update to child projects with empty addresses
                self::cascade_address_to_children($post_id, (string) $address);
            }

            if (class_exists('BDS_Filter_Options_Service')) {
                BDS_Filter_Options_Service::flush_all_caches();
            }

            return ['address' => (string) $address];
        } finally {
            unset(self::$syncing[$post_id]);
        }
    }

    /**
     * Cascade address from a parent project to child projects if child address is empty.
     */
    private static function cascade_address_to_children(int $parent_id, string $parent_address): void {
        if ($parent_id <= 0 || empty($parent_address)) {
            return;
        }

        global $wpdb;
        if (!$wpdb || !method_exists($wpdb, 'get_col') || !method_exists($wpdb, 'prepare')) {
            return;
        }

        // Find child projects pointing to this parent
        $child_ids = $wpdb->get_col($wpdb->prepare("
            SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = 'du_an_cha' AND meta_value = %s
        ", (string) $parent_id));

        if (!empty($child_ids)) {
            foreach ($child_ids as $cid) {
                $cid = (int) $cid;
                $child_addr = get_post_meta($cid, 'address', true);
                if (empty($child_addr)) {
                    update_post_meta($cid, 'address', $parent_address);
                    if (function_exists('update_field')) {
                        update_field('address', $parent_address, $cid);
                    }
                }
            }
        }
    }

    /**
     * Batch migrate all existing posts to populate normalized metadata.
     *
     * @param bool $force
     * @return array [ 'bds_count' => int, 'du_an_count' => int ]
     */
    public static function migrate_all_posts(bool $force = false): array {
        global $wpdb;

        $bds_count = 0;
        $du_an_count = 0;

        if (!$wpdb || !method_exists($wpdb, 'get_col')) {
            return ['bds_count' => 0, 'du_an_count' => 0];
        }

        // Migrate BĐS listings
        $bds_ids = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type IN ('bat-dong-san', 'hang-chuyen-nhuong', 'nha-dat-ban')
              AND post_status IN ('publish', 'draft', 'pending')
        ");

        if (!empty($bds_ids)) {
            foreach ($bds_ids as $id) {
                self::sync_listing_meta((int) $id);
                $bds_count++;
            }
        }

        // Migrate Projects
        $du_an_ids = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'du-an'
              AND post_status IN ('publish', 'draft', 'pending')
        ");

        if (!empty($du_an_ids)) {
            foreach ($du_an_ids as $id) {
                self::sync_project_meta((int) $id);
                $du_an_count++;
            }
        }

        update_option(self::DB_VERSION_OPTION, self::CURRENT_VERSION);
        if (class_exists('BDS_Filter_Options_Service')) {
            BDS_Filter_Options_Service::flush_all_caches();
        }

        return [
            'bds_count'   => $bds_count,
            'du_an_count' => $du_an_count,
        ];
    }

    /**
     * Runtime self-healing migration triggered on admin_init.
     */
    public static function maybe_run_runtime_migration(): void {
        $installed_ver = get_option(self::DB_VERSION_OPTION, '0.0.0');
        if (version_compare((string) $installed_ver, self::CURRENT_VERSION, '<')) {
            // Prevent multiple concurrent requests running migration
            $lock = get_transient('bds_meta_sync_migrating');
            if ($lock !== false) {
                return;
            }
            set_transient('bds_meta_sync_migrating', 1, 300);

            self::migrate_all_posts();

            delete_transient('bds_meta_sync_migrating');
        }
    }

    /**
     * WP-CLI migration command implementation.
     * Usage: wp bds sync-meta [--force]
     */
    public static function cli_sync_meta($args, $assoc_args): void {
        $force = isset($assoc_args['force']);
        if (class_exists('WP_CLI')) {
            WP_CLI::line("Starting BDS numeric postmeta synchronization...");
        }
        $start = microtime(true);

        $result = self::migrate_all_posts($force);

        $elapsed = round(microtime(true) - $start, 2);
        if (class_exists('WP_CLI')) {
            WP_CLI::success(sprintf(
                "Synchronized %d BĐS listings and %d Projects in %s seconds. Database version: %s.",
                $result['bds_count'],
                $result['du_an_count'],
                $elapsed,
                self::CURRENT_VERSION
            ));
        }
    }

    /**
     * Check if post synchronization should be skipped.
     */
    private static function should_skip_sync($post_id): bool {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return true;
        }
        if (function_exists('wp_is_post_revision') && wp_is_post_revision($post_id)) {
            return true;
        }
        if (function_exists('wp_is_post_autosave') && wp_is_post_autosave($post_id)) {
            return true;
        }
        return false;
    }
}
