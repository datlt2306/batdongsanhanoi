<?php

/**
 * Sidebar template
 *
 * Hiển thị sidebar cho các trang tin tức với widget "Bài viết mới nhất" và "Bài viết được xem nhiều nhất"
 */
?>

<aside class="flex flex-col gap-6" aria-label="<?php esc_attr_e('Sidebar', 'batdongsan'); ?>">
    <?php if (is_active_sidebar('sidebar-1')) : ?>
        <?php dynamic_sidebar('sidebar-1'); ?>
    <?php else : ?>
        <!-- Mặc định hiển thị Bài viết mới nhất nếu chưa có widget -->
        <div class="bg-white rounded-2xl p-5 border border-black/[0.06] shadow-[0_2px_12px_rgba(0,0,0,0.03)]">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-charcoal/70 m-0 mb-3.5 pb-2.5 border-b border-black/[0.06]"><?php esc_html_e('Bài viết mới nhất', 'batdongsan'); ?></h3>
            <ul class="list-none p-0 m-0 flex flex-col gap-3">
                <?php
                $recent_posts = wp_get_recent_posts(
                    array(
                        'numberposts' => 5,
                        'post_status' => 'publish',
                    )
                );
                foreach ($recent_posts as $post) :
                    $post_id = $post['ID'];
                    $thumbnail = get_the_post_thumbnail($post_id, 'thumbnail', ['class' => 'w-full h-full object-cover']);
                ?>
                    <li class="flex gap-3 items-start">
                        <?php if ($thumbnail) : ?>
                            <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="block w-16 h-12 rounded-xl overflow-hidden bg-stone shrink-0 border border-black/[0.06]">
                                <?php echo $thumbnail; ?>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="text-xs font-medium text-charcoal leading-snug line-clamp-2 hover:text-primary transition-colors">
                            <?php echo esc_html($post['post_title']); ?>
                        </a>
                    </li>
                <?php
                endforeach;
                wp_reset_query();
                ?>
            </ul>
        </div>

        <!-- Bài viết được xem nhiều nhất -->
        <div class="bg-white rounded-2xl p-5 border border-black/[0.06] shadow-[0_2px_12px_rgba(0,0,0,0.03)]">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-charcoal/70 m-0 mb-3.5 pb-2.5 border-b border-black/[0.06]"><?php esc_html_e('Bài viết được xem nhiều nhất', 'batdongsan'); ?></h3>
            <ul class="list-none p-0 m-0 flex flex-col gap-2">
                <?php
                $popular_args = array(
                    'post_type'           => 'post',
                    'posts_per_page'      => 5,
                    'ignore_sticky_posts' => true,
                    'post_status'         => 'publish',
                    'meta_key'            => 'views',
                    'orderby'             => 'meta_value_num',
                    'order'               => 'DESC',
                    'no_found_rows'       => true,
                );
                $popular_query = new WP_Query($popular_args);

                if ($popular_query->have_posts()) :
                    while ($popular_query->have_posts()) : $popular_query->the_post();
                ?>
                        <li>
                            <a href="<?php the_permalink(); ?>" class="block py-2.5 px-3.5 text-gray-600 no-underline text-sm rounded-md bg-gray-50 transition-all duration-200 hover:bg-gray-100 hover:text-gray-900">
                                <?php the_title(); ?>
                            </a>
                        </li>
                    <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    // Nếu không có bài viết nào có meta 'views', hiển thị bài viết mới nhất thay thế
                    $fallback_posts = wp_get_recent_posts(
                        array(
                            'numberposts' => 5,
                            'post_status' => 'publish',
                        )
                    );
                    foreach ($fallback_posts as $post) :
                    ?>
                        <li>
                            <a href="<?php echo esc_url(get_permalink($post['ID'])); ?>" class="block py-2.5 px-3.5 text-gray-600 no-underline text-sm rounded-md bg-gray-50 transition-all duration-200 hover:bg-gray-100 hover:text-gray-900">
                                <?php echo esc_html($post['post_title']); ?>
                            </a>
                        </li>
                <?php
                    endforeach;
                    wp_reset_query();
                endif;
                ?>
            </ul>
        </div>
    <?php endif; ?>
</aside>