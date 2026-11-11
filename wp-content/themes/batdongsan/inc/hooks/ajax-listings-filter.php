<?php

/**
 * AJAX handler: Lọc bất động sản (bat-dong-san) theo các tiêu chí tìm kiếm.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__DIR__) . '/helpers/format.php';
require_once dirname(__DIR__) . '/helpers/filter-options.php';
require_once dirname(__DIR__) . '/helpers/filter-slug.php';
require_once dirname(__DIR__) . '/services/filter/bootstrap.php';

/**
 * Helper format label khoảng giá cho chip/badge filter.
 */
if (!function_exists('batdongsan_format_price_range_label')) {
    function batdongsan_format_price_range_label(string $key): string {
        if ($key === 'thoa_thuan') {
            return 'Thỏa thuận';
        }
        $fmt = function ($v) {
            if ($v >= 1000) {
                $ty = $v / 1000;
                return (floor($ty) == $ty ? (int) $ty : number_format($ty, 1, ',', '.')) . ' tỷ';
            }
            if ($v > 0 && $v <= 100) {
                return (floor($v) == $v ? (int) $v : number_format($v, 1, ',', '.')) . ' tỷ';
            }
            return $v . ' triệu';
        };
        if (strpos($key, '-') !== false) {
            [$min, $max] = array_map('floatval', explode('-', $key, 2));
            if ($min === 0.0) {
                return 'Dưới ' . $fmt($max);
            }
            if ($max === 0.0) {
                return 'Trên ' . $fmt($min);
            }
            return $fmt($min) . ' - ' . $fmt($max);
        }
        $v = (float) $key;
        return 'Trên ' . $fmt($v);
    }
}

/**
 * Helper format label diện tích cho chip/badge filter.
 */
if (!function_exists('batdongsan_format_area_range_label')) {
    function batdongsan_format_area_range_label(string $key): string {
        if (strpos($key, '-') !== false) {
            [$min, $max] = array_map('floatval', explode('-', $key, 2));
            if ($min === 0.0) {
                return 'Dưới ' . $max . ' m²';
            }
            if ($max === 0.0) {
                return 'Trên ' . $min . ' m²';
            }
            return $min . ' - ' . $max . ' m²';
        }
        return 'Trên ' . $key . ' m²';
    }
}

/**
 * AJAX filter cho bài đăng CPT bat-dong-san.
 */
function batdongsan_filter_listings(): void {
    // 1. Security: Nonce verification
    if (!check_ajax_referer('bds_filter_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Forbidden: Invalid or missing nonce'], 403);
        exit;
    }

    // 2. Sanitize user inputs
    $hinh_thuc = isset($_POST['hinh_thuc']) ? sanitize_text_field(wp_unslash($_POST['hinh_thuc'])) : '';
    if (empty($hinh_thuc) && isset($_POST['hinh-thuc'])) {
        $hinh_thuc = sanitize_text_field(wp_unslash($_POST['hinh-thuc']));
    }

    $loai_hinh = isset($_POST['loai_hinh']) ? sanitize_text_field(wp_unslash($_POST['loai_hinh'])) : '';
    if (empty($loai_hinh) && isset($_POST['loai-hinh'])) {
        $loai_hinh = sanitize_text_field(wp_unslash($_POST['loai-hinh']));
    }
    if (empty($loai_hinh) && isset($_POST['type'])) {
        $loai_hinh = sanitize_text_field(wp_unslash($_POST['type']));
    }

    $khu_vuc = isset($_POST['khu_vuc']) ? sanitize_text_field(wp_unslash($_POST['khu_vuc'])) : '';
    if (empty($khu_vuc) && isset($_POST['khu-vuc'])) {
        $khu_vuc = sanitize_text_field(wp_unslash($_POST['khu-vuc']));
    }

    $du_an  = isset($_POST['du_an']) ? absint($_POST['du_an']) : 0;
    $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
    $name   = isset($_POST['project-name']) ? trim(sanitize_text_field(wp_unslash($_POST['project-name']))) : '';
    if (empty($name) && isset($_POST['name'])) {
        $name = trim(sanitize_text_field(wp_unslash($_POST['name'])));
    }

    // Extended parameters (Bug 6)
    $so_phong_ngu = '';
    if (isset($_POST['so_phong_ngu'])) {
        $so_phong_ngu = sanitize_text_field(wp_unslash($_POST['so_phong_ngu']));
    } elseif (isset($_POST['phong_ngu'])) {
        $so_phong_ngu = sanitize_text_field(wp_unslash($_POST['phong_ngu']));
    } elseif (isset($_POST['bedrooms'])) {
        $so_phong_ngu = sanitize_text_field(wp_unslash($_POST['bedrooms']));
    }

    $so_phong_ve_sinh = '';
    if (isset($_POST['so_phong_ve_sinh'])) {
        $so_phong_ve_sinh = sanitize_text_field(wp_unslash($_POST['so_phong_ve_sinh']));
    } elseif (isset($_POST['phong_ve_sinh'])) {
        $so_phong_ve_sinh = sanitize_text_field(wp_unslash($_POST['phong_ve_sinh']));
    } elseif (isset($_POST['bathrooms'])) {
        $so_phong_ve_sinh = sanitize_text_field(wp_unslash($_POST['bathrooms']));
    }

    $huong_nha = '';
    if (isset($_POST['huong_nha'])) {
        $huong_nha = sanitize_text_field(wp_unslash($_POST['huong_nha']));
    } elseif (isset($_POST['huong'])) {
        $huong_nha = sanitize_text_field(wp_unslash($_POST['huong']));
    } elseif (isset($_POST['direction'])) {
        $huong_nha = sanitize_text_field(wp_unslash($_POST['direction']));
    }

    $phap_ly = '';
    if (isset($_POST['phap_ly'])) {
        $phap_ly = sanitize_text_field(wp_unslash($_POST['phap_ly']));
    } elseif (isset($_POST['legal'])) {
        $phap_ly = sanitize_text_field(wp_unslash($_POST['legal']));
    }


    // Price inputs
    $gia_min   = (isset($_POST['gia_min']) && $_POST['gia_min'] !== '' && $_POST['gia_min'] !== '0') ? (float) $_POST['gia_min'] : null;
    $gia_max   = (isset($_POST['gia_max']) && $_POST['gia_max'] !== '' && $_POST['gia_max'] !== '0') ? (float) $_POST['gia_max'] : null;
    $price_raw = isset($_POST['price']) ? sanitize_text_field(wp_unslash($_POST['price'])) : '';

    if ($gia_min === null && $gia_max === null && $price_raw !== '' && $price_raw !== '0' && $price_raw !== 'thoa_thuan') {
        if (strpos($price_raw, '-') !== false) {
            $parts   = explode('-', $price_raw, 2);
            $gia_min = trim($parts[0]) !== '' ? (float) trim($parts[0]) : null;
            $gia_max = trim($parts[1]) !== '' ? (float) trim($parts[1]) : null;
            if ($gia_max !== null && $gia_max <= 0.0) {
                $gia_max = null;
            }
            if ($gia_min !== null && $gia_min <= 0.0) {
                $gia_min = null;
            }
        } else {
            $gia_min = (float) $price_raw;
        }
    }

    // Area inputs
    $dien_tich_min = (isset($_POST['dien_tich_min']) && $_POST['dien_tich_min'] !== '' && $_POST['dien_tich_min'] !== '0') ? (float) $_POST['dien_tich_min'] : null;
    $dien_tich_max = (isset($_POST['dien_tich_max']) && $_POST['dien_tich_max'] !== '' && $_POST['dien_tich_max'] !== '0') ? (float) $_POST['dien_tich_max'] : null;
    $area_raw      = isset($_POST['area']) ? sanitize_text_field(wp_unslash($_POST['area'])) : '';

    if ($dien_tich_min === null && $dien_tich_max === null && $area_raw !== '' && $area_raw !== '0') {
        if (strpos($area_raw, '-') !== false) {
            $parts         = explode('-', $area_raw, 2);
            $dien_tich_min = trim($parts[0]) !== '' ? (float) trim($parts[0]) : null;
            $dien_tich_max = trim($parts[1]) !== '' ? (float) trim($parts[1]) : null;
            if ($dien_tich_max !== null && $dien_tich_max <= 0.0) {
                $dien_tich_max = null;
            }
            if ($dien_tich_min !== null && $dien_tich_min <= 0.0) {
                $dien_tich_min = null;
            }
        } else {
            $dien_tich_min = (float) $area_raw;
        }
    }

    // Pagination & Sorting
    $paged = isset($_POST['paged']) && is_scalar($_POST['paged'])
        ? max(1, (int) $_POST['paged'])
        : 1;
    $posts_per_page = isset($_POST['posts_per_page']) && (int) $_POST['posts_per_page'] > 0 ? absint($_POST['posts_per_page']) : 20;
    $orderby        = isset($_POST['orderby']) ? sanitize_text_field(wp_unslash($_POST['orderby'])) : '';
    if (empty($orderby) && isset($_POST['order'])) {
        $orderby = sanitize_text_field(wp_unslash($_POST['order']));
    }
    if (empty($orderby)) {
        $orderby = '1';
    }

    // Danh sách quận/huyện Hà Nội để lọc theo dia_chi (Bao gồm ngoại thành - Bug 7)
    $area_options = function_exists('batdongsan_get_area_options')
        ? batdongsan_get_area_options(false)
        : [
            'gia-lam'     => 'Gia Lâm',
            'nam-tu-liem' => 'Nam Từ Liêm',
            'hai-ba-trung'=> 'Hai Bà Trưng',
            'tay-ho'      => 'Tây Hồ',
            'long-bien'   => 'Long Biên',
            'cau-giay'    => 'Cầu Giấy',
            'dong-da'     => 'Đống Đa',
            'hoang-mai'   => 'Hoàng Mai',
            'thanh-xuan'  => 'Thanh Xuân',
            'ha-dong'     => 'Hà Đông',
            'ba-dinh'     => 'Ba Đình',
            'hoan-kiem'   => 'Hoàn Kiếm',
        ];


    // 3. Construct filter parameters and query listings via BDS_Filter_Query_Service
    $price_key = $price_raw ?: (($gia_min !== null || $gia_max !== null) ? (($gia_min ?? 0) . '-' . ($gia_max ?? 0)) : null);
    $area_key  = $area_raw ?: (($dien_tich_min !== null || $dien_tich_max !== null) ? (($dien_tich_min ?? 0) . '-' . ($dien_tich_max ?? 0)) : null);
    $order_num = (int) ($orderby === 'price_asc' ? 2 : ($orderby === 'price_desc' ? 3 : ($orderby === 'area_asc' ? 4 : ($orderby === 'area_desc' ? 5 : $orderby))));

    $filter_params = [
        'hinh_thuc'        => $hinh_thuc,
        'loai_hinh'        => $loai_hinh,
        'type'             => $loai_hinh,
        'khu_vuc'          => $khu_vuc,
        'du_an'            => $du_an,
        'status'           => $status,
        'name'             => $name,
        'project-name'     => $name,
        'so_phong_ngu'     => $so_phong_ngu,
        'so_phong_ve_sinh' => $so_phong_ve_sinh,
        'huong_nha'        => $huong_nha,
        'phap_ly'          => $phap_ly,
        'price'            => $price_raw,
        'gia_min'          => $gia_min,
        'gia_max'          => $gia_max,
        'area'             => $area_raw,
        'dien_tich_min'    => $dien_tich_min,
        'dien_tich_max'    => $dien_tich_max,
        'paged'            => $paged,
        'posts_per_page'   => $posts_per_page,
        'orderby'          => $orderby,
        'order'            => $orderby,
    ];

    // Server-side Transient Cache (bds_ajax_*) for production speedup
    $is_test_env = class_exists('BDS\Tests\Framework\MockWordPress', false) || defined('BDS_TESTING');
    $encoded_params = function_exists('wp_json_encode') ? wp_json_encode($filter_params) : json_encode($filter_params);
    $cache_key   = 'bds_ajax_' . md5($encoded_params);
    if (!$is_test_env && function_exists('get_transient')) {
        $cached_res = get_transient($cache_key);
        if (!empty($cached_res) && is_array($cached_res)) {
            wp_send_json_success($cached_res);
            return;
        }
    }

    if (class_exists('BDS_Filter_Query_Service')) {
        $query = BDS_Filter_Query_Service::query_listings($filter_params);
    } else {
        $query = new WP_Query([
            'post_type'      => 'bat-dong-san',
            'post_status'    => 'publish',
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
        ]);
    }

    $paged_posts  = $query->posts;
    $total        = (int) $query->found_posts;
    $total_pages  = max(1, (int) $query->max_num_pages);
    $current_page = $paged;

    // 8. Render listing HTML
    ob_start();
    if (!empty($paged_posts)) {
        if (function_exists('update_post_caches')) {
            update_post_caches($paged_posts, 'bat-dong-san', true, true);
        }
        if (function_exists('batdongsan_prime_listings_cache')) {
            batdongsan_prime_listings_cache($paged_posts);
        }
        foreach ($paged_posts as $value) {
            $item_template = get_template_directory() . '/paths/bat-dong-san-item.php';
            if (file_exists($item_template)) {
                (function ($value, $item_template) {
                    include $item_template;
                })($value, $item_template);
            } else {
                echo '<div class="bds-item"><h3>' . esc_html($value->post_title) . '</h3></div>';
            }
        }
    } else {
        echo '<div class="text-center py-16 px-5 bg-white rounded-2xl border border-gray-100 shadow-sm">';
        if (function_exists('batdongsan_icon')) {
            echo batdongsan_icon('search', ['width' => 56, 'height' => 56, 'class' => 'mx-auto mb-4 text-gray-300']);
        }
        echo '<h3 class="text-lg font-semibold text-gray-800 mb-1">Không tìm thấy bất động sản nào</h3>';
        echo '<p class="text-gray-500 text-sm mb-5">Thử nới lỏng khoảng giá, diện tích hoặc đổi khu vực tìm kiếm.</p>';
        echo '<button type="button" data-clear-all-filters class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white text-xs font-medium rounded-full hover:bg-primary-hover transition shadow-sm">';
        echo 'Xóa bộ lọc và xem tất cả';
        echo '</button>';
        echo '</div>';
    }
    $html = ob_get_clean();

    // 9. SEO URL & Pagination Generation
    $base_url = home_url('/bat-dong-san/');
    $query_params = array_filter([
        'hinh_thuc'        => $hinh_thuc ?: null,
        'du_an'            => $du_an ?: null,
        'project-name'     => $name ?: null,
        'type'             => $loai_hinh ?: null,
        'khu_vuc'          => $khu_vuc ?: null,
        'status'           => $status ?: null,
        'order'            => ($order_num > 1) ? $order_num : null,
        'so_phong_ngu'     => $so_phong_ngu ?: null,
        'so_phong_ve_sinh' => $so_phong_ve_sinh ?: null,
        'huong_nha'        => !empty($filter_params['huong_nha']) ? $filter_params['huong_nha'] : null,
        'phap_ly'          => !empty($filter_params['phap_ly']) ? $filter_params['phap_ly'] : null,
    ]);

    $url = function_exists('batdongsan_bds_filter_url')
        ? batdongsan_bds_filter_url($base_url, $price_key, $area_key, $query_params, $current_page)
        : $base_url;

    ob_start();
    if ($total_pages > 1) {
        echo '<nav class="mt-8 flex justify-center" aria-label="Phân trang"><ul class="flex items-center gap-1.5 list-none p-0 m-0">';
        if ($current_page > 1) {
            $prev_url = function_exists('batdongsan_bds_filter_url')
                ? batdongsan_bds_filter_url($base_url, $price_key, $area_key, $query_params, $current_page - 1)
                : '#';
            echo '<li><a href="' . esc_url($prev_url) . '" data-page="' . ($current_page - 1) . '" class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-black/[0.08] bg-white text-charcoal no-underline text-xs font-medium hover:bg-black/[0.04] transition" aria-label="Trang trước">←</a></li>';
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i === $current_page) {
                echo '<li><span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-primary text-white text-xs font-semibold shadow-sm" aria-current="page">' . $i . '</span></li>';
            } else {
                $page_url = function_exists('batdongsan_bds_filter_url')
                    ? batdongsan_bds_filter_url($base_url, $price_key, $area_key, $query_params, $i)
                    : '#';
                echo '<li><a href="' . esc_url($page_url) . '" data-page="' . $i . '" class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-black/[0.08] bg-white text-charcoal no-underline text-xs font-medium hover:bg-black/[0.04] transition">' . $i . '</a></li>';
            }
        }
        if ($current_page < $total_pages) {
            $next_url = function_exists('batdongsan_bds_filter_url')
                ? batdongsan_bds_filter_url($base_url, $price_key, $area_key, $query_params, $current_page + 1)
                : '#';
            echo '<li><a href="' . esc_url($next_url) . '" data-page="' . ($current_page + 1) . '" class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-black/[0.08] bg-white text-charcoal no-underline text-xs font-medium hover:bg-black/[0.04] transition" aria-label="Trang sau">→</a></li>';
        }
        echo '</ul></nav>';
    }
    $pagination_html = ob_get_clean();

    // 10. Construct Active Filter Chips for UI/UX
    $active_filters = [];
    if (!empty($hinh_thuc) && $hinh_thuc !== '0') {
        $ht_labels = [
            'chuyen-nhuong' => 'Hàng chuyển nhượng',
            'cho-thue'      => 'Cho thuê',
            'mua-ban'       => 'Mua / Bán',
            'so-cap'        => 'Sơ cấp',
            'trung-cap'     => 'Trung cấp',
            'cao-cap'       => 'Cao cấp',
        ];
        if (isset($ht_labels[$hinh_thuc])) {
            $active_filters[] = ['key' => 'hinh_thuc', 'label' => 'Hình thức: ' . $ht_labels[$hinh_thuc]];
        }
    }
    if (!empty($khu_vuc) && isset($area_options[$khu_vuc])) {
        $active_filters[] = ['key' => 'khu_vuc', 'label' => 'Khu vực: ' . $area_options[$khu_vuc]];
    }
    if (!empty($loai_hinh) && $loai_hinh !== '0') {
        $lh_term = is_numeric($loai_hinh) ? get_term((int)$loai_hinh, 'loai_hinh_bat_dong_san') : get_term_by('slug', $loai_hinh, 'loai_hinh_bat_dong_san');
        if ($lh_term && !is_wp_error($lh_term)) {
            $active_filters[] = ['key' => 'type', 'label' => 'Loại hình: ' . $lh_term->name];
        }
    }
    if (!empty($price_key) && $price_key !== '0') {
        $active_filters[] = ['key' => 'price', 'label' => 'Giá: ' . batdongsan_format_price_range_label($price_key)];
    }
    if (!empty($area_key) && $area_key !== '0') {
        $active_filters[] = ['key' => 'area', 'label' => 'Diện tích: ' . batdongsan_format_area_range_label($area_key)];
    }
    if (!empty($du_an)) {
        $active_filters[] = ['key' => 'du_an', 'label' => 'Dự án: ' . get_the_title($du_an)];
    }
    if (!empty($name)) {
        $active_filters[] = ['key' => 'project-name', 'label' => 'Từ khóa: "' . $name . '"'];
    }
    if (!empty($so_phong_ngu) && $so_phong_ngu !== '0') {
        $active_filters[] = ['key' => 'so_phong_ngu', 'label' => 'Phòng ngủ: ' . $so_phong_ngu . '+'];
    }
    if (!empty($so_phong_ve_sinh) && $so_phong_ve_sinh !== '0') {
        $active_filters[] = ['key' => 'so_phong_ve_sinh', 'label' => 'Vệ sinh: ' . $so_phong_ve_sinh . '+'];
    }
    $filter_huong_nha = $filter_params['huong_nha'] ?? '';
    if (!empty($filter_huong_nha) && $filter_huong_nha !== '0' && $filter_huong_nha !== 'all') {
        $hn_labels = [
            'dong' => 'Đông', 'tay' => 'Tây', 'nam' => 'Nam', 'bac' => 'Bắc',
            'dong-nam' => 'Đông Nam', 'dong_nam' => 'Đông Nam',
            'dong-bac' => 'Đông Bắc', 'dong_bac' => 'Đông Bắc',
            'tay-nam' => 'Tây Nam', 'tay_nam' => 'Tây Nam',
            'tay-bac' => 'Tây Bắc', 'tay_bac' => 'Tây Bắc'
        ];
        $hn_key = str_replace('-', '_', $filter_huong_nha);
        $active_filters[] = ['key' => 'huong_nha', 'label' => 'Hướng: ' . ($hn_labels[$filter_huong_nha] ?? ($hn_labels[$hn_key] ?? $filter_huong_nha))];
    }
    $filter_phap_ly = $filter_params['phap_ly'] ?? '';
    if (!empty($filter_phap_ly) && $filter_phap_ly !== '0' && $filter_phap_ly !== 'all') {
        $pl_labels = [
            'so-do' => 'Sổ đỏ / Sổ hồng', 'so_do' => 'Sổ đỏ / Sổ hồng',
            'so_hong' => 'Sổ hồng', 'so_do_so_hong' => 'Sổ đỏ / Sổ hồng',
            'hop-dong-mua-ban' => 'Hợp đồng mua bán', 'hop_dong_mua_ban' => 'Hợp đồng mua bán', 'hop_dong' => 'Hợp đồng mua bán',
            'dang_ky_quyen_su_dung_dat' => 'Đăng ký QSD đất',
            'giay_chung_nhan' => 'Giấy chứng nhận',
            'dang-cho-so' => 'Đang chờ sổ', 'dang_cho_so' => 'Đang chờ sổ',
            'khac' => 'Khác',
        ];
        $pl_key = str_replace('-', '_', $filter_phap_ly);
        $active_filters[] = ['key' => 'phap_ly', 'label' => 'Pháp lý: ' . ($pl_labels[$filter_phap_ly] ?? ($pl_labels[$pl_key] ?? $filter_phap_ly))];
    }

    // 11. Compute Dynamic SEO Title for client-side synchronization
    $seo_title = function_exists('batdongsan_get_bds_filter_seo_title')
        ? batdongsan_get_bds_filter_seo_title([
            'hinh_thuc' => $hinh_thuc,
            'type'      => $loai_hinh,
            'khu_vuc'   => $khu_vuc,
            'price'     => $price_key,
            'area'      => $area_key,
            'du_an'     => $du_an,
            'name'      => $name,
        ])
        : null;

    $heading_title = $seo_title;
    if (empty($heading_title)) {
        $ht_default_headings = [
            'chuyen-nhuong' => 'Hàng chuyển nhượng',
            'cho-thue'      => 'Bất động sản cho thuê',
            'mua-ban'       => 'Bất động sản mua bán',
            'so-cap'        => 'Bất động sản sơ cấp',
            'trung-cap'     => 'Bất động sản trung cấp',
            'cao-cap'       => 'Bất động sản cao cấp',
        ];
        $heading_title = $ht_default_headings[$hinh_thuc] ?? 'Bất động sản';
    }

    // 12. Send JSON Response (supports count, found_posts, max_num_pages, title)
    $response_data = [
        'html'           => $html,
        'pagination'     => $pagination_html,
        'total'          => $total,
        'count'          => $total,
        'found_posts'    => $total,
        'total_pages'    => $total_pages,
        'max_num_pages'  => $total_pages,
        'current_page'   => $current_page,
        'url'            => $url,
        'title'          => $seo_title ? ($seo_title . ' | ' . (function_exists('get_bloginfo') ? get_bloginfo('name') : 'Bất Động Sản Hà Nội')) : null,
        'heading_title'  => $heading_title,
        'active_filters' => $active_filters,
    ];

    if (!$is_test_env && function_exists('set_transient')) {
        set_transient($cache_key, $response_data, 1800);
    }

    wp_send_json_success($response_data);
}

add_action('wp_ajax_batdongsan_filter_listings', 'batdongsan_filter_listings');
add_action('wp_ajax_nopriv_batdongsan_filter_listings', 'batdongsan_filter_listings');
add_action('wp_ajax_bds_filter_listings', 'batdongsan_filter_listings');
add_action('wp_ajax_nopriv_bds_filter_listings', 'batdongsan_filter_listings');
