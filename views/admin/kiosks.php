<section class="admin-kiosks">
    <div class="section-header">
        <h2>Manage Kiosks</h2>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Add Kiosk Form -->
    <div class="card form-card">
        <h3>Add New Kiosk</h3>
        <form method="POST" action="<?= BASE_URL ?>/admin/kiosks/create" class="form-inline-grid">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">

            <div class="form-group">
                <label for="name">Branch Name</label>
                <input type="text" id="name" name="name" class="form-input" required>
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" class="form-input" required>
            </div>

            <div class="form-group form-actions">
                <button type="submit" class="btn btn-primary">Add Kiosk</button>
            </div>
        </form>
    </div>

    <!-- Kiosks Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Branch Name</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kiosks)): ?>
                    <tr><td colspan="6" class="text-center">No kiosks found.</td></tr>
                <?php else: ?>
                    <?php foreach ($kiosks as $kiosk): ?>
                        <tr>
                            <td><?= $kiosk['Kiosk_ID'] ?></td>
                            <td><?= htmlspecialchars($kiosk['Name']) ?></td>
                            <td><?= htmlspecialchars($kiosk['Location']) ?></td>
                            <td>
                                <span class="badge <?= $kiosk['Active'] ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $kiosk['Active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($kiosk['Created_at'])) ?></td>
                            <td>
                                <form method="POST" action="<?= BASE_URL ?>/admin/kiosks/update" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                    <input type="hidden" name="kiosk_id" value="<?= $kiosk['Kiosk_ID'] ?>">
                                    <input type="hidden" name="name" value="<?= htmlspecialchars($kiosk['Name']) ?>">
                                    <input type="hidden" name="location" value="<?= htmlspecialchars($kiosk['Location']) ?>">
                                    <input type="hidden" name="active" value="<?= $kiosk['Active'] ? '0' : '1' ?>">
                                    <button type="button" class="btn btn-sm <?= $kiosk['Active'] ? 'btn-danger' : 'btn-success' ?>"
                                            onclick="showConfirmModal({
                                                title: <?= $kiosk['Active'] ? "'Deactivate Kiosk'" : "'Activate Kiosk'" ?>,
                                                message: <?= $kiosk['Active']
                                                    ? "'Are you sure you want to deactivate this kiosk? Staff assigned to it will not be able to record data while it is inactive.'"
                                                    : "'Are you sure you want to reactivate this kiosk?'" ?>,
                                                confirmText: <?= $kiosk['Active'] ? "'Yes, Deactivate'" : "'Yes, Activate'" ?>,
                                                type: <?= $kiosk['Active'] ? "'delete'" : "'submit'" ?>,
                                                onConfirm: () => this.closest('form').submit()
                                            })">
                                        <?= $kiosk['Active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
