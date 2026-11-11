<?php
/**
 * Search Form Unified Template Part
 * 
 * Luxury Real Estate Unified Search Bar & Advisory Filter Console
 * 
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

// Default args - Tự động lấy giá trị từ URL nếu không được truyền vào
$defaults = [
    'form_action'      => home_url('/'),
    'title'            => '',
    'subtitle'         => '',
    'placeholder'      => 'Nhập địa chỉ, tên dự án, đường phố (vd: Cầu Giấy, Vinhomes)...',
    'current_tab'      => 'all', // 'all' | 'ban' | 'cho-thue' | 'du-an'
    'current_name'     => isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : (isset($_GET['project-name']) ? sanitize_text_field(wp_unslash($_GET['project-name'])) : ''),
    'current_price'    => isset($_GET['price']) ? sanitize_text_field(wp_unslash($_GET['price'])) : '0',
    'current_status'   => isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '0',
    'current_type'     => isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '0',
    'current_khu_vuc'  => isset($_GET['khu_vuc']) ? sanitize_text_field(wp_unslash($_GET['khu_vuc'])) : (isset($_GET['area']) ? sanitize_text_field(wp_unslash($_GET['area'])) : ''),
    'current_hinh_thuc'=> isset($_GET['hinh_thuc']) ? sanitize_text_field(wp_unslash($_GET['hinh_thuc'])) : '',
    'current_du_an'    => isset($_GET['du_an']) ? absint($_GET['du_an']) : 0,
    'show_tabs'        => false, // Mặc định không hiển thị tab, search toàn bộ BĐS, Cho thuê và Dự án
    'show_area'        => true,
    'show_type'        => true,
    'show_price'       => true,
    'show_status'      => false,
    'show_reset'       => false,
    'area_options'     => function_exists('batdongsan_get_area_options') ? batdongsan_get_area_options(true) : [],
];

$args = wp_parse_args($args ?? [], $defaults);

// Extract arguments
$form_action       = $args['form_action'];
$title             = $args['title'];
$subtitle          = $args['subtitle'];
$placeholder       = $args['placeholder'];
$current_tab       = $args['current_tab'];
$current_name      = $args['current_name'];
$current_price     = $args['current_price'];
$current_status    = $args['current_status'];
$current_type      = $args['current_type'];
$current_khu_vuc   = $args['current_khu_vuc'];
$current_hinh_thuc = $args['current_hinh_thuc'];
$current_du_an     = isset($args['current_du_an']) ? absint($args['current_du_an']) : 0;
$show_tabs         = !empty($args['show_tabs']);
$show_area         = !empty($args['show_area']);
$show_type         = !empty($args['show_type']);
$show_price        = !empty($args['show_price']);
$show_status       = !empty($args['show_status']);
$show_reset        = !empty($args['show_reset']);
$area_options      = !empty($args['area_options']) ? $args['area_options'] : (function_exists('batdongsan_get_area_options') ? batdongsan_get_area_options(true) : []);

// Tự động xác định tab active ban đầu dựa trên URL / args
if (!empty($current_hinh_thuc)) {
    if ($current_hinh_thuc === 'cho-thue') {
        $current_tab = 'cho-thue';
    } else {
        $current_tab = 'ban';
    }
} elseif (strpos($form_action, 'du-an') !== false) {
    $current_tab = 'du-an';
}

// Đồng bộ giá trị hinh_thuc ban đầu tương ứng với active tab (cho non-JS HTML GET submission)
$default_hinh_thuc = '';
if ($current_tab === 'ban') {
    $default_hinh_thuc = 'chuyen-nhuong';
} elseif ($current_tab === 'cho-thue') {
    $default_hinh_thuc = 'cho-thue';
}
if (!empty($current_hinh_thuc)) {
    $default_hinh_thuc = ($current_hinh_thuc === 'ban') ? 'chuyen-nhuong' : $current_hinh_thuc;
}

// Action URL tương ứng với cấu hình
if (!$show_tabs) {
    $action_url = !empty($args['form_action']) ? $args['form_action'] : home_url('/');
} elseif ($current_tab === 'du-an') {
    $action_url = home_url('/du-an');
    if (empty($args['placeholder']) || $args['placeholder'] === $defaults['placeholder']) {
        $placeholder = 'Tìm kiếm dự án bất động sản cao cấp...';
    }
} else {
    $action_url = home_url('/bat-dong-san');
    if ($current_tab === 'cho-thue') {
        if (empty($args['placeholder']) || $args['placeholder'] === $defaults['placeholder']) {
            $placeholder = 'Tìm kiếm căn hộ, biệt thự, shophouse cho thuê...';
        }
    } else {
        if (empty($args['placeholder']) || $args['placeholder'] === $defaults['placeholder']) {
            $placeholder = 'Tìm kiếm bất động sản, biệt thự, nhà phố bán...';
        }
    }
}

// BĐS Type terms (loai_hinh_bat_dong_san)
$bds_type_terms = [];
$bds_terms = get_terms(['taxonomy' => 'loai_hinh_bat_dong_san', 'hide_empty' => false]);
if (!is_wp_error($bds_terms) && !empty($bds_terms)) {
    $bds_type_terms = $bds_terms;
}

// Dự án Type terms (loai_hinh_du_an)
$duan_type_terms = function_exists('batdongsan_get_du_an_type_options') ? batdongsan_get_du_an_type_options() : [];

// BĐS Price ranges
$bds_price_ranges = function_exists('batdongsan_get_bds_price_ranges') ? batdongsan_get_bds_price_ranges() : ['0' => 'Tất cả mức giá'];

// Dự án Price ranges
$duan_price_ranges = function_exists('bds_get_price_ranges') ? bds_get_price_ranges() : [
    '0'   => 'Tất cả mức giá',
    '0-1' => 'Dưới 1 tỷ',
    '1-2' => '1 - 2 tỷ',
    '2-5' => '2 - 5 tỷ',
    '5'   => 'Trên 5 tỷ',
];

// Status options
$status_options = [
    '0'            => 'Tất cả trạng thái',
    'dang-mo-ban'  => 'Đang mở bán',
    'sap-mo-ban'   => 'Sắp mở bán',
    'da-ban-het'   => 'Đã bán hết',
    'tam-dung'     => 'Tạm dừng',
    'dang-cap-nhat'=> 'Đang cập nhật',
];

// Labels hiển thị ban đầu trên dropdown triggers
$selected_khu_vuc_label = 'Toàn Hà Nội';
if (!empty($current_khu_vuc) && isset($area_options[$current_khu_vuc])) {
    $selected_khu_vuc_label = $area_options[$current_khu_vuc];
}

$selected_type_label = 'Tất cả loại hình';
if ($current_type !== '0' && !empty($current_type)) {
    if ($current_tab === 'du-an') {
        foreach ($duan_type_terms as $t) {
            if ((string)$t->term_id === (string)$current_type) {
                $selected_type_label = $t->name;
                break;
            }
        }
    } else {
        foreach ($bds_type_terms as $t) {
            if ((string)$t->term_id === (string)$current_type) {
                $selected_type_label = $t->name;
                break;
            }
        }
    }
}

$selected_price_label = 'Tất cả mức giá';
if ($current_price !== '0' && !empty($current_price)) {
    if ($current_tab === 'du-an' && isset($duan_price_ranges[$current_price])) {
        $selected_price_label = $duan_price_ranges[$current_price];
    } elseif (isset($bds_price_ranges[$current_price])) {
        $selected_price_label = $bds_price_ranges[$current_price];
    }
}

$has_active_filters = ($current_name !== '' || ($current_price !== '0' && $current_price !== '') || ($current_type !== '0' && $current_type !== '') || $current_khu_vuc !== '');
?>

<div class="unified-search-wrapper relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 z-20 -mt-8 sm:-mt-10 md:-mt-11 lg:-mt-12 mb-3">
    <div class="unified-search-container bg-white/95 rounded-3xl lg:rounded-full border border-black/[0.08] shadow-[0_16px_48px_rgba(0,0,0,0.12)] p-2.5 sm:p-3 backdrop-blur-2xl transition-all duration-300">
        <?php if (!empty($title)) : ?>
            <div class="text-center pt-2 pb-1 px-4">
                <h2 class="text-lg md:text-xl text-charcoal font-bold tracking-tight m-0 mb-1 leading-tight">
                    <?php echo esc_html($title); ?>
                </h2>
                <?php if (!empty($subtitle)) : ?>
                    <p class="font-sans text-xs md:text-sm text-charcoal-muted m-0 leading-normal max-w-2xl mx-auto">
                        <?php echo esc_html($subtitle); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Search Form Console -->
        <form action="<?php echo esc_url($action_url); ?>" method="get" class="unified-search-form" data-unified-search-form>
            <!-- Hidden Fields -->
            <input type="hidden" name="hinh_thuc" id="unified-hinh-thuc" value="<?php echo esc_attr($default_hinh_thuc); ?>" data-unified-input="hinh_thuc">
            <?php if ($current_du_an > 0) : ?>
                <input type="hidden" name="du_an" value="<?php echo esc_attr((string) $current_du_an); ?>">
            <?php endif; ?>
            <?php if ($show_status) : ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($current_status); ?>">
            <?php endif; ?>

            <!-- Single Unified Capsule Row -->
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-2.5 lg:gap-2">
                <?php if ($show_tabs) : ?>
                    <!-- Pill Chọn Hình Thức Giao Dịch (Bán / Thuê / Dự án) - Apple Segmented Control Style -->
                    <div class="unified-search-tabs flex items-center justify-center p-1.5 bg-stone/70 rounded-full border border-black/[0.06] shrink-0" role="tablist" aria-label="Hình thức giao dịch" data-unified-tabs>
                        <!-- Tab: Bán (Apple SF Symbol: house) -->
                        <button type="button" 
                                role="tab"
                                class="unified-search-tab px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-200 cursor-pointer flex items-center gap-1.5 <?php echo ($current_tab === 'ban') ? 'bg-white text-charcoal shadow-sm font-semibold' : 'text-charcoal-muted hover:text-charcoal font-medium'; ?>"
                                aria-selected="<?php echo ($current_tab === 'ban') ? 'true' : 'false'; ?>"
                                data-unified-tab="ban"
                                data-action="<?php echo esc_url(home_url('/bat-dong-san')); ?>"
                                data-hinh-thuc="chuyen-nhuong"
                                data-placeholder="Tìm kiếm bất động sản, nhà phố, căn hộ...">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 10.5L12 3l9 7.5V20a1.5 1.5 0 0 1-1.5 1.5H4.5A1.5 1.5 0 0 1 3 20v-9.5z"/>
                                <path d="M18 7.5V4h-2.5v1.4"/>
                                <path d="M9.5 21.5v-6a2.5 2.5 0 0 1 5 0v6"/>
                            </svg>
                            <span>Bán</span>
                        </button>

                        <!-- Tab: Cho thuê (Apple SF Symbol: key) -->
                        <button type="button" 
                                role="tab"
                                class="unified-search-tab px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-200 cursor-pointer flex items-center gap-1.5 <?php echo ($current_tab === 'cho-thue') ? 'bg-white text-charcoal shadow-sm font-semibold' : 'text-charcoal-muted hover:text-charcoal font-medium'; ?>"
                                aria-selected="<?php echo ($current_tab === 'cho-thue') ? 'true' : 'false'; ?>"
                                data-unified-tab="cho-thue"
                                data-action="<?php echo esc_url(home_url('/bat-dong-san')); ?>"
                                data-hinh-thuc="cho-thue"
                                data-placeholder="Tìm kiếm căn hộ, mặt bằng, nhà cho thuê...">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="8" cy="15" r="4"/>
                                <path d="M10.8 12.2L19 4M15.5 4l3.5 3.5M13 6.5l2.5 2.5"/>
                            </svg>
                            <span>Cho thuê</span>
                        </button>

                        <!-- Tab: Dự án (Apple SF Symbol: building.2) -->
                        <button type="button" 
                                role="tab"
                                class="unified-search-tab px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-200 cursor-pointer flex items-center gap-1.5 <?php echo ($current_tab === 'du-an') ? 'bg-white text-charcoal shadow-sm font-semibold' : 'text-charcoal-muted hover:text-charcoal font-medium'; ?>"
                                aria-selected="<?php echo ($current_tab === 'du-an') ? 'true' : 'false'; ?>"
                                data-unified-tab="du-an"
                                data-action="<?php echo esc_url(home_url('/du-an')); ?>"
                                data-hinh-thuc=""
                                data-placeholder="Tìm kiếm dự án khu đô thị, chung cư...">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 21.5h18"/>
                                <path d="M5 21.5V6a1.5 1.5 0 0 1 1.5-1.5h6A1.5 1.5 0 0 1 14 6v15.5"/>
                                <path d="M14 10.5h4.5A1.5 1.5 0 0 1 20 12v9.5"/>
                                <path d="M8 8h3M8 12h3M8 16h3M17 14h.01M17 17.5h.01"/>
                            </svg>
                            <span>Dự án</span>
                        </button>
                    </div>

                    <div class="hidden lg:block w-px h-7 bg-black/[0.08] shrink-0 my-auto"></div>
                <?php endif; ?>

                <!-- Field 1: Keyword Search Input (Apple SF Symbol: magnifyingglass) -->
                <div class="relative flex-1 min-w-[240px] md:min-w-[280px] group">
                    <div class="flex items-center w-full bg-stone/40 hover:bg-stone/60 focus-within:bg-white border border-black/[0.04] focus-within:border-primary/50 focus-within:ring-2 focus-within:ring-primary/10 rounded-full px-4 sm:px-5 py-3 sm:py-3.5 transition-all duration-200">
                        <span class="text-charcoal-muted mr-3 shrink-0" aria-hidden="true">
                            <svg class="w-5 h-5 text-charcoal/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="10.5" cy="10.5" r="6.5"/>
                                <line x1="15.5" y1="15.5" x2="20.5" y2="20.5" stroke-width="2.2"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            name="s"
                            class="w-full bg-transparent border-none outline-none font-sans text-sm sm:text-base text-charcoal placeholder:text-charcoal-muted/70"
                            placeholder="<?php echo esc_attr($placeholder); ?>"
                            value="<?php echo esc_attr($current_name); ?>"
                            aria-label="Từ khóa tìm kiếm">
                        <?php if (!empty($current_name)) : ?>
                            <button type="button" class="text-charcoal-muted hover:text-charcoal p-1.5 cursor-pointer shrink-0" onclick="this.previousElementSibling.value=''; this.remove();" aria-label="Xóa từ khóa">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="6.5" y1="6.5" x2="17.5" y2="17.5"/><line x1="17.5" y1="6.5" x2="6.5" y2="17.5"/></svg>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Field 2: District / Location Dropdown (Apple SF Symbol: mappin.and.ellipse) -->
                <?php if ($show_area) : ?>
                    <div class="relative w-full lg:w-52 shrink-0 unified-dropdown" data-unified-dropdown="khu_vuc">
                        <button type="button" 
                                class="unified-dropdown-trigger w-full flex items-center justify-between gap-2 px-4 sm:px-5 py-3 sm:py-3.5 bg-stone/40 hover:bg-stone/60 border border-black/[0.04] rounded-full text-left font-sans text-sm sm:text-base text-charcoal transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary/50 cursor-pointer"
                                aria-haspopup="listbox"
                                aria-expanded="false"
                                data-unified-trigger="khu_vuc">
                            <div class="flex items-center gap-2 min-w-0">
                                <svg class="w-4 h-4 text-charcoal/50 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2.5a6.5 6.5 0 0 0-6.5 6.5c0 4.8 6.5 12.5 6.5 12.5s6.5-7.7 6.5-12.5a6.5 6.5 0 0 0-6.5-6.5z"/>
                                    <circle cx="12" cy="9" r="2.2"/>
                                </svg>
                                <span class="truncate block text-charcoal text-sm sm:text-base unified-dropdown-label">
                                    <?php echo esc_html($selected_khu_vuc_label); ?>
                                </span>
                            </div>
                            <svg class="w-3.5 h-3.5 text-charcoal-muted shrink-0 transition-transform duration-200 unified-dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9l6 6 6-6"/>
                            </svg>
                        </button>
                        <input type="hidden" name="khu_vuc" value="<?php echo esc_attr($current_khu_vuc); ?>" data-unified-input="khu_vuc">
                        <div class="unified-dropdown-panel hidden absolute top-[calc(100%+8px)] left-0 min-w-full sm:min-w-[240px] z-50 max-h-72 overflow-y-auto bg-white/95 backdrop-blur-xl rounded-2xl border border-black/[0.08] shadow-[0_16px_36px_-4px_rgba(0,0,0,0.12)] p-2 focus:outline-none"
                             role="listbox"
                             data-unified-panel="khu_vuc">
                            <?php foreach ($area_options as $val => $lbl) : ?>
                                <button type="button" 
                                        role="option"
                                        data-unified-option
                                        class="unified-dropdown-option w-full text-left px-4 py-2.5 text-sm font-sans text-charcoal hover:bg-stone/50 hover:text-primary rounded-xl transition-colors flex items-center justify-between cursor-pointer <?php echo ($current_khu_vuc === (string)$val) ? 'text-primary font-semibold bg-stone/40' : ''; ?>"
                                        data-value="<?php echo esc_attr($val); ?>"
                                        data-label="<?php echo esc_attr($lbl); ?>">
                                    <span><?php echo esc_html($lbl); ?></span>
                                    <?php if ($current_khu_vuc === (string)$val) : ?>
                                        <svg class="w-4 h-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5 9.5-10"/></svg>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Field 3: Property Type Dropdown (Apple SF Symbol: building.2) -->
                <?php if ($show_type) : ?>
                    <div class="relative w-full lg:w-48 shrink-0 unified-dropdown" data-unified-dropdown="type">
                        <button type="button" 
                                class="unified-dropdown-trigger w-full flex items-center justify-between gap-2 px-4 sm:px-5 py-3 sm:py-3.5 bg-stone/40 hover:bg-stone/60 border border-black/[0.04] rounded-full text-left font-sans text-sm sm:text-base text-charcoal transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary/50 cursor-pointer"
                                aria-haspopup="listbox"
                                aria-expanded="false"
                                data-unified-trigger="type">
                            <div class="flex items-center gap-2 min-w-0">
                                <svg class="w-4 h-4 text-charcoal/50 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 21.5h18"/>
                                    <path d="M5 21.5V6a1.5 1.5 0 0 1 1.5-1.5h6A1.5 1.5 0 0 1 14 6v15.5"/>
                                    <path d="M14 10.5h4.5A1.5 1.5 0 0 1 20 12v9.5"/>
                                    <path d="M8 8h3M8 12h3M8 16h3M17 14h.01M17 17.5h.01"/>
                                </svg>
                                <span class="truncate block text-charcoal text-sm sm:text-base unified-dropdown-label">
                                    <?php echo esc_html($selected_type_label); ?>
                                </span>
                            </div>
                            <svg class="w-3.5 h-3.5 text-charcoal-muted shrink-0 transition-transform duration-200 unified-dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9l6 6 6-6"/>
                            </svg>
                        </button>
                        <input type="hidden" name="type" value="<?php echo esc_attr((string)$current_type); ?>" data-unified-input="type">
                        <div class="unified-dropdown-panel hidden absolute top-[calc(100%+8px)] left-0 min-w-full sm:min-w-[240px] z-50 max-h-72 overflow-y-auto bg-white/95 backdrop-blur-xl rounded-2xl border border-black/[0.08] shadow-[0_16px_36px_-4px_rgba(0,0,0,0.12)] p-2 focus:outline-none"
                             role="listbox"
                             data-unified-panel="type">
                            <button type="button" 
                                     role="option"
                                    data-unified-option
                                    class="unified-dropdown-option w-full text-left px-4 py-2.5 text-sm font-sans text-charcoal hover:bg-stone/50 hover:text-primary rounded-xl transition-colors flex items-center justify-between cursor-pointer <?php echo ($current_type === '0') ? 'text-primary font-semibold bg-stone/40' : ''; ?>"
                                    data-value="0"
                                    data-group="all"
                                    data-label="Tất cả loại hình">
                                <span>Tất cả loại hình</span>
                            </button>
                            <!-- BĐS Types (Bán & Cho thuê) -->
                            <?php foreach ($bds_type_terms as $term) : ?>
                                <button type="button" 
                                        role="option"
                                        data-unified-option
                                        class="unified-dropdown-option w-full text-left px-4 py-2.5 text-sm font-sans text-charcoal hover:bg-stone/50 hover:text-primary rounded-xl transition-colors flex items-center justify-between cursor-pointer <?php echo ($current_type === (string)$term->term_id) ? 'text-primary font-semibold bg-stone/40' : ''; ?>"
                                        data-value="<?php echo esc_attr($term->term_id); ?>"
                                        data-group="bds"
                                        data-label="<?php echo esc_attr($term->name); ?>">
                                <span><?php echo esc_html($term->name); ?></span>
                            </button>
                        <?php endforeach; ?>
                        <!-- Dự án Types -->
                        <?php foreach ($duan_type_terms as $term) : ?>
                            <button type="button" 
                                    role="option"
                                    data-unified-option
                                    class="unified-dropdown-option w-full text-left px-4 py-2.5 text-sm font-sans text-charcoal hover:bg-stone/50 hover:text-primary rounded-xl transition-colors flex items-center justify-between cursor-pointer <?php echo ($current_type === (string)$term->term_id) ? 'text-primary font-semibold bg-stone/40' : ''; ?>"
                                    data-value="<?php echo esc_attr($term->term_id); ?>"
                                    data-group="du-an"
                                    data-label="<?php echo esc_attr($term->name); ?>">
                                <span><?php echo esc_html($term->name); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Field 4: Price Range Dropdown (Apple SF Symbol: banknote) -->
            <?php if ($show_price) : ?>
                <div class="relative w-full lg:w-48 shrink-0 unified-dropdown" data-unified-dropdown="price">
                    <button type="button" 
                            class="unified-dropdown-trigger w-full flex items-center justify-between gap-2 px-4 sm:px-5 py-3 sm:py-3.5 bg-stone/40 hover:bg-stone/60 border border-black/[0.04] rounded-full text-left font-sans text-sm sm:text-base text-charcoal transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary/10 focus:border-primary/50 cursor-pointer"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            data-unified-trigger="price">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="w-4 h-4 text-charcoal/50 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="6" width="18" height="12" rx="2.5"/>
                                <circle cx="12" cy="12" r="2.5" stroke-width="1.6"/>
                                <path d="M6 9h.01M18 15h.01" stroke-width="2"/>
                            </svg>
                            <span class="truncate block text-charcoal text-sm sm:text-base unified-dropdown-label">
                                <?php echo esc_html($selected_price_label); ?>
                            </span>
                        </div>
                        <svg class="w-3.5 h-3.5 text-charcoal-muted shrink-0 transition-transform duration-200 unified-dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 9l6 6 6-6"/>
                        </svg>
                    </button>
                    <input type="hidden" name="price" value="<?php echo esc_attr($current_price); ?>" data-unified-input="price">
                    <div class="unified-dropdown-panel hidden absolute top-[calc(100%+8px)] left-0 min-w-full sm:min-w-[240px] z-50 max-h-72 overflow-y-auto bg-white/95 backdrop-blur-xl rounded-2xl border border-black/[0.08] shadow-[0_16px_36px_-4px_rgba(0,0,0,0.12)] p-2 focus:outline-none"
                         role="listbox"
                         data-unified-panel="price">
                        <button type="button" 
                                role="option"
                                data-unified-option
                                class="unified-dropdown-option w-full text-left px-4 py-2.5 text-sm font-sans text-charcoal hover:bg-stone/50 hover:text-primary rounded-xl transition-colors flex items-center justify-between cursor-pointer <?php echo ($current_price === '0') ? 'text-primary font-semibold bg-stone/40' : ''; ?>"
                                data-value="0"
                                data-price-group="all"
                                data-label="Tất cả mức giá">
                            <span>Tất cả mức giá</span>
                        </button>
                        <!-- BĐS Price Ranges -->
                        <?php foreach ($bds_price_ranges as $val => $lbl) : ?>
                            <?php if ((string)$val === '0') continue; ?>
                            <button type="button" 
                                    role="option"
                                    data-unified-option
                                    class="unified-dropdown-option w-full text-left px-4 py-2.5 text-sm font-sans text-charcoal hover:bg-stone/50 hover:text-primary rounded-xl transition-colors flex items-center justify-between cursor-pointer <?php echo ($current_price === (string)$val) ? 'text-primary font-semibold bg-stone/40' : ''; ?>"
                                    data-value="<?php echo esc_attr($val); ?>"
                                    data-price-group="bds"
                                    data-label="<?php echo esc_attr($lbl); ?>">
                                <span><?php echo esc_html($lbl); ?></span>
                            </button>
                        <?php endforeach; ?>
                        <!-- Dự án Price Ranges -->
                        <?php foreach ($duan_price_ranges as $val => $lbl) : ?>
                            <?php if ((string)$val === '0') continue; ?>
                            <button type="button" 
                                    role="option"
                                    data-unified-option
                                    class="unified-dropdown-option w-full text-left px-4 py-2.5 text-sm font-sans text-charcoal hover:bg-stone/50 hover:text-primary rounded-xl transition-colors flex items-center justify-between cursor-pointer <?php echo ($current_price === (string)$val) ? 'text-primary font-semibold bg-stone/40' : ''; ?>"
                                    data-value="<?php echo esc_attr($val); ?>"
                                    data-price-group="du-an"
                                    data-label="<?php echo esc_attr($lbl); ?>">
                                <span><?php echo esc_html($lbl); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Field 5: Primary Search Button (Apple SF Symbol: magnifyingglass) -->
            <div class="w-full lg:w-auto shrink-0">
                <button type="submit" 
                        class="w-full h-12 sm:h-[50px] min-h-[48px] px-8 sm:px-9 bg-primary hover:bg-primary-hover active:scale-[0.98] text-white font-sans text-sm sm:text-base font-semibold rounded-full shadow-[0_4px_16px_rgba(201,51,71,0.35)] hover:shadow-[0_6px_20px_rgba(201,51,71,0.45)] flex items-center justify-center gap-2.5 transition-all duration-200 cursor-pointer"
                        aria-label="Tìm kiếm">
                    <svg class="w-5 h-5 text-white shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="10.5" cy="10.5" r="6.5"/>
                        <line x1="15.5" y1="15.5" x2="20.5" y2="20.5" stroke-width="2.5"/>
                    </svg>
                    <span>Tìm kiếm</span>
                </button>
            </div>
        </div>

            <!-- Optional Reset Filter Link (Apple SF Symbol: arrow.clockwise) -->
            <?php if ($show_reset && $has_active_filters) : ?>
                <div class="flex items-center justify-center pt-2">
                    <a href="<?php echo esc_url($action_url); ?>" class="inline-flex items-center gap-1.5 text-xs text-charcoal-muted hover:text-primary font-sans transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 12A8 8 0 1 1 12 4c2.5 0 4.7 1.1 6.2 3"/>
                            <polyline points="18 3 18.5 7.5 14 7"/>
                        </svg>
                        <span>Đặt lại bộ lọc tìm kiếm</span>
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>
