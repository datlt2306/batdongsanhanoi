<?php
/**
 * Main Filter Query Service Facade.
 *
 * Provides a unified entry point for executing database-level filtered queries
 * and generating standardized AJAX JSON response payloads for BĐS and Projects.
 *
 * @package BatDongSan
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class BDS_Filter_Query_Service {

    /**
     * Execute filtered query for Bất Động Sản listings.
     *
     * @param array<string, mixed> $params
     * @return WP_Query
     */
    public static function query_listings(array $params): WP_Query {
        $builder = new BDS_Listing_Query_Builder();
        return $builder->parse_params($params)->execute();
    }

    /**
     * Execute filtered query for Development Projects (Dự Án).
     *
     * @param array<string, mixed> $params
     * @return WP_Query
     */
    public static function query_projects(array $params): WP_Query {
        $builder = new BDS_Project_Query_Builder();
        return $builder->parse_params($params)->execute();
    }

    /**
     * Get compiled WP_Query arguments for listings without executing query.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public static function get_listing_query_args(array $params): array {
        $builder = new BDS_Listing_Query_Builder();
        return $builder->parse_params($params)->build_query_args();
    }

    /**
     * Get compiled WP_Query arguments for projects without executing query.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public static function get_project_query_args(array $params): array {
        $builder = new BDS_Project_Query_Builder();
        return $builder->parse_params($params)->build_query_args();
    }

    /**
     * Format standardized AJAX response payload for BĐS listings.
     * Preserves 100% of existing JSON schema expected by js/main.js and tests.
     *
     * @param WP_Query             $query
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public static function format_listings_ajax_response(WP_Query $query, array $params): array {
        $paged        = max(1, (int) ($params['paged'] ?? 1));
        $total        = (int) $query->found_posts;
        $total_pages  = max(1, (int) $query->max_num_pages);
        $current_page = $paged;
        $posts        = $query->posts;

        // 1. Render Card Grid HTML
        ob_start();
        if (!empty($posts)) {
            if (function_exists('update_post_caches')) {
                update_post_caches($posts, 'bat-dong-san', true, true);
            }
            if (function_exists('batdongsan_prime_listings_cache')) {
                batdongsan_prime_listings_cache($posts);
            }
            echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="bds-listings-grid">';
            global $post;
            foreach ($posts as $value) {
                $post_obj = is_object($value) ? $value : get_post($value);
                $value    = $post_obj;
                $post     = $post_obj;
                $GLOBALS['post'] = $post_obj;
                if (function_exists('setup_postdata')) {
                    setup_postdata($post_obj);
                }
                $item_template = get_template_directory() . '/paths/bat-dong-san-item.php';
                if (file_exists($item_template)) {
                    (function ($value, $item_template) {
                        include $item_template;
                    })($value, $item_template);
                } else {
                    echo '<div class="listing-card"><h3>' . esc_html(get_the_title($post_obj)) . '</h3></div>';
                }
            }
            if (function_exists('wp_reset_postdata')) {
                wp_reset_postdata();
            }
            echo '</div>';
        } else {
            echo '<div class="text-center py-16 px-5 bg-white rounded-2xl border border-gray-100 shadow-sm">';
            if (function_exists('batdongsan_icon')) {
                echo batdongsan_icon('search', ['width' => 56, 'height' => 56, 'class' => 'mx-auto mb-4 text-gray-300']);
            }
            echo '<h3 class="text-lg font-semibold text-gray-800 mb-1">Không tìm thấy bất động sản nào</h3>';
            echo '<p class="text-gray-500 text-sm mb-5">Thử nới lỏng khoảng giá, diện tích hoặc đổi khu vực tìm kiếm.</p>';
            echo '<button type="button" data-clear-all-filters class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary-hover transition shadow-button">';
            echo 'Xóa bộ lọc và xem tất cả';
            echo '</button>';
            echo '</div>';
        }
        $html = ob_get_clean();

        // 2. Render Pagination HTML
        $price_key = $params['price'] ?? '';
        $area_key  = $params['area'] ?? '';
        $base_url  = home_url('/bat-dong-san/');
        if (!empty($params['du_an']) && function_exists('batdongsan_bds_du_an_base_url')) {
            $base_url = batdongsan_bds_du_an_base_url((int) $params['du_an']);
        }

        $query_params = array_filter([
            'hinh_thuc'        => !empty($params['hinh_thuc']) ? $params['hinh_thuc'] : null,
            'type'             => !empty($params['type']) ? $params['type'] : null,
            'khu_vuc'          => !empty($params['khu_vuc']) ? $params['khu_vuc'] : null,
            'status'           => !empty($params['status']) ? $params['status'] : null,
            'so_phong_ngu'     => !empty($params['so_phong_ngu']) ? $params['so_phong_ngu'] : null,
            'so_phong_ve_sinh' => !empty($params['so_phong_ve_sinh']) ? $params['so_phong_ve_sinh'] : null,
            'huong_nha'        => !empty($params['huong_nha']) ? $params['huong_nha'] : null,
            'phap_ly'          => !empty($params['phap_ly']) ? $params['phap_ly'] : null,
            'du_an'            => !empty($params['du_an']) ? $params['du_an'] : null,
            'project-name'     => !empty($params['name']) ? $params['name'] : (!empty($params['keyword']) ? $params['keyword'] : null),
            'order'            => (!empty($params['order']) && (string) $params['order'] !== '1') ? $params['order'] : null,
        ]);

        ob_start();
        if ($total_pages > 1) {
            echo '<nav class="mt-8 flex justify-center" aria-label="Phân trang"><ul class="flex items-center gap-2 list-none p-0 m-0">';
            if ($current_page > 1) {
                $prev_url = function_exists('batdongsan_bds_filter_url')
                    ? batdongsan_bds_filter_url($base_url, $price_key, $area_key, $query_params, $current_page - 1)
                    : '#';
                echo '<li><a href="' . esc_url($prev_url) . '" data-page="' . ($current_page - 1) . '" class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-lg border border-border text-charcoal no-underline text-sm font-medium hover:bg-stone hover:border-primary/40 transition" aria-label="Trang trước">←</a></li>';
            }
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i === $current_page) {
                    echo '<li><span class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-lg bg-primary text-white text-sm font-semibold shadow-button" aria-current="page">' . $i . '</span></li>';
                } else {
                    $page_url = function_exists('batdongsan_bds_filter_url')
                        ? batdongsan_bds_filter_url($base_url, $price_key, $area_key, $query_params, $i)
                        : '#';
                    echo '<li><a href="' . esc_url($page_url) . '" data-page="' . $i . '" class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-lg border border-border text-charcoal no-underline text-sm font-medium hover:bg-stone hover:border-primary/40 transition">' . $i . '</a></li>';
                }
            }
            if ($current_page < $total_pages) {
                $next_url = function_exists('batdongsan_bds_filter_url')
                    ? batdongsan_bds_filter_url($base_url, $price_key, $area_key, $query_params, $current_page + 1)
                    : '#';
                echo '<li><a href="' . esc_url($next_url) . '" data-page="' . ($current_page + 1) . '" class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-lg border border-border text-charcoal no-underline text-sm font-medium hover:bg-stone hover:border-primary/40 transition" aria-label="Trang sau">→</a></li>';
            }
            echo '</ul></nav>';
        }
        $pagination_html = ob_get_clean();

        // 3. Compute Active Filter Chips
        $active_filters = [];
        $area_options = function_exists('batdongsan_get_area_options') ? batdongsan_get_area_options(false) : [];

        if (!empty($params['hinh_thuc']) && $params['hinh_thuc'] !== '0') {
            $ht_labels = [
                'chuyen-nhuong' => 'Hàng chuyển nhượng',
                'cho-thue'      => 'Cho thuê',
                'mua-ban'       => 'Mua / Bán',
                'so-cap'        => 'Sơ cấp',
                'trung-cap'     => 'Trung cấp',
                'cao-cap'       => 'Cao cấp',
            ];
            if (isset($ht_labels[$params['hinh_thuc']])) {
                $active_filters[] = ['key' => 'hinh_thuc', 'label' => 'Hình thức: ' . $ht_labels[$params['hinh_thuc']]];
            }
        }
        if (!empty($params['khu_vuc']) && isset($area_options[$params['khu_vuc']])) {
            $active_filters[] = ['key' => 'khu_vuc', 'label' => 'Khu vực: ' . $area_options[$params['khu_vuc']]];
        }
        if (!empty($params['type']) && $params['type'] !== '0') {
            $lh_term = is_numeric($params['type']) ? get_term((int) $params['type'], 'loai_hinh_bat_dong_san') : get_term_by('slug', (string) $params['type'], 'loai_hinh_bat_dong_san');
            if ($lh_term && !is_wp_error($lh_term)) {
                $active_filters[] = ['key' => 'type', 'label' => 'Loại hình: ' . $lh_term->name];
            }
        }
        if (!empty($price_key) && $price_key !== '0' && function_exists('batdongsan_format_price_range_label')) {
            $active_filters[] = ['key' => 'price', 'label' => 'Giá: ' . batdongsan_format_price_range_label($price_key)];
        }
        if (!empty($area_key) && $area_key !== '0' && function_exists('batdongsan_format_area_range_label')) {
            $active_filters[] = ['key' => 'area', 'label' => 'Diện tích: ' . batdongsan_format_area_range_label($area_key)];
        }
        if (!empty($params['du_an'])) {
            $active_filters[] = ['key' => 'du_an', 'label' => 'Dự án: ' . get_the_title((int) $params['du_an'])];
        }
        $name_val = $params['name'] ?? ($params['keyword'] ?? '');
        if (!empty($name_val)) {
            $active_filters[] = ['key' => 'project-name', 'label' => 'Từ khóa: "' . $name_val . '"'];
        }
        if (!empty($params['so_phong_ngu']) && $params['so_phong_ngu'] !== '0') {
            $active_filters[] = ['key' => 'so_phong_ngu', 'label' => 'Phòng ngủ: ' . $params['so_phong_ngu'] . '+'];
        }
        if (!empty($params['so_phong_ve_sinh']) && $params['so_phong_ve_sinh'] !== '0') {
            $active_filters[] = ['key' => 'so_phong_ve_sinh', 'label' => 'Vệ sinh: ' . $params['so_phong_ve_sinh'] . '+'];
        }
        if (!empty($params['huong_nha']) && $params['huong_nha'] !== '0' && $params['huong_nha'] !== 'all') {
            $hn_labels = [
                'dong' => 'Đông', 'tay' => 'Tây', 'nam' => 'Nam', 'bac' => 'Bắc',
                'dong-nam' => 'Đông Nam', 'dong_nam' => 'Đông Nam',
                'dong-bac' => 'Đông Bắc', 'dong_bac' => 'Đông Bắc',
                'tay-nam' => 'Tây Nam', 'tay_nam' => 'Tây Nam',
                'tay-bac' => 'Tây Bắc', 'tay_bac' => 'Tây Bắc'
            ];
            $hn_val = (string) $params['huong_nha'];
            $hn_key = str_replace('-', '_', $hn_val);
            $active_filters[] = ['key' => 'huong_nha', 'label' => 'Hướng: ' . ($hn_labels[$hn_val] ?? ($hn_labels[$hn_key] ?? $hn_val))];
        }
        if (!empty($params['phap_ly']) && $params['phap_ly'] !== '0' && $params['phap_ly'] !== 'all') {
            $pl_labels = [
                'so-do' => 'Sổ đỏ / Sổ hồng', 'so_do' => 'Sổ đỏ / Sổ hồng',
                'so_hong' => 'Sổ hồng', 'so_do_so_hong' => 'Sổ đỏ / Sổ hồng',
                'hop-dong-mua-ban' => 'Hợp đồng mua bán', 'hop_dong_mua_ban' => 'Hợp đồng mua bán', 'hop_dong' => 'Hợp đồng mua bán',
                'dang_ky_quyen_su_dung_dat' => 'Đăng ký QSD đất',
                'giay_chung_nhan' => 'Giấy chứng nhận',
                'dang-cho-so' => 'Đang chờ sổ', 'dang_cho_so' => 'Đang chờ sổ',
                'khac' => 'Khác',
            ];
            $pl_val = (string) $params['phap_ly'];
            $pl_key = str_replace('-', '_', $pl_val);
            $active_filters[] = ['key' => 'phap_ly', 'label' => 'Pháp lý: ' . ($pl_labels[$pl_val] ?? ($pl_labels[$pl_key] ?? $pl_val))];
        }

        // 4. Compute Dynamic SEO Title
        $seo_title = function_exists('batdongsan_get_bds_filter_seo_title')
            ? batdongsan_get_bds_filter_seo_title([
                'hinh_thuc' => $params['hinh_thuc'] ?? '',
                'type'      => $params['type'] ?? '',
                'khu_vuc'   => $params['khu_vuc'] ?? '',
                'price'     => $price_key,
                'area'      => $area_key,
                'du_an'     => $params['du_an'] ?? 0,
                'name'      => $name_val,
            ])
            : null;

        // 5. Compute SEO URL
        $filtered_url = function_exists('batdongsan_bds_filter_url')
            ? batdongsan_bds_filter_url($base_url, $price_key, $area_key, $query_params, $current_page)
            : $base_url;

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
            $hinh_thuc_val = $params['hinh_thuc'] ?? '';
            $heading_title = $ht_default_headings[$hinh_thuc_val] ?? 'Bất động sản';
        }

        return [
            'html'           => $html,
            'pagination'     => $pagination_html,
            'total'          => $total,
            'count'          => $total,
            'found_posts'    => $total,
            'total_pages'    => $total_pages,
            'max_num_pages'  => $total_pages,
            'current_page'   => $current_page,
            'url'            => $filtered_url,
            'title'          => $seo_title ? ($seo_title . ' | ' . (function_exists('get_bloginfo') ? get_bloginfo('name') : 'Bất Động Sản Hà Nội')) : null,
            'heading_title'  => $heading_title,
            'active_filters' => $active_filters,
        ];
    }

    /**
     * Format standardized AJAX response payload for Projects.
     * Preserves 100% of existing JSON schema expected by js/main.js and tests.
     *
     * @param WP_Query             $query
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public static function format_projects_ajax_response(WP_Query $query, array $params): array {
        $paged        = max(1, (int) ($params['paged'] ?? 1));
        $total        = (int) $query->found_posts;
        $total_pages  = max(1, (int) $query->max_num_pages);
        $current_page = $paged;
        $posts        = $query->posts;

        // 1. Render Project Cards HTML
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
        $html = ob_get_clean();

        // 2. Render Search Results Breadcrumb Summary
        $name         = $params['name'] ?? ($params['project-name'] ?? ($params['keyword'] ?? ''));
        $area         = $params['area'] ?? ($params['khu_vuc'] ?? '');
        $price        = $params['price'] ?? '';
        $type         = $params['type'] ?? '';
        $status       = $params['status'] ?? '';
        $area_options = function_exists('batdongsan_get_area_options') ? batdongsan_get_area_options(true) : [];

        $has_criteria = (!empty($name) || (!empty($type) && $type !== '0') || (!empty($area) && isset($area_options[$area])) || (!empty($status) && $status !== '0' && $status !== 'all') || ($price !== null && $price !== '' && $price !== '0'));
        $title_heading = $has_criteria ? 'Kết quả tìm kiếm phù hợp các tiêu chí:' : 'Tất cả dự án';

        $search_results_html = '';
        if ($has_criteria) {
            ob_start();
            $PriceDisplay = function_exists('batdongsan_get_price_ranges') ? batdongsan_get_price_ranges() : [];
            echo '<ul class="page-content__search-results">';
            if ($name !== '') {
                echo '<li>Tên dự án theo từ khóa: <strong>' . esc_html($name) . '</strong></li>';
            }
            if ($area !== '' && isset($area_options[$area])) {
                echo '<li>Khu vực: <strong>' . esc_html($area_options[$area]) . '</strong></li>';
            }
            if ($price !== '' && $price !== '0' && isset($PriceDisplay[$price])) {
                echo '<li>Giá sản phẩm: <strong>' . esc_html($PriceDisplay[$price]) . '</strong></li>';
            }
            if ($type !== '' && $type !== '0') {
                $type_term = get_term((int) $type, 'loai_hinh_du_an');
                if ($type_term && !is_wp_error($type_term)) {
                    echo '<li>Loại dự án: <strong>' . esc_html($type_term->name) . '</strong></li>';
                }
            }
            if ($status !== '' && $status !== '0') {
                $raw_key = function_exists('sanitize_key') ? sanitize_key($status) : strtolower((string) preg_replace('/[^a-z0-9_\-]/i', '', $status));
                $status_norm_key = str_replace('-', '_', $raw_key);
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
                    ?? ($status_display_options[$status] ?? $status);
                echo '<li>Trạng thái: <strong>' . esc_html($status_label) . '</strong></li>';
            }
            echo '</ul>';
            $search_results_html = ob_get_clean();
        }

        // 3. Render Pagination HTML
        ob_start();
        if ($total_pages > 1) {
            echo '<nav class="mt-8 flex justify-center" aria-label="Phân trang"><ul class="flex items-center gap-2 list-none p-0 m-0">';
            if ($current_page > 1) {
                echo '<li><a href="#" data-page="' . ($current_page - 1) . '" class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-lg border border-gray-200 text-gray-700 no-underline text-sm font-medium hover:bg-gray-50" aria-label="Trang trước">←</a></li>';
            }
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i === $current_page) {
                    echo '<li><span class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-lg bg-primary text-white text-sm font-semibold shadow-button" aria-current="page">' . $i . '</span></li>';
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

        // 4. Compute Project SEO URL
        $base_url  = home_url('/du-an/');
        $type_term = ($type !== '' && $type !== '0') ? get_term((int) $type, 'loai_hinh_du_an') : null;
        $type_slug = ($type_term && !is_wp_error($type_term)) ? $type_term->slug : null;

        if ($type_slug && $area !== '') {
            $url = home_url('/du-an-' . $type_slug . '-' . $area . '/');
        } elseif ($type_slug) {
            $url = home_url('/du-an-' . $type_slug . '/');
        } elseif ($area !== '') {
            $url = home_url('/du-an-' . $area . '/');
        } else {
            $url = $base_url;
        }

        $query_params = [];
        if ($current_page > 1) {
            $query_params['paged'] = $current_page;
        }
        if ($price !== '' && $price !== '0') {
            $query_params['price'] = $price;
        }
        if ($status !== '' && $status !== '0') {
            $query_params['status'] = $status;
        }
        if ($name !== '') {
            $query_params['project-name'] = $name;
        }
        if (!empty($params['order']) && $params['order'] !== 'date_desc' && (string) $params['order'] !== '1') {
            $query_params['order'] = $params['order'];
        }
        if (!empty($query_params)) {
            $url = add_query_arg($query_params, $url);
        }

        return [
            'html'          => $html,
            'pagination'    => $pagination_html,
            'found_posts'   => $total,
            'total'         => $total,
            'total_pages'   => $total_pages,
            'current_page'  => $current_page,
            'title'         => $title_heading,
            'searchResults' => $search_results_html,
            'url'           => $url,
            'pretty_url'    => $url,
        ];
    }
}
