<?php
/**
 * Image helpers
 * 
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tối ưu hình ảnh: Thêm responsive images và lazy loading
 * 
 * @param int|string $image_id ID của attachment
 * @param string     $size     Kích thước ảnh
 * @param array      $attr     Các thuộc tính HTML
 * @param bool       $lazy_load Có lazy load hay không (mặc định true)
 * @return string HTML của hình ảnh
 */
function batdongsan_get_optimized_image($image_id, string $size = 'large', array $attr = [], bool $lazy_load = true): string {
    if (empty($image_id)) {
        return '';
    }

    $image_id = (int) $image_id;
    $default_attr = [
        'loading' => $lazy_load ? 'lazy' : 'eager',
        'decoding' => 'async',
    ];

    // Chỉ thêm fetchpriority và bypass lazy-load cho ảnh LCP (không lazy)
    if (!$lazy_load) {
        $default_attr['fetchpriority'] = 'high';
        $default_attr['data-no-lazy'] = '1';
        if (isset($attr['class'])) {
            $attr['class'] = trim($attr['class'] . ' no-lazy skip-lazy');
        } else {
            $default_attr['class'] = 'no-lazy skip-lazy';
        }
    }

    $attr = array_merge($default_attr, $attr);

    // Tự động bổ sung Alt text chuẩn SEO nếu chưa có hoặc rỗng
    if (!isset($attr['alt']) || trim((string) $attr['alt']) === '') {
        $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        if (empty($alt)) {
            $attachment = get_post($image_id);
            if ($attachment && !empty($attachment->post_title)) {
                $alt = sanitize_text_field($attachment->post_title);
            }
        }
        if (empty($alt) && function_exists('get_the_title') && get_the_ID()) {
            $alt = sanitize_text_field(get_the_title());
        }
        if (empty($alt)) {
            $alt = function_exists('get_bloginfo') ? get_bloginfo('name') : 'Bất động sản Hà Nội';
        }
        $attr['alt'] = $alt;
    }

    $img_html = wp_get_attachment_image($image_id, $size, false, $attr);
    if (empty($img_html)) {
        return '';
    }

    // Check if webp equivalent exists to serve next-gen image
    $img_src = wp_get_attachment_image_url($image_id, $size);
    if ($img_src && !preg_match('/\.webp$/i', $img_src)) {
        $upload_dir = wp_get_upload_dir();
        $relative_path = str_replace($upload_dir['baseurl'], '', $img_src);
        $file_path = $upload_dir['basedir'] . $relative_path;
        $webp_file = preg_replace('/\.(png|jpe?g)$/i', '.webp', $file_path);
        if (file_exists($webp_file)) {
            $webp_url = preg_replace('/\.(png|jpe?g)$/i', '.webp', $img_src);
            return '<picture class="contents"><source type="image/webp" srcset="' . esc_url($webp_url) . '">' . $img_html . '</picture>';
        }
    }

    return $img_html;
}

/**
 * Chuyển đổi gallery ACF từ array sang mảng ID ảnh
 * 
 * @param mixed $gallery_field Giá trị từ ACF gallery field
 * @return array Mảng các image ID
 */
function batdongsan_parse_gallery_field($gallery_field): array {
    $image_ids = [];
    
    if (!$gallery_field || !is_array($gallery_field) || empty($gallery_field)) {
        return $image_ids;
    }
    
    foreach ($gallery_field as $image) {
        // Gallery ACF return_format="array" trả về array với key 'ID' hoặc có thể là ID trực tiếp
        $image_id = is_array($image) && isset($image['ID']) 
            ? (int) $image['ID'] 
            : (is_numeric($image) ? (int) $image : 0);
            
        if ($image_id > 0) {
            $image_ids[] = $image_id;
        }
    }
    
    return $image_ids;
}

/**
 * Lấy gallery image IDs với fallback về featured image
 * 
 * @param mixed  $gallery_field Giá trị từ ACF gallery field
 * @param int    $post_id       Post ID để fallback
 * @return array Mảng các image ID
 */
function batdongsan_get_gallery_image_ids($gallery_field, $post_id = 0): array {
    $image_ids = batdongsan_parse_gallery_field($gallery_field);
    
    // Fallback về featured image nếu không có gallery
    if (empty($image_ids) && $post_id && has_post_thumbnail($post_id)) {
        $image_ids[] = get_post_thumbnail_id($post_id);
    }
    
    return $image_ids;
}

/**
 * Global fallback for attachment image alt text to guarantee zero missing alt tags.
 *
 * @param array $attr Image attributes
 * @param WP_Post $attachment Attachment post object
 * @return array
 */
function batdongsan_filter_attachment_image_alt_fallback(array $attr, $attachment): array {
    if (empty($attr['alt']) || trim((string) $attr['alt']) === '') {
        $alt = '';
        if ($attachment instanceof WP_Post && !empty($attachment->post_title)) {
            $alt = sanitize_text_field($attachment->post_title);
        }
        if (empty($alt) && function_exists('get_the_title') && get_the_ID()) {
            $alt = sanitize_text_field(get_the_title());
        }
        if (empty($alt)) {
            $alt = function_exists('get_bloginfo') ? get_bloginfo('name') : 'Bất động sản Hà Nội';
        }
        $attr['alt'] = $alt;
    }
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'batdongsan_filter_attachment_image_alt_fallback', 10, 2);


