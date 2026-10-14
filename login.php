<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

/* ---- demo-grade login with an in-session rate limiter ---- */

$error = '';
$next  = local_url((string) ($_GET['next'] ?? '/admin/'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    // 5 attempts per 15 minutes, then a lockout
    $attempts  = $_SESSION['_login']['fail'] ?? 0;
    $blockedAt = $_SESSION['_login']['blocked_at'] ?? 0;

    if ($attempts >= 5 && (time() - $blockedAt) < 900) {
        $error = 'Too many failed attempts. Try again in a few minutes.';
    } else {
        csrf_verify();

        if ($username === '' || $password === '') {
            $error = 'Username and password are required.';
        } else {
            $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            $ok = $user !== false && password_verify($password, $user['password_hash']);

            if ($ok) {
                // Success -> wipe the limiter and rotate the session id
                session_regenerate_id(true);
                $_SESSION['user_id']  = (int) $user['id'];
                $_SESSION['username'] = $user['username'];
                unset($_SESSION['_login']);
                redirect($next);
            }

            $attempts++;
            $_SESSION['_login']['fail']       = $attempts;
            $_SESSION['_login']['blocked_at'] = time();
            $error = $attempts >= 5
                ? 'Too many failed attempts. Locked for 15 minutes.'
                : 'Invalid credentials.';
        }
    }
}

render_header('Login', 'login');
?>
<h1>Admin login</h1>

<?php if ($error): ?>
    <p class="alert"><?php echo h($error); ?></p>
<?php endif; ?>

<?php if (flash('pwd')): ?>
    <p class="ok"><?php echo h(flash('pwd')); ?></p>
<?php endif; ?>

<form method="post" action="/login.php" class="card" autocomplete="off">
    <input type="hidden" name="next" value="<?php echo h($next); ?>">
    <?php echo csrf_field(); ?>

    <label>Username
        <input type="text" name="username" required autocomplete="username"
               value="<?php echo h($_POST['username'] ?? ''); ?>">
    </label>
    <label>Password
        <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button type="submit">Log in</button>
</form>

<p class="muted">First-run credentials: <code>admin / admin</code> — change them immediately after logging in.</p>
<?php render_footer(); ?>