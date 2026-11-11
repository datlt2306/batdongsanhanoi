<?php

/**
 * Search Form Projects Template Part
 *
 * Form tìm kiếm dự án dùng chung
 *
 * @package BatDongSan
 *
 * @param array $args {
 *     @type string $form_action    URL action của form (default: /du-an)
 *     @type string $title          Tiêu đề form (optional)
 *     @type string $current_name   Giá trị hiện tại của project-name
 *     @type string $current_price  Giá trị hiện tại của price filter
 *     @type string $current_status Giá trị hiện tại của status filter
 *     @type string $current_type   Giá trị hiện tại của type filter
 *     @type string $current_area   Giá trị hiện tại của area filter
 *     @type bool   $show_area      Hiển thị filter khu vực (default: false)
 *     @type bool   $show_type      Hiển thị filter loại hình (default: true)
 *     @type bool   $show_price     Hiển thị filter giá (default: true)
 *     @type bool   $show_status    Hiển thị filter trạng thái (default: true)
 *     @type array  $area_options   Mảng options khu vực
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

// Default args
$defaults = [
    'form_action' => home_url('/du-an'),
    'title' => 'Tìm Bất Động Sản Lý Tưởng Của Bạn',
    'current_name' => '',
    'current_price' => '0',
    'current_status' => '0',
    'current_type' => '0',
    'current_area' => '',
    'show_area' => false,
    'show_type' => true,
    'show_price' => true,
    'show_status' => true,
    'area_options' => [],
];

$args = wp_parse_args($args ?? [], $defaults);

// Lấy các giá trị
$form_action = $args['form_action'];
$title = $args['title'];
$current_name = $args['current_name'];
$current_price = $args['current_price'];
$current_status = $args['current_status'];
$current_type = $args['current_type'];
$current_area = $args['current_area'];
$show_area = $args['show_area'];
$show_type = $args['show_type'];
$show_price = $args['show_price'];
$show_status = $args['show_status'];
$area_options = $args['area_options'];

// Lấy price ranges
$price_ranges = function_exists('bds_get_price_ranges') ? bds_get_price_ranges() : [
    '0' => 'Tất cả mức giá',
    '0-1' => 'Dưới 1 tỷ',
    '1-2' => '1 - 2 tỷ',
    '2-5' => '2 - 5 tỷ',
    '5' => 'Trên 5 tỷ',
];

// Status options
$status_options = [
    '0' => 'Tất cả',
    'dang-mo-ban' => 'Đang mở bán',
    'sap-mo-ban' => 'Sắp mở bán',
    'da-ban-het' => 'Đã bán hết',
    'tam-dung' => 'Tạm dừng',
];

// Lấy loại hình từ taxonomy
$loai_hinh_terms = [];
if ($show_type) {
    $terms = get_terms([
        'taxonomy' => 'loai_hinh_du_an',
        'hide_empty' => false,
    ]);
    if (!is_wp_error($terms)) {
        $loai_hinh_terms = $terms;
    }
}

// Include icons library
if (!function_exists('batdongsan_icon')) {
    require_once get_template_directory() . '/template-parts/svg-icons.php';
}
?>

<div id="search-section" class="search-panel">
    <div class="container">
        <div class="search-project-wrapper">
            <div class="search-project">
                <?php if ($title) : ?>
                    <div class="search-project__header">
                        <h2 class="search-project__title"><?php echo esc_html($title); ?></h2>
                    </div>
                <?php endif; ?>

                <form action="<?php echo esc_url($form_action); ?>" method="get" class="projects-filter-bar__form">
                    <div class="projects-filter-bar__field">
                        <label>Dự án / Khu vực</label>
                        <div class="projects-filter-bar__search">
                            <span class="projects-filter-bar__search-icon" aria-hidden="true">
                                <?php echo batdongsan_icon('search', ['width' => 16, 'height' => 16]); ?>
                            </span>
                            <input
                                type="text"
                                name="project-name"
                                class="projects-filter-bar__search-input"
                                placeholder="Tìm kiếm dự án..."
                                value="<?php echo esc_attr($current_name); ?>"
                                aria-label="Tìm kiếm dự án">
                        </div>
                    </div>

                    <div class="projects-filter-bar__group">
                        <?php if ($show_area && !empty($area_options)) : ?>
                            <div class="projects-filter-bar__field">
                                <label for="filter-area">Khu vực</label>
                                <select name="area" id="filter-area" class="projects-filter-bar__select">
                                    <?php foreach ($area_options as $value => $label) : ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($current_area, $value); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($show_type && !empty($loai_hinh_terms)) : ?>
                            <div class="projects-filter-bar__field">
                                <label for="filter-type">Loại hình</label>
                                <select name="type" id="filter-type" class="projects-filter-bar__select">
                                    <option value="0" <?php selected($current_type, '0'); ?>>Tất cả</option>
                                    <?php foreach ($loai_hinh_terms as $term) : ?>
                                        <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($current_type, $term->term_id); ?>>
                                            <?php echo esc_html($term->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($show_price) : ?>
                            <div class="projects-filter-bar__field">
                                <label for="filter-price">Khoảng giá</label>
                                <select name="price" id="filter-price" class="projects-filter-bar__select">
                                    <?php foreach ($price_ranges as $value => $label) : ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($current_price, (string)$value); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($show_status) : ?>
                            <div class="projects-filter-bar__field">
                                <label for="filter-status">Trạng thái</label>
                                <select name="status" id="filter-status" class="projects-filter-bar__select">
                                    <?php foreach ($status_options as $value => $label) : ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($current_status, $value); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="projects-filter-bar__actions">
                            <button type="submit" class="projects-filter-bar__apply">
                                Áp dụng
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>