<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/* Helpers used by every page. */

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function e(?string $value): void
{
    echo h($value);
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['csrf'] ?? '';
    if (empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], (string) $token)) {
        http_response_code(419);
        exit('Invalid or expired CSRF token. Go back and resubmit.');
    }
}

/* Only allow local /relative/ redirect targets (blocks open-redirect). */
function local_url(string $url): string
{
    if (!str_starts_with($url, '/')) {
        $url = '/' . $url;
    }
    if (str_starts_with($url, '//')) {
        $url = '/' . $url; // kill protocol-relative URLs
    }
    return $url;
}

/* Blinding-hash compare used for login brute-force feeler. */
function slow_equals(string $a, string $b): bool
{
    return hash_equals(hash('sha256', $a), hash('sha256', $b));
}