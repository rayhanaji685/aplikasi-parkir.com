<?php
require_once __DIR__ . "/../../app/middleware.php";
$u = require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect("/rehan/public/admin/area_index.php");

try {
  $del = $pdo->prepare("DELETE FROM tb_area_parkir WHERE id_area = :id");
  $del->execute(['id' => $id]);
} catch (Throwable $e) {
  // kalau mau ditampilkan, bisa pakai session flash
}

redirect("/rehan/public/admin/area_index.php");
