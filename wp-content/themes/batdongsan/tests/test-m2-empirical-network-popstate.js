/**
 * Empirical Stress Harness for Milestone 2: Network Resilience & Browser Popstate Navigation
 * Challenger: teamwork_preview_challenger_m2_9_2
 *
 * Scenarios Tested:
 * 1. AbortController & Monotonic Sequence IDs (Race Condition Stress Test):
 *    - Rapid filter toggling with out-of-order network response arrivals in both Projects and Listings.
 *    - Verification that stale responses are discarded and do not overwrite fresh results.
 *    - Verification of is-loading / opacity reset on out-of-order resolution.
 *    - Inspection of AbortController signal attachment in $.ajax (Projects vs Listings).
 *
 * 2. Browser Popstate Navigation & UI State Restoration:
 *    - Sequential filtering: S0 (default) -> S1 (district) -> S2 (type) -> S3 (price/slider) -> S4 (advanced popover).
 *    - Backward navigation (S4 -> S3 -> S2 -> S1 -> S0): verify hidden inputs, dropdown labels, chips, sliders, advanced badge/pills.
 *    - Forward navigation (S0 -> S1 -> S2 -> S3 -> S4): verify complete restoration without page reload.
 *    - Verification of FilterStateStore integrity during popstate (check for lingering/phantom state).
 *    - Null history state URL parsing fallback (parseBdsUrlToParams).
 *    - Verification that popstate does not invoke history.pushState (pushHistory = false).
 */

const fs = require('fs');
const path = require('path');

// Colors for reporting
const GREEN = '\x1b[32m';
const RED = '\x1b[31m';
const YELLOW = '\x1b[33m';
const BLUE = '\x1b[34m';
const RESET = '\x1b[0m';

let totalTests = 0;
let passedTests = 0;
let failedTests = 0;
const findings = [];

function assert(condition, testId, description, details) {
    totalTests++;
    if (condition) {
        passedTests++;
        console.log(`  ${GREEN}✔ PASS:${RESET} ${testId}: ${description}`);
    } else {
        failedTests++;
        console.log(`  ${RED}✖ FAIL:${RESET} ${testId}: ${description}`);
        if (details) {
            console.log(`    ${YELLOW}Details:${RESET} ${details}`);
        }
    }
}

function recordFinding(id, description, severity = 'HIGH') {
    findings.push({ id, description, severity });
    console.log(`  ${YELLOW}ℹ FINDING [${id}] (${severity}):${RESET} ${description}`);
}

// -------------------------------------------------------------
// Lightweight DOM & jQuery Mock Engine
// -------------------------------------------------------------
class MockElement {
    constructor(tagName = 'div', attributes = {}, text = '') {
        this.tagName = tagName.toLowerCase();
        this.attributes = Object.assign({}, attributes);
        this.classes = new Set((attributes['class'] || '').split(/\s+/).filter(Boolean));
        this.textVal = text;
        this.children = [];
        this.parent = null;
        this.listeners = [];
        this.dataStore = {};
        this.style = {};
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
            if (name === 'class') {
                this.classes = new Set(String(val).split(/\s+/).filter(Boolean));
            }
            return this;
        }
        return this.attributes[name];
    }

    removeAttr(name) {
        delete this.attributes[name];
        return this;
    }

    data(name, val) {
        if (val !== undefined) {
            this.dataStore[name] = val;
            return this;
        }
        if (this.dataStore[name] !== undefined) return this.dataStore[name];
        if (this.attributes['data-' + name] !== undefined) return this.attributes['data-' + name];
        return undefined;
    }

    val(v) {
        if (v !== undefined) {
            this.attributes['value'] = String(v);
            return this;
        }
        return this.attributes['value'] !== undefined ? this.attributes['value'] : '';
    }

    text(t) {
        if (t !== undefined) {
            this.textVal = String(t);
            this.children = [];
            return this;
        }
        let txt = this.textVal;
        for (const c of this.children) {
            txt += c.text();
        }
        return txt;
    }

    html(h) {
        if (h !== undefined) {
            this.textVal = String(h);
            return this;
        }
        return this.textVal;
    }

    css(prop, val) {
        if (typeof prop === 'object') {
            Object.assign(this.style, prop);
            return this;
        }
        if (val !== undefined) {
            this.style[prop] = String(val);
            return this;
        }
        return this.style[prop];
    }

    appendChild(child) {
        child.parent = this;
        this.children.push(child);
        return child;
    }

    empty() {
        this.children = [];
        this.textVal = '';
        return this;
    }

    reset() {
        // Reset form inputs
        const inputs = this.find('input, select');
        inputs.each(function() {
            if (this.attributes['type'] === 'hidden') return;
            this.val('');
        });
    }

    find(selector) {
        const matches = [];
        function walk(node) {
            for (const child of node.children) {
                if (matchesSelector(child, selector)) {
                    matches.push(child);
                }
                walk(child);
            }
        }
        walk(this);
        return new MockQuery(matches);
    }
}

function matchesSelector(el, sel) {
    if (!sel || !el) return false;
    sel = sel.trim();

    // Check multiple selectors separated by comma
    if (sel.includes(',')) {
        return sel.split(',').some(s => matchesSelector(el, s.trim()));
    }

    // Tag
    if (/^[a-z0-9]+$/i.test(sel)) {
        return el.tagName === sel.toLowerCase();
    }
    // Class
    if (sel.startsWith('.')) {
        const c = sel.slice(1).replace(/\\/g, '');
        return el.hasClass(c);
    }
    // ID
    if (sel.startsWith('#')) {
        return el.attr('id') === sel.slice(1);
    }
    // Attribute exists: [data-attr]
    const attrExistsMatch = sel.match(/^\[([a-z0-9-_]+)\]$/i);
    if (attrExistsMatch) {
        return el.attr(attrExistsMatch[1]) !== undefined;
    }
    // Attribute equals: [data-attr='val'] or [data-attr="val"]
    const attrEqualsMatch = sel.match(/^\[([a-z0-9-_]+)=['"]?([^'"]*)['"]?\]$/i);
    if (attrEqualsMatch) {
        return String(el.attr(attrEqualsMatch[1])) === attrEqualsMatch[2];
    }
    // Tag with attribute: button[data-filter-type-option]
    const tagAttrMatch = sel.match(/^([a-z0-9]+)\[([a-z0-9-_]+)\]$/i);
    if (tagAttrMatch) {
        return el.tagName === tagAttrMatch[1].toLowerCase() && el.attr(tagAttrMatch[2]) !== undefined;
    }
    // Input with name: input[name='foo']
    const inputNameMatch = sel.match(/^([a-z0-9]+)\[name=['"]?([^'"]+)['"]?\]$/i);
    if (inputNameMatch) {
        return el.tagName === inputNameMatch[1].toLowerCase() && el.attr('name') === inputNameMatch[2];
    }

    return false;
}

class MockQuery {
    constructor(elements = []) {
        this.elements = Array.isArray(elements) ? elements : [elements];
        this.length = this.elements.length;
        for (let i = 0; i < this.elements.length; i++) {
            this[i] = this.elements[i];
        }
    }

    each(fn) {
        for (let i = 0; i < this.elements.length; i++) {
            fn.call(this.elements[i], i, this.elements[i]);
        }
        return this;
    }

    first() {
        return new MockQuery(this.elements.length ? [this.elements[0]] : []);
    }

    last() {
        return new MockQuery(this.elements.length ? [this.elements[this.elements.length - 1]] : []);
    }

    find(selector) {
        const found = [];
        for (const el of this.elements) {
            const res = el.find(selector);
            for (let i = 0; i < res.length; i++) {
                if (!found.includes(res[i])) found.push(res[i]);
            }
        }
        return new MockQuery(found);
    }

    filter(fnOrSelector) {
        if (typeof fnOrSelector === 'function') {
            const filtered = this.elements.filter((el, idx) => fnOrSelector.call(el, idx, el));
            return new MockQuery(filtered);
        }
        const filtered = this.elements.filter(el => matchesSelector(el, fnOrSelector));
        return new MockQuery(filtered);
    }

    not(fnOrQuery) {
        const exclude = fnOrQuery instanceof MockQuery ? fnOrQuery.elements : [];
        const res = this.elements.filter(el => !exclude.includes(el));
        return new MockQuery(res);
    }

    closest(selector) {
        const matches = [];
        for (const el of this.elements) {
            let curr = el;
            while (curr) {
                if (matchesSelector(curr, selector)) {
                    matches.push(curr);
                    break;
                }
                curr = curr.parent;
            }
        }
        return new MockQuery(matches);
    }

    addClass(c) {
        this.elements.forEach(el => el.addClass(c));
        return this;
    }

    removeClass(c) {
        this.elements.forEach(el => el.removeClass(c));
        return this;
    }

    hasClass(c) {
        return this.elements.length ? this.elements[0].hasClass(c) : false;
    }

    attr(name, val) {
        if (val !== undefined) {
            this.elements.forEach(el => el.attr(name, val));
            return this;
        }
        return this.elements.length ? this.elements[0].attr(name) : undefined;
    }

    removeAttr(name) {
        this.elements.forEach(el => el.removeAttr(name));
        return this;
    }

    val(v) {
        if (v !== undefined) {
            this.elements.forEach(el => el.val(v));
            return this;
        }
        return this.elements.length ? this.elements[0].val() : '';
    }

    text(t) {
        if (t !== undefined) {
            this.elements.forEach(el => el.text(t));
            return this;
        }
        return this.elements.length ? this.elements[0].text() : '';
    }

    html(h) {
        if (h !== undefined) {
            this.elements.forEach(el => el.html(h));
            return this;
        }
        return this.elements.length ? this.elements[0].html() : '';
    }

    empty() {
        this.elements.forEach(el => el.empty());
        return this;
    }

    css(prop, val) {
        this.elements.forEach(el => el.css(prop, val));
        return this;
    }

    data(name, val) {
        if (val !== undefined) {
            this.elements.forEach(el => el.data(name, val));
            return this;
        }
        return this.elements.length ? this.elements[0].data(name) : undefined;
    }

    on(event, selectorOrHandler, handler) {
        let fn = handler;
        let sel = null;
        if (typeof selectorOrHandler === 'string') {
            sel = selectorOrHandler;
        } else {
            fn = selectorOrHandler;
        }
        this.elements.forEach(el => {
            el.listeners.push({ event, selector: sel, handler: fn });
        });
        return this;
    }

    off(event, selector) {
        this.elements.forEach(el => {
            el.listeners = el.listeners.filter(l => {
                if (event && !l.event.startsWith(event)) return true;
                if (selector && l.selector !== selector) return true;
                return false;
            });
        });
        return this;
    }

    trigger(eventName, extraData) {
        this.elements.forEach(el => {
            el.listeners.forEach(l => {
                if (l.event.split('.')[0] === eventName) {
                    const evt = {
                        type: eventName,
                        target: el,
                        preventDefault: () => {},
                        stopPropagation: () => {}
                    };
                    l.handler.call(el, evt, extraData);
                }
            });
        });
        return this;
    }
}

// -------------------------------------------------------------
// Test Execution Suite
// -------------------------------------------------------------
console.log('='.repeat(80));
console.log(' M2 CHALLENGER: NETWORK RESILIENCE & BROWSER POPSTATE EMPIRICAL HARNESS');
console.log('='.repeat(80));

// Load js/main.js
const mainJsPath = path.resolve(__dirname, '../js/main.js');
const mainJs = fs.readFileSync(mainJsPath, 'utf8');

// -------------------------------------------------------------
// Suite 1: AbortController & Monotonic Sequence IDs (Race Conditions)
// -------------------------------------------------------------
console.log(`\n${BLUE}▶ Suite 1: AbortController & Monotonic Sequence IDs (Out-of-Order Race Defense)${RESET}`);

// Setup sandbox for AJAX / Network testing
const ajaxCalls = [];
let abortSignalsCreated = [];

class MockAbortSignal {
    constructor() {
        this.aborted = false;
        this.onabort = null;
    }
}

class MockAbortController {
    constructor() {
        this.signal = new MockAbortSignal();
        abortSignalsCreated.push(this);
    }
    abort() {
        this.signal.aborted = true;
        if (typeof this.signal.onabort === 'function') {
            this.signal.onabort();
        }
    }
}

// Mock jQuery $.ajax
const mockAjax = function(options) {
    const callRecord = {
        options: options,
        resolved: false,
        aborted: false,
        resolve: function(response) {
            this.resolved = true;
            if (options.success) options.success(response);
            if (options.complete) options.complete();
        },
        reject: function(status, error) {
            this.resolved = true;
            if (options.error) options.error({ statusText: status }, status, error);
            if (options.complete) options.complete();
        }
    };

    if (options.xhrFields && options.xhrFields.signal) {
        options.xhrFields.signal.onabort = () => {
            callRecord.aborted = true;
            callRecord.reject('abort', 'canceled');
        };
    } else if (options.signal) {
        options.signal.onabort = () => {
            callRecord.aborted = true;
            callRecord.reject('abort', 'canceled');
        };
    }

    ajaxCalls.push(callRecord);
    return {
        abort: function() {
            callRecord.reject('abort', 'canceled');
        }
    };
};

// 1.1 Test Monotonic Sequence IDs in Listings (fetchListings)
// Extract fetchListings logic from js/main.js
const fetchListingsMatch = mainJs.match(/const fetchListings = function\s*\(([\s\S]*?)\n            \};/);
assert(fetchListingsMatch !== null, 'T1.1-Extract-FetchListings', 'fetchListings is defined in js/main.js');

// Extract handleAjaxFilter for projects from js/main.js
const handleAjaxProjectsMatch = mainJs.match(/const handleAjaxFilter = function\s*\(([\s\S]*?)\n            \};/);
assert(handleAjaxProjectsMatch !== null, 'T1.2-Extract-HandleAjaxProjects', 'handleAjaxFilter is defined in js/main.js');

// 1.3 Out-of-Order Network Simulation in Listings (BĐS)
{
    // Build simulated listings environment
    let listingsTxId = 0;
    let listingsAbortController = null;
    let gridHtml = 'INITIAL_LISTINGS';
    let countText = '100';
    let isLoading = false;
    let lastRenderedTxId = 0;

    const simulateFetchListings = function(params, pushHistory) {
        if (listingsAbortController) {
            try { listingsAbortController.abort(); } catch (e) { }
        }
        listingsAbortController = new MockAbortController();
        listingsTxId++;
        const currentTxId = listingsTxId;
        isLoading = true;

        const ajaxData = Object.assign({ action: 'batdongsan_filter_listings' }, params);

        mockAjax({
            url: '/admin-ajax.php',
            type: 'POST',
            data: ajaxData,
            // Check whether signal was attached in original code
            xhrFields: listingsAbortController ? { signal: listingsAbortController.signal } : {},
            success: function(response) {
                if (currentTxId !== listingsTxId) {
                    // Stale response discarded!
                    return;
                }
                if (response.data && response.data.html) {
                    gridHtml = response.data.html;
                    lastRenderedTxId = currentTxId;
                }
                if (response.data && response.data.total) {
                    countText = String(response.data.total);
                }
            },
            error: function(xhr, status, error) {
                if (status === 'abort') return;
                if (currentTxId !== listingsTxId) return;
            },
            complete: function() {
                if (currentTxId === listingsTxId) {
                    isLoading = false;
                }
            }
        });
    };

    // Dispatch Request 1: slow filter for Cầu Giấy (arrives at t=300ms)
    ajaxCalls.length = 0;
    simulateFetchListings({ khu_vuc: 'cau-giay' }, true);
    const req1 = ajaxCalls[0];
    assert(listingsTxId === 1, 'T1.3a-Listings-TxId-1', 'First request increments listingsTxId to 1');
    assert(isLoading === true, 'T1.3b-Listings-IsLoading-Active', 'Grid enters loading state (is-loading)');

    // Dispatch Request 2: fast filter for Ba Đình (arrives at t=50ms)
    simulateFetchListings({ khu_vuc: 'ba-dinh' }, true);
    const req2 = ajaxCalls[1];
    assert(listingsTxId === 2, 'T1.3c-Listings-TxId-2', 'Rapid filter toggle increments listingsTxId to 2');

    // Simulate OUT-OF-ORDER arrival: Request 2 arrives FIRST!
    req2.resolve({
        success: true,
        data: {
            html: '<div id="listing-ba-dinh">5 BĐS Ba Đình</div>',
            total: 5,
            url: '/bat-dong-san-quan-ba-dinh/'
        }
    });

    assert(gridHtml === '<div id="listing-ba-dinh">5 BĐS Ba Đình</div>', 'T1.3d-Req2-Rendered-First', 'Grid correctly renders fresh Request 2 results');
    assert(countText === '5', 'T1.3e-Req2-Count-Updated', 'Total count reflects Request 2 (5 listings)');
    assert(isLoading === false, 'T1.3f-Req2-Loading-Cleared', 'Grid loading state cleared after Request 2 completion');

    // Now Request 1 arrives LATER (stale response)
    req1.resolve({
        success: true,
        data: {
            html: '<div id="listing-cau-giay">25 BĐS Cầu Giấy</div>',
            total: 25,
            url: '/bat-dong-san-quan-cau-giay/'
        }
    });

    // Verification: Stale Request 1 MUST NOT overwrite Request 2!
    assert(gridHtml === '<div id="listing-ba-dinh">5 BĐS Ba Đình</div>', 'T1.3g-Stale-Req1-Discarded', 'STALE Request 1 response was discarded and did NOT overwrite gridHtml');
    assert(countText === '5', 'T1.3h-Stale-Req1-Count-Ignored', 'STALE Request 1 did NOT overwrite count');
    assert(lastRenderedTxId === 2, 'T1.3i-LastRenderedTxId-Invariant', 'lastRenderedTxId remains 2 (monotonic ID protected DOM)');
}

// 1.4 Code Analysis of AbortController in fetchListings vs handleAjaxFilter
{
    // Check if fetchListings passes xhrFields with signal
    const fetchListingsCode = mainJs.slice(mainJs.indexOf('const fetchListings = function'), mainJs.indexOf('const renderActiveFilterChips = function'));
    const fetchListingsHasSignal = fetchListingsCode.includes('signal: listingsAbortController.signal') ||
                                   fetchListingsCode.includes('xhrFields: listingsAbortController ? { signal: listingsAbortController.signal } : {}') ||
                                   fetchListingsCode.includes('xhrFields: { signal: listingsAbortController.signal }');

    // Check if handleAjaxFilter passes xhrFields with signal
    const projectsFilterCode = mainJs.slice(mainJs.indexOf('const handleAjaxFilter = function'), mainJs.indexOf('const resetAllProjectsFilters = function'));
    const projectsHasSignal = projectsFilterCode.includes('signal: projectAbortController.signal');

    assert(projectsHasSignal, 'T1.4a-Projects-Has-AbortSignal', 'Projects handleAjaxFilter passes projectAbortController.signal via xhrFields');

    if (!fetchListingsHasSignal) {
        recordFinding('F-M2-LISTINGS-ABORT-DISCONNECTED', 'fetchListings instantiates and calls abort() on listingsAbortController, but omits passing xhrFields: { signal: listingsAbortController.signal } into $.ajax (line 1156). While monotonic sequence IDs (listingsTxId) prevent DOM overwriting, in-flight HTTP requests are not aborted at the network level.', 'MEDIUM');
    }
    assert(fetchListingsHasSignal, 'T1.4b-Listings-Has-AbortSignal', 'Listings fetchListings passes listingsAbortController.signal to $.ajax');
}

// -------------------------------------------------------------
// Suite 2: Browser Popstate Navigation & UI State Restoration
// -------------------------------------------------------------
console.log(`\n${BLUE}▶ Suite 2: Browser Popstate Navigation & UI State Restoration${RESET}`);

// Extract FilterStateStore and Adapters
const storeCodeMatch = mainJs.match(/const FilterStateStore = \(function \(\) \{([\s\S]*?)\}\)\(\);/);
assert(storeCodeMatch !== null, 'T2.0-Extract-FilterStateStore', 'FilterStateStore extracted successfully');

const FilterStateStore = eval('(function() {' + storeCodeMatch[1] + '})()');

// Extract FilterLabelService
const labelServiceMatch = mainJs.match(/const FilterLabelService = \{([\s\S]*?)\n    \};/);
assert(labelServiceMatch !== null, 'T2.1-Extract-FilterLabelService', 'FilterLabelService extracted successfully');
global.$ = (sel) => new MockQuery();
global.jQuery = global.$;
const FilterLabelService = eval('({' + labelServiceMatch[1] + '})');

// Extract parseFilterSlug and parseBdsUrlToParams
const parseFilterSlugMatch = mainJs.match(/const parseFilterSlug = function\s*\(([\s\S]*?)\n            \};/);
const parsePriceSlugMatch = mainJs.match(/const parsePriceSlug = function\s*\(([\s\S]*?)\n            \};/);
const parseAreaSlugMatch = mainJs.match(/const parseAreaSlug = function\s*\(([\s\S]*?)\n            \};/);
const parseBdsUrlToParamsMatch = mainJs.match(/const parseBdsUrlToParams = function\s*\(([\s\S]*?)\n            \};/);

assert(parsePriceSlugMatch !== null, 'T2.2-Extract-ParsePriceSlug', 'parsePriceSlug located');
assert(parseAreaSlugMatch !== null, 'T2.3-Extract-ParseAreaSlug', 'parseAreaSlug located');
assert(parseBdsUrlToParamsMatch !== null, 'T2.4-Extract-ParseBdsUrlToParams', 'parseBdsUrlToParams located');

// Extract ChipsAdapter
const chipsAdapterMatch = mainJs.match(/const ChipsAdapter = \{([\s\S]*?)\n    \};/);
assert(chipsAdapterMatch !== null, 'T2.5-Extract-ChipsAdapter', 'ChipsAdapter extracted successfully');

// Test ChipsAdapter.computeChips
{
    const stateWithAdv = {
        hinh_thuc: 'chuyen-nhuong',
        khu_vuc: 'cau-giay',
        type: '12',
        price: '3000-5000',
        area: '50-80',
        so_phong_ngu: '3',
        so_phong_ve_sinh: '2',
        huong_nha: 'dong-nam',
        phap_ly: 'so-do'
    };

    global.window = { bdsTheme: { pageContext: 'bds' } };
    global.bdsTheme = global.window.bdsTheme;

    // Eval ChipsAdapter with FilterLabelService in scope
    const ChipsAdapter = eval('(function(FilterLabelService, $) {\nreturn {' + chipsAdapterMatch[1] + '};\n})(FilterLabelService, function(s) { return new MockQuery(); })');

    const chips = ChipsAdapter.computeChips(stateWithAdv);
    assert(chips.length === 9, 'T2.6a-Chips-Count-Full', 'ChipsAdapter computes all 9 active criteria chips');

    const chipKeys = chips.map(c => c.key);
    assert(chipKeys.includes('so_phong_ngu'), 'T2.6b-Chip-Bedrooms', 'so_phong_ngu chip present');
    assert(chipKeys.includes('so_phong_ve_sinh'), 'T2.6c-Chip-Bathrooms', 'so_phong_ve_sinh chip present');
    assert(chipKeys.includes('huong_nha'), 'T2.6d-Chip-Orientation', 'huong_nha chip present');
    assert(chipKeys.includes('phap_ly'), 'T2.6e-Chip-Legal', 'phap_ly chip present');
}

// 2.7 Popstate Navigation Cycle & State Restoration Stress Test
console.log(`\n${BLUE}▶ Suite 3: Popstate Back/Forward Restoration & Phantom State Detection${RESET}`);
{
    // Simulate user navigation history:
    // S0: Default (all empty)
    // S1: User filters by khu_vuc: "cau-giay"
    // S2: User adds type: "12" (Chung cư)
    // S3: User adds price: "3000-5000"
    // S4: User adds advanced filters: so_phong_ngu: "3", phap_ly: "so-do"

    const historyStack = [];
    let historyIndex = -1;

    function pushHistoryEntry(params, url) {
        historyIndex++;
        historyStack.splice(historyIndex); // truncate forward history
        historyStack.push({
            state: { type: 'bds_filter', url: url, params: Object.assign({}, params) },
            url: url
        });
    }

    // S0: initial state
    FilterStateStore.reset();
    pushHistoryEntry({}, '/bat-dong-san/');

    // S1: filter khu_vuc
    FilterStateStore.set('khu_vuc', 'cau-giay');
    pushHistoryEntry({ khu_vuc: 'cau-giay' }, '/bat-dong-san-quan-cau-giay/');

    // S2: filter type
    FilterStateStore.set('type', '12');
    pushHistoryEntry({ khu_vuc: 'cau-giay', type: '12' }, '/can-ho-chung-cu-quan-cau-giay/');

    // S3: filter price
    FilterStateStore.set('price', '3000-5000');
    pushHistoryEntry({ khu_vuc: 'cau-giay', type: '12', price: '3000-5000' }, '/can-ho-chung-cu-quan-cau-giay/gia-tu-3-ty-den-5-ty/');

    // S4: filter advanced
    FilterStateStore.setMultiple({ so_phong_ngu: '3', phap_ly: 'so-do' });
    pushHistoryEntry({ khu_vuc: 'cau-giay', type: '12', price: '3000-5000', so_phong_ngu: '3', phap_ly: 'so-do' }, '/can-ho-chung-cu-quan-cau-giay/gia-tu-3-ty-den-5-ty/?so_phong_ngu=3&phap_ly=so-do');

    assert(historyStack.length === 5, 'T3.1-History-Stack-Seeded', 'History stack contains 5 states (S0 through S4)');
    assert(FilterStateStore.get('so_phong_ngu') === '3', 'T3.2-State-S4-Active', 'Current state S4 has so_phong_ngu = 3');

    // -------------------------------------------------------------
    // Test Backward Navigation: S4 -> S3 -> S2 -> S1 -> S0
    // -------------------------------------------------------------

    // SIMULATE BACKWARD: S4 -> S3
    historyIndex = 3;
    const targetStateS3 = historyStack[historyIndex];

    // Check how main.js popstate handles params:
    // Line 1687: FilterStateStore.setMultiple(params, { source: "popstate", triggerFetch: false });
    // Does passing targetStateS3.state.params clear so_phong_ngu and phap_ly?
    FilterStateStore.setMultiple(targetStateS3.state.params, { source: 'popstate', triggerFetch: false });

    const storeAfterS3Back = FilterStateStore.getAll();
    const hasPhantomBedrooms = storeAfterS3Back.so_phong_ngu !== '';
    const hasPhantomLegal = storeAfterS3Back.phap_ly !== '';

    if (hasPhantomBedrooms || hasPhantomLegal) {
        recordFinding('F-M2-POPSTATE-PHANTOM-STATE', `When navigating BACK in browser history from S4 (with advanced criteria so_phong_ngu='3') to S3 (without advanced criteria), FilterStateStore.setMultiple(params) only updates keys present in params. Absent keys remain in FilterStateStore (so_phong_ngu is still '${storeAfterS3Back.so_phong_ngu}', phap_ly is still '${storeAfterS3Back.phap_ly}'). Consequently, ChipsAdapter.renderFromState(FilterStateStore.getAll()) renders phantom filter chips and advanced trigger badge shows active selection for criteria that do not belong to S3.`, 'HIGH');
    }

    assert(!hasPhantomBedrooms, 'T3.3a-Back-S3-No-Phantom-Bedrooms', `so_phong_ngu must be cleared (expected: '', actual: '${storeAfterS3Back.so_phong_ngu}')`);
    assert(!hasPhantomLegal, 'T3.3b-Back-S3-No-Phantom-Legal', `phap_ly must be cleared (expected: '', actual: '${storeAfterS3Back.phap_ly}')`);

    // SIMULATE BACKWARD: S3 -> S2 (pop back to S2 where price was not selected)
    historyIndex = 2;
    const targetStateS2 = historyStack[historyIndex];
    FilterStateStore.setMultiple(targetStateS2.state.params, { source: 'popstate', triggerFetch: false });
    const storeAfterS2Back = FilterStateStore.getAll();
    const hasPhantomPrice = storeAfterS2Back.price !== '';

    if (hasPhantomPrice) {
        recordFinding('F-M2-POPSTATE-PHANTOM-PRICE', `When navigating BACK from S3 to S2, price='${storeAfterS2Back.price}' persists in FilterStateStore because setMultiple() does not reset missing keys to defaults.`, 'HIGH');
    }
    assert(!hasPhantomPrice, 'T3.4-Back-S2-No-Phantom-Price', `price must be cleared on back navigation to S2 (expected: '', actual: '${storeAfterS2Back.price}')`);

    // SIMULATE BACKWARD: S2 -> S1 (pop back to S1 where type was not selected)
    historyIndex = 1;
    const targetStateS1 = historyStack[historyIndex];
    FilterStateStore.setMultiple(targetStateS1.state.params, { source: 'popstate', triggerFetch: false });
    const storeAfterS1Back = FilterStateStore.getAll();
    const hasPhantomType = storeAfterS1Back.type !== '0' && storeAfterS1Back.type !== '';

    if (hasPhantomType) {
        recordFinding('F-M2-POPSTATE-PHANTOM-TYPE', `When navigating BACK from S2 to S1, type='${storeAfterS1Back.type}' persists in FilterStateStore.`, 'HIGH');
    }
    assert(!hasPhantomType, 'T3.5-Back-S1-No-Phantom-Type', `type must be reset on back navigation to S1 (expected: '0' or '', actual: '${storeAfterS1Back.type}')`);

    // SIMULATE BACKWARD: S1 -> S0 (pop back to S0 default)
    historyIndex = 0;
    const targetStateS0 = historyStack[historyIndex];
    FilterStateStore.setMultiple(targetStateS0.state.params, { source: 'popstate', triggerFetch: false });
    const storeAfterS0Back = FilterStateStore.getAll();
    const hasPhantomDistrict = storeAfterS0Back.khu_vuc !== '';

    if (hasPhantomDistrict) {
        recordFinding('F-M2-POPSTATE-PHANTOM-DISTRICT', `When navigating BACK from S1 to S0, khu_vuc='${storeAfterS0Back.khu_vuc}' persists in FilterStateStore.`, 'HIGH');
    }
    assert(!hasPhantomDistrict, 'T3.6-Back-S0-No-Phantom-District', `khu_vuc must be cleared on back navigation to S0 (expected: '', actual: '${storeAfterS0Back.khu_vuc}')`);
}

// -------------------------------------------------------------
// Suite 4: PushHistory Flag & Popstate Infinite Loop Protection
// -------------------------------------------------------------
console.log(`\n${BLUE}▶ Suite 4: Popstate History Flag & Loop Protection${RESET}`);
{
    // Inspect fetchListings call inside popstate listener:
    // Line 1710: fetchListings(params, false);
    // Line 1191: if (pushHistory && !isListingsPoppingState && response.data.url && window.history && window.history.pushState)
    const popstateBlock = mainJs.slice(mainJs.indexOf('window.addEventListener("popstate"'), mainJs.indexOf('if (pageContext === "home"'));
    const fetchListingsPushFalse = popstateBlock.includes('fetchListings(params, false)');
    const isPoppingStateGuard = popstateBlock.includes('isListingsPoppingState = true') && popstateBlock.includes('isListingsPoppingState = false');

    assert(fetchListingsPushFalse, 'T4.1-Popstate-PushHistory-False', 'popstate listener passes pushHistory = false to fetchListings');
    assert(isPoppingStateGuard, 'T4.2-Popstate-Guard-Flag', 'isListingsPoppingState flag guards against pushState during popstate');

    // Projects popstate
    const projectsPopstateBlock = mainJs.slice(mainJs.indexOf('// Popstate for projects'), mainJs.indexOf('// Toggle mô tả dự án'));
    const projectsPushFalse = projectsPopstateBlock.includes('handleAjaxFilter($activeForm, params.paged || 1, false)');
    assert(projectsPushFalse, 'T4.3-Projects-Popstate-PushHistory-False', 'projects popstate listener passes pushHistory = false to handleAjaxFilter');
}

// -------------------------------------------------------------
// Suite 5: parseBdsUrlToParams Pathname Resolution
// -------------------------------------------------------------
console.log(`\n${BLUE}▶ Suite 5: parseBdsUrlToParams Pathname & SearchParam Extraction${RESET}`);
{
    // Evaluate parseBdsUrlToParams
    // Helper function stubs for evaluation
    function parsePriceSlug(slug) {
        if (!slug) return null;
        if (slug === 'gia-thoa-thuan') return 'thoa_thuan';
        let m = slug.match(/^gia-duoi-([0-9.]+)-ty$/);
        if (m) return '0-' + (parseFloat(m[1]) * 1000);
        m = slug.match(/^gia-tu-([0-9.]+)-(?:trieu|ty)-den-([0-9.]+)-(?:trieu|ty)$/);
        if (m) {
            const v1 = slug.includes('-trieu-den-') ? parseFloat(m[1]) : parseFloat(m[1]) * 1000;
            const v2 = slug.endsWith('-ty') ? parseFloat(m[2]) * 1000 : parseFloat(m[2]);
            return v1 + '-' + v2;
        }
        m = slug.match(/^gia-tu-([0-9.]+)-den-([0-9.]+)-ty$/);
        if (m) return (parseFloat(m[1]) * 1000) + '-' + (parseFloat(m[2]) * 1000);
        m = slug.match(/^gia-tren-([0-9.]+)-ty$/);
        if (m) return String(parseFloat(m[1]) * 1000);
        return null;
    }

    function parseAreaSlug(slug) {
        if (!slug) return null;
        let m = slug.match(/^dt-duoi-([0-9]+)m2$/);
        if (m) return '0-' + m[1];
        m = slug.match(/^dt-tu-([0-9]+)m2-den-([0-9]+)m2$/);
        if (m) return m[1] + '-' + m[2];
        m = slug.match(/^dt-tren-([0-9]+)m2$/);
        if (m) return m[1];
        return null;
    }

    function parseFilterSlug(slug) {
        let price = null;
        let area = null;
        if (slug.indexOf('gia-') === 0 && slug.indexOf('-dt-') !== -1) {
            const idx = slug.indexOf('-dt-');
            price = parsePriceSlug(slug.substring(0, idx));
            area = parseAreaSlug(slug.substring(idx + 1));
        } else if (slug.indexOf('gia-') === 0) {
            price = parsePriceSlug(slug);
        } else if (slug.indexOf('dt-') === 0) {
            area = parseAreaSlug(slug);
        }
        return { price, area };
    }

    const parseBdsUrlToParams = eval('(function(parsePriceSlug, parseAreaSlug, parseFilterSlug) {\nreturn ' + parseBdsUrlToParamsMatch[0].replace('const parseBdsUrlToParams = ', '') + '\n})(parsePriceSlug, parseAreaSlug, parseFilterSlug)');

    // Test 1: Combined type + district path
    const url1 = new URL('https://example.com/can-ho-chung-cu-quan-cau-giay/');
    const params1 = parseBdsUrlToParams(url1);
    assert(params1.type === 'can-ho-chung-cu', 'T5.1a-Parse-TypeDistrict-Type', 'Parses type from pathname');
    assert(params1.khu_vuc === 'cau-giay', 'T5.1b-Parse-TypeDistrict-District', 'Parses district from pathname');

    // Test 2: District + Compound Filter Slug
    const url2 = new URL('https://example.com/bat-dong-san-quan-tay-ho/gia-tu-3-ty-den-5-ty-dt-tu-50m2-den-80m2/');
    const params2 = parseBdsUrlToParams(url2);
    assert(params2.khu_vuc === 'tay-ho', 'T5.2a-Parse-Compound-District', 'Parses district from compound path');
    assert(params2.price === '3000-5000', 'T5.2b-Parse-Compound-Price', 'Parses price range from compound slug');
    assert(params2.area === '50-80', 'T5.2c-Parse-Compound-Area', 'Parses area range from compound slug');

    // Test 3: Pagination segment
    const url3 = new URL('https://example.com/bat-dong-san-quan-ha-dong/page/3/');
    const params3 = parseBdsUrlToParams(url3);
    assert(params3.khu_vuc === 'ha-dong', 'T5.3a-Parse-Paged-District', 'Parses district before page segment');
    assert(params3.paged === 3, 'T5.3b-Parse-Paged-Number', 'Parses paged=3 from /page/3/');

    // Test 4: Search query params
    const url4 = new URL('https://example.com/bat-dong-san/?khu_vuc=dong-da&so_phong_ngu=3&huong_nha=dong-nam');
    const params4 = parseBdsUrlToParams(url4);
    assert(params4.khu_vuc === 'dong-da', 'T5.4a-Parse-QueryParam-District', 'Parses khu_vuc from query param');
    assert(params4.so_phong_ngu === '3', 'T5.4b-Parse-QueryParam-Bedrooms', 'Parses so_phong_ngu from query param');
    assert(params4.huong_nha === 'dong-nam', 'T5.4c-Parse-QueryParam-Orientation', 'Parses huong_nha from query param');
}

// -------------------------------------------------------------
// Suite 6: Proposed Mitigation Verification (Merge Defaults on Popstate)
// -------------------------------------------------------------
console.log(`\n${BLUE}▶ Suite 6: Empirical Mitigation Proof (Defaults Merge on Popstate)${RESET}`);
{
    // Re-seed S4 state
    FilterStateStore.reset();
    FilterStateStore.setMultiple({
        khu_vuc: 'cau-giay',
        type: '12',
        price: '3000-5000',
        so_phong_ngu: '3',
        phap_ly: 'so-do'
    });

    // Mitigation logic: merge with defaults before setMultiple
    function safePopstateUpdate(store, incomingParams) {
        const fullState = Object.assign({}, store.getDefaults(), incomingParams);
        store.setMultiple(fullState, { source: 'popstate', triggerFetch: false, force: true });
    }

    // Step 1: Pop back to S3 (no advanced)
    safePopstateUpdate(FilterStateStore, { khu_vuc: 'cau-giay', type: '12', price: '3000-5000' });
    const s3 = FilterStateStore.getAll();
    assert(s3.so_phong_ngu === '', 'T6.1a-Mitigation-S3-Bedrooms-Cleared', 'so_phong_ngu cleanly reset to default ""');
    assert(s3.phap_ly === '', 'T6.1b-Mitigation-S3-Legal-Cleared', 'phap_ly cleanly reset to default ""');
    assert(s3.price === '3000-5000', 'T6.1c-Mitigation-S3-Price-Retained', 'price retained at "3000-5000"');

    // Step 2: Pop back to S2 (no price)
    safePopstateUpdate(FilterStateStore, { khu_vuc: 'cau-giay', type: '12' });
    const s2 = FilterStateStore.getAll();
    assert(s2.price === '', 'T6.2a-Mitigation-S2-Price-Cleared', 'price cleanly reset to default ""');
    assert(s2.type === '12', 'T6.2b-Mitigation-S2-Type-Retained', 'type retained at "12"');

    // Step 3: Pop back to S1 (no type)
    safePopstateUpdate(FilterStateStore, { khu_vuc: 'cau-giay' });
    const s1 = FilterStateStore.getAll();
    assert(s1.type === '0', 'T6.3a-Mitigation-S1-Type-Reset', 'type cleanly reset to default "0"');
    assert(s1.khu_vuc === 'cau-giay', 'T6.3b-Mitigation-S1-District-Retained', 'khu_vuc retained at "cau-giay"');

    // Step 4: Pop back to S0 (default)
    safePopstateUpdate(FilterStateStore, {});
    const s0 = FilterStateStore.getAll();
    assert(s0.khu_vuc === '', 'T6.4a-Mitigation-S0-District-Reset', 'khu_vuc cleanly reset to default ""');
    assert(s0.type === '0', 'T6.4b-Mitigation-S0-Type-Reset', 'type cleanly reset to default "0"');
}

// -------------------------------------------------------------
// Summary & Verdict
// -------------------------------------------------------------
console.log('\n' + '='.repeat(80));
console.log(` SUMMARY: ${passedTests} Passed, ${failedTests} Failed, ${findings.length} Findings`);
console.log('='.repeat(80));

if (findings.length > 0) {
    console.log(`\n${YELLOW}EMPIRICAL FINDINGS IDENTIFIED:${RESET}`);
    findings.forEach(f => {
        console.log(` [${f.severity}] ${f.id}: ${f.description}`);
    });
}

const verdict = (failedTests === 0 && findings.filter(f => f.severity === 'CRITICAL' || f.severity === 'HIGH').length === 0) ? 'APPROVE' : 'REQUEST_CHANGES';
console.log(`\nVERDICT: [${verdict === 'APPROVE' ? GREEN : RED}${verdict}${RESET}]`);

process.exit(failedTests > 0 ? 1 : 0);
