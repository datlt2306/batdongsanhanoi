<?php
/**
 * Filter Header Modal - Khu vực
 * Button + modal (thay dropdown) cho header filter mobile
 * Apple Cupertino Half-Sheet style with rounded squircle, SF checkmark, and backdrop blur.
 *
 * @package BatDongSan
 */
if (!defined('ABSPATH')) exit;

$defaults = ['current_khu_vuc' => '', 'area_options' => []];
$args = wp_parse_args($args ?? [], $defaults);
$current = $args['current_khu_vuc'];
$options = $args['area_options'];
$label = isset($options[$current]) ? $options[$current] : 'Toàn Hà Nội';
$is_filtered = !empty($current);
?>
<div class="w-full min-w-0" data-filter-header-modal="khu-vuc">
    <button type="button" class="filter-header-modal-trigger w-full inline-flex items-center justify-center gap-1 h-8 px-1.5 xs:px-2 rounded-full text-xs font-medium cursor-pointer outline-none transition-all active:scale-95 <?php echo $is_filtered ? 'bg-primary text-white font-semibold shadow-sm' : 'bg-black/[0.04] text-charcoal hover:bg-black/[0.08] border border-black/[0.07]'; ?>" data-filter-header-modal-trigger aria-label="Chọn khu vực">
        <span class="truncate max-w-[calc(100%-14px)] text-[11px] xs:text-xs text-center"><?php echo esc_html($label); ?></span>
        <svg class="w-2.5 h-2.5 xs:w-3 xs:h-3 <?php echo $is_filtered ? 'text-white' : 'text-charcoal-muted'; ?> shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
    </button>
    <div class="filter-header-modal fixed inset-0 z-[1100] hidden flex flex-col items-stretch justify-end" data-filter-header-modal-panel data-filter-header-modal-id="khu-vuc" role="dialog" aria-modal="true" aria-label="Chọn khu vực">
        <div class="absolute inset-0 bg-black/45 backdrop-blur-sm" data-filter-header-modal-backdrop aria-hidden="true"></div>
        <div class="relative w-full max-h-[82vh] sm:max-h-[620px] sm:max-w-md sm:mx-auto bg-white rounded-t-[28px] sm:rounded-2xl shadow-[0_-12px_48px_rgba(0,0,0,0.18)] border-t border-black/[0.06] flex flex-col z-10 animate-slide-up">
            <div class="w-10 h-1 bg-border rounded-full mx-auto mt-2.5 mb-1 shrink-0" aria-hidden="true"></div>
            <div class="flex items-center justify-between px-5 py-3 border-b border-black/[0.06]">
                <h3 class="text-base font-semibold text-charcoal tracking-tight font-sans m-0">Chọn khu vực</h3>
                <button type="button" class="w-8 h-8 flex items-center justify-center rounded-full bg-black/[0.04] text-charcoal/70 hover:text-charcoal hover:bg-black/[0.08] active:scale-90 transition-all cursor-pointer" data-filter-header-modal-close aria-label="Đóng">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="6.5" y1="6.5" x2="17.5" y2="17.5"/><line x1="17.5" y1="6.5" x2="6.5" y2="17.5"/></svg>
                </button>
            </div>
            <div class="p-4 space-y-3 overflow-y-auto flex-1 overscroll-contain">
                <ul class="space-y-1" role="listbox">
                    <li>
                        <button type="button" class="filter-header-modal-option w-full flex items-center justify-between py-2.5 px-3.5 text-left text-sm rounded-xl font-medium transition-all <?php echo empty($current) ? 'bg-primary/10 text-primary font-semibold' : 'text-charcoal hover:bg-black/[0.03]'; ?>" data-value="" role="option" <?php echo empty($current) ? 'aria-selected="true"' : ''; ?>>
                            <span>Toàn Hà Nội</span>
                            <svg class="w-4 h-4 text-primary shrink-0 <?php echo empty($current) ? '' : 'hidden'; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>
                    </li>
                    <?php foreach ($options as $val => $opt_label) :
                        if ((string)$val === '') continue;
                        $active = (string)$current === (string)$val;
                    ?>
                    <li>
                        <button type="button" class="filter-header-modal-option w-full flex items-center justify-between py-2.5 px-3.5 text-left text-sm rounded-xl font-medium transition-all <?php echo $active ? 'bg-primary/10 text-primary font-semibold' : 'text-charcoal hover:bg-black/[0.03]'; ?>" data-value="<?php echo esc_attr((string)$val); ?>" role="option" <?php echo $active ? 'aria-selected="true"' : ''; ?>>
                            <span><?php echo esc_html($opt_label); ?></span>
                            <svg class="w-4 h-4 text-primary shrink-0 <?php echo $active ? '' : 'hidden'; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="flex items-center gap-3 p-4 border-t border-black/[0.06] bg-white rounded-b-[28px] sm:rounded-b-2xl">
                <button type="button" class="py-2.5 px-4 text-xs font-semibold text-charcoal-muted hover:text-charcoal active:scale-95 transition-all cursor-pointer" data-filter-header-modal-reset>Thiết lập lại</button>
                <button type="button" class="flex-1 h-11 rounded-full bg-primary hover:bg-primary-hover text-white text-sm font-semibold flex items-center justify-center gap-2 shadow-sm active:scale-95 transition-all cursor-pointer" data-filter-header-modal-apply>
                    <span>Áp dụng</span>
                </button>
            </div>
        </div>
    </div>
</div>
