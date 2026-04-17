<section class="reports">
    <div class="section-header">
        <h2>Reports</h2>
    </div>

    <?php require __DIR__ . '/tabs.php'; ?>

    <!-- Filters -->
    <div class="card form-card">
        <form method="GET" action="<?= BASE_URL ?>/reports/consolidated" class="form-inline-row">
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

    <!-- Overview Cards -->
    <div class="report-overview">
        <div class="card report-card">
            <h4>Total Sales</h4>
            <span class="report-value text-success">P<?= number_format($grand_sales, 2) ?></span>
        </div>
        <div class="card report-card">
            <h4>Total Expenses</h4>
            <span class="report-value text-danger">P<?= number_format($grand_expenses, 2) ?></span>
        </div>
        <div class="card report-card">
            <h4>Net Income</h4>
            <span class="report-value <?= $net_income >= 0 ? 'text-success' : 'text-danger' ?>">
                P<?= number_format($net_income, 2) ?>
            </span>
        </div>
    </div>

    <!-- Sales by Kiosk -->
    <div class="card">
        <h3>Sales by Kiosk</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Kiosk</th>
                        <th>Transactions</th>
                        <th>Total Sales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales_by_kiosk as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['Kiosk_Name']) ?></td>
                            <td><?= $s['Total_Transactions'] ?></td>
                            <td><strong>P<?= number_format($s['Total_Sales'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Expenses by Kiosk -->
    <div class="card">
        <h3>Expenses by Kiosk</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Kiosk</th>
                        <th>Entries</th>
                        <th>Total Expenses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expense_by_kiosk as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['Kiosk_Name']) ?></td>
                            <td><?= $e['Total_Entries'] ?></td>
                            <td><strong>P<?= number_format($e['Total_Expenses'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Daily Sales Breakdown -->
    <div class="card">
        <h3>Daily Sales Breakdown</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Kiosk</th>
                        <th>Transactions</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daily_sales)): ?>
                        <tr><td colspan="4" class="text-center">No sales in this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($daily_sales as $ds): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($ds['Sales_date'])) ?></td>
                                <td><?= htmlspecialchars($ds['Kiosk_Name']) ?></td>
                                <td><?= $ds['Transactions'] ?></td>
                                <td><strong>P<?= number_format($ds['Day_Total'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Daily Expense Breakdown -->
    <div class="card">
        <h3>Daily Expense Breakdown</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Kiosk</th>
                        <th>Entries</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daily_expenses)): ?>
                        <tr><td colspan="4" class="text-center">No expenses in this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($daily_expenses as $de): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($de['Expense_date'])) ?></td>
                                <td><?= htmlspecialchars($de['Kiosk_Name']) ?></td>
                                <td><?= $de['Entries'] ?></td>
                                <td><strong>P<?= number_format($de['Day_Total'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delivery Summary -->
    <div class="card">
        <h3>Delivery Summary</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Kiosk</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Total Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deliveries)): ?>
                        <tr><td colspan="5" class="text-center">No deliveries in this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($deliveries as $del): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($del['Delivery_Date'])) ?></td>
                                <td><?= htmlspecialchars($del['Kiosk_Name']) ?></td>
                                <td><?= htmlspecialchars($del['Product_Name']) ?></td>
                                <td><span class="badge badge-category"><?= htmlspecialchars($del['Category_Name']) ?></span></td>
                                <td><strong><?= $del['Total_Qty'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Anomalies -->
    <?php if (!empty($anomalies)): ?>
        <div class="card">
            <h3>Missing Inventory Snapshots</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Kiosk</th>
                            <th>Missing</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($anomalies as $a): ?>
                            <tr class="row-warning">
                                <td><?= date('M j, Y', strtotime($a['Snapshot_date'])) ?></td>
                                <td><?= htmlspecialchars($a['Kiosk_Name']) ?></td>
                                <td>
                                    <?php
                                        $missing = [];
                                        if ($a['has_beginning'] == 0) $missing[] = 'Beginning';
                                        if ($a['has_ending'] == 0) $missing[] = 'Ending';
                                        echo implode(' & ', $missing);
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
