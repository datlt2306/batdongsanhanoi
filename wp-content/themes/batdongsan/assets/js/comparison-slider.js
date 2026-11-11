/**
 * BEAF Engine (Before and After Ultimate Slider)
 * 
 * Các tính năng chuẩn BEAF:
 * 1. Move Slider on Hover (Di chuột qua là thanh trượt tự động bám theo con trỏ chuột).
 * 2. Click to Move (Nhấp bất kỳ điểm nào trên ảnh để thanh trượt lướt tới).
 * 3. Drag & Drop (Nắm giữ kéo thả) qua Pointer Events API mượt mà trên cả máy tính & cảm ứng.
 * 4. Hỗ trợ Horizontal (Ngang) và Vertical (Dọc).
 * 5. Tự động mờ nhãn (Auto-fade labels) khi thanh trượt tới gần mép.
 * 6. Điều khiển phím mũi tên chuẩn Accessibility (WCAG 2.2).
 */
(function() {
    'use strict';

    function initBeafSliders() {
        const sliders = document.querySelectorAll('[data-beaf-slider]');
        if (!sliders.length) return;

        sliders.forEach(slider => {
            if (slider._beafInitialized) return;
            slider._beafInitialized = true;

            const isVertical = slider.getAttribute('data-orientation') === 'vertical';
            const moveOnHover = slider.getAttribute('data-move-on-hover') === 'true';
            const handle = slider.querySelector('.beaf-handle-wrap');
            const beforeLabel = slider.querySelector('.beaf-before-label');
            const afterLabel = slider.querySelector('.beaf-after-label');
            const modeBtns = slider.querySelectorAll('.beaf-mode-btn');

            let isDragging = false;
            let currentPos = parseFloat(slider.getAttribute('data-default-offset')) || 50;
            let ticking = false;

            function updateSlider(percent, smooth = false) {
                currentPos = Math.max(0, Math.min(100, percent));

                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        if (smooth) {
                            slider.style.transition = '--beaf-pos 0.25s ease-out';
                            setTimeout(() => { slider.style.transition = ''; }, 260);
                        }

                        slider.style.setProperty('--beaf-pos', `${currentPos}%`);

                        if (handle) {
                            handle.setAttribute('aria-valuenow', Math.round(currentPos));
                        }

                        // Auto fade labels khi thanh trượt che lấp
                        if (beforeLabel) {
                            beforeLabel.style.opacity = currentPos < 18 ? '0' : '1';
                        }
                        if (afterLabel) {
                            afterLabel.style.opacity = currentPos > 82 ? '0' : '1';
                        }

                        // Cập nhật nút quick mode
                        modeBtns.forEach(btn => {
                            const btnPos = parseFloat(btn.getAttribute('data-pos'));
                            const isActive = Math.abs(btnPos - currentPos) < 6;
                            btn.classList.toggle('bg-primary', isActive);
                            btn.classList.toggle('text-white', isActive);
                            btn.classList.toggle('font-black', isActive);
                            btn.classList.toggle('text-slate-300', !isActive);
                        });

                        ticking = false;
                    });
                    ticking = true;
                }
            }

            function getPercentFromEvent(e) {
                const rect = slider.getBoundingClientRect();
                if (isVertical) {
                    const y = e.clientY - rect.top;
                    return (y / rect.height) * 100;
                } else {
                    const x = e.clientX - rect.left;
                    return (x / rect.width) * 100;
                }
            }

            // 1. Move Slider on Hover (Tính năng ăn khách của BEAF)
            // Chỉ kích hoạt trên thiết bị có chuột (hover pointer: fine) để không ảnh hưởng cuộn trang trên mobile
            const isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

            if (moveOnHover && !isTouch) {
                slider.addEventListener('mousemove', function(e) {
                    if (isDragging) return;
                    const percent = getPercentFromEvent(e);
                    updateSlider(percent);
                });
            }

            // 2. Click to Move & Drag Start
            function onPointerDown(e) {
                if (e.target.closest('.beaf-quick-switcher')) return;
                if (e.button !== undefined && e.button !== 0 && e.pointerType === 'mouse') return;

                isDragging = true;
                slider.setPointerCapture(e.pointerId);

                const percent = getPercentFromEvent(e);
                updateSlider(percent, true); // Nhấp thì lướt tới mượt
            }

            function onPointerMove(e) {
                if (!isDragging) return;
                const percent = getPercentFromEvent(e);
                updateSlider(percent, false);
            }

            function onPointerUp(e) {
                if (!isDragging) return;
                isDragging = false;
                try {
                    slider.releasePointerCapture(e.pointerId);
                } catch(err) {}
            }

            slider.addEventListener('pointerdown', onPointerDown);
            slider.addEventListener('pointermove', onPointerMove);
            slider.addEventListener('pointerup', onPointerUp);
            slider.addEventListener('pointercancel', onPointerUp);

            // 3. Phím bấm trợ năng Accessibility (WCAG 2.2)
            if (handle) {
                handle.addEventListener('keydown', function(e) {
                    const step = 5;
                    if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                        e.preventDefault();
                        updateSlider(currentPos - step, true);
                    } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                        e.preventDefault();
                        updateSlider(currentPos + step, true);
                    } else if (e.key === 'Home') {
                        e.preventDefault();
                        updateSlider(0, true);
                    } else if (e.key === 'End') {
                        e.preventDefault();
                        updateSlider(100, true);
                    }
                });
            }

            // 4. Quick Mode buttons (100% | 50% | 0%)
            modeBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const targetPos = parseFloat(this.getAttribute('data-pos'));
                    updateSlider(targetPos, true);
                });
            });

            // Set initial position
            updateSlider(currentPos);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBeafSliders);
    } else {
        initBeafSliders();
    }

    // Export ra toàn cục
    window.bdsInitBeafSliders = initBeafSliders;
})();
