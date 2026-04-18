<section class="dashboard">
    <h2>Welcome, <?= htmlspecialchars(Auth::fullName() ?? '') ?>!</h2>
    <p>You are logged in as <strong><?= ROLE_NAMES[Auth::roleId()] ?? 'Unknown' ?></strong>.</p>

    <?php if (Auth::isStaff() && Auth::kioskId()): ?>
        <p>Assigned kiosk: <strong><?= htmlspecialchars($kiosk_name ?? 'N/A') ?></strong></p>
    <?php endif; ?>

    <div class="dashboard-cards">
        <?php if (Auth::isOwner() || Auth::isStaff()): ?>
            <div class="card">
                <h3>Inventory</h3>
                <p>Record beginning and ending stock</p>
                <a href="<?= BASE_URL ?>/inventory" class="btn btn-primary">Go</a>
            </div>
            <div class="card">
                <h3>Sales</h3>
                <p>Record daily sales per product</p>
                <a href="<?= BASE_URL ?>/sales" class="btn btn-primary">Go</a>
            </div>
            <div class="card">
                <h3>Deliveries</h3>
                <p>Log incoming deliveries</p>
                <a href="<?= BASE_URL ?>/delivery" class="btn btn-primary">Go</a>
            </div>
            <div class="card">
                <h3>Expenses</h3>
                <p>Track daily operational expenses</p>
                <a href="<?= BASE_URL ?>/expenses" class="btn btn-primary">Go</a>
            </div>
        <?php endif; ?>

        <?php if (Auth::isOwner() || Auth::isAuditor()): ?>
            <div class="card">
                <h3>Reports</h3>
                <p>View summaries and audit trails</p>
                <a href="<?= BASE_URL ?>/reports" class="btn btn-primary">Go</a>
            </div>
        <?php endif; ?>

        <?php if (Auth::isOwner()): ?>
            <div class="card">
                <h3>Admin</h3>
                <p>Manage users, products, and kiosks</p>
                <a href="<?= BASE_URL ?>/admin" class="btn btn-primary">Go</a>
            </div>
        <?php endif; ?>
    </div>
</section>