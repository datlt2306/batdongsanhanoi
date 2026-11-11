<?php
/**
 * Advisor Quick Card Template Part (Mobile Optimized)
 *
 * Hiển thị thẻ thông tin chuyên viên tư vấn / người môi giới tinh gọn dành riêng cho Mobile.
 * Giúp người dùng trên thiết bị di động tiếp cận ngay thông tin liên hệ mà không phải cuộn xuống đáy trang.
 *
 * @package BatDongSan
 *
 * @param array $args {
 *     @type string $advisor_name          Tên chuyên viên / cố vấn
 *     @type int    $advisor_avatar        Avatar ID
 *     @type string $advisor_phone         Số điện thoại hotline
 *     @type string $advisor_zalo          Zalo URL
 *     @type string $advisor_years         Số năm kinh nghiệm
 *     @type string $advisor_listing_count Số lượng giỏ hàng
 *     @type string $role_title            Chức danh (mặc định: 'Chuyên viên phụ trách')
 *     @type string $badge_label           Nhãn badge (mặc định: 'Xác minh')
 *     @type string $modal_cta_label       Nhãn nút mở modal (mặc định: 'Đặt lịch xem nhà / Nhận tư vấn chi tiết')
 *     @type string $container_class       Class tùy biến thêm cho container
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

$advisor_name          = !empty($args['advisor_name']) ? $args['advisor_name'] : 'Chuyên viên BĐS Hà Nội';
$advisor_avatar        = !empty($args['advisor_avatar']) ? (int) $args['advisor_avatar'] : 0;
$advisor_phone         = !empty($args['advisor_phone']) ? $args['advisor_phone'] : '';
$advisor_zalo          = !empty($args['advisor_zalo']) ? $args['advisor_zalo'] : '';
$advisor_years         = !empty($args['advisor_years']) ? $args['advisor_years'] : '';
$advisor_listing_count = !empty($args['advisor_listing_count']) ? $args['advisor_listing_count'] : '';
$role_title            = !empty($args['role_title']) ? $args['role_title'] : 'Chuyên viên phụ trách';
$badge_label           = !empty($args['badge_label']) ? $args['badge_label'] : 'Xác minh';
$modal_cta_label       = !empty($args['modal_cta_label']) ? $args['modal_cta_label'] : 'Đặt lịch xem nhà / Nhận tư vấn chi tiết';
$container_class       = !empty($args['container_class']) ? $args['container_class'] : '';

$sanitized_phone = preg_replace('/\D/', '', $advisor_phone);
if (empty($advisor_zalo) && !empty($sanitized_phone)) {
    $advisor_zalo = 'https://zalo.me/' . $sanitized_phone;
}
?>

<div class="advisor-quick-card lg:!hidden bg-surface rounded-2xl border border-border/80 p-4 sm:p-5 shadow-subtle mb-4 sm:mb-5 <?= esc_attr($container_class); ?>" aria-label="Thông tin người môi giới phụ trách">
    <!-- Header: Status & Trust Badge -->
    <div class="flex items-center justify-between pb-3 mb-3 border-b border-black/[0.06]">
        <div class="flex items-center gap-2">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <span class="text-[11px] font-sans font-semibold uppercase tracking-wider text-charcoal-muted">
                <?= esc_html($role_title); ?>
            </span>
        </div>
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200/80">
            <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12" />
            </svg>
            <span><?= esc_html($badge_label); ?></span>
        </span>
    </div>

    <!-- Broker Info Row: Avatar, Name, Trust Stats -->
    <div class="flex items-center gap-3.5 mb-3.5">
        <div class="relative shrink-0">
            <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-full overflow-hidden ring-2 ring-primary/20 shadow-sm bg-stone">
                <?php if ($advisor_avatar) : ?>
                    <?= wp_get_attachment_image($advisor_avatar, 'thumbnail', false, [
                        'class' => 'w-full h-full object-cover',
                        'alt'   => esc_attr($advisor_name),
                    ]); ?>
                <?php else : ?>
                    <img 
                        src="<?= esc_url(get_template_directory_uri() . '/assets/images/default-advisor.jpg'); ?>" 
                        alt="<?= esc_attr($advisor_name); ?>" 
                        class="w-full h-full object-cover"
                        onerror="this.src='https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=150&q=80'"
                    >
                <?php endif; ?>
            </div>
            <span class="absolute -bottom-0.5 -right-0.5 w-4.5 h-4.5 rounded-full bg-primary text-white flex items-center justify-center ring-2 ring-surface shadow-xs">
                <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
            </span>
        </div>

        <div class="flex flex-col min-w-0 flex-1">
            <h3 class="text-base font-bold text-charcoal m-0 leading-snug truncate">
                <?= esc_html($advisor_name); ?>
            </h3>
            <div class="flex items-center gap-2 mt-1 text-xs text-charcoal-muted font-sans flex-wrap">
                <?php if ($advisor_years) : ?>
                    <span class="inline-flex items-center gap-1 font-medium">
                        <svg class="w-3 h-3 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span><?= esc_html($advisor_years); ?> năm KN</span>
                    </span>
                <?php endif; ?>
                <?php if ($advisor_years && $advisor_listing_count) : ?>
                    <span class="text-charcoal-muted/30">•</span>
                <?php endif; ?>
                <?php if ($advisor_listing_count) : ?>
                    <span class="inline-flex items-center gap-1 font-medium">
                        <svg class="w-3 h-3 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        </svg>
                        <span><?= esc_html($advisor_listing_count); ?> giỏ hàng</span>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Media Video Channel Pills -->
    <?php
    $advisor_tiktok = function_exists('batdongsan_get_option') ? batdongsan_get_option('advisor_tiktok_url', 'https://tiktok.com/@batdongsanhanoi') : 'https://tiktok.com/@batdongsanhanoi';
    $advisor_youtube = function_exists('batdongsan_get_option') ? batdongsan_get_option('advisor_youtube_url', 'https://youtube.com/@batdongsanhanoi') : 'https://youtube.com/@batdongsanhanoi';
    ?>
    <div class="flex items-center gap-2 mb-3">
        <?php if ($advisor_tiktok) : ?>
            <a href="<?= esc_url($advisor_tiktok); ?>" target="_blank" rel="noopener noreferrer" class="flex-1 flex items-center justify-center gap-1.5 py-1.5 px-2 bg-black hover:bg-neutral-800 text-white rounded-xl text-[10.5px] font-semibold no-underline shadow-xs transition-colors">
                <?= batdongsan_icon('tiktok', ['width' => 11, 'height' => 11]); ?>
                <span>TikTok Review</span>
            </a>
        <?php endif; ?>
        <?php if ($advisor_youtube) : ?>
            <a href="<?= esc_url($advisor_youtube); ?>" target="_blank" rel="noopener noreferrer" class="flex-1 flex items-center justify-center gap-1.5 py-1.5 px-2 bg-[#c4302b] hover:bg-[#a8241f] text-white rounded-xl text-[10.5px] font-semibold no-underline shadow-xs transition-colors">
                <?= batdongsan_icon('youtube', ['width' => 11, 'height' => 11]); ?>
                <span>YouTube Tour</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Direct Contact CTAs: Call & Zalo -->
    <div class="grid grid-cols-2 gap-2">
        <?php if ($advisor_phone) : ?>
            <a 
                href="tel:<?= esc_attr($sanitized_phone); ?>" 
                class="flex items-center justify-center gap-1.5 h-10 px-3 bg-primary hover:bg-primary-hover active:scale-[0.98] text-white font-sans font-semibold text-xs rounded-xl shadow-button transition-all"
                title="Gọi trực tiếp cho chuyên viên"
            >
                <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
                <span class="truncate">Gọi <?= esc_html($advisor_phone); ?></span>
            </a>
        <?php endif; ?>

        <?php if ($advisor_zalo) : ?>
            <a 
                href="<?= esc_url($advisor_zalo); ?>" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="flex items-center justify-center gap-1.5 h-10 px-3 bg-[#0068FF] hover:bg-[#0055D4] active:scale-[0.98] text-white font-sans font-semibold text-xs rounded-xl shadow-sm transition-all"
                title="Chat Zalo với chuyên viên"
            >
                <span class="w-4 h-4 bg-white text-[#0068FF] rounded-full text-[10px] font-bold flex items-center justify-center shrink-0" aria-hidden="true">Z</span>
                <span>Chat Zalo</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Quick Consultation Button (Triggers #advisor-modal) -->
    <button 
        type="button" 
        class="w-full mt-2.5 h-9 bg-canvas hover:bg-stone text-charcoal font-sans font-medium text-xs rounded-xl border border-border flex items-center justify-center gap-1.5 transition-colors cursor-pointer active:scale-[0.99]" 
        data-modal-target="advisor-modal"
    >
        <svg class="w-3.5 h-3.5 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <span><?= esc_html($modal_cta_label); ?></span>
    </button>
</div>
