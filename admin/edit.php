<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$load = db()->prepare('SELECT id, title, content FROM items WHERE id = ?');
$load->execute([$id]);
$item = $load->fetch();

if ($item === false) {
    http_response_code(404);
    exit('Item not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $title = trim((string) ($_POST['title'] ?? ''));
    $body  = (string) ($_POST['content'] ?? '');

    if ($title === '' || mb_strlen($title) > 120) {
        set_flash('msg', 'Title is required (max 120 characters).');
    } else {
        $stmt = db()->prepare('UPDATE items SET title = ?, content = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$title, $body, date('c'), $id]);
        set_flash('msg', 'Item updated.');
    }
    redirect('/admin/');
}

render_header('Edit item', 'admin');
?>
<h1>Edit item #<?php echo (int) $item['id']; ?></h1>
<form method="post" action="/admin/edit.php?id=<?php echo (int) $item['id']; ?>" class="card">
    <?php echo csrf_field(); ?>
    <label>Title
        <input type="text" name="title" required maxlength="120" value="<?php echo h($item['title']); ?>">
    </label>
    <label>Content
        <textarea name="content" rows="8"><?php echo h($item['content']); ?></textarea>
    </label>
    <button type="submit">Save</button>
    <a class="btn" href="/admin/">Cancel</a>
</form>
<?php render_footer(); ?>