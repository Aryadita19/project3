<?php
declare(strict_types=1);
// File bersama untuk in.php dan out.php. Variabel $type ('IN'/'OUT') diset oleh pemanggil.
require __DIR__ . '/../../bootstrap.php';
$user = require_role('admin', 'staff');
$pdo  = db();

$products = $pdo->query('SELECT id, sku, name, stock FROM products ORDER BY name')->fetchAll();
$errors = [];
$form = ['product_id' => '', 'quantity' => '', 'note' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $form['product_id'] = input_str($_POST, 'product_id');
    $form['quantity']   = input_str($_POST, 'quantity');
    $form['note']       = input_str($_POST, 'note');

    $productId = input_int($_POST, 'product_id');
    $qty       = input_int($_POST, 'quantity');

    if ($productId === null) $errors[] = 'Produk wajib dipilih.';
    if ($qty === null || $qty <= 0) $errors[] = 'Jumlah harus bilangan bulat lebih dari nol.';
    if (mb_strlen($form['note']) > 255) $errors[] = 'Catatan maksimal 255 karakter.';

    if (!$errors) {
        try {
            record_movement($pdo, $productId, $type, $qty, $user['id'], $form['note'] ?: null);
            flash('success', $type === 'IN' ? 'Barang masuk berhasil dicatat.' : 'Barang keluar berhasil dicatat.');
            redirect($type === 'IN' ? '/stock/in.php' : '/stock/out.php');
        } catch (DomainException $ex) {
            $errors[] = $ex->getMessage();          // pesan business rule aman ditampilkan
        } catch (Throwable $ex) {
            error_log($ex->getMessage());            // detail teknis hanya ke log
            $errors[] = 'Terjadi kesalahan sistem. Transaksi dibatalkan.';
        }
    }
}

$label = $type === 'IN' ? 'Barang Masuk' : 'Barang Keluar';
$pageTitle = $label;
require __DIR__ . '/../../views/header.php';
?>
<h1><?= e($label) ?></h1>
<div class="card">
    <?php if ($errors): ?><div class="errors"><ul><?php foreach ($errors as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>Produk</label>
        <select name="product_id">
            <option value="">-- pilih produk --</option>
            <?php foreach ($products as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= (string) $p['id'] === $form['product_id'] ? 'selected' : '' ?>>
                    <?= e($p['sku']) ?> - <?= e($p['name']) ?> (stok: <?= e($p['stock']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <label>Jumlah</label><input name="quantity" value="<?= e($form['quantity']) ?>">
        <label>Catatan (opsional)</label><input name="note" value="<?= e($form['note']) ?>">
        <p><button class="btn"><?= $type === 'IN' ? 'Simpan Barang Masuk' : 'Simpan Barang Keluar' ?></button></p>
    </form>
</div>
<?php require __DIR__ . '/../../views/footer.php'; ?>
