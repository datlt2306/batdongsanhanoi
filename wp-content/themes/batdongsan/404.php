<?php
/**
 * 404 Error Template - Trang không tìm thấy
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

// Gửi HTTP status 404 và không cache trang lỗi
status_header(404);
nocache_headers();

get_header();

$site_hotline     = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_hotline', '0934.69.9191') : '0934.69.9191';
$site_hotline_raw = function_exists('batdongsan_get_option') ? batdongsan_get_option('site_hotline_raw', '0934699191') : '0934699191';

$breadcrumb_parts = [
    [
        'label' => 'Trang chủ',
        'url'   => home_url('/'),
    ],
    [
        'label' => '404 - Không tìm thấy trang',
        'url'   => '',
    ],
];
?>

<main id="primary" class="site-main py-8 md:py-14 bg-canvas min-h-[70vh]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumbs -->
        <?php
        get_template_part('template-parts/breadcrumb', null, [
            'breadcrumb_parts' => $breadcrumb_parts,
            'current_label'    => 'Lỗi 404',
            'prefix_class'     => 'error-404',
        ]);
        ?>

        <!-- 404 Hero Card -->
        <section class="mt-4 bg-white rounded-3xl border border-border shadow-card p-8 sm:p-12 lg:p-16 text-center max-w-4xl mx-auto relative overflow-hidden">
            <!-- Background Decorative Glow -->
            <div class="absolute -top-24 -right-24 w-72 h-72 bg-primary/5 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
            <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-gold/10 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

            <!-- 404 Badge -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary-light border border-primary/15 text-primary text-xs font-semibold uppercase tracking-wider mb-6">
                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                <span>Mã lỗi 404 &bull; Không tìm thấy nội dung</span>
            </div>

            <!-- Big 404 Graphic Number -->
            <div class="relative mb-6 select-none">
                <span class="text-7xl sm:text-8xl lg:text-9xl font-black text-charcoal/10 tracking-tighter block leading-none font-sans">
                    404
                </span>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-primary to-primary-dark text-white flex items-center justify-center shadow-button shadow-primary/20">
                        <svg class="w-8 h-8 sm:w-10 sm:h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Title & Description -->
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-charcoal tracking-tight mb-3">
                Rất tiếc! Trang bạn đang tìm kiếm không tồn tại
            </h1>
            <p class="text-charcoal-muted text-sm sm:text-base leading-relaxed max-w-2xl mx-auto mb-8">
                Đường dẫn này có thể đã thay đổi, bất động sản đã hoàn tất giao dịch hoặc bài viết đã được gỡ bỏ. Quý khách có thể tìm kiếm lại hoặc truy cập nhanh các danh mục dưới đây.
            </p>

            <!-- Search Bar -->
            <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="max-w-md mx-auto mb-8">
                <div class="relative flex items-center">
                    <input type="search" name="s" placeholder="Nhập tên dự án, khu vực, loại hình..." class="w-full pl-11 pr-24 py-3 bg-stone/40 border border-border rounded-full text-sm text-charcoal placeholder-charcoal-muted/70 focus:outline-none focus:border-primary focus:bg-white transition-all shadow-subtle" required>
                    <svg class="absolute left-4 w-4 h-4 text-charcoal-muted pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <button type="submit" class="absolute right-1.5 px-4 py-2 bg-primary hover:bg-primary-hover text-white text-xs font-semibold rounded-full transition-colors cursor-pointer shadow-sm">
                        Tìm kiếm
                    </button>
                </div>
            </form>

            <!-- Quick Navigation Buttons -->
            <div class="flex flex-wrap items-center justify-center gap-3">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-xs sm:text-sm font-semibold rounded-full shadow-button transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <span>Về Trang Chủ</span>
                </a>

                <a href="<?php echo esc_url(home_url('/bat-dong-san/')); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-stone/70 hover:bg-stone text-charcoal text-xs sm:text-sm font-medium rounded-full border border-border transition-colors">
                    <span>Xem Nhà Đất Bán</span>
                </a>

                <a href="<?php echo esc_url(home_url('/du-an/')); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-stone/70 hover:bg-stone text-charcoal text-xs sm:text-sm font-medium rounded-full border border-border transition-colors">
                    <span>Dự Án Quy Hoạch</span>
                </a>

                <a href="<?php echo esc_url(home_url('/lien-he/')); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-stone/70 hover:bg-stone text-charcoal text-xs sm:text-sm font-medium rounded-full border border-border transition-colors">
                    <span>Liên Hệ Tư Vấn</span>
                </a>
            </div>

            <!-- Hotline Support -->
            <div class="mt-8 pt-6 border-t border-border flex items-center justify-center gap-2 text-xs text-charcoal-muted">
                <span>Cần hỗ trợ tra cứu nhanh? Gọi Hotline:</span>
                <a href="tel:<?php echo esc_attr($site_hotline_raw); ?>" class="text-primary font-bold hover:underline">
                    <?php echo esc_html($site_hotline); ?>
                </a>
            </div>
        </section>

        <!-- Suggested Properties / Projects Section -->
        <?php
        $suggested_query = new WP_Query([
            'post_type'      => ['bat-dong-san', 'du-an'],
            'posts_per_page' => 4,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ]);

        if ($suggested_query->have_posts()) :
        ?>
            <section class="mt-14 max-w-6xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg sm:text-xl font-bold text-charcoal tracking-tight m-0">
                            Bất Động Sản & Dự Án Mới Nhất
                        </h2>
                        <p class="text-xs sm:text-sm text-charcoal-muted m-0 mt-1">
                            Có thể bạn quan tâm các bất động sản đang mở bán và chuyển nhượng tại Hà Nội
                        </p>
                    </div>
                    <a href="<?php echo esc_url(home_url('/bat-dong-san/')); ?>" class="text-xs sm:text-sm font-semibold text-primary hover:underline inline-flex items-center gap-1 shrink-0">
                        <span>Xem tất cả</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <?php
                    while ($suggested_query->have_posts()) :
                        $suggested_query->the_post();
                        $post_id   = get_the_ID();
                        $post_type = get_post_type();
                        $permalink = get_permalink();
                        $title     = get_the_title();
                        $thumb_url = get_the_post_thumbnail_url($post_id, 'medium_large') ?: (get_template_directory_uri() . '/assets/images/default-thumbnail.jpg');
                        $badge_label = ($post_type === 'du-an') ? 'Dự Án' : 'Bất Động Sản';
                        $price_text = function_exists('get_field') ? get_field('muc_gia', $post_id) : '';
                        $area_text  = function_exists('get_field') ? get_field('dien_tich', $post_id) : '';
                    ?>
                        <article class="bg-white rounded-2xl border border-border overflow-hidden hover:shadow-card-hover transition-all duration-300 flex flex-col group">
                            <a href="<?php echo esc_url($permalink); ?>" class="relative block aspect-[4/3] overflow-hidden bg-stone">
                                <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                                <span class="absolute top-2.5 left-2.5 px-2.5 py-1 bg-charcoal/80 backdrop-blur-sm text-white text-[11px] font-semibold rounded-full uppercase tracking-wider">
                                    <?php echo esc_html($badge_label); ?>
                                </span>
                            </a>
                            <div class="p-4 flex flex-col flex-1">
                                <h3 class="text-sm font-semibold text-charcoal line-clamp-2 group-hover:text-primary transition-colors m-0 mb-2 leading-snug">
                                    <a href="<?php echo esc_url($permalink); ?>">
                                        <?php echo esc_html($title); ?>
                                    </a>
                                </h3>
                                <div class="mt-auto pt-3 border-t border-border-light flex items-center justify-between text-xs">
                                    <?php if (!empty($price_text)) : ?>
                                        <span class="font-bold text-primary"><?php echo esc_html($price_text); ?></span>
                                    <?php else : ?>
                                        <span class="text-charcoal-muted">Giá thỏa thuận</span>
                                    <?php endif; ?>

                                    <?php if (!empty($area_text)) : ?>
                                        <span class="text-charcoal-muted"><?php echo esc_html($area_text); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </section>
        <?php endif; ?>

    </div>
</main>

<?php
get_footer();
