<?php $appConfig = require CONFIG_PATH . '/app.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(($title ?? $appConfig['name']) . ' | ' . $appConfig['name'], ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --app-bg: #f4f6f9;
            --app-surface: #ffffff;
            --app-surface-2: #f8fafc;
            --app-border: #e5e7eb;
            --app-text: #0f172a;
            --app-muted: #64748b;
            --app-primary: #2563eb;
            --app-primary-soft: #eef2ff;
            --app-shadow: 0 0.125rem 0.75rem rgba(15, 23, 42, .08);
            --app-navbar: #111827;
        }

        [data-theme="dark"] {
            --app-bg: #0f172a;
            --app-surface: #111827;
            --app-surface-2: #1f2937;
            --app-border: #334155;
            --app-text: #e5e7eb;
            --app-muted: #94a3b8;
            --app-primary: #60a5fa;
            --app-primary-soft: rgba(96, 165, 250, .14);
            --app-shadow: 0 0.125rem 0.9rem rgba(0, 0, 0, .28);
            --app-navbar: #020617;
        }

        body { background: var(--app-bg); color: var(--app-text); }
        a { color: var(--app-primary); }
        .navbar { background: var(--app-navbar) !important; }
        .sidebar { min-height: calc(100vh - 56px); background: var(--app-surface); border-right: 1px solid var(--app-border); }
        .sidebar .nav-link { color: var(--app-text); border-radius: .6rem; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: var(--app-primary-soft); color: var(--app-primary); }
        .card, .modal-content, .dropdown-menu { background: var(--app-surface); color: var(--app-text); border-color: var(--app-border); }
        .table { --bs-table-bg: transparent; --bs-table-color: var(--app-text); --bs-table-border-color: var(--app-border); }
        .table-responsive { border-radius: .75rem; }
        .text-muted, .small.text-muted { color: var(--app-muted) !important; }
        .form-control, .form-select { background-color: var(--app-surface); color: var(--app-text); border-color: var(--app-border); }
        .form-control:focus, .form-select:focus { background-color: var(--app-surface); color: var(--app-text); }
        textarea.code-like { min-height: 260px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .stat-card { border: 1px solid var(--app-border); box-shadow: var(--app-shadow); }
        .metric-card { border: 1px solid var(--app-border); box-shadow: var(--app-shadow); border-radius: 1rem; }
        .metric-label { color: var(--app-muted); font-size: .82rem; }
        .metric-value { font-size: clamp(1.7rem, 4vw, 2.35rem); font-weight: 700; line-height: 1; }
        .progress { background-color: var(--app-surface-2); }
        .chart-box { height: 260px; }
        .bar-row { display: grid; grid-template-columns: minmax(95px, 1fr) 3fr 58px; gap: .75rem; align-items: center; }
        .bar-track { height: .75rem; background: var(--app-surface-2); border-radius: 999px; overflow: hidden; }
        .bar-fill { height: 100%; background: var(--app-primary); border-radius: 999px; }
        .status-dot { width: .7rem; height: .7rem; border-radius: 999px; display: inline-block; margin-right: .4rem; }
        .status-draft { background:#94a3b8; }
        .status-active { background:#16a34a; }
        .status-processing { background:#2563eb; }
        .status-paused { background:#f59e0b; }
        .status-completed { background:#10b981; }
        .status-failed, .status-cancelled { background:#dc2626; }
        .status-inactive { background:#64748b; }

        @media (max-width: 767.98px) {
            .navbar .container-fluid { align-items: flex-start; gap: .75rem; }
            .navbar-brand { font-size: 1rem; }
            .top-actions { width: 100%; justify-content: space-between; flex-wrap: wrap; }
            .sidebar { min-height: auto; border-right: 0; border-bottom: 1px solid var(--app-border); }
            .sidebar .nav { flex-direction: row !important; overflow-x: auto; padding-bottom: .25rem; }
            .sidebar .nav-link { white-space: nowrap; }
            main { padding-top: 1rem !important; }
            .page-header { align-items: flex-start !important; flex-direction: column; gap: 1rem; }
            .page-actions { width: 100%; }
            .page-actions .btn { flex: 1 1 auto; }
            .bar-row { grid-template-columns: 1fr; gap: .35rem; }
        }
    </style>
</head>
<script>
(function () {
    const savedTheme = localStorage.getItem('umbrales_mail_theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
})();

window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});

window.addEventListener('load', function () {
    const navEntries = performance.getEntriesByType('navigation');
    if (navEntries.length && navEntries[0].type === 'back_forward') {
        window.location.reload();
    }
});
</script>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="/">Umbrales Mail Sender</a>
        <?php if (Auth::check()): ?>
            <div class="top-actions d-flex align-items-center gap-2 text-white ms-auto">
                <button type="button" class="btn btn-outline-light btn-sm" id="themeToggle" aria-label="Cambiar modo visual">Modo oscuro</button>
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
<script>
(function () {
    const button = document.getElementById('themeToggle');
    if (!button) return;

    function syncLabel() {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        button.textContent = current === 'dark' ? 'Modo claro' : 'Modo oscuro';
    }

    button.addEventListener('click', function () {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('umbrales_mail_theme', next);
        syncLabel();
    });

    syncLabel();
})();
</script>
</body>
</html>
