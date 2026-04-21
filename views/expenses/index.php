<?php $staff_locked = Auth::isStaff(); ?>
<section class="expenses">
    <div class="section-header">
        <h2>Expenses — <?= htmlspecialchars($kiosk['Name'] ?? 'Unknown') ?></h2>
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
        <form method="GET" action="<?= BASE_URL ?>/expenses" class="form-inline-row">
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
            <h3>Recent Expenses</h3>
            <div class="history-links">
                <?php foreach (array_slice($history, 0, 7) as $record): ?>
                    <a href="<?= BASE_URL ?>/expenses?kiosk_id=<?= $kiosk_id ?>&date=<?= $record['Expense_date'] ?>"
                       class="btn btn-sm <?= $record['Expense_date'] === $date ? 'btn-primary' : 'btn-outline' ?>">
                        <?= date('M j', strtotime($record['Expense_date'])) ?>
                        (P<?= number_format($record['day_total'], 2) ?>)
                        <?= $record['is_locked'] ? '&#128274;' : '' ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Add Expense Form -->
    <?php if ($is_today && !$any_locked): ?>
        <div class="card form-card">
            <h3>Record Expense</h3>
            <form method="POST" action="<?= BASE_URL ?>/expenses/store" class="form-inline-row">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                <input type="hidden" name="kiosk_id" value="<?= $kiosk_id ?>">
                <input type="hidden" name="date" value="<?= $date ?>">

                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" class="form-input"
                           placeholder="e.g. LPG refill, paper cups" required>
                </div>

                <div class="form-group">
                    <label for="amount">Amount (P)</label>
                    <input type="number" id="amount" name="amount" class="form-input input-sm"
                           min="0.01" step="0.01" required>
                </div>

                <div class="form-group form-actions">
                    <button type="submit" class="btn btn-primary">Add Expense</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Expenses Table -->
    <div class="card">
        <div class="section-header">
            <h3>Expense Records</h3>
            <div class="section-header-right">
                <span class="total-display">Day Total: <strong>P<?= number_format($day_total, 2) ?></strong></span>
                <?php if (!empty($expenses) && !$any_locked && $is_today && Auth::isOwner()): ?>
                    <form method="POST" action="<?= BASE_URL ?>/expenses/lock" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                        <input type="hidden" name="kiosk_id" value="<?= $kiosk_id ?>">
                        <input type="hidden" name="date" value="<?= $date ?>">
                        <button type="button" class="btn btn-sm btn-secondary"
                                onclick="showConfirmModal({
                                    title: 'Lock All Expenses',
                                    message: 'Are you sure you want to lock all expense records for today?',
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
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Recorded By</th>
                        <th>Time</th>
                        <th>Status</th>
                        <?php if (Auth::isOwner()): ?><th>Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                        <tr><td colspan="<?= Auth::isOwner() ? 6 : 5 ?>" class="text-center">No expenses recorded for this date.</td></tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $e): ?>
                            <?php $show_locked = $staff_locked || $e['Locked_status']; ?>
                            <tr>
                                <td><?= htmlspecialchars($e['Description']) ?></td>
                                <td><strong>P<?= number_format($e['Amount'], 2) ?></strong></td>
                                <td><?= htmlspecialchars($e['Recorded_by']) ?></td>
                                <td><?= date('g:i A', strtotime($e['Created_at'])) ?></td>
                                <td>
                                    <span class="badge <?= $show_locked ? 'badge-locked' : 'badge-active' ?>">
                                        <?= $show_locked ? 'Locked' : 'Open' ?>
                                    </span>
                                </td>
                                <?php if (Auth::isOwner()): ?>
                                <td>
                                    <?php if (!$e['Locked_status']): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/expenses/delete" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                            <input type="hidden" name="expense_id" value="<?= $e['Expense_ID'] ?>">
                                            <input type="hidden" name="date" value="<?= $date ?>">
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="showConfirmModal({
                                                        title: 'Delete Expense',
                                                        message: 'Are you sure you want to delete this expense record? This action cannot be undone.',
                                                        confirmText: 'Yes, Delete',
                                                        type: 'delete',
                                                        onConfirm: () => this.closest('form').submit()
                                                    })">Delete</button>
                                        </form>
                                    <?php elseif ($e['Locked_status']): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/expenses/unlock" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                            <input type="hidden" name="expense_id" value="<?= $e['Expense_ID'] ?>">
                                            <input type="hidden" name="date" value="<?= $date ?>">
                                            <button type="button" class="btn btn-sm btn-outline"
                                                    onclick="showConfirmModal({
                                                        title: 'Unlock Record',
                                                        message: 'Are you sure you want to unlock this expense record? This will allow editing of past data.',
                                                        confirmText: 'Yes, Unlock',
                                                        type: 'unlock',
                                                        onConfirm: () => this.closest('form').submit()
                                                    })">Unlock</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-light">—</span>
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
</section>
