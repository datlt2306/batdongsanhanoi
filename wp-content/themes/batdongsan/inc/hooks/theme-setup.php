<?php
/**
 * Theme setup hooks
 * 
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Thiết lập theme cơ bản.
 */
function batdongsan_theme_setup(): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);

    // Hỗ trợ custom logo từ Customizer
    add_theme_support('custom-logo', [
        'height'               => 80,
        'width'                => 300,
        'flex-height'          => true,
        'flex-width'           => true,
        'header-text'          => ['site-title', 'site-description'],
        'unlink-homepage-logo' => false,
    ]);

    register_nav_menus([
        'primary' => __('Primary Menu', 'batdongsan'),
        'utility' => __('Utility Menu', 'batdongsan'),
        'footer'  => __('Footer Menu', 'batdongsan'),
    ]);
}
add_action('after_setup_theme', 'batdongsan_theme_setup');

/**
 * Tắt Gutenberg (Block Editor) cho toàn bộ site
 * Sử dụng Classic Editor cho tất cả post types
 */
function batdongsan_disable_gutenberg(): bool {
    return false;
}
add_filter('use_block_editor_for_post', 'batdongsan_disable_gutenberg', 10, 2);
add_filter('use_block_editor_for_post_type', 'batdongsan_disable_gutenberg', 10, 2);

add_filter('register_post_type_args', 'batdongsan_du_an_rewrite_slug', 10, 2);
function batdongsan_du_an_rewrite_slug(array $args, string $post_type): array {
    if ($post_type === 'du-an') {
        $args['has_archive']   = true;
        $args['rewrite']       = array_merge((array) ($args['rewrite'] ?? []), ['slug' => 'du-an', 'with_front' => true]);
    }
    return $args;
}


/**
 * Khi truy cập archive của CPT bat-dong-san hoặc du-an, load page template tương ứng
 * thay vì archive template. CPT có has_archive=true nên URL là archive, không phải Page –
 * page-*.php sẽ không được load nếu không có filter này.
 */
/**
 * Thêm query var và rewrite rules cho filter slug BĐS.
 * URL: /bat-dong-san/gia-tu-30-ty-den-40-ty/ hoặc /bat-dong-san/dt-tu-100m2-den-150m2/
 */
add_filter('query_vars', 'batdongsan_add_filter_slug_query_var');
function batdongsan_add_filter_slug_query_var(array $vars): array {
    $vars[] = 'bds_filter_slug';
    $vars[] = 'du_an_khu_vuc';
    $vars[] = 'du_an_type_slug';
    $vars[] = 'du_an_price_slug';
    $vars[] = 'du_an_slug_1';
    $vars[] = 'bds_khu_vuc';
    $vars[] = 'bds_type_slug';
    $vars[] = 'bds_slug_1';
    $vars[] = 'bds_ban_slug';
    $vars[] = 'bds_area_slug';
    $vars[] = 'bds_price_slug';
    $vars[] = 'bds_du_an_slug';
    return $vars;
}

add_action('after_switch_theme', 'batdongsan_flush_rewrite_rules');
function batdongsan_flush_rewrite_rules(): void {
    batdongsan_add_bds_filter_rewrite_rules();
    flush_rewrite_rules();
}

add_action('init', 'batdongsan_add_bds_filter_rewrite_rules', 20);
function batdongsan_add_bds_filter_rewrite_rules(): void {
    // BĐS: ban-{type} hoặc ban-{type}-gia-xxx hoặc ban-{type}-dien-tich-xxx-gia-xxx
    add_rewrite_rule(
        'ban-([a-z0-9-]+)/page/([0-9]+)/?$',
        'index.php?post_type=bat-dong-san&bds_ban_slug=$matches[1]&paged=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        'ban-([a-z0-9-]+)/?$',
        'index.php?post_type=bat-dong-san&bds_ban_slug=$matches[1]',
        'top'
    );
    // BĐS: bat-dong-san/du-an-{slug} (filter thuộc dự án)
    add_rewrite_rule(
        'bat-dong-san/du-an-([a-z0-9-]+)/page/([0-9]+)/?$',
        'index.php?post_type=bat-dong-san&bds_du_an_slug=$matches[1]&paged=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        'bat-dong-san/du-an-([a-z0-9-]+)/?$',
        'index.php?post_type=bat-dong-san&bds_du_an_slug=$matches[1]',
        'top'
    );
    $bds_filter_re = '((?:gia-|dt-)[a-z0-9\.-]+)';
    add_rewrite_rule(
        'bat-dong-san/du-an-([a-z0-9-]+)/' . $bds_filter_re . '/page/([0-9]+)/?$',
        'index.php?post_type=bat-dong-san&bds_du_an_slug=$matches[1]&bds_filter_slug=$matches[2]&paged=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        'bat-dong-san/du-an-([a-z0-9-]+)/' . $bds_filter_re . '/?$',
        'index.php?post_type=bat-dong-san&bds_du_an_slug=$matches[1]&bds_filter_slug=$matches[2]',
        'top'
    );
    // BĐS: bat-dong-san/gia-... hoặc bat-dong-san/dt-... (giá hoặc diện tích, không có type/khu vực)
    add_rewrite_rule(
        'bat-dong-san/' . $bds_filter_re . '/page/([0-9]+)/?$',
        'index.php?post_type=bat-dong-san&bds_filter_slug=$matches[1]&paged=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        'bat-dong-san/' . $bds_filter_re . '/?$',
        'index.php?post_type=bat-dong-san&bds_filter_slug=$matches[1]',
        'top'
    );
    // BĐS: {type}-quan-{khu_vuc} (loại hình + khu vực), VD: biet-thu-lien-ke-quan-gia-lam
    // Rule bat-dong-san-quan-xxx phải thêm sau để ưu tiên hơn (tránh bat-dong-san-quan-gia-lam bị match type=bat-dong-san)
    $type_quan_re = '([a-z0-9-]+)-quan-([a-z0-9-]+)';
    add_rewrite_rule(
        $type_quan_re . '/' . $bds_filter_re . '/page/([0-9]+)/?$',
        'index.php?post_type=bat-dong-san&bds_type_slug=$matches[1]&bds_khu_vuc=$matches[2]&bds_filter_slug=$matches[3]&paged=$matches[4]',
        'top'
    );
    add_rewrite_rule(
        $type_quan_re . '/' . $bds_filter_re . '/?$',
        'index.php?post_type=bat-dong-san&bds_type_slug=$matches[1]&bds_khu_vuc=$matches[2]&bds_filter_slug=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        $type_quan_re . '/page/([0-9]+)/?$',
        'index.php?post_type=bat-dong-san&bds_type_slug=$matches[1]&bds_khu_vuc=$matches[2]&paged=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        $type_quan_re . '/?$',
        'index.php?post_type=bat-dong-san&bds_type_slug=$matches[1]&bds_khu_vuc=$matches[2]',
        'top'
    );
    // BĐS: bat-dong-san-quan-xxx (kết hợp filter slug hoặc đứng một mình)
    add_rewrite_rule(
        'bat-dong-san-quan-([a-z0-9-]+)/' . $bds_filter_re . '/page/([0-9]+)/?$',
        'index.php?post_type=bat-dong-san&bds_khu_vuc=$matches[1]&bds_filter_slug=$matches[2]&paged=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        'bat-dong-san-quan-([a-z0-9-]+)/' . $bds_filter_re . '/?$',
        'index.php?post_type=bat-dong-san&bds_khu_vuc=$matches[1]&bds_filter_slug=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        'bat-dong-san-quan-([a-z0-9-]+)/page/([0-9]+)/?$',
        'index.php?post_type=bat-dong-san&bds_khu_vuc=$matches[1]&paged=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        'bat-dong-san-quan-([a-z0-9-]+)/?$',
        'index.php?post_type=bat-dong-san&bds_khu_vuc=$matches[1]',
        'top'
    );
    // Dự án + giá: du-an-can-ho-chung-cu-nam-tu-liem/gia-tu-10-trieu-m2-den-20-trieu-m2
    $price_slug_re = '(gia-(?:tu|duoi|tren|thoa)[a-z0-9-]+)';
    $du_an_districts_re = '(gia-lam|nam-tu-liem|hai-ba-trung|tay-ho|long-bien|cau-giay|dong-da|hoang-mai|thanh-xuan|ha-dong|ba-dinh|hoan-kiem|bac-tu-liem|me-linh|soc-son|dong-anh|dan-phuong|hoai-duc|quoc-oai|thach-that|chuong-my|thanh-oai|thuong-tin|phu-xuyen|ung-hoa|my-duc|son-tay|ba-vi|phuc-tho|thanh-tri)';

    // Clean /du-an rules
    add_rewrite_rule(
        'du-an-([a-z0-9-]+)/' . $price_slug_re . '/page/([0-9]+)/?$',
        'index.php?post_type=du-an&du_an_khu_vuc=$matches[1]&du_an_price_slug=$matches[2]&paged=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        'du-an-([a-z0-9-]+)/' . $price_slug_re . '/?$',
        'index.php?post_type=du-an&du_an_khu_vuc=$matches[1]&du_an_price_slug=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        'du-an-([a-z0-9-]+)-' . $du_an_districts_re . '/' . $price_slug_re . '/page/([0-9]+)/?$',
        'index.php?post_type=du-an&du_an_type_slug=$matches[1]&du_an_khu_vuc=$matches[2]&du_an_price_slug=$matches[3]&paged=$matches[4]',
        'top'
    );
    add_rewrite_rule(
        'du-an-([a-z0-9-]+)-' . $du_an_districts_re . '/' . $price_slug_re . '/?$',
        'index.php?post_type=du-an&du_an_type_slug=$matches[1]&du_an_khu_vuc=$matches[2]&du_an_price_slug=$matches[3]',
        'top'
    );
    // Dự án chỉ theo giá: /du-an/gia-tu-10-trieu-m2-den-20-trieu-m2
    add_rewrite_rule(
        'du-an/' . $price_slug_re . '/page/([0-9]+)/?$',
        'index.php?post_type=du-an&du_an_price_slug=$matches[1]&paged=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        'du-an/' . $price_slug_re . '/?$',
        'index.php?post_type=du-an&du_an_price_slug=$matches[1]',
        'top'
    );
    // Root archive cho /du-an/
    add_rewrite_rule(
        'du-an/?$',
        'index.php?post_type=du-an',
        'top'
    );
    // Phân trang gốc cho /du-an/page/2/
    add_rewrite_rule(
        'du-an/page/([0-9]+)/?$',
        'index.php?post_type=du-an&paged=$matches[1]',
        'top'
    );
    // Dự án theo loại hình + khu vực: /du-an-biet-thu-lien-ke-gia-lam
    add_rewrite_rule(
        'du-an-([a-z0-9-]+)-' . $du_an_districts_re . '/page/([0-9]+)/?$',
        'index.php?post_type=du-an&du_an_type_slug=$matches[1]&du_an_khu_vuc=$matches[2]&paged=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        'du-an-([a-z0-9-]+)-' . $du_an_districts_re . '/?$',
        'index.php?post_type=du-an&du_an_type_slug=$matches[1]&du_an_khu_vuc=$matches[2]',
        'top'
    );
    // Dự án theo loại hình + khu vực dạng phân tách rõ ràng (-tai- hoặc -quan-): /du-an-biet-thu-lien-ke-tai-nam-tu-liem
    add_rewrite_rule(
        'du-an-([a-z0-9-]+)-(?:tai|quan)-([a-z0-9-]+)/page/([0-9]+)/?$',
        'index.php?post_type=du-an&du_an_type_slug=$matches[1]&du_an_khu_vuc=$matches[2]&paged=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        'du-an-([a-z0-9-]+)-(?:tai|quan)-([a-z0-9-]+)/?$',
        'index.php?post_type=du-an&du_an_type_slug=$matches[1]&du_an_khu_vuc=$matches[2]',
        'top'
    );
    // Dự án 1 segment: khu vực hoặc loại hình (/du-an-gia-lam hoặc /du-an-can-ho-chung-cu)
    add_rewrite_rule(
        'du-an-([a-z0-9-]+)/page/([0-9]+)/?$',
        'index.php?post_type=du-an&du_an_slug_1=$matches[1]&paged=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        'du-an-([a-z0-9-]+)/?$',
        'index.php?post_type=du-an&du_an_slug_1=$matches[1]',
        'top'
    );

    // Legacy /du-an-bat-dong-san/ backward compatibility rewrite rules
    add_rewrite_rule(
        'du-an-bat-dong-san-([a-z0-9-]+)/page/([0-9]+)/?$',
        'index.php?post_type=du-an&du_an_slug_1=$matches[1]&paged=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        'du-an-bat-dong-san-([a-z0-9-]+)/?$',
        'index.php?post_type=du-an&du_an_slug_1=$matches[1]',
        'top'
    );
    add_rewrite_rule(
        'du-an-bat-dong-san/?$',
        'index.php?post_type=du-an',
        'top'
    );
    add_rewrite_rule(
        'du-an-bat-dong-san/page/([0-9]+)/?$',
        'index.php?post_type=du-an&paged=$matches[1]',
        'top'
    );
}

add_action('template_redirect', 'batdongsan_du_an_redirect_old_slug', 1);
function batdongsan_du_an_redirect_old_slug(): void {
    $request = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    // Chuyển hướng 301 toàn bộ URL cũ /du-an-bat-dong-san... về /du-an...
    if (strpos($request, '/du-an-bat-dong-san') === 0) {
        $new = preg_replace('#^/du-an-bat-dong-san(/?)#', '/du-an$1', $request);
        wp_safe_redirect(home_url($new), 301);
        exit;
    }
}

add_action('template_redirect', 'batdongsan_redirect_loai_hinh_taxonomy_to_ban', 3);
function batdongsan_redirect_loai_hinh_taxonomy_to_ban(): void {
    if (!is_tax('loai-hinh') && !is_tax('loai_hinh_bat_dong_san')) {
        return;
    }
    $term = get_queried_object();
    if (!$term || !isset($term->slug)) {
        return;
    }
    wp_safe_redirect(home_url('/ban-' . $term->slug . '/'), 301);
    exit;
}

add_action('template_redirect', 'batdongsan_bds_redirect_to_pretty_url', 4);
function batdongsan_bds_redirect_to_pretty_url(): void {
    if (!is_post_type_archive('bat-dong-san')) {
        return;
    }
    $type    = isset($_GET['type']) ? $_GET['type'] : '';
    $khu_vuc = isset($_GET['khu_vuc']) ? sanitize_text_field(wp_unslash($_GET['khu_vuc'])) : '';
    $price   = isset($_GET['price']) ? $_GET['price'] : null;
    $area    = isset($_GET['area']) ? $_GET['area'] : null;
    $du_an   = isset($_GET['du_an']) ? absint($_GET['du_an']) : 0;
    $name    = isset($_GET['project-name']) ? trim((string) $_GET['project-name']) : '';
    if ($name !== '') {
        return;
    }
    $bds_du_an_slug = get_query_var('bds_du_an_slug', '');
    if ($bds_du_an_slug !== '' && isset($_GET['du_an']) && ($_GET['du_an'] === '0' || $_GET['du_an'] === '')) {
        // Giữ lại type, khu_vuc, price, area, order khi chuyển từ trang dự án sang "Tất cả dự án"
        $target = batdongsan_bds_pretty_url($type, $khu_vuc, $price ?: null, $area ?: null);
        if ($target === null) {
            $target = home_url('/bat-dong-san/');
            $query_args = array_filter([
                'type'    => ($type && $type !== '0') ? $type : null,
                'khu_vuc' => $khu_vuc ?: null,
                'price'   => $price ?: null,
                'area'    => $area ?: null,
                'order'   => (isset($_GET['order']) && (int) $_GET['order'] > 1) ? (int) $_GET['order'] : null,
            ], fn($v) => $v !== null && $v !== '');
            if (!empty($query_args)) {
                $target = add_query_arg($query_args, $target);
            }
        } else {
            $query_args = [];
            if (isset($_GET['order']) && (int) $_GET['order'] > 1 && (int) $_GET['order'] <= 5) {
                $query_args['order'] = (int) $_GET['order'];
            }
            if (!empty($query_args)) {
                $target = add_query_arg($query_args, $target);
            }
        }
        wp_safe_redirect($target, 301);
        exit;
    }
    if ($du_an > 0) {
        $du_an_slug = get_post_field('post_name', $du_an);
        if ($du_an_slug) {
            $target = home_url('/bat-dong-san/du-an-' . $du_an_slug . '/');
            $filter_slug = function_exists('batdongsan_build_filter_slug') ? batdongsan_build_filter_slug($price ?: null, $area ?: null) : '';
            if ($filter_slug) {
                $target = home_url('/bat-dong-san/du-an-' . $du_an_slug . '/' . $filter_slug . '/');
            }
            $query_args = array_filter([
                'type'    => ($type && $type !== '0') ? $type : null,
                'khu_vuc' => $khu_vuc ?: null,
                'order'   => (isset($_GET['order']) && (int) $_GET['order'] > 1) ? (int) $_GET['order'] : null,
            ], fn($v) => $v !== null && $v !== '');
            if (!empty($query_args)) {
                $target = add_query_arg($query_args, $target);
            }
            wp_safe_redirect($target, 301);
            exit;
        }
    }
    $target = batdongsan_bds_pretty_url($type, $khu_vuc, $price ?: null, $area ?: null);
    if ($target === null) {
        return;
    }
    $query_args = [];
    $order = isset($_GET['order']) ? absint($_GET['order']) : 1;
    if ($order > 1 && $order <= 5) {
        $query_args['order'] = $order;
    }
    if (!empty($query_args)) {
        $target = add_query_arg($query_args, $target);
    }
    wp_safe_redirect($target, 301);
    exit;
}

function batdongsan_bds_pretty_url(string $type, string $khu_vuc, ?string $price_key, ?string $area_key): ?string {
    $type_slug = null;
    if ($type && $type !== '0' && function_exists('batdongsan_loai_hinh_id_to_slug')) {
        $type_slug = batdongsan_loai_hinh_id_to_slug((int) $type);
        if ($type_slug === '') {
            return null;
        }
    }
    $khu_vuc = $khu_vuc ?: '';
    if (empty($type_slug) && empty($khu_vuc) && empty($price_key) && empty($area_key)) {
        return null;
    }
    if (!empty($type_slug) && !empty($khu_vuc)) {
        $path = $type_slug . '-quan-' . $khu_vuc;
        $filter_slug = function_exists('batdongsan_build_filter_slug') ? batdongsan_build_filter_slug($price_key, $area_key) : '';
        return home_url('/' . $path . ($filter_slug ? '/' . $filter_slug : '') . '/');
    }
    if (!empty($type_slug)) {
        $ban_slug = function_exists('batdongsan_build_ban_slug') ? batdongsan_build_ban_slug($type_slug, $price_key, $area_key) : $type_slug;
        return home_url('/' . $ban_slug . '/');
    }
    if (!empty($khu_vuc)) {
        return home_url('/bat-dong-san-quan-' . $khu_vuc . '/');
    }
    $filter_slug = function_exists('batdongsan_build_filter_slug') ? batdongsan_build_filter_slug($price_key, $area_key) : '';
    if ($filter_slug) {
        return home_url('/bat-dong-san/' . $filter_slug . '/');
    }
    return null;
}

add_action('template_redirect', 'batdongsan_du_an_redirect_to_pretty_url', 5);
function batdongsan_du_an_redirect_to_pretty_url(): void {
    if (!is_post_type_archive('du-an')) {
        return;
    }
    $type    = isset($_GET['type']) ? $_GET['type'] : '';
    $khu_vuc = isset($_GET['khu_vuc']) ? sanitize_text_field(wp_unslash($_GET['khu_vuc'])) : '';
    $price   = isset($_GET['price']) ? $_GET['price'] : '';
    $status  = isset($_GET['status']) ? $_GET['status'] : '';
    $name    = isset($_GET['project-name']) ? trim((string) $_GET['project-name']) : '';
    if ($name !== '') {
        return;
    }
    $order  = isset($_GET['order']) ? absint($_GET['order']) : 1;
    $target = batdongsan_du_an_pretty_url($type, $khu_vuc, $price ?: '', $order, $status);
    if ($target === null) {
        return;
    }
    wp_safe_redirect($target, 301);
    exit;
}

/**
 * Tạo pretty URL trang dự án. Trả về null nếu không cần redirect.
 * Status và order thêm vào query string (?status=X&order=Y).
 */
function batdongsan_du_an_pretty_url(string $type, string $khu_vuc, string $price_key = '', int $order = 1, string $status = ''): ?string {
    $type_slug = null;
    if ($type && $type !== '0' && function_exists('batdongsan_loai_hinh_du_an_id_to_slug')) {
        $type_slug = batdongsan_loai_hinh_du_an_id_to_slug((int) $type);
    }
    $khu_vuc = $khu_vuc ?: '';
    if (empty($type_slug) && empty($khu_vuc) && empty($price_key)) {
        return null;
    }
    if (!empty($type_slug) && !empty($khu_vuc)) {
        $base = '/du-an-' . $type_slug . '-' . $khu_vuc;
    } elseif (!empty($khu_vuc)) {
        $base = '/du-an-' . $khu_vuc;
    } elseif (!empty($type_slug)) {
        $base = '/du-an-' . $type_slug;
    } else {
        $base = '/du-an';
    }
    $price_slug = ($price_key && $price_key !== '0' && function_exists('batdongsan_du_an_price_key_to_slug')) ? batdongsan_du_an_price_key_to_slug($price_key) : '';
    if ($price_slug) {
        $base .= '/' . $price_slug;
    }
    $url = home_url($base);
    $query_args = [];
    if ($order > 1) {
        $query_args['order'] = $order;
    }
    if ($status && $status !== '0') {
        $query_args['status'] = $status;
    }
    if (!empty($query_args)) {
        $url = add_query_arg($query_args, $url);
    }
    return $url;
}

/**
 * @deprecated Sử dụng batdongsan_du_an_pretty_url()
 */
function batdongsan_du_an_khu_vuc_url(string $khu_vuc_slug, int $order = 1): string {
    $url = batdongsan_du_an_pretty_url('', $khu_vuc_slug, '', $order, '');
    return $url ?: home_url('/du-an');
}

/**
 * Xóa ACF field group cũ "Thông tin sản phẩm" (group_nha_dat_ban_fields) khỏi database.
 * Chỉ chạy 1 lần sau khi đã đổi sang group_bat_dong_san_fields (Thông tin bất động sản).
 */
add_action('acf/init', 'batdongsan_remove_legacy_product_fields_group');
function batdongsan_remove_legacy_product_fields_group(): void {
    if (!function_exists('acf_delete_field_group')) {
        return;
    }
    $done = get_option('batdongsan_removed_nha_dat_ban_group', false);
    if ($done) {
        return;
    }
    acf_delete_field_group('group_nha_dat_ban_fields');
    update_option('batdongsan_removed_nha_dat_ban_group', true);
}

add_filter('template_include', 'batdongsan_cpt_archive_use_page_template', 5);
function batdongsan_cpt_archive_use_page_template(string $template): string {
    $mapping = [
        'bat-dong-san' => 'page-bat-dong-san.php',
        'du-an'        => 'page-du-an.php',
    ];
    foreach ($mapping as $post_type => $page_file) {
        if (is_post_type_archive($post_type)) {
            $page_template = get_template_directory() . '/' . $page_file;
            if (file_exists($page_template)) {
                return $page_template;
            }
        }
    }
    return $template;
}

/**
 * Helper format label khoảng giá cho SEO Title & Filter chip.
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
 * Helper format label diện tích cho SEO Title & Filter chip.
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
 * Tạo tiêu đề động cho bộ lọc BĐS chuẩn SEO (F11).
 * Hỗ trợ các trường hợp: Loại hình, Khu vực (Quận/Huyện), Khoảng giá, Diện tích, Dự án, Hình thức giao dịch.
 *
 * @param array $params Tham số truyền vào (nếu gọi từ AJAX) hoặc rỗng (sẽ tự động đọc từ query_vars và $_GET).
 * @return string|null Tiêu đề đã format hoặc null nếu không có filter nào active.
 */
function batdongsan_get_bds_filter_seo_title(array $params = []): ?string {
    // Helper lấy query var an toàn trong cả môi trường WordPress và Test suite
    $get_qv = function (string $var, $default = '') {
        return function_exists('get_query_var') ? get_query_var($var, $default) : $default;
    };
    $sanitize = function ($val) {
        if (!is_string($val)) return (string) $val;
        if (function_exists('sanitize_text_field')) {
            return function_exists('wp_unslash') ? sanitize_text_field(wp_unslash($val)) : sanitize_text_field($val);
        }
        return trim(strip_tags($val));
    };

    // 1. Thu thập tham số
    $type_raw     = $params['type'] ?? $get_qv('bds_type_slug');
    $khu_vuc_raw  = $params['khu_vuc'] ?? $get_qv('bds_khu_vuc');
    $hinh_thuc    = $params['hinh_thuc'] ?? ($get_qv('bds_hinh_thuc') ?: ($_GET['hinh_thuc'] ?? ''));
    $ban_slug     = $get_qv('bds_ban_slug');
    $filter_slug  = $get_qv('bds_filter_slug', '');
    $du_an_slug   = $get_qv('bds_du_an_slug', '');
    $du_an_id     = isset($params['du_an']) ? (int) $params['du_an'] : (isset($_GET['du_an']) ? (int) $_GET['du_an'] : 0);
    $price_raw    = $params['price'] ?? ($_GET['price'] ?? null);
    $area_raw     = $params['area'] ?? ($_GET['area'] ?? null);

    // Fallback sang $_GET nếu query var rỗng
    if (empty($khu_vuc_raw) && isset($_GET['khu_vuc'])) {
        $khu_vuc_raw = $sanitize($_GET['khu_vuc']);
    }
    if (empty($type_raw) && isset($_GET['type'])) {
        $type_raw = $sanitize($_GET['type']);
    }

    // Xử lý type từ ban_slug nếu có (vd: ban-can-ho-chung-cu)
    if (empty($type_raw) && !empty($ban_slug) && function_exists('batdongsan_parse_ban_slug')) {
        $parsed_ban = batdongsan_parse_ban_slug($ban_slug);
        if (!empty($parsed_ban['type_slug'])) {
            $type_raw = $parsed_ban['type_slug'];
        }
        if ($price_raw === null && !empty($parsed_ban['price'])) {
            $price_raw = $parsed_ban['price'];
        }
        if ($area_raw === null && !empty($parsed_ban['area'])) {
            $area_raw = $parsed_ban['area'];
        }
    }

    // Xử lý filter_slug nếu có (vd: gia-tu-3-ty-den-5-ty)
    if (!empty($filter_slug) && function_exists('batdongsan_parse_filter_slug')) {
        $parsed_filter = batdongsan_parse_filter_slug($filter_slug);
        if ($price_raw === null && !empty($parsed_filter['price'])) {
            $price_raw = $parsed_filter['price'];
        }
        if ($area_raw === null && !empty($parsed_filter['area'])) {
            $area_raw = $parsed_filter['area'];
        }
    }

    // Xử lý bds_type_slug đặc biệt 'bat-dong-san' trong rule bat-dong-san-quan-{khuvuc}
    if ($type_raw === 'bat-dong-san') {
        $type_raw = '';
    }

    // 2. Chuyển đổi sang nhãn tiếng Việt
    $area_options = function_exists('batdongsan_get_area_options')
        ? batdongsan_get_area_options(false)
        : [];
    $khu_vuc_name = null;
    if (!empty($khu_vuc_raw) && $khu_vuc_raw !== '0') {
        if (isset($area_options[$khu_vuc_raw])) {
            $khu_vuc_name = $area_options[$khu_vuc_raw];
        } else {
            $khu_vuc_name = ucwords(str_replace('-', ' ', $khu_vuc_raw));
        }
    }

    $type_name = null;
    if (!empty($type_raw) && $type_raw !== '0') {
        $term = null;
        if (is_numeric($type_raw) && function_exists('get_term')) {
            $term = get_term((int) $type_raw, 'loai_hinh_bat_dong_san');
        } elseif (function_exists('get_term_by')) {
            $term = get_term_by('slug', $type_raw, 'loai_hinh_bat_dong_san');
        }
        if ($term && !is_wp_error($term)) {
            $type_name = $term->name;
        } else {
            // Mảng ánh xạ slug chuẩn tiếng Việt diacritics
            $type_slug_map = [
                'can-ho-chung-cu'      => 'Căn Hộ Chung Cư',
                'biet-thu-lien-ke'     => 'Biệt Thự Liền Kề',
                'nha-pho-shophouse'    => 'Nhà Phố Shophouse',
                'dat-nen'              => 'Đất Nền',
                'chung-cu-mini'        => 'Chung Cư Mini',
                'nha-rieng'            => 'Nhà Riêng',
                'mat-bang-kinh-doanh' => 'Mặt Bằng Kinh Doanh',
            ];
            $type_name = $type_slug_map[$type_raw] ?? ucwords(str_replace('-', ' ', (string) $type_raw));
        }
    }

    $du_an_name = null;
    if (!empty($du_an_slug) && function_exists('get_page_by_path')) {
        $post = get_page_by_path($du_an_slug, OBJECT, 'du-an');
        if ($post && !is_wp_error($post)) {
            $du_an_name = $post->post_title;
        }
    } elseif ($du_an_id > 0 && function_exists('get_the_title')) {
        $du_an_name = get_the_title($du_an_id);
    }

    $price_label = null;
    if (!empty($price_raw) && $price_raw !== '0' && function_exists('batdongsan_format_price_range_label')) {
        $price_label = batdongsan_format_price_range_label((string) $price_raw);
    }

    $area_label = null;
    if (!empty($area_raw) && $area_raw !== '0' && function_exists('batdongsan_format_area_range_label')) {
        $area_label = batdongsan_format_area_range_label((string) $area_raw);
    }

    if (!$khu_vuc_name && !$type_name && !$du_an_name && !$price_label && !$area_label && empty($hinh_thuc)) {
        return null;
    }

    // 3. Ghép chuỗi tiêu đề SEO
    $prefix = 'Bất Động Sản';
    if ($hinh_thuc === 'chuyen-nhuong') {
        $prefix = $type_name ? 'Chuyển Nhượng ' . $type_name : 'Hàng Chuyển Nhượng';
    } elseif ($hinh_thuc === 'cho-thue') {
        $prefix = $type_name ? 'Cho Thuê ' . $type_name : 'Cho Thuê Bất Động Sản';
    } elseif ($hinh_thuc === 'mua-ban') {
        $prefix = $type_name ? 'Mua Bán ' . $type_name : 'Bất Động Sản Mua Bán';
    } elseif ($hinh_thuc === 'so-cap') {
        $prefix = $type_name ? 'Mở Bán ' . $type_name : 'Bất Động Sản Sơ Cấp';
    } elseif ($hinh_thuc === 'trung-cap') {
        $prefix = $type_name ? 'Bán ' . $type_name . ' Trung Cấp' : 'Bất Động Sản Trung Cấp';
    } elseif ($hinh_thuc === 'cao-cap') {
        $prefix = $type_name ? 'Bán ' . $type_name . ' Cao Cấp' : 'Bất Động Sản Cao Cấp';
    } elseif ($type_name) {
        $prefix = $type_name;
    }

    $location = '';
    if ($du_an_name) {
        $location = 'Dự Án ' . $du_an_name . ', Hà Nội';
    } elseif ($khu_vuc_name) {
        $location = $khu_vuc_name . ', Hà Nội';
    } else {
        $location = 'Hà Nội';
    }

    $title_parts = [];
    $title_parts[] = $prefix . ' ' . $location;

    if ($price_label) {
        $title_parts[] = 'Giá ' . $price_label;
    }
    if ($area_label) {
        $title_parts[] = 'DT ' . $area_label;
    }

    return implode(' ', $title_parts);
}

/**
 * Filter document_title_parts: Tự động cập nhật thẻ <title> cho trang bộ lọc & archive SEO.
 */
add_filter('document_title_parts', 'batdongsan_filter_document_title_parts', 20);
function batdongsan_filter_document_title_parts(array $title): array {
    // 1. Loại bỏ triệt để từ "Archive" / "Archives" / "Lưu trữ" từ WordPress core
    if (isset($title['title']) && is_string($title['title'])) {
        $title['title'] = trim(str_ireplace([' Archive', ' Archives', 'Lưu trữ: ', 'Lưu trữ '], '', $title['title']));
    }

    // 2. Dự Án Filter & Archive
    $is_du_an = (function_exists('is_post_type_archive') && is_post_type_archive('du-an'))
        || (function_exists('is_page') && (is_page('du-an-bat-dong-san') || is_page('du-an')))
        || (function_exists('is_page_template') && is_page_template('page-du-an.php'))
        || (function_exists('get_query_var') && (
            get_query_var('du_an_khu_vuc') ||
            get_query_var('du_an_type_slug') ||
            get_query_var('du_an_price_slug') ||
            get_query_var('du_an_slug_1') ||
            get_query_var('post_type') === 'du-an'
        ));

    if ($is_du_an) {
        if (function_exists('batdongsan_get_du_an_filter_seo_title')) {
            $dynamic_title = batdongsan_get_du_an_filter_seo_title();
            if (!empty($dynamic_title)) {
                $title['title'] = $dynamic_title;
                return $title;
            }
        }
        $title['title'] = 'Dự Án Bất Động Sản Hà Nội - Bảng Giá 2026';
        return $title;
    }

    // 3. BĐS Filter & Archive
    $is_bds = (function_exists('is_post_type_archive') && is_post_type_archive('bat-dong-san'))
        || (function_exists('is_page') && is_page('bat-dong-san'))
        || (function_exists('is_page_template') && is_page_template('page-bat-dong-san.php'))
        || (function_exists('get_query_var') && (
            get_query_var('bds_ban_slug') ||
            get_query_var('bds_khu_vuc') ||
            get_query_var('bds_filter_slug') ||
            get_query_var('bds_type_slug') ||
            get_query_var('post_type') === 'bat-dong-san'
        ));

    if ($is_bds) {
        if (function_exists('batdongsan_get_bds_filter_seo_title')) {
            $dynamic_title = batdongsan_get_bds_filter_seo_title();
            if (!empty($dynamic_title)) {
                $title['title'] = $dynamic_title;
                return $title;
            }
        }
        $title['title'] = 'Mua Bán & Cho Thuê Bất Động Sản Hà Nội';
        return $title;
    }

    return $title;
}

/**
 * Filter pre_get_document_title: Hỗ trợ SEO Plugins bypass nếu cần.
 */
add_filter('pre_get_document_title', 'batdongsan_filter_pre_get_document_title', 20);
function batdongsan_filter_pre_get_document_title(string $title): string {
    if (!empty($title)) {
        if (stripos($title, 'Archive') !== false) {
            $title = trim(str_ireplace([' Archive', ' Archives', 'Lưu trữ: ', 'Lưu trữ '], '', $title));
        }
        return $title;
    }

    if (function_exists('batdongsan_get_seo_master_title')) {
        $seo_master = batdongsan_get_seo_master_title();
        if (!empty($seo_master)) {
            return $seo_master;
        }
    }

    return $title;
}

/**
 * Filter post_type_archive_title: Ngăn WordPress core hiển thị từ 'Archive' trong tiêu đề CPT.
 */
add_filter('post_type_archive_title', 'batdongsan_filter_post_type_archive_title', 10, 2);
function batdongsan_filter_post_type_archive_title(string $title, string $post_type): string {
    if ($post_type === 'du-an') {
        return 'Dự Án Bất Động Sản Hà Nội';
    }
    if ($post_type === 'bat-dong-san') {
        return 'Mua Bán & Cho Thuê Bất Động Sản Hà Nội';
    }
    return trim(str_ireplace([' Archive', ' Archives', 'Lưu trữ: ', 'Lưu trữ '], '', $title));
}

