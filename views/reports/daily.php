<section class="reports">
    <div class="section-header">
        <h2>Reports</h2>
    </div>

    <?php require __DIR__ . '/tabs.php'; ?>

    <!-- Filters -->
    <div class="card form-card">
        <form method="GET" action="<?= BASE_URL ?>/reports/daily" class="form-inline-row">
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
            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" class="form-input"
                       value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <!-- Daily Summary -->
    <div class="card">
        <div class="section-header">
            <h3>Daily Summary — <?= htmlspecialchars($kiosk['Name'] ?? '') ?></h3>
            <div class="section-header-right">
                <span class="total-display">Total Sales: <strong>P<?= number_format($total_sales, 2) ?></strong></span>
                <?php if ($has_discrepancy): ?>
                    <span class="badge badge-locked">Has Discrepancies</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Product</th>
                        <th>Unit</th>
                        <th>Beginning</th>
                        <th>Delivered</th>
                        <th>Sold</th>
                        <th>Sales (P)</th>
                        <th>Ending</th>
                        <th>Expected</th>
                        <th>Discrepancy</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report)): ?>
                        <tr><td colspan="10" class="text-center">No data for this date. Make sure beginning stock has been recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($report as $row): ?>
                            <tr class="<?= $row['Discrepancy'] !== 0 ? 'row-warning' : '' ?>">
                                <td><?= htmlspecialchars($row['Category_Name']) ?></td>
                                <td><?= htmlspecialchars($row['Product_Name']) ?></td>
                                <td><?= htmlspecialchars($row['Unit']) ?></td>
                                <td><?= $row['Beginning_Qty'] ?></td>
                                <td><?= $row['Delivered_Qty'] ?></td>
                                <td><?= $row['Sold_Qty'] ?></td>
                                <td>P<?= number_format($row['Sales_Total'], 2) ?></td>
                                <td><?= $row['Ending_Qty'] ?></td>
                                <td><?= $row['Expected_Qty'] ?></td>
                                <td>
                                    <?php if ($row['Discrepancy'] !== 0): ?>
                                        <strong class="<?= $row['Discrepancy'] < 0 ? 'text-danger' : 'text-success' ?>">
                                            <?= $row['Discrepancy'] > 0 ? '+' : '' ?><?= $row['Discrepancy'] ?>
                                        </strong>
                                    <?php else: ?>
                                        <span class="text-light">0</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($report)): ?>
            <p class="report-note">
                Expected = Beginning + Delivered - Sold. Discrepancy = Ending - Expected.
                Negative = stock shortage, Positive = excess.
            </p>
        <?php endif; ?>
    </div>
</section>
