<?php
/**
 * Template Name: Chính Sách Bảo Mật
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$site_name        = get_bloginfo('name');
$site_hotline     = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_hotline', '0934.69.9191') : '0934.69.9191';
$site_hotline_raw = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_hotline_raw', '0934699191') : '0934699191';
$site_email       = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_email', 'saleso@batdongsanhanoi.net.vn') : 'saleso@batdongsanhanoi.net.vn';
$site_address     = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_address', 'Số 68 Cầu Giấy, Phường Quan Hoa, Quận Cầu Giấy, Hà Nội') : 'Số 68 Cầu Giấy, Phường Quan Hoa, Quận Cầu Giấy, Hà Nội';

$breadcrumb_parts = [
    [
        'label' => 'Trang chủ',
        'url'   => home_url('/'),
    ],
    [
        'label' => 'Chính sách bảo mật',
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
            'current_label'    => 'Chính sách bảo mật',
            'prefix_class'     => 'privacy-page',
        ]);
        ?>

        <!-- Page Header -->
        <header class="mt-4 mb-10 max-w-4xl">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary-light border border-primary/15 text-primary text-xs font-semibold uppercase tracking-wider mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                <span>An Toàn &amp; Bảo Mật Dữ Liệu</span>
            </div>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-charcoal tracking-tight m-0 mb-3">
                Chính Sách Bảo Mật Thông Tin
            </h1>
            <p class="text-charcoal-muted text-sm sm:text-base leading-relaxed m-0">
                Quy định về việc bảo vệ quyền riêng tư và dữ liệu cá nhân của Quý khách hàng tại <strong><?php echo esc_html($site_name); ?></strong> theo tiêu chuẩn pháp luật Việt Nam (Nghị định 13/2023/NĐ-CP).
            </p>
            <div class="flex items-center gap-4 text-xs text-charcoal-muted mt-4 pt-4 border-t border-border-light">
                <span>Hiệu lực từ: <strong>01/01/2026</strong></span>
                <span>&bull;</span>
                <span>Cập nhật lần cuối: <strong><?php echo esc_html(get_the_modified_date('d/m/Y') ?: date('d/m/Y')); ?></strong></span>
            </div>
        </header>

        <!-- Layout Grid: Nội Dung & Mục Lục Sticky -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">

            <!-- Main Content Area (8 Cols) -->
            <article class="lg:col-span-8 bg-white rounded-3xl border border-border shadow-card p-6 sm:p-10 lg:p-12 space-y-10">

                <!-- Alert Cam Kết -->
                <div class="p-5 rounded-2xl bg-primary-light/50 border border-primary/15 flex items-start gap-3.5">
                    <svg class="w-6 h-6 text-primary shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    <div class="text-xs sm:text-sm text-charcoal/90 leading-relaxed">
                        <strong class="font-semibold text-charcoal block mb-1">Cam kết từ BatDongSanHaNoi:</strong>
                        Chúng tôi tôn trọng và cam kết bảo vệ dữ liệu riêng tư của Quý khách. Mọi thông tin cung cấp qua website đều được mã hóa, lưu trữ nghiêm ngặt và không bao giờ chuyển giao cho bất kỳ bên thứ ba nào vì mục đích thương mại ngoài phạm vi dịch vụ bất động sản mà Quý khách yêu cầu.
                    </div>
                </div>

                <!-- Điều 1 -->
                <section id="dieu-1" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">1</span>
                        <span>Mục Đích Thu Thập Dữ Liệu</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed mb-3">
                        <?php echo esc_html($site_name); ?> thu thập dữ liệu cá nhân của người dùng chỉ phục vụ cho các mục đích hợp pháp sau:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-xs sm:text-sm text-charcoal/80 leading-relaxed">
                        <li>Tiếp nhận và phản hồi các yêu cầu tư vấn, giải đáp thắc mắc về các dự án bất động sản và nhà đất thổ cư.</li>
                        <li>Cung cấp bảng giá chi tiết, chính sách bán hàng mới nhất, hồ sơ pháp lý và tài liệu thiết kế mặt bằng dự án.</li>
                        <li>Kết nối khách hàng với chuyên viên tư vấn phụ trách khu vực để sắp xếp lịch hẹn khảo sát thực địa.</li>
                        <li>Hỗ trợ hoàn thiện các thủ tục đặt cọc, đàm phán hợp đồng mua bán và thủ tục công chứng sang tên sổ đỏ.</li>
                        <li>Gửi bản tin cập nhật thị trường, diễn biến lãi suất ngân hàng (khi khách hàng chủ động đăng ký nhận tin).</li>
                    </ul>
                </section>

                <!-- Điều 2 -->
                <section id="dieu-2" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">2</span>
                        <span>Phạm Vi Dữ Liệu Thu Thập</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed mb-3">
                        Các thông tin cá nhân chúng tôi có thể thu thập khi Quý khách tương tác với biểu mẫu hoặc liên hệ qua nền tảng gồm:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-xs sm:text-sm text-charcoal/80 leading-relaxed">
                        <li><strong>Thông tin định danh:</strong> Họ và tên, số điện thoại liên lạc, địa chỉ email.</li>
                        <li><strong>Thông tin nhu cầu:</strong> Loại hình bất động sản quan tâm (căn hộ, nhà phố, biệt thự), khoảng tài chính, khu vực mong muốn tìm kiếm.</li>
                        <li><strong>Dữ liệu kỹ thuật tự động:</strong> Địa chỉ IP ẩn danh, loại trình duyệt, thời gian truy cập, hành vi click xem dự án (thông qua Google Analytics và Cookies ẩn danh) nhằm tối ưu tốc độ và trải nghiệm duyệt web.</li>
                    </ul>
                </section>

                <!-- Điều 3 -->
                <section id="dieu-3" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">3</span>
                        <span>Thời Gian Lưu Trữ Thông Tin</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed">
                        Thông tin cá nhân của Quý khách sẽ được lưu trữ an toàn trong hệ thống dữ liệu khách hàng nội bộ của chúng tôi cho đến khi hoàn thành mục đích tư vấn hoặc khi có yêu cầu huỷ bỏ, cập nhật từ phía khách hàng qua Hotline hoặc Email hỗ trợ.
                    </p>
                </section>

                <!-- Điều 4 -->
                <section id="dieu-4" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">4</span>
                        <span>Những Bên Có Thể Tiếp Cận Dữ Liệu</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed mb-3">
                        Chúng tôi tuyệt đối <strong>không bán, chia sẻ hoặc cho thuê</strong> thông tin cá nhân của Quý khách cho bên thứ ba vì bất kỳ mục đích quảng cáo thương mại không mong muốn nào. Thông tin chỉ được tiếp cận bởi:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-xs sm:text-sm text-charcoal/80 leading-relaxed">
                        <li>Ban quản trị và Chuyên viên tư vấn được phân công xử lý trực tiếp giao dịch của Quý khách.</li>
                        <li>Các đối tác phòng công chứng, văn phòng luật sư hoặc ngân hàng bảo lãnh (chỉ khi có sự đồng thuận cụ thể từ Quý khách trong quá trình hoàn thiện hồ sơ mua bán).</li>
                        <li>Cơ quan nhà nước có thẩm quyền trong các trường hợp bắt buộc theo quy định của pháp luật Việt Nam.</li>
                    </ul>
                </section>

                <!-- Điều 5 -->
                <section id="dieu-5" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">5</span>
                        <span>Quyền Của Khách Hàng Đối Với Dữ Liệu</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed mb-3">
                        Căn cứ Nghị định 13/2023/NĐ-CP, Quý khách luôn có toàn quyền đối với dữ liệu của mình:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-xs sm:text-sm text-charcoal/80 leading-relaxed">
                        <li>Quyền yêu cầu xem lại, chỉnh sửa hoặc bổ sung thông tin cá nhân đã cung cấp.</li>
                        <li>Quyền yêu cầu xóa bỏ hoàn toàn dữ liệu khỏi hệ thống tư vấn của chúng tôi.</li>
                        <li>Quyền từ chối tiếp nhận các cuộc gọi, tin nhắn thông báo hoặc email bản tin bất kỳ lúc nào.</li>
                    </ul>
                </section>

                <!-- Điều 6 -->
                <section id="dieu-6" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">6</span>
                        <span>Cam Kết An Toàn &amp; Bảo Mật Dữ Liệu</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed">
                        Hệ thống website của chúng tôi ứng dụng chứng chỉ mã hóa dữ liệu SSL 256-bit chuẩn quốc tế, tường lửa bảo mật máy chủ và cơ chế xác thực bảo mật nhiều lớp. Khi Quý khách gửi biểu mẫu, toàn bộ dữ liệu truyền tải đều được mã hóa nhằm ngăn chặn hành vi can thiệp trái phép.
                    </p>
                </section>

                <!-- Điều 7 -->
                <section id="dieu-7" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">7</span>
                        <span>Chính Sách Sử Dụng Cookies</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed">
                        Website sử dụng cookies để ghi nhớ tùy chọn bộ lọc tìm kiếm (khoảng giá, khu vực, loại hình) nhằm nâng cao tốc độ tải và tiện ích cho Quý khách ở những lần truy cập sau. Quý khách có thể tùy ý cấu hình chặn hoặc xóa cookies trong cài đặt trình duyệt của mình.
                    </p>
                </section>

                <!-- Điều 8 -->
                <section id="dieu-8" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">8</span>
                        <span>Thông Tin Đơn Vị Quản Lý Dữ Liệu</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed mb-4">
                        Nếu Quý khách có bất kỳ câu hỏi, khiếu nại hoặc muốn thực hiện quyền thay đổi dữ liệu cá nhân, vui lòng liên hệ trực tiếp với bộ phận tiếp nhận thông tin:
                    </p>
                    <div class="p-5 rounded-2xl bg-stone/40 border border-border text-xs sm:text-sm space-y-2">
                        <p class="m-0 font-bold text-charcoal">Đơn vị quản lý: <?php echo esc_html($site_name); ?></p>
                        <p class="m-0 text-charcoal/80">Địa chỉ: <?php echo esc_html($site_address); ?></p>
                        <p class="m-0 text-charcoal/80">Hotline: <a href="tel:<?php echo esc_attr($site_hotline_raw); ?>" class="text-primary font-semibold hover:underline"><?php echo esc_html($site_hotline); ?></a></p>
                        <p class="m-0 text-charcoal/80">Email: <a href="mailto:<?php echo esc_attr($site_email); ?>" class="text-primary font-semibold hover:underline"><?php echo esc_html($site_email); ?></a></p>
                    </div>
                </section>

                <!-- Editor Content fallback nếu có -->
                <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                    <?php if (get_the_content()) : ?>
                        <div class="pt-6 border-t border-border-light prose prose-slate max-w-none text-charcoal/90 leading-relaxed">
                            <?php the_content(); ?>
                        </div>
                    <?php endif; ?>
                <?php endwhile; endif; ?>

            </article>

            <!-- Sticky Sidebar: Table of Contents & Support (4 Cols) -->
            <aside class="lg:col-span-4 space-y-6 lg:sticky lg:top-24">
                
                <!-- Table of Contents -->
                <div class="bg-white rounded-3xl border border-border shadow-card p-6">
                    <h3 class="text-sm font-bold text-charcoal uppercase tracking-wider m-0 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <span>Mục Lục Chính Sách</span>
                    </h3>
                    <nav class="space-y-2 text-xs sm:text-sm">
                        <a href="#dieu-1" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">1. Mục đích thu thập dữ liệu</a>
                        <a href="#dieu-2" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">2. Phạm vi dữ liệu thu thập</a>
                        <a href="#dieu-3" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">3. Thời gian lưu trữ dữ liệu</a>
                        <a href="#dieu-4" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">4. Các bên tiếp cận thông tin</a>
                        <a href="#dieu-5" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">5. Quyền của khách hàng</a>
                        <a href="#dieu-6" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">6. Cam kết bảo mật an toàn</a>
                        <a href="#dieu-7" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">7. Chính sách Cookies</a>
                        <a href="#dieu-8" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">8. Đơn vị quản lý dữ liệu</a>
                    </nav>
                </div>

                <!-- Support Contact Box -->
                <div class="bg-primary/5 rounded-3xl border border-primary/15 p-6">
                    <h3 class="text-sm font-bold text-charcoal uppercase tracking-wider m-0 mb-2">
                        Hỗ Trợ Quyền Riêng Tư
                    </h3>
                    <p class="text-xs text-charcoal-muted leading-relaxed m-0 mb-4">
                        Nếu Quý khách có yêu cầu xóa hoặc chỉnh sửa dữ liệu đã cung cấp, vui lòng liên hệ ngay với chúng tôi:
                    </p>
                    <a href="tel:<?php echo esc_attr($site_hotline_raw); ?>" class="w-full py-2.5 px-4 bg-primary hover:bg-primary-hover text-white text-xs font-semibold rounded-full shadow-button flex items-center justify-center gap-2 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H3.625A2.25 2.25 0 001.375 4.5v2.25z" /></svg>
                        <span>Gọi: <?php echo esc_html($site_hotline); ?></span>
                    </a>
                </div>

            </aside>

        </div>

    </div>
</main>

<?php
get_footer();
