<?php

/**
 * Section Header Template Part
 *
 * Header cho các section dùng chung
 *
 * @package BatDongSan
 *
 * @param array $args {
 *     @type string $title          Tiêu đề section (required)
 *     @type string $subtitle       Phụ đề (optional)
 *     @type string $alignment      'center', 'left', 'right' (default: 'center')
 *     @type string $title_tag      Tag HTML cho title: h1, h2, h3 (default: 'h2')
 *     @type string $class          Class bổ sung (optional)
 *     @type string $title_class    Class cho tiêu đề (optional, vd: mb-2 để giảm margin-bottom)
 *     @type string $more_link      URL xem thêm (optional)
 *     @type string $more_text      Text nút xem thêm (default: 'Xem thêm')
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

// Default args
$defaults = [
    'title' => '',
    'subtitle' => '',
    'alignment' => 'center',
    'title_tag' => 'h2',
    'class' => '',
    'title_class' => '',
    'more_link' => '',
    'more_text' => 'Xem thêm',
];

$args = wp_parse_args($args ?? [], $defaults);

if (empty($args['title'])) {
    return;
}

$title = $args['title'];
$subtitle = $args['subtitle'];
$alignment = $args['alignment'];
$title_tag = in_array($args['title_tag'], ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']) ? $args['title_tag'] : 'h2';
$custom_class = $args['class'];
$title_class = $args['title_class'];
$more_link = $args['more_link'];
$more_text = $args['more_text'];

// Build Tailwind classes based on alignment
$header_tw = 'mb-6';
$title_tw = 'font-bold tracking-tight text-charcoal m-0 text-2xl md:text-3xl mb-2';
$subtitle_tw = 'text-charcoal-muted text-sm md:text-base leading-relaxed m-0 mb-6 max-w-[640px]';

if ($alignment === 'center') {
    $header_tw .= ' flex flex-col items-center justify-center text-center';
    $title_tw .= ' text-center';
    $subtitle_tw .= ' text-center mx-auto';
} elseif ($alignment === 'left') {
    $header_tw .= ' flex flex-col items-start';
    $title_tw .= ' text-left';
}

if ($custom_class) {
    $header_tw .= ' ' . $custom_class;
}
if ($title_class) {
    $title_tw .= ' ' . $title_class;
}
?>

<div class="<?php echo esc_attr($header_tw); ?>">
    <<?php echo $title_tag; ?> class="<?php echo esc_attr($title_tw); ?>">
        <?php echo esc_html($title); ?>
    </<?php echo $title_tag; ?>>

    <?php if ($subtitle) : ?>
        <p class="<?php echo esc_attr($subtitle_tw); ?>"><?php echo esc_html($subtitle); ?></p>
    <?php endif; ?>

    <?php if ($more_link) : ?>
        <a href="<?php echo esc_url($more_link); ?>" class="text-sm font-medium text-primary hover:underline inline-flex items-center gap-1">
            <?php echo esc_html($more_text); ?> →
        </a>
    <?php endif; ?>
</div>