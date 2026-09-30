<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Validation;
use App\Models\{User, Category, Ticket, Model};
use PDOException;

final class ManagementController extends \App\Core\Controller
{
    private const LABELS = ['users' => ['Usuários', 'usuário'], 'categories' => ['Categorias', 'categoria'], 'tickets' => ['Chamados', 'chamado']];

    public function handle(string $page, string $action, ?int $id): void
    {
        $canManage = \App\Core\Auth::isAdmin();
        $visibleTo = $page === 'tickets' && !$canManage ? (int) \App\Core\Auth::user()['id'] : null;
        if (!$canManage && ($page !== 'tickets' || !in_array($action, ['index', 'show'], true))) {
            $this->error(403, 'Acesso reservado à coordenação.');
            return;
        }
        $model = $this->model($page);
        $record = $id !== null ? ($page === 'tickets' ? $model->detail($id, $visibleTo) : $model->find($id)) : null;
        if ($id !== null && !$record) {
            $this->error(404, 'Registro não encontrado.');
            return;
        }
        switch ($action) {
            case 'index':
                $search = $this->query('q');
                $filters = ['status' => $this->query('status'), 'priority' => $this->query('priority'), 'category' => $this->query('category')];
                $rows = $page === 'tickets' ? $model->search($search, $filters['status'], $filters['priority'], $filters['category'], $visibleTo) : $model->search($search);
                $this->render('listing', [
                    'page' => $page, 'title' => $visibleTo !== null ? 'Meus Chamados' : self::LABELS[$page][0], 'rows' => $rows, 'search' => $search, 'canManage' => $canManage,
                    'filters' => $filters, 'categories' => $page === 'tickets' ? (new Category())->options() : [],
                ]);
                break;
            case 'create':
            case 'edit':
                $this->form($page, $record ?? []);
                break;
            case 'show':
                if ($page !== 'tickets') {
                    $this->redirect($page, ['action' => 'edit', 'id' => $id]);
                }
                $this->render('detail', ['page' => $page, 'title' => 'Detalhes do chamado', 'record' => $record, 'canManage' => $canManage]);
                break;
            case 'store':
            case 'update':
                $this->save($page, $model, $id);
                break;
            case 'delete':
                if ($page === 'users' && $id === (int) \App\Core\Auth::user()['id']) {
                    $this->flash('Não é possível excluir o usuário conectado.', 'error');
                    $this->redirect($page);
                }
                try {
                    $model->delete($id);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Registro excluído com sucesso.'];
                } catch (PDOException $exception) {
                    if (($exception->errorInfo[1] ?? null) !== 1451) {
                        throw $exception;
                    }
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Este registro possui chamados ou reservas vinculados e não pode ser excluído. Preserve o histórico ou remova os vínculos permitidos antes de tentar novamente.'];
                }
                $this->redirect($page);
        }
    }

    private function save(string $page, Model $model, ?int $id): void
    {
        $validation = new Validation();
        if ($page === 'users') {
            $validation->text($_POST, 'name', 'Nome', 120)->email($_POST)->choice($_POST, 'role', ['Administrador', 'Técnico', 'Usuário']);
            if (!isset($validation->errors['email']) && $model->emailTaken($validation->data['email'], $id)) {
                $validation->errors['email'] = 'Este e-mail já está cadastrado.';
            }
            $password = $_POST['password'] ?? '';
            if (!is_string($password) || (!$id && $password === '') || ($password !== '' && (trim($password) === '' || mb_strlen($password) < 8 || strlen($password) > 72))) {
                $validation->errors['password'] = 'Informe uma senha de 8 a 72 caracteres.';
            } elseif ($password !== '') {
                $validation->data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            if ($id === (int) \App\Core\Auth::user()['id'] && $validation->data['role'] !== 'Administrador') {
                $validation->errors['role'] = 'Você não pode remover seu próprio acesso de administrador.';
            }
        } elseif ($page === 'categories') {
            $validation->text($_POST, 'name', 'Nome', 100)->text($_POST, 'description', 'Descrição', 500);
            if (!isset($validation->errors['name']) && $model->nameTaken($validation->data['name'], $id)) {
                $validation->errors['name'] = 'Já existe uma categoria com este nome.';
            }
        } else {
            $users = new User();
            $categories = new Category();
            $validation->text($_POST, 'subject', 'Título', 180)->text($_POST, 'description', 'Descrição', 10000)
                ->choice($_POST, 'status', Ticket::STATUSES)->choice($_POST, 'priority', Ticket::PRIORITIES)
                ->reference($_POST, 'user_id', fn ($value) => $users->find($value) !== null)
                ->reference($_POST, 'category_id', fn ($value) => $categories->find($value) !== null)
                ->reference($_POST, 'assignee_id', fn ($value) => $users->find($value) !== null, false);
        }
        if (!$validation->errors) {
            try {
                $model->save($validation->data, $id);
                $_SESSION['flash'] = ['type' => 'success', 'message' => $id ? 'Alterações salvas com sucesso.' : 'Cadastro realizado com sucesso.'];
                $this->redirect($page);
            } catch (PDOException $exception) {
                $code = $exception->errorInfo[1] ?? null;
                if ($code === 1062) {
                    $validation->errors[$page === 'users' ? 'email' : 'name'] = 'Este valor já está cadastrado.';
                } elseif ($code === 1452) {
                    $validation->errors['_form'] = 'Um registro selecionado não existe mais. Revise as opções.';
                } else {
                    throw $exception;
                }
            }
        }
        http_response_code(422);
        $this->form($page, $validation->data + ['id' => $id], $validation->errors);
    }

    private function form(string $page, array $record, array $errors = []): void
    {
        $this->render('form', [
            'page' => $page, 'title' => (!empty($record['id']) ? 'Editar ' : 'Cadastrar ') . self::LABELS[$page][1],
            'record' => $record, 'errors' => $errors,
            'users' => $page === 'tickets' ? (new User())->options() : [],
            'categories' => $page === 'tickets' ? (new Category())->options() : [],
        ]);
    }

    private function model(string $page): Model
    {
        return match ($page) { 'users' => new User(), 'categories' => new Category(), 'tickets' => new Ticket() };
    }

}
