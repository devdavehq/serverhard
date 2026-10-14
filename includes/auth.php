<?php

declare(strict_types=1);

require_once __DIR__ . '/app.php';

/*
 * Hardened session bootstrap. Called first on every page.
 */

// Store sessions outside the web root (defense in depth).
if (SESSION_PATH !== '') {
    if (!is_dir(SESSION_PATH)) {
        mkdir(SESSION_PATH, 0700, true);
    }
    session_save_path(SESSION_PATH);
}

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? '') === '443';

    session_set_cookie_params([
        'lifetime' => 0,                          // session cookie (not persistent)
        'path'     => '/',
        'domain'   => '',                         // host-only cookie
        'secure'   => $isHttps,                   // requires HTTPS in production
        'httponly' => true,                       // no JS access
        'samesite' => 'Lax',                      // mitigates CSRF on top of tokens
    ]);
    session_name('SID');
    session_start();
}

// Regenerate the session id periodically to fight session fixation.
$now = time();
if (!isset($_SESSION['_created'])) {
    $_SESSION['_created'] = $now;
} elseif ($now - $_SESSION['_created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_created'] = $now;
}