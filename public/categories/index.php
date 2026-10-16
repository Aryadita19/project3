<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
require_role('admin');

$rows = db()->query(
    'SELECT c.id, c.name, c.description, COUNT(p.id) AS total_products
     FROM categories c LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name, c.description ORDER BY c.name'
)->fetchAll();

$pageTitle = 'Kategori';
require __DIR__ . '/../../views/header.php';
?>
<div class="top"><h1>Kategori</h1><a class="btn" href="<?= e(url('/categories/form.php')) ?>">+ Tambah Kategori</a></div>
<div class="card"><table>
    <tr><th>Nama</th><th>Deskripsi</th><th class="right">Jumlah Produk</th><th>Aksi</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= e($r['name']) ?></td><td><?= e($r['description']) ?></td><td class="right"><?= e($r['total_products']) ?></td>
        <td>
            <a class="btn btn-sm btn-gray" href="<?= e(url('/categories/form.php?id=' . (int) $r['id'])) ?>">Edit</a>
            <form method="post" action="<?= e(url('/categories/delete.php')) ?>" style="display:inline" onsubmit="return confirm('Hapus kategori ini?')">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn btn-sm btn-danger">Hapus</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/../../views/footer.php'; ?>
