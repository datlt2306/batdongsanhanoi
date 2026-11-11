<?php
/**
 * Leads Management CPT, Custom Admin Schema, and Secure AJAX Endpoint
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

// Polyfill helpers for CLI/test environment where full WP Core may not be loaded
if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field(string $str): string {
        $filtered = strip_tags($str);
        return trim($filtered);
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email(string $email): string {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL) ?: '';
    }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('selected')) {
    function selected($selected, $current = true, bool $echo = true): string {
        $result = ((string) $selected === (string) $current) ? ' selected="selected"' : '';
        if ($echo) {
            echo $result;
        }
        return $result;
    }
}

/**
 * 1. Register CPT khach-hang-lien-he (Leads)
 */
function batdongsan_register_cpt_leads(): void {
    if (!function_exists('register_post_type')) {
        return;
    }

    $labels = [
        'name'                  => 'Khách hàng liên hệ',
        'singular_name'         => 'Khách hàng liên hệ',
        'menu_name'             => 'Khách hàng liên hệ',
        'all_items'             => 'Tất cả khách hàng',
        'add_new'               => 'Thêm khách hàng',
        'add_new_item'          => 'Thêm khách hàng mới',
        'edit_item'             => 'Chi tiết khách hàng',
        'new_item'              => 'Khách hàng mới',
        'view_item'             => 'Xem thông tin khách hàng',
        'search_items'          => 'Tìm kiếm khách hàng',
        'not_found'             => 'Không tìm thấy khách hàng',
        'not_found_in_trash'    => 'Không có khách hàng trong thùng rác',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 26,
        'menu_icon'          => 'dashicons-id-alt',
        'capability_type'    => 'post',
        'hierarchical'       => false,
        'supports'           => ['title', 'custom-fields'],
        'has_archive'        => false,
        'rewrite'            => false,
        'query_var'          => false,
        'publicly_queryable' => false,
        'exclude_from_search'=> true,
    ];

    register_post_type('khach-hang-lien-he', $args);
}
add_action('init', 'batdongsan_register_cpt_leads', 10);

/**
 * 2. Custom Admin Columns for khach-hang-lien-he
 */
function batdongsan_leads_columns(array $columns): array {
    $new_columns = [];
    if (isset($columns['cb'])) {
        $new_columns['cb'] = $columns['cb'];
    }
    $new_columns['title']        = 'Khách hàng';
    $new_columns['phone']        = 'Số điện thoại';
    $new_columns['email']        = 'Email';
    $new_columns['source']       = 'Nguồn form';
    $new_columns['related_post'] = 'Dự án / BĐS';
    $new_columns['status']       = 'Trạng thái';
    $new_columns['submitted_at'] = 'Thời gian gửi';

    return $new_columns;
}
add_filter('manage_khach-hang-lien-he_posts_columns', 'batdongsan_leads_columns');
add_filter('manage_edit-khach-hang-lien-he_columns', 'batdongsan_leads_columns');

/**
 * Display Custom Column Data for khach-hang-lien-he
 */
function batdongsan_leads_custom_column(string $column, int $post_id): void {
    switch ($column) {
        case 'phone':
            $phone = get_post_meta($post_id, 'phone', true);
            if (!empty($phone)) {
                echo '<a href="tel:' . esc_attr($phone) . '" style="font-weight:600;color:#0284c7;">' . esc_html($phone) . '</a>';
            } else {
                echo '<span style="color:#94a3b8;">—</span>';
            }
            break;

        case 'email':
            $email = get_post_meta($post_id, 'email', true);
            if (!empty($email)) {
                echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
            } else {
                echo '<span style="color:#94a3b8;">—</span>';
            }
            break;

        case 'source':
            $source = get_post_meta($post_id, 'source', true);
            $source_labels = [
                'advisor_modal'  => 'Advisor Modal',
                'newsletter'     => 'Bản tin thị trường',
                'project_detail' => 'Chi tiết dự án',
                'listing_detail' => 'Chi tiết BĐS',
            ];
            $label = $source_labels[$source] ?? (!empty($source) ? esc_html($source) : 'Trực tiếp');
            echo '<span class="badge" style="background:#f1f5f9;color:#475569;padding:2px 8px;border-radius:4px;font-size:12px;">' . esc_html($label) . '</span>';
            break;

        case 'related_post':
            $rel_post_id = get_post_meta($post_id, 'post_id', true);
            if ($rel_post_id && ($rel_post = get_post((int) $rel_post_id))) {
                $edit_url = function_exists('get_edit_post_link') ? get_edit_post_link($rel_post->ID) : '';
                if ($edit_url) {
                    echo '<a href="' . esc_url($edit_url) . '"><strong>' . esc_html($rel_post->post_title) . '</strong></a>';
                } else {
                    echo '<strong>' . esc_html($rel_post->post_title) . '</strong>';
                }
            } else {
                echo '<span style="color:#94a3b8;">—</span>';
            }
            break;

        case 'status':
            $status = get_post_meta($post_id, 'status', true) ?: 'moi';
            $status_configs = [
                'moi'        => ['label' => 'Mới', 'bg' => '#e0f2fe', 'color' => '#0369a1'],
                'da_goi'     => ['label' => 'Đã gọi', 'bg' => '#ffedd5', 'color' => '#c2410c'],
                'thanh_cong' => ['label' => 'Thành công', 'bg' => '#dcfce7', 'color' => '#15803d'],
                'huy'        => ['label' => 'Hủy', 'bg' => '#fee2e2', 'color' => '#b91c1c'],
            ];
            $cfg = $status_configs[$status] ?? ['label' => esc_html($status), 'bg' => '#f1f5f9', 'color' => '#475569'];
            echo '<span style="background:' . esc_attr($cfg['bg']) . ';color:' . esc_attr($cfg['color']) . ';padding:3px 8px;border-radius:4px;font-weight:600;font-size:12px;display:inline-block;">' . esc_html($cfg['label']) . '</span>';
            break;

        case 'submitted_at':
            $submitted = get_post_meta($post_id, 'submitted_at', true);
            if ($submitted && function_exists('date_i18n')) {
                echo esc_html(date_i18n('d/m/Y H:i', strtotime($submitted)));
            } elseif (function_exists('get_the_date')) {
                echo esc_html(get_the_date('d/m/Y H:i', $post_id));
            } else {
                echo esc_html($submitted ?: '—');
            }
            break;
    }
}
add_action('manage_khach-hang-lien-he_posts_custom_column', 'batdongsan_leads_custom_column', 10, 2);

/**
 * Make Columns Sortable in Admin
 */
function batdongsan_leads_sortable_columns(array $columns): array {
    $columns['phone']        = 'phone';
    $columns['email']        = 'email';
    $columns['source']       = 'source';
    $columns['status']       = 'status';
    $columns['submitted_at'] = 'submitted_at';
    return $columns;
}
add_filter('manage_edit-khach-hang-lien-he_sortable_columns', 'batdongsan_leads_sortable_columns');

/**
 * Filter Dropdowns for Leads in WP Admin
 */
function batdongsan_leads_admin_filters(string $post_type): void {
    if ($post_type !== 'khach-hang-lien-he') {
        return;
    }

    $current_status = $_GET['lead_status'] ?? '';
    $statuses = [
        'moi'        => 'Mới',
        'da_goi'     => 'Đã gọi',
        'thanh_cong' => 'Thành công',
        'huy'        => 'Hủy',
    ];

    echo '<select name="lead_status">';
    echo '<option value="">-- Tất cả trạng thái --</option>';
    foreach ($statuses as $val => $name) {
        printf('<option value="%s"%s>%s</option>', esc_attr($val), selected($current_status, $val, false), esc_html($name));
    }
    echo '</select>';

    $current_source = $_GET['lead_source'] ?? '';
    $sources = [
        'advisor_modal'  => 'Advisor Modal',
        'newsletter'     => 'Bản tin thị trường',
        'project_detail' => 'Chi tiết dự án',
        'listing_detail' => 'Chi tiết BĐS',
    ];

    echo '<select name="lead_source">';
    echo '<option value="">-- Tất cả nguồn --</option>';
    foreach ($sources as $val => $name) {
        printf('<option value="%s"%s>%s</option>', esc_attr($val), selected($current_source, $val, false), esc_html($name));
    }
    echo '</select>';
}
add_action('restrict_manage_posts', 'batdongsan_leads_admin_filters');

/**
 * Filter Query for Admin Screen
 */
function batdongsan_leads_filter_query($query): void {
    global $pagenow;
    if (is_admin() && $pagenow === 'edit.php' && isset($query->query_vars['post_type']) && $query->query_vars['post_type'] === 'khach-hang-lien-he') {
        $meta_query = $query->get('meta_query') ?: [];

        if (!empty($_GET['lead_status'])) {
            $meta_query[] = [
                'key'     => 'status',
                'value'   => sanitize_text_field($_GET['lead_status']),
                'compare' => '=',
            ];
        }

        if (!empty($_GET['lead_source'])) {
            $meta_query[] = [
                'key'     => 'source',
                'value'   => sanitize_text_field($_GET['lead_source']),
                'compare' => '=',
            ];
        }

        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }

        $orderby = $query->get('orderby');
        if (in_array($orderby, ['phone', 'email', 'source', 'status', 'submitted_at'], true)) {
            $query->set('meta_key', $orderby);
            $query->set('orderby', 'meta_value');
        }
    }
}
add_action('pre_get_posts', 'batdongsan_leads_filter_query');

/**
 * Meta Box for Viewing / Editing Lead Details
 */
function batdongsan_leads_meta_boxes(): void {
    if (function_exists('add_meta_box')) {
        add_meta_box(
            'bds_lead_details',
            'Chi tiết thông tin khách hàng liên hệ',
            'batdongsan_render_lead_meta_box',
            'khach-hang-lien-he',
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'batdongsan_leads_meta_boxes');

function batdongsan_render_lead_meta_box($post): void {
    if (function_exists('wp_nonce_field')) {
        wp_nonce_field('bds_save_lead_meta', 'bds_lead_meta_nonce');
    }
    $fullname = get_post_meta($post->ID, 'fullname', true);
    $phone = get_post_meta($post->ID, 'phone', true);
    $email = get_post_meta($post->ID, 'email', true);
    $source = get_post_meta($post->ID, 'source', true);
    $status = get_post_meta($post->ID, 'status', true) ?: 'moi';
    $message = get_post_meta($post->ID, 'message', true);
    $post_id = get_post_meta($post->ID, 'post_id', true);
    $ip_address = get_post_meta($post->ID, 'ip_address', true);
    $submitted_at = get_post_meta($post->ID, 'submitted_at', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="lead_status"><strong>Trạng thái xử lý:</strong></label></th>
            <td>
                <select name="lead_status" id="lead_status" style="font-weight:600;padding:5px 10px;">
                    <option value="moi" <?php selected($status, 'moi'); ?>>🔵 Mới</option>
                    <option value="da_goi" <?php selected($status, 'da_goi'); ?>>🟠 Đã gọi</option>
                    <option value="thanh_cong" <?php selected($status, 'thanh_cong'); ?>>🟢 Thành công</option>
                    <option value="huy" <?php selected($status, 'huy'); ?>>🔴 Hủy</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><strong>Họ và tên:</strong></th>
            <td><input type="text" name="lead_fullname" value="<?php echo esc_attr($fullname); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><strong>Số điện thoại:</strong></th>
            <td>
                <input type="text" name="lead_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text">
                <?php if ($phone): ?>
                    <a href="tel:<?php echo esc_attr($phone); ?>" class="button button-secondary" style="margin-left:8px;">Gọi ngay</a>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><strong>Email:</strong></th>
            <td>
                <input type="email" name="lead_email" value="<?php echo esc_attr($email); ?>" class="regular-text">
                <?php if ($email): ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>" class="button button-secondary" style="margin-left:8px;">Gửi email</a>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><strong>Nguồn gửi:</strong></th>
            <td><code><?php echo esc_html($source ?: 'advisor_modal'); ?></code></td>
        </tr>
        <?php if ($post_id): ?>
        <tr>
            <th><strong>Bài viết / Dự án liên quan:</strong></th>
            <td>
                <?php
                $rel = get_post((int)$post_id);
                if ($rel) {
                    $link = function_exists('get_permalink') ? get_permalink($rel->ID) : '#';
                    echo '<a href="' . esc_url($link) . '" target="_blank">' . esc_html($rel->post_title) . ' (#' . $rel->ID . ')</a>';
                } else {
                    echo '#' . (int)$post_id;
                }
                ?>
            </td>
        </tr>
        <?php endif; ?>
        <tr>
            <th><strong>Nội dung yêu cầu:</strong></th>
            <td><textarea name="lead_message" rows="4" class="large-text"><?php echo esc_textarea($message); ?></textarea></td>
        </tr>
        <tr>
            <th><strong>Thời gian gửi:</strong></th>
            <td><?php echo esc_html($submitted_at ?: (function_exists('get_the_date') ? get_the_date('Y-m-d H:i:s', $post->ID) : '—')); ?></td>
        </tr>
        <tr>
            <th><strong>Địa chỉ IP:</strong></th>
            <td><code><?php echo esc_html($ip_address ?: '—'); ?></code></td>
        </tr>
    </table>
    <?php
}

function batdongsan_save_lead_meta(int $post_id): void {
    if (!isset($_POST['bds_lead_meta_nonce']) || !wp_verify_nonce($_POST['bds_lead_meta_nonce'], 'bds_save_lead_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (function_exists('current_user_can') && !current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['lead_status'])) {
        $allowed = ['moi', 'da_goi', 'thanh_cong', 'huy'];
        $new_status = sanitize_text_field($_POST['lead_status']);
        if (in_array($new_status, $allowed, true)) {
            update_post_meta($post_id, 'status', $new_status);
        }
    }
    if (isset($_POST['lead_fullname'])) {
        update_post_meta($post_id, 'fullname', sanitize_text_field($_POST['lead_fullname']));
    }
    if (isset($_POST['lead_phone'])) {
        update_post_meta($post_id, 'phone', sanitize_text_field($_POST['lead_phone']));
    }
    if (isset($_POST['lead_email'])) {
        update_post_meta($post_id, 'email', sanitize_email($_POST['lead_email']));
    }
    if (isset($_POST['lead_message'])) {
        $msg = sanitize_textarea_field($_POST['lead_message']);
        update_post_meta($post_id, 'message', $msg);
    }
}
add_action('save_post_khach-hang-lien-he', 'batdongsan_save_lead_meta');

/**
 * 3. Secure AJAX Lead Submission Endpoint
 * Action: batdongsan_submit_lead
 */
function batdongsan_ajax_submit_lead(): void {
    // 1. Nonce check: return HTTP 403 on missing or invalid nonce
    $nonce = isset($_POST['nonce']) ? trim((string) $_POST['nonce']) : '';
    if ($nonce === '' || !check_ajax_referer('bds_lead_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Forbidden: Invalid or missing nonce'], 403);
        exit;
    }

    // 2. Honeypot check: anti-bot trap (both 'bds_honeypot' and 'honeypot')
    $hp_bds = isset($_POST['bds_honeypot']) ? (string) $_POST['bds_honeypot'] : '';
    $hp_gen = isset($_POST['honeypot']) ? (string) $_POST['honeypot'] : '';

    if ($hp_bds !== '' || $hp_gen !== '') {
        wp_send_json_error(['message' => 'Spam detected'], 400);
        exit;
    }

    // 3. Extract & sanitize input fields
    $fullname = isset($_POST['fullname']) ? sanitize_text_field(wp_unslash($_POST['fullname'])) : '';
    if ($fullname === '' && isset($_POST['newsletter_name'])) {
        $fullname = sanitize_text_field(wp_unslash($_POST['newsletter_name']));
    }

    $phone = isset($_POST['phone']) ? trim(sanitize_text_field(wp_unslash($_POST['phone']))) : '';
    if ($phone === '' && isset($_POST['newsletter_phone'])) {
        $phone = trim(sanitize_text_field(wp_unslash($_POST['newsletter_phone'])));
    }

    $email = isset($_POST['email']) ? trim(sanitize_text_field(wp_unslash($_POST['email']))) : '';
    if ($email === '' && isset($_POST['newsletter_email'])) {
        $email = trim(sanitize_text_field(wp_unslash($_POST['newsletter_email'])));
    }

    $message = '';
    if (isset($_POST['message'])) {
        $raw_message = wp_unslash($_POST['message']);
        $message = sanitize_textarea_field($raw_message);
    }

    $source = isset($_POST['source']) ? sanitize_text_field(wp_unslash($_POST['source'])) : 'advisor_modal';
    if ($source === '') {
        $source = 'advisor_modal';
    }

    $post_id_rel = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

    // 4. Validate required fields
    if ($fullname === '') {
        wp_send_json_error(['message' => 'Vui lòng nhập họ và tên'], 400);
        exit;
    }

    // Vietnamese phone format: 10 digits starting with 03, 05, 07, 08, 09 or +84 / 84
    if ($phone === '') {
        if ($source !== 'newsletter') {
            wp_send_json_error(['message' => 'Vui lòng nhập số điện thoại'], 400);
            exit;
        } elseif ($email === '') {
            wp_send_json_error(['message' => 'Vui lòng cung cấp số điện thoại hoặc email'], 400);
            exit;
        }
    } else {
        if (!preg_match('/^(?:\+84|84|0)[35789][0-9]{8}$/', $phone)) {
            wp_send_json_error(['message' => 'Số điện thoại không đúng định dạng'], 400);
            exit;
        }
    }

    // Email validation (optional unless provided)
    if ($email !== '') {
        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Email không đúng định dạng'], 400);
            exit;
        }
        $email = sanitize_email($email);
    }

    // 5. Create CPT khach-hang-lien-he post
    $post_title = wp_strip_all_tags($fullname) . ($phone ? ' - ' . $phone : '');
    $submitted_at = function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

    $insert_result = wp_insert_post([
        'post_title'   => $post_title,
        'post_type'    => 'khach-hang-lien-he',
        'post_status'  => 'publish',
        'post_content' => $message,
    ]);

    if (!$insert_result || is_wp_error($insert_result)) {
        wp_send_json_error(['message' => 'Không thể lưu thông tin liên hệ'], 500);
        exit;
    }

    $lead_id = is_object($insert_result) ? $insert_result->ID : (int) $insert_result;

    // 6. Save metadata
    update_post_meta($lead_id, 'fullname', $fullname);
    update_post_meta($lead_id, 'phone', $phone);
    update_post_meta($lead_id, 'email', $email);
    update_post_meta($lead_id, 'source', $source);
    update_post_meta($lead_id, 'status', 'moi');
    update_post_meta($lead_id, 'message', $message);
    update_post_meta($lead_id, 'post_id', $post_id_rel);
    update_post_meta($lead_id, 'ip_address', $ip_address);
    update_post_meta($lead_id, 'submitted_at', $submitted_at);

    // Also lead_* aliases for admin/compatibility
    update_post_meta($lead_id, 'lead_fullname', $fullname);
    update_post_meta($lead_id, 'lead_phone', $phone);
    update_post_meta($lead_id, 'lead_email', $email);
    update_post_meta($lead_id, 'lead_source', $source);
    update_post_meta($lead_id, 'lead_status', 'moi');

    // 7. Trigger Admin Email Notification (wp_mail)
    $admin_email = function_exists('get_option') ? get_option('admin_email') : 'admin@batdongsanhanoi.local';
    if (empty($admin_email)) {
        $admin_email = 'admin@batdongsanhanoi.local';
    }

    $admin_url = function_exists('admin_url') ? admin_url('post.php?post=' . $lead_id . '&action=edit') : home_url('/wp-admin/post.php?post=' . $lead_id . '&action=edit');

    $subject = sprintf('[BatDongSan] Khách hàng mới liên hệ: %s', $fullname);
    $body = "Hệ thống vừa nhận được thông tin liên hệ mới:\n\n"
        . "• Họ và tên: " . $fullname . "\n"
        . "• Số điện thoại: " . $phone . "\n"
        . "• Email: " . ($email ?: 'Không có') . "\n"
        . "• Nguồn gửi: " . $source . "\n"
        . "• Nội dung: " . ($message ?: 'Không có') . "\n"
        . "• ID bài viết: " . ($post_id_rel ?: 'Không có') . "\n"
        . "• Thời gian gửi: " . $submitted_at . "\n"
        . "• Địa chỉ IP: " . $ip_address . "\n\n"
        . "Xem chi tiết trong WordPress Admin:\n" . $admin_url . "\n";

    if (function_exists('wp_mail')) {
        wp_mail($admin_email, $subject, $body);
    }

    // 8. Fire extensible action hook: batdongsan_lead_created
    $lead_data = [
        'fullname'     => $fullname,
        'phone'        => $phone,
        'email'        => $email,
        'source'       => $source,
        'status'       => 'moi',
        'message'      => $message,
        'post_id'      => $post_id_rel,
        'ip_address'   => $ip_address,
        'submitted_at' => $submitted_at,
    ];
    do_action('batdongsan_lead_created', $lead_id, $lead_data);

    // 9. Send success JSON response
    wp_send_json_success([
        'lead_id' => $lead_id,
        'message' => 'Cảm ơn bạn! Thông tin liên hệ đã được gửi thành công.',
    ]);
    exit;
}
add_action('wp_ajax_batdongsan_submit_lead', 'batdongsan_ajax_submit_lead');
add_action('wp_ajax_nopriv_batdongsan_submit_lead', 'batdongsan_ajax_submit_lead');
