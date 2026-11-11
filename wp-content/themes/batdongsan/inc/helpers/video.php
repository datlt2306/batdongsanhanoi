<?php

/**
 * Video helpers
 * 
 * Quản lý nhận diện, chuẩn hóa và lấy danh sách video review thực tế
 * (TikTok, YouTube Shorts, YouTube) cho Dự án và Bất động sản riêng lẻ.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Nhận diện nền tảng video (YouTube, YouTube Shorts, TikTok, Vimeo)
 *
 * @param string $video_url URL của video
 * @return string 'youtube' | 'youtube_shorts' | 'tiktok' | 'vimeo' | 'video'
 */
function batdongsan_detect_video_platform(string $video_url): string {
    if (empty($video_url)) {
        return '';
    }
    if (strpos($video_url, 'tiktok.com') !== false) {
        return 'tiktok';
    }
    if (strpos($video_url, 'youtube.com/shorts') !== false) {
        return 'youtube_shorts';
    }
    if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
        return 'youtube';
    }
    if (strpos($video_url, 'vimeo.com') !== false) {
        return 'vimeo';
    }
    return 'video';
}

/**
 * Lấy thumbnail từ video URL (YouTube, YouTube Shorts hoặc Vimeo)
 * 
 * @param string $video_url URL của video
 * @return string URL của thumbnail hoặc empty string nếu không lấy được
 */
function batdongsan_get_video_thumbnail(string $video_url): string {
    if (empty($video_url)) {
        return '';
    }

    // YouTube hoặc YouTube Shorts
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches)) {
        $video_id = $matches[1];
        return "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
    }

    // Vimeo – trả về URL thumbnail trực tiếp
    if (preg_match('/vimeo\.com\/(?:.*\/)?(\d+)/', $video_url, $matches)) {
        $video_id = $matches[1];
        return "https://vumbnail.com/{$video_id}.jpg";
    }

    return '';
}

/**
 * Chuyển đổi video URL thành embed URL chuẩn player
 * 
 * @param string $video_url URL của video
 * @return string Embed URL hoặc URL gốc
 */
function batdongsan_get_video_embed_url(string $video_url): string {
    if (empty($video_url)) {
        return '';
    }

    // YouTube hoặc YouTube Shorts
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches)) {
        $video_id = $matches[1];
        return 'https://www.youtube.com/embed/' . $video_id;
    }

    // TikTok (Hỗ trợ định dạng player mới nhất của TikTok, tương thích iframe player/v1)
    if (preg_match('/(?:tiktok\.com\/.*?(?:video\/|v\/|embed\/|player\/v1\/))(\d+)/', $video_url, $matches)) {
        $video_id = $matches[1];
        return 'https://www.tiktok.com/player/v1/' . $video_id;
    }

    // Vimeo
    if (preg_match('/vimeo\.com\/(?:.*\/)?(\d+)/', $video_url, $matches)) {
        $video_id = $matches[1];
        return 'https://player.vimeo.com/video/' . $video_id;
    }

    return $video_url;
}

/**
 * Lấy danh sách video review thực tế cho Dự án (Project)
 *
 * @param int  $project_id      ID của dự án
 * @param bool $include_fallback Có sử dụng video fallback không nếu dự án chưa có video riêng (mặc định: false)
 * @return array Mảng các video đã chuẩn hóa (rỗng nếu chưa gắn video)
 */
function batdongsan_get_project_videos(int $project_id, bool $include_fallback = false): array {
    $videos = [];
    $default_fallbacks = [
        'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=700&h=1240&q=80',
        'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=700&h=1240&q=80',
        'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=700&h=1240&q=80',
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=700&h=1240&q=80',
    ];

    if (function_exists('get_field')) {
        // 1. Kiểm tra repeater `video_du_an`
        $custom_videos = get_field('video_du_an', $project_id);
        if (empty($custom_videos)) {
            $custom_videos = get_field('danh_sach_video_review', $project_id);
        }

        if (!empty($custom_videos) && is_array($custom_videos)) {
            foreach ($custom_videos as $idx => $vid) {
                if (empty($vid['video_url'])) {
                    continue;
                }

                $platform = !empty($vid['platform']) ? $vid['platform'] : batdongsan_detect_video_platform($vid['video_url']);
                $thumb = '';

                if (!empty($vid['thumbnail'])) {
                    $thumb = is_numeric($vid['thumbnail']) ? wp_get_attachment_image_url($vid['thumbnail'], 'large') : (string) $vid['thumbnail'];
                }
                if (empty($thumb)) {
                    $thumb = batdongsan_get_video_thumbnail($vid['video_url']);
                }
                if (empty($thumb) && has_post_thumbnail($project_id)) {
                    $thumb = get_the_post_thumbnail_url($project_id, 'large');
                }
                if (empty($thumb)) {
                    $thumb = $default_fallbacks[$idx % count($default_fallbacks)];
                }

                $embed = batdongsan_get_video_embed_url($vid['video_url']);
                $title = !empty($vid['title']) ? trim($vid['title']) : ('Review thực tế dự án ' . get_the_title($project_id));
                $badge = !empty($vid['badge']) ? trim($vid['badge']) : 'Review Thực Tế';

                $duration = !empty($vid['duration']) ? trim($vid['duration']) : '';
                if (empty($duration)) {
                    $duration = ($platform === 'tiktok' || $platform === 'youtube_shorts') ? '0:58' : '3:45';
                }

                $views = !empty($vid['views']) ? trim($vid['views']) : '';
                if (empty($views)) {
                    $seed = abs(crc32($vid['video_url'] . $project_id));
                    $k_views = number_format(2.4 + ($seed % 420) / 10, 1, '.', '');
                    $views = $k_views . 'K view';
                }

                $videos[] = [
                    'title'       => $title,
                    'platform'    => $platform,
                    'video_url'   => $vid['video_url'],
                    'embed_url'   => $embed,
                    'thumbnail'   => $thumb,
                    'badge'       => $badge,
                    'duration'    => $duration,
                    'views'       => $views,
                    'is_fallback' => false,
                ];
            }
        }

        // 2. Nếu chưa có repeater, kiểm tra field đơn lẻ `video_url`
        if (empty($videos)) {
            $single_video_url = get_field('video_url', $project_id);
            if (!empty($single_video_url)) {
                $platform = batdongsan_detect_video_platform($single_video_url);
                $thumb = get_the_post_thumbnail_url($project_id, 'large') ?: $default_fallbacks[0];
                $embed = batdongsan_get_video_embed_url($single_video_url);

                $videos[] = [
                    'title'       => 'Trực tiếp khảo sát & đánh giá dự án ' . get_the_title($project_id),
                    'platform'    => $platform,
                    'video_url'   => $single_video_url,
                    'embed_url'   => $embed,
                    'thumbnail'   => $thumb,
                    'badge'       => 'Trải Nghiệm Thực Tế',
                    'duration'    => ($platform === 'tiktok' || $platform === 'youtube_shorts') ? '1:02' : '4:15',
                    'views'       => '36.8K view',
                    'is_fallback' => false,
                ];
            }
        }
    }

    // 3. Fallback sang danh sách video review chuyên viên nếu chưa có video riêng
    if (empty($videos) && $include_fallback && function_exists('batdongsan_get_media_videos')) {
        $raw_fallbacks = batdongsan_get_media_videos();
        foreach ($raw_fallbacks as $fb) {
            $fb['is_fallback'] = true;
            $videos[] = $fb;
        }
    }

    return $videos;
}

/**
 * Lấy danh sách video review thực tế cho Bất Động Sản riêng lẻ (Property)
 *
 * @param int  $post_id          ID của bất động sản
 * @param bool $include_fallback Có sử dụng video fallback không nếu BĐS chưa có video riêng (mặc định: false)
 * @return array Mảng các video đã chuẩn hóa (rỗng nếu chưa gắn video)
 */
function batdongsan_get_property_videos(int $post_id, bool $include_fallback = false): array {
    $videos = [];
    $default_fallbacks = [
        'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=700&h=1240&q=80',
        'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=700&h=1240&q=80',
        'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=700&h=1240&q=80',
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=700&h=1240&q=80',
    ];

    if (function_exists('get_field')) {
        // 1. Kiểm tra repeater `video_bds_list` hoặc `danh_sach_video_review`
        $custom_videos = get_field('video_bds_list', $post_id);
        if (empty($custom_videos)) {
            $custom_videos = get_field('danh_sach_video_review', $post_id);
        }

        if (!empty($custom_videos) && is_array($custom_videos)) {
            foreach ($custom_videos as $idx => $vid) {
                if (empty($vid['video_url'])) {
                    continue;
                }

                $platform = !empty($vid['platform']) ? $vid['platform'] : batdongsan_detect_video_platform($vid['video_url']);
                $thumb = '';

                if (!empty($vid['thumbnail'])) {
                    $thumb = is_numeric($vid['thumbnail']) ? wp_get_attachment_image_url($vid['thumbnail'], 'large') : (string) $vid['thumbnail'];
                }
                if (empty($thumb)) {
                    $thumb = batdongsan_get_video_thumbnail($vid['video_url']);
                }
                if (empty($thumb) && has_post_thumbnail($post_id)) {
                    $thumb = get_the_post_thumbnail_url($post_id, 'large');
                }
                if (empty($thumb)) {
                    $thumb = $default_fallbacks[$idx % count($default_fallbacks)];
                }

                $embed = batdongsan_get_video_embed_url($vid['video_url']);
                $title = !empty($vid['title']) ? trim($vid['title']) : ('Review thực tế ' . get_the_title($post_id));
                $badge = !empty($vid['badge']) ? trim($vid['badge']) : 'Review Căn Hộ';

                $duration = !empty($vid['duration']) ? trim($vid['duration']) : '';
                if (empty($duration)) {
                    $duration = ($platform === 'tiktok' || $platform === 'youtube_shorts') ? '0:55' : '3:15';
                }

                $views = !empty($vid['views']) ? trim($vid['views']) : '';
                if (empty($views)) {
                    $seed = abs(crc32($vid['video_url'] . $post_id));
                    $k_views = number_format(1.9 + ($seed % 380) / 10, 1, '.', '');
                    $views = $k_views . 'K view';
                }

                $videos[] = [
                    'title'       => $title,
                    'platform'    => $platform,
                    'video_url'   => $vid['video_url'],
                    'embed_url'   => $embed,
                    'thumbnail'   => $thumb,
                    'badge'       => $badge,
                    'duration'    => $duration,
                    'views'       => $views,
                    'is_fallback' => false,
                ];
            }
        }

        // 2. Kiểm tra trường đơn `video_url` (cùng các trường đi kèm nếu có)
        if (empty($videos)) {
            $video_url = get_field('video_url', $post_id);
            if (!empty($video_url)) {
                $platform = batdongsan_detect_video_platform($video_url);

                $thumb = '';
                $custom_thumb = get_field('video_thumbnail', $post_id);
                if (!empty($custom_thumb)) {
                    $thumb = is_numeric($custom_thumb) ? wp_get_attachment_image_url($custom_thumb, 'large') : (string) $custom_thumb;
                }
                if (empty($thumb)) {
                    $thumb = batdongsan_get_video_thumbnail($video_url);
                }
                if (empty($thumb) && has_post_thumbnail($post_id)) {
                    $thumb = get_the_post_thumbnail_url($post_id, 'large');
                }
                if (empty($thumb)) {
                    $thumb = $default_fallbacks[0];
                }

                $embed = batdongsan_get_video_embed_url($video_url);
                $title = get_field('video_title', $post_id);
                if (empty($title)) {
                    $title = 'Review chi tiết ' . get_the_title($post_id);
                }

                $badge = get_field('video_badge', $post_id) ?: 'Review Thực Tế';
                $duration = get_field('video_duration', $post_id) ?: (($platform === 'tiktok' || $platform === 'youtube_shorts') ? '0:58' : '3:20');

                $views = get_field('video_views', $post_id);
                if (empty($views)) {
                    $seed = abs(crc32($video_url . $post_id));
                    $k_views = number_format(2.1 + ($seed % 310) / 10, 1, '.', '');
                    $views = $k_views . 'K view';
                }

                $videos[] = [
                    'title'       => $title,
                    'platform'    => $platform,
                    'video_url'   => $video_url,
                    'embed_url'   => $embed,
                    'thumbnail'   => $thumb,
                    'badge'       => $badge,
                    'duration'    => $duration,
                    'views'       => $views,
                    'is_fallback' => false,
                ];
            }
        }

        // 3. Nếu căn này chưa có video riêng, thử lấy video từ Dự án liên quan
        if (empty($videos)) {
            $related_project_id = function_exists('batdongsan_get_related_project_id')
                ? batdongsan_get_related_project_id($post_id)
                : (int) get_post_meta($post_id, 'du_an_lien_quan', true);

            if ($related_project_id > 0) {
                $project_videos = batdongsan_get_project_videos($related_project_id, false);
                if (!empty($project_videos)) {
                    foreach ($project_videos as $pv) {
                        $pv['badge'] = 'Dự Án ' . get_the_title($related_project_id);
                        $pv['is_fallback'] = false;
                        $videos[] = $pv;
                    }
                }
            }
        }
    }

    // 4. Fallback sang danh sách video review chuyên viên nếu chưa có video
    if (empty($videos) && $include_fallback && function_exists('batdongsan_get_media_videos')) {
        $raw_fallbacks = batdongsan_get_media_videos();
        foreach ($raw_fallbacks as $fb) {
            $fb['is_fallback'] = true;
            $videos[] = $fb;
        }
    }

    return $videos;
}
