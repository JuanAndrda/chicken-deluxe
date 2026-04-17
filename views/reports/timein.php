<section class="reports">
    <div class="section-header">
        <h2>Reports</h2>
    </div>

    <?php require __DIR__ . '/tabs.php'; ?>

    <!-- Filters -->
    <div class="card form-card">
        <form method="GET" action="<?= BASE_URL ?>/reports/timein" class="form-inline-row">
            <div class="form-group">
                <label for="outlet_id">Kiosk</label>
                <select id="outlet_id" name="outlet_id" class="form-select">
                    <option value="">All Kiosks</option>
                    <?php foreach ($kiosks as $k): ?>
                        <option value="<?= $k['Kiosk_ID'] ?>" <?= $k['Kiosk_ID'] == $outlet_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="from_date">From</label>
                <input type="date" id="from_date" name="from_date" class="form-input"
                       value="<?= htmlspecialchars($from_date) ?>">
            </div>
            <div class="form-group">
                <label for="to_date">To</label>
                <input type="date" id="to_date" name="to_date" class="form-input"
                       value="<?= htmlspecialchars($to_date) ?>">
            </div>
            <div class="form-group form-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>

    <!-- Time-In Records -->
    <div class="card">
        <h3>Staff Attendance Records</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Staff Name</th>
                        <th>Username</th>
                        <th>Kiosk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="5" class="text-center">No time-in records found for this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($r['Timestamp'])) ?></td>
                                <td><?= date('g:i A', strtotime($r['Timestamp'])) ?></td>
                                <td><?= htmlspecialchars($r['Full_name']) ?></td>
                                <td><?= htmlspecialchars($r['Username']) ?></td>
                                <td><?= htmlspecialchars($r['Kiosk_Name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
