<?php
/**
 * Project helpers: Xử lý logic thừa hưởng dữ liệu đệ quy
 * 
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lấy giá trị trường ACF từ dự án hiện tại, nếu trống thì tìm đệ quy lên dự án cha
 * 
 * @param string $field_name Tên trường ACF cần lấy
 * @param int    $post_id    ID của bài viết (mặc định là hiện tại)
 * @param int    $limit      Giới hạn số tầng tìm kiếm (mặc định 3 tầng: Con -> Cha -> Ông)
 * @return mixed Giá trị của trường
 */
function batdongsan_get_project_meta_recursive($field_name, $post_id = 0, $limit = 3) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    if (!$post_id || $limit <= 0) {
        return null;
    }

    // Lấy giá trị từ bài viết hiện tại
    $value = get_field($field_name, $post_id);

    // Nếu giá trị không rỗng, trả về luôn
    if (!empty($value)) {
        return $value;
    }

    // Nếu rỗng, kiểm tra xem có dự án cha không
    $du_an_cha = get_field('du_an_cha', $post_id);
    if ($du_an_cha && is_object($du_an_cha)) {
        // Tìm kiếm đệ quy lên tầng trên
        return batdongsan_get_project_meta_recursive($field_name, $du_an_cha->ID, $limit - 1);
    }

    return null;
}

/**
 * Lấy danh sách bất động sản thuộc dự án (bao gồm dự án con, phân khu)
 *
 * @param int   $project_id      ID dự án (du-an)
 * @param array $args            Tham số WP_Query bổ sung (posts_per_page, orderby, ...)
 * @return WP_Query
 */
function batdongsan_get_project_listings(int $project_id, array $args = []): WP_Query {
    $defaults = [
        'post_type'      => ['bat-dong-san', 'hang-chuyen-nhuong', 'nha-dat-ban', 'nha-dat-cho-thue'],
        'posts_per_page' => 6,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ];

    $project_ids = [(int) $project_id];

    // Dự án cha: thêm các du-an con (phân khu cấp du-an)
    $child_du_an = new WP_Query([
        'post_type'      => 'du-an',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [
            [
                'key'     => 'du_an_cha',
                'value'   => $project_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ],
        ],
    ]);
    if ($child_du_an->have_posts()) {
        $project_ids = array_merge($project_ids, $child_du_an->posts);
    }
    wp_reset_postdata();

    // Thêm các phân khu (phan-khu CPT) thuộc dự án này
    $phan_khu_ids = [];
    $phan_khu_q = new WP_Query([
        'post_type'      => 'phan-khu',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [
            [
                'key'     => 'du_an_cha',
                'value'   => $project_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ],
        ],
    ]);
    if ($phan_khu_q->have_posts()) {
        $phan_khu_ids = $phan_khu_q->posts;
    }
    wp_reset_postdata();

    $project_ids = array_unique(array_map('intval', $project_ids));
    $phan_khu_ids = array_unique(array_map('intval', $phan_khu_ids));

    $conditions = [];

    // Quan hệ dự án: so sánh số học indexed qua helper
    if (!empty($project_ids)) {
        $project_clause = function_exists('batdongsan_build_project_meta_query')
            ? batdongsan_build_project_meta_query($project_ids)
            : [
                'key'     => 'du_an_lien_quan',
                'value'   => count($project_ids) === 1 ? (int) reset($project_ids) : array_map('intval', $project_ids),
                'compare' => count($project_ids) === 1 ? '=' : 'IN',
                'type'    => 'NUMERIC',
            ];
        if (!empty($project_clause)) {
            $conditions[] = $project_clause;
        }
    }

    // Quan hệ phân khu con
    if (!empty($phan_khu_ids)) {
        $conditions[] = [
            'key'     => 'phan_khu',
            'value'   => $phan_khu_ids,
            'compare' => 'IN',
            'type'    => 'NUMERIC',
        ];
    }

    $meta_query = [];
    if (!empty($conditions)) {
        $meta_query = (count($conditions) === 1)
            ? $conditions
            : array_merge(['relation' => 'OR'], $conditions);
    }

    $query_args = array_merge($defaults, $args, [
        'meta_query' => $meta_query,
    ]);

    return new WP_Query($query_args);
}

/**
 * Lấy logo chủ đầu tư theo tên từ post type 'doi-tac' với static request cache và transient cache.
 * Khắc phục N+1 query khi render card dự án trong vòng lặp.
 *
 * @param string|null $chu_dau_tu Tên chủ đầu tư
 * @return mixed ID ảnh logo, URL, hoặc null nếu không tìm thấy
 */
function batdongsan_get_partner_logo_by_name(?string $chu_dau_tu) {
    if (empty($chu_dau_tu)) {
        return null;
    }

    $name = trim($chu_dau_tu);
    if ($name === '') {
        return null;
    }

    static $partner_logo_cache = [];
    $cache_key = md5(mb_strtolower($name));

    if (array_key_exists($cache_key, $partner_logo_cache)) {
        return $partner_logo_cache[$cache_key];
    }

    $transient_key = 'bds_partner_logo_' . substr($cache_key, 0, 20);
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        $partner_logo_cache[$cache_key] = $cached;
        return $cached;
    }

    $partner_query = new WP_Query([
        'post_type'      => 'doi-tac',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        's'              => $name,
        'no_found_rows'  => true,
    ]);

    $logo_id = null;
    if ($partner_query->have_posts()) {
        $partner_post = $partner_query->posts[0];
        $logo_id = get_field('logo', $partner_post->ID);
    }
    wp_reset_postdata();

    set_transient($transient_key, $logo_id, HOUR_IN_SECONDS);
    $partner_logo_cache[$cache_key] = $logo_id;

    return $logo_id;
}

/**
 * Shortcode [bds_before_after] & [beaf_before_after]
 * Chuẩn tính năng so sánh hình ảnh Before/After của plugin BEAF
 *
 * Cách dùng:
 * [bds_before_after before="URL_HOAC_ID" after="URL_HOAC_ID" before_label="Bản vẽ 2D" after_label="Phối cảnh 3D" orientation="horizontal" move_on_hover="true"]
 */
function bds_beaf_before_after_shortcode($atts) {
    $atts = shortcode_atts([
        'before'        => '',
        'after'         => '',
        'before_label'  => 'Bản vẽ 2D',
        'after_label'   => 'Phối cảnh 3D',
        'orientation'   => 'horizontal',
        'move_on_hover' => 'true',
        'offset'        => 50,
        'aspect_ratio'  => 'aspect-[16/10]',
    ], $atts, 'bds_before_after');

    if (empty($atts['before']) && empty($atts['after'])) {
        return '';
    }

    ob_start();
    get_template_part('template-parts/comparison-slider', null, [
        'before_url'    => $atts['before'],
        'after_url'     => $atts['after'],
        'before_label'  => $atts['before_label'],
        'after_label'   => $atts['after_label'],
        'orientation'   => $atts['orientation'],
        'move_on_hover' => filter_var($atts['move_on_hover'], FILTER_VALIDATE_BOOLEAN),
        'offset'        => (int) $atts['offset'],
        'aspect_ratio'  => $atts['aspect_ratio'],
    ]);
    return ob_get_clean();
}
if (function_exists('add_shortcode')) {
    add_shortcode('bds_before_after', 'bds_beaf_before_after_shortcode');
    add_shortcode('beaf_before_after', 'bds_beaf_before_after_shortcode');
}

