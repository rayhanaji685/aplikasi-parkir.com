<?php
require_once __DIR__ . "/../../app/middleware.php";
$u = require_role('petugas');

$current = 'dashboard';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Petugas</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    body { background: #f6f7fb; color: #495057; }

    /* Sidebar */
    .sidebar {
      width: 260px;
      min-height: 100vh;
      background: linear-gradient(180deg, #007bff 0%, #6c63ff 100%);
      color: #fff;
      position: sticky;
      top: 0;
    }
    .brand { font-weight: 800; letter-spacing: .5px; font-size: 1.25rem; }

    .side-link {
      color: rgba(255,255,255,.85);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 16px;
      border-radius: 10px;
      margin-bottom: 6px;
      transition: background-color 0.2s ease;
    }
    .side-link:hover { background: rgba(255,255,255,.12); color:#fff; }
    .side-link.active { background: rgba(255,255,255,.18); color:#fff; }

    .content { padding: 30px; }

    .card-soft {
      border: 0;
      border-radius: 16px;
      box-shadow: 0 12px 30px rgba(15,23,42,.08);
      background-color: #fff;
      color: #495057;
    }

    .menu-card { text-decoration: none; color: inherit; }

    .menu-card:hover .card-soft {
      transform: translateY(-3px);
      transition: .2s ease;
      box-shadow: 0 16px 38px rgba(15,23,42,.15);
    }

    .stat-icon {
      width: 60px; height: 60px;
      border-radius: 50%;
      display:flex; align-items:center; justify-content:center;
      font-size: 1.5rem;
    }

    /* Pulse effect for real-time updates */
    .pulse-dot {
      width: 12px; height: 12px; border-radius: 999px;
      background: #20c997;
      box-shadow: 0 0 0 rgba(32,201,151,.4);
      animation: pulse 1.6s infinite;
    }

    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(32,201,151,.45); }
      70% { box-shadow: 0 0 0 10px rgba(32,201,151,0); }
      100% { box-shadow: 0 0 0 0 rgba(32,201,151,0); }
    }

    .footer {
      font-size: 14px;
      color: rgba(255, 255, 255, 0.6);
      padding-top: 20px;
      border-top: 1px solid rgba(255,255,255,.1);
      text-align: center;
    }

  </style>
</head>

<body>
<div class="d-flex">

  <!-- SIDEBAR -->
  <aside class="sidebar p-3">
    <div class="d-flex align-items-center gap-2 mb-4">
      <div class="fs-5">🧾</div>
      <div class="brand fs-5">PETUGAS</div>
    </div>

    <div class="text-white-50 small mb-2">Menu</div>

    <a class="side-link <?= $current==='dashboard'?'active':'' ?>" href="/rehan/public/petugas/dashboard.php">
      <i class="bi bi-grid"></i> Dashboard
    </a>

    <!-- MENU UTAMA: PARKIR MASUK -->
    <a class="side-link" href="/rehan/public/petugas/parkir_masuk.php">
      <i class="bi bi-box-arrow-in-right"></i> Parkir Masuk
    </a>

    <!-- MENU UTAMA: PARKIR KELUAR -->
    <a class="side-link" href="/rehan/public/petugas/parkir_keluar.php">
      <i class="bi bi-box-arrow-left"></i> Parkir Keluar
    </a>

    <hr class="border-light opacity-25 my-3">

    <a class="side-link text-danger" href="/rehan/public/logout.php"
       onclick="return confirm('Yakin ingin logout?')">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </aside>

  <!-- CONTENT -->
  <main class="flex-grow-1 content">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <div class="d-flex align-items-center gap-2">
          <h4 class="mb-0 fw-bold">Dashboard Petugas</h4>
        </div>
        <div class="text-muted">Pilih menu untuk memproses parkir</div>
      </div>

      <a href="/rehan/public/logout.php" class="btn btn-outline-danger"
         onclick="return confirm('Yakin ingin logout?')">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>

    <!-- MENU CARDS -->
    <div class="row g-4 mt-2">

      <div class="col-md-6 col-lg-4">
        <a class="menu-card" href="/rehan/public/petugas/parkir_masuk.php">
          <div class="card card-soft">
            <div class="card-body d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(13,110,253,.10); color:#0d6efd;">
                  <i class="bi bi-box-arrow-in-right"></i>
                </div>
                <div>
                  <div class="fw-semibold">Parkir Masuk</div>
                  <div class="text-muted small">Input kendaraan & cetak struk masuk</div>
                </div>
              </div>
              <i class="bi bi-chevron-right text-muted"></i>
            </div>
          </div>
        </a>
      </div>

      <div class="col-md-6 col-lg-4">
        <a class="menu-card" href="/rehan/public/petugas/parkir_keluar.php">
          <div class="card card-soft">
            <div class="card-body d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(255,193,7,.18); color:#b78100;">
                  <i class="bi bi-box-arrow-left"></i>
                </div>
                <div>
                  <div class="fw-semibold">Parkir Keluar</div>
                  <div class="text-muted small">Pilih plat yang masih masuk & cetak struk keluar</div>
                </div>
              </div>
              <i class="bi bi-chevron-right text-muted"></i>
            </div>
          </div>
        </a>
      </div>

    </div>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
