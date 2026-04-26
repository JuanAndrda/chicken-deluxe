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
                       value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
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
                            <input type="hidden" name="product_id" id="entryProductId">

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

                <!-- Product grid -->
                <div class="delivery-grid-section">
                    <h3 class="delivery-grid-title">Select Product</h3>

                    <div class="pos-category-tabs" style="margin-bottom:16px;">
                        <button class="pos-cat-btn active" data-category="all">All</button>
                        <?php foreach ($products as $category => $items): ?>
                            <button class="pos-cat-btn" data-category="<?= htmlspecialchars($category) ?>">
                                <?= htmlspecialchars($category) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="pos-product-grid delivery-product-grid" id="deliveryProductGrid">
                        <?php foreach ($products as $category => $items): ?>
                            <?php foreach ($items as $product): ?>
                                <?php $imgUrl = ProductModel::getProductImagePath($product['Name']); ?>
                                <button type="button"
                                        class="pos-product-card delivery-product-card"
                                        data-id="<?= $product['Product_ID'] ?>"
                                        data-name="<?= htmlspecialchars($product['Name']) ?>"
                                        data-price="<?= $product['Price'] ?>"
                                        data-unit="<?= htmlspecialchars($product['Unit']) ?>"
                                        data-img="<?= htmlspecialchars($imgUrl) ?>"
                                        data-category="<?= htmlspecialchars($category) ?>">
                                    <img class="pos-product-img"
                                         src="<?= htmlspecialchars($imgUrl) ?>"
                                         alt="<?= htmlspecialchars($product['Name']) ?>"
                                         loading="lazy">
                                    <span class="pos-product-name">
                                        <?= htmlspecialchars($product['Name']) ?>
                                    </span>
                                    <span class="pos-product-price">
                                        <?= htmlspecialchars($product['Unit']) ?>
                                    </span>
                                </button>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
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
                    <?php if ($is_today && !$any_locked && !empty($deliveries) && Auth::isOwner()): ?>
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
                                <th>Product</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Unit</th>
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
                                    <td><?= htmlspecialchars($d['Product_Name']) ?></td>
                                    <td>
                                        <span class="badge badge-category">
                                            <?= htmlspecialchars($d['Category_Name'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td><strong><?= $d['Quantity'] ?></strong></td>
                                    <td><?= htmlspecialchars($d['Unit']) ?></td>
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
                                        <td colspan="<?= Auth::isOwner() ? 8 : 7 ?>">
                                            <form method="POST" action="<?= BASE_URL ?>/delivery/update"
                                                  class="inline-edit-form">
                                                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                <input type="hidden" name="delivery_id" value="<?= $d['Delivery_ID'] ?>">
                                                <input type="hidden" name="date" value="<?= $date ?>">
                                                <div class="inline-edit-fields">
                                                    <div class="form-group">
                                                        <label>Product</label>
                                                        <input type="text" class="form-input input-sm"
                                                               value="<?= htmlspecialchars($d['Product_Name']) ?>" disabled>
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

    // ============ PRODUCT GRID ============
    const grid       = document.getElementById('deliveryProductGrid');
    const entryPanel = document.getElementById('deliveryEntryPanel');
    let selectedCard = null;

    if (grid) {
        // Category filter
        document.querySelectorAll('.pos-cat-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.pos-cat-btn')
                        .forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const cat = this.dataset.category;
                grid.querySelectorAll('.delivery-product-card').forEach(function(card) {
                    card.style.display =
                        (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
                });
            });
        });

        // Card click — populate entry panel above grid
        grid.addEventListener('click', function(e) {
            const card = e.target.closest('.delivery-product-card');
            if (!card) return;

            if (selectedCard) selectedCard.classList.remove('selected');
            selectedCard = card;
            card.classList.add('selected');

            const id   = card.dataset.id;
            const name = card.dataset.name;
            const unit = card.dataset.unit;
            const img  = card.dataset.img;

            document.getElementById('entryProductId').value     = id;
            document.getElementById('entryProductName').textContent = name;
            document.getElementById('entryProductMeta').textContent = 'Unit: ' + unit;
            document.getElementById('entryQtyInput').value      = 1;

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
            if (selectedCard) {
                selectedCard.classList.remove('selected');
                selectedCard = null;
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
})();
</script>
