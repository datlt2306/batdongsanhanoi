<?php

/**
 * Filter Area Range Dropdown (Diện tích)
 *
 * Panel diện tích: ô Từ/Đến, thanh trượt, preset, nút Đặt lại/Áp dụng.
 * Layout giống filter-price-dropdown.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$defaults = [
    'current_area'   => '',
    'area_ranges'    => [],
    'area_bounds'    => [0, 500],
    'reset_url'      => '',
    'base_url'       => '',
    'segment_layout' => false,
    'compact'        => false,
];

$args = wp_parse_args($args ?? [], $defaults);

$current_area   = isset($args['current_area']) ? (string) $args['current_area'] : '';
$segment_layout = !empty($args['segment_layout']);
$compact        = !empty($args['compact']);
$base_url       = $args['base_url'] ?: remove_query_arg(['area', 'paged'], home_url('/bat-dong-san'));
$area_ranges    = is_array($args['area_ranges']) ? $args['area_ranges'] : [];
$area_bounds    = $args['area_bounds'] ?? [0, 500];
$bound_min      = isset($area_bounds[0]) ? (int) $area_bounds[0] : 0;
$bound_max      = isset($area_bounds[1]) ? (int) $area_bounds[1] : 500;
$step           = max(5, (int) floor(($bound_max - $bound_min) / 50));
$reset_url      = $args['reset_url'] ?: remove_query_arg(['area', 'paged'], home_url('/bat-dong-san'));

$current_label = isset($area_ranges[$current_area]) ? $area_ranges[$current_area] : 'Tất cả diện tích';
$has_area      = $current_area !== '' && $current_area !== '0';

if ($has_area && !isset($area_ranges[$current_area])) {
    if (strpos($current_area, '-') !== false) {
        [$v1, $v2] = array_map('intval', array_map('trim', explode('-', $current_area, 2)));
        if ($v1 > 0 || $v2 > 0) {
            $current_label = $v1 . ' - ' . $v2 . ' m²';
        }
    } else {
        $v = (int) $current_area;
        if ($v > 0) {
            $current_label = $v . ' m²';
        }
    }
}

$input_min = '';
$input_max = '';

if ($current_area && $current_area !== '0') {
    if (strpos($current_area, '-') !== false) {
        [$input_min, $input_max] = explode('-', $current_area, 2);
        $input_min = max($bound_min, (int) trim($input_min));
        $input_max = min($bound_max, (int) trim($input_max));
    } else {
        $input_min = max($bound_min, (int) $current_area);
        $input_max = $bound_max;
    }
}
if ($input_max === '' || $input_max < $input_min) {
    $input_max = $bound_max;
}

$id_prefix = 'filter-area-range-' . wp_rand(1000, 9999);
?>

<?php
$area_range_btn_class = $compact
    ? 'inline-flex items-center gap-1.5 py-2 px-3 rounded-full bg-[#F5F5F7] text-xs font-sans text-charcoal shrink-0 cursor-pointer outline-none hover:bg-stone/70 transition-all data-[open=true]:bg-stone/80'
    : ($segment_layout ? 'w-full flex items-center justify-between text-left px-3.5 py-2 min-w-0 cursor-pointer outline-none hover:bg-stone/40 active:scale-[0.99] transition-all data-[open=true]:bg-stone/60 rounded-xl lg:rounded-2xl group' : 'inline-flex items-center gap-2 py-2.5 pl-3 pr-8 border border-border rounded-xl bg-surface text-sm font-sans text-charcoal min-w-[160px] cursor-pointer outline-none hover:bg-stone/40 transition-all data-[open=true]:bg-stone/60');
?>
<div class="filter-area-range-dropdown relative <?php echo $compact ? 'shrink-0' : ($segment_layout ? 'filter-bar-segment flex-1 min-w-0 md:border-r md:border-black/[0.06]' : 'shrink-0'); ?>" data-filter-area-range data-filter-area-range-base-url="<?php echo esc_url($base_url); ?>">
    <button
        type="button"
        class="<?php echo esc_attr($area_range_btn_class); ?>"
        aria-haspopup="true"
        aria-expanded="false"
        aria-label="Chọn khoảng diện tích"
        data-filter-area-range-trigger>
        <?php if ($compact) : ?>
            <span class="whitespace-nowrap font-sans font-medium text-charcoal"><?php echo esc_html($current_label); ?></span>
            <svg class="w-3.5 h-3.5 text-charcoal-muted shrink-0 transition-transform" data-filter-area-range-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        <?php elseif ($segment_layout) : ?>
            <div class="flex flex-col text-left min-w-0 flex-1 pr-1.5">
                <span class="block text-[11px] font-medium text-charcoal-muted tracking-tight mb-0.5 font-sans">Diện tích</span>
                <span class="block text-xs sm:text-[13px] font-semibold text-charcoal font-sans whitespace-nowrap overflow-hidden text-ellipsis"><?php echo esc_html($current_label); ?></span>
            </div>
            <svg class="w-3.5 h-3.5 text-charcoal-muted/70 group-hover:text-charcoal shrink-0 transition-transform duration-200 pointer-events-none" data-filter-area-range-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        <?php else : ?>
            <span class="flex-1 text-left truncate font-sans font-medium text-charcoal"><?php echo esc_html($current_label); ?></span>
        <?php endif; ?>
        <?php if ($has_area && !$segment_layout && !$compact) : ?>
            <span class="shrink-0 text-charcoal-muted hover:text-primary" data-filter-area-range-clear title="Xóa lọc diện tích" aria-label="Xóa lọc diện tích">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="6.5" y1="6.5" x2="17.5" y2="17.5"/>
                    <line x1="17.5" y1="6.5" x2="6.5" y2="17.5"/>
                </svg>
            </span>
        <?php endif; ?>
    </button>

    <div
        class="absolute top-full left-0 mt-2 z-50 hidden w-[320px] bg-surface/95 bg-white/95 rounded-2xl border border-black/[0.08] shadow-[0_16px_40px_rgba(0,0,0,0.14)] p-4 backdrop-blur-2xl overflow-hidden apple-dropdown-panel"
        data-filter-area-range-panel
        role="dialog"
        aria-label="Bộ lọc diện tích">
        <div data-filter-area-range-content>
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <label for="<?php echo esc_attr($id_prefix); ?>-from" class="text-xs font-semibold text-charcoal-muted shrink-0 font-sans">Từ</label>
                    <input
                        type="number"
                        id="<?php echo esc_attr($id_prefix); ?>-from"
                        data-filter-area-range-input-min
                        value="<?php echo esc_attr($input_min); ?>"
                        placeholder="<?php echo esc_attr((string) $bound_min); ?>"
                        min="<?php echo esc_attr($bound_min); ?>"
                        max="<?php echo esc_attr($bound_max); ?>"
                        class="flex-1 py-2 px-3 border border-border rounded-xl text-sm font-sans text-charcoal outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 bg-stone/20"
                        aria-label="Diện tích từ (m²)">
                    <span class="text-charcoal-muted text-sm">→</span>
                    <label for="<?php echo esc_attr($id_prefix); ?>-to" class="text-xs font-semibold text-charcoal-muted shrink-0 font-sans">Đến</label>
                    <input
                        type="number"
                        id="<?php echo esc_attr($id_prefix); ?>-to"
                        data-filter-area-range-input-max
                        value="<?php echo esc_attr($input_max); ?>"
                        placeholder="<?php echo esc_attr((string) $bound_max); ?>"
                        min="<?php echo esc_attr($bound_min); ?>"
                        max="<?php echo esc_attr($bound_max); ?>"
                        class="flex-1 py-2 px-3 border border-border rounded-xl text-sm font-sans text-charcoal outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 bg-stone/20"
                        aria-label="Diện tích đến (m²)">
                </div>

                <?php
                $val_min = $input_min !== '' ? (int) $input_min : $bound_min;
                $val_max = $input_max !== '' ? (int) $input_max : $bound_max;
                $range = $bound_max - $bound_min;
                $pct_min = $range > 0 ? round((($val_min - $bound_min) / $range) * 100) : 0;
                $pct_max = $range > 0 ? round((($val_max - $bound_min) / $range) * 100) : 100;
                ?>
                <div class="filter-area-range-dual relative w-full pt-1 pb-2" data-filter-area-range-slider data-bound-min="<?php echo esc_attr($bound_min); ?>" data-bound-max="<?php echo esc_attr($bound_max); ?>" data-step="<?php echo esc_attr($step); ?>">
                    <div class="flex justify-between text-xs font-sans text-charcoal-muted mb-2 font-medium">
                        <span data-filter-area-range-label-min><?php echo esc_html($val_min . ' m²'); ?></span>
                        <span data-filter-area-range-label-max><?php echo esc_html($val_max . ' m²'); ?></span>
                    </div>
                    <div class="relative h-8 flex items-center">
                        <div class="absolute left-0 right-0 h-2 bg-stone/80 rounded-full -translate-y-1/2" aria-hidden="true"></div>
                        <div class="absolute h-2 bg-primary rounded-full pointer-events-none -translate-y-1/2" aria-hidden="true" data-filter-area-range-fill style="left: <?php echo esc_attr($pct_min); ?>%; width: <?php echo esc_attr($pct_max - $pct_min); ?>%;"></div>
                        <div class="absolute w-full h-8 -top-1 cursor-pointer" data-filter-area-range-track>
                            <div class="absolute w-5 h-5 rounded-full bg-primary shadow-md cursor-grab active:cursor-grabbing z-10 -translate-x-1/2 -translate-y-1/2 border-2 border-white" data-filter-area-range-thumb-min style="left: <?php echo esc_attr($pct_min); ?>%; top: 50%;" role="slider" tabindex="0" aria-valuemin="<?php echo esc_attr($bound_min); ?>" aria-valuemax="<?php echo esc_attr($bound_max); ?>" aria-valuenow="<?php echo esc_attr($val_min); ?>" aria-label="Diện tích tối thiểu"></div>
                            <div class="absolute w-5 h-5 rounded-full bg-primary shadow-md cursor-grab active:cursor-grabbing z-10 -translate-x-1/2 -translate-y-1/2 border-2 border-white" data-filter-area-range-thumb-max style="left: <?php echo esc_attr($pct_max); ?>%; top: 50%;" role="slider" tabindex="0" aria-valuemin="<?php echo esc_attr($bound_min); ?>" aria-valuemax="<?php echo esc_attr($bound_max); ?>" aria-valuenow="<?php echo esc_attr($val_max); ?>" aria-label="Diện tích tối đa"></div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-border pt-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-charcoal-muted mb-2 font-sans">Khoảng diện tích có sẵn</p>
                    <ul class="space-y-1 max-h-[200px] overflow-y-auto" role="listbox" aria-label="Chọn khoảng diện tích">
                        <li>
                            <label class="flex items-center gap-2 py-2 px-3 rounded-lg cursor-pointer hover:bg-stone/50 transition-colors text-sm font-sans <?php echo ($current_area === '' || $current_area === '0') ? 'bg-primary-light text-primary font-medium border border-primary/20' : 'text-charcoal'; ?>" data-filter-area-range-preset-label data-value="0">
                                <span>Tất cả diện tích</span>
                            </label>
                        </li>
                        <?php foreach ($area_ranges as $value => $label) : ?>
                            <?php if ($value === '0' || $value === 0) {
                                continue;
                            } ?>
                            <li>
                                <label class="flex items-center gap-2 py-2 px-3 rounded-lg cursor-pointer hover:bg-stone/50 transition-colors text-sm font-sans <?php echo $current_area === (string) $value ? 'bg-primary-light text-primary font-medium border border-primary/20' : 'text-charcoal'; ?>" data-filter-area-range-preset-label data-value="<?php echo esc_attr((string) $value); ?>">
                                    <span><?php echo esc_html($label); ?></span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 mt-4 pt-3 border-t border-border">
                <a
                    href="<?php echo esc_url($reset_url); ?>"
                    class="inline-flex items-center gap-1.5 py-2 px-3 text-xs font-sans font-medium text-charcoal-muted hover:text-primary transition-colors"
                    aria-label="Đặt lại bộ lọc diện tích">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 11A8.1 8.1 0 0 0 4.5 9M4 5v4h4m-4 4a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                    </svg>
                    Đặt lại
                </a>
                <button
                    type="button"
                    class="py-2 px-4 bg-primary text-white text-xs font-sans font-semibold rounded-lg hover:bg-primary-hover shadow-button transition-colors cursor-pointer"
                    aria-label="Áp dụng bộ lọc diện tích"
                    data-filter-area-range-apply>
                    Áp dụng
                </button>
            </div>
        </div>
    </div>
</div>