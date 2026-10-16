<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
require_login();             // admin & staff boleh melihat produk
$pdo = db();

// ---- Input: search, filter, sorting, pagination ----
$q          = input_str($_GET, 'q');
$categoryId = input_int($_GET, 'category');

// Allowlist: nama kolom TIDAK boleh dibind, jadi dipetakan dari daftar yang diizinkan
$sortable = ['name' => 'p.name', 'sku' => 'p.sku', 'price' => 'p.price', 'stock' => 'p.stock'];
$sortKey  = $_GET['sort'] ?? 'name';
if (!is_string($sortKey) || !isset($sortable[$sortKey])) { $sortKey = 'name'; }
$dir = (is_string($_GET['dir'] ?? null) && strtoupper($_GET['dir']) === 'DESC') ? 'DESC' : 'ASC';

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 5;

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE :q1 OR p.sku LIKE :q2)';
    $params['q1'] = '%' . $q . '%';
    $params['q2'] = '%' . $q . '%';
}
if ($categoryId !== null) {
    $where[] = 'p.category_id = :cat';
    $params['cat'] = $categoryId;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ---- Hitung total untuk pagination ----
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
$stmt->execute($params);
$total      = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// ---- Ambil data ----
$sql = "SELECT p.id, p.sku, p.name, p.price, p.stock, p.minimum_stock, c.name AS category_name
        FROM products p JOIN categories c ON c.id = p.category_id
        $whereSql
        ORDER BY {$sortable[$sortKey]} $dir
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

function sort_link(string $key, string $label, string $currentKey, string $currentDir): string
{
    $newDir = ($currentKey === $key && $currentDir === 'ASC') ? 'DESC' : 'ASC';
    $arrow  = $currentKey === $key ? ($currentDir === 'ASC' ? ' ▲' : ' ▼') : '';
    return '<a href="?' . e(build_query(['sort' => $key, 'dir' => $newDir, 'page' => 1])) . '">' . e($label) . $arrow . '</a>';
}

$pageTitle = 'Produk';
require __DIR__ . '/../../views/header.php';
?>
<div class="top">
    <h1>Produk</h1>
    <?php if (is_admin()): ?><a class="btn" href="<?= e(url('/products/form.php')) ?>">+ Tambah Produk</a><?php endif; ?>
</div>

<div class="card">
<form method="get" class="filters">
    <input name="q" placeholder="Cari nama atau SKU..." value="<?= e($q) ?>">
    <select name="category">
        <option value="">Semua kategori</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $categoryId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="sort">
        <?php foreach (['name' => 'Nama', 'sku' => 'SKU', 'price' => 'Harga', 'stock' => 'Stok'] as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= $sortKey === $k ? 'selected' : '' ?>>Urut: <?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="dir">
        <option value="ASC"  <?= $dir === 'ASC'  ? 'selected' : '' ?>>Naik (ASC)</option>
        <option value="DESC" <?= $dir === 'DESC' ? 'selected' : '' ?>>Turun (DESC)</option>
    </select>
    <button class="btn">Terapkan</button>
</form>

<table>
    <tr>
        <th><?= sort_link('sku', 'SKU', $sortKey, $dir) ?></th>
        <th><?= sort_link('name', 'Nama', $sortKey, $dir) ?></th>
        <th>Kategori</th>
        <th class="right"><?= sort_link('price', 'Harga', $sortKey, $dir) ?></th>
        <th class="right"><?= sort_link('stock', 'Stok', $sortKey, $dir) ?></th>
        <?php if (is_admin()): ?><th>Aksi</th><?php endif; ?>
    </tr>
    <?php foreach ($products as $p): ?>
    <tr>
        <td><?= e($p['sku']) ?></td>
        <td><?= e($p['name']) ?></td>
        <td><?= e($p['category_name']) ?></td>
        <td class="right"><?= e(rupiah($p['price'])) ?></td>
        <td class="right">
            <?= e($p['stock']) ?>
            <?php if ((int) $p['stock'] <= (int) $p['minimum_stock']): ?><span class="badge low">rendah</span><?php endif; ?>
        </td>
        <?php if (is_admin()): ?>
        <td>
            <a class="btn btn-sm btn-gray" href="<?= e(url('/products/form.php?id=' . (int) $p['id'])) ?>">Edit</a>
            <form method="post" action="<?= e(url('/products/delete.php')) ?>" style="display:inline" onsubmit="return confirm('Hapus produk ini?')">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button class="btn btn-sm btn-danger">Hapus</button>
            </form>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <?php if (!$products): ?><tr><td colspan="6" class="muted">Tidak ada produk yang cocok.</td></tr><?php endif; ?>
</table>

<p class="muted">Total <?= e($total) ?> produk &middot; Halaman <?= e($page) ?> dari <?= e($totalPages) ?></p>
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
        <?php else: ?><a href="?<?= e(build_query(['page' => $i])) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
</div>
</div>
<?php require __DIR__ . '/../../views/footer.php'; ?>
