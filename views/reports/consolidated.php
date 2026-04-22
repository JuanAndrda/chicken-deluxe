<?php
    /*
     * Consolidated report view — pre-compute the per-kiosk shares so the
     * bar visuals can be rendered server-side (one inline width per row).
     * Everything else is just bucketed display, no business logic.
     */
    $max_kiosk_sales    = 0;
    foreach (($sales_by_kiosk ?? []) as $s)    { $max_kiosk_sales    = max($max_kiosk_sales, (float) $s['Total_Sales']); }
    $max_kiosk_expenses = 0;
    foreach (($expense_by_kiosk ?? []) as $e)  { $max_kiosk_expenses = max($max_kiosk_expenses, (float) $e['Total_Expenses']); }
    $has_anomalies = !empty($anomalies);
?>
<section class="reports">
    <div class="section-header">
        <h2>Reports</h2>
    </div>

    <?php require __DIR__ . '/tabs.php'; ?>

    <!-- Filters -->
    <div class="card form-card">
        <form method="GET" action="<?= BASE_URL ?>/reports/consolidated" class="form-inline-row">
            <div class="form-group">
                <label for="kiosk_id">Kiosk</label>
                <select id="kiosk_id" name="kiosk_id" class="form-select">
                    <option value="">All Kiosks</option>
                    <?php foreach ($kiosks as $k): ?>
                        <option value="<?= $k['Kiosk_ID'] ?>" <?= $k['Kiosk_ID'] == $kiosk_id ? 'selected' : '' ?>>
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

    <!-- Overview Cards (always visible) -->
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

    <!-- Inner sub-tabs -->
    <div class="report-inner-tabs" id="consolidatedInnerTabs" role="tablist">
        <button type="button" class="report-inner-tab active" data-tab="sales-kiosk" role="tab">Sales by Kiosk</button>
        <button type="button" class="report-inner-tab"        data-tab="exp-kiosk"   role="tab">Expenses by Kiosk</button>
        <button type="button" class="report-inner-tab"        data-tab="daily-sales" role="tab">Daily Sales</button>
        <button type="button" class="report-inner-tab"        data-tab="daily-exp"   role="tab">Daily Expenses</button>
        <button type="button" class="report-inner-tab"        data-tab="deliveries"  role="tab">Deliveries</button>
        <?php if ($has_anomalies): ?>
            <button type="button" class="report-inner-tab has-anomaly" data-tab="anomalies" role="tab">Anomalies</button>
        <?php endif; ?>
    </div>

    <!-- ======================= TAB 1: Sales by Kiosk ======================= -->
    <div class="card report-inner-panel active" data-tab="sales-kiosk">
        <h3>Sales by Kiosk</h3>
        <?php if (empty($sales_by_kiosk)): ?>
            <?php require __DIR__ . '/_empty_state.php'; ?>
        <?php else: ?>
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
                            <?php $pct = $max_kiosk_sales > 0 ? ($s['Total_Sales'] / $max_kiosk_sales) * 100 : 0; ?>
                            <tr>
                                <td><?= htmlspecialchars($s['Kiosk_Name']) ?></td>
                                <td><?= $s['Total_Transactions'] ?></td>
                                <td class="kiosk-bar-cell">
                                    <div class="kiosk-bar-wrap">
                                        <div class="kiosk-bar-bg">
                                            <div class="kiosk-bar-fill" style="width:<?= number_format($pct, 1) ?>%"></div>
                                        </div>
                                        <strong>P<?= number_format($s['Total_Sales'], 2) ?></strong>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ======================= TAB 2: Expenses by Kiosk ===================== -->
    <div class="card report-inner-panel" data-tab="exp-kiosk">
        <h3>Expenses by Kiosk</h3>
        <?php if (empty($expense_by_kiosk)): ?>
            <?php require __DIR__ . '/_empty_state.php'; ?>
        <?php else: ?>
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
                            <?php $pct = $max_kiosk_expenses > 0 ? ($e['Total_Expenses'] / $max_kiosk_expenses) * 100 : 0; ?>
                            <tr>
                                <td><?= htmlspecialchars($e['Kiosk_Name']) ?></td>
                                <td><?= $e['Total_Entries'] ?></td>
                                <td class="kiosk-bar-cell">
                                    <div class="kiosk-bar-wrap">
                                        <div class="kiosk-bar-bg">
                                            <div class="kiosk-bar-fill kiosk-bar-fill-expense" style="width:<?= number_format($pct, 1) ?>%"></div>
                                        </div>
                                        <strong>P<?= number_format($e['Total_Expenses'], 2) ?></strong>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ======================= TAB 3: Daily Sales (paginated) =============== -->
    <div class="card report-inner-panel" data-tab="daily-sales">
        <h3>Daily Sales Breakdown</h3>
        <?php if (empty($daily_sales)): ?>
            <?php require __DIR__ . '/_empty_state.php'; ?>
        <?php else: ?>
            <div class="table-container">
                <table class="report-paginated" data-page-size="10">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Kiosk</th>
                            <th>Transactions</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_sales as $ds): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($ds['Sales_date'])) ?></td>
                                <td><?= htmlspecialchars($ds['Kiosk_Name']) ?></td>
                                <td><?= $ds['Transactions'] ?></td>
                                <td><strong>P<?= number_format($ds['Day_Total'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="report-pagination" style="display:none;">
                <button type="button" class="btn btn-sm btn-outline" data-page="prev">&laquo; Prev</button>
                <span class="report-pagination-info"></span>
                <button type="button" class="btn btn-sm btn-outline" data-page="next">Next &raquo;</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- ======================= TAB 4: Daily Expenses (paginated) ============ -->
    <div class="card report-inner-panel" data-tab="daily-exp">
        <h3>Daily Expense Breakdown</h3>
        <?php if (empty($daily_expenses)): ?>
            <?php require __DIR__ . '/_empty_state.php'; ?>
        <?php else: ?>
            <div class="table-container">
                <table class="report-paginated" data-page-size="10">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Kiosk</th>
                            <th>Entries</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_expenses as $de): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($de['Expense_date'])) ?></td>
                                <td><?= htmlspecialchars($de['Kiosk_Name']) ?></td>
                                <td><?= $de['Entries'] ?></td>
                                <td><strong>P<?= number_format($de['Day_Total'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="report-pagination" style="display:none;">
                <button type="button" class="btn btn-sm btn-outline" data-page="prev">&laquo; Prev</button>
                <span class="report-pagination-info"></span>
                <button type="button" class="btn btn-sm btn-outline" data-page="next">Next &raquo;</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- ======================= TAB 5: Deliveries (paginated) ================ -->
    <div class="card report-inner-panel" data-tab="deliveries">
        <h3>Delivery Summary</h3>
        <?php if (empty($deliveries)): ?>
            <?php require __DIR__ . '/_empty_state.php'; ?>
        <?php else: ?>
            <div class="table-container">
                <table class="report-paginated" data-page-size="10">
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
                        <?php foreach ($deliveries as $del): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($del['Delivery_Date'])) ?></td>
                                <td><?= htmlspecialchars($del['Kiosk_Name']) ?></td>
                                <td><?= htmlspecialchars($del['Product_Name']) ?></td>
                                <td><span class="badge badge-category"><?= htmlspecialchars($del['Category_Name']) ?></span></td>
                                <td><strong><?= $del['Total_Qty'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="report-pagination" style="display:none;">
                <button type="button" class="btn btn-sm btn-outline" data-page="prev">&laquo; Prev</button>
                <span class="report-pagination-info"></span>
                <button type="button" class="btn btn-sm btn-outline" data-page="next">Next &raquo;</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- ======================= TAB 6: Anomalies (only if non-empty) ========= -->
    <?php if ($has_anomalies): ?>
        <div class="card report-inner-panel" data-tab="anomalies">
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
                                        if ($a['has_ending'] == 0)    $missing[] = 'Ending';
                                        echo implode(' &amp; ', $missing);
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
