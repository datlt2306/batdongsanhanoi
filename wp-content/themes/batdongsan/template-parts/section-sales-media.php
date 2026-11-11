<?php
/**
 * Section Sales Persona & Media Video Hub (TikTok & YouTube)
 * 
 * Thiết kế tinh giản (Apple Cupertino Luxury), giảm thiểu tối đa visual clutter,
 * làm nổi bật chuyên viên tư vấn và các video review thực tế dạng dọc (Reels/TikTok).
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$advisor = function_exists('batdongsan_get_advisor_data') ? batdongsan_get_advisor_data() : [];
$videos  = function_exists('batdongsan_get_media_videos') ? batdongsan_get_media_videos() : [];

$advisor_name        = $advisor['advisor_name'] ?? 'Quỳnh Nguyễn';
$advisor_title       = $advisor['advisor_title'] ?? 'Chuyên Viên Tư Vấn & Định Giá';
$advisor_avatar      = $advisor['advisor_avatar'] ?? 0;
$advisor_phone       = $advisor['advisor_phone'] ?? '0934.69.9191';
$advisor_zalo        = $advisor['advisor_zalo'] ?? '0934699191';
$advisor_tiktok_url  = $advisor['advisor_tiktok_url'] ?? 'https://tiktok.com/@batdongsanhanoi';
$advisor_youtube_url = $advisor['advisor_youtube_url'] ?? 'https://youtube.com/@batdongsanhanoi';

$sanitized_phone = preg_replace('/\D/', '', $advisor_phone);
$zalo_url = $advisor_zalo;
if (strpos($zalo_url, 'http') === false) {
    $clean_zalo = preg_replace('/\D/', '', $zalo_url);
    $zalo_url = !empty($clean_zalo) ? 'https://zalo.me/' . $clean_zalo : 'https://zalo.me/' . $sanitized_phone;
}
?>

<section class="py-8 sm:py-10 bg-canvas border-b border-border/60" id="chuyen-vien-truyen-thong">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- SECTION HEADER: Tiêu đề kết hợp Persona Cố Vấn Tinh Giản (Không rườm rà) -->
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 pb-5 border-b border-border/60">
            <!-- Left: Title & Subtitle -->
            <div class="max-w-2xl">
                <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-1 block">
                    Góc Nhìn Thực Tế & Chuyên Gia
                </span>
                <h2 class="font-serif text-2xl md:text-3xl font-bold tracking-tight text-charcoal m-0">
                    Video Review & Trải Nghiệm Nhà Đất
                </h2>
                <p class="font-sans text-xs sm:text-sm text-charcoal-muted mt-1.5 m-0 leading-relaxed">
                    Đánh giá thực địa, thẩm định pháp lý và cập nhật tiến độ các dự án nổi bật tại Hà Nội.
                </p>
            </div>

            <!-- Right: Compact Cupertino Advisor Identity Pill -->
            <div class="flex items-center gap-3 bg-surface border border-border/80 rounded-2xl p-2 sm:p-2.5 shadow-subtle shrink-0 self-start lg:self-auto">
                <div class="relative shrink-0 w-11 h-11 rounded-full overflow-hidden ring-2 ring-primary/20 bg-stone">
                    <?php if ($advisor_avatar) : ?>
                        <?php echo wp_get_attachment_image($advisor_avatar, 'thumbnail', false, [
                            'class' => 'w-full h-full object-cover',
                            'alt'   => esc_attr($advisor_name),
                        ]); ?>
                    <?php else : ?>
                        <img 
                            src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=150&q=80" 
                            alt="<?php echo esc_attr($advisor_name); ?>" 
                            class="w-full h-full object-cover"
                        >
                    <?php endif; ?>
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 rounded-full ring-2 ring-surface" title="Đang trực tuyến"></span>
                </div>

                <div class="text-left pr-1 min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span class="font-bold text-charcoal truncate"><?php echo esc_html($advisor_name); ?></span>
                        <span class="inline-flex text-primary" title="Đã xác thực">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </span>
                    </div>
                    <span class="text-xs text-charcoal-muted block truncate"><?php echo esc_html($advisor_title); ?></span>
                </div>

                <div class="flex items-center gap-1.5 shrink-0 pl-1 border-l border-border/60">
                    <a 
                        href="tel:<?php echo esc_attr($sanitized_phone); ?>" 
                        class="inline-flex items-center justify-center gap-1.5 h-8 px-2.5 sm:px-3 bg-charcoal hover:bg-black text-white rounded-xl font-medium transition-all active:scale-95 no-underline whitespace-nowrap"
                        title="Gọi trực tiếp chuyên viên"
                    >
                        <?php echo batdongsan_icon('phone', ['width' => 12, 'height' => 12]); ?>
                        <span><?php echo esc_html($advisor_phone); ?></span>
                    </a>

                    <a 
                        href="<?php echo esc_url($zalo_url); ?>" 
                        target="_blank" 
                        rel="noopener noreferrer" 
                        class="inline-flex items-center justify-center h-8 px-2.5 sm:px-3 bg-[#0068ff] hover:bg-[#0057d6] text-white rounded-xl text-xs font-medium shadow-sm transition-all active:scale-95 no-underline whitespace-nowrap"
                        title="Chat Zalo tư vấn"
                    >
                        <span>Zalo</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- VIDEO REEL GRID: Dạng Dọc 9:14 Thanh Lịch, Tối Giản, Không Rối Mắt -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5 mt-6 sm:mt-7">
            <?php foreach ($videos as $v) : 
                $platform = $v['platform'] ?? 'tiktok';
            ?>
                <div 
                    class="group relative aspect-[9/14] sm:aspect-[9/15] rounded-2xl overflow-hidden border border-border/70 shadow-subtle hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 cursor-pointer bg-stone flex flex-col justify-between"
                    data-video-modal-trigger
                    data-video-url="<?php echo esc_url($v['video_url']); ?>"
                    data-video-embed="<?php echo esc_url($v['embed_url']); ?>"
                    data-video-title="<?php echo esc_attr($v['title']); ?>"
                    data-video-badge="<?php echo esc_attr($v['badge']); ?>"
                    data-video-platform="<?php echo esc_attr($platform); ?>"
                >
                    <!-- Background Thumbnail -->
                    <img 
                        src="<?php echo esc_url($v['thumbnail']); ?>" 
                        alt="<?php echo esc_attr($v['title']); ?>" 
                        loading="lazy" 
                        class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                    >

                    <!-- Smooth Gradient Scrim (chỉ phủ nhẹ ở chân thẻ để tôn tiêu đề) -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-transparent pointer-events-none"></div>

                    <!-- Top Bar: Platform pill + Duration -->
                    <div class="relative z-10 p-2.5 sm:p-3 flex items-center justify-between gap-1.5">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-black/40 text-white/95 backdrop-blur-md border border-white/10 shadow-xs">
                            <?php if ($platform === 'tiktok') : ?>
                                <?php echo batdongsan_icon('tiktok', ['width' => 10, 'height' => 10]); ?>
                                <span>TikTok</span>
                            <?php else : ?>
                                <?php echo batdongsan_icon('youtube', ['width' => 10, 'height' => 10]); ?>
                                <span>YouTube</span>
                            <?php endif; ?>
                        </span>

                        <span class="text-[10px] text-white/80 font-mono bg-black/30 px-1.5 py-0.5 rounded backdrop-blur-sm">
                            <?php echo esc_html($v['duration']); ?>
                        </span>
                    </div>

                    <!-- Center: Frosted Glass Play Button -->
                    <div class="relative z-10 flex items-center justify-center my-auto pointer-events-none">
                        <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-white/85 text-primary group-hover:bg-primary group-hover:text-white shadow-lg flex items-center justify-center transition-all duration-300 group-hover:scale-110 backdrop-blur-md ring-2 ring-white/30">
                            <svg class="w-5 h-5 translate-x-0.5 fill-current" viewBox="0 0 24 24">
                                <polygon points="5 3 19 12 5 21 5 3"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Bottom: Video Title & Views -->
                    <div class="relative z-10 p-3 sm:p-3.5 text-white">
                        <h3 class="text-xs sm:text-sm font-semibold text-white line-clamp-2 leading-snug m-0 mb-1.5 drop-shadow-sm group-hover:text-white">
                            <?php echo esc_html($v['title']); ?>
                        </h3>

                        <div class="flex items-center justify-between text-[10.5px] text-white/75 font-medium pt-1.5 border-t border-white/15">
                            <span class="flex items-center gap-1">
                                <?php echo batdongsan_icon('eye', ['width' => 11, 'height' => 11]); ?>
                                <span><?php echo esc_html($v['views']); ?></span>
                            </span>
                            <span class="text-white/60 text-[10px]">Chạm để xem</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Section Footer Link: Tinh gọn, không đóng hộp, nhẹ nhàng thanh thoát -->
        <div class="mt-4 pt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-charcoal-muted">
            <span>Video được cập nhật liên tục từ các buổi khảo sát và review thực địa tại Hà Nội.</span>
            <div class="flex items-center gap-4">
                <a 
                    href="<?php echo esc_url($advisor_tiktok_url); ?>" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="inline-flex items-center gap-1.5 text-charcoal hover:text-primary transition-colors font-medium no-underline"
                >
                    <?php echo batdongsan_icon('tiktok', ['width' => 12, 'height' => 12]); ?>
                    <span>Kênh TikTok</span>
                    <span aria-hidden="true">&rarr;</span>
                </a>
                <span class="text-border">|</span>
                <a 
                    href="<?php echo esc_url($advisor_youtube_url); ?>" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="inline-flex items-center gap-1.5 text-charcoal hover:text-primary transition-colors font-medium no-underline"
                >
                    <?php echo batdongsan_icon('youtube', ['width' => 12, 'height' => 12]); ?>
                    <span>Kênh YouTube</span>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>

    </div>
</section>
