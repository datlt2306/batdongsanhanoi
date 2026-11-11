<?php

/**
 * Filter options helpers - chỉ hiển thị các tùy chọn filter có sản phẩm
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Danh sách toàn diện quận/huyện Hà Nội cho bộ lọc tìm kiếm.
 * Bao gồm đầy đủ 12 quận nội thành và các huyện/thị xã ngoại thành.
 *
 * @param bool $include_all Có thêm option 'Toàn Hà Nội' ở đầu mảng hay không.
 * @return array<string, string> Mảng slug => Tên hiển thị
 */
function batdongsan_get_area_options(bool $include_all = true): array {
    $options = [];

    // Nếu taxonomy 'khu-vuc' tồn tại và có terms trong cơ sở dữ liệu
    if (function_exists('taxonomy_exists') && taxonomy_exists('khu-vuc') && function_exists('get_terms')) {
        $terms = get_terms([
            'taxonomy'   => 'khu-vuc',
            'hide_empty' => false,
        ]);

        if (!empty($terms) && !is_wp_error($terms)) {
            // Thứ tự ưu tiên quận nội thành chuẩn của Hà Nội
            $preferred_order = [
                'ba-dinh', 'hoan-kiem', 'tay-ho', 'long-bien', 'cau-giay',
                'dong-da', 'hai-ba-trung', 'hoang-mai', 'thanh-xuan', 'ha-dong',
                'nam-tu-liem', 'bac-tu-liem', 'hoai-duc', 'dan-phuong', 'gia-lam',
                'dong-anh', 'soc-son', 'thanh-tri', 'me-linh', 'quoc-oai',
                'thach-that', 'chuong-my', 'thanh-oai', 'thuong-tin', 'phu-xuyen',
                'ung-hoa', 'my-duc', 'son-tay', 'ba-vi', 'phuc-tho',
            ];

            $term_map = [];
            foreach ($terms as $term) {
                if ($term->slug === 'ha-noi') {
                    continue; // term cha Tỉnh/Thành
                }
                $term_map[$term->slug] = $term->name;
            }

            // Sắp xếp: quận theo thứ tự quen thuộc trước, các quận/huyện mới thêm vào sau
            foreach ($preferred_order as $slug) {
                if (isset($term_map[$slug])) {
                    $options[$slug] = $term_map[$slug];
                    unset($term_map[$slug]);
                }
            }
            foreach ($term_map as $slug => $name) {
                $options[$slug] = $name;
            }
        }
    }

    // Fallback nếu chưa có terms
    if (empty($options)) {
        $options = [
            'ba-dinh'       => 'Ba Đình',
            'hoan-kiem'     => 'Hoàn Kiếm',
            'tay-ho'        => 'Tây Hồ',
            'long-bien'     => 'Long Biên',
            'cau-giay'      => 'Cầu Giấy',
            'dong-da'       => 'Đống Đa',
            'hai-ba-trung'  => 'Hai Bà Trưng',
            'hoang-mai'     => 'Hoàng Mai',
            'thanh-xuan'    => 'Thanh Xuân',
            'ha-dong'       => 'Hà Đông',
            'nam-tu-liem'   => 'Nam Từ Liêm',
            'bac-tu-liem'   => 'Bắc Từ Liêm',
            'hoai-duc'      => 'Hoài Đức',
            'dan-phuong'    => 'Đan Phượng',
            'gia-lam'       => 'Gia Lâm',
            'dong-anh'      => 'Đông Anh',
            'soc-son'       => 'Sóc Sơn',
            'thanh-tri'     => 'Thanh Trì',
            'me-linh'       => 'Mê Linh',
            'quoc-oai'      => 'Quốc Oai',
            'thach-that'    => 'Thạch Thất',
            'chuong-my'     => 'Chương Mỹ',
            'thanh-oai'     => 'Thanh Oai',
            'thuong-tin'    => 'Thường Tín',
            'phu-xuyen'     => 'Phú Xuyên',
            'ung-hoa'       => 'Ứng Hòa',
            'my-duc'        => 'Mỹ Đức',
            'son-tay'       => 'Sơn Tây',
            'ba-vi'         => 'Ba Vì',
            'phuc-tho'      => 'Phúc Thọ',
        ];
    }

    if ($include_all) {
        return array_merge(['' => 'Toàn Hà Nội'], $options);
    }

    return $options;
}


/**
 * Resolve slug loại hình BĐS sang term_id.
 *
 * @param string $slug term slug (vd: can-ho-chung-cu)
 * @return int 0 nếu không tìm thấy
 */
function batdongsan_resolve_loai_hinh_slug_to_id(string $slug): int {
    if ($slug === '') {
        return 0;
    }
    $term = get_term_by('slug', $slug, 'loai_hinh_bat_dong_san');
    return ($term && !is_wp_error($term)) ? (int) $term->term_id : 0;
}

/**
 * Lấy slug từ term_id loại hình BĐS.
 *
 * @param int $term_id
 * @return string
 */
function batdongsan_loai_hinh_id_to_slug(int $term_id): string {
    if ($term_id <= 0) {
        return '';
    }
    $term = get_term($term_id, 'loai_hinh_bat_dong_san');
    return ($term && !is_wp_error($term)) ? $term->slug : '';
}

/**
 * Lấy danh sách loại hình BĐS có ít nhất 1 sản phẩm.
 *
 * @param WP_Post[] $posts Danh sách BĐS
 * @return object[] term-like cho filter dropdown (term_id, name, slug)
 */
function batdongsan_filter_bds_type_options_by_posts(array $posts): array {
    return batdongsan_filter_type_terms_by_posts(
        get_terms(['taxonomy' => 'loai_hinh_bat_dong_san', 'hide_empty' => false]),
        $posts,
        'loai_hinh_bat_dong_san'
    );
}

/**
 * Resolve slug loại hình Dự án sang term_id.
 */
function batdongsan_resolve_loai_hinh_du_an_slug_to_id(string $slug): int {
    if ($slug === '') {
        return 0;
    }
    $term = get_term_by('slug', $slug, 'loai_hinh_du_an');
    return ($term && !is_wp_error($term)) ? (int) $term->term_id : 0;
}

/**
 * Lấy slug từ term_id loại hình Dự án.
 */
function batdongsan_loai_hinh_du_an_id_to_slug(int $term_id): string {
    if ($term_id <= 0) {
        return '';
    }
    $term = get_term($term_id, 'loai_hinh_du_an');
    return ($term && !is_wp_error($term)) ? $term->slug : '';
}

/**
 * Lấy danh sách loại hình dự án cho filter dropdown.
 *
 * @return object[] Array of objects với term_id, name, slug
 */
function batdongsan_get_du_an_type_options(): array {
    $terms = get_terms(['taxonomy' => 'loai_hinh_du_an', 'hide_empty' => false]);
    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }
    return array_map(function ($t) {
        return (object) ['term_id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug];
    }, $terms);
}

if (!function_exists('bds_filter_posts_by_name')) {
    /**
     * Filter posts_where để tìm theo project-name (post_title, post_content).
     * Dùng chung cho page-bat-dong-san, queries.
     *
     * @param string $where SQL WHERE clause
     * @return string
     */
    function bds_filter_posts_by_name($where) {
        global $wpdb;
        $search_name = isset($GLOBALS['bds_search_name']) ? $GLOBALS['bds_search_name'] : '';
        if ($search_name !== '') {
            $where .= $wpdb->prepare(
                " AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s)",
                '%' . $wpdb->esc_like($search_name) . '%',
                '%' . $wpdb->esc_like($search_name) . '%'
            );
        }
        return $where;
    }
}

/**
 * Lọc danh sách khu vực (area_options) chỉ giữ những quận/huyện có ít nhất 1 sản phẩm.
 *
 * @param array    $all_options Mảng slug => label (vd: ['gia-lam' => 'Gia Lâm', ...])
 * @param WP_Post[] $posts       Danh sách posts để kiểm tra meta (address hoặc dia_chi)
 * @param string   $meta_key    Meta key chứa địa chỉ (default: 'dia_chi' cho BĐS, dùng 'address' cho Dự án)
 * @return array Chỉ các option có sản phẩm (luôn có '' => 'Toàn Hà Nội')
 */
function batdongsan_filter_khu_vuc_options_by_posts(array $all_options, array $posts, string $meta_key = 'dia_chi'): array {
    $result = [];
    if (isset($all_options[''])) {
        $result[''] = $all_options[''];
    }

    $found_slugs = [];
    foreach ($posts as $post) {
        if (!($post instanceof WP_Post)) {
            continue;
        }

        // 1. Kiểm tra taxonomy 'khu-vuc' gán cho bài viết
        if (function_exists('wp_get_post_terms')) {
            $post_terms = wp_get_post_terms($post->ID, 'khu-vuc');
            if (!is_wp_error($post_terms) && !empty($post_terms)) {
                foreach ($post_terms as $t) {
                    if (is_object($t) && isset($t->slug) && isset($all_options[$t->slug])) {
                        $found_slugs[$t->slug] = true;
                    }
                }
            }
        }

        // 2. Fallback: kiểm tra chuỗi dia_chi hoặc address
        $dia_chi = get_post_meta($post->ID, $meta_key, true);
        if (is_string($dia_chi) && $dia_chi !== '') {
            foreach ($all_options as $slug => $label) {
                if ($slug === '') {
                    continue;
                }
                if (strpos($dia_chi, $label) !== false) {
                    $found_slugs[$slug] = true;
                }
            }
        }
    }

    foreach ($all_options as $slug => $label) {
        if ($slug !== '' && isset($found_slugs[$slug])) {
            $result[$slug] = $label;
        }
    }

    return $result;
}

/**
 * Lọc danh sách terms chỉ giữ các term có ít nhất 1 post.
 *
 * @param WP_Term[]|object[] $terms Danh sách terms
 * @param WP_Post[]          $posts Danh sách posts
 * @param string             $taxonomy Taxonomy name
 * @return object[] term-like (term_id, name, slug)
 */
function batdongsan_filter_type_terms_by_posts(array $terms, array $posts, string $taxonomy = 'loai_hinh_bat_dong_san'): array {
    if (empty($terms) || empty($posts)) {
        return [];
    }

    $term_ids_with_posts = [];
    foreach ($posts as $post) {
        if (!($post instanceof WP_Post)) {
            continue;
        }
        $post_terms = wp_get_post_terms($post->ID, $taxonomy);
        if (is_wp_error($post_terms) || empty($post_terms)) {
            continue;
        }
        foreach ($post_terms as $t) {
            $term_ids_with_posts[(int) $t->term_id] = true;
        }
    }

    $filtered = array_values(array_filter($terms, function ($term) use ($term_ids_with_posts) {
        $id = (int) ($term->term_id ?? $term->ID ?? 0);
        return $id > 0 && isset($term_ids_with_posts[$id]);
    }));
    return array_map(function ($t) {
        return (object) [
            'term_id' => (int) ($t->term_id ?? $t->ID ?? 0),
            'name'    => $t->name ?? $t->post_title ?? '',
            'slug'    => $t->slug ?? $t->post_name ?? '',
        ];
    }, $filtered);
}
