<?php
require_once __DIR__ . "/../../app/middleware.php";
$u = require_role('admin');

$error = "";
$success = "";

$nama = "";
$username = "";
$role = "petugas";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama = trim($_POST['nama_lengkap'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $role = $_POST['role'] ?? 'petugas';

  $allowed_roles = ['admin','petugas','owner'];
  if (!in_array($role, $allowed_roles, true)) $role = 'petugas';

  if ($nama === '' || $username === '' || $password === '') {
    $error = "Semua field wajib diisi.";
  } else {
    try {
      $hash = password_hash($password, PASSWORD_BCRYPT);

      // default akun aktif
      $status = 1;

      $stmt = $pdo->prepare("
        INSERT INTO tb_user (nama_lengkap, username, password, role, status_aktif, is_online)
        VALUES (:n, :u, :p, :r, :s, 0)
      ");
      $stmt->execute([
        'n' => $nama,
        'u' => $username,
        'p' => $hash,
        'r' => $role,
        's' => $status,
      ]);

      redirect("/rehan/public/admin/user_index.php");
    } catch (Throwable $e) {
      $msg = $e->getMessage();
      if (stripos($msg, 'Duplicate') !== false) {
        $error = "Username sudah dipakai. Coba username lain.";
      } else {
        $error = "Gagal simpan: " . $msg;
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
  <title>Tambah User</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    body { background:#f6f7fb; }
    .card-soft { border:0; border-radius:16px; box-shadow: 0 10px 30px rgba(15,23,42,.06); }
  </style>
</head>
<body>

<div class="container py-4" style="max-width: 720px;">
  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-0 fw-bold">Tambah User</h4>
      <div class="text-muted small">Login sebagai: <?= htmlspecialchars($u['nama_lengkap']) ?></div>
    </div>

    <a href="/rehan/public/admin/user_index.php" class="btn btn-outline-secondary">
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
          <label class="form-label">Nama Lengkap</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
            <input
              type="text"
              name="nama_lengkap"
              class="form-control"
              placeholder="Contoh: Budi Santoso"
              required
              value="<?= htmlspecialchars($nama) ?>"
            >
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Username</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-at"></i></span>
            <input
              type="text"
              name="username"
              class="form-control"
              placeholder="Contoh: budi"
              required
              value="<?= htmlspecialchars($username) ?>"
            >
          </div>
          <div class="form-text">Username harus unik (tidak boleh sama).</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-key"></i></span>
            <input
              type="password"
              name="password"
              id="password"
              class="form-control"
              placeholder="Minimal 6 karakter"
              required
            >
            <button class="btn btn-outline-secondary" type="button" id="togglePass" title="Lihat/Sembunyikan">
              <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label">Role</label>
          <select name="role" class="form-select" required>
            <option value="admin"   <?= ($role==='admin') ? 'selected' : '' ?>>admin</option>
            <option value="petugas" <?= ($role==='petugas') ? 'selected' : '' ?>>petugas</option>
            <option value="owner"   <?= ($role==='owner') ? 'selected' : '' ?>>owner</option>
          </select>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Simpan
          </button>
          <a href="/rehan/public/admin/user_index.php" class="btn btn-light">
            Batal
          </a>
        </div>
      </form>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const toggle = document.getElementById('togglePass');
  const pass = document.getElementById('password');
  const icon = document.getElementById('eyeIcon');

  toggle.addEventListener('click', () => {
    const isPwd = pass.getAttribute('type') === 'password';
    pass.setAttribute('type', isPwd ? 'text' : 'password');
    icon.className = isPwd ? 'bi bi-eye-slash' : 'bi bi-eye';
  });
</script>
</body>
</html>
