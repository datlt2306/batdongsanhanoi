<?php

/**
 * Template hiển thị 1 item bất động sản (bat-dong-san, hang-chuyen-nhuong)
 * Layout 2 hàng: hàng trên ảnh, hàng dưới thông tin
 * Hiển thị thông tin BĐS (giá, diện tích, địa chỉ, mô tả)
 */

// Nội dung giữ nguyên logic từ template cũ, chỉ đổi tên file/description cho đúng context BĐS

$post_id = $value->ID;
$is_featured = get_field('featured', $post_id);
$dia_chi = get_field('dia_chi', $post_id);
$khoang_gia = get_field('khoang_gia', $post_id);
$gia_tren_met_vuong = get_field('gia_tren_met_vuong', $post_id);
$gia_cho_thue = get_field('gia_cho_thue', $post_id);
$dien_tich = get_field('dien_tich', $post_id);
$dien_tich_su_dung = get_field('dien_tich_su_dung', $post_id);
$mat_tien = get_field('mat_tien', $post_id);
$so_mat_tien = get_field('so_mat_tien', $post_id);
$so_tang = get_field('so_tang', $post_id);
$card_phap_ly = get_field('phap_ly', $post_id);
$card_huong_nha = get_field('huong_nha', $post_id);
$duong_vao = get_field('duong_vao', $post_id);
$gallery_nha_dat = get_field('gallery_nha_dat', $post_id);
$video_url = get_field('video_url', $post_id);
$trang_thai = get_field('trang_thai', $post_id);
$mo_ta_chi_tiet = get_field('mo_ta_chi_tiet', $post_id);
$ngay_dang = get_field('ngay_dang', $post_id);
$is_single_thumb = !empty($single_thumbnail_only) || (function_exists('is_singular') && is_singular('du-an'));

// Lấy thông tin cấp độ dự án
$du_an_lien_quan = function_exists('batdongsan_get_listing_project')
    ? batdongsan_get_listing_project($post_id)
    : null;
if (!$du_an_lien_quan) {
    $du_an_lien_quan_raw = get_field('du_an_lien_quan', $post_id);
    if (!empty($du_an_lien_quan_raw)) {
        $first = is_array($du_an_lien_quan_raw) ? $du_an_lien_quan_raw[0] : $du_an_lien_quan_raw;
        $project_id = $first instanceof WP_Post ? $first->ID : (int) $first;
        if ($project_id > 0) {
            $du_an_lien_quan = (object) [
                'id'    => $project_id,
                'title' => get_the_title($project_id),
                'url'   => get_permalink($project_id),
            ];
        }
    }
}
$phan_khu_terms = get_the_terms($post_id, 'phan-khu'); // Level 3
$phan_khu_name = ($phan_khu_terms && !is_wp_error($phan_khu_terms)) ? $phan_khu_terms[0]->name : '';

// Lấy loại hình: hang-chuyen-nhuong dùng danh-muc-hang-chuyen-nhuong, bat-dong-san dùng loai_hinh_bat_dong_san
$post_type = function_exists('get_post_type') ? get_post_type($post_id) : ($value->post_type ?? 'bat-dong-san');
$loai_hinh_name = '';
if ($post_type === 'hang-chuyen-nhuong') {
    $loai_hinh_terms = get_the_terms($post_id, 'danh-muc-hang-chuyen-nhuong');
    if ($loai_hinh_terms && !is_wp_error($loai_hinh_terms) && !empty($loai_hinh_terms)) {
        $loai_hinh_name = $loai_hinh_terms[0]->name;
    }
} else {
    $loai_hinh_terms = get_the_terms($post_id, 'loai_hinh_bat_dong_san');
    if ($loai_hinh_terms && !is_wp_error($loai_hinh_terms) && !empty($loai_hinh_terms)) {
        $loai_hinh_name = $loai_hinh_terms[0]->name;
    }
}
if ($loai_hinh_name && $post_type !== 'hang-chuyen-nhuong') {
    if (function_exists('mb_ucfirst')) {
        $loai_hinh_name = mb_ucfirst($loai_hinh_name);
    } else {
        $loai_hinh_name = mb_strtoupper(mb_substr($loai_hinh_name, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($loai_hinh_name, 1, null, 'UTF-8');
    }
}

// Lấy ảnh từ gallery ACF (return_format="array" trả về array với key 'ID')
// Layout: 1 ảnh lớn bên trái + tối đa 3 ảnh nhỏ bên phải (tổng tối đa 4 ảnh)
$demo_image_url = get_template_directory_uri() . '/assets/images/placeholder-real-estate.jpg';
$main_image_url = '';
$gallery_images = [];
$gallery_image_ids = [];
$total_gallery_count = 0;
$remaining_images_count = 0;

if ($gallery_nha_dat && is_array($gallery_nha_dat) && !empty($gallery_nha_dat)) {
    // Chuyển đổi gallery ACF từ array sang mảng ID ảnh
    foreach ($gallery_nha_dat as $image) {
        // Gallery ACF return_format="array" trả về array với key 'ID' hoặc có thể là ID trực tiếp
        $image_id = is_array($image) && isset($image['ID']) ? (int) $image['ID'] : (is_numeric($image) ? (int) $image : 0);
        if ($image_id > 0) {
            $gallery_image_ids[] = $image_id;
        }
    }

    // Lưu tổng số ảnh để hiển thị badge
    $total_gallery_count = count($gallery_image_ids);

    // Chỉ lấy tối đa 4 ảnh để hiển thị (1 ảnh lớn + 3 ảnh nhỏ)
    $display_image_ids = array_slice($gallery_image_ids, 0, 4);

    // Lấy ảnh chính (ảnh đầu tiên)
    if (!empty($display_image_ids)) {
        $main_image_url = wp_get_attachment_image_url($display_image_ids[0], 'large');
        // Lấy tối đa 3 ảnh tiếp theo cho grid góc phải (2 ảnh trên + 1 ảnh dưới)
        for ($i = 1; $i < min(4, count($display_image_ids)); $i++) {
            $gallery_images[] = [
                'type' => 'image',
                'url' => wp_get_attachment_image_url($display_image_ids[$i], 'medium'),
                'id' => $display_image_ids[$i],
                'index' => $i - 1 // Index để xác định vị trí trong grid (0, 1, 2)
            ];
        }
    }
}

// Kiểm tra có video không
$has_video = !empty($video_url);
$total_media_count = $total_gallery_count + ($has_video ? 1 : 0);

// Tính số media còn lại (sau khi hiển thị 4 media đầu: 1 ảnh lớn + 3 nhỏ)
if ($total_media_count > 4) {
    $remaining_images_count = $total_media_count - 4;
}

// Xử lý video URL nếu có
if (!empty($video_url)) {
    // Lấy thumbnail từ YouTube/Vimeo
    $video_thumbnail_url = bds_get_video_thumbnail($video_url);

    // Nếu có video và chưa đủ 3 items trong gallery, thêm video vào
    if (count($gallery_images) < 3 && !empty($video_thumbnail_url)) {
        $gallery_images[] = [
            'type' => 'video',
            'url' => $video_thumbnail_url,
            'video_url' => $video_url,
            'index' => count($gallery_images)
        ];
    }
}

// Fallback về featured image nếu không có gallery
if (empty($main_image_url) && function_exists('has_post_thumbnail') && has_post_thumbnail($post_id)) {
    $main_image_url = function_exists('get_the_post_thumbnail_url') ? get_the_post_thumbnail_url($post_id, 'large') : '';
}

// Nếu vẫn chưa có ảnh chính, dùng ảnh demo
if (empty($main_image_url)) {
    $main_image_url = $demo_image_url;
}

// Nếu không có bất kỳ ảnh gallery nào, tạo 3 thumbnail demo để tránh lỗ trống (chỉ khi không phải single thumbnail)
if (!$is_single_thumb && empty($gallery_images) && empty($gallery_image_ids)) {
    for ($i = 0; $i < 3; $i++) {
        $gallery_images[] = [
            'type' => 'image',
            'url' => $demo_image_url,
            'id'  => 0,
        ];
    }
    $remaining_images_count = 0;
}

// Tính giá/m² nếu chưa có
if (!$gia_tren_met_vuong && $khoang_gia && $dien_tich) {
    // Extract số từ khoang_gia (ví dụ: "17,5 tỷ" -> 17.5)
    preg_match('/([\d,]+)/', str_replace(',', '.', $khoang_gia), $matches);
    if (!empty($matches[1])) {
        $gia_number = floatval(str_replace(',', '.', $matches[1]));
        // Extract số từ dien_tich (ví dụ: "120 m²" -> 120)
        preg_match('/([\d,]+)/', $dien_tich, $area_matches);
        if (!empty($area_matches[1])) {
            $area_number = floatval(str_replace(',', '.', $area_matches[1]));
            if ($area_number > 0) {
                $gia_tren_met_vuong = number_format(($gia_number * 1000) / $area_number, 2, ',', '.') . ' tr/m²';
            }
        }
    }
}

// Giá trị hiển thị luôn có fallback, không để trống
$display_price = $khoang_gia ?: 'Giá liên hệ';
$display_area = $dien_tich ?: 'Diện tích đang cập nhật';
$display_price_per_m2 = $gia_tren_met_vuong ?: 'Đơn giá đang cập nhật';
$display_address = $dia_chi ?: 'Địa chỉ đang cập nhật';

// Các label cho pháp lý, hướng nhà, số mặt tiền
$phap_ly_labels = [
    'so_do' => 'Sổ đỏ',
    'so_hong' => 'Sổ hồng',
    'so_do_so_hong' => 'Sổ đỏ/ Sổ hồng',
    'dang_ky_quyen_su_dung_dat' => 'Đang ký quyền sử dụng đất',
    'giay_chung_nhan' => 'Giấy chứng nhận',
    'khac' => 'Pháp lý khác',
];

$huong_nha_labels = [
    'dong' => 'Đông',
    'tay' => 'Tây',
    'nam' => 'Nam',
    'bac' => 'Bắc',
    'dong_bac' => 'Đông Bắc',
    'dong_nam' => 'Đông Nam',
    'tay_bac' => 'Tây Bắc',
    'tay_nam' => 'Tây Nam',
];

$so_mat_tien_labels = [
    '1' => '1 mặt tiền',
    '2' => '2 mặt tiền',
    '3' => '3 mặt tiền',
    '4' => '4 mặt tiền trở lên',
];

$display_phap_ly = $card_phap_ly && isset($phap_ly_labels[$card_phap_ly]) ? $phap_ly_labels[$card_phap_ly] : '';
$display_huong_nha = $card_huong_nha && isset($huong_nha_labels[$card_huong_nha]) ? $huong_nha_labels[$card_huong_nha] : '';
$display_so_mat_tien = $so_mat_tien && isset($so_mat_tien_labels[$so_mat_tien]) ? $so_mat_tien_labels[$so_mat_tien] : '';

// Gom các thông tin phụ thành mảng để hiển thị
$extra_items = [];
if ($dien_tich_su_dung) {
    $extra_items[] = $dien_tich_su_dung;
}
if ($mat_tien) {
    $extra_items[] = 'Mặt tiền ' . $mat_tien;
}
if ($display_so_mat_tien) {
    $extra_items[] = $display_so_mat_tien;
}
if ($so_tang) {
    $extra_items[] = $so_tang;
}
if ($display_phap_ly) {
    $extra_items[] = $display_phap_ly;
}
if ($display_huong_nha) {
    $extra_items[] = 'Hướng ' . $display_huong_nha;
}
if ($duong_vao) {
    $extra_items[] = 'Đường vào ' . $duong_vao;
}
if ($gia_cho_thue) {
    $extra_items[] = 'Giá thuê ' . $gia_cho_thue;
}

// Format ngày đăng
$ngay_dang_display = '';
if ($ngay_dang) {
    $ngay_dang_display = function_exists('date_i18n') ? date_i18n('d/m/Y', strtotime($ngay_dang)) : date('d/m/Y', strtotime($ngay_dang));
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($ngay_dang == $today) {
        $ngay_dang_display = 'Đăng hôm nay';
    } elseif ($ngay_dang == $yesterday) {
        $ngay_dang_display = 'Đăng hôm qua';
    }
} else {
    $post_date = function_exists('get_the_date') ? get_the_date('Y-m-d', $post_id) : date('Y-m-d');
    $today = date('Y-m-d');
    if ($post_date == $today) {
        $ngay_dang_display = 'Đăng hôm nay';
    } else {
        $ngay_dang_display = 'Đăng ' . (function_exists('get_the_date') ? get_the_date('d/m/Y', $post_id) : date('d/m/Y'));
    }
}

// Lấy avatar từ author hoặc default (cho tương lai nếu cần)
$author_id = get_post_field('post_author', $post_id);
$author_avatar_url = '';
if ($author_id) {
    $author_avatar_url = get_avatar_url($author_id, ['size' => 80]);
}

// Layout 2 hàng: hàng trên ảnh, hàng dưới thông tin
$card_classes = 'group bg-surface rounded-xl border border-border/80 overflow-hidden shadow-card transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-card-hover flex flex-col';
$has_thumbnails = !$is_single_thumb && $total_media_count > 1 && !empty($gallery_images);
?>
<article class="<?= esc_attr($card_classes); ?>">
    <!-- Hàng trên: Gallery Media (1 ảnh lớn hoặc 1 ảnh lớn + 3 thumbnails) -->
    <div class="relative w-full shrink-0 <?php echo $has_thumbnails ? 'h-[210px] md:h-[240px] flex max-md:h-auto max-md:bg-surface max-md:flex-col max-md:gap-2' : 'h-[200px] sm:h-[220px] md:h-[240px] block'; ?> bg-stone overflow-hidden">
        <?php if ($has_thumbnails) : ?>
            <!-- Layout: ảnh lớn trái (2/3) + 3 ảnh nhỏ phải (1/3) -->
            <a href="<?= get_permalink($post_id); ?>" class="block w-2/3 h-full shrink-0 relative overflow-hidden max-md:w-full max-md:h-auto max-md:aspect-video max-md:rounded-xl" aria-label="Chi tiết <?= esc_attr($value->post_title); ?>">
                <img src="<?= esc_url($main_image_url); ?>" alt="<?= esc_attr($value->post_title); ?>" loading="lazy" decoding="async" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105">

                <!-- Badge Nổi bật (Mobile overlay trực tiếp lên ảnh chính) -->
                <?php if ($is_featured) : ?>
                    <div class="hidden max-md:block absolute top-3 left-3 z-10 px-2.5 py-1 bg-charcoal/85 text-white text-[10px] font-bold rounded-md shadow-subtle uppercase tracking-wider backdrop-blur-sm">
                        <span>NỔI BẬT</span>
                    </div>
                <?php endif; ?>
                <?php if ($loai_hinh_name || $phan_khu_name) : ?>
                    <div class="hidden max-md:flex absolute top-3 right-3 z-10 flex-wrap gap-1 justify-end max-w-[70%]">
                        <?php if ($phan_khu_name) : ?>
                            <span class="px-2 py-0.5 bg-primary text-white text-[10px] font-semibold rounded-md shadow-subtle"><?= esc_html($phan_khu_name); ?></span>
                        <?php endif; ?>
                        <?php if ($loai_hinh_name) : ?>
                            <span class="px-2 py-0.5 bg-surface/90 text-charcoal text-[10px] font-semibold rounded-md shadow-subtle backdrop-blur-sm border border-border/60"><?= esc_html($loai_hinh_name); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </a>
            <div class="w-1/3 h-full flex flex-col gap-0.5 pl-0.5 shrink-0 max-md:w-full max-md:h-auto max-md:flex-row max-md:gap-2 max-md:pl-0">
                <?php for ($i = 0; $i < min(3, count($gallery_images)); $i++) : ?>
                    <?php $item = $gallery_images[$i];
                    $img_url = isset($item['url']) ? $item['url'] : $demo_image_url; ?>
                    <a href="<?= get_permalink($post_id); ?>" class="relative block flex-1 min-h-0 overflow-hidden max-md:aspect-[4/3] max-md:rounded-xl group/thumb" aria-hidden="true">
                        <img src="<?= esc_url($img_url); ?>" alt="<?= esc_attr($value->post_title . ' - Hình ảnh chi tiết ' . ($i + 1)); ?>" loading="lazy" decoding="async" class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover/thumb:scale-105">
                        <?php if ($i === 2 && $remaining_images_count > 0) : // Ảnh cuối cùng trên mobile ?>
                            <div class="hidden max-md:flex absolute inset-0 bg-charcoal/60 backdrop-blur-xs flex-col items-center justify-center">
                                <span class="text-white font-bold text-base leading-none">+<?= esc_html($remaining_images_count); ?></span>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php else : ?>
            <!-- Chỉ 1 ảnh thumbnail duy nhất (Full width & height) -->
            <a href="<?= get_permalink($post_id); ?>" class="block w-full h-full relative overflow-hidden group/image" aria-label="Chi tiết <?= esc_attr($value->post_title); ?>">
                <img src="<?= esc_url($main_image_url); ?>" alt="<?= esc_attr($value->post_title); ?>" loading="lazy" decoding="async" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105">
                <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>

                <!-- Badges trên Mobile cho 1 ảnh duy nhất -->
                <?php if ($is_featured) : ?>
                    <div class="hidden max-md:block absolute top-3 left-3 z-10 px-2.5 py-1 bg-charcoal/85 text-white text-[10px] font-bold rounded-md shadow-subtle uppercase tracking-wider backdrop-blur-sm">
                        <span>NỔI BẬT</span>
                    </div>
                <?php endif; ?>
                <?php if ($loai_hinh_name || $phan_khu_name) : ?>
                    <div class="hidden max-md:flex absolute top-3 right-3 z-10 flex-wrap gap-1 justify-end max-w-[70%]">
                        <?php if ($phan_khu_name) : ?>
                            <span class="px-2 py-0.5 bg-primary text-white text-[10px] font-semibold rounded-md shadow-subtle"><?= esc_html($phan_khu_name); ?></span>
                        <?php endif; ?>
                        <?php if ($loai_hinh_name) : ?>
                            <span class="px-2 py-0.5 bg-surface/90 text-charcoal text-[10px] font-semibold rounded-md shadow-subtle backdrop-blur-sm border border-border/60"><?= esc_html($loai_hinh_name); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <!-- Badges trên Desktop -->
        <?php if ($is_featured) : ?>
            <div class="absolute top-3 left-3 z-10 max-md:hidden">
                <span class="px-2.5 py-1 bg-charcoal/85 rounded-md text-[10px] font-bold text-white uppercase tracking-wider shadow-subtle backdrop-blur-sm">Nổi bật</span>
            </div>
        <?php endif; ?>
        <?php if ($loai_hinh_name || $phan_khu_name) : ?>
            <div class="absolute top-3 right-3 z-10 flex flex-wrap gap-1.5 justify-end max-w-[50%] max-md:hidden">
                <?php if ($phan_khu_name) : ?>
                    <span class="px-2.5 py-1 bg-primary text-white text-xs font-semibold rounded-full shadow-subtle"><?= esc_html($phan_khu_name); ?></span>
                <?php endif; ?>
                <?php if ($loai_hinh_name) : ?>
                    <span class="px-2.5 py-1 bg-surface/90 text-xs font-semibold text-charcoal rounded-full shadow-subtle backdrop-blur-sm border border-border/60"><?= esc_html($loai_hinh_name); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if (!$is_single_thumb && $total_media_count > 1) : ?>
            <div class="absolute bottom-2.5 right-2.5 z-10 flex items-center gap-1 px-2.5 py-1 bg-charcoal/70 text-white text-xs font-medium rounded-lg backdrop-blur-sm max-md:hidden">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                    <circle cx="8.5" cy="8.5" r="1.5" />
                    <polyline points="21 15 16 10 5 21" />
                </svg>
                <span><?= (int) $total_media_count; ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Hàng dưới: Thông tin chi tiết -->
    <div class="flex flex-col gap-3 p-4 md:p-5 min-w-0 flex-1">
        <div>
            <h3 class="font-sans font-semibold text-sm sm:text-[15px] text-navy m-0 leading-snug line-clamp-2" title="<?= esc_attr($value->post_title); ?>">
                <a href="<?= get_permalink($post_id); ?>" class="hover:text-brand-red transition-colors duration-200"><?= esc_html($value->post_title); ?></a>
            </h3>
            <?php if (empty($hide_project_link_in_item) && $du_an_lien_quan && !empty($du_an_lien_quan->title)) : ?>
                <div class="flex items-center gap-1.5 mt-1.5 text-xs text-charcoal-muted">
                    <span class="text-primary shrink-0" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 10.5L12 3l9 7.5V20a1.5 1.5 0 0 1-1.5 1.5H4.5A1.5 1.5 0 0 1 3 20v-9.5z" />
                            <path d="M18 7.5V4h-2.5v1.4" />
                            <path d="M9.5 21.5v-6a2.5 2.5 0 0 1 5 0v6" />
                        </svg>
                    </span>
                    <span>Thuộc dự án:</span>
                    <a href="<?= esc_url($du_an_lien_quan->url); ?>" class="font-semibold text-primary hover:text-primary-hover transition-colors underline-offset-2 hover:underline"><?= esc_html($du_an_lien_quan->title); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($single_description)) : ?>
            <div class="text-sm text-charcoal-muted leading-relaxed line-clamp-2">
                <?= esc_html($single_description); ?>
            </div>
        <?php endif; ?>

        <!-- Key Specs Row: Price, Area, Unit price, Address (Apple SF Symbols Standard) -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm pt-1">
            <span class="flex items-center gap-1.5 text-brand-red text-base md:text-lg font-bold tracking-tight">
                <span class="shrink-0 text-brand-red" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="6" width="18" height="12" rx="2.5" />
                        <circle cx="12" cy="12" r="2.5" stroke-width="1.6" />
                        <path d="M6 9h.01M18 15h.01" stroke-width="2" />
                    </svg>
                </span>
                <?= esc_html($display_price); ?>
            </span>
            <span class="flex items-center gap-1.5 text-charcoal font-semibold text-sm">
                <span class="shrink-0 text-primary" aria-hidden="true">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <rect x="3.5" y="3.5" width="17" height="17" rx="3.5" />
                        <path d="M3.5 14h17M10 14v7.5M14 3.5v10.5" />
                    </svg>
                </span>
                <?= esc_html($display_area); ?>
            </span>
            <?php if ($display_price_per_m2 && $display_price_per_m2 !== 'Đơn giá đang cập nhật') : ?>
                <span class="flex items-center gap-1.5 text-xs text-charcoal-muted">
                    <span class="text-primary/70 shrink-0" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                            <circle cx="5" cy="7" r="1.5" fill="currentColor"/>
                            <line x1="9" y1="7" x2="20" y2="7" stroke-width="2"/>
                            <circle cx="5" cy="12" r="1.5" fill="currentColor"/>
                            <line x1="9" y1="12" x2="20" y2="12" stroke-width="2"/>
                            <circle cx="5" cy="17" r="1.5" fill="currentColor"/>
                            <line x1="9" y1="17" x2="20" y2="17" stroke-width="2"/>
                        </svg>
                    </span>
                    <?= esc_html($display_price_per_m2); ?>
                </span>
            <?php endif; ?>
            <span class="flex items-center gap-1.5 text-xs text-charcoal-muted">
                <span class="text-primary shrink-0" aria-hidden="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2.5a6.5 6.5 0 0 0-6.5 6.5c0 4.8 6.5 12.5 6.5 12.5s6.5-7.7 6.5-12.5a6.5 6.5 0 0 0-6.5-6.5z" />
                        <circle cx="12" cy="9" r="2.2" />
                    </svg>
                </span>
                <span class="truncate max-w-[280px]"><?= esc_html($display_address); ?></span>
            </span>
        </div>

        <?php if (!empty($extra_items)) : ?>
            <div class="flex items-center flex-wrap gap-1.5 text-xs text-charcoal-muted">
                <?php foreach (array_slice($extra_items, 0, 4) as $extra) : ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-stone/50 border border-border/70 text-[11px] font-medium text-charcoal">
                        <?= esc_html($extra); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Footer Row: Date & Action CTA -->
        <div class="flex items-center justify-between gap-3 pt-3 mt-auto border-t border-border/70 text-xs text-charcoal-muted">
            <div class="flex items-center gap-1.5">
                <?php if ($ngay_dang_display) : ?>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="text-primary/70">
                        <rect x="3" y="4.5" width="18" height="16.5" rx="3.5" />
                        <line x1="16" y1="2" x2="16" y2="5" stroke-linecap="round" />
                        <line x1="8" y1="2" x2="8" y2="5" stroke-linecap="round" />
                        <line x1="3" y1="9.5" x2="21" y2="9.5" />
                    </svg>
                    <span><?= esc_html($ngay_dang_display); ?></span>
                <?php endif; ?>
            </div>
            <a href="<?= get_permalink($post_id); ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-light text-primary hover:bg-primary hover:text-white text-xs font-semibold rounded-full transition-all duration-300 shadow-sm" tabindex="0" aria-label="Xem chi tiết <?= esc_attr($value->post_title); ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2.5 12C4.5 7.5 8 5 12 5s7.5 2.5 9.5 7c-2 4.5-5.5 7-9.5 7s-7.5-2.5-9.5-7z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <span>Chi tiết</span>
            </a>
        </div>
    </div>
</article>