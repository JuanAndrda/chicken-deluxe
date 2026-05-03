<?php
    // Staff always sees records as locked — they cannot edit or delete
    $staff_locked = Auth::isStaff();
    // Auto-switch to Records tab right after a successful add
    $show_records_tab = isset($_GET['success']) && !empty($deliveries);
?>
<section class="delivery">
    <div class="section-header">
        <h2>Deliveries — <?= htmlspecialchars($kiosk['Name'] ?? 'Unknown') ?></h2>
        <span class="date-display"><?= date('l, F j, Y', strtotime($date)) ?></span>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($date > date('Y-m-d')): ?>
        <div class="alert alert-warning">
            ⏳ <strong><?= date('M j, Y', strtotime($date)) ?></strong> hasn't happened yet — you can't record deliveries for future dates. Pick today or an earlier day.
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card form-card">
        <form method="GET" action="<?= BASE_URL ?>/delivery" class="form-inline-row">
            <?php if (Auth::isOwner() && !empty($kiosks)): ?>
                <div class="form-group">
                    <label for="kiosk_id">Kiosk</label>
                    <select id="kiosk_id" name="kiosk_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($kiosks as $k): ?>
                            <option value="<?= $k['Kiosk_ID'] ?>" <?= $k['Kiosk_ID'] == $kiosk_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" class="form-input"
                       value="<?= htmlspecialchars($date) ?>"
                       max="<?= date('Y-m-d') ?>"
                       onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <!-- History Quick Links -->
    <?php if (!empty($history)): ?>
        <div class="card">
            <h3>Recent Deliveries</h3>
            <div class="history-links">
                <?php foreach (array_slice($history, 0, 7) as $record): ?>
                    <a href="<?= BASE_URL ?>/delivery?kiosk_id=<?= $kiosk_id ?>&date=<?= $record['Delivery_Date'] ?>"
                       class="btn btn-sm <?= $record['Delivery_Date'] === $date ? 'btn-primary' : 'btn-outline' ?>">
                        <?= date('M j', strtotime($record['Delivery_Date'])) ?>
                        (<?= $record['item_count'] ?> item<?= $record['item_count'] == 1 ? '' : 's' ?>)
                        <?= $record['is_locked'] ? '&#128274;' : '' ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================== TAB CONTAINER ================== -->
    <div class="delivery-tab-container card">

        <!-- Tab Nav -->
        <div class="delivery-tab-nav">
            <button class="delivery-tab-btn <?= $show_records_tab ? '' : 'active' ?>"
                    id="tabAddBtn"
                    data-tab="add"
                    <?php if (!$is_today || $any_locked): ?>style="display:none;"<?php endif; ?>>
                <span>➕ Add Delivery</span>
            </button>
            <button class="delivery-tab-btn <?= $show_records_tab ? 'active' : '' ?>"
                    id="tabRecordsBtn"
                    data-tab="records">
                Delivery Records
                <?php if (!empty($deliveries)): ?>
                    <span class="delivery-tab-badge"><?= count($deliveries) ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- ===== TAB 1: ADD DELIVERY ===== -->
        <div class="delivery-tab-panel <?= $show_records_tab ? '' : 'active' ?>" id="tabAdd">

            <?php if ($is_today && !$any_locked): ?>

                <!-- Selected product entry panel (above grid) -->
                <div id="deliveryEntryPanel" class="delivery-entry-panel" style="display:none;">
                    <div class="delivery-entry-inner">

                        <div class="delivery-entry-product">
                            <img id="entryProductImg" class="delivery-entry-img"
                                 src="" alt="" loading="lazy">
                            <div>
                                <div class="delivery-entry-name" id="entryProductName">—</div>
                                <div class="delivery-entry-meta" id="entryProductMeta">—</div>
                            </div>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>/delivery/store"
                              class="delivery-entry-form">
                            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                            <input type="hidden" name="kiosk_id"   value="<?= $kiosk_id ?>">
                            <input type="hidden" name="date"       value="<?= $date ?>">
                            <input type="hidden" name="part_id"    id="entryProductId">

                            <div class="delivery-qty-row">
                                <label class="delivery-qty-label">Quantity Delivered</label>
                                <div class="delivery-qty-controls">
                                    <button type="button" class="btn btn-outline delivery-qty-btn"
                                            id="entryQtyMinus">−</button>
                                    <input type="number" name="quantity" id="entryQtyInput"
                                           class="form-input delivery-qty-input"
                                           value="1" min="1" required>
                                    <button type="button" class="btn btn-outline delivery-qty-btn"
                                            id="entryQtyPlus">+</button>
                                </div>
                            </div>

                            <div class="delivery-entry-actions">
                                <button type="submit" class="btn btn-primary delivery-save-btn">
                                    ✓ Record Delivery
                                </button>
                                <button type="button" class="btn btn-outline" id="entryCancelBtn">
                                    Cancel
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

                <!-- Parts table -->
                <div class="delivery-grid-section">
                    <h3 class="delivery-grid-title">Select Part</h3>
                    <div class="delivery-product-table-wrap">
                        <table class="delivery-select-table">
                            <thead>
                                <tr>
                                    <th>Part</th>
                                    <th>Type</th>
                                    <th>Unit</th>
                                    <th>Stock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="deliveryProductGrid">
                                <?php foreach ($parts as $part): ?>
                                    <?php $stock = $part_stock[$part['Part_ID']] ?? null; ?>
                                    <tr class="delivery-product-row"
                                        data-id="<?= $part['Part_ID'] ?>"
                                        data-name="<?= htmlspecialchars($part['Name']) ?>"
                                        data-unit="<?= htmlspecialchars($part['Unit']) ?>"
                                        data-img=""
                                        data-category="parts">
                                        <td><?= htmlspecialchars($part['Name']) ?></td>
                                        <td>
                                            <span class="delivery-parts-badge">Part</span>
                                        </td>
                                        <td><?= htmlspecialchars($part['Unit']) ?></td>
                                        <td>
                                            <?php if ($stock !== null): ?>
                                                <strong><?= $stock ?></strong> pcs
                                            <?php else: ?>
                                                <span style="color:var(--color-muted,#888);">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary delivery-select-btn">
                                                Select
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($any_locked): ?>
                <div class="delivery-locked-notice">
                    🔒 Deliveries for this date are locked.
                </div>
            <?php else: ?>
                <div class="delivery-locked-notice">
                    📅 Viewing past records — select today to add deliveries.
                </div>
            <?php endif; ?>

        </div><!-- /tabAdd -->

        <!-- ===== TAB 2: DELIVERY RECORDS ===== -->
        <div class="delivery-tab-panel <?= $show_records_tab ? 'active' : '' ?>" id="tabRecords">

            <div class="section-header" style="margin-bottom:16px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <h3 style="margin:0;"><?= date('M j, Y', strtotime($date)) ?></h3>
                    <?php if (!empty($deliveries)): ?>
                        <span class="total-display">
                            Total Units:
                            <strong><?= array_sum(array_column($deliveries, 'Quantity')) ?></strong>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="section-header-right">
                    <?php if ($is_today && !$any_locked): ?>
                        <button type="button" class="btn btn-outline" id="pulloutToggleBtn"
                                onclick="togglePulloutPanel()">
                            📤 Record Pullout
                        </button>
                    <?php endif; ?>
                    <?php if ($is_today && !empty($deliveries) && Auth::isOwner() && !empty($any_unlocked)): ?>
                        <form method="POST" action="<?= BASE_URL ?>/delivery/lock"
                              onsubmit="return confirm('Lock all delivery records for today?')">
                            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                            <input type="hidden" name="kiosk_id"   value="<?= $kiosk_id ?>">
                            <input type="hidden" name="date"       value="<?= $date ?>">
                            <button type="submit" class="btn btn-primary">🔒 Lock All</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pullout Panel (hidden by default) -->
            <?php if ($is_today && !$any_locked): ?>
            <div id="pulloutPanel" class="pullout-panel" style="display:none;">
                <h4 class="pullout-panel-title">📤 Record Pullout</h4>
                <form method="POST" action="<?= BASE_URL ?>/delivery/pullout" class="pullout-form">
                    <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                    <input type="hidden" name="kiosk_id"   value="<?= $kiosk_id ?>">
                    <input type="hidden" name="date"       value="<?= $date ?>">
                    <div class="pullout-fields">
                        <div class="form-group" style="flex:1 1 200px;">
                            <label for="pulloutPart">Part</label>
                            <select id="pulloutPart" name="part_id" class="form-select" required>
                                <option value="">— Select part —</option>
                                <?php foreach ($parts as $p): ?>
                                    <?php $stk = $part_stock[$p['Part_ID']] ?? 0; ?>
                                    <option value="<?= $p['Part_ID'] ?>"
                                            data-stock="<?= $stk ?>"
                                            <?= $stk <= 0 ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($p['Name']) ?>
                                        (<?= htmlspecialchars($p['Unit']) ?>) — <?= $stk ?> in stock
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex:0 0 140px;">
                            <label for="pulloutQty">Quantity <span id="pulloutQtyHint" class="form-hint"></span></label>
                            <input type="number" id="pulloutQty" name="quantity"
                                   class="form-input" min="1" value="1" required>
                        </div>
                        <div class="form-group" style="flex:2 1 260px;">
                            <label for="pulloutNotes">Reason / Notes</label>
                            <input type="text" id="pulloutNotes" name="notes"
                                   class="form-input" placeholder="e.g. Expired, returned to supplier">
                        </div>
                        <div class="form-group pullout-submit-btn" style="align-self:flex-end;">
                            <button type="submit" class="btn btn-primary">Record Pullout</button>
                            <button type="button" class="btn btn-outline"
                                    onclick="togglePulloutPanel()">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if (empty($deliveries)): ?>
                <div class="empty-state-card">
                    <div class="empty-state-icon">📦</div>
                    <div class="empty-state-text">No deliveries recorded</div>
                    <div class="empty-state-sub">
                        <?php if ($is_today && !$any_locked): ?>
                            Switch to "Add Delivery" tab to record one.
                        <?php else: ?>
                            No deliveries found for <?= date('M j, Y', strtotime($date)) ?>.
                        <?php endif; ?>
                    </div>
                    <?php if ($is_today && !$any_locked): ?>
                        <button type="button" class="btn btn-primary"
                                style="margin-top:16px;"
                                onclick="switchTab('add')">
                            ➕ Add Delivery
                        </button>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Source</th>
                                <th>Category</th>
                                <th>Op</th>
                                <th>Quantity</th>
                                <th>Notes</th>
                                <th>Recorded By</th>
                                <th>Time</th>
                                <th>Status</th>
                                <?php if (Auth::isOwner()): ?><th>Actions</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveries as $d): ?>
                                <?php $show_locked = $staff_locked || $d['Locked_status']; ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['Display_Name'] ?? '—') ?></td>
                                    <td>
                                        <span class="<?= ($d['Source'] ?? 'Product') === 'Part' ? 'delivery-parts-badge' : 'badge badge-category' ?>">
                                            <?= htmlspecialchars($d['Source'] === 'Part' ? 'Part Delivery' : 'Product Delivery') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-category">
                                            <?= htmlspecialchars($d['Display_Cat'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= ($d['Type'] ?? 'Delivery') === 'Pullout' ? 'badge-pullout' : 'badge-delivery' ?>">
                                            <?= htmlspecialchars($d['Type'] ?? 'Delivery') ?>
                                        </span>
                                    </td>
                                    <td><strong><?= $d['Quantity'] ?></strong></td>
                                    <td><?= htmlspecialchars($d['Notes'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($d['Recorded_by'] ?? '—') ?></td>
                                    <td style="white-space:nowrap;">
                                        <?= date('g:i A', strtotime($d['Created_at'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $show_locked ? 'badge-locked' : 'badge-active' ?>">
                                            <?= $show_locked ? '🔒 Locked' : 'Open' ?>
                                        </span>
                                    </td>
                                    <?php if (Auth::isOwner()): ?>
                                        <td>
                                            <div class="table-actions">
                                                <?php if (!$d['Locked_status']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline"
                                                            onclick="toggleDeliveryEdit(<?= $d['Delivery_ID'] ?>)">
                                                        Edit
                                                    </button>
                                                    <form method="POST" action="<?= BASE_URL ?>/delivery/delete"
                                                          class="inline-form">
                                                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                        <input type="hidden" name="delivery_id" value="<?= $d['Delivery_ID'] ?>">
                                                        <input type="hidden" name="date" value="<?= $date ?>">
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                                onclick="showConfirmModal({
                                                                    title: 'Delete Delivery',
                                                                    message: 'Are you sure you want to delete this delivery record? This action cannot be undone.',
                                                                    confirmText: 'Yes, Delete',
                                                                    type: 'delete',
                                                                    onConfirm: () => this.closest('form').submit()
                                                                })">Delete</button>
                                                    </form>
                                                    <form method="POST" action="<?= BASE_URL ?>/delivery/lock"
                                                          class="inline-form">
                                                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                        <input type="hidden" name="delivery_id" value="<?= $d['Delivery_ID'] ?>">
                                                        <input type="hidden" name="date" value="<?= $date ?>">
                                                        <button type="button" class="btn btn-sm btn-primary"
                                                                onclick="showConfirmModal({
                                                                    title: 'Lock Record',
                                                                    message: 'Lock this delivery record? It will become read-only until unlocked.',
                                                                    confirmText: 'Yes, Lock',
                                                                    type: 'lock',
                                                                    onConfirm: () => this.closest('form').submit()
                                                                })">🔒 Lock</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="<?= BASE_URL ?>/delivery/unlock"
                                                          class="inline-form">
                                                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                        <input type="hidden" name="delivery_id" value="<?= $d['Delivery_ID'] ?>">
                                                        <input type="hidden" name="date" value="<?= $date ?>">
                                                        <button type="button" class="btn btn-sm btn-outline"
                                                                onclick="showConfirmModal({
                                                                    title: 'Unlock Record',
                                                                    message: 'Unlock this delivery record? This will allow editing of past data.',
                                                                    confirmText: 'Yes, Unlock',
                                                                    type: 'unlock',
                                                                    onConfirm: () => this.closest('form').submit()
                                                                })">🔓 Unlock</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>

                                <?php if (!$d['Locked_status'] && Auth::isOwner()): ?>
                                    <tr id="delivery-edit-<?= $d['Delivery_ID'] ?>"
                                        class="inline-edit-row" style="display:none;">
                                        <td colspan="<?= Auth::isOwner() ? 10 : 9 ?>">
                                            <form method="POST" action="<?= BASE_URL ?>/delivery/update"
                                                  class="inline-edit-form">
                                                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                <input type="hidden" name="delivery_id" value="<?= $d['Delivery_ID'] ?>">
                                                <input type="hidden" name="date" value="<?= $date ?>">
                                                <div class="inline-edit-fields">
                                                    <div class="form-group">
                                                        <label>Item</label>
                                                        <input type="text" class="form-input input-sm"
                                                               value="<?= htmlspecialchars($d['Display_Name'] ?? '—') ?>" disabled>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Quantity</label>
                                                        <input type="number" name="quantity"
                                                               class="form-input input-sm"
                                                               value="<?= $d['Quantity'] ?>" min="1" required>
                                                    </div>
                                                    <div class="inline-edit-actions">
                                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                                        <button type="button" class="btn btn-sm btn-outline"
                                                                onclick="toggleDeliveryEdit(<?= $d['Delivery_ID'] ?>)">Cancel</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div><!-- /tabRecords -->

    </div><!-- /delivery-tab-container -->
</section>

<script>
(function() {
    // ============ TAB SWITCHING ============
    function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

    function switchTab(name) {
        document.querySelectorAll('.delivery-tab-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.tab === name);
        });
        document.querySelectorAll('.delivery-tab-panel').forEach(function(panel) {
            panel.classList.toggle('active', panel.id === 'tab' + cap(name));
        });
    }

    document.querySelectorAll('.delivery-tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            switchTab(this.dataset.tab);
        });
    });

    // ============ PRODUCT TABLE ============
    const grid       = document.getElementById('deliveryProductGrid');
    const entryPanel = document.getElementById('deliveryEntryPanel');
    let selectedRow  = null;

    if (grid) {
        // Row select button click — populate entry panel above table
        grid.addEventListener('click', function(e) {
            const btn = e.target.closest('.delivery-select-btn');
            if (!btn) return;
            const row = btn.closest('.delivery-product-row');
            if (!row) return;

            if (selectedRow) selectedRow.classList.remove('selected-row');
            selectedRow = row;
            row.classList.add('selected-row');

            const id   = row.dataset.id;
            const name = row.dataset.name;
            const unit = row.dataset.unit;
            const img  = row.dataset.img;

            document.getElementById('entryProductId').value        = id;
            document.getElementById('entryProductName').textContent = name;
            document.getElementById('entryProductMeta').textContent = 'Unit: ' + unit;
            document.getElementById('entryQtyInput').value         = 1;

            const imgEl = document.getElementById('entryProductImg');
            if (imgEl) {
                imgEl.src = img;
                imgEl.style.display = img ? '' : 'none';
            }

            entryPanel.style.display = '';
            document.getElementById('entryQtyInput').focus();
        });
    }

    // Cancel button
    const cancelBtn = document.getElementById('entryCancelBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            entryPanel.style.display = 'none';
            if (selectedRow) {
                selectedRow.classList.remove('selected-row');
                selectedRow = null;
            }
            document.getElementById('entryProductId').value = '';
        });
    }

    // +/− qty buttons
    const qtyInput = document.getElementById('entryQtyInput');
    const minusBtn = document.getElementById('entryQtyMinus');
    const plusBtn  = document.getElementById('entryQtyPlus');

    if (minusBtn && qtyInput) {
        minusBtn.addEventListener('click', function() {
            const val = parseInt(qtyInput.value) || 1;
            if (val > 1) qtyInput.value = val - 1;
        });
    }
    if (plusBtn && qtyInput) {
        plusBtn.addEventListener('click', function() {
            const val = parseInt(qtyInput.value) || 1;
            qtyInput.value = val + 1;
        });
    }

    // ============ INLINE EDIT TOGGLE ============
    window.toggleDeliveryEdit = function(id) {
        const row = document.getElementById('delivery-edit-' + id);
        if (!row) return;
        const isHidden = row.style.display === 'none' || row.style.display === '';
        row.style.display = isHidden ? 'table-row' : 'none';
    };

    // Expose for empty-state shortcut button
    window.switchTab = switchTab;

    // ============ PULLOUT PANEL TOGGLE ============
    window.togglePulloutPanel = function() {
        const panel = document.getElementById('pulloutPanel');
        if (!panel) return;
        panel.style.display = panel.style.display === 'none' ? '' : 'none';
    };

    // ============ PULLOUT STOCK GUARD ============
    // Mirror the Qty input's max to the selected part's running stock so the
    // browser blocks over-pulls before the form submits. Server-side check
    // in DeliveryController::pullout() is the real safety net.
    const pulloutPart = document.getElementById('pulloutPart');
    const pulloutQty  = document.getElementById('pulloutQty');
    const pulloutHint = document.getElementById('pulloutQtyHint');

    if (pulloutPart && pulloutQty) {
        function syncPulloutMax() {
            const opt   = pulloutPart.options[pulloutPart.selectedIndex];
            const stock = opt ? parseInt(opt.dataset.stock || '0', 10) : 0;
            if (stock > 0) {
                pulloutQty.max = stock;
                if (parseInt(pulloutQty.value, 10) > stock) pulloutQty.value = stock;
                if (pulloutHint) pulloutHint.textContent = '(max ' + stock + ')';
            } else {
                pulloutQty.removeAttribute('max');
                if (pulloutHint) pulloutHint.textContent = '';
            }
        }
        pulloutPart.addEventListener('change', syncPulloutMax);
        // Snap value back into range as the user types
        pulloutQty.addEventListener('input', function() {
            const max = parseInt(pulloutQty.max || '0', 10);
            const val = parseInt(pulloutQty.value || '0', 10);
            if (max > 0 && val > max) pulloutQty.value = max;
        });
        syncPulloutMax();
    }
})();
</script>
