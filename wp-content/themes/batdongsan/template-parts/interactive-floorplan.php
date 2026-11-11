<?php
/**
 * Template Part: Bộ Chọn & Xem Mặt Bằng Tầng Đơn Giản, Tinh Gọn (Clean Floorplan Selector)
 *
 * @package BatDongSan
 */

if (!defined('ABSPATH')) {
    exit;
}

$args = wp_parse_args($args ?? [], [
    'project_id'          => 0,
    'danh_sach_mat_bang'  => [],
    'mat_bang_gallery'    => [],
]);

$project_id = (int) $args['project_id'];
$raw_list   = (array) ($args['danh_sach_mat_bang'] ?? []);
$gallery    = (array) ($args['mat_bang_gallery'] ?? []);

// Chuẩn hóa danh sách mặt bằng thành mảng đơn giản
$floorplans = [];

if (!empty($raw_list)) {
    foreach ($raw_list as $idx => $item) {
        $img_raw = $item['anh_mat_bang'] ?? null;
        $img_url = '';
        $img_id  = 0;
        if (is_array($img_raw) && !empty($img_raw['url'])) {
            $img_url = $img_raw['url'];
            $img_id  = (int) ($img_raw['ID'] ?? 0);
        } elseif (is_numeric($img_raw)) {
            $img_id  = (int) $img_raw;
            $img_url = wp_get_attachment_url($img_id);
        } elseif (is_string($img_raw)) {
            $img_url = $img_raw;
        }

        if (!empty($img_url)) {
            $ten = trim($item['ten_mat_bang'] ?? ('Mặt bằng ' . ($idx + 1)));
            $floorplans[] = [
                'id'      => 'mb-' . $idx,
                'ten'     => $ten,
                'ghi_chu' => trim($item['ghi_chu'] ?? ''),
                'url'     => $img_url,
                'img_id'  => $img_id,
            ];
        }
    }
}

// Fallback sang gallery cũ nếu chưa nhập danh sách
if (empty($floorplans) && !empty($gallery)) {
    foreach ($gallery as $idx => $img_id) {
        $img_id = (int) $img_id;
        $url = wp_get_attachment_url($img_id);
        $title = get_the_title($img_id);
        if ($url) {
            $floorplans[] = [
                'id'      => 'mb-' . $idx,
                'ten'     => $title ?: ('Mặt bằng số ' . ($idx + 1)),
                'ghi_chu' => 'Bản vẽ quy hoạch tổng thể',
                'url'     => $url,
                'img_id'  => $img_id,
            ];
        }
    }
}

if (empty($floorplans)) {
    return;
}

$first_fp = $floorplans[0];
?>

<div class="floorplan-selector" data-floorplan-selector>
    <!-- Hàng nút chọn mặt bằng (Pill Buttons đơn giản, trực quan) -->
    <?php if (count($floorplans) > 1): ?>
        <div class="flex items-center gap-2 mb-4 overflow-x-auto no-scrollbar pb-1">
            <?php foreach ($floorplans as $idx => $fp): ?>
                <?php $is_act = ($idx === 0); ?>
                <button
                    type="button"
                    class="floorplan-pill-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer shrink-0 <?php echo $is_act ? 'bg-primary text-white shadow-xs' : 'bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60'; ?>"
                    data-target-mb="<?php echo esc_attr($fp['id']); ?>"
                >
                    <?php echo esc_html($fp['ten']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Khung hiển thị mặt bằng chính -->
    <div class="relative w-full rounded-xl sm:rounded-2xl border border-border/70 bg-stone/20 overflow-hidden shadow-subtle p-3 sm:p-5">
        <?php foreach ($floorplans as $idx => $fp): ?>
            <?php $is_act = ($idx === 0); ?>
            <div
                class="floorplan-panel-item <?php echo $is_act ? 'block' : 'hidden'; ?>"
                id="<?php echo esc_attr($fp['id']); ?>"
                data-mb-panel
            >
                <!-- Tiêu đề & Ghi chú ngắn -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3 pb-2.5 border-b border-border/50">
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-charcoal m-0"><?php echo esc_html($fp['ten']); ?></h3>
                        <?php if (!empty($fp['ghi_chu'])): ?>
                            <p class="text-xs text-charcoal-muted m-0 mt-0.5"><?php echo esc_html($fp['ghi_chu']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a
                            href="<?php echo esc_url($fp['url']); ?>"
                            target="_blank"
                            class="floorplan-zoom-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white hover:bg-stone/60 text-charcoal border border-border/70 text-xs font-semibold shadow-xs transition-colors cursor-pointer"
                            data-full-url="<?php echo esc_url($fp['url']); ?>"
                            data-title="<?php echo esc_attr($fp['ten']); ?>"
                        >
                            <svg class="w-3.5 h-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" /></svg>
                            <span>Phóng to bản vẽ</span>
                        </a>
                    </div>
                </div>

                <!-- Bản vẽ mặt bằng hiển thị lớn & sắc nét -->
                <div class="flex items-center justify-center min-h-[380px] max-h-[640px] overflow-hidden rounded-lg bg-white/70 p-2 sm:p-4">
                    <img
                        src="<?php echo esc_url($fp['url']); ?>"
                        alt="<?php echo esc_attr($fp['ten']); ?>"
                        class="max-w-full max-h-[600px] w-auto h-auto object-contain mx-auto transition-transform duration-300 hover:scale-[1.01] cursor-pointer floorplan-click-img"
                        data-full-url="<?php echo esc_url($fp['url']); ?>"
                        data-title="<?php echo esc_attr($fp['ten']); ?>"
                        loading="lazy"
                    />
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="text-center text-xs text-charcoal-muted mt-2.5">
        Nhấp chuột vào bản vẽ hoặc bấm <strong>Phóng to</strong> để xem chi tiết số căn, kích thước và thông số kỹ thuật.
    </p>

    <!-- Modal Phóng To Full HD Đơn Giản -->
    <div id="simple-floorplan-modal" class="fixed inset-0 z-[999] bg-charcoal/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 opacity-0 invisible transition-all duration-200">
        <div class="bg-white rounded-2xl w-full max-w-5xl max-h-[92vh] flex flex-col overflow-hidden shadow-2xl border border-border">
            <div class="px-5 py-3.5 border-b border-border/80 flex items-center justify-between bg-canvas">
                <h4 class="text-sm sm:text-base font-bold text-charcoal m-0" id="simple-floorplan-modal-title">Bản Vẽ Mặt Bằng</h4>
                <button type="button" class="p-2 rounded-full hover:bg-stone/60 text-charcoal-muted hover:text-charcoal transition-colors cursor-pointer" id="simple-floorplan-close" aria-label="Đóng">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="p-4 flex items-center justify-center bg-stone/20 overflow-auto max-h-[75vh]">
                <img id="simple-floorplan-modal-img" src="" alt="Mặt bằng phóng to" class="max-w-full max-h-[72vh] object-contain rounded-lg shadow-sm" />
            </div>
            <div class="px-5 py-3 bg-canvas border-t border-border flex items-center justify-between">
                <span class="text-xs text-charcoal-muted">Bản vẽ thiết kế quy hoạch chi tiết</span>
                <button type="button" class="px-4 py-1.5 rounded-lg bg-primary text-white text-xs font-semibold hover:bg-primary-dark transition-colors cursor-pointer" data-modal-target="advisor-modal">
                    Tải Trọn Bộ Bản Vẽ CAD
                </button>
            </div>
        </div>
    </div>
</div>
