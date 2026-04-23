<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Panel principal</h1>
        <p class="text-muted mb-0">Resumen general de la plataforma.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card stat-card"><div class="card-body">
            <h2 class="h6 text-muted">Destinatarios activos</h2>
            <div class="display-6"><?= (int) $activeRecipients ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card"><div class="card-body">
            <h2 class="h6 text-muted">Campañas creadas</h2>
            <div class="display-6"><?= (int) $campaignsCount ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card"><div class="card-body">
            <h2 class="h6 text-muted">Usuario activo</h2>
            <div class="fw-semibold"><?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-muted small"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h5">Ruta de trabajo</h2>
            <ol class="mb-0">
                <li>Configurar Amazon SES en <strong>Configuración</strong>.</li>
                <li>Cargar o importar destinatarios.</li>
                <li>Crear campaña y redactar el mensaje.</li>
                <li>Iniciar envío y monitorear la sesión.</li>
            </ol>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h5">Última sesión de envío</h2>
            <?php if (!empty($latestSession)): ?>
                <p class="mb-1"><strong>ID:</strong> #<?= (int) $latestSession['id'] ?></p>
                <p class="mb-1"><strong>Estado:</strong> <?= htmlspecialchars($latestSession['status'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-1"><strong>Procesados:</strong> <?= (int) $latestSession['processed_count'] ?> / <?= (int) $latestSession['total_count'] ?></p>
                <a href="/sending/<?= (int) $latestSession['id'] ?>" class="btn btn-sm btn-primary">Abrir monitoreo</a>
            <?php else: ?>
                <p class="text-muted mb-0">Aún no hay sesiones de envío registradas.</p>
            <?php endif; ?>
        </div></div>
    </div>
</div>
