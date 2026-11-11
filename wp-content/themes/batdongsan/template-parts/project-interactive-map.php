<?php
/**
 * Template part: Vị trí dự án & Bản đồ Google Maps chuẩn Core Web Vitals (Tối ưu Performance 100%)
 *
 * @package BatDongSan
 * @param array $args {
 *     @type int    $post_id
 *     @type string $map_embed
 *     @type string $address
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id   = $args['post_id'] ?? get_the_ID();
$map_embed = $args['map_embed'] ?? '';
$address   = $args['address'] ?? '';

$project_title = get_the_title($post_id);

// Trích xuất an toàn iframe src từ map_embed
$iframe_src = '';
if (!empty($map_embed) && preg_match('/src=["\']([^"\']+)["\']/', $map_embed, $matches)) {
    $raw_src = trim($matches[1]);
    if (str_starts_with($raw_src, '//')) {
        $raw_src = 'https:' . $raw_src;
    }
    $iframe_src = esc_url($raw_src, ['https']);
}

$directions_url = "https://www.google.com/maps/dir/?api=1&destination=" . urlencode($project_title . ' ' . $address);
$placeholder_img = get_template_directory_uri() . '/assets/images/map-placeholder.jpg';
$map_facade_id = 'bds-map-facade-' . $post_id;
?>

<div id="project-location-section" class="bg-surface rounded-xl sm:rounded-2xl p-5 md:p-6 mb-4 md:mb-5 border border-border/70 shadow-subtle max-md:rounded-none max-md:-mx-4 max-md:px-4 max-md:mb-3 max-md:border-x-0">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-border/80">
        <div>
            <h2 class="text-lg md:text-xl font-bold text-charcoal tracking-tight m-0 flex items-center gap-2">
                <svg class="w-5 h-5 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 21s-6-5.33-6-10a6 6 0 1 1 12 0c0 4.67-6 10-6 10z"/>
                    <circle cx="12" cy="11" r="2.5"/>
                </svg>
                <span>Vị trí dự án <?php echo esc_html($project_title); ?></span>
            </h2>
            <?php if ($address): ?>
                <p class="text-xs sm:text-sm text-charcoal-muted m-0 mt-1 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                    <span><?php echo esc_html($address); ?></span>
                </p>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
            <a 
                href="<?php echo esc_url($directions_url); ?>" 
                target="_blank" 
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-primary/20 bg-primary/5 hover:bg-primary hover:text-white text-primary text-xs font-semibold transition-all shadow-xs"
                title="Mở chỉ đường trên Google Maps"
            >
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                </svg>
                <span>Chỉ đường Google Maps</span>
            </a>
        </div>
    </div>

    <!-- Map Facade (Tối ưu Core Web Vitals - 0 request Google JS khi tải trang) -->
    <?php if (!empty($iframe_src)): ?>
        <div 
            id="<?php echo esc_attr($map_facade_id); ?>" 
            class="map-facade group relative aspect-[16/9] sm:aspect-[21/9] min-h-[300px] sm:min-h-[380px] w-full rounded-xl overflow-hidden border border-border/80 bg-stone cursor-pointer"
            data-map-src="<?php echo esc_url($iframe_src); ?>"
            role="button"
            aria-label="Nhấp để tải tương tác bản đồ Google Maps"
            tabindex="0"
        >
            <!-- Facade Placeholder Layer: Chỉ hiển thị trước khi click -->
            <div class="map-facade__placeholder absolute inset-0 flex items-center justify-center overflow-hidden">
                <img
                    src="<?php echo esc_url($placeholder_img); ?>"
                    alt=""
                    role="presentation"
                    class="absolute inset-0 w-full h-full object-cover opacity-60 scale-105 pointer-events-none transition-transform duration-700 group-hover:scale-110"
                    fetchpriority="low"
                    width="1200"
                    height="675"
                />

                <div class="absolute inset-0 bg-gradient-to-t from-charcoal/50 via-charcoal/20 to-charcoal/10 transition-opacity duration-300 group-hover:opacity-75"></div>

                <!-- Trung tâm: Pin nổi bật & Nút bấm tương tác -->
                <div class="relative z-10 flex flex-col items-center gap-3 p-4 text-center max-w-md mx-auto">
                    <!-- Icon Pin Dự án -->
                    <div class="flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 bg-primary hover:bg-primary-hover text-white rounded-full shadow-button transition-all duration-300 group-hover:scale-110 ring-4 ring-white/30">
                        <svg class="w-7 h-7 sm:w-8 sm:h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 21s-6-5.33-6-10a6 6 0 1 1 12 0c0 4.67-6 10-6 10z"/>
                            <circle cx="12" cy="11" r="2.5"/>
                        </svg>
                    </div>

                    <!-- Nút kích hoạt bản đồ -->
                    <span class="inline-flex items-center gap-2 text-xs sm:text-sm font-semibold text-charcoal bg-white/95 px-5 py-2.5 rounded-full shadow-card backdrop-blur-md transition-all group-hover:bg-white group-hover:shadow-button">
                        <span>Chạm để khám phá bản đồ</span>
                        <span class="text-primary text-[11px]">&bull; Click to load map</span>
                    </span>

                    <?php if ($address): ?>
                        <span class="text-xs text-white/95 font-medium drop-shadow-sm max-w-[320px] line-clamp-2">
                            <?php echo esc_html($address); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Container cho Iframe (Chỉ nạp khi người dùng nhấp) -->
            <div class="map-facade__iframe-container absolute inset-0 z-20 bg-surface hidden [&>iframe]:block [&>iframe]:w-full [&>iframe]:h-full [&>iframe]:border-0"></div>

            <!-- Loading Spinner khi đang kéo iframe Google về -->
            <div class="map-facade__loading absolute inset-0 z-10 hidden flex-col items-center justify-center gap-2.5 bg-surface/95 backdrop-blur-sm">
                <div class="w-10 h-10 border-3 border-border border-t-primary rounded-full animate-spin"></div>
                <span class="text-xs font-medium text-charcoal-muted tracking-wider uppercase">Đang kết nối Google Maps...</span>
            </div>
        </div>
    <?php endif; ?>
</div>
