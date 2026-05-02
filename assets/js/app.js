/**
 * Chicken Deluxe — Main JavaScript
 */

/* ------------------------------------------------------------------
   SIDEBAR LIVE CLOCK
   Updates #sidebarClock every second with the current local time.
------------------------------------------------------------------ */
(function () {
    const clockEl = document.getElementById('sidebarClock');
    if (!clockEl) return;

    function tick() {
        const now  = new Date();
        let   h    = now.getHours();
        const m    = String(now.getMinutes()).padStart(2, '0');
        const s    = String(now.getSeconds()).padStart(2, '0');
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        clockEl.textContent = `${h}:${m}:${s} ${ampm}`;
    }

    tick();
    setInterval(tick, 1000);
})();

/* ------------------------------------------------------------------
 * showConfirmModal(options)
 *
 * Drop-in replacement for native window.confirm(). Drives the single
 * #confirmOverlay markup defined in views/layouts/modal.php.
 *
 * options:
 *   title       (string)  — modal heading
 *   message     (string)  — modal body text
 *   confirmText (string)  — label on the confirm button
 *   type        (string)  — 'delete' | 'unlock' | 'lock' | 'submit'
 *                           controls the confirm button colour
 *   onConfirm   (func)    — callback fired when the user confirms
 * ----------------------------------------------------------------*/
const CONFIRM_COLORS = {
    delete: '#E53935', // red
    unlock: '#F57C00', // orange
    lock:   '#546E7A', // blue-gray
    submit: '#1565C0'  // blue
};

function showConfirmModal(options) {
    const overlay     = document.getElementById('confirmOverlay');
    const titleEl     = document.getElementById('modalTitle');
    const messageEl   = document.getElementById('modalMessage');
    const confirmBtn  = document.getElementById('modalConfirm');
    const cancelBtn   = document.getElementById('modalCancel');
    const closeBtn    = document.getElementById('modalClose');

    if (!overlay) {
        // Modal partial not on this page — fall back to native confirm
        if (window.confirm(options.message || 'Are you sure?')) {
            if (typeof options.onConfirm === 'function') options.onConfirm();
        }
        return;
    }

    // Populate
    titleEl.textContent    = options.title       || 'Confirm';
    messageEl.textContent  = options.message     || 'Are you sure?';
    confirmBtn.textContent = options.confirmText || 'Confirm';
    confirmBtn.style.backgroundColor = CONFIRM_COLORS[options.type] || CONFIRM_COLORS.submit;

    // Show
    overlay.classList.add('modal-overlay-visible');
    overlay.setAttribute('aria-hidden', 'false');

    // Close helper — also tears down its own listeners
    const closeModal = () => {
        overlay.classList.remove('modal-overlay-visible');
        overlay.setAttribute('aria-hidden', 'true');
        confirmBtn.removeEventListener('click', handleConfirm);
        cancelBtn.removeEventListener('click', closeModal);
        closeBtn.removeEventListener('click', closeModal);
        overlay.removeEventListener('click', handleBackdrop);
        document.removeEventListener('keydown', handleEscape);
    };

    const handleConfirm = () => {
        closeModal();
        if (typeof options.onConfirm === 'function') options.onConfirm();
    };

    const handleBackdrop = (e) => {
        if (e.target === overlay) closeModal();
    };

    const handleEscape = (e) => {
        if (e.key === 'Escape') closeModal();
    };

    confirmBtn.addEventListener('click', handleConfirm);
    cancelBtn.addEventListener('click', closeModal);
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', handleBackdrop);
    document.addEventListener('keydown', handleEscape);
}

// Sidebar toggle for mobile
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 &&
                !sidebar.contains(e.target) &&
                !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
});

/* =====================================================================
 * INVENTORY ENTRY — tab switching, progress tracking, +/- buttons,
 * keyboard navigation. Wires up every .inventory-entry-form on the page.
 * ===================================================================== */
(function () {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.inventory-entry-form').forEach(initInventoryForm);
        document.querySelectorAll('.inventory-saved-view').forEach(initSavedTabs);
    });

    // Read-only saved view: tabs only, no progress / qty logic.
    function initSavedTabs(root) {
        const tabs   = Array.from(root.querySelectorAll('.inventory-tab'));
        const panels = Array.from(root.querySelectorAll('.inventory-tab-panel'));
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t   => t.classList.toggle('active', t === tab));
                panels.forEach(p => p.classList.toggle('active', p.dataset.category === tab.dataset.category));
            });
        });
    }

    function initInventoryForm(form) {
        const total      = parseInt(form.dataset.total || '0', 10);
        const baseLabel  = form.dataset.label || 'Save';
        const tabs       = Array.from(form.querySelectorAll('.inventory-tab'));
        const panels     = Array.from(form.querySelectorAll('.inventory-tab-panel'));
        const allInputs  = Array.from(form.querySelectorAll('.inventory-qty-input'));
        const progressBar   = form.querySelector('.inventory-progress-bar');
        const filledLabels  = form.querySelectorAll('.inventory-filled-count');
        const submitBtn     = form.querySelector('.inventory-submit-btn');

        // -- Tab switching ------------------------------------------------
        tabs.forEach(tab => {
            tab.addEventListener('click', () => activateTab(tab.dataset.category));
        });

        function activateTab(category) {
            tabs.forEach(t   => t.classList.toggle('active', t.dataset.category === category));
            panels.forEach(p => p.classList.toggle('active', p.dataset.category === category));
        }

        // -- +/- quantity buttons -----------------------------------------
        form.querySelectorAll('.inventory-qty-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.parentElement.querySelector('.inventory-qty-input');
                if (!input) return;
                const current = parseInt(input.value, 10) || 0;
                input.value = btn.dataset.action === 'inc'
                    ? current + 1
                    : Math.max(0, current - 1);
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });

        // -- Progress + tab-completion + ending-warning -------------------
        allInputs.forEach(input => {
            input.addEventListener('input',  refreshState);
            input.addEventListener('change', refreshState);
        });

        function refreshState() {
            // Filled count + progress bar + sticky button label
            const filled = allInputs.filter(i => (parseInt(i.value, 10) || 0) > 0).length;
            filledLabels.forEach(el => el.textContent = filled);
            if (progressBar && total > 0) {
                progressBar.style.width = ((filled / total) * 100).toFixed(1) + '%';
            }
            if (submitBtn) {
                // Preserve any nested span; just update the leading text node.
                const span = submitBtn.querySelector('.inventory-filled-count');
                if (span) span.textContent = filled;
                else submitBtn.textContent = `${baseLabel} (${filled} of ${total} filled)`;
            }

            // Per-tab completion check
            panels.forEach(panel => {
                const inputs = panel.querySelectorAll('.inventory-qty-input');
                const filledHere = Array.from(inputs)
                    .filter(i => (parseInt(i.value, 10) || 0) > 0).length;
                const tab = tabs.find(t => t.dataset.category === panel.dataset.category);
                if (!tab) return;
                const allFilled = inputs.length > 0 && filledHere === inputs.length;
                tab.classList.toggle('tab-complete', allFilled);
                // Update the parens meta to "X/Y" while in progress, "(Y)" when done
                const meta = tab.querySelector('.inventory-tab-meta');
                if (meta) meta.textContent = allFilled
                    ? `(${inputs.length})`
                    : `(${filledHere}/${inputs.length})`;
            });

            // Ending-form: highlight rows where ending qty exceeds beginning qty
            form.querySelectorAll('.inventory-product-row[data-beginning]').forEach(row => {
                const beg   = parseInt(row.dataset.beginning, 10) || 0;
                const input = row.querySelector('.inventory-qty-input');
                const val   = parseInt(input.value, 10) || 0;
                row.classList.toggle('inventory-row-warning', val > beg);
            });
        }

        // -- Keyboard nav: Enter advances to next product input -----------
        // (Tab already moves naturally; we only handle Enter so it doesn't
        //  submit the form.)
        allInputs.forEach((input, idx) => {
            input.addEventListener('keydown', e => {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                const next = allInputs[idx + 1];
                if (!next) { input.blur(); return; }
                // If next input lives in a different tab, activate that tab first
                const nextPanel = next.closest('.inventory-tab-panel');
                if (nextPanel && !nextPanel.classList.contains('active')) {
                    activateTab(nextPanel.dataset.category);
                }
                next.focus();
                next.select();
            });
        });

        // Initial paint (in case browser preserved values on reload)
        refreshState();
    }
})();

/* =====================================================================
 * REPORT VIEWS — daily category tabs, consolidated inner tabs,
 * generic table pagination, time-in date groups + collapse toggle.
 * Each block is a no-op if its target elements aren't in the DOM.
 * ===================================================================== */

/* ---------- Generic: table pagination over visible rows ---------- */
function makePager(table, controls, pageSize) {
    const tbody = table.querySelector('tbody');
    const info  = controls.querySelector('.report-pagination-info');
    const prev  = controls.querySelector('[data-page="prev"]');
    const next  = controls.querySelector('[data-page="next"]');
    let page = 1;

    function dataRows() {
        // Skip the totals row (kept always pinned at the visible bottom)
        return Array.from(tbody.querySelectorAll('tr')).filter(r =>
            !r.classList.contains('report-totals-row') &&
            !r.dataset.hiddenByFilter
        );
    }

    function render(filterFn) {
        const rows = dataRows().filter(r => !filterFn || filterFn(r));
        // Hide all data rows first
        dataRows().forEach(r => { r.style.display = 'none'; });

        const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
        if (page > totalPages) page = totalPages;

        const start = (page - 1) * pageSize;
        const end   = start + pageSize;
        rows.slice(start, end).forEach(r => { r.style.display = ''; });

        if (rows.length <= pageSize) {
            controls.style.display = 'none';
        } else {
            controls.style.display = '';
            info.textContent = `Page ${page} of ${totalPages} (${rows.length} records)`;
            prev.disabled = (page === 1);
            next.disabled = (page === totalPages);
        }
        return rows;   // return current visible-set so callers can recompute totals
    }

    prev.addEventListener('click', () => { if (page > 1) { page--; render(currentFilter); afterRender && afterRender(); } });
    next.addEventListener('click', () => { page++; render(currentFilter); afterRender && afterRender(); });

    let currentFilter = null;
    let afterRender   = null;
    return {
        render: (filter, after) => {
            currentFilter = filter || null;
            afterRender   = after || null;
            return render(currentFilter);
        },
        reset: () => { page = 1; },
    };
}

/* ---------- Daily report: category tabs + totals row + pagination ---------- */
(function initDailyReport() {
    document.addEventListener('DOMContentLoaded', () => {
        const tabsBar = document.getElementById('dailyCatTabs');
        const table   = document.getElementById('dailyReportTable');
        const pagBox  = document.getElementById('dailyPagination');
        if (!table) return;

        const tabs       = tabsBar ? Array.from(tabsBar.querySelectorAll('.report-cat-tab')) : [];
        const totalsRow  = document.getElementById('dailyTotalsRow');
        const totalsVal  = document.getElementById('dailyTotalsValue');
        const pager      = pagBox ? makePager(table, pagBox, 10) : null;

        let activeCategory = '__all__';

        const filterFn = row =>
            activeCategory === '__all__' || row.dataset.category === activeCategory;

        function recomputeTotals(rows) {
            // Sum sales of all rows that pass the filter (across pages, not just visible)
            const total = rows.reduce((s, r) => s + parseFloat(r.dataset.sales || 0), 0);
            if (totalsVal) totalsVal.textContent = total.toLocaleString(undefined, {
                minimumFractionDigits: 2, maximumFractionDigits: 2
            });
            // Make sure the totals row sits at the visible bottom of the page
            if (totalsRow) totalsRow.style.display = '';
        }

        function refresh() {
            if (pager) {
                const rows = pager.render(filterFn, () => {});
                recomputeTotals(rows);
            } else {
                // No pagination — just hide non-matching rows
                Array.from(table.querySelectorAll('tbody tr.report-row')).forEach(r => {
                    r.style.display = filterFn(r) ? '' : 'none';
                });
                const rows = Array.from(table.querySelectorAll('tbody tr.report-row'))
                    .filter(filterFn);
                recomputeTotals(rows);
            }
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.toggle('active', t === tab));
                activeCategory = tab.dataset.category;
                if (pager) pager.reset();
                refresh();
            });
        });

        refresh();
    });
})();

/* ---------- Consolidated report: inner tabs + per-panel pagination ---------- */
(function initConsolidatedReport() {
    document.addEventListener('DOMContentLoaded', () => {
        const tabsBar = document.getElementById('consolidatedInnerTabs');
        if (!tabsBar) return;

        const tabs   = Array.from(tabsBar.querySelectorAll('.report-inner-tab'));
        const panels = Array.from(document.querySelectorAll('.report-inner-panel'));

        // Build pagers for any paginated table inside the panels
        const pagers = new Map();   // panel -> pager
        panels.forEach(panel => {
            const table    = panel.querySelector('table.report-paginated');
            const controls = panel.querySelector('.report-pagination');
            if (table && controls) {
                const pageSize = parseInt(table.dataset.pageSize || '10', 10);
                const pager    = makePager(table, controls, pageSize);
                pager.render();
                pagers.set(panel, pager);
            }
        });

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;
                tabs.forEach(t => t.classList.toggle('active', t === tab));
                panels.forEach(p => p.classList.toggle('active', p.dataset.tab === target));
                // Reset pagination of the newly activated panel
                const activePanel = panels.find(p => p.dataset.tab === target);
                if (activePanel && pagers.has(activePanel)) {
                    const pager = pagers.get(activePanel);
                    pager.reset();
                    pager.render();
                }
            });
        });
    });
})();

/* ---------- Time-In report: collapsible date groups + group pagination ---------- */
(function initTimeinReport() {
    document.addEventListener('DOMContentLoaded', () => {
        const wrap   = document.getElementById('timeinGroups');
        const pagBox = document.getElementById('timeinPagination');
        const toggle = document.getElementById('timeinToggleAll');
        if (!wrap) return;

        const groups = Array.from(wrap.querySelectorAll('.timein-date-group'));

        // Click any header to collapse/expand its group
        groups.forEach(group => {
            const header = group.querySelector('[data-toggle="timein-group"]');
            if (!header) return;
            header.addEventListener('click', () => {
                group.classList.toggle('collapsed');
            });
        });

        // Collapse / Expand All
        if (toggle) {
            toggle.addEventListener('click', () => {
                const collapse = toggle.dataset.state === 'expanded';
                groups.forEach(g => g.classList.toggle('collapsed', collapse));
                toggle.dataset.state = collapse ? 'collapsed' : 'expanded';
                toggle.textContent   = collapse ? 'Expand All' : 'Collapse All';
            });
        }

        // Pagination over date groups (7 per page)
        const PAGE_SIZE = 7;
        if (groups.length <= PAGE_SIZE || !pagBox) return;

        const info = pagBox.querySelector('.report-pagination-info');
        const prev = pagBox.querySelector('[data-page="prev"]');
        const next = pagBox.querySelector('[data-page="next"]');
        let page = 1;
        const totalPages = Math.ceil(groups.length / PAGE_SIZE);

        function render() {
            const start = (page - 1) * PAGE_SIZE;
            const end   = start + PAGE_SIZE;
            groups.forEach((g, i) => { g.style.display = (i >= start && i < end) ? '' : 'none'; });
            const fromDay = start + 1;
            const toDay   = Math.min(end, groups.length);
            info.textContent = `Showing days ${fromDay}\u2013${toDay} of ${groups.length} days`;
            prev.disabled = (page === 1);
            next.disabled = (page === totalPages);
        }
        prev.addEventListener('click', () => { if (page > 1) { page--; render(); } });
        next.addEventListener('click', () => { if (page < totalPages) { page++; render(); } });
        pagBox.style.display = '';
        render();
    });
})();
