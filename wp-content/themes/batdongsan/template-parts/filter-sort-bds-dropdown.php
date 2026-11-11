<?php

/**
 * Filter Sort Dropdown (Sắp xếp) - Trang Bất động sản
 *
 * Dropdown sắp xếp BĐS: Mặc định, Giá thấp đến cao, Giá cao đến thấp,
 * Diện tích nhỏ đến lớn, Diện tích lớn đến nhỏ.
 * Luxury Real Estate styling with warm limestone, champagne bronze, and charcoal tokens.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$defaults = [
    'current_order' => 1,
    'form_action'   => home_url('/bat-dong-san'),
    'hidden_params' => [],
];

$args = wp_parse_args($args ?? [], $defaults);

$current_order  = (int) $args['current_order'];
$form_action    = $args['form_action'];
$hidden_params  = $args['hidden_params'];

$order_options = [
    1 => 'Mặc định',
    2 => 'Giá thấp đến cao',
    3 => 'Giá cao đến thấp',
    4 => 'Diện tích nhỏ đến lớn',
    5 => 'Diện tích lớn đến nhỏ',
];

$current_label = $order_options[$current_order] ?? 'Mặc định';

?>

<form method="get" action="<?php echo esc_url($form_action); ?>" class="sort-form shrink-0 relative inline-flex items-center">
    <?php foreach ($hidden_params as $name => $value) : ?>
        <?php if ($value !== '' && $value !== null) : ?>
            <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>">
        <?php endif; ?>
    <?php endforeach; ?>
    <div class="relative inline-flex items-center w-full">
        <select
            name="order"
            class="cursor-pointer appearance-none bg-surface bg-white border border-border hover:border-primary/50 rounded-xl px-4 py-2.5 pr-10 font-sans text-sm font-medium text-charcoal focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all duration-200 shadow-subtle"
            aria-label="Sắp xếp bất động sản"
            onchange="this.form.submit()">
            <?php foreach ($order_options as $value => $label) : ?>
                <?php $is_selected = $current_order === (int) $value; ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($is_selected); ?> class="bg-surface text-charcoal py-1.5">
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-charcoal-muted" aria-hidden="true">
            <svg class="w-4 h-4 text-charcoal-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </div>
    </div>
</form>
