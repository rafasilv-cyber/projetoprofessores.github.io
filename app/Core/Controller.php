<?php
declare(strict_types=1);
namespace App\Core;

class Controller
{
    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        $currentUser = Auth::user();
        $admin = Auth::adminView();
        $notifications = $currentUser ? (new \App\Models\Reservation())->notifications((int) $currentUser['id'], $admin) : [];
        require dirname(__DIR__) . '/Views/layout.php';
    }
    public function error(int $code, string $message): void
    {
        http_response_code($code);
        require dirname(__DIR__) . '/Views/error.php';
    }
    protected function redirect(string $page, array $params = []): never
    {
        header('Location: ' . \url($page, $params), true, 303);
        exit;
    }
    protected function query(string $key, string $default = ''): string
    {
        return isset($_GET[$key]) && is_string($_GET[$key]) ? mb_substr(trim($_GET[$key]), 0, 200) : $default;
    }
    protected function flash(string $message, string $type = 'success'): void
    {
        $_SESSION['flash'] = compact('message', 'type');
    }
}
