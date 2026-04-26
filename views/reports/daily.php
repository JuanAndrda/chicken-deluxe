<?php
    /*
     * Daily report view — pre-compute per-category groupings + discrepancy
     * counts here so the rendering loop stays clean. The controller still
     * passes $report as a flat list; we just bucket it by Category_Name
     * for the sub-tab UI.
     */
    $by_category    = [];
    $disc_categories = [];   // categories that contain at least one discrepant row
    $disc_count      = 0;    // total discrepant rows (for the banner)
    foreach (($report ?? []) as $row) {
        $cat = $row['Category_Name'];
        $by_category[$cat][] = $row;
        if ((int) $row['Discrepancy'] !== 0) {
            $disc_categories[$cat] = true;
            $disc_count++;
        }
    }
    $categories = array_keys($by_category);
?>
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
                       value="<?= htmlspecialchars($date) ?>"
                       max="<?= date('Y-m-d') ?>"
                       onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <!-- Daily Summary -->
    <div class="card">
        <div class="section-header">
            <h3>Daily Summary &mdash; <?= htmlspecialchars($kiosk['Name'] ?? '') ?></h3>
            <div class="section-header-right">
                <span class="total-display">Total Sales: <strong>P<?= number_format($total_sales, 2) ?></strong></span>
                <?php if ($has_discrepancy): ?>
                    <span class="badge badge-locked">Has Discrepancies</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($has_discrepancy && $disc_count > 0): ?>
            <div class="report-discrepancy-banner">
                &#9888; Discrepancies found in <strong><?= $disc_count ?></strong>
                product<?= $disc_count === 1 ? '' : 's' ?>. Check highlighted rows.
            </div>
        <?php endif; ?>

        <?php if (!empty($report)): ?>
            <!-- Category sub-tabs -->
            <div class="report-cat-tabs" id="dailyCatTabs" role="tablist">
                <button type="button" class="report-cat-tab active" data-category="__all__" role="tab">All</button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button"
                            class="report-cat-tab<?= isset($disc_categories[$cat]) ? ' has-discrepancy' : '' ?>"
                            data-category="<?= htmlspecialchars($cat) ?>" role="tab">
                        <?= htmlspecialchars($cat) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table id="dailyReportTable">
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
                            <tr class="report-row <?= $row['Discrepancy'] !== 0 ? 'row-warning' : '' ?>"
                                data-category="<?= htmlspecialchars($row['Category_Name']) ?>"
                                data-sales="<?= (float) $row['Sales_Total'] ?>">
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
                        <!-- Totals row (kept inside <tbody>; JS keeps it pinned to the visible bottom) -->
                        <tr class="report-totals-row" id="dailyTotalsRow">
                            <td colspan="6" class="text-right"><strong>Total Sales (visible)</strong></td>
                            <td colspan="4"><strong>P<span id="dailyTotalsValue">0.00</span></strong></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination (rendered by JS into this container) -->
        <div class="report-pagination" id="dailyPagination" style="display:none;">
            <button type="button" class="btn btn-sm btn-outline" data-page="prev">&laquo; Prev</button>
            <span class="report-pagination-info"></span>
            <button type="button" class="btn btn-sm btn-outline" data-page="next">Next &raquo;</button>
        </div>

        <?php if (!empty($report)): ?>
            <p class="report-note">
                Expected = Beginning + Delivered &minus; Sold. Discrepancy = Ending &minus; Expected.
                Negative = stock shortage, Positive = excess.
            </p>
        <?php endif; ?>
    </div>
</section>
