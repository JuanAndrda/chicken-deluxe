<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Dashboard') ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="app-body">
    <div class="app-wrapper">
        <?php require __DIR__ . '/sidebar.php'; ?>

        <div class="app-main">
            <?php require __DIR__ . '/navbar.php'; ?>

            <main class="app-content">
                <?= $content ?>
            </main>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
