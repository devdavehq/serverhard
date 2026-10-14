<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();

/* Self-service password change (POSTs back to this page). */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $current = (string) ($_POST['current_password'] ?? '');
    $new     = (string) ($_POST['new_password'] ?? '');

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();

    if ($row === false || !password_verify($current, $row['password_hash'])) {
        set_flash('pwderr', 'Current password is wrong.');
    } elseif (strlen($new) < 10) {
        set_flash('pwderr', 'New password must be at least 10 characters.');
    } else {
        $upd = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $upd->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);

        session_regenerate_id(true); // re-auth after credential change
        set_flash('pwd', 'Password updated.');
    }
    redirect('/admin/');
}

$items = db()->query('SELECT id, title, created_at FROM items ORDER BY id DESC')
    ->fetchAll();

render_header('Admin', 'admin');
?>
<h1>Admin panel</h1>
<p class="lede">Signed in as <strong><?php echo h($_SESSION['username']); ?></strong>.
This area is guarded by <code>require_login()</code>, rate-limited logins,
CSRF tokens on every form, and the extra rules in <code>admin/.htaccess</code>.</p>

<?php if (flash('msg')): ?><p class="ok"><?php echo h(flash('msg')); ?></p><?php endif; ?>
<?php if (flash('pwderr')): ?><p class="alert"><?php echo h(flash('pwderr')); ?></p><?php endif; ?>
<?php if (flash('pwd')): ?><p class="ok"><?php echo h(flash('pwd')); ?></p><?php endif; ?>

<section class="card">
    <h2>Items <a class="btn" href="/admin/create.php">+ New</a></h2>
    <?php if (!$items): ?>
        <p>No items yet. Create the first one.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>ID</th><th>Title</th><th>Created</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo (int) $item['id']; ?></td>
                    <td><?php echo h($item['title']); ?></td>
                    <td class="muted"><?php echo h($item['created_at']); ?></td>
                    <td class="actions">
                        <a href="/admin/edit.php?id=<?php echo (int) $item['id']; ?>">Edit</a>
                        <form method="post" action="/admin/delete.php" class="inline">
                            <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="link danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Change password</h2>
    <form method="post" action="/admin/" autocomplete="off">
        <?php echo csrf_field(); ?>
        <label>Current password
            <input type="password" name="current_password" required autocomplete="current-password">
        </label>
        <label>New password (min 10 chars)
            <input type="password" name="new_password" required minlength="10" autocomplete="new-password">
        </label>
        <button type="submit">Update password</button>
    </form>
</section>
<?php render_footer(); ?>