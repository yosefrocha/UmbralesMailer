<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0">Define el nombre y descripción de la campaña.</p>
    </div>
    <a href="/campaigns" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card border-0 shadow-sm"><div class="card-body">
    <form method="post" action="<?= $campaign ? '/campaigns/' . (int) $campaign['id'] . '/update' : '/campaigns/store' ?>">
        <?= Csrf::input() ?>
        <div class="mb-3"><label class="form-label">Nombre de campaña</label><input type="text" class="form-control" name="name" value="<?= htmlspecialchars((string) ($campaign['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
        <div class="mb-3"><label class="form-label">Descripción</label><textarea class="form-control" name="description" rows="4"><?= htmlspecialchars((string) ($campaign['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></div>
        <button type="submit" class="btn btn-primary">Guardar campaña</button>
    </form>
</div></div>
