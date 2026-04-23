<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0">Completa los datos del usuario.</p>
    </div>
    <button type="button" id="backUserBtn" class="btn btn-outline-secondary">Volver</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form id="userForm" method="post" action="<?= $userData ? '/users/' . (int) $userData['id'] . '/update' : '/users/store' ?>">
            <?= Csrf::input() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input
                        type="text"
                        class="form-control"
                        name="name"
                        value="<?= htmlspecialchars((string) ($userData['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Correo</label>
                    <input
                        type="email"
                        class="form-control"
                        name="email"
                        value="<?= htmlspecialchars((string) ($userData['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Rol</label>
                    <select class="form-select" name="role">
                        <option value="user" <?= (($userData['role'] ?? 'user') === 'user') ? 'selected' : '' ?>>Usuario</option>
                        <option value="admin" <?= (($userData['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrador</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Contraseña <?= $userData ? '(dejar vacía para conservar)' : '' ?></label>
                    <input
                        type="password"
                        class="form-control"
                        name="password"
                        <?= $userData ? '' : 'required' ?>
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Activo</label>
                    <select class="form-select" name="is_active">
                        <option value="1" <?= ((int) ($userData['is_active'] ?? 1) === 1) ? 'selected' : '' ?>>Sí</option>
                        <option value="0" <?= ((int) ($userData['is_active'] ?? 1) === 0) ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <button type="button" id="cancelUserBtn" class="btn btn-outline-secondary">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="unsavedUserModal" tabindex="-1" aria-labelledby="unsavedUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unsavedUserModalLabel">Cambios sin guardar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                Has realizado cambios en este usuario. ¿Qué deseas hacer?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Seguir editando
                </button>
                <button type="button" id="discardUserChangesBtn" class="btn btn-outline-danger">
                    Descartar cambios
                </button>
                <button type="button" id="saveUserChangesBtn" class="btn btn-primary">
                    Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('userForm');
    const backBtn = document.getElementById('backUserBtn');
    const cancelBtn = document.getElementById('cancelUserBtn');
    const saveBtn = document.getElementById('saveUserChangesBtn');
    const discardBtn = document.getElementById('discardUserChangesBtn');
    const modalElement = document.getElementById('unsavedUserModal');

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

    function leaveToUsers() {
        window.location.href = '/users';
    }

    function handleLeaveAttempt() {
        checkDirty();

        if (!isDirty) {
            leaveToUsers();
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

    discardBtn.addEventListener('click', leaveToUsers);

    window.addEventListener('beforeunload', function (event) {
        checkDirty();

        if (isDirty && !isSubmitting) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
});
</script>