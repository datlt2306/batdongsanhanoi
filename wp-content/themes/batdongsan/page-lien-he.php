<?php
/**
 * Template Name: Trang Liên Hệ
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Lấy thông tin cấu hình website từ ACF Site Options
$site_hotline       = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_hotline', '0934.69.9191') : '0934.69.9191';
$site_hotline_raw   = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_hotline_raw', '0934699191') : '0934699191';
$site_zalo          = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_zalo', '0934699191') : '0934699191';
$site_email         = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_email', 'saleso@batdongsanhanoi.net.vn') : 'saleso@batdongsanhanoi.net.vn';
$site_address       = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_address', 'Số 68 Cầu Giấy, Phường Quan Hoa, Quận Cầu Giấy, Hà Nội') : 'Số 68 Cầu Giấy, Phường Quan Hoa, Quận Cầu Giấy, Hà Nội';
$site_working_hours = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_working_hours', '8:00 - 21:00 hàng ngày (kể cả Thứ Bảy & Chủ Nhật)') : '8:00 - 21:00 hàng ngày (kể cả Thứ Bảy & Chủ Nhật)';

$social_facebook    = function_exists('batdongsan_get_option') ? batdongsan_get_option('social_facebook', 'https://facebook.com') : 'https://facebook.com';
$social_zalo        = function_exists('batdongsan_get_option') ? batdongsan_get_option('social_zalo', 'https://zalo.me/' . $site_zalo) : 'https://zalo.me/' . $site_zalo;
$social_youtube     = function_exists('batdongsan_get_option') ? batdongsan_get_option('social_youtube', '') : '';
$social_tiktok      = function_exists('batdongsan_get_option') ? batdongsan_get_option('social_tiktok', '') : '';

// Thông tin chuyên viên tư vấn
$advisor_data = function_exists('batdongsan_get_advisor_data') ? batdongsan_get_advisor_data() : [];
$advisor_name = $advisor_data['advisor_name'] ?? 'Chuyên viên Bất Động Sản Hà Nội';
$advisor_phone = $advisor_data['advisor_phone'] ?? $site_hotline;
$advisor_phone_raw = $advisor_data['advisor_phone_raw'] ?? $site_hotline_raw;
$advisor_avatar = $advisor_data['advisor_avatar'] ?? 0;
$advisor_years = $advisor_data['advisor_years'] ?? '8+ năm';

$breadcrumb_parts = [
    [
        'label' => 'Trang chủ',
        'url'   => home_url('/'),
    ],
    [
        'label' => 'Liên hệ',
        'url'   => '',
    ],
];
?>

<main id="primary" class="site-main py-8 md:py-14 bg-canvas min-h-[70vh]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <?php
        get_template_part('template-parts/breadcrumb', null, [
            'breadcrumb_parts' => $breadcrumb_parts,
            'current_label'    => 'Liên hệ',
            'prefix_class'     => 'contact-page',
        ]);
        ?>

        <!-- Hero Section Header -->
        <header class="mt-4 mb-8 sm:mb-12 text-center max-w-3xl mx-auto">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary-light border border-primary/15 text-primary text-xs font-semibold uppercase tracking-wider mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                <span>Kết Nối Với Chúng Tôi</span>
            </div>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-charcoal tracking-tight m-0 mb-4">
                Liên Hệ BatDongSanHaNoi
            </h1>
            <p class="text-charcoal-muted text-sm sm:text-base leading-relaxed m-0">
                Đội ngũ chuyên viên tư vấn am hiểu sâu sắc quy hoạch và biến động thị trường tại Hà Nội luôn sẵn sàng hỗ trợ Quý khách hàng 24/7. Hãy để lại thông tin hoặc kết nối trực tiếp với chúng tôi.
            </p>
        </header>

        <!-- Main Contact Grid: Info & Form -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">

            <!-- Cột Trái (5 cols): Thông Tin Trực Tiếp & Cam Kết -->
            <div class="lg:col-span-5 space-y-6">

                <!-- Card Thông tin liên hệ nhanh -->
                <div class="bg-white rounded-3xl border border-border shadow-card p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-charcoal tracking-tight m-0 mb-6 flex items-center gap-2.5">
                        <span class="w-2 h-5 bg-primary rounded-full"></span>
                        <span>Thông Tin Liên Hệ Trực Tiếp</span>
                    </h2>

                    <div class="space-y-5 text-sm">
                        <!-- Hotline -->
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-2xl bg-primary-light text-primary flex items-center justify-center shrink-0 border border-primary/15">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H3.625A2.25 2.25 0 001.375 4.5v2.25z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-charcoal-muted uppercase font-semibold tracking-wider m-0 mb-0.5">Hotline Tư Vấn 24/7</p>
                                <a href="tel:<?php echo esc_attr($site_hotline_raw); ?>" class="text-base sm:text-lg font-bold text-primary hover:underline block">
                                    <?php echo esc_html($site_hotline); ?>
                                </a>
                                <span class="text-xs text-charcoal-muted">Hỗ trợ cuộc gọi miễn phí cước tư vấn</span>
                            </div>
                        </div>

                        <!-- Zalo -->
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-200">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.75.75 0 01-.89-.89 6.002 6.002 0 011.057-3.035C4.03 15.688 3 13.938 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-charcoal-muted uppercase font-semibold tracking-wider m-0 mb-0.5">Nhắn Tin Zalo Nhanh</p>
                                <a href="<?php echo esc_url($social_zalo); ?>" target="_blank" rel="noopener noreferrer" class="font-bold text-charcoal hover:text-primary transition-colors block">
                                    <?php echo esc_html($site_zalo); ?>
                                </a>
                                <span class="text-xs text-charcoal-muted">Gửi định vị, sổ đỏ, hình ảnh thực tế</span>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-2xl bg-stone text-charcoal flex items-center justify-center shrink-0 border border-border">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-charcoal-muted uppercase font-semibold tracking-wider m-0 mb-0.5">Email Hỗ Trợ & Hợp Tác</p>
                                <a href="mailto:<?php echo esc_attr($site_email); ?>" class="font-medium text-charcoal hover:text-primary transition-colors block break-all">
                                    <?php echo esc_html($site_email); ?>
                                </a>
                            </div>
                        </div>

                        <!-- Địa chỉ -->
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-2xl bg-stone text-charcoal flex items-center justify-center shrink-0 border border-border">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-charcoal-muted uppercase font-semibold tracking-wider m-0 mb-0.5">Trụ Sở Văn Phòng</p>
                                <p class="font-medium text-charcoal m-0 leading-snug">
                                    <?php echo esc_html($site_address); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Giờ làm việc -->
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-2xl bg-stone text-charcoal flex items-center justify-center shrink-0 border border-border">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-charcoal-muted uppercase font-semibold tracking-wider m-0 mb-0.5">Thời Gian Làm Việc</p>
                                <p class="font-medium text-charcoal m-0 leading-snug">
                                    <?php echo esc_html($site_working_hours); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Social Channels -->
                    <div class="mt-8 pt-6 border-t border-border-light">
                        <p class="text-xs font-semibold text-charcoal-muted uppercase tracking-wider mb-3">Kênh Mạng Xã Hội</p>
                        <div class="flex items-center gap-2.5">
                            <?php if (!empty($social_facebook)) : ?>
                                <a href="<?php echo esc_url($social_facebook); ?>" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-stone hover:bg-primary hover:text-white text-charcoal flex items-center justify-center transition-all duration-200 border border-border" aria-label="Facebook">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($social_zalo)) : ?>
                                <a href="<?php echo esc_url($social_zalo); ?>" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-stone hover:bg-primary hover:text-white text-charcoal flex items-center justify-center transition-all duration-200 border border-border" aria-label="Zalo">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.03 2 11c0 2.87 1.5 5.43 3.84 7.04-.15.93-.65 2.8-1.84 4.01 0 0 2.45-.25 4.68-1.74.43.08.87.13 1.32.13 5.52 0 10-4.03 10-9s-4.48-9-10-9zm-1.8 12.5H7.2a.5.5 0 0 1-.41-.78l2.25-3.22H7.2a.5.5 0 1 1 0-1h3a.5.5 0 0 1 .41.78l-2.25 3.22h1.84a.5.5 0 0 1 0 1zm4.6 0h-1.4a.5.5 0 0 1-.5-.5V8.5a.5.5 0 0 1 .5-.5h1.4c1.27 0 2.3 1.03 2.3 2.3v1.9c0 1.27-1.03 2.3-2.3 2.3zm0-3.7h-.5v2.7h.5c.72 0 1.3-.58 1.3-1.3v-.1c0-.72-.58-1.3-1.3-1.3z"/></svg>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($social_youtube)) : ?>
                                <a href="<?php echo esc_url($social_youtube); ?>" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full bg-stone hover:bg-primary hover:text-white text-charcoal flex items-center justify-center transition-all duration-200 border border-border" aria-label="YouTube">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Card Cam Kết Dịch Vụ -->
                <div class="bg-primary/5 rounded-3xl border border-primary/15 p-6 text-sm">
                    <h3 class="text-sm font-bold text-charcoal uppercase tracking-wider m-0 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        <span>Cam Kết Từ BatDongSanHaNoi</span>
                    </h3>
                    <ul class="space-y-2 m-0 p-0 list-none text-xs sm:text-sm text-charcoal/80">
                        <li class="flex items-start gap-2">
                            <span class="text-primary font-bold">&check;</span>
                            <span>Phản hồi thông tin trong vòng <strong>15 phút</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary font-bold">&check;</span>
                            <span>Tư vấn hoàn toàn <strong>miễn phí 100%</strong>, không phát sinh chi phí ẩn.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary font-bold">&check;</span>
                            <span>Kiểm tra quy hoạch, định giá thực tế và pháp lý sổ đỏ chuẩn xác.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary font-bold">&check;</span>
                            <span>Bảo mật tuyệt đối thông tin cá nhân khách hàng.</span>
                        </li>
                    </ul>
                </div>

            </div>

            <!-- Cột Phải (7 cols): Card Form Gửi Yêu Cầu Tư Vấn / Ký Gửi -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-3xl border border-border shadow-card p-6 sm:p-10 relative overflow-hidden">
                    <div class="mb-6">
                        <span class="text-xs font-semibold uppercase tracking-wider text-primary">Biểu Mẫu Tiếp Nhận</span>
                        <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mt-1">
                            Gửi Yêu Cầu Tư Vấn &amp; Ký Gửi Nhà Đất
                        </h2>
                        <p class="text-xs sm:text-sm text-charcoal-muted mt-1.5 leading-relaxed m-0">
                            Điền thông tin bên dưới để chuyên viên phụ trách khu vực liên hệ tư vấn và gửi bảng giá chi tiết.
                        </p>
                    </div>

                    <!-- Contact Form -->
                    <form class="bds-contact-form space-y-4" method="post" novalidate>
                        <!-- Honeypot anti-spam trap -->
                        <div class="hidden" aria-hidden="true">
                            <input type="text" name="bds_honeypot" tabindex="-1" autocomplete="off" value="">
                        </div>

                        <!-- Feedback Message Alert -->
                        <div class="bds-contact-feedback hidden text-xs sm:text-sm py-3 px-4 rounded-xl border" role="alert" aria-live="polite"></div>

                        <!-- Row: Họ tên & Số điện thoại -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="contact-fullname" class="block text-xs font-semibold text-charcoal uppercase tracking-wider mb-1.5">
                                    Họ và tên <span class="text-primary">*</span>
                                </label>
                                <input type="text" id="contact-fullname" name="fullname" placeholder="VD: Nguyễn Văn An" required class="w-full px-4 py-3 bg-stone/30 border border-border rounded-xl text-sm text-charcoal placeholder-charcoal-muted/60 focus:outline-none focus:border-primary focus:bg-white transition-all">
                            </div>
                            <div>
                                <label for="contact-phone" class="block text-xs font-semibold text-charcoal uppercase tracking-wider mb-1.5">
                                    Số điện thoại <span class="text-primary">*</span>
                                </label>
                                <input type="tel" id="contact-phone" name="phone" placeholder="VD: 0912345678" required class="w-full px-4 py-3 bg-stone/30 border border-border rounded-xl text-sm text-charcoal placeholder-charcoal-muted/60 focus:outline-none focus:border-primary focus:bg-white transition-all">
                            </div>
                        </div>

                        <!-- Row: Email & Nhu cầu tư vấn -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="contact-email" class="block text-xs font-semibold text-charcoal uppercase tracking-wider mb-1.5">
                                    Email (không bắt buộc)
                                </label>
                                <input type="email" id="contact-email" name="email" placeholder="example@gmail.com" class="w-full px-4 py-3 bg-stone/30 border border-border rounded-xl text-sm text-charcoal placeholder-charcoal-muted/60 focus:outline-none focus:border-primary focus:bg-white transition-all">
                            </div>
                            <div>
                                <label for="contact-demand" class="block text-xs font-semibold text-charcoal uppercase tracking-wider mb-1.5">
                                    Nhu cầu tư vấn
                                </label>
                                <select id="contact-demand" name="demand" class="w-full px-4 py-3 bg-stone/30 border border-border rounded-xl text-sm text-charcoal focus:outline-none focus:border-primary focus:bg-white transition-all">
                                    <option value="mua-nha-dat">Cần mua nhà đất / chung cư</option>
                                    <option value="ky-gui-ban">Ký gửi bán bất động sản</option>
                                    <option value="thue-cho-thue">Tìm thuê hoặc cho thuê BĐS</option>
                                    <option value="tu-van-du-an">Tìm hiểu dự án mới &amp; quy hoạch</option>
                                    <option value="dinh-gia-phap-ly">Tư vấn định giá &amp; Pháp lý sổ đỏ</option>
                                    <option value="khac">Nhu cầu khác</option>
                                </select>
                            </div>
                        </div>

                        <!-- Khu vực / Dự án quan tâm -->
                        <div>
                            <label for="contact-project" class="block text-xs font-semibold text-charcoal uppercase tracking-wider mb-1.5">
                                Khu vực hoặc Dự án Quý khách đang quan tâm
                            </label>
                            <input type="text" id="contact-project" name="project" placeholder="VD: Vinhomes Ocean Park, Cầu Giấy, Nam Từ Liêm, Tây Hồ..." class="w-full px-4 py-3 bg-stone/30 border border-border rounded-xl text-sm text-charcoal placeholder-charcoal-muted/60 focus:outline-none focus:border-primary focus:bg-white transition-all">
                        </div>

                        <!-- Lời nhắn chi tiết -->
                        <div>
                            <label for="contact-message" class="block text-xs font-semibold text-charcoal uppercase tracking-wider mb-1.5">
                                Nội dung chi tiết hoặc yêu cầu cụ thể
                            </label>
                            <textarea id="contact-message" name="message" rows="4" placeholder="VD: Tôi cần tìm căn 2PN tài chính khoảng 3 tỷ tại khu vực Cầu Giấy, hướng Đông Nam..." class="w-full px-4 py-3 bg-stone/30 border border-border rounded-xl text-sm text-charcoal placeholder-charcoal-muted/60 focus:outline-none focus:border-primary focus:bg-white transition-all resize-y"></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" class="bds-contact-submit w-full py-3.5 px-6 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-full shadow-button transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer">
                                <span>Gửi Yêu Cầu Tư Vấn Ngay</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                                </svg>
                            </button>
                            <p class="text-center text-[11px] text-charcoal-muted mt-2.5">
                                Bằng việc gửi thông tin, Quý khách đồng ý với <a href="<?php echo esc_url(home_url('/chinh-sach-bao-mat/')); ?>" class="underline hover:text-primary">Chính sách bảo mật</a> của chúng tôi.
                            </p>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        <!-- Section: Bản Đồ Văn Phòng -->
        <section class="mt-14 lg:mt-20">
            <div class="bg-white rounded-3xl border border-border shadow-card p-6 sm:p-8 lg:p-10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-primary">Vị Trí Trụ Sở</span>
                        <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mt-0.5">
                            Bản Đồ Chỉ Đường Đến Văn Phòng
                        </h2>
                    </div>
                    <a href="https://maps.google.com/?q=<?php echo urlencode($site_address); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2 bg-stone/70 hover:bg-stone text-charcoal text-xs font-semibold rounded-full border border-border transition-colors shrink-0">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        <span>Mở Trên Google Maps</span>
                    </a>
                </div>

                <!-- Google Maps Embed Container -->
                <div class="w-full aspect-[16/7] min-h-[320px] rounded-2xl overflow-hidden border border-border relative bg-stone">
                    <iframe
                        title="Bản đồ vị trí BatDongSanHaNoi"
                        src="https://maps.google.com/maps?q=<?php echo urlencode($site_address); ?>&t=&z=14&ie=UTF8&iwloc=&output=embed"
                        class="w-full h-full border-0 absolute inset-0"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </section>

        <!-- Section: Câu Hỏi Thường Gặp (FAQ) -->
        <section class="mt-14 max-w-4xl mx-auto">
            <div class="text-center mb-8">
                <span class="text-xs font-semibold uppercase tracking-wider text-primary">Giải Đáp Nhanh</span>
                <h2 class="text-2xl font-bold text-charcoal tracking-tight m-0 mt-1">
                    Câu Hỏi Thường Gặp Khi Liên Hệ
                </h2>
            </div>

            <div class="space-y-4">
                <details class="group bg-white rounded-2xl border border-border p-5 transition-all">
                    <summary class="font-semibold text-sm sm:text-base text-charcoal cursor-pointer flex items-center justify-between list-none">
                        <span>Tôi có phải trả phí khi liên hệ tư vấn và khảo sát nhà đất không?</span>
                        <svg class="w-4 h-4 text-charcoal-muted group-open:rotate-180 transition-transform duration-200 shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    </summary>
                    <p class="text-xs sm:text-sm text-charcoal-muted mt-3 leading-relaxed m-0 border-t border-border-light pt-3">
                        Hoàn toàn <strong>KHÔNG</strong>. Mọi hoạt động tiếp nhận yêu cầu, tư vấn thông tin quy hoạch, phân tích tài chính và đưa đón khách khảo sát thực địa đều hoàn toàn miễn phí 100%. Quý khách chỉ thanh toán giao dịch khi ký kết hợp đồng công chứng hợp pháp.
                    </p>
                </details>

                <details class="group bg-white rounded-2xl border border-border p-5 transition-all">
                    <summary class="font-semibold text-sm sm:text-base text-charcoal cursor-pointer flex items-center justify-between list-none">
                        <span>Tôi muốn ký gửi bán căn hộ hoặc nhà phố thì thủ tục như thế nào?</span>
                        <svg class="w-4 h-4 text-charcoal-muted group-open:rotate-180 transition-transform duration-200 shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    </summary>
                    <p class="text-xs sm:text-sm text-charcoal-muted mt-3 leading-relaxed m-0 border-t border-border-light pt-3">
                        Quý khách chỉ cần để lại thông tin số điện thoại hoặc liên hệ trực tiếp qua Hotline/Zalo. Chuyên viên sẽ liên hệ khảo sát thực tế, thẩm định mức giá bán phù hợp nhất và tiến hành chụp ảnh, quay video để đăng tải quảng bá tới mạng lưới hàng ngàn khách mua tiềm năng.
                    </p>
                </details>

                <details class="group bg-white rounded-2xl border border-border p-5 transition-all">
                    <summary class="font-semibold text-sm sm:text-base text-charcoal cursor-pointer flex items-center justify-between list-none">
                        <span>Thời gian hỗ trợ xem nhà ngoài giờ hành chính có được không?</span>
                        <svg class="w-4 h-4 text-charcoal-muted group-open:rotate-180 transition-transform duration-200 shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    </summary>
                    <p class="text-xs sm:text-sm text-charcoal-muted mt-3 leading-relaxed m-0 border-t border-border-light pt-3">
                        Có. Đội ngũ chuyên viên sẵn sàng sắp xếp lịch khảo sát nhà vào buổi tối hoặc các ngày cuối tuần (Thứ Bảy, Chủ Nhật) theo khung giờ thuận tiện nhất của Quý khách.
                    </p>
                </details>
            </div>
        </section>

    </div>
</main>

<?php
get_footer();
