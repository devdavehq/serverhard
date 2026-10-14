<?php

declare(strict_types=1);

/*
 * Everything the app needs (constants + SQLite access), in one file,
 * sitting OUTSIDE what any sane server would serve (see .htaccess
 * SECTION 5 / the deny-all .htaccess in this folder).
 */

const APP_NAME  = 'Hardened Demo';
const APP_ENV   = 'dev'; // 'dev' shows errors, 'prod' hides them
const DB_PATH   = __DIR__ . '/../storage/app.sqlite';
const SESSION_PATH = __DIR__ . '/../storage/sessions';

error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'dev' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('expose_php', '0');               // no X-Powered-By header
date_default_timezone_set('UTC');

/* ---- SQLite access (PDO), all queries go through prepared statements ---- */

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        if (!is_dir(dirname(DB_PATH))) {
            mkdir(dirname(DB_PATH), 0700, true);
        }
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $pdo->exec('PRAGMA foreign_keys = ON');
        initSchema($pdo);
    }
    return $pdo;
}

function initSchema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
             id            INTEGER PRIMARY KEY AUTOINCREMENT,
             username      TEXT    NOT NULL UNIQUE,
             password_hash TEXT    NOT NULL,
             created_at    TEXT    NOT NULL
         );
         CREATE TABLE IF NOT EXISTS items (
             id         INTEGER PRIMARY KEY AUTOINCREMENT,
             title      TEXT NOT NULL,
             content    TEXT NOT NULL DEFAULT \'\',
             created_at TEXT NOT NULL,
             updated_at TEXT NOT NULL
         );'
    );

    // Seed a default admin ONLY on first run. CHANGE IT after login.
    if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, ?)');
        $stmt->execute(['admin', password_hash('admin', PASSWORD_DEFAULT), date('c')]);
    }
}