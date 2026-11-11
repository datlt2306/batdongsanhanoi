<?php

/**
 * Template part: Card bài viết cho khu vực tin tức (index & category tin)
 *
 * Sử dụng trong: index.php, category-tin-du-an.php, category-tin-thi-truong.php
 */

if (!isset($args)) {
    $args = [];
}
?>

<article <?php post_class('news-post flex flex-col bg-white rounded-xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-md transition-shadow'); ?>>
    <div class="flex flex-col md:flex-row gap-4">
        <div class="relative w-full md:w-48 h-40 md:h-auto shrink-0 overflow-hidden rounded-t-xl md:rounded-l-xl md:rounded-t-none">
            <a href="<?php the_permalink(); ?>" class="block w-full h-full">
                <?php
                if (has_post_thumbnail()) {
                    the_post_thumbnail('medium', ['class' => 'w-full h-full object-cover']);
                } else {
                    echo '<div class="w-full h-full bg-gray-200"></div>';
                }
                ?>
            </a>

            <div class="absolute top-2 left-2 px-2 py-1 bg-[#b20000] text-white text-xs font-bold rounded">TIN TỨC</div>
        </div>

        <div class="flex-1 p-4">
            <div class="text-xs text-gray-500 mb-2">
                <span class="">
                    <?php echo esc_html(get_the_date('d/m/Y H:i')); ?>
                </span>
            </div>

            <h2 class="text-base font-semibold text-gray-900 m-0 mb-2 leading-snug line-clamp-2">
                <a href="<?php the_permalink(); ?>" class="hover:text-[#b20000] transition-colors">
                    <?php the_title(); ?>
                </a>
            </h2>

            <div class="text-sm text-gray-600 line-clamp-2">
                <?php
                if (has_excerpt()) {
                    the_excerpt();
                } else {
                    echo wp_kses_post(wp_trim_words(get_the_content(), 30, '...'));
                }
                ?>
            </div>
        </div>
    </div>

</article>