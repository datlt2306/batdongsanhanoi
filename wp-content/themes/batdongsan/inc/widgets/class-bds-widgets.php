<?php

/**
 * Custom widgets cho BĐS & Dự án
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget: Thẻ môi giới (Advisor Card)
 */
class BDS_Widget_Advisor_Card extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'bds_advisor_card',
            __('BĐS: Thẻ môi giới', 'batdongsan'),
            ['description' => __('Hiển thị thông tin môi giới / tư vấn BĐS', 'batdongsan')]
        );
    }

    public function widget($args, $instance) {
        $advisor = batdongsan_get_advisor_data();
        get_template_part('template-parts/advisor-card', null, $advisor);
    }

    public function form($instance) {
        echo '<p class="text-gray-500">' . esc_html__('Thông tin môi giới lấy từ Cài đặt ACF Options.', 'batdongsan') . '</p>';
    }

    public function update($new_instance, $old_instance) {
        return $old_instance;
    }
}

/**
 * Widget: Ảnh GIF quảng cáo dự án
 */
class BDS_Widget_Promo_Gif extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'bds_promo_gif',
            __('BĐS: Ảnh GIF dự án', 'batdongsan'),
            ['description' => __('Hiển thị ảnh GIF động quảng cáo dự án BĐS', 'batdongsan')]
        );
    }

    public function widget($args, $instance) {
        $url  = !empty($instance['gif_url']) ? $instance['gif_url'] : get_template_directory_uri() . '/assets/images/promo-project.gif';
        $link = !empty($instance['link_url']) ? $instance['link_url'] : home_url('/du-an');
        $url  = apply_filters('batdongsan_sidebar_promo_gif_url', $url);
        $link = apply_filters('batdongsan_sidebar_promo_gif_link', $link);

        if (empty($url)) {
            return;
        }

        echo $args['before_widget'];
        ?>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <a href="<?= esc_url($link); ?>" class="block" aria-label="Xem dự án bất động sản">
                <img src="<?= esc_url($url); ?>" alt="Dự án bất động sản" class="w-full h-auto object-cover" loading="lazy" decoding="async">
            </a>
        </div>
        <?php
        echo $args['after_widget'];
    }

    public function form($instance) {
        $gif_url  = !empty($instance['gif_url']) ? $instance['gif_url'] : '';
        $link_url = !empty($instance['link_url']) ? $instance['link_url'] : home_url('/du-an');
        ?>
        <p>
            <label for="<?= esc_attr($this->get_field_id('gif_url')); ?>"><?= esc_html__('URL ảnh GIF:', 'batdongsan'); ?></label>
            <input type="url" class="widefat" id="<?= esc_attr($this->get_field_id('gif_url')); ?>" name="<?= esc_attr($this->get_field_name('gif_url')); ?>" value="<?= esc_attr($gif_url); ?>" placeholder="<?= esc_attr(get_template_directory_uri()); ?>/assets/images/promo-project.gif">
        </p>
        <p>
            <label for="<?= esc_attr($this->get_field_id('link_url')); ?>"><?= esc_html__('Link khi click:', 'batdongsan'); ?></label>
            <input type="url" class="widefat" id="<?= esc_attr($this->get_field_id('link_url')); ?>" name="<?= esc_attr($this->get_field_name('link_url')); ?>" value="<?= esc_attr($link_url); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['gif_url']  = esc_url_raw($new_instance['gif_url'] ?? '');
        $instance['link_url'] = esc_url_raw($new_instance['link_url'] ?? '');
        return $instance;
    }
}

/**
 * Widget: Dự án theo khu vực (chỉ single BĐS)
 */
class BDS_Widget_Related_Projects extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'bds_related_projects',
            __('BĐS: Dự án theo khu vực', 'batdongsan'),
            ['description' => __('Hiển thị dự án liên quan theo khu vực (chỉ trang chi tiết BĐS)', 'batdongsan')]
        );
    }

    public function widget($args, $instance) {
        $post_id = is_singular('bat-dong-san') ? get_the_ID() : 0;
        if (!$post_id) {
            return;
        }

        $dia_chi = get_field('dia_chi', $post_id);
        $khu_vuc_name = '';
        if ($dia_chi) {
            $parts = explode(',', $dia_chi);
            if (count($parts) >= 2) {
                $khu_vuc_name = trim(end($parts));
            }
        }

        if (!$khu_vuc_name) {
            return;
        }

        $q = new WP_Query([
            'post_type'      => 'du-an',
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
            'meta_query'     => [
                ['key' => 'address', 'value' => $khu_vuc_name, 'compare' => 'LIKE'],
            ],
        ]);

        if (!$q->have_posts()) {
            wp_reset_postdata();
            return;
        }

        echo $args['before_widget'];
        echo '<div class="bg-white rounded-xl border border-gray-200 p-5">';
        echo '<h3 class="text-base font-bold text-gray-900 m-0 mb-4">Bất động sản theo dự án tại ' . esc_html($khu_vuc_name) . '</h3>';
        echo '<ul class="list-none p-0 m-0 space-y-2">';
        $bds_publish_count = (int) wp_count_posts('bat-dong-san')->publish;
        while ($q->have_posts()) {
            $q->the_post();
            echo '<li><a href="' . esc_url(get_permalink()) . '" class="flex items-center justify-between text-sm text-gray-700 hover:text-[#b20000] py-2 border-b border-gray-100 last:border-0"><span class="line-clamp-1">' . esc_html(get_the_title()) . '</span><span class="text-xs text-gray-400">(' . $bds_publish_count . ')</span></a></li>';
        }
        echo '</ul></div>';
        wp_reset_postdata();
        echo $args['after_widget'];
    }

    public function form($instance) {
        echo '<p class="text-gray-500">' . esc_html__('Chỉ hiển thị trên trang chi tiết BĐS, lấy khu vực từ địa chỉ.', 'batdongsan') . '</p>';
    }

    public function update($new_instance, $old_instance) {
        return $old_instance;
    }
}

/**
 * Widget: Bất động sản nổi bật
 */
class BDS_Widget_Featured_Listings extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'bds_featured_listings',
            __('BĐS: Bất động sản nổi bật', 'batdongsan'),
            ['description' => __('Hiển thị danh sách BĐS nổi bật (featured)', 'batdongsan')]
        );
    }

    public function widget($args, $instance) {
        $count = !empty($instance['count']) ? (int) $instance['count'] : 5;
        $exclude = !empty($instance['exclude_current']) && is_singular('bat-dong-san') ? [get_the_ID()] : [];

        $q = new WP_Query([
            'post_type'      => 'bat-dong-san',
            'posts_per_page' => $count,
            'post__not_in'   => $exclude,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
            'meta_query'     => [['key' => 'featured', 'value' => '1', 'compare' => '=']],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        if (!$q->have_posts()) {
            wp_reset_postdata();
            return;
        }

        echo $args['before_widget'];
        echo '<div class="bg-white rounded-xl border border-gray-200 p-5">';
        echo '<h3 class="text-base font-bold text-gray-900 m-0 mb-4">Bất động sản nổi bật</h3>';
        echo '<ul class="list-none p-0 m-0 space-y-2">';
        while ($q->have_posts()) {
            $q->the_post();
            echo '<li><a href="' . esc_url(get_permalink()) . '" class="block text-sm text-gray-700 hover:text-[#b20000] py-2 border-b border-gray-100 last:border-0 line-clamp-2">' . esc_html(get_the_title()) . '</a></li>';
        }
        echo '</ul></div>';
        wp_reset_postdata();
        echo $args['after_widget'];
    }

    public function form($instance) {
        $count = !empty($instance['count']) ? (int) $instance['count'] : 5;
        ?>
        <p>
            <label for="<?= esc_attr($this->get_field_id('count')); ?>"><?= esc_html__('Số tin:', 'batdongsan'); ?></label>
            <input type="number" class="tiny-text" id="<?= esc_attr($this->get_field_id('count')); ?>" name="<?= esc_attr($this->get_field_name('count')); ?>" value="<?= esc_attr($count); ?>" min="1" max="20">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['count'] = max(1, min(20, (int) ($new_instance['count'] ?? 5)));
        return $instance;
    }
}

/**
 * Widget: Bài viết được quan tâm / Tin mới
 */
class BDS_Widget_Posts_Of_Interest extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'bds_posts_of_interest',
            __('BĐS: Bài viết được quan tâm', 'batdongsan'),
            ['description' => __('Hiển thị bài viết theo lượt xem hoặc mới nhất', 'batdongsan')]
        );
    }

    public function widget($args, $instance) {
        $count = !empty($instance['count']) ? (int) $instance['count'] : 5;

        $q = new WP_Query([
            'post_type'           => 'post',
            'posts_per_page'      => $count,
            'ignore_sticky_posts' => true,
            'post_status'         => 'publish',
            'no_found_rows'       => true,
            'meta_key'            => 'views',
            'orderby'             => 'meta_value_num',
            'order'               => 'DESC',
        ]);

        if (!$q->have_posts()) {
            wp_reset_postdata();
            $q = new WP_Query([
                'post_type'      => 'post',
                'posts_per_page' => $count,
                'post_status'    => 'publish',
                'no_found_rows'  => true,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]);
        }

        if (!$q->have_posts()) {
            wp_reset_postdata();
            return;
        }

        echo $args['before_widget'];
        echo '<div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">';
        echo '<h3 class="text-base font-bold text-gray-900 m-0 mb-4 pb-3 border-b border-gray-200">Bài viết được quan tâm</h3>';
        echo '<ul class="list-none p-0 m-0 flex flex-col gap-4">';
        while ($q->have_posts()) {
            $q->the_post();
            ?>
            <li class="flex gap-3 items-start">
                <a href="<?php the_permalink(); ?>" class="flex gap-3 items-start no-underline group">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="w-20 h-14 rounded-md overflow-hidden bg-gray-200 shrink-0">
                            <?php the_post_thumbnail('thumbnail', ['class' => 'w-full h-full object-cover transition-transform duration-300 group-hover:scale-105']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex flex-col flex-1 min-w-0">
                        <h4 class="text-sm font-semibold text-gray-900 m-0 mb-1 leading-snug line-clamp-2 group-hover:text-[#b20000] transition-colors"><?php the_title(); ?></h4>
                        <span class="text-xs text-gray-400"><?= esc_html(get_the_date('d/m/Y')); ?></span>
                    </div>
                </a>
            </li>
            <?php
        }
        echo '</ul></div>';
        wp_reset_postdata();
        echo $args['after_widget'];
    }

    public function form($instance) {
        $count = !empty($instance['count']) ? (int) $instance['count'] : 5;
        ?>
        <p>
            <label for="<?= esc_attr($this->get_field_id('count')); ?>"><?= esc_html__('Số bài:', 'batdongsan'); ?></label>
            <input type="number" class="tiny-text" id="<?= esc_attr($this->get_field_id('count')); ?>" name="<?= esc_attr($this->get_field_name('count')); ?>" value="<?= esc_attr($count); ?>" min="1" max="20">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['count'] = max(1, min(20, (int) ($new_instance['count'] ?? 5)));
        return $instance;
    }
}

/**
 * Widget: Tin dự án / Tin tức mới
 */
class BDS_Widget_Project_News extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'bds_project_news',
            __('BĐS: Tin dự án', 'batdongsan'),
            ['description' => __('Hiển thị tin tức mới nhất', 'batdongsan')]
        );
    }

    public function widget($args, $instance) {
        $count = !empty($instance['count']) ? (int) $instance['count'] : 6;

        $q = new WP_Query([
            'post_type'      => 'post',
            'posts_per_page' => $count,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ]);

        if (!$q->have_posts()) {
            wp_reset_postdata();
            return;
        }

        echo $args['before_widget'];
        echo '<div class="bg-white rounded-[10px] p-5 border border-gray-200 shadow-[0_2px_8px_rgba(0,0,0,0.08)]">';
        echo '<h3 class="text-base font-bold text-gray-900 m-0 mb-4 pb-3 border-b-2 border-gray-200">Tin dự án</h3>';
        echo '<ul class="list-none p-0 m-0 flex flex-col gap-4">';
        while ($q->have_posts()) {
            $q->the_post();
            ?>
            <li class="flex gap-3 items-start">
                <?php if (has_post_thumbnail()) : ?>
                    <a href="<?php the_permalink(); ?>" class="block w-20 h-14 rounded-md overflow-hidden bg-gray-200 shrink-0">
                        <?php the_post_thumbnail('thumbnail', ['class' => 'w-full h-full object-cover']); ?>
                    </a>
                <?php endif; ?>
                <div class="flex flex-col flex-1 min-w-0">
                    <h4 class="text-sm font-semibold text-gray-900 m-0 mb-1 leading-snug">
                        <a href="<?php the_permalink(); ?>" class="text-inherit no-underline hover:text-[#b20000] transition-colors line-clamp-2"><?php the_title(); ?></a>
                    </h4>
                    <time class="text-xs text-gray-400" datetime="<?= esc_attr(get_the_date('c')); ?>"><?= esc_html(get_the_date('d/m/Y')); ?></time>
                </div>
            </li>
            <?php
        }
        echo '</ul>';
        echo '<a href="' . esc_url(home_url('/tin-tuc')) . '" class="inline-flex items-center gap-2 text-[#b20000] font-medium text-sm no-underline hover:underline mt-4">Xem tất cả tin tức <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></a>';
        echo '</div>';
        wp_reset_postdata();
        echo $args['after_widget'];
    }

    public function form($instance) {
        $count = !empty($instance['count']) ? (int) $instance['count'] : 6;
        ?>
        <p>
            <label for="<?= esc_attr($this->get_field_id('count')); ?>"><?= esc_html__('Số tin:', 'batdongsan'); ?></label>
            <input type="number" class="tiny-text" id="<?= esc_attr($this->get_field_id('count')); ?>" name="<?= esc_attr($this->get_field_name('count')); ?>" value="<?= esc_attr($count); ?>" min="1" max="20">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['count'] = max(1, min(20, (int) ($new_instance['count'] ?? 6)));
        return $instance;
    }
}

/**
 * Widget: Liên hệ tư vấn
 */
class BDS_Widget_Contact extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'bds_contact',
            __('BĐS: Liên hệ tư vấn', 'batdongsan'),
            ['description' => __('Hiển thị block liên hệ tư vấn', 'batdongsan')]
        );
    }

    public function widget($args, $instance) {
        $phone = !empty($instance['phone']) ? $instance['phone'] : '0934699191';
        $title = !empty($instance['title']) ? $instance['title'] : 'Liên hệ tư vấn';
        $text  = !empty($instance['text']) ? $instance['text'] : 'Bạn cần tư vấn về dự án? Liên hệ ngay với chúng tôi!';

        echo $args['before_widget'];
        ?>
        <div class="bg-gradient-to-br from-[#b20000] to-[#8b0000] rounded-[10px] p-5 text-white">
            <h3 class="text-base font-bold text-white m-0 mb-3"><?= esc_html($title); ?></h3>
            <p class="text-white/80 text-sm m-0 mb-4"><?= esc_html($text); ?></p>
            <a href="tel:<?= esc_attr(preg_replace('/\s+/', '', $phone)); ?>" class="inline-flex items-center gap-2 px-5 py-3 bg-white text-[#b20000] rounded-lg font-bold text-sm no-underline shadow-lg hover:shadow-xl transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
                <?= esc_html($phone); ?>
            </a>
        </div>
        <?php
        echo $args['after_widget'];
    }

    public function form($instance) {
        $phone = !empty($instance['phone']) ? $instance['phone'] : '0934699191';
        $title = !empty($instance['title']) ? $instance['title'] : 'Liên hệ tư vấn';
        $text  = !empty($instance['text']) ? $instance['text'] : 'Bạn cần tư vấn về dự án? Liên hệ ngay với chúng tôi!';
        ?>
        <p>
            <label for="<?= esc_attr($this->get_field_id('title')); ?>"><?= esc_html__('Tiêu đề:', 'batdongsan'); ?></label>
            <input type="text" class="widefat" id="<?= esc_attr($this->get_field_id('title')); ?>" name="<?= esc_attr($this->get_field_name('title')); ?>" value="<?= esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?= esc_attr($this->get_field_id('text')); ?>"><?= esc_html__('Mô tả:', 'batdongsan'); ?></label>
            <input type="text" class="widefat" id="<?= esc_attr($this->get_field_id('text')); ?>" name="<?= esc_attr($this->get_field_name('text')); ?>" value="<?= esc_attr($text); ?>">
        </p>
        <p>
            <label for="<?= esc_attr($this->get_field_id('phone')); ?>"><?= esc_html__('Số điện thoại:', 'batdongsan'); ?></label>
            <input type="text" class="widefat" id="<?= esc_attr($this->get_field_id('phone')); ?>" name="<?= esc_attr($this->get_field_name('phone')); ?>" value="<?= esc_attr($phone); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['phone'] = sanitize_text_field($new_instance['phone'] ?? '');
        $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
        $instance['text']  = sanitize_text_field($new_instance['text'] ?? '');
        return $instance;
    }
}

/**
 * Widget: Lọc theo khoảng giá (chỉ page BĐS, cần context từ template)
 */
class BDS_Widget_Price_Filter extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'bds_price_filter',
            __('BĐS: Lọc theo khoảng giá', 'batdongsan'),
            ['description' => __('Hiển thị filter theo khoảng giá (chỉ trang danh sách BĐS)', 'batdongsan')]
        );
    }

    public function widget($args, $instance) {
        $ctx = $GLOBALS['bds_sidebar_filter_context'] ?? null;
        if (!$ctx || empty($ctx['PriceDisplay'])) {
            return;
        }
        $PriceDisplay = $ctx['PriceDisplay'];
        $price        = $ctx['price'] ?? null;
        $page_url     = $ctx['page_url'] ?? home_url('/bat-dong-san');
        $area         = $ctx['area'] ?? null;
        $du_an        = $ctx['du_an'] ?? 0;
        $name         = $ctx['project_name'] ?? null;
        $query_args   = array_filter(['du_an' => $du_an ?: null, 'project-name' => $name ?: null]);

        $has_options = false;
        foreach ($PriceDisplay as $k => $l) {
            if ($k !== '0' && $k !== 0) {
                $has_options = true;
                break;
            }
        }
        if (!$has_options) {
            return;
        }

        echo $args['before_widget'];
        echo '<div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">';
        echo '<h3 class="text-base font-bold text-gray-900 m-0 mb-4 pb-3 border-b border-gray-200">Lọc theo khoảng giá</h3>';
        echo '<ul class="list-none p-0 m-0 flex flex-col gap-2">';
        foreach ($PriceDisplay as $value => $label) {
            if ($value === '0' || $value === 0) continue;
            $is_active = $price === (string) $value;
            $price_url = batdongsan_bds_filter_url($page_url, $is_active ? null : $value, $area ?: null, $query_args);
            echo '<li><a href="' . esc_url($price_url) . '" class="block py-2.5 px-3.5 text-gray-600 no-underline text-sm rounded-md bg-gray-50 transition-all duration-200 hover:bg-gray-100 hover:text-gray-900' . ($is_active ? ' !bg-[#b20000] !text-white' : '') . '">' . esc_html($label) . '</a></li>';
        }
        echo '</ul></div>';
        echo $args['after_widget'];
    }

    public function form($instance) {
        echo '<p class="text-gray-500">' . esc_html__('Chỉ hiển thị trên trang danh sách BĐS.', 'batdongsan') . '</p>';
    }

    public function update($new_instance, $old_instance) {
        return $old_instance;
    }
}

/**
 * Widget: Lọc theo diện tích (chỉ page BĐS, cần context từ template)
 */
class BDS_Widget_Area_Filter extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'bds_area_filter',
            __('BĐS: Lọc theo diện tích', 'batdongsan'),
            ['description' => __('Hiển thị filter theo diện tích (chỉ trang danh sách BĐS)', 'batdongsan')]
        );
    }

    public function widget($args, $instance) {
        $ctx = $GLOBALS['bds_sidebar_filter_context'] ?? null;
        if (!$ctx || empty($ctx['AreaDisplay'])) {
            return;
        }
        $AreaDisplay = $ctx['AreaDisplay'];
        $area        = $ctx['area'] ?? null;
        $page_url    = $ctx['page_url'] ?? home_url('/bat-dong-san');
        $price       = $ctx['price'] ?? null;
        $du_an       = $ctx['du_an'] ?? 0;
        $name        = $ctx['project_name'] ?? null;
        $query_args  = array_filter(['du_an' => $du_an ?: null, 'project-name' => $name ?: null]);

        echo $args['before_widget'];
        echo '<div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">';
        echo '<h3 class="text-base font-bold text-gray-900 m-0 mb-4 pb-3 border-b border-gray-200">Lọc theo diện tích</h3>';
        echo '<ul class="list-none p-0 m-0 flex flex-col gap-2">';
        foreach ($AreaDisplay as $value => $label) {
            $is_active = $area === (string) $value;
            $area_url  = batdongsan_bds_filter_url($page_url, $price ?: null, $is_active ? null : $value, $query_args);
            echo '<li><a href="' . esc_url($area_url) . '" class="block py-2.5 px-3.5 text-gray-600 no-underline text-sm rounded-md bg-gray-50 transition-all duration-200 hover:bg-gray-100 hover:text-gray-900' . ($is_active ? ' !bg-[#b20000] !text-white' : '') . '">' . esc_html($label) . '</a></li>';
        }
        echo '</ul></div>';
        echo $args['after_widget'];
    }

    public function form($instance) {
        echo '<p class="text-gray-500">' . esc_html__('Chỉ hiển thị trên trang danh sách BĐS.', 'batdongsan') . '</p>';
    }

    public function update($new_instance, $old_instance) {
        return $old_instance;
    }
}
