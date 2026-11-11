<?php

/**
 * Advisor/Broker Card Template Part - Luxury Real Estate Redesign (Milestone 5)
 *
 * @package BatDongSan
 *
 * @param array $args {
 *     @type string $advisor_name          Tên chuyên viên / cố vấn
 *     @type int    $advisor_avatar        Avatar ID
 *     @type string $advisor_phone         Số điện thoại hotline
 *     @type string $advisor_email         Email
 *     @type string $advisor_zalo          Zalo URL
 *     @type string $advisor_years         Số năm kinh nghiệm
 *     @type string $advisor_listing_count  Số lượng giỏ hàng đang phụ trách
 *     @type string $advisor_profile_url   URL trang cá nhân
 *     @type string $advisor_platform_name Tên nền tảng (default: 'BatDongSanHaNoi')
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

$advisor_name          = $args['advisor_name'] ?? 'Chuyên viên Bất động sản Hà Nội';
$advisor_avatar        = $args['advisor_avatar'] ?? 0;
$advisor_phone         = $args['advisor_phone'] ?? '';
$advisor_email         = $advisor_email ?? ($args['advisor_email'] ?? '');
$advisor_zalo          = $args['advisor_zalo'] ?? '';
$advisor_years         = $args['advisor_years'] ?? '10+';
$advisor_listing_count = $args['advisor_listing_count'] ?? '85+';
$advisor_profile_url   = $args['advisor_profile_url'] ?? '';
$advisor_platform_name = $args['advisor_platform_name'] ?? 'BatDongSanHaNoi';

$sanitized_phone = preg_replace('/\D/', '', $advisor_phone);
if (empty($advisor_zalo) && !empty($sanitized_phone)) {
    $advisor_zalo = 'https://zalo.me/' . $sanitized_phone;
}
?>

<div class="advisor-card bg-surface rounded-2xl border border-border shadow-subtle overflow-hidden">
    <div class="p-5 sm:p-5.5 flex flex-col items-center text-center">
        
        <!-- Advisor Avatar with Champagne Bronze Ring & Verified Badge -->
        <div class="relative mb-3">
            <div class="w-18 h-18 sm:w-20 sm:h-20 rounded-full overflow-hidden ring-3 ring-primary/25 shadow-subtle bg-stone">
                <?php if ($advisor_avatar) : ?>
                    <?= wp_get_attachment_image($advisor_avatar, 'medium', false, [
                        'class' => 'w-full h-full object-cover',
                        'alt'   => esc_attr($advisor_name),
                    ]); ?>
                <?php else : ?>
                    <img 
                        src="<?= esc_url(get_template_directory_uri() . '/assets/images/default-advisor.jpg'); ?>" 
                        alt="<?= esc_attr($advisor_name); ?>" 
                        class="w-full h-full object-cover"
                        onerror="this.src='https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=256&q=80'"
                    >
                <?php endif; ?>
            </div>

            <!-- Verified Badge Icon -->
            <span class="absolute bottom-0 right-0 w-5.5 h-5.5 rounded-full bg-primary text-white flex items-center justify-center ring-2 ring-surface shadow-subtle" title="Cố vấn được xác minh">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
            </span>
        </div>

        <!-- Advisor Title & Role -->
        <span class="text-[10px] font-sans font-semibold uppercase tracking-wider text-primary mb-0.5">
            Cố Vấn Bất Động Sản
        </span>
        <h3 class="text-base sm:text-lg font-bold text-charcoal m-0 leading-tight">
            <?= esc_html($advisor_name); ?>
        </h3>

        <!-- Trust Stats (Experience & Portfolio) -->
        <?php if ($advisor_years || $advisor_listing_count) : ?>
            <div class="grid grid-cols-2 gap-2 w-full mt-3 pt-3 border-t border-black/[0.06]">
                <div class="bg-[#F5F5F7] rounded-xl p-2 border border-black/[0.04] flex flex-col">
                    <span class="text-[10px] text-charcoal-muted font-sans font-medium">Kinh nghiệm</span>
                    <span class="text-xs sm:text-sm font-bold text-charcoal mt-0.5">
                        <?= $advisor_years ? esc_html($advisor_years) . ' năm' : '10+ năm'; ?>
                    </span>
                </div>
                <div class="bg-[#F5F5F7] rounded-xl p-2 border border-black/[0.04] flex flex-col">
                    <span class="text-[10px] text-charcoal-muted font-sans font-medium">Giỏ hàng</span>
                    <span class="text-xs sm:text-sm font-bold text-charcoal mt-0.5">
                        <?= $advisor_listing_count ? esc_html($advisor_listing_count) . ' tin' : '85+ tin'; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($advisor_profile_url) : ?>
            <a href="<?= esc_url($advisor_profile_url); ?>" class="inline-flex items-center gap-1.5 mt-2 text-xs font-sans font-medium text-primary hover:text-primary-hover underline underline-offset-2 transition-colors">
                <span>Xem hồ sơ & danh mục phụ trách</span>
                <span>&rarr;</span>
            </a>
        <?php endif; ?>

        <!-- Social Media Video Channels (Tinh tế dạng text link, không tranh chấp CTA chính) -->
        <?php
        $advisor_tiktok = function_exists('batdongsan_get_option') ? batdongsan_get_option('advisor_tiktok_url', 'https://tiktok.com/@batdongsanhanoi') : 'https://tiktok.com/@batdongsanhanoi';
        $advisor_youtube = function_exists('batdongsan_get_option') ? batdongsan_get_option('advisor_youtube_url', 'https://youtube.com/@batdongsanhanoi') : 'https://youtube.com/@batdongsanhanoi';
        ?>
        <?php if ($advisor_tiktok || $advisor_youtube) : ?>
            <div class="flex items-center justify-center gap-3 w-full mt-2.5 pt-2 border-t border-black/[0.04]">
                <?php if ($advisor_tiktok) : ?>
                    <a 
                        href="<?= esc_url($advisor_tiktok); ?>" 
                        target="_blank" 
                        rel="noopener noreferrer" 
                        class="inline-flex items-center gap-1 text-[11px] font-medium text-charcoal-muted hover:text-charcoal transition-colors no-underline"
                        title="Xem các video review trên TikTok"
                    >
                        <?= batdongsan_icon('tiktok', ['width' => 11, 'height' => 11]); ?>
                        <span>Kênh TikTok</span>
                    </a>
                <?php endif; ?>
                <?php if ($advisor_tiktok && $advisor_youtube) : ?>
                    <span class="text-black/15">·</span>
                <?php endif; ?>
                <?php if ($advisor_youtube) : ?>
                    <a 
                        href="<?= esc_url($advisor_youtube); ?>" 
                        target="_blank" 
                        rel="noopener noreferrer" 
                        class="inline-flex items-center gap-1 text-[11px] font-medium text-charcoal-muted hover:text-[#c4302b] transition-colors no-underline"
                        title="Xem các video review trên YouTube"
                    >
                        <?= batdongsan_icon('youtube', ['width' => 11, 'height' => 11]); ?>
                        <span>Kênh YouTube</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Quick Consultation Form (Embedded High-Conversion Lead Capture) -->
        <div class="w-full mt-3 pt-3 border-t border-black/[0.06] text-left">
            <h4 class="text-xs sm:text-sm font-semibold text-charcoal m-0 mb-0.5">
                Yêu cầu tư vấn & tham quan
            </h4>
            <p class="text-[11px] text-charcoal-muted font-sans m-0 mb-3">
                Chuyên viên sẽ gửi tài liệu quy hoạch & bảng giá chi tiết trong 15 phút.
            </p>

            <form class="advisor-quick-lead-form flex flex-col gap-2.5" method="POST" action="">
                <?php wp_nonce_field('bds_lead_nonce', 'bds_nonce'); ?>
                <input type="hidden" name="action" value="batdongsan_submit_lead">
                <input type="hidden" name="source" value="advisor_modal">
                <input type="hidden" name="post_id" value="<?php echo esc_attr(get_the_ID()); ?>">

                <!-- Anti-bot honeypot -->
                <input type="text" name="bds_honeypot" value="" class="hidden" tabindex="-1" autocomplete="off">

                <div>
                    <input 
                        type="text" 
                        name="fullname" 
                        required 
                        placeholder="Họ và tên quý khách" 
                        class="w-full h-9 bg-[#F5F5F7] border border-black/[0.06] rounded-full px-3.5 text-xs font-sans text-charcoal placeholder:text-charcoal-muted/70 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none transition-all"
                    >
                </div>

                <div>
                    <input 
                        type="tel" 
                        name="phone" 
                        required 
                        placeholder="Số điện thoại liên hệ" 
                        class="w-full h-9 bg-[#F5F5F7] border border-black/[0.06] rounded-full px-3.5 text-xs font-sans text-charcoal placeholder:text-charcoal-muted/70 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none transition-all"
                    >
                </div>

                <button 
                    type="submit" 
                    class="w-full h-9 bg-primary hover:bg-primary-hover text-white font-sans font-medium text-[11px] uppercase tracking-wider rounded-full transition-all shadow-sm active:scale-[0.99] cursor-pointer flex items-center justify-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                    <span>Gửi Yêu Cầu Tư Vấn</span>
                </button>

                <div class="advisor-quick-lead-feedback hidden text-xs text-center py-1.5 rounded-full font-sans"></div>
            </form>

            <!-- Hoặc liên hệ trực tiếp qua Zalo / Hotline -->
            <div class="relative flex items-center justify-center my-3">
                <span class="absolute inset-0 flex items-center">
                    <span class="w-full border-t border-black/[0.06]"></span>
                </span>
                <span class="relative bg-white px-2.5 text-[10px] font-sans uppercase tracking-wider text-charcoal-muted">
                    Hoặc liên hệ trực tiếp
                </span>
            </div>

            <!-- Direct Contact CTAs: Zalo & Phone -->
            <div class="grid grid-cols-2 gap-2 w-full">
                <?php if ($advisor_zalo) : ?>
                    <a 
                        href="<?= esc_url($advisor_zalo); ?>" 
                        class="flex items-center justify-center gap-1.5 h-9 bg-[#0068FF] hover:bg-[#0055D4] text-white font-sans font-medium text-xs rounded-full transition-all shadow-subtle no-underline" 
                        target="_blank" 
                        rel="noopener noreferrer"
                        title="Nhắn tin qua Zalo"
                    >
                        <span class="w-3.5 h-3.5 bg-white text-[#0068FF] rounded-full text-[9px] font-bold flex items-center justify-center" aria-hidden="true">Z</span>
                        <span>Chat Zalo</span>
                    </a>
                <?php endif; ?>

                <?php if ($advisor_phone) : ?>
                    <a
                        href="tel:<?= esc_attr($sanitized_phone); ?>"
                        class="flex items-center justify-center gap-1.5 h-9 bg-charcoal hover:bg-black text-white font-sans font-medium text-xs rounded-full transition-all shadow-subtle no-underline"
                        title="Gọi điện hotline"
                    >
                        <svg class="w-3 h-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <span><?= esc_html($advisor_phone); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var quickForm = document.querySelector('.advisor-quick-lead-form');
    if (!quickForm) return;

    quickForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = quickForm.querySelector('button[type="submit"]');
        var feedback = quickForm.querySelector('.advisor-quick-lead-feedback');
        var originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span>Đang gửi thông tin...</span>';
        if (feedback) {
            feedback.className = 'advisor-quick-lead-feedback hidden text-xs text-center py-1.5 rounded-lg font-sans';
        }

        var formData = new FormData(quickForm);
        var nonceInput = quickForm.querySelector('[name="bds_nonce"]');
        if (nonceInput) {
            formData.append('nonce', nonceInput.value);
        }

        var ajaxUrl = (window.bdsTheme && window.bdsTheme.ajaxUrl) || (window.bds_vars && window.bds_vars.ajax_url) || '/wp-admin/admin-ajax.php';
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(resData) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (feedback) {
                feedback.classList.remove('hidden');
                if (resData && resData.success) {
                    feedback.className = 'advisor-quick-lead-feedback text-xs text-center py-2 px-3 rounded-xl font-sans bg-emerald-50 text-emerald-800 border border-emerald-200 mt-2';
                    feedback.textContent = (resData.data && resData.data.message) ? resData.data.message : 'Yêu cầu tư vấn của bạn đã được tiếp nhận thành công!';
                    quickForm.reset();
                } else {
                    feedback.className = 'advisor-quick-lead-feedback text-xs text-center py-2 px-3 rounded-xl font-sans bg-stone text-charcoal border border-border mt-2';
                    feedback.textContent = (resData && resData.data && resData.data.message) ? resData.data.message : 'Đã có lỗi xảy ra. Vui lòng thử lại hoặc gọi hotline!';
                }
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (feedback) {
                feedback.classList.remove('hidden');
                feedback.className = 'advisor-quick-lead-feedback text-xs text-center py-2 px-3 rounded-xl font-sans bg-stone text-charcoal border border-border mt-2';
                feedback.textContent = 'Kết nối mạng gián đoạn. Vui lòng gọi trực tiếp hotline!';
            }
        });
    });
});
</script>
