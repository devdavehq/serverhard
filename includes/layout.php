<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/* Layout helpers: shared header/footer. */

function render_header(string $title, string $active = ''): void
{
    $name = APP_NAME;
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; script-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'">
<title><?php echo h($title) . ' · ' . h($name); ?></title>
<link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="topbar">
    <nav>
        <a href="/" class="<?php echo $active === 'home' ? 'on' : ''; ?>">Home</a>
        <?php if (is_logged_in()): ?>
            <a href="/admin/" class="<?php echo $active === 'admin' ? 'on' : ''; ?>">Admin</a>
            <a href="/logout.php">Log out (<?php echo h($_SESSION['username'] ?? ''); ?>)</a>
        <?php else: ?>
            <a href="/login.php" class="<?php echo $active === 'login' ? 'on' : ''; ?>">Login</a>
        <?php endif; ?>
    </nav>
</header>
<main><?php
}

function render_footer(): void
{
    ?>
</main>
<footer class="foot">Hardening demo - this folder IS the docroot; includes/ and storage/ are blocked by .htaccess (and by router.php in php -S).</footer>
</body>
</html><?php
}

function flash(string $key): string
{
    $msg = $_SESSION['_flash'][$key] ?? '';
    unset($_SESSION['_flash'][$key]);
    return (string) $msg;
}

function set_flash(string $key, string $msg): void
{
    $_SESSION['_flash'][$key] = $msg;
}