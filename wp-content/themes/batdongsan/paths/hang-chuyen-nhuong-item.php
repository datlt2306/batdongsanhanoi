<?php
/**
 * Legacy Listing Item Proxy
 * Forwards to paths/bat-dong-san-item.php for backward compatibility.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

include get_template_directory() . '/paths/bat-dong-san-item.php';