<?php

/**
 * Map Facade Template Part - Luxury Real Estate Redesign (Milestone 5)
 *
 * Hiển thị Google Maps với lazy loading (click to load) chuẩn hiệu năng Core Web Vitals.
 * Giảm ~800KB+ render-blocking scripts khi tải trang ban đầu.
 *
 * @package BatDongSan
 *
 * @param array $args {
 *     @type string $embed_code   Mã iframe embed của Google Maps (required)
 *     @type string $address      Địa chỉ hiển thị trên placeholder (optional)
 *     @type string $class        Class CSS bổ sung (optional)
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

$embed_code   = $args['embed_code'] ?? '';
$address      = $args['address'] ?? '';
$custom_class = $args['class'] ?? '';

if (empty($embed_code)) {
    return;
}

// 1. Trích xuất và thẩm định src từ iframe embed code (SEC-02: Stored XSS defense matrix)
$iframe_src = '';
if (preg_match('/src=["\']([^"\']+)["\']/', $embed_code, $matches)) {
    $raw_src = trim($matches[1]);

    // Chặn triệt để các giao thức nguy hiểm (javascript, data, vbscript, ...)
    if (!preg_match('/^\s*(?:javascript|data|vbscript):/i', $raw_src)) {
        if (str_starts_with($raw_src, '//')) {
            $raw_src = 'https:' . $raw_src;
        }

        $parsed = parse_url($raw_src);
        $scheme = strtolower($parsed['scheme'] ?? '');
        $host   = strtolower($parsed['host'] ?? '');

        // Whitelist: Chỉ chấp nhận giao thức HTTPS và domain thuộc Google Maps
        $is_google_maps = ($scheme === 'https') && (
            $host === 'www.google.com' ||
            $host === 'maps.google.com' ||
            $host === 'google.com' ||
            preg_match('/^(?:[a-z0-9-]+\.)?google\.com$/i', $host)
        );

        if ($is_google_maps) {
            $iframe_src = esc_url($raw_src, ['https']);
        }
    }
}

// Nếu không có src Google Maps hợp lệ, ngắt ngay không render để chống XSS
if (empty($iframe_src)) {
    return;
}

// Placeholder: ảnh blur bản đồ vệ tinh nhẹ (~44KB)
$placeholder_image = get_template_directory_uri() . '/assets/images/map-placeholder.jpg';

// Unique ID cho mỗi map
$map_id = 'map-facade-' . wp_unique_id();
?>

<div 
    id="<?php echo esc_attr($map_id); ?>"
    class="map-facade group relative w-full aspect-video rounded-2xl overflow-hidden cursor-pointer bg-stone border border-border shadow-card max-sm:aspect-[4/3] max-sm:rounded-xl transition-all duration-300 hover:shadow-card-hover <?php echo esc_attr($custom_class); ?>" 
    data-map-src="<?php echo esc_url($iframe_src); ?>"
    role="button"
    tabindex="0"
    aria-label="Nhấp để tải tương tác bản đồ Google Maps"
>
    <!-- Facade Placeholder Layer -->
    <div class="map-facade__placeholder absolute inset-0 flex items-center justify-center">
        <!-- Background static map preview -->
        <img
            src="<?php echo esc_url($placeholder_image); ?>"
            alt=""
            role="presentation"
            class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
            fetchpriority="low"
            width="1200"
            height="675"
        />

        <!-- Soft architectural overlay gradient -->
        <div class="absolute inset-0 bg-charcoal/25 backdrop-blur-[1.5px] transition-all duration-300 group-hover:bg-charcoal/20"></div>
        
        <!-- Central Luxury Pin & Interactive Trigger -->
        <div class="relative z-10 flex flex-col items-center gap-3.5 p-6 text-center max-w-lg mx-auto">
            
            <!-- Champagne Bronze Animated Pin Button -->
            <div class="map-facade__play flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 bg-primary hover:bg-primary-hover text-white rounded-full shadow-button transition-all duration-300 group-hover:scale-110 active:scale-95 cursor-pointer ring-4 ring-white/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 sm:w-9 sm:h-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 21s-6-5.33-6-10a6 6 0 1 1 12 0c0 4.67-6 10-6 10z"/>
                    <circle cx="12" cy="11" r="2.5"/>
                </svg>
            </div>

            <!-- Action Pill -->
            <span class="inline-flex items-center gap-2 text-sm sm:text-base font-sans font-semibold text-charcoal bg-surface/95 px-6 py-2.5 rounded-full shadow-card backdrop-blur-md transition-all group-hover:bg-surface group-hover:shadow-button">
                <span>Nhấp để khám phá vị trí</span>
                <span class="text-primary text-xs">&bull; Click to explore</span>
            </span>

            <?php if ($address) : ?>
                <span class="text-xs sm:text-sm text-white/95 max-w-[340px] leading-snug drop-shadow-sm font-sans font-medium">
                    <?php echo esc_html($address); ?>
                </span>
            <?php endif; ?>

            <!-- Nearby Amenities Preview Pills -->
            <div class="hidden sm:flex items-center justify-center flex-wrap gap-2 pt-2">
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-sans font-medium bg-surface/85 text-charcoal backdrop-blur-sm shadow-subtle">
                    <span>🏫</span> Trường học
                </span>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-sans font-medium bg-surface/85 text-charcoal backdrop-blur-sm shadow-subtle">
                    <span>🏥</span> Bệnh viện
                </span>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-sans font-medium bg-surface/85 text-charcoal backdrop-blur-sm shadow-subtle">
                    <span>🛍️</span> TTTM & Công viên
                </span>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-sans font-medium bg-surface/85 text-charcoal backdrop-blur-sm shadow-subtle">
                    <span>🚇</span> Tuyến Metro & Vành đai
                </span>
            </div>

        </div>
    </div>
    
    <!-- Container cho Iframe (Được inject động khi người dùng nhấp hoặc Enter) -->
    <div class="map-facade__iframe-container absolute inset-0 z-20 bg-surface hidden [&>iframe]:block [&>iframe]:w-full [&>iframe]:h-full [&>iframe]:border-0"></div>
    
    <!-- Loading Spinner với Champagne Bronze Accent -->
    <div class="map-facade__loading absolute inset-0 z-10 hidden flex-col items-center justify-center gap-3 bg-surface/95 backdrop-blur-sm">
        <div class="w-12 h-12 border-3 border-border border-t-primary rounded-full animate-spin"></div>
        <span class="text-xs font-sans font-medium text-charcoal-muted tracking-wider uppercase">Đang tải bản đồ tương tác...</span>
    </div>
</div>
