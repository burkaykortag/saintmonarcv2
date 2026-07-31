/**
 * SaintMonarc Enterprise PIM V2 – Shared JavaScript Module
 * Sprint 29.5 | Reusable UI logic for all PIM screens
 */

'use strict';

/* ═══════════════════════════════════════════════════════════
   PIM NAMESPACE
═══════════════════════════════════════════════════════════ */
window.PIM = window.PIM || {};

/* ─── Toast Notifications ───────────────────────────────── */
PIM.toast = (function () {
    let wrap = null;
    function getWrap() {
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'pim-toast-wrap';
            wrap.setAttribute('role', 'status');
            wrap.setAttribute('aria-live', 'polite');
            document.body.appendChild(wrap);
        }
        return wrap;
    }
    function show(msg, type = 'info', duration = 4000) {
        const icons = { success: 'bi-check-circle-fill', error: 'bi-exclamation-triangle-fill', info: 'bi-stars' };
        const el = document.createElement('div');
        el.className = `pim-toast ${type}`;
        el.setAttribute('role', 'alert');
        el.innerHTML = `
            <i class="bi ${icons[type] || 'bi-info-circle'} pim-toast-icon"></i>
            <span class="pim-toast-msg">${msg}</span>
            <i class="bi bi-x pim-toast-close" onclick="this.parentElement.remove()"></i>`;
        getWrap().appendChild(el);
        if (duration > 0) setTimeout(() => { el.classList.add('exit'); setTimeout(() => el.remove(), 320); }, duration);
        return el;
    }
    return { show, success: (m,d) => show(m,'success',d), error: (m,d) => show(m,'error',d), info: (m,d) => show(m,'info',d) };
})();

/* ─── Workspace Tab System ──────────────────────────────── */
PIM.tabs = (function () {
    function init(containerSelector) {
        const container = document.querySelector(containerSelector);
        if (!container) return;
        const items = container.querySelectorAll('.pim-tab-item');
        const panes = container.querySelectorAll('.pim-tab-pane');

        function activate(id) {
            items.forEach(it => {
                const matches = it.getAttribute('data-tab') === id;
                it.classList.toggle('active', matches);
                it.setAttribute('aria-selected', matches);
            });
            panes.forEach(p => p.classList.toggle('active', p.id === id));
            // Persist in sessionStorage
            try { sessionStorage.setItem('pim_tab_' + containerSelector, id); } catch (e) {}
        }

        // Restore saved tab
        const saved = (() => { try { return sessionStorage.getItem('pim_tab_' + containerSelector); } catch(e) { return null; } })();
        const initial = (saved && container.querySelector('[data-tab="' + saved + '"]')) ? saved
            : (items[0] ? items[0].getAttribute('data-tab') : null);
        if (initial) activate(initial);

        // Open tab from URL hash or query param
        const urlTab = new URLSearchParams(window.location.search).get('tab');
        if (urlTab && container.querySelector('[data-tab="' + urlTab + '"]')) activate(urlTab);

        items.forEach(it => {
            it.addEventListener('click', () => activate(it.getAttribute('data-tab')));
            it.setAttribute('tabindex', '0');
            it.setAttribute('role', 'tab');
            it.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); it.click(); }
            });
        });

        return { activate };
    }
    return { init };
})();

/* ─── Wizard / Stepper ──────────────────────────────────── */
PIM.wizard = (function () {
    function init(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        const steps  = Array.from(container.querySelectorAll('.pim-wizard-step'));
        const dots   = Array.from(container.querySelectorAll('.pim-step'));
        const conns  = Array.from(container.querySelectorAll('.pim-step-connector'));
        const prevBtn = container.querySelector('#wizPrevBtn');
        const nextBtn = container.querySelector('#wizNextBtn');
        const finBtn  = container.querySelector('#wizFinishBtn');
        const counter = container.querySelector('#wizStepCounter');
        let current = 0;

        function go(idx) {
            if (idx < 0 || idx >= steps.length) return;
            steps.forEach((s, i) => s.classList.toggle('active', i === idx));
            dots.forEach((d, i) => {
                d.classList.toggle('active', i === idx);
                d.classList.toggle('done', i < idx);
            });
            conns.forEach((c, i) => c.classList.toggle('done', i < idx));
            if (counter) counter.textContent = `Adım ${idx + 1} / ${steps.length}`;
            if (prevBtn) prevBtn.disabled = idx === 0;
            if (nextBtn) nextBtn.style.display = idx === steps.length - 1 ? 'none' : '';
            if (finBtn)  finBtn.style.display  = idx === steps.length - 1 ? '' : 'none';
            current = idx;
        }

        if (prevBtn) prevBtn.addEventListener('click', () => go(current - 1));
        if (nextBtn) nextBtn.addEventListener('click', () => {
            if (validateStep(current)) go(current + 1);
        });
        go(0);

        function validateStep(idx) {
            const step = steps[idx];
            if (!step) return true;
            const required = step.querySelectorAll('[required]');
            let ok = true;
            required.forEach(el => {
                if (!el.value.trim()) {
                    el.style.borderColor = 'var(--pim-danger)';
                    el.focus();
                    ok = false;
                } else {
                    el.style.borderColor = '';
                }
            });
            if (!ok) PIM.toast.error('Lütfen zorunlu alanları doldurun.');
            return ok;
        }

        return { go, current: () => current };
    }
    return { init };
})();

/* ─── Instant Search ────────────────────────────────────── */
PIM.search = (function () {
    function bind(inputId, rowSelector, attrs = ['data-name', 'data-sku']) {
        const input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll(rowSelector).forEach(el => {
                const text = attrs.map(a => (el.getAttribute(a) || '')).join(' ').toLowerCase();
                el.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        });
    }
    return { bind };
})();

/* ─── Tag Input ─────────────────────────────────────────── */
PIM.tagInput = (function () {
    function init(inputId, hiddenId, delimiters = [',', 'Enter']) {
        const input  = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        if (!input || !hidden) return;
        const wrap = input.parentElement;
        let tags = hidden.value ? hidden.value.split(',').filter(Boolean) : [];

        function render() {
            wrap.querySelectorAll('.pim-tag').forEach(t => t.remove());
            tags.forEach((tag, i) => {
                const el = document.createElement('span');
                el.className = 'pim-tag';
                el.innerHTML = `${tag} <i class="bi bi-x pim-tag-remove" data-i="${i}"></i>`;
                el.querySelector('.pim-tag-remove').addEventListener('click', () => {
                    tags.splice(i, 1); sync(); render();
                });
                wrap.insertBefore(el, input);
            });
            sync();
        }
        function sync() { hidden.value = tags.join(','); }
        function addTag(val) {
            val = val.trim();
            if (val && !tags.includes(val)) { tags.push(val); render(); }
            input.value = '';
        }

        input.addEventListener('keydown', e => {
            if (delimiters.includes(e.key)) { e.preventDefault(); addTag(input.value); }
            if (e.key === 'Backspace' && !input.value && tags.length) {
                tags.pop(); render();
            }
        });
        input.addEventListener('blur', () => { if (input.value.trim()) addTag(input.value); });
        render();
        return { getTags: () => tags };
    }
    return { init };
})();

/* ─── Character Counter ─────────────────────────────────── */
PIM.charCounter = (function () {
    function bind(inputId, counterId, limits = { warn: 140, max: 160 }) {
        const input   = document.getElementById(inputId);
        const counter = document.getElementById(counterId);
        if (!input || !counter) return;
        function update() {
            const len = input.value.length;
            counter.textContent = len + ' / ' + limits.max;
            counter.className = 'pim-char-counter' +
                (len > limits.max ? ' over' : len > limits.warn ? ' warn' : '');
        }
        input.addEventListener('input', update);
        update();
    }
    return { bind };
})();

/* ─── Dropzone (Media Manager) ──────────────────────────── */
PIM.dropzone = (function () {
    function init(zoneId, onFiles) {
        const zone = document.getElementById(zoneId);
        if (!zone) return;
        ['dragenter','dragover'].forEach(ev => zone.addEventListener(ev, e => {
            e.preventDefault(); zone.classList.add('dragover');
        }));
        ['dragleave','dragend','drop'].forEach(ev => zone.addEventListener(ev, () => zone.classList.remove('dragover')));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            if (e.dataTransfer.files.length && onFiles) onFiles(e.dataTransfer.files);
        });
        const fileInput = zone.querySelector('input[type="file"]');
        if (fileInput) fileInput.addEventListener('change', () => {
            if (fileInput.files.length && onFiles) onFiles(fileInput.files);
        });
    }
    return { init };
})();

/* ─── Sortable Media Grid ───────────────────────────────── */
PIM.sortable = (function () {
    function init(gridId) {
        const grid = document.getElementById(gridId);
        if (!grid) return;
        let dragged = null;
        grid.querySelectorAll('.pim-media-item[draggable]').forEach(item => {
            item.addEventListener('dragstart', () => { dragged = item; item.style.opacity = '.4'; });
            item.addEventListener('dragend',   () => { dragged = null; item.style.opacity = ''; });
            item.addEventListener('dragover',  e => { e.preventDefault(); });
            item.addEventListener('drop', e => {
                e.preventDefault();
                if (dragged && dragged !== item) grid.insertBefore(dragged, item);
            });
        });
    }
    return { init };
})();

/* ─── Inline Edit ───────────────────────────────────────── */
PIM.inlineEdit = (function () {
    function init(cellSelector, onChange) {
        document.querySelectorAll(cellSelector).forEach(cell => {
            let original;
            cell.addEventListener('dblclick', () => {
                if (cell.querySelector('input')) return;
                original = cell.textContent.trim();
                const inp = document.createElement('input');
                inp.className = 'pim-input';
                inp.value = original;
                inp.style.cssText = 'width:100%;padding:4px 8px;font-size:13px;';
                cell.textContent = '';
                cell.appendChild(inp);
                inp.focus(); inp.select();

                function save() {
                    const val = inp.value.trim();
                    cell.textContent = val;
                    if (val !== original && onChange) onChange(cell, val, original);
                }
                inp.addEventListener('blur', save);
                inp.addEventListener('keydown', e => {
                    if (e.key === 'Enter') { e.preventDefault(); save(); }
                    if (e.key === 'Escape') { cell.textContent = original; }
                });
            });
        });
    }
    return { init };
})();

/* ─── Bulk Selection ────────────────────────────────────── */
PIM.bulkSelect = (function () {
    function init({ rowSelector, checkboxSelector, allSelector, barId, countId, onToggle }) {
        const bar   = document.getElementById(barId);
        const count = document.getElementById(countId);
        const allCb = document.getElementById(allSelector);

        function update() {
            const checked = document.querySelectorAll(checkboxSelector + ':checked');
            const total   = document.querySelectorAll(checkboxSelector);
            if (count) count.textContent = checked.length;
            if (bar)   bar.style.display = checked.length > 0 ? '' : 'none';
            if (allCb) allCb.indeterminate = checked.length > 0 && checked.length < total.length;
            if (allCb) allCb.checked = checked.length === total.length && total.length > 0;
            document.querySelectorAll(rowSelector).forEach(r => {
                const cb = r.querySelector(checkboxSelector);
                r.classList.toggle('selected', cb && cb.checked);
            });
            if (onToggle) onToggle(Array.from(checked).map(c => c.value));
        }

        document.querySelectorAll(checkboxSelector).forEach(cb => cb.addEventListener('change', update));
        if (allCb) allCb.addEventListener('change', () => {
            document.querySelectorAll(checkboxSelector).forEach(cb => cb.checked = allCb.checked);
            update();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.querySelectorAll(checkboxSelector).forEach(cb => cb.checked = false);
                if (allCb) allCb.checked = false;
                update();
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                const active = document.activeElement;
                if (active && active.tagName === 'INPUT' && active.type !== 'checkbox') return;
                e.preventDefault();
                document.querySelectorAll(checkboxSelector).forEach(cb => cb.checked = true);
                update();
            }
        });

        // Shift-click range selection
        let lastChecked = null;
        document.querySelectorAll(checkboxSelector).forEach(cb => {
            cb.addEventListener('click', e => {
                if (e.shiftKey && lastChecked) {
                    const all = Array.from(document.querySelectorAll(checkboxSelector));
                    const a = all.indexOf(lastChecked), b = all.indexOf(cb);
                    const [s, end] = a < b ? [a, b] : [b, a];
                    all.slice(s, end + 1).forEach(c => c.checked = true);
                    update();
                }
                lastChecked = cb;
            });
        });

        return { getSelected: () => Array.from(document.querySelectorAll(checkboxSelector + ':checked')).map(c => c.value) };
    }
    return { init };
})();

/* ─── SEO Score Calculator ──────────────────────────────── */
PIM.seoScore = (function () {
    function calculate(data) {
        let score = 0;
        if (data.title && data.title.length >= 40 && data.title.length <= 60) score += 20;
        else if (data.title && data.title.length > 0) score += 10;
        if (data.description && data.description.length >= 120 && data.description.length <= 160) score += 20;
        else if (data.description && data.description.length > 0) score += 10;
        if (data.slug) score += 10;
        if (data.keywords && data.keywords.split(',').length >= 3) score += 10;
        if (data.og_title) score += 10;
        if (data.og_description) score += 10;
        if (data.canonical) score += 10;
        if (data.schema_json) score += 10;
        return Math.min(score, 100);
    }

    function render(score, ringId) {
        const ring = document.getElementById(ringId);
        if (!ring) return;
        const deg   = Math.round(score / 100 * 360);
        const color = score >= 70 ? 'var(--pim-success)' : score >= 40 ? 'var(--pim-warning)' : 'var(--pim-danger)';
        ring.style.cssText = `--score-d:${deg}deg;--score-c:${color};`;
        const inner = ring.querySelector('.pim-seo-score-num');
        if (inner) inner.textContent = score;
    }
    return { calculate, render };
})();

/* ─── Sparkline Renderer ────────────────────────────────── */
PIM.sparkline = (function () {
    function render(containerId, data, color = 'var(--pim-gold)') {
        const c = document.getElementById(containerId);
        if (!c) return;
        const max = Math.max(...data, 1);
        c.innerHTML = data.map(v => {
            const h = Math.max(Math.round((v / max) * 28), 2);
            return `<div class="pim-spark-bar" style="height:${h}px;background:${color}" title="${v}"></div>`;
        }).join('');
    }
    return { render };
})();

/* ─── Column Toggle ─────────────────────────────────────── */
PIM.columnToggle = (function () {
    function init(toggleSelector) {
        document.querySelectorAll(toggleSelector).forEach(el => {
            el.addEventListener('change', function () {
                const col = this.getAttribute('data-col');
                document.querySelectorAll('.' + col).forEach(c => {
                    c.style.display = this.checked ? '' : 'none';
                });
                // persist
                try {
                    const saved = JSON.parse(localStorage.getItem('pim_cols') || '{}');
                    saved[col] = this.checked;
                    localStorage.setItem('pim_cols', JSON.stringify(saved));
                } catch(e) {}
            });
            // restore
            try {
                const saved = JSON.parse(localStorage.getItem('pim_cols') || '{}');
                const col = el.getAttribute('data-col');
                if (saved[col] === false) { el.checked = false; el.dispatchEvent(new Event('change')); }
            } catch(e) {}
        });
    }
    return { init };
})();

/* ─── Lazy Load Images ──────────────────────────────────── */
PIM.lazyLoad = (function () {
    function init() {
        if ('IntersectionObserver' in window) {
            const obs = new IntersectionObserver(entries => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        const img = e.target;
                        if (img.dataset.src) { img.src = img.dataset.src; delete img.dataset.src; }
                        obs.unobserve(img);
                    }
                });
            });
            document.querySelectorAll('img[data-src]').forEach(img => obs.observe(img));
        } else {
            document.querySelectorAll('img[data-src]').forEach(img => { img.src = img.dataset.src; });
        }
    }
    return { init };
})();

/* ─── Price Calculator ──────────────────────────────────── */
PIM.pricing = (function () {
    function calcMargin(price, cost) {
        if (!price || price <= 0) return 0;
        return Math.round(((price - cost) / price) * 100 * 10) / 10;
    }
    function calcMarkup(price, cost) {
        if (!cost || cost <= 0) return 0;
        return Math.round(((price - cost) / cost) * 100 * 10) / 10;
    }
    function bindLive(priceId, costId, marginDisplayId, markupDisplayId) {
        const priceEl  = document.getElementById(priceId);
        const costEl   = document.getElementById(costId);
        const marginEl = document.getElementById(marginDisplayId);
        const markupEl = document.getElementById(markupDisplayId);
        if (!priceEl || !costEl) return;
        function update() {
            const p = parseFloat(priceEl.value) || 0;
            const c = parseFloat(costEl.value) || 0;
            if (marginEl) marginEl.textContent = '%' + calcMargin(p, c);
            if (markupEl) markupEl.textContent = '%' + calcMarkup(p, c);
        }
        priceEl.addEventListener('input', update);
        costEl.addEventListener('input', update);
        update();
    }
    return { calcMargin, calcMarkup, bindLive };
})();

/* ─── Keyboard Navigation for Grid ─────────────────────── */
PIM.gridKeyboard = (function () {
    function init(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        table.setAttribute('tabindex', '0');
        let focusedRow = -1;
        const rows = () => Array.from(table.querySelectorAll('tbody tr'));
        table.addEventListener('keydown', e => {
            const r = rows();
            if (!r.length) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); focusedRow = Math.min(focusedRow + 1, r.length - 1); }
            if (e.key === 'ArrowUp')   { e.preventDefault(); focusedRow = Math.max(focusedRow - 1, 0); }
            if (e.key === ' ') {
                e.preventDefault();
                const cb = r[focusedRow]?.querySelector('.pim-row-cb');
                if (cb) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change', { bubbles: true })); }
            }
            r.forEach((row, i) => row.classList.toggle('keyboard-focus', i === focusedRow));
            if (r[focusedRow]) r[focusedRow].scrollIntoView({ block: 'nearest' });
        });
    }
    return { init };
})();

/* ─── Auto-init on DOM ready ────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    PIM.lazyLoad.init();
    PIM.columnToggle.init('.pim-col-toggle');
});
