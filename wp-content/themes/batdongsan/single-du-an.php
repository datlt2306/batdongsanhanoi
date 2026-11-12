<?php

/**
 * Template cho single post type du-an - Luxury Real Estate Redesign
 * 
 * @package BatDongSan
 */

get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        $project_id = get_the_ID();

        // Helper làm sạch text dấu ngoặc vuông thô từ dữ liệu nháp (VD: "[Đang cập nhật từ CĐT]" -> "Đang cập nhật")
        $clean_draft_text = function ($val, $fallback = 'Đang cập nhật') {
            $str = trim((string) $val);
            if ($str === '') return $fallback;
            $str = preg_replace('/^\[(.*)\]$/u', '$1', $str);
            return trim($str);
        };

        // Kiểm tra xem đây là Phân khu hay Dự án lớn
        $du_an_cha = get_field('du_an_cha', $project_id);
        $is_subdivision = !empty($du_an_cha);

        // Xử lý thừa hưởng dữ liệu ĐỆ QUY (Con -> Cha -> Ông)
        $address = batdongsan_get_project_meta_recursive('address', $project_id);
        $chu_dau_tu = batdongsan_get_project_meta_recursive('chu_dau_tu', $project_id);
        $quy_mo = batdongsan_get_project_meta_recursive('quy_mo', $project_id);
        $map_embed = batdongsan_get_project_meta_recursive('map_embed', $project_id);
        $gallery_du_an = batdongsan_get_project_meta_recursive('gallery_du_an', $project_id) ?: [];

        $dien_tich = function_exists('batdongsan_get_project_meta_recursive')
            ? batdongsan_get_project_meta_recursive('dien_tich', $project_id)
            : get_field('dien_tich', $project_id);

        $so_can = get_field('so_can', $project_id);
        $so_toa = get_field('so_toa', $project_id);
        if (!$so_toa) {
            $so_toa = function_exists('batdongsan_get_project_meta_recursive')
                ? batdongsan_get_project_meta_recursive('so_toa', $project_id)
                : '';
        }
        $ban_giao = get_field('ban_giao', $project_id);
        $tien_ich = get_field('tien_ich', $project_id);
        $tien_ich_ngoai_khu = get_field('tien_ich_ngoai_khu', $project_id);
        $danh_sach_loai_can_ho = get_field('danh_sach_loai_can_ho', $project_id) ?: [];
        $chinh_sach = get_field('chinh_sach', $project_id);
        $thu_vien_anh = get_field('thu_vien_anh', $project_id);
        $mat_bang_gallery = get_field('mat_bang_gallery', $project_id);
        $gia_ban_du_kien = get_field('gia_ban_du_kien', $project_id);
        if (!$gia_ban_du_kien) {
            $price_val = batdongsan_get_project_meta_recursive('price', $project_id);
            if ($price_val) {
                $price_str = trim((string) $price_val);
                if (preg_match('/cập nhật|liên hệ|thỏa thuận|chưa có/iu', $price_str)) {
                    $gia_ban_du_kien = 'Liên hệ báo giá';
                } elseif (is_numeric(str_replace([',', ' '], ['.', ''], $price_str))) {
                    $gia_ban_du_kien = 'Từ ' . $price_str . ' tỷ';
                } elseif (preg_match('/tỷ|triệu|m2|m²/iu', $price_str)) {
                    $gia_ban_du_kien = preg_match('/^(từ|chỉ từ|khoảng)/iu', $price_str) ? $price_str : 'Từ ' . $price_str;
                } else {
                    $gia_ban_du_kien = $price_str;
                }
            }
        }

        // Lấy pháp lý dự án (fallback 'Sổ hồng lâu dài' nếu không có trường riêng)
        $phap_ly = function_exists('batdongsan_get_project_meta_recursive')
            ? batdongsan_get_project_meta_recursive('phap_ly', $project_id)
            : get_field('phap_ly', $project_id);
        if (!$phap_ly) {
            $phap_ly = 'Sổ hồng lâu dài';
        }

        // 1. Pháp lý dự án chi tiết (chỉ lấy dữ liệu thực từ ACF, không fake dữ liệu)
        $phap_ly_hinh_thuc_so_huu = get_field('phap_ly_hinh_thuc_so_huu', $project_id);
        $phap_ly_quy_hoach = get_field('phap_ly_quy_hoach', $project_id);
        $phap_ly_gpxd = get_field('phap_ly_gpxd', $project_id);
        $phap_ly_dieu_kien_ban_giao = get_field('phap_ly_dieu_kien_ban_giao', $project_id);
        $phap_ly_ngan_hang_bao_lanh = get_field('phap_ly_ngan_hang_bao_lanh', $project_id);
        $phap_ly_mo_ta = get_field('phap_ly_mo_ta', $project_id);

        $has_legal = !empty($phap_ly_hinh_thuc_so_huu) || !empty($phap_ly_quy_hoach) || !empty($phap_ly_gpxd) || !empty($phap_ly_dieu_kien_ban_giao) || !empty($phap_ly_ngan_hang_bao_lanh) || !empty($phap_ly_mo_ta);

        // 2. Phối cảnh & Tiến độ thực tế (chỉ lấy nếu có dữ liệu)
        $phoi_canh_3d_gallery = get_field('phoi_canh_3d_gallery', $project_id);
        if (!is_array($phoi_canh_3d_gallery)) $phoi_canh_3d_gallery = [];
        $nha_mau_gallery = get_field('nha_mau_gallery', $project_id);
        if (!is_array($nha_mau_gallery)) $nha_mau_gallery = [];
        $tien_do_gallery = get_field('tien_do_gallery', $project_id);
        if (!is_array($tien_do_gallery)) $tien_do_gallery = [];
        $tien_do_ghi_chu = get_field('tien_do_ghi_chu', $project_id);
        $tien_do_ngay_cap_nhat = get_field('tien_do_ngay_cap_nhat', $project_id);

        $all_display_gallery = [];
        if (!empty($tien_do_gallery)) {
            foreach ($tien_do_gallery as $img) {
                $img_id = is_array($img) ? ($img['ID'] ?? 0) : (int) $img;
                if ($img_id > 0) $all_display_gallery[] = ['id' => $img_id, 'cat' => 'progress'];
            }
        }
        if (!empty($nha_mau_gallery)) {
            foreach ($nha_mau_gallery as $img) {
                $img_id = is_array($img) ? ($img['ID'] ?? 0) : (int) $img;
                if ($img_id > 0) $all_display_gallery[] = ['id' => $img_id, 'cat' => 'showroom'];
            }
        }
        if (!empty($phoi_canh_3d_gallery)) {
            foreach ($phoi_canh_3d_gallery as $img) {
                $img_id = is_array($img) ? ($img['ID'] ?? 0) : (int) $img;
                if ($img_id > 0) $all_display_gallery[] = ['id' => $img_id, 'cat' => '3d'];
            }
        }
        $has_gallery_progress = !empty($all_display_gallery) || !empty($tien_do_ghi_chu);

        // 4. Bảng giá chi tiết & Tiêu chuẩn bàn giao
        $bang_gia_can_ho = get_field('bang_gia_can_ho', $project_id);
        if (!is_array($bang_gia_can_ho)) {
            $bang_gia_can_ho = [];
        }
        $tieu_chuan_ban_giao = get_field('tieu_chuan_ban_giao', $project_id);
        $tai_lieu = get_field('tai_lieu_brochure', $project_id);
        $has_pricing_policy = !empty($bang_gia_can_ho) || (!empty($chinh_sach) && trim($chinh_sach) !== '') || (!empty($tai_lieu) && !empty($tai_lieu['url'])) || (!empty($tieu_chuan_ban_giao) && trim($tieu_chuan_ban_giao) !== '');

        $hotline = get_field('hotline', $project_id) ?: '0934 69 9191';
        $email = get_field('email', $project_id) ?: 'quynhnguyen291@gmail.com';
        // Thông tin môi giới (option) - sử dụng helper function với fallback từ post meta
        $advisor_data = batdongsan_get_advisor_data([
            'lien_he_email' => $email, // Fallback email từ post nếu không có trong ACF options
        ]);
        $advisor_name = $advisor_data['advisor_name'];
        $advisor_avatar = $advisor_data['advisor_avatar'];
        $advisor_phone = $advisor_data['advisor_phone'];
        $advisor_email = $advisor_data['advisor_email'];
        $advisor_zalo = $advisor_data['advisor_zalo'];
        $advisor_years = $advisor_data['advisor_years'];
        $advisor_listing_count = $advisor_data['advisor_listing_count'];
        $advisor_profile_url = $advisor_data['advisor_profile_url'];
        $advisor_platform_name = $advisor_data['advisor_platform_name'];

        // Lấy FAQ từ ACF Repeater (chỉ hiển thị nếu có nhập)
        $faq_items = get_field('cau_hoi_thuong_gap', $project_id);
        if (!is_array($faq_items)) {
            $faq_items = [];
        }
        $has_faq = !empty($faq_items);

        // Dự án lân cận: CHỈ hiển thị khi có dự án thực sự ở gần (cùng khu vực taxonomy hoặc cùng quận/huyện)
        $related_projects_query = null;
        $has_related_projects   = false;
        $nearby_district_name   = '';

        // Ưu tiên 1: Lấy theo taxonomy khu-vuc-du-an hoặc batdongsan_get_related_projects
        $base_related = batdongsan_get_related_projects($project_id, 6);
        if ($base_related && $base_related->have_posts()) {
            $related_projects_query = $base_related;
            $has_related_projects   = true;
        } else {
            // Ưu tiên 2: Tìm theo cùng quận/huyện dựa vào địa chỉ dự án
            $project_addr = !empty($address) ? $address : get_the_title($project_id);
            $hanoi_districts = [
                'Ba Đình', 'Hoàn Kiếm', 'Tây Hồ', 'Long Biên', 'Cầu Giấy', 'Đống Đa',
                'Hai Bà Trưng', 'Hoàng Mai', 'Thanh Xuân', 'Sóc Sơn', 'Đông Anh', 'Gia Lâm',
                'Nam Từ Liêm', 'Thanh Trì', 'Bắc Từ Liêm', 'Mê Linh', 'Hà Đông', 'Sơn Tây',
                'Ba Vì', 'Phúc Thọ', 'Đan Phượng', 'Hoài Đức', 'Quốc Oai', 'Thạch Thất',
                'Chương Mỹ', 'Thanh Oai', 'Thường Tín', 'Phú Xuyên', 'Ứng Hòa', 'Mỹ Đức'
            ];
            foreach ($hanoi_districts as $d) {
                if (mb_stripos($project_addr, $d) !== false || mb_stripos(get_the_title($project_id), $d) !== false) {
                    $nearby_district_name = $d;
                    break;
                }
            }
            if (!empty($nearby_district_name)) {
                $district_related = new WP_Query([
                    'post_type'      => 'du-an',
                    'posts_per_page' => 6,
                    'post_status'    => 'publish',
                    'post__not_in'   => [$project_id],
                    'meta_query'     => [
                        [
                            'key'     => 'address',
                            'value'   => $nearby_district_name,
                            'compare' => 'LIKE',
                        ],
                    ],
                ]);
                if ($district_related->have_posts()) {
                    $related_projects_query = $district_related;
                    $has_related_projects   = true;
                }
            }
        }
        wp_reset_postdata();

        // Kiểm tra và lấy danh sách BĐS thuộc dự án (bat-dong-san) qua normalized numeric meta relation
        $listing_query = batdongsan_get_project_listings($project_id, ['posts_per_page' => 1]);
        $has_listing = $listing_query->have_posts();
        wp_reset_postdata();

        // Chỉ hiển thị menu "Xem thêm" nếu có ít nhất một trong hai
        $has_more_menu = $has_faq || $has_related_projects;

        // Breadcrumb dự án (Dự án / Khu vực / Tên dự án)
        $breadcrumb_parts = batdongsan_get_du_an_breadcrumb($project_id);

        // Lấy loại hình dự án từ taxonomy loai_hinh_du_an (không hardcode)
        $loai_hinh_terms = get_the_terms($project_id, 'loai_hinh_du_an');
        $loai_hinh_name  = (!empty($loai_hinh_terms) && !is_wp_error($loai_hinh_terms)) ? $loai_hinh_terms[0]->name : '';
        if (empty($loai_hinh_name)) {
            $loai_hinh_meta = get_field('loai_hinh_du_an', $project_id);
            if (!empty($loai_hinh_meta)) {
                if (is_numeric($loai_hinh_meta)) {
                    $t = get_term((int) $loai_hinh_meta, 'loai_hinh_du_an');
                    if ($t && !is_wp_error($t)) {
                        $loai_hinh_name = $t->name;
                    }
                } elseif (is_object($loai_hinh_meta) && isset($loai_hinh_meta->name)) {
                    $loai_hinh_name = $loai_hinh_meta->name;
                } elseif (is_string($loai_hinh_meta)) {
                    $loai_hinh_name = $loai_hinh_meta;
                }
            }
        }

        $trang_thai = get_field('trang_thai', $project_id);
        $trang_thai_labels = [
            'dang_mo_ban'   => 'Đang mở bán',
            'sap_mo_ban'    => 'Sắp mở bán',
            'da_ban_het'    => 'Đã bán hết',
            'tam_dung'      => 'Tạm dừng',
            'dang_cap_nhat' => 'Đang cập nhật',
        ];
        $trang_thai_label = isset($trang_thai_labels[$trang_thai]) ? $trang_thai_labels[$trang_thai] : '';
        $status_colors = [
            'dang_mo_ban'   => 'bg-emerald-600 text-white shadow-sm',
            'sap_mo_ban'    => 'bg-amber-500 text-white shadow-sm',
            'da_ban_het'    => 'bg-stone text-charcoal-muted border border-border',
            'tam_dung'      => 'bg-charcoal/70 text-white/90 backdrop-blur-sm',
            'dang_cap_nhat' => 'bg-charcoal/70 text-white/90 backdrop-blur-sm',
        ];
        $status_color = $status_colors[$trang_thai] ?? 'bg-stone text-charcoal-muted border border-border';
        $is_featured = get_field('featured', $project_id);

?>
        <main class="bg-canvas min-h-screen py-6 pb-20 max-lg:py-4 max-lg:pb-10 max-lg:overflow-x-clip">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div>
                    <!-- 1. Utility Row: Breadcrumb -->
                    <div class="py-1.5 mb-2.5">
                        <div class="min-w-0 overflow-hidden">
                            <?php if (function_exists('rank_math_the_breadcrumbs')) : ?>
                                <div class="project-detail__breadcrumb-wrapper text-xs text-charcoal-muted truncate">
                                    <?php rank_math_the_breadcrumbs(); ?>
                                </div>
                            <?php else : ?>
                                <?php
                                get_template_part('template-parts/breadcrumb', null, [
                                    'breadcrumb_parts' => $breadcrumb_parts,
                                    'prefix_class'     => 'project-detail',
                                    'current_label'    => get_the_title(),
                                    'mobile_optimized' => true,
                                ]);
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 2. Apple Split Command Header -->
                    <header class="mb-4">
                        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                            <!-- Left: Eyebrow + Title + Meta -->
                            <div class="min-w-0 max-w-3xl">
                                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                    <?php if ($loai_hinh_name) : ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-black/[0.05] text-charcoal border border-black/[0.08] text-[11px] font-semibold uppercase tracking-wider">
                                            <?php echo esc_html($loai_hinh_name); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($trang_thai_label)) : ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold <?php echo esc_attr($status_color); ?>">
                                            <?php if ($trang_thai === 'dang_mo_ban' || $trang_thai === 'dang-mo-ban') : ?>
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-200 animate-pulse"></span>
                                            <?php endif; ?>
                                            <span><?php echo esc_html($trang_thai_label); ?></span>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h1 class="text-2xl sm:text-3xl lg:text-[34px] font-bold text-charcoal m-0 leading-tight tracking-tight">
                                    <?php the_title(); ?>
                                </h1>

                                <?php if ($address): ?>
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-charcoal-muted text-[13px] mt-2 leading-relaxed">
                                        <span class="inline-flex items-center text-primary shrink-0" aria-hidden="true">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 2.5a6.5 6.5 0 0 0-6.5 6.5c0 4.8 6.5 12.5 6.5 12.5s6.5-7.7 6.5-12.5a6.5 6.5 0 0 0-6.5-6.5z" />
                                                <circle cx="12" cy="9" r="2.2" />
                                            </svg>
                                        </span>
                                        <span class="text-charcoal-muted"><?php echo esc_html($address); ?></span>
                                        <?php if ($is_subdivision && !empty($du_an_cha)) : ?>
                                            <span class="text-black/20">·</span>
                                            <span>Thuộc: <a href="<?php echo esc_url(get_permalink($du_an_cha->ID)); ?>" class="font-medium text-primary hover:underline"><?php echo esc_html($du_an_cha->post_title); ?></a></span>
                                        <?php endif; ?>
                                        <span class="text-black/20">·</span>
                                        <button
                                            type="button"
                                            class="project-detail__view-map inline-flex items-center gap-0.5 border-0 bg-transparent text-primary font-medium text-[13px] cursor-pointer p-0 hover:underline"
                                            data-scroll-target="#project-location-section">
                                            <span>Bản đồ</span>
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Right: Price & Fast CTA Button + Prominent Share -->
                            <div class="flex flex-row md:flex-col items-center md:items-end justify-between md:justify-center gap-3 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-black/[0.06]">
                                <?php if (!empty($gia_ban_du_kien)) : ?>
                                    <?php $is_contact_price = (mb_stripos($gia_ban_du_kien, "liên hệ") !== false); ?>
                                    <div class="text-left md:text-right flex flex-col md:items-end">
                                        <span class="text-[11px] font-semibold uppercase tracking-wider text-charcoal-muted/90 block mb-0.5">Giá bán dự kiến</span>
                                        <?php if ($is_contact_price) : ?>
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-primary font-bold text-sm sm:text-base">
                                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                                <span>Liên hệ nhận báo giá</span>
                                            </div>
                                        <?php else : ?>
                                            <span class="text-2xl sm:text-3xl font-extrabold text-primary tracking-tight font-sans leading-none">
                                                <?php echo esc_html($gia_ban_du_kien); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center gap-2">
                                    <!-- Nút Chia sẻ nổi bật (Apple Luxury Capsule) -->
                                    <?php
                                    get_template_part('template-parts/share-dropdown', null, [
                                        'title'   => get_the_title(),
                                        'heading' => 'Chia sẻ dự án',
                                        'align'   => 'right',
                                    ]);
                                    ?>

                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-full bg-primary text-white text-xs font-semibold hover:bg-primary-dark transition-all shadow-subtle hover:shadow-md cursor-pointer"
                                        data-scroll-target="#project-loan-section">
                                        <span>Tư vấn &amp; Báo giá</span>
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </header>

                    <!-- Gallery ảnh (Desktop: order 3, Mobile: order 1) -->
                    <?php
                    // Chuyển đổi gallery ACF từ array sang mảng ID ảnh
                    $gallery_images = [];
                    if (is_array($gallery_du_an) && !empty($gallery_du_an)) {
                        foreach ($gallery_du_an as $image) {
                            $image_id = is_array($image) && isset($image['ID']) ? (int) $image['ID'] : (is_numeric($image) ? (int) $image : 0);
                            if ($image_id > 0) {
                                $gallery_images[] = $image_id;
                            }
                        }
                    }

                    // Chuẩn bị dữ liệu hiển thị thông số trên ảnh chính (nếu có)
                    $so_can_val = '';
                    $so_can_unit = 'căn hộ';
                    if (!empty($so_can)) {
                        $so_can_clean = trim((string) $so_can);
                        if (preg_match('/(?:hơn\s+)?([0-9.,]+)\s*(.*)/iu', $so_can_clean, $m_can)) {
                            $prefix = (stripos($so_can_clean, 'hơn') === 0) ? '> ' : '';
                            $so_can_val = $prefix . $m_can[1];
                            $unit_raw = trim($m_can[2]);
                            $so_can_unit = !empty($unit_raw) ? (mb_strlen($unit_raw) > 12 ? 'căn hộ' : $unit_raw) : 'căn hộ';
                        } else {
                            $so_can_val = mb_strimwidth($so_can_clean, 0, 15, '...');
                        }
                    }

                    $quy_mo_val = '';
                    $quy_mo_unit = 'm²';
                    if (!empty($quy_mo)) {
                        $quy_mo_clean = trim((string) $quy_mo);
                        if (preg_match('/^([0-9.,]+)\s*([a-zA-Z²]+)?/u', $quy_mo_clean, $m_qm)) {
                            $quy_mo_val = $m_qm[1];
                            $quy_mo_unit = !empty($m_qm[2]) ? trim($m_qm[2]) : 'm²';
                        } else {
                            $quy_mo_val = mb_strimwidth($quy_mo_clean, 0, 12, '...');
                            $quy_mo_unit = '';
                        }
                    }
                    ?>
                    <?php if (!empty($gallery_images)): ?>
                        <?php
                        $total_images     = count($gallery_images);
                        $main_image_id    = $gallery_images[0];
                        $secondary_images = array_slice($gallery_images, 1, 4);
                        $hidden_images    = array_slice($gallery_images, 1 + count($secondary_images));
                        ?>
                        <!-- Mobile Gallery: Swiper 1 item (Chỉ trên mobile < 1024px, tắt zoom tối ưu tốc độ, counter 1/N) -->
                        <div class="project-detail__hero-gallery-swiper swiper lg:!hidden w-full aspect-[16/10] sm:aspect-[16/9] bg-stone relative rounded-2xl overflow-hidden mb-4 border border-black/[0.08]">
                            <div class="swiper-wrapper">
                                <?php foreach ($gallery_images as $idx => $img_id) : ?>
                                    <div class="swiper-slide w-full h-full">
                                        <?php echo wp_get_attachment_image($img_id, 'large', false, [
                                            'class'   => 'w-full h-full object-cover select-none',
                                            'loading' => $idx === 0 ? 'eager' : 'lazy',
                                        ]); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Status Badges trên mobile -->
                            <div class="flex absolute top-3 left-3 z-10 gap-1.5 items-center pointer-events-none">
                                <?php if ($trang_thai_label) : ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/90 backdrop-blur-md border border-black/[0.06] text-charcoal shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full <?php echo (stripos($trang_thai_label, 'Bàn giao') !== false) ? 'bg-emerald-500' : 'bg-primary'; ?>"></span>
                                        <span><?php echo esc_html($trang_thai_label); ?></span>
                                    </span>
                                <?php endif; ?>
                                <?php if ($is_featured) : ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gold text-white shadow-sm">
                                        <svg class="w-2.5 h-2.5 fill-white" viewBox="0 0 24 24"><path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5-5.9-3.1-5.9 3.1 1.2-6.5-4.8-4.6 6.6-.9 2.9-6z"/></svg>
                                        <span>Nổi bật</span>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Badge đếm ảnh góc dưới phải (1 / N) -->
                            <?php if ($total_images > 1) : ?>
                                <div class="project-hero-swiper__counter absolute bottom-3 right-3 z-10 px-2.5 py-1 rounded-full bg-black/60 backdrop-blur-md text-white text-[11px] font-sans font-medium tracking-wider pointer-events-none">
                                    <span class="project-hero-swiper__current">1</span> / <span><?php echo $total_images; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Desktop Gallery: Cân đối & Fit tỉ lệ chuẩn (Desktop >= 1024px) -->
                        <?php
                        $grid_classes = 'project-detail__hero-gallery-grid w-full h-[400px] lg:h-[430px] xl:h-[460px] rounded-2xl overflow-hidden bg-stone border border-black/[0.06] shadow-sm select-none';
                        if (empty($secondary_images)) {
                            $grid_classes .= ' project-detail__hero-gallery-grid--single';
                        }
                        ?>
                        <div class="hidden lg:!block mb-6">
                            <div class="<?php echo esc_attr($grid_classes); ?>">
                                <?php
                                // Ảnh lớn bên trái
                                $full = wp_get_attachment_image_src($main_image_id, 'full');
                                if ($full && !empty($full[0])) :
                                    $full_url = $full[0];
                                    $full_w = isset($full[1]) ? (int) $full[1] : 0;
                                    $full_h = isset($full[2]) ? (int) $full[2] : 0;
                                    $data_size = ($full_w > 0 && $full_h > 0) ? $full_w . '-' . $full_h : '';
                                ?>
                                    <a
                                        href="<?php echo esc_url($full_url); ?>"
                                        class="project-detail__hero-gallery-item project-detail__hero-gallery-item--main relative group/hero block w-full h-full min-h-0 min-w-0 overflow-hidden bg-slate-900"
                                        <?php if ($data_size): ?>data-lg-size="<?php echo esc_attr($data_size); ?>" <?php endif; ?>>
                                        <?php echo wp_get_attachment_image($main_image_id, 'large', false, [
                                            'class' => 'project-detail__hero-gallery-img absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-out group-hover/hero:scale-105',
                                        ]); ?>

                                        <!-- Gradient bóng mờ nhẹ để nổi bật chữ & badge -->
                                        <div class="absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-black/40 via-transparent to-transparent pointer-events-none"></div>
                                        <div class="absolute inset-x-0 bottom-0 h-36 bg-gradient-to-t from-black/80 via-black/30 to-transparent pointer-events-none"></div>

                                        <!-- Badge trạng thái góc trên bên trái -->
                                        <div class="flex absolute top-3.5 left-3.5 z-10 gap-2 items-center pointer-events-none">
                                            <?php if ($trang_thai_label) : ?>
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/95 text-charcoal shadow-sm border border-black/[0.08] backdrop-blur-md">
                                                    <?php if ($trang_thai === 'dang_mo_ban' || $trang_thai === 'dang-mo-ban') : ?>
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    <?php endif; ?>
                                                    <span><?php echo esc_html($trang_thai_label); ?></span>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($is_featured) : ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-gold text-white shadow-sm">
                                                    <svg class="w-3 h-3 fill-white" viewBox="0 0 24 24"><path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5-5.9-3.1-5.9 3.1 1.2-6.5-4.8-4.6 6.6-.9 2.9-6z"/></svg>
                                                    <span>Nổi bật</span>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Thông số nổi bật góc dưới bên trái (Số căn, Quy mô) -->
                                        <?php if (!empty($so_can_val) || !empty($quy_mo_val)): ?>
                                            <div class="absolute bottom-3.5 left-3.5 sm:bottom-4 sm:left-4 z-10 flex items-center gap-5 sm:gap-6 text-white pointer-events-none">
                                                <?php if (!empty($so_can_val)): ?>
                                                    <div class="flex items-center gap-2.5">
                                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center border border-white/40 bg-black/30 backdrop-blur-md text-white shrink-0 shadow-sm">
                                                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                                                <path d="M12 11c-1.1 0-2 .9-2 2 0 1.5 2 3.5 2 3.5s2-2 2-3.5c0-1.1-.9-2-2-2z"/>
                                                            </svg>
                                                        </div>
                                                        <div class="flex flex-col leading-tight">
                                                            <span class="text-lg font-bold text-white tracking-tight leading-none drop-shadow-sm"><?php echo esc_html($so_can_val); ?></span>
                                                            <span class="text-[11px] text-white/90 font-medium mt-1 leading-none drop-shadow-sm"><?php echo esc_html($so_can_unit); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($quy_mo_val)): ?>
                                                    <div class="flex items-center gap-2.5">
                                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center border border-white/40 bg-black/30 backdrop-blur-md text-white shrink-0 shadow-sm">
                                                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <rect x="4" y="2" width="16" height="20" rx="2" ry="2"/>
                                                                <path d="M9 22v-4h6v4"/>
                                                                <path d="M8 6h.01M16 6h.01M8 10h.01M16 10h.01M8 14h.01M16 14h.01"/>
                                                            </svg>
                                                        </div>
                                                        <div class="flex flex-col leading-tight">
                                                            <span class="text-lg font-bold text-white tracking-tight leading-none drop-shadow-sm"><?php echo esc_html($quy_mo_val); ?></span>
                                                            <span class="text-[11px] text-white/90 font-medium mt-1 leading-none drop-shadow-sm"><?php echo esc_html($quy_mo_unit); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($secondary_images)): ?>
                                    <?php
                                    $secondary_count = count($secondary_images);
                                    // Xác định layout grid phù hợp với số lượng ảnh
                                    $side_grid_classes = 'project-detail__hero-gallery-side grid gap-1.5 w-full h-full min-h-0 min-w-0 ';
                                    if ($secondary_count === 1) {
                                        $side_grid_classes .= 'project-detail__hero-gallery-side--1col project-detail__hero-gallery-side--1row';
                                    } elseif ($secondary_count === 2) {
                                        $side_grid_classes .= 'project-detail__hero-gallery-side--1col';
                                    }
                                    ?>
                                    <div class="<?php echo esc_attr($side_grid_classes); ?>">
                                        <?php
                                        $index = 0;
                                        foreach ($secondary_images as $image_id) :
                                            $index++;
                                            $full = wp_get_attachment_image_src($image_id, 'full');
                                            if (!$full || empty($full[0])) {
                                                continue;
                                            }
                                            $full_url  = $full[0];
                                            $full_w    = isset($full[1]) ? (int) $full[1] : 0;
                                            $full_h    = isset($full[2]) ? (int) $full[2] : 0;
                                            $data_size = ($full_w > 0 && $full_h > 0) ? $full_w . '-' . $full_h : '';

                                            $is_last_visible_desktop = ($index === $secondary_count);
                                            $item_span_class = '';
                                            if ($secondary_count === 3 && $index === 3) {
                                                $item_span_class = 'col-span-2';
                                            }
                                        ?>
                                            <a
                                                href="<?php echo esc_url($full_url); ?>"
                                                class="project-detail__hero-gallery-item project-detail__hero-gallery-item--side <?php echo esc_attr($item_span_class); ?> relative group/thumb block w-full h-full min-h-0 min-w-0 overflow-hidden bg-slate-900"
                                                <?php if ($data_size): ?>data-lg-size="<?php echo esc_attr($data_size); ?>" <?php endif; ?>>
                                                <?php echo wp_get_attachment_image($image_id, 'large', false, [
                                                    'class' => 'project-detail__hero-gallery-img absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-out group-hover/thumb:scale-105'
                                                ]); ?>

                                                <?php if ($is_last_visible_desktop): ?>
                                                    <!-- Badge tổng số ảnh góc dưới phải -->
                                                    <div class="absolute bottom-3 right-3 z-10 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-black/65 backdrop-blur-md text-white text-xs font-semibold shadow-md pointer-events-none group-hover/thumb:bg-black/80 transition-colors">
                                                        <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                                            <polyline points="21 15 16 10 5 21"/>
                                                        </svg>
                                                        <span><?php echo (int) $total_images; ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($hidden_images)): ?>
                                    <?php foreach ($hidden_images as $image_id) : ?>
                                        <?php
                                        $full = wp_get_attachment_image_src($image_id, 'full');
                                        if (!$full || empty($full[0])) {
                                            continue;
                                        }
                                        $full_url  = $full[0];
                                        $full_w    = isset($full[1]) ? (int) $full[1] : 0;
                                        $full_h    = isset($full[2]) ? (int) $full[2] : 0;
                                        $data_size = ($full_w > 0 && $full_h > 0) ? $full_w . '-' . $full_h : '';
                                        ?>
                                        <a
                                            href="<?php echo esc_url($full_url); ?>"
                                            class="project-detail__hero-gallery-item project-detail__hero-gallery-item--hidden hidden"
                                            <?php if ($data_size): ?>data-lg-size="<?php echo esc_attr($data_size); ?>" <?php endif; ?>
                                            style="display:none;">
                                            <?php echo wp_get_attachment_image($image_id, 'large', false, ['class' => 'project-detail__hero-gallery-img']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php
                // Kiểm tra mặt bằng từ gallery ACF
                $mat_bang_images = [];
                if (is_array($mat_bang_gallery) && !empty($mat_bang_gallery)) {
                    foreach ($mat_bang_gallery as $image) {
                        $image_id = is_array($image) && isset($image['ID']) ? (int) $image['ID'] : (is_numeric($image) ? (int) $image : 0);
                        if ($image_id > 0) {
                            $mat_bang_images[] = $image_id;
                        }
                    }
                }

                $danh_sach_mat_bang   = get_field('danh_sach_mat_bang', $project_id) ?: [];
                $has_floorplan        = !empty($danh_sach_mat_bang) || !empty($mat_bang_images);
                $has_location         = (bool) ($map_embed || !empty($vi_tri_lien_ket_items) || !empty($address));
                $has_amenities_internal = !empty($tien_ich) && trim(strip_tags($tien_ich)) !== '';
                $has_amenities_external = !empty($tien_ich_ngoai_khu) && trim(strip_tags($tien_ich_ngoai_khu)) !== '';
                $has_amenities        = $has_amenities_internal || $has_amenities_external;
                $has_unit_types       = !empty($danh_sach_loai_can_ho) || !empty($bang_gia_can_ho);
                $project_videos       = function_exists('batdongsan_get_project_videos') ? batdongsan_get_project_videos($project_id) : [];
                $has_videos           = !empty($project_videos);

                // Điều kiện hiển thị 6 Section chính
                $has_sec_location_amenities = $has_location || $has_amenities;
                $has_sec_floorplan_products = $has_floorplan || !empty($all_display_gallery) || $has_unit_types;
                $has_sec_legal_progress     = $has_legal || $has_gallery_progress;
                $has_sec_pricing_policy     = true; // Bảng giá, chính sách, bàn giao & công cụ tính khoản vay

                // Truy vấn tin BĐS thuộc dự án (Đang bán & Đang cho thuê)
                $sale_listings_query = batdongsan_get_project_listings($project_id, [
                    'posts_per_page' => 6,
                    'no_found_rows'  => false,
                    'tax_query' => [
                        [
                            'taxonomy' => 'hinh-thuc-bds',
                            'field'    => 'slug',
                            'terms'    => ['cho-thue'],
                            'operator' => 'NOT IN',
                        ],
                    ],
                ]);
                $rent_listings_query = batdongsan_get_project_listings($project_id, [
                    'posts_per_page' => 6,
                    'no_found_rows'  => false,
                    'tax_query' => [
                        [
                            'taxonomy' => 'hinh-thuc-bds',
                            'field'    => 'slug',
                            'terms'    => ['cho-thue'],
                            'operator' => 'IN',
                        ],
                    ],
                ]);

                // Nếu bài đăng không gắn taxonomy cho thuê, lọc qua meta gia_cho_thue hoặc trang_thai = dang_cho_thue
                if (!$rent_listings_query->have_posts() && !empty($sale_listings_query->posts)) {
                    $actual_rent_posts = [];
                    $actual_sale_posts = [];
                    foreach ($sale_listings_query->posts as $p_item) {
                        $p_gia_thue = get_post_meta($p_item->ID, 'gia_cho_thue', true);
                        $p_trang_thai = get_post_meta($p_item->ID, 'trang_thai', true);
                        $terms = wp_get_post_terms($p_item->ID, 'hinh-thuc-bds', ['fields' => 'slugs']);
                        $is_thue = (!is_wp_error($terms) && in_array('cho-thue', $terms, true)) 
                                    || (!empty($p_gia_thue) && trim($p_gia_thue) !== '') 
                                    || ($p_trang_thai === 'dang_cho_thue');
                        if ($is_thue) {
                            $actual_rent_posts[] = $p_item;
                        } else {
                            $actual_sale_posts[] = $p_item;
                        }
                    }
                    if (!empty($actual_rent_posts)) {
                        $rent_listings_query->posts = $actual_rent_posts;
                        $sale_listings_query->posts = $actual_sale_posts;
                    }
                }

                // Đếm chính xác số lượng sản phẩm đang có thực tế
                $sale_count = !empty($sale_listings_query->posts) ? count($sale_listings_query->posts) : 0;
                $rent_count = !empty($rent_listings_query->posts) ? count($rent_listings_query->posts) : 0;
                $has_sale   = ($sale_count > 0);
                $has_rent   = ($rent_count > 0);

                // Section 06 chỉ hiển thị nếu CÓ ÍT NHẤT một sản phẩm đang bán HOẶC cho thuê
                $has_sec_listing = ($has_sale || $has_rent);

                // Tìm bản vẽ quy hoạch tổng thể cho Section 03
                $master_plan = null;
                if (!empty($danh_sach_mat_bang)) {
                    foreach ($danh_sach_mat_bang as $mb_item) {
                        $mb_name = $mb_item['ten_mat_bang'] ?? '';
                        if (preg_match('/tổng|quy hoạch|toàn khu/iu', $mb_name)) {
                            $master_plan = $mb_item;
                            break;
                        }
                    }
                    if (!$master_plan) {
                        $master_plan = $danh_sach_mat_bang[0];
                    }
                } elseif (!empty($mat_bang_images)) {
                    $mp_img_id = $mat_bang_images[0];
                    $master_plan = [
                        'ten_mat_bang' => get_the_title($mp_img_id) ?: 'Mặt bằng tổng thể',
                        'ghi_chu'      => 'Bản vẽ quy hoạch tổng thể mặt bằng dự án',
                        'anh_mat_bang' => [
                            'ID'  => $mp_img_id,
                            'url' => wp_get_attachment_url($mp_img_id),
                        ],
                    ];
                }
                $master_plan_url = '';
                if ($master_plan) {
                    if (isset($master_plan['anh_mat_bang']['url'])) {
                        $master_plan_url = $master_plan['anh_mat_bang']['url'];
                    } elseif (!empty($master_plan['anh_mat_bang'])) {
                        $master_plan_url = is_numeric($master_plan['anh_mat_bang']) ? wp_get_attachment_url($master_plan['anh_mat_bang']) : (string) $master_plan['anh_mat_bang'];
                    }
                }
                ?>

                <!-- THANH ĐIỀU HƯỚNG CẤP 1 (STICKY HORIZONTAL NAVIGATION - ĐÚNG 6 MỤC CHÍNH) -->
                <nav class="project-detail__tabs sticky top-[var(--tabs-top-offset,68px)] z-[800] mb-5 bg-white/95 backdrop-blur-md border-b border-border/80 shadow-xs overflow-x-auto no-scrollbar scroll-smooth" aria-label="Điều hướng chính dự án">
                    <div class="project-detail__tabs-scroll max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-nowrap items-stretch justify-start lg:justify-between gap-3 sm:gap-6 min-w-max lg:min-w-0 w-full">
                        <!-- 1. TỔNG QUAN -->
                        <button type="button" class="project-detail__tab project-detail__tab--active text-left py-2.5 sm:py-3.5 relative flex flex-col justify-center cursor-pointer transition-colors group select-none shrink-0 lg:flex-1" data-scroll-target="#project-overview-section">
                            <span class="project-detail__tab-title font-bold text-xs sm:text-[14px] leading-tight text-charcoal group-hover:text-primary transition-colors whitespace-nowrap">Tổng quan</span>
                            <span class="project-detail__tab-subtitle text-[10px] sm:text-[11px] text-charcoal-muted leading-tight mt-0.5 whitespace-nowrap">Giới thiệu &amp; Quy mô</span>
                        </button>

                        <!-- 2. VỊ TRÍ & TIỆN ÍCH -->
                        <?php if ($has_sec_location_amenities) : ?>
                            <button type="button" class="project-detail__tab text-left py-2.5 sm:py-3.5 relative flex flex-col justify-center cursor-pointer transition-colors group select-none shrink-0 lg:flex-1" data-scroll-target="#project-location-amenities-section">
                                <span class="project-detail__tab-title font-bold text-xs sm:text-[14px] leading-tight text-charcoal-muted group-hover:text-charcoal transition-colors whitespace-nowrap">Vị trí &amp; Tiện ích</span>
                                <span class="project-detail__tab-subtitle text-[10px] sm:text-[11px] text-charcoal-muted/80 leading-tight mt-0.5 whitespace-nowrap">Bản đồ &amp; Không gian sống</span>
                            </button>
                        <?php endif; ?>

                        <!-- 3. MẶT BẰNG & SẢN PHẨM -->
                        <?php if ($has_sec_floorplan_products) : ?>
                            <button type="button" class="project-detail__tab text-left py-2.5 sm:py-3.5 relative flex flex-col justify-center cursor-pointer transition-colors group select-none shrink-0 lg:flex-1" data-scroll-target="#project-floorplan-products-section">
                                <span class="project-detail__tab-title font-bold text-xs sm:text-[14px] leading-tight text-charcoal-muted group-hover:text-charcoal transition-colors whitespace-nowrap">Mặt bằng &amp; Sản phẩm</span>
                                <span class="project-detail__tab-subtitle text-[10px] sm:text-[11px] text-charcoal-muted/80 leading-tight mt-0.5 whitespace-nowrap">Quy hoạch &amp; Căn hộ</span>
                            </button>
                        <?php endif; ?>

                        <!-- 4. PHÁP LÝ & TIẾN ĐỘ -->
                        <?php if ($has_sec_legal_progress) : ?>
                            <button type="button" class="project-detail__tab text-left py-2.5 sm:py-3.5 relative flex flex-col justify-center cursor-pointer transition-colors group select-none shrink-0 lg:flex-1" data-scroll-target="#project-legal-progress-section">
                                <span class="project-detail__tab-title font-bold text-xs sm:text-[14px] leading-tight text-charcoal-muted group-hover:text-charcoal transition-colors whitespace-nowrap">Pháp lý &amp; Tiến độ</span>
                                <span class="project-detail__tab-subtitle text-[10px] sm:text-[11px] text-charcoal-muted/80 leading-tight mt-0.5 whitespace-nowrap">Hồ sơ &amp; Thực tế</span>
                            </button>
                        <?php endif; ?>

                        <!-- 5. GIÁ & CHÍNH SÁCH -->
                        <?php if ($has_sec_pricing_policy) : ?>
                            <button type="button" class="project-detail__tab text-left py-2.5 sm:py-3.5 relative flex flex-col justify-center cursor-pointer transition-colors group select-none shrink-0 lg:flex-1" data-scroll-target="#project-pricing-policy-section">
                                <span class="project-detail__tab-title font-bold text-xs sm:text-[14px] leading-tight text-charcoal-muted group-hover:text-charcoal transition-colors whitespace-nowrap">Giá &amp; Chính sách</span>
                                <span class="project-detail__tab-subtitle text-[10px] sm:text-[11px] text-charcoal-muted/80 leading-tight mt-0.5 whitespace-nowrap">Bảng giá &amp; Vay vốn</span>
                            </button>
                        <?php endif; ?>

                        <!-- 6. MUA BÁN & CHO THUÊ -->
                        <?php if ($has_sec_listing) : ?>
                            <button type="button" class="project-detail__tab text-left py-2.5 sm:py-3.5 relative flex flex-col justify-center cursor-pointer transition-colors group select-none shrink-0 lg:flex-1" data-scroll-target="#project-listing-section">
                                <span class="project-detail__tab-title font-bold text-xs sm:text-[14px] leading-tight text-charcoal-muted group-hover:text-charcoal transition-colors whitespace-nowrap">Mua bán &amp; Cho thuê</span>
                                <span class="project-detail__tab-subtitle text-[10px] sm:text-[11px] text-charcoal-muted/80 leading-tight mt-0.5 whitespace-nowrap">Giỏ hàng giao dịch</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </nav>

                <div class="grid grid-cols-1 lg:grid-cols-[1fr_350px] gap-4 lg:gap-5 items-start">
                    <!-- Cột trái: Thông tin dự án -->
                    <div class="flex flex-col min-w-0">

                        <!-- SECTION 01: TỔNG QUAN -->
                        <div id="project-overview-section" class="bg-surface rounded-xl sm:rounded-2xl p-5 md:p-6 mb-4 md:mb-5 border border-border/70 shadow-subtle max-md:rounded-none max-md:-mx-4 max-md:px-4 max-md:mb-3 max-md:border-x-0">
                            <h2 class="text-xl md:text-2xl font-bold text-charcoal tracking-tight m-0 mb-5">Tổng quan <?php echo esc_html(get_the_title()); ?></h2>

                            <!-- Bảng thông số tổng quan chuẩn mẫu (2 cột đối xứng) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 md:gap-x-10 lg:gap-x-16 mb-6 pb-2 border-b border-border/60">
                                <!-- Cột 1 -->
                                <div class="flex flex-col">
                                    <div class="flex items-center justify-between py-3 border-b border-border/60 text-sm md:text-[14.5px] gap-3 sm:gap-4">
                                        <span class="font-bold text-charcoal shrink-0 whitespace-nowrap">Số căn hộ</span>
                                        <span class="text-charcoal font-medium text-right min-w-0"><?php echo esc_html($so_can ?: 'Đang cập nhật'); ?></span>
                                    </div>
                                    <div class="flex items-center justify-between py-3 border-b border-border/60 text-sm md:text-[14.5px] gap-3 sm:gap-4">
                                        <span class="font-bold text-charcoal shrink-0 whitespace-nowrap">Số tòa</span>
                                        <span class="text-charcoal font-medium text-right min-w-0"><?php echo esc_html($so_toa ?: '1 tòa'); ?></span>
                                    </div>
                                    <div class="flex items-center justify-between py-3 border-b border-border/60 md:border-b-0 text-sm md:text-[14.5px] gap-3 sm:gap-4">
                                        <span class="font-bold text-charcoal shrink-0 whitespace-nowrap">Pháp lý</span>
                                        <span class="text-charcoal font-medium text-right min-w-0"><?php echo esc_html($phap_ly ?: 'Sở hữu lâu dài'); ?></span>
                                    </div>
                                </div>
                                <!-- Cột 2 -->
                                <div class="flex flex-col">
                                    <div class="flex items-center justify-between py-3 border-b border-border/60 text-sm md:text-[14.5px] gap-3 sm:gap-4">
                                        <span class="font-bold text-charcoal shrink-0 whitespace-nowrap"><?php echo !empty($quy_mo) ? 'Quy mô' : 'Diện tích'; ?></span>
                                        <span class="text-charcoal font-medium text-right min-w-0"><?php echo esc_html($quy_mo ?: ($dien_tich ?? 'Đang cập nhật')); ?></span>
                                    </div>
                                    <div class="flex items-center justify-between py-3 border-b border-border/60 text-sm md:text-[14.5px] gap-3 sm:gap-4">
                                        <span class="font-bold text-charcoal shrink-0 whitespace-nowrap mr-2">Chủ đầu tư</span>
                                        <span class="font-bold text-primary text-right min-w-0 truncate" title="<?php echo esc_attr($chu_dau_tu); ?>"><?php echo esc_html($chu_dau_tu ?: 'Đang cập nhật'); ?></span>
                                    </div>
                                    <?php if ($ban_giao || !empty($gia_ban_du_kien)) : ?>
                                        <div class="flex items-center justify-between py-3 border-b-0 text-sm md:text-[14.5px] gap-3 sm:gap-4">
                                            <?php if ($ban_giao): ?>
                                                <span class="font-bold text-charcoal shrink-0 whitespace-nowrap">Bàn giao</span>
                                                <span class="text-charcoal font-medium text-right min-w-0"><?php echo esc_html($ban_giao); ?></span>
                                            <?php else: ?>
                                                <span class="font-bold text-charcoal shrink-0 whitespace-nowrap">Giá dự kiến</span>
                                                <span class="font-bold text-primary text-right min-w-0"><?php echo esc_html($gia_ban_du_kien); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php
                            $desc_id        = 'project-desc-' . $project_id;
                            $raw_content    = get_the_content();
                            $description_html = '';
                            $has_long_desc  = false;

                            if ($raw_content) {
                                $description_html = apply_filters('the_content', $raw_content);
                                $plain_text       = wp_strip_all_tags($description_html);
                                $has_long_desc    = mb_strlen(trim($plain_text)) > 300;
                            } else {
                                $description_html .= '<p>' . esc_html(get_the_title()) . ' là tổ hợp bất động sản cao cấp được phát triển bài bản với thiết kế kiến trúc hiện đại, tầm nhìn khoáng đạt và hạ tầng đồng bộ.</p>';
                                $has_long_desc    = false;
                            }
                            ?>
                            <div id="<?php echo esc_attr($desc_id); ?>" class="project-detail__description-container <?php echo $has_long_desc ? 'is-collapsed' : ''; ?>">
                                <div class="project-detail__description text-charcoal leading-relaxed max-lg:text-sm overflow-x-hidden [&_p]:mb-4 [&_p:last-child]:mb-0 [&_h2]:text-xl sm:[&_h2]:text-2xl [&_h2]:font-bold [&_h2]:text-charcoal [&_h2]:mt-6 [&_h2]:mb-3 [&_h3]:text-base sm:[&_h3]:text-lg [&_h3]:font-bold [&_h3]:text-charcoal [&_h3]:mt-4 [&_h3]:mb-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-2 [&_ul]:mb-4 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:space-y-2 [&_ol]:mb-4 [&_li]:text-charcoal/90 [&_strong]:text-charcoal [&_strong]:font-bold [&_hr]:my-6 [&_hr]:border-border/60 [&_img]:w-full [&_img]:h-auto [&_img]:block [&_img]:rounded-xl [&_figure]:w-full">
                                    <?php echo $description_html; ?>
                                </div>
                            </div>
                            <?php if ($has_long_desc) : ?>
                                <button class="project-detail__toggle mt-4 border-0 bg-transparent text-primary font-semibold text-sm inline-flex items-center gap-1.5 cursor-pointer transition-all duration-200 rounded-full text-center justify-center w-full hover:text-primary-hover" data-target="<?php echo esc_attr($desc_id); ?>" type="button" aria-expanded="false">
                                    <span class="project-detail__toggle-label">Xem thêm chi tiết</span>
                                    <span aria-hidden="true" class="project-detail__toggle-icon inline-block transition-transform duration-200">↓</span>
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- SECTION 02: VỊ TRÍ & TIỆN ÍCH -->
                        <?php if ($has_sec_location_amenities): ?>
                            <div id="project-location-amenities-section" class="bg-surface rounded-xl sm:rounded-2xl p-5 md:p-6 mb-4 md:mb-5 border border-border/70 shadow-subtle max-md:rounded-none max-md:-mx-4 max-md:px-4 max-md:mb-3 max-md:border-x-0" data-section-tabs-container>
                                <div id="project-location-section" class="sr-only"></div>
                                <div id="project-amenities-section" class="sr-only"></div>

                                <!-- Header Section -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-border/80">
                                    <div>
                                        <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-0.5 block">Vị Trí &amp; Không Gian Sống</span>
                                        <h2 class="text-lg md:text-xl font-bold text-charcoal tracking-tight m-0">Vị Trí &amp; Tiện Ích <?php echo esc_html(get_the_title()); ?></h2>
                                        <?php if ($address): ?>
                                            <p class="text-xs sm:text-sm text-charcoal-muted m-0 mt-1 flex items-center gap-1.5">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                <span><?php echo esc_html($address); ?></span>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                    $directions_url = "https://www.google.com/maps/dir/?api=1&destination=" . urlencode(get_the_title() . ' ' . $address);
                                    ?>
                                    <a href="<?php echo esc_url($directions_url); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full border border-primary/20 bg-primary/5 hover:bg-primary hover:text-white text-primary text-xs font-semibold transition-all shadow-xs self-start sm:self-center shrink-0">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                                        </svg>
                                        <span>Chỉ đường Google Maps</span>
                                    </a>
                                </div>

                                <!-- Tabs Navigation: Vị trí, Tiện ích nội khu, Tiện ích ngoại khu -->
                                <div class="bds-section-tabs" role="tablist" aria-label="Tabs Vị trí và Tiện ích">
                                    <button type="button" role="tab" aria-selected="true" class="bds-section-tab-btn is-active" data-section-tab-btn="location-map" data-tab-group="location-amenities">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                        <span>1. Vị trí &amp; Bản đồ</span>
                                    </button>
                                    <?php if ($has_amenities_internal): ?>
                                        <button type="button" role="tab" aria-selected="false" class="bds-section-tab-btn" data-section-tab-btn="amenities-internal" data-tab-group="location-amenities">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                                            <span>2. Tiện ích nội khu</span>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($has_amenities_external): ?>
                                        <button type="button" role="tab" aria-selected="false" class="bds-section-tab-btn" data-section-tab-btn="amenities-external" data-tab-group="location-amenities">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
                                            <span>3. Tiện ích ngoại khu</span>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- Tab Panel 1: Vị trí & Bản đồ -->
                                <div data-section-tab-panel="location-map" data-tab-group="location-amenities">
                                    <?php
                                    $iframe_src = '';
                                    if (!empty($map_embed) && preg_match('~src=["\']([^"\']+)["\']~', $map_embed, $matches)) {
                                        $raw_src = trim($matches[1]);
                                        if (str_starts_with($raw_src, '//')) {
                                            $raw_src = 'https:' . $raw_src;
                                        }
                                        $iframe_src = esc_url($raw_src, ['https']);
                                    }
                                    $placeholder_img = get_template_directory_uri() . '/assets/images/map-placeholder.jpg';
                                    $map_facade_id = 'bds-map-facade-' . $project_id;
                                    ?>
                                    <?php if (!empty($iframe_src)): ?>
                                        <div 
                                            id="<?php echo esc_attr($map_facade_id); ?>" 
                                            class="map-facade group relative aspect-[16/9] sm:aspect-[21/9] min-h-[300px] sm:min-h-[380px] w-full rounded-xl overflow-hidden border border-border/80 bg-stone cursor-pointer mb-4"
                                            data-map-src="<?php echo esc_url($iframe_src); ?>"
                                            role="button"
                                            aria-label="Nhấp để tải tương tác bản đồ Google Maps"
                                            tabindex="0"
                                        >
                                            <div class="map-facade__placeholder absolute inset-0 flex items-center justify-center overflow-hidden">
                                                <img src="<?php echo esc_url($placeholder_img); ?>" alt="" role="presentation" class="absolute inset-0 w-full h-full object-cover opacity-60 scale-105 pointer-events-none transition-transform duration-700 group-hover:scale-110" fetchpriority="low" width="1200" height="675" />
                                                <div class="absolute inset-0 bg-gradient-to-t from-charcoal/50 via-charcoal/20 to-charcoal/10 transition-opacity duration-300 group-hover:opacity-75"></div>
                                                <div class="relative z-10 flex flex-col items-center gap-3 p-4 text-center max-w-md mx-auto">
                                                    <div class="flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 bg-primary hover:bg-primary-hover text-white rounded-full shadow-button transition-all duration-300 group-hover:scale-110 ring-4 ring-white/30">
                                                        <svg class="w-7 h-7 sm:w-8 sm:h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M12 21s-6-5.33-6-10a6 6 0 1 1 12 0c0 4.67-6 10-6 10z"/>
                                                            <circle cx="12" cy="11" r="2.5"/>
                                                        </svg>
                                                    </div>
                                                    <span class="inline-flex items-center gap-2 text-xs sm:text-sm font-semibold text-charcoal bg-white/95 px-5 py-2.5 rounded-full shadow-card backdrop-blur-md transition-all group-hover:bg-white group-hover:shadow-button">
                                                        <span>Chạm để khám phá bản đồ vệ tinh</span>
                                                        <span class="text-primary text-[11px]">&bull; Click to load map</span>
                                                    </span>
                                                    <?php if ($address): ?>
                                                        <span class="text-xs text-white/95 font-medium drop-shadow-sm max-w-[320px] line-clamp-2"><?php echo esc_html($address); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="map-facade__iframe-container absolute inset-0 z-20 bg-surface hidden [&>iframe]:block [&>iframe]:w-full [&>iframe]:h-full [&>iframe]:border-0"></div>
                                            <div class="map-facade__loading absolute inset-0 z-10 hidden flex-col items-center justify-center gap-2.5 bg-surface/95 backdrop-blur-sm">
                                                <div class="w-10 h-10 border-3 border-border border-t-primary rounded-full animate-spin"></div>
                                                <span class="text-xs font-medium text-charcoal-muted tracking-wider uppercase">Đang kết nối Google Maps...</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Hạ tầng giao thông kết nối -->
                                    <div class="p-4 rounded-xl bg-canvas border border-border/70 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs sm:text-sm">
                                        <div class="flex items-center gap-2.5 text-charcoal">
                                            <div class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </div>
                                            <span>Kết nối trực tiếp các trục đường huyết mạch, ga Metro và trung tâm hành chính.</span>
                                        </div>
                                        <a href="<?php echo esc_url($directions_url); ?>" target="_blank" class="text-primary font-semibold hover:underline shrink-0">Xem lộ trình di chuyển &rarr;</a>
                                    </div>
                                </div>

                                <!-- Tab Panel 2: Tiện ích nội khu -->
                                <?php if ($has_amenities_internal): ?>
                                    <div data-section-tab-panel="amenities-internal" data-tab-group="location-amenities" class="hidden">
                                        <div class="mb-4">
                                            <h3 class="text-base font-bold text-charcoal m-0 mb-1">Hệ sinh thái tiện ích nội khu</h3>
                                            <p class="text-xs text-charcoal-muted m-0">Không gian sống xanh, tiện nghi đặc quyền phục vụ cư dân tại <?php echo esc_html(get_the_title()); ?>.</p>
                                        </div>
                                        <div class="text-charcoal leading-relaxed text-xs sm:text-sm [&_ul]:list-disc [&_ul]:pl-5 [&_li]:mb-2 [&_p]:mb-3">
                                            <?php echo wp_kses_post($tien_ich); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Tab Panel 3: Tiện ích ngoại khu (Chỉ hiển thị khi có dữ liệu thật do Admin nhập) -->
                                <?php if ($has_amenities_external): ?>
                                    <div data-section-tab-panel="amenities-external" data-tab-group="location-amenities" class="hidden">
                                        <div class="mb-4">
                                            <h3 class="text-base font-bold text-charcoal m-0 mb-1">Tiện ích ngoại khu &amp; Liên kết vùng</h3>
                                            <p class="text-xs text-charcoal-muted m-0">Mạng lưới hạ tầng giao thông, giáo dục, y tế và thương mại xung quanh dự án.</p>
                                        </div>
                                        <div class="text-charcoal leading-relaxed text-xs sm:text-sm [&_ul]:list-disc [&_ul]:pl-5 [&_li]:mb-2 [&_p]:mb-3">
                                            <?php echo wp_kses_post($tien_ich_ngoai_khu); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- SECTION 03: MẶT BẰNG & SẢN PHẨM -->
                        <?php if ($has_sec_floorplan_products): ?>
                            <div id="project-floorplan-products-section" class="bg-surface rounded-xl sm:rounded-2xl p-5 md:p-6 mb-4 md:mb-5 border border-border/70 shadow-subtle max-md:rounded-none max-md:-mx-4 max-md:px-4 max-md:mb-3 max-md:border-x-0" data-section-tabs-container>
                                <div id="project-floorplan-section" class="sr-only"></div>

                                <!-- Header Section -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-border/80">
                                    <div>
                                        <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-0.5 block">Kiến Trúc &amp; Không Gian Căn Hộ</span>
                                        <h2 class="text-lg md:text-xl font-bold text-charcoal tracking-tight m-0">Mặt Bằng &amp; Sản Phẩm Dự Án</h2>
                                    </div>
                                    <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full border border-primary/20 bg-primary/5 text-primary text-xs font-semibold hover:bg-primary hover:text-white transition-all cursor-pointer self-start sm:self-center shrink-0 shadow-xs" data-modal-target="advisor-modal">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                        <span>Tải Trọn Bộ Bản Vẽ CAD</span>
                                    </button>
                                </div>

                                <!-- Tabs Navigation: Quy hoạch tổng thể, Mặt bằng tầng, Loại căn hộ, Thiết kế -->
                                <div class="bds-section-tabs" role="tablist" aria-label="Tabs Mặt bằng và Sản phẩm">
                                    <?php if ($master_plan): ?>
                                        <button type="button" role="tab" aria-selected="true" class="bds-section-tab-btn is-active" data-section-tab-btn="master-plan" data-tab-group="floorplan-products">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                                            <span>1. Quy hoạch tổng thể</span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($has_floorplan): ?>
                                        <button type="button" role="tab" aria-selected="<?php echo !$master_plan ? 'true' : 'false'; ?>" class="bds-section-tab-btn <?php echo !$master_plan ? 'is-active' : ''; ?>" data-section-tab-btn="floor-plans" data-tab-group="floorplan-products">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21"/></svg>
                                            <span>2. Mặt bằng tầng</span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($has_unit_types): ?>
                                        <button type="button" role="tab" aria-selected="false" class="bds-section-tab-btn" data-section-tab-btn="unit-types" data-tab-group="floorplan-products">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.75a.75.75 0 00-.75-.75h-9a.75.75 0 00-.75.75V21h4.5z"/></svg>
                                            <span>3. Loại căn hộ</span>
                                            <span class="bds-section-tab-badge"><?php echo !empty($danh_sach_loai_can_ho) ? count($danh_sach_loai_can_ho) : count($bang_gia_can_ho); ?></span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if (!empty($all_display_gallery)): ?>
                                        <button type="button" role="tab" aria-selected="false" class="bds-section-tab-btn" data-section-tab-btn="design-interior" data-tab-group="floorplan-products">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                                            <span>4. Thiết kế &amp; 3D</span>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- Tab Panel 1: Quy hoạch tổng thể -->
                                <?php if ($master_plan): ?>
                                    <div data-section-tab-panel="master-plan" data-tab-group="floorplan-products">
                                        <div class="relative w-full rounded-xl sm:rounded-2xl border border-border/70 bg-stone/20 overflow-hidden shadow-subtle p-3 sm:p-5">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3 pb-2.5 border-b border-border/50">
                                                <div>
                                                    <h3 class="text-sm sm:text-base font-bold text-charcoal m-0"><?php echo esc_html($master_plan['ten_mat_bang'] ?? 'Bản vẽ quy hoạch tổng thể'); ?></h3>
                                                    <?php if (!empty($master_plan['ghi_chu'])): ?>
                                                        <p class="text-xs text-charcoal-muted m-0 mt-0.5"><?php echo esc_html($master_plan['ghi_chu']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex items-center gap-2 shrink-0">
                                                    <?php if ($master_plan_url): ?>
                                                        <a href="<?php echo esc_url($master_plan_url); ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white hover:bg-stone/60 text-charcoal border border-border/70 text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                                                            <svg class="w-3.5 h-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" /></svg>
                                                            <span>Phóng to bản vẽ</span>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <?php if ($master_plan_url): ?>
                                                <div class="flex items-center justify-center min-h-[380px] max-h-[620px] overflow-hidden rounded-lg bg-white/80 p-2 sm:p-4">
                                                    <img src="<?php echo esc_url($master_plan_url); ?>" alt="<?php echo esc_attr($master_plan['ten_mat_bang'] ?? 'Quy hoạch tổng thể'); ?>" class="max-w-full max-h-[580px] w-auto h-auto object-contain mx-auto transition-transform duration-300 hover:scale-[1.01]" loading="lazy" />
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Tab Panel 2: Mặt bằng tầng -->
                                <?php if ($has_floorplan): ?>
                                    <div data-section-tab-panel="floor-plans" data-tab-group="floorplan-products" class="<?php echo $master_plan ? 'hidden' : ''; ?>">
                                        <?php
                                        get_template_part('template-parts/interactive-floorplan', null, [
                                            'project_id'         => $project_id,
                                            'danh_sach_mat_bang' => $danh_sach_mat_bang,
                                            'mat_bang_gallery'   => $mat_bang_images,
                                        ]);
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Tab Panel 3: Loại căn hộ & Sơ đồ Layout -->
                                <?php if ($has_unit_types): ?>
                                    <div data-section-tab-panel="unit-types" data-tab-group="floorplan-products" class="hidden" data-unit-filter-container>
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                                            <div>
                                                <h3 class="text-sm sm:text-base font-bold text-charcoal m-0">Phân loại sản phẩm căn hộ &amp; Sơ đồ thiết kế</h3>
                                                <p class="text-xs text-charcoal-muted m-0 mt-0.5">Chọn loại phòng ngủ để xem chi tiết diện tích, khoảng giá và layout mặt bằng.</p>
                                            </div>
                                            <!-- Pills lọc nhanh loại phòng -->
                                            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1">
                                                <button type="button" class="px-3 py-1.5 rounded-full text-xs font-semibold bg-primary text-white shadow-xs cursor-pointer shrink-0" data-unit-filter="all">Tất cả</button>
                                                <button type="button" class="px-3 py-1.5 rounded-full text-xs font-semibold bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60 cursor-pointer shrink-0" data-unit-filter="1">1 Phòng ngủ</button>
                                                <button type="button" class="px-3 py-1.5 rounded-full text-xs font-semibold bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60 cursor-pointer shrink-0" data-unit-filter="2">2 Phòng ngủ</button>
                                                <button type="button" class="px-3 py-1.5 rounded-full text-xs font-semibold bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60 cursor-pointer shrink-0" data-unit-filter="3">3 Phòng ngủ</button>
                                                <button type="button" class="px-3 py-1.5 rounded-full text-xs font-semibold bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60 cursor-pointer shrink-0" data-unit-filter="vip">VIP / Penthouse</button>
                                            </div>
                                        </div>

                                        <?php if (!empty($danh_sach_loai_can_ho)): ?>
                                            <!-- Hiển thị theo trường chuyên biệt danh_sach_loai_can_ho (có ảnh sơ đồ layout) -->
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <?php foreach ($danh_sach_loai_can_ho as $can_ho): ?>
                                                    <?php
                                                    $lh = $can_ho['ten_loai_can'] ?? '';
                                                    $pn = $can_ho['so_phong_ngu'] ?? '2';
                                                    $dt = $can_ho['dien_tich'] ?? '';
                                                    $kg = $can_ho['khoang_gia'] ?? '';
                                                    $img_layout = $can_ho['anh_layout'] ?? null;
                                                    $img_id = is_array($img_layout) ? ($img_layout['ID'] ?? $img_layout['id'] ?? 0) : (is_numeric($img_layout) ? (int)$img_layout : 0);
                                                    $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'large') : (is_array($img_layout) ? ($img_layout['url'] ?? '') : '');
                                                    ?>
                                                    <div class="p-4 sm:p-5 rounded-xl bg-canvas border border-border/70 flex flex-col justify-between gap-3.5 hover:border-primary/40 transition-colors shadow-subtle" data-unit-card data-unit-type="<?php echo esc_attr($pn); ?>">
                                                        <div>
                                                            <div class="flex items-center justify-between gap-2 mb-2 pb-2 border-b border-border/50">
                                                                <h4 class="font-bold text-charcoal text-sm sm:text-base m-0 leading-snug"><?php echo esc_html($lh); ?></h4>
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"><?php echo esc_html($pn === 'vip' ? 'VIP / Đặc quyền' : $pn . ' Phòng ngủ'); ?></span>
                                                            </div>
                                                            <div class="grid grid-cols-2 gap-2 text-xs text-charcoal-muted mb-3">
                                                                <div>
                                                                    <span>Diện tích:</span>
                                                                    <strong class="text-charcoal font-semibold block text-xs sm:text-sm"><?php echo esc_html($dt ?: 'Đang cập nhật'); ?></strong>
                                                                </div>
                                                                <div>
                                                                    <span>Giá tham khảo:</span>
                                                                    <strong class="text-primary font-bold block text-xs sm:text-sm"><?php echo esc_html($clean_draft_text($kg, 'Liên hệ báo giá')); ?></strong>
                                                                </div>
                                                            </div>
                                                            <?php if (!empty($img_url)): ?>
                                                                <a href="<?php echo esc_url($img_url); ?>" target="_blank" class="group/img block relative aspect-[16/10] rounded-lg overflow-hidden border border-border/60 bg-white p-2">
                                                                    <img src="<?php echo esc_url($img_url); ?>" alt="Layout <?php echo esc_attr($lh); ?>" class="w-full h-full object-contain group-hover/img:scale-105 transition-transform duration-300" loading="lazy">
                                                                    <div class="absolute inset-0 bg-charcoal/30 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center">
                                                                        <span class="px-2.5 py-1 rounded bg-white/95 text-charcoal text-[11px] font-semibold shadow-xs">Xem bản vẽ chi tiết &rarr;</span>
                                                                    </div>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="pt-3 border-t border-border/50 flex items-center justify-between gap-2">
                                                            <span class="text-[11px] text-charcoal-muted">Thiết kế tối ưu công năng</span>
                                                            <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline cursor-pointer" data-modal-target="advisor-modal">
                                                                <span>Tư vấn chọn căn</span>
                                                                <span aria-hidden="true">&rarr;</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php elseif (!empty($bang_gia_can_ho)): ?>
                                            <!-- Fallback từ bảng giá căn hộ nếu chưa nhập trường danh_sach_loai_can_ho -->
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                                <?php foreach ($bang_gia_can_ho as $can_ho): ?>
                                                    <?php
                                                    $lh = $can_ho['loai_hinh'] ?? '';
                                                    $dt = $can_ho['dien_tich'] ?? '';
                                                    $kg = $can_ho['khoang_gia'] ?? '';
                                                    $tt = $can_ho['tinh_trang'] ?? 'Đang mở bán';
                                                    $filter_tag = (stripos($lh, '1') !== false) ? '1' : ((stripos($lh, '2') !== false) ? '2' : ((stripos($lh, '3') !== false) ? '3' : 'vip'));
                                                    ?>
                                                    <div class="p-4 rounded-xl bg-canvas border border-border/70 flex flex-col justify-between gap-3 hover:border-primary/40 transition-colors" data-unit-card data-unit-type="<?php echo esc_attr($filter_tag); ?>">
                                                        <div>
                                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                                <h4 class="font-bold text-charcoal text-sm m-0 leading-snug"><?php echo esc_html($lh); ?></h4>
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"><?php echo esc_html($tt); ?></span>
                                                            </div>
                                                            <div class="space-y-1.5 text-xs text-charcoal-muted">
                                                                <div class="flex items-center justify-between">
                                                                    <span>Diện tích thông thủy:</span>
                                                                    <strong class="text-charcoal font-semibold"><?php echo esc_html($dt ?: 'Đang cập nhật'); ?></strong>
                                                                </div>
                                                                <div class="flex items-center justify-between">
                                                                    <span>Khoảng giá tham khảo:</span>
                                                                    <strong class="text-primary font-bold text-sm"><?php echo esc_html($clean_draft_text($kg, 'Liên hệ báo giá')); ?></strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="pt-3 border-t border-border/50 flex items-center justify-between gap-2">
                                                            <span class="text-[11px] text-charcoal-muted">Bàn giao nội thất cao cấp</span>
                                                            <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline cursor-pointer" data-modal-target="advisor-modal">
                                                                <span>Tư vấn chọn căn</span>
                                                                <span aria-hidden="true">&rarr;</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Tab Panel 4: Thiết kế & Phối cảnh 3D -->
                                <?php if (!empty($all_display_gallery)): ?>
                                    <div data-section-tab-panel="design-interior" data-tab-group="floorplan-products" class="hidden">
                                        <div class="mb-4">
                                            <h3 class="text-sm sm:text-base font-bold text-charcoal m-0">Không gian thiết kế kiến trúc &amp; Căn hộ mẫu thực tế</h3>
                                            <p class="text-xs text-charcoal-muted m-0 mt-0.5">Hình ảnh thực tế showroom nội thất và bản vẽ phối cảnh không gian 3D.</p>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" id="project-visual-gallery">
                                            <?php foreach (array_slice($all_display_gallery, 0, 9) as $item): ?>
                                                <?php
                                                $img_id = $item['id'];
                                                $full = wp_get_attachment_image_src($img_id, 'full');
                                                $full_url = $full ? $full[0] : '';
                                                $thumb_url = wp_get_attachment_image_url($img_id, 'medium_large') ?: $full_url;
                                                $cat_label = ($item['cat'] === 'progress') ? 'Tiến độ thực tế' : (($item['cat'] === 'showroom') ? 'Căn hộ mẫu' : 'Phối cảnh 3D');
                                                ?>
                                                <a href="<?php echo esc_url($full_url); ?>" class="group block relative aspect-[4/3] rounded-xl overflow-hidden border border-border/60 bg-stone" data-sub-html="<?php echo esc_attr($cat_label . ' - ' . get_the_title()); ?>">
                                                    <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($cat_label . ' ' . get_the_title()); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                                                    <div class="absolute inset-0 bg-gradient-to-t from-charcoal/60 via-transparent to-transparent opacity-80 group-hover:opacity-100 transition-opacity"></div>
                                                    <span class="absolute bottom-2 left-2 px-2 py-0.5 rounded-md bg-charcoal/70 backdrop-blur-sm text-white text-[10px] font-semibold">
                                                        <?php echo esc_html($cat_label); ?>
                                                    </span>
                                                    <span class="absolute top-2 right-2 w-7 h-7 rounded-full bg-white/80 backdrop-blur-sm text-charcoal flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-sm">
                                                        <svg class="w-3.5 h-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" /></svg>
                                                    </span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- SECTION 04: PHÁP LÝ & TIẾN ĐỘ -->
                        <?php if ($has_sec_legal_progress): ?>
                            <div id="project-legal-progress-section" class="bg-surface rounded-xl sm:rounded-2xl p-5 md:p-6 mb-4 md:mb-5 border border-border/70 shadow-subtle max-md:rounded-none max-md:-mx-4 max-md:px-4 max-md:mb-3 max-md:border-x-0" data-section-tabs-container>
                                <div id="project-legal-section" class="sr-only"></div>
                                <div id="project-gallery-progress-section" class="sr-only"></div>

                                <!-- Header Section -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-border/80">
                                    <div>
                                        <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-0.5 block">Hồ Sơ &amp; Thực Tế</span>
                                        <h2 class="text-lg md:text-xl font-bold text-charcoal tracking-tight m-0">Hồ Sơ Pháp Lý &amp; Tiến Độ Xây Dựng</h2>
                                    </div>
                                    <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-primary/20 bg-primary/5 text-primary text-xs font-semibold hover:bg-primary hover:text-white transition-all cursor-pointer self-start sm:self-center shrink-0 shadow-xs" data-modal-target="advisor-modal">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                        <span>Tải Trọn Bộ Pháp Lý PDF</span>
                                    </button>
                                </div>

                                <!-- Tabs Navigation: Pháp lý, Tiến độ xây dựng -->
                                <div class="bds-section-tabs" role="tablist" aria-label="Tabs Pháp lý và Tiến độ">
                                    <button type="button" role="tab" aria-selected="true" class="bds-section-tab-btn is-active" data-section-tab-btn="legal-docs" data-tab-group="legal-progress">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                                        <span>1. Hồ sơ pháp lý</span>
                                    </button>
                                    <?php if ($has_gallery_progress): ?>
                                        <button type="button" role="tab" aria-selected="false" class="bds-section-tab-btn" data-section-tab-btn="construction-progress" data-tab-group="legal-progress">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.67 2.67 0 0021 17.25l-5.83-5.83M11.42 15.17l2.496-3.03c.315-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.07a4.5 4.5 0 004.486-6.32l-3.27 3.27-2.83-2.83 3.27-3.27a4.5 4.5 0 00-6.32 4.486c.118.58.094 1.193-.07 1.743"/></svg>
                                            <span>2. Tiến độ xây dựng</span>
                                            <?php if (!empty($tien_do_ngay_cap_nhat)): ?>
                                                <span class="bds-section-tab-badge">Mới</span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- Tab Panel 1: Hồ sơ pháp lý -->
                                <div data-section-tab-panel="legal-docs" data-tab-group="legal-progress">
                                    <div class="bg-canvas/80 rounded-xl p-3.5 sm:p-4 divide-y divide-border/50 mb-4">
                                        <?php if (!empty($phap_ly_hinh_thuc_so_huu)): ?>
                                            <div class="py-3 first:pt-0 last:pb-0 flex items-start gap-3">
                                                <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <span class="text-[11px] text-charcoal-muted uppercase font-semibold tracking-wider block mb-0.5">1. Hình thức sở hữu &amp; Sổ hồng</span>
                                                    <p class="text-xs sm:text-sm font-semibold text-charcoal m-0 leading-snug"><?php echo esc_html($phap_ly_hinh_thuc_so_huu); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($phap_ly_quy_hoach)): ?>
                                            <div class="py-3 first:pt-0 last:pb-0 flex items-start gap-3">
                                                <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <span class="text-[11px] text-charcoal-muted uppercase font-semibold tracking-wider block mb-0.5">2. Quy hoạch 1/500 &amp; Chấp thuận đầu tư</span>
                                                    <p class="text-xs sm:text-sm font-semibold text-charcoal m-0 leading-snug"><?php echo esc_html($phap_ly_quy_hoach); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($phap_ly_gpxd)): ?>
                                            <div class="py-3 first:pt-0 last:pb-0 flex items-start gap-3">
                                                <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <span class="text-[11px] text-charcoal-muted uppercase font-semibold tracking-wider block mb-0.5">3. Giấy phép xây dựng (GPXD)</span>
                                                    <p class="text-xs sm:text-sm font-semibold text-charcoal m-0 leading-snug"><?php echo esc_html($phap_ly_gpxd); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($phap_ly_dieu_kien_ban_giao)): ?>
                                            <div class="py-3 first:pt-0 last:pb-0 flex items-start gap-3">
                                                <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <span class="text-[11px] text-charcoal-muted uppercase font-semibold tracking-wider block mb-0.5">4. Điều kiện bán hàng &amp; Nghiệm thu công trình</span>
                                                    <p class="text-xs sm:text-sm font-semibold text-charcoal m-0 leading-snug"><?php echo esc_html($phap_ly_dieu_kien_ban_giao); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($phap_ly_ngan_hang_bao_lanh)): ?>
                                            <div class="py-3 first:pt-0 last:pb-0 flex items-start gap-3">
                                                <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <span class="text-[11px] text-charcoal-muted uppercase font-semibold tracking-wider block mb-0.5">5. Bảo lãnh tài chính ngân hàng</span>
                                                    <p class="text-xs sm:text-sm font-semibold text-charcoal m-0 leading-snug"><?php echo esc_html($phap_ly_ngan_hang_bao_lanh); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($phap_ly_mo_ta)): ?>
                                        <div class="p-4 rounded-xl bg-stone/40 border border-border/60 text-xs sm:text-sm text-charcoal leading-relaxed mb-4">
                                            <?php echo wp_kses_post($phap_ly_mo_ta); ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Legal CTA Bar -->
                                    <div class="p-4 rounded-xl bg-primary/5 border border-primary/15 flex flex-col sm:flex-row items-center justify-between gap-3">
                                        <div class="flex items-center gap-2.5 text-xs text-charcoal">
                                            <svg class="w-4 h-4 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <span>Quý khách cần kiểm tra quy hoạch phân khu &amp; đối chiếu hồ sơ gốc?</span>
                                        </div>
                                        <a href="tel:<?php echo esc_attr($advisor_phone); ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary hover:bg-primary-hover text-white text-xs font-semibold rounded-full shadow-button transition-all shrink-0">
                                            <span>Tư Vấn Pháp Lý 24/7: <?php echo esc_html($advisor_phone); ?></span>
                                        </a>
                                    </div>
                                </div>

                                <!-- Tab Panel 2: Tiến độ xây dựng -->
                                <?php if ($has_gallery_progress): ?>
                                    <div data-section-tab-panel="construction-progress" data-tab-group="legal-progress" class="hidden">
                                        <?php if (!empty($tien_do_ngay_cap_nhat)): ?>
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-bold mb-4">
                                                <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                                                <span>Thời điểm cập nhật: <?php echo esc_html($tien_do_ngay_cap_nhat); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($tien_do_ghi_chu)): ?>
                                            <div class="p-3.5 sm:p-4 rounded-xl bg-canvas border border-border/70 mb-5 flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0 mt-0.5">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                </div>
                                                <div class="text-xs sm:text-sm">
                                                    <strong class="font-bold text-charcoal block mb-0.5">Tình trạng thi công thực tế tại công trường:</strong>
                                                    <p class="text-charcoal-muted m-0 leading-relaxed"><?php echo esc_html($tien_do_ghi_chu); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($tien_do_gallery)): ?>
                                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                                <?php foreach ($tien_do_gallery as $img): ?>
                                                    <?php
                                                    $img_id = is_array($img) ? ($img['ID'] ?? 0) : (int) $img;
                                                    if ($img_id <= 0) continue;
                                                    $full_src = wp_get_attachment_image_src($img_id, 'full');
                                                    $full_url = $full_src ? $full_src[0] : '';
                                                    $thumb_url = wp_get_attachment_image_url($img_id, 'medium_large') ?: $full_url;
                                                    ?>
                                                    <a href="<?php echo esc_url($full_url); ?>" class="group block relative aspect-[4/3] rounded-xl overflow-hidden border border-border/60 bg-stone" data-sub-html="Tiến độ thực tế - <?php echo esc_attr(get_the_title()); ?>">
                                                        <img src="<?php echo esc_url($thumb_url); ?>" alt="Tiến độ xây dựng <?php echo esc_attr(get_the_title()); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                                                        <div class="absolute inset-0 bg-gradient-to-t from-charcoal/60 via-transparent to-transparent opacity-80 group-hover:opacity-100 transition-opacity"></div>
                                                        <span class="absolute bottom-2 left-2 px-2 py-0.5 rounded-md bg-charcoal/70 backdrop-blur-sm text-white text-[10px] font-semibold">Hình ảnh công trường</span>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- SECTION 05: GIÁ & CHÍNH SÁCH -->
                        <div id="project-pricing-policy-section" class="bg-surface rounded-xl sm:rounded-2xl p-5 md:p-6 mb-4 md:mb-5 border border-border/70 shadow-subtle max-md:rounded-none max-md:-mx-4 max-md:px-4 max-md:mb-3 max-md:border-x-0" data-section-tabs-container>
                            <!-- Header Section -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-border/80">
                                <div>
                                    <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-0.5 block">Tài Chính &amp; Chính Sách</span>
                                    <h2 class="text-lg md:text-xl font-bold text-charcoal tracking-tight m-0">Giá Bán, Chính Sách &amp; Ước Tính Khoản Vay</h2>
                                </div>
                                <?php if (!empty($gia_ban_du_kien)): ?>
                                    <?php $is_sec5_contact = (mb_stripos($gia_ban_du_kien, "liên hệ") !== false); ?>
                                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary self-start sm:self-center">
                                        <?php if ($is_sec5_contact): ?>
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                            <strong class="text-sm font-bold text-primary">Liên hệ nhận bảng giá chi tiết</strong>
                                        <?php else: ?>
                                            <span class="text-[11px] font-medium uppercase tracking-wider text-charcoal-muted">Giá từ:</span>
                                            <strong class="text-base font-extrabold text-primary"><?php echo esc_html($gia_ban_du_kien); ?></strong>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tabs Navigation: Giá bán, Thanh toán, Bàn giao, Ước tính khoản vay -->
                            <div class="bds-section-tabs" role="tablist" aria-label="Tabs Giá và Chính sách">
                                <button type="button" role="tab" aria-selected="true" class="bds-section-tab-btn is-active" data-section-tab-btn="pricing-sales" data-tab-group="pricing-policy">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>1. Giá bán tham khảo</span>
                                </button>
                                <?php if (!empty($chinh_sach) && trim($chinh_sach) !== ''): ?>
                                    <button type="button" role="tab" aria-selected="false" class="bds-section-tab-btn" data-section-tab-btn="pricing-payment" data-tab-group="pricing-policy">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5"/></svg>
                                        <span>2. Tiến độ thanh toán</span>
                                    </button>
                                <?php endif; ?>
                                <?php if (!empty($tieu_chuan_ban_giao) && trim($tieu_chuan_ban_giao) !== ''): ?>
                                    <button type="button" role="tab" aria-selected="false" class="bds-section-tab-btn" data-section-tab-btn="pricing-handover" data-tab-group="pricing-policy">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>3. Tiêu chuẩn bàn giao</span>
                                    </button>
                                <?php endif; ?>
                                <button type="button" role="tab" aria-selected="false" class="bds-section-tab-btn" data-section-tab-btn="pricing-loan" data-tab-group="pricing-policy">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zM3.75 6.75h16.5M3.75 3h16.5A2.25 2.25 0 0122.5 5.25v13.5A2.25 2.25 0 0120.25 21H3.75A2.25 2.25 0 011.5 18.75V5.25A2.25 2.25 0 013.75 3z"/></svg>
                                    <span>4. Ước tính khoản vay</span>
                                </button>
                            </div>

                            <!-- Tab Panel 1: Bảng giá bán -->
                            <div data-section-tab-panel="pricing-sales" data-tab-group="pricing-policy">
                                <?php if (!empty($bang_gia_can_ho)): ?>
                                    <div class="overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0 mb-4">
                                        <table class="w-full text-left border-collapse text-xs sm:text-sm">
                                            <thead>
                                                <tr class="border-b border-border/80 bg-stone/40 text-charcoal-muted uppercase text-[11px] font-bold">
                                                    <th class="py-3 px-3">Loại hình sản phẩm</th>
                                                    <th class="py-3 px-3">Diện tích</th>
                                                    <th class="py-3 px-3">Khoảng giá</th>
                                                    <th class="py-3 px-3 text-right">Tình trạng</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-border/50 text-charcoal">
                                                <?php foreach ($bang_gia_can_ho as $bg): ?>
                                                    <tr class="hover:bg-stone/20 transition-colors">
                                                        <td class="py-3 px-3 font-semibold text-charcoal"><?php echo esc_html($bg['loai_hinh'] ?? ''); ?></td>
                                                        <td class="py-3 px-3 text-charcoal-muted"><?php echo esc_html($bg['dien_tich'] ?? ''); ?></td>
                                                        <td class="py-3 px-3 font-bold text-primary"><?php echo esc_html($clean_draft_text($bg['khoang_gia'] ?? '', 'Liên hệ báo giá')); ?></td>
                                                        <td class="py-3 px-3 text-right">
                                                            <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                                <?php echo esc_html($bg['tinh_trang'] ?? 'Đang mở bán'); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="p-4 rounded-xl bg-canvas border border-border/70 text-xs sm:text-sm text-charcoal mb-4">
                                        Giá bán đang được cập nhật chi tiết theo từng tầng và hướng view. Vui lòng liên hệ hotline để nhận bảng giá độc quyền.
                                    </div>
                                <?php endif; ?>

                                <!-- Brochure Download Block -->
                                <?php if (!empty($tai_lieu) && !empty($tai_lieu['url'])): ?>
                                    <div class="pt-4 border-t border-border/80">
                                        <div class="flex items-center justify-between gap-4 p-3.5 bg-canvas rounded-xl border border-border/80 hover:border-primary transition-colors">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-9 h-9 rounded-lg bg-primary-light text-primary flex items-center justify-center shrink-0">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                                </div>
                                                <div class="flex flex-col min-w-0">
                                                    <span class="text-xs sm:text-sm font-semibold text-charcoal truncate"><?php echo esc_html($tai_lieu['filename'] ?? 'Brochure-du-an.pdf'); ?></span>
                                                    <?php if (!empty($tai_lieu['filesize'])): ?>
                                                        <span class="text-[11px] text-charcoal-muted"><?php echo size_format($tai_lieu['filesize']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <a href="<?php echo esc_url($tai_lieu['url']); ?>" target="_blank" class="inline-flex items-center gap-1 px-3.5 py-2 bg-primary text-white rounded-lg font-semibold text-xs no-underline hover:bg-primary-hover shadow-button shrink-0" download>
                                                <span>Tải ngay</span>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab Panel 2: Tiến độ thanh toán -->
                            <?php if (!empty($chinh_sach) && trim($chinh_sach) !== ''): ?>
                                <div data-section-tab-panel="pricing-payment" data-tab-group="pricing-policy" class="hidden">
                                    <div class="flex items-center justify-between gap-2 mb-3">
                                        <span class="inline-block px-2.5 py-1 rounded bg-primary/10 text-primary font-bold text-[11px] uppercase tracking-wider">Chính sách ưu đãi &amp; Chiết khấu</span>
                                        <button type="button" class="text-xs text-primary font-semibold hover:underline cursor-pointer inline-flex items-center gap-1" data-modal-target="advisor-modal">
                                            <span>Nhận chiết khấu tối đa &rarr;</span>
                                        </button>
                                    </div>
                                    <div class="project-detail__chinh-sach text-xs sm:text-sm text-charcoal/90 leading-relaxed [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:my-3 [&_ul]:space-y-2 [&_li]:pl-1 [&_p]:mb-2.5">
                                        <?php echo wp_kses_post($chinh_sach); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Tab Panel 3: Tiêu chuẩn bàn giao -->
                            <?php if (!empty($tieu_chuan_ban_giao) && trim($tieu_chuan_ban_giao) !== ''): ?>
                                <div data-section-tab-panel="pricing-handover" data-tab-group="pricing-policy" class="hidden">
                                    <div class="flex items-center justify-between gap-2 mb-3">
                                        <span class="inline-block px-2.5 py-1 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[11px] uppercase tracking-wider">Tiêu chuẩn trang thiết bị bàn giao</span>
                                        <button type="button" class="text-xs text-primary font-semibold hover:underline cursor-pointer inline-flex items-center gap-1" data-modal-target="advisor-modal">
                                            <span>Nhận phụ lục bàn giao &rarr;</span>
                                        </button>
                                    </div>
                                    <div class="project-detail__tieu-chuan-ban-giao text-xs sm:text-sm text-charcoal/90 leading-relaxed">
                                        <?php echo wp_kses_post($tieu_chuan_ban_giao); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Tab Panel 4: Ước tính khoản vay -->
                            <div data-section-tab-panel="pricing-loan" data-tab-group="pricing-policy" class="hidden">
                                <div id="project-loan-section" class="py-1">
                                    <h3 class="text-base font-bold text-charcoal m-0 mb-4">Công cụ tính lãi suất vay ngân hàng mua nhà</h3>
                                    <div class="grid grid-cols-1 lg:grid-cols-[1.2fr_0.8fr] gap-5">
                                        <!-- Form nhập liệu -->
                                        <div class="loan-calculator__form flex flex-col gap-3">
                                            <div class="loan-calculator__field">
                                                <label class="block text-xs font-semibold uppercase tracking-wider text-charcoal-muted mb-1.5">Giá trị nhà đất</label>
                                                <div class="relative">
                                                    <input type="text" id="loan-property-value" class="w-full h-11 bg-canvas border border-border/80 rounded-xl px-3.5 pr-12 text-sm font-bold text-charcoal focus:outline-none focus:border-primary transition-colors" value="3.000.000.000" placeholder="0">
                                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-medium text-charcoal-muted">VNĐ</span>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="loan-calculator__field">
                                                    <label class="block text-xs font-semibold uppercase tracking-wider text-charcoal-muted mb-1.5">Tỷ lệ vay (%)</label>
                                                    <div class="relative">
                                                        <input type="number" id="loan-ratio" class="w-full h-11 bg-canvas border border-border/80 rounded-xl px-3.5 pr-8 text-sm font-semibold text-charcoal focus:outline-none focus:border-primary transition-colors" value="50" min="0" max="85" step="5">
                                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-medium text-charcoal-muted">%</span>
                                                    </div>
                                                </div>
                                                <div class="loan-calculator__field">
                                                    <label class="block text-xs font-semibold uppercase tracking-wider text-charcoal-muted mb-1.5">Số tiền cần vay</label>
                                                    <div class="relative">
                                                        <input type="text" id="loan-amount" class="w-full h-11 bg-canvas border border-border/80 rounded-xl px-3.5 pr-12 text-sm font-semibold text-charcoal focus:outline-none focus:border-primary transition-colors" value="1.500.000.000" placeholder="0">
                                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-medium text-charcoal-muted">VNĐ</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="loan-calculator__field">
                                                    <label class="block text-xs font-semibold uppercase tracking-wider text-charcoal-muted mb-1.5">Thời hạn vay</label>
                                                    <div class="relative">
                                                        <select id="loan-term" class="w-full h-11 bg-canvas border border-border/80 rounded-xl px-3.5 pr-8 text-sm font-semibold text-charcoal focus:outline-none focus:border-primary transition-colors appearance-none">
                                                            <option value="5">5 năm</option>
                                                            <option value="10">10 năm</option>
                                                            <option value="15">15 năm</option>
                                                            <option value="20" selected>20 năm</option>
                                                            <option value="25">25 năm</option>
                                                            <option value="30">30 năm</option>
                                                        </select>
                                                        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-charcoal-muted">
                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="loan-calculator__field">
                                                    <label class="block text-xs font-semibold uppercase tracking-wider text-charcoal-muted mb-1.5">Ngân hàng hỗ trợ</label>
                                                    <div class="relative">
                                                        <select id="loan-bank" class="w-full h-11 bg-canvas border border-border/80 rounded-xl px-3.5 pr-8 text-sm font-semibold text-charcoal focus:outline-none focus:border-primary transition-colors appearance-none">
                                                            <option value="6.5" data-url="https://vietcombank.com.vn">Vietcombank (6.5%)</option>
                                                            <option value="7.0" data-url="https://bidv.com.vn">BIDV (7.0%)</option>
                                                            <option value="6.8" data-url="https://vietinbank.vn">VietinBank (6.8%)</option>
                                                            <option value="7.5" data-url="https://techcombank.com">Techcombank (7.5%)</option>
                                                            <option value="7.9" data-url="https://mbbank.com.vn">MB Bank (7.9%)</option>
                                                            <option value="8.0" data-url="https://vpbank.com.vn">VPBank (8.0%)</option>
                                                        </select>
                                                        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-charcoal-muted">
                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="loan-calculator__field">
                                                    <label class="block text-xs font-semibold uppercase tracking-wider text-charcoal-muted mb-1.5">Lãi suất (%/năm)</label>
                                                    <div class="relative">
                                                        <input type="text" id="loan-interest-rate" class="w-full h-11 bg-canvas border border-border/80 rounded-xl px-3.5 pr-8 text-sm font-semibold text-charcoal focus:outline-none focus:border-primary transition-colors" value="6.5">
                                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-medium text-charcoal-muted">%</span>
                                                    </div>
                                                </div>
                                                <div class="loan-calculator__field">
                                                    <label class="block text-xs font-semibold uppercase tracking-wider text-charcoal-muted mb-1.5">Phương thức trả</label>
                                                    <div class="relative">
                                                        <select id="loan-method" class="w-full h-11 bg-canvas border border-border/80 rounded-xl px-3.5 pr-8 text-sm font-semibold text-charcoal focus:outline-none focus:border-primary transition-colors appearance-none">
                                                            <option value="reducing" selected>Dư nợ giảm dần</option>
                                                            <option value="fixed">Gốc &amp; Lãi đều hàng tháng</option>
                                                        </select>
                                                        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-charcoal-muted">
                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Kết quả tính toán -->
                                        <div class="loan-calculator__results bg-canvas/90 rounded-xl p-4 sm:p-5 border border-border/70 flex flex-col justify-between">
                                            <div>
                                                <span class="text-xs uppercase tracking-wider font-semibold text-charcoal-muted block mb-1">Tổng tiền cần thanh toán</span>
                                                <span class="text-2xl sm:text-[28px] font-extrabold text-charcoal tracking-tight leading-none block mb-4" id="loan-result-total">3.342.743.750 đ</span>

                                                <!-- Thanh tỷ lệ phần trăm -->
                                                <div class="w-full h-2 rounded-full bg-stone overflow-hidden flex mb-2">
                                                    <div class="loan-calculator__chart-equity bg-slate-600 transition-all duration-300" style="width: 44.8%"></div>
                                                    <div class="loan-calculator__chart-principal bg-primary transition-all duration-300" style="width: 44.9%"></div>
                                                    <div class="loan-calculator__chart-interest bg-amber-400 transition-all duration-300" style="width: 10.3%"></div>
                                                </div>
                                                <div class="flex items-center text-[11px] font-bold mb-4">
                                                    <div class="loan-calculator__pct-equity text-slate-600 text-center" style="width: 44.8%">44.8%</div>
                                                    <div class="loan-calculator__pct-principal text-primary text-center" style="width: 44.9%">44.9%</div>
                                                    <div class="loan-calculator__pct-interest text-amber-500 text-center" style="width: 10.3%">10.3%</div>
                                                </div>

                                                <div class="flex flex-col gap-2.5 mb-4 text-xs">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-charcoal-muted flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-slate-600"></span>Vốn tự có</span>
                                                        <strong class="text-charcoal" id="loan-result-equity">1.500.000.000 đ</strong>
                                                    </div>
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-charcoal-muted flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-primary"></span>Gốc cần trả</span>
                                                        <strong class="text-charcoal" id="loan-result-principal">1.500.000.000 đ</strong>
                                                    </div>
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-charcoal-muted flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400"></span>Lãi cần trả</span>
                                                        <strong class="text-charcoal" id="loan-result-interest">342.743.750 đ</strong>
                                                    </div>
                                                </div>

                                                <div class="pt-3 border-t border-border/60">
                                                    <span class="text-[11px] uppercase tracking-wider font-semibold text-charcoal-muted block">Thanh toán tháng đầu</span>
                                                    <span class="text-xl sm:text-2xl font-bold text-primary leading-tight block mt-0.5" id="loan-result-first-month">36.237.500 đ</span>
                                                </div>
                                            </div>

                                            <div class="mt-4 flex flex-col gap-2">
                                                <button id="loan-export-excel" type="button" class="w-full h-10 bg-white hover:bg-stone text-charcoal font-semibold rounded-xl border border-border/80 flex items-center justify-center gap-2 transition-colors text-xs cursor-pointer shadow-xs">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                                    <span>Tải lịch thanh toán chi tiết</span>
                                                </button>
                                                <a href="https://vietcombank.com.vn" id="loan-bank-policy-link" target="_blank" class="flex items-center justify-center gap-1 text-[11px] font-medium text-charcoal-muted hover:text-primary transition-colors">
                                                    <span>Xem chính sách ngân hàng đối tác</span>
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div> <!-- End Left Column -->

                    <!-- Cột phải: Advisor Sidebar & Video Review -->
                    <aside class="hidden lg:!flex flex-col gap-4 sm:gap-5">
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

                        <!-- Video Thực Địa & Review Dự Án -->
                        <?php if ($has_videos) : ?>
                            <?php
                            get_template_part('template-parts/sidebar-video-widget', null, [
                                'videos'           => $project_videos,
                                'title'            => 'Video thực địa &amp; Review',
                                'subtitle'         => get_the_title(),
                                'show_header'      => true,
                                'aspect_ratio'     => '16/10',
                                'autoplay'         => false,
                                'show_meta'        => true,
                                'container_class'  => 'bg-surface rounded-xl sm:rounded-2xl p-4 sm:p-5 border border-border/70 shadow-subtle',
                            ]);
                            ?>
                        <?php endif; ?>

                        <!-- Tin tức thị trường liên quan -->
                        <?php
                        $market_news_args = [
                            'post_type'      => 'post',
                            'posts_per_page' => 4,
                            'post_status'    => 'publish',
                            'orderby'        => 'date',
                            'order'          => 'DESC',
                        ];
                        if (!empty($khu_vuc_terms) && !is_wp_error($khu_vuc_terms)) {
                            $kv_slug = $khu_vuc_terms[0]->slug;
                            $market_news_args['category_name'] = $kv_slug;
                        }
                        $market_news_query = new WP_Query($market_news_args);
                        if (!$market_news_query->have_posts()) {
                            $market_news_query = new WP_Query([
                                'post_type'      => 'post',
                                'posts_per_page' => 4,
                                'post_status'    => 'publish',
                                'orderby'        => 'date',
                                'order'          => 'DESC',
                            ]);
                        }
                        ?>
                        <?php if ($market_news_query->have_posts()) : ?>
                            <div class="bg-surface rounded-xl sm:rounded-2xl p-4 sm:p-5 border border-border/70 shadow-subtle">
                                <div class="flex items-center justify-between mb-3.5 pb-2.5 border-b border-border/60">
                                    <h3 class="text-sm font-bold text-charcoal m-0 uppercase tracking-wider">Tin Tức Thị Trường</h3>
                                    <span class="text-xs text-primary font-medium">Bất động sản</span>
                                </div>
                                <ul class="divide-y divide-border/50 list-none m-0 p-0">
                                    <?php while ($market_news_query->have_posts()) : $market_news_query->the_post(); ?>
                                        <li class="py-2.5 first:pt-0 last:pb-0 flex items-start gap-3 group">
                                            <?php if (has_post_thumbnail()) : ?>
                                                <a href="<?php the_permalink(); ?>" class="block w-16 h-12 rounded-lg overflow-hidden bg-stone shrink-0 border border-border/60">
                                                    <?php the_post_thumbnail('thumbnail', ['class' => 'w-full h-full object-cover transition-transform duration-300 group-hover:scale-105']); ?>
                                                </a>
                                            <?php endif; ?>
                                            <div class="flex flex-col flex-1 min-w-0">
                                                <h4 class="text-xs sm:text-sm font-semibold text-charcoal m-0 mb-0.5 leading-snug">
                                                    <a href="<?php the_permalink(); ?>" class="text-inherit no-underline hover:text-primary transition-colors line-clamp-2"><?php the_title(); ?></a>
                                                </h4>
                                                <time class="text-[11px] text-charcoal-muted" datetime="<?= esc_attr(get_the_date('c')); ?>"><?= esc_html(get_the_date('d/m/Y')); ?></time>
                                            </div>
                                        </li>
                                    <?php endwhile; wp_reset_postdata(); ?>
                                </ul>
                                <a href="<?= esc_url(home_url('/tin-tuc')); ?>" class="inline-flex items-center gap-1.5 text-primary font-semibold text-xs no-underline hover:text-primary-hover mt-3 pt-2.5 border-t border-border/60">
                                    <span>Xem tất cả tin tức</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 15 6" /></svg>
                                </a>
                            </div>
                        <?php endif; ?>
                    </aside>
                </div> <!-- End Grid Layout -->

                <!-- SECTION 06: MUA BÁN & CHO THUÊ (CHỈ HIỂN THỊ KHI CÓ SẢN PHẨM THỰC TẾ) -->
                <?php if ($has_sec_listing) : ?>
                    <div id="project-listing-section" class="bg-surface rounded-xl sm:rounded-2xl p-5 md:p-6 mt-6 md:mt-8 border border-border/70 shadow-subtle max-md:rounded-none max-md:-mx-4 max-md:px-4 max-md:border-x-0" data-section-tabs-container>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-border/80">
                            <div>
                                <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-0.5 block">Thị Trường Giao Dịch</span>
                                <h2 class="text-lg md:text-xl font-bold text-charcoal tracking-tight m-0">
                                    <?php
                                    if ($has_sale && $has_rent) {
                                        echo 'Mua Bán &amp; Cho Thuê Tại ' . esc_html(get_the_title());
                                    } elseif ($has_sale) {
                                        echo 'Bất Động Sản Đang Bán Tại ' . esc_html(get_the_title());
                                    } else {
                                        echo 'Bất Động Sản Cho Thuê Tại ' . esc_html(get_the_title());
                                    }
                                    ?>
                                </h2>
                            </div>
                            <?php
                            $bat_dong_san_url = function_exists('batdongsan_bds_du_an_base_url')
                                ? batdongsan_bds_du_an_base_url($project_id)
                                : add_query_arg('du_an', $project_id, home_url('/bat-dong-san'));
                            ?>
                            <a href="<?php echo esc_url($bat_dong_san_url); ?>" class="text-xs sm:text-sm font-semibold text-primary hover:text-primary-hover hover:underline transition-colors flex items-center gap-1 self-start sm:self-center">
                                <span>Xem tất cả tin đăng (<?php echo ($sale_count + $rent_count); ?>)</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>

                        <?php if ($has_sale && $has_rent) : ?>
                            <!-- Tabs Navigation: Chỉ hiển thị khi CẢ HAI tab đều có sản phẩm -->
                            <div class="bds-section-tabs" role="tablist" aria-label="Tabs Mua bán và Cho thuê">
                                <button type="button" role="tab" aria-selected="true" class="bds-section-tab-btn is-active" data-section-tab-btn="listings-sale" data-tab-group="project-listings">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                                    <span>1. Đang bán</span>
                                    <span class="bds-section-tab-badge"><?php echo $sale_count; ?></span>
                                </button>
                                <button type="button" role="tab" aria-selected="false" class="bds-section-tab-btn" data-section-tab-btn="listings-rent" data-tab-group="project-listings">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
                                    <span>2. Đang cho thuê</span>
                                    <span class="bds-section-tab-badge"><?php echo $rent_count; ?></span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <!-- Tab Panel 1: Đang bán (Chỉ render khi có bài bán) -->
                        <?php if ($has_sale) : ?>
                            <div data-section-tab-panel="listings-sale" data-tab-group="project-listings" class="<?php echo ($has_sale && $has_rent) ? '' : ''; ?>">
                                <div class="relative group/carousel w-full">
                                    <div id="project-listings-carousel" class="bds-carousel flex items-stretch gap-4 md:gap-5 overflow-x-auto scroll-smooth snap-x snap-mandatory no-scrollbar py-2 -mx-4 px-4 sm:mx-0 sm:px-0">
                                        <?php
                                        $hide_project_link_in_item = true;
                                        $single_thumbnail_only = true;
                                        foreach ($sale_listings_query->posts as $value) {
                                            echo '<div class="snap-start shrink-0 w-[82%] sm:w-[calc(50%-10px)] lg:w-[calc(33.333%-14px)] flex flex-col h-auto">';
                                            (function ($value, $hide_project_link_in_item, $single_thumbnail_only) {
                                                include get_template_directory() . '/paths/bat-dong-san-item.php';
                                            })($value, $hide_project_link_in_item, $single_thumbnail_only);
                                            echo '</div>';
                                        }
                                        wp_reset_postdata();
                                        ?>
                                    </div>
                                    <div class="hidden md:block">
                                        <button type="button" class="absolute top-1/2 -translate-y-1/2 -left-5 z-20 w-11 h-11 rounded-full bg-white border border-border shadow-subtle flex items-center justify-center cursor-pointer transition-all duration-300 hover:scale-110 hover:border-primary hover:bg-primary hover:text-white" data-carousel-prev="#project-listings-carousel" aria-label="Trước">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6" /></svg>
                                        </button>
                                        <button type="button" class="absolute top-1/2 -translate-y-1/2 -right-5 z-20 w-11 h-11 rounded-full bg-white border border-border shadow-subtle flex items-center justify-center cursor-pointer transition-all duration-300 hover:scale-110 hover:border-primary hover:bg-primary hover:text-white" data-carousel-next="#project-listings-carousel" aria-label="Tiếp theo">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Tab Panel 2: Đang cho thuê (Chỉ render khi có bài cho thuê) -->
                        <?php if ($has_rent) : ?>
                            <div data-section-tab-panel="listings-rent" data-tab-group="project-listings" class="<?php echo ($has_sale && $has_rent) ? 'hidden' : ''; ?>">
                                <div class="relative group/carousel w-full">
                                    <div id="project-rent-carousel" class="bds-carousel flex items-stretch gap-4 md:gap-5 overflow-x-auto scroll-smooth snap-x snap-mandatory no-scrollbar py-2 -mx-4 px-4 sm:mx-0 sm:px-0">
                                        <?php
                                        $hide_project_link_in_item = true;
                                        $single_thumbnail_only = true;
                                        foreach ($rent_listings_query->posts as $value) {
                                            echo '<div class="snap-start shrink-0 w-[82%] sm:w-[calc(50%-10px)] lg:w-[calc(33.333%-14px)] flex flex-col h-auto">';
                                            (function ($value, $hide_project_link_in_item, $single_thumbnail_only) {
                                                include get_template_directory() . '/paths/bat-dong-san-item.php';
                                            })($value, $hide_project_link_in_item, $single_thumbnail_only);
                                            echo '</div>';
                                        }
                                        wp_reset_postdata();
                                        ?>
                                    </div>
                                    <div class="hidden md:block">
                                        <button type="button" class="absolute top-1/2 -translate-y-1/2 -left-5 z-20 w-11 h-11 rounded-full bg-white border border-border shadow-subtle flex items-center justify-center cursor-pointer transition-all duration-300 hover:scale-110 hover:border-primary hover:bg-primary hover:text-white" data-carousel-prev="#project-rent-carousel" aria-label="Trước">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6" /></svg>
                                        </button>
                                        <button type="button" class="absolute top-1/2 -translate-y-1/2 -right-5 z-20 w-11 h-11 rounded-full bg-white border border-border shadow-subtle flex items-center justify-center cursor-pointer transition-all duration-300 hover:scale-110 hover:border-primary hover:bg-primary hover:text-white" data-carousel-next="#project-rent-carousel" aria-label="Tiếp theo">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- FAQ – CÂU HỎI THƯỜNG GẶP (ĐƯA XUỐNG CUỐI TRANG, DẠNG ACCORDION CHUẨN) -->
                <?php if ($has_faq): ?>
                    <div id="project-faq-section" class="bg-surface rounded-xl sm:rounded-2xl p-5 md:p-6 mt-6 md:mt-8 border border-border/70 shadow-subtle max-md:rounded-none max-md:-mx-4 max-md:px-4 max-md:border-x-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-border/80">
                            <div>
                                <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-0.5 block">Hỏi Đáp Dự Án</span>
                                <h2 class="text-lg md:text-xl font-bold text-charcoal tracking-tight m-0">Câu Hỏi Thường Gặp Về <?php echo esc_html(get_the_title()); ?></h2>
                            </div>
                            <span class="text-xs text-charcoal-muted font-medium"><?php echo count($faq_items); ?> câu hỏi giải đáp</span>
                        </div>

                        <?php if (count($faq_items) > 3): ?>
                            <!-- Phân nhóm chủ đề câu hỏi nếu có nhiều câu hỏi -->
                            <div class="flex items-center gap-2 mb-4 overflow-x-auto no-scrollbar pb-1">
                                <button type="button" class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-primary text-white shadow-xs cursor-pointer shrink-0" data-faq-filter="all">Tất cả</button>
                                <button type="button" class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60 cursor-pointer shrink-0" data-faq-filter="phap-ly">Pháp lý &amp; Sổ hồng</button>
                                <button type="button" class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60 cursor-pointer shrink-0" data-faq-filter="gia-ban">Giá bán &amp; Thanh toán</button>
                                <button type="button" class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60 cursor-pointer shrink-0" data-faq-filter="tien-ich">Tiện ích &amp; Vận hành</button>
                            </div>
                        <?php endif; ?>

                        <div class="space-y-3">
                            <?php foreach ($faq_items as $f_idx => $faq): ?>
                                <?php
                                $q = $faq['cau_hoi'] ?? '';
                                $a = $faq['cau_tra_loi'] ?? '';
                                if (empty($q)) continue;
                                $faq_topic = 'all';
                                if (preg_match('/sổ|pháp lý|quy hoạch|hợp đồng/iu', $q)) {
                                    $faq_topic = 'phap-ly';
                                } elseif (preg_match('/giá|thanh toán|tiền|vay|ngân hàng|chiết khấu/iu', $q)) {
                                    $faq_topic = 'gia-ban';
                                } elseif (preg_match('/tiện ích|phí|vận hành|quản lý|đỗ xe|hồ bơi/iu', $q)) {
                                    $faq_topic = 'tien-ich';
                                }
                                ?>
                                <details class="group bg-canvas rounded-xl border border-border/70 transition-all overflow-hidden" data-faq-item data-faq-topic="<?php echo esc_attr($faq_topic); ?>" <?php echo $f_idx === 0 ? 'open' : ''; ?>>
                                    <summary class="font-semibold text-xs sm:text-sm text-charcoal p-3.5 sm:p-4 cursor-pointer flex items-center justify-between list-none select-none hover:text-primary transition-colors">
                                        <span class="flex items-center gap-2.5 pr-2">
                                            <span class="w-5 h-5 rounded-md bg-primary-light text-primary text-[11px] font-bold flex items-center justify-center shrink-0">Q</span>
                                            <span><?php echo esc_html($q); ?></span>
                                        </span>
                                        <svg class="w-4 h-4 text-charcoal-muted group-open:rotate-180 transition-transform duration-200 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    </summary>
                                    <div class="px-3.5 sm:px-4 pb-3.5 sm:pb-4 text-xs sm:text-sm text-charcoal/80 leading-relaxed border-t border-border/50 pt-3">
                                        <?php echo wp_kses_post($a); ?>
                                    </div>
                                </details>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- DỰ ÁN LIÊN QUAN (ĐƯA XUỐNG DƯỚI CÙNG, KHÔNG NẰM TRONG NAV CẤP 1) -->
                <?php if ($has_related_projects && $related_projects_query->have_posts()) : ?>
                    <div id="project-related-projects-section" class="bg-surface rounded-xl sm:rounded-2xl p-5 md:p-6 mt-6 md:mt-8 border border-border/70 shadow-subtle max-md:rounded-none max-md:-mx-4 max-md:px-4 max-md:border-x-0">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-border/80">
                            <div>
                                <span class="text-xs font-semibold tracking-[0.2em] uppercase text-primary mb-0.5 block font-sans">Khu Vực Tương Đồng</span>
                                <h2 class="text-lg md:text-xl font-bold text-charcoal tracking-tight m-0">
                                    Dự án lân cận
                                    <span class="text-xs sm:text-sm font-normal text-charcoal-muted ml-2 font-sans">(<?php echo count($related_projects_query->posts); ?> dự án<?php echo !empty($nearby_district_name) ? " tại " . esc_html($nearby_district_name) : ""; ?>)</span>
                                </h2>
                            </div>
                        </div>
                        <div class="relative group/carousel w-full">
                            <div id="project-related-carousel" class="bds-carousel flex items-stretch gap-4 md:gap-5 overflow-x-auto scroll-smooth snap-x snap-mandatory no-scrollbar py-2 -mx-4 px-4 sm:mx-0 sm:px-0">
                                <?php while ($related_projects_query->have_posts()) : $related_projects_query->the_post(); ?>
                                    <?php
                                    $pk_id = get_the_ID();
                                    $pk_title = get_the_title();
                                    $pk_link = get_permalink();
                                    $pk_trang_thai = get_field('trang_thai', $pk_id);
                                    $pk_trang_thai_label = isset($trang_thai_labels[$pk_trang_thai]) ? $trang_thai_labels[$pk_trang_thai] : '';
                                    $pk_featured = get_field('featured', $pk_id);
                                    $pk_du_an_cha = get_field('du_an_cha', $pk_id);
                                    $pk_address = function_exists('batdongsan_get_project_meta_recursive') ? batdongsan_get_project_meta_recursive('address', $pk_id) : get_field('address', $pk_id);
                                    $pk_quy_mo = function_exists('batdongsan_get_project_meta_recursive') ? batdongsan_get_project_meta_recursive('quy_mo', $pk_id) : get_field('quy_mo', $pk_id);
                                    $pk_status_color = $status_colors[$pk_trang_thai] ?? 'bg-stone text-charcoal-muted border border-border';
                                    ?>
                                    <div class="snap-start shrink-0 w-[82%] sm:w-[calc(50%-10px)] lg:w-[calc(33.333%-14px)] flex flex-col h-auto">
                                        <div class="relative h-full">
                                            <div class="group block flex flex-col gap-0 relative bg-surface rounded-xl overflow-hidden shadow-card border border-border/80 transition-all duration-300 hover:-translate-y-1 hover:shadow-card-hover h-full">
                                                <div class="relative w-full shrink-0 aspect-[16/10] bg-stone overflow-hidden flex">
                                                    <a href="<?php echo esc_url($pk_link); ?>" class="block w-full h-full relative overflow-hidden group" aria-hidden="true" tabindex="-1">
                                                        <?php if (has_post_thumbnail($pk_id)) : ?>
                                                            <?php echo get_the_post_thumbnail($pk_id, 'medium_large', ['class' => 'w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105']); ?>
                                                        <?php else : ?>
                                                            <div class="w-full h-full flex items-center justify-center text-charcoal-muted">
                                                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                                                    <polyline points="9 22 9 12 15 12 15 22" />
                                                                </svg>
                                                            </div>
                                                        <?php endif; ?>
                                                    </a>
                                                    <div class="absolute top-3 left-3 flex flex-col gap-1.5 pointer-events-none">
                                                        <?php if ($pk_trang_thai_label) : ?>
                                                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider <?php echo esc_attr($pk_status_color); ?>"><?php echo esc_html($pk_trang_thai_label); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($pk_featured) : ?>
                                                            <span class="px-2.5 py-1 bg-gold rounded-md text-[10px] font-bold text-white uppercase tracking-wider shadow-subtle">Nổi bật</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="p-5 flex-1 min-w-0 flex flex-col justify-between gap-3">
                                                    <div>
                                                        <h3 class="text-base font-semibold text-charcoal m-0 mb-1.5 leading-snug line-clamp-2">
                                                            <a href="<?php echo esc_url($pk_link); ?>" class="text-inherit no-underline hover:text-primary transition-colors"><?php echo esc_html($pk_title); ?></a>
                                                        </h3>
                                                        <?php if ($pk_du_an_cha) :
                                                            $parent_id = $pk_du_an_cha instanceof WP_Post ? $pk_du_an_cha->ID : (int) $pk_du_an_cha;
                                                            $parent_title = $pk_du_an_cha instanceof WP_Post ? $pk_du_an_cha->post_title : get_the_title($parent_id);
                                                            $parent_url = get_permalink($parent_id);
                                                        ?>
                                                            <p class="text-xs text-charcoal-muted m-0 mb-2">
                                                                Thuộc dự án: <a href="<?php echo esc_url($parent_url); ?>" class="font-semibold text-primary hover:underline"><?php echo esc_html($parent_title); ?></a>
                                                            </p>
                                                        <?php endif; ?>
                                                        <?php if ($pk_address) : ?>
                                                            <p class="flex items-center gap-1.5 text-xs text-charcoal-muted m-0 mb-2">
                                                                <span class="text-primary shrink-0" aria-hidden="true">
                                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                                                        <path d="M12 21s7-5.1 7-11a7 7 0 1 0-14 0c0 5.9 7 11 7 11Z" stroke-linecap="round" stroke-linejoin="round" />
                                                                        <circle cx="12" cy="10" r="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                                                    </svg>
                                                                </span>
                                                                <span class="line-clamp-1"><?php echo esc_html($pk_address); ?></span>
                                                            </p>
                                                        <?php endif; ?>
                                                        <?php if ($pk_quy_mo) : ?>
                                                            <p class="text-xs text-charcoal-muted m-0">
                                                                Quy mô: <span><?php echo esc_html($pk_quy_mo); ?></span>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="pt-3 border-t border-border/70 flex items-center justify-between">
                                                        <a href="<?php echo esc_url($pk_link); ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:text-primary-hover transition-colors">
                                                            <span>Xem chi tiết</span>
                                                            <span aria-hidden="true">&rarr;</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                                <?php wp_reset_postdata(); ?>
                            </div>
                            <div class="hidden md:block">
                                <button type="button" class="absolute top-1/2 -translate-y-1/2 -left-5 z-20 w-11 h-11 rounded-full bg-white border border-border shadow-subtle flex items-center justify-center cursor-pointer transition-all duration-300 hover:scale-110 hover:border-primary hover:bg-primary hover:text-white" data-carousel-prev="#project-related-carousel" aria-label="Trước">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6" /></svg>
                                </button>
                                <button type="button" class="absolute top-1/2 -translate-y-1/2 -right-5 z-20 w-11 h-11 rounded-full bg-white border border-border shadow-subtle flex items-center justify-center cursor-pointer transition-all duration-300 hover:scale-110 hover:border-primary hover:bg-primary hover:text-white" data-carousel-next="#project-related-carousel" aria-label="Tiếp theo">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div> <!-- End Max Width Container -->
        </main>

<?php
    endwhile;
endif;

get_footer();
