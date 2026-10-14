<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $title = trim((string) ($_POST['title'] ?? ''));
    $body  = (string) ($_POST['content'] ?? '');
    $now   = date('c');

    if ($title === '' || mb_strlen($title) > 120) {
        set_flash('msg', 'Title is required (max 120 characters).');
    } else {
        $stmt = db()->prepare('INSERT INTO items (title, content, created_at, updated_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([$title, $body, $now, $now]);
        set_flash('msg', 'Item created.');
    }
    redirect('/admin/');
}

render_header('New item', 'admin');
?>
<h1>Create item</h1>
<form method="post" action="/admin/create.php" class="card">
    <?php echo csrf_field(); ?>
    <label>Title
        <input type="text" name="title" required maxlength="120" value="<?php echo h($_POST['title'] ?? ''); ?>">
    </label>
    <label>Content
        <textarea name="content" rows="8"><?php echo h($_POST['content'] ?? ''); ?></textarea>
    </label>
    <button type="submit">Save</button>
    <a class="btn" href="/admin/">Cancel</a>
</form>
<?php render_footer(); ?>