<?php

/**
 * ACF filters hooks
 * 
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Convert dữ liệu google_map từ array (Google Maps API) sang string (embed code)
 * Xử lý dữ liệu cũ khi field đổi từ google_map sang textarea
 * 
 * @param mixed  $value   Giá trị từ database
 * @param int    $post_id Post ID
 * @param array  $field   Field array
 * @return string Embed code hoặc empty string
 */
function batdongsan_acf_load_value_google_map($value, $post_id, $field) {
    // Nếu đã là string thì return luôn
    if (!is_array($value)) {
        return $value;
    }

    // Nếu là array (dữ liệu cũ từ Google Maps API), convert sang embed code
    if (isset($value['address']) || isset($value['lat']) || isset($value['lng'])) {
        $address = isset($value['address']) ? $value['address'] : '';
        $lat = isset($value['lat']) ? (float) $value['lat'] : 0;
        $lng = isset($value['lng']) ? (float) $value['lng'] : 0;

        // Tạo embed code đơn giản - để trống và yêu cầu người dùng nhập lại
        // Hoặc có thể tạo embed code từ address nếu có
        $embed_code = '';

        if ($address) {
            // Tạo embed URL từ address (không cần API key cho embed)
            $embed_url = 'https://www.google.com/maps/embed/v1/place?q=' . urlencode($address);
            $embed_code = sprintf(
                '<iframe src="%s" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>',
                esc_url($embed_url)
            );
        } else {
            // Nếu không có address, để trống
            $embed_code = '';
        }

        // Lưu lại giá trị mới để không phải convert lại lần sau
        if ($post_id && $embed_code) {
            update_field('google_map', $embed_code, $post_id);
        } elseif ($post_id) {
            // Nếu không có embed code, xóa field để tránh lỗi
            update_field('google_map', '', $post_id);
        }

        return $embed_code;
    }

    // Nếu không phải format mong đợi, return empty string
    if ($post_id) {
        update_field('google_map', '', $post_id);
    }
    return '';
}
add_filter('acf/load_value/name=google_map', 'batdongsan_acf_load_value_google_map', 20, 3);

/**
 * Tự động cập nhật field 'du_an' khi chọn 'phan_khu'
 * Khi người dùng chọn phân khu, hệ thống sẽ tự động lấy dự án cha từ phân khu đó
 * để đảm bảo tính nhất quán của dữ liệu
 * 
 * @param int $post_id Post ID đang được lưu
 */
function batdongsan_sync_du_an_from_phan_khu($post_id) {
    // Bỏ qua nếu đang autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Chỉ áp dụng cho post types bat-dong-san và hang-chuyen-nhuong
    $post_type = get_post_type($post_id);
    if (!in_array($post_type, ['bat-dong-san', 'hang-chuyen-nhuong'])) {
        return;
    }

    // Lấy phân khu đã chọn
    $phan_khu = get_field('phan_khu', $post_id);

    if (!$phan_khu) {
        return;
    }

    // Lấy dự án cha từ phân khu
    $phan_khu_id = is_object($phan_khu) ? $phan_khu->ID : (int) $phan_khu;
    $du_an_cha = get_field('du_an_cha', $phan_khu_id);

    if ($du_an_cha) {
        $du_an_id = is_object($du_an_cha) ? $du_an_cha->ID : (int) $du_an_cha;

        // Cập nhật field du_an_lien_quan nếu chưa được set hoặc khác với dự án cha của phân khu
        $current_du_an_id = function_exists('batdongsan_get_related_project_id')
            ? batdongsan_get_related_project_id($post_id)
            : (int) get_post_meta($post_id, 'du_an_lien_quan', true);

        if ($current_du_an_id !== $du_an_id) {
            update_field('du_an_lien_quan', (string) $du_an_id, $post_id);
            update_post_meta($post_id, 'du_an_lien_quan', $du_an_id);
        }
    }
}
add_action('acf/save_post', 'batdongsan_sync_du_an_from_phan_khu', 20);

/**
 * Đảm bảo giá trị trường du_an_lien_quan luôn được lưu dưới dạng số nguyên đơn trong DB.
 */
function batdongsan_normalize_du_an_lien_quan_on_save($value, $post_id, $field) {
    if (empty($value)) {
        return '';
    }
    if (is_array($value)) {
        $first = reset($value);
        $id = is_object($first) ? absint($first->ID ?? 0) : absint($first);
        return $id > 0 ? (string) $id : '';
    }
    if (is_object($value)) {
        return isset($value->ID) ? (string) absint($value->ID) : '';
    }
    $id = absint($value);
    return $id > 0 ? (string) $id : '';
}
add_filter('acf/update_value/name=du_an_lien_quan', 'batdongsan_normalize_du_an_lien_quan_on_save', 10, 3);


/**
 * Đảm bảo dropdown "Thuộc dự án" (du_an_lien_quan) có danh sách để chọn.
 * Trong admin cho phép hiển thị cả bài publish, draft, private, pending.
 *
 * @param array $args Query args của ACF post_object/relationship
 * @param array $field ACF field
 * @param int   $post_id Post ID
 * @return array
 */
function batdongsan_du_an_lien_quan_query($args, $field, $post_id) {
    if (!isset($field['name']) || $field['name'] !== 'du_an_lien_quan') {
        return $args;
    }
    $args['post_status'] = ['publish'];
    if (is_admin()) {
        $args['post_status'] = ['publish', 'draft', 'private', 'pending'];
    }
    $args['posts_per_page'] = -1;
    $args['orderby'] = 'title';
    $args['order'] = 'ASC';
    return $args;
}
add_filter('acf/fields/post_object/query/name=du_an_lien_quan', 'batdongsan_du_an_lien_quan_query', 10, 3);
add_filter('acf/fields/relationship/query/name=du_an_lien_quan', 'batdongsan_du_an_lien_quan_query', 10, 3);

/**
 * Xóa cache filter transients khi bài viết được lưu, xóa hoặc chuyển trạng thái thùng rác
 */
function batdongsan_clear_filter_transients_on_post_change($post_id) {
    $post_type = get_post_type($post_id);

    if (!$post_type || $post_type === 'du-an') {
        delete_transient('bds_price_ranges');
        delete_transient('bds_projects_with_listings');
    }

    if (!$post_type || in_array($post_type, ['bat-dong-san', 'hang-chuyen-nhuong', 'nha-dat-ban'])) {
        delete_transient('bds_listings_price_ranges_total');
        delete_transient('bds_listings_area_ranges_total');
        delete_transient('bds_area_ranges');
        delete_transient('bds_projects_with_listings');
    }

    // Invalidate dynamic header transients (bds_hdr_bds_* and bds_hdr_duan_*)
    batdongsan_clear_dynamic_header_transients();
}

/**
 * Xóa tất cả các dynamic header transients (bds_hdr_bds_* và bds_hdr_duan_*)
 */
function batdongsan_clear_dynamic_header_transients(): void {
    global $wpdb;
    if ($wpdb && !empty($wpdb->options)) {
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bds_hdr_%' OR option_name LIKE '_transient_timeout_bds_hdr_%' OR option_name LIKE '_transient_bds_ajax_%' OR option_name LIKE '_transient_timeout_bds_ajax_%'");
    }
    if (class_exists('BDS\Tests\Framework\MockWordPress', false)) {
        foreach (array_keys(\BDS\Tests\Framework\MockWordPress::$transients) as $k) {
            if (strpos($k, 'bds_hdr_') === 0 || strpos($k, 'bds_ajax_') === 0) {
                unset(\BDS\Tests\Framework\MockWordPress::$transients[$k]);
            }
        }
    }
    if (function_exists('batdongsan_reset_filter_options_memory_cache')) {
        batdongsan_reset_filter_options_memory_cache();
    }
}
add_action('save_post', 'batdongsan_clear_filter_transients_on_post_change', 20);
add_action('save_post_bat-dong-san', 'batdongsan_clear_filter_transients_on_post_change', 20);
add_action('save_post_du-an', 'batdongsan_clear_filter_transients_on_post_change', 20);
add_action('delete_post', 'batdongsan_clear_filter_transients_on_post_change', 20);
add_action('trashed_post', 'batdongsan_clear_filter_transients_on_post_change', 20);
add_action('untrashed_post', 'batdongsan_clear_filter_transients_on_post_change', 20);

/**
 * Enqueue custom admin JS for ACF
 */
function batdongsan_admin_enqueue_scripts($hook) {
    global $post_type;
    if ($post_type === 'bat-dong-san' || $post_type === 'hang-chuyen-nhuong' || $post_type === 'du-an') {
        $js_file = get_template_directory() . '/assets/js/admin-acf.js';
        $version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';
        wp_enqueue_script(
            'batdongsan-admin-acf',
            get_template_directory_uri() . '/assets/js/admin-acf.js',
            ['jquery'], // Changed dependency to just jquery since we use DOM
            $version,
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'batdongsan_admin_enqueue_scripts');

/**
 * Hiển thị chỉ dẫn kích thước & tỉ lệ ảnh chuẩn cho Ảnh đại diện (Featured Image) trong WordPress Admin
 */
add_filter('admin_post_thumbnail_html', 'batdongsan_admin_featured_image_dimensions_guide', 10, 3);
function batdongsan_admin_featured_image_dimensions_guide($content, $post_id, $thumbnail_id) {
    $post_type = get_post_type($post_id);

    $guides = [
        'du-an' => [
            'size'   => '1200 x 800px (Tỉ lệ 3:2) hoặc 1600 x 900px (16:9)',
            'min'    => 'Tối thiểu 800 x 533px',
            'desc'   => 'Ảnh đại diện dự án hiển thị trên card danh sách, hero banner và chia sẻ mạng xã hội.',
            'weight' => '< 350KB (WebP, JPG)'
        ],
        'bat-dong-san' => [
            'size'   => '1200 x 800px (Tỉ lệ 3:2) hoặc 1024 x 683px',
            'min'    => 'Tối thiểu 800 x 533px',
            'desc'   => 'Ảnh đại diện chính hiển thị trên danh sách tin đăng, thẻ bất động sản và thẻ xem nhanh.',
            'weight' => '< 300KB (WebP, JPG)'
        ],
        'hang-chuyen-nhuong' => [
            'size'   => '1200 x 800px (Tỉ lệ 3:2) hoặc 1024 x 683px',
            'min'    => 'Tối thiểu 800 x 533px',
            'desc'   => 'Ảnh đại diện hàng chuyển nhượng hiển thị trên danh sách thẻ và chi tiết.',
            'weight' => '< 300KB (WebP, JPG)'
        ],
        'nha-dat-cho-thue' => [
            'size'   => '1200 x 800px (Tỉ lệ 3:2) hoặc 1024 x 683px',
            'min'    => 'Tối thiểu 800 x 533px',
            'desc'   => 'Ảnh đại diện tin cho thuê hiển thị trên danh sách và chi tiết.',
            'weight' => '< 300KB (WebP, JPG)'
        ],
        'nha-dat-ban' => [
            'size'   => '1200 x 800px (Tỉ lệ 3:2) hoặc 1024 x 683px',
            'min'    => 'Tối thiểu 800 x 533px',
            'desc'   => 'Ảnh đại diện nhà đất bán hiển thị trên danh sách và chi tiết.',
            'weight' => '< 300KB (WebP, JPG)'
        ],
        'banner' => [
            'size'   => '1920 x 720px hoặc 1920 x 600px (Tỉ lệ ~21:9 hoặc 16:9 ngang)',
            'min'    => 'Tối thiểu 1440 x 540px',
            'desc'   => 'Banner hiển thị toàn màn hình trên slider trang chủ và đầu chuyên mục.',
            'weight' => '< 400KB (WebP, JPG)'
        ],
        'doi-tac' => [
            'size'   => '400 x 200px (Tỉ lệ 2:1 ngang)',
            'min'    => 'Tối thiểu 200 x 100px',
            'desc'   => 'Logo đối tác / chủ đầu tư. Ưu tiên PNG nền trong suốt hoặc SVG.',
            'weight' => '< 150KB (PNG, SVG, WebP)'
        ],
        'post' => [
            'size'   => '1200 x 675px (Tỉ lệ 16:9) hoặc 1200 x 800px (3:2)',
            'min'    => 'Tối thiểu 800 x 450px',
            'desc'   => 'Ảnh đại diện bài viết tin tức, chuẩn hiển thị thumbnail Facebook/Zalo.',
            'weight' => '< 250KB (WebP, JPG)'
        ],
    ];

    $guide = $guides[$post_type] ?? [
        'size'   => '1200 x 800px (Tỉ lệ 3:2) hoặc 1200 x 675px (16:9)',
        'min'    => 'Tối thiểu 800 x 533px',
        'desc'   => 'Ảnh đại diện chính của bài viết.',
        'weight' => '< 300KB (WebP, JPG)'
    ];

    $box = '<div class="bds-admin-img-guide" style="margin-top: 10px; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #C93347; border-radius: 6px; font-size: 11px; line-height: 1.5; color: #475569;">'
         . '<div style="font-weight: 700; color: #0f172a; margin-bottom: 4px; display: flex; align-items: center; gap: 4px;">'
         . '<span style="font-size: 13px;">📐</span> <span>Kích thước & Tỉ lệ chuẩn</span>'
         . '</div>'
         . '<div><strong>• Kích thước:</strong> <span style="color: #C93347; font-weight: 600;">' . esc_html($guide['size']) . '</span></div>'
         . '<div><strong>• ' . esc_html($guide['min']) . '</strong> | Dung lượng: ' . esc_html($guide['weight']) . '</div>'
         . '<div style="margin-top: 4px; color: #64748b; font-style: italic;">' . esc_html($guide['desc']) . '</div>'
         . '</div>';

    return $content . $box;
}

/**
 * Tự động gắn bảng chỉ dẫn kích thước & tỉ lệ ảnh cho tất cả trường ACF Image và Gallery trong quản trị
 */
add_filter('acf/prepare_field', 'batdongsan_acf_image_fields_dimension_guide');
function batdongsan_acf_image_fields_dimension_guide($field) {
    if (!is_admin() || !isset($field['type']) || !in_array($field['type'], ['image', 'gallery'], true)) {
        return $field;
    }

    $name = $field['name'] ?? '';

    // Bản đồ kích thước tỉ lệ cụ thể theo từng trường
    $dimension_map = [
        'gallery_du_an' => [
            'size'  => '1200 x 800px (Tỉ lệ 3:2) hoặc 1600 x 900px (16:9)',
            'desc'  => 'Ảnh dự án chất lượng cao. Ảnh đầu tiên làm ảnh đại diện phụ nếu chưa đặt ảnh chính. Dung lượng < 350KB/ảnh.'
        ],
        'thu_vien_anh' => [
            'size'  => '1200 x 800px (Tỉ lệ 3:2) hoặc 1200 x 675px (16:9)',
            'desc'  => 'Sơ đồ vị trí và liên kết giao thông của dự án. Dung lượng < 300KB.'
        ],
        'mat_bang_gallery' => [
            'size'  => '1600 x 1060px (Tỉ lệ 3:2) hoặc 1600 x 1200px (4:3)',
            'desc'  => 'Mặt bằng tổng thể và bản vẽ phân khu/căn hộ độ phân giải cao để xem rõ nét khi phóng to. Dung lượng < 400KB/ảnh.'
        ],
        'gallery_nha_dat' => [
            'size'  => '1200 x 800px (Tỉ lệ 3:2) hoặc 1200 x 900px (4:3)',
            'desc'  => 'Hình ảnh thực tế bất động sản góc rộng. Ảnh đầu tiên dùng làm ảnh chính tin đăng. Dung lượng < 300KB/ảnh.'
        ],
        'banner_image' => [
            'size'  => '1920 x 720px hoặc 1920 x 600px (Tỉ lệ ~21:9 hoặc 16:9 ngang)',
            'desc'  => 'Banner tràn màn hình slider trang chủ. Dung lượng < 400KB.'
        ],
        'logo' => [
            'size'  => '400 x 200px (Tỉ lệ 2:1 ngang, tối thiểu 200 x 100px)',
            'desc'  => 'Logo đối tác / chủ đầu tư. Khuyến nghị PNG nền trong suốt hoặc SVG.'
        ],
        'avatar' => [
            'size'  => '500 x 500px hoặc 400 x 400px (Tỉ lệ 1:1 vuông)',
            'desc'  => 'Ảnh chân dung chuyên viên tư vấn chính diện, rõ nét, nền sáng. Dung lượng < 150KB.'
        ]
    ];

    $guide = $dimension_map[$name] ?? null;

    if (!$guide) {
        if ($field['type'] === 'gallery') {
            $guide = [
                'size' => '1200 x 800px (Tỉ lệ 3:2) hoặc 1600 x 900px (16:9)',
                'desc' => 'Tải nhiều ảnh chất lượng cao, đồng nhất tỉ lệ để hiển thị đẹp mắt. Dung lượng < 350KB/ảnh.'
            ];
        } else {
            $guide = [
                'size' => '1200 x 800px (Tỉ lệ 3:2) hoặc 1200 x 675px (16:9)',
                'desc' => 'Tải ảnh rõ nét, đúng tỉ lệ khung hình. Dung lượng tối ưu < 300KB.'
            ];
        }
    }

    $guide_html = '<span class="bds-acf-field-guide" style="display: block; margin-top: 6px; padding: 6px 10px; background: #f8fafc; border-left: 3px solid #C93347; border-radius: 4px; font-size: 11px; line-height: 1.5; color: #334155;">'
                . '📐 <strong>Kích thước chuẩn:</strong> <span style="color: #C93347; font-weight: 600;">' . esc_html($guide['size']) . '</span> — '
                . '<em>' . esc_html($guide['desc']) . '</em>'
                . '</span>';

    $current_instructions = $field['instructions'] ?? '';
    if (strpos($current_instructions, 'bds-acf-field-guide') === false) {
        $field['instructions'] = $current_instructions
            ? $current_instructions . '<br>' . $guide_html
            : $guide_html;
    }

    return $field;
}
