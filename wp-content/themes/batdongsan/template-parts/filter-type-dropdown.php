<?php

/**
 * Filter Type Dropdown (Loại hình)
 *
 * Dropdown panel loại hình với icon, chọn là lọc ngay.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$defaults = [
    'current_type'      => '0',
    'type_terms'        => [],
    'form_action'       => home_url('/bat-dong-san'),
    'segment_layout'    => false,
    'compact'           => false,
    'current_type_slug' => '',
];

$args = wp_parse_args($args ?? [], $defaults);

$current_type      = $args['current_type'];
$type_terms        = $args['type_terms'];
$form_action       = $args['form_action'];
$segment_layout    = !empty($args['segment_layout']);
$compact           = !empty($args['compact']);
$current_type_slug = isset($args['current_type_slug']) ? (string) $args['current_type_slug'] : '';

$current_label = 'Tất cả loại hình';
foreach ($type_terms as $term) {
    $term_id  = isset($term->term_id) ? (string) $term->term_id : '';
    $term_slug = isset($term->slug) ? (string) $term->slug : '';
    if ($term_id === (string) $current_type || ($current_type_slug && $term_slug === $current_type_slug)) {
        $current_label = $term->name;
        break;
    }
}

$icon_map = [
    'can-ho-chung-cu'  => 'building',
    'can_ho'           => 'building',
    'nha-dat'          => 'home',
    'nha_dat'          => 'home',
    'biet-thu-lien-ke' => 'home',
    'biet_thu'         => 'home',
    'cao-oc-van-phong' => 'building',
    'trung-tam-thuong-mai' => 'building',
    'khu-do-thi-moi'   => 'grid',
    'khu-phuc-hop'     => 'grid',
    'nha-o-xa-hoi'     => 'home',
    'khu-nghi-duong'   => 'grid',
];

if (!function_exists('batdongsan_icon')) {
    require_once get_template_directory() . '/template-parts/svg-icons.php';
}

$get_icon = function ($slug) use ($icon_map) {
    $icon = $icon_map[$slug] ?? 'building';
    return function_exists('batdongsan_icon') ? batdongsan_icon($icon, ['width' => 20, 'height' => 20]) : '';
};

$id_prefix = 'filter-type-' . wp_rand(1000, 9999);
?>

<?php
$type_trigger_class = $compact
    ? 'filter-type-trigger inline-flex items-center gap-1.5 py-2 px-3 rounded-full bg-[#F5F5F7] text-xs font-sans text-charcoal shrink-0 cursor-pointer outline-none hover:bg-stone/70 transition-all data-[open=true]:bg-stone/80'
    : 'filter-type-trigger w-full flex items-center justify-between text-left px-3.5 py-2 min-w-0 cursor-pointer outline-none hover:bg-stone/40 active:scale-[0.99] transition-all data-[open=true]:bg-stone/60 rounded-xl lg:rounded-2xl group';
?>
<div class="filter-type-dropdown relative <?php echo $compact ? 'shrink-0' : ($segment_layout ? 'filter-bar-segment flex-1 min-w-0 md:border-r md:border-black/[0.06]' : 'shrink-0'); ?>" data-filter-type>
    <button
        type="button"
        class="<?php echo esc_attr($type_trigger_class); ?>"
        aria-haspopup="true"
        aria-expanded="false"
        aria-label="Chọn loại hình"
        data-filter-type-trigger>

        <?php if ($compact) : ?>
            <span class="whitespace-nowrap font-sans font-medium text-charcoal"><?php echo esc_html($current_label); ?></span>
            <svg class="w-3.5 h-3.5 text-charcoal-muted shrink-0 transition-transform" data-filter-type-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        <?php else : ?>
            <div class="flex flex-col text-left min-w-0 flex-1 pr-1.5">
                <span class="block text-[11px] font-medium text-charcoal-muted tracking-tight mb-0.5 font-sans">Loại hình</span>
                <span class="block text-xs sm:text-[13px] font-semibold text-charcoal font-sans whitespace-nowrap overflow-hidden text-ellipsis"><?php echo esc_html($current_label); ?></span>
            </div>
            <svg class="w-3.5 h-3.5 text-charcoal-muted/70 group-hover:text-charcoal shrink-0 transition-transform duration-200 pointer-events-none" data-filter-type-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        <?php endif; ?>

    </button>

    <div
        class="absolute top-full left-0 mt-2 z-50 hidden min-w-[240px] max-w-[280px] bg-surface/95 bg-white/95 rounded-2xl border border-black/[0.08] shadow-[0_16px_40px_rgba(0,0,0,0.14)] p-2 backdrop-blur-2xl overflow-hidden apple-dropdown-panel"
        data-filter-type-panel
        role="dialog"
        aria-label="Chọn loại hình">
        <ul class="max-h-[280px] overflow-y-auto py-1 space-y-0.5 apple-scrollbar" role="listbox" aria-label="Chọn loại hình">
            <li>
                <button type="button" class="filter-type-option w-full flex items-center gap-2.5 py-2 px-3 text-left text-xs sm:text-sm font-sans rounded-xl transition-all duration-150 hover:bg-stone/60 cursor-pointer data-[active=true]:text-primary data-[active=true]:font-semibold data-[active=true]:bg-primary/10" data-filter-type-option data-value="0" <?php echo ($current_type === '0' && $current_type_slug === '') ? 'data-active="true"' : ''; ?>>
                    <span class="flex shrink-0 w-7 h-7 flex items-center justify-center text-charcoal-muted [.data-\[active\=true\]_&]:text-primary" aria-hidden="true">
                        <?php echo batdongsan_icon('grid', ['width' => 16, 'height' => 16]); ?>
                    </span>
                    <span class="flex-1">Tất cả loại hình</span>
                </button>
            </li>
            <?php foreach ($type_terms as $term) : ?>
                <?php
                $slug = $term->slug ?? '';
                $icon = $get_icon($slug);
                $term_id = isset($term->term_id) ? (string) $term->term_id : '';
                $is_active = $term_id === (string) $current_type || ($current_type_slug && $slug === $current_type_slug);
                ?>
                <li>
                    <button type="button" class="filter-type-option w-full flex items-center gap-2.5 py-2 px-3 text-left text-xs sm:text-sm font-sans rounded-xl transition-all duration-150 hover:bg-stone/60 cursor-pointer <?php echo $is_active ? 'text-primary font-semibold bg-primary/10' : 'text-charcoal'; ?>" data-filter-type-option data-value="<?php echo esc_attr((string) $term->term_id); ?>">
                        <span class="flex shrink-0 w-7 h-7 flex items-center justify-center <?php echo $is_active ? 'text-primary' : 'text-charcoal-muted'; ?>" aria-hidden="true">
                            <?php echo $icon; ?>
                        </span>
                        <span class="flex-1"><?php echo esc_html($term->name); ?></span>
                        <?php if ($is_active) : ?>
                            <svg class="w-3.5 h-3.5 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        <?php endif; ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>