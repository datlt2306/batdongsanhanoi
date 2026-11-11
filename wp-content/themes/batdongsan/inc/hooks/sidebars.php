<?php

/**
 * Đăng ký sidebar widget areas cho BĐS và Dự án
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Đăng ký sidebars
 */
add_action('widgets_init', 'batdongsan_register_sidebars');

function batdongsan_register_sidebars(): void {
    register_sidebar([
        'name'          => __('Sidebar Chi tiết BĐS', 'batdongsan'),
        'id'            => 'sidebar-single-bat-dong-san',
        'description'   => __('Hiển thị trên trang chi tiết bất động sản (single-bat-dong-san)', 'batdongsan'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title text-base font-bold text-gray-900 m-0 mb-4">',
        'after_title'   => '</h3>',
    ]);

    register_sidebar([
        'name'          => __('Sidebar Danh sách BĐS', 'batdongsan'),
        'id'            => 'sidebar-page-bat-dong-san',
        'description'   => __('Hiển thị trên trang danh sách bất động sản (page-bat-dong-san)', 'batdongsan'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title text-base font-bold text-gray-900 m-0 mb-4 pb-3 border-b border-gray-200">',
        'after_title'   => '</h3>',
    ]);

    register_sidebar([
        'name'          => __('Sidebar Danh sách Dự án', 'batdongsan'),
        'id'            => 'sidebar-page-du-an',
        'description'   => __('Hiển thị trên trang danh sách dự án (page-du-an)', 'batdongsan'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title text-base font-bold text-gray-900 m-0 mb-4 pb-3 border-b-2 border-gray-200">',
        'after_title'   => '</h3>',
    ]);
}

/**
 * Đăng ký custom widgets
 */
add_action('widgets_init', 'batdongsan_register_widgets');

function batdongsan_register_widgets(): void {
    require_once get_template_directory() . '/inc/widgets/class-bds-widgets.php';

    register_widget('BDS_Widget_Advisor_Card');
    register_widget('BDS_Widget_Promo_Gif');
    register_widget('BDS_Widget_Related_Projects');
    register_widget('BDS_Widget_Featured_Listings');
    register_widget('BDS_Widget_Posts_Of_Interest');
    register_widget('BDS_Widget_Project_News');
    register_widget('BDS_Widget_Contact');
    register_widget('BDS_Widget_Price_Filter');
    register_widget('BDS_Widget_Area_Filter');
}
