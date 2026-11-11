<?php

/**
 * Cấu hình Website (Site Options Page)
 *
 * Menu quản lý cấu hình chung cho website: Hotline, Zalo, Email, Chuyên viên tư vấn,
 * Mạng xã hội, Chân trang và Mã nhúng Header/Footer.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Đăng ký trang Cấu hình Website cấp cao nhất (Top-level Menu, vị trí ngay dưới Bàn làm việc)
 */
add_action('acf/init', 'batdongsan_register_options_page');
function batdongsan_register_options_page(): void {
    if (!function_exists('acf_add_options_page')) {
        return;
    }

    acf_add_options_page([
        'page_title'      => 'Cấu hình Website',
        'menu_title'      => 'Cấu hình Website',
        'menu_slug'       => 'cau-hinh-website',
        'capability'      => 'manage_options',
        'position'        => 3, // Nằm ngay dưới Dashboard (Bảng điều khiển)
        'icon_url'        => 'dashicons-admin-generic',
        'redirect'        => false,
        'update_button'   => 'Lưu Cấu Hình',
        'updated_message' => 'Đã lưu cấu hình website thành công.',
    ]);
}

/**
 * Loại bỏ menu con "Cấu hình chung" cũ nằm trong menu Giao diện (themes.php)
 */
add_action('admin_menu', 'batdongsan_cleanup_legacy_appearance_submenus', 999);
function batdongsan_cleanup_legacy_appearance_submenus(): void {
    remove_submenu_page('themes.php', 'cau-hinh-chung');
}

/**
 * Helper lấy giá trị cấu hình website từ ACF Option với fallback
 *
 * @param string $key Tên trường trong Cấu hình Website
 * @param mixed $default Giá trị mặc định nếu trường chưa được thiết lập
 * @return mixed
 */
function batdongsan_get_option(string $key, $default = '') {
    if (function_exists('get_field')) {
        $val = get_field($key, 'option');
        if ($val !== null && $val !== '' && $val !== false) {
            return $val;
        }
    }
    return $default;
}

/**
 * Nhúng mã Header Scripts vào <head>
 */
add_action('wp_head', 'batdongsan_output_header_scripts', 100);
function batdongsan_output_header_scripts(): void {
    $scripts = batdongsan_get_option('header_scripts', '');
    if (!empty($scripts)) {
        echo $scripts . "\n";
    }
}

/**
 * Nhúng mã Footer Scripts trước </body>
 */
add_action('wp_footer', 'batdongsan_output_footer_scripts', 100);
function batdongsan_output_footer_scripts(): void {
    $scripts = batdongsan_get_option('footer_scripts', '');
    if (!empty($scripts)) {
        echo $scripts . "\n";
    }
}

/**
 * Khai báo Field Group bằng code cho trang Cấu hình Website
 */
add_action('acf/init', 'batdongsan_register_site_options_fields');
function batdongsan_register_site_options_fields(): void {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key'                   => 'group_site_options',
        'title'                 => 'Thiết lập Cấu hình Website',
        'fields'                => [
            // ============================================
            // TAB 1: LIÊN HỆ & HOTLINE
            // ============================================
            [
                'key'   => 'field_tab_contact',
                'label' => 'Liên Hệ & Hotline',
                'type'  => 'tab',
            ],
            [
                'key'           => 'field_site_hotline',
                'label'         => 'Hotline hiển thị',
                'name'          => 'site_hotline',
                'type'          => 'text',
                'instructions'  => 'Số điện thoại hiển thị trên header, footer và các trang (vd: 0934.69.9191)',
                'default_value' => '0934.69.9191',
                'placeholder'   => '0934.69.9191',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_site_hotline_raw',
                'label'         => 'Hotline số gọi (tel:)',
                'name'          => 'site_hotline_raw',
                'type'          => 'text',
                'instructions'  => 'Dãy số viết liền khi bấm gọi trực tiếp (vd: 0934699191)',
                'default_value' => '0934699191',
                'placeholder'   => '0934699191',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_site_zalo',
                'label'         => 'Số điện thoại Zalo',
                'name'          => 'site_zalo',
                'type'          => 'text',
                'instructions'  => 'Số điện thoại liên hệ qua Zalo (vd: 0934699191)',
                'default_value' => '0934699191',
                'placeholder'   => '0934699191',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_site_email',
                'label'         => 'Email liên hệ',
                'name'          => 'site_email',
                'type'          => 'email',
                'instructions'  => 'Địa chỉ email nhận thông tin liên hệ',
                'default_value' => 'saleso@batdongsanhanoi.net.vn',
                'placeholder'   => 'saleso@batdongsanhanoi.net.vn',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_site_address',
                'label'         => 'Địa chỉ văn phòng / Trụ sở',
                'name'          => 'site_address',
                'type'          => 'text',
                'instructions'  => 'Địa chỉ văn phòng giao dịch bất động sản',
                'default_value' => 'Discovery Complex, 302 Cầu Giấy, Dịch Vọng, Cầu Giấy, Hà Nội',
                'placeholder'   => 'Discovery Complex, 302 Cầu Giấy, Dịch Vọng, Cầu Giấy, Hà Nội',
            ],
            [
                'key'           => 'field_site_working_hours',
                'label'         => 'Giờ làm việc',
                'name'          => 'site_working_hours',
                'type'          => 'text',
                'instructions'  => 'Thời gian hỗ trợ và tiếp nhận khách hàng',
                'default_value' => '8:00 - 20:00 (Thứ 2 - Chủ Nhật)',
                'placeholder'   => '8:00 - 20:00 (Thứ 2 - Chủ Nhật)',
            ],

            // ============================================
            // TAB 2: CHUYÊN VIÊN TƯ VẤN & NHÂN HIỆU
            // ============================================
            [
                'key'   => 'field_tab_advisor',
                'label' => 'Chuyên Viên Tư Vấn',
                'type'  => 'tab',
            ],
            [
                'key'           => 'field_advisor_name',
                'label'         => 'Tên chuyên viên tư vấn',
                'name'          => 'name',
                'type'          => 'text',
                'instructions'  => 'Họ và tên chuyên viên hiển thị ở card môi giới, popup tư vấn',
                'default_value' => 'Quỳnh Nguyễn',
                'placeholder'   => 'Quỳnh Nguyễn',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_advisor_title',
                'label'         => 'Chức danh / Danh vị chuyên môn',
                'name'          => 'advisor_title',
                'type'          => 'text',
                'instructions'  => 'Chức danh hiển thị (vd: Chuyên Gia Tư Vấn & Định Giá BĐS Cao Cấp)',
                'default_value' => 'Chuyên Viên Tư Vấn & Định Giá BĐS Cao Cấp',
                'placeholder'   => 'Chuyên Viên Tư Vấn & Định Giá BĐS Cao Cấp',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_advisor_avatar',
                'label'         => 'Ảnh đại diện chuyên viên',
                'name'          => 'avatar',
                'type'          => 'image',
                'instructions'  => 'Ảnh chân dung chuyên viên (khuyến nghị tỉ lệ 1:1 vuông)',
                'return_format' => 'id',
                'preview_size'  => 'thumbnail',
                'library'       => 'all',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_advisor_phone',
                'label'         => 'Số điện thoại chuyên viên',
                'name'          => 'phone',
                'type'          => 'text',
                'instructions'  => 'Hotline riêng của chuyên viên (vd: 0934 69 9191)',
                'default_value' => '0934 69 9191',
                'placeholder'   => '0934 69 9191',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_advisor_zalo',
                'label'         => 'Zalo chuyên viên',
                'name'          => 'zalo',
                'type'          => 'text',
                'instructions'  => 'Số Zalo hoặc link https://zalo.me/... của chuyên viên',
                'default_value' => '0934699191',
                'placeholder'   => '0934699191',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_advisor_email',
                'label'         => 'Email chuyên viên',
                'name'          => 'email',
                'type'          => 'email',
                'instructions'  => 'Email nhận tư vấn của chuyên viên',
                'default_value' => 'saleso@batdongsanhanoi.net.vn',
                'placeholder'   => 'saleso@batdongsanhanoi.net.vn',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_advisor_years',
                'label'         => 'Số năm kinh nghiệm',
                'name'          => 'advisor_years',
                'type'          => 'text',
                'instructions'  => 'Kinh nghiệm tư vấn BĐS (vd: 8+ năm)',
                'default_value' => '8+',
                'placeholder'   => '8+',
                'wrapper'       => ['width' => '33.33'],
            ],
            [
                'key'           => 'field_advisor_listing_count',
                'label'         => 'Số giao dịch / Giỏ hàng phụ trách',
                'name'          => 'advisor_listing_count',
                'type'          => 'text',
                'instructions'  => 'Số giao dịch hoặc căn hộ đã hỗ trợ (vd: 150+)',
                'default_value' => '150+',
                'placeholder'   => '150+',
                'wrapper'       => ['width' => '33.33'],
            ],
            [
                'key'           => 'field_advisor_deals',
                'label'         => 'Tỷ lệ khách hàng hài lòng',
                'name'          => 'advisor_deals',
                'type'          => 'text',
                'instructions'  => 'Tỷ lệ hài lòng (vd: 99% Hài Lòng)',
                'default_value' => '99%',
                'placeholder'   => '99%',
                'wrapper'       => ['width' => '33.33'],
            ],
            [
                'key'           => 'field_advisor_specialties',
                'label'         => 'Khu vực thế mạnh & am hiểu chuyên sâu',
                'name'          => 'advisor_specialties',
                'type'          => 'text',
                'instructions'  => 'Các quận/khu vực phụ trách mạnh nhất (vd: Cầu Giấy • Nam Từ Liêm • Tây Hồ • Thanh Xuân)',
                'default_value' => 'Cầu Giấy • Nam Từ Liêm • Tây Hồ • Thanh Xuân',
                'placeholder'   => 'Cầu Giấy • Nam Từ Liêm • Tây Hồ • Thanh Xuân',
            ],
            [
                'key'           => 'field_advisor_bio',
                'label'         => 'Tôn chỉ làm việc / Cam kết của chuyên viên',
                'name'          => 'advisor_bio',
                'type'          => 'textarea',
                'rows'          => 3,
                'instructions'  => 'Lời cam kết chân thành và phương châm làm việc với khách hàng',
                'default_value' => 'Hơn 8 năm kinh nghiệm thực chiến tại thị trường Hà Nội. Cam kết thông tin thật 100%, định giá chính xác và đồng hành trọn vẹn từ lúc xem nhà đến khi sang tên sổ đỏ.',
                'placeholder'   => 'Hơn 8 năm kinh nghiệm thực chiến...',
            ],
            [
                'key'           => 'field_advisor_platform_name',
                'label'         => 'Tổ chức / Đơn vị chứng nhận',
                'name'          => 'advisor_platform_name',
                'type'          => 'text',
                'instructions'  => 'Tên nền tảng thẩm định uy tín (vd: Batdongsan.com.vn)',
                'default_value' => 'Batdongsan.com.vn',
                'placeholder'   => 'Batdongsan.com.vn',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_advisor_profile_url',
                'label'         => 'Link hồ sơ cá nhân / Chứng chỉ',
                'name'          => 'advisor_profile_url',
                'type'          => 'url',
                'instructions'  => 'Đường dẫn đến trang hồ sơ môi giới chuyên nghiệp (tùy chọn)',
                'placeholder'   => 'https://batdongsan.com.vn/...',
                'wrapper'       => ['width' => '50'],
            ],

            // ============================================
            // TAB 3: TRUYỀN THÔNG & VIDEO REVIEW (TIKTOK & YOUTUBE)
            // ============================================
            [
                'key'   => 'field_tab_media_videos',
                'label' => 'Truyền Thông & Video Review',
                'type'  => 'tab',
            ],
            [
                'key'           => 'field_advisor_tiktok_url',
                'label'         => 'Link Kênh TikTok chuyên gia',
                'name'          => 'advisor_tiktok_url',
                'type'          => 'url',
                'instructions'  => 'Link trang TikTok cá nhân hoặc công ty (vd: https://tiktok.com/@batdongsanhanoi)',
                'default_value' => 'https://tiktok.com/@batdongsanhanoi',
                'placeholder'   => 'https://tiktok.com/@batdongsanhanoi',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_advisor_tiktok_handle',
                'label'         => 'Tên hiển thị TikTok',
                'name'          => 'advisor_tiktok_handle',
                'type'          => 'text',
                'instructions'  => 'Handle hiển thị (vd: @quynhnguyen.bds)',
                'default_value' => '@batdongsanhanoi',
                'placeholder'   => '@batdongsanhanoi',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_advisor_youtube_url',
                'label'         => 'Link Kênh YouTube chuyên gia',
                'name'          => 'advisor_youtube_url',
                'type'          => 'url',
                'instructions'  => 'Link kênh YouTube review dự án (vd: https://youtube.com/@batdongsanhanoi)',
                'default_value' => 'https://youtube.com/@batdongsanhanoi',
                'placeholder'   => 'https://youtube.com/@batdongsanhanoi',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_advisor_youtube_name',
                'label'         => 'Tên Kênh YouTube',
                'name'          => 'advisor_youtube_name',
                'type'          => 'text',
                'instructions'  => 'Tên hiển thị kênh YouTube (vd: Bất Động Sản Hà Nội Official)',
                'default_value' => 'Bất Động Sản Hà Nội Official',
                'placeholder'   => 'Bất Động Sản Hà Nội Official',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_advisor_media_videos',
                'label'         => 'Danh sách Video Review Thực Tế & Kinh Nghiệm BĐS',
                'name'          => 'advisor_media_videos',
                'type'          => 'repeater',
                'instructions'  => 'Thêm các video review thực tế của Sales trên TikTok hoặc YouTube để hiển thị nổi bật trên Trang Chủ và trang chi tiết.',
                'layout'        => 'block',
                'button_label'  => '+ Thêm Video Review Mới',
                'sub_fields'    => [
                    [
                        'key'           => 'field_media_video_title',
                        'label'         => 'Tiêu đề Video',
                        'name'          => 'title',
                        'type'          => 'text',
                        'required'      => 0,
                        'instructions'  => 'Tùy chọn. Nếu để trống, hệ thống sẽ tự động gán tiêu đề phù hợp.',
                        'placeholder'   => 'Review thực tế căn hộ...',
                        'wrapper'       => ['width' => '60'],
                    ],
                    [
                        'key'           => 'field_media_video_platform',
                        'label'         => 'Nền tảng',
                        'name'          => 'platform',
                        'type'          => 'select',
                        'choices'       => [
                            'youtube'        => 'YouTube (Video ngang 16:9)',
                            'youtube_shorts' => 'YouTube Shorts (Video dọc 9:16)',
                            'tiktok'         => 'TikTok (Video dọc 9:16)',
                        ],
                        'default_value' => 'youtube',
                        'wrapper'       => ['width' => '40'],
                    ],
                    [
                        'key'           => 'field_media_video_url',
                        'label'         => 'Đường link Video (URL)',
                        'name'          => 'video_url',
                        'type'          => 'url',
                        'required'      => 1,
                        'instructions'  => 'Link xem video (YouTube hoặc TikTok)',
                        'placeholder'   => 'https://www.youtube.com/watch?v=... hoặc https://www.tiktok.com/@.../video/...',
                        'wrapper'       => ['width' => '60'],
                    ],
                    [
                        'key'           => 'field_media_video_badge',
                        'label'         => 'Nhãn / Tag phân loại',
                        'name'          => 'badge',
                        'type'          => 'text',
                        'instructions'  => 'vd: Review Dự Án, Kinh Nghiệm Mua Nhà, Pháp Lý BĐS',
                        'default_value' => 'Review Thực Tế',
                        'placeholder'   => 'Review Thực Tế',
                        'wrapper'       => ['width' => '40'],
                    ],
                    [
                        'key'           => 'field_media_video_thumbnail',
                        'label'         => 'Ảnh bìa Thumbnail (Tùy chọn)',
                        'name'          => 'thumbnail',
                        'type'          => 'image',
                        'return_format' => 'id',
                        'preview_size'  => 'thumbnail',
                        'instructions'  => 'Nếu để trống, hệ thống sẽ tự động lấy ảnh bìa từ YouTube.',
                        'wrapper'       => ['width' => '40'],
                    ],
                    [
                        'key'           => 'field_media_video_duration',
                        'label'         => 'Thời lượng (vd: 1:30)',
                        'name'          => 'duration',
                        'type'          => 'text',
                        'instructions'  => 'Tùy chọn. Để trống hệ thống sẽ tự động gán.',
                        'default_value' => '',
                        'placeholder'   => 'Tự động (vd: 0:58)',
                        'wrapper'       => ['width' => '30'],
                    ],
                    [
                        'key'           => 'field_media_video_views',
                        'label'         => 'Lượt xem hiển thị',
                        'name'          => 'views',
                        'type'          => 'text',
                        'instructions'  => 'Tùy chọn. Để trống hệ thống sẽ tự động tính lượt xem.',
                        'default_value' => '',
                        'placeholder'   => 'Tự động (vd: 18.5K view)',
                        'wrapper'       => ['width' => '30'],
                    ],
                ],
            ],

            // ============================================
            // TAB 4: MẠNG XÃ HỘI
            // ============================================
            [
                'key'   => 'field_tab_social',
                'label' => 'Mạng Xã Hội',
                'type'  => 'tab',
            ],
            [
                'key'           => 'field_social_facebook',
                'label'         => 'Facebook Fanpage',
                'name'          => 'social_facebook',
                'type'          => 'url',
                'instructions'  => 'Link trang Facebook của công ty / dự án',
                'placeholder'   => 'https://facebook.com/batdongsanhanoi',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_social_zalo',
                'label'         => 'Zalo Official / Cá nhân',
                'name'          => 'social_zalo',
                'type'          => 'url',
                'instructions'  => 'Link Zalo tư vấn (vd: https://zalo.me/0934699191)',
                'default_value' => 'https://zalo.me/0934699191',
                'placeholder'   => 'https://zalo.me/0934699191',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_social_youtube',
                'label'         => 'Kênh YouTube',
                'name'          => 'social_youtube',
                'type'          => 'url',
                'instructions'  => 'Link kênh YouTube review dự án',
                'placeholder'   => 'https://youtube.com/@batdongsanhanoi',
                'wrapper'       => ['width' => '50'],
            ],
            [
                'key'           => 'field_social_tiktok',
                'label'         => 'Kênh TikTok',
                'name'          => 'social_tiktok',
                'type'          => 'url',
                'instructions'  => 'Link kênh TikTok bất động sản',
                'placeholder'   => 'https://tiktok.com/@batdongsanhanoi',
                'wrapper'       => ['width' => '50'],
            ],

            // ============================================
            // TAB 4: CHÂN TRANG (FOOTER)
            // ============================================
            [
                'key'   => 'field_tab_footer',
                'label' => 'Chân Trang',
                'type'  => 'tab',
            ],
            [
                'key'           => 'field_footer_about',
                'label'         => 'Mô tả giới thiệu chân trang',
                'name'          => 'footer_about',
                'type'          => 'textarea',
                'rows'          => 3,
                'instructions'  => 'Đoạn văn ngắn giới thiệu về website ở cột 1 của Footer',
                'default_value' => 'Cổng thông tin & tư vấn bất động sản, dự án và nhà đất thổ cư tại Hà Nội.',
                'placeholder'   => 'Cổng thông tin & tư vấn bất động sản, dự án và nhà đất thổ cư tại Hà Nội.',
            ],
            [
                'key'           => 'field_footer_copyright',
                'label'         => 'Bản quyền chân trang (Copyright)',
                'name'          => 'footer_copyright',
                'type'          => 'text',
                'instructions'  => 'Dòng chữ bản quyền hiển thị dưới cùng của website',
                'default_value' => 'BatDongSanHaNoi. Tất cả quyền được bảo lưu.',
                'placeholder'   => 'BatDongSanHaNoi. Tất cả quyền được bảo lưu.',
            ],

            // ============================================
            // TAB 5: MÃ NHÚNG & GOOGLE API
            // ============================================
            [
                'key'   => 'field_tab_scripts',
                'label' => 'Mã Nhúng & Google API',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_google_maps_api_key',
                'label'        => 'Google Maps / Places API Key',
                'name'         => 'google_maps_api_key',
                'type'         => 'text',
                'instructions' => 'Nhập API Key từ Google Cloud Console (đã bật Places API và Geocoding API) để tự động quét 100% tiện ích thực tế xung quanh dự án.',
                'placeholder'  => 'AIzaSy...',
            ],
            [
                'key'          => 'field_header_scripts',
                'label'        => 'Mã nhúng Header (trong thẻ <head>)',
                'name'         => 'header_scripts',
                'type'         => 'textarea',
                'rows'         => 6,
                'instructions' => 'Dán mã Google Analytics, Google Tag Manager, Google Search Console meta tag...',
                'placeholder'  => "<!-- Google tag (gtag.js) -->\n<script async src=\"https://www.googletagmanager.com/gtag/js?id=...\"></script>",
            ],
            [
                'key'          => 'field_footer_scripts',
                'label'        => 'Mã nhúng Footer (trước thẻ đóng </body>)',
                'name'         => 'footer_scripts',
                'type'         => 'textarea',
                'rows'         => 6,
                'instructions' => 'Dán mã Facebook Pixel, Zalo Chat widget, Live Chat script...',
                'placeholder'  => "<!-- Chat Widget -->\n<script>...</script>",
            ],
        ],
        'location'              => [
            [
                [
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'cau-hinh-website',
                ],
            ],
        ],
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen'        => '',
        'active'                => true,
        'description'           => 'Cấu hình chung toàn bộ thông tin website bất động sản',
    ]);
}
