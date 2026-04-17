<header class="navbar">
    <div class="navbar-left">
        <button class="sidebar-toggle" id="sidebarToggle">&#9776;</button>
        <h3 class="page-title"><?= htmlspecialchars($page_title ?? '') ?></h3>
    </div>

    <div class="navbar-right">
        <span class="navbar-user"><?= htmlspecialchars(Auth::username() ?? '') ?></span>
        <a href="<?= BASE_URL ?>/logout" class="btn btn-sm btn-outline">Logout</a>
    </div>
</header>
