<?php
    // Staff always sees records as locked — they cannot edit or delete
    $staff_locked = Auth::isStaff();
?>
<section class="sales">
    <div class="section-header">
        <h2>Sales — <?= htmlspecialchars($kiosk['Name'] ?? 'Unknown') ?></h2>
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
        <form method="GET" action="<?= BASE_URL ?>/sales" class="form-inline-row">
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

    <div class="pos-layout">
        <!-- LEFT: POS Product Grid -->
        <?php if ($is_today && !$any_locked): ?>
            <div class="pos-panel">
                <div class="card">
                    <h3>Select Product</h3>

                    <!-- Category filter tabs -->
                    <div class="pos-category-tabs">
                        <button class="pos-cat-btn active" data-category="all">All</button>
                        <?php foreach ($products as $category => $items): ?>
                            <button class="pos-cat-btn" data-category="<?= htmlspecialchars($category) ?>">
                                <?= htmlspecialchars($category) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Product grid -->
                    <div class="pos-product-grid" id="posProductGrid">
                        <?php foreach ($products as $category => $items): ?>
                            <?php foreach ($items as $product): ?>
                                <?php $imgUrl = ProductModel::getProductImagePath($product['Name']); ?>
                                <button type="button" class="pos-product-card"
                                        data-id="<?= $product['Product_ID'] ?>"
                                        data-name="<?= htmlspecialchars($product['Name']) ?>"
                                        data-price="<?= $product['Price'] ?>"
                                        data-unit="<?= htmlspecialchars($product['Unit']) ?>"
                                        data-image="<?= htmlspecialchars($imgUrl) ?>"
                                        data-category="<?= htmlspecialchars($category) ?>">
                                    <img class="pos-product-img"
                                         src="<?= htmlspecialchars($imgUrl) ?>"
                                         alt="<?= htmlspecialchars($product['Name']) ?>"
                                         loading="lazy">
                                    <span class="pos-product-name"><?= htmlspecialchars($product['Name']) ?></span>
                                    <span class="pos-product-price">P<?= number_format($product['Price'], 2) ?></span>
                                    <span class="pos-product-unit"><?= htmlspecialchars($product['Unit']) ?></span>
                                </button>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Selected product form -->
                <div class="card pos-entry-card" id="posEntryCard" style="display:none;">
                    <h3>Record Sale</h3>
                    <form method="POST" action="<?= BASE_URL ?>/sales/store" id="posForm">
                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                        <input type="hidden" name="kiosk_id" value="<?= $kiosk_id ?>">
                        <input type="hidden" name="date" value="<?= $date ?>">
                        <input type="hidden" name="product_id" id="posProductId" value="">

                        <div class="pos-selected-info">
                            <img id="posSelectedImg" class="pos-selected-img"
                                 src="" alt="" style="display:none;">
                            <div class="pos-selected-text">
                                <div class="pos-selected-name" id="posSelectedName">—</div>
                                <div class="pos-selected-detail">
                                    <span>Unit Price: <strong id="posSelectedPrice">P0.00</strong></span>
                                    <span>Unit: <strong id="posSelectedUnit">—</strong></span>
                                </div>
                            </div>
                        </div>

                        <div class="pos-qty-row">
                            <label for="quantity_sold">Quantity</label>
                            <div class="pos-qty-controls">
                                <button type="button" class="btn btn-outline pos-qty-btn" id="posQtyMinus">-</button>
                                <input type="number" id="quantity_sold" name="quantity_sold"
                                       class="form-input pos-qty-input" min="1" value="1" required>
                                <button type="button" class="btn btn-outline pos-qty-btn" id="posQtyPlus">+</button>
                            </div>
                        </div>

                        <div class="pos-line-total">
                            Line Total: <strong id="posLineTotal">P0.00</strong>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full">Add to Sales</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- RIGHT: Sales Record Table -->
        <div class="pos-records">
            <div class="card">
                <div class="section-header">
                    <h3>Sales Records</h3>
                    <div class="section-header-right">
                        <span class="total-display">Day Total: <strong>P<?= number_format($day_total, 2) ?></strong></span>
                        <?php if (!empty($sales) && !$any_locked && $is_today && Auth::isOwner()): ?>
                            <form method="POST" action="<?= BASE_URL ?>/sales/lock" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                <input type="hidden" name="kiosk_id" value="<?= $kiosk_id ?>">
                                <input type="hidden" name="date" value="<?= $date ?>">
                                <button type="button" class="btn btn-sm btn-secondary"
                                        onclick="showConfirmModal({
                                            title: 'Lock All Sales',
                                            message: 'Are you sure you want to lock all sales records for today? Locked records cannot be edited by staff.',
                                            confirmText: 'Lock All',
                                            type: 'lock',
                                            onConfirm: () => this.closest('form').submit()
                                        })">
                                    Lock All
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Line Total</th>
                                <th>Status</th>
                                <?php if (Auth::isOwner()): ?><th>Action</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sales)): ?>
                                <tr><td colspan="<?= Auth::isOwner() ? 7 : 6 ?>" class="text-center">No sales recorded for this date.</td></tr>
                            <?php else: ?>
                                <?php foreach ($sales as $s): ?>
                                    <?php
                                        // Staff always sees locked; owner sees actual status
                                        $show_locked = $staff_locked || $s['Locked_status'];
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['Product_Name']) ?></td>
                                        <td><span class="badge badge-category"><?= htmlspecialchars($s['Category_Name']) ?></span></td>
                                        <td><strong><?= $s['Quantity_sold'] ?></strong></td>
                                        <td>P<?= number_format($s['Unit_Price'], 2) ?></td>
                                        <td><strong>P<?= number_format($s['Line_total'], 2) ?></strong></td>
                                        <td>
                                            <span class="badge <?= $show_locked ? 'badge-locked' : 'badge-active' ?>">
                                                <?= $show_locked ? 'Locked' : 'Open' ?>
                                            </span>
                                        </td>
                                        <?php if (Auth::isOwner()): ?>
                                            <td>
                                                <?php if (!$s['Locked_status']): ?>
                                                    <form method="POST" action="<?= BASE_URL ?>/sales/delete" class="inline-form">
                                                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                        <input type="hidden" name="sales_id" value="<?= $s['Sales_ID'] ?>">
                                                        <input type="hidden" name="date" value="<?= $date ?>">
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                                onclick="showConfirmModal({
                                                                    title: 'Delete Sale',
                                                                    message: 'Are you sure you want to delete this sale record? This action cannot be undone.',
                                                                    confirmText: 'Yes, Delete',
                                                                    type: 'delete',
                                                                    onConfirm: () => this.closest('form').submit()
                                                                })">Delete</button>
                                                    </form>
                                                <?php elseif ($s['Locked_status']): ?>
                                                    <form method="POST" action="<?= BASE_URL ?>/sales/unlock" class="inline-form">
                                                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                        <input type="hidden" name="sales_id" value="<?= $s['Sales_ID'] ?>">
                                                        <input type="hidden" name="date" value="<?= $date ?>">
                                                        <button type="button" class="btn btn-sm btn-outline"
                                                                onclick="showConfirmModal({
                                                                    title: 'Unlock Record',
                                                                    message: 'Are you sure you want to unlock this sales record? This will allow editing of past data.',
                                                                    confirmText: 'Yes, Unlock',
                                                                    type: 'unlock',
                                                                    onConfirm: () => this.closest('form').submit()
                                                                })">Unlock</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    const productMap = <?= json_encode($product_map ?? []) ?>;
    const grid = document.getElementById('posProductGrid');
    const entryCard = document.getElementById('posEntryCard');
    if (!grid || !entryCard) return;

    const prodIdInput = document.getElementById('posProductId');
    const nameEl = document.getElementById('posSelectedName');
    const priceEl = document.getElementById('posSelectedPrice');
    const unitEl = document.getElementById('posSelectedUnit');
    const imgEl = document.getElementById('posSelectedImg');
    const qtyInput = document.getElementById('quantity_sold');
    const lineTotalEl = document.getElementById('posLineTotal');
    let selectedPrice = 0;

    // Product card click
    grid.addEventListener('click', function(e) {
        const card = e.target.closest('.pos-product-card');
        if (!card) return;

        // Highlight selected
        grid.querySelectorAll('.pos-product-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');

        const id = card.dataset.id;
        const p = productMap[id];
        if (!p) return;

        prodIdInput.value = id;
        nameEl.textContent = p.Name;
        priceEl.textContent = 'P' + parseFloat(p.Price).toFixed(2);
        unitEl.textContent = p.Unit;
        if (imgEl && p.Image) {
            imgEl.src = p.Image;
            imgEl.alt = p.Name;
            imgEl.style.display = '';
        }
        selectedPrice = parseFloat(p.Price);
        qtyInput.value = 1;
        updateLineTotal();
        entryCard.style.display = 'block';
        qtyInput.focus();
    });

    // Category filter
    document.querySelectorAll('.pos-cat-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.pos-cat-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const cat = this.dataset.category;
            grid.querySelectorAll('.pos-product-card').forEach(function(card) {
                card.style.display = (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
            });
        });
    });

    // Quantity controls
    document.getElementById('posQtyMinus').addEventListener('click', function() {
        const v = parseInt(qtyInput.value) || 1;
        qtyInput.value = Math.max(1, v - 1);
        updateLineTotal();
    });
    document.getElementById('posQtyPlus').addEventListener('click', function() {
        qtyInput.value = (parseInt(qtyInput.value) || 0) + 1;
        updateLineTotal();
    });
    qtyInput.addEventListener('input', updateLineTotal);

    function updateLineTotal() {
        const qty = parseInt(qtyInput.value) || 0;
        lineTotalEl.textContent = 'P' + (qty * selectedPrice).toFixed(2);
    }
})();
</script>
