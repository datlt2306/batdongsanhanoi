<?php

/**
 * Share menu template part
 * 
 * @package BatDongSan
 * 
 * @param array $args {
 *     @type string $prefix_class CSS prefix class (default: 'hang-chuyen-nhuong-detail' hoặc 'project-detail')
 *     @type string $button_id ID cho share button (optional)
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

$prefix_class = $args['prefix_class'] ?? 'hang-chuyen-nhuong-detail';
$button_id = $args['button_id'] ?? 'share-btn';
?>

<div class="<?= esc_attr($prefix_class); ?>__share">
    <button
        type="button"
        class="<?= esc_attr($prefix_class); ?>__share-button"
        aria-haspopup="true"
        aria-expanded="false"
        id="<?= esc_attr($button_id); ?>">
        <span class="<?= esc_attr($prefix_class); ?>__share-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                <polyline points="16 6 12 2 8 6"/>
                <line x1="12" y1="2" x2="12" y2="15"/>
            </svg>
        </span>
        <span class="<?= esc_attr($prefix_class); ?>__share-label">Chia sẻ</span>
    </button>
    <div class="<?= esc_attr($prefix_class); ?>__share-menu" role="menu" aria-hidden="true">
        <button type="button" class="<?= esc_attr($prefix_class); ?>__share-item" data-share="facebook" role="menuitem">
            <span class="<?= esc_attr($prefix_class); ?>__share-item-icon <?= esc_attr($prefix_class); ?>__share-item-icon--fb" aria-hidden="true">f</span>
            <span>Facebook</span>
        </button>
        <button type="button" class="<?= esc_attr($prefix_class); ?>__share-item" data-share="zalo" role="menuitem">
            <span class="<?= esc_attr($prefix_class); ?>__share-item-icon <?= esc_attr($prefix_class); ?>__share-item-icon--zalo" aria-hidden="true">Z</span>
            <span>Zalo</span>
        </button>
        <button type="button" class="<?= esc_attr($prefix_class); ?>__share-item" data-share="copy" role="menuitem">
            <span class="<?= esc_attr($prefix_class); ?>__share-item-icon flex items-center justify-center" aria-hidden="true">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
            </span>
            <span>Sao chép liên kết</span>
        </button>
    </div>
</div>