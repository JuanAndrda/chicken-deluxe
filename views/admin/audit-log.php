<?php
/**
 * Audit Log — Owner-only view of Audit_Log table.
 * Surfaces trigger-captured change data (Old_values / New_values JSON)
 * as an expandable diff panel per row.
 *
 * Expected data: $logs, $from_date, $to_date, $action, $total_rows,
 *                $total_pages, $current_page, $per_page.
 */

// Action => CSS badge color map
$action_colors = [
    'LOGIN'           => 'badge-blue',
    'LOGOUT'          => 'badge-gray',
    'CREATE'          => 'badge-green',
    'INSERT'          => 'badge-green',
    'UPDATE'          => 'badge-orange',
    'DELETE'          => 'badge-red',
    'LOCK'            => 'badge-slate',
    'UNLOCK'          => 'badge-amber',
    'RECORD_UNLOCKED' => 'badge-amber',
];

// Action filter dropdown (label => value). Uses label keys so duplicates are allowed
// (e.g. application-level CREATE vs trigger-level INSERT both map to different enums).
$action_options = [
    ''                => 'All Actions',
    'LOGIN'           => 'Login',
    'LOGOUT'          => 'Logout',
    'CREATE'          => 'Create (app)',
    'UPDATE'          => 'Update',
    'DELETE'          => 'Delete',
    'LOCK'            => 'Lock',
    'UNLOCK'          => 'Unlock',
    'RECORD_UNLOCKED' => 'Record Unlocked',
    'INSERT'          => 'DB Insert (trigger)',
];

// Quick-filter pill dates
$today       = date('Y-m-d');
$this_monday = date('Y-m-d', strtotime('monday this week'));
$last_30     = date('Y-m-d', strtotime('-30 days'));

// Helper: short-form any scalar JSON value for display
$diff_val = static function ($v): string {
    if ($v === null)              return 'NULL';
    if (is_bool($v))              return $v ? 'true' : 'false';
    if (is_array($v) || is_object($v)) $v = json_encode($v);
    $s = (string) $v;
    return strlen($s) > 50 ? substr($s, 0, 50) . '…' : $s;
};

// Helper: full-form for tooltip
$diff_full = static function ($v): string {
    if ($v === null)              return 'NULL';
    if (is_bool($v))              return $v ? 'true' : 'false';
    if (is_array($v) || is_object($v)) $v = json_encode($v);
    return (string) $v;
};

// Helper to preserve filters in pagination links
$build_page_url = static function (int $page) use ($from_date, $to_date, $action): string {
    $qs = http_build_query(array_filter([
        'from_date' => $from_date,
        'to_date'   => $to_date,
        'action'    => $action,
        'page'      => $page,
    ], static fn($v) => $v !== null && $v !== ''));
    return BASE_URL . '/admin/audit-log' . ($qs ? "?{$qs}" : '');
};

// Active-pill detection
$pill_active = static function (string $from, string $to) use ($from_date, $to_date): string {
    return ($from_date === $from && $to_date === $to) ? 'active' : '';
};

$start = $total_rows > 0 ? (($current_page - 1) * $per_page + 1) : 0;
$end   = min($current_page * $per_page, $total_rows);
?>

<section class="admin-audit">
    <div class="section-header">
        <h2>Audit Log</h2>
        <span class="audit-total-badge"><?= number_format($total_rows) ?> entries</span>
    </div>

    <!-- Filter bar -->
    <div class="card form-card">
        <form method="GET" action="<?= BASE_URL ?>/admin/audit-log" class="form-inline-row">
            <div class="form-group">
                <label for="from_date">From</label>
                <input type="date" id="from_date" name="from_date" class="form-input"
                       value="<?= htmlspecialchars($from_date ?? '') ?>"
                       max="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label for="to_date">To</label>
                <input type="date" id="to_date" name="to_date" class="form-input"
                       value="<?= htmlspecialchars($to_date ?? '') ?>"
                       max="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label for="action">Action</label>
                <select id="action" name="action" class="form-select">
                    <?php foreach ($action_options as $val => $label): ?>
                        <option value="<?= htmlspecialchars($val) ?>"
                                <?= $action === $val ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group form-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= BASE_URL ?>/admin/audit-log" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>

    <!-- Quick filter pills -->
    <div class="audit-pills">
        <a href="<?= BASE_URL ?>/admin/audit-log?from_date=<?= $today ?>&to_date=<?= $today ?>"
           class="audit-pill <?= $pill_active($today, $today) ?>">Today</a>
        <a href="<?= BASE_URL ?>/admin/audit-log?from_date=<?= $this_monday ?>&to_date=<?= $today ?>"
           class="audit-pill <?= $pill_active($this_monday, $today) ?>">This Week</a>
        <a href="<?= BASE_URL ?>/admin/audit-log?from_date=<?= $last_30 ?>&to_date=<?= $today ?>"
           class="audit-pill <?= $pill_active($last_30, $today) ?>">Last 30 Days</a>
    </div>

    <!-- Log table -->
    <div class="table-container">
        <table class="audit-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Details</th>
                    <th>Changes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center">No log entries match your filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                            $log_id    = (int) $log['Log_ID'];
                            $color     = $action_colors[$log['Action']] ?? 'badge-gray';
                            $old_raw   = $log['Old_values'] ?? null;
                            $new_raw   = $log['New_values'] ?? null;
                            $old       = $old_raw ? (json_decode($old_raw, true) ?: []) : [];
                            $new       = $new_raw ? (json_decode($new_raw, true) ?: []) : [];

                            $has_old   = !empty($old);
                            $has_new   = !empty($new);
                            $has_any   = $has_old || $has_new;

                            // Determine change badge type
                            if ($has_new && !$has_old)      { $chg_badge = 'badge-green';  $chg_txt = 'New record'; }
                            elseif ($has_new && $has_old)   { $chg_badge = 'badge-orange'; $chg_txt = 'Changed'; }
                            elseif ($has_old && !$has_new)  { $chg_badge = 'badge-red';    $chg_txt = 'Deleted record'; }
                            else                            { $chg_badge = null; }

                            // Truncated detail
                            $details_full  = $log['Details'] ?? '';
                            $details_short = strlen($details_full) > 60
                                ? substr($details_full, 0, 60) . '…'
                                : $details_full;
                        ?>
                        <tr data-log-id="<?= $log_id ?>">
                            <td><span class="text-light">#<?= $log_id ?></span></td>
                            <td><?= date('M j, Y g:i A', strtotime($log['Timestamp'])) ?></td>
                            <td>
                                <div><?= htmlspecialchars($log['Full_name'] ?? 'System') ?></div>
                                <?php if (!empty($log['Username'])): ?>
                                    <div class="text-light" style="font-size:11px;">
                                        @<?= htmlspecialchars($log['Username']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $color ?>">
                                    <?= htmlspecialchars($log['Action']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($log['Table_name'])): ?>
                                    <span class="audit-table-chip"><?= htmlspecialchars($log['Table_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-light">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td title="<?= htmlspecialchars($details_full) ?>">
                                <?= htmlspecialchars($details_short) ?: '<span class="text-light">&mdash;</span>' ?>
                            </td>
                            <td>
                                <?php if ($chg_badge === null): ?>
                                    <span class="text-light">&mdash;</span>
                                <?php else: ?>
                                    <span class="badge <?= $chg_badge ?>"><?= $chg_txt ?></span>
                                    <button type="button" class="audit-expand-btn"
                                            data-log-id="<?= $log_id ?>">&#9660;</button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php if ($has_any): ?>
                            <tr id="expand-<?= $log_id ?>" class="audit-expand-row" style="display:none;">
                                <td colspan="7">
                                    <div class="audit-diff-panels">
                                        <?php if ($has_old && $has_new): ?>
                                            <!-- UPDATE: side-by-side -->
                                            <div class="audit-diff-panel audit-diff-old">
                                                <div class="audit-diff-panel-header">Before</div>
                                                <?php foreach ($old as $k => $v): ?>
                                                    <?php
                                                        $changed = !array_key_exists($k, $new) || $new[$k] !== $v;
                                                    ?>
                                                    <div class="audit-diff-row <?= $changed ? 'audit-diff-changed' : '' ?>">
                                                        <span class="audit-diff-key"><?= htmlspecialchars((string) $k) ?></span>
                                                        <span class="audit-diff-val"
                                                              title="<?= htmlspecialchars($diff_full($v)) ?>">
                                                            <?= htmlspecialchars($diff_val($v)) ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="audit-diff-panel audit-diff-new">
                                                <div class="audit-diff-panel-header">After</div>
                                                <?php foreach ($new as $k => $v): ?>
                                                    <?php
                                                        $changed = !array_key_exists($k, $old) || $old[$k] !== $v;
                                                    ?>
                                                    <div class="audit-diff-row <?= $changed ? 'audit-diff-changed' : '' ?>">
                                                        <span class="audit-diff-key"><?= htmlspecialchars((string) $k) ?></span>
                                                        <span class="audit-diff-val"
                                                              title="<?= htmlspecialchars($diff_full($v)) ?>">
                                                            <?= htmlspecialchars($diff_val($v)) ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                        <?php elseif ($has_new): ?>
                                            <!-- INSERT: single green panel -->
                                            <div class="audit-diff-panel audit-diff-new">
                                                <div class="audit-diff-panel-header">New Record</div>
                                                <?php foreach ($new as $k => $v): ?>
                                                    <div class="audit-diff-row">
                                                        <span class="audit-diff-key"><?= htmlspecialchars((string) $k) ?></span>
                                                        <span class="audit-diff-val"
                                                              title="<?= htmlspecialchars($diff_full($v)) ?>">
                                                            <?= htmlspecialchars($diff_val($v)) ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                        <?php else: ?>
                                            <!-- DELETE: single red panel -->
                                            <div class="audit-diff-panel audit-diff-old">
                                                <div class="audit-diff-panel-header">Deleted Record</div>
                                                <?php foreach ($old as $k => $v): ?>
                                                    <div class="audit-diff-row">
                                                        <span class="audit-diff-key"><?= htmlspecialchars((string) $k) ?></span>
                                                        <span class="audit-diff-val"
                                                              title="<?= htmlspecialchars($diff_full($v)) ?>">
                                                            <?= htmlspecialchars($diff_val($v)) ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="audit-pagination">
            <span class="audit-pagination-info">
                Showing <?= $start ?>&ndash;<?= $end ?> of <?= number_format($total_rows) ?> entries
            </span>
            <div class="audit-pagination-pages">
                <?php
                    $prev_disabled = $current_page <= 1 ? 'disabled' : '';
                    $next_disabled = $current_page >= $total_pages ? 'disabled' : '';
                    // Show max 5 page links centered on current
                    $window_start = max(1, $current_page - 2);
                    $window_end   = min($total_pages, $window_start + 4);
                    $window_start = max(1, $window_end - 4);
                ?>
                <a class="audit-page-btn <?= $prev_disabled ?>"
                   href="<?= $build_page_url(max(1, $current_page - 1)) ?>">Previous</a>

                <?php if ($window_start > 1): ?>
                    <a class="audit-page-btn" href="<?= $build_page_url(1) ?>">1</a>
                    <?php if ($window_start > 2): ?>
                        <span class="text-light">…</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($p = $window_start; $p <= $window_end; $p++): ?>
                    <a class="audit-page-btn <?= $p === $current_page ? 'active' : '' ?>"
                       href="<?= $build_page_url($p) ?>"><?= $p ?></a>
                <?php endfor; ?>

                <?php if ($window_end < $total_pages): ?>
                    <?php if ($window_end < $total_pages - 1): ?>
                        <span class="text-light">…</span>
                    <?php endif; ?>
                    <a class="audit-page-btn" href="<?= $build_page_url($total_pages) ?>"><?= $total_pages ?></a>
                <?php endif; ?>

                <a class="audit-page-btn <?= $next_disabled ?>"
                   href="<?= $build_page_url(min($total_pages, $current_page + 1)) ?>">Next</a>
            </div>
        </div>
    <?php endif; ?>
</section>

<script>
    document.querySelectorAll('.audit-expand-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const logId = this.dataset.logId;
            const expandRow = document.getElementById('expand-' + logId);
            if (!expandRow) return;
            const isOpen = expandRow.style.display !== 'none';
            expandRow.style.display = isOpen ? 'none' : 'table-row';
            this.innerHTML = isOpen ? '&#9660;' : '&#9650;';
        });
    });
</script>
