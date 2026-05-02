<?php
    /*
     * Time-in report view — group records by date and pre-compute summary
     * stats. The controller still passes a flat $records array; we just
     * rearrange it into date-keyed buckets for the collapsible UI.
     */
    $grouped     = [];
    $unique_users  = [];
    $unique_kiosks = [];
    foreach (($records ?? []) as $r) {
        $d = date('Y-m-d', strtotime($r['Timestamp']));
        $grouped[$d][] = $r;
        $unique_users[$r['Username']]   = true;
        $unique_kiosks[$r['Kiosk_Name']] = true;
    }
    // Newest day first — matches how the flat list would have read.
    krsort($grouped);

    $total_checkins = count($records ?? []);
    $total_users    = count($unique_users);
    $total_kiosks   = count($unique_kiosks);
?>
<section class="reports">
    <div class="section-header">
        <h2>Reports</h2>
    </div>

    <?php require __DIR__ . '/tabs.php'; ?>

    <!-- Filters -->
    <div class="card form-card">
        <form method="GET" action="<?= BASE_URL ?>/reports/timein" class="form-inline-row">
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
                       value="<?= htmlspecialchars($from_date) ?>"
                       max="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label for="to_date">To</label>
                <input type="date" id="to_date" name="to_date" class="form-input"
                       value="<?= htmlspecialchars($to_date) ?>"
                       max="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group form-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>

    <!-- Summary stat cards -->
    <div class="timein-stats">
        <div class="timein-stat-card">
            <div class="stat-value"><?= $total_checkins ?></div>
            <div class="stat-label">Total Check-ins</div>
        </div>
        <div class="timein-stat-card">
            <div class="stat-value"><?= $total_users ?></div>
            <div class="stat-label">Unique Staff</div>
        </div>
        <div class="timein-stat-card">
            <div class="stat-value"><?= $total_kiosks ?></div>
            <div class="stat-label">Kiosks With Check-ins</div>
        </div>
    </div>

    <!-- Time-In Records -->
    <div class="card">
        <div class="section-header">
            <h3>Staff Attendance Records</h3>
            <div class="section-header-right">
                <?php if (!empty($grouped)): ?>
                    <button type="button" class="btn btn-sm btn-outline" id="timeinToggleAll" data-state="expanded">
                        Collapse All
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($grouped)): ?>
            <p class="text-center text-light" style="padding:24px 0;">No time-in records found for this period.</p>
        <?php else: ?>
            <div class="timein-groups" id="timeinGroups">
                <?php foreach ($grouped as $day => $rows): ?>
                    <div class="timein-date-group">
                        <div class="timein-date-header" data-toggle="timein-group">
                            <span><?= date('l, F j, Y', strtotime($day)) ?>
                                <span class="text-light">&middot; <?= count($rows) ?> check-in<?= count($rows) === 1 ? '' : 's' ?></span>
                            </span>
                            <span class="toggle-icon">&#9650;</span>
                        </div>
                        <div class="timein-date-body">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Staff Name</th>
                                            <th>Username</th>
                                            <th>Kiosk</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rows as $r): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($r['Full_name']) ?></td>
                                                <td><?= htmlspecialchars($r['Username']) ?></td>
                                                <td><?= htmlspecialchars($r['Kiosk_Name']) ?></td>
                                                <td><?= date('g:i A', strtotime($r['Timestamp'])) ?></td>
                                                <td>
                                                    <?php if (!empty($r['Time_out'])): ?>
                                                        <?= date('g:i A', strtotime($r['Time_out'])) ?>
                                                    <?php else: ?>
                                                        <span class="text-light">— still active</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Date-level pagination (7 day-groups per page) -->
            <div class="report-pagination" id="timeinPagination" style="display:none;">
                <button type="button" class="btn btn-sm btn-outline" data-page="prev">&laquo; Prev</button>
                <span class="report-pagination-info"></span>
                <button type="button" class="btn btn-sm btn-outline" data-page="next">Next &raquo;</button>
            </div>
        <?php endif; ?>
    </div>
</section>
