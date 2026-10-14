<?php

declare(strict_types=1);

/*
 * Router for the PHP built-in dev server:
 *
 *     php -S 127.0.0.1:8080 router.php
 *
 * `php -S` IGNORES .htaccess, so this file mirrors the important Apache
 * rules so you can demo the hardening locally. On real Apache you don't
 * need it - .htaccess does all of this natively.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

/* 1. Dotfiles -> 403  (mirrors: RewriteRule (^|/)\. - [F]) */
if (preg_match('{(^|/)\.[^/]}', $uri)) {
    http_response_code(403);
    exit('Forbidden');
}

/* 2. Sensitive directories -> 403  (mirrors: SECTION 5) */
if (preg_match('{^/(?:includes|storage|config)(/|$)}', $uri)) {
    http_response_code(403);
    exit('Forbidden');
}

/* 3. Backup/temp/source leftovers -> 403  (mirrors: SECTION 4b) */
if (preg_match('{\.(?:bak|old|orig|~|swp|swo|save|sql|zip|tar|gz|log|sh|ps1|md|json|lock|dist|ini|tmp)$}i', $uri)) {
    http_response_code(403);
    exit('Forbidden');
}

/* 4. Scripts inside writeable-looking dirs -> 403  (mirrors: SECTION 6) */
if (preg_match('{^/(?:uploads?|files|media|tmp)/.*\.(?:php[0-9]?|phtml|phar|inc|module)$}i', $uri)) {
    http_response_code(403);
    exit('Forbidden');
}

/* 5. Traversal / null bytes -> 400  (mirrors: SECTION 5d) */
if (preg_match('{(?:\.\.(?:/|\\\\)|%2e%2e|%00)}i', $uri)) {
    http_response_code(400);
    exit('Bad request');
}

/* 6. Query-string exploit patterns -> 403  (mirrors: SECTION 11) */
if (isset($_SERVER['QUERY_STRING'])) {
    $q = $_SERVER['QUERY_STRING'];
    if (preg_match('{(<|%3C)|(union(\s|%20|\+)*select)|(concat|concat_ws|group_concat)\(|(base64_(en|de)code|sleep|benchmark)\(|(%2e%2e|%0d%0a)}i', $q)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

/* 7. Only the verbs the app uses */
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD', 'POST', 'OPTIONS'], true)) {
    http_response_code(405);
    exit('Method not allowed');
}

/* 8. Serve real files (built-in server executes .php ones); route the rest. */
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false;
}
require __DIR__ . '/index.php';