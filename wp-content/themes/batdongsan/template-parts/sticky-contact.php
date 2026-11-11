<?php

/**
 * Sticky contact bar template part (Mobile only)
 * 
 * @package BatDongSan
 * 
 * @param array $args {
 *     @type string $advisor_name Tên môi giới
 *     @type int    $advisor_avatar Avatar ID
 *     @type string $advisor_phone Số điện thoại
 *     @type string $advisor_zalo Zalo URL
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

$advisor_name = $args['advisor_name'] ?? '';
$advisor_avatar = $args['advisor_avatar'] ?? 0;
$advisor_phone = $args['advisor_phone'] ?? '';
$advisor_zalo = $args['advisor_zalo'] ?? '';
$mobile_only = $args['mobile_only'] ?? false;

if (!$advisor_phone && !$advisor_zalo) {
    return;
}

$bar_class = 'sticky-contact-bar';
if ($mobile_only) {
    $bar_class .= ' sticky-contact-bar--mobile-only';
}
?>

<div class="<?= esc_attr($bar_class); ?> fixed bottom-3 left-3 right-3 sm:left-auto sm:right-auto sm:w-full sm:max-w-md sm:left-1/2 sm:-translate-x-1/2 z-50 bg-white/85 backdrop-blur-2xl border border-black/[0.08] rounded-full p-1.5 shadow-[0_12px_40px_rgba(0,0,0,0.15)] pb-[calc(0.4rem+env(safe-area-inset-bottom,0px))] lg:hidden transition-all duration-300">
    <div class="flex items-center justify-between gap-1.5 w-full">
        <?php if ($advisor_avatar) : ?>
            <div class="w-9 h-9 rounded-full overflow-hidden shrink-0 border border-black/[0.08] shadow-sm ml-0.5">
                <?= wp_get_attachment_image($advisor_avatar, 'thumbnail', false, ['class' => 'w-full h-full object-cover', 'alt' => esc_attr($advisor_name ?: 'Chuyên viên tư vấn bất động sản')]); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($advisor_zalo) : ?>
            <a href="<?= esc_url($advisor_zalo); ?>" target="_blank" rel="noopener noreferrer" class="flex-1 h-10 flex items-center justify-center gap-1.5 px-3 bg-[#0068FF] text-white font-semibold text-xs rounded-full hover:bg-[#0058DD] active:scale-95 transition-all shadow-sm">
                <svg class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 2C6.48 2 2 6.03 2 11c0 2.87 1.5 5.43 3.84 7.04-.15.93-.65 2.8-1.84 4.01 0 0 2.45-.25 4.68-1.74.43.08.87.13 1.32.13 5.52 0 10-4.03 10-9s-4.48-9-10-9zm-1.8 12.5H7.2a.5.5 0 0 1-.41-.78l2.25-3.22H7.2a.5.5 0 1 1 0-1h3a.5.5 0 0 1 .41.78l-2.25 3.22h1.84a.5.5 0 0 1 0 1zm4.6 0h-1.4a.5.5 0 0 1-.5-.5V8.5a.5.5 0 0 1 .5-.5h1.4c1.27 0 2.3 1.03 2.3 2.3v1.9c0 1.27-1.03 2.3-2.3 2.3zm0-3.7h-.5v2.7h.5c.72 0 1.3-.58 1.3-1.3v-.1c0-.72-.58-1.3-1.3-1.3z"/>
                </svg>
                <span>Chat Zalo</span>
            </a>
        <?php endif; ?>
        
        <?php if ($advisor_phone) : ?>
            <a
                href="tel:<?= esc_attr(preg_replace('/\s+/', '', $advisor_phone)); ?>"
                class="flex-1 h-10 flex items-center justify-center gap-1.5 px-3 bg-primary text-white font-semibold text-xs rounded-full hover:bg-primary-hover active:scale-95 transition-all shadow-[0_2px_10px_rgba(201,51,71,0.3)]">
                <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
                <span>Gọi <?= esc_html($advisor_phone); ?></span>
            </a>
        <?php endif; ?>
    </div>
</div>
