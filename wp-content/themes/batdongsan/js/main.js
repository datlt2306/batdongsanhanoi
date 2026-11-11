(function ($) {
    "use strict";

    let isManualScrolling = false;

    // Constants - tránh magic numbers, dễ bảo trì
    const BREAKPOINT_MOBILE = 1023;
    const DEBOUNCE_RESIZE_MS = 250;
    const SCROLL_SPY_OFFSET = 220;

    const $win = $(window);
    const $doc = $(document);

    const debounce = function (fn, delay) {
        let timer = null;
        return function () {
            const args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(this, args);
            }, delay);
        };
    };

    const isLightGalleryReady = function () {
        return (
            typeof lightGallery !== "undefined" &&
            typeof lgZoom !== "undefined" &&
            typeof lgThumbnail !== "undefined"
        );
    };

    const getDefaultLightGalleryOptions = function (overrides) {
        const isMobileScreen = $win.width() < 1024;
        const plugins = [];
        if (typeof lgThumbnail !== "undefined") plugins.push(lgThumbnail);
        // Tắt plugin zoom trên mobile để tối ưu tốc độ và tránh giật lag
        if (!isMobileScreen && typeof lgZoom !== "undefined") plugins.push(lgZoom);

        const base = {
            plugins: plugins,
            download: false,
            thumbnail: !isMobileScreen,
            zoom: !isMobileScreen,
            actualSize: !isMobileScreen,
            closeOnTap: true,
            escKey: true,
            pager: false,
            speed: 300,
            backdropDuration: 250,
            startAnimationDuration: 250,
            zoomFromOrigin: false,
            mode: "lg-fade",
            hideBarsDelay: 2000,
            slideDelay: 0,
            closable: true,
            hideScrollbar: true,
            container: document.body,
            exThumbImage: "data-external-thumb-image",
            thumbWidth: 80,
            thumbHeight: "60px",
            thumbMargin: 6,
        };
        return $.extend(true, {}, base, overrides || {});
    };

    /**
     * =============================================================================
     * CENTRALIZED FILTER CONTROLLER & STATE STORE (Milestone 2 - F07, F08, F09, F10)
     * =============================================================================
     */

    // 1. Centralized Reactive State Store
    const FilterStateStore = (function () {
        const DEFAULT_STATE = {
            hinh_thuc: "",
            type: "0",
            khu_vuc: "",
            price: "",
            area: "",
            "project-name": "",
            du_an: "0",
            so_phong_ngu: "",
            so_phong_ve_sinh: "",
            huong_nha: "",
            phap_ly: "",
            status: "0",
            order: "1",
            paged: 1
        };

        let state = Object.assign({}, DEFAULT_STATE);
        const subscribers = [];
        let isNotifying = false;

        return {
            getDefaults: function () {
                return Object.assign({}, DEFAULT_STATE);
            },
            init: function (initialState, defaultOverrides) {
                const base = Object.assign({}, DEFAULT_STATE, defaultOverrides || {});
                state = Object.assign({}, base, initialState || {});
            },
            get: function (key) {
                return state[key] !== undefined ? state[key] : (DEFAULT_STATE[key] || "");
            },
            getAll: function () {
                return Object.assign({}, state);
            },
            set: function (key, value, options) {
                options = options || {};
                const strVal = value !== null && value !== undefined ? String(value).trim() : "";
                if (state[key] === strVal && !options.force) return;
                const prev = state[key];
                state[key] = strVal;

                if (!options.silent) {
                    const changes = {};
                    changes[key] = { prev: prev, next: strVal };
                    this.notify(changes, options);
                }
            },
            setMultiple: function (updates, options) {
                options = options || {};
                const changes = {};
                let hasChange = false;

                let mergedUpdates = updates;
                if (options.source === "popstate" || options.resetMissing) {
                    mergedUpdates = Object.assign({}, DEFAULT_STATE, updates);
                }

                for (const key in mergedUpdates) {
                    if (!Object.prototype.hasOwnProperty.call(mergedUpdates, key)) continue;
                    const strVal = mergedUpdates[key] !== null && mergedUpdates[key] !== undefined ? String(mergedUpdates[key]).trim() : "";
                    if (state[key] !== strVal || options.force) {
                        changes[key] = { prev: state[key], next: strVal };
                        state[key] = strVal;
                        hasChange = true;
                    }
                }

                if ((hasChange || options.force) && !options.silent) {
                    this.notify(changes, options);
                }
            },
            reset: function (options) {
                options = options || {};
                const changes = {};
                for (const key in DEFAULT_STATE) {
                    if (Object.prototype.hasOwnProperty.call(DEFAULT_STATE, key)) {
                        changes[key] = { prev: state[key], next: DEFAULT_STATE[key] };
                    }
                }
                state = Object.assign({}, DEFAULT_STATE);
                this.notify(changes, Object.assign({ source: "reset" }, options));
            },
            subscribe: function (listener) {
                if (typeof listener === "function" && subscribers.indexOf(listener) === -1) {
                    subscribers.push(listener);
                }
                return function () {
                    const idx = subscribers.indexOf(listener);
                    if (idx !== -1) subscribers.splice(idx, 1);
                };
            },
            notify: function (changes, options) {
                if (isNotifying) return;
                isNotifying = true;
                try {
                    const currentState = this.getAll();
                    for (let i = 0; i < subscribers.length; i++) {
                        try {
                            subscribers[i](currentState, changes, options || {});
                        } catch (e) {
                            console.error("FilterStateStore subscriber error:", e);
                        }
                    }
                } finally {
                    isNotifying = false;
                }
            }
        };
    })();

    // 2. Canonical Filter Label & Formatter Service
    const FilterLabelService = {
        formatKhuVuc: function (val) {
            if (!val) return "Toàn Hà Nội";
            const $opt = $("[data-filter-area-option][data-value='" + val + "'], .filter-header-modal-option[data-value='" + val + "']").first();
            return $opt.length ? $.trim($opt.text()) : "Toàn Hà Nội";
        },

        formatType: function (val) {
            if (!val || val === "0") return "Tất cả loại hình";
            const $opt = $("[data-filter-type-option][data-value='" + val + "'], [data-filter-header-modal-id='loai-hinh'] .filter-header-modal-option[data-value='" + val + "']").first();
            if ($opt.length) {
                return $.trim($opt.find("span").last().text() || $opt.text());
            }
            return "Tất cả loại hình";
        },

        formatStatus: function (val) {
            if (!val || val === "0") return "Tất cả";
            const statusMap = {
                "dang_mo_ban": "Đang mở bán",
                "dang-mo-ban": "Đang mở bán",
                "sap_mo_ban": "Sắp mở bán",
                "sap-mo-ban": "Sắp mở bán",
                "da_ban_het": "Đã bán hết",
                "da-ban-het": "Đã bán hết",
                "da_ban_giao": "Đã bàn giao",
                "tam_dung": "Tạm dừng",
                "tam-dung": "Tạm dừng",
                "dang_cap_nhat": "Đang cập nhật",
                "dang-cap-nhat": "Đang cập nhật"
            };
            if (statusMap[val]) return statusMap[val];
            const $opt = $("[data-filter-status-option][data-value='" + val + "']").first();
            return $opt.length ? $.trim($opt.find("span").last().text() || $opt.text()) : "Tất cả";
        },

        formatPrice: function (val) {
            if (!val || val === "0") return "Tất cả khoảng giá";
            if (val === "thoa_thuan") return "Thỏa thuận";

            const $preset = $("[data-filter-price-preset-label][data-value='" + val + "'], [data-filter-header-modal-id='khoang-gia'] .filter-header-modal-option[data-value='" + val + "']").first();
            if ($preset.length) return $.trim($preset.text());

            if (val.indexOf("-") !== -1) {
                const parts = val.split("-");
                const v1 = parseFloat(parts[0]) || 0;
                const v2 = parseFloat(parts[1]) || 0;
                const f = function (v) {
                    return v >= 1000 ? (Math.round(v / 100) / 10) + " tỷ" : v + " triệu";
                };
                return f(v1) + " - " + f(v2);
            }
            const v = parseFloat(val) || 0;
            return v >= 1000 ? "Từ " + (Math.round(v / 100) / 10) + " tỷ" : "Từ " + v + " triệu";
        },

        formatArea: function (val) {
            if (!val || val === "0") return "Tất cả diện tích";
            const $preset = $("[data-filter-area-range-preset-label][data-value='" + val + "']").first();
            if ($preset.length) return $.trim($preset.text());

            if (val.indexOf("-") !== -1) {
                const parts = val.split("-");
                return parts[0] + " - " + parts[1] + " m²";
            }
            return "Từ " + val + " m²";
        }
    };

    // 3. Desktop Adapter: Synchronizes horizontal desktop bar, triggers, sliders, popover
    const DesktopAdapter = {
        init: function () {
            FilterStateStore.subscribe(function (state, changes, options) {
                if (options && options.source === "desktop_internal") return;
                DesktopAdapter.render(state, changes);
            });
        },

        render: function (state, changes) {
            const $desktopForms = $(".filter-bar-horizontal__form");
            if (!$desktopForms.length) return;

            // 1. Sync hidden inputs across all filter forms
            $desktopForms.find("[data-filter-area-hidden]").val(state.khu_vuc || "");
            $desktopForms.find("[data-filter-type-hidden]").val(state.type || "0");
            $desktopForms.find("[data-filter-price-hidden]").val(state.price || "");
            $desktopForms.find("[data-filter-area-range-hidden]").val(state.area || "");
            $desktopForms.find("[data-filter-status-hidden]").val(state.status || "0");
            $desktopForms.find("[data-filter-hinh-thuc-hidden], input[name='hinh_thuc']").val(state.hinh_thuc || "");
            $desktopForms.find("[data-filter-so-phong-ngu-hidden]").val(state.so_phong_ngu || "");
            $desktopForms.find("[data-filter-so-phong-ve-sinh-hidden]").val(state.so_phong_ve_sinh || "");
            $desktopForms.find("[data-filter-huong-nha-hidden]").val(state.huong_nha || "");
            $desktopForms.find("[data-filter-phap-ly-hidden]").val(state.phap_ly || "");
            $desktopForms.find("input[name='project-name']").val(state["project-name"] || "");

            // 2. Khu Vực Dropdown Trigger & Options
            const areaLabel = FilterLabelService.formatKhuVuc(state.khu_vuc);
            $("[data-filter-area-trigger]").each(function () {
                $(this).find("span").last().text(areaLabel);
            });
            $("[data-filter-area-option]").removeClass("text-primary font-semibold")
                .filter(function () {
                    return String($(this).data("value")) === String(state.khu_vuc || "");
                }).addClass("text-primary font-semibold");

            // 3. Loại Hình Dropdown Trigger & Options
            const typeLabel = FilterLabelService.formatType(state.type);
            $("[data-filter-type-trigger]").each(function () {
                $(this).find("span").last().text(typeLabel);
            });
            $("[data-filter-type-option]").removeAttr("data-active").removeClass("text-primary font-semibold")
                .find("span:first").removeClass("text-primary");
            const $activeType = $("[data-filter-type-option]").filter(function () {
                return String($(this).data("value")) === String(state.type || "0");
            });
            if ($activeType.length) {
                $activeType.attr("data-active", "true").addClass("text-primary font-semibold");
                $activeType.find("span").first().addClass("text-primary");
            }

            // 4. Trạng Thái Dropdown Trigger & Options
            const statusLabel = FilterLabelService.formatStatus(state.status);
            $("[data-filter-status-trigger]").each(function () {
                $(this).find("span").last().text(statusLabel);
            });
            $("[data-filter-status-option]").removeClass("text-primary font-semibold")
                .filter(function () {
                    return String($(this).data("value")) === String(state.status || "0");
                }).addClass("text-primary font-semibold");

            // 5. Khoảng Giá Dropdown Trigger, Presets & Dual Slider
            const priceLabel = FilterLabelService.formatPrice(state.price);
            $("[data-filter-price-trigger]").each(function () {
                $(this).find("span").last().text(priceLabel);
            });
            $("[data-filter-price-preset-label]").removeClass("bg-red-50 bg-primary-light text-primary font-medium")
                .filter(function () {
                    return String($(this).data("value")) === String(state.price || "");
                }).addClass("bg-primary-light text-primary font-medium");
            DesktopAdapter.syncPriceSlider(state.price);

            // 6. Diện Tích Dropdown Trigger, Presets & Dual Slider
            const areaLabelText = FilterLabelService.formatArea(state.area);
            $("[data-filter-area-range-trigger]").each(function () {
                $(this).find("span").last().text(areaLabelText);
            });
            $("[data-filter-area-range-preset-label]").removeClass("bg-red-50 bg-primary-light text-primary font-medium")
                .filter(function () {
                    return String($(this).data("value")) === String(state.area || "");
                }).addClass("bg-primary-light text-primary font-medium");
            DesktopAdapter.syncAreaSlider(state.area);

            // 7. Advanced Popover Pills & Trigger Badge
            DesktopAdapter.syncAdvancedPopover(state);

            // 8. Transaction Tabs
            const activeTab = state.hinh_thuc || "";
            $("#bds-transaction-tabs [data-bds-tab]").each(function () {
                const tabVal = $(this).attr("data-bds-tab") || "";
                if (tabVal === activeTab) {
                    $(this).removeClass("bg-gray-100 text-gray-700 hover:bg-gray-200").addClass("bg-primary text-white shadow-button");
                } else {
                    $(this).removeClass("bg-primary text-white shadow-button").addClass("bg-gray-100 text-gray-700 hover:bg-gray-200");
                }
            });
        },

        syncDualSlider: function (wrapperSelector, rangeSelector, prefix, rawVal, defaultMin, defaultMax, fmtFn) {
            $(wrapperSelector).each(function () {
                const $wrap = $(this);
                const $range = $wrap.find(rangeSelector);
                if (!$range.length) return;

                const boundMin = parseInt($range.data("bound-min"), 10) || defaultMin;
                const boundMax = parseInt($range.data("bound-max"), 10) || defaultMax;
                const rangeSpan = boundMax - boundMin;
                if (rangeSpan <= 0) return;

                let vMin = boundMin;
                let vMax = boundMax;

                if (rawVal && rawVal !== "0" && rawVal !== "thoa_thuan") {
                    if (rawVal.indexOf("-") !== -1) {
                        const p = rawVal.split("-");
                        vMin = Math.max(boundMin, Math.min(boundMax, parseInt(p[0], 10) || boundMin));
                        vMax = Math.min(boundMax, parseInt(p[1], 10) || boundMax);
                    } else {
                        vMin = Math.max(boundMin, Math.min(boundMax, parseInt(rawVal, 10) || boundMin));
                        vMax = boundMax;
                    }
                }

                const pctMin = Math.max(0, Math.min(100, ((vMin - boundMin) / rangeSpan) * 100));
                const pctMax = Math.max(0, Math.min(100, ((vMax - boundMin) / rangeSpan) * 100));

                $wrap.find("[" + prefix + "-thumb-min]").css("left", pctMin + "%").attr("aria-valuenow", vMin);
                $wrap.find("[" + prefix + "-thumb-max]").css("left", pctMax + "%").attr("aria-valuenow", vMax);
                $wrap.find("[" + prefix + "-fill]").css({ left: pctMin + "%", width: (pctMax - pctMin) + "%" });
                $wrap.find("[" + prefix + "-input-min]").val(vMin > boundMin ? vMin : "");
                $wrap.find("[" + prefix + "-input-max]").val(vMax < boundMax ? vMax : "");
                $wrap.find("[" + prefix + "-label-min]").text(fmtFn(vMin));
                $wrap.find("[" + prefix + "-label-max]").text(fmtFn(vMax));
            });
        },

        syncPriceSlider: function (priceVal) {
            const fmt = function (v) { return v >= 1000 ? (Math.round(v / 100) / 10) + " tỷ" : v + " tr"; };
            this.syncDualSlider("[data-filter-price]", "[data-filter-price-range]", "data-filter-price", priceVal, 0, 60000, fmt);
        },

        syncAreaSlider: function (areaVal) {
            const fmt = function (v) { return v + " m²"; };
            this.syncDualSlider("[data-filter-area-range]", "[data-filter-area-range-slider]", "data-filter-area-range", areaVal, 0, 500, fmt);
        },

        syncAdvancedPopover: function (state) {
            const targets = ["so_phong_ngu", "so_phong_ve_sinh", "huong_nha", "phap_ly"];
            let count = 0;

            targets.forEach(function (t) {
                const val = state[t] || "";
                if (val && val !== "all") count++;

                $(".adv-pill[data-adv-target='" + t + "']").each(function () {
                    const pillVal = String($(this).data("adv-val") || "");
                    if (pillVal === val) {
                        $(this).removeClass("bg-stone/50 text-charcoal hover:bg-stone border-border/60")
                            .addClass("bg-primary text-white shadow-button");
                    } else {
                        $(this).removeClass("bg-primary text-white shadow-button")
                            .addClass("bg-stone/50 text-charcoal hover:bg-stone border-border/60");
                    }
                });
            });

            const $triggerSpan = $("[data-filter-advanced-trigger] span").last();
            if (count > 0) {
                $triggerSpan.html('<span class="inline-flex items-center gap-1.5 text-primary"><span>Đã chọn (' + count + ')</span></span>');
            } else {
                $triggerSpan.text("Nâng cao");
            }
        }
    };

    // 4. Mobile Adapter: Synchronizes mobile header triggers, modals, inputs
    const MobileAdapter = {
        init: function () {
            FilterStateStore.subscribe(function (state, changes, options) {
                if (options && options.source === "mobile_internal") return;
                MobileAdapter.render(state, changes);
            });
        },

        render: function (state, changes) {
            const $mobileForms = $(".block.lg\\:hidden .filter-bar-horizontal__form, .header__filter-mobile .filter-bar-horizontal__form");
            if ($mobileForms.length) {
                $mobileForms.find("[data-filter-area-hidden]").val(state.khu_vuc || "");
                $mobileForms.find("[data-filter-type-hidden]").val(state.type || "0");
                $mobileForms.find("[data-filter-price-hidden]").val(state.price || "");
                $mobileForms.find("input[name='project-name']").val(state["project-name"] || "");
                $mobileForms.find("[data-filter-status-hidden]").val(state.status || "0");
                $mobileForms.find("[data-filter-hinh-thuc-hidden], input[name='hinh_thuc']").val(state.hinh_thuc || "");
            }

            // 1. Khu Vực Header Trigger & Modal Options
            const areaLabel = FilterLabelService.formatKhuVuc(state.khu_vuc);
            const hasArea = !!state.khu_vuc;
            const $areaTrigger = $("[data-filter-header-modal='khu-vuc'] [data-filter-header-modal-trigger]");
            if ($areaTrigger.length) {
                $areaTrigger.find("span").first().text(areaLabel);
                MobileAdapter.toggleTriggerActive($areaTrigger, hasArea);
            }
            const $areaPanel = $("[data-filter-header-modal-panel][data-filter-header-modal-id='khu-vuc']");
            if ($areaPanel.length) {
                $areaPanel.find(".filter-header-modal-option").each(function () {
                    const optVal = String($(this).data("value") || "");
                    const isSelected = optVal === (state.khu_vuc || "");
                    MobileAdapter.toggleOptionActive($(this), isSelected);
                });
            }

            // 2. Loại Hình Header Trigger & Modal Options
            const typeLabel = FilterLabelService.formatType(state.type);
            const hasType = !!state.type && state.type !== "0";
            const $typeTrigger = $("[data-filter-header-modal='loai-hinh'] [data-filter-header-modal-trigger]");
            if ($typeTrigger.length) {
                $typeTrigger.find("span").first().text(typeLabel);
                MobileAdapter.toggleTriggerActive($typeTrigger, hasType);
            }
            const $typePanel = $("[data-filter-header-modal-panel][data-filter-header-modal-id='loai-hinh']");
            if ($typePanel.length) {
                $typePanel.find(".filter-header-modal-option").each(function () {
                    const optVal = String($(this).data("value") || "0");
                    const isSelected = optVal === (state.type || "0");
                    MobileAdapter.toggleOptionActive($(this), isSelected);
                });
            }

            // 3. Khoảng Giá Header Trigger & Modal Options
            const priceLabel = FilterLabelService.formatPrice(state.price);
            const hasPrice = !!state.price && state.price !== "0";
            const $priceTrigger = $("[data-filter-header-modal='khoang-gia'] [data-filter-header-modal-trigger]");
            if ($priceTrigger.length) {
                $priceTrigger.find("span").first().text(priceLabel);
                MobileAdapter.toggleTriggerActive($priceTrigger, hasPrice);
            }
            const $pricePanel = $("[data-filter-header-modal-panel][data-filter-header-modal-id='khoang-gia']");
            if ($pricePanel.length) {
                $pricePanel.find(".filter-header-modal-option").each(function () {
                    const optVal = String($(this).data("value") || "");
                    const isSelected = optVal === (state.price || "");
                    MobileAdapter.toggleOptionActive($(this), isSelected);
                });
            }
        },

        toggleTriggerActive: function ($trigger, isActive) {
            if (isActive) {
                $trigger.removeClass("bg-stone/60 text-charcoal hover:bg-stone border-border/80 bg-black/[0.04] hover:bg-black/[0.08] border-black/[0.07] border-black/[0.06]")
                    .addClass("bg-primary text-white font-semibold shadow-sm");
                $trigger.find("svg").removeClass("text-charcoal-muted").addClass("text-white");
            } else {
                $trigger.removeClass("bg-primary text-white font-semibold shadow-sm bg-primary/10 text-primary border-primary/30")
                    .addClass("bg-black/[0.04] text-charcoal hover:bg-black/[0.08] border border-black/[0.07]");
                $trigger.find("svg").removeClass("text-white text-primary").addClass("text-charcoal-muted");
            }
        },

        toggleOptionActive: function ($opt, isSelected) {
            if (isSelected) {
                $opt.removeClass("text-gray-700 hover:bg-stone/40 text-charcoal hover:bg-black/[0.03]")
                    .addClass("bg-primary/10 text-primary font-semibold")
                    .attr("aria-selected", "true");
                $opt.find("svg").removeClass("hidden");
            } else {
                $opt.removeClass("bg-primary/10 bg-primary/8 text-primary font-semibold")
                    .addClass("text-charcoal hover:bg-black/[0.03]")
                    .attr("aria-selected", "false");
                $opt.find("svg").addClass("hidden");
            }
        }
    };

    // 5. Chips Adapter: Renders #bds-active-filters & handles 1-click removal / clear-all
    const ChipsAdapter = {
        computeChips: function (state) {
            const chips = [];
            const isProject = ($("#project-listings-grid").length > 0 || (window.bdsTheme && (window.bdsTheme.pageContext === "projects" || window.bdsTheme.pageContext === "du-an")));

            if (state.hinh_thuc) {
                const htMap = {
                    "chuyen-nhuong": "Hàng chuyển nhượng",
                    "cho-thue": "Cho thuê",
                    "mua-ban": "Mua / Bán",
                    "so-cap": "Sơ cấp",
                    "trung-cap": "Trung cấp",
                    "cao-cap": "Cao cấp"
                };
                chips.push({ key: "hinh_thuc", label: "Hình thức: " + (htMap[state.hinh_thuc] || state.hinh_thuc) });
            }
            if (state.khu_vuc) {
                chips.push({ key: "khu_vuc", label: "Khu vực: " + FilterLabelService.formatKhuVuc(state.khu_vuc) });
            }
            if (state.type && state.type !== "0") {
                const prefix = isProject ? "Loại dự án: " : "Loại hình: ";
                chips.push({ key: "type", label: prefix + FilterLabelService.formatType(state.type) });
            }
            if (state.price && state.price !== "0") {
                chips.push({ key: "price", label: "Giá: " + FilterLabelService.formatPrice(state.price) });
            }
            if (state.area && state.area !== "0") {
                chips.push({ key: "area", label: "Diện tích: " + FilterLabelService.formatArea(state.area) });
            }
            if (state["project-name"]) {
                chips.push({ key: "project-name", label: 'Từ khóa: "' + state["project-name"] + '"' });
            }
            if (state.du_an && state.du_an !== "0") {
                chips.push({ key: "du_an", label: "Dự án: #" + state.du_an });
            }
            if (state.so_phong_ngu) {
                chips.push({ key: "so_phong_ngu", label: "Phòng ngủ: " + state.so_phong_ngu + "+" });
            }
            if (state.so_phong_ve_sinh) {
                chips.push({ key: "so_phong_ve_sinh", label: "Vệ sinh: " + state.so_phong_ve_sinh + "+" });
            }
            if (state.huong_nha) {
                const hnMap = {
                    "dong": "Đông", "tay": "Tây", "nam": "Nam", "bac": "Bắc",
                    "dong-nam": "Đông Nam", "dong_nam": "Đông Nam",
                    "dong-bac": "Đông Bắc", "dong_bac": "Đông Bắc",
                    "tay-nam": "Tây Nam", "tay_nam": "Tây Nam",
                    "tay-bac": "Tây Bắc", "tay_bac": "Tây Bắc"
                };
                chips.push({ key: "huong_nha", label: "Hướng: " + (hnMap[state.huong_nha] || state.huong_nha) });
            }
            if (state.phap_ly) {
                const plMap = {
                    "so-do": "Sổ đỏ / Sổ hồng", "so_do": "Sổ đỏ / Sổ hồng",
                    "so_hong": "Sổ hồng", "so_do_so_hong": "Sổ đỏ / Sổ hồng",
                    "hop-dong-mua-ban": "HĐ mua bán", "hop_dong_mua_ban": "HĐ mua bán", "hop_dong": "HĐ mua bán",
                    "dang_ky_quyen_su_dung_dat": "Đăng ký QSD đất",
                    "giay_chung_nhan": "Giấy chứng nhận",
                    "dang-cho-so": "Đang chờ sổ", "dang_cho_so": "Đang chờ sổ",
                    "khac": "Khác"
                };
                chips.push({ key: "phap_ly", label: "Pháp lý: " + (plMap[state.phap_ly] || state.phap_ly) });
            }
            if (state.status && state.status !== "0") {
                chips.push({ key: "status", label: "Trạng thái: " + FilterLabelService.formatStatus(state.status) });
            }
            return chips;
        },

        render: function (filters) {
            const $activeContainers = $("#bds-active-filters");
            if (!$activeContainers.length) return;
            if (!filters || !filters.length) {
                $activeContainers.empty().addClass("hidden");
                return;
            }
            let html = '<span class="text-xs text-charcoal-muted font-medium">Đang lọc theo:</span>';
            filters.forEach(function (f) {
                const safeKey = $("<div>").text(f.key).html();
                const safeLabel = $("<div>").text(f.label).html();
                html += '<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-primary-light text-primary border border-primary/20 text-xs font-medium rounded-full shadow-sm">' +
                    '<span>' + safeLabel + '</span>' +
                    '<button type="button" class="hover:text-primary-dark cursor-pointer flex items-center justify-center p-0.5 rounded-full hover:bg-primary/20 transition-colors" data-remove-filter="' + safeKey + '" aria-label="Xóa bộ lọc ' + safeLabel + '">' +
                    '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
                    '</button>' +
                    '</span>';
            });
            html += '<button type="button" class="text-xs text-charcoal-muted hover:text-primary font-medium underline ml-1 cursor-pointer transition-colors" data-clear-all-filters>Xóa tất cả</button>';
            $activeContainers.html(html).removeClass("hidden");
        },

        renderFromState: function (state) {
            const chips = this.computeChips(state);
            this.render(chips);
        }
    };

    window.FilterStateStore = FilterStateStore;

    $(document).ready(function () {
        const pageContext = (window.bdsTheme && window.bdsTheme.pageContext) || "default";

        /**
         * Swiper 1 item cho Hero Gallery trang chi tiết dự án trên Mobile (< 1024px)
         * Tắt zoom hoàn toàn để tối ưu tốc độ và vuốt 60fps mượt mà
         */
        const initProjectHeroSwiper = function () {
            const $heroSwiperEl = $(".project-detail__hero-gallery-swiper");
            if (!$heroSwiperEl.length || typeof Swiper === "undefined") return;

            const swiperEl = $heroSwiperEl[0];
            if (swiperEl.swiper) return;

            new Swiper(swiperEl, {
                loop: false,
                slidesPerView: 1,
                spaceBetween: 0,
                autoHeight: false,
                watchSlidesProgress: true,
                grabCursor: true,
                resistanceRatio: 0.85,
                on: {
                    slideChange: function () {
                        const current = this.realIndex + 1;
                        $heroSwiperEl.find(".project-hero-swiper__current").text(current);
                    },
                },
            });
        };

        const initGalleryLightbox = function () {
            const $galleryEl = $(".project-detail__hero-gallery-grid");
            if (!$galleryEl.length || !isLightGalleryReady()) {
                if ($galleryEl.length) console.warn("lightGallery chưa sẵn sàng, bỏ qua bind.");
                return;
            }

            const galleryEl = $galleryEl[0];
            if (galleryEl._lg) {
                galleryEl._lg.destroy(true);
            }

            lightGallery(galleryEl, getDefaultLightGalleryOptions({
                selector: ".project-detail__hero-gallery-item",
            }));
        };

        /**
         * Swiper + lightGallery cho phần "Mặt bằng dự án"
         */
        const initFloorplanSwiperAndLightbox = function () {
            const $container = $(".project-detail__floorplan-swiper");
            if (!$container.length) return;

            const container = $container[0];

            if (typeof Swiper !== "undefined") {
                new Swiper(container, {
                    slidesPerView: 1,
                    spaceBetween: 16,
                    loop: true,
                    width: null,
                    watchOverflow: true,
                    navigation: {
                        nextEl: ".project-detail__floorplan-next",
                        prevEl: ".project-detail__floorplan-prev",
                    },
                    pagination: {
                        el: ".project-detail__floorplan-pagination",
                        clickable: true,
                    },
                    on: {
                        init: function () {
                            $container.find(".swiper-slide").css("width", "");
                        },
                    },
                });
            } else {
                console.warn("Swiper chưa sẵn sàng, bỏ qua slider mặt bằng.");
            }

            if (!isLightGalleryReady()) {
                console.warn("lightGallery chưa sẵn sàng cho mặt bằng dự án.");
                return;
            }

            if (container._lg) {
                container._lg.destroy(true);
            }

            lightGallery(container, getDefaultLightGalleryOptions({
                selector: ".project-detail__floorplan-slide",
            }));
        };

        /**
         * Chuyển đổi tab mặt bằng tổng thể / thiết kế căn hộ / tiêu chuẩn bàn giao
         */
        const initFloorplanSubTabs = function () {
            const $container = $("[data-floorplan-tabs]");
            if (!$container.length) return;

            $container.on("click", "[data-tab-btn]", function (e) {
                e.preventDefault();
                const tabKey = $(this).attr("data-tab-btn");
                const $section = $(this).closest("#project-floorplan-section");

                $section
                    .find("[data-tab-btn]")
                    .removeClass("bg-primary text-white shadow-sm")
                    .addClass("bg-stone/60 text-charcoal-muted hover:text-charcoal hover:bg-stone");
                $(this)
                    .removeClass("bg-stone/60 text-charcoal-muted hover:text-charcoal hover:bg-stone")
                    .addClass("bg-primary text-white shadow-sm");

                $section.find("[data-tab-panel]").addClass("hidden");
                const $activePanel = $section.find('[data-tab-panel="' + tabKey + '"]');
                $activePanel.removeClass("hidden");

                const swiperEl = $activePanel.find(".swiper")[0];
                if (swiperEl && swiperEl.swiper) {
                    swiperEl.swiper.update();
                }
            });
        };

        /**
         * Chuyển đổi tab Bảng giá / Chính sách / Tiêu chuẩn bàn giao
         */
        const initPricingTabs = function () {
            const $container = $("[data-pricing-tabs]");
            if (!$container.length) return;

            $container.on("click", "[data-pricing-tab-btn]", function (e) {
                e.preventDefault();
                const tabKey = $(this).attr("data-pricing-tab-btn");
                const $section = $(this).closest("#project-pricing-policy-section");

                $section
                    .find("[data-pricing-tab-btn]")
                    .removeClass("bg-primary text-white shadow-xs")
                    .addClass("bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60");
                $(this)
                    .removeClass("bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60")
                    .addClass("bg-primary text-white shadow-xs");

                $section.find("[data-pricing-tab-panel]").addClass("hidden");
                const $activePanel = $section.find('[data-pricing-tab-panel="' + tabKey + '"]');
                $activePanel.removeClass("hidden");
            });
        };

        /**
         * Generic Section Internal Tabs (Tất cả tabs cấp 2 trong từng section)
         * Xử lý chuyển đổi mượt mà, hỗ trợ accessibility (aria-selected, aria-hidden)
         */
        const initSectionTabs = function () {
            $doc.on("click", "[data-section-tab-btn]", function (e) {
                e.preventDefault();
                const $btn = $(this);
                const group = $btn.attr("data-tab-group");
                const targetKey = $btn.attr("data-section-tab-btn");
                const $container = $btn.closest("[data-section-tabs-container]");
                if (!$container.length) return;

                // Cập nhật trạng thái active cho tab buttons trong cùng group
                $container.find('[data-section-tab-btn][data-tab-group="' + group + '"]').each(function () {
                    const isMatch = $(this).attr("data-section-tab-btn") === targetKey;
                    $(this).attr("aria-selected", isMatch ? "true" : "false");
                    if (isMatch) {
                        $(this).addClass("is-active");
                    } else {
                        $(this).removeClass("is-active");
                    }
                });

                // Hiển thị panel tương ứng, ẩn các panel khác
                $container.find('[data-section-tab-panel][data-tab-group="' + group + '"]').each(function () {
                    const isMatch = $(this).attr("data-section-tab-panel") === targetKey;
                    if (isMatch) {
                        $(this).removeClass("hidden").attr("aria-hidden", "false");
                    } else {
                        $(this).addClass("hidden").attr("aria-hidden", "true");
                    }
                });

                // Cập nhật lại Swiper hoặc Gallery nếu có trong panel vừa active
                setTimeout(function () {
                    window.dispatchEvent(new Event("resize"));
                }, 60);
            });

            // Bộ lọc loại căn hộ (Studio, 1PN, 2PN, 3PN, VIP)
            $doc.on("click", "[data-unit-filter]", function (e) {
                e.preventDefault();
                const filterVal = $(this).attr("data-unit-filter");
                const $container = $(this).closest("[data-unit-filter-container]");
                if (!$container.length) return;

                $container.find("[data-unit-filter]").removeClass("bg-primary text-white shadow-xs").addClass("bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60");
                $(this).removeClass("bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60").addClass("bg-primary text-white shadow-xs");

                $container.find("[data-unit-card]").each(function () {
                    const cardType = $(this).attr("data-unit-type") || "";
                    if (filterVal === "all" || cardType.indexOf(filterVal) !== -1) {
                        $(this).removeClass("hidden");
                    } else {
                        $(this).addClass("hidden");
                    }
                });
            });

            // Bộ lọc chủ đề FAQ
            $doc.on("click", "[data-faq-filter]", function (e) {
                e.preventDefault();
                const filterTopic = $(this).attr("data-faq-filter");
                const $container = $(this).closest("#project-faq-section");
                if (!$container.length) return;

                $container.find("[data-faq-filter]").removeClass("bg-primary text-white shadow-xs").addClass("bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60");
                $(this).removeClass("bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60").addClass("bg-primary text-white shadow-xs");

                $container.find("[data-faq-item]").each(function () {
                    const itemTopic = $(this).attr("data-faq-topic") || "all";
                    if (filterTopic === "all" || itemTopic === filterTopic) {
                        $(this).removeClass("hidden");
                    } else {
                        $(this).addClass("hidden");
                    }
                });
            });
        };

        /**
         * Bộ chọn và phóng to mặt bằng tầng tinh gọn
         */
        const initFloorplanSelector = function () {
            const $container = $("[data-floorplan-selector]");
            if (!$container.length) return;

            $container.each(function () {
                const $root = $(this);
                const $modal = $root.find("#simple-floorplan-modal");
                const $modalImg = $root.find("#simple-floorplan-modal-img");
                const $modalTitle = $root.find("#simple-floorplan-modal-title");

                // Chuyển đổi giữa các mặt bằng
                $root.on("click", ".floorplan-pill-btn", function (e) {
                    e.preventDefault();
                    const targetId = $(this).attr("data-target-mb");

                    $root.find(".floorplan-pill-btn")
                        .removeClass("bg-primary text-white shadow-xs")
                        .addClass("bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60");
                    $(this)
                        .removeClass("bg-stone/50 hover:bg-stone text-charcoal-muted hover:text-charcoal border border-border/60")
                        .addClass("bg-primary text-white shadow-xs");

                    $root.find("[data-mb-panel]").addClass("hidden").removeClass("block");
                    $root.find("#" + targetId).removeClass("hidden").addClass("block");
                });

                // Phóng to bản vẽ mặt bằng
                $root.on("click", ".floorplan-zoom-btn, .floorplan-click-img", function (e) {
                    e.preventDefault();
                    const url = $(this).attr("data-full-url") || $(this).attr("src") || $(this).attr("href");
                    const title = $(this).attr("data-title") || "Bản Vẽ Mặt Bằng";
                    if (!url) return;

                    $modalImg.attr("src", url);
                    $modalTitle.text(title);
                    $modal.removeClass("opacity-0 invisible").addClass("opacity-100 visible");
                    $("body").addClass("overflow-hidden");
                });

                // Đóng modal phóng to
                $root.on("click", "#simple-floorplan-close, #simple-floorplan-modal", function (e) {
                    if (e.target === this || $(this).attr("id") === "simple-floorplan-close") {
                        $modal.removeClass("opacity-100 visible").addClass("opacity-0 invisible");
                        $("body").removeClass("overflow-hidden");
                    }
                });

                $(document).on("keydown", function (e) {
                    if (e.key === "Escape" && $modal.hasClass("visible")) {
                        $modal.removeClass("opacity-100 visible").addClass("opacity-0 invisible");
                        $("body").removeClass("overflow-hidden");
                    }
                });
            });
        };

        /**
         * Lightbox cho thư viện phối cảnh 3D & tiến độ thực tế
         */
        const initProjectVisualGalleryLightbox = function () {
            const $gallery = $("#project-visual-gallery");
            if (!$gallery.length) return;

            if (!isLightGalleryReady()) {
                return;
            }

            const galleryEl = $gallery[0];
            if (galleryEl._lg) {
                galleryEl._lg.destroy(true);
            }

            lightGallery(galleryEl, getDefaultLightGalleryOptions({
                selector: "a[data-sub-html]",
            }));
        };

        // Helper scroll mượt tới section (dùng chung cho tất cả trang) - NATIVE CSS scroll-margin-top
        let scrollCompleteTimer = null;
        const scrollToSection = function (targetSelector, onComplete) {
            if (!targetSelector) return;
            const targetEl = document.querySelector(targetSelector);
            if (!targetEl) return;

            isManualScrolling = true;

            targetEl.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });

            clearTimeout(scrollCompleteTimer);
            scrollCompleteTimer = setTimeout(function () {
                isManualScrolling = false;
                if (typeof onComplete === "function") {
                    onComplete();
                }
            }, 600);
        };

        const updateRelatedProjectsSwipers = function () {
            const $section = $("#project-related-projects-section");
            if (!$section.length) return;
            $section.find(".project-detail__related-swiper").each(function () {
                const el = this;
                if (el && el.swiper) {
                    el.swiper.updateSize();
                    el.swiper.updateSlides();
                }
            });
        };

        // Scroll đến section cho các nút thông thường (trừ tabs dự án đã có handler riêng)
        $("[data-scroll-target]:not(.project-detail__tab):not(.project-detail__tab-more-item):not(.project-detail__view-map)").on("click", function (e) {
            e.preventDefault();
            const targetSelector = $(this).data("scroll-target") || $(this).attr("data-scroll-target");
            if (targetSelector) {
                scrollToSection(targetSelector);
            }
        });

        /**
         * AJAX filter cho trang /du-an và trang chủ (F13, F15)
         */
        const initProjectsAjaxFilters = function () {
            if (typeof bdsTheme === "undefined" || !bdsTheme.ajaxUrl) {
                return;
            }

            const $mobileFilterForm = $(
                ".header__project-search-mobile .projects-filter-bar__form"
            );
            const $desktopFilterForm = $(".search-panel .projects-filter-bar__form");
            const $horizontalFilterForm = (pageContext !== "bds" && !$("#bds-listings-grid").length)
                ? $(".filter-bar-horizontal__form")
                : $();
            const $projectsSection = $(".section--projects").length
                ? $(".section--projects")
                : $("#project-listings-container").closest("section, .max-w-7xl").parent();
            const $projectsGrid = $("#project-listings-grid").length
                ? $("#project-listings-grid")
                : $projectsSection.find(".grid").first();
            // Title: try .section__title, then fall back to the heading near #project-listings-count
            const $projectsTitle = $projectsSection.find(".section__title").length
                ? $projectsSection.find(".section__title")
                : $("#project-listings-count").siblings("h1, h2").first();
            const $projectsPagination = $("#project-listings-pagination");

            if (
                (!$mobileFilterForm.length && !$desktopFilterForm.length && !$horizontalFilterForm.length) ||
                !$projectsGrid.length
            ) {
                return;
            }

            let projectTxId = 0;
            let projectAbortController = null;
            let isProjectsPoppingState = false;

            const getActiveProjectForm = function () {
                return $horizontalFilterForm.length ? $horizontalFilterForm : ($desktopFilterForm.length ? $desktopFilterForm : $mobileFilterForm);
            };

            const getProjectFormParams = function () {
                const $form = getActiveProjectForm();
                const params = {};
                if ($form.length) {
                    const formData = new FormData($form[0]);
                    formData.forEach(function (v, k) {
                        if (v !== "" && v !== null) params[k] = v;
                    });
                }
                return params;
            };

            // Seed initial state into FilterStateStore
            const initialProjectParams = getProjectFormParams();
            FilterStateStore.init(initialProjectParams);
            DesktopAdapter.init();
            MobileAdapter.init();
            ChipsAdapter.renderFromState(FilterStateStore.getAll());

            if (window.history && window.history.replaceState && !window.history.state) {
                window.history.replaceState({
                    type: "projects_filter",
                    url: window.location.href,
                    params: initialProjectParams
                }, document.title, window.location.href);
            }

            const handleAjaxFilter = function ($filterForm, paged, pushHistory) {
                if (typeof paged === "undefined") paged = 1;
                if (typeof pushHistory === "undefined") pushHistory = true;

                // 1. Abort existing in-flight request
                if (projectAbortController) {
                    try {
                        projectAbortController.abort();
                    } catch (e) {}
                    projectAbortController = null;
                }
                if (typeof AbortController !== "undefined") {
                    projectAbortController = new AbortController();
                }

                // 2. Monotonic transaction sequence ID
                projectTxId++;
                const currentTxId = projectTxId;

                let formData;
                if ($filterForm && $filterForm.length) {
                    formData = new FormData($filterForm[0]);
                } else {
                    formData = new FormData();
                }
                formData.append("action", "batdongsan_filter_projects");
                formData.append("nonce", bdsTheme.filterNonce || "");
                if (paged) {
                    formData.set("paged", paged);
                }

                $projectsGrid.addClass("is-loading").css("opacity", "0.5");

                $.ajax({
                    url: bdsTheme.ajaxUrl,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhrFields: projectAbortController ? { signal: projectAbortController.signal } : {},
                    success: function (response) {
                        // Drop stale response
                        if (currentTxId !== projectTxId) return;

                        if (!response || !response.success) {
                            console.warn("Không thể lọc dự án qua AJAX", response);
                            return;
                        }

                        if (typeof response.data.html === "string") {
                            $projectsGrid.html(response.data.html);
                        }

                        if (response.data.title && $projectsTitle.length) {
                            $projectsTitle.text(response.data.title);
                            document.title = response.data.title;
                        }

                        if ($("#project-listings-count strong").length) {
                            const total = response.data.total !== undefined ? response.data.total : response.data.found_posts;
                            if (total !== undefined) {
                                $("#project-listings-count strong").text(total);
                            }
                        }

                        if ($projectsPagination.length && typeof response.data.pagination === "string") {
                            $projectsPagination.html(response.data.pagination);
                        }

                        const $searchResults = $projectsSection.find(
                            ".page-content__search-results"
                        );
                        if (response.data.searchResults && $searchResults.length) {
                            $searchResults.replaceWith(response.data.searchResults);
                        } else if (response.data.searchResults) {
                            const $pageContent = $projectsSection.find(".page-content");
                            if ($pageContent.length) {
                                const $titleElement = $pageContent.find(".section__title");
                                if ($titleElement.length) {
                                    $titleElement.after(response.data.searchResults);
                                }
                            }
                        }

                        // Update Active Filter Chips for Projects
                        if (response.data.active_filters !== undefined) {
                            ChipsAdapter.render(response.data.active_filters);
                        } else {
                            ChipsAdapter.renderFromState(FilterStateStore.getAll());
                        }

                        const targetUrl = response.data.url || response.data.pretty_url;
                        if (pushHistory && !isProjectsPoppingState && targetUrl && window.history && window.history.pushState) {
                            const formObj = {};
                            formData.forEach(function (v, k) { formObj[k] = v; });
                            window.history.pushState({ type: "projects_filter", url: targetUrl, params: formObj }, response.data.title || document.title, targetUrl);
                        }

                        // Scroll đến phần kết quả sau khi filter
                        // Scroll đến phần kết quả sau khi filter (nếu người dùng đang ở xa phía dưới)
                        const scrollAnchor = document.querySelector("#project-listings-container") || ($projectsSection.length ? $projectsSection[0] : null);
                        if (scrollAnchor && (window.scrollY || window.pageYOffset || 0) > scrollAnchor.offsetTop) {
                            scrollAnchor.scrollIntoView({ behavior: "smooth", block: "start" });
                        }
                    },
                    error: function (xhr, status, error) {
                        if (status === "abort") return;
                        if (currentTxId !== projectTxId) return;
                        console.error("Lỗi AJAX filter dự án:", error);
                    },
                    complete: function () {
                        if (currentTxId === projectTxId) {
                            $projectsGrid.removeClass("is-loading").css("opacity", "1");
                        }
                    },
                });
            };

            // Reset all filters function for projects
            const resetAllProjectsFilters = function () {
                FilterStateStore.reset({ source: "reset", triggerFetch: false });
                const $activeForm = getActiveProjectForm();
                if ($activeForm.length && $activeForm[0].reset) {
                    $activeForm[0].reset();
                }
                DesktopAdapter.render(FilterStateStore.getAll());
                MobileAdapter.render(FilterStateStore.getAll());
                ChipsAdapter.render([]);
                handleAjaxFilter($activeForm, 1, true);
            };

            // Reset button click in horizontal filter bar on Project page
            $horizontalFilterForm.closest(".bg-white").find("a[aria-label='Đặt lại bộ lọc']").off("click.projReset").on("click.projReset", function (e) {
                e.preventDefault();
                resetAllProjectsFilters();
            });

            // Clear all filters click handler on Project page
            $doc.off("click.projClearAll", "#project-listings-container [data-clear-all-filters], .section--projects [data-clear-all-filters]").on("click.projClearAll", "#project-listings-container [data-clear-all-filters], .section--projects [data-clear-all-filters]", function (e) {
                e.preventDefault();
                resetAllProjectsFilters();
            });

            // 1-Click filter chip removal for Project page
            $doc.off("click.projRemoveChip", "#project-listings-container [data-remove-filter], .section--projects [data-remove-filter]").on("click.projRemoveChip", "#project-listings-container [data-remove-filter], .section--projects [data-remove-filter]", function (e) {
                e.preventDefault();
                const key = $(this).attr("data-remove-filter");
                if (!key) return;
                const defaultVal = (key === "type" || key === "status" || key === "du_an") ? "0" : "";
                FilterStateStore.set(key, defaultVal, { source: "chips", triggerFetch: false });
                DesktopAdapter.render(FilterStateStore.getAll());
                MobileAdapter.render(FilterStateStore.getAll());
                ChipsAdapter.renderFromState(FilterStateStore.getAll());
                const $activeForm = getActiveProjectForm();
                handleAjaxFilter($activeForm, 1, true);
            });

            // Xử lý form mobile
            if ($mobileFilterForm.length) {
                $mobileFilterForm
                    .find(".projects-filter-bar__filter-select")
                    .on("change", function (e) {
                        e.preventDefault();
                        handleAjaxFilter($mobileFilterForm, 1, true);
                    });

                $mobileFilterForm.on("submit", function (e) {
                    e.preventDefault();
                    handleAjaxFilter($mobileFilterForm, 1, true);
                });
            }

            // Xử lý form desktop
            if ($desktopFilterForm.length) {
                $desktopFilterForm.find(".projects-filter-bar__select, .projects-filter-bar__filter-select").on("change", function (e) {
                    e.preventDefault();
                    handleAjaxFilter($desktopFilterForm, 1, true);
                });

                $desktopFilterForm.on("submit", function (e) {
                    e.preventDefault();
                    handleAjaxFilter($desktopFilterForm, 1, true);
                });
            }

            // Xử lý form horizontal trên trang dự án
            if ($horizontalFilterForm.length) {
                $horizontalFilterForm.on("submit", function (e) {
                    e.preventDefault();
                    handleAjaxFilter($horizontalFilterForm, 1, true);
                });
            }

            // Pagination link clicks cho dự án
            $doc.on("click", "#project-listings-pagination a", function (e) {
                e.preventDefault();
                const href = $(this).attr("href");
                if (!href) return;
                let page = 1;
                const pageMatch = href.match(/\/page\/([0-9]+)/) || href.match(/[?&]paged=([0-9]+)/);
                if (pageMatch) {
                    page = parseInt(pageMatch[1], 10);
                }
                const $activeForm = getActiveProjectForm();
                handleAjaxFilter($activeForm, page, true);
            });

            // Popstate for projects
            window.addEventListener("popstate", function (e) {
                if (e.state && e.state.type === "projects_filter") {
                    const params = (e.state && e.state.params) ? $.extend({}, e.state.params) : {};
                    isProjectsPoppingState = true;
                    const fullParams = $.extend({}, FilterStateStore.getDefaults(), params);
                    FilterStateStore.setMultiple(fullParams, { source: "popstate", triggerFetch: false });
                    DesktopAdapter.render(fullParams);
                    MobileAdapter.render(fullParams);
                    ChipsAdapter.renderFromState(FilterStateStore.getAll());
                    const $activeForm = getActiveProjectForm();
                    handleAjaxFilter($activeForm, params.paged || 1, false);
                    isProjectsPoppingState = false;
                } else if ($("#project-listings-grid").length) {
                    const url = new URL(window.location.href);
                    const params = {};
                    url.searchParams.forEach(function (v, k) { params[k] = v; });
                    isProjectsPoppingState = true;
                    const fullParams = $.extend({}, FilterStateStore.getDefaults(), params);
                    FilterStateStore.setMultiple(fullParams, { source: "popstate", triggerFetch: false });
                    DesktopAdapter.render(fullParams);
                    MobileAdapter.render(fullParams);
                    ChipsAdapter.renderFromState(FilterStateStore.getAll());
                    const $activeForm = getActiveProjectForm();
                    handleAjaxFilter($activeForm, params.paged || 1, false);
                    isProjectsPoppingState = false;
                }
            });
        };

        /**
         * AJAX filter cho trang /bat-dong-san (F12, F14, F15)
         * Hỗ trợ bộ lọc ngang, transaction tabs, khoảng giá, diện tích, phân trang,
         * browser history sync (pushState / popstate).
         */
        const initListingsAjaxFilters = function () {
            if (typeof bdsTheme === "undefined" || !bdsTheme.ajaxUrl) {
                return;
            }

            const $container = $("#bds-listings-container");
            const $grid = $("#bds-listings-grid");
            const $pagination = $("#bds-listings-pagination");
            const $count = $("#bds-listings-count");
            const $filterForm = $(".filter-bar-horizontal__form");

            if (!$grid.length || !$filterForm.length) {
                return;
            }

            const getActiveFilterForm = function () {
                const $visible = $(".filter-bar-horizontal__form:visible");
                if ($visible.length) {
                    return $visible.first();
                }
                return $(".filter-bar-horizontal__form").first();
            };

            const getFormParams = function () {
                const $form = getActiveFilterForm();
                if (!$form.length) return {};
                const formData = new FormData($form[0]);
                const params = {};
                formData.forEach(function (value, key) {
                    if (value !== "" && value !== null) {
                        params[key] = value;
                    }
                });

                // Lấy thêm order từ dropdown sắp xếp nếu có
                const sortOrder = $(".sort-form select[name='order']").val();
                if (sortOrder && sortOrder !== "1") {
                    params["order"] = sortOrder;
                }

                return params;
            };

            // Đồng bộ 2 chiều giữa mobile form và desktop form (Bug 1)
            $doc.on("input change", ".filter-bar-horizontal__form input, .filter-bar-horizontal__form select", function () {
                const inputName = $(this).attr("name");
                if (!inputName) return;
                const inputVal = $(this).val();
                $(".filter-bar-horizontal__form").not($(this).closest("form")).each(function () {
                    const $peerInput = $(this).find("[name='" + inputName + "']");
                    if ($peerInput.length && $peerInput.val() !== inputVal) {
                        $peerInput.val(inputVal);
                    }
                });
            });

            let listingsAbortController = null;
            let listingsTxId = 0;
            let isListingsPoppingState = false;

            // In-memory filter cache for 0ms instantaneous tab & filter response
            const listingsCache = new Map();
            const getCacheKey = function (p) {
                if (!p || typeof p !== "object") return "";
                const clean = {};
                Object.keys(p).sort().forEach(function (k) {
                    if (p[k] !== "" && p[k] !== null && p[k] !== undefined && k !== "nonce" && k !== "action") {
                        clean[k] = String(p[k]);
                    }
                });
                return JSON.stringify(clean);
            };

            // Seed initial state into FilterStateStore and seed replaceState
            const initialListingsParams = getFormParams();
            FilterStateStore.setMultiple(initialListingsParams, { source: "initial", triggerFetch: false });
            if (window.history && window.history.replaceState && !window.history.state) {
                window.history.replaceState(
                    { type: "bds_filter", url: window.location.href, params: initialListingsParams },
                    document.title,
                    window.location.href
                );
            }

            // Seed initial page content into cache
            const initialKey = getCacheKey(initialListingsParams);
            listingsCache.set(initialKey, {
                success: true,
                data: {
                    html: $grid.html(),
                    pagination: $pagination.html(),
                    total: parseInt($count.find("strong").text(), 10) || 0,
                    title: document.title,
                    heading_title: $("#bds-page-title").text(),
                    url: window.location.href,
                    active_filters: ChipsAdapter.computeChips(FilterStateStore.getAll())
                }
            });

            // Fast-render listings response (used by both cache hits & fresh AJAX)
            const renderListingsResult = function (response, params, pushHistory) {
                if (typeof response.data.html === "string") {
                    $grid.html(response.data.html);
                }

                if (typeof response.data.pagination === "string") {
                    $pagination.html(response.data.pagination);
                }

                const totalCount = response.data.total !== undefined ? response.data.total : response.data.found_posts;
                if (totalCount !== undefined && $count.length) {
                    $count.find("strong").text(totalCount);
                }

                if (response.data.active_filters !== undefined) {
                    renderActiveFilterChips(response.data.active_filters);
                } else {
                    renderActiveFilterChips(ChipsAdapter.computeChips(FilterStateStore.getAll()));
                }

                if (response.data.title && typeof response.data.title === "string") {
                    document.title = response.data.title;
                }

                if (response.data.heading_title && typeof response.data.heading_title === "string") {
                    $("#bds-page-title").text(response.data.heading_title);
                } else if (response.data.title && typeof response.data.title === "string") {
                    const headingPart = response.data.title.split(" | ")[0];
                    if (headingPart) {
                        $("#bds-page-title").text(headingPart);
                    }
                }

                if (pushHistory && !isListingsPoppingState && response.data.url && window.history && window.history.pushState) {
                    window.history.pushState(
                        { type: "bds_filter", url: response.data.url, params: params },
                        response.data.title || document.title,
                        response.data.url
                    );
                }

                // Cuộn mượt tới đầu danh sách chỉ khi cuộn vượt quá container
                if ($container.length && (window.scrollY || window.pageYOffset || 0) > $container.offset().top + 100) {
                    $container[0].scrollIntoView({ behavior: "smooth", block: "start" });
                }
            };

            const fetchListings = function (params, pushHistory) {
                if (typeof pushHistory === "undefined") pushHistory = true;

                const cacheKey = getCacheKey(params);
                if (listingsCache.has(cacheKey)) {
                    const cachedResponse = listingsCache.get(cacheKey);
                    renderListingsResult(cachedResponse, params, pushHistory);
                    return;
                }

                if (listingsAbortController) {
                    try { listingsAbortController.abort(); } catch (e) { }
                }
                if (typeof AbortController !== "undefined") {
                    listingsAbortController = new AbortController();
                }
                listingsTxId++;
                const currentTxId = listingsTxId;

                $grid.addClass("is-loading pointer-events-none transition-opacity duration-150").css("opacity", "0.6");

                const ajaxData = $.extend({
                    action: "batdongsan_filter_listings",
                    nonce: bdsTheme.bdsFilterNonce || "",
                }, params);

                $.ajax({
                    url: bdsTheme.ajaxUrl,
                    type: "POST",
                    data: ajaxData,
                    xhrFields: listingsAbortController ? { signal: listingsAbortController.signal } : {},
                    success: function (response) {
                        if (currentTxId !== listingsTxId) return;

                        if (!response || !response.success) {
                            console.warn("Không thể lọc bất động sản qua AJAX", response);
                            return;
                        }

                        // Lưu vào client-side in-memory cache
                        listingsCache.set(cacheKey, response);

                        renderListingsResult(response, ajaxData, pushHistory);
                    },
                    error: function (xhr, status, error) {
                        if (status === "abort") return;
                        if (currentTxId !== listingsTxId) return;
                        console.error("Lỗi AJAX lọc BĐS:", error);
                        if ($grid.children().length === 0) {
                            $grid.html('<div class="text-center py-16 px-5 bg-surface rounded-2xl border border-border"><p class="text-charcoal-muted text-sm mb-4">Không thể kết nối đến máy chủ. Vui lòng thử lại sau.</p><button type="button" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-hover transition" onclick="window.location.reload()">Tải lại trang</button></div>');
                        }
                    },
                    complete: function () {
                        if (currentTxId === listingsTxId) {
                            $grid.removeClass("is-loading pointer-events-none").css("opacity", "1");
                        }
                    },
                });
            };

            // Prefetch ngầm các tab phổ biến sau khi trang đã render ổn định
            setTimeout(function () {
                const tabsToPrefetch = ["chuyen-nhuong", "cho-thue", "mua-ban", "so-cap", "trung-cap", "cao-cap", ""];
                tabsToPrefetch.forEach(function (tabSlug) {
                    const tabParams = $.extend({}, initialListingsParams, { hinh_thuc: tabSlug, paged: 1 });
                    const tKey = getCacheKey(tabParams);
                    if (!listingsCache.has(tKey)) {
                        $.ajax({
                            url: bdsTheme.ajaxUrl,
                            type: "POST",
                            data: $.extend({ action: "batdongsan_filter_listings", nonce: bdsTheme.bdsFilterNonce || "" }, tabParams),
                            success: function (res) {
                                if (res && res.success) {
                                    listingsCache.set(tKey, res);
                                }
                            }
                        });
                    }
                });
            }, 600);

            // Hover-intent prefetch: Khi rê chuột vào tab nào, prefetch ngay tức thì
            $doc.on("mouseenter", "#bds-transaction-tabs [data-bds-tab]", function () {
                const tabVal = $(this).attr("data-bds-tab") || "";
                const tabParams = $.extend({}, getFormParams(), { hinh_thuc: tabVal, paged: 1 });
                const tKey = getCacheKey(tabParams);
                if (!listingsCache.has(tKey)) {
                    $.ajax({
                        url: bdsTheme.ajaxUrl,
                        type: "POST",
                        data: $.extend({ action: "batdongsan_filter_listings", nonce: bdsTheme.bdsFilterNonce || "" }, tabParams),
                        success: function (res) {
                            if (res && res.success) {
                                listingsCache.set(tKey, res);
                            }
                        }
                    });
                }
            });

            const $activeFilters = $("#bds-active-filters");
            const renderActiveFilterChips = function (filters) {
                ChipsAdapter.render(filters);
            };

            // Intercept form submit
            $filterForm.on("submit", function (e) {
                e.preventDefault();
                const params = getFormParams();
                params["paged"] = 1;
                fetchListings(params, true);
            });

            // Intercept search input Enter key
            $filterForm.find("input[name='project-name']").on("keypress", function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $filterForm.trigger("submit");
                }
            });

            // Transaction tabs click
            $doc.on("click", "#bds-transaction-tabs [data-bds-tab]", function (e) {
                e.preventDefault();
                const tabVal = $(this).attr("data-bds-tab") || "";

                // 1. Cập nhật FilterStateStore
                if (window.FilterStateStore) {
                    FilterStateStore.set("hinh_thuc", tabVal, { source: "tabs", triggerFetch: false });
                }

                // 2. Cập nhật tất cả input hidden trong các form (desktop & mobile)
                $(".filter-bar-horizontal__form").each(function () {
                    let $hinhThucInput = $(this).find("input[name='hinh_thuc']");
                    if (!$hinhThucInput.length) {
                        $hinhThucInput = $("<input>", { type: "hidden", name: "hinh_thuc" }).appendTo($(this));
                    }
                    $hinhThucInput.val(tabVal);
                    $(this).find("[data-filter-hinh-thuc-hidden]").val(tabVal);
                });

                // 3. Cập nhật styling các tabs
                $("#bds-transaction-tabs [data-bds-tab]").each(function () {
                    const thisTabVal = $(this).attr("data-bds-tab") || "";
                    if (thisTabVal === tabVal) {
                        $(this).removeClass("bg-gray-100 text-gray-700 hover:bg-gray-200").addClass("bg-primary text-white shadow-button");
                    } else {
                        $(this).removeClass("bg-[#b20000] bg-primary text-white shadow-button").addClass("bg-gray-100 text-gray-700 hover:bg-gray-200");
                    }
                });

                // 4. Phản hồi tiêu đề trang tức thì
                const htHeadings = {
                    "": "Bất động sản",
                    "chuyen-nhuong": "Hàng chuyển nhượng",
                    "cho-thue": "Bất động sản cho thuê",
                    "mua-ban": "Bất động sản mua bán",
                    "so-cap": "Bất động sản sơ cấp",
                    "trung-cap": "Bất động sản trung cấp",
                    "cao-cap": "Bất động sản cao cấp"
                };
                if (htHeadings[tabVal] !== undefined) {
                    $("#bds-page-title").text(htHeadings[tabVal]);
                }

                const params = getFormParams();
                params["hinh_thuc"] = tabVal;
                params["paged"] = 1;
                fetchListings(params, true);
            });

            // Sort dropdown change trên trang BĐS
            $doc.on("change", ".sort-form select[name='order']", function (e) {
                e.preventDefault();
                const params = getFormParams();
                params["order"] = $(this).val();
                params["paged"] = 1;
                fetchListings(params, true);
            });

            // Pagination link clicks
            $doc.on("click", "#bds-listings-pagination a", function (e) {
                e.preventDefault();
                const pageAttr = $(this).data("page") || $(this).attr("data-page");
                let page = 1;
                if (pageAttr) {
                    page = parseInt(pageAttr, 10);
                } else {
                    const href = $(this).attr("href");
                    if (href) {
                        const pageMatch = href.match(/\/page\/([0-9]+)/) || href.match(/[?&]paged=([0-9]+)/);
                        if (pageMatch) {
                            page = parseInt(pageMatch[1], 10);
                        }
                    }
                }

                const params = getFormParams();
                params["paged"] = page;
                fetchListings(params, true);
            });

            // Remove specific filter chip
            $doc.on("click", "[data-remove-filter]", function (e) {
                e.preventDefault();
                const key = $(this).attr("data-remove-filter");
                if (!key) return;

                const defaultVal = (key === "type" || key === "status" || key === "du_an") ? "0" : "";
                FilterStateStore.set(key, defaultVal, { source: "chips", triggerFetch: false });

                if (key === "hinh_thuc") {
                    $filterForm.find("input[name='hinh_thuc'], [data-filter-hinh-thuc-hidden]").val("");
                    $("#bds-transaction-tabs [data-bds-tab]").each(function () {
                        if (!$(this).attr("data-bds-tab")) {
                            $(this).removeClass("bg-gray-100 text-gray-700 hover:bg-gray-200").addClass("bg-primary text-white shadow-button");
                        } else {
                            $(this).removeClass("bg-[#b20000] bg-primary text-white shadow-button").addClass("bg-gray-100 text-gray-700 hover:bg-gray-200");
                        }
                    });
                    $("#bds-page-title").text("Bất động sản");
                } else if (key === "khu_vuc") {
                    $filterForm.find("input[name='khu_vuc']").val("");
                    $("[data-filter-area-trigger] span").last().text("Toàn Hà Nội");
                    $("[data-filter-area-option]").removeClass("text-primary font-semibold");
                } else if (key === "type") {
                    $filterForm.find("input[name='type']").val("0");
                    $("[data-filter-type-trigger] span").last().text("Tất cả loại hình");
                    $("[data-filter-type-option]").removeClass("text-primary font-semibold");
                } else if (key === "price") {
                    $filterForm.find("input[name='price']").val("");
                    $("[data-filter-price-trigger] span").last().text("Tất cả khoảng giá");
                    $("[data-filter-price-preset-label]").removeClass("bg-red-50 bg-primary-light text-primary font-medium");
                } else if (key === "area") {
                    $filterForm.find("input[name='area']").val("");
                    $("[data-filter-area-range-trigger] span").last().text("Tất cả diện tích");
                    $("[data-filter-area-range-preset-label]").removeClass("bg-red-50 bg-primary-light text-primary font-medium");
                } else if (key === "project-name") {
                    $filterForm.find("input[name='project-name']").val("");
                } else if (key === "du_an") {
                    $filterForm.find("input[name='du_an']").val("0");
                } else if (key === "so_phong_ngu") {
                    $filterForm.find("input[name='so_phong_ngu'], select[name='so_phong_ngu']").val("");
                } else if (key === "so_phong_ve_sinh") {
                    $filterForm.find("input[name='so_phong_ve_sinh'], select[name='so_phong_ve_sinh']").val("");
                } else if (key === "huong_nha") {
                    $filterForm.find("input[name='huong_nha'], select[name='huong_nha']").val("");
                } else if (key === "phap_ly") {
                    $filterForm.find("input[name='phap_ly'], select[name='phap_ly']").val("");
                }

                DesktopAdapter.render(FilterStateStore.getAll());
                MobileAdapter.render(FilterStateStore.getAll());
                ChipsAdapter.renderFromState(FilterStateStore.getAll());

                const params = getFormParams();
                params["paged"] = 1;
                fetchListings(params, true);
            });

            // Reset all filters function
            const resetAllListingsFilters = function () {
                FilterStateStore.reset({ source: "reset", triggerFetch: false });
                $filterForm.each(function () {
                    if (this.reset) this.reset();
                });
                $filterForm.find("input[type='hidden']").each(function () {
                    const name = $(this).attr("name");
                    if (name === "type" || name === "status" || name === "du_an") {
                        $(this).val("0");
                    } else {
                        $(this).val("");
                    }
                });
                $filterForm.find("input[name='project-name']").val("");
                ["so_phong_ngu", "so_phong_ve_sinh", "huong_nha", "phap_ly"].forEach(function (k) {
                    $filterForm.find("input[name='" + k + "'], select[name='" + k + "']").val("");
                });
                $("#bds-transaction-tabs [data-bds-tab]").each(function () {
                    if (!$(this).attr("data-bds-tab")) {
                        $(this).removeClass("bg-gray-100 text-gray-700 hover:bg-gray-200").addClass("bg-primary text-white shadow-button");
                    } else {
                        $(this).removeClass("bg-[#b20000] bg-primary text-white shadow-button").addClass("bg-gray-100 text-gray-700 hover:bg-gray-200");
                    }
                });
                $("#bds-page-title").text("Bất động sản");

                $("[data-filter-area-trigger] span").last().text("Toàn Hà Nội");
                $("[data-filter-type-trigger] span").last().text("Tất cả loại hình");
                $("[data-filter-price-trigger] span").last().text("Tất cả khoảng giá");
                $("[data-filter-area-range-trigger] span").last().text("Tất cả diện tích");
                $("[data-filter-status-trigger] span").last().text("Tất cả");

                $("[data-filter-area-option], [data-filter-type-option], [data-filter-status-option]").removeClass("text-primary font-semibold");
                $("[data-filter-price-preset-label], [data-filter-area-range-preset-label]").removeClass("bg-red-50 bg-primary-light text-primary font-medium");

                DesktopAdapter.render(FilterStateStore.getAll());
                MobileAdapter.render(FilterStateStore.getAll());

                renderActiveFilterChips([]);
                fetchListings({ paged: 1 }, true);
            };

            // Clear all filters click handler
            $doc.on("click", "[data-clear-all-filters]", function (e) {
                e.preventDefault();
                resetAllListingsFilters();
            });

            // Reset button click in horizontal filter bar
            $filterForm.closest(".bg-white").find("a[aria-label='Đặt lại bộ lọc']").on("click", function (e) {
                e.preventDefault();
                resetAllListingsFilters();
            });

            // Đồng bộ nhãn nút trigger dropdown khi popstate / reset (Bug 9)
            const syncDropdownLabels = function (params) {
                if (!params) params = {};

                // Khu vực
                const khuVucVal = params.khu_vuc || "";
                const $areaOpts = $("[data-filter-area-option]");
                $areaOpts.removeClass("text-primary font-semibold");
                let areaText = "Toàn Hà Nội";
                if (khuVucVal) {
                    const $match = $areaOpts.filter(function () {
                        return String($(this).data("value")) === String(khuVucVal);
                    }).first();
                    if ($match.length) {
                        $match.addClass("text-primary font-semibold");
                        areaText = $.trim($match.text());
                    }
                }
                $("[data-filter-area-trigger] span").last().text(areaText);

                // Loại hình
                const typeVal = String(params.type || "0");
                const $typeOpts = $("[data-filter-type-option]");
                $typeOpts.removeAttr("data-active").removeClass("text-primary font-semibold")
                    .find("span:first").removeClass("text-primary");
                let typeText = "Tất cả loại hình";
                if (typeVal && typeVal !== "0") {
                    const $match = $typeOpts.filter(function () {
                        return String($(this).data("value")) === typeVal;
                    }).first();
                    if ($match.length) {
                        $match.attr("data-active", "true").addClass("text-primary font-semibold");
                        $match.find("span").first().addClass("text-primary");
                        typeText = $.trim($match.find("span").last().text());
                    }
                }
                $("[data-filter-type-trigger] span").last().text(typeText);

                // Trạng thái
                const statusVal = String(params.status || "0");
                const $statusOpts = $("[data-filter-status-option]");
                $statusOpts.removeClass("text-primary font-semibold");
                let statusText = "Tất cả";
                if (statusVal && statusVal !== "0") {
                    const $match = $statusOpts.filter(function () {
                        return String($(this).data("value")) === statusVal;
                    }).first();
                    if ($match.length) {
                        $match.addClass("text-primary font-semibold");
                        statusText = $.trim($match.find("span").last().text());
                    }
                }
                $("[data-filter-status-trigger] span").last().text(statusText);

                // Khoảng giá
                const priceVal = params.price || "";
                const $pricePresets = $("[data-filter-price-preset-label]");
                $pricePresets.removeClass("bg-red-50 bg-primary-light text-primary font-medium");
                let priceText = "Tất cả khoảng giá";
                if (priceVal && priceVal !== "0") {
                    const $match = $pricePresets.filter(function () {
                        return String($(this).data("value")) === String(priceVal);
                    }).first();
                    if ($match.length) {
                        $match.addClass("bg-primary-light text-primary font-medium");
                        priceText = $.trim($match.text());
                    } else if (priceVal.indexOf("-") !== -1) {
                        const parts = priceVal.split("-");
                        const p0 = parseFloat(parts[0]) || 0;
                        const p1 = parseFloat(parts[1]) || 0;
                        const l0 = p0 >= 1000 ? (p0 / 1000) + " tỷ" : p0 + " triệu";
                        const l1 = p1 >= 1000 ? (p1 / 1000) + " tỷ" : p1 + " triệu";
                        priceText = l0 + " - " + l1;
                    } else {
                        const p0 = parseFloat(priceVal) || 0;
                        priceText = p0 >= 1000 ? "Từ " + (p0 / 1000) + " tỷ" : "Từ " + p0 + " triệu";
                    }
                }
                $("[data-filter-price-trigger] span").last().text(priceText);

                // Diện tích
                const areaVal = params.area || "";
                const $areaPresets = $("[data-filter-area-range-preset-label]");
                $areaPresets.removeClass("bg-red-50 bg-primary-light text-primary font-medium");
                let areaRangeText = "Tất cả diện tích";
                if (areaVal && areaVal !== "0") {
                    const $match = $areaPresets.filter(function () {
                        return String($(this).data("value")) === String(areaVal);
                    }).first();
                    if ($match.length) {
                        $match.addClass("bg-primary-light text-primary font-medium");
                        areaRangeText = $.trim($match.text());
                    } else if (areaVal.indexOf("-") !== -1) {
                        areaRangeText = areaVal + " m²";
                    } else {
                        areaRangeText = "Từ " + areaVal + " m²";
                    }
                }
                $("[data-filter-area-range-trigger] span").last().text(areaRangeText);
                DesktopAdapter.render(params);
                MobileAdapter.render(params);
            };

            // Helper: Chuyển đổi slug giá sang price key (vd: 'gia-duoi-2-ty' -> '0-2000', 'gia-tu-3-ty-den-5-ty' -> '3000-5000')
            const parsePriceSlug = function (slug) {
                if (!slug) return null;
                if (slug === "gia-thoa-thuan") return "thoa_thuan";

                const parseNum = function (str) {
                    if (!str) return 0;
                    if (str.indexOf(".") !== -1) return parseFloat(str);
                    const hm = str.match(/^(\d+)-(\d+)$/);
                    if (hm) return parseFloat(hm[1] + "." + hm[2]);
                    return parseFloat(str);
                };

                let m;
                if ((m = slug.match(/^gia-duoi-([\d\.\-]+)-ty$/))) {
                    return "0-" + (parseNum(m[1]) * 1000);
                }
                if ((m = slug.match(/^gia-duoi-([\d\.\-]+)-trieu$/))) {
                    return "0-" + parseNum(m[1]);
                }
                if ((m = slug.match(/^gia-tren-([\d\.\-]+)-ty$/))) {
                    return String(parseNum(m[1]) * 1000);
                }
                if ((m = slug.match(/^gia-tren-([\d\.\-]+)-trieu$/))) {
                    return String(parseNum(m[1]));
                }
                if ((m = slug.match(/^gia-tu-([\d\.\-]+)-ty-den-([\d\.\-]+)-ty$/))) {
                    return (parseNum(m[1]) * 1000) + "-" + (parseNum(m[2]) * 1000);
                }
                if ((m = slug.match(/^gia-tu-([\d\.\-]+)-trieu-den-([\d\.\-]+)-ty$/))) {
                    return parseNum(m[1]) + "-" + (parseNum(m[2]) * 1000);
                }
                if ((m = slug.match(/^gia-tu-([\d\.\-]+)-trieu-den-([\d\.\-]+)-trieu$/))) {
                    return parseNum(m[1]) + "-" + parseNum(m[2]);
                }
                return null;
            };

            // Helper: Chuyển đổi slug diện tích sang area key (vd: 'dt-tu-50m2-den-80m2' -> '50-80', 'dt-duoi-50m2' -> '0-50')
            const parseAreaSlug = function (slug) {
                if (!slug) return null;
                const norm = slug.replace(/^dien-tich-/, "dt-");
                let m;
                if ((m = norm.match(/^dt-duoi-([\d\.\-]+)m2$/))) {
                    return "0-" + m[1];
                }
                if ((m = norm.match(/^dt-tren-([\d\.\-]+)m2$/))) {
                    return String(m[1]);
                }
                if ((m = norm.match(/^dt-tu-([\d\.\-]+)m2-den-([\d\.\-]+)m2$/))) {
                    return m[1] + "-" + m[2];
                }
                return null;
            };

            // Helper: Parse filter slug đơn lẻ hoặc compound (gia-...-dt-... hoặc dt-...-gia-...)
            const parseFilterSlug = function (slug) {
                let price = null;
                let area = null;
                if (slug.indexOf("-dt-") !== -1) {
                    const idx = slug.search(/-(?=dt-(?:tu|duoi|tren))/);
                    if (idx !== -1) {
                        price = parsePriceSlug(slug.substring(0, idx));
                        area  = parseAreaSlug(slug.substring(idx + 1));
                    }
                } else if (slug.indexOf("-gia-") !== -1) {
                    const idx = slug.search(/-(?=gia-(?:tu|duoi|tren|thoa))/);
                    if (idx !== -1) {
                        area  = parseAreaSlug(slug.substring(0, idx));
                        price = parsePriceSlug(slug.substring(idx + 1));
                    }
                } else if (slug.indexOf("gia-") === 0) {
                    price = parsePriceSlug(slug);
                } else if (slug.indexOf("dt-") === 0) {
                    area = parseAreaSlug(slug);
                }
                return { price: price, area: area };
            };

            // Helper: Parse toàn diện URL (path + searchParams) thành đối tượng params
            const parseBdsUrlToParams = function (urlObj) {
                const params = {};
                if (!urlObj) return params;

                // 1. Đọc search parameters (GET query string)
                urlObj.searchParams.forEach(function (v, k) {
                    params[k] = v;
                });

                // 2. Phân tách và giải mã các segment của pathname
                const path = urlObj.pathname.replace(/^\/|\/$/g, "");
                const parts = path ? path.split("/") : [];

                for (let i = 0; i < parts.length; i++) {
                    const seg = parts[i];
                    if (!seg || seg === "bat-dong-san") continue;

                    // Phân trang: /page/{num}/
                    if (seg === "page" && parts[i + 1]) {
                        params.paged = parseInt(parts[i + 1], 10);
                        i++;
                        continue;
                    }

                    // Dự án slug: /du-an-{slug}/
                    const projectMatch = seg.match(/^du-an-([a-z0-9-]+)$/);
                    if (projectMatch) {
                        params.du_an_slug = projectMatch[1];
                        continue;
                    }

                    // Khu vực độc lập: /bat-dong-san-quan-{khu_vuc}/
                    const districtOnlyMatch = seg.match(/^bat-dong-san-quan-([a-z0-9-]+)$/);
                    if (districtOnlyMatch) {
                        params.khu_vuc = districtOnlyMatch[1];
                        continue;
                    }

                    // Loại hình + Khu vực: /{type}-quan-{khu_vuc}/
                    const typeDistrictMatch = seg.match(/^([a-z0-9-]+)-quan-([a-z0-9-]+)$/);
                    if (typeDistrictMatch) {
                        params.type    = typeDistrictMatch[1];
                        params.khu_vuc = typeDistrictMatch[2];
                        continue;
                    }

                    // Dạng bán: /ban-{type}-dien-tich-xxx-gia-xxx/
                    if (seg.indexOf("ban-") === 0) {
                        let rest = seg.substring(4);
                        const pMatch = rest.match(/-(gia-(?:tu|duoi|tren|thoa)[a-z0-9-]+)$/);
                        if (pMatch) {
                            params.price = parsePriceSlug(pMatch[1]);
                            rest = rest.substring(0, rest.length - pMatch[1].length - 1);
                        }
                        const aMatch = rest.match(/-(dien-tich-(?:tu|duoi|tren)[a-z0-9-]+)$/);
                        if (aMatch) {
                            params.area = parseAreaSlug(aMatch[1]);
                            rest = rest.substring(0, rest.length - aMatch[1].length - 1);
                        }
                        if (rest) {
                            params.type = rest;
                        }
                        continue;
                    }

                    // Filter slug (giá, diện tích, hoặc compound)
                    const filterData = parseFilterSlug(seg);
                    if (filterData.price !== null) {
                        params.price = filterData.price;
                    }
                    if (filterData.area !== null) {
                        params.area = filterData.area;
                    }
                }

                return params;
            };

            // Browser History Back/Forward (popstate) (Bug 9)
            window.addEventListener("popstate", function (e) {
                let params = {};
                if (e.state && e.state.type === "bds_filter" && e.state.params) {
                    params = $.extend({}, e.state.params);
                } else {
                    const url = new URL(window.location.href);
                    params = parseBdsUrlToParams(url);
                }

                isListingsPoppingState = true;
                const fullParams = $.extend({}, FilterStateStore.getDefaults(), params);
                FilterStateStore.setMultiple(fullParams, { source: "popstate", triggerFetch: false });

                // Cập nhật tất cả input form (desktop và mobile)
                const $forms = $(".filter-bar-horizontal__form");
                ["hinh_thuc", "type", "khu_vuc", "price", "area", "project-name", "so_phong_ngu", "so_phong_ve_sinh", "huong_nha", "phap_ly"].forEach(function (k) {
                    const val = fullParams[k] !== undefined ? fullParams[k] : (k === "type" ? "0" : "");
                    $forms.find("input[name='" + k + "'], select[name='" + k + "']").val(val);
                });

                if (params.hinh_thuc !== undefined) {
                    const htVal = params.hinh_thuc || "";
                    $("#bds-transaction-tabs [data-bds-tab]").each(function () {
                        if ($(this).attr("data-bds-tab") === htVal) {
                            $(this).removeClass("bg-gray-100 text-gray-700 hover:bg-gray-200").addClass("bg-primary text-white shadow-button");
                        } else {
                            $(this).removeClass("bg-[#b20000] bg-primary text-white shadow-button").addClass("bg-gray-100 text-gray-700 hover:bg-gray-200");
                        }
                    });
                }

                // Cập nhật nhãn dropdown trigger buttons (Bug 9)
                syncDropdownLabels(params);
                ChipsAdapter.renderFromState(FilterStateStore.getAll());

                fetchListings(params, false);
                isListingsPoppingState = false;
            });
        };

        if (pageContext === "home" || $("#project-listings-grid").length || $(".section--projects").length) {
            initProjectsAjaxFilters();
        }

        if (pageContext === "bds" || $("#bds-listings-grid").length) {
            initListingsAjaxFilters();
        }

        // Toggle mô tả dự án - chỉ trang chi tiết dự án
        if (pageContext === "project-detail") {
            $(".project-detail__toggle").on("click", function () {
                const targetId = $(this).data("target");
                if (!targetId) return;
                const $container = $("#" + targetId);
                if (!$container.length) return;

                const isExpanded = $container.hasClass("is-expanded");

                if (isExpanded) {
                    $container.removeClass("is-expanded").addClass("is-collapsed");
                    $(this).attr("aria-expanded", "false");
                    $(this).find(".project-detail__toggle-label").text("Xem thêm");
                } else {
                    $container.removeClass("is-collapsed").addClass("is-expanded");
                    $(this).attr("aria-expanded", "true");
                    $(this).find(".project-detail__toggle-label").text("Thu gọn");
                }
            });
        }

        // Helper khởi tạo Swiper an toàn và dùng chung (giảm trùng lặp boilerplate)
        const createSwiper = function (selector, options) {
            const $el = $(selector);
            if (!$el.length || typeof Swiper === "undefined") return null;
            return new Swiper($el[0], options);
        };

        /**
         * Native CSS Scroll-Snap Carousel Navigation & State
         * Tối ưu hiệu năng: dùng native scrollBy + CSS scroll-snap thay thế Swiper cồng kềnh.
         */
        const initNativeCarousels = function () {
            const updateCarouselNav = function (carousel) {
                if (!carousel || !carousel.id) return;
                const id = "#" + carousel.id;
                const prevBtn = document.querySelector(`[data-carousel-prev="${id}"]`);
                const nextBtn = document.querySelector(`[data-carousel-next="${id}"]`);
                if (prevBtn) {
                    prevBtn.disabled = carousel.scrollLeft <= 4;
                }
                if (nextBtn) {
                    const maxScroll = carousel.scrollWidth - carousel.clientWidth - 4;
                    nextBtn.disabled = carousel.scrollLeft >= maxScroll;
                }
            };

            $(document).on("click", "[data-carousel-prev], [data-carousel-next]", function (e) {
                e.preventDefault();
                const $btn = $(this);
                const targetSelector = $btn.attr("data-carousel-prev") || $btn.attr("data-carousel-next");
                if (!targetSelector) return;
                const container = document.querySelector(targetSelector);
                if (!container) return;

                const isNext = $btn.is("[data-carousel-next]");
                const firstChild = container.firstElementChild;
                const scrollAmount = firstChild ? (firstChild.offsetWidth + 16) : Math.round(container.clientWidth * 0.8);

                container.scrollBy({
                    left: isNext ? scrollAmount : -scrollAmount,
                    behavior: "smooth"
                });
            });

            const carousels = document.querySelectorAll(".bds-carousel");
            carousels.forEach(function (carousel) {
                updateCarouselNav(carousel);
                let ticking = false;
                carousel.addEventListener("scroll", function () {
                    if (!ticking) {
                        requestAnimationFrame(function () {
                            updateCarouselNav(carousel);
                            ticking = false;
                        });
                        ticking = true;
                    }
                }, { passive: true });
            });

            window.addEventListener("resize", function () {
                carousels.forEach(updateCarouselNav);
            }, { passive: true });
        };

        // Swiper cho hero banner (LayerSlider Motion Engine)
        const initHeroSwiper = function () {
            const $heroEl = $(".hero-swiper");
            if (!$heroEl.length || typeof Swiper === "undefined") return;

            const triggerHeroProgress = function () {
                const $progressBar = $heroEl.closest(".hero-layerslider-container").find(".hero-slider-progress-bar");
                if (!$progressBar.length) return;
                $progressBar.removeClass("is-active");
                void $progressBar[0].offsetWidth;
                $progressBar.addClass("is-active");
            };

            createSwiper(".hero-swiper", {
                slidesPerView: 1,
                spaceBetween: 0,
                loop: true,
                parallax: true,
                watchSlidesProgress: true,
                autoplay: {
                    delay: 6000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true,
                },
                effect: "fade",
                fadeEffect: { crossFade: true },
                speed: 1000,
                navigation: {
                    nextEl: ".hero-swiper__nav--next",
                    prevEl: ".hero-swiper__nav--prev",
                },
                pagination: {
                    el: ".hero-swiper__pagination",
                    clickable: true,
                    dynamicBullets: false,
                },
                on: {
                    init: function () {
                        triggerHeroProgress();
                    },
                    slideChangeTransitionStart: function () {
                        triggerHeroProgress();
                    },
                },
            });
        };

        // Swiper cho hero projects banner (LayerSlider Motion Engine)
        const initHeroProjectsSwiper = function () {
            const $heroEl = $(".hero-projects-swiper");
            if (!$heroEl.length || typeof Swiper === "undefined") return;

            const triggerHeroProgress = function () {
                const $progressBar = $heroEl.closest(".hero-layerslider-container").find(".hero-slider-progress-bar");
                if (!$progressBar.length) return;
                $progressBar.removeClass("is-active");
                void $progressBar[0].offsetWidth;
                $progressBar.addClass("is-active");
            };

            createSwiper(".hero-projects-swiper", {
                slidesPerView: 1,
                spaceBetween: 0,
                loop: true,
                parallax: true,
                watchSlidesProgress: true,
                autoplay: {
                    delay: 6000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true,
                },
                effect: "fade",
                fadeEffect: { crossFade: true },
                speed: 1000,
                navigation: {
                    nextEl: ".hero-projects-swiper__nav--next",
                    prevEl: ".hero-projects-swiper__nav--prev",
                },
                pagination: {
                    el: ".hero-projects-swiper__pagination",
                    clickable: true,
                    dynamicBullets: false,
                },
                on: {
                    init: function () {
                        triggerHeroProgress();
                    },
                    slideChangeTransitionStart: function () {
                        triggerHeroProgress();
                    },
                },
            });
        };

        // Swiper cho section đối tác
        const initPartnersSwiper = function () {
            createSwiper(".partners-swiper", {
                slidesPerView: 2,
                spaceBetween: 20,
                loop: true,
                autoplay: {
                    delay: 3000,
                    disableOnInteraction: false,
                },
                breakpoints: {
                    640: { slidesPerView: 3, spaceBetween: 24 },
                    768: { slidesPerView: 4, spaceBetween: 24 },
                    1024: { slidesPerView: 5, spaceBetween: 30 },
                    1280: { slidesPerView: 6, spaceBetween: 30 },
                },
                navigation: {
                    nextEl: ".partners-swiper__nav--next",
                    prevEl: ".partners-swiper__nav--prev",
                },
                pagination: {
                    el: ".partners-swiper__pagination",
                    clickable: true,
                    dynamicBullets: true,
                },
            });
        };

        // Swiper cho Video Review ở Sidebar dự án (1 item/slide)
        const initSidebarVideoSwiper = function () {
            createSwiper(".sidebar-video-swiper", {
                slidesPerView: 1,
                spaceBetween: 12,
                watchOverflow: true,
                speed: 400,
                navigation: {
                    nextEl: ".sidebar-video-swiper__next",
                    prevEl: ".sidebar-video-swiper__prev",
                },
                pagination: {
                    el: ".sidebar-video-swiper__pagination",
                    clickable: true,
                    dynamicBullets: true,
                },
            });
        };

        /**
         * Swiper cho gallery preview trong trang chi tiết nhà đất bán + lightGallery (jQuery)
         */
        const initNhaDatBanGallerySwiper = function () {
            const $galleryContainer = $("#gallery-container");
            if (!$galleryContainer.length) return;

            const $previewSwiperEl = $galleryContainer.find(
                ".hang-chuyen-nhuong-detail__gallery-preview.swiper"
            );
            const $thumbSwiperEl = $galleryContainer.find(
                ".hang-chuyen-nhuong-detail__gallery-thumbnails-swiper"
            );
            const $galleryItems = $galleryContainer.find(
                ".hang-chuyen-nhuong-detail__gallery-item"
            );
            const $galleryItemsContainer = $galleryContainer.find(
                ".hang-chuyen-nhuong-detail__gallery-items"
            );

            if (!$previewSwiperEl.length) return;

            let lgInstance = null;
            const ensureLightGallery = function () {
                if (!isLightGalleryReady()) {
                    console.warn("lightGallery chưa sẵn sàng.");
                    return null;
                }
                if (lgInstance) return lgInstance;
                if (!$galleryItemsContainer.length) return null;
                try {
                    lgInstance = lightGallery($galleryItemsContainer[0], getDefaultLightGalleryOptions({
                        selector: ".hang-chuyen-nhuong-detail__gallery-item",
                    }));
                    return lgInstance;
                } catch (error) {
                    console.error("Lỗi khởi tạo lightGallery:", error);
                    return null;
                }
            };

            // Khởi tạo Swiper cho preview và thumbnails
            let previewSwiper = null;
            let thumbSwiper = null;

            if (typeof Swiper !== "undefined") {
                const previewContainer = $previewSwiperEl[0];
                const $nextButton = $previewSwiperEl.find(".swiper-button-next");
                const $prevButton = $previewSwiperEl.find(".swiper-button-prev");

                const updateSwiperHeight = function (swiperInstance) {
                    if (!swiperInstance) return;
                    const container = swiperInstance.el;
                    if ($(container).height() > 0) {
                        swiperInstance.update();
                        swiperInstance.updateSlides();
                        swiperInstance.updateAutoHeight();
                    }
                };

                const makePreviewOnHandlers = function () {
                    return {
                        init: function () {
                            const self = this;
                            setTimeout(function () {
                                updateSwiperHeight(self);
                            }, 50);
                            const $images = $(previewContainer).find("img");
                            if ($images.length > 0) {
                                let loadedCount = 0;
                                const checkAllLoaded = function () {
                                    loadedCount++;
                                    if (loadedCount === $images.length) {
                                        setTimeout(function () {
                                            updateSwiperHeight(self);
                                        }, 100);
                                    }
                                };
                                $images.each(function () {
                                    if (this.complete) {
                                        checkAllLoaded();
                                    } else {
                                        $(this).on("load error", checkAllLoaded);
                                    }
                                });
                            } else {
                                setTimeout(function () {
                                    updateSwiperHeight(self);
                                }, 100);
                            }
                        },
                        resize: function () {
                            updateSwiperHeight(this);
                        },
                        slideChange: function () {
                            const current = this.realIndex + 1;
                            $galleryContainer.find(".bds-gallery-swiper__current").text(current);
                        },
                    };
                };

                const navConfig =
                    $nextButton.length && $prevButton.length
                        ? { nextEl: $nextButton[0], prevEl: $prevButton[0] }
                        : false;

                if ($thumbSwiperEl.length) {
                    thumbSwiper = new Swiper($thumbSwiperEl[0], {
                        loop: false,
                        spaceBetween: 8,
                        slidesPerView: "auto",
                        freeMode: true,
                        watchSlidesProgress: true,
                        breakpoints: { 768: { slidesPerView: 6, freeMode: false } },
                    });

                    previewSwiper = new Swiper(previewContainer, {
                        loop: false,
                        spaceBetween: 10,
                        autoHeight: false,
                        height: null,
                        watchSlidesProgress: true,
                        observer: true,
                        observeParents: true,
                        navigation: navConfig,
                        thumbs: { swiper: thumbSwiper },
                        on: makePreviewOnHandlers(),
                    });
                } else {
                    previewSwiper = new Swiper(previewContainer, {
                        loop: false,
                        spaceBetween: 10,
                        autoHeight: false,
                        height: null,
                        watchSlidesProgress: true,
                        observer: true,
                        observeParents: true,
                        navigation: navConfig,
                        on: makePreviewOnHandlers(),
                    });
                }

                $win.on(
                    "resize",
                    debounce(function () {
                        if (previewSwiper) updateSwiperHeight(previewSwiper);
                        if (thumbSwiper) thumbSwiper.update();
                    }, DEBOUNCE_RESIZE_MS)
                );
            } else {
                console.warn("Swiper chưa sẵn sàng, bỏ qua gallery preview swiper.");
            }

            // Click vào preview slide -> mở lightGallery qua API (chỉ trên Desktop/Tablet >= 768px, tắt zoom trên mobile)
            if (previewSwiper && $galleryItems.length) {
                $previewSwiperEl.on("click", ".swiper-slide", function (e) {
                    if ($(e.target).closest(".swiper-button-next, .swiper-button-prev").length) {
                        return;
                    }

                    // Tắt zoom/lightbox trên mobile để tối ưu tốc độ và tránh chạm nhầm khi vuốt
                    if ($win.width() < 768) {
                        return;
                    }

                    const slideIndex = $.inArray(this, previewSwiper.slides);
                    if (slideIndex < 0 || slideIndex >= $galleryItems.length) return;

                    const lg = ensureLightGallery();
                    if (lg && typeof lg.openGallery === "function") {
                        lg.openGallery(slideIndex);
                    }
                });
            }

            // Click vào thumb swiper -> sync preview swiper và update active state
            if (thumbSwiper && previewSwiper) {
                const syncActiveThumb = function (index) {
                    $thumbSwiperEl
                        .find(".hang-chuyen-nhuong-detail__gallery-thumb")
                        .removeClass("hang-chuyen-nhuong-detail__gallery-thumb--active !border-primary");
                    const $activeThumb = $thumbSwiperEl.find(
                        '[data-thumb-index="' + index + '"]'
                    );
                    if ($activeThumb.length) {
                        $activeThumb.addClass("hang-chuyen-nhuong-detail__gallery-thumb--active");
                        const thumbSlide = $activeThumb.closest(".swiper-slide")[0];
                        const thumbSlideIndex = $.inArray(thumbSlide, thumbSwiper.slides);
                        if (thumbSlideIndex >= 0) {
                            thumbSwiper.slideTo(thumbSlideIndex);
                        }
                    }
                };

                $thumbSwiperEl.on(
                    "click",
                    ".hang-chuyen-nhuong-detail__gallery-thumb",
                    function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const $thumb = $(this);
                        const thumbIndex = parseInt($thumb.data("thumb-index"), 10);

                        if (!isNaN(thumbIndex) && previewSwiper) {
                            previewSwiper.slideTo(thumbIndex);
                            syncActiveThumb(thumbIndex);
                        }
                    }
                );

                // Sync active state khi preview swiper thay đổi
                previewSwiper.on("slideChange", function () {
                    syncActiveThumb(previewSwiper.activeIndex);
                });

                // Khởi tạo ban đầu
                syncActiveThumb(previewSwiper.activeIndex || 0);
            }
        };

        // Chạy init theo pageContext; trì hoãn Swiper/lightGallery để giảm TBT (tốt cho Core Web Vitals)
        const runDeferred = function (fn) {
            if (typeof requestIdleCallback !== "undefined") {
                requestIdleCallback(function () { fn(); }, { timeout: 300 });
            } else {
                setTimeout(fn, 0);
            }
        };

        if (pageContext === "home") {
            runDeferred(function () {
                initHeroSwiper();
                initHeroProjectsSwiper();
                initPartnersSwiper();
            });
        }

        if (pageContext === "project-detail") {
            // Khởi tạo Swiper 1 item cho mobile và lightbox cho desktop
            initProjectHeroSwiper();
            initGalleryLightbox();
            initFloorplanSubTabs();
            initFloorplanSelector();
            initPricingTabs();
            runDeferred(function () {
                initGalleryLightbox();
                initFloorplanSwiperAndLightbox();
                initProjectVisualGalleryLightbox();
                initSidebarVideoSwiper();
            });
            $win.on("load", function () {
                const $galleryPreview = $(".hang-chuyen-nhuong-detail__gallery-preview.swiper");
                if ($galleryPreview.length && $galleryPreview[0].swiper) {
                    setTimeout(function () {
                        $galleryPreview[0].swiper.update();
                        $galleryPreview[0].swiper.updateSlides();
                    }, 100);
                }
            });
        }

        if (pageContext === "nha-dat-detail") {
            runDeferred(function () {
                initNhaDatBanGallerySwiper();
                initProjectLoanCalculator();
            });
            $win.on("load", function () {
                const $galleryPreview = $(".hang-chuyen-nhuong-detail__gallery-preview.swiper");
                if ($galleryPreview.length && $galleryPreview[0].swiper) {
                    setTimeout(function () {
                        $galleryPreview[0].swiper.update();
                        $galleryPreview[0].swiper.updateSlides();
                    }, 100);
                }
            });
        }

        // Khởi tạo native carousels (CSS scroll-snap) cho toàn bộ trang
        initNativeCarousels();

        // ==================== Project Detail Page Functionality ====================

        // Ước tính khoản vay (Loan Calculator)
        const initProjectLoanCalculator = function () {
            const $section = $("#project-loan-section");
            if (!$section.length) return;

            const $pvInput = $("#loan-property-value");
            const $ratioInput = $("#loan-ratio");
            const $amountInput = $("#loan-amount");
            const $termInput = $("#loan-term");
            const $bankSelect = $("#loan-bank");
            const $rateInput = $("#loan-interest-rate");
            const $methodSelect = $("#loan-method");

            const $resultTotal = $("#loan-result-total");
            const $resultEquity = $("#loan-result-equity");
            const $resultPrincipal = $("#loan-result-principal");
            const $resultInterest = $("#loan-result-interest");
            const $resultFirstMonth = $("#loan-result-first-month");

            const $chartEquity = $(".loan-calculator__chart-equity");
            const $chartPrincipal = $(".loan-calculator__chart-principal");
            const $chartInterest = $(".loan-calculator__chart-interest");
            const $pctEquity = $(".loan-calculator__pct-equity");
            const $pctPrincipal = $(".loan-calculator__pct-principal");
            const $pctInterest = $(".loan-calculator__pct-interest");
            const $bankPolicyLink = $("#loan-bank-policy-link");

            const formatVND = function (num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") + " đ";
            };

            const parseVND = function (str) {
                return parseInt(str.toString().replace(/[^\d]/g, ""), 10) || 0;
            };

            const calculate = function () {
                const pv = parseVND($pvInput.val());
                const ratio = parseFloat($ratioInput.val()) || 0;
                const loanAmount = parseVND($amountInput.val());
                const termYears = parseInt($termInput.val(), 10) || 0;
                const bankRate = parseFloat($rateInput.val().replace(",", ".")) || 0;
                const method = $methodSelect.val();

                const n = termYears * 12;
                if (n <= 0) return;

                const r = (bankRate / 100) / 12;
                const equity = pv - loanAmount;

                let totalInterest = 0;
                let firstMonthPayment = 0;

                if (method === "reducing") {
                    // Dư nợ giảm dần: Gốc đều + Lãi giảm dần
                    const monthlyPrincipal = loanAmount / n;
                    // Lãi tháng đầu: P * r
                    // Lãi tháng cuối: monthlyPrincipal * r
                    // Tổng lãi = n * (Lãi đầu + Lãi cuối) / 2
                    totalInterest = (n * (loanAmount * r + monthlyPrincipal * r)) / 2;
                    firstMonthPayment = monthlyPrincipal + (loanAmount * r);
                } else {
                    // Dư nợ gốc chia đều (Fixed): Logic thường dùng là dư nợ cố định (niên kim) hoặc gốc đều
                    // Theo ảnh mẫu và yêu cầu phổ biến là Niên kim (Annuity) hoặc Gốc cố định lãi cố định.
                    // Tuy nhiên trong Real Estate VN, "Dư nợ gốc chia đều" thường hiểu là Gốc đều nhưng lãi tính trên gốc ban đầu (Flat rate)
                    // Hoặc đơn giản là Gốc đều, lãi cũng không giảm. Để chuẩn ảnh mẫu "Dư nợ giảm dần" đã khớp. 
                    // Ở đây tôi giả định "Gốc chia đều" là Flat rate để tạo sự khác biệt rõ rệt.
                    totalInterest = loanAmount * (bankRate / 100) * termYears;
                    firstMonthPayment = (loanAmount / n) + (loanAmount * r);
                }

                const totalToPay = equity + loanAmount + totalInterest;

                // Update UI Results
                if (totalToPay >= 1000000000) {
                    $resultTotal.text((totalToPay / 1000000000).toLocaleString("vi-VN", { maximumFractionDigits: 2 }) + " tỷ");
                } else {
                    $resultTotal.text(formatVND(Math.round(totalToPay)));
                }

                $resultEquity.text(formatVND(Math.round(equity)));
                $resultPrincipal.text(formatVND(Math.round(loanAmount)));
                $resultInterest.text(formatVND(Math.round(totalInterest)));
                $resultFirstMonth.text(formatVND(Math.round(firstMonthPayment)));

                // Update Chart
                const totalComp = equity + loanAmount + totalInterest;
                const equityPct = (equity / totalComp) * 100;
                const principalPct = (loanAmount / totalComp) * 100;
                const interestPct = (totalInterest / totalComp) * 100;

                $chartEquity.css("width", equityPct + "%");
                $chartPrincipal.css("width", principalPct + "%");
                $chartInterest.css("width", interestPct + "%");

                $pctEquity.css("width", equityPct + "%").text(equityPct.toFixed(1) + "%");
                $pctPrincipal.css("width", principalPct + "%").text(principalPct.toFixed(1) + "%");
                $pctInterest.css("width", interestPct + "%").text(interestPct.toFixed(1) + "%");

                // Ẩn text nếu phần trăm quá nhỏ (< 7%) để tránh chồng chéo
                [$pctEquity, $pctPrincipal, $pctInterest].forEach($el => {
                    const pct = parseFloat($el.text());
                    if (pct < 7) $el.addClass("opacity-0");
                    else $el.removeClass("opacity-0");
                });
            };

            // Events
            $pvInput.on("input", function () {
                let val = parseVND($(this).val());
                $(this).val(val.toLocaleString("vi-VN"));

                const ratio = parseFloat($ratioInput.val()) || 0;
                const amount = Math.round(val * ratio / 100);
                $amountInput.val(amount.toLocaleString("vi-VN"));
                calculate();
            });

            $ratioInput.on("input", function () {
                const ratio = parseFloat($(this).val()) || 0;
                const pv = parseVND($pvInput.val());
                const amount = Math.round(pv * ratio / 100);
                $amountInput.val(amount.toLocaleString("vi-VN"));
                calculate();
            });

            $amountInput.on("input", function () {
                let val = parseVND($(this).val());
                $(this).val(val.toLocaleString("vi-VN"));

                const pv = parseVND($pvInput.val());
                if (pv > 0) {
                    const ratio = (val / pv) * 100;
                    $ratioInput.val(Math.round(ratio));
                }
                calculate();
            });

            $termInput.on("input", calculate);
            $methodSelect.on("change", calculate);

            $bankSelect.on("change", function () {
                const rate = $(this).val();
                $rateInput.val(rate.replace(".", ","));

                const url = $(this).find("option:selected").data("url");
                if (url) {
                    $bankPolicyLink.attr("href", url);
                }

                calculate();
            });

            $rateInput.on("input", calculate);

            // Initial calculation
            const initPV = parseVND($pvInput.val());
            $pvInput.val(initPV.toLocaleString("vi-VN"));
            const initAmount = parseVND($amountInput.val());
            $amountInput.val(initAmount.toLocaleString("vi-VN"));

            const initUrl = $bankSelect.find("option:selected").data("url");
            if (initUrl) {
                $bankPolicyLink.attr("href", initUrl);
            }

            calculate();

            // Export Excel logic
            const $exportBtn = $("#loan-export-excel");
            if ($exportBtn.length) {
                $exportBtn.on("click", function (e) {
                    e.preventDefault();

                    const pv = parseVND($pvInput.val());
                    const loanAmount = parseVND($amountInput.val());
                    const termYears = parseInt($termInput.val(), 10) || 0;
                    const bankRate = parseFloat($rateInput.val().replace(",", ".")) || 0;
                    const methodLabel = $methodSelect.find("option:selected").text();
                    const method = $methodSelect.val();

                    const n = termYears * 12;
                    if (n <= 0) {
                        alert("Vui lòng nhập thời hạn vay.");
                        return;
                    }

                    const r = (bankRate / 100) / 12;
                    const mPrincipal = loanAmount / n;

                    // Header info and table rows
                    const today = new Date().toLocaleDateString("vi-VN");
                    let rows = "";
                    let balance = loanAmount;
                    let totalInt = 0;
                    let totalPay = 0;

                    // Start with Kỳ 0
                    rows += `
                        <tr>
                            <td style="text-align: center; border: 1px solid #d1d5db;">0</td>
                            <td style="border: 1px solid #d1d5db;"></td>
                            <td style="border: 1px solid #d1d5db;"></td>
                            <td style="border: 1px solid #d1d5db;"></td>
                            <td style="text-align: right; border: 1px solid #d1d5db;">${Math.round(balance).toLocaleString("en-US")}</td>
                        </tr>
                    `;

                    for (let i = 1; i <= n; i++) {
                        const interest = balance * r;
                        let principal = mPrincipal;
                        const payment = interest + principal;
                        balance -= principal;
                        if (balance < 0) balance = 0;

                        totalInt += interest;
                        totalPay += payment;

                        const rowBg = i % 2 === 0 ? 'background-color: #f9fafb;' : '';
                        rows += `
                            <tr style="${rowBg}">
                                <td style="text-align: center; border: 1px solid #d1d5db;">${i}</td>
                                <td style="text-align: right; border: 1px solid #d1d5db;">${Math.round(interest).toLocaleString("en-US")}</td>
                                <td style="text-align: right; border: 1px solid #d1d5db;">${Math.round(principal).toLocaleString("en-US")}</td>
                                <td style="text-align: right; border: 1px solid #d1d5db; font-weight: bold; color: #C93347;">${Math.round(payment).toLocaleString("en-US")}</td>
                                <td style="text-align: right; border: 1px solid #d1d5db;">${Math.round(balance).toLocaleString("en-US")}</td>
                            </tr>
                        `;
                    }

                    const excelTemplate = `
                        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                        <head>
                            <meta charset="UTF-8">
                            <!--[if gte mso 9]>
                            <xml>
                                <x:ExcelWorkbook>
                                    <x:ExcelWorksheets>
                                        <x:ExcelWorksheet>
                                            <x:Name>Loan Schedule</x:Name>
                                            <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
                                        </x:ExcelWorksheet>
                                    </x:ExcelWorksheets>
                                </x:ExcelWorkbook>
                            </xml>
                            <![endif]-->
                            <style>
                                table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
                                td, th { padding: 8px 12px; font-size: 11pt; border: 1px solid #d1d5db; }
                                .title { background-color: #C93347; color: #ffffff; font-weight: bold; text-align: center; font-size: 16pt; }
                                .summary-label { font-weight: bold; font-style: italic; background-color: #f3f4f6; }
                                .header { background-color: #333333; color: #ffffff; font-weight: bold; text-align: center; }
                                .footer { background-color: #f3f4f6; font-weight: bold; }
                                .number { mso-number-format: "#,##0"; }
                            </style>
                        </head>
                        <body>
                            <table>
                                <tr><th colspan="5" class="title">CHI TIẾT KHOẢN VAY - ${today}</th></tr>
                                <tr><td colspan="5"></td></tr>
                                <tr>
                                    <td colspan="2" class="summary-label">Số tiền vay</td>
                                    <td colspan="3" style="text-align: right; font-weight: bold;">${loanAmount.toLocaleString("en-US")}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="summary-label">Thời hạn vay</td>
                                    <td colspan="3" style="text-align: right; font-weight: bold;">${termYears} năm</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="summary-label">Lãi suất</td>
                                    <td colspan="3" style="text-align: right; font-weight: bold;">${bankRate}%/năm</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="summary-label">Hình thức</td>
                                    <td colspan="3" style="text-align: right; font-weight: bold;">${methodLabel}</td>
                                </tr>
                                <tr><td colspan="5"></td></tr>
                                <tr class="header">
                                    <th>Kỳ hạn</th>
                                    <th>Lãi phải trả</th>
                                    <th>Gốc phải trả</th>
                                    <th>Số tiền phải trả</th>
                                    <th>Số tiền còn lại</th>
                                </tr>
                                ${rows}
                                <tr class="footer">
                                    <td style="text-align: center;">Tổng</td>
                                    <td style="text-align: right;">${Math.round(totalInt).toLocaleString("en-US")}</td>
                                    <td style="text-align: right;">${Math.round(loanAmount).toLocaleString("en-US")}</td>
                                    <td style="text-align: right; color: #C93347;">${Math.round(totalPay).toLocaleString("en-US")}</td>
                                    <td></td>
                                </tr>
                            </table>
                        </body>
                        </html>
                    `;

                    const blob = new Blob([excelTemplate], { type: "application/vnd.ms-excel" });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = "Chi tiết khoản vay - 342026.xls";
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                });
            }
        };

        // Share popup cho trang chi tiết dự án & bất động sản (Hỗ trợ nhiều nút & sao chép mượt mà)
        const initProjectDetailShare = function () {
            const $shareWraps = $(".project-detail__share");
            if (!$shareWraps.length) return;

            $shareWraps.each(function () {
                const $wrap = $(this);
                const $button = $wrap.find(".project-detail__share-button");
                const $menu = $wrap.find(".project-detail__share-menu");
                const $items = $wrap.find(".project-detail__share-item");

                if (!$button.length || !$menu.length) return;

                const toggle = function (isOpen) {
                    const open = typeof isOpen === "boolean" ? isOpen : $menu.attr("aria-hidden") === "true";
                    $menu.attr("aria-hidden", open ? "false" : "true");
                    $button.attr("aria-expanded", open ? "true" : "false");
                    if (open) {
                        $menu.addClass("project-detail__share-menu--open");
                    } else {
                        $menu.removeClass("project-detail__share-menu--open");
                    }
                };

                $button.on("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const isHidden = $menu.attr("aria-hidden") !== "false";
                    $(".project-detail__share-menu--open").not($menu).removeClass("project-detail__share-menu--open").attr("aria-hidden", "true");
                    $(".project-detail__share-button").not($button).attr("aria-expanded", "false");
                    toggle(isHidden);
                });

                $items.on("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const type = $(this).attr("data-share");
                    const pageUrl = window.location.href;
                    const sharePopupOpts = "noopener,noreferrer,width=600,height=500";
                    const shareUrls = {
                        facebook: "https://www.facebook.com/sharer/sharer.php?u=" + encodeURIComponent(pageUrl),
                        zalo: "https://zalo.me/share?url=" + encodeURIComponent(pageUrl),
                    };

                    if (shareUrls[type]) {
                        window.open(shareUrls[type], "_blank", sharePopupOpts);
                        toggle(false);
                    } else if (type === "copy") {
                        const copySuccess = function () {
                            const $title = $wrap.find(".project-detail__share-copy-title");
                            if ($title.length) {
                                const orig = $title.text();
                                $title.html('<span class="text-emerald-600 font-bold flex items-center gap-1">Đã sao chép liên kết! ✓</span>');
                                setTimeout(function () {
                                    $title.text(orig);
                                    toggle(false);
                                }, 1200);
                            } else {
                                toggle(false);
                            }
                        };

                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(pageUrl).then(copySuccess).catch(function () {
                                copySuccess();
                            });
                        } else {
                            const $tempInput = $("<input>");
                            $("body").append($tempInput);
                            $tempInput.val(pageUrl).select();
                            document.execCommand("copy");
                            $tempInput.remove();
                            copySuccess();
                        }
                    }
                });
            });

            $doc.on("click", function (event) {
                if (!$(event.target).closest(".project-detail__share").length) {
                    $(".project-detail__share-menu--open").removeClass("project-detail__share-menu--open").attr("aria-hidden", "true");
                    $(".project-detail__share-button").attr("aria-expanded", "false");
                }
            });
        };

        // Helper function để set active tab theo target selector hoặc button element
        const setActiveTabByTarget = function (targetSelectorOrButton) {
            const $tabButtons = $(".project-detail__tab, .project-detail__tab-more-item");
            if (!$tabButtons.length) return;

            let $targetButton = null;

            // Xử lý mapping alias nếu target trỏ vào sub-section
            let resolvedTarget = targetSelectorOrButton;
            if (typeof targetSelectorOrButton === "string") {
                if (targetSelectorOrButton === "#project-location-section") {
                    resolvedTarget = "#project-location-amenities-section";
                    $('[data-section-tab-btn="location-map"]').trigger("click");
                } else if (targetSelectorOrButton === "#project-floorplan-section") {
                    resolvedTarget = "#project-floorplan-products-section";
                    $('[data-section-tab-btn="floor-plans"]').trigger("click");
                } else if (targetSelectorOrButton === "#project-legal-section") {
                    resolvedTarget = "#project-legal-progress-section";
                    $('[data-section-tab-btn="legal-docs"]').trigger("click");
                } else if (targetSelectorOrButton === "#project-gallery-progress-section") {
                    resolvedTarget = "#project-legal-progress-section";
                    $('[data-section-tab-btn="construction-progress"]').trigger("click");
                } else if (targetSelectorOrButton === "#project-loan-section") {
                    resolvedTarget = "#project-pricing-policy-section";
                    $('[data-section-tab-btn="pricing-loan"]').trigger("click");
                }
            }

            if (typeof resolvedTarget === "object" && resolvedTarget) {
                $targetButton = $(resolvedTarget);
            } else if (typeof resolvedTarget === "string") {
                $tabButtons.each(function () {
                    const buttonTarget = $(this).attr("data-scroll-target");
                    if (buttonTarget === resolvedTarget) {
                        $targetButton = $(this);
                        return false;
                    }
                });
            }

            if ($targetButton && $targetButton.length) {
                const $moreToggle = $(".project-detail__tab--more-toggle");
                $tabButtons.each(function () {
                    const $button = $(this);
                    if ($button.is($targetButton)) {
                        // Chỉ add active cho tab thường hoặc more-toggle, không add cho submenu (more-item)
                        if (!$targetButton.hasClass("project-detail__tab-more-item")) {
                            $button.addClass("project-detail__tab--active");
                        }
                    } else if (!$button.hasClass("project-detail__tab--more-toggle")) {
                        $button.removeClass("project-detail__tab--active");
                    }
                });

                // Tối ưu UX Mobile: Tự động cuộn thanh tabs để tab active luôn nằm ở giữa tầm nhìn (chỉ scroll ngang trong tabs container, KHÔNG gọi scrollIntoView làm delay scroll trang)
                if ($targetButton.length && $targetButton[0]) {
                    const tabNav = $targetButton.closest(".project-detail__tabs")[0];
                    if (tabNav) {
                        const btnLeft = $targetButton[0].offsetLeft;
                        const btnWidth = $targetButton[0].offsetWidth;
                        const navWidth = tabNav.clientWidth;
                        tabNav.scrollTo({
                            left: Math.max(0, btnLeft - (navWidth / 2) + (btnWidth / 2)),
                            behavior: "smooth",
                        });
                    }
                }

                // Khi đang xem FAQ/Dự án liên quan: active nút "Xem thêm", không active submenu bên trong
                if ($moreToggle.length) {
                    if ($targetButton.hasClass("project-detail__tab-more-item")) {
                        $moreToggle.addClass("project-detail__tab--active");
                    } else {
                        $moreToggle.removeClass("project-detail__tab--active");
                    }
                }
            }
        };

        // Smooth scroll cho nút "Xem bản đồ" trong trang chi tiết dự án
        const initProjectDetailViewMap = function () {
            const $viewMapBtn = $(".project-detail__view-map");
            if (!$viewMapBtn.length) return;

            $viewMapBtn.on("click", function (e) {
                e.preventDefault();
                const targetSelector = $(this).attr("data-scroll-target");
                if (targetSelector) {
                    setActiveTabByTarget(targetSelector);
                    scrollToSection(targetSelector);
                }
            });
        };

        // Tabs điều hướng nội dung dự án
        const initProjectDetailTabs = function () {
            const $tabButtons = $(".project-detail__tab");
            const $moreToggle = $(".project-detail__tab--more-toggle");
            const $moreMenu = $(".project-detail__tab-more-menu");
            const $moreItems = $(".project-detail__tab-more-item");

            if ($tabButtons.length) {
                $tabButtons.on("click", function (e) {
                    e.preventDefault();
                    const $button = $(this);
                    const targetSelector = $button.attr("data-scroll-target");
                    if (!targetSelector) return;

                    setActiveTabByTarget($button);
                    const onComplete = targetSelector === "#project-related-projects-section" ? updateRelatedProjectsSwipers : undefined;
                    scrollToSection(targetSelector, onComplete);
                });
            }

            if ($moreToggle.length && $moreMenu.length) {
                const toggleMoreMenu = function (isOpen) {
                    const open =
                        typeof isOpen === "boolean"
                            ? isOpen
                            : $moreMenu.attr("aria-hidden") === "true";
                    $moreMenu.attr("aria-hidden", open ? "false" : "true");
                    $moreToggle.attr("aria-expanded", open ? "true" : "false");
                    if (open) {
                        $moreMenu.addClass("project-detail__tab-more-menu--open");
                    } else {
                        $moreMenu.removeClass("project-detail__tab-more-menu--open");
                    }
                };

                $moreToggle.on("click", function () {
                    const isHidden = $moreMenu.attr("aria-hidden") !== "false";
                    toggleMoreMenu(isHidden);
                });

                $doc.on("click", function (event) {
                    if (
                        !$moreToggle.is(event.target) &&
                        !$moreToggle.has(event.target).length &&
                        !$moreMenu.is(event.target) &&
                        !$moreMenu.has(event.target).length
                    ) {
                        toggleMoreMenu(false);
                    }
                });
            }

            if ($moreItems.length) {
                $moreItems.on("click", function (e) {
                    e.preventDefault();
                    const targetSelector = $(this).attr("data-scroll-target");
                    if (targetSelector) {
                        const onComplete = targetSelector === "#project-related-projects-section" ? updateRelatedProjectsSwipers : undefined;
                        setActiveTabByTarget(targetSelector);
                        scrollToSection(targetSelector, onComplete);
                    }
                });
            }
        };

        // FAQ Toggle cho trang chi tiết dự án
        const initProjectDetailFAQ = function () {
            $(".project-detail__faq-question").on("click", function () {
                const $item = $(this).closest(".project-detail__faq-item");
                const $answer = $item.find(".project-detail__faq-answer");
                const isExpanded = $(this).attr("aria-expanded") === "true";

                $(this).attr("aria-expanded", !isExpanded);
                if (isExpanded) {
                    $answer.attr("hidden", "");
                    $item.removeClass("project-detail__faq-item--active");
                } else {
                    $answer.removeAttr("hidden");
                    $item.addClass("project-detail__faq-item--active");
                }
            });
        };

        // Collapse sections cho mobile (Thông tin chi tiết và Tiện ích)
        const initProjectDetailCollapse = function () {
            $(".project-detail__collapse-toggle").on("click", function () {
                const $section = $(this).closest(".project-detail__collapse-section");
                const $content = $section.find(".project-detail__collapse-content");
                const isExpanded = $(this).attr("aria-expanded") === "true";

                $(this).attr("aria-expanded", !isExpanded);
                if (isExpanded) {
                    $content.attr("hidden", "");
                    $section.removeClass("project-detail__collapse-section--expanded");
                } else {
                    $content.removeAttr("hidden");
                    $section.addClass("project-detail__collapse-section--expanded");
                }
            });
        };

        /**
         * Scroll Spy - Tự động active tab khi scroll qua section tương ứng
         * Tối ưu hiệu năng: Cache metrics trước, không query DOM reflow trong scroll frame, dùng passive listener
         */
        const initScrollSpy = function () {
            const $tabs = $('.project-detail__tab[data-scroll-target], .project-detail__tab-more-item[data-scroll-target]');
            if (!$tabs.length) return;

            const sections = [];
            $tabs.each(function () {
                const target = $(this).attr('data-scroll-target');
                if (target && !sections.find(s => s.target === target)) {
                    const $section = $(target);
                    if ($section.length) {
                        sections.push({ target: target, $section: $section });
                    }
                }
            });

            if (sections.length === 0) return;

            let sectionMetrics = [];
            const recalculateMetrics = function () {
                sectionMetrics = sections.map(function (s) {
                    const top = s.$section.offset().top;
                    const height = s.$section.outerHeight();
                    return { target: s.target, top: top, bottom: top + height };
                });
            };
            recalculateMetrics();

            window.addEventListener("resize", debounce(recalculateMetrics, 150), { passive: true });
            window.addEventListener("orientationchange", debounce(recalculateMetrics, 150), { passive: true });
            window.addEventListener("load", recalculateMetrics, { passive: true });

            let lastActiveTarget = null;
            let ticking = false;

            const updateActiveTab = function () {
                if (isManualScrolling) {
                    ticking = false;
                    return;
                }

                const scrollTop = window.scrollY || window.pageYOffset || 0;
                const windowHeight = window.innerHeight;
                const docHeight = document.documentElement.scrollHeight;
                const triggerLine = scrollTop + SCROLL_SPY_OFFSET;

                let currentTarget = null;

                if (scrollTop < 80) {
                    currentTarget = sectionMetrics[0] ? sectionMetrics[0].target : null;
                } else if (scrollTop + windowHeight >= docHeight - 50) {
                    currentTarget = sectionMetrics[sectionMetrics.length - 1] ? sectionMetrics[sectionMetrics.length - 1].target : null;
                } else {
                    for (let i = 0; i < sectionMetrics.length; i++) {
                        if (triggerLine >= sectionMetrics[i].top && triggerLine < sectionMetrics[i].bottom) {
                            currentTarget = sectionMetrics[i].target;
                            break;
                        }
                    }
                    if (!currentTarget) {
                        for (let i = sectionMetrics.length - 1; i >= 0; i--) {
                            if (triggerLine >= sectionMetrics[i].top) {
                                currentTarget = sectionMetrics[i].target;
                                break;
                            }
                        }
                    }
                }

                if (currentTarget && currentTarget !== lastActiveTarget) {
                    lastActiveTarget = currentTarget;
                    setActiveTabByTarget(currentTarget);
                }
                ticking = false;
            };

            const handleScroll = function () {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(updateActiveTab);
            };

            window.addEventListener("scroll", handleScroll, { passive: true });
            setTimeout(updateActiveTab, 300);
        };

        // Khởi tạo functionality trang chi tiết dự án - chỉ khi context đúng
        if (pageContext === "project-detail") {
            initProjectDetailShare();
            initProjectDetailViewMap();
            initSectionTabs();
            initProjectDetailTabs();
            initProjectDetailFAQ();
            initProjectDetailCollapse();
            initProjectLoanCalculator();
            initScrollSpy();
        } else if (pageContext === "nha-dat-detail") {
            initProjectDetailShare();
        }

        // Core: luôn chạy mọi trang
        initMobileMenu();
        initFooterAccordion();

        // Advisor Modal
        initAdvisorModal();

        // Newsletter Forms
        initNewsletterForms();

        // Contact Page Forms
        initContactForms();
    });

    /**
     * Advisor Modal - Đăng ký tư vấn (tạo động khi click để tăng performance)
     */
    const initAdvisorModal = function () {
        const $body = $("body");
        let $modal = null;
        let isModalCreated = false;

        const createModalHTML = function () {
            const data = typeof bdsAdvisor !== "undefined" ? bdsAdvisor : {};
            const projectName = data.projectName || "";
            const projectLogo = data.projectLogo || "";
            const policyUrl = data.policyUrl || "/chinh-sach-bao-mat";
            const logoHTML = projectLogo
                ? `<div class="text-center mb-5"><img src="${projectLogo}" alt="${projectName}" class="max-w-[120px] max-h-16 w-auto h-auto object-contain mx-auto"></div>`
                : "";

            return `
<div id="advisor-modal" class="advisor-modal fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 opacity-0 invisible transition-[opacity,visibility] duration-300" aria-hidden="true" role="dialog" aria-labelledby="advisor-modal-title">
  <div class="absolute inset-0 bg-charcoal/70 backdrop-blur-sm cursor-pointer" data-modal-close></div>
  <div class="advisor-modal__container relative bg-surface rounded-2xl max-w-[500px] w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-border translate-y-5 transition-transform duration-300">
    <button type="button" class="advisor-modal__close absolute top-5 right-5 z-20 flex items-center gap-1.5 bg-stone/50 hover:bg-stone border border-border/60 rounded-full cursor-pointer text-xs font-semibold text-charcoal-muted hover:text-charcoal px-3 py-1.5 transition-colors" data-modal-close aria-label="Đóng">
      <span class="pointer-events-none">ĐÓNG</span>
      <svg class="pointer-events-none" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
    </button>
    <div class="p-6 sm:p-8 pt-8">
      ${logoHTML}
      <div class="text-center mb-6">
        <span class="text-xs uppercase tracking-wider text-primary font-semibold block mb-1">Tư vấn chuyên sâu</span>
        <h2 id="advisor-modal-title" class="text-2xl sm:text-[26px] font-bold text-charcoal m-0 mb-2">Đăng ký nhận thông tin</h2>
        <p class="text-sm text-charcoal-muted m-0">Chuyên viên tư vấn sẽ liên hệ bảo mật trong vòng 15 phút</p>
      </div>
      <form class="flex flex-col gap-3.5" id="advisor-form" method="post">
        <input type="text" name="bds_honeypot" class="hidden" style="display:none !important;" tabindex="-1" autocomplete="off" aria-hidden="true">
        <input type="hidden" name="action" value="batdongsan_submit_lead">
        <input type="hidden" name="source" value="advisor_modal">
        <input type="hidden" name="post_id" value="${data.postId || ''}">
        <input type="hidden" name="project_name" value="${projectName}">
        
        <div class="relative">
          <label class="block text-xs font-medium text-charcoal mb-1">Họ và tên <span class="text-primary">*</span></label>
          <input type="text" name="fullname" class="w-full py-2.5 px-4 text-xs font-sans border border-black/[0.08] rounded-full bg-[#F5F5F7] text-charcoal transition-all focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 placeholder:text-charcoal-muted/60" placeholder="Nguyễn Văn A" required>
        </div>
        
        <div class="relative">
          <label class="block text-xs font-medium text-charcoal mb-1">Số điện thoại <span class="text-primary">*</span></label>
          <input type="tel" name="phone" class="w-full py-2.5 px-4 text-xs font-sans border border-black/[0.08] rounded-full bg-[#F5F5F7] text-charcoal transition-all focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 placeholder:text-charcoal-muted/60" placeholder="09xx xxx xxx" required pattern="(0|\\+84|84)[3|5|7|8|9][0-9]{8}">
          <span class="absolute right-3.5 top-[30px] text-charcoal-muted/40 pointer-events-none">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
          </span>
        </div>
        
        <details class="advisor-modal__expand mt-0.5">
          <summary class="flex items-center gap-1.5 cursor-pointer text-primary hover:text-primary-hover text-xs font-medium list-none py-1 [&::-webkit-details-marker]:hidden">
            <span>Thêm Email &amp; Ghi chú yêu cầu</span>
            <svg class="transition-transform duration-200 [[open]>&]:rotate-180" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
          </summary>
          <div class="pt-3 flex flex-col gap-3">
            <div>
              <label class="block text-xs font-medium text-charcoal mb-1">Email</label>
              <input type="email" name="email" class="w-full py-2.5 px-4 text-xs font-sans border border-black/[0.08] rounded-full bg-[#F5F5F7] text-charcoal transition-all focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 placeholder:text-charcoal-muted/60" placeholder="email@domain.com">
            </div>
            <div>
              <label class="block text-xs font-medium text-charcoal mb-1">Nhu cầu chi tiết</label>
              <textarea name="message" class="w-full py-2.5 px-4 text-xs font-sans border border-black/[0.08] rounded-2xl bg-[#F5F5F7] text-charcoal transition-all focus:bg-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 placeholder:text-charcoal-muted/60 resize-y" placeholder="Khoảng giá, diện tích, hướng hoặc mã căn Quý Khách quan tâm..." rows="3"></textarea>
            </div>
          </div>
        </details>
        
        <div class="advisor-modal__feedback hidden text-xs py-2 px-3 rounded-full border my-1" role="alert" aria-live="polite"></div>

        <p class="text-xs text-charcoal-muted leading-relaxed mt-1 mb-1">
          Bằng việc bấm vào nút "Nhận tư vấn", bạn đồng ý với <a href="${policyUrl}" class="text-primary underline hover:text-primary-hover">Chính Sách Bảo Mật</a> của chúng tôi.
        </p>
        
        <button type="submit" class="advisor-modal__submit w-full py-3 px-6 text-xs font-semibold tracking-wider uppercase text-white bg-primary hover:bg-primary-hover rounded-full shadow-sm cursor-pointer transition-all duration-200 mt-1 flex items-center justify-center gap-2">
          <span>NHẬN TƯ VẤN</span>
        </button>
      </form>
    </div>
  </div>
</div>`;
        };

        const createModal = function () {
            if (isModalCreated) return;
            if ($("#advisor-modal").length) {
                $modal = $("#advisor-modal");
            } else {
                $body.append(createModalHTML());
                $modal = $("#advisor-modal");
            }
            isModalCreated = true;
            bindModalEvents();
        };

        const bindModalEvents = function () {
            // Close modal when clicking [data-modal-close]
            $modal.on("click", "[data-modal-close]", function (e) {
                e.preventDefault();
                closeModal();
            });

            // Close modal when clicking dark backdrop outside container
            $modal.on("click", function (e) {
                if (!$(e.target).closest(".advisor-modal__container").length) {
                    closeModal();
                }
            });
            $modal.on("submit", "#advisor-form", function (e) {
                e.preventDefault();
                const $form = $(this);
                const $submitBtn = $form.find(".advisor-modal__submit");
                const $feedback = $form.find(".advisor-modal__feedback");
                const originalBtnHtml = $submitBtn.html();

                // Clear feedback
                if ($feedback.length) {
                    $feedback.addClass("hidden").removeClass("bg-rose-50 text-rose-700 border-rose-200 bg-emerald-50 text-emerald-800 border-emerald-200").text("");
                }

                // Client-side phone validation
                const phone = ($form.find('input[name="phone"]').val() || "").trim();
                const phoneRegex = /^(?:\+84|84|0)[35789][0-9]{8}$/;
                if (!phoneRegex.test(phone)) {
                    if ($feedback.length) {
                        $feedback.removeClass("hidden").addClass("bg-rose-50 text-rose-700 border-rose-200").text("Số điện thoại không đúng định dạng (VD: 0912345678).");
                    }
                    $form.find('input[name="phone"]').focus();
                    return;
                }

                // Prevent double-submit with loading spinner
                $submitBtn.prop("disabled", true).html(
                    '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> <span>Đang gửi thông tin...</span>'
                );

                const ajaxUrl = (window.bds_vars && window.bds_vars.ajax_url) || (window.bdsTheme && window.bdsTheme.ajaxUrl) || "/wp-admin/admin-ajax.php";
                const nonce = (window.bds_vars && window.bds_vars.bdsLeadNonce) || (window.bdsTheme && window.bdsTheme.bdsLeadNonce) || "";

                const formData = {
                    action: "batdongsan_submit_lead",
                    nonce: nonce,
                    fullname: $form.find('input[name="fullname"]').val() || "",
                    phone: phone,
                    email: $form.find('input[name="email"]').val() || "",
                    message: $form.find('textarea[name="message"]').val() || "",
                    source: "advisor_modal",
                    post_id: (typeof bdsAdvisor !== "undefined" && bdsAdvisor.postId) ? bdsAdvisor.postId : ($form.find('input[name="post_id"]').val() || 0),
                    bds_honeypot: $form.find('input[name="bds_honeypot"]').val() || ""
                };

                $.ajax({
                    url: ajaxUrl,
                    type: "POST",
                    data: formData,
                    dataType: "json",
                    success: function (res) {
                        if (res && res.success) {
                            const successMsg = (res.data && res.data.message) ? res.data.message : "Đăng ký thành công! Chuyên viên sẽ liên hệ với bạn trong thời gian sớm nhất.";
                            $form.html(
                                '<div class="text-center py-6">' +
                                '<div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-emerald-200">' +
                                '<svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>' +
                                '</div>' +
                                '<h3 class="text-xl font-bold text-charcoal m-0 mb-2">Đăng ký thành công!</h3>' +
                                '<p class="text-sm text-charcoal-muted m-0 leading-relaxed">' + successMsg + '</p></div>'
                            );
                            setTimeout(closeModal, 2500);
                        } else {
                            const errorMsg = (res && res.data && res.data.message) ? res.data.message : "Đã có lỗi xảy ra. Vui lòng kiểm tra lại thông tin!";
                            if ($feedback.length) {
                                $feedback.removeClass("hidden").addClass("bg-rose-50 text-rose-700 border-rose-200").text(errorMsg);
                            }
                            $submitBtn.prop("disabled", false).html(originalBtnHtml);
                        }
                    },
                    error: function (xhr) {
                        let errorMsg = "Đã có lỗi kết nối. Vui lòng thử lại sau!";
                        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                            errorMsg = xhr.responseJSON.data.message;
                        }
                        if ($feedback.length) {
                            $feedback.removeClass("hidden").addClass("bg-rose-50 text-rose-700 border-rose-200").text(errorMsg);
                        }
                        $submitBtn.prop("disabled", false).html(originalBtnHtml);
                    }
                });
            });
        };

        const openModal = function () {
            createModal();
            setTimeout(function () {
                $modal.attr("aria-hidden", "false");
                $body.css("overflow", "hidden");
            }, 10);
        };

        const closeModal = function () {
            if (!$modal) return;
            $modal.attr("aria-hidden", "true");
            $body.css("overflow", "");
            setTimeout(function () {
                if ($modal) {
                    $modal.remove();
                    $modal = null;
                    isModalCreated = false;
                }
            }, 300);
        };

        $doc.on("click", "[data-modal-target='advisor-modal']", function (e) {
            e.preventDefault();
            openModal();
        });
        $doc.on("keydown", function (e) {
            if (e.key === "Escape" && $modal && $modal.attr("aria-hidden") === "false") {
                closeModal();
            }
        });
    };

    /**
     * Newsletter Form - AJAX submission
     */
    const initNewsletterForms = function () {
        $doc.on("submit", ".bds-newsletter-form", function (e) {
            e.preventDefault();
            const $form = $(this);
            const $submitBtn = $form.find(".bds-newsletter-submit, button[type='submit']");
            const originalBtnHtml = $submitBtn.html();
            let $feedback = $form.find(".bds-form-feedback");

            if (!$feedback.length) {
                $feedback = $('<div class="bds-form-feedback hidden text-xs py-2.5 px-3 rounded-lg border mt-2" role="alert" aria-live="polite"></div>');
                $submitBtn.before($feedback);
            }

            // Clear feedback
            $feedback.addClass("hidden").removeClass("bg-rose-50 text-rose-700 border-rose-200 bg-emerald-50 text-emerald-800 border-emerald-200").text("");

            const phone = ($form.find('input[name="phone"], input[name="newsletter_phone"]').val() || "").trim();
            const email = ($form.find('input[name="email"], input[name="newsletter_email"]').val() || "").trim();

            // Client-side validation: must have at least phone or email
            if (!phone && !email) {
                $feedback.removeClass("hidden").addClass("bg-rose-50 text-rose-700 border-rose-200").text("Vui lòng nhập số điện thoại hoặc email.");
                return;
            }
            if (phone) {
                const phoneRegex = /^(?:\+84|84|0)[35789][0-9]{8}$/;
                if (!phoneRegex.test(phone)) {
                    $feedback.removeClass("hidden").addClass("bg-rose-50 text-rose-700 border-rose-200").text("Số điện thoại không đúng định dạng (VD: 0912345678).");
                    return;
                }
            }

            // Lock button & spinner
            $submitBtn.prop("disabled", true).html(
                '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> <span>Đang đăng ký...</span>'
            );

            const ajaxUrl = (window.bds_vars && window.bds_vars.ajax_url) || (window.bdsTheme && window.bdsTheme.ajaxUrl) || "/wp-admin/admin-ajax.php";
            const nonce = $form.find('input[name="nonce"]').val() || (window.bds_vars && window.bds_vars.bdsLeadNonce) || (window.bdsTheme && window.bdsTheme.bdsLeadNonce) || "";

            const formData = {
                action: "batdongsan_submit_lead",
                nonce: nonce,
                fullname: $form.find('input[name="fullname"], input[name="newsletter_name"]').val() || "",
                phone: phone,
                email: email,
                source: "newsletter",
                bds_honeypot: $form.find('input[name="bds_honeypot"]').val() || ""
            };

            $.ajax({
                url: ajaxUrl,
                type: "POST",
                data: formData,
                dataType: "json",
                success: function (res) {
                    if (res && res.success) {
                        const msg = (res.data && res.data.message) ? res.data.message : "Đăng ký nhận bản tin thành công!";
                        $feedback.removeClass("hidden").addClass("bg-emerald-50 text-emerald-800 border-emerald-200").text(msg);
                        $form[0].reset();
                        $submitBtn.prop("disabled", false).html("Đã đăng ký ✓");
                        setTimeout(function () {
                            $submitBtn.html(originalBtnHtml);
                        }, 4000);
                    } else {
                        const errorMsg = (res && res.data && res.data.message) ? res.data.message : "Đã có lỗi xảy ra. Vui lòng thử lại!";
                        $feedback.removeClass("hidden").addClass("bg-rose-50 text-rose-700 border-rose-200").text(errorMsg);
                        $submitBtn.prop("disabled", false).html(originalBtnHtml);
                    }
                },
                error: function (xhr) {
                    let errorMsg = "Đã có lỗi kết nối. Vui lòng thử lại!";
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }
                    $feedback.removeClass("hidden").addClass("bg-rose-50 text-rose-700 border-rose-200").text(errorMsg);
                    $submitBtn.prop("disabled", false).html(originalBtnHtml);
                }
            });
        });
    };

    /**
     * Contact Page Form - AJAX submission
     */
    const initContactForms = function () {
        $doc.on("submit", ".bds-contact-form", function (e) {
            e.preventDefault();
            const $form = $(this);
            const $submitBtn = $form.find(".bds-contact-submit, button[type='submit']");
            const originalBtnHtml = $submitBtn.html();
            let $feedback = $form.find(".bds-contact-feedback");

            if (!$feedback.length) {
                $feedback = $('<div class="bds-contact-feedback hidden text-xs sm:text-sm py-3 px-4 rounded-xl border mb-3" role="alert" aria-live="polite"></div>');
                $form.prepend($feedback);
            }

            // Reset feedback
            $feedback.addClass("hidden").removeClass("bg-rose-50 text-rose-700 border-rose-200 bg-emerald-50 text-emerald-800 border-emerald-200").text("");

            const fullname = ($form.find('input[name="fullname"]').val() || "").trim();
            const phone = ($form.find('input[name="phone"]').val() || "").trim();
            const email = ($form.find('input[name="email"]').val() || "").trim();
            const demand = ($form.find('select[name="demand"]').val() || "").trim();
            const project = ($form.find('input[name="project"]').val() || "").trim();
            const userMsg = ($form.find('textarea[name="message"]').val() || "").trim();

            if (!fullname) {
                $feedback.removeClass("hidden").addClass("bg-rose-50 text-rose-700 border-rose-200").text("Vui lòng nhập họ và tên của Quý khách.");
                $form.find('input[name="fullname"]').focus();
                return;
            }

            if (!phone) {
                $feedback.removeClass("hidden").addClass("bg-rose-50 text-rose-700 border-rose-200").text("Vui lòng nhập số điện thoại liên hệ.");
                $form.find('input[name="phone"]').focus();
                return;
            }

            const phoneRegex = /^(?:\+84|84|0)[35789][0-9]{8}$/;
            if (!phoneRegex.test(phone)) {
                $feedback.removeClass("hidden").addClass("bg-rose-50 text-rose-700 border-rose-200").text("Số điện thoại không đúng định dạng (VD: 0912345678).");
                $form.find('input[name="phone"]').focus();
                return;
            }

            // Gộp nội dung lời nhắn bao gồm nhu cầu & dự án quan tâm
            let fullMessage = "";
            if (demand) {
                const demandText = $form.find('select[name="demand"] option:selected').text();
                fullMessage += "[Nhu cầu: " + demandText + "] ";
            }
            if (project) {
                fullMessage += "[Khu vực/Dự án: " + project + "] ";
            }
            if (userMsg) {
                fullMessage += userMsg;
            }

            // Lock button & spinner
            $submitBtn.prop("disabled", true).html(
                '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> <span>Đang gửi thông tin...</span>'
            );

            const ajaxUrl = (window.bds_vars && window.bds_vars.ajax_url) || (window.bdsTheme && window.bdsTheme.ajaxUrl) || "/wp-admin/admin-ajax.php";
            const nonce = (window.bds_vars && window.bds_vars.bdsLeadNonce) || (window.bdsTheme && window.bdsTheme.bdsLeadNonce) || "";

            const formData = {
                action: "batdongsan_submit_lead",
                nonce: nonce,
                fullname: fullname,
                phone: phone,
                email: email,
                message: fullMessage,
                source: "contact_page",
                bds_honeypot: $form.find('input[name="bds_honeypot"]').val() || ""
            };

            $.ajax({
                url: ajaxUrl,
                type: "POST",
                data: formData,
                dataType: "json",
                success: function (res) {
                    if (res && res.success) {
                        const msg = (res.data && res.data.message) ? res.data.message : "Cảm ơn Quý khách! Chuyên viên tư vấn sẽ liên hệ lại trong vòng 15 phút.";
                        $feedback.removeClass("hidden").addClass("bg-emerald-50 text-emerald-800 border-emerald-200").text(msg);
                        $form[0].reset();
                        $submitBtn.prop("disabled", false).html("Gửi thành công ✓");
                        setTimeout(function () {
                            $submitBtn.html(originalBtnHtml);
                        }, 5000);
                    } else {
                        const errorMsg = (res && res.data && res.data.message) ? res.data.message : "Đã có lỗi xảy ra. Vui lòng kiểm tra lại thông tin!";
                        $feedback.removeClass("hidden").addClass("bg-rose-50 text-rose-700 border-rose-200").text(errorMsg);
                        $submitBtn.prop("disabled", false).html(originalBtnHtml);
                    }
                },
                error: function (xhr) {
                    let errorMsg = "Đã có lỗi kết nối. Vui lòng gọi trực tiếp hotline để được hỗ trợ tức thì!";
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }
                    $feedback.removeClass("hidden").addClass("bg-rose-50 text-rose-700 border-rose-200").text(errorMsg);
                    $submitBtn.prop("disabled", false).html(originalBtnHtml);
                }
            });
        });
    };

    /**
     * Mobile Menu Toggle và Canvas Menu
     */
    function initMobileMenu() {
        const $menuToggle = $(".header__menu-toggle");
        const $mobileMenu = $(".mobile-menu");
        const $overlay = $(".mobile-menu-overlay");
        const $body = $("body");

        if (!$menuToggle.length || !$mobileMenu.length || !$overlay.length) return;

        function openMenu() {
            $menuToggle.attr("aria-expanded", "true");
            $mobileMenu.attr("aria-hidden", "false");
            $mobileMenu.addClass("active");
            $overlay.addClass("active");
            $body.addClass("mobile-menu-open").css("overflow", "hidden");
        }

        function closeMenu() {
            $menuToggle.attr("aria-expanded", "false");
            $mobileMenu.attr("aria-hidden", "true");
            $mobileMenu.removeClass("active");
            $overlay.removeClass("active");
            $body.removeClass("mobile-menu-open").css("overflow", "");

            // Close tất cả submenu
            $mobileMenu.find(".menu-item-has-children.expanded").removeClass("expanded");
        }

        // Toggle menu
        $menuToggle.on("click", function () {
            const isExpanded = $menuToggle.attr("aria-expanded") === "true";
            if (isExpanded) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        // Close menu khi click overlay
        $overlay.on("click", function () {
            closeMenu();
        });

        // Close menu khi click nút close trong drawer
        $mobileMenu.on("click", ".mobile-menu__close", function () {
            closeMenu();
        });

        // Close menu khi press ESC
        $doc.on("keydown", function (e) {
            if (e.key === "Escape" && $menuToggle.attr("aria-expanded") === "true") {
                closeMenu();
            }
        });

        // Expand/Collapse submenu
        $mobileMenu
            .find(".mobile-menu__list .menu-item-has-children > a")
            .on("click", function (e) {
                const $menuItem = $(this).parent();
                const hasSubmenu = $menuItem.hasClass("menu-item-has-children");

                if (hasSubmenu) {
                    e.preventDefault();
                    const isExpanded = $menuItem.hasClass("expanded");

                    if (isExpanded) {
                        $menuItem.removeClass("expanded");
                    } else {
                        // Close các submenu khác ở cùng level
                        $menuItem.siblings(".menu-item-has-children").removeClass("expanded");
                        $menuItem.addClass("expanded");
                    }
                }
            });
    }

    /**
     * Footer Accordion on Mobile (Apple Style)
     */
    function initFooterAccordion() {
        $doc.on("click", "[data-footer-col] .footer-acc-trigger", function (e) {
            if (window.innerWidth >= 768) return;
            e.preventDefault();
            const $btn = $(this);
            const $content = $btn.next(".footer-acc-content");
            const isHidden = $content.hasClass("hidden");

            if (isHidden) {
                $content.removeClass("hidden");
                $btn.attr("aria-expanded", "true");
            } else {
                $content.addClass("hidden");
                $btn.attr("aria-expanded", "false");
            }
        });
    }

    /**
     * Filter dropdowns (Khu vực, Loại hình, Khoảng giá, Trạng thái)
     */
    function initFilterDropdowns() {
        var $doc = $(document);

        function closeAllPanels(exceptPanel) {
            var $openPanels = $(
                "[data-filter-area-panel].block," +
                "[data-filter-area-range-panel].block," +
                "[data-filter-type-panel].block," +
                "[data-filter-price-panel].block," +
                "[data-filter-status-panel].block," +
                "[data-filter-advanced-panel].block"
            );

            if (!$openPanels.length) return;

            $openPanels.each(function () {
                if (exceptPanel && this === exceptPanel) return;
                var $panel = $(this);
                $panel.removeClass("block").addClass("hidden");

                var $wrapper = $panel.closest(
                    "[data-filter-area],[data-filter-area-range],[data-filter-type],[data-filter-price],[data-filter-status],[data-filter-advanced]"
                );
                if (!$wrapper.length) return;

                var $trigger = $wrapper.find(
                    "[data-filter-area-trigger]," +
                    "[data-filter-area-range-trigger]," +
                    "[data-filter-type-trigger]," +
                    "[data-filter-price-trigger]," +
                    "[data-filter-status-trigger]," +
                    "[data-filter-advanced-trigger]"
                );

                if ($trigger.length) {
                    $trigger.attr("aria-expanded", "false").attr("data-open", "false");
                }
            });

            window.batdongsanFilterCloseAll = closeAllPanels;
        }

        function togglePanel($panel, $trigger, open) {
            var isOpen = typeof open === "boolean" ? open : !$panel.hasClass("block");
            if (isOpen) {
                closeAllPanels($panel.get(0));
                $panel.removeClass("hidden").addClass("block");
                if ($trigger && $trigger.length) {
                    $trigger.attr("aria-expanded", "true").attr("data-open", "true");
                }
            } else {
                $panel.removeClass("block").addClass("hidden");
                if ($trigger && $trigger.length) {
                    $trigger.attr("aria-expanded", "false").attr("data-open", "false");
                }
            }
        }

        // Event delegation mở/đóng dropdown dùng chung cho tất cả các bộ lọc (Khu vực, Loại hình, Trạng thái, Nâng cao)
        $doc.on("click", "[data-filter-area-trigger], [data-filter-type-trigger], [data-filter-status-trigger], [data-filter-advanced-trigger]", function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $trigger = $(this);
            var $wrapper = $trigger.closest("[data-filter-area], [data-filter-type], [data-filter-status], [data-filter-advanced]");
            var $panel = $wrapper.find("[data-filter-area-panel], [data-filter-type-panel], [data-filter-status-panel], [data-filter-advanced-panel]");
            if (!$panel.length) return;
            togglePanel($panel, $trigger);
        });

        $doc.on("click", "[data-filter-area-option]", function (e) {
            e.preventDefault();
            var $opt = $(this);
            var $wrapper = $opt.closest("[data-filter-area]");
            var $panel = $wrapper.find("[data-filter-area-panel]");
            var $trigger = $wrapper.find("[data-filter-area-trigger]");
            var $form = $wrapper.closest("form");
            if (!$form.length) return;

            var $input = $form.find("input[name='khu_vuc'], select[name='khu_vuc']");
            if (!$input.length) {
                $input = $("<input>", { type: "hidden", name: "khu_vuc" }).appendTo($form);
            }

            var val = $opt.data("value");
            $input.val(val);

            $wrapper.find("[data-filter-area-option]").removeClass("text-primary font-semibold");
            $opt.addClass("text-primary font-semibold");

            var labelText = $.trim($opt.text());
            $trigger.find("span").last().text(labelText);

            if (window.FilterStateStore) {
                FilterStateStore.set("khu_vuc", val || "", { source: "area_opt", triggerFetch: false });
            }

            togglePanel($panel, $trigger, false);
            $form.trigger("submit");
        });

        // Loại hình
        $doc.on("click", "[data-filter-type-option]", function (e) {
            e.preventDefault();
            var $opt = $(this);
            var $wrapper = $opt.closest("[data-filter-type]");
            var $panel = $wrapper.find("[data-filter-type-panel]");
            var $trigger = $wrapper.find("[data-filter-type-trigger]");
            var $form = $wrapper.closest("form");
            if (!$form.length) return;

            var $input = $form.find("input[name='type']");
            if (!$input.length) {
                $input = $("<input>", { type: "hidden", name: "type", value: "0" }).appendTo($form);
            }

            var val = String($opt.data("value") || "0");
            $input.val(val);

            $wrapper.find("[data-filter-type-option]")
                .removeAttr("data-active")
                .removeClass("text-primary font-semibold")
                .find("span:first")
                .removeClass("text-primary");

            $opt.attr("data-active", "true").addClass("text-primary font-semibold");
            $opt.find("span").first().addClass("text-primary");

            var labelText = $.trim($opt.find("span").last().text());
            $trigger.find("span").last().text(labelText || "Tất cả loại hình");

            if (window.FilterStateStore) {
                FilterStateStore.set("type", val, { source: "type_opt", triggerFetch: false });
            }

            togglePanel($panel, $trigger, false);

            if (val === "0") {
                if ($form.hasClass("filter-bar-horizontal__form")) {
                    $form.trigger("submit");
                } else {
                    try {
                        var action = $form.attr("action") || window.location.href;
                        var url = new URL(action, window.location.origin);
                        url.searchParams.delete("type");
                        url.searchParams.delete("paged");
                        window.location.href = url.pathname + (url.search || "");
                    } catch (err) {
                        window.location.href = action.split("?")[0];
                    }
                }
            } else {
                $form.trigger("submit");
            }
        });

        // Trạng thái
        $doc.on("click", "[data-filter-status-option]", function (e) {
            e.preventDefault();
            var $opt = $(this);
            var $wrapper = $opt.closest("[data-filter-status]");
            var $panel = $wrapper.find("[data-filter-status-panel]");
            var $trigger = $wrapper.find("[data-filter-status-trigger]");
            var $form = $wrapper.closest("form");
            if (!$form.length) return;

            var $input = $form.find("input[name='status'], select[name='status']");
            if (!$input.length) {
                $input = $("<input>", { type: "hidden", name: "status", value: "0" }).appendTo($form);
            }

            var val = String($opt.data("value") || "0");
            $input.val(val);

            $wrapper.find("[data-filter-status-option]").removeClass("text-primary font-semibold");
            $opt.addClass("text-primary font-semibold");

            var labelText = $.trim($opt.find("span").last().text());
            $trigger.find("span").last().text(labelText || "Tất cả");

            if (window.FilterStateStore) {
                FilterStateStore.set("status", val, { source: "status_opt", triggerFetch: false });
            }

            togglePanel($panel, $trigger, false);
            $form.trigger("submit");
        });

        // Pill clicks trong popover nâng cao
        $doc.on("click", ".adv-pill[data-adv-target]", function (e) {
            e.preventDefault();
            var $pill = $(this);
            var target = $pill.data("adv-target");
            var val = String($pill.data("adv-val") === undefined ? "" : $pill.data("adv-val"));
            var $panel = $pill.closest("[data-filter-advanced-panel]");
            var $form = $panel.closest("form");

            // Cập nhật giao diện pill trong nhóm này
            $panel.find(".adv-pill[data-adv-target='" + target + "']").each(function () {
                var pVal = String($(this).data("adv-val") === undefined ? "" : $(this).data("adv-val"));
                if (pVal === val) {
                    $(this).removeClass("bg-stone/50 text-charcoal hover:bg-stone border-border/60")
                        .addClass("bg-primary text-white shadow-button");
                } else {
                    $(this).removeClass("bg-primary text-white shadow-button")
                        .addClass("bg-stone/50 text-charcoal hover:bg-stone border-border/60");
                }
            });

            // Cập nhật input trong form và FilterStateStore
            if ($form.length) {
                var $input = $form.find("input[name='" + target + "'], select[name='" + target + "']");
                if (!$input.length) {
                    $input = $("<input>", { type: "hidden", name: target }).appendTo($form);
                }
                $input.val(val);
            }
            if (window.FilterStateStore) {
                FilterStateStore.set(target, val, { source: "adv_pill", triggerFetch: false });
            }
        });

        // Nút đặt lại trong popover nâng cao
        $doc.on("click", "[data-filter-advanced-reset]", function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $panel = $btn.closest("[data-filter-advanced-panel]");
            var $wrapper = $panel.closest("[data-filter-advanced]");
            var $form = $wrapper.closest("form");

            // Đặt lại các pills về giá trị rỗng (Tất cả)
            $panel.find(".adv-pill").each(function () {
                var pVal = String($(this).data("adv-val") === undefined ? "" : $(this).data("adv-val"));
                if (pVal === "" || pVal === "all") {
                    $(this).removeClass("bg-stone/50 text-charcoal hover:bg-stone border-border/60")
                        .addClass("bg-primary text-white shadow-button");
                } else {
                    $(this).removeClass("bg-primary text-white shadow-button")
                        .addClass("bg-stone/50 text-charcoal hover:bg-stone border-border/60");
                }
            });

            // Reset inputs
            ["so_phong_ngu", "so_phong_ve_sinh", "huong_nha", "phap_ly"].forEach(function (k) {
                if ($form.length) {
                    $form.find("input[name='" + k + "'], select[name='" + k + "']").val("");
                }
                if (window.FilterStateStore) {
                    FilterStateStore.set(k, "", { source: "adv_reset", triggerFetch: false });
                }
            });

            $wrapper.find("[data-filter-advanced-trigger] span").last().text("Nâng cao");
        });

        // Nút áp dụng trong popover nâng cao
        $doc.on("click", "[data-filter-advanced-apply]", function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $panel = $btn.closest("[data-filter-advanced-panel]");
            var $wrapper = $panel.closest("[data-filter-advanced]");
            var $trigger = $wrapper.find("[data-filter-advanced-trigger]");
            var $chevron = $wrapper.find("[data-filter-advanced-chevron]");
            var $form = $wrapper.closest("form");

            // Đếm tiêu chí đã chọn
            var state = window.FilterStateStore ? FilterStateStore.getAll() : {};
            var count = 0;
            ["so_phong_ngu", "so_phong_ve_sinh", "huong_nha", "phap_ly"].forEach(function (k) {
                var val = state[k] || ($form.length ? $form.find("input[name='" + k + "']").val() : "");
                if (val && val !== "all") count++;
            });

            var $triggerSpan = $trigger.find("span").last();
            if (count > 0) {
                $triggerSpan.html('<span class="inline-flex items-center gap-1.5 text-primary"><span>Đã chọn (' + count + ')</span></span>');
            } else {
                $triggerSpan.text("Nâng cao");
            }

            togglePanel($panel, $trigger, false);
            if ($form.length) {
                $form.trigger("submit");
            }
        });

        // Helper khởi tạo Slider 2 đầu (Khoảng Giá & Diện Tích) - Gom 2 slider trùng lặp 500 dòng
        function bindDualRangeSlider(config) {
            $(config.wrapperSelector).each(function () {
                var wrapper = this;
                var $wrapper = $(wrapper);
                var $trigger = $wrapper.find(config.triggerSelector);
                var $panel = $wrapper.find(config.panelSelector);
                var $chevron = $wrapper.find(config.chevronSelector);
                var $parentForm = $wrapper.closest("form");
                if (!$trigger.length || !$panel.length || !$parentForm.length) return;

                var parentForm = $parentForm.get(0);
                var hiddenInput = parentForm.querySelector(config.hiddenSelector);
                var clearBtn = wrapper.querySelector(config.clearSelector);
                var applyBtn = wrapper.querySelector(config.applySelector);
                var inputMin = wrapper.querySelector(config.inputMinSelector);
                var inputMax = wrapper.querySelector(config.inputMaxSelector);
                var rangeEl = wrapper.querySelector(config.rangeSelector);
                var trackEl = wrapper.querySelector(config.trackSelector);
                var thumbMin = wrapper.querySelector(config.thumbMinSelector);
                var thumbMax = wrapper.querySelector(config.thumbMaxSelector);
                var fillEl = wrapper.querySelector(config.fillSelector);
                var labelMin = wrapper.querySelector(config.labelMinSelector);
                var labelMax = wrapper.querySelector(config.labelMaxSelector);
                var presetLabels = wrapper.querySelectorAll(config.presetSelector);
                var resetLink = $wrapper.find("a[href]").get(0);

                if (!hiddenInput) return;

                $trigger.on("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if ($(e.target).closest(config.clearSelector).length) return;
                    togglePanel($panel, $trigger);
                });

                if (clearBtn && resetLink) {
                    clearBtn.addEventListener("click", function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        window.location.href = resetLink.href;
                    });
                }

                function updateHiddenFromPreset(value) {
                    hiddenInput.value = value || "0";
                }

                function updateHiddenFromCustom() {
                    var min = inputMin && inputMin.value ? parseInt(inputMin.value, 10) : null;
                    var max = inputMax && inputMax.value ? parseInt(inputMax.value, 10) : null;
                    if (min !== null && max !== null && !isNaN(min) && !isNaN(max)) {
                        hiddenInput.value = min + "-" + max;
                    } else if (min !== null && !isNaN(min)) {
                        hiddenInput.value = String(min);
                    }
                }

                presetLabels.forEach(function (label) {
                    label.addEventListener("click", function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var val = label.getAttribute("data-value") || "0";
                        updateHiddenFromPreset(val);
                        presetLabels.forEach(function (l) {
                            l.classList.remove("bg-red-50", "bg-primary-light", "text-primary", "font-medium");
                        });
                        label.classList.add("bg-primary-light", "text-primary", "font-medium");

                        var labelText = $.trim(label.textContent);
                        $trigger.find("span").last().text(val === "0" ? config.defaultLabel : labelText);

                        if (window.FilterStateStore) {
                            FilterStateStore.set(config.stateKey, val, { source: config.stateKey + "_preset", triggerFetch: false });
                        }

                        togglePanel($panel, $trigger, false);
                        if (val === "0" && !$parentForm.hasClass("filter-bar-horizontal__form")) {
                            var baseUrl = wrapper.getAttribute(config.baseUrlAttr);
                            var targetUrl = (baseUrl ? baseUrl.split("?")[0] : parentForm.action.split("?")[0]) || "/bat-dong-san/";
                            window.location.href = targetUrl;
                        } else {
                            $parentForm.trigger("submit");
                        }
                    });
                });

                if (rangeEl && trackEl && thumbMin && thumbMax && fillEl && labelMin && labelMax && inputMin && inputMax) {
                    var boundMin = parseInt(rangeEl.getAttribute("data-bound-min"), 10) || config.defaultBoundMin;
                    var boundMax = parseInt(rangeEl.getAttribute("data-bound-max"), 10) || config.defaultBoundMax;
                    var step = parseInt(rangeEl.getAttribute("data-step"), 10) || config.defaultStep;
                    var rangeVal = boundMax - boundMin;
                    var dragging = null;

                    function pctToVal(pct) {
                        var v = boundMin + (pct / 100) * rangeVal;
                        v = Math.round(v / step) * step;
                        return Math.max(boundMin, Math.min(boundMax, v));
                    }

                    function valToPct(v) {
                        return rangeVal > 0 ? ((v - boundMin) / rangeVal) * 100 : 0;
                    }

                    function updateFromValues(valMin, valMax) {
                        valMin = Math.max(boundMin, Math.min(valMax, valMin));
                        valMax = Math.max(valMin, Math.min(boundMax, valMax));
                        var pctMin = valToPct(valMin);
                        var pctMax = valToPct(valMax);
                        thumbMin.style.left = pctMin + "%";
                        thumbMax.style.left = pctMax + "%";
                        fillEl.style.left = pctMin + "%";
                        fillEl.style.width = pctMax - pctMin + "%";
                        labelMin.textContent = config.formatLabel(valMin, boundMin);
                        labelMax.textContent = config.formatLabel(valMax, boundMin);
                        inputMin.value = valMin;
                        inputMax.value = valMax;
                        hiddenInput.value = valMin + "-" + valMax;
                        thumbMin.setAttribute("aria-valuenow", String(valMin));
                        thumbMax.setAttribute("aria-valuenow", String(valMax));
                    }

                    function handleMove(e) {
                        if (!dragging) return;
                        var rect = trackEl.getBoundingClientRect();
                        var clientX = e.touches ? e.touches[0].clientX : e.clientX;
                        var x = clientX - rect.left;
                        var pct = Math.max(0, Math.min(100, (x / rect.width) * 100));
                        var v = pctToVal(pct);
                        var curMin = parseInt(inputMin.value, 10) || boundMin;
                        var curMax = parseInt(inputMax.value, 10) || boundMax;
                        if (dragging === "min") {
                            updateFromValues(Math.min(v, curMax), curMax);
                        } else {
                            updateFromValues(curMin, Math.max(v, curMin));
                        }
                    }

                    function handleUp() {
                        dragging = null;
                        document.removeEventListener("mousemove", handleMove);
                        document.removeEventListener("mouseup", handleUp);
                        document.removeEventListener("touchmove", handleMove);
                        document.removeEventListener("touchend", handleUp);
                    }

                    thumbMin.addEventListener("mousedown", function (e) {
                        e.preventDefault();
                        dragging = "min";
                        document.addEventListener("mousemove", handleMove);
                        document.addEventListener("mouseup", handleUp);
                    });
                    thumbMax.addEventListener("mousedown", function (e) {
                        e.preventDefault();
                        dragging = "max";
                        document.addEventListener("mousemove", handleMove);
                        document.addEventListener("mouseup", handleUp);
                    });
                    thumbMin.addEventListener("touchstart", function (e) {
                        e.preventDefault();
                        dragging = "min";
                        document.addEventListener("touchmove", handleMove, { passive: false });
                        document.addEventListener("touchend", handleUp);
                    });
                    thumbMax.addEventListener("touchstart", function (e) {
                        e.preventDefault();
                        dragging = "max";
                        document.addEventListener("touchmove", handleMove, { passive: false });
                        document.addEventListener("touchend", handleUp);
                    });

                    inputMin.addEventListener("change", function () {
                        var v = parseInt(inputMin.value, 10);
                        if (!isNaN(v)) {
                            updateFromValues(Math.max(boundMin, Math.min(parseInt(inputMax.value, 10) || boundMax, v)), parseInt(inputMax.value, 10) || boundMax);
                        }
                    });
                    inputMax.addEventListener("change", function () {
                        var v = parseInt(inputMax.value, 10);
                        if (!isNaN(v)) {
                            updateFromValues(parseInt(inputMin.value, 10) || boundMin, Math.min(boundMax, Math.max(parseInt(inputMin.value, 10) || boundMin, v)));
                        }
                    });
                } else {
                    if (inputMin) inputMin.addEventListener("change", updateHiddenFromCustom);
                    if (inputMax) inputMax.addEventListener("change", updateHiddenFromCustom);
                }

                if (applyBtn) {
                    applyBtn.addEventListener("click", function (e) {
                        e.preventDefault();
                        var min = inputMin && inputMin.value ? parseInt(inputMin.value, 10) : null;
                        var max = inputMax && inputMax.value ? parseInt(inputMax.value, 10) : null;
                        var labelText = config.formatApplyLabel(min, max);
                        if (labelText) {
                            $trigger.find("span").last().text(labelText);
                        }
                        if (window.FilterStateStore) {
                            FilterStateStore.set(config.stateKey, hiddenInput.value, { source: config.stateKey + "_apply", triggerFetch: false });
                        }
                        togglePanel($panel, $trigger, false);
                        $parentForm.trigger("submit");
                    });
                }
            });
        }

        // Khởi tạo Diện tích (Area Range Slider)
        bindDualRangeSlider({
            wrapperSelector: "[data-filter-area-range]",
            triggerSelector: "[data-filter-area-range-trigger]",
            panelSelector: "[data-filter-area-range-panel]",
            chevronSelector: "[data-filter-area-range-chevron]",
            hiddenSelector: "[data-filter-area-range-hidden]",
            clearSelector: "[data-filter-area-range-clear]",
            applySelector: "[data-filter-area-range-apply]",
            inputMinSelector: "[data-filter-area-range-input-min]",
            inputMaxSelector: "[data-filter-area-range-input-max]",
            rangeSelector: "[data-filter-area-range-slider]",
            trackSelector: "[data-filter-area-range-track]",
            thumbMinSelector: "[data-filter-area-range-thumb-min]",
            thumbMaxSelector: "[data-filter-area-range-thumb-max]",
            fillSelector: "[data-filter-area-range-fill]",
            labelMinSelector: "[data-filter-area-range-label-min]",
            labelMaxSelector: "[data-filter-area-range-label-max]",
            presetSelector: "[data-filter-area-range-preset-label]",
            baseUrlAttr: "data-filter-area-range-base-url",
            stateKey: "area",
            defaultLabel: "Tất cả diện tích",
            defaultBoundMin: 0,
            defaultBoundMax: 500,
            defaultStep: 5,
            formatLabel: function (v) { return (v == null || isNaN(v)) ? "0 m²" : v + " m²"; },
            formatApplyLabel: function (min, max) {
                if (min !== null && max !== null && !isNaN(min) && !isNaN(max)) {
                    return min + " - " + max + " m²";
                } else if (min !== null && !isNaN(min)) {
                    return "Từ " + min + " m²";
                }
                return "Tất cả diện tích";
            }
        });

        // Khởi tạo Khoảng giá (Price Range Slider)
        bindDualRangeSlider({
            wrapperSelector: "[data-filter-price]",
            triggerSelector: "[data-filter-price-trigger]",
            panelSelector: "[data-filter-price-panel]",
            chevronSelector: "[data-filter-price-chevron]",
            hiddenSelector: "[data-filter-price-hidden]",
            clearSelector: "[data-filter-price-clear]",
            applySelector: "[data-filter-price-apply]",
            inputMinSelector: "[data-filter-price-input-min]",
            inputMaxSelector: "[data-filter-price-input-max]",
            rangeSelector: "[data-filter-price-range]",
            trackSelector: "[data-filter-price-track]",
            thumbMinSelector: "[data-filter-price-thumb-min]",
            thumbMaxSelector: "[data-filter-price-thumb-max]",
            fillSelector: "[data-filter-price-fill]",
            labelMinSelector: "[data-filter-price-label-min]",
            labelMaxSelector: "[data-filter-price-label-max]",
            presetSelector: "[data-filter-price-preset-label]",
            baseUrlAttr: "data-filter-price-base-url",
            stateKey: "price",
            defaultLabel: "Tất cả khoảng giá",
            defaultBoundMin: 0,
            defaultBoundMax: 60000,
            defaultStep: 100,
            formatLabel: function (v, boundMin) {
                if (v == null || isNaN(v)) return boundMin >= 1000 ? boundMin / 1000 + " tỷ" : boundMin + " tr";
                return v >= 1000 ? Math.round(v / 100) / 10 + " tỷ" : v + " tr";
            },
            formatApplyLabel: function (min, max) {
                var fmt = function (v) { return v >= 1000 ? (Math.round(v / 100) / 10) + " tỷ" : v + " tr"; };
                if (min !== null && max !== null && !isNaN(min) && !isNaN(max)) {
                    return fmt(min) + " - " + fmt(max);
                } else if (min !== null && !isNaN(min)) {
                    return "Từ " + fmt(min);
                }
                return "Tất cả khoảng giá";
            }
        });

        // Click bên ngoài đóng tất cả
        $doc.on("click", function (e) {
            var $target = $(e.target);
            if (
                $target.closest("[data-filter-area]").length ||
                $target.closest("[data-filter-area-range]").length ||
                $target.closest("[data-filter-type]").length ||
                $target.closest("[data-filter-price]").length ||
                $target.closest("[data-filter-status]").length ||
                $target.closest("[data-filter-advanced]").length
            ) {
                return;
            }
            closeAllPanels();
        });
    }

    /**
     * Filter Header Modals (mobile) - Khu vực, Loại hình, Khoảng giá
     * Panel được move ra body để phủ toàn màn hình (không bị header chặn)
     */
    function initFilterHeaderModals() {
        var $doc = $(document);

        function getPanelByKind(kind) {
            return $("[data-filter-header-modal-panel][data-filter-header-modal-id='" + kind + "']");
        }

        function getWrapperByKind(kind) {
            return $("[data-filter-header-modal='" + kind + "']").first();
        }

        function closeModal($panel) {
            $panel.removeClass("flex").addClass("hidden");
            $("body").css("overflow", "");
        }

        function openModal($panel) {
            $panel.removeClass("hidden").addClass("flex");
            $("body").css("overflow", "hidden");
        }

        // Di chuyển panel ra body để không bị header chặn (phủ toàn màn hình)
        $("[data-filter-header-modal-panel]").each(function () {
            document.body.appendChild(this);
        });

        $doc.on("click", "[data-filter-header-modal-trigger]", function (e) {
            e.preventDefault();
            var $trigger = $(this);
            var $wrapper = $trigger.closest("[data-filter-header-modal]");
            var kind = $wrapper.attr("data-filter-header-modal") || "";
            var $panel = getPanelByKind(kind);
            if (!$panel.length) return;
            $doc.find("[data-filter-header-modal-panel]").each(function () {
                if (this !== $panel.get(0)) closeModal($(this));
            });
            openModal($panel);
        });

        $doc.on("click", "[data-filter-header-modal-backdrop], [data-filter-header-modal-close]", function (e) {
            e.preventDefault();
            var $panel = $(this).closest("[data-filter-header-modal-panel]");
            if ($panel.length) closeModal($panel);
        });

        $doc.on("click", ".filter-header-modal-option", function (e) {
            e.preventDefault();
            var $opt = $(this);
            var $panel = $opt.closest("[data-filter-header-modal-panel]");
            var kind = $panel.attr("data-filter-header-modal-id") || "";
            var $wrapper = getWrapperByKind(kind);

            $panel.find(".filter-header-modal-option").each(function () {
                MobileAdapter.toggleOptionActive($(this), false);
            });
            MobileAdapter.toggleOptionActive($opt, true);

            var label = $.trim($opt.find("span").first().text() || $opt.text());
            $wrapper.find("[data-filter-header-modal-trigger] span").first().text(label);
        });

        $doc.on("click", "[data-filter-header-modal-reset]", function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $panel = $btn.closest("[data-filter-header-modal-panel]");
            var kind = $panel.attr("data-filter-header-modal-id") || "";
            var $wrapper = getWrapperByKind(kind);
            var $form = $wrapper.closest("form");
            if (!$form.length) return;

            var $first = $panel.find(".filter-header-modal-option").first();
            if ($first.length) {
                $panel.find(".filter-header-modal-option").each(function () {
                    MobileAdapter.toggleOptionActive($(this), false);
                });
                MobileAdapter.toggleOptionActive($first, true);
                var label = $.trim($first.find("span").first().text() || $first.text());
                $wrapper.find("[data-filter-header-modal-trigger] span").first().text(label);
            }

            if (kind === "khu-vuc") {
                var $in = $form.find("[data-filter-area-hidden]");
                if ($in.length) $in.val("");
                if (window.FilterStateStore) {
                    FilterStateStore.set("khu_vuc", "", { source: "modal_reset", triggerFetch: false });
                }
            } else if (kind === "loai-hinh") {
                var $in = $form.find("[data-filter-type-hidden]");
                if ($in.length) $in.val("0");
                if (window.FilterStateStore) {
                    FilterStateStore.set("type", "0", { source: "modal_reset", triggerFetch: false });
                }
            } else if (kind === "khoang-gia") {
                var $in = $form.find("[data-filter-price-hidden]");
                if ($in.length) $in.val("");
                if (window.FilterStateStore) {
                    FilterStateStore.set("price", "", { source: "modal_reset", triggerFetch: false });
                }
            }
            closeModal($panel);
        });

        $doc.on("click", "[data-filter-header-modal-apply]", function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $panel = $btn.closest("[data-filter-header-modal-panel]");
            var kind = $panel.attr("data-filter-header-modal-id") || "";
            var $wrapper = getWrapperByKind(kind);
            var $form = $wrapper.closest("form");
            if (!$form.length) return;

            var $active = $panel.find(".filter-header-modal-option[aria-selected='true']");
            if (!$active.length) $active = $panel.find(".filter-header-modal-option").first();
            var val = $active.length ? String($active.data("value") || "") : "";

            if (kind === "khu-vuc") {
                var $in = $form.find("[data-filter-area-hidden]");
                if ($in.length) $in.val(val);
                if (window.FilterStateStore) {
                    FilterStateStore.set("khu_vuc", val, { source: "modal_apply", triggerFetch: false });
                }
            } else if (kind === "loai-hinh") {
                var $in = $form.find("[data-filter-type-hidden]");
                if ($in.length) $in.val(val);
                if (window.FilterStateStore) {
                    FilterStateStore.set("type", val, { source: "modal_apply", triggerFetch: false });
                }
            } else if (kind === "khoang-gia") {
                var $in = $form.find("[data-filter-price-hidden]");
                if ($in.length) $in.val(val);
                if (window.FilterStateStore) {
                    FilterStateStore.set("price", val, { source: "modal_apply", triggerFetch: false });
                }
            }
            closeModal($panel);
            $form.trigger("submit");
        });
    }

    /**
     * Map Facade - Lazy load Google Maps
     * Chỉ tải iframe khi người dùng click
     */
    function initMapFacade() {
        const mapFacades = document.querySelectorAll('.map-facade');

        if (!mapFacades.length) return;

        mapFacades.forEach(facade => {
            // Skip nếu đã loaded
            if (facade.classList.contains('map-facade--loaded')) return;

            const handleLoad = () => {
                // Prevent double click
                if (facade.classList.contains('map-facade--loading') ||
                    facade.classList.contains('map-facade--loaded')) {
                    return;
                }

                const mapSrc = facade.dataset.mapSrc || facade.dataset.src;
                if (!mapSrc) {
                    console.warn('Map Facade: No map src found');
                    return;
                }

                const iframeContainer = facade.querySelector('.map-facade__iframe-container');

                // Thêm class loading
                facade.classList.add('map-facade--loading');

                // Tạo iframe
                const iframe = document.createElement('iframe');
                iframe.src = mapSrc;
                iframe.style.width = '100%';
                iframe.style.height = '100%';
                iframe.style.border = '0';
                iframe.setAttribute('allowfullscreen', '');
                iframe.setAttribute('referrerpolicy', 'no-referrer-when-downgrade');

                // Function để hiển thị map sau khi load
                const showMap = () => {
                    // Chỉ chạy 1 lần
                    if (facade.classList.contains('map-facade--loaded')) return;

                    facade.classList.remove('map-facade--loading');
                    facade.classList.add('map-facade--loaded');
                };

                // Khi iframe load xong
                iframe.onload = showMap;

                // Error handler
                iframe.onerror = () => {
                    console.error('Map Facade: Failed to load iframe');
                    showMap(); // Vẫn hiển thị để user thấy lỗi từ Google
                };

                // Timeout fallback - hiển thị sau 2 giây dù iframe chưa fire onload
                // (Google Maps iframe không phải lúc nào cũng fire onload event)
                setTimeout(showMap, 2000);

                // Thêm iframe vào container
                if (iframeContainer) {
                    iframeContainer.appendChild(iframe);
                }

                // Remove event listeners
                facade.removeEventListener('click', handleLoad);
                facade.removeEventListener('keydown', handleKeydown);
            };

            const handleKeydown = (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    handleLoad();
                }
            };

            facade.addEventListener('click', handleLoad);
            facade.addEventListener('keydown', handleKeydown);
        });
    }

    // Loan calculator: toggle kết quả trên mobile
    $(document).on("click", "[data-loan-toggle-result]", function () {
        var $btn = $(this);
        var $panel = $("[data-loan-result-panel]");
        var isOpen = !$panel.hasClass("max-md:hidden");
        if (isOpen) {
            $panel.addClass("max-md:hidden");
            $btn.find("[data-loan-toggle-label]").text("Xem kết quả");
            $btn.attr("aria-expanded", "false");
        } else {
            $panel.removeClass("max-md:hidden");
            $btn.find("[data-loan-toggle-label]").text("Thu gọn");
            $btn.attr("aria-expanded", "true");
            $("html, body").animate({ scrollTop: $panel.offset().top - 100 }, 300);
        }
    });

    /**
     * Unified Search Form Controller (F08 - Milestone 3)
     * Handles Transaction Tab Switching, Dropdown Lifecycle, and Filter Synchronization
     */
    function initUnifiedSearchForm() {
        var $form = $("[data-unified-search-form]");
        if (!$form.length) return;

        // Idempotency guard: prevent duplicate event registration across deferred/DOM ready cycles
        if ($form.data("unified-search-initialized") || $form.data("unified-initialized") || $form.attr("data-unified-initialized") === "true") return;
        $form.data("unified-search-initialized", true)
             .data("unified-initialized", true)
             .attr("data-unified-initialized", "true");

        // Ensure all inputs are enabled on initialization (e.g. initial load or bfcache restore)
        $form.find(":disabled").prop("disabled", false);

        var $tabs = $("[data-unified-tabs] [data-unified-tab]");
        if (!$tabs.length) {
            $tabs = $form.find("[data-unified-tab]");
        }
        var $hinhThucInput = $form.find("input[name='hinh_thuc']");
        var $keywordInput = $form.find("input[name='project-name']");

        function closeAllDropdowns(exceptPanel) {
            $form.find("[data-unified-panel]").each(function () {
                if (exceptPanel && this === exceptPanel) return;
                var $panel = $(this);
                if (!$panel.hasClass("hidden")) {
                    $panel.addClass("hidden");
                    var $wrapper = $panel.closest("[data-unified-dropdown]");
                    $wrapper.find("[data-unified-trigger]").attr("aria-expanded", "false");
                    $wrapper.find(".unified-dropdown-arrow").removeClass("rotate-180");
                }
            });
        }

        // 1. Transaction Tab Switching (namespaced event)
        $tabs.off(".unifiedSearch").on("click.unifiedSearch", function (e) {
            e.preventDefault();
            var $tab = $(this);
            var tabType = $tab.data("unified-tab"); // 'ban' | 'cho-thue' | 'du-an'
            var actionUrl = $tab.data("action");
            var hinhThuc = $tab.data("hinh-thuc") || "";
            var placeholder = $tab.data("placeholder");

            // Update tab visual states (Apple Segmented Control: clean white active card)
            $tabs.removeClass("bg-white text-charcoal shadow-sm font-semibold bg-primary text-white shadow-button")
                 .addClass("text-charcoal-muted hover:text-charcoal font-medium")
                 .attr("aria-selected", "false");
            $tab.removeClass("text-charcoal-muted hover:text-charcoal font-medium")
                .addClass("bg-white text-charcoal shadow-sm font-semibold")
                .attr("aria-selected", "true");

            // Update form action & hidden hinh_thuc
            if (actionUrl) {
                $form.attr("action", actionUrl);
            }
            if ($hinhThucInput.length) {
                $hinhThucInput.val(hinhThuc);
            }

            // Update keyword placeholder
            if (placeholder && $keywordInput.length) {
                $keywordInput.attr("placeholder", placeholder);
            }

            // Filter Type dropdown options
            var isDuAn = (tabType === "du-an");
            var $typeDropdown = $form.find("[data-unified-dropdown='type']");
            if ($typeDropdown.length) {
                var $typeOptions = $typeDropdown.find("[data-unified-option], .unified-dropdown-option");
                var currentTypeVal = $typeDropdown.find("[data-unified-input='type']").val();
                var currentTypeStillValid = false;

                $typeOptions.each(function () {
                    var $opt = $(this);
                    var group = $opt.data("group"); // 'all' | 'bds' | 'du-an'
                    if (group === "all") {
                        $opt.show();
                        if (currentTypeVal === "0" || currentTypeVal === "") currentTypeStillValid = true;
                    } else if (isDuAn && group === "du-an") {
                        $opt.show();
                        if ($opt.data("value") == currentTypeVal) currentTypeStillValid = true;
                    } else if (!isDuAn && group === "bds") {
                        $opt.show();
                        if ($opt.data("value") == currentTypeVal) currentTypeStillValid = true;
                    } else {
                        $opt.hide();
                    }
                });

                if (!currentTypeStillValid) {
                    $typeDropdown.find("[data-unified-input='type']").val("0");
                    $typeDropdown.find(".unified-dropdown-label").text("Tất cả loại hình");
                    $typeOptions.removeClass("text-primary font-semibold bg-stone/30");
                    $typeDropdown.find("[data-group='all']").addClass("text-primary font-semibold bg-stone/30");
                }
            }

            // Filter Price dropdown options
            var $priceDropdown = $form.find("[data-unified-dropdown='price']");
            if ($priceDropdown.length) {
                var $priceOptions = $priceDropdown.find("[data-unified-option], .unified-dropdown-option");
                var currentPriceVal = $priceDropdown.find("[data-unified-input='price']").val();
                var currentPriceStillValid = false;

                $priceOptions.each(function () {
                    var $opt = $(this);
                    var priceGroup = $opt.data("price-group"); // 'all' | 'bds' | 'du-an'
                    if (priceGroup === "all") {
                        $opt.show();
                        if (currentPriceVal === "0" || currentPriceVal === "") currentPriceStillValid = true;
                    } else if (isDuAn && priceGroup === "du-an") {
                        $opt.show();
                        if ($opt.data("value") == currentPriceVal) currentPriceStillValid = true;
                    } else if (!isDuAn && priceGroup === "bds") {
                        $opt.show();
                        if ($opt.data("value") == currentPriceVal) currentPriceStillValid = true;
                    } else {
                        $opt.hide();
                    }
                });

                if (!currentPriceStillValid) {
                    $priceDropdown.find("[data-unified-input='price']").val("0");
                    $priceDropdown.find(".unified-dropdown-label").text("Tất cả khoảng giá");
                    $priceOptions.removeClass("text-primary font-semibold bg-stone/30");
                    $priceDropdown.find("[data-price-group='all']").addClass("text-primary font-semibold bg-stone/30");
                }
            }
        });

        // 2. Dropdown open/close (namespaced to avoid duplicate listeners)
        $form.off("click.unifiedSearch", "[data-unified-trigger]").on("click.unifiedSearch", "[data-unified-trigger]", function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $trigger = $(this);
            var $wrapper = $trigger.closest("[data-unified-dropdown]");
            var $panel = $wrapper.find("[data-unified-panel]");
            var $arrow = $wrapper.find(".unified-dropdown-arrow");
            var isOpen = !$panel.hasClass("hidden");

            closeAllDropdowns($panel[0]);

            if (isOpen) {
                $panel.addClass("hidden");
                $trigger.attr("aria-expanded", "false");
                $arrow.removeClass("rotate-180");
            } else {
                $panel.removeClass("hidden");
                $trigger.attr("aria-expanded", "true");
                $arrow.addClass("rotate-180");
            }
        });

        // 3. Option selection (namespaced)
        $form.off("click.unifiedSearch", "[data-unified-option], .unified-dropdown-option").on("click.unifiedSearch", "[data-unified-option], .unified-dropdown-option", function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $opt = $(this);
            var $wrapper = $opt.closest("[data-unified-dropdown]");
            var $panel = $wrapper.find("[data-unified-panel]");
            var $trigger = $wrapper.find("[data-unified-trigger]");
            var $label = $trigger.find(".unified-dropdown-label");
            var $arrow = $wrapper.find(".unified-dropdown-arrow");
            var $input = $wrapper.find("[data-unified-input]");

            var val = String($opt.data("value") !== undefined ? $opt.data("value") : "");
            var label = $opt.data("label") || $opt.find("span").first().text().trim();

            $input.val(val);
            $label.text(label);

            $wrapper.find("[data-unified-option], .unified-dropdown-option").removeClass("text-primary font-semibold bg-stone/30");
            $opt.addClass("text-primary font-semibold bg-stone/30");

            $panel.addClass("hidden");
            $trigger.attr("aria-expanded", "false");
            $arrow.removeClass("rotate-180");
        });

        // 4. Outside click closes all dropdowns (namespaced)
        $(document).off("click.unifiedSearch").on("click.unifiedSearch", function (e) {
            if (!$(e.target).closest("[data-unified-dropdown]").length) {
                closeAllDropdowns();
            }
        });

        // 5. Escape key listener (namespaced)
        $(document).off("keydown.unifiedSearch").on("keydown.unifiedSearch", function (e) {
            if (e.key === "Escape") {
                closeAllDropdowns();
            }
        });

        // 6. Clean submit handler: disable empty inputs before submit to keep clean GET query string
        $form.off("submit.unifiedSearch").on("submit.unifiedSearch", function () {
            closeAllDropdowns();
            $form.find("input").each(function () {
                var $inp = $(this);
                var val = $inp.val();
                if (val === "" || val === "0" || val === null) {
                    if ($inp.attr("name") !== "project-name" && $inp.attr("name") !== "s") {
                        $inp.prop("disabled", true);
                    }
                }
            });
            // Re-enable disabled inputs shortly after submission starts (in case navigation is cancelled or delayed)
            setTimeout(function () {
                $form.find(":disabled").prop("disabled", false);
            }, 500);
            return true;
        });

        // 7. bfcache navigation & page restoration: re-enable disabled inputs when navigating back
        $(window).off("pageshow.unifiedSearch").on("pageshow.unifiedSearch", function () {
            $form.find(":disabled").prop("disabled", false);
        });

        // Initialize active tab's dropdown visibility
        var $activeTab = $tabs.filter(".bg-white, .bg-primary, [aria-selected='true']").first();
        if ($activeTab.length) {
            $activeTab.triggerHandler("click");
        }
    }

    // Khởi tạo filter dropdowns, filter header modals & Map Facade
    initFilterDropdowns();
    initFilterHeaderModals();
    initMapFacade();
    initUnifiedSearchForm();
    initMobileImmersiveBars();

    /**
     * Mobile Immersive Auto-Hide Header & Footer Bars (<= 1024px)
     * - Khi lướt xuống (scroll down): Cụp header lên và bottom bar xuống ngay lập tức để tối ưu không gian đọc.
     * - Khi lướt lên (scroll up) hoặc chạm màn hình: Hiện lại ngay lập tức để người dùng thao tác.
     * - Khi dừng thao tác (idle ~2.5s) ở giữa trang: Tự động cụp lại gọn gàng.
     * - Ở sát đầu trang (<= 60px): Luôn hiển thị đầy đủ logo & menu.
     */
    function initMobileImmersiveBars() {
        if (typeof window === "undefined") return;

        function getHeader() {
            return $("header.header");
        }

        function getBottomBars() {
            return $(
                ".sticky-contact-bar, .bds-mobile-bottom-bar, div[class*='fixed bottom-'][class*='lg:hidden'], div.fixed.bottom-0.inset-x-0.lg\\:hidden"
            );
        }

        if (!getHeader().length) return;

        let idleTimer = null;
        let ticking = false;
        let lastScrollY = window.scrollY || window.pageYOffset || 0;
        const SCROLL_THRESHOLD = 6; // Ngưỡng nhạy nhận diện hướng cuộn
        const IDLE_DELAY = 2500;    // 2.5s không thao tác thì tự cụp lại

        function isMobile() {
            return (
                window.innerWidth <= 1024 ||
                (window.matchMedia && window.matchMedia("(max-width: 1024px)").matches)
            );
        }

        function isMenuOrModalOpen() {
            return (
                $("body").hasClass("mobile-menu-open") ||
                $("body").hasClass("modal-open") ||
                $(".mobile-menu.active").length > 0 ||
                $("[data-drawer-toggle][aria-expanded='true']").length > 0 ||
                $("[data-filter-header-modal-panel]:not(.hidden)").length > 0 ||
                $("#advisor-modal[aria-hidden='false']").length > 0
            );
        }

        function isInputFocused() {
            const active = document.activeElement;
            if (!active) return false;
            const tag = active.tagName ? active.tagName.toLowerCase() : "";
            return (
                tag === "input" ||
                tag === "textarea" ||
                tag === "select" ||
                active.isContentEditable
            );
        }

        function showBars() {
            getHeader().removeClass("header--collapsed");
            getBottomBars().removeClass("bar--collapsed");
        }

        function collapseBars() {
            if (!isMobile()) {
                showBars();
                return;
            }
            // Không cụp khi ở sát đầu trang (để lộ logo và navigation ban đầu)
            const scrollY = window.scrollY || window.pageYOffset || 0;
            if (scrollY <= 60) {
                showBars();
                return;
            }
            // Không cụp khi đang mở menu, modal hoặc đang gõ text
            if (isMenuOrModalOpen() || isInputFocused()) {
                return;
            }
            getHeader().addClass("header--collapsed");
            getBottomBars().addClass("bar--collapsed");
        }

        function scheduleCollapse() {
            clearTimeout(idleTimer);
            if (!isMobile()) return;
            const scrollY = window.scrollY || window.pageYOffset || 0;
            if (scrollY <= 60 || isMenuOrModalOpen() || isInputFocused()) {
                return;
            }
            idleTimer = setTimeout(collapseBars, IDLE_DELAY);
        }

        function handleScroll() {
            if (!isMobile()) {
                showBars();
                return;
            }

            const scrollY = window.scrollY || window.pageYOffset || 0;
            const deltaY = scrollY - lastScrollY;

            // Ở sát đầu trang: luôn hiển thị nguyên vẹn
            if (scrollY <= 60) {
                showBars();
                clearTimeout(idleTimer);
                lastScrollY = scrollY;
                return;
            }

            // Lướt xuống (Scroll Down): Cụp ngay lập tức để người dùng xem tin/dự án
            if (deltaY > SCROLL_THRESHOLD) {
                collapseBars();
                clearTimeout(idleTimer);
            }
            // Lướt lên (Scroll Up): Hiện ngay lập tức để người dùng thao tác menu/hotline
            else if (deltaY < -SCROLL_THRESHOLD) {
                showBars();
                scheduleCollapse(); // Sau khi dừng lướt lên, tự động cụp lại sau 2.5s
            }

            lastScrollY = scrollY;
        }

        // Lắng nghe sự kiện scroll (dùng rAF throttle để đạt 60fps mượt mà)
        window.addEventListener(
            "scroll",
            function () {
                if (!ticking) {
                    window.requestAnimationFrame(function () {
                        handleScroll();
                        ticking = false;
                    });
                    ticking = true;
                }
            },
            { passive: true }
        );

        // Chạm vào màn hình -> nếu đang dừng đọc, hiện nhẹ và hẹn giờ cụp lại
        window.addEventListener(
            "touchstart",
            function () {
                if (!isMobile()) return;
                const scrollY = window.scrollY || window.pageYOffset || 0;
                if (scrollY > 60) {
                    scheduleCollapse();
                }
            },
            { passive: true }
        );

        // Tương tác hoặc click vào header / bottom bar -> hiện và reset timer
        $(document).on("click touchstart", "header.header, .sticky-contact-bar", function () {
            showBars();
            scheduleCollapse();
        });

        // Khi resize cửa sổ (xoay ngang điện thoại hoặc đổi kích thước)
        window.addEventListener(
            "resize",
            function () {
                if (!isMobile()) {
                    clearTimeout(idleTimer);
                    showBars();
                } else {
                    scheduleCollapse();
                }
            },
            { passive: true }
        );

        // Khởi động nếu trang tải ở vị trí đã cuộn sẵn
        if (isMobile() && (window.scrollY || window.pageYOffset || 0) > 60) {
            scheduleCollapse();
        }
    }

    /**
     * Loan Calculator - Ước tính khoản vay
     */
})(jQuery);

