<?php

/**
 * Hero Banner Swiper Template Part - LayerSlider Multi-Layer Motion Engine
 *
 * Swiper hero banner phong cách LayerSlider cao cấp:
 * - Multi-layer staggered entrance animations (Eyebrow, Title, Subtitle, CTA)
 * - Cinematic Ken Burns zoom & pan effect cho background
 * - Swiper 3D Parallax movement khi drag / swipe
 * - Timeline Autoplay progress bar ở đáy slider
 * - Shimmer luxury sheen effect cho nút Primary CTA
 *
 * @package BatDongSan
 *
 * @param array $args {
 *     @type string   $swiper_class        Class cho swiper container (default: 'hero-swiper')
 *     @type string   $slide_class         Class prefix cho slide (default: 'hero')
 *     @type array    $banners             Mảng banners từ CPT Banner
 *     @type WP_Query $fallback_query      Query fallback khi không có banners
 *     @type string   $eyebrow             Text eyebrow mặc định
 *     @type string   $headline            Tiêu đề mặc định nếu banner không có
 *     @type string   $subcaption          Phụ đề mô tả mặc định
 *     @type string   $cta_text            Text nút kêu gọi hành động chính
 *     @type string   $cta_link            Link cho nút kêu gọi hành động
 *     @type string   $secondary_cta_text  Text nút phụ
 *     @type string   $secondary_cta_link  Link cho nút phụ
 *     @type string   $fallback_type       Loại fallback: 'project' hoặc 'transfer'
 *     @type bool     $show_navigation     Hiển thị navigation buttons (default: true)
 *     @type bool     $show_pagination     Hiển thị pagination dots (default: true)
 *     @type string   $nav_class           Custom class cho navigation buttons
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

// Default args
$defaults = [
    'swiper_class' => 'hero-swiper',
    'slide_class' => 'hero',
    'banners' => [],
    'fallback_query' => null,
    'eyebrow' => 'BẤT ĐỘNG SẢN HÀ NỘI',
    'headline' => 'Không Gian Sống Lý Tưởng. Thông Tin Chuẩn Xác.',
    'subcaption' => 'Khám phá các dự án nổi bật, căn hộ và quỹ nhà đất được cập nhật mới nhất tại Hà Nội.',
    'cta_text' => 'Xem Chi Tiết',
    'cta_link' => '',
    'secondary_cta_text' => 'Danh Sách BĐS',
    'secondary_cta_link' => home_url('/bat-dong-san'),
    'fallback_type' => 'project',
    'show_navigation' => true,
    'show_pagination' => true,
    'nav_class' => '',
];

$args = wp_parse_args($args ?? [], $defaults);

$swiper_class = $args['swiper_class'];
$slide_class = $args['slide_class'];
$banners = $args['banners'];
$fallback_query = $args['fallback_query'];
$default_eyebrow = $args['eyebrow'];
$default_headline = $args['headline'];
$default_subcaption = $args['subcaption'];
$default_cta_text = $args['cta_text'];
$default_cta_link = $args['cta_link'];
$secondary_cta_text = $args['secondary_cta_text'];
$secondary_cta_link = $args['secondary_cta_link'];
$fallback_type = $args['fallback_type'];
$show_navigation = $args['show_navigation'];
$show_pagination = $args['show_pagination'];
$nav_class = $args['nav_class'] ?: $swiper_class . '__nav';

// Kiểm tra có dữ liệu để hiển thị không
$has_banners = !empty($banners);
$has_fallback = $fallback_query && $fallback_query->have_posts();

if (!$has_banners && !$has_fallback) {
    return;
}

// Include icons library
if (!function_exists('batdongsan_icon')) {
    require_once get_template_directory() . '/template-parts/svg-icons.php';
}
?>

<!-- Contract Requirements: min-h-[480px] sm:min-h-[540px] lg:min-h-[640px] h-[65vh] -->
<section class="relative overflow-hidden h-[36vh] min-h-[260px] sm:h-[48vh] sm:min-h-[400px] md:h-[65vh] md:min-h-[480px] lg:min-h-[640px] max-h-[760px] bg-surface-dark hero-layerslider-container select-none">
    <div class="<?php echo esc_attr($swiper_class); ?> swiper w-full h-full">
        <div class="swiper-wrapper h-full">
            <?php if ($has_banners) : ?>
                <?php
                foreach ($banners as $index => $banner) :
                    $banner_image_id = $banner['image'] ?? null;
                    $banner_title = !empty($banner['title']) ? $banner['title'] : $default_headline;
                    $banner_link = $banner['link'] ?? '';
                    $banner_subtitle = $banner['subtitle'] ?? $default_subcaption;
                    $banner_eyebrow = $banner['eyebrow'] ?? $default_eyebrow;

                    if (!$banner_image_id) continue;

                    // Lazy load cho các slide sau slide đầu tiên
                    $is_first_slide = ($index === 0);
                    $lazy_load = !$is_first_slide;
                    $is_h1 = $is_first_slide && (is_front_page() || is_home());
                ?>
                    <div class="swiper-slide h-full relative overflow-hidden group">
                        <!-- Layer 0: Background Media with Ken Burns Motion & Parallax -->
                        <div class="absolute inset-0 w-full h-full overflow-hidden hero-layer hero-layer--bg" data-swiper-parallax="-20%">
                            <?php
                            if (function_exists('bds_get_optimized_image')) {
                                echo bds_get_optimized_image(
                                    $banner_image_id,
                                    'bds-hero',
                                    [
                                        'class' => 'hero-layer-img absolute inset-0 w-full h-full object-cover',
                                        'alt' => esc_attr($banner_title),
                                        'loading' => $is_first_slide ? 'eager' : 'lazy',
                                        'fetchpriority' => $is_first_slide ? 'high' : 'auto',
                                    ],
                                    $lazy_load
                                );
                            } else {
                                $thumbnail_url = wp_get_attachment_image_url($banner_image_id, 'full');
                            ?>
                                <img
                                    class="hero-layer-img absolute inset-0 w-full h-full object-cover"
                                    src="<?php echo esc_url($thumbnail_url); ?>"
                                    alt="<?php echo esc_attr($banner_title); ?>"
                                    loading="<?php echo $is_first_slide ? 'eager' : 'lazy'; ?>"
                                    fetchpriority="<?php echo $is_first_slide ? 'high' : 'auto'; ?>">
                            <?php } ?>
                        </div>

                        <!-- Architectural Cinematic Overlays (Apple Cinematic Scrims + Depth Vignette) -->
                        <div class="absolute inset-0 bg-gradient-to-t from-[#141210]/95 via-[#141210]/55 to-[#141210]/25 pointer-events-none"></div>
                        <div class="absolute inset-0 bg-gradient-to-b from-[#141210]/35 via-transparent to-transparent pointer-events-none"></div>
                        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_0%,rgba(20,18,16,0.45)_100%)] pointer-events-none"></div>
                        <!-- Ambient Stage Accent Glow -->
                        <div class="absolute -top-32 -left-32 w-96 h-96 bg-primary/15 rounded-full blur-3xl pointer-events-none hero-ambient-glow"></div>

                        <!-- Multi-Layer Stage Content Overlay -->
                        <div class="relative z-10 w-full h-full flex items-center">
                            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-4 sm:py-8 lg:py-12">
                                <div class="max-w-3xl">
                                    <?php if ($banner_eyebrow) : ?>
                                        <!-- Layer 1: Eyebrow Badge -->
                                        <div class="hero-layer hero-layer--eyebrow hidden xs:inline-flex items-center gap-2 px-2.5 sm:px-3.5 py-1 sm:py-1.5 rounded-full bg-white/15 backdrop-blur-xl border border-white/20 text-white text-[10px] sm:text-xs font-semibold uppercase tracking-wider mb-2 sm:mb-3.5 shadow-[0_2px_12px_rgba(0,0,0,0.15)]" data-swiper-parallax-y="-50" data-swiper-parallax-duration="650">
                                            <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse shadow-[0_0_8px_rgba(201,51,71,0.8)]"></span>
                                            <span><?php echo esc_html($banner_eyebrow); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Layer 2: Main Headline Title -->
                                    <div class="hero-layer hero-layer--title" data-swiper-parallax-y="80" data-swiper-parallax-duration="850">
                                        <?php if ($is_h1) : ?>
                                            <h1 class="text-lg xs:text-xl sm:text-3xl md:text-5xl lg:text-6xl text-white font-bold tracking-[-0.02em] leading-snug sm:leading-[1.18] mb-1.5 sm:mb-3 line-clamp-2 drop-shadow-[0_2px_8px_rgba(0,0,0,0.45)]">
                                                <?php echo esc_html($banner_title); ?>
                                            </h1>
                                        <?php else : ?>
                                            <h2 class="text-lg xs:text-xl sm:text-3xl md:text-5xl lg:text-6xl text-white font-bold tracking-[-0.02em] leading-snug sm:leading-[1.18] mb-1.5 sm:mb-3 line-clamp-2 drop-shadow-[0_2px_8px_rgba(0,0,0,0.45)]">
                                                <?php echo esc_html($banner_title); ?>
                                            </h2>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($banner_subtitle) : ?>
                                        <!-- Layer 3: Subtitle / Description -->
                                        <div class="hero-layer hero-layer--subtitle" data-swiper-parallax-y="60" data-swiper-parallax-duration="950">
                                            <p class="hidden sm:block font-sans text-white/85 text-xs sm:text-base md:text-lg font-normal leading-relaxed mb-4 sm:mb-6 line-clamp-2 max-w-2xl drop-shadow-[0_1px_4px_rgba(0,0,0,0.3)]">
                                                <?php echo esc_html($banner_subtitle); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Layer 4: Call-To-Action Actions -->
                                    <div class="hero-layer hero-layer--cta flex flex-row items-center gap-2 sm:gap-3.5 mt-1 sm:mt-2" data-swiper-parallax-y="40" data-swiper-parallax-duration="1050">
                                        <?php if ($banner_link) : ?>
                                            <a href="<?php echo esc_url($banner_link); ?>" class="hero-btn-shimmer inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-6 h-8 sm:h-11 bg-primary text-white text-[11px] sm:text-sm font-semibold rounded-full hover:bg-primary-hover active:scale-95 transition-all duration-150 shadow-[0_4px_16px_rgba(201,51,71,0.35)] shrink-0">
                                                <span><?php echo esc_html($default_cta_text); ?></span>
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                                            </a>
                                        <?php else : ?>
                                            <a href="<?php echo esc_url(home_url('/du-an')); ?>" class="hero-btn-shimmer inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-6 h-8 sm:h-11 bg-primary text-white text-[11px] sm:text-sm font-semibold rounded-full hover:bg-primary-hover active:scale-95 transition-all duration-150 shadow-[0_4px_16px_rgba(201,51,71,0.35)] shrink-0">
                                                <span>Khám Phá Dự Án</span>
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($secondary_cta_link) : ?>
                                            <a href="<?php echo esc_url($secondary_cta_link); ?>" class="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-6 h-8 sm:h-11 bg-white/15 hover:bg-white/25 active:bg-white/30 text-white border border-white/25 text-[11px] sm:text-sm font-medium rounded-full backdrop-blur-xl active:scale-95 transition-all duration-150 shadow-[0_2px_10px_rgba(0,0,0,0.12)] shrink-0">
                                                <span><?php echo esc_html($secondary_cta_text); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php elseif ($has_fallback) : ?>
                <?php
                $index = 0;
                while ($fallback_query->have_posts()) : $fallback_query->the_post();
                    $post_id = get_the_ID();
                    $is_first_slide = ($index === 0);
                    $is_h1 = $is_first_slide && (is_front_page() || is_home());

                    // Lấy ảnh
                    $thumbnail_url = '';
                    $address = '';
                    $price = '';
                    if ($fallback_type === 'project') {
                        $gallery = get_field('gallery_du_an', $post_id);
                        $address = get_field('address', $post_id);
                        $price = get_field('price', $post_id);
                    } else {
                        $gallery = get_field('gallery_nha_dat', $post_id);
                        $address = get_field('dia_chi', $post_id);
                        $price = get_field('khoang_gia', $post_id);
                    }

                    $image_id = 0;
                    if (is_array($gallery) && !empty($gallery)) {
                        $first_image = $gallery[0];
                        $image_id = is_array($first_image) && isset($first_image['ID']) ? (int) $first_image['ID'] : (is_numeric($first_image) ? (int) $first_image : 0);
                        if ($image_id > 0) {
                            $thumbnail_url = wp_get_attachment_image_url($image_id, 'full');
                        }
                    }

                    if (empty($thumbnail_url) && has_post_thumbnail($post_id)) {
                        $image_id = get_post_thumbnail_id($post_id);
                        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
                    }

                    if (empty($thumbnail_url)) {
                        $thumbnail_url = get_template_directory_uri() . '/assets/images/placeholder-real-estate.jpg';
                    }

                    // Format price
                    $price_display = '';
                    if ($price) {
                        if (is_numeric($price) && floatval($price) > 0) {
                            $price_display = 'Từ ' . number_format(floatval($price), 0, ',', '.') . ' tỷ VNĐ';
                        } else {
                            $price_display = $price;
                        }
                    }

                    $permalink = get_permalink($post_id);
                    $slide_title = get_the_title();
                ?>
                    <div class="swiper-slide h-full relative overflow-hidden group">
                        <!-- Layer 0: Background Media with Ken Burns Motion & Parallax -->
                        <div class="absolute inset-0 w-full h-full overflow-hidden hero-layer hero-layer--bg" data-swiper-parallax="-20%">
                            <?php if ($image_id && function_exists('bds_get_optimized_image')) : ?>
                                <?php echo bds_get_optimized_image(
                                    $image_id,
                                    'bds-hero',
                                    [
                                        'class' => 'hero-layer-img absolute inset-0 w-full h-full object-cover',
                                        'alt' => esc_attr($slide_title),
                                        'loading' => $is_first_slide ? 'eager' : 'lazy',
                                        'fetchpriority' => $is_first_slide ? 'high' : 'auto',
                                    ],
                                    !$is_first_slide
                                ); ?>
                            <?php else : ?>
                                <img
                                    class="hero-layer-img absolute inset-0 w-full h-full object-cover"
                                    src="<?php echo esc_url($thumbnail_url); ?>"
                                    alt="<?php echo esc_attr($slide_title); ?>"
                                    loading="<?php echo $is_first_slide ? 'eager' : 'lazy'; ?>"
                                    fetchpriority="<?php echo $is_first_slide ? 'high' : 'auto'; ?>">
                            <?php endif; ?>
                        </div>

                        <!-- Architectural Cinematic Overlays (Apple Cinematic Scrims + Depth Vignette) -->
                        <div class="absolute inset-0 bg-gradient-to-t from-[#141210]/95 via-[#141210]/55 to-[#141210]/25 pointer-events-none"></div>
                        <div class="absolute inset-0 bg-gradient-to-b from-[#141210]/35 via-transparent to-transparent pointer-events-none"></div>
                        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_0%,rgba(20,18,16,0.45)_100%)] pointer-events-none"></div>
                        <!-- Ambient Stage Accent Glow -->
                        <div class="absolute -top-32 -left-32 w-96 h-96 bg-primary/15 rounded-full blur-3xl pointer-events-none hero-ambient-glow"></div>

                        <!-- Multi-Layer Stage Content Overlay -->
                        <div class="relative z-10 w-full h-full flex items-center">
                            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-4 sm:py-8 lg:py-12">
                                <div class="max-w-3xl">
                                    <!-- Layer 1: Eyebrow Badge -->
                                    <div class="hero-layer hero-layer--eyebrow hidden xs:inline-flex items-center gap-2 px-2.5 sm:px-3.5 py-1 sm:py-1.5 rounded-full bg-white/15 backdrop-blur-xl border border-white/20 text-white text-[10px] sm:text-xs font-semibold uppercase tracking-wider mb-2 sm:mb-3.5 shadow-[0_2px_12px_rgba(0,0,0,0.15)]" data-swiper-parallax-y="-50" data-swiper-parallax-duration="650">
                                        <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse shadow-[0_0_8px_rgba(201,51,71,0.8)]"></span>
                                        <span><?php echo esc_html($address ?: $default_eyebrow); ?></span>
                                    </div>

                                    <!-- Layer 2: Main Headline Title -->
                                    <div class="hero-layer hero-layer--title" data-swiper-parallax-y="80" data-swiper-parallax-duration="850">
                                        <?php if ($is_h1) : ?>
                                            <h1 class="text-lg xs:text-xl sm:text-3xl md:text-5xl lg:text-6xl text-white font-bold tracking-[-0.02em] leading-snug sm:leading-[1.18] mb-1.5 sm:mb-3 line-clamp-2 drop-shadow-[0_2px_8px_rgba(0,0,0,0.45)]">
                                                <?php echo esc_html($slide_title); ?>
                                            </h1>
                                        <?php else : ?>
                                            <h2 class="text-lg xs:text-xl sm:text-3xl md:text-5xl lg:text-6xl text-white font-bold tracking-[-0.02em] leading-snug sm:leading-[1.18] mb-1.5 sm:mb-3 line-clamp-2 drop-shadow-[0_2px_8px_rgba(0,0,0,0.45)]">
                                                <?php echo esc_html($slide_title); ?>
                                            </h2>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($price_display || $address) : ?>
                                        <!-- Layer 3: Price & Address Specs -->
                                        <div class="hero-layer hero-layer--subtitle inline-flex items-center gap-2 text-stone/90 text-xs sm:text-base md:text-lg font-medium mb-3 sm:mb-5" data-swiper-parallax-y="60" data-swiper-parallax-duration="950">
                                            <?php if ($price_display) : ?>
                                                <span class="text-primary font-bold"><?php echo esc_html($price_display); ?></span>
                                            <?php endif; ?>
                                            <?php if ($address) : ?>
                                                <?php if ($price_display) : ?><span class="text-white/40">•</span><?php endif; ?>
                                                <span class="text-white/80 text-[11px] sm:text-sm line-clamp-1"><?php echo esc_html($address); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Layer 4: Call-To-Action Actions -->
                                    <div class="hero-layer hero-layer--cta flex flex-row items-center gap-2 sm:gap-3.5 mt-1 sm:mt-2" data-swiper-parallax-y="40" data-swiper-parallax-duration="1050">
                                        <a href="<?php echo esc_url($permalink); ?>" class="hero-btn-shimmer inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-6 h-8 sm:h-11 bg-primary text-white text-[11px] sm:text-sm font-semibold rounded-full hover:bg-primary-hover active:scale-95 transition-all duration-150 shadow-[0_4px_16px_rgba(201,51,71,0.35)] shrink-0">
                                            <span>Xem Chi Tiết Dự Án</span>
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                                        </a>

                                        <a href="<?php echo esc_url(home_url('/du-an')); ?>" class="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3.5 sm:px-6 h-8 sm:h-11 bg-white/15 hover:bg-white/25 active:bg-white/30 text-white border border-white/25 text-[11px] sm:text-sm font-medium rounded-full backdrop-blur-xl active:scale-95 transition-all duration-150 shadow-[0_2px_10px_rgba(0,0,0,0.12)] shrink-0">
                                            <span>Xem Danh Sách Dự Án</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                    $index++;
                endwhile;
                wp_reset_postdata();
                ?>
            <?php endif; ?>
        </div>

        <?php if ($show_navigation) : ?>
            <!-- Navigation - Keep BEM classes for JS -->
            <div class="<?php echo esc_attr($nav_class); ?> <?php echo esc_attr($nav_class); ?>--prev absolute top-1/2 -translate-y-1/2 z-20 left-4 sm:left-8 w-12 h-12 rounded-full bg-black/35 hover:bg-primary text-white border border-white/20 backdrop-blur-md flex items-center justify-center cursor-pointer transition-all duration-300 hover:scale-105 shadow-dropdown hidden sm:flex [&.swiper-button-disabled]:opacity-0 [&.swiper-button-disabled]:pointer-events-none" aria-label="Slide trước">
                <?php echo batdongsan_icon('arrow-left', ['width' => 20, 'height' => 20]); ?>
            </div>
            <div class="<?php echo esc_attr($nav_class); ?> <?php echo esc_attr($nav_class); ?>--next absolute top-1/2 -translate-y-1/2 z-20 right-4 sm:right-8 w-12 h-12 rounded-full bg-black/35 hover:bg-primary text-white border border-white/20 backdrop-blur-md flex items-center justify-center cursor-pointer transition-all duration-300 hover:scale-105 shadow-dropdown hidden sm:flex [&.swiper-button-disabled]:opacity-0 [&.swiper-button-disabled]:pointer-events-none" aria-label="Slide sau">
                <?php echo batdongsan_icon('arrow-right', ['width' => 20, 'height' => 20]); ?>
            </div>
        <?php endif; ?>

        <?php if ($show_pagination) : ?>
            <!-- Apple Island Pagination Capsule - Keep BEM class for JS -->
            <div class="<?php echo esc_attr($swiper_class); ?>__pagination absolute bottom-11 sm:bottom-12 md:bottom-14 left-1/2 -translate-x-1/2 z-20 w-auto flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-full bg-black/40 backdrop-blur-xl border border-white/20 shadow-[0_4px_20px_rgba(0,0,0,0.25)]"></div>
        <?php endif; ?>

        <!-- LayerSlider Timeline Progress Bar -->
        <div class="hero-slider-progress absolute bottom-0 left-0 right-0 h-[3px] bg-white/10 z-20 pointer-events-none overflow-hidden">
            <div class="hero-slider-progress-bar h-full bg-gradient-to-r from-primary via-rose-400 to-amber-300 w-0"></div>
        </div>
    </div>
</section>
