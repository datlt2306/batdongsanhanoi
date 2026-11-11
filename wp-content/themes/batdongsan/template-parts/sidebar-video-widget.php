<?php
/**
 * Sidebar Video Review Widget
 * Swiper 1 item per slide dành riêng cho Sidebar Dự Án
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$videos = !empty($args['videos']) && is_array($args['videos']) ? $args['videos'] : [];
if (empty($videos)) {
    return;
}

$advisor_name       = $args['advisor_name'] ?? 'Chuyên viên';
$advisor_tiktok_url = $args['advisor_tiktok_url'] ?? '';
$total_videos       = count($videos);
?>

<div class="sidebar-video-widget bg-white rounded-xl sm:rounded-2xl border border-black/[0.06] p-4 sm:p-5 shadow-subtle">
    <!-- Header -->
    <div class="flex items-center justify-between pb-3 mb-3.5 border-b border-black/[0.06]">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                    <polygon points="5 3 19 12 5 21 5 3"/>
                </svg>
            </span>
            <div>
                <h3 class="text-xs sm:text-sm font-bold text-charcoal m-0 leading-tight">Video Thực Địa Dự Án</h3>
                <span class="text-[11px] text-charcoal-muted leading-tight block mt-0.5">Khảo sát &amp; review thực tế</span>
            </div>
        </div>
        <?php if ($total_videos > 1) : ?>
            <div class="flex items-center gap-1.5">
                <button type="button" class="sidebar-video-swiper__prev w-7 h-7 rounded-full border border-black/10 bg-white hover:bg-stone text-charcoal flex items-center justify-center cursor-pointer transition-all duration-200 hover:text-primary [&.swiper-button-disabled]:opacity-30 [&.swiper-button-disabled]:pointer-events-none" aria-label="Video trước">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </button>
                <button type="button" class="sidebar-video-swiper__next w-7 h-7 rounded-full border border-black/10 bg-white hover:bg-stone text-charcoal flex items-center justify-center cursor-pointer transition-all duration-200 hover:text-primary [&.swiper-button-disabled]:opacity-30 [&.swiper-button-disabled]:pointer-events-none" aria-label="Video tiếp">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Swiper 1 item per slide -->
    <div class="sidebar-video-swiper swiper w-full rounded-xl overflow-hidden relative">
        <div class="swiper-wrapper">
            <?php foreach ($videos as $idx => $v) : 
                $platform = $v['platform'] ?? 'tiktok';
            ?>
                <div class="swiper-slide w-full">
                    <div 
                        class="group relative rounded-xl overflow-hidden aspect-[4/5] sm:aspect-[9/13] bg-stone cursor-pointer select-none shadow-xs hover:shadow-card transition-all duration-300 flex flex-col justify-between"
                        data-video-modal-trigger
                        data-video-url="<?php echo esc_url($v['url']); ?>"
                        data-video-embed="<?php echo esc_url($v['embed_url']); ?>"
                        data-video-title="<?php echo esc_attr($v['title']); ?>"
                        data-video-platform="<?php echo esc_attr($platform); ?>"
                    >
                        <!-- Thumbnail -->
                        <img 
                            src="<?php echo esc_url($v['thumbnail']); ?>" 
                            alt="<?php echo esc_attr($v['title']); ?>" 
                            loading="lazy" 
                            class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                        >

                        <!-- Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-black/30 pointer-events-none"></div>

                        <!-- Top bar: Platform & Duration -->
                        <div class="relative z-10 p-3 flex items-center justify-between gap-1.5">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-black/50 text-white backdrop-blur-md border border-white/10">
                                <?php if ($platform === 'tiktok') : ?>
                                    <?php echo batdongsan_icon('tiktok', ['width' => 10, 'height' => 10]); ?>
                                    <span>TikTok</span>
                                <?php else : ?>
                                    <?php echo batdongsan_icon('youtube', ['width' => 10, 'height' => 10]); ?>
                                    <span>YouTube</span>
                                <?php endif; ?>
                            </span>

                            <span class="text-[10px] text-white/90 font-mono bg-black/50 px-1.5 py-0.5 rounded backdrop-blur-md">
                                <?php echo esc_html($v['duration']); ?>
                            </span>
                        </div>

                        <!-- Center Play Button -->
                        <div class="relative z-10 flex items-center justify-center pointer-events-none my-auto">
                            <div class="w-12 h-12 rounded-full bg-white/95 text-primary group-hover:bg-primary group-hover:text-white shadow-xl flex items-center justify-center transition-all duration-300 group-hover:scale-110 ring-4 ring-white/20">
                                <svg class="w-5 h-5 translate-x-0.5 fill-current" viewBox="0 0 24 24">
                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Bottom Content -->
                        <div class="relative z-10 p-3.5 text-white">
                            <?php if (!empty($v['badge'])) : ?>
                                <span class="inline-block text-[10px] font-bold uppercase tracking-wider text-primary-light mb-1 drop-shadow-sm">
                                    <?php echo esc_html($v['badge']); ?>
                                </span>
                            <?php endif; ?>
                            <h4 class="text-xs sm:text-sm font-semibold text-white line-clamp-2 leading-snug m-0 mb-2 drop-shadow-sm">
                                <?php echo esc_html($v['title']); ?>
                            </h4>

                            <div class="flex items-center justify-between text-[11px] text-white/80 font-medium pt-1.5 border-t border-white/15">
                                <span class="flex items-center gap-1">
                                    <?php echo batdongsan_icon('eye', ['width' => 11, 'height' => 11]); ?>
                                    <span><?php echo esc_html($v['views']); ?></span>
                                </span>
                                <span class="text-white/80 text-[10.5px] font-semibold group-hover:text-primary-light transition-colors flex items-center gap-0.5">
                                    <span>Xem video</span>
                                    <span>&rarr;</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_videos > 1) : ?>
            <div class="sidebar-video-swiper__pagination mt-3 flex items-center justify-center gap-1.5 [&_.swiper-pagination-bullet]:w-1.5 [&_.swiper-pagination-bullet]:h-1.5 [&_.swiper-pagination-bullet]:bg-charcoal/20 [&_.swiper-pagination-bullet-active]:w-5 [&_.swiper-pagination-bullet-active]:bg-primary [&_.swiper-pagination-bullet-active]:rounded-full transition-all"></div>
        <?php endif; ?>
    </div>
</div>
