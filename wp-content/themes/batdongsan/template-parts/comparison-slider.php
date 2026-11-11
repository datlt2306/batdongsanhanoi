<?php
/**
 * Component: Slider So Sánh Ảnh Before / After Chuẩn Plugin BEAF (Ultimate Before After)
 *
 * Tính năng chuẩn BEAF:
 * 1. Move Slider on Hover (Di chuột tự động lướt bám theo trỏ chuột mượt mà).
 * 2. Click to Move (Nhấp bất kỳ điểm nào trên ảnh để thanh trượt bay tới).
 * 3. Drag & Drop mượt mà với Pointer Events API (chuẩn cho mobile & tablet).
 * 4. Tay nắm BEAF Handle Signature: Vòng tròn trung tâm với mũi tên đối xứng ◀ ▶ và đường phân cách viền sáng.
 * 5. Nhãn Before & After chuẩn BEAF bo góc kính mờ với hiệu ứng tự mờ khi thanh trượt tới gần.
 * 6. Hỗ trợ cả 2 chiều: Horizontal (Ngang) và Vertical (Dọc).
 *
 * @package BatDongSan
 * @param array $args {
 *     @type string|int $before_url    URL hoặc Attachment ID ảnh Trước (2D)
 *     @type string|int $after_url     URL hoặc Attachment ID ảnh Sau (3D)
 *     @type string     $before_label  Nhãn ảnh Trước (mặc định: 'Bản vẽ 2D')
 *     @type string     $after_label   Nhãn ảnh Sau (mặc định: 'Phối cảnh 3D')
 *     @type string     $orientation   'horizontal' hoặc 'vertical' (mặc định: 'horizontal')
 *     @type bool       $move_on_hover Có tự động trượt theo chuột không (mặc định: true)
 *     @type int        $offset        Vị trí bắt đầu % (mặc định: 50)
 *     @type string     $aspect_ratio  Tỉ lệ khung hình (mặc định: 'aspect-[16/10]')
 *     @type string     $slider_id     ID định danh
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

$raw_before    = $args['before_url'] ?? '';
$raw_after     = $args['after_url'] ?? '';
$before_label  = $args['before_label'] ?? ($args['badge_before'] ?? 'Bản vẽ 2D');
$after_label   = $args['after_label'] ?? ($args['badge_after'] ?? 'Phối cảnh 3D');
$orientation   = in_array($args['orientation'] ?? '', ['vertical', 'horizontal']) ? $args['orientation'] : 'horizontal';
$move_on_hover = isset($args['move_on_hover']) ? (bool) $args['move_on_hover'] : true;
$default_pos   = isset($args['offset']) ? (int) $args['offset'] : 50;
$aspect_ratio  = $args['aspect_ratio'] ?? 'aspect-[16/10]';
$slider_id     = $args['slider_id'] ?? ('beaf-slider-' . uniqid());

// Xử lý nếu truyền vào Attachment ID
$before_url = is_numeric($raw_before) ? wp_get_attachment_url((int)$raw_before) : (is_array($raw_before) ? ($raw_before['url'] ?? '') : $raw_before);
$after_url  = is_numeric($raw_after) ? wp_get_attachment_url((int)$raw_after) : (is_array($raw_after) ? ($raw_after['url'] ?? '') : $raw_after);

$before_alt = $args['before_alt'] ?? ($before_label ?: 'Before image');
$after_alt  = $args['after_alt'] ?? ($after_label ?: 'After image');

if (empty($before_url) && empty($after_url)) {
    return;
}

// Nếu chỉ có 1 ảnh thì hiển thị ảnh đơn
if (empty($before_url) || empty($after_url)):
    $single_url = $before_url ?: $after_url;
    $single_label = $before_url ? $before_label : $after_label;
    ?>
    <div class="relative w-full <?php echo esc_attr($aspect_ratio); ?> rounded-xl overflow-hidden bg-slate-900 border border-border/80 flex items-center justify-center p-2 group shadow-xs">
        <img 
            src="<?php echo esc_url($single_url); ?>" 
            alt="<?php echo esc_attr($single_label); ?>" 
            class="w-full h-full object-contain block select-none group-hover:scale-105 transition-transform duration-300" 
            loading="lazy" 
            decoding="async"
        >
        <span class="absolute top-3 left-3 z-10 px-3 py-1 rounded-md text-xs font-bold tracking-wider uppercase bg-charcoal/80 text-white backdrop-blur-md border border-white/20">
            <?php echo esc_html($single_label); ?>
        </span>
    </div>
    <?php
    return;
endif;
?>

<!-- BEAF Image Comparison Slider Container -->
<div 
    id="<?php echo esc_attr($slider_id); ?>" 
    class="beaf-container beaf-<?php echo esc_attr($orientation); ?> relative w-full <?php echo esc_attr($aspect_ratio); ?> rounded-2xl overflow-hidden bg-slate-950 border border-slate-800 shadow-card select-none group touch-none" 
    data-beaf-slider
    data-orientation="<?php echo esc_attr($orientation); ?>"
    data-move-on-hover="<?php echo $move_on_hover ? 'true' : 'false'; ?>"
    data-default-offset="<?php echo esc_attr($default_pos); ?>"
    style="--beaf-pos: <?php echo esc_attr($default_pos); ?>%;"
>
    <!-- Lớp Ảnh 1: BEFORE (Nằm dưới toàn phần) -->
    <div class="beaf-layer beaf-layer-before absolute inset-0 w-full h-full flex items-center justify-center p-2 pointer-events-none select-none">
        <img 
            src="<?php echo esc_url($before_url); ?>" 
            alt="<?php echo esc_attr($before_alt); ?>" 
            class="w-full h-full object-contain block select-none pointer-events-none" 
            loading="lazy" 
            decoding="async"
            draggable="false"
        >
    </div>

    <!-- Lớp Ảnh 2: AFTER (Nằm trên, cắt tỉ lệ bằng clip-path) -->
    <div 
        class="beaf-layer beaf-layer-after absolute inset-0 w-full h-full flex items-center justify-center p-2 pointer-events-none select-none"
        style="<?php echo $orientation === 'vertical' ? 'clip-path: inset(var(--beaf-pos) 0 0 0); -webkit-clip-path: inset(var(--beaf-pos) 0 0 0);' : 'clip-path: inset(0 0 0 var(--beaf-pos)); -webkit-clip-path: inset(0 0 0 var(--beaf-pos));'; ?>"
    >
        <img 
            src="<?php echo esc_url($after_url); ?>" 
            alt="<?php echo esc_attr($after_alt); ?>" 
            class="w-full h-full object-contain block select-none pointer-events-none" 
            loading="lazy" 
            decoding="async"
            draggable="false"
        >
    </div>

    <!-- Thanh Phân Cách & Tay Nắm Chuẩn BEAF (Divider & Handle) -->
    <div 
        class="beaf-handle-wrap absolute z-20 pointer-events-auto flex items-center justify-center cursor-pointer"
        style="<?php echo $orientation === 'vertical' ? 'top: var(--beaf-pos); left: 0; right: 0; height: 40px; transform: translateY(-50%); cursor: ns-resize;' : 'left: var(--beaf-pos); top: 0; bottom: 0; width: 40px; transform: translateX(-50%); cursor: ew-resize;'; ?>"
        role="slider"
        tabindex="0"
        aria-label="So sánh hình ảnh Before và After"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-valuenow="<?php echo esc_attr($default_pos); ?>"
    >
        <!-- Đường Kẻ Line Trắng Sáng Bóng Chuẩn BEAF -->
        <div class="beaf-line <?php echo $orientation === 'vertical' ? 'w-full h-[3px]' : 'h-full w-[3px]'; ?> bg-white shadow-[0_0_12px_rgba(0,0,0,0.6),0_0_4px_rgba(255,255,255,0.9)]"></div>
        
        <!-- Nút Tròn Tay Nắm Trọng Tâm Với Mũi Tên Đối Xứng ◀ ▶ -->
        <div class="beaf-handle-circle absolute w-10 h-10 rounded-full bg-white/95 backdrop-blur-md shadow-[0_6px_20px_rgba(0,0,0,0.45)] border-[3px] border-primary text-primary flex items-center justify-center font-bold text-xs select-none transition-transform duration-150 hover:scale-115 active:scale-95 cursor-grab active:cursor-grabbing">
            <?php if ($orientation === 'vertical'): ?>
                <div class="flex flex-col items-center gap-0.5 text-primary text-[10px] font-black leading-none">
                    <span>▲</span>
                    <span>▼</span>
                </div>
            <?php else: ?>
                <div class="flex items-center gap-1.5 text-primary text-[11px] font-black leading-none tracking-tighter">
                    <span>◀</span>
                    <span>▶</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Nhãn BEAF: Before Label -->
    <span class="beaf-label beaf-before-label absolute z-10 px-3.5 py-1.5 rounded-lg text-xs font-extrabold tracking-wider uppercase bg-slate-950/80 text-white backdrop-blur-md border border-white/20 shadow-lg pointer-events-none transition-opacity duration-200 <?php echo $orientation === 'vertical' ? 'top-3 left-3' : 'top-3 left-3'; ?>">
        <?php echo esc_html($before_label); ?>
    </span>

    <!-- Nhãn BEAF: After Label -->
    <span class="beaf-label beaf-after-label absolute z-10 px-3.5 py-1.5 rounded-lg text-xs font-extrabold tracking-wider uppercase bg-primary/90 text-white backdrop-blur-md border border-white/20 shadow-lg pointer-events-none transition-opacity duration-200 <?php echo $orientation === 'vertical' ? 'bottom-3 right-3' : 'top-3 right-3'; ?>">
        <?php echo esc_html($after_label); ?>
    </span>

    <!-- BEAF Quick Mode Controls (2D | So sánh 50/50 | 3D) -->
    <div class="beaf-quick-switcher absolute bottom-3 right-3 z-20 flex items-center gap-1 bg-slate-950/80 backdrop-blur-md p-1 rounded-xl border border-white/15 text-[11px] font-bold text-white shadow-xl pointer-events-auto">
        <button type="button" class="beaf-mode-btn px-2.5 py-1 rounded-lg transition-all hover:bg-white/20 text-slate-300" data-pos="100">
            <?php echo esc_html($before_label); ?>
        </button>
        <button type="button" class="beaf-mode-btn px-2.5 py-1 rounded-lg transition-all bg-primary text-white shadow-xs font-black" data-pos="50">
            So sánh ⇄
        </button>
        <button type="button" class="beaf-mode-btn px-2.5 py-1 rounded-lg transition-all hover:bg-white/20 text-slate-300" data-pos="0">
            <?php echo esc_html($after_label); ?>
        </button>
    </div>
</div>
