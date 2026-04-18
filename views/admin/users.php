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
                        <tr>
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
                                    <form method="POST" action="<?= BASE_URL ?>/admin/users/<?= $user['Active_status'] ? 'deactivate' : 'activate' ?>" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                        <input type="hidden" name="user_id" value="<?= $user['User_ID'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $user['Active_status'] ? 'btn-danger' : 'btn-success' ?>"
                                                onclick="return confirm('Are you sure?')">
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
</section>
