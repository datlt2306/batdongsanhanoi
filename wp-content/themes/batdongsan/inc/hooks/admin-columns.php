<?php
/**
 * Admin columns customization
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Thêm cột "Dự án cha" vào danh sách bài viết Dự án (du-an)
 */
function batdongsan_set_du_an_columns($columns) {
    $new_columns = [];
    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
        if ($key == 'title') {
            $new_columns['du_an_cha'] = 'Dự án cha';
        }
    }
    return $new_columns;
}
add_filter('manage_du-an_posts_columns', 'batdongsan_set_du_an_columns');

/**
 * Hiển thị dữ liệu cho cột "Dự án cha"
 */
function batdongsan_display_du_an_custom_column($column, $post_id) {
    if ($column === 'du_an_cha') {
        $du_an_cha = get_field('du_an_cha', $post_id);
        if ($du_an_cha) {
            $parent_obj = is_object($du_an_cha) ? $du_an_cha : get_post($du_an_cha);
            if ($parent_obj) {
                echo '<a href="' . get_edit_post_link($parent_obj->ID) . '"><strong>' . esc_html($parent_obj->post_title) . '</strong></a>';
                echo '<div class="row-actions"><span class="view"><a href="' . get_permalink($parent_obj->ID) . '" target="_blank">Xem ngoài web</a></span></div>';
            }
        } else {
            echo '<span class="na" style="color:#ccc;">—</span>';
        }
    }
}
add_action('manage_du-an_posts_custom_column', 'batdongsan_display_du_an_custom_column', 10, 2);

/**
 * Làm cho cột Dự án cha có thể sắp xếp
 */
function batdongsan_make_du_an_columns_sortable($columns) {
    $columns['du_an_cha'] = 'du_an_cha';
    return $columns;
}
add_filter('manage_edit-du-an_sortable_columns', 'batdongsan_make_du_an_columns_sortable');

/**
 * Thêm cột "Dự án / Phân khu" vào danh sách Bất động sản
 */
function batdongsan_set_bds_columns($columns) {
    $new_columns = [];
    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
        if ($key == 'title') {
            $new_columns['thuoc_du_an'] = 'Thuộc dự án';
        }
    }
    return $new_columns;
}
add_filter('manage_bat-dong-san_posts_columns', 'batdongsan_set_bds_columns');
add_filter('manage_hang-chuyen-nhuong_posts_columns', 'batdongsan_set_bds_columns');

/**
 * Hiển thị dữ liệu cho cột "Thuộc dự án" cho BĐS
 */
function batdongsan_display_bds_custom_column($column, $post_id) {
    if ($column === 'thuoc_du_an') {
        $du_an = get_field('du_an_lien_quan', $post_id);
        $phan_khu = get_the_terms($post_id, 'phan-khu');
        
        $output = [];
        
        if ($du_an) {
            $parent_obj = is_object($du_an) ? $du_an : get_post($du_an);
            if ($parent_obj) {
                $output[] = '<a href="' . get_edit_post_link($parent_obj->ID) . '"><strong>' . esc_html($parent_obj->post_title) . '</strong></a>';
            }
        }
        
        if ($phan_khu && !is_wp_error($phan_khu)) {
            $term_names = wp_list_pluck($phan_khu, 'name');
            $output[] = '<span style="color: #666; font-size: 11px;">Khu: ' . implode(', ', $term_names) . '</span>';
        }
        
        if (!empty($output)) {
            echo implode('<br>', $output);
        } else {
            echo '<span class="na" style="color:#ccc;">—</span>';
        }
    }
}
add_action('manage_bat-dong-san_posts_custom_column', 'batdongsan_display_bds_custom_column', 10, 2);
add_action('manage_hang-chuyen-nhuong_posts_custom_column', 'batdongsan_display_bds_custom_column', 10, 2);
