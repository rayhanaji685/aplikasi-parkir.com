<?php
require_once __DIR__ . "/../../app/middleware.php";
$u = require_role('admin');

$stmt = $pdo->query("
  SELECT id_area, nama_area, kapasitas, COALESCE(terisi,0) AS terisi, updated_at
  FROM tb_area_parkir
  ORDER BY id_area DESC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Area Parkir</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    body { background:#f6f7fb; }
    .card-soft {
      border:0;
      border-radius:16px;
      box-shadow: 0 10px 30px rgba(15,23,42,.06);
    }
    .badge-dot {
      display:inline-block;
      width:10px;
      height:10px;
      border-radius:50%;
      margin-right:6px;
      vertical-align:middle;
    }
  </style>
</head>
<body>

<div class="container py-4">

  <!-- Header -->
  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-0 fw-bold">Area Parkir</h4>
      <div class="text-muted small">Kelola area & kapasitas parkir</div>
    </div>
    <div class="d-flex gap-2">
      <a href="/rehan/public/admin/dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Dashboard
      </a>
      <a href="/rehan/public/admin/area_create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Tambah Area
      </a>
    </div>
  </div>

  <!-- Table -->
  <div class="card card-soft">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:60px;">No</th>
              <th>Nama Area</th>
              <th style="width:100px;">Kapasitas</th>
              <th style="width:90px;">Terisi</th>
              <th style="width:90px;">Sisa</th>
              <th style="width:120px;">Status</th>
              <th style="width:200px;">Update Terakhir</th>
              <th style="width:170px;" class="text-end">Edit | Hapus
              </th>
            </tr>
          </thead>
          <tbody>

          <?php if (!$rows): ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-4">
                Belum ada data area parkir.
              </td>
            </tr>
          <?php endif; ?>

          <?php $no = 1; foreach ($rows as $r): ?>
            <?php
              $kap = max(0, (int)$r['kapasitas']);
              $ter = max(0, (int)$r['terisi']);
              if ($ter > $kap) $ter = $kap;
              $sisa = $kap - $ter;

              $penuh = ($kap > 0 && $sisa === 0);
              $statusText = $penuh ? "Penuh" : "Tersedia";
              $badgeClass = $penuh ? "bg-danger" : "bg-success";
            ?>
            <tr>
              <td class="text-muted"><?= $no++ ?></td>
              <td class="fw-semibold"><?= htmlspecialchars($r['nama_area']) ?></td>
              <td><?= $kap ?></td>
              <td><?= $ter ?></td>
              <td><?= $sisa ?></td>
              <td>
                <span class="badge-dot <?= $badgeClass ?>"></span>
                <?= $statusText ?>
              </td>

              <!-- Update Terakhir (REALTIME) -->
              <td>
                <div class="fw-semibold time-ago"
                     data-time="<?= htmlspecialchars($r['updated_at']) ?>">
                  -
                </div>
                <div class="text-muted small">
                  <?= date('d-m-Y H:i:s', strtotime($r['updated_at'])) ?>
                </div>
              </td>

              <td class="text-end">
                <a href="/rehan/public/admin/area_edit.php?id=<?= (int)$r['id_area'] ?>"
                   class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="/rehan/public/admin/area_delete.php?id=<?= (int)$r['id_area'] ?>"
                   onclick="return confirm('Yakin hapus area ini?')"
                   class="btn btn-sm btn-outline-danger ms-1">
                  <i class="bi bi-trash"></i> Hapus
                </a>
              </td>
            </tr>
          <?php endforeach; ?>

          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- TIME AGO REALTIME SCRIPT -->
<script>
function timeAgoIndo(datetime) {
  const time = new Date(datetime.replace(' ', 'T'));
  const now = new Date();

  let diff = Math.floor((now - time) / 1000);
  if (diff < 0) diff = 0;

  const seconds = diff % 60;
  const minutes = Math.floor(diff / 60) % 60;
  const hours   = Math.floor(diff / 3600) % 24;
  const days    = Math.floor(diff / 86400);

  let parts = [];

  if (days > 0) parts.push(days + ' hari');
  if (hours > 0) parts.push(hours + ' jam');
  if (minutes > 0 && days === 0) parts.push(minutes + ' menit');

  // kalau masih < 1 menit
  if (parts.length === 0) {
    return 'kurang dari 1 menit yang lalu';
  }

  return parts.join(' ') + ' yang lalu';
}

function updateTimeAgo() {
  document.querySelectorAll('.time-ago').forEach(el => {
    const dt = el.getAttribute('data-time');
    if (dt) el.textContent = timeAgoIndo(dt);
  });
}

// render awal
updateTimeAgo();

// update otomatis tiap 30 detik
setInterval(updateTimeAgo, 30000);
</script>


</body>
</html>
