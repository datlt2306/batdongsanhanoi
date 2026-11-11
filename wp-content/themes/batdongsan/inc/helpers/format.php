<?php

/**
 * Format helpers
 * 
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Format ngày đăng với logic "Hôm nay", "Hôm qua"
 * 
 * @param string $ngay_dang Ngày đăng từ ACF field
 * @param int    $post_id   Post ID để fallback về post_date
 * @return string Ngày đã format
 */
function batdongsan_format_ngay_dang($ngay_dang = '', $post_id = 0): string {
    if ($ngay_dang) {
        $ngay_dang_display = date_i18n('d/m/Y', strtotime($ngay_dang));
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if ($ngay_dang == $today) {
            return 'Hôm nay';
        } elseif ($ngay_dang == $yesterday) {
            return 'Hôm qua';
        }

        return $ngay_dang_display;
    }

    if ($post_id) {
        $post_date = get_the_date('Y-m-d', $post_id);
        $today = date('Y-m-d');

        if ($post_date == $today) {
            return 'Hôm nay';
        }

        return get_the_date('d/m/Y', $post_id);
    }

    return '';
}

/**
 * Format ngày hết hạn
 * 
 * @param string $ngay_het_han Ngày hết hạn từ ACF field
 * @return string Ngày đã format
 */
function batdongsan_format_ngay_het_han($ngay_het_han = ''): string {
    if ($ngay_het_han) {
        return date_i18n('d/m/Y', strtotime($ngay_het_han));
    }

    return '';
}

/**
 * Lấy label cho pháp lý
 * 
 * @param string $phap_ly Giá trị pháp lý từ ACF
 * @return string Label hiển thị
 */
function batdongsan_get_phap_ly_label($phap_ly = ''): string {
    $labels = [
        'so_do' => 'Sổ đỏ',
        'so_hong' => 'Sổ hồng',
        'so_do_so_hong' => 'Sổ đỏ/ Sổ hồng',
        'dang_ky_quyen_su_dung_dat' => 'Đăng ký quyền sử dụng đất',
        'giay_chung_nhan' => 'Giấy chứng nhận',
        'khac' => 'Khác',
    ];

    return isset($labels[$phap_ly]) ? $labels[$phap_ly] : '';
}

/**
 * Lấy label cho hướng nhà
 * 
 * @param string $huong_nha Giá trị hướng nhà từ ACF
 * @return string Label hiển thị
 */
function batdongsan_get_huong_nha_label($huong_nha = ''): string {
    $labels = [
        'dong' => 'Đông',
        'tay' => 'Tây',
        'nam' => 'Nam',
        'bac' => 'Bắc',
        'dong_bac' => 'Đông Bắc',
        'dong_nam' => 'Đông Nam',
        'tay_bac' => 'Tây Bắc',
        'tay_nam' => 'Tây Nam',
    ];

    return isset($labels[$huong_nha]) ? $labels[$huong_nha] : '';
}

/**
 * Lấy label cho số mặt tiền
 * 
 * @param string $so_mat_tien Giá trị số mặt tiền từ ACF
 * @return string Label hiển thị
 */
function batdongsan_get_so_mat_tien_label($so_mat_tien = ''): string {
    $labels = [
        '1' => '1 mặt tiền',
        '2' => '2 mặt tiền',
        '3' => '3 mặt tiền',
        '4' => '4 mặt tiền trở lên',
    ];

    return isset($labels[$so_mat_tien]) ? $labels[$so_mat_tien] : '';
}

/**
 * Lấy label cho trạng thái
 * 
 * @param string $trang_thai Giá trị trạng thái từ ACF
 * @return string Label hiển thị
 */
function batdongsan_get_trang_thai_label($trang_thai = ''): string {
    $labels = [
        'dang_ban' => 'Đang bán',
        'da_ban' => 'Đã bán',
        'dang_cho_thue' => 'Đang cho thuê',
        'tam_dung' => 'Tạm dừng',
    ];

    return isset($labels[$trang_thai]) ? $labels[$trang_thai] : '';
}

/**
 * Tính giá trên m² từ khoảng giá và diện tích
 * 
 * @param string $khoang_gia Khoảng giá từ ACF
 * @param string $dien_tich  Diện tích từ ACF
 * @return string Giá trên m² đã format
 */
function batdongsan_calculate_gia_tren_met_vuong($khoang_gia = '', $dien_tich = ''): string {
    if (!$khoang_gia || !$dien_tich) {
        return '';
    }

    preg_match('/([\d,]+)/', str_replace(',', '.', $khoang_gia), $matches);
    if (empty($matches[1])) {
        return '';
    }

    $gia_number = floatval(str_replace(',', '.', $matches[1]));
    preg_match('/([\d,]+)/', $dien_tich, $area_matches);

    if (empty($area_matches[1])) {
        return '';
    }

    $area_number = floatval(str_replace(',', '.', $area_matches[1]));
    if ($area_number <= 0) {
        return '';
    }

    return '~' . number_format(($gia_number * 1000) / $area_number, 2, ',', '.') . ' triệu/m²';
}

/**
 * Chuẩn hóa chuỗi số (phân biệt dấu thập phân vs phân cách hàng nghìn).
 *
 * @param string $n Chuỗi số (vd: "4.2", "1.500", "1,5")
 * @return float
 */
function batdongsan_normalize_price_number(string $n): float {
    $n = trim($n);
    if (strpos($n, ',') !== false && strpos($n, '.') !== false) {
        // Cả hai: "1.500,50" -> dot=ngàn, phẩy=thập phân
        $n = str_replace('.', '', $n);
        $n = str_replace(',', '.', $n);
    } elseif (strpos($n, ',') !== false) {
        // Chỉ phẩy: "1,5" -> thập phân
        $n = str_replace(',', '.', $n);
    } elseif (strpos($n, '.') !== false) {
        if (preg_match('/^(\d+)\.(\d{1,2})$/u', $n, $m)) {
            // Một chấm, 1-2 chữ sau -> thập phân (4.2, 5.25)
            $n = $m[1] . '.' . $m[2];
        } else {
            // Phân cách hàng nghìn (1.500, 1.500.000)
            $n = str_replace('.', '', $n);
        }
    }
    return (float) $n;
}

/**
 * Parse khoảng giá / giá cho thuê thành số triệu VND.
 * Hỗ trợ: "500 - 800 triệu", "1,5 tỷ", "4.2 tỷ", "1 - 2 tỷ", "Thỏa thuận", "Liên hệ".
 *
 * @param string $text Text từ khoang_gia hoặc gia_cho_thue
 * @return float|null Giá trị triệu VND (lấy số nhỏ nhất nếu là khoảng), null nếu không parse được
 */
function batdongsan_parse_total_price_million(string $text = ''): ?float {
    $text = trim($text);
    if ($text === '') {
        return null;
    }
    $lower = mb_strtolower($text);
    if (preg_match('/th[oỏ][aả]\s*thu[aậ]n|thoa\s*thuan|liên hệ|lien he|call|call\s*zalo|\/(?:m²|m2|tháng|thang)/iu', $lower)) {
        return null;
    }

    // Helper: Parse a single price segment (e.g., "800 triệu", "1 tỷ", "1.5", "500")
    $parse_segment = function (string $seg, ?float $fallback_mult = null): ?float {
        $seg_clean = trim($seg);
        if ($seg_clean === '') {
            return null;
        }
        $seg_lower = mb_strtolower($seg_clean);
        $has_ty    = (bool) preg_match('/tỷ|ty|tỉ|billion/i', $seg_lower);
        $has_trieu = (bool) preg_match('/triệu|trieu|\btr\b|\bm\b/i', $seg_lower);

        $mult = 1.0;
        if ($has_ty) {
            $mult = 1000.0;
        } elseif ($has_trieu) {
            $mult = 1.0;
        } elseif ($fallback_mult !== null) {
            $mult = $fallback_mult;
        } else {
            $mult = 1.0;
        }

        if (preg_match('/([\d\.,]+)/u', $seg_clean, $m)) {
            $num = batdongsan_normalize_price_number($m[1]);
            if ($num > 0) {
                return (float) ($num * $mult);
            }
        }
        return null;
    };

    // Check if the text is a range: e.g. "800 triệu - 1 tỷ", "500 - 800 triệu", "3 - 5 tỷ", "1 tỷ đến 2 tỷ"
    if (preg_match('/^(.*?)(?:\s*[-–—]\s*|\s+(?:đến|to|tới)\s+)(.*)$/iu', $text, $parts)) {
        $part1 = trim($parts[1]);
        $part2 = trim($parts[2]);

        $part2_lower = mb_strtolower($part2);
        $part2_is_ty = (bool) preg_match('/tỷ|ty|tỉ|billion/i', $part2_lower);
        $part2_mult  = $part2_is_ty ? 1000.0 : 1.0;

        $part1_lower = mb_strtolower($part1);
        $part1_has_unit = (bool) preg_match('/tỷ|ty|tỉ|billion|triệu|trieu|\btr\b/i', $part1_lower);

        // If part1 lacks a unit, inherit unit from part2
        $part1_mult = $part1_has_unit ? null : $part2_mult;

        $val1 = $parse_segment($part1, $part1_mult);
        $val2 = $parse_segment($part2, $part2_mult);

        if ($val1 !== null && $val2 !== null) {
            return (float) min($val1, $val2);
        }
        if ($val1 !== null) {
            return $val1;
        }
        if ($val2 !== null) {
            return $val2;
        }
    }

    // Single segment parsing
    $is_ty = (bool) preg_match('/tỷ|ty|tỉ|billion/i', $lower);
    $mult = $is_ty ? 1000.0 : 1.0;

    if (preg_match_all('/([\d\.,]+)/u', $text, $matches)) {
        $numbers = array_map('batdongsan_normalize_price_number', $matches[1]);
        $numbers = array_filter($numbers, fn($v) => $v > 0);
        if (empty($numbers)) {
            return null;
        }
        $min_num = min($numbers);
        $min_val = $min_num * $mult;
        return $min_val > 0 ? (float) $min_val : null;
    }

    return null;
}

/**
 * Lấy tổng giá (triệu VND) từ BĐS.
 * Nguồn ưu tiên: khoang_gia → gia_cho_thue → giá dự án liên quan (du_an_lien_quan).
 *
 * @param int $post_id
 * @return float|null Giá triệu VND, null nếu Thỏa thuận / không xác định
 */
function batdongsan_get_total_price_million(int $post_id): ?float {
    static $cache = [];
    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }
    $khoang_gia   = get_field('khoang_gia', $post_id);
    $gia_cho_thue = get_field('gia_cho_thue', $post_id);
    $text         = is_string($khoang_gia) ? trim($khoang_gia) : '';
    if ($text === '' && is_string($gia_cho_thue)) {
        $text = trim($gia_cho_thue);
    }
    if ($text !== '') {
        $cache[$post_id] = batdongsan_parse_total_price_million($text);
        return $cache[$post_id];
    }

    // Fallback: lấy giá từ dự án liên quan (du-an price: số tỷ VNĐ)
    $du_an_raw = get_field('du_an_lien_quan', $post_id);
    if (empty($du_an_raw)) {
        $cache[$post_id] = null;
        return null;
    }
    $du_an   = is_array($du_an_raw) ? ($du_an_raw[0] ?? null) : $du_an_raw;
    $du_an_id = $du_an instanceof WP_Post ? $du_an->ID : (int) $du_an;
    if ($du_an_id <= 0) {
        return null;
    }

    $price_du_an = get_field('price', $du_an_id);
    $price_text  = is_string($price_du_an) ? trim($price_du_an) : '';
    if ($price_text === '') {
        $cache[$post_id] = null;
        return null;
    }
    // Giá dự án thường là số tỷ (vd: "35", "5.5"); nếu chưa có đơn vị thì coi là tỷ
    $lower = mb_strtolower($price_text);
    if (!preg_match('/tỷ|ty|tỉ|billion/i', $lower) && preg_match('/^[\d\.,\s\-]+$/u', $price_text)) {
        $price_text .= ' tỷ';
    }
    $cache[$post_id] = batdongsan_parse_total_price_million($price_text);
    return $cache[$post_id];
}

/**
 * Kiểm tra BĐS có giá "Thỏa thuận" (có text giá nhưng không có số cụ thể).
 *
 * @param int $post_id
 * @return bool
 */
function batdongsan_is_thoa_thuan_price(int $post_id): bool {
    $khoang_gia = get_field('khoang_gia', $post_id);
    $gia_cho_thue = get_field('gia_cho_thue', $post_id);
    $text = trim((string) $khoang_gia) . ' ' . trim((string) $gia_cho_thue);
    if ($text === ' ' || trim($text) === '') {
        return false;
    }
    $lower = mb_strtolower($text);
    return (bool) preg_match('/th[oỏ][aả]\s*thu[aậ]n|thoa\s*thuan|liên hệ|lien he|call|call\s*zalo/iu', $lower);
}

/**
 * Lấy giá trị số (triệu/m²) từ field giá trên m² hoặc từ khoảng giá + diện tích.
 *
 * Ưu tiên dùng ACF `gia_tren_met_vuong` (nếu đã lưu), fallback sang tính toán
 * từ `khoang_gia` và `dien_tich` bằng hàm batdongsan_calculate_gia_tren_met_vuong().
 *
 * @param int $post_id
 * @return float|null Giá theo triệu/m² hoặc null nếu không xác định được
 */
function batdongsan_get_price_per_m2_number(int $post_id): ?float {
    $gia_tren_met_vuong = get_field('gia_tren_met_vuong', $post_id);

    // 1. Cố gắng parse trực tiếp từ field gia_tren_met_vuong (ví dụ: "52,63 tr/m²" hoặc "~52,63 triệu/m²")
    if (is_string($gia_tren_met_vuong) && trim($gia_tren_met_vuong) !== '') {
        if (preg_match('/([\d\.,]+)/u', $gia_tren_met_vuong, $matches) && !empty($matches[1])) {
            $number = str_replace(['.', ','], ['', '.'], $matches[1]);
            $value  = (float) $number;
            if ($value > 0) {
                return $value;
            }
        }
    }

    // 2. Fallback: tính từ khoảng giá + diện tích rồi parse số
    $khoang_gia = get_field('khoang_gia', $post_id);
    $dien_tich  = get_field('dien_tich', $post_id);
    $calculated = batdongsan_calculate_gia_tren_met_vuong($khoang_gia, $dien_tich);

    if (is_string($calculated) && trim($calculated) !== '') {
        if (preg_match('/([\d\.,]+)/u', $calculated, $matches) && !empty($matches[1])) {
            $number = str_replace(['.', ','], ['', '.'], $matches[1]);
            $value  = (float) $number;
            if ($value > 0) {
                return $value;
            }
        }
    }

    return null;
}

/**
 * Lấy diện tích số (m²) từ field diện tích ACF.
 *
 * @param int $post_id
 * @return float|null Diện tích m² hoặc null
 */
function batdongsan_get_area_number(int $post_id): ?float {
    $dien_tich = get_field('dien_tich', $post_id);
    if ($dien_tich === null || $dien_tich === false || $dien_tich === '') {
        return null;
    }
    if (is_numeric($dien_tich)) {
        $val = (float) $dien_tich;
        return $val > 0 ? $val : null;
    }
    if (!is_string($dien_tich) || trim($dien_tich) === '') {
        return null;
    }
    if (preg_match('/([\d\.,]+)/u', $dien_tich, $matches) && !empty($matches[1])) {
        $value = batdongsan_normalize_price_number($matches[1]);
        return $value > 0 ? $value : null;
    }
    return null;
}

/**
 * Lọc danh sách bài BĐS theo khoảng diện tích (m²).
 *
 * @param WP_Post[] $posts
 * @param string    $area_range_key Ví dụ: "50-80", "80-100", "150"
 * @return WP_Post[]
 */
function batdongsan_filter_listings_by_area_range(array $posts, string $area_range_key): array {
    $area_range_key = trim($area_range_key);
    if ($area_range_key === '' || $area_range_key === '0') {
        return $posts;
    }

    $min = null;
    $max = null;

    if (strpos($area_range_key, '-') !== false) {
        [$min_str, $max_str] = array_map('trim', explode('-', $area_range_key, 2));
        $min = $min_str !== '' ? (float) $min_str : null;
        $max = $max_str !== '' ? (float) $max_str : null;
    } else {
        $min = (float) $area_range_key;
        $max = null;
    }

    // Treat 0 or negative bounds as unbound (open-ended)
    if ($min !== null && $min <= 0.0) {
        $min = null;
    }
    if ($max !== null && $max <= 0.0) {
        $max = null;
    }

    if ($min !== null && $max !== null && $min > $max) {
        return [];
    }

    $filtered = [];
    foreach ($posts as $post) {
        if (!($post instanceof WP_Post)) {
            continue;
        }
        $value = batdongsan_get_area_number($post->ID);
        if ($value === null) {
            continue;
        }
        if ($min !== null && $value < $min) {
            continue;
        }
        if ($max !== null && $value > $max) {
            continue;
        }
        $filtered[] = $post;
    }
    return $filtered;
}

/**
 * Lọc danh sách bài BĐS theo khoảng giá TỔNG (triệu/tỷ VND), không phải tr/m².
 *
 * @param WP_Post[] $posts
 * @param string    $price_range_key Ví dụ: "thoa_thuan", "0-500", "500-800", "1000-2000", "60000"
 * @return WP_Post[]
 */
function batdongsan_filter_listings_by_price_range(array $posts, string $price_range_key): array {
    $price_range_key = trim($price_range_key);
    if ($price_range_key === '' || $price_range_key === '0') {
        return $posts;
    }

    $filtered = [];

    if ($price_range_key === 'thoa_thuan') {
        foreach ($posts as $post) {
            if ($post instanceof WP_Post && batdongsan_is_thoa_thuan_price($post->ID)) {
                $filtered[] = $post;
            }
        }
        return $filtered;
    }

    $min = null;
    $max = null;

    if (strpos($price_range_key, '-') !== false) {
        [$min_str, $max_str] = array_map('trim', explode('-', $price_range_key, 2));
        $min = $min_str !== '' ? (float) $min_str : null;
        $max = $max_str !== '' ? (float) $max_str : null;
    } else {
        $min = (float) $price_range_key;
        $max = null;
    }

    // Treat 0 or negative bounds as unbound (open-ended)
    if ($min !== null && $min <= 0.0) {
        $min = null;
    }
    if ($max !== null && $max <= 0.0) {
        $max = null;
    }

    if ($min !== null && $max !== null && $min > $max) {
        return [];
    }

    foreach ($posts as $post) {
        if (!($post instanceof WP_Post)) {
            continue;
        }

        $value = batdongsan_get_total_price_million($post->ID);
        if ($value === null) {
            continue;
        }

        if ($min !== null && $value < $min) {
            continue;
        }

        if ($max !== null && $value > $max) {
            continue;
        }

        $filtered[] = $post;
    }

    return $filtered;
}

/**
 * Tính giá trên m² từ giá và diện tích (dự án)
 * 
 * @param string|float $price     Giá từ ACF
 * @param string       $dien_tich Diện tích từ ACF
 * @return string Giá trên m² đã format
 */
function batdongsan_calculate_price_per_m2($price = '', $dien_tich = ''): string {
    if (!$price || !$dien_tich) {
        return '';
    }

    $price_float = floatval($price);
    $dien_tich_arr = explode('-', $dien_tich);
    $dien_tich_min = floatval($dien_tich_arr[0]);

    if ($price_float > 0 && $dien_tich_min > 0) {
        return '~' . number_format($price_float / $dien_tich_min, 0) . ' triệu/m²';
    }

    return '';
}

/**
 * Parse tiện ích từ textarea thành mảng
 * 
 * @param string $tien_ich Textarea content từ ACF
 * @return array Mảng các tiện ích với icon và label
 */
function batdongsan_parse_tien_ich($tien_ich = ''): array {
    $items = [];

    if (!is_string($tien_ich) || trim($tien_ich) === '') {
        return $items;
    }

    $lines = preg_split('/\r\n|\r|\n/', $tien_ich);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        // Mặc định icon + nhãn
        $icon = '🏊';
        $label = $line;

        // Nếu có emoji + khoảng trắng + tên tiện ích, tách ra
        if (preg_match('/^(\X)\s+(.*)$/u', $line, $matches)) {
            $icon = $matches[1];
            $label = $matches[2];
        }

        $items[] = [
            'icon'  => $icon,
            'label' => $label,
        ];
    }

    return $items;
}

/**
 * Parse vị trí liên kết từ textarea thành mảng
    $items = [];

    if (!is_string($vi_tri_lien_ket) || trim($vi_tri_lien_ket) === '') {
        return $items;
    }

    $lines = preg_split('/\r\n|\r|\n/', $vi_tri_lien_ket);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $parts = array_map('trim', explode('|', $line));
        $name = $parts[0] ?? '';
        $distance = $parts[1] ?? '';

        if ($name === '' && $distance === '') {
            continue;
        }

        $items[] = [
            'name'     => $name,
            'distance' => $distance,
        ];
    }

    return $items;
}

/**
 * Tối ưu hiệu năng: Batch prime caches cho danh sách bài viết BĐS (attachments, gallery, dự án liên quan).
 * Giảm thiểu triệt để N+1 queries khi render card template.
 *
 * @param array $posts Danh sách WP_Post hoặc post IDs
 */
function batdongsan_prime_listings_cache(array $posts): void {
    if (empty($posts)) {
        return;
    }

    if (function_exists('update_post_caches')) {
        update_post_caches($posts, 'bat-dong-san', true, true);
    }

    $prime_ids = [];
    foreach ($posts as $p) {
        $post_id = is_object($p) ? $p->ID : (int) $p;
        if (!$post_id) {
            continue;
        }

        if (function_exists('get_post_thumbnail_id')) {
            $thumb_id = get_post_thumbnail_id($post_id);
            if ($thumb_id) {
                $prime_ids[] = (int) $thumb_id;
            }
        }

        $gal = get_post_meta($post_id, 'gallery_nha_dat', true);
        if (!empty($gal)) {
            if (is_array($gal)) {
                foreach ($gal as $item) {
                    $id = is_array($item) ? ($item['ID'] ?? 0) : (int) $item;
                    if ($id) {
                        $prime_ids[] = (int) $id;
                    }
                }
            } elseif (is_numeric($gal)) {
                $prime_ids[] = (int) $gal;
            }
        }

        $da = get_post_meta($post_id, 'du_an_lien_quan', true);
        if (!empty($da)) {
            $first = is_array($da) ? $da[0] : $da;
            $id = is_object($first) ? $first->ID : (int) $first;
            if ($id) {
                $prime_ids[] = (int) $id;
            }
        }
    }

    $unique_ids = array_unique(array_filter($prime_ids));
    if (!empty($unique_ids)) {
        if (function_exists('_prime_post_caches')) {
            _prime_post_caches($unique_ids, true, false);
        } elseif (function_exists('update_post_caches')) {
            $prime_posts = get_posts(['include' => $unique_ids, 'post_type' => 'any', 'posts_per_page' => count($unique_ids)]);
            if (!empty($prime_posts)) {
                update_post_caches($prime_posts, 'any', true, false);
            }
        }
    }
}
