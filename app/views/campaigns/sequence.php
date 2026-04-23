<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Secuencia de campaña</h1>
        <p class="text-muted mb-0"><?= htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <a href="/campaigns/<?= (int) $campaign['id'] ?>" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/sequence">
            <?= Csrf::input() ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Tipo de contenido</label>
                    <div class="d-flex gap-3 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="content_mode" id="modeText" value="text" <?= (($campaign['content_mode'] ?? 'text') === 'text') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="modeText">Texto plano</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="content_mode" id="modeHtml" value="html" <?= (($campaign['content_mode'] ?? '') === 'html') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="modeHtml">HTML</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Inicio de secuencia</label>
                    <input type="datetime-local" class="form-control" name="sequence_start_at"
                        value="<?= !empty($campaign['sequence_start_at']) ? date('Y-m-d\TH:i', strtotime((string) $campaign['sequence_start_at'])) : '' ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Frecuencia</label>
                    <input type="text" class="form-control" value="Un día sí y un día no (cada 2 días)" disabled>
                </div>
            </div>

            <?php for ($i = 1; $i <= 10; $i++): ?>
                <?php $step = $steps[$i] ?? null; ?>
                <div class="card border mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h6 mb-0">Envío <?= $i ?></h2>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active[<?= $i ?>]" id="is_active_<?= $i ?>" <?= !empty($step['is_active']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active_<?= $i ?>">Activo</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Asunto</label>
                            <input type="text" class="form-control" name="subject[<?= $i ?>]"
                                value="<?= htmlspecialchars((string) ($step['subject'] ?? ('Mensaje ' . $i)), ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="text-mode-block mb-3">
                            <label class="form-label">Contenido en texto plano</label>
                            <textarea class="form-control" name="text_body[<?= $i ?>]" rows="8"><?= htmlspecialchars((string) ($step['text_body'] ?? "Hola,\n\nEscribe aquí el mensaje {$i}.\n\nSaludos,\nEquipo Umbrales"), ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text">El sistema agregará automáticamente el enlace de baja al final.</div>
                        </div>

                        <div class="html-mode-block mb-3">
                            <label class="form-label">Contenido HTML</label>
                            <textarea class="form-control code-like" name="html_body[<?= $i ?>]" rows="8"><?= htmlspecialchars((string) ($step['html_body'] ?? '<p>Hola,</p><p>Escribe aquí el mensaje ' . $i . '.</p><p>Saludos,<br>Equipo Umbrales</p>'), ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text">El sistema agregará automáticamente el bloque de baja.</div>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar secuencia</button>
                <a href="/campaigns/<?= (int) $campaign['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modeText = document.getElementById('modeText');
    const modeHtml = document.getElementById('modeHtml');

    function toggleModeBlocks() {
        const isText = modeText.checked;
        document.querySelectorAll('.text-mode-block').forEach(el => {
            el.style.display = isText ? 'block' : 'none';
        });
        document.querySelectorAll('.html-mode-block').forEach(el => {
            el.style.display = isText ? 'none' : 'block';
        });
    }

    modeText.addEventListener('change', toggleModeBlocks);
    modeHtml.addEventListener('change', toggleModeBlocks);

    toggleModeBlocks();
});
</script>