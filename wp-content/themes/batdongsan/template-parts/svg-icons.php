<?php

/**
 * SVG Icons Library - Apple SF Symbols Standard
 *
 * Tập hợp các SVG icons thiết kế chuẩn Apple Human Interface Guidelines (SF Symbols).
 * Thiết kế tối giản, bo góc chuẩn mực, nét stroke đồng nhất (1.8px - 2.2px) và đường cong tinh tế.
 *
 * @package BatDongSan
 *
 * Cách sử dụng:
 * echo batdongsan_icon('location');
 * echo batdongsan_icon('arrow-left', ['width' => 24, 'height' => 24]);
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lấy SVG icon theo tên (chuẩn Apple SF Symbols)
 *
 * @param string $name Tên icon
 * @param array $attrs Các attributes tùy chỉnh
 * @return string HTML của SVG icon
 */
function batdongsan_icon($name, $attrs = []) {
    $default_attrs = [
        'width' => 16,
        'height' => 16,
        'class' => '',
        'aria-hidden' => 'true',
    ];

    $attrs = array_merge($default_attrs, $attrs);

    $icons = [
        // Apple SF Symbol: mappin.and.ellipse (Vị trí bản đồ)
        'location' => '<path d="M12 2.5a6.5 6.5 0 0 0-6.5 6.5c0 4.8 6.5 12.5 6.5 12.5s6.5-7.7 6.5-12.5a6.5 6.5 0 0 0-6.5-6.5z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="9" r="2.2" stroke="currentColor" stroke-width="1.8"/>',

        // Apple SF Symbol: magnifyingglass (Tìm kiếm)
        'search' => '<circle cx="10.5" cy="10.5" r="6.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><line x1="15.5" y1="15.5" x2="20.5" y2="20.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>',

        // Apple SF Symbol: chevron.left (Mũi tên trái)
        'arrow-left' => '<path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',
        'chevron-left' => '<path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',

        // Apple SF Symbol: chevron.right (Mũi tên phải)
        'arrow-right' => '<path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',
        'chevron-right' => '<path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',

        // Apple SF Symbol: chevron.down (Mũi tên xuống / dropdown)
        'chevron-down' => '<path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',

        // Apple SF Symbol: chevron.up (Mũi tên lên)
        'chevron-up' => '<path d="M18 15l-6-6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',

        // Apple SF Symbol: building.2 (Dự án / Tòa nhà cao tầng)
        'building' => '<path d="M3 21.5h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 21.5V6a1.5 1.5 0 0 1 1.5-1.5h6A1.5 1.5 0 0 1 14 6v15.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 10.5h4.5A1.5 1.5 0 0 1 20 12v9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 8h3M8 12h3M8 16h3M17 14h.01M17 17.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'building-2' => '<path d="M3 21.5h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 21.5V6a1.5 1.5 0 0 1 1.5-1.5h6A1.5 1.5 0 0 1 14 6v15.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 10.5h4.5A1.5 1.5 0 0 1 20 12v9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 8h3M8 12h3M8 16h3M17 14h.01M17 17.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',

        // Apple SF Symbol: house (Nhà ở / Biệt thự / Trang chủ)
        'home' => '<path d="M3 10.5L12 3l9 7.5V20a1.5 1.5 0 0 1-1.5 1.5H4.5A1.5 1.5 0 0 1 3 20v-9.5z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M18 7.5V4h-2.5v1.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9.5 21.5v-6a2.5 2.5 0 0 1 5 0v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'house' => '<path d="M3 10.5L12 3l9 7.5V20a1.5 1.5 0 0 1-1.5 1.5H4.5A1.5 1.5 0 0 1 3 20v-9.5z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M18 7.5V4h-2.5v1.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9.5 21.5v-6a2.5 2.5 0 0 1 5 0v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',

        // Apple SF Symbol: bed.double (Phòng ngủ)
        'bed' => '<path d="M3 19.5v-10a1.5 1.5 0 0 1 1.5-1.5h15A1.5 1.5 0 0 1 21 9.5v10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M3 15.5h18" stroke="currentColor" stroke-width="1.8"/><path d="M3 21.5v-2M21 21.5v-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><rect x="5.5" y="10" width="5" height="3" rx="1" stroke="currentColor" stroke-width="1.6"/><rect x="13.5" y="10" width="5" height="3" rx="1" stroke="currentColor" stroke-width="1.6"/>',

        // Apple SF Symbol: shower / bathtub (Phòng tắm / WC)
        'bath' => '<path d="M4 21.5h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 12h14a1 1 0 0 1 1 1v2a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-2a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 12V5a2.5 2.5 0 0 1 5 0v1.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7 20l-1 2M17 20l1 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',

        // Apple SF Symbol: square.split.diagonal.2x2 / ruler (Diện tích)
        'area' => '<rect x="3.5" y="3.5" width="17" height="17" rx="3.5" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 14h17M10 14v7.5M14 3.5v10.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'square' => '<rect x="3.5" y="3.5" width="17" height="17" rx="3.5" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 14h17M10 14v7.5M14 3.5v10.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',

        // Apple SF Symbol: banknote (Giá niêm yết / Tài chính)
        'price' => '<rect x="3" y="6" width="18" height="12" rx="2.5" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.6"/><path d="M6 9h.01M18 15h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

        // Apple SF Symbol: square.grid.2x2 (Tất cả / Lưới)
        'grid' => '<rect x="3.5" y="3.5" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.8"/><rect x="13.5" y="3.5" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.8"/><rect x="3.5" y="13.5" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.8"/><rect x="13.5" y="13.5" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.8"/>',

        // Apple SF Symbol: list.bullet (Danh sách)
        'list' => '<circle cx="5" cy="7" r="1.5" fill="currentColor"/><line x1="9" y1="7" x2="20" y2="7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="5" cy="12" r="1.5" fill="currentColor"/><line x1="9" y1="12" x2="20" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="5" cy="17" r="1.5" fill="currentColor"/><line x1="9" y1="17" x2="20" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',

        // Apple SF Symbol: phone (Hotline / Điện thoại)
        'phone' => '<path d="M20.5 16.5c-1.2-1-2.4-1.4-3.5-.3l-1.3 1.3c-.3.3-.8.4-1.2.2-2.3-1.1-4.2-3-5.3-5.3-.2-.4-.1-.9.2-1.2l1.3-1.3c1.1-1.1.7-2.3-.3-3.5L9.1 4.8C8 3.7 6.8 3.7 5.7 4.8L4.3 6.2c-1.2 1.2-1.5 2.8-.7 4.6 1.8 4.2 5 7.4 9.2 9.2 1.8.8 3.4.5 4.6-.7l1.4-1.4c1.1-1.1 1.1-2.3 0-3.4l-1.3-1.4z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',

        // Apple SF Symbol: envelope (Email / Hòm thư)
        'email' => '<rect x="3" y="5.5" width="18" height="13" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'mail' => '<rect x="3" y="5.5" width="18" height="13" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',

        // Apple SF Symbol: square.and.arrow.up (Chia sẻ)
        'share' => '<path d="M4 12v6.5A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5V12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 3v11M8 7l4-4 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',

        // Apple SF Symbol: calendar (Lịch đăng tin)
        'calendar' => '<rect x="3" y="4.5" width="18" height="16.5" rx="3.5" stroke="currentColor" stroke-width="1.8"/><line x1="16" y1="2" x2="16" y2="5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><line x1="8" y1="2" x2="8" y2="5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><line x1="3" y1="9.5" x2="21" y2="9.5" stroke="currentColor" stroke-width="1.6"/><circle cx="8" cy="14" r="1" fill="currentColor"/><circle cx="12" cy="14" r="1" fill="currentColor"/><circle cx="16" cy="14" r="1" fill="currentColor"/>',

        // Apple SF Symbol: clock (Thời gian / Giờ)
        'clock' => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3.5 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',

        // Apple SF Symbol: checkmark (Xác nhận / Đạt)
        'check' => '<path d="M5 12.5l4.5 4.5 9.5-10" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',

        // Apple SF Symbol: xmark (Đóng / Hủy)
        'close' => '<line x1="6.5" y1="6.5" x2="17.5" y2="17.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><line x1="17.5" y1="6.5" x2="6.5" y2="17.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>',
        'xmark' => '<line x1="6.5" y1="6.5" x2="17.5" y2="17.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><line x1="17.5" y1="6.5" x2="6.5" y2="17.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>',

        // Apple SF Symbol: doc.on.doc (Sao chép)
        'copy' => '<rect x="8.5" y="8.5" width="11" height="12.5" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M5 15.5H4.5A1.5 1.5 0 0 1 3 14V4.5A1.5 1.5 0 0 1 4.5 3h9.5A1.5 1.5 0 0 1 15.5 4.5V5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',

        // Apple SF Symbol: star.fill (Sản phẩm nổi bật)
        'star' => '<path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5-5.9-3.1-5.9 3.1 1.2-6.5-4.8-4.6 6.6-.9 2.9-6z" fill="currentColor"/>',

        // Apple SF Symbol: eye (Lượt xem)
        'eye' => '<path d="M2.5 12C4.5 7.5 8 5 12 5s7.5 2.5 9.5 7c-2 4.5-5.5 7-9.5 7s-7.5-2.5-9.5-7z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/>',

        // Apple SF Symbol: checkmark.shield (Pháp lý minh bạch / Xác thực)
        'shield' => '<path d="M12 2.5l7 3.5v5.8c0 4.8-3.1 9.2-7 10.7-3.9-1.5-7-5.9-7-10.7V6L12 2.5z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',

        // Apple SF Symbol: slider.horizontal.3 (Bộ lọc nâng cao)
        'filter' => '<line x1="4" y1="7" x2="20" y2="7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9" cy="7" r="2.5" fill="white" stroke="currentColor" stroke-width="1.8"/><line x1="4" y1="17" x2="20" y2="17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="15" cy="17" r="2.5" fill="white" stroke="currentColor" stroke-width="1.8"/>',

        // Apple SF Symbol: compass (Hướng nhà / Phong thủy)
        'compass' => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><polygon points="14.5 9.5 12.5 14.5 9.5 14.5 11.5 9.5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" fill="currentColor"/>',

        // Apple SF Symbol: play.circle (Video / Tour)
        'play' => '<circle cx="12" cy="12" r="9.5" stroke="currentColor" stroke-width="1.8"/><polygon points="10 8.5 16 12 10 15.5" fill="currentColor"/>',
        'video' => '<rect x="3" y="6" width="13" height="12" rx="2.5" stroke="currentColor" stroke-width="1.8"/><path d="M16 10l5-3.5v11l-5-3.5V10z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',

        // Apple SF Symbol: photo.stack (Ảnh / Bộ sưu tập)
        'gallery' => '<rect x="3" y="3" width="14" height="14" rx="2.5" stroke="currentColor" stroke-width="1.8"/><circle cx="7.5" cy="7.5" r="1.5" fill="currentColor"/><path d="M3 13.5l3.5-3.5a1.5 1.5 0 0 1 2 0l4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 19h12a2 2 0 0 0 2-2V7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="3.5" stroke="currentColor" stroke-width="1.8"/><circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.6"/><path d="M3 16l5-5a2 2 0 0 1 2.8 0l7.2 7.2M14 14l2-2a2 2 0 0 1 2.8 0L21 14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',

        // Apple SF Symbol: message (Tư vấn / Tin nhắn / Zalo)
        'message' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 8.5-8.5 8.5 8.5 0 0 1 8.5 8.5z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',

        // Apple SF Symbol: building.columns (Ngân hàng / Vay vốn)
        'bank' => '<path d="M3 21h18M4 7l8-4 8 4M4 7v2h16V7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 10.5v8M10 10.5v8M14 10.5v8M18 10.5v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',

        // Apple SF Symbol: arrow.clockwise (Đặt lại / Làm mới)
        'refresh' => '<path d="M20 12A8 8 0 1 1 12 4c2.5 0 4.7 1.1 6.2 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><polyline points="18 3 18.5 7.5 14 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',

        // Apple SF Symbol: person.crop.circle (Cố vấn / Tài khoản)
        'person' => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="9.5" r="3" stroke="currentColor" stroke-width="1.8"/><path d="M6.5 18a6.5 6.5 0 0 1 11 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',

        // Apple SF Symbol: lock (Bảo mật thông tin)
        'lock' => '<rect x="5" y="11" width="14" height="10" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M8 11V7a4 4 0 0 1 8 0v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',

        // Social Icons
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'zalo' => '<path d="M12 2C6.48 2 2 6.03 2 11c0 2.87 1.5 5.43 3.84 7.04-.15.93-.65 2.8-1.84 4.01 0 0 2.45-.25 4.68-1.74.43.08.87.13 1.32.13 5.52 0 10-4.03 10-9s-4.48-9-10-9zm-1.8 12.5H7.2a.5.5 0 0 1-.41-.78l2.25-3.22H7.2a.5.5 0 1 1 0-1h3a.5.5 0 0 1 .41.78l-2.25 3.22h1.84a.5.5 0 0 1 0 1zm4.6 0h-1.4a.5.5 0 0 1-.5-.5V8.5a.5.5 0 0 1 .5-.5h1.4c1.27 0 2.3 1.03 2.3 2.3v1.9c0 1.27-1.03 2.3-2.3 2.3zm0-3.7h-.5v2.7h.5c.72 0 1.3-.58 1.3-1.3v-.1c0-.72-.58-1.3-1.3-1.3z" fill="currentColor"/>',
        'tiktok' => '<path d="M19.589 6.686a4.793 4.793 0 0 1-3.77-4.245V2h-3.445v13.672a2.896 2.896 0 0 1-2.882 2.882 2.896 2.896 0 0 1-2.882-2.882 2.896 2.896 0 0 1 2.882-2.882c.31 0 .607.05.884.14V9.45a6.326 6.326 0 0 0-.884-.063 6.342 6.342 0 0 0-6.328 6.342A6.342 6.342 0 0 0 9.49 22.07a6.342 6.342 0 0 0 6.328-6.342v-6.9a8.212 8.212 0 0 0 3.77 1.042V6.686z" fill="currentColor"/>',
        'youtube' => '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19.1c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.43z" fill="currentColor"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="white"/>',
        'badge-check' => '<path d="M12 2l2.4 2.4 3.4-.4 1.4 3.1 3 1.7-.8 3.3.8 3.3-3 1.7-1.4 3.1-3.4-.4L12 22l-2.4-2.4-3.4.4-1.4-3.1-3-1.7.8-3.3-.8-3.3 3-1.7 1.4-3.1 3.4.4L12 2z" fill="currentColor"/><path d="M8.5 12l2.5 2.5 4.5-4.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    if (!isset($icons[$name])) {
        return '';
    }

    $attr_string = '';
    foreach ($attrs as $key => $value) {
        if ($key !== 'class' || !empty($value)) {
            $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
    }

    return sprintf(
        '<svg viewBox="0 0 24 24" fill="none"%s>%s</svg>',
        $attr_string,
        $icons[$name]
    );
}

/**
 * Echo SVG icon
 */
function batdongsan_the_icon($name, $attrs = []) {
    echo batdongsan_icon($name, $attrs);
}

/**
 * Lấy emoji icon từ thư mục assets/icons/emoji
 */
function batdongsan_emoji($name, $attrs = []) {
    $default_attrs = [
        'width' => 24,
        'height' => 24,
        'class' => 'emoji-icon',
        'alt' => $name,
        'loading' => 'lazy',
    ];

    $attrs = wp_parse_args($attrs, $default_attrs);

    $emoji_path = get_template_directory() . '/assets/icons/emoji/' . $name . '.svg';
    $emoji_url = get_template_directory_uri() . '/assets/icons/emoji/' . $name . '.svg';

    if (!file_exists($emoji_path)) {
        return '';
    }

    $attr_string = '';
    foreach ($attrs as $key => $value) {
        $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
    }

    return sprintf('<img src="%s"%s />', esc_url($emoji_url), $attr_string);
}

/**
 * Echo emoji icon
 */
function batdongsan_the_emoji($name, $attrs = []) {
    echo batdongsan_emoji($name, $attrs);
}

/**
 * Lấy inline SVG emoji
 */
function batdongsan_emoji_inline($name, $attrs = []) {
    $default_attrs = [
        'width' => '24',
        'height' => '24',
        'class' => 'emoji-icon emoji-icon--inline',
    ];

    $attrs = wp_parse_args($attrs, $default_attrs);

    $emoji_path = get_template_directory() . '/assets/icons/emoji/' . $name . '.svg';

    if (!file_exists($emoji_path)) {
        return '';
    }

    $svg_content = file_get_contents($emoji_path);

    $attr_string = '';
    foreach ($attrs as $key => $value) {
        $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
    }

    $svg_content = preg_replace('/<svg/', '<svg' . $attr_string, $svg_content, 1);

    return $svg_content;
}
