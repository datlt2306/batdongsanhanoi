<?php

/**
 * Query services
 * 
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Xây dựng meta_query tối ưu cho quan hệ dự án (du_an_lien_quan).
 * Sử dụng so sánh số học (NUMERIC) thay vì so sánh chuỗi, loại bỏ full table scan.
 *
 * @param int|int[] $project_ids Một ID hoặc mảng các ID dự án
 * @return array Meta query clause chuẩn của WordPress
 */
function batdongsan_build_project_meta_query($project_ids): array {
    if (is_array($project_ids)) {
        $clean_ids = [];
        foreach ($project_ids as $pid) {
            $raw = is_numeric($pid) ? (int) $pid : 0;
            if ($raw > 0) {
                $clean_ids[] = absint($raw);
            }
        }
        $clean_ids = array_values(array_unique($clean_ids));
        if (empty($clean_ids)) {
            return [];
        }
        if (count($clean_ids) === 1) {
            return [
                'key'     => 'du_an_lien_quan',
                'value'   => $clean_ids[0],
                'compare' => '=',
                'type'    => 'NUMERIC',
            ];
        }
        return [
            'key'     => 'du_an_lien_quan',
            'value'   => $clean_ids,
            'compare' => 'IN',
            'type'    => 'NUMERIC',
        ];
    }

    $raw_id = is_numeric($project_ids) ? (int) $project_ids : 0;
    if ($raw_id <= 0) {
        return [];
    }

    $id = absint($raw_id);

    return [
        'key'     => 'du_an_lien_quan',
        'value'   => $id,
        'compare' => '=',
        'type'    => 'NUMERIC',
    ];
}

/**
 * Lấy ID dự án liên quan của một bài đăng với runtime memory cache.
 * Hỗ trợ backwards compatibility: đọc được cả ID số chuẩn lẫn serialized array cũ.
 *
 * @param int  $post_id ID bài viết BĐS hoặc tin tức
 * @param bool $reset   Nếu true, xóa sạch bộ nhớ đệm runtime
 * @return int ID dự án (0 nếu không có)
 */
function batdongsan_get_related_project_id(int $post_id, bool $reset = false): int {
    static $cache = [];
    if ($reset) {
        $cache = [];
        return 0;
    }

    if ($post_id <= 0) {
        return 0;
    }

    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }

    $val = get_post_meta($post_id, 'du_an_lien_quan', true);

    if (empty($val)) {
        $cache[$post_id] = 0;
        return 0;
    }

    // 1. Dạng chuẩn hóa: số nguyên hoặc chuỗi số
    if (is_numeric($val)) {
        $cache[$post_id] = (int) $val > 0 ? absint($val) : 0;
        return $cache[$post_id];
    }

    // 2. Dạng WP_Post object
    if (is_object($val) && isset($val->ID)) {
        $cache[$post_id] = (int) $val->ID > 0 ? absint($val->ID) : 0;
        return $cache[$post_id];
    }

    // 3. Dạng serialized array cũ hoặc chuỗi có serialized
    if (is_string($val)) {
        $val = trim($val);
        if ($val === '' || $val === '0' || $val === 'N;' || $val === 'b:0;') {
            $cache[$post_id] = 0;
            return 0;
        }

        if (str_starts_with($val, 'a:') || str_starts_with($val, 's:') || str_contains($val, ':{')) {
            $unserialized = @unserialize($val);
            if ($unserialized !== false) {
                if (is_array($unserialized)) {
                    $first = reset($unserialized);
                    if (is_object($first) && isset($first->ID)) {
                        $cache[$post_id] = (int) $first->ID > 0 ? absint($first->ID) : 0;
                        return $cache[$post_id];
                    }
                    if (is_numeric($first)) {
                        $cache[$post_id] = (int) $first > 0 ? absint($first) : 0;
                        return $cache[$post_id];
                    }
                    $cache[$post_id] = 0;
                    return 0;
                }
                if (is_numeric($unserialized)) {
                    $cache[$post_id] = (int) $unserialized > 0 ? absint($unserialized) : 0;
                    return $cache[$post_id];
                }
            }
        }

        // Fallback for corrupted serialized strings
        // 1. Serialized element values (e.g. {i:0;i:773; or {i:0;s:3:"773" or i:773;)
        if (preg_match('/(?:[;{]i:\d+;i:|[;{]s:\d+:\"?)(\d{1,10})/i', $val, $matches)
            || preg_match('/(?:i:|s:\d+:)\"?(\d{1,10})\"?;/i', $val, $matches)) {
            $cand = absint($matches[1]);
            if ($cand > 0) {
                $cache[$post_id] = $cand;
                return $cache[$post_id];
            }
        }

        // 2. String payload with digits (e.g. s:25:"project_id_is_773_here";)
        if (preg_match('/s:\d+:"([^"]*)"?/i', $val, $str_matches)) {
            if (preg_match('/(\d{1,10})/', $str_matches[1], $num_matches)) {
                $cand = absint($num_matches[1]);
                if ($cand > 0) {
                    $cache[$post_id] = $cand;
                    return $cache[$post_id];
                }
            }
        }

        // 3. Fallback: digits not preceded by a: or s:
        if (preg_match('/(?<![as]):(\d{1,10})/i', $val, $matches)) {
            $cand = absint($matches[1]);
            if ($cand > 0) {
                $cache[$post_id] = $cand;
                return $cache[$post_id];
            }
        }
    }

    // 4. Dạng array PHP
    if (is_array($val) && !empty($val)) {
        $first = reset($val);
        if (is_object($first) && isset($first->ID)) {
            $cache[$post_id] = (int) $first->ID > 0 ? absint($first->ID) : 0;
            return $cache[$post_id];
        }
        if (is_numeric($first)) {
            $cache[$post_id] = (int) $first > 0 ? absint($first) : 0;
            return $cache[$post_id];
        }
    }

    $cache[$post_id] = 0;
    return 0;
}

/**
 * Lấy thông tin dự án liên quan (id, title, url) cho card rendering.
 *
 * @param int $post_id ID của bài đăng BĐS
 * @return object|null Object có cấu trúc {id: int, title: string, url: string} hoặc null
 */
function batdongsan_get_listing_project(int $post_id): ?object {
    $project_id = batdongsan_get_related_project_id($post_id);
    if ($project_id <= 0) {
        return null;
    }

    static $project_cache = [];
    if (isset($project_cache[$project_id])) {
        return $project_cache[$project_id];
    }

    $project_post = get_post($project_id);
    if (!$project_post || $project_post->post_status !== 'publish') {
        $project_cache[$project_id] = null;
        return null;
    }

    $data = (object) [
        'id'    => $project_id,
        'title' => get_the_title($project_id),
        'url'   => get_permalink($project_id),
    ];

    $project_cache[$project_id] = $data;
    return $data;
}


/**
 * Query dự án liên quan: cùng khu vực HOẶC cùng chủ đầu tư HOẶC cùng cấp cha (siblings/children).
 *
 * @param int $project_id   ID dự án hiện tạiimage.png
 * @param int $posts_per_page Số lượng tối đa
 * @return WP_Query
 */
function batdongsan_get_related_projects($project_id = 0, $posts_per_page = 6): WP_Query {
    if ($project_id <= 0) {
        return new WP_Query(['post__in' => [0], 'no_found_rows' => true]);
    }

    $related_ids = [];
    $du_an_cha   = get_field('du_an_cha', $project_id);
    $chu_dau_tu  = function_exists('batdongsan_get_project_meta_recursive') ? batdongsan_get_project_meta_recursive('chu_dau_tu', $project_id) : get_field('chu_dau_tu', $project_id);
    $khu_vuc_terms = get_the_terms($project_id, 'khu-vuc-du-an');

    // 1. Cùng cấp cha: siblings (du_an_cha = cha của mình) hoặc children (du_an_cha = mình)
    if (!empty($du_an_cha)) {
        $parent_id = $du_an_cha instanceof WP_Post ? $du_an_cha->ID : (int) $du_an_cha;
        $siblings  = new WP_Query([
            'post_type'      => 'du-an',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'post__not_in'   => [$project_id],
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                ['key' => 'du_an_cha', 'value' => $parent_id, 'compare' => '=', 'type' => 'NUMERIC'],
            ],
        ]);
        $related_ids = array_merge($related_ids, $siblings->posts);
        wp_reset_postdata();
    } else {
        $children = new WP_Query([
            'post_type'      => 'du-an',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                ['key' => 'du_an_cha', 'value' => $project_id, 'compare' => '=', 'type' => 'NUMERIC'],
            ],
        ]);
        $related_ids = array_merge($related_ids, $children->posts);
        wp_reset_postdata();
    }

    // 2. Cùng khu vực (taxonomy)
    if ($khu_vuc_terms && !is_wp_error($khu_vuc_terms) && !empty($khu_vuc_terms)) {
        $term_ids   = wp_list_pluck($khu_vuc_terms, 'term_id');
        $by_area    = new WP_Query([
            'post_type'      => 'du-an',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'post__not_in'   => [$project_id],
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'tax_query'      => [
                ['taxonomy' => 'khu-vuc-du-an', 'field' => 'term_id', 'terms' => $term_ids],
            ],
        ]);
        $related_ids = array_merge($related_ids, $by_area->posts);
        wp_reset_postdata();
    }

    // 3. Cùng chủ đầu tư
    if (!empty($chu_dau_tu) && is_string($chu_dau_tu)) {
        $chu_dau_tu = trim($chu_dau_tu);
        $by_investor = new WP_Query([
            'post_type'      => 'du-an',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'post__not_in'   => [$project_id],
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                ['key' => 'chu_dau_tu', 'value' => $chu_dau_tu, 'compare' => 'LIKE'],
            ],
        ]);
        $related_ids = array_merge($related_ids, $by_investor->posts);
        wp_reset_postdata();
    }

    $related_ids = array_unique(array_filter(array_map('intval', $related_ids)));
    $related_ids = array_diff($related_ids, [(int) $project_id]);

    if (empty($related_ids)) {
        return new WP_Query(['post__in' => [0], 'no_found_rows' => true]);
    }

    return new WP_Query([
        'post_type'      => 'du-an',
        'post__in'       => $related_ids,
        'posts_per_page' => $posts_per_page,
        'post_status'    => 'publish',
        'orderby'        => 'post__in',
        'no_found_rows'  => true,
    ]);
}

/**
 * Query dự án liên quan theo khu vực
 * 
 * @param string $khu_vuc_name Tên khu vực (lấy từ địa chỉ)
 * @return WP_Query|null Query object hoặc null nếu không có khu vực
 */
function batdongsan_get_related_projects_by_area($khu_vuc_name = ''): ?WP_Query {
    if (empty($khu_vuc_name)) {
        return null;
    }

    return new WP_Query([
        'post_type'      => 'du-an',
        'posts_per_page' => 10,
        'post_status'    => 'publish',
        'no_found_rows'  => true,
        'meta_query'     => [
            [
                'key'     => 'address',
                'value'   => $khu_vuc_name,
                'compare' => 'LIKE',
            ],
        ],
    ]);
}

/**
 * Query bất động sản nổi bật
 * 
 * @param int   $post_id       Post ID để loại trừ
 * @param int   $posts_per_page Số lượng posts
 * @return WP_Query Query object
 */
function batdongsan_get_featured_listings($post_id = 0, $posts_per_page = 5): WP_Query {
    $args = [
        'post_type'      => 'hang-chuyen-nhuong',
        'posts_per_page' => $posts_per_page,
        'post_status'    => 'publish',
        'no_found_rows'  => true,
        'meta_query'     => [
            [
                'key'   => 'featured',
                'value' => '1',
            ],
        ],
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    if ($post_id > 0) {
        $args['post__not_in'] = [$post_id];
    }

    return new WP_Query($args);
}

/**
 * Query dự án liên quan theo taxonomy
 * 
 * @param int   $project_id    Project ID để loại trừ
 * @param array $khu_vuc_terms Mảng các term objects
 * @param int   $posts_per_page Số lượng posts
 * @return WP_Query|null Query object hoặc null nếu không có terms
 */
function batdongsan_get_related_projects_by_taxonomy($project_id = 0, $khu_vuc_terms = [], $posts_per_page = 6): ?WP_Query {
    if (empty($khu_vuc_terms) || is_wp_error($khu_vuc_terms)) {
        return null;
    }

    $khu_vuc_ids = wp_list_pluck($khu_vuc_terms, 'term_id');

    $args = [
        'post_type'      => 'du-an',
        'posts_per_page' => $posts_per_page,
        'post_status'    => 'publish',
        'no_found_rows'  => true,
        'tax_query'      => [
            [
                'taxonomy' => 'khu-vuc-du-an',
                'field'    => 'term_id',
                'terms'    => $khu_vuc_ids,
            ],
        ],
    ];

    if ($project_id > 0) {
        $args['post__not_in'] = [$project_id];
    }

    return new WP_Query($args);
}

/**
 * Lấy danh sách dự án có ít nhất 1 BĐS (du_an_lien_quan).
 * Dùng cho filter "Thuộc dự án" trên trang BĐS.
 *
 * @return array [project_id => project_title]
 */
function batdongsan_get_projects_with_bds(): array {
    $cached = get_transient('bds_projects_with_listings');
    if ($cached !== false && is_array($cached)) {
        return $cached;
    }

    $query = new WP_Query([
        'post_type'      => ['bat-dong-san', 'hang-chuyen-nhuong', 'nha-dat-ban'],
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);

    $ids = [];
    foreach ($query->posts as $post_id) {
        $project_id = batdongsan_get_related_project_id((int) $post_id);
        if ($project_id > 0) {
            $ids[$project_id] = true;
        }
    }
    wp_reset_postdata();

    $result = [0 => 'Tất cả dự án'];
    $project_ids = array_keys($ids);
    if (!empty($project_ids)) {
        $proj_query = new WP_Query([
            'post_type'      => 'du-an',
            'post_status'    => 'publish',
            'post__in'       => $project_ids,
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ]);
        foreach ($proj_query->posts as $proj) {
            $p_obj = is_object($proj) ? $proj : get_post($proj);
            if ($p_obj) {
                $result[$p_obj->ID] = $p_obj->post_title;
            }
        }
        wp_reset_postdata();
    }

    set_transient('bds_projects_with_listings', $result, 12 * HOUR_IN_SECONDS);
    return $result;
}

/**
 * Query BĐS cơ bản để xây dựng filter options (khu vực, loại hình).
 * Không lọc theo type và khu_vuc để hiển thị đủ các option có sản phẩm.
 *
 * @param array $filters ['du_an' => int, 'project-name' => string, 'status' => string]
 * @return WP_Post[]
 */
function batdongsan_get_bds_posts_for_filter_options(array $filters = [], bool $reset = false): array {
    static $memory_cache = [];
    if ($reset) {
        $memory_cache = [];
        return [];
    }
    $cache_key = 'bds_hdr_bds_' . md5(serialize($filters));

    if (isset($memory_cache[$cache_key])) {
        return $memory_cache[$cache_key];
    }

    $transient_val = get_transient($cache_key);
    if ($transient_val !== false && is_array($transient_val)) {
        $memory_cache[$cache_key] = $transient_val;
        return $transient_val;
    }

    $args = [
        'posts_per_page' => -1,
        'post_type'      => ['bat-dong-san', 'hang-chuyen-nhuong', 'nha-dat-ban'],
        'post_status'    => 'publish',
        'orderby'        => 'post_date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ];

    $du_an  = isset($filters['du_an']) ? absint($filters['du_an']) : 0;
    $name   = isset($filters['project-name']) ? trim(sanitize_text_field($filters['project-name'])) : '';
    $status = isset($filters['status']) ? sanitize_text_field($filters['status']) : '';

    if (!isset($args['meta_query'])) {
        $args['meta_query'] = ['relation' => 'AND'];
    }

    if ($du_an > 0) {
        $project_clause = batdongsan_build_project_meta_query($du_an);
        if (!empty($project_clause)) {
            $args['meta_query'][] = $project_clause;
        }
    }

    if ($status && $status !== '0') {
        $status_map = [
            'dang-ban'      => 'dang_ban',
            'da-ban'        => 'da_ban',
            'dang-cho-thue' => 'dang_cho_thue',
            'tam-dung'      => 'tam_dung',
        ];
        $db_status = $status_map[$status] ?? $status;
        if (!isset($args['meta_query'])) {
            $args['meta_query'] = [];
        }
        $args['meta_query'][] = [
            'key'     => 'trang_thai',
            'value'   => $db_status,
            'compare' => '=',
        ];
    }

    if ($name !== '') {
        $GLOBALS['bds_search_name'] = $name;
        add_filter('posts_where', 'bds_filter_posts_by_name', 10, 1);
    }

    $query = new WP_Query($args);
    $posts = $query->posts;
    wp_reset_postdata();

    if ($name !== '') {
        remove_filter('posts_where', 'bds_filter_posts_by_name', 10);
        unset($GLOBALS['bds_search_name']);
    }

    $result = is_array($posts) ? $posts : [];
    set_transient($cache_key, $result, 12 * HOUR_IN_SECONDS);
    $memory_cache[$cache_key] = $result;

    return $result;
}

/**
 * Query dự án (du-an) cơ bản để xây dựng filter options khu vực.
 * Không lọc theo area để hiển thị đủ các option có sản phẩm.
 *
 * @param array $filters ['project-name' => string, 'type' => string, 'status' => string, 'price' => string]
 * @return WP_Post[]
 */
function batdongsan_get_du_an_posts_for_filter_options(array $filters = [], bool $reset = false): array {
    static $memory_cache = [];
    if ($reset) {
        $memory_cache = [];
        return [];
    }
    $cache_key = 'bds_hdr_duan_' . md5(serialize($filters));

    if (isset($memory_cache[$cache_key])) {
        return $memory_cache[$cache_key];
    }

    $transient_val = get_transient($cache_key);
    if ($transient_val !== false && is_array($transient_val)) {
        $memory_cache[$cache_key] = $transient_val;
        return $transient_val;
    }

    $args = [
        'posts_per_page' => -1,
        'post_type'      => 'du-an',
        'post_status'    => 'publish',
        'orderby'        => 'post_date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ];

    $name   = isset($filters['project-name']) ? trim(sanitize_text_field($filters['project-name'])) : '';
    $type   = isset($filters['type']) ? sanitize_text_field($filters['type']) : '';
    $status = isset($filters['status']) ? sanitize_text_field($filters['status']) : '';
    $price  = isset($filters['price']) ? sanitize_text_field($filters['price']) : null;

    if ($name !== '') {
        $GLOBALS['bds_search_name'] = $name;
        add_filter('posts_where', 'bds_filter_posts_by_name', 10, 1);
    }

    if ($type && $type !== '0') {
        $args['tax_query'] = [
            ['taxonomy' => 'loai_hinh_du_an', 'field' => 'term_id', 'terms' => (int) $type],
        ];
    }

    // Bug 4: Correct project status meta key to 'trang_thai' and underscore normalization
    if ($status && $status !== '0') {
        if (!isset($args['meta_query'])) {
            $args['meta_query'] = ['relation' => 'AND'];
        }
        $status_norm = str_replace('-', '_', sanitize_key($status));
        $args['meta_query'][] = ['key' => 'trang_thai', 'value' => $status_norm, 'compare' => '='];
    }

    if ($price && $price !== '0') {
        $priceArr = explode('-', $price);
        if (!isset($args['meta_query'])) {
            $args['meta_query'] = ['relation' => 'AND'];
        }
        if (count($priceArr) > 1) {
            $args['meta_query'][] = ['key' => 'price', 'value' => $priceArr[0], 'type' => 'NUMERIC', 'compare' => '>='];
            $args['meta_query'][] = ['key' => 'price', 'value' => $priceArr[1], 'type' => 'NUMERIC', 'compare' => ($priceArr[1] == 5) ? '<' : '<='];
        } else {
            $args['meta_query'][] = ['key' => 'price', 'value' => $price, 'type' => 'NUMERIC', 'compare' => '>='];
        }
    }

    $query = new WP_Query($args);
    $posts = $query->posts;
    wp_reset_postdata();

    if ($name !== '') {
        remove_filter('posts_where', 'bds_filter_posts_by_name', 10);
        unset($GLOBALS['bds_search_name']);
    }

    $result = is_array($posts) ? $posts : [];
    set_transient($cache_key, $result, 12 * HOUR_IN_SECONDS);
    $memory_cache[$cache_key] = $result;

    return $result;
}

/**
 * Query bài viết liên quan theo dự án
 * 
 * @param int $project_id Project ID
 * @param int $posts_per_page Số lượng posts
 * @return WP_Query Query object
 */
function batdongsan_get_related_posts_by_project($project_id = 0, $posts_per_page = 4): WP_Query {
    $project_id = absint($project_id);
    if ($project_id <= 0) {
        return new WP_Query(['post__in' => [0], 'no_found_rows' => true]);
    }

    $project_clause = batdongsan_build_project_meta_query($project_id);

    return new WP_Query([
        'post_type'      => 'post',
        'posts_per_page' => $posts_per_page,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
        'meta_query'     => !empty($project_clause) ? [$project_clause] : [],
    ]);
}

/**
 * Reset memory cache trong request cho filter options
 */
function batdongsan_reset_filter_options_memory_cache(): void {
    batdongsan_get_bds_posts_for_filter_options([], true);
    batdongsan_get_du_an_posts_for_filter_options([], true);
}


