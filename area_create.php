<?php
require_once __DIR__ . "/../../app/middleware.php";
$u = require_role('admin');

$error = "";
$nama_area = "";
$kapasitas = "";
$terisi = "0";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama_area = trim($_POST['nama_area'] ?? '');
  $kapasitas = trim($_POST['kapasitas'] ?? '');
  $terisi    = trim($_POST['terisi'] ?? '0');

  if ($nama_area === '' || $kapasitas === '') {
    $error = "Nama area dan kapasitas wajib diisi.";
  } elseif (!ctype_digit($kapasitas)) {
    $error = "Kapasitas harus angka (0 atau lebih).";
  } elseif ($terisi !== '' && !ctype_digit($terisi)) {
    $error = "Terisi harus angka (0 atau lebih).";
  } else {
    $kap = (int)$kapasitas;
    $ter = (int)($terisi === '' ? 0 : $terisi);

    if ($ter > $kap) {
      $error = "Nilai terisi tidak boleh lebih besar dari kapasitas.";
    } else {
      try {
        $stmt = $pdo->prepare("
          INSERT INTO tb_area_parkir (nama_area, kapasitas, terisi)
          VALUES (:nama, :kap, :ter)
        ");
        $stmt->execute([
          'nama' => $nama_area,
          'kap'  => $kap,
          'ter'  => $ter,
        ]);

        redirect("/rehan/public/admin/area_index.php");
      } catch (Throwable $e) {
        $error = "Gagal simpan: " . $e->getMessage();
      }
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah Area</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    body { background:#f6f7fb; }
    .card-soft {
      border: 0;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(15,23,42,.06);
    }
  </style>
</head>
<body>
<div class="container py-4" style="max-width: 760px;">

  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-0 fw-bold">Tambah Area Parkir</h4>
      <div class="text-muted small">Buat area baru</div>
    </div>
    <a href="/rehan/public/admin/area_index.php" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="card card-soft">
    <div class="card-body p-4">

      <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <div><?= htmlspecialchars($error) ?></div>
        </div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label class="form-label">Nama Area</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
            <input
              type="text"
              name="nama_area"
              class="form-control"
              placeholder="Contoh: Basement A"
              value="<?= htmlspecialchars($nama_area) ?>"
              required
            >
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Kapasitas</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-collection"></i></span>
              <input
                type="number"
                name="kapasitas"
                class="form-control"
                min="0"
                value="<?= htmlspecialchars($kapasitas) ?>"
                required
              >
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Terisi (opsional)</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-car-front"></i></span>
              <input
                type="number"
                name="terisi"
                class="form-control"
                min="0"
                value="<?= htmlspecialchars($terisi) ?>"
              >
            </div>
            <div class="form-text">Jika kosong, otomatis 0.</div>
          </div>
        </div>

        <div class="d-flex gap-2 mt-4">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Simpan
          </button>
          

    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
