<?php
require_once __DIR__ . "/../../app/middleware.php";
$u = require_role('admin');

$stmt = $pdo->query("
  SELECT id_user, nama_lengkap, username, role, status_aktif, is_online
  FROM tb_user
  ORDER BY id_user DESC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola User</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    body { background:#f6f7fb; }
    .card-soft { border:0; border-radius:16px; box-shadow: 0 10px 30px rgba(15,23,42,.06); }
    .badge-dot {
      display:inline-block; width:10px; height:10px; border-radius:50%;
      margin-right:6px; vertical-align:middle;
    }
  </style>
</head>

<body class="bg-light">
<div class="container py-4">

  <!-- Top bar -->
  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-0 fw-bold">Kelola User</h4>
      <div class="text-muted small">Login sebagai: <?= htmlspecialchars($u['nama_lengkap']) ?></div>
    </div>

    <div class="d-flex gap-2">
      <a href="/rehan/public/admin/dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Dashboard
      </a>
      <a href="/rehan/public/admin/user_create.php" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Tambah User
      </a>
    </div>
  </div>

  <!-- Table card -->
  <div class="card card-soft">
    <div class="card-body">

      <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:70px;">No</th>
              <th>Nama</th>
              <th>Username</th>
              <th style="width:130px;">Role</th>
              <th style="width:140px;">Status</th>
              <th style="width:160px;" class="text-end">Edit | Hapus</th>
            </tr>
          </thead>

          <tbody>
            <?php if (!$rows): ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">
                  Belum ada data user.
                </td>
              </tr>
            <?php endif; ?>

            <?php $no = 1; ?>
            <?php foreach ($rows as $r): ?>
              <?php
                $online = ((int)$r['is_online'] === 1);
                $badgeClass = $online ? "bg-success" : "bg-secondary";
                $statusText = $online ? "Online" : "Offline";

                $role = strtolower((string)$r['role']);
                $roleBadge = match($role) {
                  'admin' => 'bg-primary',
                  'owner' => 'bg-warning text-dark',
                  default => 'bg-info text-dark',
                };
              ?>
              <tr>
                <td class="text-muted"><?= $no++ ?></td>

                <td class="fw-semibold"><?= htmlspecialchars($r['nama_lengkap']) ?></td>

                <td>
                  <span class="text-muted">@<?= htmlspecialchars($r['username']) ?></span>
                </td>

                <td>
                  <span class="badge <?= $roleBadge ?>"><?= htmlspecialchars($r['role']) ?></span>
                </td>

                <td>
                  <span class="badge-dot <?= $badgeClass ?>"></span>
                  <span class="small fw-semibold"><?= $statusText ?></span>
                </td>

                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary"
                     href="/rehan/public/admin/user_edit.php?id=<?= (int)$r['id_user'] ?>">
                    <i class="bi bi-pencil-square"></i> Edit
                  </a>

                  <a class="btn btn-sm btn-outline-danger ms-1"
                     href="/rehan/public/admin/user_delete.php?id=<?= (int)$r['id_user'] ?>"
                     onclick="return confirm('Yakin hapus user ini?')">
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
