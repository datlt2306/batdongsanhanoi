<?php
/**
 * Component Share Dropdown - Theo chuẩn thiết kế hình ảnh mẫu
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$share_title = !empty($args['title']) ? $args['title'] : get_the_title();
$share_heading = !empty($args['heading']) ? $args['heading'] : 'Chia sẻ dự án';
$button_class = !empty($args['button_class']) ? $args['button_class'] : '';
$menu_align = !empty($args['align']) ? $args['align'] : 'right'; // 'right' or 'left'
?>

<div class="project-detail__share relative inline-block">
    <button
        type="button"
        class="project-detail__share-button inline-flex items-center gap-1.5 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-full bg-white hover:bg-stone/60 border border-black/[0.12] hover:border-black/25 text-charcoal shadow-sm hover:shadow transition-all text-xs font-semibold cursor-pointer group shrink-0 <?php echo esc_attr($button_class); ?>"
        aria-haspopup="true"
        aria-expanded="false"
    >
        <svg class="w-3.5 h-3.5 text-primary group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 12v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-6"/>
            <polyline points="16 6 12 2 8 6"/>
            <line x1="12" y1="2" x2="12" y2="15"/>
        </svg>
        <span class="whitespace-nowrap font-medium text-charcoal">Chia sẻ</span>
    </button>

    <div class="project-detail__share-menu absolute <?php echo ($menu_align === 'left') ? 'left-0' : 'right-0'; ?> top-[calc(100%+8px)] w-[310px] sm:w-[350px] bg-white rounded-2xl shadow-[0_12px_40px_rgba(0,0,0,0.15)] border border-black/[0.08] p-3 sm:p-4 z-[999] opacity-0 invisible -translate-y-2 transition-all duration-200 pointer-events-none" role="menu" aria-hidden="true">
        <!-- Header -->
        <div class="px-1 pt-0.5 pb-2.5 border-b border-black/[0.06]">
            <h4 class="text-sm font-bold text-charcoal m-0 leading-tight">
                <?php echo esc_html($share_heading); ?>
            </h4>
            <p class="text-xs text-charcoal-muted mt-1 mb-0 truncate font-normal">
                <?php echo esc_html($share_title); ?>
            </p>
        </div>

        <!-- 3 Items -->
        <div class="pt-2 space-y-1">
            <!-- 1. Sao chép liên kết -->
            <button
                type="button"
                class="project-detail__share-item w-full flex items-center gap-3 p-2.5 rounded-xl hover:bg-black/[0.04] active:bg-black/[0.08] transition-colors text-left cursor-pointer border-0 bg-transparent group"
                data-share="copy"
                role="menuitem"
            >
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-blue-100/70 transition-all">
                    <svg class="w-5 h-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="project-detail__share-copy-title block text-sm font-bold text-charcoal leading-snug">
                        Sao chép liên kết
                    </span>
                    <span class="block text-xs text-charcoal-muted mt-0.5 leading-tight">
                        Copy URL gửi qua Zalo, Messenger
                    </span>
                </div>
            </button>

            <!-- 2. Chia sẻ Facebook -->
            <button
                type="button"
                class="project-detail__share-item w-full flex items-center gap-3 p-2.5 rounded-xl hover:bg-black/[0.04] active:bg-black/[0.08] transition-colors text-left cursor-pointer border-0 bg-transparent group"
                data-share="facebook"
                role="menuitem"
            >
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#1877f2] flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-blue-100/70 transition-all">
                    <svg class="w-5 h-5 fill-[#1877f2]" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block text-sm font-bold text-charcoal leading-snug">
                        Chia sẻ Facebook
                    </span>
                    <span class="block text-xs text-charcoal-muted mt-0.5 leading-tight">
                        Đăng lên trang cá nhân hoặc nhóm
                    </span>
                </div>
            </button>

            <!-- 3. Chia sẻ qua Zalo -->
            <button
                type="button"
                class="project-detail__share-item w-full flex items-center gap-3 p-2.5 rounded-xl hover:bg-black/[0.04] active:bg-black/[0.08] transition-colors text-left cursor-pointer border-0 bg-transparent group"
                data-share="zalo"
                role="menuitem"
            >
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0068ff] flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-blue-100/70 transition-all">
                    <span class="font-extrabold text-[13px] tracking-tight text-[#0068ff]">Zalo</span>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="block text-sm font-bold text-charcoal leading-snug">
                        Chia sẻ qua Zalo
                    </span>
                    <span class="block text-xs text-charcoal-muted mt-0.5 leading-tight">
                        Gửi trực tiếp cho bạn bè, nhóm Zalo
                    </span>
                </div>
            </button>
        </div>
    </div>
</div>
