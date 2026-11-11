<?php

/**
 * Template for taxonomy: loai-hinh
 * Hiển thị danh sách bất động sản theo loại hình (BĐS dùng taxonomy này; Dự án dùng CPT loai-hinh-du-an)
 *
 * @package BatDongSan
 */

get_header();

// Lấy term hiện tại
$term = get_queried_object();
$term_id = $term->term_id;
$term_name = $term->name;
$term_slug = $term->slug;

// Loại hình taxonomy chỉ dùng cho BĐS (bat-dong-san, hang-chuyen-nhuong, nha-dat-ban)
$args = array(
    'posts_per_page' => 9,
    'post_type'      => ['bat-dong-san', 'hang-chuyen-nhuong', 'nha-dat-ban'],
    'post_status'    => 'publish',
    'orderby'        => 'post_date',
    'order'          => 'DESC',
    'tax_query'      => array(
        array(
            'taxonomy' => 'loai-hinh',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ),
    ),
);

$title = $term_name;
$price  = isset($_GET['price']) ? sanitize_text_field(wp_unslash($_GET['price'])) : null;
$status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : null;
$name   = isset($_GET['project-name']) ? trim(sanitize_text_field(wp_unslash($_GET['project-name']))) : null;
$search = false;

// Xử lý tìm kiếm theo tên dự án TRƯỚC
if ($name && !empty($name)) {
    $GLOBALS['bds_search_name'] = $name;
    add_filter('posts_where', 'bds_filter_posts_by_name', 10, 1);
    $search = true;
}

// Xử lý filter theo trạng thái
if ($status && $status != '0') {
    if (!isset($args['meta_query'])) {
        $args['meta_query'] = array('relation' => 'AND');
    }
    $args['meta_query'][] = array(
        'key' => 'status',
        'value' => $status,
        'compare' => '='
    );
    $search = true;
}

// Sử dụng function động để lấy các khoảng giá
$PriceDisplay = bds_get_price_ranges();

// Xử lý filter theo giá (chỉ khi có điều kiện)
if ($price != null && $price != '0') {
    $priceArr = explode('-', $price);
    if (!isset($args['meta_query'])) {
        $args['meta_query'] = array('relation' => 'AND');
    }
    if (count($priceArr) > 1) {
        $args['meta_query'][] = array(
            'key' => 'price',
            'value' => $priceArr[0],
            'type' => 'NUMERIC',
            'compare' => '>=',
        );
        $args['meta_query'][] = array(
            'key' => 'price',
            'value' => $priceArr[1],
            'type' => 'NUMERIC',
            'compare' => ($priceArr[1] == 5) ? '<' : '<=',
        );
    } else {
        $args['meta_query'][] = array(
            'key' => 'price',
            'value' => $price,
            'type' => 'NUMERIC',
            'compare' => '>=',
        );
    }
    $search = true;
}

$query = new WP_Query($args);
$posts = $query->posts;
wp_reset_postdata();

// Xóa filter sau khi query để tránh ảnh hưởng đến các query khác
if ($name && !empty($name)) {
    remove_filter('posts_where', 'bds_filter_posts_by_name', 10);
    unset($GLOBALS['bds_search_name']);
}

// Lấy URL của taxonomy term để dùng trong form
$taxonomy_url = get_term_link($term);

// Query banners
$project_banners_query = new WP_Query([
    'post_type' => 'banner',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'tax_query' => [
        [
            'taxonomy' => 'loai-banner',
            'field' => 'slug',
            'terms' => 'trang-du-an',
        ],
    ],
    'orderby' => 'menu_order',
    'order' => 'ASC',
    'no_found_rows' => true,
]);

$project_banners = [];
if ($project_banners_query->have_posts()) {
    while ($project_banners_query->have_posts()) {
        $project_banners_query->the_post();
        $banner_image_id = get_field('banner_image', get_the_ID());
        if (!$banner_image_id && has_post_thumbnail()) {
            $banner_image_id = get_post_thumbnail_id();
        }
        $project_banners[] = [
            'image' => $banner_image_id,
            'title' => get_the_title(),
            'link' => get_field('banner_link', get_the_ID()),
        ];
    }
    wp_reset_postdata();
}

// Fallback query nếu không có banners (BĐS theo loại hình)
$banner_projects = null;
if (empty($project_banners)) {
    $banner_projects = new WP_Query([
        'post_type'      => ['bat-dong-san', 'hang-chuyen-nhuong', 'nha-dat-ban'],
        'posts_per_page' => 5,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'tax_query'      => [
            [
                'taxonomy' => 'loai-hinh',
                'field'    => 'term_id',
                'terms'    => $term_id,
            ],
        ],
        'no_found_rows'  => true,
    ]);
}

?>

<main>
    <?php
    // Hero Banner Swiper - sử dụng template part
    get_template_part('template-parts/hero-banner-swiper', null, [
        'swiper_class' => 'hero-projects-swiper',
        'slide_class' => 'hero-projects',
        'banners' => $project_banners,
        'fallback_query' => empty($project_banners) ? $banner_projects : null,
        'eyebrow' => $term_name,
        'fallback_type' => 'project',
    ]);
    ?>

    <?php
    get_template_part('template-parts/search-form-unified', null, [
        'form_action'    => $taxonomy_url,
        'title'          => 'Tìm Bất Động Sản Lý Tưởng Của Bạn',
        'current_name'   => $name ?: '',
        'current_price'  => $price ?: '0',
        'current_status' => $status ?: '0',
        'show_area'      => false,
        'show_type'      => false,
        'show_price'     => true,
        'show_status'    => true,
    ]);
    ?>

    <section class="section section--projects py-16">
        <div class="max-w-7xl mx-auto px-4">
            <div class="row">
                <div class="large-12 col page-content">
                    <?php if ($search): ?>
                        <h2 class="text-2xl font-bold text-gray-900 m-0 mb-6 text-left max-md:text-xl">Kết quả tìm kiếm phù hợp các tiêu chí:</h2>
                        <?php
                        echo '<ul class="list-none p-0 m-0 mb-6 flex flex-wrap gap-2">';
                        echo '<li>Loại hình: <strong>' . esc_html($term_name) . '</strong></li>';
                        echo ($name) ? '<li>Tên dự án theo từ khóa: <strong>' . esc_html($name) . '</strong></li>' : '';
                        echo ($price != null && $price != '0') ? '<li>Giá sản phẩm: <strong>' . esc_html($PriceDisplay[$price]) . '</strong></li>' : '';
                        if ($status && $status != '0') {
                            $status_options = array(
                                'dang-mo-ban' => 'Đang mở bán',
                                'sap-mo-ban' => 'Sắp mở bán',
                                'da-ban-het' => 'Đã bán hết',
                                'tam-dung' => 'Tạm dừng',
                                'dang-cap-nhat' => 'Đang cập nhật',
                            );
                            $status_label = isset($status_options[$status]) ? $status_options[$status] : $status;
                            echo '<li>Trạng thái: <strong>' . esc_html($status_label) . '</strong></li>';
                        }
                        echo '</ul>';
                        ?>
                    <?php else: ?>
                        <?php
                        get_template_part('template-parts/section-header', null, [
                            'title' => $title,
                            'alignment' => 'center',
                        ]);
                        ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="large-12 col">
                    <div class="grid gap-5 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                        <?php
                        if (!empty($posts)) {
                            foreach ($posts as $value) {
                                get_template_part('template-parts/card-project', null, [
                                    'post' => $value,
                                ]);
                            }
                        } else {
                            echo '<p class="empty">Không tìm thấy dự án nào.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Block BĐS cho bạn -->
    <section id="for-you" class="section-for-you py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="row">
                <div class="large-12 col section-for-you__container">
                    <?php
                    get_template_part('template-parts/section-header', null, [
                        'title' => 'Hàng chuyển nhượng nổi bật',
                        'alignment' => 'center',
                    ]);
                    ?>
                    <?= do_shortcode('[BDS_HangChuyenNhuong]') ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>