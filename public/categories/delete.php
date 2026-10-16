<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/categories/index.php'); }
csrf_verify();
$id = input_int($_POST, 'id');

if ($id !== null) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
    $stmt->execute(['id' => $id]);
    if ((int) $stmt->fetchColumn() > 0) {
        flash('error', 'Kategori tidak bisa dihapus karena masih dipakai oleh produk.');
    } else {
        $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $id]);
        flash('success', 'Kategori dihapus.');
    }
}
redirect('/categories/index.php');
