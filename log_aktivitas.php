<?php
require_once __DIR__ . "/../../app/middleware.php";
$u = require_role('admin'); // hanya admin

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Log Aktivitas</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    body { background:#f6f7fb; }
    .card-soft { border:0; border-radius:16px; box-shadow: 0 12px 30px rgba(15,23,42,.08); }
    .badge-dot{
      display:inline-flex; align-items:center; gap:8px;
    }
    .dot{
      width:10px; height:10px; border-radius:999px; background:#20c997;
      box-shadow:0 0 0 rgba(32,201,151,.4);
      animation:pulse 1.5s infinite;
    }
    @keyframes pulse{
      0%{ box-shadow:0 0 0 0 rgba(32,201,151,.45); }
      70%{ box-shadow:0 0 0 10px rgba(32,201,151,0); }
      100%{ box-shadow:0 0 0 0 rgba(32,201,151,0); }
    }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
  </style>
</head>

<body>
<div class="container py-4">

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h4 class="mb-0 fw-bold">Log Aktivitas</h4>
      <div class="text-muted small">
        <span class="badge-dot text-success"><span class="dot"></span> realtime</span>
        <span class="ms-2">Aktivitas terbaru semua user</span>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="/rehan/public/admin/dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Dashboard
      </a>
      <button class="btn btn-primary" id="btnRefresh">
        <i class="bi bi-arrow-clockwise"></i> Refresh
      </button>
    </div>
  </div>

  <div class="card card-soft mb-3">
    <div class="card-body">
      <form class="row g-2 align-items-center" onsubmit="return false;">
        <div class="col-md-6">
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" id="q" placeholder="Cari nama / username / aktivitas...">
          </div>
        </div>
        <div class="col-md-3">
          <select class="form-select" id="limit">
            <option value="50" selected>50 terbaru</option>
            <option value="100">100 terbaru</option>
            <option value="200">200 terbaru</option>
            <option value="500">500 terbaru</option>
          </select>
        </div>
        <div class="col-md-3 text-md-end">
          <small class="text-muted" id="lastUpdate">Memuat...</small>
        </div>
      </form>
    </div>
  </div>

  <div class="card card-soft">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:90px;">#</th>
              <th style="width:220px;">User</th>
              <th>Aktivitas</th>
              <th style="width:200px;">Waktu</th>
            </tr>
          </thead>
          <tbody id="tbodyLogs">
            <tr>
              <td colspan="4" class="text-center text-muted py-4">Memuat data...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const tbody = document.getElementById('tbodyLogs');
  const qEl = document.getElementById('q');
  const limitEl = document.getElementById('limit');
  const lastUpdate = document.getElementById('lastUpdate');
  const btnRefresh = document.getElementById('btnRefresh');

  function esc(s){
    return String(s ?? '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  async function loadLogs(){
    const q = qEl.value.trim();
    const limit = limitEl.value;

    try{
      const url = new URL('/rehan/public/admin/api_log_aktivitas.php', window.location.origin);
      url.searchParams.set('limit', limit);
      if(q) url.searchParams.set('q', q);

      const res = await fetch(url.toString(), { cache: 'no-store' });
      const data = await res.json();

      if(!data.ok){
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">${esc(data.message || 'Gagal memuat')}</td></tr>`;
        return;
      }

      if(!data.rows || data.rows.length === 0){
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">Belum ada data log.</td></tr>`;
        lastUpdate.textContent = "Update: " + (data.server_time || '');
        return;
      }

      let html = '';
      for(const r of data.rows){
        html += `
          <tr>
            <td class="text-muted">${esc(r.id_log)}</td>
            <td>
              <div class="fw-semibold">${esc(r.nama_lengkap)}</div>
              <div class="text-muted small">@${esc(r.username)} • ${esc(r.role)}</div>
            </td>
            <td class="mono">${esc(r.aktivitas)}</td>
            <td class="text-muted">${esc(r.waktu_aktivitas)}</td>
          </tr>
        `;
      }
      tbody.innerHTML = html;
      lastUpdate.textContent = "Update: " + (data.server_time || '');
    }catch(e){
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">Gagal memuat (cek endpoint/api).</td></tr>`;
    }
  }

  // realtime polling
  loadLogs();
  setInterval(loadLogs, 3000);

  // refresh manual
  btnRefresh.addEventListener('click', loadLogs);

  // auto search debounce
  let t=null;
  qEl.addEventListener('input', () => {
    clearTimeout(t);
    t = setTimeout(loadLogs, 300);
  });
  limitEl.addEventListener('change', loadLogs);
</script>
</body>
</html>
