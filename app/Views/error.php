<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Não foi possível abrir a página</title>
    <link rel="stylesheet" href="public/assets/app.css">
    <link rel="icon" href="public/assets/logo.svg">
</head>
<body>
    <main class="card card-body narrow success-card">
        <img src="public/assets/logo.svg" alt="SalaHub" width="40" height="40">
        <h1>Não foi possível abrir a página</h1>
        <p><?= e($message) ?></p>
        <a class="button" href="<?= e(url()) ?>">Voltar ao início</a>
    </main>
</body>
</html>
