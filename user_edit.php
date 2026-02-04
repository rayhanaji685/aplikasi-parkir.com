<?php
require_once __DIR__ . "/../../app/middleware.php";
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("ID tidak valid");

$stmt = $pdo->prepare("SELECT * FROM tb_user WHERE id_user = :id");
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();
if (!$user) die("User tidak ditemukan");

$error = "";
$allowed_roles = ['admin','petugas','owner'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama = trim($_POST['nama_lengkap'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $role = $_POST['role'] ?? $user['role'];
  $status = (int)($_POST['status_aktif'] ?? $user['status_aktif']);
  $newpass = $_POST['new_password'] ?? '';

  if (!in_array($role, $allowed_roles, true)) $role = $user['role'];

  if ($nama === '' || $username === '') {
    $error = "Nama & username wajib diisi.";
  } else {
    try {
      if ($newpass !== '') {
        $hash = password_hash($newpass, PASSWORD_BCRYPT);
        $sql = "UPDATE tb_user 
                SET nama_lengkap=:n, username=:u, role=:r, 
                    status_aktif=:s, password=:p 
                WHERE id_user=:id";
        $params = [
          'n'=>$nama,'u'=>$username,'r'=>$role,
          's'=>$status,'p'=>$hash,'id'=>$id
        ];
      } else {
        $sql = "UPDATE tb_user 
                SET nama_lengkap=:n, username=:u, 
                    role=:r, status_aktif=:s 
                WHERE id_user=:id";
        $params = [
          'n'=>$nama,'u'=>$username,
          'r'=>$role,'s'=>$status,'id'=>$id
        ];
      }

      $up = $pdo->prepare($sql);
      $up->execute($params);

      redirect("/rehan/public/admin/user_index.php");
    } catch (Throwable $e) {
      $error = "Gagal update: " . $e->getMessage();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit User</title>

<style>
body {
  font-family: "Segoe UI", Arial, sans-serif;
  background: #f4f6f8;
  margin: 0;
  padding: 0;
}

.container {
  max-width: 520px;
  margin: 60px auto;
  background: #ffffff;
  padding: 30px;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
}

h2 {
  text-align: center;
  margin-bottom: 6px;
}

.subtitle {
  text-align: center;
  color: #6b7280;
  font-size: 14px;
  margin-bottom: 25px;
}

.form-group {
  margin-bottom: 15px;
}

label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 6px;
}

input, select {
  width: 100%;
  padding: 10px 12px;
  font-size: 14px;
  border-radius: 6px;
  border: 1px solid #d1d5db;
  transition: .2s;
}

input:focus, select:focus {
  outline: none;
  border-color: #2563eb;
}

button {
  width: 100%;
  padding: 12px;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
}

button:hover {
  background: #1d4ed8;
}

.back {
  display: inline-block;
  margin-bottom: 15px;
  font-size: 14px;
  color: #2563eb;
  text-decoration: none;
}

.error {
  background: #fee2e2;
  color: #991b1b;
  padding: 10px;
  border-radius: 6px;
  font-size: 14px;
  margin-bottom: 15px;
}
</style>
</head>

<body>

<div class="container">

  <a class="back" href="user_index.php">← Kembali</a>

  <h2>Edit User</h2>
  <div class="subtitle">Perbarui data akun pengguna</div>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">

    <div class="form-group">
      <label>Nama Lengkap</label>
      <input name="nama_lengkap"
             value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
    </div>

    <div class="form-group">
      <label>Username</label>
      <input name="username"
             value="<?= htmlspecialchars($user['username']) ?>" required>
    </div>

    <div class="form-group">
      <label>Role</label>
      <select name="role">
        <?php foreach (['admin','petugas','owner'] as $r): ?>
          <option value="<?= $r ?>" <?= ($user['role']===$r?'selected':'') ?>>
            <?= ucfirst($r) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Status Akun</label>
      <select name="status_aktif">
        <option value="1" <?= ((int)$user['status_aktif']===1?'selected':'') ?>>
          Aktif
        </option>
        <option value="0" <?= ((int)$user['status_aktif']===0?'selected':'') ?>>
          Nonaktif
        </option>
      </select>
    </div>

    <div class="form-group">
      <label>Password Baru</label>
      <input type="password"
             name="new_password"
             placeholder="Kosongkan jika tidak diganti">
    </div>

    <button type="submit">Simpan Perubahan</button>
  </form>

</div>

</body>
</html>
