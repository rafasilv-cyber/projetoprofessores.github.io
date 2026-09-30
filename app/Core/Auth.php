<?php
declare(strict_types=1);
namespace App\Core;
use App\Models\User;

final class Auth
{
    public static function user(): ?array
    {
        return isset($_SESSION['user_id']) ? (new User())->find((int) $_SESSION['user_id']) : null;
    }
    public static function isAdmin(): bool { return (self::user()['role'] ?? '') === 'Administrador'; }
    public static function adminView(): bool { return self::isAdmin() && ($_SESSION['view_mode'] ?? 'admin') === 'admin'; }
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION = ['user_id' => (int) $user['id'], 'view_mode' => $user['role'] === 'Administrador' ? 'admin' : 'teacher'];
    }
}
