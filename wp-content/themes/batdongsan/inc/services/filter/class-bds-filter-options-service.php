<?php
/**
 * BDS Filter Options Service
 *
 * Fast database-level aggregations and compact transient caching (< 500 bytes)
 * replacing legacy in-memory full-post scans (posts_per_page => -1).
 *
 * @package BatDongSan
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class BDS_Filter_Options_Service {

    const CACHE_GROUP             = 'bds_filter_options';
    const TRANSIENT_PRICE_BOUNDS  = 'bds_opt_price_bounds';
    const TRANSIENT_PRICE_RANGES  = 'bds_listings_price_ranges_total'; // Legacy test-compatible key
    const TRANSIENT_AREA_BOUNDS   = 'bds_opt_area_bounds';
    const TRANSIENT_AREA_RANGES   = 'bds_listings_area_ranges_total';  // Legacy test-compatible key
    const TRANSIENT_BDS_DISTRICTS = 'bds_opt_bds_districts';
    const TRANSIENT_DUAN_DISTRICTS= 'bds_opt_duan_districts';
    const TRANSIENT_TTL           = 43200; // 12 Hours

    private static array $memory_cache = [];

    /**
     * Get price bounds (min, max, has_thoa_thuan) via fast SQL MIN/MAX.
     *
     * @return array [0 => min, 1 => max, 'min' => float, 'max' => float, 'has_thoa_thuan' => bool]
     */
    public static function get_price_bounds(): array {
        if (isset(self::$memory_cache['price_bounds'])) {
            return self::$memory_cache['price_bounds'];
        }

        $cached = get_transient(self::TRANSIENT_PRICE_BOUNDS);
        if ($cached !== false && is_array($cached)) {
            self::$memory_cache['price_bounds'] = $cached;
            return $cached;
        }

        global $wpdb;

        $min_price = 0.0;
        $max_price = 60000.0;
        $has_thoa_thuan = false;

        if ($wpdb && method_exists($wpdb, 'get_row')) {
            $row = $wpdb->get_row("
                SELECT 
                    MIN(CAST(pm.meta_value AS DECIMAL(12,2))) AS min_val,
                    MAX(CAST(pm.meta_value AS DECIMAL(12,2))) AS max_val
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_bds_price_million'
                  AND p.post_type = 'bat-dong-san'
                  AND p.post_status = 'publish'
                  AND CAST(pm.meta_value AS DECIMAL(12,2)) > 0
            ");

            if ($row && $row->max_val !== null) {
                $min_price = floor((float) $row->min_val);
                $max_price = ceil((float) $row->max_val);
            }

            if (method_exists($wpdb, 'get_var')) {
                $thoa_thuan_val = $wpdb->get_var("
                    SELECT 1 
                    FROM {$wpdb->postmeta} pm
                    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                    WHERE pm.meta_key = '_bds_is_thoa_thuan'
                      AND pm.meta_value = '1'
                      AND p.post_type = 'bat-dong-san'
                      AND p.post_status = 'publish'
                    LIMIT 1
                ");
                $has_thoa_thuan = ($thoa_thuan_val !== null);
            }
        }

        $effective_max = max($max_price, 30000.0);
        $result = [
            0                => $min_price,
            1                => $effective_max,
            'min'            => $min_price,
            'max'            => $effective_max,
            'has_thoa_thuan' => $has_thoa_thuan,
        ];

        set_transient(self::TRANSIENT_PRICE_BOUNDS, $result, self::TRANSIENT_TTL);
        self::$memory_cache['price_bounds'] = $result;

        return $result;
    }

    /**
     * Get active price ranges using single-pass conditional SQL aggregation.
     * Replaces batdongsan_build_price_ranges_from_posts().
     *
     * @return array [ '0' => 'Tất cả', 'thoa_thuan' => 'Thỏa thuận', '0-500' => 'Dưới 500 triệu', ... ]
     */
    public static function get_price_ranges(): array {
        if (isset(self::$memory_cache['price_ranges'])) {
            return self::$memory_cache['price_ranges'];
        }

        $cached = get_transient(self::TRANSIENT_PRICE_RANGES);
        if ($cached !== false && is_array($cached)) {
            self::$memory_cache['price_ranges'] = $cached;
            return $cached;
        }

        global $wpdb;

        $price_ranges = ['0' => 'Tất cả'];

        if (!$wpdb || !method_exists($wpdb, 'get_row')) {
            $default = function_exists('batdongsan_get_bds_price_ranges_default')
                ? batdongsan_get_bds_price_ranges_default()
                : ['0' => 'Tất cả'];
            return $default;
        }

        $row = $wpdb->get_row("
            SELECT
                SUM(CASE WHEN val > 0 AND val <= 500 THEN 1 ELSE 0 END) AS c_0_500,
                SUM(CASE WHEN val >= 500 AND val <= 800 THEN 1 ELSE 0 END) AS c_500_800,
                SUM(CASE WHEN val >= 800 AND val <= 1000 THEN 1 ELSE 0 END) AS c_800_1000,
                SUM(CASE WHEN val >= 1000 AND val <= 2000 THEN 1 ELSE 0 END) AS c_1000_2000,
                SUM(CASE WHEN val >= 2000 AND val <= 3000 THEN 1 ELSE 0 END) AS c_2000_3000,
                SUM(CASE WHEN val >= 3000 AND val <= 5000 THEN 1 ELSE 0 END) AS c_3000_5000,
                SUM(CASE WHEN val >= 5000 AND val <= 7000 THEN 1 ELSE 0 END) AS c_5000_7000,
                SUM(CASE WHEN val >= 7000 AND val <= 10000 THEN 1 ELSE 0 END) AS c_7000_10000,
                SUM(CASE WHEN val >= 10000 AND val <= 20000 THEN 1 ELSE 0 END) AS c_10000_20000,
                SUM(CASE WHEN val >= 20000 AND val <= 30000 THEN 1 ELSE 0 END) AS c_20000_30000,
                SUM(CASE WHEN val >= 30000 AND val <= 40000 THEN 1 ELSE 0 END) AS c_30000_40000,
                SUM(CASE WHEN val >= 40000 AND val <= 60000 THEN 1 ELSE 0 END) AS c_40000_60000,
                SUM(CASE WHEN val >= 60000 THEN 1 ELSE 0 END) AS c_60000
            FROM (
                SELECT CAST(pm.meta_value AS DECIMAL(12,2)) AS val
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_bds_price_million'
                  AND p.post_type = 'bat-dong-san'
                  AND p.post_status = 'publish'
                  AND CAST(pm.meta_value AS DECIMAL(12,2)) > 0
            ) AS prices
        ");

        $bounds = self::get_price_bounds();
        if ($bounds['has_thoa_thuan']) {
            $price_ranges['thoa_thuan'] = 'Thỏa thuận';
        }

        $candidates = [
            'c_0_500'       => ['key' => '0-500',       'label' => 'Dưới 500 triệu'],
            'c_500_800'     => ['key' => '500-800',     'label' => '500 - 800 triệu'],
            'c_800_1000'    => ['key' => '800-1000',    'label' => '800 triệu - 1 tỷ'],
            'c_1000_2000'   => ['key' => '1000-2000',   'label' => '1 - 2 tỷ'],
            'c_2000_3000'   => ['key' => '2000-3000',   'label' => '2 - 3 tỷ'],
            'c_3000_5000'   => ['key' => '3000-5000',   'label' => '3 - 5 tỷ'],
            'c_5000_7000'   => ['key' => '5000-7000',   'label' => '5 - 7 tỷ'],
            'c_7000_10000'  => ['key' => '7000-10000',  'label' => '7 - 10 tỷ'],
            'c_10000_20000' => ['key' => '10000-20000', 'label' => '10 - 20 tỷ'],
            'c_20000_30000' => ['key' => '20000-30000', 'label' => '20 - 30 tỷ'],
            'c_30000_40000' => ['key' => '30000-40000', 'label' => '30 - 40 tỷ'],
            'c_40000_60000' => ['key' => '40000-60000', 'label' => '40 - 60 tỷ'],
            'c_60000'       => ['key' => '60000',       'label' => 'Trên 60 tỷ'],
        ];

        $total_found = 0;
        if ($row) {
            foreach ($candidates as $col => $c) {
                $count = (int) ($row->$col ?? 0);
                if ($count > 0) {
                    $price_ranges[$c['key']] = $c['label'];
                    $total_found += $count;
                }
            }
        }

        // Fallback to default schema if DB returned zero rows (e.g. Empty DB or Mock test environment)
        if ($total_found === 0) {
            $price_ranges = function_exists('batdongsan_get_bds_price_ranges_default')
                ? batdongsan_get_bds_price_ranges_default()
                : ['0' => 'Tất cả'];
        }

        set_transient(self::TRANSIENT_PRICE_RANGES, $price_ranges, self::TRANSIENT_TTL);
        self::$memory_cache['price_ranges'] = $price_ranges;

        return $price_ranges;
    }

    /**
     * Get area bounds (min, max m²) via fast SQL MIN/MAX.
     *
     * @return array [0 => min, 1 => max, 'min' => float, 'max' => float]
     */
    public static function get_area_bounds(): array {
        if (isset(self::$memory_cache['area_bounds'])) {
            return self::$memory_cache['area_bounds'];
        }

        $cached = get_transient(self::TRANSIENT_AREA_BOUNDS);
        if ($cached !== false && is_array($cached)) {
            self::$memory_cache['area_bounds'] = $cached;
            return $cached;
        }

        global $wpdb;

        $min_area = 0.0;
        $max_area = 500.0;

        if ($wpdb && method_exists($wpdb, 'get_row')) {
            $row = $wpdb->get_row("
                SELECT 
                    MIN(CAST(pm.meta_value AS DECIMAL(10,2))) AS min_val,
                    MAX(CAST(pm.meta_value AS DECIMAL(10,2))) AS max_val
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_bds_area_m2'
                  AND p.post_type = 'bat-dong-san'
                  AND p.post_status = 'publish'
                  AND CAST(pm.meta_value AS DECIMAL(10,2)) > 0
            ");

            if ($row && $row->max_val !== null) {
                $min_area = floor((float) $row->min_val);
                $max_area = ceil((float) $row->max_val);
            }
        }

        $effective_max = max($max_area, 300.0);
        $result = [
            0     => $min_area,
            1     => $effective_max,
            'min' => $min_area,
            'max' => $effective_max,
        ];

        set_transient(self::TRANSIENT_AREA_BOUNDS, $result, self::TRANSIENT_TTL);
        self::$memory_cache['area_bounds'] = $result;

        return $result;
    }

    /**
     * Get active area ranges using single-pass conditional SQL aggregation.
     *
     * @return array [ '0-50' => 'Dưới 50 m²', '50-80' => '50-80 m²', ... ]
     */
    public static function get_area_ranges(): array {
        if (isset(self::$memory_cache['area_ranges'])) {
            return self::$memory_cache['area_ranges'];
        }

        $cached = get_transient(self::TRANSIENT_AREA_RANGES);
        if ($cached !== false && is_array($cached)) {
            self::$memory_cache['area_ranges'] = $cached;
            return $cached;
        }

        global $wpdb;

        $area_ranges = [];

        if (!$wpdb || !method_exists($wpdb, 'get_row')) {
            return [];
        }

        $row = $wpdb->get_row("
            SELECT
                SUM(CASE WHEN val > 0 AND val <= 50 THEN 1 ELSE 0 END) AS c_0_50,
                SUM(CASE WHEN val >= 50 AND val <= 80 THEN 1 ELSE 0 END) AS c_50_80,
                SUM(CASE WHEN val >= 80 AND val <= 100 THEN 1 ELSE 0 END) AS c_80_100,
                SUM(CASE WHEN val >= 100 AND val <= 120 THEN 1 ELSE 0 END) AS c_100_120,
                SUM(CASE WHEN val >= 120 AND val <= 150 THEN 1 ELSE 0 END) AS c_120_150,
                SUM(CASE WHEN val >= 150 AND val <= 200 THEN 1 ELSE 0 END) AS c_150_200,
                SUM(CASE WHEN val >= 200 AND val <= 300 THEN 1 ELSE 0 END) AS c_200_300,
                SUM(CASE WHEN val >= 300 AND val <= 500 THEN 1 ELSE 0 END) AS c_300_500,
                SUM(CASE WHEN val >= 500 THEN 1 ELSE 0 END) AS c_500,
                MAX(val) AS max_val
            FROM (
                SELECT CAST(pm.meta_value AS DECIMAL(10,2)) AS val
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_bds_area_m2'
                  AND p.post_type = 'bat-dong-san'
                  AND p.post_status = 'publish'
                  AND CAST(pm.meta_value AS DECIMAL(10,2)) > 0
            ) AS areas
        ");

        $candidates = [
            'c_0_50'   => ['key' => '0-50',   'label' => 'Dưới 50 m²'],
            'c_50_80'  => ['key' => '50-80',  'label' => '50-80 m²'],
            'c_80_100' => ['key' => '80-100', 'label' => '80-100 m²'],
            'c_100_120'=> ['key' => '100-120','label' => '100-120 m²'],
            'c_120_150'=> ['key' => '120-150','label' => '120-150 m²'],
            'c_150_200'=> ['key' => '150-200','label' => '150-200 m²'],
            'c_200_300'=> ['key' => '200-300','label' => '200-300 m²'],
            'c_300_500'=> ['key' => '300-500','label' => '300-500 m²'],
        ];

        if ($row) {
            foreach ($candidates as $col => $c) {
                if ((int) ($row->$col ?? 0) > 0) {
                    $area_ranges[$c['key']] = $c['label'];
                }
            }

            if ((int) ($row->c_500 ?? 0) > 0) {
                $max_val = (float) ($row->max_val ?? 0);
                $above   = (int) (ceil($max_val / 50) * 50);
                $above   = max($above, 500);
                $area_ranges[(string) $above] = 'Trên ' . $above . ' m²';
            }
        }

        set_transient(self::TRANSIENT_AREA_RANGES, $area_ranges, self::TRANSIENT_TTL);
        self::$memory_cache['area_ranges'] = $area_ranges;

        return $area_ranges;
    }

    /**
     * Get active district options for BĐS filtered against DISTINCT dia_chi in database.
     *
     * @param bool $include_all
     * @return array [ '' => 'Toàn Hà Nội', 'cau-giay' => 'Cầu Giấy', ... ]
     */
    public static function get_bds_district_options(bool $include_all = true): array {
        $cache_key = self::TRANSIENT_BDS_DISTRICTS . ($include_all ? '_all' : '_strict');
        if (isset(self::$memory_cache[$cache_key])) {
            return self::$memory_cache[$cache_key];
        }

        $cached = get_transient($cache_key);
        if ($cached !== false && is_array($cached)) {
            self::$memory_cache[$cache_key] = $cached;
            return $cached;
        }

        global $wpdb;

        $all_districts = function_exists('batdongsan_get_area_options')
            ? batdongsan_get_area_options($include_all)
            : [];

        if (!$wpdb || !method_exists($wpdb, 'get_col')) {
            return $all_districts;
        }

        $addresses = $wpdb->get_col("
            SELECT DISTINCT pm.meta_value 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = 'dia_chi'
              AND p.post_type = 'bat-dong-san'
              AND p.post_status = 'publish'
              AND pm.meta_value != ''
        ");

        if (empty($addresses)) {
            return $all_districts;
        }

        $result = [];
        if ($include_all && isset($all_districts[''])) {
            $result[''] = $all_districts[''];
        }

        $combined_addresses = ' ' . implode(' | ', $addresses) . ' ';

        foreach ($all_districts as $slug => $label) {
            if ($slug === '') {
                continue;
            }
            if (mb_stripos($combined_addresses, $label) !== false) {
                $result[$slug] = $label;
            }
        }

        set_transient($cache_key, $result, self::TRANSIENT_TTL);
        self::$memory_cache[$cache_key] = $result;

        return $result;
    }

    /**
     * Get active district options for Du An filtered against DISTINCT address in database.
     *
     * @param bool $include_all
     * @return array [ '' => 'Toàn Hà Nội', 'gia-lam' => 'Gia Lâm', ... ]
     */
    public static function get_project_district_options(bool $include_all = true): array {
        $cache_key = self::TRANSIENT_DUAN_DISTRICTS . ($include_all ? '_all' : '_strict');
        if (isset(self::$memory_cache[$cache_key])) {
            return self::$memory_cache[$cache_key];
        }

        $cached = get_transient($cache_key);
        if ($cached !== false && is_array($cached)) {
            self::$memory_cache[$cache_key] = $cached;
            return $cached;
        }

        global $wpdb;

        $all_districts = function_exists('batdongsan_get_area_options')
            ? batdongsan_get_area_options($include_all)
            : [];

        if (!$wpdb || !method_exists($wpdb, 'get_col')) {
            return $all_districts;
        }

        $addresses = $wpdb->get_col("
            SELECT DISTINCT pm.meta_value 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = 'address'
              AND p.post_type = 'du-an'
              AND p.post_status = 'publish'
              AND pm.meta_value != ''
        ");

        if (empty($addresses)) {
            return $all_districts;
        }

        $result = [];
        if ($include_all && isset($all_districts[''])) {
            $result[''] = $all_districts[''];
        }

        $combined_addresses = ' ' . implode(' | ', $addresses) . ' ';

        foreach ($all_districts as $slug => $label) {
            if ($slug === '') {
                continue;
            }
            if (mb_stripos($combined_addresses, $label) !== false) {
                $result[$slug] = $label;
            }
        }

        set_transient($cache_key, $result, self::TRANSIENT_TTL);
        self::$memory_cache[$cache_key] = $result;

        return $result;
    }

    /**
     * Get active BĐS property types (loai_hinh_bat_dong_san).
     *
     * @return object[] Array of term-like objects: term_id, name, slug
     */
    public static function get_bds_type_options(): array {
        $terms = get_terms([
            'taxonomy'   => 'loai_hinh_bat_dong_san',
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        return array_map(function ($t) {
            return (object) [
                'term_id' => (int) $t->term_id,
                'name'    => $t->name,
                'slug'    => $t->slug,
            ];
        }, $terms);
    }

    /**
     * Alias of get_bds_type_options().
     */
    public static function get_type_options(): array {
        return self::get_bds_type_options();
    }

    /**
     * Get active Project types (loai_hinh_du_an).
     *
     * @return object[] Array of term-like objects: term_id, name, slug
     */
    public static function get_project_type_options(): array {
        $terms = get_terms([
            'taxonomy'   => 'loai_hinh_du_an',
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        return array_map(function ($t) {
            return (object) [
                'term_id' => (int) $t->term_id,
                'name'    => $t->name,
                'slug'    => $t->slug,
            ];
        }, $terms);
    }

    /**
     * Invalidate all filter options transients and in-memory caches.
     * Hooked into post lifecycle actions.
     */
    public static function flush_all_caches(): void {
        self::$memory_cache = [];

        delete_transient(self::TRANSIENT_PRICE_BOUNDS);
        delete_transient(self::TRANSIENT_PRICE_RANGES);
        delete_transient(self::TRANSIENT_AREA_BOUNDS);
        delete_transient(self::TRANSIENT_AREA_RANGES);
        delete_transient(self::TRANSIENT_BDS_DISTRICTS . '_all');
        delete_transient(self::TRANSIENT_BDS_DISTRICTS . '_strict');
        delete_transient(self::TRANSIENT_DUAN_DISTRICTS . '_all');
        delete_transient(self::TRANSIENT_DUAN_DISTRICTS . '_strict');
        delete_transient('bds_price_ranges');
        delete_transient('bds_area_ranges');
        delete_transient('bds_projects_with_listings');

        // Clear dynamic header transients (bds_hdr_*) for test compatibility
        if (function_exists('batdongsan_clear_dynamic_header_transients')) {
            batdongsan_clear_dynamic_header_transients();
        }
    }
}
