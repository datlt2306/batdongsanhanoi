<?php
/**
 * CPT Loại hình dự án - dùng cho filter Dự án (tách khỏi taxonomy loai-hinh của BĐS)
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'batdongsan_register_cpt_loai_hinh_du_an', 20);
function batdongsan_register_cpt_loai_hinh_du_an(): void {
    register_post_type('loai-hinh-du-an', [
        'labels' => [
            'name'               => 'Loại hình dự án',
            'singular_name'      => 'Loại hình dự án',
            'menu_name'          => 'Loại hình dự án',
            'add_new'            => 'Thêm mới',
            'add_new_item'       => 'Thêm loại hình',
            'edit_item'          => 'Sửa loại hình',
            'new_item'           => 'Loại hình mới',
            'view_item'          => 'Xem loại hình',
            'search_items'       => 'Tìm loại hình',
            'not_found'          => 'Không tìm thấy',
            'not_found_in_trash' => 'Không có trong thùng rác',
        ],
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=du-an',
        'capability_type'    => 'post',
        'hierarchical'       => false,
        'supports'           => ['title'],
        'has_archive'        => false,
        'rewrite'            => false,
        'query_var'          => false,
    ]);
}
