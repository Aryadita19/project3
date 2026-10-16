<?php
$user = current_user();
$pageTitle = $pageTitle ?? 'Inventory';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - Inventory System</title>
    <link rel="stylesheet" href="<?= e(url('/assets/style.css')) ?>">
</head>
<body>
<?php if ($user): ?>
<nav class="nav">
    <strong>📦 Inventory</strong>
    <a href="<?= e(url('/dashboard.php')) ?>">Dashboard</a>
    <a href="<?= e(url('/products/index.php')) ?>">Produk</a>
    <?php if ($user['role'] === 'admin'): ?>
        <a href="<?= e(url('/categories/index.php')) ?>">Kategori</a>
        <a href="<?= e(url('/suppliers/index.php')) ?>">Supplier</a>
        <a href="<?= e(url('/users/index.php')) ?>">User</a>
    <?php endif; ?>
    <a href="<?= e(url('/stock/in.php')) ?>">Barang Masuk</a>
    <a href="<?= e(url('/stock/out.php')) ?>">Barang Keluar</a>
    <a href="<?= e(url('/stock/history.php')) ?>">Histori</a>
    <span class="spacer"></span>
    <span><?= e($user['full_name']) ?> (<?= e($user['role']) ?>)</span>
    <form method="post" action="<?= e(url('/logout.php')) ?>" style="display:inline">
        <?= csrf_field() ?>
        <button class="btn btn-sm">Logout</button>
    </form>
</nav>
<?php endif; ?>
<main class="container">
<?php foreach (flash_pull() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
