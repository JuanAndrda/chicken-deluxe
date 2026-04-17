<section class="admin-audit">
    <div class="section-header">
        <h2>Audit Log</h2>
    </div>

    <!-- Date Filter -->
    <div class="card form-card">
        <form method="GET" action="<?= BASE_URL ?>/admin/audit-log" class="form-inline-row">
            <div class="form-group">
                <label for="from_date">From</label>
                <input type="date" id="from_date" name="from_date" class="form-input"
                       value="<?= htmlspecialchars($from_date ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="to_date">To</label>
                <input type="date" id="to_date" name="to_date" class="form-input"
                       value="<?= htmlspecialchars($to_date ?? '') ?>">
            </div>
            <div class="form-group form-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= BASE_URL ?>/admin/audit-log" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>

    <!-- Log Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="text-center">No log entries found.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= $log['Log_ID'] ?></td>
                            <td><?= date('M j, Y g:i A', strtotime($log['Timestamp'])) ?></td>
                            <td><?= htmlspecialchars($log['Full_name'] ?? 'System') ?></td>
                            <td><span class="badge badge-action"><?= htmlspecialchars($log['Action']) ?></span></td>
                            <td><?= htmlspecialchars($log['Details'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
