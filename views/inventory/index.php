<?php $staff_locked = Auth::isStaff(); ?>
<section class="inventory">
    <div class="section-header">
        <h2>Inventory — <?= htmlspecialchars($kiosk['Name'] ?? 'Unknown') ?></h2>
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
        <form method="GET" action="<?= BASE_URL ?>/inventory" class="form-inline-row">
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
            <h3>Recent Records</h3>
            <div class="history-links">
                <?php foreach (array_slice($history, 0, 7) as $record): ?>
                    <a href="<?= BASE_URL ?>/inventory?kiosk_id=<?= $kiosk_id ?>&date=<?= $record['Snapshot_date'] ?>"
                       class="btn btn-sm <?= $record['Snapshot_date'] === $date ? 'btn-primary' : 'btn-outline' ?>">
                        <?= date('M j', strtotime($record['Snapshot_date'])) ?>
                        <?= $record['is_locked'] ? '&#128274;' : '' ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Beginning Stock -->
    <div class="card">
        <h3>Beginning Stock</h3>
        <?php if ($has_beginning): ?>
            <?php
                // Group saved rows by category to mirror the entry-form tab layout
                $beginning_grouped = [];
                foreach ($beginning as $row) {
                    $beginning_grouped[$row['Category_Name']][] = $row;
                }
            ?>
            <div class="inventory-saved-view">
                <div class="inventory-tabs" role="tablist">
                    <?php $first = true; foreach ($beginning_grouped as $category => $items): ?>
                        <button type="button"
                                class="inventory-tab<?= $first ? ' active' : '' ?>"
                                data-category="<?= htmlspecialchars($category) ?>"
                                role="tab">
                            <span class="inventory-tab-name"><?= htmlspecialchars($category) ?></span>
                            <span class="inventory-tab-meta">(<?= count($items) ?>)</span>
                        </button>
                    <?php $first = false; endforeach; ?>
                </div>

                <?php $first = true; foreach ($beginning_grouped as $category => $items): ?>
                    <div class="inventory-tab-panel<?= $first ? ' active' : '' ?>"
                         data-category="<?= htmlspecialchars($category) ?>" role="tabpanel">
                        <?php foreach ($items as $row): ?>
                            <?php $show_locked = $staff_locked || $row['Locked_status']; ?>
                            <div class="inventory-product-row">
                                <div class="inventory-product-name"><?= htmlspecialchars($row['Product_Name']) ?></div>
                                <div class="inventory-product-unit"><?= htmlspecialchars($row['Unit']) ?></div>
                                <div class="inventory-saved-qty"><?= $row['Quantity'] ?></div>
                                <div class="inventory-saved-status">
                                    <span class="badge <?= $show_locked ? 'badge-locked' : 'badge-active' ?>">
                                        <?= $show_locked ? 'Locked' : 'Open' ?>
                                    </span>
                                </div>
                                <?php if (Auth::isOwner()): ?>
                                    <div class="inventory-saved-action">
                                        <?php if ($row['Locked_status']): ?>
                                            <form method="POST" action="<?= BASE_URL ?>/inventory/unlock" class="inline-form">
                                                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                <input type="hidden" name="inventory_id" value="<?= $row['Inventory_ID'] ?>">
                                                <input type="hidden" name="date" value="<?= $date ?>">
                                                <button type="button" class="btn btn-sm btn-outline"
                                                        onclick="showConfirmModal({
                                                            title: 'Unlock Record',
                                                            message: 'Are you sure you want to unlock this inventory record? This will allow editing of past data.',
                                                            confirmText: 'Yes, Unlock',
                                                            type: 'unlock',
                                                            onConfirm: () => this.closest('form').submit()
                                                        })">Unlock</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-light">&mdash;</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php $first = false; endforeach; ?>
            </div>
        <?php elseif ($is_today): ?>
            <!-- Beginning Stock Form (tab layout) -->
            <?php $total_products = array_sum(array_map('count', $products)); ?>
            <form method="POST" action="<?= BASE_URL ?>/inventory/store"
                  class="inventory-entry-form" data-total="<?= $total_products ?>"
                  data-label="Save Beginning Stock">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                <input type="hidden" name="snapshot_type" value="beginning">
                <input type="hidden" name="kiosk_id" value="<?= $kiosk_id ?>">
                <input type="hidden" name="date" value="<?= $date ?>">

                <div class="inventory-progress-label">
                    <span class="inventory-filled-count">0</span> of <?= $total_products ?> products filled
                </div>
                <div class="inventory-progress">
                    <div class="inventory-progress-bar" style="width:0%"></div>
                </div>

                <div class="inventory-tabs" role="tablist">
                    <?php $first = true; foreach ($products as $category => $items): ?>
                        <button type="button"
                                class="inventory-tab<?= $first ? ' active' : '' ?>"
                                data-category="<?= htmlspecialchars($category) ?>"
                                role="tab">
                            <span class="inventory-tab-name"><?= htmlspecialchars($category) ?></span>
                            <span class="inventory-tab-meta">(<?= count($items) ?>)</span>
                        </button>
                    <?php $first = false; endforeach; ?>
                </div>

                <?php $first = true; foreach ($products as $category => $items): ?>
                    <div class="inventory-tab-panel<?= $first ? ' active' : '' ?>"
                         data-category="<?= htmlspecialchars($category) ?>" role="tabpanel">
                        <?php foreach ($items as $product): ?>
                            <div class="inventory-product-row">
                                <div class="inventory-product-name"><?= htmlspecialchars($product['Name']) ?></div>
                                <div class="inventory-product-unit"><?= htmlspecialchars($product['Unit']) ?></div>
                                <div class="inventory-qty-control">
                                    <button type="button" class="inventory-qty-btn" data-action="dec" aria-label="Decrease">&minus;</button>
                                    <input type="number"
                                           name="qty[<?= $product['Product_ID'] ?>]"
                                           class="inventory-qty-input"
                                           min="0" value="0" required>
                                    <button type="button" class="inventory-qty-btn" data-action="inc" aria-label="Increase">+</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php $first = false; endforeach; ?>

                <div class="inventory-sticky-submit">
                    <button type="button" class="btn btn-primary inventory-submit-btn"
                            onclick="showConfirmModal({
                                title: 'Submit Beginning Stock',
                                message: 'Are you sure you want to submit the beginning stock for today? Please double-check all quantities before confirming.',
                                confirmText: 'Yes, Submit',
                                type: 'submit',
                                onConfirm: () => this.closest('form').submit()
                            })">
                        Save Beginning Stock (<span class="inventory-filled-count">0</span> of <?= $total_products ?> filled)
                    </button>
                </div>
            </form>
        <?php else: ?>
            <p class="text-light">No beginning stock recorded for this date.</p>
        <?php endif; ?>
    </div>

    <!-- Ending Stock -->
    <div class="card">
        <h3>Ending Stock</h3>
        <?php if ($has_ending): ?>
            <?php
                // Group + map beginning quantities so we can show "Beginning: X" alongside ending
                $ending_grouped = [];
                foreach ($ending as $row) {
                    $ending_grouped[$row['Category_Name']][] = $row;
                }
                $beginning_qty_map = [];
                foreach ($beginning as $b_row) {
                    $beginning_qty_map[(int) $b_row['Product_ID']] = (int) $b_row['Quantity'];
                }
            ?>
            <div class="inventory-saved-view">
                <div class="inventory-tabs" role="tablist">
                    <?php $first = true; foreach ($ending_grouped as $category => $items): ?>
                        <button type="button"
                                class="inventory-tab<?= $first ? ' active' : '' ?>"
                                data-category="<?= htmlspecialchars($category) ?>"
                                role="tab">
                            <span class="inventory-tab-name"><?= htmlspecialchars($category) ?></span>
                            <span class="inventory-tab-meta">(<?= count($items) ?>)</span>
                        </button>
                    <?php $first = false; endforeach; ?>
                </div>

                <?php $first = true; foreach ($ending_grouped as $category => $items): ?>
                    <div class="inventory-tab-panel<?= $first ? ' active' : '' ?>"
                         data-category="<?= htmlspecialchars($category) ?>" role="tabpanel">
                        <?php foreach ($items as $row): ?>
                            <?php
                                $show_locked = $staff_locked || $row['Locked_status'];
                                $beg_qty     = $beginning_qty_map[(int) $row['Product_ID']] ?? null;
                                $is_warn     = $beg_qty !== null && (int) $row['Quantity'] > $beg_qty;
                            ?>
                            <div class="inventory-product-row<?= $is_warn ? ' inventory-row-warning' : '' ?>">
                                <div class="inventory-product-name"><?= htmlspecialchars($row['Product_Name']) ?></div>
                                <div class="inventory-product-unit"><?= htmlspecialchars($row['Unit']) ?></div>
                                <?php if ($beg_qty !== null): ?>
                                    <div class="inventory-ending-ref">Beginning: <strong><?= $beg_qty ?></strong></div>
                                <?php endif; ?>
                                <div class="inventory-saved-qty"><?= $row['Quantity'] ?></div>
                                <div class="inventory-saved-status">
                                    <span class="badge <?= $show_locked ? 'badge-locked' : 'badge-active' ?>">
                                        <?= $show_locked ? 'Locked' : 'Open' ?>
                                    </span>
                                </div>
                                <?php if (Auth::isOwner()): ?>
                                    <div class="inventory-saved-action">
                                        <?php if ($row['Locked_status']): ?>
                                            <form method="POST" action="<?= BASE_URL ?>/inventory/unlock" class="inline-form">
                                                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                <input type="hidden" name="inventory_id" value="<?= $row['Inventory_ID'] ?>">
                                                <input type="hidden" name="date" value="<?= $date ?>">
                                                <button type="button" class="btn btn-sm btn-outline"
                                                        onclick="showConfirmModal({
                                                            title: 'Unlock Record',
                                                            message: 'Are you sure you want to unlock this inventory record? This will allow editing of past data.',
                                                            confirmText: 'Yes, Unlock',
                                                            type: 'unlock',
                                                            onConfirm: () => this.closest('form').submit()
                                                        })">Unlock</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-light">&mdash;</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php $first = false; endforeach; ?>
            </div>
        <?php elseif ($is_today && $has_beginning): ?>
            <!-- Ending Stock Form (tab layout) -->
            <?php
                // Map Product_ID => beginning quantity for the per-row reference
                $beginning_qty_map = [];
                foreach ($beginning as $row) {
                    $beginning_qty_map[(int) $row['Product_ID']] = (int) $row['Quantity'];
                }
                $total_products = array_sum(array_map('count', $products));
            ?>
            <form method="POST" action="<?= BASE_URL ?>/inventory/store"
                  class="inventory-entry-form" data-total="<?= $total_products ?>"
                  data-label="Save Ending Stock">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                <input type="hidden" name="snapshot_type" value="ending">
                <input type="hidden" name="kiosk_id" value="<?= $kiosk_id ?>">
                <input type="hidden" name="date" value="<?= $date ?>">

                <div class="inventory-progress-label">
                    <span class="inventory-filled-count">0</span> of <?= $total_products ?> products filled
                </div>
                <div class="inventory-progress">
                    <div class="inventory-progress-bar" style="width:0%"></div>
                </div>

                <div class="inventory-tabs" role="tablist">
                    <?php $first = true; foreach ($products as $category => $items): ?>
                        <button type="button"
                                class="inventory-tab<?= $first ? ' active' : '' ?>"
                                data-category="<?= htmlspecialchars($category) ?>"
                                role="tab">
                            <span class="inventory-tab-name"><?= htmlspecialchars($category) ?></span>
                            <span class="inventory-tab-meta">(<?= count($items) ?>)</span>
                        </button>
                    <?php $first = false; endforeach; ?>
                </div>

                <?php $first = true; foreach ($products as $category => $items): ?>
                    <div class="inventory-tab-panel<?= $first ? ' active' : '' ?>"
                         data-category="<?= htmlspecialchars($category) ?>" role="tabpanel">
                        <?php foreach ($items as $product): ?>
                            <?php $beg_qty = $beginning_qty_map[(int) $product['Product_ID']] ?? 0; ?>
                            <div class="inventory-product-row" data-beginning="<?= $beg_qty ?>">
                                <div class="inventory-product-name"><?= htmlspecialchars($product['Name']) ?></div>
                                <div class="inventory-product-unit"><?= htmlspecialchars($product['Unit']) ?></div>
                                <div class="inventory-ending-ref">Beginning: <strong><?= $beg_qty ?></strong></div>
                                <div class="inventory-qty-control">
                                    <button type="button" class="inventory-qty-btn" data-action="dec" aria-label="Decrease">&minus;</button>
                                    <input type="number"
                                           name="qty[<?= $product['Product_ID'] ?>]"
                                           class="inventory-qty-input"
                                           min="0" value="0" required>
                                    <button type="button" class="inventory-qty-btn" data-action="inc" aria-label="Increase">+</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php $first = false; endforeach; ?>

                <div class="inventory-sticky-submit">
                    <button type="button" class="btn btn-primary inventory-submit-btn"
                            onclick="showConfirmModal({
                                title: 'Submit Ending Stock',
                                message: 'Are you sure you want to submit the ending stock for today? This will lock all inventory records for this date.',
                                confirmText: 'Yes, Submit',
                                type: 'submit',
                                onConfirm: () => this.closest('form').submit()
                            })">
                        Save Ending Stock (<span class="inventory-filled-count">0</span> of <?= $total_products ?> filled)
                    </button>
                </div>
            </form>
        <?php elseif ($is_today && !$has_beginning): ?>
            <p class="text-light">Record beginning stock first before entering ending stock.</p>
        <?php else: ?>
            <p class="text-light">No ending stock recorded for this date.</p>
        <?php endif; ?>
    </div>
</section>
