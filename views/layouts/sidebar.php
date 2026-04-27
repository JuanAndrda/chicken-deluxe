<?php
    // Determine active page from current URL
    $current_path = str_replace(BASE_URL, '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $current_path = '/' . trim($current_path, '/');

    function isActive(string $path, string $current): string {
        // Match exact or starts-with for sub-pages
        if ($current === $path || strpos($current, $path . '/') === 0) {
            return 'active';
        }
        // Dashboard matches root
        if ($path === '/dashboard' && ($current === '/' || $current === '/dashboard')) {
            return 'active';
        }
        return '';
    }
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2><?= APP_NAME ?></h2>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/dashboard" class="nav-link <?= isActive('/dashboard', $current_path) ?>">Dashboard</a>

        <?php if (Auth::isOwner() || Auth::isStaff()): ?>
            <a href="<?= BASE_URL ?>/sales"     class="nav-link <?= isActive('/sales', $current_path) ?>">Point of Sales</a>
            <a href="<?= BASE_URL ?>/expenses"  class="nav-link <?= isActive('/expenses', $current_path) ?>">Expenses</a>
            <a href="<?= BASE_URL ?>/inventory" class="nav-link <?= isActive('/inventory', $current_path) ?>">Inventory</a>
            <a href="<?= BASE_URL ?>/delivery"  class="nav-link <?= isActive('/delivery', $current_path) ?>">Deliveries</a>
        <?php endif; ?>

        <?php if (Auth::isOwner() || Auth::isAuditor()): ?>
            <a href="<?= BASE_URL ?>/reports" class="nav-link <?= isActive('/reports', $current_path) ?>">Reports</a>
        <?php endif; ?>

        <?php if (Auth::isOwner()): ?>
            <div class="nav-section-label">Admin Panel</div>
            <a href="<?= BASE_URL ?>/admin/users"    class="nav-link nav-sub <?= isActive('/admin/users', $current_path) ?: (isActive('/admin', $current_path) && $current_path === '/admin' ? 'active' : '') ?>">Users</a>
            <a href="<?= BASE_URL ?>/admin/products" class="nav-link nav-sub <?= isActive('/admin/products', $current_path) ?>">Products</a>
            <a href="<?= BASE_URL ?>/admin/kiosks"   class="nav-link nav-sub <?= isActive('/admin/kiosks', $current_path) ?>">Kiosks</a>
            <a href="<?= BASE_URL ?>/admin/audit-log" class="nav-link nav-sub <?= isActive('/admin/audit-log', $current_path) ?>">Audit Log</a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <span class="sidebar-user"><?= htmlspecialchars(Auth::fullName() ?? '') ?></span>
        <span class="sidebar-role"><?= ROLE_NAMES[Auth::roleId()] ?? '' ?></span>
        <?php if (Auth::isStaff() && Auth::kioskId()): ?>
            <span class="sidebar-kiosk">
                &#128205;
                <?= htmlspecialchars($_SESSION['kiosk_name'] ?? 'Kiosk ' . Auth::kioskId()) ?>
            </span>
        <?php endif; ?>
    </div>
</aside>
