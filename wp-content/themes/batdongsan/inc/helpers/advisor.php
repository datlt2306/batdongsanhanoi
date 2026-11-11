<?php

/**
 * Advisor helpers
 * 
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lấy thông tin advisor từ ACF options với fallback
 * 
 * @param array $fallback_data Dữ liệu fallback từ post meta
 * @return array Mảng thông tin advisor
 */
function batdongsan_get_advisor_data($fallback_data = []): array {
    $advisor_name = get_field('name', 'option') ?: ($fallback_data['lien_he_ten'] ?? 'Quỳnh Nguyễn');
    $advisor_title = get_field('advisor_title', 'option') ?: 'Chuyên Viên Tư Vấn & Định Giá BĐS';
    $advisor_avatar = get_field('avatar', 'option') ?: 0;
    $advisor_phone = get_field('phone', 'option') ?: ($fallback_data['lien_he_sdt'] ?? '0934.69.9191');
    $advisor_email = get_field('email', 'option') ?: ($fallback_data['lien_he_email'] ?? 'saleso@batdongsanhanoi.net.vn');
    $advisor_zalo = get_field('zalo', 'option') ?: '0934699191';
    $advisor_years = get_field('advisor_years', 'option') ?: '8+';
    $advisor_listing_count = get_field('advisor_listing_count', 'option') ?: '150+';
    $advisor_rating = get_field('advisor_rating', 'option') ?: '4.9/5';
    $advisor_deals = get_field('advisor_deals', 'option') ?: '98%';
    $advisor_bio = get_field('advisor_bio', 'option') ?: 'Hơn 8 năm kinh nghiệm chuyên sâu thị trường căn hộ và dự án cao cấp tại Hà Nội. Cam kết thông tin thật 100%, định giá chính xác và hỗ trợ pháp lý trọn vẹn.';
    $advisor_specialties = get_field('advisor_specialties', 'option') ?: 'Cầu Giấy • Nam Từ Liêm • Tây Hồ • Thanh Xuân';
    $advisor_tiktok_url = get_field('advisor_tiktok_url', 'option') ?: get_field('social_tiktok', 'option') ?: 'https://tiktok.com/@batdongsanhanoi';
    $advisor_tiktok_handle = get_field('advisor_tiktok_handle', 'option') ?: '@batdongsanhanoi';
    $advisor_youtube_url = get_field('advisor_youtube_url', 'option') ?: get_field('social_youtube', 'option') ?: 'https://youtube.com/@batdongsanhanoi';
    $advisor_youtube_name = get_field('advisor_youtube_name', 'option') ?: 'Bất Động Sản Hà Nội';
    $advisor_profile_url = get_field('advisor_profile_url', 'option') ?: '';
    $advisor_platform_name = get_field('advisor_platform_name', 'option') ?: 'Batdongsan.com.vn';

    return [
        'advisor_name'          => $advisor_name,
        'advisor_title'         => $advisor_title,
        'advisor_avatar'        => $advisor_avatar,
        'advisor_phone'         => $advisor_phone,
        'advisor_email'         => $advisor_email,
        'advisor_zalo'          => $advisor_zalo,
        'advisor_years'         => $advisor_years,
        'advisor_listing_count' => $advisor_listing_count,
        'advisor_rating'        => $advisor_rating,
        'advisor_deals'         => $advisor_deals,
        'advisor_bio'           => $advisor_bio,
        'advisor_specialties'   => $advisor_specialties,
        'advisor_tiktok_url'    => $advisor_tiktok_url,
        'advisor_tiktok_handle' => $advisor_tiktok_handle,
        'advisor_youtube_url'   => $advisor_youtube_url,
        'advisor_youtube_name'  => $advisor_youtube_name,
        'advisor_profile_url'   => $advisor_profile_url,
        'advisor_platform_name' => $advisor_platform_name,
    ];
}

/**
 * Lấy danh sách video truyền thông / review thực tế của chuyên viên
 * 
 * @return array
 */
function batdongsan_get_media_videos(): array {
    $videos = [];
    $default_fallbacks = [
        'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=700&h=1240&q=80',
        'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=700&h=1240&q=80',
        'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=700&h=1240&q=80',
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=700&h=1240&q=80',
    ];

    if (function_exists('get_field')) {
        $custom_videos = get_field('advisor_media_videos', 'option');
        if (!empty($custom_videos) && is_array($custom_videos)) {
            foreach ($custom_videos as $idx => $vid) {
                if (!empty($vid['video_url'])) {
                    $platform = !empty($vid['platform']) ? $vid['platform'] : (function_exists('batdongsan_detect_video_platform') ? batdongsan_detect_video_platform($vid['video_url']) : 'youtube');
                    $thumb = !empty($vid['thumbnail']) ? wp_get_attachment_image_url($vid['thumbnail'], 'large') : '';
                    if (empty($thumb) && function_exists('batdongsan_get_video_thumbnail')) {
                        $thumb = batdongsan_get_video_thumbnail($vid['video_url']);
                    }
                    if (empty($thumb)) {
                        $thumb = $default_fallbacks[$idx % count($default_fallbacks)];
                    }
                    $embed = function_exists('batdongsan_get_video_embed_url') ? batdongsan_get_video_embed_url($vid['video_url']) : $vid['video_url'];

                    $title = !empty($vid['title']) ? trim($vid['title']) : 'Review thực tế bất động sản & căn hộ Hà Nội';

                    $duration = !empty($vid['duration']) ? trim($vid['duration']) : '';
                    if (empty($duration)) {
                        $duration = ($platform === 'tiktok' || $platform === 'youtube_shorts') ? '0:58' : '3:45';
                    }

                    $views = !empty($vid['views']) ? trim($vid['views']) : '';
                    if (empty($views)) {
                        $seed = abs(crc32($vid['video_url']));
                        $k_views = number_format(1.8 + ($seed % 350) / 10, 1, '.', '');
                        $views = $k_views . 'K view';
                    }

                    $videos[] = [
                        'title'        => $title,
                        'platform'     => $platform,
                        'video_url'    => $vid['video_url'],
                        'embed_url'    => $embed,
                        'thumbnail'    => $thumb,
                        'badge'        => !empty($vid['badge']) ? $vid['badge'] : 'Review Thực Tế',
                        'duration'     => $duration,
                        'views'        => $views,
                    ];
                }
            }
        }
    }

    // Default curated fallback videos if none configured
    if (empty($videos)) {
        $videos = [
            [
                'title'        => 'Thực tế căn hộ 2PN Discovery Complex 302 Cầu Giấy bàn giao full nội thất',
                'platform'     => 'tiktok',
                'video_url'    => 'https://www.tiktok.com/@batdongsanhanoi',
                'embed_url'    => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'thumbnail'    => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=700&h=1240&q=80',
                'badge'        => 'Review Thực Tế',
                'duration'     => '1:05',
                'views'        => '48.2K view',
            ],
            [
                'title'        => 'Top 3 lưu ý sống còn khi kiểm tra sổ hồng căn hộ chung cư tránh rủi ro pháp lý',
                'platform'     => 'tiktok',
                'video_url'    => 'https://www.tiktok.com/@batdongsanhanoi',
                'embed_url'    => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'thumbnail'    => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=700&h=1240&q=80',
                'badge'        => 'Pháp Lý Sổ Đỏ',
                'duration'     => '0:58',
                'views'        => '64.5K view',
            ],
            [
                'title'        => 'Kinh nghiệm chọn khoảng tầng và hướng mát đón gió tại các tòa cao tầng Hà Nội',
                'platform'     => 'tiktok',
                'video_url'    => 'https://www.tiktok.com/@batdongsanhanoi',
                'embed_url'    => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'thumbnail'    => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=700&h=1240&q=80',
                'badge'        => 'Kinh Nghiệm Mua Nhà',
                'duration'     => '1:18',
                'views'        => '39.1K view',
            ],
            [
                'title'        => 'Phân tích quy hoạch ga Metro Nhổn - Cầu Giấy và tiềm năng tăng giá BĐS khu Tây',
                'platform'     => 'youtube',
                'video_url'    => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'embed_url'    => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'thumbnail'    => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=700&h=1240&q=80',
                'badge'        => 'Quy Hoạch & Tiềm Năng',
                'duration'     => '4:30',
                'views'        => '25.8K view',
            ],
        ];
    }

    return $videos;
}
