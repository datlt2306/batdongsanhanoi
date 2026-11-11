<?php

/**
 * Search Results Template - Universal Real Estate Search Hub
 *
 * Màn hình kết quả tìm kiếm đa năng: phân loại BĐS Bán, Cho thuê và Dự án qua Smart Tabs.
 *
 * @package BatDongSan
 */

get_header();

// Lấy tham số tìm kiếm
$raw_s       = get_search_query() ?: sanitize_text_field(wp_unslash($_GET['s'] ?? ($_GET['project-name'] ?? '')));
$s           = trim($raw_s);
$khu_vuc     = sanitize_text_field(wp_unslash($_GET['khu_vuc'] ?? ($_GET['area'] ?? '')));
$type        = sanitize_text_field(wp_unslash($_GET['type'] ?? '0'));
$price       = sanitize_text_field(wp_unslash($_GET['price'] ?? '0'));
$loai        = sanitize_text_field(wp_unslash($_GET['loai'] ?? 'tat-ca')); // 'tat-ca' | 'ban' | 'cho-thue' | 'du-an'
$paged       = max(1, (int) (get_query_var('paged') ?: ($_GET['paged'] ?? 1)));
$posts_per_page = 12;

// Danh sách khu vực
$area_options = function_exists('batdongsan_get_area_options') ? batdongsan_get_area_options(true) : [];
$khu_vuc_label = (!empty($khu_vuc) && isset($area_options[$khu_vuc])) ? $area_options[$khu_vuc] : '';

// 1. QUERY ĐẾM SỐ LƯỢNG CHO TỪNG LOẠI (Smart Counters)
// Helper build tax/meta queries
$build_bds_tax_query = function($hinh_thuc_slug = null) use ($type) {
    $tax_query = ['relation' => 'AND'];
    if ($type && $type !== '0') {
        if (is_numeric($type)) {
            $tax_query[] = [
                'taxonomy' => 'loai_hinh_bat_dong_san',
                'field'    => 'term_id',
                'terms'    => absint($type),
            ];
        } else {
            $tax_query[] = [
                'taxonomy' => 'loai_hinh_bat_dong_san',
                'field'    => 'slug',
                'terms'    => sanitize_title($type),
            ];
        }
    }
    if ($hinh_thuc_slug) {
        $tax_query[] = [
            'taxonomy'         => 'hinh-thuc-bds',
            'field'            => 'slug',
            'terms'            => (array) $hinh_thuc_slug,
            'include_children' => true,
        ];
    }
    return count($tax_query) > 1 ? $tax_query : [];
};

$build_meta_query = function() use ($khu_vuc, $area_options) {
    $meta_query = ['relation' => 'AND'];
    if (!empty($khu_vuc)) {
        $khu_vuc_name = $area_options[$khu_vuc] ?? $khu_vuc;
        $meta_query[] = [
            'relation' => 'OR',
            ['key' => 'dia_chi', 'value' => $khu_vuc_name, 'compare' => 'LIKE'],
            ['key' => 'address', 'value' => $khu_vuc_name, 'compare' => 'LIKE'],
        ];
    }
    return count($meta_query) > 1 ? $meta_query : [];
};

// Đếm Dự Án
$duan_count_args = [
    'post_type'      => 'du-an',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'no_found_rows'  => false,
];
if ($s !== '') {
    $duan_count_args['s'] = $s;
}
$duan_meta = $build_meta_query();
if (!empty($duan_meta)) {
    $duan_count_args['meta_query'] = $duan_meta;
}
if ($type && $type !== '0') {
    $duan_count_args['tax_query'] = [
        [
            'taxonomy' => 'loai_hinh_du_an',
            'field'    => is_numeric($type) ? 'term_id' : 'slug',
            'terms'    => is_numeric($type) ? absint($type) : sanitize_title($type),
        ],
    ];
}
$duan_count_query = new WP_Query($duan_count_args);
$count_duan = (int) $duan_count_query->found_posts;

// Đếm BĐS Bán (chuyen-nhuong, mua-ban, so-cap, v.v.)
$ban_count_args = [
    'post_type'      => 'bat-dong-san',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'no_found_rows'  => false,
];
if ($s !== '') {
    $ban_count_args['s'] = $s;
}
$ban_meta = $build_meta_query();
if (!empty($ban_meta)) {
    $ban_count_args['meta_query'] = $ban_meta;
}
$ban_tax = $build_bds_tax_query(['chuyen-nhuong', 'mua-ban', 'so-cap', 'trung-cap', 'cao-cap']);
if (!empty($ban_tax)) {
    $ban_count_args['tax_query'] = $ban_tax;
}
$ban_count_query = new WP_Query($ban_count_args);
$count_ban = (int) $ban_count_query->found_posts;

// Đếm BĐS Cho Thuê (cho-thue)
$thue_count_args = [
    'post_type'      => 'bat-dong-san',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'no_found_rows'  => false,
];
if ($s !== '') {
    $thue_count_args['s'] = $s;
}
$thue_meta = $build_meta_query();
if (!empty($thue_meta)) {
    $thue_count_args['meta_query'] = $thue_meta;
}
$thue_tax = $build_bds_tax_query(['cho-thue']);
if (!empty($thue_tax)) {
    $thue_count_args['tax_query'] = $thue_tax;
}
$thue_count_query = new WP_Query($thue_count_args);
$count_thue = (int) $thue_count_query->found_posts;

$count_total = $count_duan + $count_ban + $count_thue;

// 2. QUERY DỮ LIỆU HIỂN THỊ DỰA TRÊN TAB HIỆN TẠI
$main_query = null;
$duan_featured_query = null;

if ($loai === 'du-an') {
    // Chỉ hiển thị Dự án
    $query_args = $duan_count_args;
    $query_args['posts_per_page'] = $posts_per_page;
    $query_args['paged']          = $paged;
    $query_args['fields']         = '';
    $main_query = new WP_Query($query_args);
} elseif ($loai === 'ban') {
    // Chỉ hiển thị BĐS Bán
    $query_args = $ban_count_args;
    $query_args['posts_per_page'] = $posts_per_page;
    $query_args['paged']          = $paged;
    $query_args['fields']         = '';
    $main_query = new WP_Query($query_args);
} elseif ($loai === 'cho-thue') {
    // Chỉ hiển thị Cho Thuê
    $query_args = $thue_count_args;
    $query_args['posts_per_page'] = $posts_per_page;
    $query_args['paged']          = $paged;
    $query_args['fields']         = '';
    $main_query = new WP_Query($query_args);
} else {
    // Tab "Tất cả":
    // Nếu có Dự án, lấy tối đa 3 Dự án nổi bật hiển thị ở khối trên
    if ($count_duan > 0 && $paged === 1) {
        $duan_query_args = $duan_count_args;
        $duan_query_args['posts_per_page'] = 3;
        $duan_query_args['fields']         = '';
        $duan_featured_query = new WP_Query($duan_query_args);
    }
    // Danh sách BĐS tổng hợp (cả bán và thuê)
    $bds_all_args = [
        'post_type'      => 'bat-dong-san',
        'post_status'    => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged'          => $paged,
        'orderby'        => 'post_date',
        'order'          => 'DESC',
    ];
    if ($s !== '') {
        $bds_all_args['s'] = $s;
    }
    $bds_meta = $build_meta_query();
    if (!empty($bds_meta)) {
        $bds_all_args['meta_query'] = $bds_meta;
    }
    $bds_tax = $build_bds_tax_query();
    if (!empty($bds_tax)) {
        $bds_all_args['tax_query'] = $bds_tax;
    }
    $main_query = new WP_Query($bds_all_args);
}

// URL helper để chuyển tab giữ nguyên các filter query params
$get_tab_url = function($target_loai) {
    return add_query_arg(['loai' => $target_loai, 'paged' => 1]);
};
?>

<main class="bg-canvas min-h-screen py-6 sm:py-8 lg:py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb Navigation -->
        <nav aria-label="Breadcrumb" class="mb-4 text-xs font-sans text-charcoal-muted flex items-center gap-2">
            <a href="<?= esc_url(home_url('/')); ?>" class="hover:text-primary transition-colors">Trang chủ</a>
            <span>/</span>
            <span class="text-charcoal font-medium">Tìm kiếm</span>
        </nav>

        <!-- Search Header Title & Meta -->
        <header class="mb-6 sm:mb-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-4 border-b border-border">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-charcoal tracking-tight m-0 leading-tight">
                        <?php if ($s !== '') : ?>
                            Kết quả tìm kiếm cho: <span class="text-primary">"<?= esc_html($s); ?>"</span>
                        <?php elseif ($khu_vuc_label) : ?>
                            Bất động sản tại <span class="text-primary"><?= esc_html($khu_vuc_label); ?></span>
                        <?php else : ?>
                            Tìm kiếm bất động sản & dự án
                        <?php endif; ?>
                    </h1>
                    <p class="font-sans text-xs sm:text-sm text-charcoal-muted mt-1.5 mb-0">
                        Tìm thấy <strong class="text-charcoal font-semibold"><?= number_format_i18n($count_total); ?></strong> kết quả phù hợp trên toàn hệ thống
                        <?= $khu_vuc_label ? 'tại khu vực ' . esc_html($khu_vuc_label) : ''; ?>
                    </p>
                </div>

                <!-- Back to Home / Clear Link -->
                <?php if ($s !== '' || !empty($khu_vuc) || ($price !== '0' && $price !== '')) : ?>
                    <a href="<?= esc_url(home_url('/')); ?>" class="inline-flex items-center gap-1.5 text-xs font-sans text-charcoal-muted hover:text-primary transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        <span>Đặt lại tìm kiếm</span>
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Compact Re-search Console (Thanh tìm kiếm nhanh không tab) -->
        <div class="mb-8">
            <?php
            get_template_part('template-parts/search-form-unified', null, [
                'show_tabs'   => false,
                'placeholder' => 'Nhập địa chỉ, tên dự án, đường phố khác...',
            ]);
            ?>
        </div>

        <!-- Smart Category Filter Tabs (Apple Segmented Style) -->
        <div class="mb-8 overflow-x-auto pb-2 scrollbar-none">
            <div class="inline-flex items-center p-1.5 bg-stone/70 rounded-2xl border border-black/[0.06] backdrop-blur-sm gap-1 min-w-max">
                
                <!-- Tab: Tất cả -->
                <a href="<?= esc_url($get_tab_url('tat-ca')); ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-sans transition-all duration-200 <?= $loai === 'tat-ca' ? 'bg-white text-charcoal font-semibold shadow-sm' : 'text-charcoal-muted hover:text-charcoal font-medium'; ?>">
                    <span>Tất cả</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] <?= $loai === 'tat-ca' ? 'bg-primary text-white' : 'bg-stone text-charcoal-muted'; ?>">
                        <?= number_format_i18n($count_total); ?>
                    </span>
                </a>

                <!-- Tab: Nhà đất bán -->
                <a href="<?= esc_url($get_tab_url('ban')); ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-sans transition-all duration-200 <?= $loai === 'ban' ? 'bg-white text-charcoal font-semibold shadow-sm' : 'text-charcoal-muted hover:text-charcoal font-medium'; ?>">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 10.5L12 3l9 7.5V20a1.5 1.5 0 0 1-1.5 1.5H4.5A1.5 1.5 0 0 1 3 20v-9.5z"/>
                    </svg>
                    <span>Nhà đất bán</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] <?= $loai === 'ban' ? 'bg-primary text-white' : 'bg-stone text-charcoal-muted'; ?>">
                        <?= number_format_i18n($count_ban); ?>
                    </span>
                </a>

                <!-- Tab: Cho thuê -->
                <a href="<?= esc_url($get_tab_url('cho-thue')); ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-sans transition-all duration-200 <?= $loai === 'cho-thue' ? 'bg-white text-charcoal font-semibold shadow-sm' : 'text-charcoal-muted hover:text-charcoal font-medium'; ?>">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="8" cy="15" r="4"/><path d="M10.8 12.2L19 4M15.5 4l3.5 3.5"/>
                    </svg>
                    <span>Cho thuê</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] <?= $loai === 'cho-thue' ? 'bg-primary text-white' : 'bg-stone text-charcoal-muted'; ?>">
                        <?= number_format_i18n($count_thue); ?>
                    </span>
                </a>

                <!-- Tab: Dự án -->
                <a href="<?= esc_url($get_tab_url('du-an')); ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-sans transition-all duration-200 <?= $loai === 'du-an' ? 'bg-white text-charcoal font-semibold shadow-sm' : 'text-charcoal-muted hover:text-charcoal font-medium'; ?>">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 21.5h18M5 21.5V6a1.5 1.5 0 0 1 1.5-1.5h6A1.5 1.5 0 0 1 14 6v15.5M14 10.5h4.5A1.5 1.5 0 0 1 20 12v9.5"/>
                    </svg>
                    <span>Dự án</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] <?= $loai === 'du-an' ? 'bg-primary text-white' : 'bg-stone text-charcoal-muted'; ?>">
                        <?= number_format_i18n($count_duan); ?>
                    </span>
                </a>

            </div>
        </div>

        <!-- RESULTS CONTENT SECTION -->

        <?php if ($count_total === 0) : ?>
            <!-- EMPTY STATE -->
            <div class="bg-surface rounded-3xl border border-border p-10 sm:p-14 text-center max-w-2xl mx-auto shadow-card">
                <div class="w-16 h-16 rounded-full bg-stone flex items-center justify-center mx-auto mb-5 text-charcoal-muted">
                    <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        <line x1="8" y1="11" x2="14" y2="11"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-charcoal mb-2">Không tìm thấy kết quả phù hợp</h3>
                <p class="font-sans text-sm text-charcoal-muted mb-6 leading-relaxed">
                    Rất tiếc, không có bất động sản hoặc dự án nào khớp hoàn toàn với tiêu chí tìm kiếm của bạn. Hãy thử thay đổi từ khóa chung hơn hoặc chọn khu vực khác.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a href="<?= esc_url(home_url('/bat-dong-san')); ?>" class="px-5 py-2.5 rounded-xl bg-primary text-white text-xs font-sans font-semibold hover:bg-primary-hover shadow-button transition-all">
                        Khám phá Bất Động Sản Bán
                    </a>
                    <a href="<?= esc_url(home_url('/du-an')); ?>" class="px-5 py-2.5 rounded-xl bg-stone hover:bg-stone/80 text-charcoal text-xs font-sans font-semibold transition-all">
                        Xem Tất Cả Dự Án
                    </a>
                </div>
            </div>

        <?php else : ?>

            <!-- KHỐI DỰ ÁN NỔI BẬT NẾU ĐANG Ở TAB "TẤT CẢ" VÀ CÓ DỰ ÁN KHỚP -->
            <?php if ($loai === 'tat-ca' && $duan_featured_query && $duan_featured_query->have_posts()) : ?>
                <section class="mb-12">
                    <div class="flex items-center justify-between pb-3 mb-6 border-b border-border">
                        <div>
                            <span class="text-[11px] font-sans font-semibold uppercase tracking-wider text-primary block">Quy mô & Đô thị</span>
                            <h2 class="text-lg sm:text-xl font-bold text-charcoal m-0">Dự án phù hợp từ khóa</h2>
                        </div>
                        <?php if ($count_duan > 3) : ?>
                            <a href="<?= esc_url($get_tab_url('du-an')); ?>" class="text-xs font-sans font-semibold text-primary hover:underline flex items-center gap-1">
                                <span>Xem tất cả <?= number_format_i18n($count_duan); ?> dự án</span>
                                <span>&rarr;</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        while ($duan_featured_query->have_posts()) :
                            $duan_featured_query->the_post();
                            get_template_part('template-parts/card-project', null, ['post' => get_post()]);
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- KHỐI DANH SÁCH BẤT ĐỘNG SẢN CHÍNH -->
            <section>
                <?php if ($loai === 'tat-ca') : ?>
                    <div class="pb-3 mb-6 border-b border-border">
                        <span class="text-[11px] font-sans font-semibold uppercase tracking-wider text-primary block">Giỏ hàng niêm yết</span>
                        <h2 class="text-lg sm:text-xl font-bold text-charcoal m-0">Danh sách bất động sản bán & cho thuê</h2>
                    </div>
                <?php endif; ?>

                <?php if ($main_query && $main_query->have_posts()) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        while ($main_query->have_posts()) :
                            $main_query->the_post();
                            if ($loai === 'du-an') {
                                get_template_part('template-parts/card-project', null, ['post' => get_post()]);
                            } else {
                                get_template_part('template-parts/card-bds-listing', null, ['post' => get_post()]);
                            }
                        endwhile;
                        ?>
                    </div>

                    <!-- PAGINATION -->
                    <?php if ($main_query->max_num_pages > 1) : ?>
                        <div class="mt-12 flex items-center justify-center">
                            <?php
                            echo paginate_links([
                                'total'     => $main_query->max_num_pages,
                                'current'   => $paged,
                                'prev_text' => '&larr; Trước',
                                'next_text' => 'Sau &rarr;',
                                'type'      => 'list',
                            ]);
                            ?>
                        </div>
                    <?php endif; ?>
                    <?php wp_reset_postdata(); ?>

                <?php else : ?>
                    <div class="bg-surface rounded-2xl border border-border p-8 text-center text-charcoal-muted">
                        <p class="mb-0 text-sm">Không có tin đăng nào thuộc phân loại này.</p>
                    </div>
                <?php endif; ?>

            </section>

        <?php endif; ?>

    </div>
</main>

<?php
get_footer();
