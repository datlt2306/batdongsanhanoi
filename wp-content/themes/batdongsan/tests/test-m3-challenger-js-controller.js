/**
 * Empirical Stress-Test for js/main.js - Unified Search Form Controller & bfcache Navigation
 * Challenger M3_iter2_1
 *
 * Requirements:
 * 1. Simulate calling initUnifiedSearchForm() 1 time, 2 times, and 5 times in a Node environment.
 *    Assert that listener count remains invariant (0 duplicate listeners).
 * 2. Assert that clicking [data-unified-trigger] successfully opens the panel (removes hidden)
 *    and does NOT immediately close it.
 * 3. Assert that $(window).trigger("pageshow") successfully re-enables any disabled inputs inside .unified-search-form.
 * 4. Stress-test edge cases: outside click, escape key, option selection, tab switching, and pre-disabled inputs.
 */

const fs = require('fs');
const path = require('path');

// 1. Load js/main.js and extract initUnifiedSearchForm
const mainJsPath = path.resolve(__dirname, '../js/main.js');
const mainJs = fs.readFileSync(mainJsPath, 'utf8');
const fnMatch = mainJs.match(/function initUnifiedSearchForm\(\) \{([\s\S]*?)\n    \}/);

if (!fnMatch) {
    console.error("FATAL: Could not locate initUnifiedSearchForm() in js/main.js");
    process.exit(1);
}

const initUnifiedSearchFormBody = fnMatch[1];

// 2. Build DOM & Event Simulation Engine
class MockNode {
    constructor(selector, props = {}) {
        this.selector = selector;
        this.tagName = props.tagName || 'div';
        this.classes = new Set(props.classes || []);
        this.attributes = Object.assign({}, props.attributes || {});
        this.dataStore = Object.assign({}, props.data || {});
        this.properties = Object.assign({ disabled: false }, props.properties || {});
        this.listeners = []; // { event, namespace, selector, handler }
        this.children = [];
        this.parent = null;
        this.textContent = props.text || '';
        this.display = 'block';
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

    prop(name, val) {
        if (val !== undefined) {
            this.properties[name] = val;
            return this;
        }
        return this.properties[name];
    }

    data(name, val) {
        const camel = name.replace(/-([a-z])/g, (_, c) => c.toUpperCase());
        const kebab = name.replace(/([A-Z])/g, '-$1').toLowerCase();
        if (val !== undefined) {
            this.dataStore[name] = val;
            this.dataStore[camel] = val;
            return this;
        }
        if (this.dataStore[name] !== undefined) return this.dataStore[name];
        if (this.dataStore[camel] !== undefined) return this.dataStore[camel];
        if (this.attributes['data-' + kebab] !== undefined) return this.attributes['data-' + kebab];
        if (this.attributes['data-' + name] !== undefined) return this.attributes['data-' + name];
        return undefined;
    }

    val(val) {
        if (val !== undefined) {
            this.attributes['value'] = String(val);
            return this;
        }
        return this.attributes['value'] !== undefined ? this.attributes['value'] : '';
    }

    text(txt) {
        if (txt !== undefined) {
            this.textContent = String(txt);
            return this;
        }
        return this.textContent;
    }

    show() {
        this.display = 'block';
        this.removeClass('hidden');
        return this;
    }

    hide() {
        this.display = 'none';
        this.addClass('hidden');
        return this;
    }

    // Match CSS selector on node
    matches(sel) {
        if (!sel) return false;
        if (sel === ':disabled') return !!this.properties.disabled;
        if (sel.includes(',')) {
            return sel.split(',').some(part => this.matches(part.trim()));
        }
        // Compound tag + attribute, e.g. input[name='hinh_thuc'] or div[data-unified-dropdown]
        const tagAttrMatch = sel.match(/^([a-zA-Z0-9_-]+)(\[[^\]]+\])$/);
        if (tagAttrMatch) {
            const [, tag, attrSel] = tagAttrMatch;
            return this.tagName.toLowerCase() === tag.toLowerCase() && this.matches(attrSel);
        }
        if (sel.startsWith('.')) {
            const cls = sel.slice(1);
            return this.classes.has(cls);
        }
        if (sel.startsWith('[') && sel.endsWith(']')) {
            const inner = sel.slice(1, -1);
            if (inner.includes('=')) {
                let [attrName, expectedVal] = inner.split('=');
                expectedVal = expectedVal.replace(/^['"]|['"]$/g, '');
                if (attrName.startsWith('data-')) {
                    const dataKey = attrName.slice(5).replace(/-([a-z])/g, (_, c) => c.toUpperCase());
                    return String(this.dataStore[dataKey] || this.attributes[attrName]) === String(expectedVal);
                }
                return String(this.attributes[attrName]) === String(expectedVal);
            } else {
                if (inner.startsWith('data-')) {
                    const dataKey = inner.slice(5).replace(/-([a-z])/g, (_, c) => c.toUpperCase());
                    return (dataKey in this.dataStore) || (inner in this.attributes);
                }
                return inner in this.attributes;
            }
        }
        return this.tagName.toLowerCase() === sel.toLowerCase();
    }

    find(sel) {
        const results = [];
        const traverse = (node) => {
            for (const child of node.children) {
                if (child.matches(sel)) {
                    results.push(child);
                }
                traverse(child);
            }
        };
        traverse(this);
        return wrapJQuery(results);
    }

    closest(sel) {
        let curr = this;
        while (curr) {
            if (curr.matches(sel)) {
                return wrapJQuery([curr]);
            }
            curr = curr.parent;
        }
        return wrapJQuery([]);
    }

    addListener(eventStr, selOrHandler, maybeHandler) {
        let sel = null;
        let handler = null;
        if (typeof selOrHandler === 'function') {
            handler = selOrHandler;
        } else {
            sel = selOrHandler;
            handler = maybeHandler;
        }

        const [eventBase, ...nsParts] = eventStr.split('.');
        const ns = nsParts.length > 0 ? '.' + nsParts.join('.') : '';

        this.listeners.push({
            event: eventBase,
            namespace: ns,
            selector: sel,
            handler: handler
        });
    }

    removeListener(eventStr, sel) {
        const [eventBase, ...nsParts] = eventStr.split('.');
        const targetNs = nsParts.length > 0 ? '.' + nsParts.join('.') : '';

        this.listeners = this.listeners.filter(l => {
            // If selector specified and doesn't match, keep
            if (sel && l.selector !== sel) return true;

            // Namespace match logic
            if (targetNs && !l.namespace.endsWith(targetNs)) return true;

            // Event name match logic
            if (eventBase && l.event !== eventBase) return true;

            // Otherwise, matched -> remove
            return false;
        });
    }

    dispatch(eventStr, extra = {}) {
        const [eventBase] = eventStr.split('.');
        const evt = Object.assign({
            type: eventBase,
            target: this,
            currentTarget: this,
            defaultPrevented: false,
            propagationStopped: false,
            preventDefault() { this.defaultPrevented = true; },
            stopPropagation() { this.propagationStopped = true; }
        }, extra);

        // Direct listeners
        for (const l of [...this.listeners]) {
            if (!l.selector && (l.event === eventBase || !eventBase)) {
                l.handler.call(this, evt);
                if (evt.propagationStopped) break;
            }
        }

        // Bubbling to parents if not stopped
        if (!evt.propagationStopped && this.parent) {
            this.parent.bubble(evt);
        }

        return evt;
    }

    bubble(evt) {
        // Check delegated listeners on this parent
        for (const l of [...this.listeners]) {
            if (l.selector && (l.event === evt.type || !evt.type)) {
                // Check if evt.target matches selector or is inside matching node
                let match = null;
                let curr = evt.target;
                while (curr && curr !== this) {
                    if (curr.matches(l.selector)) {
                        match = curr;
                        break;
                    }
                    curr = curr.parent;
                }
                if (match) {
                    evt.currentTarget = match;
                    l.handler.call(match, evt);
                    if (evt.propagationStopped) return;
                }
            } else if (!l.selector && (l.event === evt.type || !evt.type)) {
                evt.currentTarget = this;
                l.handler.call(this, evt);
                if (evt.propagationStopped) return;
            }
        }

        if (!evt.propagationStopped && this.parent) {
            this.parent.bubble(evt);
        }
    }
}

// Global Document and Window mock
const mockDocument = new MockNode('document', { tagName: '#document' });
const mockWindow = new MockNode('window', { tagName: '#window' });

function wrapJQuery(nodes) {
    const arr = Array.isArray(nodes) ? nodes : [nodes];
    const wrapper = Object.create(jQueryProto);
    for (let i = 0; i < arr.length; i++) {
        wrapper[i] = arr[i];
    }
    wrapper.length = arr.length;
    return wrapper;
}

const jQueryProto = {
    length: 0,
    each(fn) {
        for (let i = 0; i < this.length; i++) {
            fn.call(this[i], i, this[i]);
        }
        return this;
    },
    first() {
        return this.length > 0 ? wrapJQuery([this[0]]) : wrapJQuery([]);
    },
    find(sel) {
        const found = [];
        this.each(function() {
            const sub = this.find(sel);
            for (let i = 0; i < sub.length; i++) {
                if (!found.includes(sub[i])) found.push(sub[i]);
            }
        });
        return wrapJQuery(found);
    },
    closest(sel) {
        const found = [];
        this.each(function() {
            const c = this.closest(sel);
            for (let i = 0; i < c.length; i++) {
                if (!found.includes(c[i])) found.push(c[i]);
            }
        });
        return wrapJQuery(found);
    },
    filter(sel) {
        const filtered = [];
        this.each(function() {
            if (this.matches(sel)) filtered.push(this);
        });
        return wrapJQuery(filtered);
    },
    addClass(cls) {
        this.each(function() { this.addClass(cls); });
        return this;
    },
    removeClass(cls) {
        this.each(function() { this.removeClass(cls); });
        return this;
    },
    hasClass(cls) {
        return this.length > 0 && this[0].hasClass(cls);
    },
    attr(k, v) {
        if (v !== undefined) {
            this.each(function() { this.attr(k, v); });
            return this;
        }
        return this.length > 0 ? this[0].attr(k) : undefined;
    },
    prop(k, v) {
        if (v !== undefined) {
            this.each(function() { this.prop(k, v); });
            return this;
        }
        return this.length > 0 ? this[0].prop(k) : undefined;
    },
    data(k, v) {
        if (v !== undefined) {
            this.each(function() { this.data(k, v); });
            return this;
        }
        return this.length > 0 ? this[0].data(k) : undefined;
    },
    val(v) {
        if (v !== undefined) {
            this.each(function() { this.val(v); });
            return this;
        }
        return this.length > 0 ? this[0].val() : '';
    },
    text(t) {
        if (t !== undefined) {
            this.each(function() { this.text(t); });
            return this;
        }
        return this.length > 0 ? this[0].text() : '';
    },
    show() {
        this.each(function() { this.show(); });
        return this;
    },
    hide() {
        this.each(function() { this.hide(); });
        return this;
    },
    on(eventStr, selOrHandler, maybeHandler) {
        this.each(function() { this.addListener(eventStr, selOrHandler, maybeHandler); });
        return this;
    },
    off(eventStr, sel) {
        this.each(function() { this.removeListener(eventStr, sel); });
        return this;
    },
    trigger(eventStr, extra) {
        this.each(function() { this.dispatch(eventStr, extra); });
        return this;
    },
    triggerHandler(eventStr, extra) {
        if (this.length === 0) return undefined;
        return this[0].dispatch(eventStr, extra);
    }
};

// 3. Build Realistic Unified Search Form DOM
function createUnifiedSearchFormDOM() {
    mockDocument.children = [];
    mockDocument.listeners = [];
    mockWindow.listeners = [];

    // Root wrapper
    const root = new MockNode('div', { classes: ['unified-search-wrapper'] });
    mockDocument.appendChild(root);

    // Tabs container
    const tabsContainer = new MockNode('div', { attributes: { 'data-unified-tabs': 'true' } });
    root.appendChild(tabsContainer);

    const tabBan = new MockNode('button', {
        classes: ['unified-search-tab', 'bg-primary', 'text-white', 'font-semibold', 'shadow-button'],
        attributes: {
            'role': 'tab',
            'aria-selected': 'true',
            'data-unified-tab': 'ban',
            'data-action': 'https://batdongsanhanoi.net.vn/bat-dong-san',
            'data-hinh-thuc': 'chuyen-nhuong',
            'data-placeholder': 'Tìm kiếm bất động sản, biệt thự...'
        },
        data: {
            unifiedTab: 'ban',
            action: 'https://batdongsanhanoi.net.vn/bat-dong-san',
            hinhThuc: 'chuyen-nhuong',
            placeholder: 'Tìm kiếm bất động sản, biệt thự...'
        }
    });
    tabsContainer.appendChild(tabBan);

    const tabChoThue = new MockNode('button', {
        classes: ['unified-search-tab', 'text-charcoal-muted'],
        attributes: {
            'role': 'tab',
            'aria-selected': 'false',
            'data-unified-tab': 'cho-thue',
            'data-action': 'https://batdongsanhanoi.net.vn/bat-dong-san',
            'data-hinh-thuc': 'cho-thue',
            'data-placeholder': 'Tìm kiếm căn hộ, cho thuê...'
        },
        data: {
            unifiedTab: 'cho-thue',
            action: 'https://batdongsanhanoi.net.vn/bat-dong-san',
            hinhThuc: 'cho-thue',
            placeholder: 'Tìm kiếm căn hộ, cho thuê...'
        }
    });
    tabsContainer.appendChild(tabChoThue);

    const tabDuAn = new MockNode('button', {
        classes: ['unified-search-tab', 'text-charcoal-muted'],
        attributes: {
            'role': 'tab',
            'aria-selected': 'false',
            'data-unified-tab': 'du-an',
            'data-action': 'https://batdongsanhanoi.net.vn/du-an-bat-dong-san',
            'data-hinh-thuc': '',
            'data-placeholder': 'Tìm kiếm dự án...'
        },
        data: {
            unifiedTab: 'du-an',
            action: 'https://batdongsanhanoi.net.vn/du-an-bat-dong-san',
            hinhThuc: '',
            placeholder: 'Tìm kiếm dự án...'
        }
    });
    tabsContainer.appendChild(tabDuAn);

    // Form
    const form = new MockNode('form', {
        tagName: 'form',
        classes: ['unified-search-form'],
        attributes: {
            'action': 'https://batdongsanhanoi.net.vn/bat-dong-san',
            'method': 'get',
            'data-unified-search-form': 'true'
        },
        data: {
            unifiedSearchForm: true
        }
    });
    root.appendChild(form);

    // Hidden input: hinh_thuc
    const hiddenHinhThuc = new MockNode('input', {
        tagName: 'input',
        attributes: {
            'type': 'hidden',
            'name': 'hinh_thuc',
            'id': 'unified-hinh-thuc',
            'value': 'chuyen-nhuong',
            'data-unified-input': 'hinh_thuc'
        },
        data: { unifiedInput: 'hinh_thuc' }
    });
    form.appendChild(hiddenHinhThuc);

    // Keyword input
    const keywordInput = new MockNode('input', {
        tagName: 'input',
        attributes: {
            'type': 'text',
            'name': 'project-name',
            'placeholder': 'Tìm kiếm bất động sản, biệt thự...',
            'value': ''
        }
    });
    form.appendChild(keywordInput);

    // Dropdown 1: Type
    const dropdownType = new MockNode('div', {
        attributes: { 'data-unified-dropdown': 'type' },
        data: { unifiedDropdown: 'type' }
    });
    form.appendChild(dropdownType);

    const triggerType = new MockNode('button', {
        attributes: {
            'type': 'button',
            'data-unified-trigger': 'true',
            'aria-expanded': 'false'
        },
        data: { unifiedTrigger: true }
    });
    dropdownType.appendChild(triggerType);

    const labelType = new MockNode('span', { classes: ['unified-dropdown-label'], text: 'Tất cả loại hình' });
    triggerType.appendChild(labelType);
    const arrowType = new MockNode('svg', { classes: ['unified-dropdown-arrow'] });
    triggerType.appendChild(arrowType);

    const panelType = new MockNode('div', {
        classes: ['hidden'],
        attributes: { 'data-unified-panel': 'true' },
        data: { unifiedPanel: true }
    });
    dropdownType.appendChild(panelType);

    const inputType = new MockNode('input', {
        tagName: 'input',
        attributes: {
            'type': 'hidden',
            'name': 'type',
            'value': '0',
            'data-unified-input': 'type'
        },
        data: { unifiedInput: 'type' }
    });
    dropdownType.appendChild(inputType);

    const optTypeAll = new MockNode('div', {
        classes: ['unified-dropdown-option', 'text-primary', 'font-semibold', 'bg-stone/30'],
        attributes: { 'data-unified-option': 'true', 'data-group': 'all', 'data-value': '0', 'data-label': 'Tất cả loại hình' },
        data: { unifiedOption: true, group: 'all', value: '0', label: 'Tất cả loại hình' },
        text: 'Tất cả loại hình'
    });
    panelType.appendChild(optTypeAll);

    const optTypeBds = new MockNode('div', {
        classes: ['unified-dropdown-option'],
        attributes: { 'data-unified-option': 'true', 'data-group': 'bds', 'data-value': '12', 'data-label': 'Biệt thự' },
        data: { unifiedOption: true, group: 'bds', value: '12', label: 'Biệt thự' },
        text: 'Biệt thự'
    });
    panelType.appendChild(optTypeBds);

    const optTypeDuAn = new MockNode('div', {
        classes: ['unified-dropdown-option'],
        attributes: { 'data-unified-option': 'true', 'data-group': 'du-an', 'data-value': '34', 'data-label': 'Chung cư cao cấp' },
        data: { unifiedOption: true, group: 'du-an', value: '34', label: 'Chung cư cao cấp' },
        text: 'Chung cư cao cấp'
    });
    panelType.appendChild(optTypeDuAn);

    // Dropdown 2: Price
    const dropdownPrice = new MockNode('div', {
        attributes: { 'data-unified-dropdown': 'price' },
        data: { unifiedDropdown: 'price' }
    });
    form.appendChild(dropdownPrice);

    const triggerPrice = new MockNode('button', {
        attributes: { 'type': 'button', 'data-unified-trigger': 'true', 'aria-expanded': 'false' },
        data: { unifiedTrigger: true }
    });
    dropdownPrice.appendChild(triggerPrice);

    const panelPrice = new MockNode('div', {
        classes: ['hidden'],
        attributes: { 'data-unified-panel': 'true' },
        data: { unifiedPanel: true }
    });
    dropdownPrice.appendChild(panelPrice);

    const inputPrice = new MockNode('input', {
        tagName: 'input',
        attributes: { 'type': 'hidden', 'name': 'price', 'value': '0', 'data-unified-input': 'price' },
        data: { unifiedInput: 'price' }
    });
    dropdownPrice.appendChild(inputPrice);

    return {
        root,
        mockDocument,
        mockWindow,
        form,
        tabsContainer,
        tabBan,
        tabChoThue,
        tabDuAn,
        hiddenHinhThuc,
        keywordInput,
        dropdownType,
        triggerType,
        panelType,
        inputType,
        arrowType,
        labelType,
        optTypeAll,
        optTypeBds,
        optTypeDuAn,
        dropdownPrice,
        triggerPrice,
        panelPrice,
        inputPrice
    };
}

// Helper to count all registered event listeners across the entire DOM tree
function countAllListeners(dom) {
    let count = 0;
    const countNode = (node) => {
        count += node.listeners.length;
        for (const child of node.children) {
            countNode(child);
        }
    };
    countNode(dom.mockDocument);
    count += dom.mockWindow.listeners.length;
    return count;
}

// Selector engine for jQuery
function setupJQuery(dom) {
    const $ = function(selector) {
        if (!selector) return wrapJQuery([]);
        if (selector === dom.mockDocument || selector === 'document') return wrapJQuery([dom.mockDocument]);
        if (selector === dom.mockWindow || selector === 'window') return wrapJQuery([dom.mockWindow]);
        if (selector instanceof MockNode) return wrapJQuery([selector]);
        if (selector.length !== undefined && selector[0] instanceof MockNode) return selector;

        if (selector === '[data-unified-search-form]') {
            return wrapJQuery([dom.form]);
        }
        if (selector === '[data-unified-tabs] [data-unified-tab]') {
            return wrapJQuery([dom.tabBan, dom.tabChoThue, dom.tabDuAn]);
        }
        return dom.mockDocument.find(selector);
    };
    $.fn = jQueryProto;
    return $;
}

// Test Runner
console.log("================================================================================");
console.log(" CHALLENGER M3_ITER2_1: JS CONTROLLER & BFCACHE EMPIRICAL STRESS HARNESS");
console.log("================================================================================\n");

let passes = 0;
let failures = 0;

function assert(condition, message) {
    if (condition) {
        passes++;
        console.log(`  ✔ PASS: ${message}`);
    } else {
        failures++;
        console.error(`  ✘ FAIL: ${message}`);
    }
}

// -----------------------------------------------------------------------------
// Test 1: Invariant Listener Count (1x, 2x, 5x calls)
// -----------------------------------------------------------------------------
console.log("▶ Test 1: Listener Invariance on Multiple Invocations (1x, 2x, 5x)");
{
    const dom = createUnifiedSearchFormDOM();
    const $ = setupJQuery(dom);

    const initFn = new Function('$', 'jQuery', 'window', 'document', 'setTimeout', initUnifiedSearchFormBody);

    // Initial count
    const initialCount = countAllListeners(dom);
    assert(initialCount === 0, "1.0: Zero initial listeners before initialization");

    // Call 1
    initFn($, $, dom.mockWindow, dom.mockDocument, setTimeout);
    const count1 = countAllListeners(dom);
    assert(count1 > 0, `1.1: Call 1 registers ${count1} active event listeners`);

    // Call 2
    initFn($, $, dom.mockWindow, dom.mockDocument, setTimeout);
    const count2 = countAllListeners(dom);
    assert(count2 === count1, `1.2: Call 2 listener count is invariant (${count2} === ${count1}, 0 duplicate listeners)`);

    // Call 3, 4, 5
    initFn($, $, dom.mockWindow, dom.mockDocument, setTimeout);
    initFn($, $, dom.mockWindow, dom.mockDocument, setTimeout);
    initFn($, $, dom.mockWindow, dom.mockDocument, setTimeout);
    const count5 = countAllListeners(dom);
    assert(count5 === count1, `1.3: Call 5 listener count remains strictly invariant (${count5} === ${count1}, 0 duplicate listeners after 5 invocations)`);

    // Verify idempotency flags set on form
    assert(dom.form.data('unified-search-initialized') === true, "1.4: data('unified-search-initialized') is true");
    assert(dom.form.data('unified-initialized') === true, "1.5: data('unified-initialized') is true");
    assert(dom.form.attr('data-unified-initialized') === 'true', "1.6: attr('data-unified-initialized') is 'true'");
}

// -----------------------------------------------------------------------------
// Test 2: Panel Toggle Behavior & Click Stability
// -----------------------------------------------------------------------------
console.log("\n▶ Test 2: Dropdown Panel Toggle & Click Stability ([data-unified-trigger])");
{
    const dom = createUnifiedSearchFormDOM();
    const $ = setupJQuery(dom);
    const initFn = new Function('$', 'jQuery', 'window', 'document', 'setTimeout', initUnifiedSearchFormBody);

    // Initialize controller
    initFn($, $, dom.mockWindow, dom.mockDocument, setTimeout);

    // Verify initial closed state
    assert(dom.panelType.hasClass('hidden'), "2.0: Panel is initially hidden");
    assert(dom.triggerType.attr('aria-expanded') === 'false', "2.1: Trigger aria-expanded is initially 'false'");
    assert(!dom.arrowType.hasClass('rotate-180'), "2.2: Arrow does not have rotate-180 initially");

    // Click trigger once -> Panel should OPEN
    dom.triggerType.dispatch('click');

    assert(!dom.panelType.hasClass('hidden'), "2.3: Clicking [data-unified-trigger] removes 'hidden' (panel successfully OPENS)");
    assert(dom.triggerType.attr('aria-expanded') === 'true', "2.4: Trigger aria-expanded updated to 'true'");
    assert(dom.arrowType.hasClass('rotate-180'), "2.5: Arrow has 'rotate-180'");

    // Verify it does NOT immediately close (e.stopPropagation prevented document outside-click from firing)
    assert(!dom.panelType.hasClass('hidden'), "2.6: Panel remains open after click dispatch cycle (NOT immediately closed by double-listener or bubbling)");

    // Click outside on document -> Panel should CLOSE
    dom.mockDocument.dispatch('click', { target: dom.mockDocument });
    assert(dom.panelType.hasClass('hidden'), "2.7: Outside click on document successfully closes the panel (re-adds 'hidden')");
    assert(dom.triggerType.attr('aria-expanded') === 'false', "2.8: Trigger aria-expanded restored to 'false'");
    assert(!dom.arrowType.hasClass('rotate-180'), "2.9: Arrow rotate-180 removed");

    // Click trigger again -> Panel OPENS again
    dom.triggerType.dispatch('click');
    assert(!dom.panelType.hasClass('hidden'), "2.10: Re-clicking trigger successfully re-opens panel");

    // Click trigger while open -> Panel CLOSES (toggle)
    dom.triggerType.dispatch('click');
    assert(dom.panelType.hasClass('hidden'), "2.11: Clicking trigger while open closes the panel");

    // Test opening panel and pressing Escape key
    dom.triggerType.dispatch('click');
    assert(!dom.panelType.hasClass('hidden'), "2.12: Panel opened before Escape key test");
    dom.mockDocument.dispatch('keydown', { key: 'Escape' });
    assert(dom.panelType.hasClass('hidden'), "2.13: Escape keydown successfully closes the panel");

    // Test opening another panel closes the current one
    dom.triggerType.dispatch('click');
    assert(!dom.panelType.hasClass('hidden'), "2.14: Panel 1 (type) is open");
    dom.triggerPrice.dispatch('click');
    assert(dom.panelType.hasClass('hidden'), "2.15: Opening Panel 2 (price) closes Panel 1 (type)");
    assert(!dom.panelPrice.hasClass('hidden'), "2.16: Panel 2 (price) is open");
}

// -----------------------------------------------------------------------------
// Test 3: bfcache Navigation & Input Re-enabling
// -----------------------------------------------------------------------------
console.log("\n▶ Test 3: bfcache Navigation & Disabled Inputs Re-enabling");
{
    const dom = createUnifiedSearchFormDOM();
    const $ = setupJQuery(dom);
    const initFn = new Function('$', 'jQuery', 'window', 'document', 'setTimeout', initUnifiedSearchFormBody);

    initFn($, $, dom.mockWindow, dom.mockDocument, setTimeout);

    // Scenario A: Simulate form submit disabling empty inputs to prune query string
    dom.keywordInput.val('');
    dom.inputType.val('0'); // empty/zero

    // Directly simulate submit handler behavior
    dom.form.dispatch('submit');

    // In submit handler, empty inputs are disabled
    assert(dom.inputType.prop('disabled') === true, "3.1: Form submit properly disables empty input to prune query");

    // Now simulate user clicking browser Back button (bfcache restoration) -> $(window).trigger("pageshow")
    $(dom.mockWindow).trigger("pageshow");

    assert(dom.inputType.prop('disabled') === false, "3.2: $(window).trigger('pageshow') successfully re-enables disabled inputs");

    // Scenario B: Test multiple disabled inputs inside the form
    dom.keywordInput.prop('disabled', true);
    dom.inputType.prop('disabled', true);
    dom.inputPrice.prop('disabled', true);
    dom.hiddenHinhThuc.prop('disabled', true);

    assert(dom.keywordInput.prop('disabled') === true, "3.3: Pre-condition: keywordInput is disabled");
    assert(dom.inputType.prop('disabled') === true, "3.4: Pre-condition: inputType is disabled");
    assert(dom.inputPrice.prop('disabled') === true, "3.5: Pre-condition: inputPrice is disabled");
    assert(dom.hiddenHinhThuc.prop('disabled') === true, "3.6: Pre-condition: hiddenHinhThuc is disabled");

    // Trigger pageshow event
    $(dom.mockWindow).trigger("pageshow");

    assert(dom.keywordInput.prop('disabled') === false, "3.7: pageshow re-enabled keywordInput");
    assert(dom.inputType.prop('disabled') === false, "3.8: pageshow re-enabled inputType");
    assert(dom.inputPrice.prop('disabled') === false, "3.9: pageshow re-enabled inputPrice");
    assert(dom.hiddenHinhThuc.prop('disabled') === false, "3.10: pageshow re-enabled hiddenHinhThuc");

    // Scenario C: Fresh initialization when DOM already has disabled inputs (e.g., hard page reload with cached form state)
    const freshDom = createUnifiedSearchFormDOM();
    freshDom.keywordInput.prop('disabled', true);
    freshDom.inputType.prop('disabled', true);
    const fresh$ = setupJQuery(freshDom);

    assert(freshDom.keywordInput.prop('disabled') === true, "3.11: Pre-condition: fresh DOM has disabled input before init");
    initFn(fresh$, fresh$, freshDom.mockWindow, freshDom.mockDocument, setTimeout);
    assert(freshDom.keywordInput.prop('disabled') === false, "3.12: initUnifiedSearchForm() immediately un-disables controls on init (line 3496)");
    assert(freshDom.inputType.prop('disabled') === false, "3.13: initUnifiedSearchForm() immediately un-disables inputType on init");
}

// -----------------------------------------------------------------------------
// Test 4: Tab Switching & Dropdown Filtering Logic
// -----------------------------------------------------------------------------
console.log("\n▶ Test 4: Tab Switching Interaction & Option Filtering");
{
    const dom = createUnifiedSearchFormDOM();
    const $ = setupJQuery(dom);
    const initFn = new Function('$', 'jQuery', 'window', 'document', 'setTimeout', initUnifiedSearchFormBody);
    initFn($, $, dom.mockWindow, dom.mockDocument, setTimeout);

    // Initial state: Tab "Bán" active
    assert(dom.hiddenHinhThuc.val() === 'chuyen-nhuong', "4.1: Initial hinh_thuc is 'chuyen-nhuong'");
    assert(dom.form.attr('action') === 'https://batdongsanhanoi.net.vn/bat-dong-san', "4.2: Initial form action is /bat-dong-san");

    // Click Tab "Cho thuê"
    dom.tabChoThue.dispatch('click');
    assert(dom.hiddenHinhThuc.val() === 'cho-thue', "4.3: Clicking Tab 'Cho thuê' updates hinh_thuc to 'cho-thue'");
    assert(dom.tabChoThue.attr('aria-selected') === 'true', "4.4: Tab 'Cho thuê' aria-selected is 'true'");
    assert(dom.tabBan.attr('aria-selected') === 'false', "4.5: Tab 'Bán' aria-selected is 'false'");

    // Click Tab "Dự án"
    dom.tabDuAn.dispatch('click');
    assert(dom.hiddenHinhThuc.val() === '', "4.6: Clicking Tab 'Dự án' clears hinh_thuc to ''");
    assert(dom.form.attr('action') === 'https://batdongsanhanoi.net.vn/du-an-bat-dong-san', "4.7: Tab 'Dự án' updates action to /du-an-bat-dong-san");

    // Check option visibility filtering: In "Dự án" mode, bds options hidden, du-an options visible
    assert(dom.optTypeDuAn.display === 'block', "4.8: Option 'du-an' is visible under Tab 'Dự án'");
    assert(dom.optTypeBds.display === 'none', "4.9: Option 'bds' is hidden under Tab 'Dự án'");

    // Switch back to Tab "Bán"
    dom.tabBan.dispatch('click');
    assert(dom.optTypeBds.display === 'block', "4.10: Switching back to Tab 'Bán' displays 'bds' options");
    assert(dom.optTypeDuAn.display === 'none', "4.11: Switching back to Tab 'Bán' hides 'du-an' options");

    // Test option selection
    dom.triggerType.dispatch('click'); // open panel
    assert(!dom.panelType.hasClass('hidden'), "4.12: Panel opened before option selection");
    dom.optTypeBds.dispatch('click'); // select Biệt thự (val 12)
    assert(dom.inputType.val() === '12', "4.13: Selecting option sets input value to '12'");
    assert(dom.labelType.text() === 'Biệt thự', "4.14: Selecting option updates trigger label to 'Biệt thự'");
    assert(dom.panelType.hasClass('hidden'), "4.15: Selecting option automatically closes the panel");
}

// -----------------------------------------------------------------------------
// Test 5: Adversarial Stress Scenarios
// -----------------------------------------------------------------------------
console.log("\n▶ Test 5: Adversarial Edge Cases & Stress Scenarios");
{
    const dom = createUnifiedSearchFormDOM();
    const $ = setupJQuery(dom);
    const initFn = new Function('$', 'jQuery', 'window', 'document', 'setTimeout', initUnifiedSearchFormBody);
    initFn($, $, dom.mockWindow, dom.mockDocument, setTimeout);

    // Adversarial 5.1: Non-empty inputs must NOT be disabled on submit
    dom.keywordInput.val('Vinhomes Riverside');
    dom.inputType.val('12');
    dom.hiddenHinhThuc.val('chuyen-nhuong');
    dom.form.dispatch('submit');

    assert(dom.keywordInput.prop('disabled') === false, "5.1: Non-empty keyword input remains enabled on submit");
    assert(dom.inputType.prop('disabled') === false, "5.2: Non-empty type input remains enabled on submit");
    assert(dom.hiddenHinhThuc.prop('disabled') === false, "5.3: Non-empty hidden hinh_thuc remains enabled on submit");

    // Adversarial 5.2: Multiple rapid pageshow events in succession
    dom.keywordInput.prop('disabled', true);
    $(dom.mockWindow).trigger("pageshow");
    $(dom.mockWindow).trigger("pageshow");
    $(dom.mockWindow).trigger("pageshow");
    assert(dom.keywordInput.prop('disabled') === false, "5.4: Multiple rapid pageshow events cleanly re-enable inputs without error");

    // Adversarial 5.3: Re-invoking initUnifiedSearchForm() while panel is currently open
    dom.triggerType.dispatch('click');
    assert(!dom.panelType.hasClass('hidden'), "5.5: Panel is open");
    initFn($, $, dom.mockWindow, dom.mockDocument, setTimeout);
    assert(!dom.panelType.hasClass('hidden'), "5.6: Re-invoking init does not disrupt open panel state or destroy listeners");

    // Adversarial 5.4: Keyboard Escape when NO dropdown is open does not crash or throw
    let escapeThrew = false;
    try {
        dom.mockDocument.dispatch('click', { target: dom.mockDocument }); // close all
        dom.mockDocument.dispatch('keydown', { key: 'Escape' });
    } catch (e) {
        escapeThrew = true;
    }
    assert(!escapeThrew, "5.7: Pressing Escape with no open panels executes cleanly without exception");
}

console.log("\n================================================================================");
console.log(` SUMMARY: ${passes} Passed, ${failures} Failed`);
console.log("================================================================================");

if (failures > 0) {
    console.error(`\n[VERDICT: REJECT] ${failures} empirical tests failed.`);
    process.exit(1);
} else {
    console.log("\n[VERDICT: APPROVE] ALL JS CONTROLLER & BFCACHE EMPIRICAL TESTS PASSED (100% SUCCESS)!");
    process.exit(0);
}
