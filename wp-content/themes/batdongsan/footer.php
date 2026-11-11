<?php
/**
 * Footer template for BatDongSan theme
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$site_hotline     = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_hotline', '0934.69.9191') : '0934.69.9191';
$site_hotline_raw = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_hotline_raw', '0934699191') : '0934699191';
$site_zalo        = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_zalo', '0934699191') : '0934699191';
$site_email       = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_email', 'saleso@batdongsanhanoi.net.vn') : 'saleso@batdongsanhanoi.net.vn';
$footer_about     = function_exists('batdongsan_get_option') ? batdongsan_get_option('footer_about', 'Cổng thông tin & tư vấn bất động sản, dự án và nhà đất thổ cư tại Hà Nội.') : 'Cổng thông tin & tư vấn bất động sản, dự án và nhà đất thổ cư tại Hà Nội.';
$footer_copyright = function_exists('batdongsan_get_option') ? batdongsan_get_option('footer_copyright', 'BatDongSanHaNoi. Tất cả quyền được bảo lưu.') : 'BatDongSanHaNoi. Tất cả quyền được bảo lưu.';
$social_facebook  = function_exists('batdongsan_get_option') ? batdongsan_get_option('social_facebook', 'https://facebook.com') : 'https://facebook.com';
$social_zalo      = function_exists('batdongsan_get_option') ? batdongsan_get_option('social_zalo', 'https://zalo.me/' . $site_zalo) : 'https://zalo.me/' . $site_zalo;
$social_youtube   = function_exists('batdongsan_get_option') ? batdongsan_get_option('social_youtube', 'https://youtube.com') : 'https://youtube.com';
?>
<footer class="bg-[#18181a] text-white/70 border-t border-white/10 text-xs sm:text-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-14">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-10">
            <!-- Cột 1: Thông Tin Liên Hệ & Thương Hiệu -->
            <div class="border-b border-white/[0.08] md:border-none pb-6 md:pb-0">
                <h3 class="m-0 mb-3 text-xs font-semibold uppercase tracking-wider text-white/90">Thông Tin Liên Hệ</h3>
                <p class="m-0 mb-3 text-xs text-white/60 leading-relaxed"><?php echo esc_html($footer_about); ?></p>
                <div class="space-y-1.5 text-xs">
                    <p class="m-0 text-white/70 flex items-center gap-2">
                        <span class="text-white/40">Hotline/Zalo:</span>
                        <a href="tel:<?php echo esc_attr($site_hotline_raw); ?>" class="text-primary hover:underline font-semibold"><?php echo esc_html($site_hotline); ?></a>
                    </p>
                    <p class="m-0 text-white/70 flex items-center gap-2">
                        <span class="text-white/40">Email:</span>
                        <a href="mailto:<?php echo esc_attr($site_email); ?>" class="text-white/80 hover:text-white transition-colors"><?php echo esc_html($site_email); ?></a>
                    </p>
                </div>
            </div>

            <!-- Cột 2: Menu Nhanh (Apple Accordion on mobile) -->
            <div class="border-b border-white/[0.08] md:border-none pb-4 md:pb-0" data-footer-col>
                <button type="button" class="footer-acc-trigger md:pointer-events-none w-full flex items-center justify-between text-left m-0 py-1 md:py-0 md:mb-3 text-xs font-semibold uppercase tracking-wider text-white/90 cursor-pointer md:cursor-default" aria-expanded="false">
                    <span>Khám Phá</span>
                    <svg class="footer-acc-icon w-3.5 h-3.5 text-white/40 md:hidden transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="footer-acc-content hidden md:block pt-3 md:pt-0">
                    <?php if (has_nav_menu('footer')) : ?>
                        <nav aria-label="<?php esc_attr_e('Footer Navigation', 'batdongsan'); ?>">
                            <?php
                            wp_nav_menu([
                                'theme_location' => 'footer',
                                'container'      => false,
                                'menu_class'     => 'list-none p-0 m-0 grid gap-2 [&_a]:text-white/60 hover:[&_a]:text-white [&_a]:transition-colors',
                                'depth'          => 1,
                                'fallback_cb'    => false,
                            ]);
                            ?>
                        </nav>
                    <?php else : ?>
                        <ul class="list-none p-0 m-0 grid gap-2">
                            <li><a href="<?php echo esc_url(home_url('/')); ?>" class="text-white/60 hover:text-white transition-colors">Trang chủ</a></li>
                            <li><a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>" class="text-white/60 hover:text-white transition-colors">Giới thiệu</a></li>
                            <li><a href="<?php echo esc_url(home_url('/bat-dong-san/')); ?>" class="text-white/60 hover:text-white transition-colors">Bất động sản</a></li>
                            <li><a href="<?php echo esc_url(home_url('/du-an/')); ?>" class="text-white/60 hover:text-white transition-colors">Dự án quy hoạch</a></li>
                            <li><a href="<?php echo esc_url(home_url('/tin-tuc/')); ?>" class="text-white/60 hover:text-white transition-colors">Tin thị trường</a></li>
                            <li><a href="<?php echo esc_url(home_url('/lien-he/')); ?>" class="text-white/60 hover:text-white transition-colors">Liên hệ</a></li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cột 3: Dịch Vụ & Lọc Nhanh (Apple Accordion on mobile) -->
            <div class="border-b border-white/[0.08] md:border-none pb-4 md:pb-0" data-footer-col>
                <button type="button" class="footer-acc-trigger md:pointer-events-none w-full flex items-center justify-between text-left m-0 py-1 md:py-0 md:mb-3 text-xs font-semibold uppercase tracking-wider text-white/90 cursor-pointer md:cursor-default" aria-expanded="false">
                    <span>Phân Loại Giao Dịch</span>
                    <svg class="footer-acc-icon w-3.5 h-3.5 text-white/40 md:hidden transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="footer-acc-content hidden md:block pt-3 md:pt-0">
                    <ul class="list-none p-0 m-0 grid gap-2">
                        <li><a href="<?php echo esc_url(home_url('/bat-dong-san/?hinh_thuc=ban')); ?>" class="text-white/60 hover:text-white transition-colors">Mua bán nhà đất</a></li>
                        <li><a href="<?php echo esc_url(home_url('/bat-dong-san/?hinh_thuc=cho-thue')); ?>" class="text-white/60 hover:text-white transition-colors">Thuê căn hộ &amp; nhà phố</a></li>
                        <li><a href="<?php echo esc_url(home_url('/bat-dong-san/?hinh_thuc=chuyen-nhuong')); ?>" class="text-white/60 hover:text-white transition-colors">Chuyển nhượng căn hộ</a></li>
                        <li><a href="tel:<?php echo esc_attr($site_hotline_raw); ?>" class="text-white/60 hover:text-white transition-colors">Tư vấn định giá &amp; Pháp lý</a></li>
                    </ul>
                </div>
            </div>

            <!-- Cột 4: Kết Nối Với Chúng Tôi (Clean Cupertino SVGs) -->
            <div class="pb-2 md:pb-0">
                <h3 class="m-0 mb-3 text-xs font-semibold uppercase tracking-wider text-white/90">Kết Nối</h3>
                <div class="flex items-center gap-2">
                    <?php if (!empty($social_facebook)) : ?>
                        <!-- Facebook SVG -->
                        <a aria-label="Facebook" href="<?php echo esc_url($social_facebook); ?>" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-white/[0.06] border border-white/[0.08] flex items-center justify-center text-white/70 hover:text-white hover:bg-white/[0.12] active:scale-90 transition-all">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($social_zalo)) : ?>
                        <!-- Zalo SVG -->
                        <a aria-label="Zalo" href="<?php echo esc_url($social_zalo); ?>" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-white/[0.06] border border-white/[0.08] flex items-center justify-center text-white/70 hover:text-white hover:bg-white/[0.12] active:scale-90 transition-all">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2C6.48 2 2 6.03 2 11c0 2.87 1.5 5.43 3.84 7.04-.15.93-.65 2.8-1.84 4.01 0 0 2.45-.25 4.68-1.74.43.08.87.13 1.32.13 5.52 0 10-4.03 10-9s-4.48-9-10-9zm-1.8 12.5H7.2a.5.5 0 0 1-.41-.78l2.25-3.22H7.2a.5.5 0 1 1 0-1h3a.5.5 0 0 1 .41.78l-2.25 3.22h1.84a.5.5 0 0 1 0 1zm4.6 0h-1.4a.5.5 0 0 1-.5-.5V8.5a.5.5 0 0 1 .5-.5h1.4c1.27 0 2.3 1.03 2.3 2.3v1.9c0 1.27-1.03 2.3-2.3 2.3zm0-3.7h-.5v2.7h.5c.72 0 1.3-.58 1.3-1.3v-.1c0-.72-.58-1.3-1.3-1.3z"/>
                            </svg>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($social_youtube)) : ?>
                        <!-- YouTube SVG -->
                        <a aria-label="YouTube" href="<?php echo esc_url($social_youtube); ?>" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-white/[0.06] border border-white/[0.08] flex items-center justify-center text-white/70 hover:text-white hover:bg-white/[0.12] active:scale-90 transition-all">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Dòng Bản Quyền & Liên Kết Pháp Lý -->
        <div class="border-t border-white/[0.08] mt-8 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-white/50 tracking-normal">
            <div>
                &copy; <?php echo esc_html(date('Y')); ?> <?php echo esc_html($footer_copyright); ?>
            </div>
            <div class="flex items-center flex-wrap justify-center gap-3 sm:gap-4 text-white/50">
                <a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>" class="hover:text-white transition-colors">Giới thiệu</a>
                <span class="text-white/20">&bull;</span>
                <a href="<?php echo esc_url(home_url('/chinh-sach-bao-mat/')); ?>" class="hover:text-white transition-colors">Chính sách bảo mật</a>
                <span class="text-white/20">&bull;</span>
                <a href="<?php echo esc_url(home_url('/dieu-khoan-dich-vu/')); ?>" class="hover:text-white transition-colors">Điều khoản dịch vụ</a>
                <span class="text-white/20">&bull;</span>
                <a href="<?php echo esc_url(home_url('/lien-he/')); ?>" class="hover:text-white transition-colors">Liên hệ</a>
            </div>
        </div>
    </div>
</footer>

<?php
// Video Lightbox Modal - Dùng chung cho toàn website
get_template_part('template-parts/video-modal');

// Sticky Contact Bar - Hiển thị trên tất cả các trang (chỉ mobile & tablet < 1024px)
$advisor_data = function_exists('batdongsan_get_advisor_data') ? batdongsan_get_advisor_data() : [];

if (!empty($advisor_data['advisor_phone']) || !empty($advisor_data['advisor_zalo'])) :
    get_template_part('template-parts/sticky-contact', null, [
        'advisor_name'   => $advisor_data['advisor_name'] ?? '',
        'advisor_avatar' => $advisor_data['advisor_avatar'] ?? 0,
        'advisor_phone'  => $advisor_data['advisor_phone'] ?? '',
        'advisor_zalo'   => $advisor_data['advisor_zalo'] ?? '',
        'mobile_only'    => true,
    ]);
endif;
?>

<?php wp_footer(); ?>
</body>

</html>