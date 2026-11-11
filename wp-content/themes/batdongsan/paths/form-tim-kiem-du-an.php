<?php
// Lấy các khoảng giá động từ function
$PriceDisplay = bds_get_price_ranges();

// Lấy giá trị từ URL
$name   = isset($_GET['project-name']) ? trim(sanitize_text_field(wp_unslash($_GET['project-name']))) : '';
$type   = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : null;
$price  = isset($_GET['price']) ? sanitize_text_field(wp_unslash($_GET['price'])) : null;
$status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : null;
$area   = isset($_GET['area']) ? sanitize_text_field(wp_unslash($_GET['area'])) : '';

// Danh sách khu vực Hà Nội
$area_options = array(
	'' => 'Tất cả khu vực',
	'gia-lam' => 'Gia Lâm',
	'nam-tu-liem' => 'Nam Từ Liêm',
	'hai-ba-trung' => 'Hai Bà Trưng',
	'tay-ho' => 'Tây Hồ',
	'long-bien' => 'Long Biên',
	'cau-giay' => 'Cầu Giấy',
	'dong-da' => 'Đống Đa',
	'hoang-mai' => 'Hoàng Mai',
	'thanh-xuan' => 'Thanh Xuân',
	'ha-dong' => 'Hà Đông',
	'ba-dinh' => 'Ba Đình',
	'hoan-kiem' => 'Hoàn Kiếm',
);
?>
<div class="search-project-wrapper">
	<div class="search-project">
		<div class="search-project__header">
			<h2 class="search-project__title">Tìm Bất Động Sản Lý Tưởng Của Bạn</h2>
		</div>
		<form action="<?php echo esc_url(home_url('/du-an')); ?>" method="get" class="projects-filter-bar__form">
			<!-- Mobile: Search Bar (hàng đầu tiên) -->
			<div class="projects-filter-bar__search-wrapper">
				<input
					type="text"
					name="project-name"
					class="projects-filter-bar__search-input"
					placeholder="Tìm kiếm dự án..."
					value="<?php echo $name ? esc_attr($name) : ''; ?>">
				<button type="submit" class="projects-filter-bar__search-button" aria-label="Tìm kiếm">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="10.5" cy="10.5" r="6.5"/>
						<line x1="15.5" y1="15.5" x2="20.5" y2="20.5"/>
					</svg>
				</button>
			</div>

			<!-- Mobile: Filter Dropdowns (hàng thứ hai) -->
			<div class="projects-filter-bar__filters">
				<div class="projects-filter-bar__filter-item">
					<select name="area" class="projects-filter-bar__filter-select">
						<?php
						foreach ($area_options as $value => $label) {
							$selected_option = ($area == $value) ? 'selected' : '';
							echo '<option value="' . esc_attr($value) . '" ' . $selected_option . '>' . esc_html($label) . '</option>';
						}
						?>
					</select>
					<svg class="projects-filter-bar__filter-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M6 9l6 6 6-6"/>
					</svg>
				</div>

				<div class="projects-filter-bar__filter-item">
					<select name="price" class="projects-filter-bar__filter-select">
						<?php
						$selected_price = $price ?? '';
						foreach ($PriceDisplay as $value => $label) {
							$selected_option = ($selected_price == (string) $value) ? 'selected' : '';
							echo '<option value="' . esc_attr($value) . '" ' . $selected_option . '>' . esc_html($label) . '</option>';
						}
						?>
					</select>
					<svg class="projects-filter-bar__filter-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M6 9l6 6 6-6"/>
					</svg>
				</div>

				<div class="projects-filter-bar__filter-item">
					<select name="type" class="projects-filter-bar__filter-select">
						<option value="0" <?php echo (!$type || $type == '0') ? 'selected' : ''; ?>>Loại hình</option>
						<?php
						$filter_terms = get_terms([
							'taxonomy'   => 'loai-hinh',
							'hide_empty' => false,
						]);
						if (!is_wp_error($filter_terms)) {
							foreach ($filter_terms as $term) {
								$selected_term = ($type && (int) $type === (int) $term->term_id) ? 'selected' : '';
								echo '<option value="' . esc_attr($term->term_id) . '" ' . $selected_term . '>' . esc_html($term->name) . '</option>';
							}
						}
						?>
					</select>
					<svg class="projects-filter-bar__filter-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M6 9l6 6 6-6"/>
					</svg>
				</div>

				<div class="projects-filter-bar__filter-item">
					<select name="status" class="projects-filter-bar__filter-select">
						<?php
						$status_options = array(
							'0' => 'Trạng thái',
							'dang-mo-ban' => 'Đang mở bán',
							'sap-mo-ban' => 'Sắp mở bán',
							'da-ban-het' => 'Đã bán hết',
							'tam-dung' => 'Tạm dừng',
						);
						$selected_status = $status ?? '0';
						foreach ($status_options as $value => $label) {
							$selected_option = ($selected_status == $value) ? 'selected' : '';
							echo '<option value="' . esc_attr($value) . '" ' . $selected_option . '>' . esc_html($label) . '</option>';
						}
						?>
					</select>
					<svg class="projects-filter-bar__filter-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M6 9l6 6 6-6"/>
					</svg>
				</div>
			</div>
		</form>
	</div>
</div>