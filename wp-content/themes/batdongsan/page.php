<?php
/**
 * Default Page Template - Trang tiêu chuẩn của WordPress
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$page_id = get_the_ID();
$breadcrumb_parts = [
    [
        'label' => 'Trang chủ',
        'url'   => home_url('/'),
    ],
    [
        'label' => get_the_title(),
        'url'   => '',
    ],
];
?>

<main id="primary" class="site-main py-8 md:py-12 bg-canvas min-h-[70vh]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <?php
        get_template_part('template-parts/breadcrumb', null, [
            'breadcrumb_parts' => $breadcrumb_parts,
            'current_label'    => get_the_title(),
            'prefix_class'     => 'page-default',
        ]);
        ?>

        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('bg-white rounded-3xl border border-border shadow-card p-6 sm:p-10 lg:p-12 mt-4'); ?>>
                
                <!-- Page Header -->
                <header class="mb-8 pb-6 border-b border-border-light">
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-charcoal tracking-tight m-0 mb-3 leading-tight">
                        <?php the_title(); ?>
                    </h1>
                    <div class="flex items-center gap-4 text-xs text-charcoal-muted">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-charcoal-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            <span>Cập nhật: <?php echo esc_html(get_the_modified_date('d/m/Y')); ?></span>
                        </span>
                    </div>
                </header>

                <!-- Featured Image if exists -->
                <?php if (has_post_thumbnail()) : ?>
                    <div class="mb-8 rounded-2xl overflow-hidden border border-border">
                        <?php the_post_thumbnail('large', ['class' => 'w-full h-auto object-cover max-h-[460px]']); ?>
                    </div>
                <?php endif; ?>

                <!-- Page Content -->
                <div class="page-content prose prose-slate lg:prose-lg max-w-none text-charcoal/90 leading-relaxed prose-headings:text-charcoal prose-headings:font-bold prose-headings:tracking-tight prose-a:text-primary prose-a:no-underline hover:prose-a:underline prose-img:rounded-2xl prose-img:shadow-sm">
                    <?php
                    the_content();

                    wp_link_pages([
                        'before' => '<div class="page-links mt-6 pt-4 border-t border-border flex items-center gap-2 text-sm font-semibold text-charcoal"><span>' . esc_html__('Trang:', 'batdongsan') . '</span>',
                        'after'  => '</div>',
                    ]);
                    ?>
                </div>

            </article>
        <?php endwhile; endif; ?>

    </div>
</main>

<?php
get_footer();
