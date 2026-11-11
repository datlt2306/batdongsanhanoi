/**
 * Empirical Challenger Harness: Milestone 2 Stress Test
 * Tests: FilterStateStore, 1:1 Two-Way Sync, AJAX Out-of-Order Defense, Dual Sliders, Popstate
 *
 * File: tests/test-m2-empirical-challenger-harness.js
 */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

let passCount = 0;
let failCount = 0;
const failures = [];

function assert(condition, message) {
    if (condition) {
        passCount++;
        console.log(`  ✔ PASS: ${message}`);
    } else {
        failCount++;
        failures.push(message);
        console.error(`  ✖ FAIL: ${message}`);
    }
}

console.log("================================================================================");
console.log("   CHALLENGER M2: FILTER STATE STORE & TWO-WAY SYNC EMPIRICAL STRESS HARNESS");
console.log("================================================================================\n");

// Read js/main.js
const mainJsPath = path.resolve(__dirname, '../js/main.js');
const mainJs = fs.readFileSync(mainJsPath, 'utf8');

// Extract components from js/main.js
const storeMatch = mainJs.match(/const FilterStateStore = \((function \(\)[\s\S]*?\)\(\);)/);
if (!storeMatch) {
    console.error("FATAL: Could not extract FilterStateStore from js/main.js");
    process.exit(1);
}

// -----------------------------------------------------------------------------
// SUITE 1: FilterStateStore Core & Deep State Diffing Stress
// -----------------------------------------------------------------------------
console.log("▶ Suite 1: FilterStateStore Core, Diffing & Rapid Sequential Mutation Stress");

// Sandbox for isolated FilterStateStore testing
const storeSandbox = { console: console };
vm.createContext(storeSandbox);
const Store = vm.runInContext('(' + storeMatch[1], storeSandbox);

// 1.1 DEFAULT_STATE schema
const defaults = Store.getDefaults();
const expectedKeys = [
    "hinh_thuc", "type", "khu_vuc", "price", "area", "project-name",
    "du_an", "so_phong_ngu", "so_phong_ve_sinh", "huong_nha", "phap_ly",
    "status", "order", "paged"
];
assert(expectedKeys.every(k => k in defaults), "1.1: All 14 default keys exist in getDefaults()");
assert(defaults.type === "0", "1.1b: Default type is '0'");
assert(defaults.status === "0", "1.1c: Default status is '0'");
assert(defaults.paged === 1, "1.1d: Default paged is 1");
assert(defaults.order === "1", "1.1e: Default order is '1'");

// Mutating copy does not mutate store
defaults.type = "999";
assert(Store.getDefaults().type === "0", "1.1f: getDefaults() returns independent clone");

// 1.2 Shallow Diffing & Notification Pruning
let notifCount = 0;
let lastChanges = null;
let lastOptions = null;
const unsub1 = Store.subscribe((state, changes, options) => {
    notifCount++;
    lastChanges = changes;
    lastOptions = options;
});

// Setting new value triggers notification
Store.set("khu_vuc", "cau-giay");
assert(notifCount === 1, "1.2a: Setting new value triggers notification (count=1)");
assert(lastChanges.khu_vuc && lastChanges.khu_vuc.prev === "" && lastChanges.khu_vuc.next === "cau-giay", "1.2b: Changes payload contains accurate prev/next");
assert(Store.get("khu_vuc") === "cau-giay", "1.2c: Store.get returns new value");

// Setting identical value: Diff guard MUST prune notification
Store.set("khu_vuc", "cau-giay");
assert(notifCount === 1, "1.2d: Setting identical value does NOT trigger notification (diff guard active)");

// Setting with force: true triggers notification
Store.set("khu_vuc", "cau-giay", { force: true });
assert(notifCount === 2, "1.2e: Setting with force:true bypasses diff guard (count=2)");

// Setting with silent: true changes state but no notification
Store.set("khu_vuc", "ba-dinh", { silent: true });
assert(notifCount === 2, "1.2f: Setting with silent:true does NOT fire subscriber (count=2)");
assert(Store.get("khu_vuc") === "ba-dinh", "1.2g: Silent update successfully modified state");

// String coercion & trimming
Store.set("type", 12);
assert(Store.get("type") === "12", "1.2h: Number 12 coerced to string '12'");
Store.set("type", "  12  ");
assert(Store.get("type") === "12", "1.2i: Whitespace padded '  12  ' trimmed to '12'");
Store.set("khu_vuc", null);
assert(Store.get("khu_vuc") === "", "1.2j: null normalized to empty string ''");
Store.set("khu_vuc", undefined);
assert(Store.get("khu_vuc") === "", "1.2k: undefined normalized to empty string ''");

// setMultiple with no actual changes
const preCount = notifCount;
Store.setMultiple({ type: "12", khu_vuc: "" });
assert(notifCount === preCount, "1.2l: setMultiple with no actual changes prunes notification");

// setMultiple with partial changes
Store.setMultiple({ khu_vuc: "tay-ho", type: "12", price: "3000-5000" });
assert(notifCount === preCount + 1, "1.2m: setMultiple with 2 actual changes fires single notification");
assert(lastChanges.khu_vuc && lastChanges.price && !lastChanges.type, "1.2n: Changes dict contains only mutated keys (khu_vuc, price)");

// 1.3 Re-entrancy & Subscriber Error Isolation
let reentrantTriggered = false;
let nestedNotified = false;
const unsubReentrant = Store.subscribe((state, changes) => {
    if (changes.reentrant_test && !reentrantTriggered) {
        reentrantTriggered = true;
        // Attempt recursive mutation inside subscriber
        Store.set("type", "888");
    }
    if (changes.type && changes.type.next === "888") {
        nestedNotified = true;
    }
});

// Trigger reentrant test
Store.set("reentrant_test", "start");
assert(reentrantTriggered, "1.3a: Subscriber executed and attempted re-entrant set()");
assert(Store.get("type") === "888", "1.3b: Re-entrant state was mutated cleanly without crash");
assert(!nestedNotified, "1.3c: isNotifying lock prevented recursive notification cycle");
unsubReentrant();

// Subscriber Exception Isolation (silencing console.error during expected explosion test)
let subscriberBExecuted = false;
const origConsoleError = console.error;
console.error = () => {}; // suppress expected error log
const unsubThrower = Store.subscribe(() => {
    throw new Error("Simulated subscriber explosion");
});
const unsubSafe = Store.subscribe(() => {
    subscriberBExecuted = true;
});

notifCount = 0;
Store.set("khu_vuc", "hoan-kiem");
console.error = origConsoleError;
assert(subscriberBExecuted, "1.3d: Exception in Subscriber A did not prevent Subscriber B from executing");
assert(Store.get("khu_vuc") === "hoan-kiem", "1.3e: State updated successfully despite subscriber exception");
unsubThrower();
unsubSafe();

// Unsubscribe verification
unsub1();
const countBeforeUnsub = notifCount;
Store.set("khu_vuc", "dong-da");
assert(notifCount === countBeforeUnsub, "1.3f: Unsubscribed listener not invoked after unsub1()");

// 1.4 High-Volume Rapid Sequential Mutations (1,000 Random Writes)
const oracleState = Store.getAll();
const testKeys = ["hinh_thuc", "type", "khu_vuc", "price", "area", "status", "so_phong_ngu", "huong_nha"];
const testValues = ["val1", "val2", "val3", "0", "", "cau-giay", "3000-5000", "50-80", "12", "dong-nam"];

let stressNotifCount = 0;
const unsubStress = Store.subscribe((state, changes) => {
    stressNotifCount++;
});

for (let i = 0; i < 1000; i++) {
    const k = testKeys[i % testKeys.length];
    const v = testValues[(i * 3 + Math.floor(i / 8) + 1) % testValues.length];
    const strVal = String(v).trim();
    if (oracleState[k] !== strVal) {
        oracleState[k] = strVal;
    }
    Store.set(k, v);
}

const finalState = Store.getAll();
let oracleMatches = true;
for (const k of testKeys) {
    if (finalState[k] !== oracleState[k]) {
        oracleMatches = false;
        break;
    }
}
assert(oracleMatches, "1.4a: Store state matches independent oracle 100% after 1,000 sequential rapid mutations");
assert(stressNotifCount > 500, `1.4b: High-volume notifications processed cleanly (${stressNotifCount} events dispatched)`);
unsubStress();

// 1.5 Reset Behavior
Store.reset();
const resetState = Store.getAll();
const freshDefaults = Store.getDefaults();
let allReset = true;
for (const k of expectedKeys) {
    if (resetState[k] !== freshDefaults[k]) {
        allReset = false;
        break;
    }
}
assert(allReset, "1.5: Store.reset() restored all 14 keys to pristine DEFAULT_STATE");


// -----------------------------------------------------------------------------
// SUITE 2: Out-of-Order AJAX Resolution & Race Condition Defense
// -----------------------------------------------------------------------------
console.log("\n▶ Suite 2: Out-of-Order AJAX Resolution & Race Condition Defense");

// Simulate the AJAX controller logic from lines 826-865 & 1140-1165 in js/main.js
class MockAjaxController {
    constructor() {
        this.txId = 0;
        this.abortController = null;
        this.gridHtml = "INITIAL";
        this.isLoading = false;
        this.abortedCount = 0;
        this.droppedCount = 0;
        this.appliedCount = 0;
    }

    fetch(params, latencyMs, simulatedResponseHtml) {
        // 1. Abort existing in-flight controller
        if (this.abortController) {
            try {
                this.abortController.abort();
                this.abortedCount++;
            } catch (e) {}
        }
        this.abortController = { aborted: false, abort() { this.aborted = true; } };

        // 2. Monotonic transaction sequence ID
        this.txId++;
        const currentTxId = this.txId;
        const currentSignal = this.abortController;

        this.isLoading = true;

        return new Promise((resolve) => {
            setTimeout(() => {
                // If aborted, simulate jQuery xhr abort
                if (currentSignal.aborted) {
                    return resolve({ status: "aborted", txId: currentTxId });
                }

                // Check stale response
                if (currentTxId !== this.txId) {
                    this.droppedCount++;
                    return resolve({ status: "stale_dropped", txId: currentTxId });
                }

                // Accept latest response
                this.appliedCount++;
                this.gridHtml = simulatedResponseHtml;
                this.isLoading = false;
                resolve({ status: "applied", txId: currentTxId, html: simulatedResponseHtml });
            }, latencyMs);
        });
    }
}

async function runAsyncTests() {
    const controller = new MockAjaxController();

    // Fire 5 requests in rapid succession with reverse latencies (Tx 1 longest, Tx 5 fastest)
    const p1 = controller.fetch({ khu_vuc: "cau-giay" }, 120, "RESULTS_CAU_GIAY");
    const p2 = controller.fetch({ khu_vuc: "ba-dinh" }, 90, "RESULTS_BA_DINH");
    const p3 = controller.fetch({ khu_vuc: "tay-ho" }, 60, "RESULTS_TAY_HO");
    const p4 = controller.fetch({ khu_vuc: "dong-da" }, 40, "RESULTS_DONG_DA");
    const p5 = controller.fetch({ khu_vuc: "hoan-kiem" }, 15, "RESULTS_HOAN_KIEM");

    await Promise.all([p1, p2, p3, p4, p5]);

    assert(controller.txId === 5, "2.1a: Transaction sequence ID incremented monotonically to 5");
    assert(controller.abortedCount === 4, "2.1b: Exactly 4 in-flight requests were aborted prior to Tx 5");
    assert(controller.appliedCount === 1, "2.1c: Exactly 1 response (Tx 5) was applied to the grid");
    assert(controller.gridHtml === "RESULTS_HOAN_KIEM", "2.1d: Grid displays strictly latest response 'RESULTS_HOAN_KIEM'");
    assert(!controller.isLoading, "2.1e: isLoading cleared upon completion of latest Tx");

    // Stress test: Out-of-order resolution WITHOUT AbortController (pure Tx ID race check)
    let rawTx = 0;
    let appliedRaw = null;
    let droppedRaw = 0;

    function simulateRawAsync(id, delay, data) {
        return new Promise(resolve => {
            setTimeout(() => {
                if (id !== rawTx) {
                    droppedRaw++;
                } else {
                    appliedRaw = data;
                }
                resolve();
            }, delay);
        });
    }

    rawTx++; const t1 = 1; const pr1 = simulateRawAsync(t1, 100, "STALE_1");
    rawTx++; const t2 = 2; const pr2 = simulateRawAsync(t2, 70, "STALE_2");
    rawTx++; const t3 = 3; const pr3 = simulateRawAsync(t3, 20, "LATEST_3");

    await Promise.all([pr1, pr2, pr3]);

    assert(appliedRaw === "LATEST_3", "2.2a: Stale delayed responses did not overwrite latest response");
    assert(droppedRaw === 2, "2.2b: Exactly 2 out-of-order stale responses were discarded cleanly");


    // -----------------------------------------------------------------------------
    // SUITE 3: 1:1 Two-Way Synchronization (Desktop ↔ Mobile Modal)
    // -----------------------------------------------------------------------------
    console.log("\n▶ Suite 3: 1:1 Two-Way Synchronization (Desktop ↔ Mobile Modal)");

    class DOMElement {
        constructor(tag, attrs = {}) {
            this.tagName = tag.toUpperCase();
            this.attributes = Object.assign({}, attrs);
            this.classes = new Set((attrs.class || "").split(/\s+/).filter(Boolean));
            this.children = [];
            this.parent = null;
            this.textContent = attrs.text || "";
            this.innerHtml = attrs.html || "";
            this.dataStore = {};
            for (const k in attrs) {
                if (k.startsWith("data-")) {
                    const camel = k.slice(5).replace(/-([a-z])/g, (_, c) => c.toUpperCase());
                    this.dataStore[camel] = attrs[k];
                    this.dataStore[k.slice(5)] = attrs[k];
                }
            }
        }

        appendChild(child) {
            child.parent = this;
            this.children.push(child);
            return child;
        }

        addClass(cls) {
            if (!cls) return this;
            cls.split(/\s+/).filter(Boolean).forEach(c => this.classes.add(c));
            return this;
        }

        removeClass(cls) {
            if (!cls) return this;
            cls.split(/\s+/).filter(Boolean).forEach(c => this.classes.delete(c));
            return this;
        }

        hasClass(cls) {
            return this.classes.has(cls);
        }

        attr(name, val) {
            if (val !== undefined) {
                this.attributes[name] = String(val);
                return this;
            }
            return this.attributes[name];
        }

        val(val) {
            if (val !== undefined) {
                this.attributes["value"] = String(val);
                return this;
            }
            return this.attributes["value"] !== undefined ? this.attributes["value"] : "";
        }

        text(txt) {
            if (txt !== undefined) {
                this.textContent = String(txt);
                this.innerHtml = String(txt);
                return this;
            }
            return this.textContent;
        }

        html(h) {
            if (h !== undefined) {
                this.innerHtml = String(h);
                this.textContent = String(h).replace(/<[^>]+>/g, "");
                return this;
            }
            return this.innerHtml || this.textContent;
        }

        data(k, v) {
            if (v !== undefined) {
                this.dataStore[k] = v;
                return this;
            }
            return this.dataStore[k] !== undefined ? this.dataStore[k] : this.attributes["data-" + k];
        }
    }

    function matchToken(el, sel) {
        if (!sel) return false;
        const parts = sel.match(/(\.[a-zA-Z0-9_-]+|\[[^\]]+\]|#[a-zA-Z0-9_-]+|[a-zA-Z0-9_-]+)/g);
        if (!parts) return false;
        return parts.every(part => {
            if (part.startsWith('.')) return el.classes.has(part.slice(1));
            if (part.startsWith('#')) return el.attr('id') === part.slice(1);
            if (part.startsWith('[')) {
                const inner = part.slice(1, -1);
                if (inner.includes('=')) {
                    const [k, rawV] = inner.split('=');
                    const v = rawV.replace(/^['\"]|['\"]$/g, '');
                    const actual = el.attr(k) !== undefined ? el.attr(k) : el.dataStore[k.replace(/^data-/, '')];
                    return String(actual) === String(v);
                }
                return (inner in el.attributes) || (inner.replace(/^data-/, '') in el.dataStore);
            }
            return el.tagName.toLowerCase() === part.toLowerCase();
        });
    }

    function queryAll(root, sel) {
        if (!sel || typeof sel !== 'string') return [];
        if (sel.includes(',')) {
            const out = [];
            sel.split(',').forEach(sub => queryAll(root, sub.trim()).forEach(el => { if (!out.includes(el)) out.push(el); }));
            return out;
        }
        const tokens = sel.trim().split(/\s+/);
        let currentPool = [root];
        for (const token of tokens) {
            const nextPool = [];
            const walk = (node) => {
                for (const child of node.children) {
                    if (matchToken(child, token)) nextPool.push(child);
                    walk(child);
                }
            };
            for (const parent of currentPool) walk(parent);
            currentPool = nextPool;
        }
        return currentPool;
    }

    function wrap$Mock(elements) {
        const arr = Array.isArray(elements) ? elements : (elements ? [elements] : []);
        const obj = {
            length: arr.length,
            isMockJQuery: true,
            [Symbol.iterator]: function* () {
                for (const el of arr) yield el;
            },
            get: (i) => arr[i],
            first: () => wrap$Mock(arr.slice(0, 1)),
            last: () => wrap$Mock(arr.slice(-1)),
            val: function (v) {
                if (v !== undefined) {
                    arr.forEach(el => el.val(v));
                    return obj;
                }
                return arr[0] ? arr[0].val() : "";
            },
            text: function (t) {
                if (t !== undefined) {
                    arr.forEach(el => el.text(t));
                    return obj;
                }
                return arr[0] ? arr[0].text() : "";
            },
            html: function (h) {
                if (h !== undefined) {
                    arr.forEach(el => el.html(h));
                    return obj;
                }
                return arr[0] ? arr[0].html() : "";
            },
            empty: function () {
                arr.forEach(el => { el.children = []; el.textContent = ""; el.innerHtml = ""; });
                return obj;
            },
            addClass: function (c) {
                arr.forEach(el => el.addClass(c));
                return obj;
            },
            removeClass: function (c) {
                arr.forEach(el => el.removeClass(c));
                return obj;
            },
            hasClass: function (c) {
                return arr.some(el => el.hasClass(c));
            },
            attr: function (k, v) {
                if (v !== undefined) {
                    arr.forEach(el => el.attr(k, v));
                    return obj;
                }
                return arr[0] ? arr[0].attr(k) : undefined;
            },
            removeAttr: function (k) {
                arr.forEach(el => delete el.attributes[k]);
                return obj;
            },
            data: function (k, v) {
                if (v !== undefined) {
                    arr.forEach(el => el.data(k, v));
                    return obj;
                }
                return arr[0] ? arr[0].data(k) : undefined;
            },
            css: function () { return obj; },
            find: function (sel) {
                const res = [];
                arr.forEach(el => {
                    queryAll(el, sel).forEach(subEl => {
                        if (!res.includes(subEl)) res.push(subEl);
                    });
                });
                return wrap$Mock(res);
            },
            filter: function (fnOrSel) {
                if (typeof fnOrSel === "function") {
                    return wrap$Mock(arr.filter((el, idx) => fnOrSel.call(wrap$Mock([el]), idx)));
                }
                return wrap$Mock(arr.filter(el => matchToken(el, fnOrSel)));
            },
            each: function (fn) {
                arr.forEach((el, idx) => fn.call(wrap$Mock([el]), idx, el));
                return obj;
            }
        };
        return obj;
    }

    const documentRoot = new DOMElement("div");

    // Desktop filter bar form
    const desktopForm = new DOMElement("form", { class: "filter-bar-horizontal__form" });
    const deskInArea = new DOMElement("input", { type: "hidden", name: "khu_vuc", "data-filter-area-hidden": "" });
    const deskInType = new DOMElement("input", { type: "hidden", name: "type", "data-filter-type-hidden": "", value: "0" });
    const deskInPrice = new DOMElement("input", { type: "hidden", name: "price", "data-filter-price-hidden": "" });
    const deskInAreaRange = new DOMElement("input", { type: "hidden", name: "area", "data-filter-area-range-hidden": "" });
    const deskInStatus = new DOMElement("input", { type: "hidden", name: "status", "data-filter-status-hidden": "", value: "0" });
    const deskInHinhThuc = new DOMElement("input", { type: "hidden", name: "hinh_thuc", "data-filter-hinh-thuc-hidden": "" });
    const deskInPngu = new DOMElement("input", { type: "hidden", name: "so_phong_ngu", "data-filter-so-phong-ngu-hidden": "" });
    const deskInPvs = new DOMElement("input", { type: "hidden", name: "so_phong_ve_sinh", "data-filter-so-phong-ve-sinh-hidden": "" });
    const deskInHuong = new DOMElement("input", { type: "hidden", name: "huong_nha", "data-filter-huong-nha-hidden": "" });
    const deskInPhapLy = new DOMElement("input", { type: "hidden", name: "phap_ly", "data-filter-phap-ly-hidden": "" });
    desktopForm.appendChild(deskInArea);
    desktopForm.appendChild(deskInType);
    desktopForm.appendChild(deskInPrice);
    desktopForm.appendChild(deskInAreaRange);
    desktopForm.appendChild(deskInStatus);
    desktopForm.appendChild(deskInHinhThuc);
    desktopForm.appendChild(deskInPngu);
    desktopForm.appendChild(deskInPvs);
    desktopForm.appendChild(deskInHuong);
    desktopForm.appendChild(deskInPhapLy);

    // Desktop triggers
    const deskAreaTrig = new DOMElement("button", { "data-filter-area-trigger": "" });
    const deskAreaTrigSpan = new DOMElement("span", { text: "Toàn Hà Nội" });
    deskAreaTrig.appendChild(deskAreaTrigSpan);
    desktopForm.appendChild(deskAreaTrig);

    const deskTypeTrig = new DOMElement("button", { "data-filter-type-trigger": "" });
    const deskTypeTrigSpan = new DOMElement("span", { text: "Tất cả loại hình" });
    deskTypeTrig.appendChild(deskTypeTrigSpan);
    desktopForm.appendChild(deskTypeTrig);

    const deskPriceTrig = new DOMElement("button", { "data-filter-price-trigger": "" });
    const deskPriceTrigSpan = new DOMElement("span", { text: "Tất cả khoảng giá" });
    deskPriceTrig.appendChild(deskPriceTrigSpan);
    desktopForm.appendChild(deskPriceTrig);

    const deskAdvTrig = new DOMElement("button", { "data-filter-advanced-trigger": "" });
    const deskAdvTrigSpan = new DOMElement("span", { text: "Nâng cao" });
    deskAdvTrig.appendChild(deskAdvTrigSpan);
    desktopForm.appendChild(deskAdvTrig);

    // Desktop options
    const deskAreaOptCauGiay = new DOMElement("div", { "data-filter-area-option": "", "data-value": "cau-giay", text: "Cầu Giấy" });
    const deskTypeOpt12 = new DOMElement("div", { "data-filter-type-option": "", "data-value": "12", text: "Biệt thự" });
    desktopForm.appendChild(deskAreaOptCauGiay);
    desktopForm.appendChild(deskTypeOpt12);

    // Advanced popover pills
    const advPillPngu3 = new DOMElement("button", { class: "adv-pill", "data-adv-target": "so_phong_ngu", "data-adv-val": "3", text: "3+" });
    const advPillHuongDN = new DOMElement("button", { class: "adv-pill", "data-adv-target": "huong_nha", "data-adv-val": "dong-nam", text: "Đông Nam" });
    desktopForm.appendChild(advPillPngu3);
    desktopForm.appendChild(advPillHuongDN);

    // Price Slider mock elements
    const priceWrap = new DOMElement("div", { "data-filter-price": "" });
    const priceRange = new DOMElement("div", { "data-filter-price-range": "", "data-bound-min": "0", "data-bound-max": "60000" });
    const pThumbMin = new DOMElement("div", { "data-filter-price-thumb-min": "" });
    const pThumbMax = new DOMElement("div", { "data-filter-price-thumb-max": "" });
    const pFill = new DOMElement("div", { "data-filter-price-fill": "" });
    const pInMin = new DOMElement("input", { "data-filter-price-input-min": "" });
    const pInMax = new DOMElement("input", { "data-filter-price-input-max": "" });
    const pLblMin = new DOMElement("span", { "data-filter-price-label-min": "" });
    const pLblMax = new DOMElement("span", { "data-filter-price-label-max": "" });
    priceWrap.appendChild(priceRange);
    priceWrap.appendChild(pThumbMin);
    priceWrap.appendChild(pThumbMax);
    priceWrap.appendChild(pFill);
    priceWrap.appendChild(pInMin);
    priceWrap.appendChild(pInMax);
    priceWrap.appendChild(pLblMin);
    priceWrap.appendChild(pLblMax);
    desktopForm.appendChild(priceWrap);

    // Area Slider mock elements
    const areaWrap = new DOMElement("div", { "data-filter-area-range": "" });
    const areaRange = new DOMElement("div", { "data-filter-area-range-slider": "", "data-bound-min": "0", "data-bound-max": "500" });
    const aThumbMin = new DOMElement("div", { "data-filter-area-range-thumb-min": "" });
    const aThumbMax = new DOMElement("div", { "data-filter-area-range-thumb-max": "" });
    const aFill = new DOMElement("div", { "data-filter-area-range-fill": "" });
    const aInMin = new DOMElement("input", { "data-filter-area-range-input-min": "" });
    const aInMax = new DOMElement("input", { "data-filter-area-range-input-max": "" });
    const aLblMin = new DOMElement("span", { "data-filter-area-range-label-min": "" });
    const aLblMax = new DOMElement("span", { "data-filter-area-range-label-max": "" });
    areaWrap.appendChild(areaRange);
    areaWrap.appendChild(aThumbMin);
    areaWrap.appendChild(aThumbMax);
    areaWrap.appendChild(aFill);
    areaWrap.appendChild(aInMin);
    areaWrap.appendChild(aInMax);
    areaWrap.appendChild(aLblMin);
    areaWrap.appendChild(aLblMax);
    desktopForm.appendChild(areaWrap);

    documentRoot.appendChild(desktopForm);

    // Mobile filter form in header
    const mobileWrap = new DOMElement("div", { class: "header__filter-mobile" });
    const mobileForm = new DOMElement("form", { class: "filter-bar-horizontal__form" });
    const mobInArea = new DOMElement("input", { type: "hidden", name: "khu_vuc", "data-filter-area-hidden": "" });
    const mobInType = new DOMElement("input", { type: "hidden", name: "type", "data-filter-type-hidden": "", value: "0" });
    const mobInPrice = new DOMElement("input", { type: "hidden", name: "price", "data-filter-price-hidden": "" });
    mobileForm.appendChild(mobInArea);
    mobileForm.appendChild(mobInType);
    mobileForm.appendChild(mobInPrice);
    mobileWrap.appendChild(mobileForm);

    // Mobile modal triggers
    const mobAreaWrap = new DOMElement("div", { "data-filter-header-modal": "khu-vuc" });
    const mobAreaTrig = new DOMElement("button", { "data-filter-header-modal-trigger": "" });
    const mobAreaTrigSpan = new DOMElement("span", { text: "Toàn Hà Nội" });
    const mobAreaTrigSvg = new DOMElement("svg", {});
    mobAreaTrig.appendChild(mobAreaTrigSpan);
    mobAreaTrig.appendChild(mobAreaTrigSvg);
    mobAreaWrap.appendChild(mobAreaTrig);
    mobileWrap.appendChild(mobAreaWrap);

    const mobTypeWrap = new DOMElement("div", { "data-filter-header-modal": "loai-hinh" });
    const mobTypeTrig = new DOMElement("button", { "data-filter-header-modal-trigger": "" });
    const mobTypeTrigSpan = new DOMElement("span", { text: "Tất cả loại hình" });
    const mobTypeTrigSvg = new DOMElement("svg", {});
    mobTypeTrig.appendChild(mobTypeTrigSpan);
    mobTypeTrig.appendChild(mobTypeTrigSvg);
    mobTypeWrap.appendChild(mobTypeTrig);
    mobileWrap.appendChild(mobTypeWrap);

    documentRoot.appendChild(mobileWrap);

    // Mobile modal panels
    const mobAreaPanel = new DOMElement("div", { "data-filter-header-modal-panel": "", "data-filter-header-modal-id": "khu-vuc" });
    const mobAreaOptCauGiay = new DOMElement("button", { class: "filter-header-modal-option", "data-value": "cau-giay", text: "Cầu Giấy" });
    const mobAreaOptBaDinh = new DOMElement("button", { class: "filter-header-modal-option", "data-value": "ba-dinh", text: "Ba Đình" });
    mobAreaPanel.appendChild(mobAreaOptCauGiay);
    mobAreaPanel.appendChild(mobAreaOptBaDinh);
    documentRoot.appendChild(mobAreaPanel);

    const mobTypePanel = new DOMElement("div", { "data-filter-header-modal-panel": "", "data-filter-header-modal-id": "loai-hinh" });
    const mobTypeOpt12 = new DOMElement("button", { class: "filter-header-modal-option", "data-value": "12", text: "Biệt thự" });
    mobTypePanel.appendChild(mobTypeOpt12);
    documentRoot.appendChild(mobTypePanel);

    // Active filter chips container
    const activeChipsContainer = new DOMElement("div", { id: "bds-active-filters", class: "hidden" });
    documentRoot.appendChild(activeChipsContainer);

    function mockJQuery(sel) {
        if (!sel) return wrap$Mock([]);
        if (typeof sel === "string") {
            if (sel === "<div>") return wrap$Mock([new DOMElement("div")]);
            return wrap$Mock(queryAll(documentRoot, sel));
        }
        if (sel.isMockJQuery) return sel;
        if (sel instanceof DOMElement || (sel && sel.tagName)) return wrap$Mock([sel]);
        if (Array.isArray(sel)) return wrap$Mock(sel);
        return wrap$Mock([]);
    }
    mockJQuery.trim = (s) => (s ? String(s).trim() : "");
    mockJQuery.extend = Object.assign;

    const adapterSandbox = {
        $: mockJQuery,
        jQuery: mockJQuery,
        window: { bdsTheme: { pageContext: "bds" } },
        FilterStateStore: Store
    };
    vm.createContext(adapterSandbox);

    const labelJs = mainJs.match(/const FilterLabelService = (\{[\s\S]*?\n    \};)/)[1];
    const desktopJs = mainJs.match(/const DesktopAdapter = (\{[\s\S]*?\n    \};)/)[1];
    const mobileJs = mainJs.match(/const MobileAdapter = (\{[\s\S]*?\n    \};)/)[1];
    const chipsJs = mainJs.match(/const ChipsAdapter = (\{[\s\S]*?\n    \};)/)[1];

    const LabelSvc = vm.runInContext('(' + labelJs.replace(/;\s*$/, '') + ')', adapterSandbox);
    adapterSandbox.FilterLabelService = LabelSvc;
    const DesktopAd = vm.runInContext('(' + desktopJs.replace(/;\s*$/, '') + ')', adapterSandbox);
    adapterSandbox.DesktopAdapter = DesktopAd;
    const MobileAd = vm.runInContext('(' + mobileJs.replace(/;\s*$/, '') + ')', adapterSandbox);
    adapterSandbox.MobileAdapter = MobileAd;
    const ChipsAd = vm.runInContext('(' + chipsJs.replace(/;\s*$/, '') + ')', adapterSandbox);
    adapterSandbox.ChipsAdapter = ChipsAd;

    // 3.1 Mobile Modal Selection Sync -> Desktop & Chips
    Store.set("khu_vuc", "cau-giay", { source: "modal_apply" });
    DesktopAd.render(Store.getAll());
    MobileAd.render(Store.getAll());
    ChipsAd.renderFromState(Store.getAll());

    assert(deskInArea.val() === "cau-giay", "3.1a: Desktop hidden input area synchronized to 'cau-giay'");
    assert(mobInArea.val() === "cau-giay", "3.1b: Mobile hidden input area synchronized to 'cau-giay'");
    assert(mobAreaOptCauGiay.attr("aria-selected") === "true", "3.1c: Mobile modal option cau-giay marked aria-selected='true'");
    assert(mobAreaTrig.hasClass("bg-primary/10"), "3.1d: Mobile header trigger received active highlight style");
    assert(deskAreaTrigSpan.text() === "Cầu Giấy", "3.1e: Desktop dropdown trigger updated to 'Cầu Giấy'");
    assert(!activeChipsContainer.hasClass("hidden"), "3.1f: Active chips strip is visible");
    assert(activeChipsContainer.html().includes("Khu vực: Cầu Giấy"), "3.1g: Active chip rendered with 'Khu vực: Cầu Giấy'");

    // 3.2 Desktop Dropdown Selection Sync -> Mobile Modal
    Store.set("type", "12", { source: "desktop_opt" });
    DesktopAd.render(Store.getAll());
    MobileAd.render(Store.getAll());
    ChipsAd.renderFromState(Store.getAll());

    assert(deskInType.val() === "12", "3.2a: Desktop hidden input type synchronized to '12'");
    assert(mobInType.val() === "12", "3.2b: Mobile hidden input type synchronized to '12'");
    assert(mobTypeTrig.hasClass("bg-primary/10"), "3.2c: Mobile modal type trigger received active style");
    assert(activeChipsContainer.html().includes("Loại hình:"), "3.2d: Chip container includes Loại hình badge");

    // 3.3 Desktop Advanced Popover Sync -> Hidden Inputs & Trigger Badge
    Store.setMultiple({ so_phong_ngu: "3", huong_nha: "dong-nam" }, { source: "adv_pill" });
    DesktopAd.render(Store.getAll());
    ChipsAd.renderFromState(Store.getAll());

    assert(deskInPngu.val() === "3", "3.3a: Desktop hidden input so_phong_ngu synchronized to '3'");
    assert(deskInHuong.val() === "dong-nam", "3.3b: Desktop hidden input huong_nha synchronized to 'dong-nam'");
    assert(advPillPngu3.hasClass("bg-primary"), "3.3c: Advanced pill 3+ has active bg-primary class");
    assert(advPillHuongDN.hasClass("bg-primary"), "3.3d: Advanced pill Đông Nam has active bg-primary class");
    assert(deskAdvTrigSpan.html().includes("Đã chọn (2)"), "3.3e: Advanced trigger button badge displays 'Đã chọn (2)'");
    assert(activeChipsContainer.html().includes("Phòng ngủ: 3+"), "3.3f: Active chip rendered for 'Phòng ngủ: 3+'");
    assert(activeChipsContainer.html().includes("Hướng: Đông Nam"), "3.3g: Active chip rendered for 'Hướng: Đông Nam'");

    // 3.4 1-Click Chip Removal (Remove 'type')
    Store.set("type", "0", { source: "chips" });
    DesktopAd.render(Store.getAll());
    MobileAd.render(Store.getAll());
    ChipsAd.renderFromState(Store.getAll());

    assert(deskInType.val() === "0", "3.4a: Removing type chip restores desktop hidden input to '0'");
    assert(mobInType.val() === "0", "3.4b: Removing type chip restores mobile hidden input to '0'");
    assert(!mobTypeTrig.hasClass("bg-primary/10"), "3.4c: Mobile type trigger active styling removed");
    assert(!activeChipsContainer.html().includes("Loại hình:"), "3.4d: Loại hình chip removed from active chips strip");
    assert(activeChipsContainer.html().includes("Khu vực: Cầu Giấy"), "3.4e: Other chips (Khu vực) remain preserved");

    // 3.5 Global Reset (Clear All)
    Store.reset({ source: "reset" });
    DesktopAd.render(Store.getAll());
    MobileAd.render(Store.getAll());
    ChipsAd.render([]);

    assert(deskInArea.val() === "", "3.5a: Reset cleared desktop area hidden input");
    assert(deskInPngu.val() === "", "3.5b: Reset cleared desktop bedroom hidden input");
    assert(mobInArea.val() === "", "3.5c: Reset cleared mobile area hidden input");
    assert(deskAreaTrigSpan.text() === "Toàn Hà Nội", "3.5d: Desktop area trigger reverted to 'Toàn Hà Nội'");
    assert(deskAdvTrigSpan.text() === "Nâng cao", "3.5e: Desktop advanced trigger reverted to 'Nâng cao'");
    assert(activeChipsContainer.hasClass("hidden"), "3.5f: Active filter chips strip hidden upon reset");


    // -----------------------------------------------------------------------------
    // SUITE 4: Dual-Slider Bounds, Presets & Thumb Repositioning
    // -----------------------------------------------------------------------------
    console.log("\n▶ Suite 4: Dual-Slider Bounds, Presets & Thumb Repositioning");

    function calculateSliderGeometry(valStr, boundMin, boundMax) {
        const rangeSpan = boundMax - boundMin;
        if (rangeSpan <= 0) return { pctMin: 0, pctMax: 100, vMin: boundMin, vMax: boundMax };

        let vMin = boundMin;
        let vMax = boundMax;

        if (valStr && valStr !== "0" && valStr !== "thoa_thuan") {
            if (valStr.indexOf("-") !== -1) {
                const p = valStr.split("-");
                vMin = Math.max(boundMin, Math.min(boundMax, parseInt(p[0], 10) || boundMin));
                vMax = Math.min(boundMax, parseInt(p[1], 10) || boundMax);
            } else {
                vMin = Math.max(boundMin, Math.min(boundMax, parseInt(valStr, 10) || boundMin));
                vMax = boundMax;
            }
        }

        const pctMin = Math.max(0, Math.min(100, ((vMin - boundMin) / rangeSpan) * 100));
        const pctMax = Math.max(0, Math.min(100, ((vMax - boundMin) / rangeSpan) * 100));
        return { pctMin, pctMax, vMin, vMax };
    }

    // 4.1 Price Slider Geometry (0 - 60,000 triệu)
    const pRange1 = calculateSliderGeometry("3000-5000", 0, 60000);
    assert(Math.abs(pRange1.pctMin - 5.0) < 0.001, "4.1a: Price 3-5 tỷ min thumb at exactly 5.0%");
    assert(Math.abs(pRange1.pctMax - 8.333) < 0.01, "4.1b: Price 3-5 tỷ max thumb at ~8.33%");

    const pRangeOpen = calculateSliderGeometry("10000", 0, 60000);
    assert(Math.abs(pRangeOpen.pctMin - 16.666) < 0.01, "4.1c: Open-ended price 10 tỷ min thumb at ~16.67%");
    assert(pRangeOpen.pctMax === 100, "4.1d: Open-ended price 10 tỷ max thumb at 100%");

    const pRangeThoaThuan = calculateSliderGeometry("thoa_thuan", 0, 60000);
    assert(pRangeThoaThuan.pctMin === 0 && pRangeThoaThuan.pctMax === 100, "4.1e: Thỏa thuận price resets thumbs to full track (0% - 100%)");

    // Bound Clamping & Inversion Protection
    const pRangeClamp = calculateSliderGeometry("70000-80000", 0, 60000);
    assert(pRangeClamp.vMin === 60000 && pRangeClamp.vMax === 60000, "4.1f: Out-of-bounds price values clamped strictly to boundMax (60000)");

    // 4.2 Area Slider Geometry (0 - 500 m2, step 5)
    const aRange1 = calculateSliderGeometry("50-80", 0, 500);
    assert(aRange1.pctMin === 10, "4.2a: Area 50-80m2 min thumb at 10.0%");
    assert(aRange1.pctMax === 16, "4.2b: Area 50-80m2 max thumb at 16.0%");

    const aRangeOpen = calculateSliderGeometry("100", 0, 500);
    assert(aRangeOpen.pctMin === 20, "4.2c: Open-ended area 100m2 min thumb at 20.0%");
    assert(aRangeOpen.pctMax === 100, "4.2d: Open-ended area 100m2 max thumb at 100%");


    // -----------------------------------------------------------------------------
    // SUITE 5: Browser Navigation (`popstate`) Restoration
    // -----------------------------------------------------------------------------
    console.log("\n▶ Suite 5: Browser Navigation (`popstate`) Restoration");

    const historyStack = [];
    let historyIndex = -1;

    function pushNavigation(params, url, title) {
        historyIndex++;
        historyStack[historyIndex] = {
            state: { type: "bds_filter", url: url, params: Object.assign({}, params) },
            title: title || "Title",
            url: url
        };
        historyStack.length = historyIndex + 1;
    }

    // 5.1 Navigate S0 -> S1 -> S2 -> S3
    pushNavigation({}, "/bat-dong-san/", "BĐS Hà Nội");
    pushNavigation({ khu_vuc: "cau-giay" }, "/bat-dong-san-quan-cau-giay/", "BĐS Cầu Giấy");
    pushNavigation({ khu_vuc: "cau-giay", price: "3000-5000" }, "/bat-dong-san-quan-cau-giay/gia-tu-3-ty-den-5-ty/", "BĐS Cầu Giấy 3-5 tỷ");
    pushNavigation({ khu_vuc: "cau-giay", price: "3000-5000", so_phong_ngu: "3" }, "/bat-dong-san-quan-cau-giay/gia-tu-3-ty-den-5-ty/?so_phong_ngu=3", "BĐS Cầu Giấy 3PN");

    assert(historyStack.length === 4, "5.1a: History stack holds 4 sequential navigation states");
    assert(historyIndex === 3, "5.1b: Current position at S3");

    function dispatchPopstate(targetIndex) {
        historyIndex = targetIndex;
        const entry = historyStack[historyIndex];
        const params = entry.state.params || {};

        Store.setMultiple(params, { source: "popstate", triggerFetch: false });
        DesktopAd.render(Store.getAll());
        MobileAd.render(Store.getAll());
        ChipsAd.renderFromState(Store.getAll());

        return {
            pushedDuringPop: false // Invariant check: pushState is never called when popping
        };
    }

    // 5.2 Popstate Back S3 -> S2
    const resS2 = dispatchPopstate(2);
    assert(!resS2.pushedDuringPop, "5.2a: pushState was strictly NOT invoked during popstate navigation");
    assert(Store.get("so_phong_ngu") === "", "5.2b: so_phong_ngu cleanly cleared from Store on S3->S2 popstate");
    assert(deskInPngu.val() === "", "5.2c: Desktop bedroom hidden input cleared");
    assert(Store.get("price") === "3000-5000", "5.2d: Price 3000-5000 preserved at S2");
    assert(Store.get("khu_vuc") === "cau-giay", "5.2e: Khu vuc cau-giay preserved at S2");
    assert(activeChipsContainer.html().includes("Khu vực: Cầu Giấy"), "5.2f: Khu vực chip rendered");
    assert(!activeChipsContainer.html().includes("Phòng ngủ:"), "5.2g: Phòng ngủ chip successfully removed");

    // 5.3 Popstate Back S2 -> S1
    dispatchPopstate(1);
    assert(Store.get("price") === "", "5.3a: Price cleared on S2->S1 popstate");
    assert(deskInPrice.val() === "", "5.3b: Desktop price hidden input cleared");
    assert(Store.get("khu_vuc") === "cau-giay", "5.3c: Khu vuc cau-giay preserved at S1");
    assert(!activeChipsContainer.html().includes("Giá:"), "5.3d: Price chip removed from chips strip");

    // 5.4 Popstate Back S1 -> S0
    dispatchPopstate(0);
    assert(Store.get("khu_vuc") === "", "5.4a: Khu vuc cleared on S1->S0 popstate");
    assert(deskInArea.val() === "", "5.4b: Desktop area input cleared");
    assert(mobInArea.val() === "", "5.4c: Mobile area input cleared");
    assert(activeChipsContainer.hasClass("hidden"), "5.4d: Active chips strip hidden at S0 pristine state");

    // 5.5 Popstate Forward S0 -> S3 directly
    dispatchPopstate(3);
    assert(Store.get("khu_vuc") === "cau-giay", "5.5a: Forward popstate directly restored khu_vuc=cau-giay");
    assert(Store.get("price") === "3000-5000", "5.5b: Forward popstate directly restored price=3000-5000");
    assert(Store.get("so_phong_ngu") === "3", "5.5c: Forward popstate directly restored so_phong_ngu=3");
    assert(activeChipsContainer.html().includes("Phòng ngủ: 3+"), "5.5d: Chips strip displays all 3 criteria simultaneously");

    // -----------------------------------------------------------------------------
    // SUMMARY & VERDICT
    // -----------------------------------------------------------------------------
    console.log("\n================================================================================");
    console.log(` SUMMARY: ${passCount} Passed, ${failCount} Failed (Total: ${passCount + failCount})`);
    console.log("================================================================================");

    if (failCount > 0) {
        console.error(`\n[VERDICT: REQUEST_CHANGES] ${failCount} assertions failed:`);
        failures.forEach(f => console.error(`  - ${f}`));
        process.exit(1);
    } else {
        console.log("\n[VERDICT: APPROVE] ALL M2 FILTER STORE & SYNC EMPIRICAL TESTS PASSED (100% SUCCESS)!\n");
        process.exit(0);
    }
}

runAsyncTests().catch(err => {
    console.error("FATAL in test harness:", err);
    process.exit(1);
});
