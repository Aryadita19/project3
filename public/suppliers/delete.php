<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/suppliers/index.php'); }
csrf_verify();
$id = input_int($_POST, 'id');

if ($id !== null) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM product_suppliers WHERE supplier_id = :id');
    $stmt->execute(['id' => $id]);
    if ((int) $stmt->fetchColumn() > 0) {
        flash('error', 'Supplier tidak bisa dihapus karena masih terhubung dengan produk.');
    } else {
        $pdo->prepare('DELETE FROM suppliers WHERE id = :id')->execute(['id' => $id]);
        flash('success', 'Supplier dihapus.');
    }
}
redirect('/suppliers/index.php');
