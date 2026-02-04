<?php
require_once __DIR__ . "/../../app/middleware.php";
$u = require_role('owner'); // hanya owner

// filter
$tgl_mulai  = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_sampai = $_GET['tgl_sampai'] ?? date('Y-m-d');
$petugas_id = (int)($_GET['petugas_id'] ?? 0);
$status     = $_GET['status'] ?? 'keluar'; // keluar / masuk / all

// ambil list petugas utk filter
$petugasList = $pdo->query("
  SELECT id_user, nama_lengkap, username
  FROM tb_user
  WHERE role='petugas' AND status_aktif=1
  ORDER BY nama_lengkap ASC
")->fetchAll(PDO::FETCH_ASSOC);

// bangun query utama (detail transaksi)
$where = [];
$params = [];

$where[] = "DATE(t.waktu_masuk) BETWEEN :mulai AND :sampai";
$params['mulai'] = $tgl_mulai;
$params['sampai'] = $tgl_sampai;

if ($petugas_id > 0) {
  $where[] = "t.id_user = :pid";
  $params['pid'] = $petugas_id;
}
if ($status !== 'all') {
  $where[] = "t.status = :st";
  $params['st'] = $status;
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

// detail transaksi
$stmt = $pdo->prepare("
  SELECT
    t.id_parkir,
    t.waktu_masuk,
    t.waktu_keluar,
    t.durasi_jam,
    t.biaya_total,
    t.status,

    k.plat_nomor,
    k.jenis_kendaraan,

    a.nama_area,

    u.nama_lengkap AS petugas_nama,
    u.username AS petugas_username
  FROM tb_transaksi t
  JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
  JOIN tb_area_parkir a ON a.id_area = t.id_area
  JOIN tb_user u ON u.id_user = t.id_user
  $whereSql
  ORDER BY t.waktu_masuk DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// rekap ringkas (summary)
$sum = $pdo->prepare("
  SELECT
    COUNT(*) AS total_transaksi,
    SUM(CASE WHEN t.status='keluar' THEN 1 ELSE 0 END) AS total_selesai,
    SUM(CASE WHEN t.status='masuk' THEN 1 ELSE 0 END) AS total_masih_masuk,
    COALESCE(SUM(CASE WHEN t.status='keluar' THEN t.biaya_total ELSE 0 END),0) AS total_pendapatan
  FROM tb_transaksi t
  $whereSql
");
$sum->execute($params);
$summary = $sum->fetch(PDO::FETCH_ASSOC);

// helper format rupiah
function rupiah($n){
  return "Rp " . number_format((float)$n, 0, ',', '.');
}

// helper hari indo (karena DAYNAME MySQL sering English)
function hari_indo($english){
  $map = [
    'Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu',
    'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'
  ];
  return $map[$english] ?? $english;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Owner - Rekap Transaksi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body { background:#f6f7fb; }
    .card-soft { border:0; border-radius:16px; box-shadow: 0 10px 30px rgba(15,23,42,.06); }
  </style>
</head>
<body>
<div class="container py-4">



<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h4 class="mb-0 fw-bold">Rekap Transaksi</h4>
    <div class="text-muted small">
      Owner - laporan transaksi masuk/keluar berdasarkan petugas & tanggal
    </div>
  </div>

  <div class="d-flex gap-2">
    <!-- KEMBALI -->
    <a class="btn btn-outline-secondary" href="/rehan/public/owner/dashboard.php">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>

    <!-- LOGOUT -->
    <a class="btn btn-outline-danger" href="/rehan/public/logout.php"
       onclick="return confirm('Yakin ingin logout?')">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>
</div>

  

  <!-- FILTER -->
  <div class="card card-soft mb-3">
    <div class="card-body">
      <form class="row g-2 align-items-end" method="get">
        <div class="col-md-3">
          <label class="form-label">Tanggal Mulai</label>
          <input type="date" class="form-control" name="tgl_mulai" value="<?= htmlspecialchars($tgl_mulai) ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tanggal Sampai</label>
          <input type="date" class="form-control" name="tgl_sampai" value="<?= htmlspecialchars($tgl_sampai) ?>" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Petugas</label>
          <select class="form-select" name="petugas_id">
            <option value="0">Semua Petugas</option>
            <?php foreach($petugasList as $p): ?>
              <option value="<?= (int)$p['id_user'] ?>" <?= $petugas_id===(int)$p['id_user']?'selected':'' ?>>
                <?= htmlspecialchars($p['nama_lengkap'] ?: $p['username']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="keluar" <?= $status==='keluar'?'selected':'' ?>>keluar</option>
            <option value="masuk"  <?= $status==='masuk'?'selected':'' ?>>masuk</option>
            <option value="all"    <?= $status==='all'?'selected':'' ?>>all</option>
          </select>
        </div>

        <div class="col-md-1 d-grid">
          <button class="btn btn-primary" type="submit">
            <i class="bi bi-filter"></i>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- SUMMARY -->
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card card-soft"><div class="card-body">
        <div class="text-muted small">Total Transaksi</div>
        <div class="fs-4 fw-bold"><?= (int)$summary['total_transaksi'] ?></div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card card-soft"><div class="card-body">
        <div class="text-muted small">Selesai (Keluar)</div>
        <div class="fs-4 fw-bold"><?= (int)$summary['total_selesai'] ?></div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card card-soft"><div class="card-body">
        <div class="text-muted small">Masih Parkir (Masuk)</div>
        <div class="fs-4 fw-bold"><?= (int)$summary['total_masih_masuk'] ?></div>
      </div></div>
    </div>
    <div class="col-md-3">
      <div class="card card-soft"><div class="card-body">
        <div class="text-muted small">Pendapatan (Keluar)</div>
        <div class="fs-4 fw-bold"><?= rupiah($summary['total_pendapatan']) ?></div>
      </div></div>
    </div>
  </div>

  <!-- TABLE -->
  <div class="card card-soft">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Tanggal</th>
              <th>Hari</th>
              <th>Jam</th>
              <th>Tahun</th>
              <th>Plat</th>
              <th>Jenis</th>
              <th>Area</th>
              <th>Petugas</th>
              <th>Masuk</th>
              <th>Keluar</th>
              <th>Durasi</th>
              <th class="text-end">Biaya</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php if(!$rows): ?>
            <tr><td colspan="14" class="text-center text-muted py-4">Tidak ada data.</td></tr>
          <?php endif; ?>

          <?php $no=1; foreach($rows as $r): 
            $dtMasuk = new DateTime($r['waktu_masuk']);
            $tanggal = $dtMasuk->format('Y-m-d');
            $tahun   = $dtMasuk->format('Y');
            $jam     = $dtMasuk->format('H:i');
            $hariEn  = $dtMasuk->format('l');
            $hariId  = hari_indo($hariEn);
          ?>
            <tr>
              <td class="text-muted"><?= $no++ ?></td>
              <td><?= htmlspecialchars($tanggal) ?></td>
              <td><?= htmlspecialchars($hariId) ?></td>
              <td><?= htmlspecialchars($jam) ?></td>
              <td><?= htmlspecialchars($tahun) ?></td>
              <td class="fw-semibold"><?= htmlspecialchars($r['plat_nomor']) ?></td>
              <td><?= htmlspecialchars($r['jenis_kendaraan']) ?></td>
              <td><?= htmlspecialchars($r['nama_area']) ?></td>
              <td><?= htmlspecialchars($r['petugas_nama'] ?: $r['petugas_username']) ?></td>
              <td><?= htmlspecialchars($r['waktu_masuk']) ?></td>
              <td><?= htmlspecialchars($r['waktu_keluar'] ?: '-') ?></td>
              <td><?= (int)($r['durasi_jam'] ?? 0) ?> jam</td>
              <td class="text-end"><?= rupiah($r['biaya_total'] ?? 0) ?></td>
              <td>
                <?php if($r['status']==='keluar'): ?>
                  <span class="badge bg-success">keluar</span>
                <?php elseif($r['status']==='masuk'): ?>
                  <span class="badge bg-warning text-dark">masuk</span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= htmlspecialchars($r['status']) ?></span>
                <?php endif; ?>
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
