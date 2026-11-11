<?php
/**
 * Template name: Page - Hàng chuyển nhượng (Legacy)
 *
 * Tự động chuyển hướng về trang Bất động sản với bộ lọc Chuyển nhượng
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$target = add_query_arg(['hinh_thuc' => 'chuyen-nhuong'], home_url('/bat-dong-san/'));
if (!empty($_SERVER['QUERY_STRING'])) {
    $target = (str_contains($target, '?') ? $target . '&' : $target . '?') . $_SERVER['QUERY_STRING'];
}
wp_safe_redirect($target, 301);
exit;
