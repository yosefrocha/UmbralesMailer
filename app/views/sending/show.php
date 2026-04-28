<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Monitoreo de envío #<?= (int) $session['id'] ?></h1>
        <p class="text-muted mb-0">Campaña: <?= htmlspecialchars($session['campaign_name'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <a href="/campaigns/<?= (int) $session['campaign_id'] ?>" class="btn btn-outline-secondary">Volver a campaña</a>
</div>

<div class="row g-4 mb-4">
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Estado</div><div class="h5 mb-0" id="js-status"><?= htmlspecialchars($session['status'], ENT_QUOTES, 'UTF-8') ?></div></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Procesados</div><div class="h5 mb-0"><span id="js-processed"><?= (int) $session['processed_count'] ?></span> / <span id="js-total"><?= (int) $session['total_count'] ?></span></div></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Enviados ✓</div><div class="h5 mb-0 text-success" id="js-success"><?= (int) $session['success_count'] ?></div></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Fallidos ✗</div><div class="h5 mb-0 text-danger" id="js-failed"><?= (int) $session['failed_count'] ?></div></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <div class="progress mb-3" role="progressbar">
        <div class="progress-bar bg-success" id="js-progress-bar" style="width: <?= (int) ($session['total_count'] > 0 ? round(((int) $session['processed_count'] / max(1, (int) $session['total_count'])) * 100) : 0) ?>%"></div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" id="js-start">Procesar siguiente lote</button>
        <button class="btn btn-outline-primary" id="js-auto">Auto procesar</button>
        <button class="btn btn-outline-warning" id="js-pause">Pausar</button>
        <button class="btn btn-outline-success" id="js-resume">Reanudar</button>
        <button class="btn btn-outline-danger" id="js-retry">Reintentar fallidos</button>
    </div>
    <p class="small text-muted mt-3 mb-0">Procesa lotes de <?= (int) $batchSize ?> destinatarios por solicitud. El envío se detiene automáticamente si la tasa de rebotes supera el 5%.</p>
</div></div>

<div class="card border-0 shadow-sm"><div class="card-body">
    <h2 class="h5">Últimos movimientos</h2>
    <div class="table-responsive">
        <table class="table table-sm align-middle" id="js-items-table">
            <thead><tr><th>ID</th><th>Correo</th><th>Estado</th><th>Error</th><th>Procesado</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>#<?= (int) $item['id'] ?></td>
                    <td><?= htmlspecialchars($item['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($item['status'] === 'sent'): ?>
                            <span class="badge bg-success">enviado</span>
                        <?php elseif ($item['status'] === 'failed'): ?>
                            <span class="badge bg-danger">fallido</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-danger"><?= htmlspecialchars((string) ($item['error_message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small"><?= htmlspecialchars((string) ($item['processed_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div></div>

<script>
const csrf = '<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>';
const sessionId = <?= (int) $session['id'] ?>;
const batchSize = <?= (int) $batchSize ?>;
let auto = false;
let busy = false;

function statusBadge(s) {
    const map = {sent:'success', failed:'danger', pending:'secondary', processing:'primary'};
    return `<span class="badge bg-${map[s]||'secondary'}">${e(s)}</span>`;
}

function updateUI(payload) {
    const s = payload.session;
    document.getElementById('js-status').textContent = s.status;
    document.getElementById('js-processed').textContent = s.processed_count;
    document.getElementById('js-total').textContent = s.total_count;
    document.getElementById('js-success').textContent = s.success_count;
    document.getElementById('js-failed').textContent = s.failed_count;
    const pct = s.total_count > 0 ? Math.round((s.processed_count / s.total_count) * 100) : 0;
    document.getElementById('js-progress-bar').style.width = pct + '%';
    const tbody = document.querySelector('#js-items-table tbody');
    tbody.innerHTML = '';
    (payload.items || []).forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>#${item.id}</td><td>${e(item.email)}</td><td>${statusBadge(item.status)}</td><td class="small text-danger">${e(item.error_message||'')}</td><td class="small">${e(item.processed_at||'—')}</td>`;
        tbody.appendChild(tr);
    });
}

function e(v) {
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function post(url, body={}) {
    const p = new URLSearchParams();
    p.append('_token', csrf);
    Object.entries(body).forEach(([k,v]) => p.append(k, v));
    const r = await fetch(url, {
        method:'POST',
        headers:{
            'Content-Type':'application/x-www-form-urlencoded',
            'Accept':'application/json',
            'X-Requested-With':'XMLHttpRequest'
        },
        body:p.toString()
    });
    const text = await r.text();
    let data;
    try {
        data = JSON.parse(text);
    } catch (err) {
        throw new Error(text ? text.substring(0, 500) : 'Respuesta vacía del servidor.');
    }
    if (!r.ok && !data.error) {
        data.error = 'Error HTTP ' + r.status;
    }
    return data;
}

async function getStatus() {
    const r = await fetch(`/sending/${sessionId}/status`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
    const text = await r.text();
    let d;
    try {
        d = JSON.parse(text);
    } catch (err) {
        console.error('Respuesta no JSON en status:', text);
        return;
    }
    updateUI(d);
}

async function processStep() {
    if (busy) return;
    busy = true;
    try {
        const d = await post(`/sending/${sessionId}/process`, {limit: batchSize});
        if (d.ok) {
            updateUI(d);
            if (auto && d.session.status === 'processing') setTimeout(processStep, 800);
            else auto = false;
        } else {
            auto = false;
            alert(d.error || 'Error al procesar lote.');
        }
    } catch(ex) {
        auto = false;
        alert('Error al comunicarse con el servidor: ' + (ex && ex.message ? ex.message : 'sin detalle.'));
    } finally {
        busy = false;
    }
}

document.getElementById('js-start').addEventListener('click', () => { auto = false; processStep(); });
document.getElementById('js-auto').addEventListener('click', () => { auto = true; processStep(); });
document.getElementById('js-pause').addEventListener('click', async () => { auto = false; await post(`/sending/${sessionId}/pause`); getStatus(); });
document.getElementById('js-resume').addEventListener('click', async () => { await post(`/sending/${sessionId}/resume`); getStatus(); });
document.getElementById('js-retry').addEventListener('click', async () => {
    if (!confirm('¿Reintentar todos los envíos fallidos?')) return;
    const d = await post(`/sending/${sessionId}/retry`);
    if (d.ok) { updateUI({session: d.session, items: []}); alert('Fallidos reseteados. Haz clic en "Procesar siguiente lote" para reenviar.'); }
    else alert(d.error || 'Error al reintentar.');
});

setInterval(getStatus, 5000);
</script>
