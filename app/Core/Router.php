<?php
declare(strict_types=1);
namespace App\Core;
use App\Controllers\{ManagementController, AuthController, SchoolController};

final class Router
{
    public function dispatch(ManagementController $management): void
    {
        $page = $_GET['page'] ?? 'dashboard';
        $action = $_GET['action'] ?? 'index';
        $routes = [
            'login'=>['index','store'], 'logout'=>['store'], 'switch'=>['store'], 'dashboard'=>['index'],
            'rooms'=>['index','show','create','edit','store','update','delete'],
            'booking'=>['create','check','store','success'], 'reservations'=>['index','cancel'],
            'approvals'=>['index','approve','reject'], 'calendar'=>['index'], 'settings'=>['index','update'],
            'users'=>['index','create','edit','store','update','delete'],
            'categories'=>['index','create','edit','store','update','delete'],
            'tickets'=>['index','show','create','edit','store','update','delete'],
        ];
        if (!is_string($page) || !is_string($action) || !isset($routes[$page]) || !in_array($action,$routes[$page],true)) {
            $management->error(404,'Página não encontrada.'); return;
        }
        $post = in_array($action,['store','update','delete','check','cancel','approve','reject'],true);
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== ($post?'POST':'GET')) {
            header('Allow: '.($post?'POST':'GET')); $management->error(405,'Método não permitido.'); return;
        }
        if ($post && !Csrf::valid($_POST['_token'] ?? null)) {
            $management->error(403,'Sua sessão expirou. Recarregue a página e tente novamente.'); return;
        }
        $auth = new AuthController();
        if ($page === 'login') { $auth->login($post); return; }
        if (!Auth::user()) { header('Location: '.\url('login'),true,303); return; }
        if ($page === 'logout') { $auth->logout(); return; }
        if ($page === 'switch') { $auth->switchView(); return; }
        $adminOnly = in_array($page,['users','categories','approvals','calendar'],true)
            || ($page==='tickets' && !in_array($action,['index','show'],true))
            || ($page==='rooms' && !in_array($action,['index','show'],true));
        if ($adminOnly && !Auth::isAdmin()) { $management->error(403,'Acesso reservado à coordenação.'); return; }
        $id = null;
        if (in_array($action,['show','edit','update','delete','cancel','approve','reject'],true) && $page !== 'settings') {
            $raw = $_GET['id'] ?? '';
            $id = is_scalar($raw) ? filter_var($raw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) : false;
            if (!$id) { $management->error(404,'Registro não encontrado.'); return; }
        }
        if (in_array($page,['users','categories','tickets'],true)) { $management->handle($page,$action,$id); return; }
        (new SchoolController())->handle($page,$action,$id);
    }
}

