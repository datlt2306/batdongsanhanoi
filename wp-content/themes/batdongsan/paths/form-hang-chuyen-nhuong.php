<?php
$terms = get_terms(array(
    'taxonomy'   => 'loai-nha-dat',
    'hide_empty' => false,
));
?>
<form action="/hang-chuyen-nhuong" class="form-hang-chuyen-nhuong">
    <select name="type">
        <option disabled selected>-Loại nhà đất-</option>
        <?php
        foreach ($terms as $key => $value) {
            echo '<option value="' . esc_attr($value->term_id) . '">' . esc_html($value->name) . '</option>';
        }
        ?>
    </select>
    <select name="price">
        <option disabled selected>-Mức giá-</option>
        <?php
        if (isset($giaban) && is_array($giaban)) {
            foreach ($giaban as $key => $value) {
                echo '<option value="' . esc_attr($value['value']) . '">' . esc_html($value['label']) . '</option>';
            }
        }
        ?>
    </select>
    <select name="area">
        <option disabled selected>-Diện tích-</option>
        <?php
        if (isset($dientich) && is_array($dientich)) {
            foreach ($dientich as $key => $value) {
                echo '<option value="' . esc_attr($value['value']) . '">' . esc_html($value['label']) . '</option>';
            }
        }
        ?>
    </select>
    <select name="house-direction">
        <option disabled selected>-Hướng nhà-</option>
        <option value="Đông">Đông</option>
        <option value="Tây">Tây</option>
        <option value="Nam">Nam</option>
        <option value="Bắc">Bắc</option>
        <option value="Đông - Bắc">Đông - Bắc</option>
        <option value="Đông - Nam">Đông - Nam</option>
        <option value="Tây - Bắc">Tây - Bắc</option>
        <option value="Tây - Nam">Tây - Nam</option>
    </select>
    <select name="balcony-direction">
        <option disabled selected>-Hướng ban công-</option>
        <option value="Đông">Đông</option>
        <option value="Tây">Tây</option>
        <option value="Nam">Nam</option>
        <option value="Bắc">Bắc</option>
        <option value="Đông - Bắc">Đông - Bắc</option>
        <option value="Đông - Nam">Đông - Nam</option>
        <option value="Tây - Bắc">Tây - Bắc</option>
        <option value="Tây - Nam">Tây - Nam</option>
    </select>
    <button class="form-search__button">Tìm kiếm ngay</button>
</form>