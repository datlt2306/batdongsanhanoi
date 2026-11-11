<?php
/**
 * Breadcrumb helpers
 * 
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tạo breadcrumb cho single bất động sản (bat-dong-san)
 * 
 * @param int $post_id Post ID
 * @return array Mảng các breadcrumb items với label và url
 */
function batdongsan_get_bat_dong_san_breadcrumb($post_id): array {
    $breadcrumb_parts = [];
    
    // 1. Trang chủ
    $breadcrumb_parts[] = [
        'label' => 'Trang chủ',
        'url'   => home_url('/'),
    ];
    
    // 2. Hình thức (Sơ cấp / Chuyển nhượng / Cho thuê)
    $hinh_thuc_terms = get_the_terms($post_id, 'hinh-thuc-bds');
    $hinh_thuc_slug = '';
    $listing_url = home_url('/bat-dong-san');
    $listing_label = 'Bất động sản';

    if ($hinh_thuc_terms && !is_wp_error($hinh_thuc_terms) && !empty($hinh_thuc_terms)) {
        // Ưu tiên leaf term (term con như cao-cap, trung-cap, so-cap) trước term cha (mua-ban)
        $term_slugs = wp_list_pluck($hinh_thuc_terms, 'slug');
        if (in_array('cao-cap', $term_slugs, true)) {
            $listing_label = 'Bất động sản cao cấp';
            $listing_url = home_url('/bat-dong-san/?hinh_thuc=cao-cap');
        } elseif (in_array('trung-cap', $term_slugs, true)) {
            $listing_label = 'Bất động sản trung cấp';
            $listing_url = home_url('/bat-dong-san/?hinh_thuc=trung-cap');
        } elseif (in_array('so-cap', $term_slugs, true)) {
            $listing_label = 'Bất động sản sơ cấp';
            $listing_url = home_url('/bat-dong-san/?hinh_thuc=so-cap');
        } elseif (in_array('chuyen-nhuong', $term_slugs, true)) {
            $listing_label = 'Hàng chuyển nhượng';
            $listing_url = home_url('/bat-dong-san/?hinh_thuc=chuyen-nhuong');
        } elseif (in_array('cho-thue', $term_slugs, true)) {
            $listing_label = 'Bất động sản cho thuê';
            $listing_url = home_url('/bat-dong-san/?hinh_thuc=cho-thue');
        } elseif (in_array('mua-ban', $term_slugs, true)) {
            $listing_label = 'Bất động sản mua bán';
            $listing_url = home_url('/bat-dong-san/?hinh_thuc=mua-ban');
        }
    }

    $breadcrumb_parts[] = [
        'label' => $listing_label,
        'url'   => $listing_url,
    ];
    
    // 3. Dự án trực thuộc (nếu có)
    $project_id = function_exists('batdongsan_get_related_project_id')
        ? batdongsan_get_related_project_id($post_id)
        : absint(get_post_meta($post_id, 'du_an_lien_quan', true));
    if ($project_id > 0) {
        $breadcrumb_parts[] = [
            'label' => get_the_title($project_id),
            'url'   => get_permalink($project_id),
        ];
    }
    
    // 4. Loại hình từ taxonomy loai_hinh_bat_dong_san
    $loai_hinh_terms = get_the_terms($post_id, 'loai_hinh_bat_dong_san');
    if (!is_wp_error($loai_hinh_terms) && !empty($loai_hinh_terms)) {
        $term = $loai_hinh_terms[0];
        $breadcrumb_parts[] = [
            'label' => $term->name,
            'url'   => home_url('/ban-' . $term->slug . '/'),
        ];
    }
    
    return $breadcrumb_parts;
}

/**
 * Legacy alias for hang_chuyen_nhuong breadcrumb.
 */
function batdongsan_get_hang_chuyen_nhuong_breadcrumb($post_id): array {
    return batdongsan_get_bat_dong_san_breadcrumb($post_id);
}

/**
 * Tạo breadcrumb cho single du-an
 * 
 * @param int $post_id Post ID
 * @return array Mảng các breadcrumb items với label và url
 */
function batdongsan_get_du_an_breadcrumb($post_id): array {
    $breadcrumb_parts = [];
    
    // Dự án
    $breadcrumb_parts[] = [
        'label' => 'Dự án',
        'url'   => get_post_type_archive_link('du-an') ?: home_url('/du-an'),
    ];
    
    // Khu vực từ taxonomy
    $khu_vuc_terms = get_the_terms($post_id, 'khu-vuc-du-an');
    if (!is_wp_error($khu_vuc_terms) && !empty($khu_vuc_terms)) {
        $primary_term = $khu_vuc_terms[0];
        $ancestors = get_ancestors($primary_term->term_id, 'khu-vuc-du-an', 'taxonomy');
        $ancestors = array_reverse($ancestors);
        
        foreach ($ancestors as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'khu-vuc-du-an');
            if ($ancestor && !is_wp_error($ancestor)) {
                $breadcrumb_parts[] = [
                    'label' => $ancestor->name,
                    'url'   => get_term_link($ancestor),
                ];
            }
        }
        
        $breadcrumb_parts[] = [
            'label' => $primary_term->name,
            'url'   => get_term_link($primary_term),
        ];
    }
    
    return $breadcrumb_parts;
}

/**
 * Rút gọn breadcrumb cho mobile (chỉ hiển thị: Trang chủ > ... > Current)
 * 
 * @param array $breadcrumb_parts Mảng breadcrumb đầy đủ
 * @return array|null Mảng breadcrumb đã rút gọn, hoặc null nếu không cần rút gọn
 */
function batdongsan_get_mobile_breadcrumb($breadcrumb_parts) {
    if (empty($breadcrumb_parts)) {
        return null;
    }
    
    // Rút gọn khi có >= 3 items để tiết kiệm không gian trên mobile
    // Ví dụ: "Trang chủ / Hàng chuyển nhượng / Căn hộ chung cư"
    // Rút gọn thành: "Trang chủ / ... / Căn hộ chung cư"
    if (count($breadcrumb_parts) < 3) {
        return null;
    }
    
    // Rút gọn: Trang chủ > ... > Item cuối cùng
    $first = $breadcrumb_parts[0];
    $last = end($breadcrumb_parts);
    
    return [
        $first,
        [
            'label' => '...',
            'url'   => '',
        ],
        $last,
    ];
}

