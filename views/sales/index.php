<?php
    // Staff always sees records as locked — they cannot edit or delete
    $staff_locked = Auth::isStaff();

    // Auto-switch to Records tab right after a successful order submit
    $show_records_tab = isset($_GET['success']) && !empty($sales);

    // Pre-compute day total for the records header / totals row
    $day_total_calc = array_sum(array_column($sales, 'Line_total'));
?>
<section class="sales">
    <div class="section-header">
        <h2>Point of Sales — <?= htmlspecialchars($kiosk['Name'] ?? 'Unknown') ?></h2>
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
            ⏳ <strong><?= date('M j, Y', strtotime($date)) ?></strong> hasn't happened yet — you can't record sales for future dates. Pick today or an earlier day.
        </div>
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
                       value="<?= htmlspecialchars($date) ?>"
                       max="<?= date('Y-m-d') ?>"
                       onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <!-- ================== TAB WRAPPER ================== -->
    <div class="sales-tab-wrapper">

        <!-- Tab Nav -->
        <div class="sales-tab-nav">
            <?php if ($is_today && !$any_locked): ?>
                <button class="sales-tab-btn <?= !$show_records_tab ? 'active' : '' ?>"
                        data-tab="order" id="tabOrderBtn">
                    🧾 New Order
                </button>
            <?php endif; ?>

            <button class="sales-tab-btn <?= $show_records_tab ? 'active' : '' ?>"
                    data-tab="records" id="tabRecordsBtn">
                Sales Records
                <?php if (!empty($sales)): ?>
                    <span class="sales-tab-badge"><?= count($sales) ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- ===== TAB 1: NEW ORDER ===== -->
        <?php if ($is_today && !$any_locked): ?>
        <div class="sales-tab-panel <?= !$show_records_tab ? 'active' : '' ?>" id="tabOrder">

            <div class="sales-pos-layout">

                <!-- LEFT: PRODUCT GRID -->
                <div class="sales-pos-left">
                    <div class="pos-category-tabs">
                        <button class="pos-cat-btn active" data-category="all">All</button>
                        <?php foreach ($products as $category => $items): ?>
                            <button class="pos-cat-btn" data-category="<?= htmlspecialchars($category) ?>">
                                <?= htmlspecialchars($category) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="pos-product-grid sales-product-grid" id="salesProductGrid">
                        <?php foreach ($products as $category => $items): ?>
                            <?php foreach ($items as $product): ?>
                                <?php
                                    $imgUrl  = ProductModel::getProductImagePath($product['Name']);
                                    $pid     = (int) $product['Product_ID'];

                                    // Parts-based availability check
                                    $avail_info = $availability_map[$pid] ?? null;
                                    $avail      = (!$is_today || $avail_info === null)
                                                ? 1            // no inventory recorded yet → don't block
                                                : ($avail_info['available'] ? 1 : 0);
                                    $max_qty    = (!$is_today || $avail_info === null)
                                                ? 9999
                                                : (int) ($avail_info['max'] ?? 0);

                                    // Build short list of missing parts for the tooltip
                                    $missing_parts = [];
                                    if ($avail_info && !$avail_info['available']) {
                                        foreach ($avail_info['parts'] as $p_check) {
                                            if (!$p_check['ok']) {
                                                $missing_parts[] = $p_check['name']
                                                    . ' (need ' . $p_check['needed']
                                                    . ', have ' . $p_check['available'] . ')';
                                            }
                                        }
                                    }
                                    $tooltip = !empty($missing_parts) ? implode('; ', $missing_parts) : '';
                                ?>
                                <button type="button"
                                        class="pos-product-card <?= ($is_today && !$avail) ? 'pos-unavailable' : '' ?>"
                                        data-id="<?= $pid ?>"
                                        data-name="<?= htmlspecialchars($product['Name']) ?>"
                                        data-price="<?= $product['Price'] ?>"
                                        data-unit="<?= htmlspecialchars($product['Unit']) ?>"
                                        data-img="<?= htmlspecialchars($imgUrl) ?>"
                                        data-category="<?= htmlspecialchars($category) ?>"
                                        data-available="<?= $avail ?>"
                                        data-max="<?= $max_qty ?>"
                                        <?= $tooltip ? 'title="Missing parts: ' . htmlspecialchars($tooltip) . '"' : '' ?>>
                                    <img class="pos-product-img"
                                         src="<?= htmlspecialchars($imgUrl) ?>"
                                         alt="<?= htmlspecialchars($product['Name']) ?>"
                                         loading="lazy">
                                    <span class="pos-product-name">
                                        <?= htmlspecialchars($product['Name']) ?>
                                    </span>
                                    <span class="pos-product-price">
                                        P<?= number_format($product['Price'], 2) ?>
                                    </span>
                                    <?php if ($is_today && $avail_info !== null): ?>
                                        <?php if (!$avail): ?>
                                            <span class="pos-stock-badge pos-stock-none"
                                                  title="<?= htmlspecialchars($tooltip) ?>">
                                                Out of Stock
                                            </span>
                                        <?php else: ?>
                                            <span class="pos-stock-badge pos-stock-ok">
                                                Available: <?= $max_qty ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- RIGHT: ORDER CART -->
                <div class="sales-pos-right">
                    <div class="sales-cart-card">

                        <div class="sales-cart-header">
                            <div class="sales-cart-title">🛒 Current Order</div>
                            <div class="sales-cart-count" id="cartItemCount">0 items</div>
                        </div>

                        <input type="hidden" id="posCsrfToken" value="<?= Auth::generateCsrf() ?>">
                        <input type="hidden" id="posSalesDate" value="<?= htmlspecialchars($date) ?>">
                        <input type="hidden" id="posKioskId"   value="<?= (int) $kiosk_id ?>">

                        <div id="cartEmpty" class="sales-cart-empty">
                            <div class="sales-cart-empty-icon">🛒</div>
                            <div class="sales-cart-empty-text">No items yet</div>
                            <div class="sales-cart-empty-sub">Tap any product to add it</div>
                        </div>

                        <div id="cartBody" class="sales-cart-body" style="display:none;"></div>

                        <div id="cartTotals" class="sales-cart-totals" style="display:none;">
                            <div class="sales-cart-totals-row">
                                <span class="sales-cart-units">
                                    Units: <strong id="cartTotalQty">0</strong>
                                </span>
                                <span class="sales-cart-grand" id="cartGrandTotal">P0.00</span>
                            </div>
                        </div>

                        <div class="sales-cart-actions">
                            <button type="button" id="cartClearBtn"
                                    class="btn btn-outline sales-cart-clear">Clear</button>
                            <button type="button" id="cartConfirmBtn"
                                    class="btn btn-primary sales-cart-confirm" disabled>
                                Confirm Order ✓
                            </button>
                        </div>

                    </div>
                </div>

            </div><!-- /sales-pos-layout -->

        </div><!-- /tabOrder -->
        <?php endif; ?>

        <!-- ===== TAB 2: SALES RECORDS ===== -->
        <div class="sales-tab-panel <?= $show_records_tab ? 'active' : '' ?>" id="tabRecords">

            <div class="section-header" style="margin-bottom:16px;padding:16px 20px 0;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <h3 style="margin:0;"><?= date('M j, Y', strtotime($date)) ?></h3>
                    <?php if (!empty($sales)): ?>
                        <span class="total-display">
                            Day Total: <strong>P<?= number_format($day_total_calc, 2) ?></strong>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="section-header-right">
                    <?php if ($is_today && !$any_locked && !empty($sales) && Auth::isOwner()): ?>
                        <form method="POST" action="<?= BASE_URL ?>/sales/lock" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                            <input type="hidden" name="kiosk_id"   value="<?= $kiosk_id ?>">
                            <input type="hidden" name="date"       value="<?= $date ?>">
                            <button type="button" class="btn btn-primary"
                                    onclick="showConfirmModal({
                                        title: 'Lock All Sales',
                                        message: 'Lock all sales records for today? Locked records cannot be edited by staff.',
                                        confirmText: 'Yes, Lock All',
                                        type: 'lock',
                                        onConfirm: () => this.closest('form').submit()
                                    })">
                                🔒 Lock All
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($is_today && !$any_locked): ?>
                        <button type="button" class="btn btn-outline"
                                onclick="switchSalesTab('order')">
                            ➕ New Order
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($sales)): ?>
                <div class="empty-state-card">
                    <div class="empty-state-icon">🧾</div>
                    <div class="empty-state-text">No sales recorded</div>
                    <div class="empty-state-sub">
                        <?php if ($is_today && !$any_locked): ?>
                            Switch to "New Order" to record a sale.
                        <?php else: ?>
                            No sales found for <?= date('M j, Y', strtotime($date)) ?>.
                        <?php endif; ?>
                    </div>
                    <?php if ($is_today && !$any_locked): ?>
                        <button type="button" class="btn btn-primary"
                                style="margin-top:16px;"
                                onclick="switchSalesTab('order')">➕ New Order</button>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="table-container" style="padding:0 20px 20px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Line Total</th>
                                <th>Recorded By</th>
                                <th>Time</th>
                                <th>Status</th>
                                <?php if (Auth::isOwner()): ?><th>Actions</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $s): ?>
                                <?php $show_locked = $staff_locked || $s['Locked_status']; ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($s['Product_Name']) ?></strong></td>
                                    <td>
                                        <span class="badge badge-category">
                                            <?= htmlspecialchars($s['Category_Name'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td><?= $s['Quantity_sold'] ?></td>
                                    <td>P<?= number_format($s['Unit_Price'], 2) ?></td>
                                    <td><strong>P<?= number_format($s['Line_total'], 2) ?></strong></td>
                                    <td><?= htmlspecialchars($s['Recorded_by'] ?? '—') ?></td>
                                    <td style="white-space:nowrap;">
                                        <?= date('g:i A', strtotime($s['Created_at'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $show_locked ? 'badge-locked' : 'badge-active' ?>">
                                            <?= $show_locked ? '🔒 Locked' : 'Open' ?>
                                        </span>
                                    </td>
                                    <?php if (Auth::isOwner()): ?>
                                        <td>
                                            <div class="table-actions">
                                                <?php if ($s['Locked_status']): ?>
                                                    <form method="POST" action="<?= BASE_URL ?>/sales/unlock" class="inline-form">
                                                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                        <input type="hidden" name="sales_id"   value="<?= $s['Sales_ID'] ?>">
                                                        <input type="hidden" name="date"       value="<?= $date ?>">
                                                        <button type="button" class="btn btn-sm btn-outline"
                                                                onclick="showConfirmModal({
                                                                    title: 'Unlock Record',
                                                                    message: 'Unlock this sales record? This will allow editing of past data.',
                                                                    confirmText: 'Yes, Unlock',
                                                                    type: 'unlock',
                                                                    onConfirm: () => this.closest('form').submit()
                                                                })">🔓 Unlock</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="<?= BASE_URL ?>/sales/delete" class="inline-form">
                                                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                        <input type="hidden" name="sales_id"   value="<?= $s['Sales_ID'] ?>">
                                                        <input type="hidden" name="date"       value="<?= $date ?>">
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                                onclick="showConfirmModal({
                                                                    title: 'Delete Sale',
                                                                    message: 'Delete this sale record? This action cannot be undone.',
                                                                    confirmText: 'Yes, Delete',
                                                                    type: 'delete',
                                                                    onConfirm: () => this.closest('form').submit()
                                                                })">Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Day-total summary row -->
                            <tr class="totals-row">
                                <td colspan="4" style="text-align:right;font-weight:700;">Day Total:</td>
                                <td style="font-weight:800;color:#1B5E20;font-size:15px;">
                                    P<?= number_format($day_total_calc, 2) ?>
                                </td>
                                <td colspan="<?= Auth::isOwner() ? 4 : 3 ?>"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div><!-- /tabRecords -->

    </div><!-- /sales-tab-wrapper -->
</section>

<script>
(function () {

    // ============ TAB SWITCHING ============
    function switchSalesTab(name) {
        document.querySelectorAll('.sales-tab-btn').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.tab === name);
        });
        const map = { order: 'tabOrder', records: 'tabRecords' };
        document.querySelectorAll('.sales-tab-panel').forEach(function (panel) {
            panel.classList.toggle('active', panel.id === map[name]);
        });
    }
    document.querySelectorAll('.sales-tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            switchSalesTab(this.dataset.tab);
        });
    });
    window.switchSalesTab = switchSalesTab;

    // ============ PRODUCT MAP (from PHP) ============
    const productMap = <?= json_encode($product_map ?? []) ?>;

    // ============ CART STATE ============
    let cart = {};

    // ============ CATEGORY FILTER ============
    const grid = document.getElementById('salesProductGrid');

    document.querySelectorAll('.pos-cat-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.pos-cat-btn')
                    .forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const cat = this.dataset.category;
            if (!grid) return;
            grid.querySelectorAll('.pos-product-card').forEach(function (card) {
                card.style.display =
                    (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
            });
        });
    });

    // ============ POS TOAST (transient warning banner) ============
    let posToastTimer = null;
    function showPosToast(msg, kind) {
        let toast = document.getElementById('posToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'posToast';
            toast.className = 'pos-toast';
            document.body.appendChild(toast);
        }
        toast.textContent = msg;
        toast.className = 'pos-toast pos-toast-' + (kind || 'warn') + ' is-visible';
        clearTimeout(posToastTimer);
        posToastTimer = setTimeout(() => toast.classList.remove('is-visible'), 2800);
    }

    // ============ PRODUCT CLICK -> ADD TO CART ============
    if (grid) {
        grid.addEventListener('click', function (e) {
            const card = e.target.closest('.pos-product-card');
            if (!card) return;
            // Block unavailable products (no inventory or out of stock)
            if (card.dataset.available === '0') {
                card.classList.add('card-shake');
                setTimeout(() => card.classList.remove('card-shake'), 400);
                showPosToast('Out of stock — not enough parts to make this product');
                return;
            }
            // Block if cart already at max for this product
            const id  = card.dataset.id;
            const max = parseInt(card.dataset.max || '9999', 10);
            const cur = cart[id] ? cart[id].qty : 0;
            if (cur >= max) {
                card.classList.add('card-shake');
                setTimeout(() => card.classList.remove('card-shake'), 400);
                showPosToast('Only ' + max + ' ' + card.dataset.name + ' available — cart already has ' + cur);
                return;
            }
            const p = productMap[id];
            if (!p) return;
            addToCart(id, p);

            card.classList.add('card-added');
            setTimeout(() => card.classList.remove('card-added'), 250);
        });
    }

    // ============ CART FUNCTIONS ============
    function addToCart(id, product) {
        if (cart[id]) {
            cart[id].qty += 1;
        } else {
            cart[id] = {
                id:    id,
                name:  product.Name,
                price: parseFloat(product.Price) || 0,
                unit:  product.Unit,
                qty:   1,
            };
        }
        renderCart();
        updateBadges();
    }
    function removeFromCart(id) {
        delete cart[id];
        renderCart();
        updateBadges();
    }
    function updateQty(id, newQty) {
        newQty = parseInt(newQty) || 0;
        if (newQty <= 0) { removeFromCart(id); return; }
        // Clamp to per-product max from data-max on the card
        const card = document.querySelector('.pos-product-card[data-id="' + id + '"]');
        const max  = card ? parseInt(card.dataset.max || '9999', 10) : 9999;
        if (newQty > max) {
            const name = (cart[id] && cart[id].name) || 'this product';
            showPosToast('Only ' + max + ' ' + name + ' available right now');
            newQty = max;
        }
        if (cart[id]) cart[id].qty = newQty;
        renderCart();
        updateBadges();
    }
    function clearCart() {
        cart = {};
        renderCart();
        updateBadges();
    }
    function cartItems()    { return Object.values(cart); }
    function cartTotal()    { return cartItems().reduce((s, i) => s + i.price * i.qty, 0); }
    function cartTotalQty() { return cartItems().reduce((s, i) => s + i.qty, 0); }

    // ============ RENDER CART ============
    function renderCart() {
        const cartBody    = document.getElementById('cartBody');
        const cartEmpty   = document.getElementById('cartEmpty');
        const cartTotals  = document.getElementById('cartTotals');
        const cartConfirm = document.getElementById('cartConfirmBtn');
        const cartCount   = document.getElementById('cartItemCount');
        const grandTotal  = document.getElementById('cartGrandTotal');
        const totalQtyEl  = document.getElementById('cartTotalQty');

        const items = cartItems();
        const total = cartTotal();
        const qty   = cartTotalQty();

        if (cartCount)   cartCount.textContent =
            items.length + (items.length === 1 ? ' item' : ' items');
        if (cartEmpty)   cartEmpty.style.display   = items.length === 0 ? '' : 'none';
        if (cartBody)    cartBody.style.display    = items.length >  0 ? '' : 'none';
        if (cartTotals)  cartTotals.style.display  = items.length >  0 ? '' : 'none';
        if (cartConfirm) cartConfirm.disabled      = items.length === 0;
        if (grandTotal)  grandTotal.textContent    = 'P' + total.toFixed(2);
        if (totalQtyEl)  totalQtyEl.textContent    = qty;

        if (!cartBody) return;
        cartBody.innerHTML = '';

        items.forEach(function (item) {
            const lineTotal = (item.price * item.qty).toFixed(2);
            const row = document.createElement('div');
            row.className = 'sales-cart-line';
            row.innerHTML =
                '<div class="sales-cart-line-info">' +
                    '<div class="sales-cart-line-name">' + esc(item.name) + '</div>' +
                    '<div class="sales-cart-line-price">P' + item.price.toFixed(2) + ' each</div>' +
                '</div>' +
                '<div class="sales-cart-line-qty">' +
                    '<button type="button" class="cart-qty-btn" data-id="' + item.id + '" data-action="dec">&minus;</button>' +
                    '<input type="number" class="cart-qty-input" value="' + item.qty + '" min="1" data-id="' + item.id + '">' +
                    '<button type="button" class="cart-qty-btn" data-id="' + item.id + '" data-action="inc">+</button>' +
                '</div>' +
                '<div class="sales-cart-line-total">P' + lineTotal + '</div>' +
                '<button type="button" class="sales-cart-remove" data-id="' + item.id + '">&times;</button>';
            cartBody.appendChild(row);
        });

        cartBody.querySelectorAll('.cart-qty-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id  = this.dataset.id;
                const act = this.dataset.action;
                const cur = cart[id] ? cart[id].qty : 0;
                updateQty(id, act === 'inc' ? cur + 1 : cur - 1);
            });
        });
        cartBody.querySelectorAll('.cart-qty-input').forEach(function (input) {
            input.addEventListener('change', function () {
                updateQty(this.dataset.id, parseInt(this.value) || 0);
            });
        });
        cartBody.querySelectorAll('.sales-cart-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                removeFromCart(this.dataset.id);
            });
        });
    }

    // ============ PRODUCT BADGES ============
    function updateBadges() {
        if (!grid) return;
        grid.querySelectorAll('.pos-product-card').forEach(function (card) {
            const id   = card.dataset.id;
            let badge  = card.querySelector('.pos-cart-badge');
            const item = cart[id];
            if (item) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'pos-cart-badge';
                    card.appendChild(badge);
                }
                badge.textContent = item.qty;
            } else if (badge) {
                badge.remove();
            }
        });
    }

    // ============ CLEAR ============
    const clearBtn = document.getElementById('cartClearBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (cartItems().length === 0) return;
            showConfirmModal({
                title: 'Clear Order',
                message: 'Remove all items from the current order?',
                confirmText: 'Yes, Clear',
                type: 'delete',
                onConfirm: () => clearCart(),
            });
        });
    }

    // ============ CONFIRM ORDER ============
    const confirmBtn = document.getElementById('cartConfirmBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            const items = cartItems();
            if (items.length === 0) return;
            showConfirmModal({
                title: 'Confirm Order',
                message: 'Submit ' + items.length + ' product(s) — '
                       + cartTotalQty() + ' total unit(s) for P'
                       + cartTotal().toFixed(2) + '?',
                confirmText: 'Yes, Confirm Order',
                type: 'submit',
                onConfirm: () => submitCart(),
            });
        });
    }

    // ============ SUBMIT ============
    function submitCart() {
        const items   = cartItems();
        const csrfEl  = document.getElementById('posCsrfToken');
        const dateEl  = document.getElementById('posSalesDate');
        const kioskEl = document.getElementById('posKioskId');
        if (!csrfEl || items.length === 0) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= BASE_URL ?>/sales/store-batch';

        const addField = (name, value) => {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = name;
            input.value = value;
            form.appendChild(input);
        };

        addField('csrf_token', csrfEl.value);
        addField('date',       dateEl  ? dateEl.value  : '<?= $date ?>');
        addField('kiosk_id',   kioskEl ? kioskEl.value : '<?= (int) $kiosk_id ?>');

        items.forEach(function (item) {
            addField('product_ids[]', item.id);
            addField('quantities[]',  item.qty);
        });

        document.body.appendChild(form);
        form.submit();
    }

    // ============ HELPERS ============
    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    renderCart();
})();
</script>
