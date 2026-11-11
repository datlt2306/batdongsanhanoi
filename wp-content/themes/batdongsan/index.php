<?php

/**
 * Index template: Trang tin tức chính
 *
 * Layout 2 cột: hero section + danh sách bài viết + sidebar (giống layout trong ảnh).
 */

get_header();
?>

<main class="news-layout py-8 md:py-12">
    <!-- Header với ngày tháng và địa điểm -->
    <div class="bg-slate-100 py-3 mb-8">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between flex-wrap gap-2 text-sm text-gray-600">
                <span class="">
                    <?php echo esc_html(date_i18n('l, \N\g\à\y d/m/Y')); ?>
                </span>
                <span class="text-xs text-gray-500">
                    <?php esc_html_e('ĐỊA ĐIỂM:', 'batdongsan'); ?>
                    <?php esc_html_e('KHU ĐÔ THỊ WATERPOINT - XÃ BẾ LỨC, TỈNH TÂY NINH', 'batdongsan'); ?>
                </span>
            </div>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-4">
        <?php
        $page_heading = '';
        if (is_home() && !is_front_page()) {
            $page_heading = single_post_title('', false) ?: 'Tin Tức Bất Động Sản';
        } elseif (is_archive()) {
            $page_heading = single_term_title('', false) ?: (single_cat_title('', false) ?: get_the_archive_title());
        } elseif (is_search()) {
            $page_heading = sprintf(__('Kết quả tìm kiếm cho: %s', 'batdongsan'), get_search_query());
        }
        if ($page_heading) : ?>
            <header class="mb-6">
                <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">
                    <?php echo esc_html($page_heading); ?>
                </h1>
            </header>
        <?php endif; ?>
        <div class="grid gap-8 grid-cols-1 lg:grid-cols-[1fr_340px]">
            <div class="flex-1 min-w-0" role="main">
                <?php if (have_posts()) : ?>
                    <?php
                    $first_post = true;
                    while (have_posts()) :
                        the_post();
                        if ($first_post) {
                            // Hero section với bài viết đầu tiên
                    ?>
                            <article class="mb-12 relative">
                                <a href="<?php the_permalink(); ?>" class="block no-underline text-inherit">
                                    <div class="relative w-full h-[400px] overflow-hidden rounded-[10px] mb-0 max-md:h-64">
                                        <?php
                                        if (has_post_thumbnail()) {
                                            the_post_thumbnail('large', ['class' => 'w-full h-full object-cover block']);
                                        } else {
                                            echo '<div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300"></div>';
                                        }
                                        ?>
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/50 to-black/30 z-10"></div>
                                        <div class="absolute top-5 left-5 bg-[#b20000] text-white px-3.5 py-1.5 text-xs font-bold uppercase tracking-wide z-20 rounded">TIN TỨC</div>
                                    </div>
                                    <div class="absolute bottom-0 left-0 right-0 p-10 z-20 text-white max-md:p-5">
                                        <div class="text-[13px] mb-3 opacity-90">
                                            <span class="font-semibold">
                                                <?php echo esc_html(get_the_date('d/m/Y H:i')); ?>
                                            </span>
                                            <span class="ml-2">- <?php the_category(', '); ?></span>
                                        </div>
                                        <h2 class="text-[32px] font-extrabold leading-snug m-0 mb-4 text-white uppercase max-md:text-xl">
                                            <?php the_title(); ?>
                                        </h2>
                                        <div class="text-[15px] leading-relaxed opacity-95 max-w-[90%]">
                                            <?php
                                            if (has_excerpt()) {
                                                the_excerpt();
                                            } else {
                                                echo wp_kses_post(wp_trim_words(get_the_content(), 40, '...'));
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </a>
                            </article>
                    <?php
                            $first_post = false;
                        } else {
                            // Các bài viết còn lại dùng template card
                            get_template_part('template-parts/content', 'news-card');
                        }
                    endwhile;
                    ?>

                    <nav class="pagination news-pagination flex items-center justify-between gap-4 mt-8 pt-6 border-t border-gray-200" aria-label="<?php esc_attr_e('Điều hướng trang', 'batdongsan'); ?>">
                        <div class="text-sm text-gray-600 hover:text-[#b20000]">
                            <?php previous_posts_link(__('← Bài mới hơn', 'batdongsan')); ?>
                        </div>
                        <div class="text-sm text-gray-600 hover:text-[#b20000]">
                            <?php next_posts_link(__('Bài cũ hơn →', 'batdongsan')); ?>
                        </div>
                    </nav>
                <?php else : ?>
                    <p class="news-empty"><?php esc_html_e('Chưa có bài viết.', 'batdongsan'); ?></p>
                <?php endif; ?>
            </div>

            <aside class="sticky top-24" aria-label="<?php esc_attr_e('Sidebar tin tức', 'batdongsan'); ?>">
                <?php get_sidebar(); ?>
            </aside>
        </div>
    </div>

</main>

<?php
get_footer();
?>