<?php
declare(strict_types=1);

$config = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'portal_chamados',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
];
$local = __DIR__ . '/local.php';
return is_file($local) ? array_replace($config, require $local) : $config;
