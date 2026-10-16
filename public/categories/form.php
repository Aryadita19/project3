<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
require_role('admin');
$pdo = db();

$id = input_int($_GET, 'id');
$data = ['name' => '', 'description' => ''];

if ($id !== null) {
    $stmt = $pdo->prepare('SELECT name, description FROM categories WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Kategori tidak ditemukan.'); }
    $data = $found;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data['name'] = input_str($_POST, 'name');
    $data['description'] = input_str($_POST, 'description');

    if ($data['name'] === '') $errors[] = 'Nama kategori wajib diisi.';
    elseif (mb_strlen($data['name']) > 100) $errors[] = 'Nama kategori maksimal 100 karakter.';
    if (mb_strlen($data['description']) > 255) $errors[] = 'Deskripsi maksimal 255 karakter.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = :n AND id <> :id');
        $stmt->execute(['n' => $data['name'], 'id' => $id ?? 0]);
        if ((int) $stmt->fetchColumn() > 0) $errors[] = 'Nama kategori sudah dipakai.';
    }

    if (!$errors) {
        if ($id === null) {
            $pdo->prepare('INSERT INTO categories (name, description) VALUES (:n, :d)')
                ->execute(['n' => $data['name'], 'd' => $data['description'] ?: null]);
            flash('success', 'Kategori ditambahkan.');
        } else {
            $pdo->prepare('UPDATE categories SET name = :n, description = :d WHERE id = :id')
                ->execute(['n' => $data['name'], 'd' => $data['description'] ?: null, 'id' => $id]);
            flash('success', 'Kategori diperbarui.');
        }
        redirect('/categories/index.php');
    }
}

$pageTitle = $id ? 'Edit Kategori' : 'Tambah Kategori';
require __DIR__ . '/../../views/header.php';
?>
<h1><?= e($pageTitle) ?></h1>
<div class="card">
    <?php if ($errors): ?><div class="errors"><ul><?php foreach ($errors as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>Nama</label><input name="name" value="<?= e($data['name']) ?>">
        <label>Deskripsi</label><input name="description" value="<?= e($data['description']) ?>">
        <p><button class="btn">Simpan</button> <a class="btn btn-gray" href="<?= e(url('/categories/index.php')) ?>">Batal</a></p>
    </form>
</div>
<?php require __DIR__ . '/../../views/footer.php'; ?>
