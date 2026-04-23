<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0">Define el nombre y descripción de la campaña.</p>
    </div>
    <button type="button" id="backCampaignBtn" class="btn btn-outline-secondary">Volver</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form id="campaignForm" method="post" action="<?= $campaign ? '/campaigns/' . (int) $campaign['id'] . '/update' : '/campaigns/store' ?>">
            <?= Csrf::input() ?>

            <div class="mb-3">
                <label class="form-label">Nombre de campaña</label>
                <input
                    type="text"
                    class="form-control"
                    name="name"
                    value="<?= htmlspecialchars((string) ($campaign['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars((string) ($campaign['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar campaña</button>
                <button type="button" id="cancelCampaignBtn" class="btn btn-outline-secondary">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="unsavedCampaignModal" tabindex="-1" aria-labelledby="unsavedCampaignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unsavedCampaignModalLabel">Cambios sin guardar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                Has realizado cambios en esta campaña. ¿Qué deseas hacer?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Seguir editando
                </button>
                <button type="button" id="discardCampaignChangesBtn" class="btn btn-outline-danger">
                    Descartar cambios
                </button>
                <button type="button" id="saveCampaignChangesBtn" class="btn btn-primary">
                    Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('campaignForm');
    const backBtn = document.getElementById('backCampaignBtn');
    const cancelBtn = document.getElementById('cancelCampaignBtn');
    const saveBtn = document.getElementById('saveCampaignChangesBtn');
    const discardBtn = document.getElementById('discardCampaignChangesBtn');
    const modalElement = document.getElementById('unsavedCampaignModal');

    if (!form || !backBtn || !cancelBtn || !saveBtn || !discardBtn || !modalElement) {
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

    function leaveToCampaigns() {
        window.location.href = '/campaigns';
    }

    function handleLeaveAttempt() {
        checkDirty();

        if (!isDirty) {
            leaveToCampaigns();
            return;
        }

        modal.show();
    }

    form.addEventListener('input', checkDirty);
    form.addEventListener('change', checkDirty);

    form.addEventListener('submit', function () {
        isSubmitting = true;
    });

    backBtn.addEventListener('click', handleLeaveAttempt);
    cancelBtn.addEventListener('click', handleLeaveAttempt);

    saveBtn.addEventListener('click', function () {
        isSubmitting = true;
        form.submit();
    });

    discardBtn.addEventListener('click', leaveToCampaigns);

    window.addEventListener('beforeunload', function (event) {
        checkDirty();

        if (isDirty && !isSubmitting) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
});
</script>