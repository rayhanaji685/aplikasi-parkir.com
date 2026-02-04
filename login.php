<?php
require_once __DIR__ . "/../app/auth.php";
require_once __DIR__ . "/../app/middleware.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  $u = attempt_login($pdo, $username, $password);

  if ($u) {
    redirect_by_role($u['role']);
  } else {
    $error = "Login gagal. Cek username/password atau akun nonaktif.";
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login Sistem Parkir 2026</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />

  <style>
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 15px;
      color: #e0e0e0;
    }

    .login-card {
      background: rgba(255, 255, 255, 0.08);
      border-radius: 25px;
      padding: 40px 30px;
      width: 100%;
      max-width: 400px;
      box-shadow:
        0 8px 32px 0 rgba(31, 38, 135, 0.37),
        0 0 10px rgba(0, 0, 0, 0.25);
      backdrop-filter: blur(15px);
      border: 1px solid rgba(255, 255, 255, 0.18);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .login-card:hover {
      transform: translateY(-6px);
      box-shadow:
        0 16px 48px 0 rgba(31, 38, 135, 0.55),
        0 0 20px rgba(0, 0, 0, 0.35);
    }

    .login-header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 30px;
    }

    .brand-icon {
      background: rgba(255, 255, 255, 0.15);
      border-radius: 50%;
      padding: 15px;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 1.8rem;
      color: #4e73df;
      box-shadow: 0 4px 15px rgba(78, 115, 223, 0.6);
      transition: background-color 0.3s ease;
    }

    .brand-icon:hover {
      background: rgba(78, 115, 223, 0.1);
      color: #1a3cb8;
    }

    .login-title {
      font-weight: 700;
      font-size: 1.7rem;
      color: #f1f5f9;
    }

    .login-subtitle {
      font-size: 0.9rem;
      color: #d1d5db;
    }

    /* Input Group */
    .form-control,
    .input-group-text {
      background: rgba(255, 255, 255, 0.12);
      border: 1.5px solid rgba(255, 255, 255, 0.35);
      color: #e0e0e0;
      font-size: 16px;
      border-radius: 15px;
      transition: all 0.3s ease;
      box-shadow: none !important;
    }

    .form-control::placeholder {
      color: rgba(224, 224, 224, 0.6);
    }

    .form-control:focus,
    .input-group-text:focus {
      border-color: #4e73df;
      background: rgba(255, 255, 255, 0.25);
      color: #fff;
      box-shadow: 0 0 8px #4e73df;
      outline: none;
    }

    /* Button */
    .btn-primary {
      background: linear-gradient(90deg, #4e73df, #2a54c9);
      border: none;
      border-radius: 15px;
      padding: 14px;
      font-weight: 700;
      font-size: 1.1rem;
      box-shadow: 0 6px 20px rgb(78 115 223 / 0.4);
      transition: background 0.3s ease, box-shadow 0.3s ease;
    }

    .btn-primary:hover {
      background: linear-gradient(90deg, #2a54c9, #183da9);
      box-shadow: 0 8px 28px rgb(40 64 255 / 0.7);
    }

    /* Error alert */
    .alert-danger {
      background: #f87171;
      color: #fff;
      font-weight: 600;
      padding: 12px 15px;
      border-radius: 12px;
      margin-bottom: 20px;
      box-shadow: 0 4px 12px rgba(248, 113, 113, 0.6);
    }

    /* Toggle password button */
    .btn-toggle-pass {
      background: transparent;
      border: none;
      color: #e0e0e0;
      font-size: 1.2rem;
      cursor: pointer;
      user-select: none;
      transition: color 0.3s ease;
    }

    .btn-toggle-pass:hover {
      color: #4e73df;
    }

    /* Responsive */
    @media (max-width: 576px) {
      .login-card {
        padding: 30px 25px;
        width: 90vw;
      }
    }
  </style>
</head>
<body>

  <div class="login-card">
    <div class="login-header">
      <div class="brand-icon">
        <i class="bi bi-shield-lock-fill"></i>
      </div>
      <div>
        <h1 class="login-title mb-1">Login</h1>
        <div class="login-subtitle">Silakan masuk untuk melanjutkan</div>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off" id="loginForm">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input
            type="text"
            id="username"
            name="username"
            class="form-control"
            placeholder="Masukkan username"
            required
            autofocus
          />
        </div>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-key"></i></span>
          <input
            type="password"
            id="password"
            name="password"
            class="form-control"
            placeholder="Masukkan password"
            required
          />
          <button
            type="button"
            class="btn btn-toggle-pass input-group-text"
            id="togglePass"
            aria-label="Lihat atau sembunyikan password"
            title="Lihat/Sembunyikan password"
          >
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>

      <button class="btn btn-primary w-100 fw-semibold" type="submit" id="btnLogin">
        <i class="bi bi-box-arrow-in-right me-2"></i> Masuk
      </button>

      <div class="text-center mt-3" style="font-size: 0.8rem; color: #94a3b8;">
        © <?= date('Y') ?> Sistem Parkir
      </div>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle password show/hide
    const togglePassBtn = document.getElementById('togglePass');
    const passwordInput = document.getElementById('password');
    const icon = togglePassBtn.querySelector('i');

    togglePassBtn.addEventListener('click', () => {
      const isPassword = passwordInput.type === 'password';
      passwordInput.type = isPassword ? 'text' : 'password';
      icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  </script>
</body>
</html>
