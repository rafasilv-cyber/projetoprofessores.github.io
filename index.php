<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

use App\Core\Router;
use App\Controllers\ManagementController;

try {
    (new Router())->dispatch(new ManagementController());
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    $message = 'Não foi possível concluir a operação. Verifique a configuração do banco de dados e tente novamente.';
    require __DIR__ . '/app/Views/error.php';
}
