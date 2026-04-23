<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Cambiar contraseña</h1>
        <p class="text-muted mb-0">Actualiza tu contraseña para mantener el acceso seguro.</p>
    </div>
</div>

<?php if (!empty($force)): ?>
    <div class="alert alert-warning">Ingresaste con una contraseña temporal. Debes definir una nueva contraseña para continuar operando con normalidad.</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="/profile/password">
            <?= Csrf::input() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" class="form-control" name="password_confirmation" required>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Guardar contraseña</button>
            </div>
        </form>
    </div>
</div>
