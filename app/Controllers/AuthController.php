<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller, Auth};
use App\Models\User;

final class AuthController extends Controller
{
    public function login(bool $submit): void
    {
        if (Auth::user()) { $this->redirect('dashboard'); }
        $error = '';
        $email = \app_config('demo_mode') ? 'ana.lima@escola.edu.br' : '';
        if ($submit) {
            $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            $portal = $_POST['portal'] ?? null;
            $attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'start' => time()];
            if (time() - $attempts['start'] > 300) { $attempts = ['count' => 0, 'start' => time()]; }
            $user = (new User())->byEmail($email);
            if ($attempts['count'] >= 10) {
                $error = 'Muitas tentativas. Aguarde cinco minutos e tente novamente.';
            } elseif ($email === '' || trim($password) === '') {
                $error = 'Informe seu e-mail e sua senha.';
            } elseif (!$user || !$user['password_hash'] || !password_verify($password, $user['password_hash'])) {
                $error = 'E-mail ou senha incorretos.';
                $attempts['count']++;
            } elseif (!is_string($portal) || !in_array($portal, ['teacher', 'admin'], true)) {
                $error = 'Selecione uma opção de acesso: Professor ou Coordenação.';
            } elseif ($user['role'] === 'Administrador' && $portal !== 'admin') {
                $error = 'Esta conta pertence à coordenação. Clique em "Entrar como Coordenação".';
            } elseif ($user['role'] === 'Usuário' && $portal !== 'teacher') {
                $error = 'Esta conta pertence a um professor. Clique em "Entrar como Professor".';
            } elseif (!in_array($user['role'], ['Administrador', 'Usuário'], true)) {
                $error = 'O perfil desta conta não está habilitado para estes acessos. Entre em contato com a coordenação.';
            } else {
                Auth::login($user);
                if (isset($_POST['remember'])) {
                    setcookie(session_name(),session_id(),['expires'=>time()+604800,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
                    $_SESSION['remember_until']=time()+604800;
                }
                $this->redirect('dashboard');
            }
            $_SESSION['login_attempts'] = $attempts;
            http_response_code(422);
        }
        require dirname(__DIR__) . '/Views/login.php';
    }
    public function logout(): void
    {
        $_SESSION = [];
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        session_destroy();
        $this->redirect('login');
    }
    public function switchView(): void
    {
        if (!Auth::isAdmin()) { $this->error(403, 'Acesso reservado à coordenação.'); return; }
        $_SESSION['view_mode'] = Auth::adminView() ? 'teacher' : 'admin';
        $this->redirect('dashboard');
    }
}
