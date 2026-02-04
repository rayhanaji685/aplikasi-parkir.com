<?php
// app/config.php
declare(strict_types=1);

session_start();

$DB_HOST = "127.0.0.1";
$DB_NAME = "tes_ukk";
$DB_USER = "root";
$DB_PASS = ""; // sesuaikan

try {
  $pdo = new PDO(
    "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (Throwable $e) {
  die("Koneksi DB gagal: " . $e->getMessage());
}

function redirect(string $path): never {
  header("Location: {$path}");
  exit;
}
