<?php
require_once __DIR__ . "/../../app/middleware.php";
require_once __DIR__ . "/../../app/log.php";

$u = require_any_role(['petugas','admin']);

$error = "";
$plat = "";
$jenis = "motor";
$warna = "";
$pemilik = "";
$id_area = 0;

// ambil daftar area
$areas = $pdo->query("SELECT id_area, nama_area, kapasitas, terisi FROM tb_area_parkir ORDER BY nama_area ASC")
             ->fetchAll(PDO::FETCH_ASSOC);

function rupiah($n) {
  return "Rp " . number_format((float)$n, 0, ',', '.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $plat   = strtoupper(trim($_POST['plat_nomor'] ?? ''));
  $jenis  = $_POST['jenis_kendaraan'] ?? 'motor';
  $warna  = trim($_POST['warna'] ?? '');
  $pemilik= trim($_POST['pemilik'] ?? '');
  $id_area= (int)($_POST['id_area'] ?? 0);

  $allowed = ['motor','mobil','lainnya'];
  if (!in_array($jenis, $allowed, true)) $jenis = 'motor';

  if ($plat === '') {
    $error = "Plat nomor wajib diisi.";
  } elseif (strlen($plat) > 15) {
    $error = "Plat nomor maksimal 15 karakter.";
  } elseif ($id_area <= 0) {
    $error = "Area parkir wajib dipilih.";
  } else {
    try {
      $pdo->beginTransaction();

      // 1) ambil kendaraan berdasarkan plat
      $cek = $pdo->prepare("SELECT * FROM tb_kendaraan WHERE plat_nomor = :p LIMIT 1");
      $cek->execute(['p' => $plat]);
      $kend = $cek->fetch(PDO::FETCH_ASSOC);

      if (!$kend) {
        // insert kendaraan baru
        $stmt = $pdo->prepare("
          INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user)
          VALUES (:p, :j, :w, :pm, :idu)
        ");
        $stmt->execute([
          'p'   => $plat,
          'j'   => $jenis,
          'w'   => $warna,
          'pm'  => $pemilik,
          'idu' => (int)$u['id_user'],
        ]);
        $id_kendaraan = (int)$pdo->lastInsertId();
      } else {
        // kendaraan sudah ada, pakai data db (biar konsisten)
        $id_kendaraan = (int)$kend['id_kendaraan'];
        $jenis = $kend['jenis_kendaraan'];
        $warna = $kend['warna'] ?? $warna;
        $pemilik = $kend['pemilik'] ?? $pemilik;
      }

      // 2) cek transaksi aktif (status masuk)
      $cekAktif = $pdo->prepare("SELECT id_parkir FROM tb_transaksi WHERE id_kendaraan=? AND status='masuk' LIMIT 1");
      $cekAktif->execute([$id_kendaraan]);
      if ($cekAktif->fetchColumn()) {
        throw new RuntimeException("Kendaraan masih berstatus MASUK (belum keluar).");
      }

      // 3) lock area + cek kapasitas
      $stArea = $pdo->prepare("SELECT nama_area, kapasitas, terisi FROM tb_area_parkir WHERE id_area=? FOR UPDATE");
      $stArea->execute([$id_area]);
      $area = $stArea->fetch(PDO::FETCH_ASSOC);
      if (!$area) throw new RuntimeException("Area tidak valid.");

      if ((int)$area['terisi'] >= (int)$area['kapasitas']) {
        throw new RuntimeException("Area parkir penuh. Pilih area lain.");
      }

      // 4) ambil tarif sesuai jenis kendaraan
      $stTarif = $pdo->prepare("SELECT id_tarif, tarif_per_jam FROM tb_tarif WHERE jenis_kendaraan=? LIMIT 1");
      $stTarif->execute([$jenis]);
      $tarif = $stTarif->fetch(PDO::FETCH_ASSOC);
      if (!$tarif) throw new RuntimeException("Tarif untuk jenis kendaraan ini belum diset.");

      // 5) insert transaksi MASUK
      $insT = $pdo->prepare("
        INSERT INTO tb_transaksi
        (id_kendaraan, waktu_masuk, id_tarif, durasi_jam, biaya_total, status, id_user, id_area)
        VALUES (?, NOW(), ?, 0, 0, 'masuk', ?, ?)
      ");
      $insT->execute([$id_kendaraan, (int)$tarif['id_tarif'], (int)$u['id_user'], (int)$id_area]);
      $id_parkir = (int)$pdo->lastInsertId();

      // 6) update terisi area
      $upA = $pdo->prepare("UPDATE tb_area_parkir SET terisi = terisi + 1 WHERE id_area=?");
      $upA->execute([(int)$id_area]);

      // 7) log aktivitas
      $namaArea = $area['nama_area'];
      add_log_current($pdo, $u, "MASUK: {$plat} ({$jenis}) area={$namaArea} id_parkir={$id_parkir}");

      $pdo->commit();

      // 8) ambil data transaksi untuk struk
      $stmt = $pdo->prepare("
        SELECT 
          t.id_parkir, t.waktu_masuk, 
          k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik,
          a.nama_area,
          tr.tarif_per_jam
        FROM tb_transaksi t
        JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
        JOIN tb_area_parkir a ON a.id_area = t.id_area
        JOIN tb_tarif tr ON tr.id_tarif = t.id_tarif
        WHERE t.id_parkir = ?
        LIMIT 1
      ");
      $stmt->execute([$id_parkir]);
      $struk = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$struk) throw new RuntimeException("Gagal mengambil data struk.");

      // tampilkan struk (langsung exit agar tidak render form lagi)
      ?>
      <!doctype html>
      <html lang="id">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Struk Masuk</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
          body { background:#f6f7fb; }
          .receipt { max-width: 380px; margin: 24px auto; background:#fff; border:1px dashed #ddd; border-radius:12px; }
          .receipt .head { padding:16px; border-bottom:1px dashed #ddd; text-align:center; }
          .receipt .body { padding:16px; }
          .kv { display:flex; justify-content:space-between; gap:12px; font-size:14px; margin-bottom:6px; }
          .kv .k { color:#6b7280; }
          .kv .v { font-weight:600; text-align:right; }
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
          <div class="fw-bold">STRUK MASUK PARKIR</div>
          <div class="small text-muted">TES UKK - Sistem Parkir</div>
        </div>

        <div class="body">
          <div class="kv"><div class="k">ID Parkir</div><div class="v">#<?= (int)$struk['id_parkir'] ?></div></div>
          <div class="kv"><div class="k">Waktu Masuk</div><div class="v"><?= htmlspecialchars($struk['waktu_masuk']) ?></div></div>
          <hr>

          <div class="kv"><div class="k">Plat</div><div class="v"><?= htmlspecialchars($struk['plat_nomor']) ?></div></div>
          <div class="kv"><div class="k">Jenis</div><div class="v"><?= htmlspecialchars($struk['jenis_kendaraan']) ?></div></div>
          <div class="kv"><div class="k">Warna</div><div class="v"><?= htmlspecialchars($struk['warna'] ?: '-') ?></div></div>
          <div class="kv"><div class="k">Pemilik</div><div class="v"><?= htmlspecialchars($struk['pemilik'] ?: '-') ?></div></div>
          <div class="kv"><div class="k">Area</div><div class="v"><?= htmlspecialchars($struk['nama_area']) ?></div></div>
          <div class="kv"><div class="k">Tarif/Jam</div><div class="v"><?= rupiah($struk['tarif_per_jam']) ?></div></div>
        </div>

        <div class="footer">
          Simpan struk ini untuk proses keluar.
        </div>
      </div>

      <div class="text-center no-print">
        <a class="btn btn-outline-secondary" href="/rehan/public/petugas/parkir_masuk.php">Input Lagi</a>
        <a class="btn btn-primary" href="/rehan/public/petugas/parkir_keluar.php">Proses Keluar</a>
        <button class="btn btn-success" onclick="window.print()">Cetak Lagi</button>
      </div>

      <script>
        // auto print
        window.addEventListener('load', () => {
          window.print();
        });
      </script>
      </body>
      </html>
      <?php
      exit;

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $error = "Gagal proses masuk: " . $e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Parkir Masuk</title>
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
      <h4 class="mb-0 fw-bold">Input Kendaraan + Masuk Parkir</h4>
      <div class="text-muted small">Setelah simpan, struk masuk akan otomatis tampil & dicetak</div>
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

      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label class="form-label">Plat Nomor</label>
          <input class="form-control" name="plat_nomor" value="<?= htmlspecialchars($plat) ?>"
                 placeholder="Contoh: B1234XYZ" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Jenis Kendaraan</label>
          <select class="form-select" name="jenis_kendaraan" required>
            <option value="motor"   <?= $jenis==='motor'?'selected':'' ?>>motor</option>
            <option value="mobil"   <?= $jenis==='mobil'?'selected':'' ?>>mobil</option>
            <option value="lainnya" <?= $jenis==='lainnya'?'selected':'' ?>>lainnya</option>
          </select>
          <div class="form-text">Jika plat sudah terdaftar, jenis akan mengikuti data yang sudah ada.</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Warna</label>
          <input class="form-control" name="warna" value="<?= htmlspecialchars($warna) ?>" placeholder="Contoh: Hitam">
        </div>

        <div class="mb-3">
          <label class="form-label">Pemilik</label>
          <input class="form-control" name="pemilik" value="<?= htmlspecialchars($pemilik) ?>" placeholder="Nama pemilik (opsional)">
        </div>

        <div class="mb-3">
          <label class="form-label">Area Parkir</label>
          <select class="form-select" name="id_area" required>
            <option value="">-- Pilih Area --</option>
            <?php foreach ($areas as $a):
              $sisa = (int)$a['kapasitas'] - (int)$a['terisi'];
            ?>
              <option value="<?= (int)$a['id_area'] ?>" <?= ((int)$id_area === (int)$a['id_area'])?'selected':'' ?>>
                <?= htmlspecialchars($a['nama_area']) ?> (Sisa: <?= $sisa ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="d-flex gap-2">
          <button class="btn btn-primary" type="submit">
            <i class="bi bi-box-arrow-in-right"></i> Simpan & Masuk Parkir
          </button>
          <a class="btn btn-light" href="/rehan/public/petugas/dashboard.php">Batal</a>
        </div>

      </form>

    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
