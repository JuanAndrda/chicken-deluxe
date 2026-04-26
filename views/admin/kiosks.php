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

    <!-- Show Inactive toggle -->
    <div class="admin-toggle-row">
        <?php if (!empty($show_all)): ?>
            <a href="<?= BASE_URL ?>/admin/kiosks" class="btn btn-sm btn-outline">Hide Inactive</a>
            <span class="text-muted">Showing all kiosks (active + inactive).</span>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/admin/kiosks?show_all=1" class="btn btn-sm btn-outline">Show Inactive</a>
            <span class="text-muted">Showing active kiosks only.</span>
        <?php endif; ?>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kiosks)): ?>
                    <tr><td colspan="6" class="text-center">No kiosks found.</td></tr>
                <?php else: ?>
                    <?php foreach ($kiosks as $kiosk): ?>
                        <?php $kid = (int) $kiosk['Kiosk_ID']; ?>
                        <tr id="kiosk-row-<?= $kid ?>" class="<?= $kiosk['Active'] ? '' : 'row-inactive' ?>">
                            <td><?= $kid ?></td>
                            <td><?= htmlspecialchars($kiosk['Name']) ?></td>
                            <td><?= htmlspecialchars($kiosk['Location']) ?></td>
                            <td>
                                <span class="badge <?= $kiosk['Active'] ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $kiosk['Active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($kiosk['Created_at'])) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary"
                                        onclick="toggleKioskEdit(<?= $kid ?>)">Edit</button>

                                <!-- Toggle active form (inline, separate from edit) -->
                                <form method="POST" action="<?= BASE_URL ?>/admin/kiosks/update" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                    <input type="hidden" name="kiosk_id" value="<?= $kid ?>">
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
                        <!-- Hidden inline edit row (shown via toggleKioskEdit) -->
                        <tr id="kiosk-edit-<?= $kid ?>" class="kiosk-edit-row" style="display:none;">
                            <td colspan="6">
                                <form method="POST" action="<?= BASE_URL ?>/admin/kiosks/update" class="kiosk-edit-fields">
                                    <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
                                    <input type="hidden" name="kiosk_id" value="<?= $kid ?>">
                                    <input type="hidden" name="active" value="<?= (int) $kiosk['Active'] ?>">

                                    <div class="form-group">
                                        <label>Branch Name</label>
                                        <input type="text" name="name" class="form-input input-sm"
                                               value="<?= htmlspecialchars($kiosk['Name']) ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Location</label>
                                        <input type="text" name="location" class="form-input input-sm"
                                               value="<?= htmlspecialchars($kiosk['Location']) ?>" required>
                                    </div>

                                    <div class="kiosk-edit-actions">
                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                        <button type="button" class="btn btn-sm btn-outline"
                                                onclick="toggleKioskEdit(<?= $kid ?>)">Cancel</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
function toggleKioskEdit(id) {
    const row = document.getElementById('kiosk-edit-' + id);
    if (!row) return;
    // Use a class toggle so Cancel reliably hides a freshly-shown row.
    // (Checking style.display alone fails because '' coerces to falsy.)
    const isShown = row.classList.contains('is-open');
    if (isShown) {
        row.classList.remove('is-open');
        row.style.display = 'none';
    } else {
        row.classList.add('is-open');
        row.style.display = '';
    }
}
</script>
