<?php $appConfig = require CONFIG_PATH . '/app.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(($title ?? $appConfig['name']) . ' | ' . $appConfig['name'], ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .sidebar { min-height: calc(100vh - 56px); background: #fff; border-right: 1px solid #e5e7eb; }
        .sidebar .nav-link { color: #334155; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #eef2ff; color: #1d4ed8; }
        .table-responsive { border-radius: .5rem; }
        textarea.code-like { min-height: 260px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .stat-card { border: 0; box-shadow: 0 0.125rem 0.5rem rgba(0,0,0,.06); }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="/">Umbrales Mail Sender</a>
        <?php if (Auth::check()): ?>
            <div class="d-flex align-items-center gap-3 text-white">
                <span class="small"><?= htmlspecialchars(Auth::user()['name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(Auth::user()['role'], ENT_QUOTES, 'UTF-8') ?></span>
                <form method="post" action="/logout" class="m-0">
                    <?= Csrf::input() ?>
                    <button type="submit" class="btn btn-outline-light btn-sm">Cerrar sesión</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</nav>

<?php if (Auth::check()): ?>
<div class="container-fluid">
    <div class="row">
        <aside class="col-lg-2 col-md-3 sidebar py-3">
            <nav class="nav flex-column gap-1">
                <a class="nav-link" href="/">Panel</a>
                <a class="nav-link" href="/campaigns">Campañas</a>
                <a class="nav-link" href="/recipients">Destinatarios</a>
                <?php if (Auth::isAdmin()): ?>
                    <a class="nav-link" href="/users">Usuarios</a>
                    <a class="nav-link" href="/settings">Configuración</a>
                <?php endif; ?>
                <a class="nav-link" href="/profile/password">Mi contraseña</a>
            </nav>
        </aside>
        <main class="col-lg-10 col-md-9 py-4">
            <?php require APP_PATH . '/views/partials/flash.php'; ?>
            <?= $content ?>
        </main>
    </div>
</div>
<?php else: ?>
<main class="container py-5">
    <?php require APP_PATH . '/views/partials/flash.php'; ?>
    <?= $content ?>
</main>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
