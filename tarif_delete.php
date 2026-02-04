<?php
require_once __DIR__ . "/../../app/middleware.php";
require_once __DIR__ . "/../../app/log.php"; // ✅ untuk log aktivitas
$u = require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id<=0) redirect("/tes_ukk/public/admin/tarif_index.php");

try {
  $pdo->prepare("DELETE FROM tb_tarif WHERE id_tarif=:id")->execute(['id'=>$id]);
} catch(Throwable $e) {}
redirect("/tes_ukk/public/admin/tarif_index.php");
