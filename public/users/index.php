<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
require_role('admin');   // staff yang membuka URL ini akan mendapat 403

$rows = db()->query('SELECT id, username, full_name, role, created_at FROM users ORDER BY id')->fetchAll();

$pageTitle = 'Manajemen User';
require __DIR__ . '/../../views/header.php';
?>
<h1>Manajemen User</h1>
<div class="card"><table>
    <tr><th>Username</th><th>Nama</th><th>Role</th><th>Dibuat</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr><td><?= e($r['username']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['role']) ?></td><td><?= e($r['created_at']) ?></td></tr>
    <?php endforeach; ?>
</table></div>
<?php require __DIR__ . '/../../views/footer.php'; ?>
