<?php
/**
 * Safe CPT Migration Engine for BatDongSan Theme.
 *
 * Migrates legacy custom post types (hang-chuyen-nhuong, nha-dat-ban, bds-cho-thue, nha-dat-cho-thue)
 * into unified CPT 'bat-dong-san' with taxonomy mapping, relationship normalization,
 * transactional integrity, dry-run support, and rollback capabilities.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

class BDS_CPT_Migration {

    public const DEFAULT_SOURCE_TYPES = [
        'hang-chuyen-nhuong',
        'nha-dat-ban',
        'bds-cho-thue',
        'nha-dat-cho-thue',
    ];

    public const TARGET_POST_TYPE = 'bat-dong-san';
    public const DEFAULT_BATCH_SIZE = 50;

    /**
     * Run migration or dry-run.
     *
     * @param array $args Options array.
     * @return array Result summary.
     */
    public static function run(array $args = []): array {
        $dry_run      = !empty($args['dry_run']);
        $batch_size   = isset($args['batch_size']) && (int) $args['batch_size'] > 0 ? (int) $args['batch_size'] : self::DEFAULT_BATCH_SIZE;
        $source_types = !empty($args['source_post_types']) && is_array($args['source_post_types'])
            ? $args['source_post_types']
            : self::DEFAULT_SOURCE_TYPES;
        $batch_id     = !empty($args['batch_id']) ? sanitize_text_field($args['batch_id']) : 'mig_' . gmdate('Ymd_His') . '_' . wp_unique_id();
        $verbose      = !empty($args['verbose']);

        $migrated_count = 0;
        $skipped_count  = 0;
        $error_count    = 0;
        $errors         = [];
        $log            = [];

        // 1. Candidate discovery
        $candidate_ids = self::get_candidate_post_ids($source_types);

        if (empty($candidate_ids)) {
            return [
                'success'        => true,
                'migrated_count' => 0,
                'skipped_count'  => 0,
                'error_count'    => 0,
                'errors'         => [],
                'log'            => [],
                'batches'        => 0,
                'batch_id'       => $batch_id,
                'dry_run'        => $dry_run,
            ];
        }

        // 2. Chunk into batches
        $chunks = array_chunk($candidate_ids, $batch_size);
        $total_batches = count($chunks);

        global $wpdb;

        foreach ($chunks as $chunk_index => $batch_post_ids) {
            // Start transaction
            if (isset($wpdb) && method_exists($wpdb, 'query')) {
                $wpdb->query('START TRANSACTION');
            }

            $batch_success  = true;
            $batch_migrated = 0;
            $batch_log      = [];

            foreach ($batch_post_ids as $post_id) {
                try {
                    $post = get_post($post_id);
                    if (!$post || $post->post_type === self::TARGET_POST_TYPE) {
                        $skipped_count++;
                        continue;
                    }

                    $source_type = $post->post_type;
                    $raw_du_an   = get_post_meta($post_id, 'du_an_lien_quan', true);
                    $clean_du_an = self::extract_clean_project_id($raw_du_an);

                    // Map transaction type
                    $hinh_thuc_slug = self::map_transaction_type($source_type, $post_id);

                    // Collect taxonomy terms
                    $term_slugs = self::collect_property_type_terms($post_id);

                    if ($dry_run) {
                        // Dry-run mode: do NOT modify database
                        $batch_migrated++;
                        if ($verbose || (count($log) + count($batch_log)) < 100) {
                            $batch_log[] = [
                                'id'         => $post_id,
                                'title'      => $post->post_title,
                                'source_cpt' => $source_type,
                                'project_id' => $clean_du_an,
                                'hinh_thuc'  => $hinh_thuc_slug,
                                'loai_hinh'  => implode(', ', $term_slugs),
                                'status'     => 'simulated',
                            ];
                        }
                    } else {
                        // Live mode: apply changes
                        // Check slug collisions with existing bat-dong-san posts
                        $final_slug = self::resolve_slug_collision($post->post_name, $post_id);

                        // Save audit metadata before altering
                        update_post_meta($post_id, '_migrated_from_post_type', $source_type);
                        update_post_meta($post_id, '_migrated_from_post_id', $post_id);
                        update_post_meta($post_id, '_migrated_batch_id', $batch_id);
                        update_post_meta($post_id, '_migrated_at', gmdate('c'));
                        if (!empty($raw_du_an)) {
                            update_post_meta($post_id, '_migrated_du_an_raw', $raw_du_an);
                        }

                        // Update post_type to bat-dong-san
                        $update_data = [
                            'ID'        => $post_id,
                            'post_type' => self::TARGET_POST_TYPE,
                        ];
                        if ($final_slug !== $post->post_name) {
                            $update_data['post_name'] = $final_slug;
                        }

                        wp_update_post($update_data);

                        // Normalize du_an_lien_quan
                        update_post_meta($post_id, 'du_an_lien_quan', $clean_du_an);
                        update_post_meta($post_id, '_du_an_lien_quan', 'field_du_an_lien_quan');
                        if (function_exists('update_field')) {
                            update_field('du_an_lien_quan', $clean_du_an > 0 ? (string) $clean_du_an : '', $post_id);
                        }

                        // Assign taxonomies
                        if (!empty($hinh_thuc_slug)) {
                            wp_set_object_terms($post_id, [$hinh_thuc_slug], 'hinh-thuc-bds', true);
                        }
                        if (!empty($term_slugs)) {
                            wp_set_object_terms($post_id, array_unique($term_slugs), 'loai_hinh_bat_dong_san', true);
                        }

                        $batch_migrated++;
                        if ($verbose || (count($log) + count($batch_log)) < 100) {
                            $batch_log[] = [
                                'id'         => $post_id,
                                'title'      => $post->post_title,
                                'source_cpt' => $source_type,
                                'project_id' => $clean_du_an,
                                'hinh_thuc'  => $hinh_thuc_slug,
                                'loai_hinh'  => implode(', ', $term_slugs),
                                'status'     => 'migrated',
                            ];
                        }
                    }

                    if (function_exists('clean_post_cache')) {
                        clean_post_cache($post_id);
                    }
                } catch (\Throwable $e) {
                    $batch_success = false;
                    $error_count++;
                    $errors[] = sprintf('Post #%d: %s', $post_id, $e->getMessage());
                }
            }

            // Commit or rollback batch
            if (isset($wpdb) && method_exists($wpdb, 'query')) {
                if ($dry_run || !$batch_success) {
                    $wpdb->query('ROLLBACK');
                } else {
                    $wpdb->query('COMMIT');
                }
            }

            if ($batch_success) {
                $migrated_count += $batch_migrated;
                foreach ($batch_log as $log_item) {
                    $log[] = $log_item;
                }
            }

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        return [
            'success'        => empty($errors),
            'migrated_count' => $migrated_count,
            'skipped_count'  => $skipped_count,
            'error_count'    => $error_count,
            'errors'         => $errors,
            'log'            => $log,
            'batches'        => $total_batches,
            'batch_id'       => $batch_id,
            'dry_run'        => $dry_run,
        ];
    }

    /**
     * Rollback a previous migration batch.
     *
     * @param array $args Options array.
     * @return array Result summary.
     */
    public static function rollback(array $args = []): array {
        $batch_id   = $args['batch_id'] ?? null;
        $batch_size = isset($args['batch_size']) && (int) $args['batch_size'] > 0 ? (int) $args['batch_size'] : self::DEFAULT_BATCH_SIZE;

        global $wpdb;

        // Find posts with _migrated_from_post_type
        $query_args = [
            'post_type'      => self::TARGET_POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ];

        $all_bds = new WP_Query($query_args);
        $candidate_ids = [];

        foreach ($all_bds->posts as $pid) {
            $orig_type = get_post_meta($pid, '_migrated_from_post_type', true);
            if (!empty($orig_type)) {
                if ($batch_id) {
                    $m_batch = get_post_meta($pid, '_migrated_batch_id', true);
                    if ($m_batch !== $batch_id) {
                        continue;
                    }
                }
                $candidate_ids[] = (int) $pid;
            }
        }

        if (empty($candidate_ids)) {
            return [
                'success'        => true,
                'migrated_count' => 0,
                'errors'         => [],
                'log'            => [],
            ];
        }

        $chunks = array_chunk($candidate_ids, $batch_size);
        $restored_count = 0;
        $errors = [];

        foreach ($chunks as $batch) {
            if (isset($wpdb) && method_exists($wpdb, 'query')) {
                $wpdb->query('START TRANSACTION');
            }
            $batch_ok = true;
            $batch_restored = 0;

            foreach ($batch as $post_id) {
                try {
                    $orig_type = get_post_meta($post_id, '_migrated_from_post_type', true);
                    if ($orig_type) {
                        wp_update_post([
                            'ID'        => $post_id,
                            'post_type' => $orig_type,
                        ]);

                        $orig_du_an = get_post_meta($post_id, '_migrated_du_an_raw', true);
                        if ($orig_du_an !== '') {
                            update_post_meta($post_id, 'du_an_lien_quan', $orig_du_an);
                        }

                        delete_post_meta($post_id, '_migrated_from_post_type');
                        delete_post_meta($post_id, '_migrated_from_post_id');
                        delete_post_meta($post_id, '_migrated_batch_id');
                        delete_post_meta($post_id, '_migrated_at');
                        delete_post_meta($post_id, '_migrated_du_an_raw');

                        $batch_restored++;
                    }
                } catch (\Throwable $e) {
                    $batch_ok = false;
                    $errors[] = sprintf('Rollback Post #%d: %s', $post_id, $e->getMessage());
                }
            }

            if (isset($wpdb) && method_exists($wpdb, 'query')) {
                if ($batch_ok) {
                    $wpdb->query('COMMIT');
                    $restored_count += $batch_restored;
                } else {
                    $wpdb->query('ROLLBACK');
                }
            }
        }

        return [
            'success'        => empty($errors),
            'migrated_count' => $restored_count,
            'errors'         => $errors,
            'log'            => [],
        ];
    }

    /**
     * Find candidate post IDs for migration.
     *
     * @param array $source_types Post types to migrate.
     * @return int[]
     */
    protected static function get_candidate_post_ids(array $source_types): array {
        $query = new WP_Query([
            'post_type'      => $source_types,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);

        return array_values(array_filter(array_map('intval', $query->posts)));
    }

    /**
     * Extract clean integer project ID from mixed/serialized values with regex fallback.
     *
     * @param mixed $raw_val Raw meta value.
     * @return int
     */
    public static function extract_clean_project_id(mixed $raw_val): int {
        if (empty($raw_val)) {
            return 0;
        }

        // 1. Direct integer or numeric string
        if (is_numeric($raw_val)) {
            return (int) $raw_val > 0 ? absint($raw_val) : 0;
        }

        // 2. Direct WP_Post or object with ID property
        if (is_object($raw_val) && isset($raw_val->ID)) {
            return absint($raw_val->ID);
        }

        // 3. PHP Array
        if (is_array($raw_val)) {
            foreach ($raw_val as $item) {
                if (is_object($item) && isset($item->ID) && (int) $item->ID > 0) {
                    return absint($item->ID);
                }
                if (is_numeric($item) && (int) $item > 0) {
                    return absint($item);
                }
            }
            return 0;
        }

        // 4. Serialized or string with regex fallback
        if (is_string($raw_val)) {
            $raw_val = trim($raw_val);
            if ($raw_val === '' || $raw_val === '0' || $raw_val === 'N;' || $raw_val === 'b:0;') {
                return 0;
            }

            if (str_starts_with($raw_val, 'a:') || str_starts_with($raw_val, 's:') || str_contains($raw_val, ':{')) {
                $unserialized = @unserialize($raw_val);
                if ($unserialized !== false) {
                    if (is_array($unserialized)) {
                        foreach ($unserialized as $item) {
                            if (is_object($item) && isset($item->ID) && (int) $item->ID > 0) {
                                return absint($item->ID);
                            }
                            if (is_numeric($item) && (int) $item > 0) {
                                return absint($item);
                            }
                        }
                        return 0;
                    }
                    if (is_numeric($unserialized)) {
                        return (int) $unserialized > 0 ? absint($unserialized) : 0;
                    }
                }
            }

            // Fallback for corrupted serialized strings
            // 1. Serialized element values (e.g. {i:0;i:773; or {i:0;s:3:"773" or i:773;)
            if (preg_match_all('/(?:[;{]i:\d+;i:|[;{]s:\d+:\"?)(\d{1,10})/i', $raw_val, $matches)
                || preg_match_all('/(?:i:|s:\d+:)\"?(\d{1,10})\"?;/i', $raw_val, $matches)) {
                foreach ($matches[1] as $cand_str) {
                    $cand = absint($cand_str);
                    if ($cand > 0) {
                        return $cand;
                    }
                }
            }

            // 2. String payload with digits (e.g. s:25:"project_id_is_773_here";)
            if (preg_match('/s:\d+:"([^"]*)"?/i', $raw_val, $str_matches)) {
                if (preg_match('/(\d{1,10})/', $str_matches[1], $num_matches)) {
                    $cand = absint($num_matches[1]);
                    if ($cand > 0) {
                        return $cand;
                    }
                }
            }

            // 3. Fallback: digits not preceded by a: or s:
            if (preg_match_all('/(?<!\b[as]):(\d{1,10})/i', $raw_val, $matches)) {
                foreach ($matches[1] as $cand_str) {
                    $cand = absint($cand_str);
                    if ($cand > 0) {
                        return $cand;
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Map source CPT to transaction type slug in taxonomy hinh-thuc-bds.
     *
     * @param string $source_type
     * @param int    $post_id
     * @return string
     */
    protected static function map_transaction_type(string $source_type, int $post_id): string {
        if (in_array($source_type, ['bds-cho-thue', 'nha-dat-cho-thue'], true)) {
            return 'cho-thue';
        }

        if ($source_type === 'hang-chuyen-nhuong') {
            return 'chuyen-nhuong';
        }

        if ($source_type === 'nha-dat-ban') {
            // Check if post has any term or meta indicating primary/so-cap
            $terms = get_the_terms($post_id, 'hinh-thuc-bds');
            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $t) {
                    if ($t->slug === 'so-cap') {
                        return 'so-cap';
                    }
                }
            }
            return 'chuyen-nhuong';
        }

        return 'chuyen-nhuong';
    }

    /**
     * Collect and map legacy property type taxonomy terms to loai_hinh_bat_dong_san slugs.
     *
     * @param int $post_id
     * @return string[]
     */
    protected static function collect_property_type_terms(int $post_id): array {
        $legacy_taxonomies = [
            'danh-muc-hang-chuyen-nhuong',
            'danh-muc-nha-dat-ban',
            'danh-muc-nha-dat-cho-thue',
            'loai-hinh',
        ];

        $slugs = [];

        foreach ($legacy_taxonomies as $legacy_tax) {
            $terms = get_the_terms($post_id, $legacy_tax);
            if (!empty($terms) && !is_wp_error($terms) && is_array($terms)) {
                foreach ($terms as $term) {
                    if (is_object($term) && !empty($term->slug)) {
                        $slugs[] = $term->slug;
                    }
                }
            }
        }

        return array_values(array_unique($slugs));
    }

    /**
     * Resolve slug collision when moving a post into bat-dong-san.
     *
     * @param string $slug
     * @param int    $post_id
     * @return string
     */
    protected static function resolve_slug_collision(string $slug, int $post_id): string {
        $slug = sanitize_title($slug);
        if (empty($slug)) {
            $slug = "bat-dong-san-$post_id";
        }

        // Check if another bat-dong-san post already uses this slug
        $existing = new WP_Query([
            'post_type'      => self::TARGET_POST_TYPE,
            'name'           => $slug,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'post__not_in'   => [$post_id],
        ]);

        if (!empty($existing->posts)) {
            // Slug exists on a different bat-dong-san post, suffix post_id
            return $slug . '-' . $post_id;
        }

        return $slug;
    }
}

// Register WP-CLI command if WP-CLI is available
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('bds migrate-cpt', function ($args, $assoc_args) {
        $dry_run    = isset($assoc_args['dry-run']);
        $rollback   = isset($assoc_args['rollback']);
        $batch_size = isset($assoc_args['batch-size']) ? (int) $assoc_args['batch-size'] : BDS_CPT_Migration::DEFAULT_BATCH_SIZE;
        $batch_id   = $assoc_args['batch-id'] ?? null;
        $verbose    = isset($assoc_args['verbose']);
        $sources    = isset($assoc_args['source'])
            ? array_map('trim', explode(',', $assoc_args['source']))
            : BDS_CPT_Migration::DEFAULT_SOURCE_TYPES;

        if ($rollback) {
            WP_CLI::log('Starting migration rollback...');
            $result = BDS_CPT_Migration::rollback([
                'batch_size' => $batch_size,
                'batch_id'   => $batch_id,
            ]);
            if ($result['success']) {
                WP_CLI::success(sprintf('Rollback completed! Restored %d posts.', $result['migrated_count']));
            } else {
                WP_CLI::error(sprintf('Rollback encountered errors: %s', implode('; ', $result['errors'])));
            }
            return;
        }

        if ($dry_run) {
            WP_CLI::log('=== RUNNING IN DRY-RUN MODE (No changes will be saved to database) ===');
        } else {
            WP_CLI::log('=== EXECUTING LIVE MIGRATION ===');
        }

        $result = BDS_CPT_Migration::run([
            'dry_run'           => $dry_run,
            'batch_size'        => $batch_size,
            'source_post_types' => $sources,
            'verbose'           => $verbose,
        ]);

        if (!$result['success']) {
            WP_CLI::error(sprintf('Migration failed: %s', implode('; ', $result['errors'])));
            return;
        }

        WP_CLI::success(sprintf(
            '%s completed successfully! Total processed: %d posts in %d batch(es).',
            $dry_run ? 'Dry-run' : 'Live migration',
            $result['migrated_count'],
            $result['batches']
        ));
    });
}
