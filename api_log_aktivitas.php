<?php
require_once __DIR__ . "/../../app/middleware.php";
$u = require_role('admin');

header('Content-Type: application/json; charset=utf-8');

$limit = (int)($_GET['limit'] ?? 50);
if ($limit < 10) $limit = 10;
if ($limit > 500) $limit = 500;

$q = trim($_GET['q'] ?? '');

try {
  if ($q !== '') {
    $stmt = $pdo->prepare("
      SELECT l.id_log, l.aktivitas, l.waktu_aktivitas,
             u.nama_lengkap, u.username, u.role
      FROM tb_log_aktivitas l
      JOIN tb_user u ON u.id_user = l.id_user
      WHERE u.nama_lengkap LIKE :q
         OR u.username LIKE :q
         OR l.aktivitas LIKE :q
      ORDER BY l.id_log DESC
      LIMIT $limit
    ");
    $stmt->execute([':q' => "%$q%"]);
  } else {
    $stmt = $pdo->query("
      SELECT l.id_log, l.aktivitas, l.waktu_aktivitas,
             u.nama_lengkap, u.username, u.role
      FROM tb_log_aktivitas l
      JOIN tb_user u ON u.id_user = l.id_user
      ORDER BY l.id_log DESC
      LIMIT $limit
    ");
  }

  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok' => true,
    'rows' => $rows,
    'server_time' => date('Y-m-d H:i:s')
  ]);
} catch (Throwable $e) {
  echo json_encode([
    'ok' => false,
    'message' => $e->getMessage(),
    'server_time' => date('Y-m-d H:i:s')
  ]);
}
