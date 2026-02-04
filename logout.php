<?php
require_once __DIR__ . "/../app/middleware.php"; // ada $pdo + redirect()
require_once __DIR__ . "/../app/auth.php";
require_once __DIR__ . "/../app/log.php";

$u = current_user();

// ✅ log dulu sebelum session dihapus
if ($u) {
  add_log($pdo, (int)$u['id_user'], "Logout");
  // kalau kamu pakai is_online
  try {
    $pdo->prepare("UPDATE tb_user SET is_online=0 WHERE id_user=:id")->execute([':id'=>(int)$u['id_user']]);
  } catch(Throwable $e){}
}

logout();
redirect("/rehan/public/login.php");
