<?php
/**
 * Template Name: Trang Giới Thiệu
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$site_hotline     = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_hotline', '0934.69.9191') : '0934.69.9191';
$site_hotline_raw = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_hotline_raw', '0934699191') : '0934699191';

$breadcrumb_parts = [
    [
        'label' => 'Trang chủ',
        'url'   => home_url('/'),
    ],
    [
        'label' => 'Giới thiệu',
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
            'current_label'    => 'Giới thiệu',
            'prefix_class'     => 'about-page',
        ]);
        ?>

        <!-- Hero Section Header -->
        <section class="mt-4 mb-12 sm:mb-16 text-center max-w-4xl mx-auto">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary-light border border-primary/15 text-primary text-xs font-semibold uppercase tracking-wider mb-4">
                <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                <span>Về BatDongSanHaNoi</span>
            </div>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-charcoal tracking-tight m-0 mb-5 leading-tight">
                Cổng Thông Tin &amp; Tư Vấn Bất Động Sản Uy Tín Hàng Đầu Thủ Đô
            </h1>
            <p class="text-charcoal-muted text-base sm:text-lg leading-relaxed m-0 max-w-3xl mx-auto">
                Chúng tôi kết nối những giá trị thực của thị trường bất động sản Hà Nội đến từng khách hàng thông qua dữ liệu quy hoạch chuẩn xác, định giá độc lập và đội ngũ chuyên gia tận tâm.
            </p>
        </section>

        <!-- Stats Numbers Strip -->
        <section class="mb-14 sm:mb-20">
            <div class="bg-white rounded-3xl border border-border shadow-card p-6 sm:p-10">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 text-center divide-y lg:divide-y-0 lg:divide-x divide-border">
                    <div class="pt-4 lg:pt-0">
                        <span class="block text-3xl sm:text-4xl lg:text-5xl font-extrabold text-primary tracking-tight font-sans">
                            10+
                        </span>
                        <span class="block text-xs sm:text-sm font-semibold text-charcoal mt-2 uppercase tracking-wider">
                            Năm Kinh Nghiệm
                        </span>
                        <span class="text-xs text-charcoal-muted mt-1 block">Thấu hiểu từng cung đường và quy hoạch Hà Nội</span>
                    </div>

                    <div class="pt-4 lg:pt-0 lg:pl-6">
                        <span class="block text-3xl sm:text-4xl lg:text-5xl font-extrabold text-charcoal tracking-tight font-sans">
                            5.000+
                        </span>
                        <span class="block text-xs sm:text-sm font-semibold text-charcoal mt-2 uppercase tracking-wider">
                            Giao Dịch Thành Công
                        </span>
                        <span class="text-xs text-charcoal-muted mt-1 block">Khách hàng trao gửi niềm tin an cư &amp; đầu tư</span>
                    </div>

                    <div class="pt-4 lg:pt-0 lg:pl-6">
                        <span class="block text-3xl sm:text-4xl lg:text-5xl font-extrabold text-primary tracking-tight font-sans">
                            100+
                        </span>
                        <span class="block text-xs sm:text-sm font-semibold text-charcoal mt-2 uppercase tracking-wider">
                            Dự Án Đối Tác
                        </span>
                        <span class="text-xs text-charcoal-muted mt-1 block">Hợp tác cùng các tập đoàn BĐS hàng đầu</span>
                    </div>

                    <div class="pt-4 lg:pt-0 lg:pl-6">
                        <span class="block text-3xl sm:text-4xl lg:text-5xl font-extrabold text-charcoal tracking-tight font-sans">
                            98%
                        </span>
                        <span class="block text-xs sm:text-sm font-semibold text-charcoal mt-2 uppercase tracking-wider">
                            Độ Hài Lòng
                        </span>
                        <span class="text-xs text-charcoal-muted mt-1 block">Khách hàng sẵn sàng đồng hành lâu dài</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Vision & Mission (Tầm nhìn & Sứ mệnh) -->
        <section class="mb-14 sm:mb-20">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Tầm nhìn -->
                <div class="bg-white rounded-3xl border border-border shadow-card p-8 sm:p-10 relative overflow-hidden group hover:border-primary/30 transition-all duration-300">
                    <div class="w-12 h-12 rounded-2xl bg-primary-light text-primary flex items-center justify-center mb-6 border border-primary/15">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-wider text-primary">Tầm Nhìn Chiến Lược</span>
                    <h2 class="text-2xl font-bold text-charcoal tracking-tight m-0 mt-2 mb-4">
                        Kiến Tạo Chuẩn Mực Minh Bạch Cho Thị Trường Bất Động Sản
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal-muted leading-relaxed m-0">
                        BatDongSanHaNoi hướng tới trở thành hệ sinh thái tư vấn và phân phối bất động sản đáng tin cậy nhất tại Thủ Đô, nơi mọi thông tin quy hoạch hạ tầng, tiến độ dự án và giá bán được kiểm chứng rõ ràng, mang lại sự yên tâm tuyệt đối cho khách hàng và nhà đầu tư.
                    </p>
                </div>

                <!-- Sứ mệnh -->
                <div class="bg-white rounded-3xl border border-border shadow-card p-8 sm:p-10 relative overflow-hidden group hover:border-primary/30 transition-all duration-300">
                    <div class="w-12 h-12 rounded-2xl bg-gold-tint text-gold flex items-center justify-center mb-6 border border-gold/20">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-wider text-gold">Sứ Mệnh Phát Triển</span>
                    <h2 class="text-2xl font-bold text-charcoal tracking-tight m-0 mt-2 mb-4">
                        Đồng Hành Cùng Khách Hàng Xây Đắp Tổ Ấm &amp; Thịnh Vượng
                    </h2>
                    <p class="text-sm sm:text-base text-charcoal-muted leading-relaxed m-0">
                        Chúng tôi cam kết không chỉ bán một bất động sản, mà là tư vấn một giải pháp tài chính an toàn và một chốn an cư trọn vẹn. Chúng tôi đặt lợi ích dài lâu của Quý khách lên hàng đầu bằng sự tận tâm, trung thực và trách nhiệm cao nhất.
                    </p>
                </div>
            </div>
        </section>

        <!-- Giá trị cốt lõi (4 Pillars) -->
        <section class="mb-14 sm:mb-20">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <span class="text-xs font-semibold uppercase tracking-wider text-primary">Giá Trị Cốt Lõi</span>
                <h2 class="text-2xl sm:text-3xl font-bold text-charcoal tracking-tight m-0 mt-1">
                    4 Trụ Cột Vững Chắc Tạo Nên Uy Tín
                </h2>
                <p class="text-sm text-charcoal-muted mt-2 leading-relaxed m-0">
                    Mọi hành động và quyết định của chúng tôi đều được định hướng bởi 4 giá trị bất biến
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Cột 1 -->
                <div class="bg-white rounded-3xl border border-border p-6 shadow-subtle hover:shadow-card hover:-translate-y-1 transition-all duration-300">
                    <div class="w-10 h-10 rounded-2xl bg-stone text-charcoal flex items-center justify-center mb-4 font-bold text-primary">
                        01
                    </div>
                    <h3 class="text-base font-bold text-charcoal tracking-tight m-0 mb-2">Minh Bạch</h3>
                    <p class="text-xs sm:text-sm text-charcoal-muted leading-relaxed m-0">
                        Đối soát kỹ lưỡng hồ sơ pháp lý, tình trạng sổ đỏ và quy hoạch chi tiết 1/500 trước khi tư vấn tới khách hàng.
                    </p>
                </div>

                <!-- Cột 2 -->
                <div class="bg-white rounded-3xl border border-border p-6 shadow-subtle hover:shadow-card hover:-translate-y-1 transition-all duration-300">
                    <div class="w-10 h-10 rounded-2xl bg-stone text-charcoal flex items-center justify-center mb-4 font-bold text-primary">
                        02
                    </div>
                    <h3 class="text-base font-bold text-charcoal tracking-tight m-0 mb-2">Chuyên Nghiệp</h3>
                    <p class="text-xs sm:text-sm text-charcoal-muted leading-relaxed m-0">
                        Đội ngũ am hiểu sâu sắc quy hoạch giao thông, biến động đơn giá từng tuyến phố và thị trường chuyển nhượng.
                    </p>
                </div>

                <!-- Cột 3 -->
                <div class="bg-white rounded-3xl border border-border p-6 shadow-subtle hover:shadow-card hover:-translate-y-1 transition-all duration-300">
                    <div class="w-10 h-10 rounded-2xl bg-stone text-charcoal flex items-center justify-center mb-4 font-bold text-primary">
                        03
                    </div>
                    <h3 class="text-base font-bold text-charcoal tracking-tight m-0 mb-2">Tận Tâm</h3>
                    <p class="text-xs sm:text-sm text-charcoal-muted leading-relaxed m-0">
                        Lắng nghe nguyện vọng thực tế, tối ưu ngân sách tài chính và hỗ trợ xem nhà không giới hạn số lần khảo sát.
                    </p>
                </div>

                <!-- Cột 4 -->
                <div class="bg-white rounded-3xl border border-border p-6 shadow-subtle hover:shadow-card hover:-translate-y-1 transition-all duration-300">
                    <div class="w-10 h-10 rounded-2xl bg-stone text-charcoal flex items-center justify-center mb-4 font-bold text-primary">
                        04
                    </div>
                    <h3 class="text-base font-bold text-charcoal tracking-tight m-0 mb-2">Bền Vững</h3>
                    <p class="text-xs sm:text-sm text-charcoal-muted leading-relaxed m-0">
                        Đồng hành xuyên suốt từ khi tìm kiếm bất động sản tới lúc nhận bàn giao nhà và hoàn tất sang tên sổ đỏ hợp pháp.
                    </p>
                </div>
            </div>
        </section>

        <!-- Hệ sinh thái dịch vụ -->
        <section class="mb-14 sm:mb-20">
            <div class="bg-white rounded-3xl border border-border shadow-card p-8 sm:p-12">
                <div class="text-center max-w-2xl mx-auto mb-10">
                    <span class="text-xs font-semibold uppercase tracking-wider text-primary">Dịch Vụ Của Chúng Tôi</span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-charcoal tracking-tight m-0 mt-1">
                        Hệ Sinh Thái Bất Động Sản Toàn Diện
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 rounded-2xl bg-canvas border border-border/70 hover:border-primary/40 transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-4">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                        </div>
                        <h3 class="text-base font-bold text-charcoal m-0 mb-2">Phân Phối Dự Án F1</h3>
                        <p class="text-xs sm:text-sm text-charcoal-muted m-0 leading-relaxed">
                            Cung cấp quỹ căn ngoại giao, giá gốc và chính sách ưu đãi trực tiếp từ các chủ đầu tư danh tiếng hàng đầu.
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-canvas border border-border/70 hover:border-primary/40 transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-4">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545" /></svg>
                        </div>
                        <h3 class="text-base font-bold text-charcoal m-0 mb-2">Nhà Đất Thổ Cư &amp; Nhà Phố</h3>
                        <p class="text-xs sm:text-sm text-charcoal-muted m-0 leading-relaxed">
                            Quản lý nguồn hàng nhà phố, phân lô ô tô, biệt thự liền kề chính chủ tại khắp các quận nội thành Hà Nội.
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-canvas border border-border/70 hover:border-primary/40 transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-4">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                        </div>
                        <h3 class="text-base font-bold text-charcoal m-0 mb-2">Chuyển Nhượng Căn Hộ</h3>
                        <p class="text-xs sm:text-sm text-charcoal-muted m-0 leading-relaxed">
                            Cập nhật giỏ hàng sang nhượng căn hộ chung cư giá tốt, hỗ trợ làm thủ tục sang tên sổ đỏ nhanh chóng.
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-canvas border border-border/70 hover:border-primary/40 transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-4">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="text-base font-bold text-charcoal m-0 mb-2">Định Giá &amp; Pháp Lý</h3>
                        <p class="text-xs sm:text-sm text-charcoal-muted m-0 leading-relaxed">
                            Thẩm định giá thị trường độc lập, kiểm tra chỉ giới quy hoạch mở đường và giải pháp vay vốn ngân hàng lãi suất tối ưu.
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-canvas border border-border/70 hover:border-primary/40 transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-4">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                        </div>
                        <h3 class="text-base font-bold text-charcoal m-0 mb-2">Ký Gửi Bất Động Sản</h3>
                        <p class="text-xs sm:text-sm text-charcoal-muted m-0 leading-relaxed">
                            Hỗ trợ chủ nhà quay chụp hình ảnh, phân tích khách hàng mục tiêu và đẩy nhanh tiến độ thanh khoản an toàn.
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-canvas border border-border/70 hover:border-primary/40 transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-4">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                        </div>
                        <h3 class="text-base font-bold text-charcoal m-0 mb-2">Cho Thuê &amp; Khai Thác</h3>
                        <p class="text-xs sm:text-sm text-charcoal-muted m-0 leading-relaxed">
                            Tìm kiếm đối tác thuê văn phòng, căn hộ dịch vụ và mặt bằng kinh doanh ổn định, sinh dòng tiền đều đặn.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Post Content from WordPress Editor (if exists) -->
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <?php if (get_the_content()) : ?>
                <section class="bg-white rounded-3xl border border-border shadow-card p-6 sm:p-10 mb-14">
                    <div class="prose prose-slate lg:prose-lg max-w-none text-charcoal/90 leading-relaxed prose-headings:text-charcoal prose-headings:font-bold prose-headings:tracking-tight prose-a:text-primary hover:prose-a:underline">
                        <?php the_content(); ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endwhile; endif; ?>

        <!-- Call to Action Banner -->
        <section class="rounded-3xl bg-charcoal text-white p-8 sm:p-12 relative overflow-hidden">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-primary/20 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
            <div class="relative z-10 max-w-2xl">
                <span class="text-xs uppercase font-semibold tracking-wider text-primary-light">Sẵn Sàng Bắt Đầu</span>
                <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight m-0 mt-2 mb-3">
                    Tìm Kiếm Bất Động Sản Hoặc Nhận Tư Vấn Miễn Phí
                </h2>
                <p class="text-white/70 text-sm sm:text-base leading-relaxed m-0 mb-6">
                    Liên hệ ngay hôm nay để nhận danh sách quỹ căn độc quyền và phân tích quy hoạch chi tiết từ chuyên gia.
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="<?php echo esc_url(home_url('/lien-he/')); ?>" class="px-6 py-3 bg-primary hover:bg-primary-hover text-white text-xs sm:text-sm font-semibold rounded-full shadow-button transition-all">
                        Liên Hệ Tư Vấn Ngay
                    </a>
                    <a href="tel:<?php echo esc_attr($site_hotline_raw); ?>" class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white text-xs sm:text-sm font-semibold rounded-full border border-white/20 transition-colors">
                        Hotline: <?php echo esc_html($site_hotline); ?>
                    </a>
                </div>
            </div>
        </section>

    </div>
</main>

<?php
get_footer();
