<?php
    $staff_locked = Auth::isStaff();

    /* ============================================================
       Step-tab layout: visibility + auto-focus
       ============================================================ */
    // Running widget renders only when these conditions hold (preserved
    // from the original implementation). Running tab is hidden when false.
    $has_running = $has_beginning && !$has_ending && !empty($running_inventory ?? []);

    // Pick which tab to open on page load
    if ($has_ending) {
        $active_step = 'ending';        // Day complete — review mode
    } elseif ($has_beginning) {
        $active_step = $has_running ? 'running' : 'ending';
    } else {
        $active_step = 'beginning';     // Day starts here
    }

    // Counts for the mini status strip (running widget only)
    $running_warn_count = 0;
    $running_total_units = 0;
    if ($has_running) {
        foreach ($running_inventory as $r) {
            $q = (int) $r['Running_Qty'];
            $running_total_units += max(0, $q);
            if ($q <= 5) $running_warn_count++;
        }
    }

    $total_parts = count($parts ?? []);
?>
<section class="inventory">
    <div class="section-header">
        <h2>Inventory — <?= htmlspecialchars($kiosk['Name'] ?? 'Unknown') ?></h2>
        <span class="date-display"><?= date('l, F j, Y', strtotime($date)) ?></span>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($date > date('Y-m-d')): ?>
        <div class="alert alert-warning">
            ⏳ <strong><?= date('M j, Y', strtotime($date)) ?></strong> hasn't happened yet — you can't record inventory for future dates. Pick today or an earlier day.
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card form-card">
        <form method="GET" action="<?= BASE_URL ?>/inventory" class="form-inline-row">
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
                       value="<?= htmlspecialchars($date) ?>"
                       max="<?= date('Y-m-d') ?>"
                       onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <!-- History Quick Links -->
    <?php if (!empty($history)): ?>
        <div class="card">
            <h3>Recent Records</h3>
            <div class="history-links">
                <?php foreach (array_slice($history, 0, 7) as $record): ?>
                    <?php
                        $is_locked   = (int) $record['is_locked'] === 1;
                        $cf_icon     = $is_locked ? '&#x2713;' : '&rarr;';
                        $cf_class    = $is_locked ? 'cf-locked' : 'cf-open';
                        $cf_title    = $is_locked ? 'Ending locked — carry-forward source' : 'In progress';
                    ?>
                    <a href="<?= BASE_URL ?>/inventory?kiosk_id=<?= $kiosk_id ?>&date=<?= $record['Snapshot_date'] ?>"
                       class="btn btn-sm <?= $record['Snapshot_date'] === $date ? 'btn-primary' : 'btn-outline' ?>"
                       title="<?= $cf_title ?>">
                        <?= date('M j', strtotime($record['Snapshot_date'])) ?>
                        <span class="cf-indicator <?= $cf_class ?>"><?= $cf_icon ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         STEP-TAB WRAPPER — Beginning / Running / Ending
         ============================================================ -->
    <div class="card inventory-step-wrapper">

        <!-- Tab nav -->
        <div class="inventory-step-nav" role="tablist">
            <button type="button"
                    class="inventory-step-btn <?= $active_step === 'beginning' ? 'active' : '' ?>"
                    data-step="beginning" role="tab">
                <span class="inventory-step-num">1</span>
                <span class="inventory-step-label">Beginning Stock</span>
                <?php if ($has_beginning): ?>
                    <span class="inventory-step-badge step-badge-done">&#x2713;</span>
                <?php elseif ($is_today): ?>
                    <span class="inventory-step-badge step-badge-pending">to do</span>
                <?php else: ?>
                    <span class="inventory-step-badge step-badge-empty">&mdash;</span>
                <?php endif; ?>
            </button>

            <?php if ($has_running): ?>
                <button type="button"
                        class="inventory-step-btn <?= $active_step === 'running' ? 'active' : '' ?>"
                        data-step="running" role="tab">
                    <span class="inventory-step-num">2</span>
                    <span class="inventory-step-label">Running Inventory <span class="text-light">(live)</span></span>
                    <?php if ($running_warn_count > 0): ?>
                        <span class="inventory-step-badge step-badge-warn">&#9888; <?= $running_warn_count ?></span>
                    <?php endif; ?>
                </button>
            <?php endif; ?>

            <button type="button"
                    class="inventory-step-btn <?= $active_step === 'ending' ? 'active' : '' ?>"
                    data-step="ending" role="tab">
                <span class="inventory-step-num"><?= $has_running ? 3 : 2 ?></span>
                <span class="inventory-step-label">Ending Stock</span>
                <?php if ($has_ending): ?>
                    <span class="inventory-step-badge step-badge-done">&#x2713;</span>
                <?php elseif ($is_today && $has_beginning): ?>
                    <span class="inventory-step-badge step-badge-pending">to do</span>
                <?php else: ?>
                    <span class="inventory-step-badge step-badge-empty">&mdash;</span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Mini status strip -->
        <div class="inventory-step-status">
            <span class="step-status-item">
                <strong>Beginning:</strong>
                <?php if ($has_beginning): ?>
                    <span class="text-success">&#x2713; done</span>
                <?php elseif ($is_today): ?>
                    <span class="text-warning">not yet</span>
                <?php else: ?>
                    <span class="text-light">&mdash;</span>
                <?php endif; ?>
            </span>
            <span class="step-status-divider">&middot;</span>
            <span class="step-status-item">
                <strong>Running:</strong>
                <?php if ($has_running): ?>
                    <?= $running_total_units ?> units left
                    <?php if ($running_warn_count > 0): ?>
                        (<?= $running_warn_count ?> low)
                    <?php endif; ?>
                <?php elseif ($has_ending): ?>
                    <span class="text-light">day closed</span>
                <?php else: ?>
                    <span class="text-light">&mdash;</span>
                <?php endif; ?>
            </span>
            <span class="step-status-divider">&middot;</span>
            <span class="step-status-item">
                <strong>Ending:</strong>
                <?php if ($has_ending): ?>
                    <span class="text-success">&#x2713; locked</span>
                <?php elseif ($is_today && $has_beginning): ?>
                    <span class="text-warning">not yet</span>
                <?php else: ?>
                    <span class="text-light">&mdash;</span>
                <?php endif; ?>
            </span>
        </div>

        <!-- ====================== STEP 1: BEGINNING ====================== -->
        <div class="inventory-step-panel <?= $active_step === 'beginning' ? 'active' : '' ?>"
             id="step-beginning" role="tabpanel">

        <?php if ($has_beginning): ?>
            <!-- Saved view: flat parts list -->
            <div class="inventory-saved-view inventory-flat">
                <h4 class="inventory-flat-heading">All Parts (<?= count($beginning) ?>)</h4>
                <?php foreach ($beginning as $row): ?>
                    <?php $show_locked = $staff_locked || $row['Locked_status']; ?>
                    <div class="inventory-product-row">
                        <div class="inventory-product-name"><?= htmlspecialchars($row['Part_Name']) ?></div>
                        <div class="inventory-product-unit"><?= htmlspecialchars($row['Unit']) ?></div>
                        <div class="inventory-saved-qty"><?= $row['Quantity'] ?></div>
                        <div class="inventory-saved-status">
                            <span class="badge <?= $show_locked ? 'badge-locked' : 'badge-active' ?>">
                                <?= $show_locked ? 'Locked' : 'Open' ?>
                            </span>
                        </div>
                        <?php if (Auth::isOwner()): ?>
                            <div class="inventory-saved-action">
                                <?php if ($row['Locked_status']): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/inventory/unlock" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                        <input type="hidden" name="inventory_id" value="<?= $row['Inventory_ID'] ?>">
                                        <input type="hidden" name="date" value="<?= $date ?>">
                                        <button type="button" class="btn btn-sm btn-outline"
                                                onclick="showConfirmModal({
                                                    title: 'Unlock Record',
                                                    message: 'Are you sure you want to unlock this inventory record? This will allow editing of past data.',
                                                    confirmText: 'Yes, Unlock',
                                                    type: 'unlock',
                                                    onConfirm: () => this.closest('form').submit()
                                                })">Unlock</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-light">&mdash;</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($is_today): ?>
            <!-- Perpetual: Option A = auto-generate / Option B = manual -->
            <?php if ($has_previous_ending): ?>
                <div class="perpetual-option perpetual-option-a">
                    <div class="perpetual-option-header">
                        <span class="perpetual-badge">Option A &mdash; Recommended</span>
                        <h4>Auto-Generate from <?= date('l, M j', strtotime($previous_date)) ?></h4>
                    </div>
                    <p class="text-light">
                        Yesterday's ending stock will be used as today's beginning.
                        You can edit any quantity afterwards if a physical recount is needed.
                    </p>

                    <!-- Preview: top 5 parts from previous ending -->
                    <div class="perpetual-preview">
                        <div class="perpetual-preview-title">Preview (top 5 of <?= count($previous_ending) ?>):</div>
                        <ul class="perpetual-preview-list">
                            <?php foreach (array_slice($previous_ending, 0, 5) as $prev): ?>
                                <li>
                                    <span class="perpetual-preview-name"><?= htmlspecialchars($prev['Part_Name']) ?></span>
                                    <span class="perpetual-preview-qty"><?= (int) $prev['Quantity'] ?> <?= htmlspecialchars($prev['Unit']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <form method="POST" action="<?= BASE_URL ?>/inventory/auto-generate" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                        <input type="hidden" name="kiosk_id" value="<?= $kiosk_id ?>">
                        <input type="hidden" name="date" value="<?= $date ?>">
                        <button type="button" class="btn btn-primary"
                                onclick="showConfirmModal({
                                    title: 'Auto-Generate Beginning Stock',
                                    message: 'Use yesterday\'s ending stock as today\'s beginning? You can still edit individual quantities afterwards.',
                                    confirmText: 'Yes, Auto-Generate',
                                    type: 'submit',
                                    onConfirm: () => this.closest('form').submit()
                                })">
                            Auto-Generate Beginning Stock
                        </button>
                    </form>
                </div>

                <button type="button" class="btn btn-outline btn-sm perpetual-toggle-manual"
                        onclick="document.getElementById('manual-beginning-form').classList.toggle('is-open'); this.classList.toggle('is-open');">
                    Or enter manually instead &#9662;
                </button>
            <?php else: ?>
                <div class="perpetual-option perpetual-option-empty">
                    <p class="text-light">
                        No previous locked ending stock found. Enter today's beginning stock manually below.
                    </p>
                </div>
            <?php endif; ?>

            <!-- Beginning Stock Form — manual / Option B  (FLAT parts list) -->
            <div id="manual-beginning-form" class="perpetual-manual-form<?= !$has_previous_ending ? ' is-open' : '' ?>">
            <form method="POST" action="<?= BASE_URL ?>/inventory/store"
                  class="inventory-entry-form" data-total="<?= $total_parts ?>"
                  data-label="Save Beginning Stock">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                <input type="hidden" name="snapshot_type" value="beginning">
                <input type="hidden" name="kiosk_id" value="<?= $kiosk_id ?>">
                <input type="hidden" name="date" value="<?= $date ?>">

                <div class="inventory-progress-label">
                    <span class="inventory-filled-count">0</span> of <?= $total_parts ?> parts filled
                </div>
                <div class="inventory-progress">
                    <div class="inventory-progress-bar" style="width:0%"></div>
                </div>

                <div class="inventory-flat">
                    <h4 class="inventory-flat-heading">All Parts (<?= $total_parts ?>)</h4>
                    <?php foreach ($parts as $part): ?>
                        <div class="inventory-product-row">
                            <div class="inventory-product-name"><?= htmlspecialchars($part['Name']) ?></div>
                            <div class="inventory-product-unit"><?= htmlspecialchars($part['Unit']) ?></div>
                            <div class="inventory-qty-control">
                                <button type="button" class="inventory-qty-btn" data-action="dec" aria-label="Decrease">&minus;</button>
                                <input type="number"
                                       name="qty[<?= $part['Part_ID'] ?>]"
                                       class="inventory-qty-input"
                                       min="0" value="0" required>
                                <button type="button" class="inventory-qty-btn" data-action="inc" aria-label="Increase">+</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="inventory-sticky-submit">
                    <button type="button" class="btn btn-primary inventory-submit-btn"
                            onclick="showConfirmModal({
                                title: 'Submit Beginning Stock',
                                message: 'Are you sure you want to submit the beginning stock for today? Please double-check all quantities before confirming.',
                                confirmText: 'Yes, Submit',
                                type: 'submit',
                                onConfirm: () => this.closest('form').submit()
                            })">
                        Save Beginning Stock (<span class="inventory-filled-count">0</span> of <?= $total_parts ?> filled)
                    </button>
                </div>
            </form>
            </div><!-- /#manual-beginning-form -->
        <?php else: ?>
            <p class="text-light">No beginning stock recorded for this date.</p>
        <?php endif; ?>

        </div><!-- /#step-beginning -->

        <!-- ====================== STEP 2: RUNNING ====================== -->
        <?php if ($has_running): ?>
            <?php
                $low_count = 0; $zero_count = 0; $neg_count = 0;
                foreach ($running_inventory as $r) {
                    $q = (int) $r['Running_Qty'];
                    if ($q < 0)       $neg_count++;
                    elseif ($q === 0) $zero_count++;
                    elseif ($q <= 5)  $low_count++;
                }
            ?>
            <div class="inventory-step-panel <?= $active_step === 'running' ? 'active' : '' ?>"
                 id="step-running" role="tabpanel">

                <div class="running-inventory-header">
                    <p class="text-light running-inventory-formula" style="margin:0;">
                        Beginning + Delivered &minus; Pulled out &minus; Used by sales = Running stock
                    </p>
                    <div class="running-inventory-stats">
                        <span class="running-stat running-stat-neg" title="Negative — sold more than available">&#9888; <?= $neg_count ?></span>
                        <span class="running-stat running-stat-zero" title="Out of stock">&#8709; <?= $zero_count ?></span>
                        <span class="running-stat running-stat-low" title="Low stock (1-5)">&#9888; <?= $low_count ?></span>
                    </div>
                </div>

                <div class="inventory-saved-view inventory-flat">
                    <h4 class="inventory-flat-heading">All Parts (<?= count($running_inventory) ?>)</h4>
                    <?php foreach ($running_inventory as $r): ?>
                        <?php
                            $q = (int) $r['Running_Qty'];
                            $row_class = '';
                            if ($q < 0)       $row_class = 'running-row-negative';
                            elseif ($q === 0) $row_class = 'running-row-zero';
                            elseif ($q <= 5)  $row_class = 'running-row-low';
                        ?>
                        <div class="inventory-product-row running-product-row <?= $row_class ?>">
                            <div class="inventory-product-name"><?= htmlspecialchars($r['Part_Name']) ?></div>
                            <div class="inventory-product-unit"><?= htmlspecialchars($r['Unit']) ?></div>
                            <div class="running-breakdown">
                                <span title="Beginning"><?= (int) $r['Beginning_Qty'] ?></span>
                                <span class="running-op">+</span>
                                <span title="Delivered today" class="running-delivered"><?= (int) $r['Delivered_Qty'] ?></span>
                                <span class="running-op">&minus;</span>
                                <span title="Pulled out today (expired / returned)" class="running-pullout"><?= (int) ($r['Pullout_Qty'] ?? 0) ?></span>
                                <span class="running-op">&minus;</span>
                                <span title="Used by sales today" class="running-sold"><?= (int) $r['Used_Qty'] ?></span>
                                <span class="running-op">=</span>
                            </div>
                            <div class="running-qty-final"><?= $q ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Quick action: jump to ending tab to close the day -->
                <div class="inventory-step-jump">
                    <button type="button" class="btn btn-outline btn-sm"
                            data-jump-step="ending">
                        Ready to close the day &rarr; Ending Stock
                    </button>
                </div>
            </div><!-- /#step-running -->
        <?php endif; ?>

        <!-- ====================== STEP 3: ENDING ====================== -->
        <div class="inventory-step-panel <?= $active_step === 'ending' ? 'active' : '' ?>"
             id="step-ending" role="tabpanel">

        <?php if ($has_ending): ?>
            <?php
                // Build a Part_ID -> beginning qty lookup for the "Beginning: X" reference
                $beginning_qty_map = [];
                foreach ($beginning as $b_row) {
                    $beginning_qty_map[(int) $b_row['Part_ID']] = (int) $b_row['Quantity'];
                }
            ?>
            <div class="inventory-saved-view inventory-flat">
                <h4 class="inventory-flat-heading">All Parts (<?= count($ending) ?>)</h4>
                <?php foreach ($ending as $row): ?>
                    <?php
                        $show_locked = $staff_locked || $row['Locked_status'];
                        $beg_qty     = $beginning_qty_map[(int) $row['Part_ID']] ?? null;
                        $is_warn     = $beg_qty !== null && (int) $row['Quantity'] > $beg_qty;
                    ?>
                    <div class="inventory-product-row<?= $is_warn ? ' inventory-row-warning' : '' ?>">
                        <div class="inventory-product-name"><?= htmlspecialchars($row['Part_Name']) ?></div>
                        <div class="inventory-product-unit"><?= htmlspecialchars($row['Unit']) ?></div>
                        <?php if ($beg_qty !== null): ?>
                            <div class="inventory-ending-ref">Beginning: <strong><?= $beg_qty ?></strong></div>
                        <?php endif; ?>
                        <div class="inventory-saved-qty"><?= $row['Quantity'] ?></div>
                        <div class="inventory-saved-status">
                            <span class="badge <?= $show_locked ? 'badge-locked' : 'badge-active' ?>">
                                <?= $show_locked ? 'Locked' : 'Open' ?>
                            </span>
                        </div>
                        <?php if (Auth::isOwner()): ?>
                            <div class="inventory-saved-action">
                                <?php if ($row['Locked_status']): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/inventory/unlock" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                        <input type="hidden" name="inventory_id" value="<?= $row['Inventory_ID'] ?>">
                                        <input type="hidden" name="date" value="<?= $date ?>">
                                        <button type="button" class="btn btn-sm btn-outline"
                                                onclick="showConfirmModal({
                                                    title: 'Unlock Record',
                                                    message: 'Are you sure you want to unlock this inventory record? This will allow editing of past data.',
                                                    confirmText: 'Yes, Unlock',
                                                    type: 'unlock',
                                                    onConfirm: () => this.closest('form').submit()
                                                })">Unlock</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-light">&mdash;</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($is_today && $has_beginning): ?>
            <!-- Ending Stock Form — pre-filled from running parts inventory -->
            <?php
                $running_qty_map   = [];
                $beginning_qty_map = [];
                foreach ($running_inventory as $r) {
                    $running_qty_map[(int) $r['Part_ID']] = (int) $r['Running_Qty'];
                }
                foreach ($beginning as $row) {
                    $beginning_qty_map[(int) $row['Part_ID']] = (int) $row['Quantity'];
                }
            ?>
            <div class="ending-prefill-notice">
                <strong>Pre-filled from running inventory.</strong>
                Edit any row to override with the actual physical count.
            </div>
            <form method="POST" action="<?= BASE_URL ?>/inventory/store"
                  class="inventory-entry-form" data-total="<?= $total_parts ?>"
                  data-label="Save Ending Stock">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                <input type="hidden" name="snapshot_type" value="ending">
                <input type="hidden" name="kiosk_id" value="<?= $kiosk_id ?>">
                <input type="hidden" name="date" value="<?= $date ?>">

                <div class="inventory-progress-label">
                    <span class="inventory-filled-count">0</span> of <?= $total_parts ?> parts filled
                </div>
                <div class="inventory-progress">
                    <div class="inventory-progress-bar" style="width:0%"></div>
                </div>

                <div class="inventory-flat">
                    <h4 class="inventory-flat-heading">All Parts (<?= $total_parts ?>)</h4>
                    <?php foreach ($parts as $part): ?>
                        <?php
                            $pid          = (int) $part['Part_ID'];
                            $beg_qty      = $beginning_qty_map[$pid] ?? 0;
                            $expected_qty = $running_qty_map[$pid] ?? $beg_qty;
                            $prefill = max(0, $expected_qty);
                        ?>
                        <div class="inventory-product-row" data-beginning="<?= $beg_qty ?>" data-expected="<?= $expected_qty ?>">
                            <div class="inventory-product-name"><?= htmlspecialchars($part['Name']) ?></div>
                            <div class="inventory-product-unit"><?= htmlspecialchars($part['Unit']) ?></div>
                            <div class="inventory-ending-ref">
                                Expected: <strong><?= $expected_qty ?></strong>
                                <span class="ending-ref-sub">(beg <?= $beg_qty ?>)</span>
                            </div>
                            <div class="inventory-qty-control">
                                <button type="button" class="inventory-qty-btn" data-action="dec" aria-label="Decrease">&minus;</button>
                                <input type="number"
                                       name="qty[<?= $part['Part_ID'] ?>]"
                                       class="inventory-qty-input"
                                       min="0" value="<?= $prefill ?>" required>
                                <button type="button" class="inventory-qty-btn" data-action="inc" aria-label="Increase">+</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="inventory-sticky-submit">
                    <button type="button" class="btn btn-primary inventory-submit-btn"
                            onclick="showConfirmModal({
                                title: 'Submit Ending Stock',
                                message: 'Are you sure you want to submit the ending stock for today? This will lock all inventory records for this date.',
                                confirmText: 'Yes, Submit',
                                type: 'submit',
                                onConfirm: () => this.closest('form').submit()
                            })">
                        Save Ending Stock (<span class="inventory-filled-count">0</span> of <?= $total_parts ?> filled)
                    </button>
                </div>
            </form>
        <?php elseif ($is_today && !$has_beginning): ?>
            <p class="text-light">Record beginning stock first before entering ending stock.</p>
        <?php else: ?>
            <p class="text-light">No ending stock recorded for this date.</p>
        <?php endif; ?>

        </div><!-- /#step-ending -->

    </div><!-- /.inventory-step-wrapper -->
</section>

<script>
/* Inventory step-tab switching — local to this page so it doesn't
   collide with any other tab JS. */
(function () {
    const navBtns = document.querySelectorAll('.inventory-step-btn');
    const panels  = document.querySelectorAll('.inventory-step-panel');
    if (!navBtns.length || !panels.length) return;

    function activate(step) {
        navBtns.forEach(b => b.classList.toggle('active', b.dataset.step === step));
        panels.forEach(p => p.classList.toggle('active', p.id === 'step-' + step));
        const wrapper = document.querySelector('.inventory-step-wrapper');
        if (wrapper) wrapper.scrollIntoView({ block: 'start', behavior: 'smooth' });
    }

    navBtns.forEach(btn => {
        btn.addEventListener('click', () => activate(btn.dataset.step));
    });

    document.querySelectorAll('[data-jump-step]').forEach(btn => {
        btn.addEventListener('click', () => activate(btn.dataset.jumpStep));
    });
})();
</script>
