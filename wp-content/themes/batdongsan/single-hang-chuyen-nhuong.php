<?php
/**
 * Legacy Single Template Proxy
 * Forwards rendering to single-bat-dong-san.php for backward compatibility.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

include get_template_directory() . '/single-bat-dong-san.php';
