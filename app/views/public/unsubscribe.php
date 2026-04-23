<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center">
                <h1 class="h4 mb-3">Cancelación de suscripción</h1>
                <?php if ($recipient): ?>
                    <p>El correo <strong><?= htmlspecialchars($recipient['email'], ENT_QUOTES, 'UTF-8') ?></strong> ha quedado excluido de futuros envíos.</p>
                <?php else: ?>
                    <p>El enlace de desuscripción no es válido o ya no está disponible.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
