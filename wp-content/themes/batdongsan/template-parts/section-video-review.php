<?php
/**
 * Component Section Video Review & Trải Nghiệm Thực Tế (TikTok & Reels)
 * 
 * Hiển thị video review thực tế của chuyên viên tư vấn (Quỳnh Nguyễn / Vợ)
 * cho Dự Án (du-an) và từng Bất Động Sản riêng lẻ (bat-dong-san).
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$videos       = !empty($args['videos']) && is_array($args['videos']) ? $args['videos'] : [];
$context      = $args['context'] ?? 'du_an'; // 'du_an' | 'bat_dong_san'
$section_id   = $args['section_id'] ?? ($context === 'du_an' ? 'project-video-section' : 'bds-video-review-section');
$custom_title = $args['title'] ?? '';
$custom_sub   = $args['subtitle'] ?? '';
$post_id      = !empty($args['post_id']) ? (int) $args['post_id'] : get_the_ID();

if (empty($videos)) {
    return;
}

// Lấy thông tin Advisor
$advisor = !empty($args['advisor']) && is_array($args['advisor']) ? $args['advisor'] : (function_exists('batdongsan_get_advisor_data') ? batdongsan_get_advisor_data() : []);

$advisor_name          = $advisor['advisor_name'] ?? 'Quỳnh Nguyễn';
$advisor_title         = $advisor['advisor_title'] ?? 'Chuyên Viên Tư Vấn & Định Giá';
$advisor_avatar        = $advisor['advisor_avatar'] ?? 0;
$advisor_phone         = $advisor['advisor_phone'] ?? '0934.69.9191';
$advisor_zalo          = $advisor['advisor_zalo'] ?? '0934699191';
$advisor_tiktok_url    = $advisor['advisor_tiktok_url'] ?? 'https://tiktok.com/@batdongsanhanoi';
$advisor_tiktok_handle = $advisor['advisor_tiktok_handle'] ?? '@batdongsanhanoi';

$sanitized_phone = preg_replace('/\D/', '', $advisor_phone);
$zalo_url = $advisor_zalo;
if (strpos($zalo_url, 'http') === false) {
    $clean_zalo = preg_replace('/\D/', '', $zalo_url);
    $zalo_url = !empty($clean_zalo) ? 'https://zalo.me/' . $clean_zalo : 'https://zalo.me/' . $sanitized_phone;
}

// Thiết lập tiêu đề và mô tả theo ngữ cảnh
$is_first_fallback = !empty($videos[0]['is_fallback']);
if (empty($custom_title)) {
    if ($context === 'du_an') {
        $custom_title = $is_first_fallback ? 'Video Review & Khảo Sát Thực Địa BĐS' : 'Video Giới Thiệu & Thực Địa Dự Án';
    } else {
        $custom_title = $is_first_fallback ? 'Video Review Thực Tế Từ Chuyên Viên' : 'Video Review Chi Tiết Căn Hộ Thực Tế';
    }
}

if (empty($custom_sub)) {
    $custom_sub = 'Góc nhìn chân thực, bóc tách chi tiết từng không gian, tiện ích và pháp lý bởi chuyên viên <strong>' . esc_html($advisor_name) . '</strong>.';
}

$video_count = count($videos);
$container_class = ($context === 'du_an')
    ? 'bg-surface rounded-xl sm:rounded-2xl p-5 md:p-6 mb-4 md:mb-5 border border-border/70 shadow-subtle max-md:rounded-none max-md:-mx-4 max-md:px-4 max-md:mb-3 max-md:border-x-0'
    : 'bg-white rounded-2xl border border-border/80 p-5 sm:p-6 shadow-subtle mb-4 sm:mb-5';
?>

<div id="<?php echo esc_attr($section_id); ?>" class="<?php echo esc_attr($container_class); ?>">
    
    <!-- SECTION HEADER: Tiêu đề kết hợp Persona Cố Vấn Tinh Giản -->
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 pb-4 sm:pb-5 border-b border-border/70">
        <!-- Left: Kicker, Title & Subtitle -->
        <div class="max-w-2xl">
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold tracking-wider uppercase text-primary mb-1">
                <?php echo batdongsan_icon('tiktok', ['width' => 12, 'height' => 12]); ?>
                <span>Góc Nhìn Thực Tế &amp; TikTok Review</span>
            </span>
            <h2 class="font-serif text-xl sm:text-2xl lg:text-[26px] font-bold tracking-tight text-charcoal m-0">
                <?php echo esc_html($custom_title); ?>
            </h2>
            <p class="font-sans text-xs sm:text-sm text-charcoal-muted mt-1 m-0 leading-relaxed">
                <?php echo wp_kses_post($custom_sub); ?>
            </p>
        </div>

        <!-- Right: Compact Cupertino Advisor Identity Pill -->
        <div class="flex items-center gap-3 bg-surface border border-border/80 rounded-2xl p-2 sm:p-2.5 shadow-subtle shrink-0 self-start lg:self-auto">
            <div class="relative shrink-0 w-10 h-10 sm:w-11 sm:h-11 rounded-full overflow-hidden ring-2 ring-primary/20 bg-stone">
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
                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-2 ring-surface" title="Đang trực tuyến"></span>
            </div>

            <div class="text-left pr-1 min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="font-bold text-xs sm:text-sm text-charcoal truncate"><?php echo esc_html($advisor_name); ?></span>
                    <span class="inline-flex text-primary" title="Đã xác thực">
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </span>
                </div>
                <span class="text-[11px] text-charcoal-muted block truncate">Người review &amp; tư vấn</span>
            </div>

            <div class="flex items-center gap-1.5 shrink-0 pl-1 border-l border-border/60">
                <a 
                    href="tel:<?php echo esc_attr($sanitized_phone); ?>" 
                    class="inline-flex items-center justify-center gap-1.5 h-7 sm:h-8 px-2.5 bg-charcoal hover:bg-black text-white rounded-xl text-xs font-medium transition-all active:scale-95 no-underline whitespace-nowrap"
                    title="Gọi trực tiếp"
                >
                    <?php echo batdongsan_icon('phone', ['width' => 11, 'height' => 11]); ?>
                    <span><?php echo esc_html($advisor_phone); ?></span>
                </a>

                <a 
                    href="<?php echo esc_url($zalo_url); ?>" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="inline-flex items-center justify-center h-7 sm:h-8 px-2.5 bg-[#0068ff] hover:bg-[#0057d6] text-white rounded-xl text-xs font-medium shadow-sm transition-all active:scale-95 no-underline whitespace-nowrap"
                    title="Chat Zalo"
                >
                    <span>Zalo</span>
                </a>
            </div>
        </div>
    </div>

    <!-- VIDEO CONTENT AREA -->
    <?php if ($video_count === 1) : 
        $single_vid = $videos[0];
        $single_platform = $single_vid['platform'] ?? 'tiktok';
    ?>
        <!-- Spotlight Layout: 1 Video Duy Nhất (Cực kỳ thanh lịch & Thuyết phục) -->
        <div class="mt-5 p-4 sm:p-5 rounded-2xl bg-canvas border border-border/70 flex flex-col md:flex-row items-center gap-5 sm:gap-7">
            <!-- Left: Portrait Reel Card (Aspect 9:15) -->
            <div 
                class="group relative w-full max-w-[240px] sm:max-w-[270px] aspect-[9/15] rounded-2xl overflow-hidden border border-border/70 shadow-card hover:shadow-card-hover transition-all duration-300 cursor-pointer bg-stone shrink-0 flex flex-col justify-between"
                data-video-modal-trigger
                data-video-url="<?php echo esc_url($single_vid['video_url']); ?>"
                data-video-embed="<?php echo esc_url($single_vid['embed_url']); ?>"
                data-video-title="<?php echo esc_attr($single_vid['title']); ?>"
                data-video-badge="<?php echo esc_attr($single_vid['badge']); ?>"
                data-video-platform="<?php echo esc_attr($single_platform); ?>"
            >
                <!-- Thumbnail -->
                <img 
                    src="<?php echo esc_url($single_vid['thumbnail']); ?>" 
                    alt="<?php echo esc_attr($single_vid['title']); ?>" 
                    loading="lazy" 
                    class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                >

                <!-- Scrim gradient -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-transparent pointer-events-none"></div>

                <!-- Top Platform Tag -->
                <div class="relative z-10 p-3 flex items-center justify-between gap-1.5">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-black/45 text-white backdrop-blur-md border border-white/10 shadow-xs">
                        <?php if ($single_platform === 'tiktok') : ?>
                            <?php echo batdongsan_icon('tiktok', ['width' => 10, 'height' => 10]); ?>
                            <span>TikTok</span>
                        <?php else : ?>
                            <?php echo batdongsan_icon('youtube', ['width' => 10, 'height' => 10]); ?>
                            <span>YouTube</span>
                        <?php endif; ?>
                    </span>

                    <span class="text-[10px] text-white/90 font-mono bg-black/40 px-1.5 py-0.5 rounded backdrop-blur-sm">
                        <?php echo esc_html($single_vid['duration']); ?>
                    </span>
                </div>

                <!-- Center Play Button -->
                <div class="relative z-10 flex items-center justify-center my-auto pointer-events-none">
                    <div class="w-13 h-13 rounded-full bg-white/90 text-primary group-hover:bg-primary group-hover:text-white shadow-xl flex items-center justify-center transition-all duration-300 group-hover:scale-110 backdrop-blur-md ring-4 ring-white/30">
                        <svg class="w-6 h-6 translate-x-0.5 fill-current" viewBox="0 0 24 24">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                    </div>
                </div>

                <!-- Bottom Title & Views -->
                <div class="relative z-10 p-3.5 text-white">
                    <span class="inline-block text-[10px] font-bold uppercase tracking-wider text-primary-light mb-1">
                        <?php echo esc_html($single_vid['badge']); ?>
                    </span>
                    <h3 class="text-xs sm:text-sm font-semibold text-white line-clamp-2 leading-snug m-0 mb-1.5 drop-shadow-sm">
                        <?php echo esc_html($single_vid['title']); ?>
                    </h3>
                    <div class="flex items-center justify-between text-[11px] text-white/80 font-medium pt-1.5 border-t border-white/15">
                        <span class="flex items-center gap-1">
                            <?php echo batdongsan_icon('eye', ['width' => 11, 'height' => 11]); ?>
                            <span><?php echo esc_html($single_vid['views']); ?></span>
                        </span>
                        <span class="text-white/70 text-[10.5px]">Phát video &rarr;</span>
                    </div>
                </div>
            </div>

            <!-- Right: Advisor Commentary & Direct Actions -->
            <div class="flex-1 min-w-0 text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold mb-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                    <span>Trực tiếp khảo sát thực địa</span>
                </div>

                <h3 class="text-base sm:text-lg font-bold text-charcoal m-0 mb-2 leading-snug">
                    <?php echo esc_html($single_vid['title']); ?>
                </h3>

                <p class="text-xs sm:text-sm text-charcoal-muted m-0 mb-4 leading-relaxed">
                    Xem trọn vẹn video để nắm bắt thực tế chất lượng hoàn thiện, hướng view ban công, ánh sáng tự nhiên và cảm giác không gian thực tế mà hình ảnh tĩnh khó truyền tải hết.
                </p>

                <!-- Key checklist -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-2.5 mb-5 text-xs text-charcoal font-medium">
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 text-[10px] font-bold">&check;</span>
                        <span>Không dùng góc rộng bóp méo không gian</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 text-[10px] font-bold">&check;</span>
                        <span>Kiểm tra thực tế tiếng ồn và ánh sáng</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 text-[10px] font-bold">&check;</span>
                        <span>Đánh giá vị trí tiện ích &amp; hạ tầng xung quanh</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 text-[10px] font-bold">&check;</span>
                        <span>Thẩm định pháp lý và lịch sử giao dịch</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <button 
                        type="button" 
                        class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold shadow-button transition-all active:scale-95 cursor-pointer"
                        data-video-modal-trigger
                        data-video-url="<?php echo esc_url($single_vid['video_url']); ?>"
                        data-video-embed="<?php echo esc_url($single_vid['embed_url']); ?>"
                        data-video-title="<?php echo esc_attr($single_vid['title']); ?>"
                        data-video-badge="<?php echo esc_attr($single_vid['badge']); ?>"
                        data-video-platform="<?php echo esc_attr($single_platform); ?>"
                    >
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        <span>Xem Video Ngay (Phát Trực Tiếp)</span>
                    </button>

                    <a 
                        href="<?php echo esc_url($zalo_url); ?>" 
                        target="_blank" 
                        rel="noopener noreferrer" 
                        class="inline-flex items-center justify-center gap-1.5 h-10 px-4 rounded-xl bg-[#0068ff] hover:bg-[#0057d6] text-white text-xs font-semibold shadow-sm transition-all active:scale-95 no-underline"
                    >
                        <span>Nhắn Zalo Tư Vấn Thêm</span>
                    </a>

                    <?php if (!empty($advisor_tiktok_url)) : ?>
                        <a 
                            href="<?php echo esc_url($advisor_tiktok_url); ?>" 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            class="inline-flex items-center justify-center gap-1.5 h-10 px-3.5 rounded-xl bg-stone hover:bg-stone/80 text-charcoal text-xs font-medium border border-border/70 transition-all active:scale-95 no-underline"
                        >
                            <?php echo batdongsan_icon('tiktok', ['width' => 12, 'height' => 12]); ?>
                            <span>Kênh TikTok</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php else : ?>
        <!-- Grid Layout: Nhiều Video Review (2 - 4+ Video Dọc Chuẩn 9:15) -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-4.5 mt-5">
            <?php foreach (array_slice($videos, 0, 4) as $v) : 
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

                    <!-- Gradient Scrim -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-transparent pointer-events-none"></div>

                    <!-- Top Bar: Platform pill + Duration -->
                    <div class="relative z-10 p-2.5 sm:p-3 flex items-center justify-between gap-1.5">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-black/45 text-white backdrop-blur-md border border-white/10 shadow-xs">
                            <?php if ($platform === 'tiktok') : ?>
                                <?php echo batdongsan_icon('tiktok', ['width' => 10, 'height' => 10]); ?>
                                <span>TikTok</span>
                            <?php else : ?>
                                <?php echo batdongsan_icon('youtube', ['width' => 10, 'height' => 10]); ?>
                                <span>YouTube</span>
                            <?php endif; ?>
                        </span>

                        <span class="text-[10px] text-white/90 font-mono bg-black/40 px-1.5 py-0.5 rounded backdrop-blur-sm">
                            <?php echo esc_html($v['duration']); ?>
                        </span>
                    </div>

                    <!-- Center: Frosted Glass Play Button -->
                    <div class="relative z-10 flex items-center justify-center my-auto pointer-events-none">
                        <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-white/90 text-primary group-hover:bg-primary group-hover:text-white shadow-lg flex items-center justify-center transition-all duration-300 group-hover:scale-110 backdrop-blur-md ring-2 ring-white/30">
                            <svg class="w-5 h-5 translate-x-0.5 fill-current" viewBox="0 0 24 24">
                                <polygon points="5 3 19 12 5 21 5 3"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Bottom: Title & Views -->
                    <div class="relative z-10 p-3 sm:p-3.5 text-white">
                        <span class="inline-block text-[10px] font-bold uppercase tracking-wider text-primary-light mb-1 drop-shadow-sm">
                            <?php echo esc_html($v['badge']); ?>
                        </span>
                        <h3 class="text-xs sm:text-sm font-semibold text-white line-clamp-2 leading-snug m-0 mb-1.5 drop-shadow-sm">
                            <?php echo esc_html($v['title']); ?>
                        </h3>

                        <div class="flex items-center justify-between text-[10.5px] text-white/80 font-medium pt-1.5 border-t border-white/15">
                            <span class="flex items-center gap-1">
                                <?php echo batdongsan_icon('eye', ['width' => 11, 'height' => 11]); ?>
                                <span><?php echo esc_html($v['views']); ?></span>
                            </span>
                            <span class="text-white/70 text-[10px]">Chạm để xem</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Section Footer Link -->
    <div class="mt-4 pt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-charcoal-muted border-t border-border/50">
        <span>Video thực địa được quay thực tế và cập nhật thường xuyên trên kênh cá nhân của chuyên viên.</span>
        <div class="flex items-center gap-3">
            <?php if (!empty($advisor_tiktok_url)) : ?>
                <a 
                    href="<?php echo esc_url($advisor_tiktok_url); ?>" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="inline-flex items-center gap-1.5 text-charcoal hover:text-primary transition-colors font-medium no-underline"
                >
                    <?php echo batdongsan_icon('tiktok', ['width' => 12, 'height' => 12]); ?>
                    <span>Kênh TikTok <strong><?php echo esc_html($advisor_tiktok_handle); ?></strong></span>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

</div>
