<?php

/**
 * Loan Calculator - Ước tính khoản vay mua nhà (Milestone 5 Luxury Redesign)
 * Dùng chung cho single-bat-dong-san.php và single-du-an.php
 *
 * @package BatDongSan
 * @param array $args {
 *     @type int $default_value Giá trị bất động sản mặc định (VND)
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

$defaults = ['default_value' => 3000000000];
$args = wp_parse_args($args ?? [], $defaults);
$loan_default_value  = (int) $args['default_value'];
$loan_default_amount = (int) ($loan_default_value * 0.5);
?>

<div id="project-loan-section" class="py-2">
    <!-- Section Header (Cupertino Sans) -->
    <div class="mb-8 pb-4 border-b border-black/[0.06]">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary mb-1 block">Giải Pháp Tài Chính & Lãi Suất</span>
        <h2 class="text-xl lg:text-2xl font-bold text-charcoal tracking-tight m-0">Ước Tính Khoản Vay Mua Nhà</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[1.2fr_0.8fr] gap-8 lg:gap-10 items-start">
        
        <!-- Calculator Form Inputs -->
        <div class="loan-calculator__form flex flex-col gap-5">
            
            <!-- 1. Giá trị nhà đất -->
            <div class="loan-calculator__field">
                <label for="loan-property-value" class="block text-xs font-sans font-medium uppercase tracking-wider text-charcoal-muted mb-2">
                    Giá trị bất động sản
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="loan-property-value" 
                        class="w-full h-12 bg-canvas/60 border border-border rounded-xl px-4 pr-10 text-base font-sans font-bold text-charcoal focus:bg-surface focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none transition-all" 
                        value="<?php echo esc_attr(number_format($loan_default_value, 0, ',', '.')); ?>" 
                        data-raw="<?php echo esc_attr($loan_default_value); ?>"
                    >
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-charcoal-muted font-sans font-semibold text-sm">đ</span>
                </div>
            </div>

            <!-- 2. Tỉ lệ vay & Số tiền vay -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="loan-calculator__field">
                    <div class="flex items-center justify-between mb-2">
                        <label for="loan-ratio" class="block text-xs font-sans font-medium uppercase tracking-wider text-charcoal-muted">
                            Tỉ lệ vay
                        </label>
                        <span class="text-xs font-sans font-semibold text-primary" id="loan-ratio-display">50%</span>
                    </div>
                    <div class="relative">
                        <input 
                            type="number" 
                            id="loan-ratio" 
                            class="w-full h-12 bg-canvas/60 border border-border rounded-xl px-4 pr-10 text-base font-sans font-bold text-charcoal focus:bg-surface focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none transition-all" 
                            value="50" 
                            min="0" 
                            max="100"
                        >
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-charcoal-muted font-sans font-semibold text-sm">%</span>
                    </div>
                </div>

                <div class="loan-calculator__field">
                    <label for="loan-amount" class="block text-xs font-sans font-medium uppercase tracking-wider text-charcoal-muted mb-2">
                        Số tiền cần vay
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="loan-amount" 
                            class="w-full h-12 bg-canvas/60 border border-border rounded-xl px-4 pr-10 text-base font-sans font-bold text-charcoal focus:bg-surface focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none transition-all" 
                            value="<?php echo esc_attr(number_format($loan_default_amount, 0, ',', '.')); ?>" 
                            data-raw="<?php echo esc_attr($loan_default_amount); ?>"
                        >
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-charcoal-muted font-sans font-semibold text-sm">đ</span>
                    </div>
                </div>
            </div>

            <!-- 3. Thời hạn vay -->
            <div class="loan-calculator__field">
                <div class="flex items-center justify-between mb-2">
                    <label for="loan-term" class="block text-xs font-sans font-medium uppercase tracking-wider text-charcoal-muted">
                        Thời hạn vay
                    </label>
                    <span class="text-xs font-sans text-charcoal-muted">(Tối đa 35 năm)</span>
                </div>
                <div class="relative">
                    <input 
                        type="number" 
                        id="loan-term" 
                        class="w-full h-12 bg-canvas/60 border border-border rounded-xl px-4 pr-16 text-base font-sans font-bold text-charcoal focus:bg-surface focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none transition-all" 
                        value="15"
                        min="1"
                        max="35"
                    >
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-charcoal-muted font-sans font-semibold text-sm">năm</span>
                </div>
            </div>

            <!-- 4. Lãi suất theo ngân hàng -->
            <div class="grid grid-cols-1 sm:grid-cols-[1fr_120px] gap-4">
                <div class="loan-calculator__field">
                    <label for="loan-bank" class="block text-xs font-sans font-medium uppercase tracking-wider text-charcoal-muted mb-2">
                        Ngân hàng ưu đãi
                    </label>
                    <div class="relative">
                        <select 
                            id="loan-bank" 
                            class="w-full h-12 bg-canvas/60 border border-border rounded-xl px-4 pr-10 text-sm font-sans font-medium text-charcoal focus:bg-surface focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none transition-all appearance-none cursor-pointer"
                        >
                            <option value="7.5" data-url="https://www.vietcombank.com.vn/vi-VN/KHCN/Vay/Vay-mua-bat-dong-san">Vietcombank (7.5%/năm)</option>
                            <option value="7.2" data-url="https://www.bidv.com.vn/vn/ca-nhan/san-pham/vay-ca-nhan/vay-nhu-cau-nha-o">BIDV (7.2%/năm)</option>
                            <option value="7.0" data-url="https://www.agribank.com.vn/vn/ca-nhan/san-pham/vay-von-agribank">Agribank (7.0%/năm)</option>
                            <option value="8.5" data-url="https://techcombank.com/khach-hang-ca-nhan/vay">Techcombank (8.5%/năm)</option>
                            <option value="8.99" data-url="https://www.baca-bank.vn/tra-cuu/lai-suat-cho-vay" selected>Bac A Bank (8.99%/năm)</option>
                            <option value="9.0" data-url="https://www.vpbank.com.vn/ca-nhan/vay/vay-mua-nha">VPBank (9.0%/năm)</option>
                        </select>
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-charcoal-muted">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9l6 6 6-6"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="loan-calculator__field">
                    <label for="loan-interest-rate" class="block text-xs font-sans font-medium uppercase tracking-wider text-charcoal-muted mb-2">
                        Lãi suất %/năm
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="loan-interest-rate" 
                            class="w-full h-12 bg-canvas/60 border border-border rounded-xl px-4 pr-8 text-base font-sans font-bold text-charcoal focus:bg-surface focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none transition-all text-center" 
                            value="8,99"
                        >
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-charcoal-muted font-sans font-semibold text-sm">%</span>
                    </div>
                </div>
            </div>

            <!-- 5. Phương thức trả gốc & lãi -->
            <div class="loan-calculator__field">
                <label for="loan-method" class="block text-xs font-sans font-medium uppercase tracking-wider text-charcoal-muted mb-2">
                    Phương thức thanh toán
                </label>
                <div class="relative">
                    <select 
                        id="loan-method" 
                        class="w-full h-12 bg-canvas/60 border border-border rounded-xl px-4 pr-10 text-sm font-sans font-medium text-charcoal focus:bg-surface focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none transition-all appearance-none cursor-pointer"
                    >
                        <option value="reducing" selected>Dư nợ giảm dần (Tiết kiệm lãi tối ưu)</option>
                        <option value="fixed">Dư nợ gốc chia đều (Gốc đều hàng tháng)</option>
                    </select>
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-charcoal-muted">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 9l6 6 6-6"/>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs text-charcoal-muted mt-1">
                <svg class="w-4 h-4 text-primary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"/>
                    <line x1="12" y1="11" x2="12" y2="17" stroke-width="2"/>
                    <circle cx="12" cy="7.5" r="1" fill="currentColor"/>
                </svg>
                <span>Kết quả ước tính mang tính chất tham khảo dựa trên chính sách hiện hành của ngân hàng.</span>
            </div>
        </div>

        <!-- Mobile Toggle Button (Apple SF Symbol: magnifyingglass) -->
        <button 
            type="button" 
            class="hidden max-md:flex w-full items-center justify-center gap-2 py-3.5 bg-primary hover:bg-primary-hover text-white font-sans font-semibold text-sm rounded-xl shadow-button transition-all cursor-pointer" 
            data-loan-toggle-result 
            aria-expanded="false"
        >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="10.5" cy="10.5" r="6.5"/><line x1="15.5" y1="15.5" x2="20.5" y2="20.5" stroke-width="2.2"/>
            </svg>
            <span data-loan-toggle-label>Xem kết quả tính toán</span>
        </button>

        <!-- Right Side: Elevated Porcelain Result Card -->
        <div 
            class="loan-calculator__result bg-white border border-black/[0.06] rounded-2xl p-6 sm:p-7 shadow-[0_2px_14px_rgba(0,0,0,0.03)] h-fit sticky top-28 max-md:hidden" 
            data-loan-result-panel
        >
            <div class="text-xs uppercase tracking-wider font-sans font-medium text-charcoal-muted mb-1.5">
                Tổng số tiền bạn cần trả
            </div>
            <div class="text-3xl sm:text-4xl font-bold text-charcoal mb-6 leading-tight" id="loan-result-total">
                --
            </div>

            <!-- Visual Allocation Chart Bar -->
            <div class="mb-6">
                <div class="loan-calculator__chart w-full h-2 rounded-full flex overflow-hidden mb-2 bg-stone">
                    <div class="loan-calculator__chart-equity bg-charcoal transition-all duration-300" style="width: 50%" title="Vốn tự có"></div>
                    <div class="loan-calculator__chart-principal bg-primary transition-all duration-300" style="width: 40%" title="Gốc cần trả"></div>
                    <div class="loan-calculator__chart-interest bg-amber-500 transition-all duration-300" style="width: 10%" title="Lãi cần trả"></div>
                </div>
                <div class="flex w-full text-[11px] font-sans font-bold">
                    <div class="loan-calculator__pct-equity text-charcoal text-center" style="width: 50%">50%</div>
                    <div class="loan-calculator__pct-principal text-primary text-center" style="width: 40%">40%</div>
                    <div class="loan-calculator__pct-interest text-amber-600 text-center" style="width: 10%">10%</div>
                </div>
            </div>

            <!-- Breakdown Detail Rows -->
            <div class="flex flex-col gap-3.5 mb-6">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full bg-charcoal shrink-0"></span>
                        <span class="text-xs font-sans text-charcoal-muted truncate">Vốn tự có (đối ứng)</span>
                    </div>
                    <span class="text-xs sm:text-sm font-sans font-bold text-charcoal whitespace-nowrap shrink-0 text-right tabular-nums" id="loan-result-equity">--</span>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full bg-primary shrink-0"></span>
                        <span class="text-xs font-sans text-charcoal-muted truncate">Gốc vay cần trả</span>
                    </div>
                    <span class="text-xs sm:text-sm font-sans font-bold text-primary whitespace-nowrap shrink-0 text-right tabular-nums" id="loan-result-principal">--</span>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                        <span class="text-xs font-sans text-charcoal-muted truncate">Tổng lãi suất dự kiến</span>
                    </div>
                    <span class="text-xs sm:text-sm font-sans font-bold text-charcoal whitespace-nowrap shrink-0 text-right tabular-nums" id="loan-result-interest">--</span>
                </div>
            </div>

            <!-- First Month Installment Highlight -->
            <div class="pt-5 border-t border-border mb-6">
                <div class="flex flex-col gap-1">
                    <span class="text-xs uppercase tracking-wider font-sans font-medium text-charcoal-muted">Thanh toán tháng đầu tiên</span>
                    <span class="text-2xl sm:text-3xl font-bold text-primary leading-tight mt-1" id="loan-result-first-month">
                        --
                    </span>
                    <span class="text-[11px] text-charcoal-muted font-sans mt-1">Gồm tiền gốc chia đều + lãi theo dư nợ</span>
                </div>
            </div>

            <!-- Financial Plan Export & Policy Link -->
            <div class="flex flex-col gap-3">
                <button 
                    type="button" 
                    id="loan-export-excel" 
                    class="w-full h-11 bg-[#F5F5F7] hover:bg-black/[0.06] text-charcoal font-sans font-medium text-xs rounded-full border border-black/[0.08] flex items-center justify-center gap-2 transition-all shadow-xs active:scale-[0.99] cursor-pointer"
                >
                    <svg class="w-4 h-4 text-charcoal/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 12v6.5A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5V12"/>
                        <path d="M12 3v12M8 11l4 4 4-4"/>
                    </svg>
                    <span>Xuất Kế Hoạch Trả Nợ (Excel)</span>
                </button>

                <a 
                    href="#" 
                    id="loan-bank-policy-link" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="flex items-center justify-center gap-1.5 text-xs font-sans font-medium text-charcoal-muted hover:text-primary transition-colors py-1"
                >
                    <span>Xem chính sách vay của ngân hàng</span>
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </a>
            </div>

        </div>
    </div>
</div>
