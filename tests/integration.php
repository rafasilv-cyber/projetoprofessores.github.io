<?php
declare(strict_types=1);
// Executa sobre o banco configurado, em uma transação revertida no final.
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\{Database, Validation, Csrf};
use App\Models\{User, Category, Ticket};

$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException('FALHOU: ' . $message);
    }
    $checks++;
    echo "OK: {$message}\n";
}

$db = Database::connection();
$db->beginTransaction();
try {
    $unique = bin2hex(random_bytes(5));
    $users = new User($db);
    $categories = new Category($db);
    $tickets = new Ticket($db);

    $user = $users->save(['name' => 'Teste ' . $unique, 'email' => $unique . '@example.test', 'role' => 'Usuário']);
    $technician = $users->save(['name' => 'Técnico ' . $unique, 'email' => 'tecnico-' . $unique . '@example.test', 'role' => 'Técnico']);
    $category = $categories->save(['name' => 'Categoria ' . $unique, 'description' => 'Categoria temporária de teste']);
    $ticket = $tickets->save(['subject' => 'Chamado ' . $unique, 'description' => 'Descrição do chamado', 'user_id' => $user, 'category_id' => $category, 'assignee_id' => $technician, 'status' => 'Aberto', 'priority' => 'Média']);
    check($tickets->detail($ticket)['user_name'] === 'Teste ' . $unique, 'Chamado criado com solicitante relacionado');
    check(count($users->search($unique)) === 2, 'Pesquisa de usuários');
    check(count($categories->search($unique)) === 1, 'Pesquisa de categorias');
    check(count($tickets->search($unique)) === 1, 'Pesquisa de chamados');
    check(count($tickets->search("' OR 1=1 --")) === 0, 'Pesquisa parametrizada não permite SQL injection');
    check(count($tickets->search($unique, 'Resolvido')) === 0, 'Filtro de status');

    $users->save(['name' => 'Nome alterado'], $user);
    $categories->save(['description' => 'Descrição alterada'], $category);
    $tickets->save(['status' => 'Resolvido'], $ticket);
    check($users->find($user)['name'] === 'Nome alterado', 'Editar usuário');
    check($categories->find($category)['description'] === 'Descrição alterada', 'Editar categoria');
    check($tickets->find($ticket)['status'] === 'Resolvido', 'Editar chamado');
    check(count($tickets->search($unique, 'Resolvido', 'Média', (string) $category)) === 1, 'Filtros combinados');
    check($users->emailTaken($unique . '@example.test'), 'Detectar e-mail duplicado');
    check(!$users->emailTaken($unique . '@example.test', $user), 'Permitir o próprio e-mail ao editar');
    check($categories->nameTaken('Categoria ' . $unique), 'Detectar categoria duplicada');

    foreach ([[$users, $user], [$categories, $category]] as [$model, $id]) {
        try {
            $model->delete($id);
            throw new RuntimeException('A chave estrangeira não impediu a exclusão.');
        } catch (PDOException $error) {
            check($error->errorInfo[1] === 1451, 'Impedir exclusão de registro vinculado');
        }
    }
    $users->delete($technician);
    check($tickets->find($ticket)['assignee_id'] === null, 'Remover responsável sem perder chamado');

    $validation = new Validation();
    $validation->text(['name' => " \t\n\u{00A0}"], 'name', 'Nome', 120)
        ->email(['email' => 'invalido'])
        ->choice(['status' => 'inventado'], 'status', Ticket::STATUSES)
        ->reference(['user_id' => -1], 'user_id', fn ($id) => $users->find($id) !== null);
    check(count($validation->errors) === 4, 'Rejeitar espaços, e-mail inválido, status inválido e referência inválida');
    $validation = new Validation();
    $validation->text(['name' => ['entrada malformada']], 'name', 'Nome', 120);
    check(isset($validation->errors['name']), 'Rejeitar array em campo textual');
    $_SESSION = [];
    check(!Csrf::valid('token-invalido'), 'Rejeitar CSRF ausente');
    check(Csrf::valid(Csrf::token()), 'Aceitar CSRF da sessão');
    check(!Csrf::valid(['token']), 'Rejeitar CSRF malformado');
    check(e('<script>') === '&lt;script&gt;', 'Escapar HTML contra XSS');

    $tickets->delete($ticket);
    $categories->delete($category);
    $users->delete($user);
    check($tickets->find($ticket) === null && $categories->find($category) === null && $users->find($user) === null, 'Excluir os três tipos de registros');
    echo "\n{$checks} verificações passaram.\n";
} finally {
    $db->rollBack();
}
