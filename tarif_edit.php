<?php
require_once __DIR__ . "/../../app/middleware.php";
require_once __DIR__ . "/../../app/log.php"; // ✅ untuk log aktivitas
$u = require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id<=0) redirect("/tes_ukk/public/admin/tarif_index.php");

$stmt = $pdo->prepare("SELECT * FROM tb_tarif WHERE id_tarif=:id LIMIT 1");
$stmt->execute(['id'=>$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$data) redirect("/tes_ukk/public/admin/tarif_index.php");

$error="";
$jenis = (string)$data['jenis_kendaraan'];
$tarif = (string)$data['tarif_per_jam'];

if($_SERVER['REQUEST_METHOD']==='POST'){
  $tarif = trim($_POST['tarif_per_jam'] ?? '');
  if ($tarif==='' || !is_numeric($tarif) || (float)$tarif < 0) {
    $error="Tarif harus angka (>=0).";
  } else {
    try{
      $up = $pdo->prepare("UPDATE tb_tarif SET tarif_per_jam=:t WHERE id_tarif=:id");
      $up->execute(['t'=>(float)$tarif,'id'=>$id]);
      redirect("/tes_ukk/public/admin/tarif_index.php");
    } catch(Throwable $e){
      $error="Gagal update: ".$e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Tarif</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>body{background:#f6f7fb}.card-soft{border:0;border-radius:16px;box-shadow:0 10px 30px rgba(15,23,42,.06)}</style>
</head>
<body>
<div class="container py-4" style="max-width:760px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0 fw-bold">Edit Tarif</h4>
      <div class="text-muted small">Jenis: <?= htmlspecialchars($jenis) ?></div>
    </div>
    <a href="/rehan/public/admin/tarif_index.php" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="card card-soft">
    <div class="card-body p-4">
      <?php if($error): ?>
        <div class="alert alert-danger d-flex align-items-center">
          <i class="bi bi-exclamation-triangle me-2"></i><div><?= htmlspecialchars($error) ?></div>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">Jenis Kendaraan</label>
          <input class="form-control" value="<?= htmlspecialchars($jenis) ?>" disabled>
        </div>

        <div class="mb-3">
          <label class="form-label">Tarif per Jam</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input class="form-control" name="tarif_per_jam" value="<?= htmlspecialchars($tarif) ?>" required>
          </div>
        </div>

        <button class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
        <a class="btn btn-light" href="/rehan/public/admin/tarif_index.php">Batal</a>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
