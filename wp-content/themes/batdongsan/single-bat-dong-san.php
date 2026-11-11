<?php

/**
 * Template cho single post type bat-dong-san - Luxury Real Estate Redesign (Milestone 5)
 *
 * Chuẩn hóa kiến trúc hiển thị chi tiết bất động sản cao cấp:
 * - Warm ivory canvas (bg-canvas) & Porcelain surface cards (bg-surface)
 * - Refined editorial typography (Cormorant Garamond font-serif + Plus Jakarta Sans)
 * - Champagne bronze accents (primary / #A88B5A), Zero legacy red
 * - Preservation contracts: All ACF getters, sub-queries no_found_rows => true, DOM IDs & Swiper/lightGallery classes
 *
 * @package BatDongSan
 */

get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        $post_id = get_the_ID();

        // 1. Lấy toàn bộ ACF fields & Post Meta
        $dia_chi            = get_field('dia_chi', $post_id);
        $khoang_gia         = get_field('khoang_gia', $post_id);
        $gia_tren_met_vuong = get_field('gia_tren_met_vuong', $post_id);
        $gia_cho_thue       = get_field('gia_cho_thue', $post_id);
        $dien_tich          = get_field('dien_tich', $post_id);
        $dien_tich_su_dung  = get_field('dien_tich_su_dung', $post_id);
        $mat_tien           = get_field('mat_tien', $post_id);
        $so_mat_tien        = get_field('so_mat_tien', $post_id);
        $so_tang            = get_field('so_tang', $post_id);
        $phap_ly            = get_field('phap_ly', $post_id);
        $huong_nha          = get_field('huong_nha', $post_id);
        $duong_vao          = get_field('duong_vao', $post_id);
        $gallery_nha_dat    = get_field('gallery_nha_dat', $post_id);
        $video_url          = get_field('video_url', $post_id);
        $thiet_ke           = get_field('thiet_ke', $post_id);
        $google_map         = get_field('google_map', $post_id);
        $trang_thai         = get_field('trang_thai', $post_id);
        $ngay_dang          = get_field('ngay_dang', $post_id);
        $ngay_het_han       = get_field('ngay_het_han', $post_id);
        $noi_that           = get_field('noi_that', $post_id);

        // Bổ sung thông số phòng ngủ & phòng vệ sinh (ACF + Meta + Fallback regex)
        $so_phong_ngu       = get_field('so_phong_ngu', $post_id);
        if (empty($so_phong_ngu)) {
            $so_phong_ngu = get_post_meta($post_id, 'so_phong_ngu', true);
        }
        $so_phong_ve_sinh   = get_field('so_phong_ve_sinh', $post_id);
        if (empty($so_phong_ve_sinh)) {
            $so_phong_ve_sinh = get_post_meta($post_id, 'so_phong_ve_sinh', true);
        }

        // Lượt xem bài viết
        $views_count        = (int) get_post_meta($post_id, 'views', true);

        // 2. Lấy taxonomy terms
        $loai_hinh_terms    = get_the_terms($post_id, 'loai_hinh_bat_dong_san');
        $loai_hinh_name     = (!is_wp_error($loai_hinh_terms) && !empty($loai_hinh_terms)) ? $loai_hinh_terms[0]->name : '';

        $hinh_thuc_terms    = get_the_terms($post_id, 'hinh-thuc-bds');
        $hinh_thuc_term     = (!is_wp_error($hinh_thuc_terms) && !empty($hinh_thuc_terms)) ? $hinh_thuc_terms[0] : null;
        $is_featured        = (bool) get_field('featured', $post_id);
        $is_cho_thue        = ($hinh_thuc_term && $hinh_thuc_term->slug === 'cho-thue');

        // 3. Dự án liên quan (Numeric relational mapping)
        $related_project_id = function_exists('batdongsan_get_related_project_id')
            ? batdongsan_get_related_project_id($post_id)
            : (int) get_post_meta($post_id, 'du_an_lien_quan', true);
        $related_project    = ($related_project_id > 0) ? get_post($related_project_id) : null;

        // 4. Xử lý Gallery images (Array ID format & Featured fallback)
        $gallery_image_ids = [];
        if ($gallery_nha_dat && is_array($gallery_nha_dat) && !empty($gallery_nha_dat)) {
            foreach ($gallery_nha_dat as $image) {
                $image_id = is_array($image) && isset($image['ID']) ? (int) $image['ID'] : (is_numeric($image) ? (int) $image : 0);
                if ($image_id > 0) {
                    $gallery_image_ids[] = $image_id;
                }
            }
        }
        if (empty($gallery_image_ids) && has_post_thumbnail($post_id)) {
            $gallery_image_ids[] = get_post_thumbnail_id($post_id);
        }

        // Xử lý video URL & thumbnail
        $has_video = !empty($video_url);
        $video_thumbnail_url = '';
        if ($has_video && function_exists('bds_get_video_thumbnail')) {
            $video_thumbnail_url = bds_get_video_thumbnail($video_url);
        }

        // Danh sách video review thực tế của chuyên viên (TikTok & Reels)
        $property_videos     = function_exists('batdongsan_get_property_videos') ? batdongsan_get_property_videos($post_id) : [];
        $has_property_videos = !empty($property_videos);

        if ($has_video && empty($video_thumbnail_url)) {
            $custom_thumb = get_field('video_thumbnail', $post_id);
            if ($custom_thumb) {
                $video_thumbnail_url = wp_get_attachment_image_url($custom_thumb, 'large');
            } elseif (!empty($property_videos[0]['thumbnail'])) {
                $video_thumbnail_url = $property_videos[0]['thumbnail'];
            }
        }
        $video_platform  = $has_video ? (function_exists('batdongsan_detect_video_platform') ? batdongsan_detect_video_platform($video_url) : 'tiktok') : 'tiktok';
        $video_embed_url = $has_video && function_exists('batdongsan_get_video_embed_url') ? batdongsan_get_video_embed_url($video_url) : '';

        // 5. Breadcrumb parts
        $breadcrumb_parts = function_exists('batdongsan_get_bat_dong_san_breadcrumb')
            ? batdongsan_get_bat_dong_san_breadcrumb($post_id)
            : [];

        // 6. Advisor data từ ACF Options
        $advisor_data          = function_exists('batdongsan_get_advisor_data') ? batdongsan_get_advisor_data([]) : [];
        $advisor_name          = $advisor_data['advisor_name'] ?? 'Chuyên viên BĐS Hà Nội';
        $advisor_avatar        = $advisor_data['advisor_avatar'] ?? 0;
        $advisor_phone         = $advisor_data['advisor_phone'] ?? get_option('admin_phone', '0901234567');
        $advisor_email         = $advisor_data['advisor_email'] ?? get_option('admin_email', '');
        $advisor_zalo          = $advisor_data['advisor_zalo'] ?? ('https://zalo.me/' . preg_replace('/\D/', '', $advisor_phone));
        $advisor_years         = $advisor_data['advisor_years'] ?? '10+';
        $advisor_listing_count = $advisor_data['advisor_listing_count'] ?? '85+';
        $advisor_profile_url   = $advisor_data['advisor_profile_url'] ?? '';
        $advisor_platform_name = $advisor_data['advisor_platform_name'] ?? 'BatDongSanHaNoi';

        // 7. Tính toán & chuẩn hóa giá / m²
        if (!$gia_tren_met_vuong && $khoang_gia && $dien_tich) {
            preg_match('/([\d,]+)/', str_replace(',', '.', $khoang_gia), $matches);
            if (!empty($matches[1])) {
                $gia_number = floatval(str_replace(',', '.', $matches[1]));
                preg_match('/([\d,]+)/', $dien_tich, $area_matches);
                if (!empty($area_matches[1])) {
                    $area_number = floatval(str_replace(',', '.', $area_matches[1]));
                    if ($area_number > 0) {
                        $gia_tren_met_vuong = '~' . number_format(($gia_number * 1000) / $area_number, 1, ',', '.') . ' triệu/m²';
                    }
                }
            }
        }

        // Fallback số phòng ngủ từ tiêu đề nếu meta rỗng
        if (empty($so_phong_ngu) && preg_match('/(\d+)\s*PN/i', get_the_title(), $matches)) {
            $so_phong_ngu = $matches[1];
        }

        // Fallback số phòng vệ sinh từ tiêu đề/mô tả nếu rỗng
        if (empty($so_phong_ve_sinh) && preg_match('/(\d+)\s*(?:WC|phòng vệ sinh)/i', get_the_title() . ' ' . $thiet_ke, $wc_m)) {
            $so_phong_ve_sinh = $wc_m[1];
        }

        // Mapping labels cho các trường Select
        $phap_ly_labels = [
            'so_do'                     => 'Sổ đỏ chính chủ',
            'so_hong'                    => 'Sổ hồng lâu dài',
            'so_do_so_hong'              => 'Sổ đỏ / Sổ hồng',
            'hop_dong_mua_ban'           => 'Hợp đồng mua bán (HĐMB)',
            'dang_ky_quyen_su_dung_dat' => 'Đang làm sổ',
            'giay_chung_nhan'           => 'Giấy chứng nhận',
            'khac'                       => 'Pháp lý minh bạch',
        ];

        $huong_nha_labels = [
            'dong'     => 'Đông',
            'tay'      => 'Tây',
            'nam'      => 'Nam',
            'bac'      => 'Bắc',
            'dong_bac' => 'Đông Bắc',
            'dong_nam' => 'Đông Nam',
            'tay_bac'  => 'Tây Bắc',
            'tay_nam'  => 'Tây Nam',
        ];

        $so_mat_tien_labels = [
            '1' => '1 mặt thoáng',
            '2' => '2 mặt tiền (Lô góc)',
            '3' => '3 mặt tiền thoáng',
            '4' => '4 mặt tiền',
        ];

        $trang_thai_labels = [
            'dang_ban'      => 'Đang mở bán',
            'da_ban'        => 'Đã giao dịch',
            'dang_cho_thue' => 'Đang cho thuê',
            'tam_dung'      => 'Tạm dừng',
        ];

        $display_phap_ly     = $phap_ly && isset($phap_ly_labels[$phap_ly]) ? $phap_ly_labels[$phap_ly] : ($phap_ly ?: 'Sổ đỏ / Sổ hồng');
        $display_huong_nha   = $huong_nha && isset($huong_nha_labels[$huong_nha]) ? $huong_nha_labels[$huong_nha] : '';
        $display_so_mat_tien = $so_mat_tien && isset($so_mat_tien_labels[$so_mat_tien]) ? $so_mat_tien_labels[$so_mat_tien] : '';
        $display_trang_thai  = $trang_thai && isset($trang_thai_labels[$trang_thai]) ? $trang_thai_labels[$trang_thai] : 'Đang mở bán';

        // Format ngày đăng
        $ngay_dang_display = '';
        if ($ngay_dang) {
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            if ($ngay_dang == $today) {
                $ngay_dang_display = 'Hôm nay';
            } elseif ($ngay_dang == $yesterday) {
                $ngay_dang_display = 'Hôm qua';
            } else {
                $ngay_dang_display = date_i18n('d/m/Y', strtotime($ngay_dang));
            }
        } else {
            $post_date = get_the_date('Y-m-d', $post_id);
            $today = date('Y-m-d');
            if ($post_date == $today) {
                $ngay_dang_display = 'Hôm nay';
            } else {
                $ngay_dang_display = get_the_date('d/m/Y', $post_id);
            }
        }

        // Lấy khu vực từ địa chỉ
        $khu_vuc_name = '';
        if ($dia_chi) {
            $dia_chi_parts = explode(',', $dia_chi);
            if (count($dia_chi_parts) >= 2) {
                $khu_vuc_name = trim(end($dia_chi_parts));
            }
        }

        // 8. Queries liên quan (Bắt buộc no_found_rows => true)
        // Sub-query 1: Dự án liên quan
        $related_projects = null;
        if ($khu_vuc_name) {
            $related_projects = new WP_Query([
                'post_type'              => 'du-an',
                'posts_per_page'         => 8,
                'post_status'            => 'publish',
                'no_found_rows'          => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => false,
                'meta_query'             => [
                    [
                        'key'     => 'address',
                        'value'   => $khu_vuc_name,
                        'compare' => 'LIKE',
                    ],
                ],
            ]);
        }

        // Sub-query 2: Bất động sản nổi bật
        $featured_listings = new WP_Query([
            'post_type'              => 'bat-dong-san',
            'posts_per_page'         => 5,
            'post__not_in'           => [$post_id],
            'post_status'            => 'publish',
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
            'meta_query'             => [
                [
                    'key'   => 'featured',
                    'value' => '1',
                ],
            ],
            'orderby'                => 'date',
            'order'                  => 'DESC',
        ]);

        // Sub-query 3: Bất động sản dành cho bạn (Cùng loại hình hoặc cùng khu vực)
        $related_args = [
            'post_type'              => 'bat-dong-san',
            'posts_per_page'         => 6,
            'post__not_in'           => [$post_id],
            'post_status'            => 'publish',
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
            'orderby'                => 'date',
            'order'                  => 'DESC',
        ];
        if (!empty($loai_hinh_terms) && !is_wp_error($loai_hinh_terms)) {
            $related_args['tax_query'] = [
                [
                    'taxonomy' => 'loai_hinh_bat_dong_san',
                    'field'    => 'term_id',
                    'terms'    => $loai_hinh_terms[0]->term_id,
                ],
            ];
        }
        $related_listings = new WP_Query($related_args);
        if (!$related_listings->have_posts()) {
            // Fallback nếu không có bài cùng loại hình
            unset($related_args['tax_query']);
            $related_listings = new WP_Query($related_args);
        }
?>

<main class="bg-canvas min-h-screen py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumbs Navigation -->
        <nav aria-label="Breadcrumb" class="mb-6">
            <?php if (function_exists('rank_math_the_breadcrumbs')) : ?>
                <div class="hang-chuyen-nhuong-detail__breadcrumb-wrapper text-xs font-sans text-charcoal-muted">
                    <?php rank_math_the_breadcrumbs(); ?>
                </div>
            <?php else : ?>
                <?php
                get_template_part('template-parts/breadcrumb', null, [
                    'breadcrumb_parts' => $breadcrumb_parts,
                    'prefix_class'     => 'hang-chuyen-nhuong-detail',
                    'current_label'    => get_the_title(),
                    'mobile_optimized' => true,
                    'no_order'         => true,
                ]);
                ?>
            <?php endif; ?>
        </nav>

        <!-- Main Layout: Content Grid & Advisor Sidebar -->
        <div class="grid gap-4 lg:gap-5 grid-cols-1 lg:grid-cols-[1fr_380px] items-start">
            
            <!-- Main Details Column -->
            <div class="flex flex-col w-full min-w-0">

                <!-- 1. Architectural Gallery / Media Showcase -->
                <?php if (!empty($gallery_image_ids) || $has_video) : ?>
                    <?php $total_media = count($gallery_image_ids) + ($has_video ? 1 : 0); ?>
                    <div class="hang-chuyen-nhuong-detail__hero-gallery mb-4 sm:mb-5 rounded-2xl overflow-hidden bg-surface border border-border/80 shadow-subtle" id="gallery-container">
                        <div class="hang-chuyen-nhuong-detail__gallery-main relative">
                            <div class="hang-chuyen-nhuong-detail__gallery-preview swiper aspect-[16/10] sm:aspect-[16/9] lg:aspect-[16/10] max-h-[580px] w-full bg-stone" id="gallery-preview">
                                <div class="swiper-wrapper">
                                    <?php foreach ($gallery_image_ids as $index => $image_id) : ?>
                                        <div class="swiper-slide cursor-default md:cursor-zoom-in">
                                            <div class="hang-chuyen-nhuong-detail__gallery-preview-btn w-full h-full">
                                                <?php echo wp_get_attachment_image($image_id, 'full', false, [
                                                    'class' => 'hang-chuyen-nhuong-detail__gallery-preview-img w-full h-full object-cover transition-transform duration-500 hover:scale-[1.02]',
                                                    'loading' => $index === 0 ? 'eager' : 'lazy',
                                                ]); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php if ($has_video && $video_thumbnail_url) : ?>
                                        <div 
                                            class="swiper-slide cursor-pointer"
                                            data-video-modal-trigger
                                            data-video-url="<?= esc_url($video_url); ?>"
                                            data-video-embed="<?= esc_url($video_embed_url); ?>"
                                            data-video-title="<?= esc_attr(get_the_title()); ?>"
                                            data-video-badge="Review Thực Tế"
                                            data-video-platform="<?= esc_attr($video_platform); ?>"
                                        >
                                            <div class="hang-chuyen-nhuong-detail__gallery-preview-btn hang-chuyen-nhuong-detail__gallery-preview-btn--video relative w-full h-full">
                                                <img src="<?= esc_url($video_thumbnail_url); ?>" alt="Video giới thiệu" class="hang-chuyen-nhuong-detail__gallery-preview-img w-full h-full object-cover">
                                                <div class="hang-chuyen-nhuong-detail__gallery-preview-video-icon absolute inset-0 flex items-center justify-center bg-charcoal/40 backdrop-blur-[2px] transition-all hover:bg-charcoal/30">
                                                    <div class="w-20 h-20 rounded-full bg-primary text-white flex items-center justify-center shadow-button transition-transform duration-300 hover:scale-110">
                                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                                                            <polygon points="8 5 19 12 8 19 8 5" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Luxury Swiper Navigation with Centered SVG Vector Icons (Desktop/Tablet only) -->
                                <div class="swiper-button-prev hidden md:flex !w-11 !h-11 sm:!w-12 sm:!h-12 !rounded-full !bg-white/95 !text-charcoal !border !border-black/15 !shadow-[0_4px_16px_rgba(0,0,0,0.15)] !backdrop-blur-sm after:!content-none hover:!bg-primary hover:!text-white hover:!border-primary transition-all duration-300 !left-3 sm:!left-4 items-center justify-center cursor-pointer pointer-events-auto group" aria-label="Ảnh trước">
                                    <svg class="w-5 h-5 text-current pointer-events-none stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 18l-6-6 6-6"/>
                                    </svg>
                                </div>
                                <div class="swiper-button-next hidden md:flex !w-11 !h-11 sm:!w-12 sm:!h-12 !rounded-full !bg-white/95 !text-charcoal !border !border-black/15 !shadow-[0_4px_16px_rgba(0,0,0,0.15)] !backdrop-blur-sm after:!content-none hover:!bg-primary hover:!text-white hover:!border-primary transition-all duration-300 !right-3 sm:!right-4 items-center justify-center cursor-pointer pointer-events-auto group" aria-label="Ảnh sau">
                                    <svg class="w-5 h-5 text-current pointer-events-none stroke-[2.5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 18l6-6-6-6"/>
                                    </svg>
                                </div>

                                <!-- Mobile Counter Badge (1 / N) -->
                                <?php if ($total_media > 1) : ?>
                                    <div class="bds-gallery-swiper__counter md:hidden absolute bottom-3 right-3 z-10 px-2.5 py-1 rounded-full bg-black/60 backdrop-blur-md text-white text-[11px] font-sans font-medium tracking-wider pointer-events-none">
                                        <span class="bds-gallery-swiper__current">1</span> / <span><?= $total_media; ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Fullscreen Lightbox Trigger Button (Desktop/Tablet only) -->
                                <button type="button" class="hidden md:inline-flex absolute bottom-3 right-3 sm:bottom-4 sm:right-4 z-10 items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl bg-white/95 hover:bg-white text-charcoal border border-black/15 shadow-[0_4px_16px_rgba(0,0,0,0.12)] backdrop-blur-md text-xs sm:text-sm font-sans font-medium transition-all hover:border-primary/50 cursor-pointer pointer-events-auto" onclick="document.querySelector('#gallery-container .hang-chuyen-nhuong-detail__gallery-preview .swiper-slide-active')?.click();">
                                    <svg class="w-4 h-4 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="15 3 21 3 21 9"></polyline>
                                        <polyline points="9 21 3 21 3 15"></polyline>
                                        <line x1="21" y1="3" x2="14" y2="10"></line>
                                        <line x1="3" y1="21" x2="10" y2="14"></line>
                                    </svg>
                                    <span>Toàn màn hình (<?= $total_media; ?> ảnh/video)</span>
                                </button>
                            </div>
                        </div>

                        <!-- Thumbnails Carousel (Desktop/Tablet only, ẩn trên Mobile để giữ swiper 1 item gọn nhẹ) -->
                        <?php if ($total_media > 1) : ?>
                            <div class="hang-chuyen-nhuong-detail__gallery-thumbnails-wrapper hang-chuyen-nhuong-detail__gallery-thumbnails-wrapper--swiper hidden md:block p-3 sm:p-4 bg-surface border-t border-border">
                                <div class="hang-chuyen-nhuong-detail__gallery-thumbnails-swiper swiper">
                                    <div class="swiper-wrapper">
                                        <?php foreach ($gallery_image_ids as $index => $image_id) : ?>
                                            <?php $thumb_image_url = wp_get_attachment_image_url($image_id, 'large'); ?>
                                            <div class="swiper-slide w-20 h-20 sm:w-24 sm:h-24 shrink-0">
                                                <button
                                                    type="button"
                                                    class="hang-chuyen-nhuong-detail__gallery-thumb w-full h-full rounded-xl overflow-hidden transition-all duration-200 cursor-pointer p-0.5 bg-stone block<?= $index === 0 ? ' hang-chuyen-nhuong-detail__gallery-thumb--active' : ''; ?>"
                                                    data-thumb-index="<?= $index; ?>"
                                                    data-type="image"
                                                    data-preview-url="<?= esc_url($thumb_image_url); ?>"
                                                    aria-label="Xem ảnh <?= $index + 1; ?>">
                                                    <?php echo wp_get_attachment_image($image_id, 'thumbnail', false, [
                                                        'class' => 'hang-chuyen-nhuong-detail__gallery-thumb-img w-full h-full object-cover',
                                                    ]); ?>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>

                                        <?php if ($has_video && $video_thumbnail_url) : ?>
                                            <div class="swiper-slide w-20 h-20 sm:w-24 sm:h-24 shrink-0">
                                                <button
                                                    type="button"
                                                    class="hang-chuyen-nhuong-detail__gallery-thumb hang-chuyen-nhuong-detail__gallery-thumb--video w-full h-full rounded-xl overflow-hidden transition-all duration-200 cursor-pointer p-0.5 bg-stone block<?= empty($gallery_image_ids) ? ' hang-chuyen-nhuong-detail__gallery-thumb--active' : ''; ?>"
                                                    data-thumb-index="<?= count($gallery_image_ids); ?>"
                                                    data-type="video"
                                                    data-preview-url="<?= esc_url($video_thumbnail_url); ?>"
                                                    aria-label="Xem video giới thiệu">
                                                    <span class="relative block w-full h-full rounded-lg overflow-hidden">
                                                        <img src="<?= esc_url($video_thumbnail_url); ?>" alt="Video" class="hang-chuyen-nhuong-detail__gallery-thumb-img w-full h-full object-cover">
                                                        <span class="absolute inset-0 flex items-center justify-center bg-charcoal/40 text-white">
                                                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                                                <polygon points="8 5 19 12 8 19 8 5" />
                                                            </svg>
                                                        </span>
                                                    </span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- LightGallery Container Ẩn (Thứ tự item khớp chuẩn Swiper slides) -->
                        <?php $video_embed_url = $has_video && function_exists('batdongsan_get_video_embed_url') ? batdongsan_get_video_embed_url($video_url) : ''; ?>
                        <div class="hang-chuyen-nhuong-detail__gallery-items hidden" aria-hidden="true">
                            <?php foreach ($gallery_image_ids as $image_id) : ?>
                                <?php
                                $full = wp_get_attachment_image_src($image_id, 'full');
                                $full_url = ($full && !empty($full[0])) ? $full[0] : wp_get_attachment_image_url($image_id, 'large');
                                if (empty($full_url)) continue;
                                $full_w = ($full && isset($full[1])) ? (int) $full[1] : 0;
                                $full_h = ($full && isset($full[2])) ? (int) $full[2] : 0;
                                $data_size = ($full_w > 0 && $full_h > 0) ? $full_w . '-' . $full_h : '';
                                $thumb_url = wp_get_attachment_image_url($image_id, 'thumbnail') ?: $full_url;
                                ?>
                                <a
                                    href="<?= esc_url($full_url); ?>"
                                    class="hang-chuyen-nhuong-detail__gallery-item"
                                    data-external-thumb-image="<?= esc_url($thumb_url); ?>"
                                    <?= $data_size ? 'data-lg-size="' . esc_attr($data_size) . '"' : ''; ?>>
                                </a>
                            <?php endforeach; ?>
                            <?php if ($has_video && $video_embed_url) : ?>
                                <a
                                    href="<?= esc_url($video_embed_url); ?>"
                                    class="hang-chuyen-nhuong-detail__gallery-item"
                                    data-iframe="true"
                                    <?= !empty($video_thumbnail_url) ? 'data-external-thumb-image="' . esc_attr($video_thumbnail_url) . '" data-poster="' . esc_attr($video_thumbnail_url) . '"' : ''; ?>>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else : ?>
                    <!-- Placeholder khi chưa có gallery -->
                    <div class="mb-8 rounded-2xl overflow-hidden bg-stone border border-border shadow-card aspect-[16/9] max-h-[440px]">
                        <img
                            src="<?= esc_url(get_template_directory_uri() . '/assets/images/placeholder-real-estate.jpg'); ?>"
                            alt="<?= esc_attr(get_the_title()); ?>"
                            class="w-full h-full object-cover"
                            loading="eager"
                            decoding="async">
                    </div>
                <?php endif; ?>

                <!-- 2. Property Header & Editorial Title Card -->
                <header class="bg-surface rounded-2xl border border-border/80 p-5 sm:p-6 shadow-subtle mb-4 sm:mb-5">
                    
                    <!-- Top Status Badges & Quick Action Meta -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-border/80">
                        <div class="flex items-center flex-wrap gap-2">
                            <?php if ($is_featured) : ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-sans font-semibold tracking-wider uppercase bg-gold text-white shadow-sm">
                                    Nổi bật
                                </span>
                            <?php endif; ?>

                            <?php if ($hinh_thuc_term) : ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-sans font-medium bg-stone text-charcoal">
                                    <?= esc_html($hinh_thuc_term->name); ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($loai_hinh_name) : ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-sans font-medium bg-canvas border border-border text-charcoal-muted">
                                    <?= esc_html($loai_hinh_name); ?>
                                </span>
                            <?php endif; ?>

                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-sans font-medium <?= $trang_thai === 'da_ban' ? 'bg-stone text-charcoal-muted' : ($trang_thai === 'tam_dung' ? 'bg-stone text-charcoal-muted' : 'bg-emerald-50 text-emerald-800 border border-emerald-200'); ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $trang_thai === 'da_ban' || $trang_thai === 'tam_dung' ? 'bg-charcoal-muted' : 'bg-emerald-500'; ?>"></span>
                                <?= esc_html($display_trang_thai); ?>
                            </span>

                            <?php if ($related_project) : ?>
                                <a href="<?= esc_url(get_permalink($related_project->ID)); ?>" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-sans font-medium bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors" title="Xem thông tin dự án">
                                    <span>Thuộc dự án:</span>
                                    <strong class="font-semibold"><?= esc_html($related_project->post_title); ?></strong>
                                    <span>&rarr;</span>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Date, Views & Actions -->
                        <div class="flex items-center gap-4 text-xs font-sans text-charcoal-muted">
                            <span class="flex items-center gap-1.5" title="Ngày đăng tin">
                                <svg class="w-3.5 h-3.5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="3" y="4.5" width="18" height="16.5" rx="3.5"/>
                                    <line x1="16" y1="2" x2="16" y2="5" stroke-linecap="round"/>
                                    <line x1="8" y1="2" x2="8" y2="5" stroke-linecap="round"/>
                                    <line x1="3" y1="9.5" x2="21" y2="9.5"/>
                                </svg>
                                <span><?= esc_html($ngay_dang_display); ?></span>
                            </span>

                            <?php if ($views_count > 0) : ?>
                                <span class="flex items-center gap-1.5" title="Lượt xem bài đăng">
                                    <svg class="w-3.5 h-3.5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M2.5 12C4.5 7.5 8 5 12 5s7.5 2.5 9.5 7c-2 4.5-5.5 7-9.5 7s-7.5-2.5-9.5-7z" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <span><?= number_format_i18n($views_count); ?> lượt xem</span>
                                </span>
                            <?php endif; ?>

                            <!-- Share menu integration (Theo chuẩn Apple iOS Share Card) -->
                            <?php
                            get_template_part('template-parts/share-dropdown', null, [
                                'title'   => get_the_title(),
                                'heading' => 'Chia sẻ bất động sản',
                                'align'   => 'right',
                            ]);
                            ?>
                        </div>
                    </div>

                    <!-- Property Title (Editorial Cupertino) -->
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-charcoal leading-tight tracking-tight mt-4 mb-3">
                        <?php the_title(); ?>
                    </h1>

                    <!-- Location Bar -->
                    <?php if ($dia_chi) : ?>
                        <div class="flex items-start sm:items-center gap-2 text-charcoal-muted text-sm font-sans mb-4 sm:mb-5">
                            <span class="text-primary shrink-0 mt-0.5 sm:mt-0" aria-hidden="true">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2.5a6.5 6.5 0 0 0-6.5 6.5c0 4.8 6.5 12.5 6.5 12.5s6.5-7.7 6.5-12.5a6.5 6.5 0 0 0-6.5-6.5z"/>
                                    <circle cx="12" cy="9" r="2.2"/>
                                </svg>
                            </span>
                            <span><?= esc_html($dia_chi); ?></span>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($dia_chi); ?>" target="_blank" rel="noopener noreferrer" class="hidden sm:inline-flex items-center gap-1 text-xs text-primary hover:text-primary-hover font-medium underline underline-offset-2 ml-2 transition-colors">
                                <span>Chỉ đường</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Highlighted Metrics Band -->
                    <div class="bg-[#F5F5F7] rounded-xl sm:rounded-2xl p-3.5 sm:p-4.5 border border-black/[0.06] grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                        
                        <!-- Metric 1: Giá -->
                        <div class="flex flex-col gap-1 border-r border-border/70 pr-4 last:border-0">
                            <span class="text-xs uppercase tracking-wider text-charcoal-muted font-sans font-medium">
                                <?= $is_cho_thue ? 'Giá thuê' : 'Mức giá niêm yết'; ?>
                            </span>
                            <div class="font-sans text-2xl sm:text-3xl font-extrabold text-primary leading-none mt-1">
                                <?php if ($is_cho_thue) : ?>
                                    <?= esc_html($gia_cho_thue ?: ($khoang_gia ?: 'Thỏa thuận')); ?>
                                <?php else : ?>
                                    <?= esc_html($khoang_gia ?: 'Giá liên hệ'); ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!$is_cho_thue && $gia_tren_met_vuong) : ?>
                                <span class="text-xs text-charcoal-muted font-sans mt-1">
                                    <?= esc_html($gia_tren_met_vuong); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Metric 2: Diện tích -->
                        <?php if ($dien_tich) : ?>
                            <div class="flex flex-col gap-1 sm:border-r border-border/70 sm:px-4 last:border-0">
                                <span class="text-xs uppercase tracking-wider text-charcoal-muted font-sans font-medium">Diện tích</span>
                                <div class="text-2xl sm:text-3xl font-bold text-charcoal leading-none mt-1">
                                    <?= esc_html($dien_tich); ?>
                                </div>
                                <span class="text-xs text-charcoal-muted font-sans mt-1">
                                    <?= $dien_tich_su_dung ? 'Sử dụng: ' . esc_html($dien_tich_su_dung) : 'Diện tích sàn'; ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Metric 3: Phòng ngủ -->
                        <?php if (!empty($so_phong_ngu) && $so_phong_ngu !== '0') : ?>
                            <div class="flex flex-col gap-1 border-r border-border/70 pr-4 sm:px-4 last:border-0">
                                <span class="text-xs uppercase tracking-wider text-charcoal-muted font-sans font-medium">Phòng ngủ</span>
                                <div class="text-2xl sm:text-3xl font-bold text-charcoal leading-none mt-1">
                                    <?= esc_html($so_phong_ngu); ?> <span class="text-base font-normal font-sans">PN</span>
                                </div>
                                <span class="text-xs text-charcoal-muted font-sans mt-1">Không gian riêng</span>
                            </div>
                        <?php endif; ?>

                        <!-- Metric 4: Phòng tắm/WC -->
                        <?php if (!empty($so_phong_ve_sinh) && $so_phong_ve_sinh !== '0') : ?>
                            <div class="flex flex-col gap-1 sm:pl-4">
                                <span class="text-xs uppercase tracking-wider text-charcoal-muted font-sans font-medium">Phòng tắm / WC</span>
                                <div class="text-2xl sm:text-3xl font-bold text-charcoal leading-none mt-1">
                                    <?= esc_html($so_phong_ve_sinh); ?> <span class="text-base font-normal font-sans">WC</span>
                                </div>
                                <span class="text-xs text-charcoal-muted font-sans mt-1">Thiết bị hiện đại</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </header>

                <!-- 2.5 Mobile Advisor Card (Tối ưu UX Mobile: người dùng thấy ngay chuyên viên phụ trách mà không phải cuộn xuống đáy) -->
                <?php
                get_template_part('template-parts/advisor-quick-card', null, [
                    'advisor_name'          => $advisor_name,
                    'advisor_avatar'        => $advisor_avatar,
                    'advisor_phone'         => $advisor_phone,
                    'advisor_zalo'          => $advisor_zalo,
                    'advisor_years'         => $advisor_years,
                    'advisor_listing_count' => $advisor_listing_count,
                    'role_title'            => 'Chuyên viên phụ trách tin này',
                    'badge_label'           => 'Môi giới xác minh',
                    'modal_cta_label'       => 'Đặt lịch xem nhà / Nhận tư vấn chi tiết',
                ]);
                ?>

                <!-- 3. Architectural Specifications Grid (Thông số kỹ thuật chi tiết - Chuẩn Apple SF Symbols) -->
                <?php
                $specs = [
                    [
                        'label' => 'Diện tích đất',
                        'value' => $dien_tich,
                        'icon'  => '<rect x="3.5" y="3.5" width="17" height="17" rx="3.5" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 14h17M10 14v7.5M14 3.5v10.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                    ],
                    [
                        'label' => 'Diện tích sử dụng',
                        'value' => $dien_tich_su_dung,
                        'icon'  => '<path d="M3 10.5L12 3l9 7.5V20a1.5 1.5 0 0 1-1.5 1.5H4.5A1.5 1.5 0 0 1 3 20v-9.5z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M18 7.5V4h-2.5v1.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9.5 21.5v-6a2.5 2.5 0 0 1 5 0v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                    ],
                    [
                        'label' => 'Phòng ngủ',
                        'value' => !empty($so_phong_ngu) ? ($so_phong_ngu . ' phòng ngủ') : '',
                        'icon'  => '<path d="M3 19.5v-10a1.5 1.5 0 0 1 1.5-1.5h15A1.5 1.5 0 0 1 21 9.5v10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M3 15.5h18" stroke="currentColor" stroke-width="1.8"/><path d="M3 21.5v-2M21 21.5v-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><rect x="5.5" y="10" width="5" height="3" rx="1" stroke="currentColor" stroke-width="1.6"/><rect x="13.5" y="10" width="5" height="3" rx="1" stroke="currentColor" stroke-width="1.6"/>',
                    ],
                    [
                        'label' => 'Phòng tắm / WC',
                        'value' => !empty($so_phong_ve_sinh) ? ($so_phong_ve_sinh . ' phòng tắm') : '',
                        'icon'  => '<path d="M4 21.5h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 12h14a1 1 0 0 1 1 1v2a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-2a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 12V5a2.5 2.5 0 0 1 5 0v1.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7 20l-1 2M17 20l1 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                    ],
                    [
                        'label' => 'Hướng nhà (Phong thủy)',
                        'value' => $display_huong_nha,
                        'icon'  => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><polygon points="14.5 9.5 12.5 14.5 9.5 14.5 11.5 9.5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" fill="currentColor"/>',
                    ],
                    [
                        'label' => 'Pháp lý sở hữu',
                        'value' => $display_phap_ly,
                        'icon'  => '<path d="M12 2.5l7 3.5v5.8c0 4.8-3.1 9.2-7 10.7-3.9-1.5-7-5.9-7-10.7V6L12 2.5z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
                    ],
                    [
                        'label' => 'Số tầng / Vị trí',
                        'value' => $so_tang,
                        'icon'  => '<path d="M3 21.5h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 21.5V6a1.5 1.5 0 0 1 1.5-1.5h6A1.5 1.5 0 0 1 14 6v15.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 10.5h4.5A1.5 1.5 0 0 1 20 12v9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 8h3M8 12h3M8 16h3M17 14h.01M17 17.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                    ],
                    [
                        'label' => 'Mặt tiền',
                        'value' => $mat_tien ? ($mat_tien . (is_numeric($mat_tien) ? ' m' : '')) : '',
                        'icon'  => '<rect x="3" y="4.5" width="18" height="15" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M7 4.5v3M11 4.5v2M15 4.5v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
                    ],
                    [
                        'label' => 'Số mặt tiền',
                        'value' => $display_so_mat_tien,
                        'icon'  => '<path d="M3 20.5h18M5 20.5V8.5a1.5 1.5 0 0 1 1.5-1.5h11a1.5 1.5 0 0 1 1.5 1.5v12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 4h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                    ],
                    [
                        'label' => 'Đường vào',
                        'value' => $duong_vao ? ($duong_vao . (is_numeric($duong_vao) ? ' m' : '')) : '',
                        'icon'  => '<path d="M5 21l3-18M19 21l-3-18M12 6v3M12 13v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                    ],
                    [
                        'label' => 'Tình trạng nội thất',
                        'value' => $noi_that ?: 'Bàn giao cao cấp',
                        'icon'  => '<path d="M4 14.5v-2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2M3 14.5a1.5 1.5 0 0 0 1.5 1.5h15a1.5 1.5 0 0 0 1.5-1.5v-2a1.5 1.5 0 0 0-1.5-1.5H4.5A1.5 1.5 0 0 0 3 12.5v2zM5 16v3M19 16v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                    ],
                    [
                        'label' => 'Tình trạng giao dịch',
                        'value' => $display_trang_thai,
                        'icon'  => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M8.5 12l2.5 2.5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
                    ],
                ];

                // Lọc bỏ các mục có giá trị rỗng
                $valid_specs = array_filter($specs, function ($item) {
                    return !empty($item['value']);
                });
                ?>

                <?php if (!empty($valid_specs)) : ?>
                    <section class="bg-white rounded-2xl border border-border/80 p-5 sm:p-6 shadow-subtle mb-4 sm:mb-5">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-black/[0.06]">
                            <div>
                                <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-1 block">Chi Tiết Kiến Trúc</span>
                                <h2 class="text-xl lg:text-2xl font-bold text-charcoal tracking-tight m-0">Thông Số Kỹ Thuật</h2>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-3.5">
                            <?php foreach ($valid_specs as $spec) : ?>
                                <div class="bg-[#F5F5F7] rounded-xl p-3 sm:p-3.5 border border-black/[0.04] flex flex-col gap-1.5 transition-all duration-200 hover:border-primary/40 hover:bg-white">
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-full bg-white text-primary border border-black/[0.06] flex items-center justify-center shrink-0">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                                                <?= $spec['icon']; ?>
                                            </svg>
                                        </span>
                                        <span class="text-[11px] uppercase tracking-wider text-charcoal-muted font-sans font-medium line-clamp-1">
                                            <?= esc_html($spec['label']); ?>
                                        </span>
                                    </div>
                                    <div class="text-sm sm:text-base font-semibold text-charcoal font-sans pl-1">
                                        <?= esc_html($spec['value']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- 3.5 Video Review & Trải Nghiệm Thực Tế Căn Hộ (TikTok & Reels) -->
                <?php if ($has_property_videos) : ?>
                    <?php
                    get_template_part('template-parts/section-video-review', null, [
                        'post_id'      => $post_id,
                        'videos'       => $property_videos,
                        'context'      => 'bat_dong_san',
                        'section_id'   => 'bds-video-review-section',
                        'title'        => 'Video Review Thực Tế Căn Hộ',
                        'subtitle'     => 'Góc nhìn chân thực, bóc tách chi tiết từng không gian, chất lượng hoàn thiện và pháp lý bởi chuyên viên <strong>' . esc_html($advisor_name) . '</strong>.',
                        'advisor'      => $advisor_data,
                    ]);
                    ?>
                <?php endif; ?>

                <!-- 4. Property Description & Content (Editorial Typography) -->
                <?php
                $post_content = get_the_content();
                if (!empty($post_content) && trim($post_content) !== '') :
                ?>
                    <section class="bg-white rounded-2xl border border-border/80 p-5 sm:p-6 shadow-subtle mb-4 sm:mb-5">
                        <div class="mb-4 pb-3 border-b border-black/[0.06]">
                            <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-1 block">Không Gian & Tiện Ích</span>
                            <h2 class="text-xl lg:text-2xl font-bold text-charcoal tracking-tight m-0">Thông Tin Mô Tả</h2>
                        </div>
                        <div class="prose max-w-none text-charcoal-muted leading-[1.8] font-sans text-[15px] lg:text-[16px] [&_p]:mb-3 [&_p:last-child]:mb-0 [&_h2]:font-bold [&_h2]:text-xl [&_h2]:text-charcoal [&_h2]:mt-5 [&_h2]:mb-2.5 [&_h3]:font-semibold [&_h3]:text-lg [&_h3]:text-charcoal [&_h3]:mt-4 [&_h3]:mb-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1.5 [&_li]:text-charcoal-muted [&_strong]:text-charcoal [&_blockquote]:border-l-2 [&_blockquote]:border-primary [&_blockquote]:pl-4 [&_blockquote]:italic [&_blockquote]:text-charcoal [&_img]:rounded-2xl [&_img]:shadow-card">
                            <?php the_content(); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- 5. Architectural Layout / Thiết Kế (If Available) -->
                <?php if ($thiet_ke) : ?>
                    <section class="bg-white rounded-2xl border border-border/80 p-5 sm:p-6 shadow-subtle mb-4 sm:mb-5">
                        <div class="mb-4 pb-3 border-b border-black/[0.06]">
                            <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-1 block">Bản Vẽ & Bố Trí Mặt Bằng</span>
                            <h2 class="text-xl lg:text-2xl font-bold text-charcoal tracking-tight m-0">Thiết Kế Kiến Trúc</h2>
                        </div>
                        <div class="text-charcoal-muted leading-[1.8] font-sans text-[15px] [&_p]:mb-3 [&_p:last-child]:mb-0">
                            <?= wp_kses_post($thiet_ke); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- 6. Google Map Facade (Lazy-loaded Click to Load) -->
                <?php if ($google_map) : ?>
                    <section class="bg-white rounded-2xl border border-border/80 p-5 sm:p-6 shadow-subtle mb-4 sm:mb-5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 pb-3 border-b border-black/[0.06]">
                            <div>
                                <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-1 block">Vị Trí & Hạ Tầng Kết Nối</span>
                                <h2 class="text-xl lg:text-2xl font-bold text-charcoal tracking-tight m-0">Vị Trí Trên Bản Đồ</h2>
                            </div>
                            <?php if ($dia_chi) : ?>
                                <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($dia_chi); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-xs font-sans font-medium text-primary hover:text-primary-hover underline underline-offset-2 transition-colors">
                                    <span>Mở trên Google Maps</span>
                                    <span>&rarr;</span>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="rounded-xl sm:rounded-2xl overflow-hidden border border-black/[0.06] bg-stone">
                            <?php
                            get_template_part('template-parts/map-facade', null, [
                                'embed_code' => $google_map,
                                'address'    => $dia_chi,
                            ]);
                            ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- 7. Loan Calculator (Dự toán tài chính & lãi suất) -->
                <section class="bg-surface rounded-2xl border border-border/80 p-5 sm:p-6 shadow-subtle mb-4 sm:mb-5">
                    <?php
                    $loan_default_value = 3000000000;
                    if (!empty($khoang_gia)) {
                        $gia_str = trim($khoang_gia);
                        if (preg_match('/([\d,\.]+)\s*tỷ/iu', $gia_str, $m)) {
                            $loan_default_value = (int) (floatval(str_replace(',', '.', str_replace('.', '', $m[1]))) * 1000000000);
                        } elseif (preg_match('/([\d,\.]+)\s*triệu/iu', $gia_str, $m)) {
                            $loan_default_value = (int) (floatval(str_replace(',', '.', str_replace('.', '', $m[1]))) * 1000000);
                        } else {
                            $raw = preg_replace('/[^\d]/', '', $gia_str);
                            if ($raw && strlen($raw) >= 6) {
                                $loan_default_value = (int) $raw;
                            }
                        }
                    }

                    get_template_part('template-parts/loan-calculator', null, [
                        'default_value' => $loan_default_value,
                    ]);
                    ?>
                </section>

                <!-- 8. Related Properties Carousel ("Bất động sản dành cho bạn") -->
                <?php if ($related_listings->have_posts()) : ?>
                    <section class="bg-white rounded-2xl border border-border/80 p-5 sm:p-6 shadow-subtle mt-4 md:mt-5 mb-4 sm:mb-5">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-black/[0.06]">
                            <div>
                                <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-1 block">Bất Động Sản Tương Tự</span>
                                <h2 class="text-xl lg:text-2xl font-bold text-charcoal tracking-tight m-0">Bất Động Sản Dành Cho Bạn</h2>
                            </div>
                            <a href="<?= esc_url(home_url('/bat-dong-san')); ?>" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-sans font-semibold text-primary hover:text-primary-hover transition-colors">
                                <span>Xem tất cả</span>
                                <span>&rarr;</span>
                            </a>
                        </div>

                        <div class="relative group/carousel w-full">
                            <div id="for-you-carousel" class="bds-carousel flex items-stretch gap-4 md:gap-5 overflow-x-auto scroll-smooth snap-x snap-mandatory no-scrollbar py-2 -mx-4 px-4 sm:mx-0 sm:px-0">
                                <?php
                                $hide_project_link_in_item = true;
                                while ($related_listings->have_posts()) : $related_listings->the_post();
                                    $value = get_post();
                                    echo '<div class="snap-start shrink-0 w-[82%] sm:w-[calc(50%-10px)] lg:w-[calc(33.333%-14px)] flex flex-col h-auto">';
                                    get_template_part('template-parts/card-transfer', null, [
                                        'post'       => $value,
                                        'show_badge' => true,
                                    ]);
                                    echo '</div>';
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </div>

                            <!-- Carousel Nav Controls -->
                            <div class="hidden md:block">
                                <button type="button" class="absolute top-1/2 -translate-y-1/2 -left-5 z-20 w-11 h-11 rounded-full bg-white border border-border shadow-subtle flex items-center justify-center cursor-pointer transition-all duration-300 hover:scale-110 hover:border-primary hover:bg-primary hover:text-white" data-carousel-prev="#for-you-carousel" aria-label="Bất động sản trước">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 18l-6-6 6-6" />
                                    </svg>
                                </button>
                                <button type="button" class="absolute top-1/2 -translate-y-1/2 -right-5 z-20 w-11 h-11 rounded-full bg-white border border-border shadow-subtle flex items-center justify-center cursor-pointer transition-all duration-300 hover:scale-110 hover:border-primary hover:bg-primary hover:text-white" data-carousel-next="#for-you-carousel" aria-label="Bất động sản tiếp theo">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 18l6-6-6-6" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

            </div>

            <!-- Sticky Right Sidebar: Advisor & Trust Panel (Chỉ hiển thị trên Desktop, ẩn trên Mobile vì không cần thiết) -->
            <aside class="hidden lg:!flex flex-col gap-4 sm:gap-5 lg:sticky lg:top-24">
                
                <!-- 1. Advisor Profile Card (Luôn luôn hiển thị) -->
                <?php
                get_template_part('template-parts/advisor-card', null, [
                    'advisor_name'          => $advisor_name,
                    'advisor_avatar'        => $advisor_avatar,
                    'advisor_phone'         => $advisor_phone,
                    'advisor_email'         => $advisor_email,
                    'advisor_zalo'          => $advisor_zalo,
                    'advisor_years'         => $advisor_years,
                    'advisor_listing_count' => $advisor_listing_count,
                    'advisor_profile_url'   => $advisor_profile_url,
                    'advisor_platform_name' => $advisor_platform_name,
                ]);
                ?>

                <!-- 2. Dynamic Sidebar (nếu có widget bổ sung) -->
                <?php if (is_active_sidebar('sidebar-single-bat-dong-san')) : ?>
                    <div class="sidebar-dynamic-widgets flex flex-col gap-4 sm:gap-5">
                        <?php dynamic_sidebar('sidebar-single-bat-dong-san'); ?>
                    </div>
                <?php endif; ?>

                <!-- 3. Promotional Banner (If filtered) -->
                <?php
                $promo_gif_url = apply_filters('batdongsan_sidebar_promo_gif_url', get_template_directory_uri() . '/assets/images/promo-project.gif');
                if ($promo_gif_url) :
                    $promo_gif_link = apply_filters('batdongsan_sidebar_promo_gif_link', home_url('/du-an'));
                ?>
                    <div class="bg-white rounded-2xl border border-black/[0.06] overflow-hidden shadow-subtle">
                        <a href="<?= esc_url($promo_gif_link); ?>" class="block group" aria-label="Khám phá các đại dự án bất động sản Hà Nội">
                            <img src="<?= esc_url($promo_gif_url); ?>" alt="Dự án bất động sản Hà Nội" class="w-full h-auto object-cover transition-transform duration-300 group-hover:scale-102" loading="lazy" decoding="async">
                        </a>
                    </div>
                <?php endif; ?>

                <!-- 4. Related Projects in the same district -->
                <?php if ($related_projects && $related_projects->have_posts()) : ?>
                    <div class="bg-surface rounded-2xl border border-border/80 p-4 sm:p-5 shadow-subtle">
                        <div class="pb-2.5 mb-3.5 border-b border-black/[0.06]">
                            <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-1 block">Khu Vực <?= esc_html($khu_vuc_name ?: 'Hà Nội'); ?></span>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-charcoal/70 m-0">Dự Án Lân Cận</h3>
                        </div>
                        <ul class="list-none p-0 m-0 space-y-2">
                            <?php 
                            $bds_publish_count = (int) (wp_count_posts('bat-dong-san')->publish ?? 0);
                            while ($related_projects->have_posts()) : $related_projects->the_post(); 
                            ?>
                                <li>
                                    <a href="<?= get_permalink(); ?>" class="flex items-center justify-between text-sm text-charcoal hover:text-primary py-1 transition-colors group">
                                        <span class="line-clamp-1 font-sans text-[13px] group-hover:translate-x-0.5 transition-transform"><?php the_title(); ?></span>
                                        <span class="text-xs font-sans text-charcoal-muted shrink-0 ml-2">(<?= $bds_publish_count; ?>)</span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- 5. Featured Listings Sidebar Widget -->
                <?php if ($featured_listings->have_posts()) : ?>
                    <div class="bg-surface rounded-2xl border border-border/80 p-4 sm:p-5 shadow-subtle">
                        <div class="pb-2.5 mb-3.5 border-b border-black/[0.06]">
                            <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-1 block">Đề Xuất Phù Hợp</span>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-charcoal/70 m-0">Bất Động Sản Nổi Bật</h3>
                        </div>
                        <ul class="list-none p-0 m-0 space-y-2.5">
                            <?php while ($featured_listings->have_posts()) : $featured_listings->the_post(); ?>
                                <li class="pb-2.5 border-b border-black/[0.06] last:border-0 last:pb-0">
                                    <a href="<?= get_permalink(); ?>" class="block group">
                                        <div class="text-[13px] font-sans font-medium text-charcoal group-hover:text-primary transition-colors line-clamp-2 leading-snug">
                                            <?php the_title(); ?>
                                        </div>
                                        <div class="flex items-center justify-between mt-1 text-xs text-charcoal-muted">
                                            <span class="font-bold text-primary">
                                                <?= esc_html(get_field('khoang_gia', get_the_ID()) ?: 'Giá liên hệ'); ?>
                                            </span>
                                            <span><?= esc_html(get_field('dien_tich', get_the_ID()) ?: ''); ?></span>
                                        </div>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

            </aside>

        </div>
    </div>

    <!-- Mobile Sticky Floating Conversion Bar (Bottom) -->
    <div class="fixed bottom-0 inset-x-0 z-40 bg-surface/95 backdrop-blur-md border-t border-border p-3 lg:hidden shadow-[0_-4px_20px_rgba(0,0,0,0.08)]">
        <div class="flex items-center justify-between gap-3 max-w-lg mx-auto">
            <div class="flex flex-col min-w-0">
                <span class="text-[11px] uppercase tracking-wider text-charcoal-muted font-sans font-medium line-clamp-1">
                    <?= $is_cho_thue ? 'Giá thuê' : 'Mức giá'; ?>
                </span>
                <span class="text-base sm:text-lg font-extrabold text-primary leading-tight truncate">
                    <?= esc_html($is_cho_thue ? ($gia_cho_thue ?: ($khoang_gia ?: 'Thỏa thuận')) : ($khoang_gia ?: 'Giá liên hệ')); ?>
                </span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <?php if ($advisor_zalo) : ?>
                    <a href="<?= esc_url($advisor_zalo); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#0068FF] text-white text-xs font-sans font-semibold shadow-sm hover:opacity-95 transition-all">
                        <span>Zalo</span>
                    </a>
                <?php endif; ?>
                <?php if ($advisor_phone) : ?>
                    <a href="tel:<?= esc_attr(preg_replace('/\D/', '', $advisor_phone)); ?>" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-white text-xs font-sans font-semibold shadow-button hover:bg-primary-hover transition-all">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <span>Gọi ngay</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
    endwhile;
endif;

get_footer();
