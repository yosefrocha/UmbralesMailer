<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Mensaje de campaña</h1>
        <p class="text-muted mb-0"><?= htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <a href="/campaigns/<?= (int) $campaign['id'] ?>" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/message">
            <?= Csrf::input() ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Tipo de contenido</label>
                    <div class="d-flex gap-3 mt-2">
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="content_mode"
                                id="modeText"
                                value="text"
                                <?= (($campaign['content_mode'] ?? 'text') === 'text') ? 'checked' : '' ?>
                            >
                            <label class="form-check-label" for="modeText">Texto plano</label>
                        </div>

                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="content_mode"
                                id="modeHtml"
                                value="html"
                                <?= (($campaign['content_mode'] ?? '') === 'html') ? 'checked' : '' ?>
                            >
                            <label class="form-check-label" for="modeHtml">HTML</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Asunto</label>
                    <input
                        type="text"
                        class="form-control"
                        name="subject"
                        value="<?= htmlspecialchars((string) ($message['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Nombre remitente</label>
                    <input
                        type="text"
                        class="form-control"
                        name="from_name"
                        value="<?= htmlspecialchars((string) ($message['from_name'] ?? ($settings['ses_from_name'] ?? 'Equipo Umbrales')), ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Correo remitente</label>
                    <input
                        type="email"
                        class="form-control"
                        name="from_email"
                        value="<?= htmlspecialchars((string) ($message['from_email'] ?? ($settings['ses_from_email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Reply-To</label>
                    <input
                        type="email"
                        class="form-control"
                        name="reply_to"
                        value="<?= htmlspecialchars((string) ($message['reply_to'] ?? ($settings['ses_reply_to'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>
            </div>

            <div class="text-mode-block mb-4">
                <label class="form-label">Mensaje en texto plano</label>
                <textarea class="form-control" name="text_body" rows="12"><?= htmlspecialchars((string) ($message['text_body'] ?? "Hola,\n\nEscribe aquí tu mensaje.\n\nSaludos,\nEquipo Umbrales"), ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="form-text">
                    El sistema agregará automáticamente el enlace de baja al final.
                </div>
            </div>

            <div class="html-mode-block mb-4">
                <label class="form-label">Mensaje HTML</label>
                <textarea class="form-control code-like" name="html_body" rows="12"><?= htmlspecialchars((string) ($message['html_body'] ?? '<p>Hola,</p><p>Escribe aquí tu mensaje.</p><p>Saludos,<br>Equipo Umbrales</p>'), ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="form-text">
                    El sistema agregará automáticamente el bloque de baja al final.
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar mensaje</button>
                <a href="/campaigns/<?= (int) $campaign['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modeText = document.getElementById('modeText');
    const modeHtml = document.getElementById('modeHtml');
    const textArea = document.querySelector('textarea[name="text_body"]');
    const htmlArea = document.querySelector('textarea[name="html_body"]');

    function toggleModeBlocks() {
        const isText = modeText.checked;

        document.querySelectorAll('.text-mode-block').forEach(el => el.style.display = isText ? 'block' : 'none');
        document.querySelectorAll('.html-mode-block').forEach(el => el.style.display = isText ? 'none' : 'block');

        textArea.disabled = !isText;
        htmlArea.disabled = isText;
    }

    modeText.addEventListener('change', toggleModeBlocks);
    modeHtml.addEventListener('change', toggleModeBlocks);

    toggleModeBlocks();
});
</script>