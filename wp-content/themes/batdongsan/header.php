<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>

<body <?php body_class('m-0 font-sans bg-canvas text-charcoal text-base max-md:text-sm antialiased overflow-x-clip selection:bg-primary/20 selection:text-primary'); ?>>
    <?php wp_body_open(); ?>
    <header class="header sticky top-0 z-[1001] bg-white/80 backdrop-blur-xl border-b border-black/[0.06]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Desktop: logo + nav + CTA (lg: >= 1024px) -->
            <div class="hidden lg:flex items-center justify-between py-3 gap-4 xl:gap-8">
                <div class="shrink-0 flex items-center">
                    <?php if (has_custom_logo()) : ?>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center shrink-0" rel="home" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
                            <?php
                            $custom_logo_id = get_theme_mod('custom_logo');
                            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
                            $logo_alt = get_post_meta($custom_logo_id, '_wp_attachment_image_alt', true) ?: get_bloginfo('name');
                            ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($logo_alt); ?>" width="232" height="44" class="custom-logo h-10 lg:h-11 w-auto max-h-[44px] object-contain skip-lazy" style="aspect-ratio: 1920/363;" loading="eager" fetchpriority="high" data-no-lazy="1" data-skip-lazy="1">
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center gap-2 font-bold text-xl tracking-tight text-charcoal hover:text-primary transition-colors" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
                            <span><?php bloginfo('name'); ?></span>
                        </a>
                    <?php endif; ?>
                </div>

                <nav class="flex items-center justify-center shrink-0 px-2 xl:px-4" aria-label="Điều hướng chính">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'primary',
                        'container' => false,
                        'menu_class' => 'nav__list flex items-center list-none m-0 p-0 text-[14px] xl:text-[15px] font-medium text-charcoal/80 gap-4 xl:gap-6 whitespace-nowrap',
                        'fallback_cb' => '__return_empty_string',
                        'depth' => 3,
                    ]);
                    ?>
                </nav>

                <?php
                $site_hotline = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_hotline', '0934.69.9191') : '0934.69.9191';
                $site_hotline_raw = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_hotline_raw', '0934699191') : '0934699191';
                $site_zalo = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_zalo', '0934699191') : '0934699191';
                ?>
                <div class="shrink-0 flex items-center gap-2.5 xl:gap-3">
                    <a href="tel:<?php echo esc_attr($site_hotline_raw); ?>" class="hidden xl:inline-flex items-center gap-2 text-xs font-medium text-charcoal/80 hover:text-primary transition-colors py-1.5 px-3 rounded-full border border-black/[0.06] bg-black/[0.02]" title="Hotline hỗ trợ">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-charcoal-muted">Hotline:</span>
                        <span class="font-semibold text-charcoal"><?php echo esc_html($site_hotline); ?></span>
                    </a>
                    <a href="https://zalo.me/<?php echo esc_attr($site_zalo); ?>" target="_blank" rel="noopener noreferrer" class="header__cta inline-flex items-center justify-center gap-2 px-4 py-2 border border-primary/35 text-primary bg-primary/[0.04] hover:bg-primary hover:text-white text-sm font-medium rounded-full active:scale-[0.98] transition-all duration-200" aria-label="Liên hệ tư vấn Zalo">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 8.5-8.5 8.5 8.5 0 0 1 8.5 8.5z"/>
                        </svg>
                        <span>Tư vấn ngay</span>
                    </a>
                </div>
            </div>

            <!-- Mobile: logo + hamburger (lg:hidden < 1024px) -->
            <div class="flex lg:hidden items-center justify-between py-2 sm:py-2.5 gap-3">
                <div class="shrink-0 flex items-center">
                    <?php if (has_custom_logo()) : ?>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center shrink-0 active:opacity-80 transition-opacity" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
                            <?php
                            $custom_logo_id = get_theme_mod('custom_logo');
                            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
                            $logo_alt = get_post_meta($custom_logo_id, '_wp_attachment_image_alt', true) ?: get_bloginfo('name');
                            ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($logo_alt); ?>" width="232" height="44" class="h-9 xs:h-10 sm:h-11 w-auto max-h-[44px] object-contain skip-lazy" style="aspect-ratio: 1920/363;" loading="eager" fetchpriority="high" data-no-lazy="1" data-skip-lazy="1">
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center font-bold text-xl sm:text-2xl text-charcoal tracking-tight shrink-0" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
                            <?php bloginfo('name'); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <button class="header__menu-toggle w-11 h-11 flex items-center justify-center bg-black/[0.04] hover:bg-black/[0.08] active:scale-90 transition-all border border-black/[0.06] cursor-pointer shrink-0 rounded-full text-charcoal shadow-sm" data-drawer-toggle="mobile-drawer" aria-label="Mở menu" aria-expanded="false">
                    <svg class="w-5 h-5 text-charcoal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="7" x2="20" y2="7"></line>
                        <line x1="4" y1="12" x2="20" y2="12"></line>
                        <line x1="4" y1="17" x2="20" y2="17"></line>
                    </svg>
                </button>
            </div>

            <?php
            $is_bds_page = is_post_type_archive('bat-dong-san')
                || is_page_template('page-bat-dong-san.php')
                || is_page('bat-dong-san');
            $is_du_an_page = is_post_type_archive('du-an')
                || is_page_template('page-du-an.php')
                || is_page('du-an');
            $is_bds_single = is_singular(['bat-dong-san', 'hang-chuyen-nhuong', 'nha-dat-ban']);
            $is_du_an_single = is_singular(['du-an', 'phan-khu']);
            $is_bds_taxonomy = is_tax('loai_hinh_bat_dong_san') || is_tax('hinh-thuc-bds') || is_tax('danh-muc-nha-dat-ban') || is_tax('danh-muc-hang-chuyen-nhuong');
            $is_du_an_taxonomy = is_tax('loai_hinh_du_an') || is_tax('loai-hinh-du-an');

            $show_header_filter = $is_bds_page || $is_du_an_page || $is_bds_single || $is_bds_taxonomy || $is_du_an_taxonomy;
            ?>
            <?php if ($show_header_filter) : ?>
                <!-- Mobile Filter Bar: BĐS / Dự án và các trang con -->
                <div class="block lg:hidden pb-2.5 pt-1 border-t border-black/[0.04]">
                    <?php

                    if ($is_bds_page || $is_bds_single || $is_bds_taxonomy) {
                        // URL trang Bất động sản (sẽ cập nhật nếu có du_an từ pretty URL)
                        $bds_page_url = is_post_type_archive('bat-dong-san')
                            ? get_post_type_archive_link('bat-dong-san')
                            : home_url('/bat-dong-san');

                        // Đọc query hiện tại và pretty URL để sync trạng thái filter (header chạy trước page, cần parse giống page-bat-dong-san)
                        $bds_name   = isset($_GET['project-name']) ? trim(sanitize_text_field(wp_unslash($_GET['project-name']))) : '';
                        $bds_status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '0';
                        $bds_du_an  = isset($_GET['du_an']) ? absint($_GET['du_an']) : 0;
                        $bds_hinh_thuc = isset($_GET['hinh_thuc']) ? sanitize_text_field(wp_unslash($_GET['hinh_thuc'])) : '';

                        $bds_ban_slug   = get_query_var('bds_ban_slug', '');
                        $bds_type_slug  = get_query_var('bds_type_slug', '');
                        $bds_khu_vuc_qv = get_query_var('bds_khu_vuc', '');
                        $bds_filter_slug = get_query_var('bds_filter_slug', '');
                        $type_param     = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : null;
                        $khu_vuc_param  = isset($_GET['khu_vuc']) ? sanitize_text_field(wp_unslash($_GET['khu_vuc'])) : null;

                        $bds_type    = $type_param ?: '0';
                        $bds_khu_vuc = $khu_vuc_param ?: '';
                        $bds_price   = isset($_GET['price']) ? sanitize_text_field(wp_unslash($_GET['price'])) : '';
                        $bds_area    = isset($_GET['area']) ? sanitize_text_field(wp_unslash($_GET['area'])) : '';

                        if ($bds_type_slug && $bds_khu_vuc_qv && !$type_param) {
                            $type_id = function_exists('batdongsan_resolve_loai_hinh_slug_to_id') ? batdongsan_resolve_loai_hinh_slug_to_id($bds_type_slug) : 0;
                            if ($type_id > 0) {
                                $bds_type    = (string) $type_id;
                                $bds_khu_vuc = $bds_khu_vuc_qv;
                            }
                            if ($bds_filter_slug !== '' && function_exists('batdongsan_parse_filter_slug')) {
                                $parsed    = batdongsan_parse_filter_slug($bds_filter_slug);
                                $bds_price = $parsed['price'] ?? '';
                                $bds_area  = $parsed['area'] ?? '';
                            }
                        } elseif ($bds_ban_slug && !$type_param && function_exists('batdongsan_parse_ban_slug')) {
                            $ban_parsed = batdongsan_parse_ban_slug($bds_ban_slug);
                            if (!empty($ban_parsed['type_slug']) && function_exists('batdongsan_resolve_loai_hinh_slug_to_id')) {
                                $type_id = batdongsan_resolve_loai_hinh_slug_to_id($ban_parsed['type_slug']);
                                if ($type_id > 0) {
                                    $bds_type = (string) $type_id;
                                }
                            }
                            if (isset($ban_parsed['price']) && $ban_parsed['price'] !== null) {
                                $bds_price = $ban_parsed['price'];
                            }
                            if (isset($ban_parsed['area']) && $ban_parsed['area'] !== null) {
                                $bds_area = $ban_parsed['area'];
                            }
                        }
                        if ($bds_khu_vuc_qv && !$khu_vuc_param) {
                            $bds_khu_vuc = $bds_khu_vuc_qv;
                        }
                        if ($bds_filter_slug !== '' && $bds_price === '' && $bds_area === '' && function_exists('batdongsan_parse_filter_slug')) {
                            $parsed    = batdongsan_parse_filter_slug($bds_filter_slug);
                            $bds_price = $parsed['price'] ?? '';
                            $bds_area  = $parsed['area'] ?? '';
                        }

                        // Parse du-an từ pretty URL (bat-dong-san/du-an-{slug}) khi không có trong GET
                        if ($bds_du_an === 0 && (!isset($_GET['du_an']) || $_GET['du_an'] !== '0')) {
                            $bds_du_an_slug = get_query_var('bds_du_an_slug', '');
                            if ($bds_du_an_slug !== '') {
                                $bds_du_an_post = get_page_by_path($bds_du_an_slug, OBJECT, 'du-an');
                                if ($bds_du_an_post && !is_wp_error($bds_du_an_post)) {
                                    $bds_du_an = (int) $bds_du_an_post->ID;
                                }
                            }
                        }
                        if ($bds_du_an > 0 && function_exists('batdongsan_bds_du_an_base_url')) {
                            $bds_page_url = batdongsan_bds_du_an_base_url($bds_du_an);
                        }

                        // Danh sách khu vực gốc, filter theo sản phẩm thực có
                        $bds_khu_vuc_options_raw = [
                            ''            => 'Toàn Hà Nội',
                            'gia-lam'     => 'Gia Lâm',
                            'nam-tu-liem' => 'Nam Từ Liêm',
                            'hai-ba-trung' => 'Hai Bà Trưng',
                            'tay-ho'      => 'Tây Hồ',
                            'long-bien'   => 'Long Biên',
                            'cau-giay'    => 'Cầu Giấy',
                            'dong-da'     => 'Đống Đa',
                            'hoang-mai'   => 'Hoàng Mai',
                            'thanh-xuan'  => 'Thanh Xuân',
                            'ha-dong'     => 'Hà Đông',
                            'ba-dinh'     => 'Ba Đình',
                            'hoan-kiem'   => 'Hoàn Kiếm',
                        ];

                        $bds_posts_for_options = function_exists('batdongsan_get_bds_posts_for_filter_options')
                            ? batdongsan_get_bds_posts_for_filter_options([
                                'du_an'        => $bds_du_an,
                                'project-name' => $bds_name,
                                'status'       => ($bds_status && $bds_status !== '0') ? $bds_status : '',
                            ])
                            : [];
                        $bds_khu_vuc_options = function_exists('batdongsan_filter_khu_vuc_options_by_posts')
                            ? batdongsan_filter_khu_vuc_options_by_posts($bds_khu_vuc_options_raw, $bds_posts_for_options)
                            : $bds_khu_vuc_options_raw;
                        if ($bds_khu_vuc && isset($bds_khu_vuc_options_raw[$bds_khu_vuc]) && !isset($bds_khu_vuc_options[$bds_khu_vuc])) {
                            $bds_khu_vuc_options[$bds_khu_vuc] = $bds_khu_vuc_options_raw[$bds_khu_vuc];
                        }

                        $bds_type_terms = [];
                        if (!empty($bds_posts_for_options) && function_exists('batdongsan_filter_bds_type_options_by_posts')) {
                            $bds_type_terms = batdongsan_filter_bds_type_options_by_posts($bds_posts_for_options);
                        }

                        $bds_price_ranges = (function_exists('batdongsan_build_price_ranges_from_posts') && !empty($bds_posts_for_options))
                            ? batdongsan_build_price_ranges_from_posts($bds_posts_for_options)
                            : (function_exists('batdongsan_get_bds_price_ranges') ? batdongsan_get_bds_price_ranges() : ['0' => 'Tất cả']);
                        $bds_area_ranges = (function_exists('batdongsan_build_area_ranges_from_posts') && !empty($bds_posts_for_options))
                            ? batdongsan_build_area_ranges_from_posts($bds_posts_for_options)
                            : [];

                        $bds_price_bounds = [0, 60000];
                        if (!empty($bds_posts_for_options) && function_exists('batdongsan_get_total_price_million')) {
                            $bds_actual_prices = [];
                            foreach ($bds_posts_for_options as $p) {
                                if ($p instanceof WP_Post) {
                                    $v = batdongsan_get_total_price_million($p->ID);
                                    if ($v !== null && $v > 0) {
                                        $bds_actual_prices[] = (float) $v;
                                    }
                                }
                            }
                            if (!empty($bds_actual_prices)) {
                                $bds_price_bounds = [0, (int) ceil(max($bds_actual_prices))];
                            }
                        }

                        $bds_area_bounds = [0, 500];

                        get_template_part('template-parts/filter-bar-horizontal', null, [
                            'layout'            => 'mobile_header',
                            'form_action'       => $bds_page_url,
                            'placeholder'       => 'Tìm kiếm bất động sản...',
                            'current_name'      => $bds_name,
                            'current_price'     => $bds_price,
                            'current_type'      => $bds_type ?: '0',
                            'current_khu_vuc'   => $bds_khu_vuc,
                            'current_hinh_thuc' => $bds_hinh_thuc,
                            'price_ranges'      => $bds_price_ranges,
                            'price_bounds'      => $bds_price_bounds,
                            'area_options'      => $bds_khu_vuc_options,
                            'type_terms'        => $bds_type_terms,
                            'show_status'       => false,
                            'show_area_range'   => false,
                        ]);
                    } elseif ($is_du_an_page || $is_du_an_single || $is_du_an_taxonomy) {
                        // URL trang Dự án
                        $du_an_page_url = is_post_type_archive('du-an')
                            ? get_post_type_archive_link('du-an')
                            : home_url('/du-an');

                        // Đọc query hiện tại để sync trạng thái filter
                        $duan_name    = isset($_GET['project-name']) ? trim(sanitize_text_field(wp_unslash($_GET['project-name']))) : '';
                        $duan_price   = isset($_GET['price']) ? sanitize_text_field(wp_unslash($_GET['price'])) : '';
                        $duan_type    = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '0';
                        $duan_status  = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '0';
                        $duan_area    = isset($_GET['khu_vuc']) ? sanitize_text_field(wp_unslash($_GET['khu_vuc'])) : (isset($_GET['area']) ? sanitize_text_field(wp_unslash($_GET['area'])) : '');

                        // Danh sách khu vực Hà Nội giống page-du-an.php, chỉ hiển thị khu vực có dự án
                        $duan_area_options_raw = [
                            ''            => 'Toàn Hà Nội',
                            'gia-lam'     => 'Gia Lâm',
                            'nam-tu-liem' => 'Nam Từ Liêm',
                            'hai-ba-trung' => 'Hai Bà Trưng',
                            'tay-ho'      => 'Tây Hồ',
                            'long-bien'   => 'Long Biên',
                            'cau-giay'    => 'Cầu Giấy',
                            'dong-da'     => 'Đống Đa',
                            'hoang-mai'   => 'Hoàng Mai',
                            'thanh-xuan'  => 'Thanh Xuân',
                            'ha-dong'     => 'Hà Đông',
                            'ba-dinh'     => 'Ba Đình',
                            'hoan-kiem'   => 'Hoàn Kiếm',
                        ];

                        $duan_posts_for_options = function_exists('batdongsan_get_du_an_posts_for_filter_options')
                            ? batdongsan_get_du_an_posts_for_filter_options([
                                'project-name' => $duan_name,
                                'type'         => ($duan_type && $duan_type !== '0') ? $duan_type : '',
                                'status'       => ($duan_status && $duan_status !== '0') ? $duan_status : '',
                                'price'        => ($duan_price && $duan_price !== '0') ? $duan_price : null,
                            ])
                            : [];
                        $duan_area_options = function_exists('batdongsan_filter_khu_vuc_options_by_posts')
                            ? batdongsan_filter_khu_vuc_options_by_posts($duan_area_options_raw, $duan_posts_for_options, 'address')
                            : $duan_area_options_raw;
                        if ($duan_area && isset($duan_area_options_raw[$duan_area]) && !isset($duan_area_options[$duan_area])) {
                            $duan_area_options[$duan_area] = $duan_area_options_raw[$duan_area];
                        }
                        if (count($duan_area_options) <= 1 && empty($duan_posts_for_options)) {
                            $duan_area_options = $duan_area_options_raw;
                        }

                        // Khoảng giá cho Dự án
                        if (function_exists('bds_get_price_ranges')) {
                            $duan_price_ranges = bds_get_price_ranges();
                        } else {
                            $duan_price_ranges = ['0' => 'Tất cả'];
                        }

                        $duan_status_options = [
                            '0'           => 'Tất cả',
                            'dang-mo-ban' => 'Đang mở bán',
                            'sap-mo-ban'  => 'Sắp mở bán',
                            'da-ban-het'  => 'Đã bán hết',
                            'tam-dung'    => 'Tạm dừng',
                            'dang-cap-nhat' => 'Đang cập nhật',
                        ];

                        $duan_type_options = function_exists('batdongsan_get_du_an_type_options') ? batdongsan_get_du_an_type_options() : [];

                        get_template_part('template-parts/filter-bar-horizontal', null, [
                            'layout'          => 'mobile_header',
                            'form_action'     => $du_an_page_url,
                            'placeholder'     => 'Tìm kiếm dự án...',
                            'current_name'    => $duan_name,
                            'current_price'   => $duan_price,
                            'current_type'    => $duan_type ?: '0',
                            'current_khu_vuc' => $duan_area,
                            'price_ranges'    => $duan_price_ranges,
                            'price_bounds'    => [0, 60000],
                            'area_options'    => $duan_area_options,
                            'type_terms'      => $duan_type_options,
                            'show_status'     => false,
                            'show_area_range' => false,
                        ]);
                    }
                    ?>
                </div>
            <?php else : ?>
                <!-- Mobile: Apple Spotlight Search Capsule -->
                <div class="block lg:hidden pb-2.5 pt-0.5">
                    <form action="<?php echo esc_url(home_url('/bat-dong-san')); ?>" method="get" class="flex items-center bg-black/[0.04] hover:bg-black/[0.06] focus-within:bg-white border border-black/[0.07] focus-within:border-primary/40 focus-within:ring-2 focus-within:ring-primary/10 rounded-full pl-3.5 pr-1 py-1 transition-all shadow-[inset_0_1px_2px_rgba(0,0,0,0.02)]">
                        <span class="text-charcoal/40 pointer-events-none shrink-0" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="10.5" cy="10.5" r="6.5"></circle>
                                <line x1="15.5" y1="15.5" x2="20.5" y2="20.5"></line>
                            </svg>
                        </span>
                        <input type="text" name="project-name" value="" placeholder="Tìm kiếm bất động sản, dự án..." class="flex-1 border-none bg-transparent py-1 px-2.5 text-[13px] text-charcoal outline-none placeholder:text-charcoal-muted/60 font-normal" aria-label="Tìm kiếm">
                        <button type="submit" class="w-11 h-11 bg-primary shrink-0 flex items-center justify-center text-white border-none cursor-pointer transition-all hover:bg-primary-hover active:scale-90 rounded-full shadow-sm" aria-label="Tìm kiếm">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="10.5" cy="10.5" r="6.5"></circle>
                                <line x1="15.5" y1="15.5" x2="20.5" y2="20.5"></line>
                            </svg>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Mobile Menu Overlay (z-index trên header để menu mở ra không bị đè) -->
    <div class="mobile-menu-overlay fixed inset-0 bg-charcoal/60 backdrop-blur-[2px] z-[1002] opacity-0 invisible transition-[opacity,visibility] duration-300 lg:hidden" aria-hidden="true"></div>

    <!-- Mobile Canvas Menu (Apple iOS Sheet Style) -->
    <aside class="mobile-menu fixed top-0 right-0 w-[85%] max-w-[360px] h-screen bg-white z-[1003] translate-x-full transition-transform duration-300 ease-out overflow-y-auto shadow-2xl lg:hidden flex flex-col justify-between border-l border-black/[0.06]" aria-label="Menu điều hướng" aria-hidden="true">
        <div>
            <!-- Drawer Topbar: Brand Identity & Dismiss Trigger -->
            <div class="flex items-center justify-between p-4 border-b border-black/[0.06] bg-white">
                <?php if (has_custom_logo()) : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center active:opacity-80 transition-opacity" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
                        <?php
                        $custom_logo_id = get_theme_mod('custom_logo');
                        $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
                        $logo_alt = get_post_meta($custom_logo_id, '_wp_attachment_image_alt', true) ?: get_bloginfo('name');
                        ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($logo_alt); ?>" class="h-7 w-auto object-contain" loading="eager">
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center font-bold text-lg text-charcoal tracking-tight" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
                        <?php bloginfo('name'); ?>
                    </a>
                <?php endif; ?>

                <button type="button" class="mobile-menu__close w-11 h-11 flex items-center justify-center rounded-full bg-black/[0.04] text-charcoal/70 hover:text-charcoal hover:bg-black/[0.08] active:scale-90 transition-all cursor-pointer" aria-label="Đóng menu">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="6.5" y1="6.5" x2="17.5" y2="17.5"></line>
                        <line x1="17.5" y1="6.5" x2="6.5" y2="17.5"></line>
                    </svg>
                </button>
            </div>

            <!-- Drawer Navigation Links -->
            <nav class="py-1" aria-label="Điều hướng chính mobile">
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container' => false,
                    'menu_class' => 'mobile-menu__list list-none m-0 p-0',
                    'fallback_cb' => '__return_empty_string',
                    'depth' => 3,
                ]);
                ?>
            </nav>
        </div>

        <!-- Drawer Footer: Advisory Hotline & Conversion -->
        <div class="p-4 border-t border-black/[0.06] bg-white mt-auto">
            <a href="tel:<?php echo esc_attr($site_hotline_raw); ?>" class="flex items-center justify-center gap-2 w-full py-3 px-4 rounded-full bg-primary text-white text-xs font-semibold hover:bg-primary-hover active:scale-95 transition-all shadow-sm">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
                <span>Tư vấn Hotline: <?php echo esc_html($site_hotline); ?></span>
            </a>
            <p class="text-[11px] text-center text-charcoal-muted mt-2 font-sans">Bất động sản &amp; Dự án quy hoạch Hà Nội</p>
        </div>
    </aside>