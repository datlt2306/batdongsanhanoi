<?php
/**
 * Video Lightbox Modal Component - Apple Clean Glassmorphism
 * 
 * Cho phép xem video YouTube, YouTube Shorts hoặc TikTok trực tiếp trên trang
 * mà không cần chuyển trang hoặc bị gián đoạn trải nghiệm người dùng.
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$advisor_data = function_exists('batdongsan_get_advisor_data') ? batdongsan_get_advisor_data() : [];
$advisor_name = !empty($advisor_data['advisor_name']) ? $advisor_data['advisor_name'] : 'Quỳnh Nguyễn';
?>

<div 
    id="bds-video-modal" 
    class="fixed inset-0 z-[10000] hidden items-center justify-center p-3 sm:p-6 transition-all duration-300 opacity-0 pointer-events-none" 
    role="dialog" 
    aria-modal="true" 
    aria-labelledby="bds-video-modal-title"
>
    <!-- Dark Frosted Backdrop -->
    <div class="bds-video-modal__backdrop absolute inset-0 bg-black/85 backdrop-blur-md transition-opacity"></div>

    <!-- Modal Dialog Window -->
    <div class="bds-video-modal__container relative z-10 w-full max-w-4xl bg-surface-dark bg-[#1c1c1e] text-white rounded-3xl border border-white/10 shadow-2xl overflow-hidden transition-all duration-300 transform scale-95 flex flex-col">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-white/10 bg-white/[0.03]">
            <div class="flex items-center gap-2.5 min-w-0 pr-4">
                <span class="bds-video-modal__badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold uppercase tracking-wider bg-primary/20 text-primary border border-primary/30 shrink-0">
                    Review Thực Tế
                </span>
                <h3 id="bds-video-modal-title" class="text-sm sm:text-base font-semibold text-white truncate m-0">
                    Xem Video
                </h3>
            </div>
            
            <!-- Close Button -->
            <button 
                type="button" 
                class="bds-video-modal__close w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer shrink-0" 
                aria-label="Đóng video"
            >
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Video Player Body (Responsive Ratio Container) -->
        <div class="bds-video-modal__player-wrap relative w-full bg-black flex items-center justify-center min-h-[300px] sm:min-h-[460px]">
            <!-- Dynamic Iframe will be injected here -->
            <div id="bds-video-iframe-slot" class="w-full h-full flex items-center justify-center aspect-video">
                <div class="bds-video-loading flex items-center justify-center text-white/50 text-sm">
                    <svg class="w-7 h-7 animate-spin text-primary mr-2" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Đang tải video...</span>
                </div>
            </div>
        </div>

        <!-- Modal Footer Info & Direct Link (Đảm bảo 1 dòng duy nhất, không xuống dòng) -->
        <div class="px-4 sm:px-5 py-3 bg-white/[0.03] border-t border-white/10 flex items-center justify-between gap-3 text-xs text-white/70 whitespace-nowrap">
            <div class="flex items-center gap-2 min-w-0 truncate">
                <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                <span class="truncate whitespace-nowrap">Chuyên viên tư vấn: <strong class="text-white"><?php echo esc_html($advisor_name); ?></strong></span>
            </div>
            <div class="shrink-0 flex items-center">
                <a 
                    id="bds-video-modal-external-link" 
                    href="#" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="inline-flex items-center justify-center gap-1 px-3.5 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-all font-medium no-underline text-xs active:scale-95 whitespace-nowrap"
                >
                    <span id="bds-video-modal-external-text">Xem thêm &rarr;</span>
                </a>
            </div>
        </div>

    </div>
</div>

<script>
(function() {
    var modal = document.getElementById('bds-video-modal');
    if (!modal) return;

    var container = modal.querySelector('.bds-video-modal__container');
    var iframeSlot = document.getElementById('bds-video-iframe-slot');
    var titleEl = document.getElementById('bds-video-modal-title');
    var badgeEl = modal.querySelector('.bds-video-modal__badge');
    var externalLink = document.getElementById('bds-video-modal-external-link');
    var externalText = document.getElementById('bds-video-modal-external-text');
    var closeBtn = modal.querySelector('.bds-video-modal__close');
    var backdrop = modal.querySelector('.bds-video-modal__backdrop');

    function openModal(data) {
        var embedUrl = data.embedUrl || '';
        var rawUrl = data.rawUrl || embedUrl;
        var title = data.title || 'Xem Video Review';
        var badge = data.badge || 'Review Thực Tế';
        var platform = data.platform || 'youtube';

        titleEl.textContent = title;
        badgeEl.textContent = badge;
        externalLink.href = rawUrl;

        if (platform === 'tiktok') {
            if (externalText) externalText.textContent = 'Xem thêm →';
            externalLink.className = 'inline-flex items-center justify-center gap-1 px-3.5 py-1.5 rounded-xl bg-[#fe2c55] hover:bg-[#e0264b] text-white transition-all font-semibold no-underline text-xs active:scale-95 shadow-md whitespace-nowrap shrink-0';
        } else {
            if (externalText) externalText.textContent = 'Xem thêm →';
            externalLink.className = 'inline-flex items-center justify-center gap-1 px-3.5 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-all font-medium no-underline text-xs active:scale-95 whitespace-nowrap shrink-0';
        }

        // Ratio styling based on platform (vertical 9:16 vs standard 16:9)
        // Kích thước chuẩn to rõ, cân đối như màn hình TikTok thực tế
        if (platform === 'tiktok' || platform === 'youtube_shorts') {
            container.className = 'bds-video-modal__container relative z-10 w-full max-w-[460px] sm:max-w-[500px] md:max-w-[520px] bg-[#1c1c1e] text-white rounded-3xl border border-white/10 shadow-2xl overflow-hidden transition-all duration-300 transform scale-100 flex flex-col my-auto';
            iframeSlot.className = 'w-full h-full flex items-center justify-center aspect-[9/16] h-[72vh] sm:h-[78vh] md:h-[82vh] max-h-[86vh]';
        } else {
            container.className = 'bds-video-modal__container relative z-10 w-full max-w-4xl bg-[#1c1c1e] text-white rounded-3xl border border-white/10 shadow-2xl overflow-hidden transition-all duration-300 transform scale-100 flex flex-col my-auto';
            iframeSlot.className = 'w-full h-full flex items-center justify-center aspect-video';
        }

        // Build iframe URL according to platform
        var autoEmbedUrl = embedUrl || rawUrl;
        if (platform === 'youtube' || platform === 'youtube_shorts') {
            var ym = autoEmbedUrl.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
            if (ym && ym[1]) {
                autoEmbedUrl = 'https://www.youtube.com/embed/' + ym[1];
            }
            var sep = autoEmbedUrl.indexOf('?') !== -1 ? '&' : '?';
            autoEmbedUrl = autoEmbedUrl + sep + 'autoplay=1&rel=0';
        } else if (platform === 'tiktok') {
            var tm = autoEmbedUrl.match(/tiktok\.com\/.*?(?:video\/|v\/|embed\/|player\/v1\/)(\d+)/);
            if (tm && tm[1]) {
                autoEmbedUrl = 'https://www.tiktok.com/player/v1/' + tm[1];
            } else if (autoEmbedUrl.indexOf('/embed/v2/') !== -1) {
                autoEmbedUrl = autoEmbedUrl.replace('/embed/v2/', '/player/v1/');
            } else if (autoEmbedUrl.indexOf('/embed/') !== -1 && autoEmbedUrl.indexOf('/player/v1/') === -1) {
                autoEmbedUrl = autoEmbedUrl.replace('/embed/', '/player/v1/');
            }
            var sep = autoEmbedUrl.indexOf('?') !== -1 ? '&' : '?';
            autoEmbedUrl = autoEmbedUrl + sep + 'autoplay=1&music_info=1&description=1';
        }

        iframeSlot.innerHTML = '<iframe src="' + autoEmbedUrl + '" class="w-full h-full border-0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen" allowfullscreen></iframe>';

        modal.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
        modal.classList.add('flex', 'opacity-100');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0', 'pointer-events-none');
        setTimeout(function() {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            iframeSlot.innerHTML = '';
            document.body.style.overflow = '';
        }, 250);
    }

    // Delegate click on any trigger element
    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('[data-video-modal-trigger]');
        if (!trigger) return;
        e.preventDefault();

        var embedUrl = trigger.getAttribute('data-video-embed') || '';
        var rawUrl = trigger.getAttribute('data-video-url') || '';
        var title = trigger.getAttribute('data-video-title') || '';
        var badge = trigger.getAttribute('data-video-badge') || 'Review Thực Tế';
        var platform = trigger.getAttribute('data-video-platform') || 'youtube';

        openModal({
            embedUrl: embedUrl,
            rawUrl: rawUrl,
            title: title,
            badge: badge,
            platform: platform
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    window.bdsOpenVideoModal = openModal;
})();
</script>
