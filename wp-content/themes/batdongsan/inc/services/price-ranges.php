<?php

/**
 * Price ranges service
 * 
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lấy các khoảng giá động dựa trên dữ liệu thực tế của các dự án.
 * Dùng chung với form tìm kiếm dự án ở trang chủ.
 * 
 * @return array Mảng các khoảng giá với key là range và value là label
 */
function batdongsan_get_price_ranges(): array {
    $cache_key    = 'bds_price_ranges';
    $price_ranges = get_transient($cache_key);

    if ($price_ranges !== false) {
        return $price_ranges;
    }

    global $wpdb;

    $prices = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT pm.meta_value 
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = 'price'
        AND p.post_type = 'du-an'
        AND p.post_status = 'publish'
        AND CAST(pm.meta_value AS UNSIGNED) > 0
        ORDER BY CAST(pm.meta_value AS UNSIGNED) ASC
    "));

    $price_ranges = [
        '0' => 'Tất cả',
    ];

    if (empty($prices)) {
        $price_ranges['0-5']  = 'Dưới 5 tr/m2';
        $price_ranges['5-10'] = '5-10 tr/m2';
        $price_ranges['10-20'] = '10-20 tr/m2';
        $price_ranges['20-35'] = '20-35 tr/m2';
        $price_ranges['35-50'] = '35-50 tr/m2';
        $price_ranges['50-80'] = '50-80 tr/m2';
        $price_ranges['80']    = 'Trên 80 tr/m2';
    } else {
        $prices    = array_map('floatval', $prices);
        $min_price = min($prices);
        $max_price = max($prices);
        $diff      = $max_price - $min_price;

        $num_ranges = 6;
        $base_range = $diff > 0 ? $diff / $num_ranges : 5;
        $range_size = max(5, ceil($base_range / 5) * 5);

        $start_price = floor($min_price / 5) * 5;

        if ($start_price > 0 && $start_price < 5) {
            $price_ranges['0-' . $start_price] = 'Dưới ' . $start_price . ' tr/m2';
        } elseif ($start_price >= 5) {
            $price_ranges['0-5'] = 'Dưới 5 tr/m2';
        }

        $current_min = max($start_price, 0);
        $last_max    = null;

        while ($current_min <= $max_price) {
            $current_max = $current_min + $range_size;
            $current_max = ceil($current_max / 5) * 5;

            if ($current_max >= $max_price) {
                if ($last_max !== null && $last_max < $max_price) {
                    $final_max = ceil($max_price / 5) * 5;
                    if ($final_max > $last_max) {
                        $price_ranges[$last_max . '-' . $final_max] = $last_max . '-' . $final_max . ' tr/m2';
                        $above_start = $final_max;
                    } else {
                        $above_start = $last_max;
                    }
                } elseif ($current_min < $max_price) {
                    $final_max = ceil($max_price / 5) * 5;
                    if ($final_max > $current_min) {
                        $price_ranges[$current_min . '-' . $final_max] = $current_min . '-' . $final_max . ' tr/m2';
                        $above_start = $final_max;
                    } else {
                        $above_start = floor($max_price / 5) * 5;
                    }
                } else {
                    $above_start = floor($max_price / 5) * 5;
                }

                $price_ranges[$above_start] = 'Trên ' . $above_start . ' tr/m2';
                break;
            }

            $price_ranges[$current_min . '-' . $current_max] = $current_min . '-' . $current_max . ' tr/m2';
            $last_max  = $current_max;
            $current_min = $current_max;
        }
    }

    set_transient($cache_key, $price_ranges, HOUR_IN_SECONDS);

    return $price_ranges;
}

/**
 * Xây dựng khoảng giá TỔNG (triệu/tỷ VND) từ danh sách posts.
 * Chỉ trả về các khoảng có ít nhất 1 sản phẩm.
 *
 * @param WP_Post[] $posts Danh sách posts đã lọc
 * @return array Mảng key => label
 */
function batdongsan_build_price_ranges_from_posts(array $posts): array {
    $price_ranges = ['0' => 'Tất cả'];

    $prices = [];
    $has_thoa_thuan = false;
    foreach ($posts as $post) {
        if (!($post instanceof WP_Post)) {
            continue;
        }
        if (function_exists('batdongsan_is_thoa_thuan_price') && batdongsan_is_thoa_thuan_price($post->ID)) {
            $has_thoa_thuan = true;
            continue;
        }
        if (function_exists('batdongsan_get_total_price_million')) {
            $value = batdongsan_get_total_price_million($post->ID);
            if ($value !== null && $value > 0) {
                $prices[] = (float) $value;
            }
        }
    }

    if ($has_thoa_thuan) {
        $price_ranges['thoa_thuan'] = 'Thỏa thuận';
    }

    $candidates = [
        ['key' => '0-500', 'min' => 0, 'max' => 500, 'label' => 'Dưới 500 triệu'],
        ['key' => '500-800', 'min' => 500, 'max' => 800, 'label' => '500 - 800 triệu'],
        ['key' => '800-1000', 'min' => 800, 'max' => 1000, 'label' => '800 triệu - 1 tỷ'],
        ['key' => '1000-2000', 'min' => 1000, 'max' => 2000, 'label' => '1 - 2 tỷ'],
        ['key' => '2000-3000', 'min' => 2000, 'max' => 3000, 'label' => '2 - 3 tỷ'],
        ['key' => '3000-5000', 'min' => 3000, 'max' => 5000, 'label' => '3 - 5 tỷ'],
        ['key' => '5000-7000', 'min' => 5000, 'max' => 7000, 'label' => '5 - 7 tỷ'],
        ['key' => '7000-10000', 'min' => 7000, 'max' => 10000, 'label' => '7 - 10 tỷ'],
        ['key' => '10000-20000', 'min' => 10000, 'max' => 20000, 'label' => '10 - 20 tỷ'],
        ['key' => '20000-30000', 'min' => 20000, 'max' => 30000, 'label' => '20 - 30 tỷ'],
        ['key' => '30000-40000', 'min' => 30000, 'max' => 40000, 'label' => '30 - 40 tỷ'],
        ['key' => '40000-60000', 'min' => 40000, 'max' => 60000, 'label' => '40 - 60 tỷ'],
        ['key' => '60000', 'min' => 60000, 'max' => null, 'label' => 'Trên 60 tỷ'],
    ];

    foreach ($candidates as $c) {
        $count = 0;
        foreach ($prices as $p) {
            if ($p < $c['min']) {
                continue;
            }
            if ($c['max'] !== null && $p > $c['max']) {
                continue;
            }
            $count++;
        }
        if ($count > 0) {
            $price_ranges[$c['key']] = $c['label'];
        }
    }

    return $price_ranges;
}

/**
 * Xây dựng khoảng diện tích từ danh sách posts.
 * Chỉ trả về các khoảng có ít nhất 1 sản phẩm.
 *
 * @param WP_Post[] $posts
 * @return array key => label
 */
function batdongsan_build_area_ranges_from_posts(array $posts): array {
    $areas = [];
    if (function_exists('batdongsan_get_area_number')) {
        foreach ($posts as $post) {
            if (!($post instanceof WP_Post)) {
                continue;
            }
            $value = batdongsan_get_area_number((int) $post->ID);
            if ($value !== null && $value > 0) {
                $areas[] = (float) $value;
            }
        }
    }

    if (empty($areas)) {
        return [];
    }

    $min_area = min($areas);
    $max_area = max($areas);

    $candidates = [
        ['key' => '0-50', 'min' => 0, 'max' => 50, 'label' => 'Dưới 50 m²'],
        ['key' => '50-80', 'min' => 50, 'max' => 80, 'label' => '50-80 m²'],
        ['key' => '80-100', 'min' => 80, 'max' => 100, 'label' => '80-100 m²'],
        ['key' => '100-120', 'min' => 100, 'max' => 120, 'label' => '100-120 m²'],
        ['key' => '120-150', 'min' => 120, 'max' => 150, 'label' => '120-150 m²'],
        ['key' => '150-200', 'min' => 150, 'max' => 200, 'label' => '150-200 m²'],
        ['key' => '200-300', 'min' => 200, 'max' => 300, 'label' => '200-300 m²'],
        ['key' => '300-500', 'min' => 300, 'max' => 500, 'label' => '300-500 m²'],
    ];
    $above = (int) (ceil($max_area / 50) * 50);
    if ($above >= 500) {
        $candidates[] = ['key' => (string) $above, 'min' => $above, 'max' => null, 'label' => 'Trên ' . $above . ' m²'];
    }

    $result = [];
    foreach ($candidates as $c) {
        $count = 0;
        foreach ($areas as $a) {
            if ($a < $c['min']) {
                continue;
            }
            if ($c['max'] !== null && $a > $c['max']) {
                continue;
            }
            $count++;
        }
        if ($count > 0) {
            $result[$c['key']] = $c['label'];
        }
    }
    return $result;
}

/**
 * Lấy các khoảng giá TỔNG (triệu/tỷ) động từ dữ liệu BĐS thực tế (khoang_gia).
 * Dùng cho filter sidebar khi chưa có danh sách posts (fallback).
 *
 * @return array Mảng các khoảng giá với key là range và value là label
 */
function batdongsan_get_bds_price_ranges(): array {
    $cache_key    = 'bds_listings_price_ranges_total';
    $price_ranges = get_transient($cache_key);

    if ($price_ranges !== false) {
        return $price_ranges;
    }

    $query = new WP_Query([
        'post_type'      => ['bat-dong-san', 'hang-chuyen-nhuong', 'nha-dat-ban'],
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);

    $posts = [];
    foreach ($query->posts as $id) {
        $posts[] = get_post($id);
    }
    wp_reset_postdata();

    $price_ranges = function_exists('batdongsan_build_price_ranges_from_posts')
        ? batdongsan_build_price_ranges_from_posts($posts)
        : batdongsan_get_bds_price_ranges_default();

    set_transient($cache_key, $price_ranges, HOUR_IN_SECONDS);

    return $price_ranges;
}

/**
 * Khoảng giá mặc định khi không có dữ liệu (triệu/tỷ VND).
 *
 * @return array
 */
function batdongsan_get_bds_price_ranges_default(): array {
    return [
        '0'        => 'Tất cả',
        'thoa_thuan' => 'Thỏa thuận',
        '0-500'    => 'Dưới 500 triệu',
        '500-800'  => '500 - 800 triệu',
        '800-1000' => '800 triệu - 1 tỷ',
        '1000-2000' => '1 - 2 tỷ',
        '2000-3000' => '2 - 3 tỷ',
        '3000-5000' => '3 - 5 tỷ',
        '5000-7000' => '5 - 7 tỷ',
        '7000-10000' => '7 - 10 tỷ',
        '10000-20000' => '10 - 20 tỷ',
        '20000-30000' => '20 - 30 tỷ',
        '30000-40000' => '30 - 40 tỷ',
        '40000-60000' => '40 - 60 tỷ',
        '60000'    => 'Trên 60 tỷ',
    ];
}

/**
 * Lấy các khoảng diện tích động từ dữ liệu BĐS thực tế, có transient cache.
 *
 * @return array
 */
function batdongsan_get_bds_area_ranges(): array {
    $cache_key   = 'bds_listings_area_ranges_total';
    $area_ranges = get_transient($cache_key);

    if ($area_ranges !== false && is_array($area_ranges)) {
        return $area_ranges;
    }

    $query = new WP_Query([
        'post_type'      => ['bat-dong-san', 'hang-chuyen-nhuong', 'nha-dat-ban'],
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);

    $posts = [];
    foreach ($query->posts as $id) {
        $posts[] = get_post($id);
    }
    wp_reset_postdata();

    $area_ranges = function_exists('batdongsan_build_area_ranges_from_posts')
        ? batdongsan_build_area_ranges_from_posts($posts)
        : [];

    set_transient($cache_key, $area_ranges, HOUR_IN_SECONDS);

    return $area_ranges;
}

