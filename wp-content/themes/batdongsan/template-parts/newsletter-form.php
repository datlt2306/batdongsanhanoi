<?php

/**
 * Newsletter subscription form template part - Luxury Real Estate Redesign
 * 
 * @package BatDongSan
 * 
 * @param array $args {
 *     @type string $title Tiêu đề form (default: 'Nhận bản tin thị trường bất động sản')
 *     @type string $description Mô tả form
 *     @type string $form_action Form action URL (default: '#')
 *     @type string $form_method Form method (default: 'post')
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

$title = $args['title'] ?? 'Nhận bản tin thị trường bất động sản';
$description = $args['description'] ?? 'Tổng hợp các báo cáo phân tích độc quyền, danh mục BĐS chọn lọc và biến động giá thị trường Hà Nội hàng tuần.';
$form_action = $args['form_action'] ?? '#';
$form_method = $args['form_method'] ?? 'post';
?>

<section class="rounded-2xl bg-white border border-black/[0.06] p-6 shadow-[0_2px_12px_rgba(0,0,0,0.03)]">
    <div class="flex items-center gap-2 mb-2">
        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
        <span class="text-xs uppercase tracking-wider text-primary font-semibold">Bản tin thị trường</span>
    </div>
    <h3 class="text-base font-bold text-charcoal m-0">
        <?= esc_html($title); ?>
    </h3>
    <?php if ($description) : ?>
        <p class="mt-2 text-xs text-charcoal-muted leading-relaxed m-0">
            <?= esc_html($description); ?>
        </p>
    <?php endif; ?>
    <form class="bds-newsletter-form mt-4 space-y-3" method="<?= esc_attr($form_method); ?>" action="<?= esc_url($form_action); ?>">
        <input type="text" name="bds_honeypot" class="hidden" style="display:none !important;" tabindex="-1" autocomplete="off" aria-hidden="true">
        <input type="hidden" name="action" value="batdongsan_submit_lead">
        <input type="hidden" name="source" value="newsletter">
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('bds_lead_nonce')); ?>">
        
        <div class="space-y-1">
            <label for="newsletter-name" class="block text-xs font-medium text-charcoal">Họ và tên <span class="text-primary">*</span></label>
            <input
                id="newsletter-name"
                type="text"
                name="fullname"
                required
                class="w-full rounded-full border border-black/[0.06] bg-[#F5F5F7] px-4 py-2 text-xs font-sans text-charcoal placeholder:text-charcoal-muted/60 outline-none transition focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20"
                placeholder="Nhập họ và tên">
        </div>
        
        <div class="space-y-1">
            <label for="newsletter-phone" class="block text-xs font-medium text-charcoal">Số điện thoại <span class="text-primary">*</span></label>
            <input
                id="newsletter-phone"
                type="tel"
                name="phone"
                required
                pattern="(0|\+84|84)[3|5|7|8|9][0-9]{8}"
                class="w-full rounded-full border border-black/[0.06] bg-[#F5F5F7] px-4 py-2 text-xs font-sans text-charcoal placeholder:text-charcoal-muted/60 outline-none transition focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20"
                placeholder="Số điện thoại di động (10 số)">
        </div>
        
        <div class="space-y-1">
            <label for="newsletter-email" class="block text-xs font-medium text-charcoal">Email</label>
            <input
                id="newsletter-email"
                type="email"
                name="email"
                class="w-full rounded-full border border-black/[0.06] bg-[#F5F5F7] px-4 py-2 text-xs font-sans text-charcoal placeholder:text-charcoal-muted/60 outline-none transition focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20"
                placeholder="Địa chỉ email nhận báo cáo">
        </div>
        
        <div class="bds-form-feedback hidden text-xs py-2 px-3 rounded-full border" role="alert" aria-live="polite"></div>
        
        <button
            type="submit"
            class="bds-newsletter-submit inline-flex w-full items-center justify-center rounded-full bg-primary hover:bg-primary-hover px-4 py-2.5 text-xs font-medium text-white shadow-sm transition-all duration-200 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
            Đăng ký nhận tin
        </button>
        <p class="text-[11px] text-charcoal-muted text-center m-0 leading-normal">
            Bảo mật thông tin 100%. Bạn có thể từ chối nhận email bất cứ khi nào.
        </p>
    </form>
</section>