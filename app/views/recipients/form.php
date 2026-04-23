<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0">Captura o ajusta los datos del destinatario.</p>
    </div>
    <button type="button" id="backRecipientBtn" class="btn btn-outline-secondary">Volver</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form id="recipientForm" method="post" action="<?= $recipient ? '/recipients/' . (int) $recipient['id'] . '/update' : '/recipients/store' ?>">
            <?= Csrf::input() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Correo</label>
                    <input
                        type="email"
                        class="form-control"
                        name="email"
                        value="<?= htmlspecialchars((string) ($recipient['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Nombre</label>
                    <input
                        type="text"
                        class="form-control"
                        name="first_name"
                        value="<?= htmlspecialchars((string) ($recipient['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Apellido</label>
                    <input
                        type="text"
                        class="form-control"
                        name="last_name"
                        value="<?= htmlspecialchars((string) ($recipient['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Institución</label>
                    <input
                        type="text"
                        class="form-control"
                        name="institution"
                        value="<?= htmlspecialchars((string) ($recipient['institution'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">País</label>
                    <input
                        type="text"
                        class="form-control"
                        name="country"
                        value="<?= htmlspecialchars((string) ($recipient['country'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Segmento</label>
                    <input
                        type="text"
                        class="form-control"
                        name="segment"
                        value="<?= htmlspecialchars((string) ($recipient['segment'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="status">
                        <option value="active" <?= (($recipient['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Activo</option>
                        <option value="inactive" <?= (($recipient['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactivo</option>
                        <option value="unsubscribed" <?= (($recipient['status'] ?? '') === 'unsubscribed') ? 'selected' : '' ?>>Desuscrito</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Consentimiento</label>
                    <input
                        type="datetime-local"
                        class="form-control"
                        name="consent_at"
                        value="<?= !empty($recipient['consent_at']) ? date('Y-m-d\TH:i', strtotime((string) $recipient['consent_at'])) : '' ?>"
                    >
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <button type="button" id="cancelRecipientBtn" class="btn btn-outline-secondary">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="unsavedChangesModal" tabindex="-1" aria-labelledby="unsavedChangesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unsavedChangesModalLabel">Cambios sin guardar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                Has realizado cambios en este destinatario. ¿Qué deseas hacer?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Seguir editando
                </button>
                <button type="button" id="discardChangesBtn" class="btn btn-outline-danger">
                    Descartar cambios
                </button>
                <button type="button" id="saveChangesBtn" class="btn btn-primary">
                    Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('recipientForm');
    const cancelBtn = document.getElementById('cancelRecipientBtn');
    const backBtn = document.getElementById('backRecipientBtn');
    const saveBtn = document.getElementById('saveChangesBtn');
    const discardBtn = document.getElementById('discardChangesBtn');
    const modalElement = document.getElementById('unsavedChangesModal');

    if (!form || !cancelBtn || !backBtn || !saveBtn || !discardBtn || !modalElement) {
        return;
    }

    const modal = new bootstrap.Modal(modalElement);
    let isDirty = false;
    let isSubmitting = false;

    function snapshotForm() {
        const data = {};
        const formData = new FormData(form);

        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }

        return JSON.stringify(data);
    }

    const initialSnapshot = snapshotForm();

    function checkDirty() {
        isDirty = snapshotForm() !== initialSnapshot;
    }

    function handleLeaveAttempt() {
        checkDirty();

        if (!isDirty) {
            window.location.href = '/recipients';
            return;
        }

        modal.show();
    }

    form.addEventListener('input', checkDirty);
    form.addEventListener('change', checkDirty);

    form.addEventListener('submit', function () {
        isSubmitting = true;
    });

    cancelBtn.addEventListener('click', handleLeaveAttempt);
    backBtn.addEventListener('click', handleLeaveAttempt);

    saveBtn.addEventListener('click', function () {
        isSubmitting = true;
        form.submit();
    });

    discardBtn.addEventListener('click', function () {
        window.location.href = '/recipients';
    });

    window.addEventListener('beforeunload', function (event) {
        checkDirty();

        if (isDirty && !isSubmitting) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
});
</script>