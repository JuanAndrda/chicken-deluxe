<section class="dashboard">
    <h2>Deliveries</h2>
    <p>Manage and track all incoming deliveries.</p>

    <?php if (Auth::isStaff() && Auth::outletId()): ?>
        <p>Assigned kiosk: <strong><?= htmlspecialchars($kiosk_name ?? 'N/A') ?></strong></p>
    <?php endif; ?>

    <div class="dashboard-cards">

        <!-- Add Delivery -->
        <div class="card">
            <h3>Add Delivery</h3>
            <p>Log new incoming stock deliveries</p>
            <a href="<?= BASE_URL ?>/delivery/create" class="btn btn-primary">Go</a>
        </div>

        <!-- View Deliveries -->
        <div class="card">
            <h3>View Deliveries</h3>
            <p>See all recorded deliveries</p>
            <a href="<?= BASE_URL ?>/delivery/list" class="btn btn-primary">Go</a>
        </div>

        <!-- Supplier Records -->
        <div class="card">
            <h3>Suppliers</h3>
            <p>Manage supplier information</p>
            <a href="<?= BASE_URL ?>/delivery/suppliers" class="btn btn-primary">Go</a>
        </div>

        <!-- Delivery History -->
        <div class="card">
            <h3>History</h3>
            <p>Track past delivery records</p>
            <a href="<?= BASE_URL ?>/delivery/history" class="btn btn-primary">Go</a>
        </div>

    </div>
</section>