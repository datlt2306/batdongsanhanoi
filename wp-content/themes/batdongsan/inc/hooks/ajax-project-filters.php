<?php

/**
 * AJAX filters cho trang Dự án
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__DIR__) . '/services/filter/bootstrap.php';

/**
 * Helper: thêm điều kiện tìm theo tên dự án (title + content).
 *
 * Dùng riêng cho AJAX filter để không phụ thuộc template page-duan.php.
 *
 * @param string $where
 *
 * @return string
 */
function batdongsan_ajax_filter_posts_by_name(string $where): string
{
    global $wpdb;

    $search_name = isset($GLOBALS['batdongsan_ajax_search_name']) ? $GLOBALS['batdongsan_ajax_search_name'] : '';
    if ($search_name !== '') {
        $like = '%' . $wpdb->esc_like($search_name) . '%';
        // Tìm theo title hoặc content
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $where .= $wpdb->prepare(
            " AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s)",
            $like,
            $like
        );
    }

    return $where;
}

/**
 * AJAX handler: lọc dự án theo bộ lọc trên trang /du-an.
 *
 * Trả về HTML danh sách card dự án + tiêu đề / đoạn mô tả ngắn.
 */
function batdongsan_ajax_filter_projects(): void
{
    // Nonce check
    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'batdongsan_filter_projects')
    ) {
        wp_send_json_error(['message' => 'Forbidden: Invalid or missing nonce'], 403);
        exit;
    }

    // Giá trị filter
    $price  = isset($_POST['price']) ? sanitize_text_field(wp_unslash($_POST['price'])) : null;
    $type   = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : null;
    $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : null;
    $name   = isset($_POST['project-name']) ? trim(sanitize_text_field(wp_unslash($_POST['project-name']))) : null;
    // Accept both 'khu_vuc' (from filter-bar-horizontal form) and 'area' (legacy/fallback)
    $area   = isset($_POST['khu_vuc']) ? sanitize_text_field(wp_unslash($_POST['khu_vuc'])) : null;
    if (empty($area)) {
        $area = isset($_POST['area']) ? sanitize_text_field(wp_unslash($_POST['area'])) : null;
    }

    // Danh sách khu vực Hà Nội
    $area_options = function_exists('batdongsan_get_area_options')
        ? batdongsan_get_area_options(true)
        : [
            '' => 'Tất cả khu vực',
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

    $paged = isset($_POST['paged']) && is_scalar($_POST['paged'])
        ? max(1, (int) $_POST['paged'])
        : 1;
    $posts_per_page = isset($_POST['posts_per_page']) && (int) $_POST['posts_per_page'] > 0 ? absint($_POST['posts_per_page']) : 9;
    $orderby        = isset($_POST['orderby']) ? sanitize_text_field(wp_unslash($_POST['orderby'])) : '';
    if (empty($orderby) && isset($_POST['order'])) {
        $orderby    = sanitize_text_field(wp_unslash($_POST['order']));
    }

    $search = (!empty($name) || (!empty($type) && $type !== '0') || (!empty($area) && isset($area_options[$area])) || (!empty($status) && $status !== '0' && $status !== 'all') || ($price !== null && $price !== '' && $price !== '0'));

    $project_params = [
        'type'           => $type,
        'area'           => $area,
        'khu_vuc'        => $area,
        'status'         => $status,
        'price'          => $price,
        'keyword'        => $name,
        'name'           => $name,
        'project-name'   => $name,
        'paged'          => $paged,
        'posts_per_page' => $posts_per_page,
        'orderby'        => $orderby,
        'order'          => $orderby,
    ];

    if (class_exists('BDS_Filter_Query_Service')) {
        $query = BDS_Filter_Query_Service::query_projects($project_params);
    } else {
        $query = new WP_Query([
            'post_type'      => 'du-an',
            'post_status'    => 'publish',
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
        ]);
    }

    $posts        = $query->posts;
    $total        = (int) $query->found_posts;
    $total_pages  = max(1, (int) $query->max_num_pages);
    $current_page = $paged;

    if (!empty($posts) && function_exists('update_post_caches')) {
        update_post_caches($posts, 'du-an', true, true);
    }

    ob_start();
    if (!empty($posts)) {
        foreach ($posts as $post_obj) {
            if (function_exists('get_template_part')) {
                get_template_part('template-parts/card-project-horizontal', null, [
                    'post' => $post_obj,
                ]);
            } else {
                echo '<div class="project-card"><h3>' . esc_html(get_the_title($post_obj)) . '</h3></div>';
            }
        }
    } else {
        echo '<p class="text-gray-500 text-center py-8">Không tìm thấy dự án nào.</p>';
    }
    wp_reset_postdata();

    $html = ob_get_clean();

    // Xóa filter tìm theo tên để không ảnh hưởng query khác
    if (!empty($name)) {
        remove_filter('posts_where', 'batdongsan_ajax_filter_posts_by_name', 10);
        unset($GLOBALS['batdongsan_ajax_search_name']);
    }

    // Chuẩn bị tiêu đề và search results giống logic trong page-duan.php
    $title = $search ? 'Kết quả tìm kiếm phù hợp các tiêu chí:' : 'Tất cả dự án';
    
    // Tạo search results HTML nếu có filter
    $search_results_html = '';
    if ($search) {
        ob_start();
        $PriceDisplay = batdongsan_get_price_ranges();
        echo '<ul class="page-content__search-results">';
        if ($name) {
            echo '<li>Tên dự án theo từ khóa: <strong>' . esc_html($name) . '</strong></li>';
        }
        if (!empty($area) && isset($area_options[$area])) {
            echo '<li>Khu vực: <strong>' . esc_html($area_options[$area]) . '</strong></li>';
        }
        if ($price !== null && $price !== '' && $price !== '0' && isset($PriceDisplay[$price])) {
            echo '<li>Giá sản phẩm: <strong>' . esc_html($PriceDisplay[$price]) . '</strong></li>';
        }
        if ($type && $type !== '0') {
            $type_term = get_term((int) $type);
            if ($type_term && !is_wp_error($type_term)) {
                echo '<li>Loại dự án: <strong>' . esc_html($type_term->name) . '</strong></li>';
            }
        }
        if ($status && $status !== '0') {
            $status_norm_key = str_replace('-', '_', sanitize_key($status));
            $status_display_options = [
                'dang_mo_ban'   => 'Đang mở bán',
                'dang-mo-ban'   => 'Đang mở bán',
                'sap_mo_ban'    => 'Sắp mở bán',
                'sap-mo-ban'    => 'Sắp mở bán',
                'da_ban_het'    => 'Đã bán hết',
                'da-ban-het'    => 'Đã bán hết',
                'tam_dung'      => 'Tạm dừng',
                'tam-dung'      => 'Tạm dừng',
                'dang_cap_nhat' => 'Đang cập nhật',
                'dang-cap-nhat' => 'Đang cập nhật',
            ];
            $status_label = $status_display_options[$status_norm_key]
                ?? $status_display_options[$status]
                ?? $status;
            echo '<li>Trạng thái: <strong>' . esc_html($status_label) . '</strong></li>';
        }
        echo '</ul>';
        $search_results_html = ob_get_clean();
    }

    // Render pagination HTML
    ob_start();
    if ($total_pages > 1) {
        echo '<nav class="mt-8 flex justify-center" aria-label="Phân trang"><ul class="flex items-center gap-2 list-none p-0 m-0">';
        if ($current_page > 1) {
            echo '<li><a href="#" data-page="' . ($current_page - 1) . '" class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-lg border border-gray-200 text-gray-700 no-underline text-sm font-medium hover:bg-gray-50" aria-label="Trang trước">←</a></li>';
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i === $current_page) {
                echo '<li><span class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-lg bg-[#b20000] text-white text-sm font-medium" aria-current="page">' . $i . '</span></li>';
            } else {
                echo '<li><a href="#" data-page="' . $i . '" class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-lg border border-gray-200 text-gray-700 no-underline text-sm font-medium hover:bg-gray-50">' . $i . '</a></li>';
            }
        }
        if ($current_page < $total_pages) {
            echo '<li><a href="#" data-page="' . ($current_page + 1) . '" class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-lg border border-gray-200 text-gray-700 no-underline text-sm font-medium hover:bg-gray-50" aria-label="Trang sau">→</a></li>';
        }
        echo '</ul></nav>';
    }
    $pagination_html = ob_get_clean();

    // Generate SEO URL
    $base_url = home_url('/du-an/');
    $type_term = ($type && $type !== '0') ? get_term((int) $type, 'loai_hinh_du_an') : null;
    $type_slug = ($type_term && !is_wp_error($type_term)) ? $type_term->slug : null;

    if ($type_slug && $area && !empty($area)) {
        $url = home_url('/du-an-' . $type_slug . '-' . $area . '/');
    } elseif ($type_slug) {
        $url = home_url('/du-an-' . $type_slug . '/');
    } elseif ($area && !empty($area)) {
        $url = home_url('/du-an-' . $area . '/');
    } else {
        $url = $base_url;
    }

    $query_params = [];
    if ($current_page > 1) {
        $query_params['paged'] = $current_page;
    }
    if ($price !== null && $price !== '' && $price !== '0') {
        $query_params['price'] = $price;
    }
    if ($status && $status !== '0') {
        $query_params['status'] = $status;
    }
    if ($name) {
        $query_params['project-name'] = $name;
    }
    if ($orderby && $orderby !== 'date_desc' && $orderby !== '1') {
        $query_params['order'] = $orderby;
    }
    if (!empty($query_params)) {
        $url = add_query_arg($query_params, $url);
    }

    wp_send_json_success(
        [
            'html'          => $html,
            'pagination'    => $pagination_html,
            'found_posts'   => $total,
            'total'         => $total,
            'total_pages'   => $total_pages,
            'current_page'  => $current_page,
            'title'         => $title,
            'searchResults' => $search_results_html,
            'url'           => $url,
            'pretty_url'    => $url,
        ]
    );
}

add_action('wp_ajax_batdongsan_filter_projects', 'batdongsan_ajax_filter_projects');
add_action('wp_ajax_nopriv_batdongsan_filter_projects', 'batdongsan_ajax_filter_projects');

