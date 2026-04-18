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
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Product</th>
                            <th>Unit</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <?php if (Auth::isOwner()): ?><th>Action</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($beginning as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Category_Name']) ?></td>
                                <td><?= htmlspecialchars($row['Product_Name']) ?></td>
                                <td><?= htmlspecialchars($row['Unit']) ?></td>
                                <td><strong><?= $row['Quantity'] ?></strong></td>
                                <td>
                                    <?php $show_locked = $staff_locked || $row['Locked_status']; ?>
                                    <span class="badge <?= $show_locked ? 'badge-locked' : 'badge-active' ?>">
                                        <?= $show_locked ? 'Locked' : 'Open' ?>
                                    </span>
                                </td>
                                <?php if (Auth::isOwner()): ?>
                                    <td>
                                        <?php if ($row['Locked_status']): ?>
                                            <form method="POST" action="<?= BASE_URL ?>/inventory/unlock" class="inline-form">
                                                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                <input type="hidden" name="inventory_id" value="<?= $row['Inventory_ID'] ?>">
                                                <input type="hidden" name="date" value="<?= $date ?>">
                                                <button type="submit" class="btn btn-sm btn-outline"
                                                        onclick="return confirm('Unlock this record?')">Unlock</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-light">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($is_today): ?>
            <!-- Beginning Stock Form -->
            <form method="POST" action="<?= BASE_URL ?>/inventory/store">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                <input type="hidden" name="snapshot_type" value="beginning">
                <input type="hidden" name="kiosk_id" value="<?= $kiosk_id ?>">
                <input type="hidden" name="date" value="<?= $date ?>">

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Product</th>
                                <th>Unit</th>
                                <th>Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $category => $items): ?>
                                <?php foreach ($items as $product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($category) ?></td>
                                        <td><?= htmlspecialchars($product['Name']) ?></td>
                                        <td><?= htmlspecialchars($product['Unit']) ?></td>
                                        <td>
                                            <input type="number" name="qty[<?= $product['Product_ID'] ?>]"
                                                   class="form-input input-sm" min="0" value="0" required>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-actions" style="margin-top:16px">
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Submit beginning stock?')">Save Beginning Stock</button>
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
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Product</th>
                            <th>Unit</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <?php if (Auth::isOwner()): ?><th>Action</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ending as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Category_Name']) ?></td>
                                <td><?= htmlspecialchars($row['Product_Name']) ?></td>
                                <td><?= htmlspecialchars($row['Unit']) ?></td>
                                <td><strong><?= $row['Quantity'] ?></strong></td>
                                <td>
                                    <?php $show_locked = $staff_locked || $row['Locked_status']; ?>
                                    <span class="badge <?= $show_locked ? 'badge-locked' : 'badge-active' ?>">
                                        <?= $show_locked ? 'Locked' : 'Open' ?>
                                    </span>
                                </td>
                                <?php if (Auth::isOwner()): ?>
                                    <td>
                                        <?php if ($row['Locked_status']): ?>
                                            <form method="POST" action="<?= BASE_URL ?>/inventory/unlock" class="inline-form">
                                                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                <input type="hidden" name="inventory_id" value="<?= $row['Inventory_ID'] ?>">
                                                <input type="hidden" name="date" value="<?= $date ?>">
                                                <button type="submit" class="btn btn-sm btn-outline"
                                                        onclick="return confirm('Unlock this record?')">Unlock</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-light">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($is_today && $has_beginning): ?>
            <!-- Ending Stock Form -->
            <form method="POST" action="<?= BASE_URL ?>/inventory/store">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                <input type="hidden" name="snapshot_type" value="ending">
                <input type="hidden" name="kiosk_id" value="<?= $kiosk_id ?>">
                <input type="hidden" name="date" value="<?= $date ?>">

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Product</th>
                                <th>Unit</th>
                                <th>Beginning Qty</th>
                                <th>Ending Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($beginning as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Category_Name']) ?></td>
                                    <td><?= htmlspecialchars($row['Product_Name']) ?></td>
                                    <td><?= htmlspecialchars($row['Unit']) ?></td>
                                    <td><?= $row['Quantity'] ?></td>
                                    <td>
                                        <input type="number" name="qty[<?= $row['Product_ID'] ?>]"
                                               class="form-input input-sm" min="0" value="0" required>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-actions" style="margin-top:16px">
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Submit ending stock?')">Save Ending Stock</button>
                </div>
            </form>
        <?php elseif ($is_today && !$has_beginning): ?>
            <p class="text-light">Record beginning stock first before entering ending stock.</p>
        <?php else: ?>
            <p class="text-light">No ending stock recorded for this date.</p>
        <?php endif; ?>
    </div>
</section>
