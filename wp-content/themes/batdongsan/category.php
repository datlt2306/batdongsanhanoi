<?php

/**
 * Category archive template.
 *
 * Tùy biến giao diện đẹp cho 2 chuyên mục:
 * - tin-du-an
 * - tin-thi-truong
 *
 * Các chuyên mục khác dùng layout đơn giản hơn.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$category      = get_queried_object();
$category_slug = isset($category->slug) ? (string) $category->slug : '';
$category_name = isset($category->name) ? (string) $category->name : '';

$is_tin_du_an      = $category_slug === 'tin-du-an';
$is_tin_thi_truong = $category_slug === 'tin-thi-truong';

// Chuẩn hóa headline & mô tả cho 2 nhóm tin chính
if ($is_tin_du_an) {
    $headline = 'Tin tức dự án';
    $subtitle = 'Cập nhật tiến độ, chính sách bán hàng và ưu đãi mới nhất của các dự án bất động sản.';
} elseif ($is_tin_thi_truong) {
    $headline = 'Tin tức thị trường';
    $subtitle = 'Phân tích xu hướng, số liệu và diễn biến thị trường bất động sản, giúp anh/chị ra quyết định tự tin hơn.';
} else {
    $headline = $category_name !== '' ? $category_name : 'Chuyên mục';
    $subtitle = 'Tổng hợp các bài viết trong chuyên mục này.';
}

// Lấy trang hiện tại cho phân trang
$paged = get_query_var('paged') ? (int) get_query_var('paged') : 1;

// Helper ước tính thời gian đọc (nếu chưa được định nghĩa từ template khác)
if (!defined('BDS_WORDS_PER_MINUTE')) {
    define('BDS_WORDS_PER_MINUTE', 220);
}

if (!function_exists('bds_estimate_read_time')) {
    /**
     * Ước tính thời gian đọc (phút) dựa trên số lượng từ.
     */
    function bds_estimate_read_time(string $content): int {
        $stripped = wp_strip_all_tags($content);
        $words    = str_word_count($stripped);
        if ($words <= 0) {
            return 1;
        }

        return max(1, (int) ceil($words / BDS_WORDS_PER_MINUTE));
    }
}

?>

<main class="min-h-screen bg-slate-50">
    <section class="mx-auto flex max-w-6xl flex-col gap-8 px-4 py-10 lg:gap-10 lg:py-14">
        <!-- Header -->
        <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-2">
                <p class="text-xs font-medium uppercase tracking-[0.18em] text-sky-600">
                    <?php echo $is_tin_thi_truong ? 'Tin thị trường' : ($is_tin_du_an ? 'Tin dự án' : 'Chuyên mục'); ?>
                </p>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">
                    <?php echo esc_html($headline); ?>
                </h1>
                <p class="max-w-2xl text-sm text-slate-600 sm:text-base">
                    <?php echo esc_html($subtitle); ?>
                </p>
            </div>
            <nav class="text-xs text-slate-500 sm:text-sm" aria-label="Breadcrumb">
                <ol class="flex items-center gap-1.5">
                    <li>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="cursor-pointer text-slate-500 transition hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50" tabindex="0" aria-label="Về trang chủ">
                            Trang chủ
                        </a>
                    </li>
                    <li aria-hidden="true" class="text-slate-400">/</li>
                    <li>
                        <a href="<?php echo esc_url(get_permalink(get_page_by_path('tin-tuc'))); ?>" class="cursor-pointer text-slate-500 transition hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50" tabindex="0" aria-label="Trang Tin tức">
                            Tin tức
                        </a>
                    </li>
                    <li aria-hidden="true" class="text-slate-400">/</li>
                    <li>
                        <span class="font-medium text-slate-900"><?php echo esc_html($category_name); ?></span>
                    </li>
                </ol>
            </nav>
        </header>

        <!-- Layout chính -->
        <section class="grid gap-8 lg:grid-cols-[minmax(0,2.2fr),minmax(0,1fr)]">
            <!-- Danh sách bài -->
            <div class="space-y-5">
                <?php if (have_posts()) : ?>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <?php
                        while (have_posts()) :
                            the_post();
                            $post_id  = get_the_ID();
                            $thumb_id = get_post_thumbnail_id($post_id);
                            $date     = get_the_date('d/m/Y', $post_id);
                            $read_min = bds_estimate_read_time(get_the_content(null, false, $post_id));
                        ?>
                            <article <?php post_class('group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-100 transition hover:-translate-y-0.5 hover:shadow-lg'); ?>>
                                <a
                                    href="<?php the_permalink(); ?>"
                                    class="relative block cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50"
                                    tabindex="0"
                                    aria-label="<?php echo esc_attr(get_the_title()); ?>"
                                    onkeydown="if(event.key==='Enter'){window.location.href='<?php the_permalink(); ?>';}">
                                    <div class="relative h-44 w-full overflow-hidden bg-slate-100">
                                        <?php if ($thumb_id) : ?>
                                            <?php echo wp_get_attachment_image($thumb_id, 'medium_large', false, ['class' => 'h-full w-full object-cover transition-transform duration-700 group-hover:scale-105']); ?>
                                        <?php else : ?>
                                            <div class="flex h-full w-full items-center justify-center text-xs text-slate-400">
                                                Chưa có ảnh đại diện
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($is_tin_du_an || $is_tin_thi_truong) : ?>
                                            <span class="absolute left-3 top-3 inline-flex items-center rounded-full bg-slate-900/85 px-2.5 py-1 text-[11px] font-medium text-slate-50 shadow-sm">
                                                <?php echo esc_html($category_name); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <div class="flex flex-1 flex-col px-4 pb-4 pt-3">
                                    <div class="flex items-center gap-2 text-xs text-slate-500">
                                        <span><?php echo esc_html($date); ?></span>
                                        <span aria-hidden="true" class="h-1 w-1 rounded-full bg-slate-400"></span>
                                        <span><?php echo esc_html($read_min); ?> phút đọc</span>
                                    </div>
                                    <h2 class="mt-2 line-clamp-2 text-base font-semibold tracking-tight text-slate-900">
                                        <a
                                            href="<?php the_permalink(); ?>"
                                            class="cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50"
                                            tabindex="0"
                                            aria-label="<?php echo esc_attr(get_the_title()); ?>"
                                            onkeydown="if(event.key==='Enter'){window.location.href='<?php the_permalink(); ?>';}">
                                            <?php the_title(); ?>
                                        </a>
                                    </h2>
                                    <p class="mt-2 line-clamp-3 text-sm text-slate-600">
                                        <?php echo esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 24)); ?>
                                    </p>
                                    <div class="mt-3 flex items-center justify-between">
                                        <a
                                            href="<?php the_permalink(); ?>"
                                            class="inline-flex items-center gap-1 text-sm font-medium text-sky-600 transition hover:text-sky-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50"
                                            tabindex="0"
                                            aria-label="Chi tiết bài: <?php echo esc_attr(get_the_title()); ?>"
                                            onkeydown="if(event.key==='Enter'){window.location.href='<?php the_permalink(); ?>';}">
                                            Xem chi tiết
                                            <span aria-hidden="true">↗</span>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>

                    <!-- Phân trang -->
                    <nav class="mt-6 flex justify-center" aria-label="Phân trang chuyên mục">
                        <?php
                        $big   = 999999999;
                        $links = paginate_links(
                            [
                                'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                                'format'    => '?paged=%#%',
                                'current'   => max(1, $paged),
                                'total'     => (int) $wp_query->max_num_pages,
                                'prev_text' => '&larr;',
                                'next_text' => '&rarr;',
                                'type'      => 'array',
                            ]
                        );

                        if (!empty($links)) :
                        ?>
                            <ul class="inline-flex items-center gap-1 rounded-full bg-white/80 p-1.5 shadow-sm ring-1 ring-slate-200">
                                <?php foreach ($links as $link) : ?>
                                    <li class="text-sm">
                                        <?php
                                        $link = str_replace(
                                            'page-numbers',
                                            'page-numbers inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-full px-3 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                                            $link
                                        );
                                        $link = str_replace(
                                            'current',
                                            'current bg-sky-600 text-white hover:bg-sky-600 hover:text-white',
                                            $link
                                        );
                                        echo wp_kses_post($link);
                                        ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </nav>
                <?php else : ?>
                    <div class="rounded-2xl bg-white px-6 py-10 text-center shadow-sm ring-1 ring-slate-200">
                        <h2 class="text-lg font-semibold text-slate-900">Chưa có bài viết trong chuyên mục này</h2>
                        <p class="mt-2 text-sm text-slate-500">
                            Vui lòng quay lại sau, chúng tôi sẽ cập nhật thêm nội dung.
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="space-y-5">
                <?php if ($is_tin_du_an || $is_tin_thi_truong) : ?>
                    <!-- Box chuyên mục khác -->
                    <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                            Chuyên mục khác
                        </h2>
                        <ul class="mt-3 space-y-2 text-sm text-slate-700">
                            <?php
                            $other_cats = get_categories(
                                [
                                    'exclude'    => [$category->term_id],
                                    'hide_empty' => false,
                                ]
                            );
                            foreach ($other_cats as $cat_item) :
                            ?>
                                <li>
                                    <a
                                        href="<?php echo esc_url(get_category_link($cat_item->term_id)); ?>"
                                        class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm text-slate-700 transition hover:bg-slate-50 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50"
                                        tabindex="0"
                                        aria-label="Chuyên mục <?php echo esc_attr($cat_item->name); ?>"
                                        onkeydown="if(event.key==='Enter'){window.location.href='<?php echo esc_js(get_category_link($cat_item->term_id)); ?>';}">
                                        <span><?php echo esc_html($cat_item->name); ?></span>
                                        <span class="text-xs text-slate-400"><?php echo (int) $cat_item->count; ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>

                    <!-- Box bài xem nhiều -->
                    <?php
                    $popular_args = [
                        'post_type'           => 'post',
                        'posts_per_page'      => 4,
                        'ignore_sticky_posts' => true,
                        'cat'                 => $category->term_id,
                        'meta_key'            => 'views',
                        'orderby'             => 'meta_value_num',
                        'order'               => 'DESC',
                        'no_found_rows'       => true,
                    ];
                    $popular_query = new WP_Query($popular_args);
                    ?>
                    <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">
                            Tin xem nhiều
                        </h2>
                        <div class="mt-3 space-y-3">
                            <?php if ($popular_query->have_posts()) : ?>
                                <?php
                                while ($popular_query->have_posts()) :
                                    $popular_query->the_post();
                                ?>
                                    <article class="group border-l-2 border-slate-200 pl-3 transition hover:border-sky-500">
                                        <a
                                            href="<?php the_permalink(); ?>"
                                            class="block text-sm font-medium text-slate-800 transition hover:text-sky-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50"
                                            tabindex="0"
                                            aria-label="<?php echo esc_attr(get_the_title()); ?>"
                                            onkeydown="if(event.key==='Enter'){window.location.href='<?php the_permalink(); ?>';}">
                                            <span class="line-clamp-2"><?php the_title(); ?></span>
                                        </a>
                                        <span class="mt-1 block text-xs text-slate-400"><?php echo esc_html(get_the_date('d/m/Y')); ?></span>
                                    </article>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <p class="text-sm text-slate-500">Chưa có dữ liệu.</p>
                            <?php endif; ?>
                            <?php wp_reset_postdata(); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Box đăng ký nhận tin -->
                <?php get_template_part('template-parts/newsletter-form'); ?>
            </aside>
        </section>
    </section>
</main>

<?php
get_footer();
