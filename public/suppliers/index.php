<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
require_role('admin');

$rows = db()->query('SELECT id, name, phone, email FROM suppliers ORDER BY name')->fetchAll();

$pageTitle = 'Supplier';
require __DIR__ . '/../../views/header.php';
?>
<div class="top"><h1>Supplier</h1><a class="btn" href="<?= e(url('/suppliers/form.php')) ?>">+ Tambah Supplier</a></div>
<div class="card"><table>
    <tr><th>Nama</th><th>Telepon</th><th>Email</th><th>Aksi</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= e($r['name']) ?></td><td><?= e($r['phone']) ?></td><td><?= e($r['email']) ?></td>
        <td>
            <a class="btn btn-sm btn-gray" href="<?= e(url('/suppliers/form.php?id=' . (int) $r['id'])) ?>">Edit</a>
            <form method="post" action="<?= e(url('/suppliers/delete.php')) ?>" style="display:inline" onsubmit="return confirm('Hapus supplier ini?')">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn btn-sm btn-danger">Hapus</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/../../views/footer.php'; ?>
