<?php
/**
 * SEO & AI Bot Optimization Engine
 *
 * Implements Google-compliant and AI Bot-ready metadata:
 * - Dynamic SEO Titles & Meta Descriptions (Google CTR & Search Intent, 50-65 chars title, 140-155 chars description)
 * - Self-referencing Canonical URLs for standard & pretty-filter endpoints
 * - Generative AI Crawler controls (robots.txt for OAI-SearchBot, GPTBot, PerplexityBot, Claude, Gemini)
 * - Geo / Local SEO Metadata (Hà Nội, Vietnam) for ChatGPT Search & Gemini Maps
 * - Rank Math seamless filter integration + Native WordPress fallback
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Format dynamic SEO title for Du An (Project) archives and pretty URLs.
 *
 * @param array $params Optional parameter overrides
 * @return string|null Formatted SEO title or null if default
 */
function batdongsan_get_du_an_filter_seo_title(array $params = []): ?string {
    $get_qv = function (string $var, $default = '') {
        return function_exists('get_query_var') ? get_query_var($var, $default) : $default;
    };
    $sanitize = function ($val) {
        if (!is_string($val)) {
            return (string) $val;
        }
        if (function_exists('sanitize_text_field')) {
            return function_exists('wp_unslash') ? sanitize_text_field(wp_unslash($val)) : sanitize_text_field($val);
        }
        return trim(strip_tags($val));
    };

    $type_raw     = $params['type'] ?? $get_qv('du_an_type_slug');
    $khu_vuc_raw  = $params['khu_vuc'] ?? $get_qv('du_an_khu_vuc');
    $price_raw    = $params['price'] ?? $get_qv('du_an_price_slug');
    $slug_1       = $get_qv('du_an_slug_1');
    $status       = $params['status'] ?? ($_GET['status'] ?? '');
    $project_name = $params['project-name'] ?? ($_GET['project-name'] ?? '');

    if (empty($khu_vuc_raw) && isset($_GET['khu_vuc'])) {
        $khu_vuc_raw = $sanitize($_GET['khu_vuc']);
    }
    if (empty($khu_vuc_raw) && isset($_GET['area'])) {
        $khu_vuc_raw = $sanitize($_GET['area']);
    }
    if (empty($type_raw) && isset($_GET['type'])) {
        $type_raw = $sanitize($_GET['type']);
    }
    if (empty($price_raw) && isset($_GET['price'])) {
        $price_raw = $sanitize($_GET['price']);
    }

    // Handle 1-segment URL (/du-an-bat-dong-san-{slug_1})
    $districts_whitelist = [
        'gia-lam', 'nam-tu-liem', 'hai-ba-trung', 'tay-ho', 'long-bien',
        'cau-giay', 'dong-da', 'hoang-mai', 'thanh-xuan', 'ha-dong',
        'ba-dinh', 'hoan-kiem', 'bac-tu-liem', 'me-linh', 'soc-son',
        'dong-anh', 'dan-phuong', 'hoai-duc', 'quoc-oai', 'thach-that',
        'chuong-my', 'thanh-oai', 'thuong-tin', 'phu-xuyen', 'ung-hoa',
        'my-duc', 'son-tay', 'ba-vi', 'phuc-tho', 'thanh-tri',
    ];

    if (!empty($slug_1)) {
        if (in_array($slug_1, $districts_whitelist, true)) {
            $khu_vuc_raw = $slug_1;
        } else {
            $type_raw = $slug_1;
        }
    }

    // Fix collision when rewrite rule maps district to du_an_type_slug
    if (!empty($type_raw) && in_array($type_raw, $districts_whitelist, true)) {
        if (empty($khu_vuc_raw)) {
            $khu_vuc_raw = $type_raw;
        }
        $type_raw = '';
    }

    // Split compound type-district if combined in type_raw (e.g. biet-thu-lien-ke-gia-lam)
    if (!empty($type_raw) && empty($khu_vuc_raw)) {
        foreach ($districts_whitelist as $d_slug) {
            $suffix = '-' . $d_slug;
            if (substr($type_raw, -strlen($suffix)) === $suffix) {
                $khu_vuc_raw = $d_slug;
                $type_raw = substr($type_raw, 0, -strlen($suffix));
                break;
            }
        }
    }

    // Convert Khu Vuc to Vietnamese
    $area_options = function_exists('batdongsan_get_area_options') ? batdongsan_get_area_options(false) : [];
    $khu_vuc_name = null;
    if (!empty($khu_vuc_raw) && $khu_vuc_raw !== '0') {
        $khu_vuc_name = $area_options[$khu_vuc_raw] ?? ucwords(str_replace('-', ' ', (string) $khu_vuc_raw));
    }

    // Convert Type to Vietnamese
    $type_name = null;
    if (!empty($type_raw) && $type_raw !== '0') {
        $term = null;
        if (is_numeric($type_raw) && function_exists('get_term')) {
            $term = get_term((int) $type_raw, 'loai_hinh_du_an');
            if (!$term || is_wp_error($term)) {
                $term = get_term((int) $type_raw, 'loai-hinh-du-an');
            }
        } elseif (function_exists('get_term_by')) {
            $term = get_term_by('slug', $type_raw, 'loai_hinh_du_an') ?: get_term_by('slug', $type_raw, 'loai-hinh-du-an');
        }
        if ($term && !is_wp_error($term)) {
            $type_name = $term->name;
        } else {
            $type_slug_map = [
                'can-ho-chung-cu'   => 'Căn Hộ Chung Cư',
                'biet-thu-lien-ke'  => 'Biệt Thự Liền Kề',
                'nha-pho-shophouse' => 'Nhà Phố Shophouse',
                'dat-nen'           => 'Đất Nền Dự Án',
                'khu-do-thi'        => 'Khu Đô Thị Mới',
                'nghi-duong'        => 'Bất Động Sản Nghỉ Dưỡng',
            ];
            $type_name = $type_slug_map[$type_raw] ?? ucwords(str_replace('-', ' ', (string) $type_raw));
        }
    }

    // Price label
    $price_label = null;
    if (!empty($price_raw) && $price_raw !== '0') {
        if (function_exists('batdongsan_du_an_slug_to_price_key')) {
            $price_key = batdongsan_du_an_slug_to_price_key($price_raw);
            if ($price_key && function_exists('batdongsan_format_price_range_label')) {
                $price_label = batdongsan_format_price_range_label($price_key);
            }
        }
        if (!$price_label && function_exists('batdongsan_format_price_range_label')) {
            $price_label = batdongsan_format_price_range_label($price_raw);
        }
    }

    // Status label
    $status_label = null;
    if (!empty($status) && $status !== '0') {
        $status_map = [
            'dang-mo-ban' => 'Đang Mở Bán',
            'sap-mo-ban'  => 'Sắp Mở Bán',
            'da-ban-het'  => 'Đã Bàn Giao',
        ];
        $status_label = $status_map[$status] ?? null;
    }

    if (!$khu_vuc_name && !$type_name && !$price_label && !$status_label && empty($project_name)) {
        return null;
    }

    $title_parts = [];
    if (!empty($project_name)) {
        $title_parts[] = 'Tìm Kiếm Dự Án "' . esc_html($project_name) . '"';
    } elseif ($type_name && $khu_vuc_name) {
        $prefix = $status_label ? 'Dự Án ' . $type_name . ' ' . $status_label : 'Dự Án ' . $type_name;
        $title_parts[] = $prefix . ' ' . $khu_vuc_name;
        $title_parts[] = 'Bảng Giá & Tiến Độ 2026';
    } elseif ($type_name) {
        $prefix = $status_label ? 'Dự Án ' . $type_name . ' ' . $status_label : 'Dự Án ' . $type_name;
        $title_parts[] = $prefix . ' Hà Nội';
        $title_parts[] = 'Bảng Giá & Mặt Bằng 2026';
    } elseif ($khu_vuc_name) {
        $prefix = $status_label ? 'Dự Án Bất Động Sản ' . $status_label : 'Dự Án Bất Động Sản';
        $title_parts[] = $prefix . ' ' . $khu_vuc_name . ' 2026';
    } else {
        $title_parts[] = 'Dự Án Bất Động Sản Hà Nội' . ($status_label ? ' ' . $status_label : '');
    }

    if ($price_label) {
        $title_parts[] = 'Giá ' . $price_label;
    }

    return implode(' - ', $title_parts);
}

/**
 * Generate Master SEO Title for any active query.
 *
 * Designed to fit within 50 - 65 characters for optimal Google CTR without truncation.
 *
 * @return string Full optimized SEO Title
 */
function batdongsan_get_seo_master_title(): string {
    $brand = 'BĐS Hà Nội';

    // 1. Front Page / Home
    if ((function_exists('is_front_page') && is_front_page()) || (function_exists('is_home') && is_home())) {
        $front_id = (int) get_option('page_on_front');
        if ($front_id > 0) {
            $custom_title = get_post_meta($front_id, 'rank_math_title', true);
            if (!empty($custom_title)) {
                return (string) $custom_title;
            }
        }
        return 'Bất Động Sản Hà Nội - Mua Bán & Cho Thuê Nhà Đất Uy Tín';
    }

    // 2. Single BĐS
    if (function_exists('is_singular') && is_singular('bat-dong-san')) {
        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
        if ($post_id > 0) {
            $custom_title = get_post_meta($post_id, 'rank_math_title', true);
            if (!empty($custom_title)) {
                return (string) $custom_title;
            }

            $raw_title = get_the_title($post_id);
            $clean_title = function_exists('wp_strip_all_tags') ? wp_strip_all_tags($raw_title) : strip_tags($raw_title);

            // If title is already descriptive (> 48 chars), just append short brand or trim if too long
            if (mb_strlen($clean_title) >= 48) {
                if (mb_strlen($clean_title) + mb_strlen($brand) + 3 <= 65) {
                    return $clean_title . ' | ' . $brand;
                }
                if (mb_strlen($clean_title) > 65) {
                    return function_exists('mb_strimwidth') ? mb_strimwidth($clean_title, 0, 62, '...', 'UTF-8') : substr($clean_title, 0, 62) . '...';
                }
                return $clean_title;
            }

            // Short title: enrich with Price or District if missing
            $price_million = function_exists('batdongsan_get_total_price_million') ? batdongsan_get_total_price_million($post_id) : null;
            $price_str = '';
            if ($price_million !== null && $price_million > 0) {
                $price_str = ($price_million >= 1000 ? round($price_million / 1000, 1) . ' tỷ' : round($price_million, 0) . 'tr');
            }

            $khu_vuc_terms = function_exists('get_the_terms') ? get_the_terms($post_id, 'khu-vuc') : false;
            $district = ($khu_vuc_terms && !is_wp_error($khu_vuc_terms) && !empty($khu_vuc_terms)) ? $khu_vuc_terms[0]->name : '';

            $extra = '';
            if ($price_str && mb_stripos($clean_title, 'tỷ') === false && mb_stripos($clean_title, 'triệu') === false) {
                $extra .= ' - Giá ' . $price_str;
            }
            if ($district && mb_stripos($clean_title, $district) === false) {
                $extra .= ' ' . $district;
            }

            $candidate = $clean_title . $extra . ' | ' . $brand;
            if (mb_strlen($candidate) <= 65) {
                return $candidate;
            }
            return $clean_title . ' | ' . $brand;
        }
    }

    // 3. Single Dự Án
    if (function_exists('is_singular') && is_singular('du-an')) {
        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
        if ($post_id > 0) {
            $custom_title = get_post_meta($post_id, 'rank_math_title', true);
            if (!empty($custom_title)) {
                return (string) $custom_title;
            }

            $raw_title = get_the_title($post_id);
            $clean_title = function_exists('wp_strip_all_tags') ? wp_strip_all_tags($raw_title) : strip_tags($raw_title);

            $district = '';
            $address = get_post_meta($post_id, 'address', true);
            if (!empty($address) && is_string($address)) {
                $parts = array_map('trim', explode(',', $address));
                if (count($parts) >= 2) {
                    $district = $parts[count($parts) - 2];
                }
            }

            $prefix = (mb_stripos($clean_title, 'Dự Án') === false) ? 'Dự Án ' . $clean_title : $clean_title;
            if ($district && mb_stripos($prefix, $district) === false && (mb_strlen($prefix) + mb_strlen($district) < 40)) {
                $prefix .= ' ' . $district;
            }

            $full_title = $prefix . ': Bảng Giá & Mặt Bằng 2026 | ' . $brand;
            if (mb_strlen($full_title) <= 65) {
                return $full_title;
            }

            $compact_title = $prefix . ' - Bảng Giá 2026 | ' . $brand;
            if (mb_strlen($compact_title) <= 65) {
                return $compact_title;
            }

            return $prefix . ' | ' . $brand;
        }
    }

    // 4. BĐS Filter & Archive
    $is_bds_archive = (function_exists('is_post_type_archive') && is_post_type_archive('bat-dong-san'))
        || (function_exists('is_page') && is_page('bat-dong-san'))
        || (function_exists('is_page_template') && is_page_template('page-bat-dong-san.php'))
        || (function_exists('get_query_var') && (
            get_query_var('bds_ban_slug') ||
            get_query_var('bds_khu_vuc') ||
            get_query_var('bds_filter_slug') ||
            get_query_var('bds_type_slug')
        ));

    if ($is_bds_archive) {
        $dynamic_title = function_exists('batdongsan_get_bds_filter_seo_title') ? batdongsan_get_bds_filter_seo_title() : null;
        if (!empty($dynamic_title)) {
            return $dynamic_title . ' | ' . $brand;
        }
        return 'Mua Bán & Cho Thuê Bất Động Sản Hà Nội | ' . $brand;
    }

    // 5. Dự Án Filter & Archive
    $is_du_an_archive = (function_exists('is_post_type_archive') && is_post_type_archive('du-an'))
        || (function_exists('is_page') && (is_page('du-an-bat-dong-san') || is_page('du-an')))
        || (function_exists('is_page_template') && is_page_template('page-du-an.php'))
        || (function_exists('get_query_var') && (
            get_query_var('du_an_khu_vuc') ||
            get_query_var('du_an_type_slug') ||
            get_query_var('du_an_price_slug') ||
            get_query_var('du_an_slug_1')
        ));

    if ($is_du_an_archive) {
        $dynamic_title = batdongsan_get_du_an_filter_seo_title();
        if (!empty($dynamic_title)) {
            return $dynamic_title . ' | ' . $brand;
        }
        return 'Dự Án Bất Động Sản Hà Nội Đang Mở Bán 2026 | ' . $brand;
    }

    // 6. Taxonomies / Categories
    if (function_exists('is_category') && is_category()) {
        $cat_name = single_cat_title('', false);
        return $cat_name . ' - Tin Tức Bất Động Sản | ' . $brand;
    }
    if (function_exists('is_tax') && is_tax()) {
        $term = get_queried_object();
        if ($term && isset($term->name)) {
            return $term->name . ' - Nhà Đất Hà Nội | ' . $brand;
        }
    }

    // 7. General Singular Page
    if (function_exists('is_singular') && is_singular()) {
        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
        if ($post_id > 0) {
            $custom_title = get_post_meta($post_id, 'rank_math_title', true);
            if (!empty($custom_title)) {
                return (string) $custom_title;
            }
            return get_the_title($post_id) . ' | ' . $brand;
        }
    }

    return 'Bất Động Sản Hà Nội';
}

/**
 * Generate Master SEO Description for any active query.
 *
 * @return string Optimized meta description (140-155 characters)
 */
function batdongsan_get_seo_master_description(): string {
    // 1. Front Page / Home
    if ((function_exists('is_front_page') && is_front_page()) || (function_exists('is_home') && is_home())) {
        $front_id = (int) get_option('page_on_front');
        if ($front_id > 0) {
            $custom_desc = get_post_meta($front_id, 'rank_math_description', true);
            if (!empty($custom_desc)) {
                return (string) $custom_desc;
            }
        }
        return 'Khám phá bất động sản Hà Nội: Mua bán, cho thuê nhà đất uy tín, giá tốt nhất. Tìm căn hộ, biệt thự, đất nền phù hợp tại Hà Nội ngay hôm nay!';
    }

    // 2. Single BĐS
    if (function_exists('is_singular') && is_singular('bat-dong-san')) {
        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
        if ($post_id > 0) {
            $custom_desc = get_post_meta($post_id, 'rank_math_description', true);
            if (!empty($custom_desc)) {
                return (string) $custom_desc;
            }

            $raw_title = get_the_title($post_id);
            $clean_title = function_exists('wp_strip_all_tags') ? wp_strip_all_tags($raw_title) : strip_tags($raw_title);

            $dia_chi = get_post_meta($post_id, 'dia_chi', true);
            $dien_tich_raw = get_post_meta($post_id, 'dien_tich', true);
            $dien_tich = trim(str_ireplace('m²', '', (string) $dien_tich_raw));

            $price_million = function_exists('batdongsan_get_total_price_million') ? batdongsan_get_total_price_million($post_id) : null;
            $price_str = '';
            if ($price_million !== null && $price_million > 0) {
                $price_str = function_exists('batdongsan_format_price_vnd') ? batdongsan_format_price_vnd($price_million * 1000000) : ($price_million >= 1000 ? round($price_million / 1000, 2) . ' tỷ' : round($price_million, 0) . ' triệu');
            }

            $desc = $clean_title;
            if ($dia_chi) {
                $desc .= ' tại ' . $dia_chi;
            }
            if ($dien_tich) {
                $desc .= ', DT ' . $dien_tich . ' m²';
            }
            if ($price_str) {
                $desc .= ', giá ' . $price_str;
            }
            $desc .= '. Pháp lý chuẩn, sổ đỏ chính chủ, xem nhà thực tế 24/7. Liên hệ ngay!';

            return function_exists('wp_html_excerpt') ? wp_html_excerpt($desc, 155, '...') : substr($desc, 0, 155);
        }
    }

    // 3. Single Dự Án
    if (function_exists('is_singular') && is_singular('du-an')) {
        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
        if ($post_id > 0) {
            $custom_desc = get_post_meta($post_id, 'rank_math_description', true);
            if (!empty($custom_desc)) {
                return (string) $custom_desc;
            }

            $title = get_the_title($post_id);
            $address = get_post_meta($post_id, 'address', true);
            $cdt = get_post_meta($post_id, 'chu_dau_tu', true);
            $gia_ban = get_post_meta($post_id, 'gia_ban_du_kien', true) ?: get_post_meta($post_id, 'price', true);

            $desc = 'Dự án ' . $title;
            if ($address) {
                $desc .= ' tọa lạc tại ' . $address;
            }
            if ($cdt) {
                $desc .= ', chủ đầu tư ' . $cdt;
            }
            if ($gia_ban) {
                $desc .= ', giá từ ' . $gia_ban;
            }
            $desc .= '. Cập nhật bảng giá, mặt bằng thiết kế và chính sách ưu đãi 2026. Hotline: 0934.69.9191.';

            return function_exists('wp_html_excerpt') ? wp_html_excerpt($desc, 155, '...') : substr($desc, 0, 155);
        }
    }

    // 4. BĐS Filter & Archive
    $is_bds_archive = (function_exists('is_post_type_archive') && is_post_type_archive('bat-dong-san'))
        || (function_exists('is_page') && is_page('bat-dong-san'))
        || (function_exists('is_page_template') && is_page_template('page-bat-dong-san.php'))
        || (function_exists('get_query_var') && (
            get_query_var('bds_ban_slug') ||
            get_query_var('bds_khu_vuc') ||
            get_query_var('bds_filter_slug') ||
            get_query_var('bds_type_slug')
        ));

    if ($is_bds_archive) {
        $title = function_exists('batdongsan_get_bds_filter_seo_title') ? batdongsan_get_bds_filter_seo_title() : '';
        if ($title) {
            return 'Danh sách ' . $title . '. Cập nhật liên tục 24/7, hình ảnh thực tế, sổ đỏ chính chủ, đàm phán giá trực tiếp. Hotline: 0934.69.9191.';
        }
        return 'Tổng hợp mua bán, cho thuê nhà đất tại Hà Nội uy tín: Căn hộ chung cư, nhà riêng, biệt thự, nhà phố shophouse chính chủ. Hỗ trợ tư vấn miễn phí 24/7.';
    }

    // 5. Dự Án Filter & Archive
    $is_du_an_archive = (function_exists('is_post_type_archive') && is_post_type_archive('du-an'))
        || (function_exists('is_page') && (is_page('du-an-bat-dong-san') || is_page('du-an')))
        || (function_exists('is_page_template') && is_page_template('page-du-an.php'))
        || (function_exists('get_query_var') && (
            get_query_var('du_an_khu_vuc') ||
            get_query_var('du_an_type_slug') ||
            get_query_var('du_an_price_slug') ||
            get_query_var('du_an_slug_1')
        ));

    if ($is_du_an_archive) {
        $title = batdongsan_get_du_an_filter_seo_title();
        if ($title) {
            return 'Danh sách ' . $title . '. Thông tin quy hoạch, bảng giá trực tiếp CĐT, mặt bằng và tiến độ xây dựng mới nhất 2026. Hotline: 0934.69.9191.';
        }
        return 'Tra cứu thông tin các dự án bất động sản tại Hà Nội đang mở bán năm 2026: Vị trí quy hoạch, bảng giá trực tiếp CĐT, mặt bằng thiết kế và chính sách ưu đãi.';
    }

    // 6. Categories & Taxonomies
    if (function_exists('is_category') && is_category()) {
        $cat_desc = category_description();
        if (!empty($cat_desc)) {
            return function_exists('wp_strip_all_tags') ? wp_strip_all_tags($cat_desc) : strip_tags($cat_desc);
        }
        return 'Cập nhật tin tức thị trường bất động sản Hà Nội mới nhất, phân tích xu hướng giá nhà đất, tiến độ các dự án đô thị và cẩm nang đầu tư uy tín.';
    }

    // 7. General Singular Page
    if (function_exists('is_singular') && is_singular()) {
        $post_id = function_exists('get_the_ID') ? get_the_ID() : 0;
        if ($post_id > 0) {
            $custom_desc = get_post_meta($post_id, 'rank_math_description', true);
            if (!empty($custom_desc)) {
                return (string) $custom_desc;
            }
            $post = get_post($post_id);
            if ($post && !empty($post->post_excerpt)) {
                return function_exists('wp_strip_all_tags') ? wp_strip_all_tags($post->post_excerpt) : strip_tags($post->post_excerpt);
            }
        }
    }

    return 'Kênh thông tin bất động sản Hà Nội hàng đầu: Mua bán nhà đất, căn hộ chung cư, biệt thự liền kề và các dự án đô thị quy hoạch mới nhất. Hotline: 0934.69.9191.';
}

/**
 * Filter Rank Math Frontend Title.
 *
 * Prioritizes Rank Math configuration, providing dynamic fallback for pretty-filter routes.
 *
 * @param string $title Existing title generated by Rank Math
 * @return string Filtered SEO Title
 */
function batdongsan_seo_filter_rank_math_title(string $title): string {
    // 1. Sanitize any accidental "Archive" remnants generated by core WordPress / Rank Math
    if (stripos($title, 'Archive') !== false) {
        $title = trim(str_ireplace([' Archive', 'Archives', 'Archive - ', 'Archive | ', 'Archive', 'Lưu trữ: ', 'Lưu trữ '], '', $title));
    }

    $is_bds_filter = function_exists('get_query_var') && (
        get_query_var('bds_ban_slug') ||
        get_query_var('bds_khu_vuc') ||
        get_query_var('bds_filter_slug') ||
        get_query_var('bds_type_slug') ||
        !empty($_GET['hinh_thuc']) ||
        !empty($_GET['khu_vuc']) ||
        !empty($_GET['type']) ||
        !empty($_GET['price']) ||
        !empty($_GET['area']) ||
        !empty($_GET['du_an'])
    );
    if ($is_bds_filter && function_exists('batdongsan_get_bds_filter_seo_title')) {
        $filter_title = batdongsan_get_bds_filter_seo_title();
        if (!empty($filter_title)) {
            return $filter_title . ' | BĐS Hà Nội';
        }
    }

    $is_du_an_filter = function_exists('get_query_var') && (
        get_query_var('du_an_khu_vuc') ||
        get_query_var('du_an_type_slug') ||
        get_query_var('du_an_price_slug') ||
        get_query_var('du_an_slug_1') ||
        !empty($_GET['khu_vuc']) ||
        !empty($_GET['type']) ||
        !empty($_GET['price']) ||
        !empty($_GET['status']) ||
        !empty($_GET['project-name'])
    );
    if ($is_du_an_filter && function_exists('batdongsan_get_du_an_filter_seo_title')) {
        $filter_title = batdongsan_get_du_an_filter_seo_title();
        if (!empty($filter_title)) {
            return $filter_title . ' | BĐS Hà Nội';
        }
    }

    $is_du_an_archive = (function_exists('is_post_type_archive') && is_post_type_archive('du-an'))
        || (function_exists('is_page') && (is_page('du-an-bat-dong-san') || is_page('du-an')))
        || (function_exists('is_page_template') && is_page_template('page-du-an.php'));
    if ($is_du_an_archive) {
        if (empty($title) || $title === 'Dự án' || $title === 'Dự án - Bất động sản Hà Nội' || $title === 'Dự án | BĐS Hà Nội') {
            return 'Dự Án Bất Động Sản Hà Nội - Bảng Giá 2026 | BĐS Hà Nội';
        }
    }

    // Respect user's Rank Math configuration directly
    if (!empty($title)) {
        return $title;
    }

    return batdongsan_get_seo_master_title();
}
add_filter('rank_math/frontend/title', 'batdongsan_seo_filter_rank_math_title', 25);
add_filter('rank_math/opengraph/facebook/title', 'batdongsan_seo_filter_rank_math_title', 25);
add_filter('rank_math/opengraph/twitter/title', 'batdongsan_seo_filter_rank_math_title', 25);

/**
 * Filter Rank Math Frontend Description.
 *
 * Prioritizes Rank Math configuration, providing dynamic fallback for pretty-filter routes.
 *
 * @param string $desc Existing description generated by Rank Math
 * @return string Filtered Meta Description
 */
function batdongsan_seo_filter_rank_math_description(string $desc): string {
    $is_filter = function_exists('get_query_var') && (
        get_query_var('bds_ban_slug') ||
        get_query_var('bds_khu_vuc') ||
        get_query_var('bds_filter_slug') ||
        get_query_var('bds_type_slug') ||
        get_query_var('du_an_khu_vuc') ||
        get_query_var('du_an_type_slug') ||
        get_query_var('du_an_price_slug') ||
        get_query_var('du_an_slug_1') ||
        !empty($_GET['hinh_thuc']) ||
        !empty($_GET['khu_vuc']) ||
        !empty($_GET['type']) ||
        !empty($_GET['price']) ||
        !empty($_GET['area']) ||
        !empty($_GET['du_an']) ||
        !empty($_GET['status']) ||
        !empty($_GET['project-name'])
    );
    if ($is_filter) {
        $seo_desc = batdongsan_get_seo_master_description();
        if (!empty($seo_desc)) {
            return $seo_desc;
        }
    }

    // Respect user's Rank Math configuration directly
    if (!empty($desc)) {
        return $desc;
    }

    return batdongsan_get_seo_master_description();
}
add_filter('rank_math/frontend/description', 'batdongsan_seo_filter_rank_math_description', 25);
add_filter('rank_math/opengraph/facebook/description', 'batdongsan_seo_filter_rank_math_description', 25);
add_filter('rank_math/opengraph/twitter/description', 'batdongsan_seo_filter_rank_math_description', 25);

/**
 * Filter OpenGraph / Twitter Image Fallback.
 *
 * @param string|null $image Existing image URL
 * @return string|null Resolved image URL
 */
function batdongsan_seo_filter_og_image(?string $image): ?string {
    if (!empty($image)) {
        return $image;
    }
    if (function_exists('is_singular') && is_singular() && function_exists('has_post_thumbnail') && has_post_thumbnail()) {
        $thumb = get_the_post_thumbnail_url(get_the_ID(), 'large');
        if ($thumb) {
            return $thumb;
        }
    }
    $custom_logo_id = function_exists('get_theme_mod') ? get_theme_mod('custom_logo') : 0;
    if ($custom_logo_id && function_exists('wp_get_attachment_image_url')) {
        $logo = wp_get_attachment_image_url($custom_logo_id, 'full');
        if ($logo) {
            return $logo;
        }
    }
    return function_exists('home_url') ? home_url('/wp-content/uploads/2024/10/20201218_Pv02_tongthe_dem.png') : null;
}
add_filter('rank_math/opengraph/facebook/image', 'batdongsan_seo_filter_og_image', 25);
add_filter('rank_math/opengraph/twitter/image', 'batdongsan_seo_filter_og_image', 25);

/**
 * Filter Rank Math Canonical URL to preserve pretty filter URLs.
 *
 * @param string $canonical Current canonical
 * @return string Canonical URL
 */
function batdongsan_seo_filter_rank_math_canonical(string $canonical): string {
    if (!empty($_SERVER['REQUEST_URI'])) {
        $req_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if ($req_path && ($req_path !== '/' && $req_path !== '')) {
            // Self-referencing canonical for pretty routes
            if (strpos($req_path, '/ban-') === 0 || strpos($req_path, '/bat-dong-san-quan-') === 0 || strpos($req_path, '/du-an') === 0) {
                return home_url($req_path);
            }
        }
    }
    return $canonical;
}
add_filter('rank_math/frontend/canonical', 'batdongsan_seo_filter_rank_math_canonical', 25);

/**
 * Robots.txt Hook for Generative AI Bots & LLM Search Crawlers.
 *
 * Ensures modern search assistants (ChatGPT Search, Gemini, Perplexity, Claude, Applebot)
 * are explicitly permitted to index and cite real estate content.
 *
 * @param string $output Current robots.txt output
 * @param bool $public Whether blog is public
 * @return string Enhanced robots.txt
 */
function batdongsan_seo_ai_robots_txt(string $output, bool $public = true): string {
    $ai_rules = "\n# Generative AI Search & Knowledge Crawlers\n";
    $ai_rules .= "User-agent: Google-Extended\nAllow: /\n\n";
    $ai_rules .= "User-agent: GPTBot\nAllow: /\n\n";
    $ai_rules .= "User-agent: OAI-SearchBot\nAllow: /\n\n";
    $ai_rules .= "User-agent: PerplexityBot\nAllow: /\n\n";
    $ai_rules .= "User-agent: ClaudeBot\nAllow: /\n\n";
    $ai_rules .= "User-agent: Claude-SearchBot\nAllow: /\n\n";
    $ai_rules .= "User-agent: Claude-User\nAllow: /\n\n";
    $ai_rules .= "User-agent: Applebot-Extended\nAllow: /\n";

    return $output . $ai_rules;
}
add_filter('robots_txt', 'batdongsan_seo_ai_robots_txt', 99, 2);
add_filter('rank_math/robots_txt', 'batdongsan_seo_ai_robots_txt', 99, 2);

/**
 * Output Local SEO & AI Search Metadata in <head>.
 *
 * Adds GEO tags (Hà Nội, Vietnam) and rich snippet controls for Google & AI Engines.
 *
 * @return void
 */
function batdongsan_seo_head_meta_tags(): void {
    // 1. GEO / Local SEO Tags (Essential for ChatGPT Search Local & Gemini Maps)
    echo "\n<!-- Local SEO & Generative AI Metadata -->\n";
    echo "<meta name=\"geo.region\" content=\"VN-HN\">\n";
    echo "<meta name=\"geo.placename\" content=\"Hà Nội\">\n";

    $post_id = is_singular() ? get_the_ID() : 0;
    $lat = null;
    $lng = null;

    if ($post_id > 0) {
        $map = get_field('google_map', $post_id);
        if (is_array($map) && !empty($map['lat']) && !empty($map['lng'])) {
            $lat = (float) $map['lat'];
            $lng = (float) $map['lng'];
        }
    }

    if ($lat && $lng) {
        echo "<meta name=\"geo.position\" content=\"{$lat};{$lng}\">\n";
        echo "<meta name=\"ICBM\" content=\"{$lat}, {$lng}\">\n";
    } else {
        // Default Hanoi Coordinates
        echo "<meta name=\"geo.position\" content=\"21.028511;105.804817\">\n";
        echo "<meta name=\"ICBM\" content=\"21.028511, 105.804817\">\n";
    }

    // 2. Fallback when Rank Math or other SEO plugins are NOT active
    if (!class_exists('RankMath')) {
        if (get_option('blog_public') != '0') {
            echo "<meta name=\"robots\" content=\"max-snippet:-1, max-image-preview:large, max-video-preview:-1\">\n";
        }

        $title = batdongsan_get_seo_master_title();
        $desc  = batdongsan_get_seo_master_description();
        $canon = function_exists('wp_get_canonical_url') ? wp_get_canonical_url() : home_url($_SERVER['REQUEST_URI'] ?? '/');

        if (!empty($desc)) {
            echo '<meta name="description" content="' . esc_attr($desc) . "\">\n";
        }
        if (!empty($canon)) {
            echo '<link rel="canonical" href="' . esc_url($canon) . "\">\n";
        }

        // OpenGraph fallback
        echo '<meta property="og:locale" content="vi_VN">' . "\n";
        echo '<meta property="og:type" content="' . (is_singular() ? 'article' : 'website') . "\">\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . "\">\n";
        echo '<meta property="og:description" content="' . esc_attr($desc) . "\">\n";
        echo '<meta property="og:url" content="' . esc_url($canon) . "\">\n";
        echo '<meta property="og:site_name" content="Bất Động Sản Hà Nội">' . "\n";

        // Twitter fallback
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . "\">\n";
        echo '<meta name="twitter:description" content="' . esc_attr($desc) . "\">\n";
    }
}
add_action('wp_head', 'batdongsan_seo_head_meta_tags', 2);

/**
 * Sanitize URL slugs: normalize superscript ², ³ into standard 2, 3
 * to prevent ugly percent-encoded slugs like %c2%b2 in real estate URLs.
 *
 * @param string $title Sanitized slug candidate
 * @param string $raw_title Original raw title
 * @param string $context Context (save, query, etc.)
 * @return string Clean URL slug
 */
function batdongsan_sanitize_title_superscript(string $title, string $raw_title = '', string $context = 'query'): string {
    return str_replace(['%c2%b2', '%c2%b3', '²', '³'], ['2', '3', '2', '3'], $title);
}
add_filter('sanitize_title', 'batdongsan_sanitize_title_superscript', 5, 3);


