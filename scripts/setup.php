<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
$config = require dirname(__DIR__) . '/config/database.php';
if (!preg_match('/^[a-zA-Z0-9_]+$/', $config['database'])) { throw new RuntimeException('Nome de banco inválido.'); }
$pdo = new PDO("mysql:host={$config['host']};port={$config['port']};charset=utf8mb4", $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$schema = file_get_contents(dirname(__DIR__) . '/database.sql');
$pdo->exec(str_replace('portal_chamados', $config['database'], $schema));
if (!$pdo->query("SHOW COLUMNS FROM users LIKE 'password_hash'")->fetch()) {
    $pdo->exec('ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL AFTER email');
}
echo "Banco de dados atualizado.\n";
if (!in_array('--demo', $argv, true)) { exit; }
require __DIR__ . '/seed.php';
