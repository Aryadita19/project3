<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/products/index.php'); }
csrf_verify();
$id = input_int($_POST, 'id');

if ($id !== null) {
    $pdo = db();
    // Business rule: produk yang punya histori transaksi tidak boleh dihapus
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM stock_movements WHERE product_id = :id');
    $stmt->execute(['id' => $id]);
    if ((int) $stmt->fetchColumn() > 0) {
        flash('error', 'Produk tidak bisa dihapus karena sudah memiliki histori barang masuk/keluar.');
    } else {
        $pdo->prepare('DELETE FROM products WHERE id = :id')->execute(['id' => $id]);
        flash('success', 'Produk dihapus.');
    }
}
redirect('/products/index.php');
