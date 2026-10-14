<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';

$items = db()->query('SELECT id, title, content, created_at FROM items ORDER BY id DESC')
    ->fetchAll();

render_header('Home', 'home');
?>
<h1><?php echo h(APP_NAME); ?></h1>
<p class="lede">A deliberately small PHP app whose real feature is
<strong>server hardening</strong>. This whole folder is the web root, so
everything sensitive (<code>includes/</code>, <code>storage/</code>) is
only protected by <code>.htaccess</code> — which is the point.</p>

<section class="card">
    <h2>Public items (<?php echo count($items); ?>)</h2>
    <?php if (!$items): ?>
        <p>Nothing here yet. <a href="/login.php">Log in</a> to add the first item.</p>
    <?php else: ?>
        <ul class="list">
        <?php foreach ($items as $item): ?>
            <li>
                <strong><?php echo h($item['title']); ?></strong>
                <span class="muted"><?php echo h($item['created_at']); ?></span>
                <p><?php echo nl2br(h($item['content'])); ?></p>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Hardening cheat sheet (what .htaccess does)</h2>
    <ul class="checks">
        <li><b>1 — Indexing &amp; traversal:</b> <code>Options -Indexes</code>, <code>FileETag None</code>, traversal URLs get 403.</li>
        <li><b>2 — Info disclosure:</b> <code>ServerSignature Off</code>, charset locked to UTF-8.</li>
        <li><b>3 — HTTP verbs:</b> TRACE/TRACK/PUT/DELETE denied, body capped at 10&nbsp;MB.</li>
        <li><b>4 — Sensitive files:</b> dotfiles (<code>.git/.env/.htaccess</code>) and *.bak *.sql *.zip *.log *.sh *.md etc. → 403.</li>
        <li><b>5 — Sensitive dirs:</b> <code>includes/</code>, <code>storage/</code>, <code>config/</code> → 403, plus per-folder deny-all + <code>php_flag engine Off</code>.</li>
        <li><b>6 — Uploads:</b> any PHP reaching an <code>uploads|files|media|tmp</code> path → 403.</li>
        <li><b>7 — Headers:</b> CSP, X-Frame-Options DENY, nosniff, Referrer-Policy, Permissions-Policy, HSTS, COOP/CORP.</li>
        <li><b>8 — Sessions:</b> strict mode, HttpOnly, SameSite=Lax, no URL ids, 30-min GC.</li>
        <li><b>9 — Runtime:</b> display_errors off, log_errors on, upload/post/input caps, execution &amp; memory limits.</li>
        <li><b>10 — HTTPS:</b> forceable 301, HSTS (commented until you deploy TLS).</li>
        <li><b>11 — Payloads:</b> script tags, SQLi (union/concat), base64/sleep, traversal/null bytes in the query string → 403.</li>
        <li><b>12 — Ops:</b> gzip + static-asset caching.</li>
        <li><b>13/14 — Optional:</b> admin IP whitelist and hotlink protection (commented).</li>
    </ul>
    <p class="muted">Try it with <code>php -S</code>: the built-in server ignores
    .htaccess, so <code>router.php</code> mirrors the important rules for
    local testing.</p>
</section>
<?php render_footer(); ?>