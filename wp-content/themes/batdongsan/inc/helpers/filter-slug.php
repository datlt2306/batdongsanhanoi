<?php
/**
 * Helper chuyển đổi giữa filter key (price/area) và slug URL.
 * URL: /bat-dong-san/gia-tu-30-ty-den-40-ty/ hoặc /bat-dong-san/dt-tu-100m2-den-150m2/
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Chuyển price key sang slug URL.
 * Ví dụ: '30000-40000' -> 'gia-tu-30-ty-den-40-ty', '0-500' -> 'gia-tu-0-trieu-den-500-trieu'
 *
 * @param string $price_key Key từ PriceDisplay
 * @return string Slug dạng gia-tu-X-den-Y
 */
function batdongsan_price_key_to_slug(string $price_key): string {
    if ($price_key === '0' || $price_key === '') {
        return '';
    }
    if ($price_key === 'thoa_thuan') {
        return 'gia-thoa-thuan';
    }

    $format_num = function (float $n): string {
        return (fmod($n, 1.0) === 0.0)
            ? (string) (int) $n
            : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    };

    if (strpos($price_key, '-') === false) {
        $val = (float) $price_key;
        if ($val >= 1000) {
            return 'gia-tren-' . $format_num($val / 1000) . '-ty';
        }
        return 'gia-tren-' . $format_num($val) . '-trieu';
    }

    $parts = explode('-', $price_key, 2);
    $min   = (float) $parts[0];
    $max   = (float) $parts[1];

    if ($min === 0.0 && $max === 0.0) {
        return '';
    }

    if ($min === 0.0) {
        if ($max >= 1000) {
            return 'gia-duoi-' . $format_num($max / 1000) . '-ty';
        }
        return 'gia-duoi-' . $format_num($max) . '-trieu';
    }

    if ($max === 0.0) {
        if ($min >= 1000) {
            return 'gia-tren-' . $format_num($min / 1000) . '-ty';
        }
        return 'gia-tren-' . $format_num($min) . '-trieu';
    }

    if ($min >= 1000 && $max >= 1000) {
        return 'gia-tu-' . $format_num($min / 1000) . '-ty-den-' . $format_num($max / 1000) . '-ty';
    }

    if ($min < 1000 && $max >= 1000) {
        return 'gia-tu-' . $format_num($min) . '-trieu-den-' . $format_num($max / 1000) . '-ty';
    }

    return 'gia-tu-' . $format_num($min) . '-trieu-den-' . $format_num($max) . '-trieu';
}

/**
 * Chuyển slug URL về price key.
 *
 * @param string $slug Slug dạng gia-tu-X-den-Y
 * @return string|null Price key hoặc null
 */
function batdongsan_slug_to_price_key(string $slug): ?string {
    if ($slug === '' || $slug === 'gia-thoa-thuan') {
        return $slug === 'gia-thoa-thuan' ? 'thoa_thuan' : null;
    }

    $format_out = function (float $n): string {
        return (fmod($n, 1.0) === 0.0)
            ? (string) (int) $n
            : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    };

    $parse_num = function (string $raw): float {
        // Hỗ trợ cả 1.5 lẫn 1-5 (slug phân tách thập phân bằng gạch nối)
        if (strpos($raw, '.') !== false) {
            return (float) $raw;
        }
        if (preg_match('/^(\d+)-(\d+)$/', $raw, $m)) {
            return (float) ($m[1] . '.' . $m[2]);
        }
        return (float) $raw;
    };

    if (preg_match('/^gia-duoi-([\d\.\-]+)-ty$/', $slug, $m)) {
        $val = $parse_num($m[1]) * 1000;
        return '0-' . $format_out($val);
    }

    if (preg_match('/^gia-duoi-([\d\.\-]+)-trieu$/', $slug, $m)) {
        $val = $parse_num($m[1]);
        return '0-' . $format_out($val);
    }

    if (preg_match('/^gia-tren-([\d\.\-]+)-ty$/', $slug, $m)) {
        $val = $parse_num($m[1]) * 1000;
        return $format_out($val);
    }

    if (preg_match('/^gia-tren-([\d\.\-]+)-trieu$/', $slug, $m)) {
        $val = $parse_num($m[1]);
        return $format_out($val);
    }

    if (preg_match('/^gia-tu-([\d\.\-]+)-ty-den-([\d\.\-]+)-ty$/', $slug, $m)) {
        $min = $parse_num($m[1]) * 1000;
        $max = $parse_num($m[2]) * 1000;
        return $format_out($min) . '-' . $format_out($max);
    }

    if (preg_match('/^gia-tu-([\d\.\-]+)-trieu-den-([\d\.\-]+)-ty$/', $slug, $m)) {
        $min = $parse_num($m[1]);
        $max = $parse_num($m[2]) * 1000;
        return $format_out($min) . '-' . $format_out($max);
    }

    if (preg_match('/^gia-tu-([\d\.\-]+)-trieu-den-([\d\.\-]+)-trieu$/', $slug, $m)) {
        $min = $parse_num($m[1]);
        $max = $parse_num($m[2]);
        return $format_out($min) . '-' . $format_out($max);
    }

    return null;
}

/**
 * Chuyển price key dự án (tr/m2) sang slug URL.
 * Ví dụ: '10-20' -> 'gia-tu-10-trieu-m2-den-20-trieu-m2'
 */
function batdongsan_du_an_price_key_to_slug(string $price_key): string {
    if ($price_key === '0' || $price_key === '') {
        return '';
    }
    if ($price_key === 'thoa_thuan') {
        return 'gia-thoa-thuan';
    }
    if (strpos($price_key, '-') === false) {
        $val = (int) $price_key;
        return 'gia-tren-' . $val . '-trieu-m2';
    }
    $parts = explode('-', $price_key, 2);
    $min   = (float) $parts[0];
    $max   = (float) $parts[1];
    if ($min === 0.0) {
        return 'gia-duoi-' . (int) $max . '-trieu-m2';
    }
    return 'gia-tu-' . (int) $min . '-trieu-m2-den-' . (int) $max . '-trieu-m2';
}

/**
 * Chuyển slug giá dự án về price key.
 */
function batdongsan_du_an_slug_to_price_key(string $slug): ?string {
    if ($slug === '' || $slug === 'gia-thoa-thuan') {
        return $slug === 'gia-thoa-thuan' ? 'thoa_thuan' : null;
    }
    if (preg_match('/^gia-duoi-(\d+)-trieu-m2$/', $slug, $m)) {
        return '0-' . (int) $m[1];
    }
    if (preg_match('/^gia-tren-(\d+)-trieu-m2$/', $slug, $m)) {
        return (string) (int) $m[1];
    }
    if (preg_match('/^gia-tu-(\d+)-trieu-m2-den-(\d+)-trieu-m2$/', $slug, $m)) {
        return (int) $m[1] . '-' . (int) $m[2];
    }
    return null;
}

/**
 * Chuyển area key sang slug URL.
 * Ví dụ: '100-150' -> 'dt-tu-100m2-den-150m2', '0-50' -> 'dt-duoi-50m2'
 *
 * @param string $area_key Key từ AreaDisplay
 * @return string Slug dạng dt-tu-Xm2-den-Ym2
 */
function batdongsan_area_key_to_slug(string $area_key): string {
    if ($area_key === '0' || $area_key === '') {
        return '';
    }
    if (strpos($area_key, '-') === false) {
        return 'dt-tren-' . (int) $area_key . 'm2';
    }
    $parts = explode('-', $area_key, 2);
    $min   = (int) $parts[0];
    $max   = (int) $parts[1];
    if ($min === 0 && $max === 0) {
        return '';
    }
    if ($min === 0) {
        return 'dt-duoi-' . $max . 'm2';
    }
    if ($max === 0) {
        return 'dt-tren-' . $min . 'm2';
    }
    return 'dt-tu-' . $min . 'm2-den-' . $max . 'm2';
}

/**
 * Chuyển slug URL về area key.
 *
 * @param string $slug Slug dạng dt-tu-Xm2-den-Ym2
 * @return string|null Area key hoặc null
 */
function batdongsan_slug_to_area_key(string $slug): ?string {
    if ($slug === '') {
        return null;
    }
    if (preg_match('/^dt-duoi-(\d+)m2$/', $slug, $m)) {
        return '0-' . (int) $m[1];
    }
    if (preg_match('/^dt-tren-(\d+)m2$/', $slug, $m)) {
        return (string) (int) $m[1];
    }
    if (preg_match('/^dt-tu-(\d+)m2-den-(\d+)m2$/', $slug, $m)) {
        return (int) $m[1] . '-' . (int) $m[2];
    }
    return null;
}

/**
 * Parse full filter slug.
 * Hỗ trợ: gia-tu-30-ty-den-40-ty, dt-tu-100m2-den-150m2, hoặc kết hợp.
 *
 * @param string $slug
 * @return array{price: string|null, area: string|null}
 */
function batdongsan_parse_filter_slug(string $slug): array {
    $slug = trim($slug, '/');
    if ($slug === '') {
        return ['price' => null, 'area' => null];
    }
    $price = null;
    $area  = null;
    if (strpos($slug, '-dt-') !== false) {
        $parts = preg_split('/-(?=dt-(?:tu|duoi|tren))/', $slug, 2);
        if (count($parts) >= 2) {
            $price = batdongsan_slug_to_price_key($parts[0]);
            $area  = batdongsan_slug_to_area_key($parts[1]);
        }
    } elseif (strpos($slug, '-gia-') !== false) {
        $parts = preg_split('/-(?=gia-(?:tu|duoi|tren|thoa))/', $slug, 2);
        if (count($parts) >= 2) {
            $area  = batdongsan_slug_to_area_key($parts[0]);
            $price = batdongsan_slug_to_price_key($parts[1]);
        }
    } elseif (strpos($slug, 'gia-') === 0) {
        $price = batdongsan_slug_to_price_key($slug);
    } elseif (strpos($slug, 'dt-') === 0) {
        $area = batdongsan_slug_to_area_key($slug);
    }
    return ['price' => $price, 'area' => $area];
}

/**
 * Build slug từ price và area key (hỗ trợ cả 2 cùng lúc).
 * Ví dụ: gia-tu-30-ty-den-40-ty-dt-tu-100m2-den-150m2
 *
 * @param string|null $price_key
 * @param string|null $area_key
 * @return string Slug
 */
function batdongsan_build_filter_slug(?string $price_key, ?string $area_key): string {
    $parts = [];
    if ($price_key && $price_key !== '0') {
        $parts[] = batdongsan_price_key_to_slug($price_key);
    }
    if ($area_key && $area_key !== '0') {
        $parts[] = batdongsan_area_key_to_slug($area_key);
    }
    return implode('-', $parts);
}

/**
 * Parse slug ban-{type}-dien-tich-xxx-gia-xxx hoặc ban-{type}-gia-xxx hoặc ban-{type}.
 *
 * @return array{type_slug: string|null, price: string|null, area: string|null}
 */
function batdongsan_parse_ban_slug(string $slug): array {
    $slug = trim($slug, '/');
    $type_slug = null;
    $price = null;
    $area = null;
    if ($slug === '') {
        return ['type_slug' => null, 'price' => null, 'area' => null];
    }

    // Hỗ trợ cả dạng đầy đủ "ban-biet-thu-lien-ke-..." và dạng rút gọn "biet-thu-lien-ke-..."
    if (strpos($slug, 'ban-') === 0) {
        $slug = substr($slug, 4);
    }

    if ($slug === '') {
        return ['type_slug' => null, 'price' => null, 'area' => null];
    }
    if (preg_match('/-(gia-(?:tu|duoi|tren|thoa)[a-z0-9-]+)$/', $slug, $m)) {
        $price = batdongsan_slug_to_price_key($m[1]);
        $slug = substr($slug, 0, -strlen($m[1]) - 1);
    }
    if (preg_match('/-(dien-tich-(?:tu|duoi|tren)[a-z0-9-]+)$/', $slug, $m)) {
        $area = batdongsan_dien_tich_slug_to_area_key($m[1]);
        $slug = substr($slug, 0, -strlen($m[1]) - 1);
    }
    $type_slug = $slug !== '' ? $slug : null;
    return ['type_slug' => $type_slug, 'price' => $price, 'area' => $area];
}

/**
 * Chuyển area key sang slug dien-tich-xxx.
 */
function batdongsan_area_key_to_dien_tich_slug(string $area_key): string {
    $base = batdongsan_area_key_to_slug($area_key);
    if ($base === '') {
        return '';
    }
    return str_replace('dt-', 'dien-tich-', $base);
}

/**
 * Chuyển slug dien-tich-xxx về area key.
 */
function batdongsan_dien_tich_slug_to_area_key(string $slug): ?string {
    $slug = str_replace('dien-tich-', 'dt-', $slug);
    return batdongsan_slug_to_area_key($slug);
}

/**
 * Build slug cho ban- URL: ban-{type} hoặc ban-{type}-gia-xxx hoặc ban-{type}-dien-tich-xxx-gia-xxx
 */
function batdongsan_build_ban_slug(string $type_slug, ?string $price_key, ?string $area_key): string {
    $parts = ['ban-' . $type_slug];
    if ($area_key && $area_key !== '0') {
        $parts[] = batdongsan_area_key_to_dien_tich_slug($area_key);
    }
    if ($price_key && $price_key !== '0') {
        $parts[] = batdongsan_price_key_to_slug($price_key);
    }
    return implode('-', $parts);
}

/**
 * Base URL khi lọc theo dự án: /bat-dong-san/du-an-{slug}/
 *
 * @param int $du_an_id ID dự án
 * @return string
 */
function batdongsan_bds_du_an_base_url(int $du_an_id): string {
    $slug = get_post_field('post_name', $du_an_id);
    if (!$slug) {
        return home_url('/bat-dong-san/');
    }
    return home_url('/bat-dong-san/du-an-' . $slug . '/');
}

/**
 * Build URL đầy đủ cho trang BĐS.
 * - Khu vực: bat-dong-san-quan-xxx
 * - Khu vực + loại hình: {type}-quan-{khu_vuc}, {type}-quan-{khu_vuc}/gia-xxx, {type}-quan-{khu_vuc}/gia-xxx-dt-xxx
 * - Chỉ loại hình: ban-can-ho-chung-cu, ban-{type}-gia-xxx, ban-{type}-dien-tich-xxx-gia-xxx
 * - Chỉ giá/diện tích: bat-dong-san/gia-...
 *
 * @param string       $base_url  URL gốc /bat-dong-san/
 * @param string|null  $price_key
 * @param string|null  $area_key
 * @param array        $query_args (du_an, project-name, type, khu_vuc)
 * @param int          $paged     Số trang
 * @return string
 */
function batdongsan_bds_filter_url(string $base_url, ?string $price_key, ?string $area_key, array $query_args = [], int $paged = 1): string {
    $type    = $query_args['type'] ?? '';
    $khu_vuc = $query_args['khu_vuc'] ?? '';
    $du_an   = isset($query_args['du_an']) ? absint($query_args['du_an']) : 0;
    unset($query_args['type'], $query_args['khu_vuc'], $query_args['du_an']);

    if ($du_an > 0) {
        $base = batdongsan_bds_du_an_base_url($du_an);
        $filter_slug = batdongsan_build_filter_slug($price_key, $area_key);
        $url = $filter_slug ? rtrim($base, '/') . '/' . $filter_slug . '/' : $base;
        if ($paged > 1) {
            $path = $filter_slug ? rtrim($base, '/') . '/' . $filter_slug : rtrim($base, '/');
            $url = $path . '/page/' . $paged . '/';
        }
        $query_args = array_filter($query_args, fn($v) => $v !== null && $v !== '' && $v !== '0');
        if (!empty($query_args)) {
            $url = add_query_arg($query_args, $url);
        }
        return $url;
    }
    $type_slug = null;
    if ($type && $type !== '0') {
        if (is_numeric($type)) {
            $type_slug = function_exists('batdongsan_loai_hinh_id_to_slug') ? batdongsan_loai_hinh_id_to_slug((int) $type) : '';
        } else {
            $type_slug = sanitize_title((string) $type);
        }
    }
    $base = rtrim($base_url, '/');
    if (!empty($type_slug) && !empty($khu_vuc)) {
        $path = $type_slug . '-quan-' . $khu_vuc;
        $filter_slug = batdongsan_build_filter_slug($price_key, $area_key);
        $url = home_url('/' . $path . ($filter_slug ? '/' . $filter_slug : '') . '/');
        if ($paged > 1) {
            $url = home_url('/' . $path . ($filter_slug ? '/' . $filter_slug : '') . '/page/' . $paged . '/');
        }
        $query_args = array_filter($query_args, fn($v) => $v !== null && $v !== '' && $v !== '0');
        if (!empty($query_args)) {
            $url = add_query_arg($query_args, $url);
        }
        return $url;
    }
    if (!empty($khu_vuc) && empty($type_slug)) {
        $base = home_url('/bat-dong-san-quan-' . $khu_vuc);
    } elseif (!empty($type_slug)) {
        $ban_slug = batdongsan_build_ban_slug($type_slug, $price_key, $area_key);
        $url = home_url('/' . $ban_slug . '/');
        if ($paged > 1) {
            $url = home_url('/' . $ban_slug . '/page/' . $paged . '/');
        }
        $query_args = array_filter($query_args, fn($v) => $v !== null && $v !== '' && $v !== '0');
        if (!empty($query_args)) {
            $url = add_query_arg($query_args, $url);
        }
        return $url;
    }
    $slug = batdongsan_build_filter_slug($price_key, $area_key);
    if ($slug) {
        $url = $base . '/' . $slug . '/';
    } else {
        $url = $base . '/';
    }
    if ($paged > 1) {
        $path = $slug ? $base . '/' . $slug : $base;
        $url  = $path . '/page/' . $paged . '/';
    }
    $query_args = array_filter($query_args, fn($v) => $v !== null && $v !== '' && $v !== '0');
    if (!empty($query_args)) {
        $url = add_query_arg($query_args, $url);
    }
    return $url;
}
