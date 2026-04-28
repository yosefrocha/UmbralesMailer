<?php
$flashError = $error ?? null;
$flashSuccess = $success ?? null;
?>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger alert-dismissible fade show app-flash-message" role="alert" data-flash-key="<?= htmlspecialchars(md5((string) $flashError), ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars((string) $flashError, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success alert-dismissible fade show app-flash-message" role="alert" data-flash-key="<?= htmlspecialchars(md5((string) $flashSuccess), ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars((string) $flashSuccess, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const seen = new Set();
    document.querySelectorAll('.app-flash-message').forEach(function (alertEl) {
        const key = alertEl.getAttribute('data-flash-key') || alertEl.textContent.trim();
        if (seen.has(key)) {
            alertEl.remove();
            return;
        }
        seen.add(key);
        window.setTimeout(function () {
            if (!alertEl.isConnected) return;
            if (window.bootstrap && bootstrap.Alert) {
                bootstrap.Alert.getOrCreateInstance(alertEl).close();
            } else {
                alertEl.remove();
            }
        }, 6000);
    });
});
</script>
