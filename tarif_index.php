<?php
require_once __DIR__ . "/../../app/middleware.php";
$u = require_role('admin');

$rows = $pdo->query("SELECT id_tarif, jenis_kendaraan, tarif_per_jam FROM tb_tarif ORDER BY id_tarif DESC")
            ->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tarif Parkir</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body{background:#f6f7fb}
    .card-soft{border:0;border-radius:16px;box-shadow:0 10px 30px rgba(15,23,42,.06)}
  </style>
</head>
<body>
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h4 class="mb-0 fw-bold">Tarif Parkir</h4>
      <div class="text-muted small">Kelola tarif per jam berdasarkan jenis kendaraan</div>
    </div>
    <div class="d-flex gap-2">
      <a href="/rehan/public/admin/dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Dashboard
      </a>
      <a href="/rehan/public/admin/tarif_create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Tambah Tarif
      </a>
    </div>
  </div>

  <div class="card card-soft">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:80px;">No</th>
              <th>Jenis Kendaraan</th>
              <th>Tarif / Jam</th>
              <th style="width:170px;" class="text-end">Edit | Hapus</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada tarif.</td></tr>
          <?php endif; ?>
          <?php $no=1; foreach($rows as $r): ?>
            <tr>
              <td class="text-muted"><?= $no++ ?></td>
              <td class="fw-semibold"><?= htmlspecialchars($r['jenis_kendaraan']) ?></td>
              <td>Rp <?= number_format((float)$r['tarif_per_jam'],0,',','.') ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary"
                   href="/rehan/public/admin/tarif_edit.php?id=<?= (int)$r['id_tarif'] ?>">
                  <i class="bi bi-pencil"></i> Edit
                </a>
                <a class="btn btn-sm btn-outline-danger ms-1"
                   href="/rehan/public/admin/tarif_delete.php?id=<?= (int)$r['id_tarif'] ?>"
                   onclick="return confirm('Yakin hapus tarif ini?')">
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
