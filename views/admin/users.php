<section class="admin-users">
    <div class="section-header">
        <h2>Manage Users</h2>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ===== TOP-LEVEL TABS ===== -->
    <div class="admin-users-tab-nav">
        <button class="admin-users-tab-btn active" data-tab="users">👥 Users</button>
        <button class="admin-users-tab-btn" data-tab="timein">🕐 Time In / Out</button>
    </div>

    <!-- ========================== TAB: USERS ========================== -->
    <div class="admin-users-tab-panel active" id="adminTabUsers">

        <!-- Add User Form -->
        <div class="card form-card">
            <h3>Add New User</h3>
            <form method="POST" action="<?= BASE_URL ?>/admin/users/create" class="form-inline-grid">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="role_id">Role</label>
                    <select id="role_id" name="role_id" class="form-select" required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['Role_ID'] ?>"><?= htmlspecialchars($role['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="kiosk_id">Assigned Kiosk</label>
                    <select id="kiosk_id" name="kiosk_id" class="form-select">
                        <option value="">None (Owner/Auditor)</option>
                        <?php foreach ($kiosks as $kiosk): ?>
                            <option value="<?= $kiosk['Kiosk_ID'] ?>"><?= htmlspecialchars($kiosk['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group form-actions">
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>

        <!-- Show Deactivated toggle -->
        <div class="admin-toggle-row">
            <?php if (!empty($show_all)): ?>
                <a href="<?= BASE_URL ?>/admin/users" class="btn btn-sm btn-outline">Hide Deactivated</a>
                <span class="text-muted">Showing all users (active + deactivated).</span>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/admin/users?show_all=1" class="btn btn-sm btn-outline">Show Deactivated</a>
                <span class="text-muted">Showing active users only.</span>
            <?php endif; ?>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Assigned Kiosk</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="8" class="text-center">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="<?= $user['Active_status'] ? '' : 'row-inactive' ?>">
                                <td><?= $user['User_ID'] ?></td>
                                <td><?= htmlspecialchars($user['Full_name']) ?></td>
                                <td><?= htmlspecialchars($user['Username']) ?></td>
                                <td><span class="badge badge-<?= strtolower($user['Role_Name']) ?>"><?= htmlspecialchars($user['Role_Name']) ?></span></td>
                                <td><?= htmlspecialchars($user['Kiosk_Name'] ?? '—') ?></td>
                                <td>
                                    <span class="badge <?= $user['Active_status'] ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= $user['Active_status'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($user['Created_at'])) ?></td>
                                <td>
                                    <?php if ($user['User_ID'] !== Auth::userId()): ?>
                                        <a href="<?= BASE_URL ?>/admin/users/edit?id=<?= $user['User_ID'] ?>"
                                           class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/users/<?= $user['Active_status'] ? 'deactivate' : 'activate' ?>" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                            <input type="hidden" name="user_id" value="<?= $user['User_ID'] ?>">
                                            <button type="button" class="btn btn-sm <?= $user['Active_status'] ? 'btn-danger' : 'btn-success' ?>"
                                                    onclick="showConfirmModal({
                                                        title: <?= $user['Active_status'] ? "'Deactivate User'" : "'Activate User'" ?>,
                                                        message: <?= $user['Active_status']
                                                            ? "'Are you sure you want to deactivate this account? The user will no longer be able to log in.'"
                                                            : "'Are you sure you want to reactivate this account?'" ?>,
                                                        confirmText: <?= $user['Active_status'] ? "'Yes, Deactivate'" : "'Yes, Activate'" ?>,
                                                        type: <?= $user['Active_status'] ? "'delete'" : "'submit'" ?>,
                                                        onConfirm: () => this.closest('form').submit()
                                                    })">
                                                <?= $user['Active_status'] ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-light">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div><!-- /adminTabUsers -->

    <!-- ========================== TAB: TIME IN/OUT ========================== -->
    <div class="admin-users-tab-panel" id="adminTabTimein">

        <!-- Sub-tab nav -->
        <div class="timein-sub-nav">
            <button class="timein-sub-btn active" data-sub="active">🟢 Time In (Active)</button>
            <button class="timein-sub-btn" data-sub="completed">✅ Time Out (Completed)</button>
        </div>

        <!-- Sub-tab: Active Sessions -->
        <div class="timein-sub-panel active" id="subActive">
            <div class="card">
                <h3>Currently Active Sessions &mdash; Today</h3>
                <?php if (empty($active_sessions)): ?>
                    <div class="empty-state-card">
                        <div class="empty-state-icon">🕐</div>
                        <div class="empty-state-text">No active sessions</div>
                        <div class="empty-state-sub">No staff are currently timed in today.</div>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Staff Name</th>
                                    <th>Username</th>
                                    <th>Kiosk</th>
                                    <th>Time In</th>
                                    <th>Duration So Far</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_sessions as $s): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['Full_name']) ?></td>
                                        <td><?= htmlspecialchars($s['Username']) ?></td>
                                        <td><?= htmlspecialchars($s['Kiosk_Name']) ?></td>
                                        <td><?= date('g:i A', strtotime($s['Timestamp'])) ?></td>
                                        <td>
                                            <?php
                                                $mins = (time() - strtotime($s['Timestamp'])) / 60;
                                                $hrs  = floor($mins / 60);
                                                $rem  = $mins % 60;
                                                echo $hrs > 0 ? "{$hrs}h {$rem}m" : round($rem) . "m";
                                            ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="<?= BASE_URL ?>/admin/users/timeout" class="inline-form">
                                                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                                <input type="hidden" name="timein_id"  value="<?= $s['Timein_ID'] ?>">
                                                <button type="button" class="btn btn-sm btn-secondary"
                                                        onclick="showConfirmModal({
                                                            title: 'Record Time Out',
                                                            message: 'Record time-out for <?= htmlspecialchars(addslashes($s['Full_name'])) ?>?',
                                                            confirmText: 'Yes, Time Out',
                                                            type: 'submit',
                                                            onConfirm: () => this.closest('form').submit()
                                                        })">
                                                    Time Out
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div><!-- /subActive -->

        <!-- Sub-tab: Completed Sessions -->
        <div class="timein-sub-panel" id="subCompleted">
            <div class="card">
                <h3>Completed Sessions &mdash; Today</h3>
                <?php if (empty($completed_sessions)): ?>
                    <div class="empty-state-card">
                        <div class="empty-state-icon">✅</div>
                        <div class="empty-state-text">No completed sessions</div>
                        <div class="empty-state-sub">No staff have timed out today yet.</div>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Staff Name</th>
                                    <th>Username</th>
                                    <th>Kiosk</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Hours Worked</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed_sessions as $s): ?>
                                    <?php
                                        $hrs = (float) $s['Hours_Worked'];
                                        if ($hrs < 4) {
                                            $hrs_class = 'hours-warning';
                                        } elseif ($hrs > 9) {
                                            $hrs_class = 'hours-overtime';
                                        } else {
                                            $hrs_class = 'hours-normal';
                                        }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['Full_name']) ?></td>
                                        <td><?= htmlspecialchars($s['Username']) ?></td>
                                        <td><?= htmlspecialchars($s['Kiosk_Name']) ?></td>
                                        <td><?= date('g:i A', strtotime($s['Timestamp'])) ?></td>
                                        <td><?= date('g:i A', strtotime($s['Time_out'])) ?></td>
                                        <td>
                                            <span class="<?= $hrs_class ?>">
                                                <?= number_format($hrs, 2) ?> hrs
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div><!-- /subCompleted -->

    </div><!-- /adminTabTimein -->

</section>

<script>
(function () {
    // ===== TOP-LEVEL TAB SWITCHING =====
    document.querySelectorAll('.admin-users-tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.admin-users-tab-btn')
                    .forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.admin-users-tab-panel')
                    .forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const target = this.dataset.tab === 'users' ? 'adminTabUsers' : 'adminTabTimein';
            document.getElementById(target).classList.add('active');
        });
    });

    // ===== SUB-TAB SWITCHING =====
    document.querySelectorAll('.timein-sub-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.timein-sub-btn')
                    .forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.timein-sub-panel')
                    .forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const target = this.dataset.sub === 'active' ? 'subActive' : 'subCompleted';
            document.getElementById(target).classList.add('active');
        });
    });

    // Auto-switch to Time In/Out tab if redirected after timeout action
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('success') || '';
    if (msg.toLowerCase().includes('time')) {
        document.querySelector('[data-tab="timein"]').click();
    }
})();
</script>
