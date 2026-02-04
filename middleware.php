<?php
// app/middleware.php

// ✅ WIB untuk PHP
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . "/auth.php";   // current_user(), dll
require_once __DIR__ . "/config.php"; // $pdo (kalau config kamu define $pdo)

// ✅ WIB untuk MySQL (biar NOW() ikut +07:00)
if (isset($pdo) && $pdo instanceof PDO) {
  try {
    $pdo->exec("SET time_zone = '+07:00'");
  } catch (Throwable $e) {
    // diamkan saja kalau gagal (tidak bikin fatal)
  }
}

if (!function_exists('redirect')) {
  function redirect(string $path): void {
    header("Location: " . $path);
    exit;
  }
}

function redirect_by_role(?string $role): void {
  switch ($role) {
    case 'admin':
      redirect("/rehan/public/admin/dashboard.php");
      break;

    case 'petugas':
      redirect("/rehan/public/petugas/dashboard.php");
      break;

    case 'owner':
      // sesuaikan kalau owner punya dashboard sendiri
      redirect("/rehan/public/owner/dashboard.php");
      break;

    default:
      redirect("/rehan/public/login.php");
      break;
  }
}

/**
 * Wajib login & wajib role tertentu.
 * Return user kalau lolos.
 */
function require_role(string $role): array {
  $u = current_user();
  if (!$u) {
    redirect("/tes_ukk/public/login.php");
  }

  if (($u['role'] ?? '') !== $role) {
    redirect_by_role($u['role'] ?? null);
  }

  return $u;
}

/**
 * Wajib login & role boleh salah satu dari daftar.
 * Contoh: require_any_role(['admin','petugas'])
 */
function require_any_role(array $roles): array {
  $u = current_user();
  if (!$u) {
    redirect("/tes_ukk/public/login.php");
  }

  if (!in_array(($u['role'] ?? ''), $roles, true)) {
    redirect_by_role($u['role'] ?? null);
  }

  return $u;
}
