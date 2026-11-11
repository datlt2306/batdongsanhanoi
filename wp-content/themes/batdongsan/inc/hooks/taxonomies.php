<?php
/**
 * Taxonomy hooks
 * 
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bật show_in_nav_menus cho các taxonomy để có thể thêm vào menu items.
 * Cho phép thêm danh mục nhà đất bán, nhà đất cho thuê, và danh mục dự án vào menu.
 * 
 * @param array  $args     Taxonomy arguments
 * @param string $taxonomy Taxonomy name
 * @return array Modified arguments
 */
function batdongsan_enable_taxonomies_in_nav_menus(array $args, string $taxonomy): array {
    // Danh sách các taxonomy cần bật show_in_nav_menus
    $taxonomies_to_enable = [
        'khu-vuc',                    // Khu vực / Quận huyện
        'loai_hinh_du_an',            // Loại hình dự án
        'loai_hinh_bat_dong_san',     // Loại hình BĐS
        'hinh-thuc-bds',              // Hình thức (Sơ cấp / Chuyển nhượng / Cho thuê / Mua bán...)
    ];

    if (in_array($taxonomy, $taxonomies_to_enable, true)) {
        $args['show_in_nav_menus'] = true;
    }

    if ($taxonomy === 'hinh-thuc-bds' || $taxonomy === 'khu-vuc') {
        $args['hierarchical'] = true;
    }

    return $args;
}
add_filter('register_taxonomy_args', 'batdongsan_enable_taxonomies_in_nav_menus', 10, 2);

/**
 * Đảm bảo các taxonomy đã đăng ký cũng được cập nhật show_in_nav_menus.
 * Chạy sau khi ACF đã đăng ký taxonomies (priority 20, sau acf/init với priority 6).
 */
function batdongsan_update_taxonomies_show_in_nav_menus(): void {
    $taxonomies_to_enable = [
        'khu-vuc',
        'loai_hinh_du_an',
        'loai_hinh_bat_dong_san',
        'hinh-thuc-bds',
    ];

    global $wp_taxonomies;

    foreach ($taxonomies_to_enable as $taxonomy_name) {
        if (isset($wp_taxonomies[$taxonomy_name])) {
            $wp_taxonomies[$taxonomy_name]->show_in_nav_menus = true;
        }
    }
}
add_action('acf/init', 'batdongsan_update_taxonomies_show_in_nav_menus', 20);

/**
 * Đăng ký Taxonomy Khu vực (Quận / Huyện) cho Bất động sản và Dự án.
 * Cho phép quản lý trực tiếp trong WP Admin (phân cấp Tỉnh/Thành -> Quận/Huyện).
 */
function batdongsan_register_khu_vuc_taxonomy(): void {
    if (taxonomy_exists('khu-vuc')) {
        return;
    }

    $labels = [
        'name'                       => 'Khu vực',
        'singular_name'              => 'Khu vực',
        'menu_name'                  => 'Khu vực',
        'all_items'                  => 'Tất cả khu vực',
        'parent_item'                => 'Khu vực cha (Tỉnh / TP)',
        'parent_item_colon'          => 'Khu vực cha:',
        'new_item_name'              => 'Tên khu vực mới',
        'add_new_item'               => 'Thêm khu vực mới',
        'edit_item'                  => 'Sửa khu vực',
        'update_item'                => 'Cập nhật khu vực',
        'view_item'                  => 'Xem khu vực',
        'separate_items_with_commas' => 'Phân cách bằng dấu phẩy',
        'add_or_remove_items'        => 'Thêm hoặc xóa khu vực',
        'choose_from_most_used'      => 'Chọn từ khu vực hay dùng',
        'popular_items'              => 'Khu vực phổ biến',
        'search_items'               => 'Tìm kiếm khu vực',
        'not_found'                  => 'Không tìm thấy khu vực nào',
        'no_terms'                   => 'Chưa có khu vực nào',
        'items_list'                 => 'Danh sách khu vực',
        'items_list_navigation'      => 'Điều hướng danh sách khu vực',
    ];

    $args = [
        'labels'            => $labels,
        'hierarchical'      => true, // Phân cấp Tỉnh/Thành phố -> Quận/Huyện
        'public'            => true,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_nav_menus' => true,
        'show_admin_column' => true,
        'show_tagcloud'     => false,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => [
            'slug'         => 'khu-vuc',
            'with_front'   => false,
            'hierarchical' => true,
        ],
    ];

    $object_types = [
        'bat-dong-san',
        'du-an',
        'hang-chuyen-nhuong',
        'nha-dat-ban',
        'bds-cho-thue',
    ];

    register_taxonomy('khu-vuc', $object_types, $args);
}
add_action('init', 'batdongsan_register_khu_vuc_taxonomy', 5);

/**
 * Gắn taxonomy vào post type ở runtime để tránh phụ thuộc việc "Sync" ACF JSON.
 * Mục tiêu: "Loại hình" dùng cho sản phẩm (hàng chuyển nhượng / nhà đất cho thuê),
 * còn CPT "Dự án" không còn phụ thuộc vào taxonomy này.
 */
function batdongsan_register_taxonomy_object_types_runtime(): void {
    // Gắn taxonomy Khu vực cho các post type BĐS và Dự án
    if (taxonomy_exists('khu-vuc')) {
        register_taxonomy_for_object_type('khu-vuc', 'bat-dong-san');
        register_taxonomy_for_object_type('khu-vuc', 'du-an');
        register_taxonomy_for_object_type('khu-vuc', 'hang-chuyen-nhuong');
        register_taxonomy_for_object_type('khu-vuc', 'nha-dat-ban');
        register_taxonomy_for_object_type('khu-vuc', 'bds-cho-thue');
    }

    // Gắn taxonomy Loại hình BĐS cho các post type bất động sản
    if (taxonomy_exists('loai_hinh_bat_dong_san')) {
        register_taxonomy_for_object_type('loai_hinh_bat_dong_san', 'bat-dong-san');
        register_taxonomy_for_object_type('loai_hinh_bat_dong_san', 'hang-chuyen-nhuong');
        register_taxonomy_for_object_type('loai_hinh_bat_dong_san', 'nha-dat-ban');
    }

    // Giữ lại taxonomy hình thức BĐS cho post type Bất động sản nếu có
    if (taxonomy_exists('hinh-thuc-bds')) {
        register_taxonomy_for_object_type('hinh-thuc-bds', 'bat-dong-san');
    }
}
add_action('init', 'batdongsan_register_taxonomy_object_types_runtime', 30);

/**
 * Ẩn meta box mặc định của taxonomy Loại hình BĐS và Loại hình dự án.
 * Dùng field ACF type taxonomy (select) thay thế.
 */
function batdongsan_remove_loai_hinh_default_meta_boxes(): void {
    remove_meta_box('tagsdiv-loai_hinh_bat_dong_san', 'bat-dong-san', 'side');
    remove_meta_box('tagsdiv-loai_hinh_du_an', 'du-an', 'side');
}
add_action('add_meta_boxes', 'batdongsan_remove_loai_hinh_default_meta_boxes', 20);

/**
 * Đảm bảo các terms chuẩn của taxonomy hinh-thuc-bds luôn tồn tại.
 * Cấu trúc kiến trúc Bất động sản:
 * - Chuyển nhượng (chuyen-nhuong)
 * - Cho thuê (cho-thue)
 * - Mua / Bán (mua-ban)
 *   ├── Sơ cấp (so-cap)
 *   ├── Trung cấp (trung-cap)
 *   └── Cao cấp (cao-cap)
 */
function batdongsan_ensure_hinh_thuc_bds_terms(): void {
    if (!taxonomy_exists('hinh-thuc-bds')) {
        return;
    }

    // Đảm bảo term cha mua-ban tồn tại
    $parent_id = 0;
    if (!term_exists('mua-ban', 'hinh-thuc-bds')) {
        $mb = wp_insert_term('Mua / Bán', 'hinh-thuc-bds', ['slug' => 'mua-ban']);
        if (!is_wp_error($mb)) {
            $parent_id = (int) $mb['term_id'];
        }
    } else {
        $t = get_term_by('slug', 'mua-ban', 'hinh-thuc-bds');
        $parent_id = $t ? (int) $t->term_id : 0;
    }

    $terms = [
        'chuyen-nhuong' => ['name' => 'Chuyển nhượng', 'parent' => 0],
        'cho-thue'      => ['name' => 'Cho thuê', 'parent' => 0],
        'so-cap'        => ['name' => 'Sơ cấp', 'parent' => $parent_id],
        'trung-cap'     => ['name' => 'Trung cấp', 'parent' => $parent_id],
        'cao-cap'       => ['name' => 'Cao cấp', 'parent' => $parent_id],
    ];

    foreach ($terms as $slug => $data) {
        if (!term_exists($slug, 'hinh-thuc-bds')) {
            wp_insert_term($data['name'], 'hinh-thuc-bds', [
                'slug'   => $slug,
                'parent' => $data['parent'],
            ]);
        }
    }
}
add_action('init', 'batdongsan_ensure_hinh_thuc_bds_terms', 35);

/**
 * Đảm bảo các quận/huyện Hà Nội luôn tồn tại trong taxonomy khu-vuc
 * và tự động gán khu vực cho các bài viết BĐS/Dự án hiện có nếu chưa được gán.
 */
function batdongsan_ensure_khu_vuc_terms(): void {
    if (!taxonomy_exists('khu-vuc')) {
        return;
    }

    // Danh sách 30 quận/huyện Hà Nội chuẩn
    $districts = [
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

    if (!get_option('bds_khu_vuc_seeded_v1')) {
        // Tạo term cha "Hà Nội"
        $parent_id = 0;
        $hanoi_term = get_term_by('slug', 'ha-noi', 'khu-vuc');
        if (!$hanoi_term) {
            $inserted = wp_insert_term('Hà Nội', 'khu-vuc', ['slug' => 'ha-noi']);
            if (!is_wp_error($inserted)) {
                $parent_id = (int) $inserted['term_id'];
            }
        } else {
            $parent_id = (int) $hanoi_term->term_id;
        }

        foreach ($districts as $slug => $name) {
            if (!term_exists($slug, 'khu-vuc')) {
                wp_insert_term($name, 'khu-vuc', [
                    'slug'   => $slug,
                    'parent' => $parent_id,
                ]);
            }
        }

        // Tự động gán taxonomy khu-vuc cho các bài viết BĐS & Dự án hiện có theo địa chỉ
        $legacy_posts = get_posts([
            'post_type'      => ['bat-dong-san', 'du-an', 'hang-chuyen-nhuong', 'nha-dat-ban', 'bds-cho-thue'],
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'fields'         => 'ids',
        ]);

        if (!empty($legacy_posts)) {
            foreach ($legacy_posts as $pid) {
                $existing = wp_get_post_terms($pid, 'khu-vuc', ['fields' => 'ids']);
                if (!empty($existing) && !is_wp_error($existing)) {
                    continue;
                }
                $addr = get_post_meta($pid, 'dia_chi', true);
                if (empty($addr)) {
                    $addr = get_post_meta($pid, 'address', true);
                }
                $title = get_the_title($pid);
                $search_text = $addr . ' ' . $title;

                foreach ($districts as $slug => $name) {
                    if (mb_stripos($search_text, $name) !== false) {
                        $term_obj = get_term_by('slug', $slug, 'khu-vuc');
                        if ($term_obj && !is_wp_error($term_obj)) {
                            wp_set_object_terms($pid, (int) $term_obj->term_id, 'khu-vuc', false);
                            break;
                        }
                    }
                }
            }
        }

        update_option('bds_khu_vuc_seeded_v1', 1);
    }
}
add_action('init', 'batdongsan_ensure_khu_vuc_terms', 36);

/**
 * Tự động đồng bộ taxonomy khu-vuc khi lưu bài viết nếu người dùng nhập địa chỉ có tên quận
 */
function batdongsan_sync_post_khu_vuc_on_save(int $post_id, WP_Post $post, bool $update): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    $allowed_post_types = ['bat-dong-san', 'du-an', 'hang-chuyen-nhuong', 'nha-dat-ban', 'bds-cho-thue'];
    if (!in_array($post->post_type, $allowed_post_types, true)) {
        return;
    }

    if (!taxonomy_exists('khu-vuc')) {
        return;
    }

    // Trường hợp 1: Nếu bài viết chưa có term khu-vuc nào được chọn -> tự động nhận diện từ địa chỉ
    $existing_terms = wp_get_post_terms($post_id, 'khu-vuc', ['fields' => 'ids']);
    if (empty($existing_terms) || is_wp_error($existing_terms)) {
        $meta_address = get_post_meta($post_id, 'dia_chi', true);
        if (empty($meta_address)) {
            $meta_address = get_post_meta($post_id, 'address', true);
        }
        $search_text = $meta_address . ' ' . $post->post_title;

        $terms = get_terms([
            'taxonomy'   => 'khu-vuc',
            'hide_empty' => false,
        ]);

        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $t) {
                if ($t->slug === 'ha-noi') {
                    continue;
                }
                if (mb_stripos($search_text, $t->name) !== false) {
                    wp_set_object_terms($post_id, (int) $t->term_id, 'khu-vuc', false);
                    break;
                }
            }
        }
    } else {
        // Trường hợp 2: Đã có term khu-vuc nhưng chưa có địa chỉ text -> tự động điền tên khu vực
        $addr_key = ($post->post_type === 'du-an') ? 'address' : 'dia_chi';
        $curr_addr = get_post_meta($post_id, $addr_key, true);
        if (empty($curr_addr)) {
            $first_term_id = reset($existing_terms);
            $term_obj = get_term($first_term_id, 'khu-vuc');
            if ($term_obj && !is_wp_error($term_obj) && $term_obj->slug !== 'ha-noi') {
                update_post_meta($post_id, $addr_key, $term_obj->name . ', Hà Nội');
            }
        }
    }
}
add_action('save_post', 'batdongsan_sync_post_khu_vuc_on_save', 20, 3);

