<section class="admin-edit-user">
    <div class="section-header">
        <h2>Edit User &mdash; <?= htmlspecialchars($user['Full_name']) ?></h2>
        <a href="<?= BASE_URL ?>/admin/users" class="btn btn-sm btn-outline">&laquo; Back to Users</a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="admin-edit-grid">

        <!-- ============== Card 1: Edit Profile ============== -->
        <div class="card form-card">
            <h3>Profile</h3>
            <form method="POST" action="<?= BASE_URL ?>/admin/users/update">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                <input type="hidden" name="user_id" value="<?= $user['User_ID'] ?>">

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-input"
                           value="<?= htmlspecialchars($user['Full_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input"
                           value="<?= htmlspecialchars($user['Username']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="role_id">Role</label>
                    <select id="role_id" name="role_id" class="form-select" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['Role_ID'] ?>"
                                <?= $role['Role_ID'] == $user['Role_ID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="kiosk_id">Assigned Kiosk</label>
                    <select id="kiosk_id" name="kiosk_id" class="form-select">
                        <option value="">None (Owner/Auditor)</option>
                        <?php foreach ($kiosks as $kiosk): ?>
                            <option value="<?= $kiosk['Kiosk_ID'] ?>"
                                <?= $kiosk['Kiosk_ID'] == $user['Kiosk_ID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kiosk['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-hint">Required for Staff role.</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-primary"
                            onclick="showConfirmModal({
                                title: 'Save Profile Changes',
                                message: 'Update this user\'s profile? Their next login will reflect the new role/kiosk assignment.',
                                confirmText: 'Yes, Save',
                                type: 'submit',
                                onConfirm: () => this.closest('form').submit()
                            })">Save Profile</button>
                </div>
            </form>
        </div>

        <!-- ============== Card 2: Reset Password ============== -->
        <div class="card form-card">
            <h3>Reset Password</h3>
            <p class="text-muted">Set a new password for this user. The old password is replaced immediately and cannot be recovered.</p>
            <form method="POST" action="<?= BASE_URL ?>/admin/users/reset-password">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                <input type="hidden" name="user_id" value="<?= $user['User_ID'] ?>">

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-input"
                           minlength="4" required>
                    <small class="form-hint">Minimum 4 characters.</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-danger"
                            onclick="showConfirmModal({
                                title: 'Reset Password',
                                message: 'This will overwrite <?= htmlspecialchars(addslashes($user['Username'])) ?>\'s password. They will need the new password to log in. Continue?',
                                confirmText: 'Yes, Reset',
                                type: 'delete',
                                onConfirm: () => this.closest('form').submit()
                            })">Reset Password</button>
                </div>
            </form>
        </div>

        <!-- ============== Card 3: Account Info ============== -->
        <div class="card form-card">
            <h3>Account Info</h3>
            <ul class="admin-info-list">
                <li class="admin-info-row">
                    <span class="admin-info-label">User ID</span>
                    <span class="admin-info-value">#<?= $user['User_ID'] ?></span>
                </li>
                <li class="admin-info-row">
                    <span class="admin-info-label">Status</span>
                    <span class="admin-info-value">
                        <span class="badge <?= $user['Active_status'] ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $user['Active_status'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </span>
                </li>
                <li class="admin-info-row">
                    <span class="admin-info-label">Current Role</span>
                    <span class="admin-info-value">
                        <span class="badge badge-<?= strtolower($user['Role_Name']) ?>">
                            <?= htmlspecialchars($user['Role_Name']) ?>
                        </span>
                    </span>
                </li>
                <li class="admin-info-row">
                    <span class="admin-info-label">Current Kiosk</span>
                    <span class="admin-info-value"><?= htmlspecialchars($user['Kiosk_Name'] ?? '—') ?></span>
                </li>
                <li class="admin-info-row">
                    <span class="admin-info-label">Created</span>
                    <span class="admin-info-value"><?= date('M j, Y g:i A', strtotime($user['Created_at'])) ?></span>
                </li>
            </ul>
        </div>

    </div>
</section>
