<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
require_role('admin');
$pdo = db();

$id = input_int($_GET, 'id');
$data = ['name' => '', 'phone' => '', 'email' => '', 'address' => ''];

if ($id !== null) {
    $stmt = $pdo->prepare('SELECT name, phone, email, address FROM suppliers WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Supplier tidak ditemukan.'); }
    $data = array_map(fn($v) => (string) $v, $found);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach (array_keys($data) as $k) { $data[$k] = input_str($_POST, $k); }

    if ($data['name'] === '') $errors[] = 'Nama supplier wajib diisi.';
    elseif (mb_strlen($data['name']) > 100) $errors[] = 'Nama supplier maksimal 100 karakter.';
    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (mb_strlen($data['phone']) > 30) $errors[] = 'Telepon maksimal 30 karakter.';
    if (mb_strlen($data['address']) > 255) $errors[] = 'Alamat maksimal 255 karakter.';

    if (!$errors) {
        $params = [
            'n' => $data['name'], 'p' => $data['phone'] ?: null,
            'e' => $data['email'] ?: null, 'a' => $data['address'] ?: null,
        ];
        if ($id === null) {
            $pdo->prepare('INSERT INTO suppliers (name, phone, email, address) VALUES (:n, :p, :e, :a)')->execute($params);
            flash('success', 'Supplier ditambahkan.');
        } else {
            $params['id'] = $id;
            $pdo->prepare('UPDATE suppliers SET name=:n, phone=:p, email=:e, address=:a WHERE id=:id')->execute($params);
            flash('success', 'Supplier diperbarui.');
        }
        redirect('/suppliers/index.php');
    }
}

$pageTitle = $id ? 'Edit Supplier' : 'Tambah Supplier';
require __DIR__ . '/../../views/header.php';
?>
<h1><?= e($pageTitle) ?></h1>
<div class="card">
    <?php if ($errors): ?><div class="errors"><ul><?php foreach ($errors as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>Nama</label><input name="name" value="<?= e($data['name']) ?>">
        <label>Telepon</label><input name="phone" value="<?= e($data['phone']) ?>">
        <label>Email</label><input name="email" value="<?= e($data['email']) ?>">
        <label>Alamat</label><input name="address" value="<?= e($data['address']) ?>">
        <p><button class="btn">Simpan</button> <a class="btn btn-gray" href="<?= e(url('/suppliers/index.php')) ?>">Batal</a></p>
    </form>
</div>
<?php require __DIR__ . '/../../views/footer.php'; ?>
