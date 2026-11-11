<?php
/**
 * Bootstrap for BatDongSan Filter Engine.
 *
 * Registers class dependencies and initializes auto-sync service hooks.
 *
 * @package BatDongSan
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$filter_dir = __DIR__;

require_once $filter_dir . '/class-bds-abstract-query-builder.php';
require_once $filter_dir . '/class-bds-listing-query-builder.php';
require_once $filter_dir . '/class-bds-project-query-builder.php';
require_once $filter_dir . '/class-bds-filter-options-service.php';
require_once $filter_dir . '/class-bds-meta-sync-service.php';
require_once $filter_dir . '/class-bds-filter-query-service.php';

// Initialize auto-sync service hooks
BDS_Meta_Sync_Service::init();
