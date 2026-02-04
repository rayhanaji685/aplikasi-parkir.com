<?php
require_once __DIR__ . "/../../app/middleware.php";
$u = require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect("/rehan/public/admin/area_index.php");

// ambil data area
$stmt = $pdo->prepare("
  SELECT id_area, nama_area, kapasitas
  FROM tb_area_parkir
  WHERE id_area = :id
  LIMIT 1
");
$stmt->execute(['id' => $id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) redirect("/rehan/public/admin/area_index.php");

$error = "";
$nama_area = (string)$data['nama_area'];
$kapasitas = (string)$data['kapasitas'];

// fungsi sync terisi (berdasarkan transaksi aktif)
function syncTerisi(PDO $pdo, int $idArea): int {
  $st = $pdo->prepare("SELECT COUNT(*) FROM tb_transaksi WHERE id_area = :id_area AND status = 'masuk'");
  $st->execute(['id_area' => $idArea]);
  $count = (int)$st->fetchColumn();

  $up = $pdo->prepare("UPDATE tb_area_parkir SET terisi = :terisi, updated_at = NOW() WHERE id_area = :id_area");
  $up->execute(['terisi' => $count, 'id_area' => $idArea]);

  return $count;
}

// setiap buka halaman -> sync dulu biar angka tidak ngaco
try {
  $terisi_view = syncTerisi($pdo, $id);
} catch (Throwable $e) {
  $terisi_view = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama_area = trim($_POST['nama_area'] ?? '');
  $kapasitas = trim($_POST['kapasitas'] ?? '');

  if ($nama_area === '' || $kapasitas === '') {
    $error = "Nama area dan kapasitas wajib diisi.";
  } elseif (!ctype_digit($kapasitas)) {
    $error = "Kapasitas harus angka (0 atau lebih).";
  } else {
    $kap = (int)$kapasitas;

    // ambil jumlah terisi dari transaksi aktif (bukan dari input)
    $st = $pdo->prepare("SELECT COUNT(*) FROM tb_transaksi WHERE id_area=:id_area AND status='masuk'");
    $st->execute(['id_area' => $id]);
    $terisi_now = (int)$st->fetchColumn();

    if ($kap < $terisi_now) {
      $error = "Kapasitas tidak boleh lebih kecil dari jumlah terisi saat ini ($terisi_now).";
    } else {
      try {
        $pdo->beginTransaction();

        // update area (tanpa input terisi)
        $up = $pdo->prepare("
          UPDATE tb_area_parkir
          SET nama_area = :nama, kapasitas = :kap, updated_at = NOW()
          WHERE id_area = :id
        ");
        $up->execute([
          'nama' => $nama_area,
          'kap'  => $kap,
          'id'   => $id,
        ]);

        // sync terisi supaya fix sesuai transaksi aktif
        $terisi_view = syncTerisi($pdo, $id);

        $pdo->commit();
        redirect("/rehan/public/admin/area_index.php");
      } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Gagal update: " . $e->getMessage();
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
  <title>Edit Area</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    body { background:#f6f7fb; }
    .card-soft { border:0; border-radius:16px; box-shadow: 0 10px 30px rgba(15,23,42,.06); }
  </style>
</head>
<body>
<div class="container py-4" style="max-width: 760px;">

  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-0 fw-bold">Edit Area Parkir</h4>
      <div class="text-muted small">ID Area: <?= (int)$id ?></div>
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
            <input type="text" name="nama_area" class="form-control"
                   value="<?= htmlspecialchars($nama_area) ?>" required>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Kapasitas</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-collection"></i></span>
              <input type="number" name="kapasitas" class="form-control"
                     min="0" value="<?= htmlspecialchars($kapasitas) ?>" required>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Terisi (Otomatis)</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-car-front"></i></span>
              <input type="number" class="form-control"
                     value="<?= (int)$terisi_view ?>" readonly>
            </div>
            <div class="form-text">
              Terisi dihitung otomatis dari transaksi dengan status <b>masuk</b> pada area ini.
            </div>
          </div>
        </div>

        <div class="d-flex gap-2 mt-4">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Update
          </button>
          <a href="/rehan/public/admin/area_index.php" class="btn btn-light">Batal</a>
        </div>
      </form>

    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
