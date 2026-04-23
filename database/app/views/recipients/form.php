<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0">Captura o ajusta los datos del destinatario.</p>
    </div>
    <a href="/recipients" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card border-0 shadow-sm"><div class="card-body">
    <form method="post" action="<?= $recipient ? '/recipients/' . (int) $recipient['id'] . '/update' : '/recipients/store' ?>">
        <?= Csrf::input() ?>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Correo</label><input type="email" class="form-control" name="email" value="<?= htmlspecialchars((string) ($recipient['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="col-md-3"><label class="form-label">Nombre</label><input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars((string) ($recipient['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-3"><label class="form-label">Apellido</label><input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars((string) ($recipient['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-4"><label class="form-label">Institución</label><input type="text" class="form-control" name="institution" value="<?= htmlspecialchars((string) ($recipient['institution'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-4"><label class="form-label">País</label><input type="text" class="form-control" name="country" value="<?= htmlspecialchars((string) ($recipient['country'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-4"><label class="form-label">Segmento</label><input type="text" class="form-control" name="segment" value="<?= htmlspecialchars((string) ($recipient['segment'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-4"><label class="form-label">Estado</label><select class="form-select" name="status"><option value="active" <?= (($recipient['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Activo</option><option value="inactive" <?= (($recipient['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactivo</option><option value="unsubscribed" <?= (($recipient['status'] ?? '') === 'unsubscribed') ? 'selected' : '' ?>>Desuscrito</option></select></div>
            <div class="col-md-4"><label class="form-label">Consentimiento</label><input type="datetime-local" class="form-control" name="consent_at" value="<?= !empty($recipient['consent_at']) ? date('Y-m-d\TH:i', strtotime((string) $recipient['consent_at'])) : '' ?>"></div>
        </div>
        <div class="mt-3 d-flex gap-2"><button type="submit" class="btn btn-primary">Guardar</button><a href="/recipients" class="btn btn-outline-secondary">Cancelar</a></div>
    </form>
</div></div>
