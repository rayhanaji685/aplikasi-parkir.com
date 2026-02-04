<?php
require_once __DIR__ . "/../../app/middleware.php";
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("ID tidak valid");

// jangan hapus diri sendiri
if (isset($_SESSION['user']) && (int)$_SESSION['user']['id_user'] === $id) {
    die("Tidak boleh menghapus akun sendiri.");
}

try {
    $pdo->beginTransaction();

    // hapus data child dulu
    $pdo->prepare("DELETE FROM tb_log_aktivitas WHERE id_user = :id")
        ->execute(['id' => $id]);

    $pdo->prepare("DELETE FROM tb_transaksi WHERE id_user = :id")
        ->execute(['id' => $id]);

    // baru hapus user
    $pdo->prepare("DELETE FROM tb_user WHERE id_user = :id")
        ->execute(['id' => $id]);

    $pdo->commit();

    redirect("/rehan/public/admin/user_index.php");

} catch (Exception $e) {
    $pdo->rollBack();
    die("Gagal menghapus user: " . $e->getMessage());
}
