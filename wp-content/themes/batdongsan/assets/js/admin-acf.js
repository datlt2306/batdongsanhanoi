jQuery(function ($) {
    // ---- LOGIC CHO BẤT ĐỘNG SẢN ----
    var bdsFieldsToToggle = [
        'field_so_tang',
        'field_phap_ly',
        'field_duong_vao',
        'field_thiet_ke',
        'field_mat_tien',
        'field_so_mat_tien'
    ];

    function toggleBdsFields() {
        var $field = $('.acf-field[data-key="field_loai_hinh_bds_tax"]');
        if (!$field.length) {
            $field = $('.acf-field[data-name="loai_hinh_bat_dong_san"]');
        }
        if (!$field.length) return;

        var selectedText = '';
        var $select = $field.find('select');

        if ($select.length) {
            selectedText = $select.find('option:selected').text().toLowerCase();
        }

        // Ẩn nếu là căn hộ chung cư
        var isApartment = selectedText.indexOf('chung cư') !== -1 || selectedText.indexOf('căn hộ') !== -1;
        var show = !isApartment;

        bdsFieldsToToggle.forEach(function (key) {
            var $f = $('.acf-field[data-key="' + key + '"]');
            if ($f.length) {
                if (show) {
                    $f.show();
                } else {
                    $f.hide();
                }
            }
        });
    }

    // ---- LOGIC CHO DỰ ÁN ----
    var duAnFieldsToToggle = [
        'field_so_tang',
        'field_so_toa',
        'field_so_can'
    ];

    function toggleDuAnFields() {
        var $field = $('.acf-field[data-key="field_loai_hinh_du_an_tax"]');
        if (!$field.length) {
            $field = $('.acf-field[data-name="loai_hinh_du_an"]');
        }
        if (!$field.length) return;

        var selectedText = '';
        var $select = $field.find('select');

        if ($select.length) {
            selectedText = $select.find('option:selected').text().toLowerCase();
        }

        // Hiển thị nếu Căn hộ chung cư, ẩn nếu là Đất, Biệt thự, Liền kề...
        var isApartment = selectedText.indexOf('chung cư') !== -1 || selectedText.indexOf('căn hộ') !== -1;
        var show = isApartment;

        duAnFieldsToToggle.forEach(function (key) {
            var $f = $('.acf-field[data-key="' + key + '"]');
            if ($f.length) {
                if (show) {
                    $f.show();
                } else {
                    $f.hide();
                }
            }
        });
    }

    // Function chạy tất cả
    function runAllToggles() {
        toggleBdsFields();
        toggleDuAnFields();
    }

    // Chạy khi trang load xong
    runAllToggles();

    // Đảm bảo DOM đã ready (phòng khi Select2 render chậm)
    setTimeout(runAllToggles, 500);
    setTimeout(runAllToggles, 1000);

    // Bắt event change trực tiếp từ jQuery
    var selectors = [
        '.acf-field[data-key="field_loai_hinh_bds_tax"] select',
        '.acf-field[data-name="loai_hinh_bat_dong_san"] select',
        '.acf-field[data-key="field_loai_hinh_du_an_tax"] select',
        '.acf-field[data-name="loai_hinh_du_an"] select'
    ].join(', ');

    $(document).on('change', selectors, function () {
        runAllToggles();
    });

    // Bắt event cụ thể của thư viện Select2 (nếu ACF đang dùng Select2)
    $(document).on('select2:select select2:unselect', selectors, function () {
        runAllToggles();
    });

    // Thêm fallback hook từ API JS của ACF (nếu ACF API đã sẵn sàng)
    if (typeof acf !== 'undefined') {
        acf.addAction('ready_field/key=field_loai_hinh_bds_tax', toggleBdsFields);
        acf.addAction('ready_field/key=field_loai_hinh_du_an_tax', toggleDuAnFields);
        acf.addAction('change', function ($el) {
            if ($el.closest(selectors).length) {
                runAllToggles();
            }
        });
    }
});
