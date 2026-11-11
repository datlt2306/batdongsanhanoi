<?php
/**
 * Search Form Transfers Template Part
 *
 * Form tìm kiếm hàng chuyển nhượng dùng chung
 *
 * @package BatDongSan
 *
 * @param array $args {
 *     @type string $form_action     URL action của form
 *     @type string $title           Tiêu đề form
 *     @type string $subtitle        Phụ đề
 *     @type string $current_name    Giá trị hiện tại của project-name
 *     @type string $current_price   Giá trị hiện tại của price filter
 *     @type string $current_status  Giá trị hiện tại của status filter
 *     @type string $current_type    Giá trị hiện tại của type filter (danh mục)
 *     @type bool   $show_type       Hiển thị filter danh mục (default: true)
 *     @type bool   $show_price      Hiển thị filter giá (default: true)
 *     @type bool   $show_status     Hiển thị filter trạng thái (default: true)
 *     @type bool   $show_reset      Hiển thị nút reset (default: auto based on search)
 *     @type bool   $is_search       Đang trong mode tìm kiếm
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

// Default args
$defaults = [
    'form_action' => get_permalink(),
    'title' => 'Tìm Bất Động Sản Lý Tưởng Của Bạn',
    'subtitle' => 'Khám phá hàng nghìn bất động sản phù hợp với nhu cầu của bạn',
    'current_name' => '',
    'current_price' => '0',
    'current_status' => '0',
    'current_type' => '0',
    'show_type' => true,
    'show_price' => true,
    'show_status' => true,
    'show_reset' => null,
    'is_search' => false,
];

$args = wp_parse_args($args ?? [], $defaults);

// Lấy các giá trị
$form_action = $args['form_action'];
$title = $args['title'];
$subtitle = $args['subtitle'];
$current_name = $args['current_name'];
$current_price = $args['current_price'];
$current_status = $args['current_status'];
$current_type = $args['current_type'];
$show_type = $args['show_type'];
$show_price = $args['show_price'];
$show_status = $args['show_status'];
$is_search = $args['is_search'];
$show_reset = $args['show_reset'] !== null ? $args['show_reset'] : $is_search;

// Lấy price ranges
$price_ranges = function_exists('bds_get_price_ranges') ? bds_get_price_ranges() : [
    '0' => 'Tất cả mức giá',
    '0-1' => 'Dưới 1 tỷ',
    '1-2' => '1 - 2 tỷ',
    '2-5' => '2 - 5 tỷ',
    '5' => 'Trên 5 tỷ',
];

// Status options cho hàng chuyển nhượng
$status_options = [
    '0' => 'Tất cả trạng thái',
    'dang-ban' => 'Đang bán',
    'da-ban' => 'Đã bán',
    'dang-cho-thue' => 'Đang cho thuê',
    'tam-dung' => 'Tạm dừng',
];

// Lấy danh mục từ taxonomy
$danh_muc_terms = [];
if ($show_type) {
    $terms = get_terms([
        'taxonomy' => 'loai_hinh_bat_dong_san',
        'hide_empty' => false,
    ]);
    if (!is_wp_error($terms)) {
        $danh_muc_terms = $terms;
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
                        <?php if ($subtitle) : ?>
                            <p class="search-project__subtitle"><?php echo esc_html($subtitle); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo esc_url($form_action); ?>" method="get" class="search-project__form">
                    <div class="search-project__filters">
                        <div class="search-project__filter-item">
                            <label class="search-project__filter-label" for="project-name">Dự án / Khu vực</label>
                            <input
                                type="text"
                                id="project-name"
                                name="project-name"
                                class="search-project__filter-input"
                                placeholder="Nhập tên dự án hoặc khu vực..."
                                value="<?php echo esc_attr($current_name); ?>"
                                aria-label="Tìm kiếm bất động sản">
                        </div>

                        <?php if ($show_type && !empty($danh_muc_terms)) : ?>
                            <div class="search-project__filter-item">
                                <label class="search-project__filter-label" for="filter-type">Danh mục</label>
                                <div class="search-project__select-wrapper">
                                    <select id="filter-type" name="type" class="search-project__filter-select">
                                        <option value="0" <?php selected($current_type, '0'); ?>>Tất cả danh mục</option>
                                        <?php foreach ($danh_muc_terms as $term) : ?>
                                            <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($current_type, $term->term_id); ?>>
                                                <?php echo esc_html($term->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <svg class="search-project__select-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 9l6 6 6-6"/>
                                    </svg>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($show_price) : ?>
                            <div class="search-project__filter-item">
                                <label class="search-project__filter-label" for="filter-price">Khoảng giá</label>
                                <div class="search-project__select-wrapper">
                                    <select id="filter-price" name="price" class="search-project__filter-select">
                                        <?php foreach ($price_ranges as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current_price, (string)$value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <svg class="search-project__select-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 9l6 6 6-6"/>
                                    </svg>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($show_status) : ?>
                            <div class="search-project__filter-item">
                                <label class="search-project__filter-label" for="filter-status">Trạng thái</label>
                                <div class="search-project__select-wrapper">
                                    <select id="filter-status" name="status" class="search-project__filter-select">
                                        <?php foreach ($status_options as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current_status, $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <svg class="search-project__select-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 9l6 6 6-6"/>
                                    </svg>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="search-project__filter-action">
                        <button type="submit" class="search-project__submit-button">
                            <?php echo batdongsan_icon('search', ['width' => 20, 'height' => 20, 'class' => 'inline-icon']); ?>
                            Tìm kiếm
                        </button>
                        <?php if ($show_reset) : ?>
                            <a href="<?php echo esc_url($form_action); ?>" class="search-project__reset-button">Đặt lại</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
