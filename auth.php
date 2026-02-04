<?php
// app/auth.php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/log.php"; // ✅ tambah ini

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function attempt_login(PDO $pdo, string $username, string $password): ?array {
    $stmt = $pdo->prepare("SELECT * FROM tb_user WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) return null;
    if ((int)$user['status_aktif'] !== 1) return null;
    if (!password_verify($password, $user['password'])) return null;

    $sessionUser = [
        'id_user'      => (int)$user['id_user'],
        'nama_lengkap' => $user['nama_lengkap'],
        'username'     => $user['username'],
        'role'         => $user['role'],
    ];

    $_SESSION['user'] = $sessionUser;

    // ✅ LOG LOGIN
    add_log($pdo, (int)$user['id_user'], "Login berhasil sebagai {$user['role']}");

    // ✅ set online juga kalau kamu pakai is_online
    try {
      $pdo->prepare("UPDATE tb_user SET is_online=1 WHERE id_user=:id")->execute([':id'=>(int)$user['id_user']]);
    } catch(Throwable $e){}

    return $sessionUser;
}

/** ambil user login dari session */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function logout(): void {
    unset($_SESSION['user']);
}
