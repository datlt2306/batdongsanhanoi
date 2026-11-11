<?php

/**
 * Filter Bar Horizontal - Luxury Real Estate Advisory Console
 *
 * Thanh lọc ngang: Tìm kiếm, Khu vực, Loại hình, Khoảng giá, Diện tích, Nâng cao, Trạng thái, nút Reset
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$defaults = [
    'form_action'             => home_url('/bat-dong-san'),
    'placeholder'             => 'Tìm kiếm bất động sản...',
    'current_name'            => '',
    'current_price'           => '',
    'current_type'            => '0',
    'current_status'          => '0',
    'current_khu_vuc'         => '',
    'price_ranges'            => ['0' => 'Tất cả'],
    'price_bounds'            => [0, 60000],
    'current_area'            => '',
    'area_ranges'             => [],
    'area_bounds'             => [0, 500],
    'area_options'            => [
        ''            => 'Toàn Hà Nội',
        'gia-lam'     => 'Gia Lâm',
        'nam-tu-liem' => 'Nam Từ Liêm',
        'hai-ba-trung'=> 'Hai Bà Trưng',
        'tay-ho'      => 'Tây Hồ',
        'long-bien'   => 'Long Biên',
        'cau-giay'    => 'Cầu Giấy',
        'dong-da'     => 'Đống Đa',
        'hoang-mai'   => 'Hoàng Mai',
        'thanh-xuan'  => 'Thanh Xuân',
    ],
    'current_order'           => 1,
    'show_status'             => true,
    'type_terms'              => null,
    'current_du_an_type_slug' => '',
    'current_hinh_thuc'       => '',
    'current_so_phong_ngu'    => '',
    'current_so_phong_ve_sinh'=> '',
    'current_huong_nha'       => '',
    'current_phap_ly'         => '',
    'show_advanced'           => false,
    'layout'                  => 'default', // 'default' | 'mobile_header'
    'show_area_range'         => true,
];

$args = wp_parse_args($args ?? [], $defaults);
$layout_mobile = isset($args['layout']) && $args['layout'] === 'mobile_header';
$current_hinh_thuc = isset($args['current_hinh_thuc']) ? (string) $args['current_hinh_thuc'] : '';

$form_action             = $args['form_action'];
$placeholder             = $args['placeholder'];
$current_name            = $args['current_name'];
$current_price           = $args['current_price'];
$current_type            = $args['current_type'];
$current_status          = $args['current_status'];
$current_khu_vuc         = $args['current_khu_vuc'];
$price_ranges            = $args['price_ranges'];
$current_area            = isset($args['current_area']) ? (string) $args['current_area'] : '';
$area_ranges             = !empty($args['area_ranges']) && is_array($args['area_ranges']) ? $args['area_ranges'] : [];
$area_options            = $args['area_options'];
$current_order           = isset($args['current_order']) ? absint($args['current_order']) : 1;
$show_status             = !empty($args['show_status']);
$show_area_range         = !isset($args['show_area_range']) || $args['show_area_range'];
$current_du_an_type_slug = isset($args['current_du_an_type_slug']) ? (string) $args['current_du_an_type_slug'] : '';
$current_so_phong_ngu    = isset($args['current_so_phong_ngu']) ? (string) $args['current_so_phong_ngu'] : '';
$current_so_phong_ve_sinh= isset($args['current_so_phong_ve_sinh']) ? (string) $args['current_so_phong_ve_sinh'] : '';
$current_huong_nha       = isset($args['current_huong_nha']) ? (string) $args['current_huong_nha'] : '';
$current_phap_ly         = isset($args['current_phap_ly']) ? (string) $args['current_phap_ly'] : '';
$show_advanced           = !empty($args['show_advanced']);

$type_terms = [];
if (isset($args['type_terms']) && is_array($args['type_terms'])) {
    $type_terms = $args['type_terms'];
} elseif (function_exists('batdongsan_get_du_an_type_options')) {
    $type_terms = batdongsan_get_du_an_type_options();
}

$status_options = !empty($args['status_options']) && is_array($args['status_options'])
    ? $args['status_options']
    : [
        '0'            => 'Tất cả',
        'dang-ban'     => 'Đang bán',
        'da-ban'       => 'Đã bán',
        'dang-cho-thue'=> 'Đang cho thuê',
        'tam-dung'     => 'Tạm dừng',
    ];

$reset_url = remove_query_arg(['project-name', 'price', 'type', 'status', 'khu_vuc', 'area', 'du_an', 'paged', 'order', 'so_phong_ngu', 'so_phong_ve_sinh', 'huong_nha', 'phap_ly', 'hinh_thuc'], $form_action);

$has_active_filters = $current_name || $current_price || $current_type !== '0' || $current_status !== '0' || $current_khu_vuc || $current_area || $current_so_phong_ngu || $current_so_phong_ve_sinh || $current_huong_nha || $current_phap_ly;
?>

<div class="<?php echo $layout_mobile ? 'bg-white' : 'bg-surface bg-white rounded-2xl lg:rounded-full shadow-subtle border border-black/[0.08] p-1.5 overflow-visible transition-all duration-300'; ?>">
    <form action="<?php echo esc_url($form_action); ?>" method="get" class="filter-bar-horizontal__form flex flex-col md:flex-row md:items-stretch">
        <?php if (!empty($type_terms)) : ?>
            <input type="hidden" name="type" value="<?php echo esc_attr((string) $current_type); ?>" data-filter-type-hidden>
        <?php endif; ?>
        <input type="hidden" name="khu_vuc" value="<?php echo esc_attr((string) $current_khu_vuc); ?>" data-filter-area-hidden>
        <input type="hidden" name="hinh_thuc" value="<?php echo esc_attr((string) $current_hinh_thuc); ?>" data-filter-hinh-thuc-hidden>
        <?php if ($show_status) : ?>
            <input type="hidden" name="status" value="<?php echo esc_attr((string) $current_status); ?>" data-filter-status-hidden>
        <?php endif; ?>
        <?php if ($current_order > 1 && $current_order <= 5) : ?>
            <input type="hidden" name="order" value="<?php echo esc_attr((string) $current_order); ?>">
        <?php endif; ?>
        <input type="hidden" name="so_phong_ngu" value="<?php echo esc_attr((string) $current_so_phong_ngu); ?>" data-filter-so-phong-ngu-hidden>
        <input type="hidden" name="so_phong_ve_sinh" value="<?php echo esc_attr((string) $current_so_phong_ve_sinh); ?>" data-filter-so-phong-ve-sinh-hidden>
        <input type="hidden" name="huong_nha" value="<?php echo esc_attr((string) $current_huong_nha); ?>" data-filter-huong-nha-hidden>
        <input type="hidden" name="phap_ly" value="<?php echo esc_attr((string) $current_phap_ly); ?>" data-filter-phap-ly-hidden>

        <div class="flex flex-1 flex-col md:flex-row md:items-stretch <?php echo $layout_mobile ? 'gap-0' : ''; ?>">
            <?php if ($layout_mobile) : ?>
                <!-- Mobile: Apple Spotlight Search capsule -->
                <div class="flex items-center bg-black/[0.04] hover:bg-black/[0.06] focus-within:bg-white border border-black/[0.07] focus-within:border-primary/40 focus-within:ring-2 focus-within:ring-primary/10 rounded-full pl-3.5 pr-1 py-1 transition-all shadow-[inset_0_1px_2px_rgba(0,0,0,0.02)]">
                    <span class="text-charcoal/40 pointer-events-none shrink-0" aria-hidden="true">
                        <svg class="w-4 h-4 text-charcoal/40" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="10.5" cy="10.5" r="6.5"></circle>
                            <line x1="15.5" y1="15.5" x2="20.5" y2="20.5"></line>
                        </svg>
                    </span>
                    <input
                        type="text"
                        name="project-name"
                        value="<?php echo esc_attr($current_name); ?>"
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        class="flex-1 border-none bg-transparent py-1 px-2.5 font-sans text-[13px] text-charcoal outline-none placeholder:text-charcoal-muted/60 font-normal"
                        aria-label="<?php echo esc_attr($placeholder); ?>">
                    <button type="submit" class="w-7.5 h-7.5 bg-primary shrink-0 flex items-center justify-center text-white border-none cursor-pointer transition-all hover:bg-primary-hover active:scale-90 rounded-full shadow-sm" aria-label="Tìm kiếm">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="10.5" cy="10.5" r="6.5"></circle>
                            <line x1="15.5" y1="15.5" x2="20.5" y2="20.5"></line>
                        </svg>
                    </button>
                </div>
                <!-- Mobile: Hàng filter chip - 3 button rộng bằng nhau trên 1 hàng -->
                <div class="grid grid-cols-3 gap-1.5 xs:gap-2 pt-2 pb-0.5 w-full items-center">
                <?php else : ?>
                    <div class="flex-1 min-w-0 relative px-2.5 py-1 md:border-r md:border-black/[0.06] md:max-w-[260px] lg:max-w-[300px] flex items-center">
                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-charcoal-muted pointer-events-none flex items-center" aria-hidden="true">
                            <svg class="w-4 h-4 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="10.5" cy="10.5" r="6.5"></circle>
                                <line x1="15.5" y1="15.5" x2="20.5" y2="20.5"></line>
                            </svg>
                        </span>
                        <input
                            type="text"
                            name="project-name"
                            value="<?php echo esc_attr($current_name); ?>"
                            placeholder="<?php echo esc_attr($placeholder); ?>"
                            class="w-full h-11 pl-10 pr-4 rounded-full bg-[#F5F5F7] hover:bg-stone/50 focus:bg-white font-sans text-xs sm:text-sm text-charcoal outline-none placeholder:text-charcoal-muted/70 border border-transparent focus:border-primary/40 focus:ring-2 focus:ring-primary/15 transition-all duration-200"
                            aria-label="<?php echo esc_attr($placeholder); ?>">
                    </div>

                    <div class="flex-1 min-w-0 flex flex-wrap md:flex-nowrap md:items-center gap-0 overflow-visible">
                    <?php endif; ?>

                    <?php if ($layout_mobile) : ?>
                        <?php get_template_part('template-parts/filter-header-modal-khu-vuc', null, ['current_khu_vuc' => $current_khu_vuc, 'area_options' => $area_options]); ?>
                        <?php if (!empty($type_terms)) : ?>
                            <?php get_template_part('template-parts/filter-header-modal-loai-hinh', null, ['current_type' => $current_type, 'type_terms' => $type_terms, 'current_type_slug' => $current_du_an_type_slug]); ?>
                        <?php endif; ?>
                        <?php if (!empty($price_ranges)) : ?>
                            <input type="hidden" name="price" value="<?php echo esc_attr($current_price); ?>" data-filter-price-hidden>
                            <?php get_template_part('template-parts/filter-header-modal-khoang-gia', null, ['current_price' => $current_price, 'price_ranges' => $price_ranges]); ?>
                        <?php endif; ?>
                    <?php else : ?>
                        <?php
                        get_template_part('template-parts/filter-area-dropdown', null, [
                            'current_khu_vuc' => $current_khu_vuc,
                            'area_options'    => $area_options,
                            'segment_layout'  => true,
                            'compact'         => false,
                        ]);
                        ?>
                        <?php if (!empty($type_terms)) : ?>
                            <?php
                            get_template_part('template-parts/filter-type-dropdown', null, [
                                'current_type'      => $current_type,
                                'type_terms'        => $type_terms,
                                'form_action'       => $form_action,
                                'segment_layout'    => true,
                                'compact'           => false,
                                'current_type_slug' => $current_du_an_type_slug,
                            ]);
                            ?>
                        <?php endif; ?>
                        <?php if (!empty($price_ranges)) : ?>
                            <input type="hidden" name="price" value="<?php echo esc_attr($current_price); ?>" data-filter-price-hidden>
                            <?php
                            get_template_part('template-parts/filter-price-dropdown', null, [
                                'current_price'   => $current_price,
                                'price_ranges'    => $price_ranges,
                                'price_bounds'    => isset($args['price_bounds']) ? $args['price_bounds'] : [0, 60000],
                                'reset_url'       => remove_query_arg(['price', 'paged'], add_query_arg(array_filter([
                                    'project-name'     => $current_name ?: null,
                                    'type'             => ($current_type !== '0') ? $current_type : null,
                                    'status'           => ($current_status !== '0') ? $current_status : null,
                                    'khu_vuc'          => $current_khu_vuc ?: null,
                                    'so_phong_ngu'     => $current_so_phong_ngu ?: null,
                                    'so_phong_ve_sinh' => $current_so_phong_ve_sinh ?: null,
                                    'huong_nha'        => $current_huong_nha ?: null,
                                    'phap_ly'          => $current_phap_ly ?: null,
                                ]), $form_action)),
                                'base_url'        => strtok($form_action, '?') ?: $form_action,
                                'segment_layout'  => true,
                                'compact'         => false,
                            ]);
                            ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($show_area_range && !empty($area_ranges)) : ?>
                        <input type="hidden" name="area" value="<?php echo esc_attr($current_area); ?>" data-filter-area-range-hidden>
                        <?php
                        $area_bounds = isset($args['area_bounds']) && is_array($args['area_bounds']) ? $args['area_bounds'] : [0, 500];
                        get_template_part('template-parts/filter-area-range-dropdown', null, [
                            'current_area'   => $current_area,
                            'area_ranges'    => $area_ranges,
                            'area_bounds'    => $area_bounds,
                            'reset_url'      => remove_query_arg(['area', 'paged'], add_query_arg(array_filter([
                                'project-name'     => $current_name ?: null,
                                'type'             => ($current_type !== '0') ? $current_type : null,
                                'status'           => ($current_status !== '0') ? $current_status : null,
                                'khu_vuc'          => $current_khu_vuc ?: null,
                                'price'            => $current_price ?: null,
                                'so_phong_ngu'     => $current_so_phong_ngu ?: null,
                                'so_phong_ve_sinh' => $current_so_phong_ve_sinh ?: null,
                                'huong_nha'        => $current_huong_nha ?: null,
                                'phap_ly'          => $current_phap_ly ?: null,
                            ]), $form_action)),
                            'base_url'       => strtok($form_action, '?') ?: $form_action,
                            'segment_layout' => !$layout_mobile,
                            'compact'        => $layout_mobile,
                        ]);
                        ?>
                    <?php endif; ?>

                    <?php if ($show_advanced && !$layout_mobile) :
                        $has_adv = !empty($current_so_phong_ngu) || !empty($current_so_phong_ve_sinh) || (!empty($current_huong_nha) && $current_huong_nha !== 'all') || (!empty($current_phap_ly) && $current_phap_ly !== 'all');
                        $adv_count = 0;
                        if (!empty($current_so_phong_ngu)) $adv_count++;
                        if (!empty($current_so_phong_ve_sinh)) $adv_count++;
                        if (!empty($current_huong_nha) && $current_huong_nha !== 'all') $adv_count++;
                        if (!empty($current_phap_ly) && $current_phap_ly !== 'all') $adv_count++;
                    ?>
                        <!-- Advanced Filter Segment (Bedrooms, Bathrooms, Orientation, Legal) -->
                        <div class="filter-advanced-dropdown relative filter-bar-segment flex-1 min-w-0 md:border-r md:border-black/[0.06]" data-filter-advanced>
                            <button
                                type="button"
                                class="filter-advanced-trigger w-full flex items-center justify-between text-left px-3.5 py-2 min-w-0 cursor-pointer outline-none hover:bg-stone/40 active:scale-[0.99] transition-all data-[open=true]:bg-stone/60 rounded-xl lg:rounded-2xl group"
                                aria-haspopup="true"
                                aria-expanded="false"
                                aria-label="Bộ lọc nâng cao"
                                data-filter-advanced-trigger>
                                <div class="flex flex-col text-left min-w-0 flex-1 pr-1.5">
                                    <span class="block text-[11px] font-medium text-charcoal-muted tracking-tight mb-0.5 font-sans">Thêm lọc</span>
                                    <span class="block text-xs sm:text-[13px] font-semibold text-charcoal font-sans whitespace-nowrap overflow-hidden text-ellipsis">
                                        <?php if ($has_adv) : ?>
                                            <span class="text-primary font-bold">Đã chọn (<?php echo esc_html($adv_count); ?>)</span>
                                        <?php else : ?>
                                            Nâng cao
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <svg class="w-3.5 h-3.5 text-charcoal-muted/70 group-hover:text-charcoal shrink-0 transition-transform duration-200 pointer-events-none" data-filter-advanced-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9l6 6 6-6"/>
                                </svg>
                            </button>

                            <div
                                class="absolute top-full right-0 md:left-auto md:right-0 mt-2 z-50 hidden w-[340px] sm:w-[380px] bg-surface/95 bg-white/95 rounded-2xl border border-black/[0.08] shadow-[0_16px_40px_rgba(0,0,0,0.14)] p-4 backdrop-blur-2xl overflow-hidden apple-dropdown-panel"
                                data-filter-advanced-panel
                                role="dialog"
                                aria-label="Bộ lọc nâng cao">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between border-b border-border pb-3">
                                        <h4 class="text-sm font-semibold text-charcoal m-0">Bộ lọc chi tiết</h4>
                                        <span class="text-xs text-charcoal-muted font-sans">Phòng, hướng & pháp lý</span>
                                    </div>

                                    <!-- Bedrooms -->
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wider text-charcoal-muted mb-2 font-sans">Phòng ngủ</label>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <?php foreach (['' => 'Tất cả', '1' => '1+', '2' => '2+', '3' => '3+', '4' => '4+', '5' => '5+'] as $val => $lbl) :
                                                $is_sel = (string)$current_so_phong_ngu === (string)$val;
                                            ?>
                                                <button type="button" class="adv-pill px-3 py-1.5 rounded-full text-xs font-sans font-medium transition-all cursor-pointer <?php echo $is_sel ? 'bg-primary text-white shadow-sm' : 'bg-stone/50 text-charcoal hover:bg-stone border border-border/60'; ?>" data-adv-target="so_phong_ngu" data-adv-val="<?php echo esc_attr($val); ?>">
                                                    <?php echo esc_html($lbl); ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Bathrooms -->
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wider text-charcoal-muted mb-2 font-sans">Phòng vệ sinh</label>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <?php foreach (['' => 'Tất cả', '1' => '1+', '2' => '2+', '3' => '3+', '4' => '4+'] as $val => $lbl) :
                                                $is_sel = (string)$current_so_phong_ve_sinh === (string)$val;
                                            ?>
                                                <button type="button" class="adv-pill px-3 py-1.5 rounded-full text-xs font-sans font-medium transition-all cursor-pointer <?php echo $is_sel ? 'bg-primary text-white shadow-sm' : 'bg-stone/50 text-charcoal hover:bg-stone border border-border/60'; ?>" data-adv-target="so_phong_ve_sinh" data-adv-val="<?php echo esc_attr($val); ?>">
                                                    <?php echo esc_html($lbl); ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Orientation -->
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wider text-charcoal-muted mb-2 font-sans">Hướng nhà</label>
                                        <div class="grid grid-cols-4 gap-1.5">
                                            <?php
                                             $huong_list = [
                                                '' => 'Tất cả', 'dong' => 'Đông', 'tay' => 'Tây', 'nam' => 'Nam',
                                                'bac' => 'Bắc', 'dong-nam' => 'Đông Nam', 'dong-bac' => 'Đông Bắc', 'tay-nam' => 'Tây Nam', 'tay-bac' => 'Tây Bắc'
                                            ];
                                            foreach ($huong_list as $val => $lbl) :
                                                $is_sel = (string)$current_huong_nha === (string)$val;
                                            ?>
                                                <button type="button" class="adv-pill py-1.5 px-2 text-center rounded-full text-xs font-sans font-medium transition-all cursor-pointer truncate <?php echo $is_sel ? 'bg-primary text-white shadow-sm' : 'bg-stone/50 text-charcoal hover:bg-stone border border-border/60'; ?>" data-adv-target="huong_nha" data-adv-val="<?php echo esc_attr($val); ?>" title="<?php echo esc_attr($lbl); ?>">
                                                    <?php echo esc_html($lbl); ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Legal -->
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wider text-charcoal-muted mb-2 font-sans">Tình trạng pháp lý</label>
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <?php
                                            $phap_ly_list = [
                                                '' => 'Tất cả', 'so-do' => 'Sổ đỏ / Sổ hồng',
                                                'hop-dong-mua-ban' => 'HĐ mua bán', 'dang-cho-so' => 'Đang chờ sổ'
                                            ];
                                            foreach ($phap_ly_list as $val => $lbl) :
                                                $is_sel = (string)$current_phap_ly === (string)$val;
                                            ?>
                                                <button type="button" class="adv-pill py-2 px-2.5 text-center rounded-full text-xs font-sans font-medium transition-all cursor-pointer truncate <?php echo $is_sel ? 'bg-primary text-white shadow-sm' : 'bg-stone/50 text-charcoal hover:bg-stone border border-border/60'; ?>" data-adv-target="phap_ly" data-adv-val="<?php echo esc_attr($val); ?>" title="<?php echo esc_attr($lbl); ?>">
                                                    <?php echo esc_html($lbl); ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Action buttons -->
                                    <div class="flex items-center justify-between gap-3 pt-3 border-t border-border">
                                        <button type="button" class="text-xs text-charcoal-muted hover:text-primary font-sans font-medium cursor-pointer" data-filter-advanced-reset>
                                            Đặt lại
                                        </button>
                                        <button type="button" class="py-2 px-4 bg-primary hover:bg-primary-hover text-white text-xs font-sans font-medium rounded-full shadow-sm transition-all cursor-pointer" data-filter-advanced-apply>
                                            Áp dụng
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_status) : ?>
                        <?php
                        get_template_part('template-parts/filter-status-dropdown', null, [
                            'current_status'  => $current_status,
                            'status_options'  => $status_options,
                            'segment_layout'  => !$layout_mobile,
                            'compact'         => $layout_mobile,
                        ]);
                        ?>
                    <?php endif; ?>
                    </div>
                    <?php if ($layout_mobile) : ?>
                </div><?php endif; ?>

            <?php if (!$layout_mobile) : ?>
                <div class="flex items-center px-1.5 md:px-2">
                    <a
                        href="<?php echo esc_url($reset_url); ?>"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-[#F5F5F7] hover:bg-black/[0.06] active:scale-95 text-charcoal-muted hover:text-primary transition-all duration-200 shrink-0 cursor-pointer group"
                        aria-label="Đặt lại bộ lọc"
                        title="Đặt lại bộ lọc">
                        <svg class="w-4 h-4 text-charcoal-muted group-hover:text-primary group-hover:rotate-180 transition-all duration-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 12A8 8 0 1 1 12 4c2.5 0 4.7 1.1 6.2 3"/>
                            <polyline points="18 3 18.5 7.5 14 7"/>
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>