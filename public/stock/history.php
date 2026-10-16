<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
require_login();
$pdo = db();

$type = $_GET['type'] ?? '';
$type = in_array($type, ['IN', 'OUT'], true) ? $type : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$whereSql = $type !== '' ? 'WHERE sm.type = :type' : '';
$params = $type !== '' ? ['type' => $type] : [];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_movements sm $whereSql");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT sm.created_at, sm.type, sm.quantity, sm.note, p.sku, p.name AS product_name, u.full_name
     FROM stock_movements sm
     JOIN products p ON p.id = sm.product_id
     JOIN users u ON u.id = sm.user_id
     $whereSql
     ORDER BY sm.created_at DESC, sm.id DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) { $stmt->bindValue(':' . $k, $v); }
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$pageTitle = 'Histori Stok';
require __DIR__ . '/../../views/header.php';
?>
<h1>Histori Barang Masuk / Keluar</h1>
<div class="card">
<form method="get" class="filters">
    <select name="type">
        <option value="">Semua tipe</option>
        <option value="IN"  <?= $type === 'IN'  ? 'selected' : '' ?>>Masuk (IN)</option>
        <option value="OUT" <?= $type === 'OUT' ? 'selected' : '' ?>>Keluar (OUT)</option>
    </select>
    <button class="btn">Filter</button>
</form>
<table>
    <tr><th>Waktu</th><th>Tipe</th><th>SKU</th><th>Produk</th><th class="right">Jumlah</th><th>Oleh</th><th>Catatan</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= e($r['created_at']) ?></td>
        <td><span class="badge <?= $r['type'] === 'IN' ? 'in' : 'out' ?>"><?= e($r['type']) ?></span></td>
        <td><?= e($r['sku']) ?></td><td><?= e($r['product_name']) ?></td>
        <td class="right"><?= e($r['quantity']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['note']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7" class="muted">Belum ada transaksi.</td></tr><?php endif; ?>
</table>
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
        <?php else: ?><a href="?<?= e(build_query(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
</div>
</div>
<?php require __DIR__ . '/../../views/footer.php'; ?>
