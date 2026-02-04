<?php
require_once __DIR__ . "/../../app/middleware.php";
require_once __DIR__ . "/../../app/log.php";

$u = require_any_role(['petugas','admin']);

$error = "";

// helper rupiah
function rupiah($n) {
  return "Rp " . number_format((float)$n, 0, ',', '.');
}

// ambil daftar kendaraan yang masih "masuk"
$aktif = $pdo->query("
  SELECT 
    t.id_parkir,
    k.plat_nomor,
    k.jenis_kendaraan,
    t.waktu_masuk,
    a.nama_area
  FROM tb_transaksi t
  JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
  JOIN tb_area_parkir a ON a.id_area = t.id_area
  WHERE t.status = 'masuk'
  ORDER BY t.waktu_masuk DESC
")->fetchAll(PDO::FETCH_ASSOC);

$id_parkir = (int)($_POST['id_parkir'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($id_parkir <= 0) {
    $error = "Pilih plat/ID parkir yang masih berstatus masuk.";
  } else {
    try {
      $pdo->beginTransaction();

      // ambil transaksi aktif + tarif (lock transaksi)
      $stmt = $pdo->prepare("
        SELECT 
          t.id_parkir, t.waktu_masuk, t.id_area,
          k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik,
          a.nama_area,
          tr.tarif_per_jam
        FROM tb_transaksi t
        JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
        JOIN tb_area_parkir a ON a.id_area = t.id_area
        JOIN tb_tarif tr ON tr.id_tarif = t.id_tarif
        WHERE t.id_parkir = ? AND t.status = 'masuk'
        LIMIT 1
        FOR UPDATE
      ");
      $stmt->execute([$id_parkir]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$row) {
        throw new RuntimeException("Transaksi tidak ditemukan / sudah keluar.");
      }

      // hitung durasi jam (ceil), minimal 1 jam
      $wMasuk = new DateTime($row['waktu_masuk']);
      $wKeluar = new DateTime(); // now

      $diffMenit = (int) floor(($wKeluar->getTimestamp() - $wMasuk->getTimestamp()) / 60);
      if ($diffMenit < 1) $diffMenit = 1;

      $durasiJam = (int) ceil($diffMenit / 60);
      if ($durasiJam < 1) $durasiJam = 1;

      $tarifPerJam = (float) $row['tarif_per_jam'];
      $biayaTotal = $durasiJam * $tarifPerJam;

      // update transaksi keluar
      $up = $pdo->prepare("
        UPDATE tb_transaksi
        SET waktu_keluar = NOW(),
            durasi_jam = ?,
            biaya_total = ?,
            status = 'keluar'
        WHERE id_parkir = ?
      ");
      $up->execute([$durasiJam, $biayaTotal, $id_parkir]);

      // update area terisi -1 (aman)
      $upA = $pdo->prepare("UPDATE tb_area_parkir SET terisi = GREATEST(terisi - 1, 0) WHERE id_area = ?");
      $upA->execute([(int)$row['id_area']]);

      // log aktivitas
      add_log_current(
        $pdo,
        $u,
        "KELUAR: {$row['plat_nomor']} ({$row['jenis_kendaraan']}) area={$row['nama_area']} durasi={$durasiJam} jam biaya={$biayaTotal} id_parkir={$id_parkir}"
      );

      $pdo->commit();

      // tampilkan struk keluar + auto print
      ?>
      <!doctype html>
      <html lang="id">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Struk Keluar</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
          body { background:#f6f7fb; }
          .receipt { max-width: 400px; margin: 24px auto; background:#fff; border:1px dashed #ddd; border-radius:12px; }
          .receipt .head { padding:16px; border-bottom:1px dashed #ddd; text-align:center; }
          .receipt .body { padding:16px; }
          .kv { display:flex; justify-content:space-between; gap:12px; font-size:14px; margin-bottom:6px; }
          .kv .k { color:#6b7280; }
          .kv .v { font-weight:700; text-align:right; }
          .total { font-size: 18px; }
          .footer { padding:16px; border-top:1px dashed #ddd; text-align:center; font-size:12px; color:#6b7280; }
          @media print {
            body { background:#fff; }
            .no-print { display:none !important; }
            .receipt { border:none; }
          }
        </style>
      </head>
      <body>

      <div class="receipt">
        <div class="head">
          <div class="fw-bold">STRUK KELUAR PARKIR</div>
          <div class="small text-muted">TES UKK - Sistem Parkir</div>
        </div>

        <div class="body">
          <div class="kv"><div class="k">ID Parkir</div><div class="v">#<?= (int)$row['id_parkir'] ?></div></div>
          <div class="kv"><div class="k">Waktu Masuk</div><div class="v"><?= htmlspecialchars($row['waktu_masuk']) ?></div></div>
          <div class="kv"><div class="k">Waktu Keluar</div><div class="v"><?= htmlspecialchars($wKeluar->format('Y-m-d H:i:s')) ?></div></div>
          <hr>

          <div class="kv"><div class="k">Plat</div><div class="v"><?= htmlspecialchars($row['plat_nomor']) ?></div></div>
          <div class="kv"><div class="k">Jenis</div><div class="v"><?= htmlspecialchars($row['jenis_kendaraan']) ?></div></div>
          <div class="kv"><div class="k">Area</div><div class="v"><?= htmlspecialchars($row['nama_area']) ?></div></div>
          <div class="kv"><div class="k">Tarif/Jam</div><div class="v"><?= rupiah($tarifPerJam) ?></div></div>
          <div class="kv"><div class="k">Durasi</div><div class="v"><?= (int)$durasiJam ?> jam</div></div>
          <hr>

          <div class="kv total">
            <div class="k">TOTAL</div>
            <div class="v"><?= rupiah($biayaTotal) ?></div>
          </div>
        </div>

        <div class="footer">
          Terima kasih. Hati-hati di jalan!
        </div>
      </div>

      <div class="text-center no-print">
        <a class="btn btn-outline-secondary" href="/rehan/public/petugas/parkir_keluar.php">Keluar Lagi</a>
        <a class="btn btn-primary" href="/rehan/public/petugas/parkir_masuk.php">Parkir Masuk</a>
        <button class="btn btn-success" onclick="window.print()">Cetak Lagi</button>
      </div>

      <script>
        window.addEventListener('load', () => window.print());
      </script>
      </body>
      </html>
      <?php
      exit;

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $error = "Gagal proses keluar: " . $e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Parkir Keluar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body { background:#f6f7fb; }
    .card-soft { border:0; border-radius:16px; box-shadow: 0 10px 30px rgba(15,23,42,.06); }
  </style>
</head>
<body>
<div class="container py-4" style="max-width: 760px;">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0 fw-bold">Parkir Keluar</h4>
      <div class="text-muted small">Pilih plat yang masih berstatus masuk (otomatis muncul)</div>
    </div>
    <a href="/rehan/public/petugas/dashboard.php" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Dashboard
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

      <?php if (!$aktif): ?>
        <div class="alert alert-info mb-0">
          <i class="bi bi-info-circle me-1"></i>
          Tidak ada kendaraan yang sedang parkir (status masuk).
        </div>
      <?php else: ?>
        <form method="post" autocomplete="off">
          <div class="mb-3">
            <label class="form-label">Pilih Kendaraan (Status Masuk)</label>
            <select class="form-select" name="id_parkir" required>
              <option value="">-- Pilih Plat --</option>
              <?php foreach ($aktif as $a): ?>
                <option value="<?= (int)$a['id_parkir'] ?>" <?= ($id_parkir === (int)$a['id_parkir'])?'selected':'' ?>>
                  <?= htmlspecialchars($a['plat_nomor']) ?> | <?= htmlspecialchars($a['jenis_kendaraan']) ?>
                  | masuk: <?= htmlspecialchars($a['waktu_masuk']) ?> | area: <?= htmlspecialchars($a['nama_area']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Daftar ini otomatis dari transaksi yang masih berstatus masuk.</div>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-warning" type="submit">
              <i class="bi bi-box-arrow-left"></i> Proses Keluar & Cetak Struk
            </button>
            <a class="btn btn-light" href="/rehan/public/petugas/dashboard.php">Batal</a>
          </div>
        </form>
      <?php endif; ?>

    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
