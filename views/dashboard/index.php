<?php
/**
 * Dashboard view — role-aware.
 * Owner   → multi-kiosk overview (stats, kiosk-status grid, alert banner)
 * Staff   → own-kiosk progress + contextual action callout
 * Auditor → minimal landing card pointing to Reports
 */
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

// Sales formatter helper (peso symbol + 2 decimals)
$fmt = static function (float $n): string {
    return '&#8369;' . number_format($n, 2);
};
?>

<section class="dashboard">

<?php /* ============================================================
        OWNER DASHBOARD
        ============================================================ */ ?>
<?php if (Auth::isOwner()): ?>
    <?php
        $missing_actions = ($kiosks_missing_beginning ?? 0) + ($kiosks_missing_ending ?? 0);
        // Stat color logic
        $sales_class = ($grand_total_today ?? 0) > 0 ? 'card-green' : 'card-gray';
        if ($total_kiosks > 0 && $kiosks_have_beginning === $total_kiosks) {
            $beg_class = 'card-green';
        } elseif ($kiosks_have_beginning > 0) {
            $beg_class = 'card-amber';
        } else {
            $beg_class = 'card-red';
        }
        if ($total_kiosks > 0 && $kiosks_have_ending === $total_kiosks) {
            $end_class = 'card-green';
        } elseif ($kiosks_have_ending > 0) {
            $end_class = 'card-amber';
        } else {
            $end_class = 'card-gray';
        }
        $action_class = $missing_actions > 0 ? 'card-red' : 'card-green';

        // Map sales totals by Kiosk_ID for fast lookup in the status grid
        $sales_map = [];
        foreach (($today_sales_by_kiosk ?? []) as $row) {
            $sales_map[(int) $row['Kiosk_ID']] = (float) $row['Day_Total'];
        }
    ?>

    <!-- Section 1: Welcome + Date -->
    <div class="dash-welcome">
        <h2><?= $greet ?>, <?= htmlspecialchars(Auth::fullName() ?? '') ?>!</h2>
        <span class="dash-date"><?= htmlspecialchars($today_pretty) ?></span>
    </div>

    <!-- Section 2: Summary Stat Cards -->
    <div class="dash-stat-cards">
        <div class="dash-stat-card <?= $sales_class ?>">
            <div class="dash-stat-value"><?= $fmt((float) $grand_total_today) ?></div>
            <div class="dash-stat-label">Across all kiosks today</div>
        </div>
        <div class="dash-stat-card <?= $beg_class ?>">
            <div class="dash-stat-value"><?= $kiosks_have_beginning ?> of <?= $total_kiosks ?></div>
            <div class="dash-stat-label">Have recorded beginning stock</div>
        </div>
        <div class="dash-stat-card <?= $end_class ?>">
            <div class="dash-stat-value"><?= $kiosks_have_ending ?> of <?= $total_kiosks ?></div>
            <div class="dash-stat-label">Have recorded ending stock</div>
        </div>
        <div class="dash-stat-card <?= $action_class ?>">
            <div class="dash-stat-value">
                <?= $missing_actions > 0 ? $missing_actions : 'All good' ?>
            </div>
            <div class="dash-stat-label">
                <?= $missing_actions > 0 ? 'Items need attention' : 'No pending items' ?>
            </div>
        </div>
    </div>

    <!-- Section 3: Kiosk Status Grid -->
    <div class="card">
        <h3>Kiosk Status &mdash; Today</h3>
        <table class="data-table kiosk-status-table">
            <thead>
                <tr>
                    <th>Kiosk Name</th>
                    <th>Today's Sales</th>
                    <th>Beginning Stock</th>
                    <th>Ending Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($kiosk_inventory_status ?? []) as $row): ?>
                    <?php
                        $kid     = (int) $row['Kiosk_ID'];
                        $sales   = $sales_map[$kid] ?? 0.0;
                        $has_b   = (int) $row['has_beginning'] === 1;
                        $has_e   = (int) $row['has_ending'] === 1;

                        if (!$has_b && !$has_e) {
                            $badge_cls = 'badge-notstarted'; $badge_txt = 'Not Started';
                        } elseif ($has_b && !$has_e) {
                            $badge_cls = 'badge-progress';   $badge_txt = 'In Progress';
                        } else {
                            $badge_cls = 'badge-closed';     $badge_txt = 'Closed';
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Kiosk_Name']) ?></td>
                        <td>
                            <?php if ($sales > 0): ?>
                                <?= $fmt($sales) ?>
                            <?php else: ?>
                                <span class="text-light"><?= $fmt(0.0) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $has_b
                                ? '<span class="status-check">&#10003;</span>'
                                : '<span class="status-dash">&mdash;</span>' ?>
                        </td>
                        <td>
                            <?= $has_e
                                ? '<span class="status-check">&#10003;</span>'
                                : '<span class="status-dash">&mdash;</span>' ?>
                        </td>
                        <td><span class="badge <?= $badge_cls ?>"><?= $badge_txt ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Section 4: Alert Banner -->
    <?php if (($kiosks_missing_beginning ?? 0) > 0 || ($kiosks_missing_ending ?? 0) > 0): ?>
        <div class="dash-alert">
            &#9888;
            <?php if ($kiosks_missing_beginning > 0): ?>
                <strong><?= $kiosks_missing_beginning ?></strong>
                kiosk<?= $kiosks_missing_beginning === 1 ? '' : 's' ?> have not recorded beginning stock today.
                These kiosks cannot track inventory until staff records opening count.
            <?php else: ?>
                <strong><?= $kiosks_missing_ending ?></strong>
                kiosk<?= $kiosks_missing_ending === 1 ? '' : 's' ?> still need to record ending stock for today.
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/inventory">View Inventory &rarr;</a>
        </div>
    <?php endif; ?>

    <!-- Section 5: Navigation Cards -->
    <div class="dashboard-cards">
        <div class="card">
            <h3>Record Stock</h3>
            <p>Beginning &amp; Ending Count</p>
            <a href="<?= BASE_URL ?>/inventory" class="btn btn-primary">Open</a>
        </div>
        <div class="card">
            <h3>Record Sales</h3>
            <p>POS &mdash; Enter Today's Transactions</p>
            <a href="<?= BASE_URL ?>/sales" class="btn btn-primary">Open</a>
        </div>
        <div class="card">
            <h3>Log Delivery</h3>
            <p>Incoming Stock From Suppliers</p>
            <a href="<?= BASE_URL ?>/delivery" class="btn btn-primary">Open</a>
        </div>
        <div class="card">
            <h3>Add Expense</h3>
            <p>Utilities, Supplies, Other Costs</p>
            <a href="<?= BASE_URL ?>/expenses" class="btn btn-primary">Open</a>
        </div>
        <div class="card">
            <h3>View Reports</h3>
            <p>Daily, Weekly &amp; Monthly Summaries</p>
            <a href="<?= BASE_URL ?>/reports" class="btn btn-primary">Open</a>
        </div>
        <div class="card">
            <h3>Admin Panel</h3>
            <p>Users, Products, Kiosks</p>
            <a href="<?= BASE_URL ?>/admin" class="btn btn-primary">Open</a>
        </div>
    </div>

<?php /* ============================================================
        STAFF DASHBOARD
        ============================================================ */ ?>
<?php elseif (Auth::isStaff()): ?>
    <?php
        $sales_class = ($today_sales_total ?? 0) > 0 ? 'card-green' : 'card-gray';
        $beg_class   = !empty($has_beginning_today) ? 'card-green' : 'card-amber';
        $end_class   = !empty($has_ending_today)    ? 'card-green' : 'card-gray';
    ?>

    <!-- Section 1: Welcome + Kiosk Context -->
    <div class="dash-welcome">
        <div>
            <h2><?= $greet ?>, <?= htmlspecialchars(Auth::fullName() ?? '') ?>!</h2>
            <p style="margin:4px 0 0 0; font-size:14px;">
                Your Kiosk: <strong><?= htmlspecialchars($kiosk_name ?? 'Unassigned') ?></strong>
            </p>
        </div>
        <span class="dash-date"><?= htmlspecialchars($today_pretty) ?></span>
    </div>

    <!-- Section 2: Today's Progress -->
    <div class="dash-stat-cards">
        <div class="dash-stat-card <?= $sales_class ?>">
            <div class="dash-stat-value"><?= $fmt((float) ($today_sales_total ?? 0)) ?></div>
            <div class="dash-stat-label">Sales recorded today</div>
        </div>
        <div class="dash-stat-card <?= $beg_class ?>">
            <div class="dash-stat-value">
                <?= !empty($has_beginning_today) ? '&#10003; Recorded' : 'Not Yet' ?>
            </div>
            <div class="dash-stat-label">Beginning inventory</div>
        </div>
        <div class="dash-stat-card <?= $end_class ?>">
            <div class="dash-stat-value">
                <?= !empty($has_ending_today) ? '&#10003; Recorded' : 'Not Yet' ?>
            </div>
            <div class="dash-stat-label">Ending inventory</div>
        </div>
    </div>

    <!-- Section 3: Action Callout (priority: first applicable) -->
    <?php if (empty($has_beginning_today)): ?>
        <div class="dash-callout callout-blue">
            <div class="dash-callout-text">
                &#128230; <strong>Start your day by recording beginning stock.</strong>
                <?php if (!empty($previous_ending_available)): ?>
                    <p>Yesterday's ending stock is ready to carry forward automatically.</p>
                <?php else: ?>
                    <p>Enter today's opening inventory count.</p>
                <?php endif; ?>
            </div>
            <a href="<?= BASE_URL ?>/inventory" class="btn btn-primary">
                <?= !empty($previous_ending_available)
                    ? 'Auto-Generate Stock &rarr;'
                    : 'Record Beginning Stock &rarr;' ?>
            </a>
        </div>
    <?php elseif (empty($has_ending_today)): ?>
        <div class="dash-callout callout-amber">
            <div class="dash-callout-text">
                &#128202; <strong>Don't forget to record ending stock before closing.</strong>
                <p>Closing count locks the day and feeds tomorrow's beginning stock.</p>
            </div>
            <a href="<?= BASE_URL ?>/inventory" class="btn btn-primary">Record Ending Stock &rarr;</a>
        </div>
    <?php else: ?>
        <div class="dash-callout callout-green">
            <div class="dash-callout-text">
                &#9989; <strong>All inventory recorded for today. Great work!</strong>
                <p>You can keep adding sales, deliveries, and expenses anytime.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Section 4: Navigation Cards (4 only — no Reports/Admin) -->
    <div class="dashboard-cards">
        <div class="card">
            <h3>Record Sales</h3>
            <p>POS &mdash; Enter today's transactions</p>
            <a href="<?= BASE_URL ?>/sales" class="btn btn-primary">Open</a>
        </div>
        <div class="card">
            <h3>Log Delivery</h3>
            <p>Incoming stock from suppliers</p>
            <a href="<?= BASE_URL ?>/delivery" class="btn btn-primary">Open</a>
        </div>
        <div class="card">
            <h3>Record Expenses</h3>
            <p>Utilities, supplies, other costs</p>
            <a href="<?= BASE_URL ?>/expenses" class="btn btn-primary">Open</a>
        </div>
        <div class="card">
            <h3>Inventory</h3>
            <p>Beginning &amp; ending count</p>
            <a href="<?= BASE_URL ?>/inventory" class="btn btn-primary">Open</a>
        </div>
    </div>

<?php /* ============================================================
        AUDITOR DASHBOARD
        ============================================================ */ ?>
<?php elseif (Auth::isAuditor()): ?>
    <div class="dash-welcome">
        <h2>Welcome, <?= htmlspecialchars(Auth::fullName() ?? '') ?>! &mdash; Auditor View</h2>
    </div>
    <p class="text-light">
        Today: <?= htmlspecialchars($today_pretty) ?>
        | Monitoring <strong><?= (int) ($total_kiosks ?? 0) ?></strong> active kiosks
    </p>

    <div class="dashboard-cards">
        <div class="card">
            <h3>View Reports</h3>
            <p>Daily summaries, consolidated reports, and audit trails</p>
            <a href="<?= BASE_URL ?>/reports" class="btn btn-primary">Open</a>
        </div>
    </div>

<?php else: ?>
    <p class="text-light">No dashboard available for your role.</p>
<?php endif; ?>

</section>
