<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Iniciar sesión</h1>
                <p class="text-muted small">Acceso al panel de campañas.</p>
                <form method="post" action="/login" novalidate>
                    <?= Csrf::input() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Correo</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>
            </div>
        </div>
    </div>
</div>
