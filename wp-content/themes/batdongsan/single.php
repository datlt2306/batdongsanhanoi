<?php

/**
 * Single Post Template: Trang chi tiết bài viết tin tức
 *
 * Hiển thị chi tiết 1 bài viết (post) với layout 2 cột: nội dung + sidebar.
 */

get_header();
?>

<main class="news-layout py-8 md:py-12">
    <!-- Header với ngày tháng và thông tin thị trường -->
    <div class="bg-surface/80 backdrop-blur-md border-b border-black/[0.06] py-3 mb-8">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between flex-wrap gap-2 text-xs text-charcoal/70">
                <span class="font-medium">
                    <?php echo esc_html(date_i18n('l, \N\g\à\y d/m/Y')); ?>
                </span>
                <span class="font-semibold text-primary uppercase tracking-wider">
                    <?php esc_html_e('Bất Động Sản Hà Nội - Cổng Thông Tin Thị Trường', 'batdongsan'); ?>
                </span>
            </div>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid gap-8 grid-cols-1 lg:grid-cols-[1fr_340px]">
            <div class="flex-1 min-w-0" role="main">
                <?php if (have_posts()) : ?>
                    <?php
                    while (have_posts()) :
                        the_post();
                    ?>
                        <article <?php post_class('news-post news-post--single'); ?>>
                            <header class="mb-6">
                                <div class="flex items-center gap-3 mb-4 text-xs text-charcoal/70">
                                    <span class="">
                                        <?php echo esc_html(get_the_date('d/m/Y H:i')); ?>
                                    </span>
                                    <span class="text-primary font-medium">
                                        <?php the_category(', '); ?>
                                    </span>
                                </div>

                                <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-charcoal m-0 leading-tight tracking-tight">
                                    <?php the_title(); ?>
                                </h1>
                            </header>

                            <?php if (has_post_thumbnail()) : ?>
                                <div class="relative mb-8 rounded-2xl overflow-hidden border border-black/[0.06]">
                                    <?php the_post_thumbnail('large', ['class' => 'w-full h-auto']); ?>
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                                    <div class="absolute top-4 left-4 px-3 py-1 bg-primary text-white text-xs font-semibold rounded-full">TIN TỨC</div>
                                </div>
                            <?php endif; ?>

                            <div class="">
                                <div class="prose prose-gray max-w-none">
                                    <?php
                                    the_content();

                                    wp_link_pages([
                                        'before' => '<div class="page-links">',
                                        'after'  => '</div>',
                                    ]);
                                    ?>
                                </div>
                            </div>

                            <footer class="mt-8 pt-6 border-t border-black/[0.06]">
                                <div class="text-sm text-charcoal-muted mb-6">
                                    <?php the_tags('<span class="font-semibold text-charcoal">Từ khóa: </span>', ', '); ?>
                                </div>

                                <nav class="news-pagination flex items-center justify-between gap-4" aria-label="<?php esc_attr_e('Điều hướng bài viết', 'batdongsan'); ?>">
                                    <div class="text-sm text-charcoal-muted hover:text-primary transition-colors">
                                        <?php previous_post_link('%link', '← Bài trước'); ?>
                                    </div>
                                    <div class="text-sm text-charcoal-muted hover:text-primary transition-colors">
                                        <?php next_post_link('%link', 'Bài tiếp theo →'); ?>
                                    </div>
                                </nav>
                            </footer>

                            <?php
                            if (comments_open() || get_comments_number()) :
                                comments_template();
                            endif;
                            ?>
                        </article>
                    <?php endwhile; ?>
                <?php else : ?>
                    <p class="news-empty">
                        <?php esc_html_e('Không tìm thấy bài viết.', 'batdongsan'); ?>
                    </p>
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
