# Template Parts

Các template parts tái sử dụng cho theme BatDongSan.

## Cách sử dụng

### 1. Advisor Card (`advisor-card.php`)

Hiển thị thông tin môi giới/broker.

```php
<?php
$advisor_data = batdongsan_get_advisor_data([
    'lien_he_ten' => $lien_he_ten,
    'lien_he_sdt' => $lien_he_sdt,
    'lien_he_email' => $lien_he_email,
]);

get_template_part('template-parts/advisor-card', null, [
    'prefix_class' => 'hang-chuyen-nhuong-detail', // hoặc 'project-detail'
    ...$advisor_data,
]);
?>
```

### 2. Share Menu (`share-menu.php`)

Menu chia sẻ (Facebook, Zalo, Copy link).

```php
<?php
get_template_part('template-parts/share-menu', null, [
    'prefix_class' => 'hang-chuyen-nhuong-detail',
    'button_id' => 'share-btn', // optional
]);
?>
```

### 3. Breadcrumb (`breadcrumb.php`)

Breadcrumb navigation.

```php
<?php
$breadcrumb_parts = batdongsan_get_hang_chuyen_nhuong_breadcrumb($post_id);
// hoặc
$breadcrumb_parts = batdongsan_get_du_an_breadcrumb($post_id);

get_template_part('template-parts/breadcrumb', null, [
    'breadcrumb_parts' => $breadcrumb_parts,
    'prefix_class' => 'hang-chuyen-nhuong-detail',
    'current_label' => get_the_title(), // optional
]);
?>
```

### 4. Related List (`related-list.php`)

Danh sách items liên quan (projects, listings, posts).

```php
<?php
$related_projects = batdongsan_get_related_projects_by_taxonomy($project_id, $khu_vuc_terms);

get_template_part('template-parts/related-list', null, [
    'query' => $related_projects,
    'title' => 'Dự án liên quan',
    'prefix_class' => 'project-detail',
    'type' => 'projects', // 'projects', 'listings', 'featured'
    'more_link_url' => '#', // optional
    'more_link_text' => 'Xem thêm', // optional
]);
?>
```

### 5. Newsletter Form (`newsletter-form.php`)

Form đăng ký nhận tin.

```php
<?php
get_template_part('template-parts/newsletter-form', null, [
    'title' => 'Nhận bản tin thị trường hàng tuần',
    'description' => 'Mô tả...',
    'form_action' => '#',
    'form_method' => 'post',
]);
?>
```

## Lưu ý

- Tất cả các template parts đều có `$args` parameter để truyền dữ liệu
- Sử dụng `prefix_class` để thay đổi CSS class prefix phù hợp với từng template
- Các template parts đều có escape output để đảm bảo security

