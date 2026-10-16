<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';
require_login();
$pdo = db();

$stats = $pdo->query(
    'SELECT COUNT(id) AS total_products,
            COALESCE(SUM(stock * price), 0) AS inventory_value,
            COALESCE(SUM(stock <= minimum_stock), 0) AS low_count
     FROM products'
)->fetch();

$mv = $pdo->query(
    "SELECT COALESCE(SUM(CASE WHEN type='IN'  THEN quantity END), 0) AS total_in,
            COALESCE(SUM(CASE WHEN type='OUT' THEN quantity END), 0) AS total_out
     FROM stock_movements"
)->fetch();

$lowStock = $pdo->query(
    'SELECT p.sku, p.name, p.stock, p.minimum_stock, c.name AS category_name
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.stock <= p.minimum_stock
     ORDER BY p.stock ASC'
)->fetchAll();

$byCategory = $pdo->query(
    'SELECT c.name, COUNT(p.id) AS total_products,
            COALESCE(SUM(p.stock), 0) AS total_stock,
            COALESCE(AVG(p.price), 0) AS average_price
     FROM categories c LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name ORDER BY c.name'
)->fetchAll();

$report = $pdo->query(
    "SELECT p.sku, p.name, p.stock,
            COALESCE(SUM(CASE WHEN sm.type='IN'  THEN sm.quantity END), 0) AS total_in,
            COALESCE(SUM(CASE WHEN sm.type='OUT' THEN sm.quantity END), 0) AS total_out
     FROM products p LEFT JOIN stock_movements sm ON sm.product_id = p.id
     GROUP BY p.id, p.sku, p.name, p.stock ORDER BY p.name"
)->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/../views/header.php';
?>
<h1>Dashboard</h1>
<div class="grid">
    <div class="stat"><small>Total Produk</small><div class="n"><?= e($stats['total_products']) ?></div></div>
    <div class="stat"><small>Nilai Inventory</small><div class="n"><?= e(rupiah($stats['inventory_value'])) ?></div></div>
    <div class="stat"><small>Produk Stok Rendah</small><div class="n"><?= e($stats['low_count']) ?></div></div>
    <div class="stat"><small>Total Barang Masuk</small><div class="n"><?= e($mv['total_in']) ?></div></div>
    <div class="stat"><small>Total Barang Keluar</small><div class="n"><?= e($mv['total_out']) ?></div></div>
</div>

<div class="card">
    <h3>Produk Stok Rendah (stok &le; minimum)</h3>
    <table>
        <tr><th>SKU</th><th>Nama</th><th>Kategori</th><th class="right">Stok</th><th class="right">Minimum</th></tr>
        <?php foreach ($lowStock as $r): ?>
            <tr>
                <td><?= e($r['sku']) ?></td><td><?= e($r['name']) ?></td><td><?= e($r['category_name']) ?></td>
                <td class="right"><span class="badge low"><?= e($r['stock']) ?></span></td>
                <td class="right"><?= e($r['minimum_stock']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$lowStock): ?><tr><td colspan="5" class="muted">Semua stok aman.</td></tr><?php endif; ?>
    </table>
</div>

<div class="card">
    <h3>Ringkasan per Kategori</h3>
    <table>
        <tr><th>Kategori</th><th class="right">Jumlah Produk</th><th class="right">Total Stok</th><th class="right">Rata-rata Harga</th></tr>
        <?php foreach ($byCategory as $r): ?>
            <tr><td><?= e($r['name']) ?></td><td class="right"><?= e($r['total_products']) ?></td>
                <td class="right"><?= e($r['total_stock']) ?></td><td class="right"><?= e(rupiah($r['average_price'])) ?></td></tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="card">
    <h3>Laporan Inventory (Masuk vs Keluar)</h3>
    <table>
        <tr><th>SKU</th><th>Produk</th><th class="right">Total Masuk</th><th class="right">Total Keluar</th><th class="right">Stok Akhir</th></tr>
        <?php foreach ($report as $r): ?>
            <tr><td><?= e($r['sku']) ?></td><td><?= e($r['name']) ?></td>
                <td class="right"><?= e($r['total_in']) ?></td><td class="right"><?= e($r['total_out']) ?></td>
                <td class="right"><b><?= e($r['stock']) ?></b></td></tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require __DIR__ . '/../views/footer.php'; ?>
