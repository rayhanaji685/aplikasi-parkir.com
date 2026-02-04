<?php
// app/log.php

/**
 * Tulis log aktivitas user ke tabel tb_log_aktivitas
 * Kolom tabel: id_log, id_user, aktivitas, waktu_aktivitas
 */
function add_log(PDO $pdo, int $id_user, string $aktivitas): void
{
  try {
    $stmt = $pdo->prepare("
      INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas)
      VALUES (:id_user, :aktivitas, NOW())
    ");
    $stmt->execute([
      ':id_user' => $id_user,
      ':aktivitas' => $aktivitas
    ]);
  } catch (Throwable $e) {
    // jangan bikin app crash kalau log gagal
  }
}

/**
 * Helper: ambil user session sekarang lalu tulis log
 */
function add_log_current(PDO $pdo, ?array $user, string $aktivitas): void
{
  $id = (int)($user['id_user'] ?? 0);
  if ($id > 0) add_log($pdo, $id, $aktivitas);
}
