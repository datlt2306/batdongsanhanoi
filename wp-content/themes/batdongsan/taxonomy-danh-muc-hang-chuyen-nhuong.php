<?php
/**
 * Legacy Taxonomy Template Proxy
 * Redirects to unified SEO rewrite URL /ban-{slug}/ or /bat-dong-san/.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$term = get_queried_object();
$target = ($term && !empty($term->slug))
    ? home_url('/ban-' . $term->slug . '/')
    : home_url('/bat-dong-san/');

if (!empty($_SERVER['QUERY_STRING'])) {
    $target = (str_contains($target, '?') ? $target . '&' : $target . '?') . $_SERVER['QUERY_STRING'];
}

wp_safe_redirect($target, 301);
exit;