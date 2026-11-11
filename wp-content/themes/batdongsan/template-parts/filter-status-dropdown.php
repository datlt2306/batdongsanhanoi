<?php

/**
 * Filter Status Dropdown (Trạng thái)
 *
 * Dropdown Trạng thái với layout giống Loại hình.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$defaults = [
    'current_status'  => '0',
    'status_options'  => [],
    'segment_layout'  => false,
    'compact'         => false,
];

$args = wp_parse_args($args ?? [], $defaults);

$current_status = $args['current_status'];
$status_options = $args['status_options'];
$segment_layout = !empty($args['segment_layout']);
$compact        = !empty($args['compact']);

$current_label = isset($status_options[$current_status]) ? $status_options[$current_status] : 'Tất cả';

?>

<?php
$status_trigger_class = $compact
    ? 'filter-status-trigger inline-flex items-center gap-1.5 py-2 px-3 rounded-full bg-[#F5F5F7] text-xs font-sans text-charcoal shrink-0 cursor-pointer outline-none hover:bg-stone/70 transition-all data-[open=true]:bg-stone/80'
    : 'filter-status-trigger w-full flex items-center justify-between text-left px-3.5 py-2 min-w-0 cursor-pointer outline-none hover:bg-stone/40 active:scale-[0.99] transition-all data-[open=true]:bg-stone/60 rounded-xl lg:rounded-2xl group';
?>
<div class="filter-status-dropdown relative <?php echo $compact ? 'shrink-0' : ($segment_layout ? 'filter-bar-segment flex-1 min-w-0' : 'shrink-0'); ?>" data-filter-status>
    <button
        type="button"
        class="<?php echo esc_attr($status_trigger_class); ?>"
        aria-haspopup="true"
        aria-expanded="false"
        aria-label="Chọn trạng thái"
        data-filter-status-trigger>

        <?php if ($compact) : ?>
            <span class="whitespace-nowrap font-sans font-medium text-charcoal"><?php echo esc_html($current_label); ?></span>
            <svg class="w-3.5 h-3.5 text-charcoal-muted shrink-0 transition-transform" data-filter-status-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        <?php else : ?>
            <div class="flex flex-col text-left min-w-0 flex-1 pr-1.5">
                <span class="block text-[11px] font-medium text-charcoal-muted tracking-tight mb-0.5 font-sans">Trạng thái</span>
                <span class="block text-xs sm:text-[13px] font-semibold text-charcoal font-sans whitespace-nowrap overflow-hidden text-ellipsis"><?php echo esc_html($current_label); ?></span>
            </div>
            <svg class="w-3.5 h-3.5 text-charcoal-muted/70 group-hover:text-charcoal shrink-0 transition-transform duration-200 pointer-events-none" data-filter-status-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        <?php endif; ?>
    </button>

    <div
        class="absolute top-full left-0 mt-2 z-50 hidden min-w-[240px] max-w-[280px] bg-surface/95 bg-white/95 rounded-2xl border border-black/[0.08] shadow-[0_16px_40px_rgba(0,0,0,0.14)] p-2 backdrop-blur-2xl overflow-hidden apple-dropdown-panel"
        data-filter-status-panel
        role="dialog"
        aria-label="Chọn trạng thái">
        <ul class="max-h-[280px] overflow-y-auto py-1 space-y-0.5 apple-scrollbar" role="listbox" aria-label="Chọn trạng thái">
            <?php foreach ($status_options as $value => $label) : ?>
                <?php $is_active = (string) $current_status === (string) $value; ?>
                <li>
                    <button
                        type="button"
                        class="filter-status-option w-full flex items-center justify-between py-2 px-3 text-left text-xs sm:text-sm font-sans rounded-xl transition-all duration-150 hover:bg-stone/60 cursor-pointer <?php echo $is_active ? 'text-primary font-semibold bg-primary/10' : 'text-charcoal'; ?>"
                        data-filter-status-option
                        data-value="<?php echo esc_attr((string) $value); ?>">
                        <span><?php echo esc_html($label); ?></span>
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