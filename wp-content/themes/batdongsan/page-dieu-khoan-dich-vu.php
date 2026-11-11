<?php
/**
 * Template Name: Điều Khoản Dịch Vụ
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

$breadcrumb_parts = [
    [
        'label' => 'Trang chủ',
        'url'   => home_url('/'),
    ],
    [
        'label' => 'Điều khoản dịch vụ',
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
            'current_label'    => 'Điều khoản dịch vụ',
            'prefix_class'     => 'terms-page',
        ]);
        ?>

        <!-- Page Header -->
        <header class="mt-4 mb-10 max-w-4xl">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary-light border border-primary/15 text-primary text-xs font-semibold uppercase tracking-wider mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                <span>Thỏa Thuận Sử Dụng Nền Tảng</span>
            </div>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-charcoal tracking-tight m-0 mb-3">
                Điều Khoản Sử Dụng Dịch Vụ
            </h1>
            <p class="text-charcoal-muted text-sm sm:text-base leading-relaxed m-0">
                Chào mừng Quý khách đến với <strong><?php echo esc_html($site_name); ?></strong>. Xin vui lòng đọc kỹ các điều khoản dưới đây trước khi tra cứu dữ liệu hoặc đăng ký dịch vụ tư vấn của chúng tôi.
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

                <!-- Alert Nguyên Tắc -->
                <div class="p-5 rounded-2xl bg-stone/50 border border-border flex items-start gap-3.5">
                    <svg class="w-6 h-6 text-primary shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <div class="text-xs sm:text-sm text-charcoal/90 leading-relaxed">
                        <strong class="font-semibold text-charcoal block mb-1">Nguyên tắc hợp tác văn minh:</strong>
                        Khi truy cập, tra cứu thông tin bất động sản hoặc gửi form liên hệ qua website, Quý khách được xem là đã hiểu, đồng ý và tuân thủ toàn bộ các điều khoản và quy định được nêu rõ dưới đây.
                    </div>
                </div>

                <!-- Điều 1 -->
                <section id="dieu-1" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">1</span>
                        <span>Chấp Thuận Các Điều Khoản</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed mb-3">
                        Website <strong><?php echo esc_html($site_name); ?></strong> cung cấp cổng thông tin, danh mục bất động sản, dự án quy hoạch và dịch vụ tư vấn môi giới tại khu vực Hà Nội. Việc Quý khách sử dụng website đồng nghĩa với việc Quý khách chấp thuận bị ràng buộc bởi các điều khoản này. Nếu không đồng ý với bất kỳ phần nào, Quý khách vui lòng ngừng truy cập website.
                    </p>
                </section>

                <!-- Điều 2 -->
                <section id="dieu-2" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">2</span>
                        <span>Tính Chất &amp; Phạm Vi Thông Tin Đăng Tải</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed mb-3">
                        Mọi thông tin về mặt bằng căn hộ, tiến độ xây dựng, hình ảnh phối cảnh và chính sách bán hàng của các dự án được đăng tải trên website đều được tổng hợp từ nguồn chính thống của chủ đầu tư và khảo sát thực tế:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-xs sm:text-sm text-charcoal/80 leading-relaxed">
                        <li>Thông tin dự án và bảng giá mang tính chất tham khảo định hướng thị trường tại thời điểm công bố. Giá bán chính thức và giỏ hàng có thể thay đổi tùy thuộc vào chính sách kinh doanh của chủ đầu tư hoặc thỏa thuận trực tiếp giữa các bên mua - bán.</li>
                        <li>Thông tin quy hoạch và hạ tầng giao thông được đối chiếu với đồ án quy hoạch của cơ quan nhà nước có thẩm quyền nhưng có thể được điều chỉnh theo quy hoạch phân khu mới nhất của thành phố Hà Nội.</li>
                    </ul>
                </section>

                <!-- Điều 3 -->
                <section id="dieu-3" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">3</span>
                        <span>Quyền &amp; Trách Nhiệm Của Người Dùng</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed mb-3">
                        Người dùng khi tương tác trên website cam kết:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-xs sm:text-sm text-charcoal/80 leading-relaxed">
                        <li>Cung cấp thông tin liên hệ (họ tên, số điện thoại, nhu cầu) chính xác, trung thực khi gửi yêu cầu nhận tư vấn hoặc ký gửi nhà đất.</li>
                        <li>Không sử dụng bất kỳ công cụ bot, sâu bọ, crawl dữ liệu tự động hoặc phương thức tấn công từ chối dịch vụ (DDoS) gây gián đoạn hoạt động của website.</li>
                        <li>Không mạo danh cá nhân hoặc tổ chức khác để đăng ký thông tin giả mạo hoặc truyền bá thông tin sai lệch về dự án bất động sản.</li>
                    </ul>
                </section>

                <!-- Điều 4 -->
                <section id="dieu-4" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">4</span>
                        <span>Quyền Sở Hữu Trí Tuệ</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed">
                        Toàn bộ nội dung bài viết, phân tích thị trường, hình ảnh chụp thực địa, giao diện thiết kế, mã nguồn và logo thương hiệu hiển thị trên website thuộc quyền sở hữu trí tuệ hợp pháp của <strong><?php echo esc_html($site_name); ?></strong> hoặc đối tác cấp phép. Nghiêm cấm sao chép, trích dẫn mà không dẫn nguồn hoặc sử dụng vào mục đích thương mại khi chưa có sự chấp thuận bằng văn bản của chúng tôi.
                    </p>
                </section>

                <!-- Điều 5 -->
                <section id="dieu-5" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">5</span>
                        <span>Quy Định Về Tư Vấn &amp; Giao Dịch</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed mb-3">
                        Quá trình tư vấn và kết nối giao dịch bất động sản được thực hiện theo nguyên tắc:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-xs sm:text-sm text-charcoal/80 leading-relaxed">
                        <li>Hoạt động tư vấn, dẫn xem nhà và đối soát quy hoạch là hoàn toàn miễn phí cho khách hàng tìm mua/thuê.</li>
                        <li>Mọi cam kết đặt cọc, chuyển nhượng quyền sử dụng đất hoặc quyền sở hữu căn hộ chỉ có giá trị pháp lý khi được xác lập bằng văn bản hợp đồng công chứng theo đúng trình tự pháp luật Việt Nam.</li>
                        <li>Chúng tôi luôn khuyến nghị khách hàng kiểm tra kỹ lưỡng bản gốc giấy chứng nhận quyền sử dụng đất (sổ đỏ/sổ hồng), giấy phép xây dựng và hồ sơ dự án trước khi thực hiện giao dịch chuyển tiền.</li>
                    </ul>
                </section>

                <!-- Điều 6 -->
                <section id="dieu-6" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">6</span>
                        <span>Giới Hạn Trách Nhiệm Pháp Lý</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed mb-3">
                        Trong phạm vi cho phép của pháp luật, chúng tôi không chịu trách nhiệm đối với:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 text-xs sm:text-sm text-charcoal/80 leading-relaxed">
                        <li>Các quyết định tài chính hoặc đầu tư độc lập của người dùng mà không thông qua quy trình thẩm định thực địa và tư vấn pháp lý chính thức.</li>
                        <li>Sự chậm trễ bàn giao nhà hoặc thay đổi đơn phương về tiện ích từ phía chủ đầu tư dự án do yếu tố bất khả kháng hoặc quyết định của cơ quan nhà nước.</li>
                        <li>Sự gián đoạn tạm thời của đường truyền mạng viễn thông máy chủ nằm ngoài tầm kiểm soát hợp lý của chúng tôi.</li>
                    </ul>
                </section>

                <!-- Điều 7 -->
                <section id="dieu-7" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">7</span>
                        <span>Quy Trình Tiếp Nhận &amp; Xử Lý Khiếu Nại</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed mb-3">
                        Khi phát sinh bất kỳ tranh chấp hoặc phản ánh nào liên quan đến dịch vụ tư vấn của chuyên viên, chúng tôi áp dụng quy trình hòa giải 3 bước:
                    </p>
                    <ol class="list-decimal pl-5 space-y-2 text-xs sm:text-sm text-charcoal/80 leading-relaxed">
                        <li><strong>Bước 1:</strong> Tiếp nhận thông tin phản ánh qua Hotline hoặc Email chính thức trong vòng 24 giờ.</li>
                        <li><strong>Bước 2:</strong> Đối chiếu biên bản làm việc, hồ sơ giao dịch và liên hệ các bên liên quan để tìm phương án khắc phục thỏa đáng.</li>
                        <li><strong>Bước 3:</strong> Trường hợp không đạt được sự thỏa thuận qua thương lượng, vụ việc sẽ được đưa ra Tòa án có thẩm quyền tại Hà Nội để giải quyết theo quy định của pháp luật.</li>
                    </ol>
                </section>

                <!-- Điều 8 -->
                <section id="dieu-8" class="scroll-mt-28">
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal tracking-tight m-0 mb-3 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-stone text-primary text-sm font-bold flex items-center justify-center shrink-0">8</span>
                        <span>Sửa Đổi &amp; Hiệu Lực Thi Hành</span>
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal/80 leading-relaxed">
                        Chúng tôi có quyền sửa đổi, bổ sung nội dung của Điều khoản dịch vụ này vào bất kỳ lúc nào để phù hợp với quy định mới của pháp luật và thực tiễn hoạt động. Các thay đổi sẽ có hiệu lực ngay khi được đăng tải trên website. Việc Quý khách tiếp tục sử dụng website sau khi các thay đổi được công bố đồng nghĩa với việc Quý khách chấp thuận toàn bộ điều khoản sửa đổi đó.
                    </p>
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
                        <span>Mục Lục Điều Khoản</span>
                    </h3>
                    <nav class="space-y-2 text-xs sm:text-sm">
                        <a href="#dieu-1" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">1. Chấp thuận điều khoản</a>
                        <a href="#dieu-2" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">2. Tính chất thông tin đăng tải</a>
                        <a href="#dieu-3" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">3. Quyền &amp; trách nhiệm người dùng</a>
                        <a href="#dieu-4" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">4. Quyền sở hữu trí tuệ</a>
                        <a href="#dieu-5" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">5. Quy định tư vấn &amp; giao dịch</a>
                        <a href="#dieu-6" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">6. Giới hạn trách nhiệm pháp lý</a>
                        <a href="#dieu-7" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">7. Giải quyết khiếu nại</a>
                        <a href="#dieu-8" class="block py-1 text-charcoal/70 hover:text-primary transition-colors hover:translate-x-1 duration-150">8. Sửa đổi &amp; hiệu lực</a>
                    </nav>
                </div>

                <!-- Support Contact Box -->
                <div class="bg-primary/5 rounded-3xl border border-primary/15 p-6">
                    <h3 class="text-sm font-bold text-charcoal uppercase tracking-wider m-0 mb-2">
                        Tư Vấn Pháp Lý BĐS
                    </h3>
                    <p class="text-xs text-charcoal-muted leading-relaxed m-0 mb-4">
                        Quý khách cần hỗ trợ kiểm tra quy hoạch, giải đáp về tính pháp lý sổ đỏ trước khi đặt cọc?
                    </p>
                    <a href="tel:<?php echo esc_attr($site_hotline_raw); ?>" class="w-full py-2.5 px-4 bg-primary hover:bg-primary-hover text-white text-xs font-semibold rounded-full shadow-button flex items-center justify-center gap-2 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H3.625A2.25 2.25 0 001.375 4.5v2.25z" /></svg>
                        <span>Hotline: <?php echo esc_html($site_hotline); ?></span>
                    </a>
                </div>

            </aside>

        </div>

    </div>
</main>

<?php
get_footer();
