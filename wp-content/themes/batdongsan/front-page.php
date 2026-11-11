<?php
get_header();

// Query dự án thật từ WordPress - tối ưu query
$projects_query = new WP_Query([
    'post_type' => 'du-an',
    'posts_per_page' => 12,
    'post_status' => 'publish',
    'orderby' => 'post_date',
    'order' => 'DESC',
    'no_found_rows' => true,
    'update_post_meta_cache' => true,
    'update_post_term_cache' => false,
]);

// Query bất động sản mới nhất
$bds_query = new WP_Query([
    'post_type' => 'bat-dong-san',
    'posts_per_page' => 8,
    'post_status' => 'publish',
    'orderby' => 'post_date',
    'order' => 'DESC',
    'no_found_rows' => true,
    'update_post_meta_cache' => true,
    'update_post_term_cache' => false,
]);

// Query banners trang chủ
$home_banners_query = new WP_Query([
    'post_type' => 'banner',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'tax_query' => [
        [
            'taxonomy' => 'loai-banner',
            'field' => 'slug',
            'terms' => 'trang-chu',
        ],
    ],
    'orderby' => 'menu_order',
    'order' => 'ASC',
    'no_found_rows' => true,
    'update_post_meta_cache' => true,
    'update_post_term_cache' => false,
]);

$home_banners = [];
if ($home_banners_query->have_posts()) {
    while ($home_banners_query->have_posts()) {
        $home_banners_query->the_post();
        $banner_image_id = get_field('banner_image', get_the_ID());
        if (!$banner_image_id && has_post_thumbnail()) {
            $banner_image_id = get_post_thumbnail_id();
        }
        $home_banners[] = [
            'image' => $banner_image_id,
            'title' => get_the_title(),
            'link' => get_field('banner_link', get_the_ID()),
            'subtitle' => get_the_excerpt() ?: get_post_meta(get_the_ID(), 'banner_subtitle', true),
        ];
    }
    wp_reset_postdata();
}

?>

<main class="bg-canvas text-charcoal min-h-screen">
    <?php
    // Hero Banner Swiper - Cupertino Showcase
    get_template_part('template-parts/hero-banner-swiper', null, [
        'swiper_class' => 'hero-swiper',
        'slide_class' => 'hero',
        'banners' => $home_banners,
        'fallback_query' => $projects_query,
        'fallback_type' => 'project',
        'eyebrow' => 'BẤT ĐỘNG SẢN HÀ NỘI',
        'headline' => 'Không Gian Sống Lý Tưởng. Thông Tin Chuẩn Xác.',
        'subcaption' => 'Khám phá các dự án nổi bật, căn hộ và quỹ nhà đất được cập nhật mới nhất tại Hà Nội.',
        'show_navigation' => true,
        'show_pagination' => true,
    ]);
    ?>

    <?php
    // Unified Search Dock (Apple Spotlight Capsule Style)
    get_template_part('template-parts/search-form-unified', null, [
        'placeholder' => 'Nhập quận huyện, tên dự án hoặc từ khóa (vd: Cầu Giấy, Vinhomes)...',
    ]);
    ?>

    <?php
    // Section Truyền Thông & Nhân Hiệu Sales (TikTok Dọc 9:16 & YouTube Spotlight)
    get_template_part('template-parts/section-sales-media');
    ?>

    <!-- Section 1: Dự án bất động sản nổi bật -->
    <section class="pt-6 sm:pt-8 md:pt-10 pb-12 sm:pb-16 lg:pb-20 bg-canvas">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8 pb-4 border-b border-border/60">
                <div>
                    <span class="text-xs font-semibold tracking-wider uppercase text-charcoal-muted mb-1.5 block">Quy Hoạch & Dự Án</span>
                    <h2 class="font-serif text-2xl md:text-3xl lg:text-4xl font-bold tracking-tight text-charcoal m-0">Dự Án Bất Động Sản Nổi Bật</h2>
                </div>
                <a href="<?php echo esc_url(home_url('/du-an')); ?>" class="group inline-flex items-center gap-1.5 text-primary font-semibold text-sm hover:underline whitespace-nowrap" aria-label="Xem thêm dự án">
                    <span>Xem toàn bộ dự án</span>
                    <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </a>
            </div>

            <?php if ($projects_query->have_posts()) : ?>
                <div class="relative group/carousel w-full">
                    <div id="home-projects-carousel" class="bds-carousel flex items-stretch gap-4 md:gap-5 overflow-x-auto scroll-smooth snap-x snap-mandatory no-scrollbar py-2 -mx-4 px-4 sm:mx-0 sm:px-0">
                        <?php
                        $slide_index = 0;
                        while ($projects_query->have_posts() && $slide_index < 12) : $projects_query->the_post();
                        ?>
                            <div class="snap-start shrink-0 w-[82%] sm:w-[calc(50%-10px)] lg:w-[calc(25%-15px)] flex flex-col h-auto">
                                <?php
                                get_template_part('template-parts/card-project-featured', null, [
                                    'post' => get_post(),
                                ]);
                                ?>
                            </div>
                        <?php
                            $slide_index++;
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                    <div class="hidden md:block">
                        <button type="button" class="absolute top-1/2 lg:-left-6 -translate-y-1/2 z-10 w-11 h-11 rounded-full border border-black/[0.08] bg-white/90 backdrop-blur-md text-charcoal shadow-card flex items-center justify-center cursor-pointer transition-all duration-200 hover:scale-105 hover:bg-white hover:text-primary" data-carousel-prev="#home-projects-carousel" aria-label="Dự án trước">
                            <?php echo batdongsan_icon('arrow-left', ['width' => 18, 'height' => 18]); ?>
                        </button>
                        <button type="button" class="absolute top-1/2 lg:-right-6 -translate-y-1/2 z-10 w-11 h-11 rounded-full border border-black/[0.08] bg-white/90 backdrop-blur-md text-charcoal shadow-card flex items-center justify-center cursor-pointer transition-all duration-200 hover:scale-105 hover:bg-white hover:text-primary" data-carousel-next="#home-projects-carousel" aria-label="Dự án tiếp theo">
                            <?php echo batdongsan_icon('arrow-right', ['width' => 18, 'height' => 18]); ?>
                        </button>
                    </div>
                </div>
            <?php else : ?>
                <p class="text-charcoal-muted text-center py-12">Hiện chưa có dự án nào được cập nhật.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Section 2: Bất động sản dành cho bạn -->
    <section class="py-16 lg:py-20 bg-surface border-t border-b border-border/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8 pb-4 border-b border-border/60">
                <div>
                    <span class="text-xs font-semibold tracking-wider uppercase text-charcoal-muted mb-1.5 block">Danh Sách Mới Nhất</span>
                    <h2 class="text-2xl md:text-3xl lg:text-4xl font-bold tracking-tight text-charcoal m-0">Bất Động Sản Dành Cho Bạn</h2>
                </div>
                <a href="<?php echo esc_url(home_url('/bat-dong-san')); ?>" class="group inline-flex items-center gap-1.5 text-primary font-semibold text-sm hover:underline whitespace-nowrap" aria-label="Xem thêm bất động sản">
                    <span>Xem tất cả bất động sản</span>
                    <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </a>
            </div>
            <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                <?php if ($bds_query->have_posts()) : ?>
                    <?php while ($bds_query->have_posts()) : $bds_query->the_post();
                        get_template_part('template-parts/card-transfer', null, [
                            'post' => get_post(),
                            'show_badge' => false,
                        ]);
                    endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <p class="text-charcoal-muted text-center py-12 col-span-full">Chưa có bất động sản nào trong danh mục này.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Section 3: Đối tác chiến lược & Chủ đầu tư -->
    <section class="py-16 lg:py-20 bg-canvas border-t border-border/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <span class="text-xs font-semibold tracking-wider uppercase text-charcoal-muted mb-1.5 block">Hệ Sinh Thái Đối Tác</span>
                <h2 class="text-2xl md:text-3xl lg:text-4xl font-bold tracking-tight text-charcoal m-0 mb-2">Chủ Đầu Tư & Đối Tác Phát Triển</h2>
                <p class="font-sans text-charcoal-muted text-sm md:text-base m-0">Đồng hành cùng những tập đoàn bất động sản uy tín hàng đầu trên thị trường</p>
            </div>

            <?php
            $partners_query = new WP_Query([
                'post_type' => 'doi-tac',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'no_found_rows' => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => false,
            ]);
            ?>
            <?php if ($partners_query->have_posts()) : ?>
                <div class="partners-swiper swiper relative overflow-hidden px-[50px] pb-10 max-sm:px-10 max-sm:pb-10">
                    <div class="swiper-wrapper items-center">
                        <?php while ($partners_query->have_posts()) : $partners_query->the_post();
                            $logo_id = get_field('logo');
                            $link = get_field('link');
                            $title = get_the_title();
                        ?>
                            <div class="swiper-slide h-auto flex items-center justify-center">
                                <div class="w-full flex items-center justify-center p-6 bg-white rounded-xl border border-border shadow-card transition-all duration-300 hover:-translate-y-1 hover:shadow-card-hover hover:border-primary/40 max-sm:p-4">
                                    <?php if ($link) : ?>
                                        <a href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener" class="group flex items-center justify-center w-full no-underline transition-all duration-300" aria-label="<?php echo esc_attr($title); ?>">
                                            <?php if ($logo_id) : ?>
                                                <?php echo bds_get_optimized_image($logo_id, 'medium', ['class' => 'max-w-full h-auto max-h-16 object-contain grayscale opacity-65 transition-all duration-300 group-hover:grayscale-0 group-hover:opacity-100', 'alt' => esc_attr($title)], true); ?>
                                            <?php else : ?>
                                                <span class="text-sm font-medium text-charcoal mt-2 text-center"><?php echo esc_html($title); ?></span>
                                            <?php endif; ?>
                                        </a>
                                    <?php else : ?>
                                        <div class="flex items-center justify-center w-full">
                                            <?php if ($logo_id) : ?>
                                                <?php echo bds_get_optimized_image($logo_id, 'medium', ['class' => 'max-w-full h-auto max-h-16 object-contain grayscale opacity-65', 'alt' => esc_attr($title)], true); ?>
                                            <?php else : ?>
                                                <span class="text-sm font-medium text-charcoal mt-2 text-center"><?php echo esc_html($title); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <!-- Navigation buttons -->
                    <div class="partners-swiper__nav partners-swiper__nav--prev absolute top-1/2 left-0 z-10 w-11 h-11 -translate-y-1/2 rounded-full border border-border bg-surface text-charcoal shadow-card flex items-center justify-center cursor-pointer transition-all duration-300 hover:scale-110 hover:border-primary hover:bg-primary hover:text-white hover:shadow-button max-sm:w-9 max-sm:h-9 max-sm:-left-2.5" aria-label="Đối tác trước">
                        <?php echo batdongsan_icon('arrow-left', ['width' => 20, 'height' => 20]); ?>
                    </div>
                    <div class="partners-swiper__nav partners-swiper__nav--next absolute top-1/2 right-0 z-10 w-11 h-11 -translate-y-1/2 rounded-full border border-border bg-surface text-charcoal shadow-card flex items-center justify-center cursor-pointer transition-all duration-300 hover:scale-110 hover:border-primary hover:bg-primary hover:text-white hover:shadow-button max-sm:w-9 max-sm:h-9 max-sm:-right-2.5" aria-label="Đối tác sau">
                        <?php echo batdongsan_icon('arrow-right', ['width' => 20, 'height' => 20]); ?>
                    </div>
                    <!-- Pagination -->
                    <div class="partners-swiper__pagination relative mt-8 text-center flex items-center justify-center gap-1.5 [&_.swiper-pagination-bullet]:w-2 [&_.swiper-pagination-bullet]:h-2 [&_.swiper-pagination-bullet]:bg-border [&_.swiper-pagination-bullet]:opacity-100 [&_.swiper-pagination-bullet-active]:w-6 [&_.swiper-pagination-bullet-active]:bg-primary [&_.swiper-pagination-bullet-active]:rounded-full transition-all"></div>
                </div>
            <?php else : ?>
                <p class="text-center text-charcoal-muted text-sm py-12 px-5">Thông tin đối tác chiến lược đang được cập nhật.</p>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    </section>

    <!-- Section 4: Brand Values & Apple-Style Clarity Hub -->
    <section class="py-12 sm:py-16 lg:py-20 bg-surface-dark bg-charcoal text-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <span class="text-xs font-semibold tracking-wider uppercase text-white/60 mb-2 block">Tiêu Chuẩn Minh Bạch</span>
                <h2 class="font-serif text-2xl md:text-3xl lg:text-4xl font-bold text-white tracking-tight mb-3">Dữ Liệu Thật. Pháp Lý Chuẩn. Hỗ Trợ Tận Tâm.</h2>
                <p class="font-sans text-white/70 text-sm md:text-base leading-relaxed m-0">
                    Được thiết kế nhằm mang lại sự an tâm tuyệt đối cho khách hàng tìm kiếm nhà đất và đầu tư bất động sản tại Hà Nội.
                </p>
            </div>

            <!-- 4 Cupertino Value Pillars (Apple SF Symbols Standard) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-12">
                <div class="p-6 rounded-2xl bg-white/[0.05] border border-white/[0.08] backdrop-blur-md transition-all duration-300 hover:bg-white/[0.08]">
                    <div class="w-10 h-10 rounded-xl bg-primary/20 text-primary flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2.5l7 3.5v5.8c0 4.8-3.1 9.2-7 10.7-3.9-1.5-7-5.9-7-10.7V6L12 2.5z"/>
                            <path d="M9 12l2 2 4-4"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-white mb-1.5">Pháp Lý Minh Bạch</h3>
                    <p class="text-white/65 text-xs leading-relaxed m-0">Kiểm tra thông tin quy hoạch, sổ đỏ và tình trạng hiện trạng trước khi cập nhật dữ liệu.</p>
                </div>
                <div class="p-6 rounded-2xl bg-white/[0.05] border border-white/0.08 backdrop-blur-md transition-all duration-300 hover:bg-white/[0.08]">
                    <div class="w-10 h-10 rounded-xl bg-primary/20 text-primary flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="6" width="18" height="12" rx="2.5"/>
                            <circle cx="12" cy="12" r="2.5" stroke-width="1.6"/>
                            <path d="M6 9h.01M18 15h.01" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-white mb-1.5">Định Giá Chuẩn Xác</h3>
                    <p class="text-white/65 text-xs leading-relaxed m-0">Dữ liệu thị trường thực tế theo từng quận huyện, giúp bạn đưa ra quyết định mua bán tối ưu.</p>
                </div>
                <div class="p-6 rounded-2xl bg-white/[0.05] border border-white/[0.08] backdrop-blur-md transition-all duration-300 hover:bg-white/[0.08]">
                    <div class="w-10 h-10 rounded-xl bg-primary/20 text-primary flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="5" y="11" width="14" height="10" rx="3"/>
                            <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-white mb-1.5">Bảo Mật Tuyệt Đối</h3>
                    <p class="text-white/65 text-xs leading-relaxed m-0">Quy trình kết nối trực tiếp, tôn trọng tối đa quyền riêng tư của người bán và người mua.</p>
                </div>
                <div class="p-6 rounded-2xl bg-white/[0.05] border border-white/[0.08] backdrop-blur-md transition-all duration-300 hover:bg-white/[0.08]">
                    <div class="w-10 h-10 rounded-xl bg-primary/20 text-primary flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/>
                            <circle cx="12" cy="9.5" r="3"/>
                            <path d="M6.5 18a6.5 6.5 0 0 1 11 0"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-white mb-1.5">Đặc Quyền Ngoại Giao</h3>
                    <p class="text-white/65 text-xs leading-relaxed m-0">Đội ngũ am hiểu sâu sắc từng tuyến phố, ngõ ngách và dự án tại khắp các quận Hà Nội.</p>
                </div>
            </div>

            <!-- Action buttons (Cupertino Pills) -->
            <div class="flex flex-wrap items-center justify-center gap-3">
                <button type="button" data-modal-target="advisor-modal" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary hover:bg-primary-hover text-white rounded-full text-sm font-medium transition-all duration-200 cursor-pointer active:scale-[0.98]">
                    <span>Đăng Ký Tư Vấn</span>
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>
                <a href="tel:0966866938" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white/10 hover:bg-white/15 text-white border border-white/20 text-sm font-medium rounded-full backdrop-blur-md transition-all duration-200 active:scale-[0.98]">
                    <svg class="w-4 h-4 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.5 16.5c-1.2-1-2.4-1.4-3.5-.3l-1.3 1.3c-.3.3-.8.4-1.2.2-2.3-1.1-4.2-3-5.3-5.3-.2-.4-.1-.9.2-1.2l1.3-1.3c1.1-1.1.7-2.3-.3-3.5L9.1 4.8C8 3.7 6.8 3.7 5.7 4.8L4.3 6.2c-1.2 1.2-1.5 2.8-.7 4.6 1.8 4.2 5 7.4 9.2 9.2 1.8.8 3.4.5 4.6-.7l1.4-1.4c1.1-1.1 1.1-2.3 0-3.4l-1.3-1.4z"/>
                    </svg>
                    <span>Hotline: 0966.866.938</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Section 5: Tin tức bất động sản -->
    <section class="pt-8 sm:pt-10 md:pt-12 pb-16 lg:pb-20 bg-surface border-t border-border/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php
            $posts_page_id = get_option('page_for_posts');
            $news_url = $posts_page_id ? get_permalink($posts_page_id) : home_url('/');
            ?>
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8 pb-4 border-b border-border/60">
                <div>
                    <span class="text-xs font-semibold tracking-wider uppercase text-charcoal-muted mb-1.5 block">Thị Trường & Nhận Định</span>
                    <h2 class="text-2xl md:text-3xl lg:text-4xl font-bold tracking-tight text-charcoal m-0">Tin Tức Thị Trường Hà Nội</h2>
                </div>
                <a href="<?php echo esc_url($news_url); ?>" class="group inline-flex items-center gap-1.5 text-primary font-semibold text-sm hover:underline whitespace-nowrap" aria-label="Xem thêm tin tức">
                    <span>Xem chuyên mục tin tức</span>
                    <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </a>
            </div>

            <?php
            $news_query = new WP_Query([
                'posts_per_page' => 9,
                'post_type' => 'post',
                'post_status' => 'publish',
                'no_found_rows' => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => false,
            ]);
            ?>

            <?php if ($news_query->have_posts()) : ?>
                <div class="relative group/carousel w-full md:px-14">
                    <div id="home-news-carousel" class="bds-carousel flex items-stretch gap-4 md:gap-5 overflow-x-auto scroll-smooth snap-x snap-mandatory no-scrollbar py-2 -mx-4 px-4 sm:mx-0 sm:px-0">
                        <?php
                        $news_index = 0;
                        while ($news_query->have_posts()) : $news_query->the_post();
                            $news_index++;
                        ?>
                            <div class="snap-start shrink-0 w-[82%] sm:w-[calc(50%-10px)] lg:w-[calc(33.333%-14px)] flex flex-col h-auto">
                                <a href="<?php echo esc_url(get_permalink()); ?>" class="block no-underline group h-full" aria-label="<?php echo esc_attr(get_the_title()); ?>">
                                    <div class="bg-canvas rounded-2xl overflow-hidden border border-black/[0.06] shadow-card hover:shadow-card-hover hover:scale-[1.01] transition-all duration-300 p-4 flex flex-col h-full">
                                        <div class="aspect-[16/10] rounded-xl overflow-hidden bg-stone mb-4 relative">
                                            <?php if (has_post_thumbnail()) : ?>
                                                <?php echo bds_get_optimized_image(get_post_thumbnail_id(), 'bds-medium', ['class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105', 'alt' => esc_attr(get_the_title())], true); ?>
                                            <?php else : ?>
                                                <div class="w-full h-full bg-stone flex items-center justify-center text-charcoal-muted">
                                                    <?php echo batdongsan_icon('image', ['width' => 32, 'height' => 32]); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex gap-3 items-start mt-auto">
                                            <span class="text-xl font-bold text-charcoal/20 leading-none shrink-0 mt-0.5"><?php echo str_pad((string) $news_index, 2, '0', STR_PAD_LEFT); ?></span>
                                            <h3 class="font-semibold text-base text-charcoal m-0 leading-snug line-clamp-2 group-hover:text-primary transition-colors"><?php echo esc_html(get_the_title()); ?></h3>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    </div>
                    <div class="hidden md:block">
                        <button type="button" class="absolute top-[calc(50%-20px)] left-0 -translate-y-1/2 z-10 w-11 h-11 rounded-full border border-black/[0.08] bg-white/90 backdrop-blur-md text-charcoal shadow-card flex items-center justify-center cursor-pointer transition-all duration-200 hover:scale-105 hover:bg-white hover:text-primary" data-carousel-prev="#home-news-carousel" aria-label="Tin trước">
                            <?php echo batdongsan_icon('arrow-left', ['width' => 18, 'height' => 18]); ?>
                        </button>
                        <button type="button" class="absolute top-[calc(50%-20px)] right-0 -translate-y-1/2 z-10 w-11 h-11 rounded-full border border-black/[0.08] bg-white/90 backdrop-blur-md text-charcoal shadow-card flex items-center justify-center cursor-pointer transition-all duration-200 hover:scale-105 hover:bg-white hover:text-primary" data-carousel-next="#home-news-carousel" aria-label="Tin tiếp theo">
                            <?php echo batdongsan_icon('arrow-right', ['width' => 18, 'height' => 18]); ?>
                        </button>
                    </div>
                </div>
            <?php else : ?>
                <p class="text-charcoal-muted text-center py-12">Hiện chưa có tin tức thị trường mới.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>