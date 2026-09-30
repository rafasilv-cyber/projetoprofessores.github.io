<?php
declare(strict_types=1);
namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        return $_SESSION['_token'] ??= bin2hex(random_bytes(32));
    }

    public static function valid(mixed $value): bool
    {
        return is_string($value) && isset($_SESSION['_token']) && hash_equals($_SESSION['_token'], $value);
    }
}
