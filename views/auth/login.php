<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><?= APP_NAME ?></h1>
                <p>Inventory & Sales Monitoring System</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($timeout): ?>
                <div class="alert alert-warning">Your session has expired. Please log in again.</div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/login" class="login-form">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input"
                           placeholder="Enter your username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input"
                           placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Log In</button>
            </form>
        </div>

        <div class="login-footer">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> — Group 7, BSIT 2-B</p>
        </div>
    </div>
</body>
</html>
