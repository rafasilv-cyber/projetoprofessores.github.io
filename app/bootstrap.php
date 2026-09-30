<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $path = __DIR__ . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});
date_default_timezone_set('America/Sao_Paulo');
if (PHP_SAPI !== 'cli') {
    ini_set('session.gc_maxlifetime', '604800');
    session_start([
        'use_strict_mode' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; font-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $resource = 'dashboard', array $params = []): string
{
    return 'index.php?' . http_build_query(['page' => $resource] + $params);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(App\Core\Csrf::token()) . '">';
}

function app_config(string $key): mixed
{
    static $config;
    $config ??= require dirname(__DIR__) . '/config/app.php';
    return $config[$key] ?? null;
}
