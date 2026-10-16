<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
require_role('admin');
$pdo = db();

$id = input_int($_GET, 'id');
$data = ['category_id' => '', 'sku' => '', 'name' => '', 'price' => '0', 'minimum_stock' => '5'];
$selectedSuppliers = [];   // [supplier_id => supplier_price]

if ($id !== null) {
    $stmt = $pdo->prepare('SELECT category_id, sku, name, price, minimum_stock FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Produk tidak ditemukan.'); }
    $data = array_map(fn($v) => (string) $v, $found);

    $stmt = $pdo->prepare('SELECT supplier_id, supplier_price FROM product_suppliers WHERE product_id = :id');
    $stmt->execute(['id' => $id]);
    foreach ($stmt->fetchAll() as $r) { $selectedSuppliers[(int) $r['supplier_id']] = $r['supplier_price']; }
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$suppliers  = $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data['category_id']   = input_str($_POST, 'category_id');
    $data['sku']           = input_str($_POST, 'sku');
    $data['name']          = input_str($_POST, 'name');
    $data['price']         = input_str($_POST, 'price');
    $data['minimum_stock'] = input_str($_POST, 'minimum_stock');

    // ---- Validasi syntactic ----
    $categoryId = input_int($_POST, 'category_id');
    $price      = input_decimal($_POST, 'price');
    $minStock   = input_int($_POST, 'minimum_stock');

    if ($categoryId === null) $errors[] = 'Kategori wajib dipilih.';
    if ($data['sku'] === '') $errors[] = 'SKU wajib diisi.';
    elseif (mb_strlen($data['sku']) > 50) $errors[] = 'SKU maksimal 50 karakter.';
    if ($data['name'] === '') $errors[] = 'Nama produk wajib diisi.';
    elseif (mb_strlen($data['name']) > 150) $errors[] = 'Nama produk maksimal 150 karakter.';
    if ($price === null || $price < 0) $errors[] = 'Harga harus angka dan tidak boleh negatif.';
    if ($minStock === null || $minStock < 0) $errors[] = 'Stok minimum harus bilangan bulat dan tidak boleh negatif.';

    // ---- Validasi semantic ----
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = :id');
        $stmt->execute(['id' => $categoryId]);
        if ((int) $stmt->fetchColumn() === 0) $errors[] = 'Kategori tidak ditemukan.';

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE sku = :sku AND id <> :id');
        $stmt->execute(['sku' => $data['sku'], 'id' => $id ?? 0]);
        if ((int) $stmt->fetchColumn() > 0) $errors[] = 'SKU sudah dipakai produk lain (SKU harus unik).';
    }

    // Supplier terpilih (many-to-many)
    $chosen = [];
    $postedSuppliers = $_POST['suppliers'] ?? [];
    $postedPrices    = $_POST['supplier_price'] ?? [];
    if (is_array($postedSuppliers)) {
        foreach ($postedSuppliers as $sid) {
            $sidInt = filter_var($sid, FILTER_VALIDATE_INT);
            if ($sidInt === false) continue;
            $sp = $postedPrices[$sidInt] ?? '';
            $spVal = ($sp === '' || !is_string($sp)) ? null : filter_var($sp, FILTER_VALIDATE_FLOAT);
            if ($spVal === false || ($spVal !== null && $spVal < 0)) {
                $errors[] = 'Harga supplier tidak boleh negatif / harus angka.';
                continue;
            }
            $chosen[(int) $sidInt] = $spVal;
        }
    }
    $selectedSuppliers = $chosen;

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            if ($id === null) {
                $pdo->prepare('INSERT INTO products (category_id, sku, name, price, minimum_stock) VALUES (:c,:s,:n,:p,:m)')
                    ->execute(['c' => $categoryId, 's' => $data['sku'], 'n' => $data['name'], 'p' => $price, 'm' => $minStock]);
                $productId = (int) $pdo->lastInsertId();
            } else {
                $pdo->prepare('UPDATE products SET category_id=:c, sku=:s, name=:n, price=:p, minimum_stock=:m WHERE id=:id')
                    ->execute(['c' => $categoryId, 's' => $data['sku'], 'n' => $data['name'], 'p' => $price, 'm' => $minStock, 'id' => $id]);
                $productId = $id;
            }
            // Sinkronkan junction table
            $pdo->prepare('DELETE FROM product_suppliers WHERE product_id = :id')->execute(['id' => $productId]);
            $ins = $pdo->prepare('INSERT INTO product_suppliers (product_id, supplier_id, supplier_price) VALUES (:p,:s,:price)');
            foreach ($chosen as $sid => $sp) {
                $ins->execute(['p' => $productId, 's' => $sid, 'price' => $sp]);
            }
            $pdo->commit();
            flash('success', $id === null ? 'Produk ditambahkan.' : 'Produk diperbarui.');
            redirect('/products/index.php');
        } catch (PDOException $ex) {
            $pdo->rollBack();
            if ($ex->getCode() === '23000') {
                $errors[] = 'Data melanggar aturan database (misalnya SKU duplikat atau relasi tidak valid).';
            } else {
                throw $ex;
            }
        }
    }
}

$pageTitle = $id ? 'Edit Produk' : 'Tambah Produk';
require __DIR__ . '/../../views/header.php';
?>
<h1><?= e($pageTitle) ?></h1>
<div class="card">
    <?php if ($errors): ?><div class="errors"><ul><?php foreach ($errors as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>Kategori</label>
        <select name="category_id">
            <option value="">-- pilih --</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (string) $c['id'] === $data['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>SKU (unik)</label><input name="sku" value="<?= e($data['sku']) ?>">
        <label>Nama Produk</label><input name="name" value="<?= e($data['name']) ?>">
        <label>Harga (Rp)</label><input name="price" value="<?= e($data['price']) ?>">
        <label>Stok Minimum</label><input name="minimum_stock" value="<?= e($data['minimum_stock']) ?>">
        <p class="muted">Stok tidak diubah dari form ini. Gunakan menu Barang Masuk / Barang Keluar agar tercatat di histori.</p>

        <label>Supplier (many-to-many)</label>
        <table>
            <tr><th style="width:60px">Pilih</th><th>Supplier</th><th>Harga dari supplier</th></tr>
            <?php foreach ($suppliers as $s): $sid = (int) $s['id']; $on = array_key_exists($sid, $selectedSuppliers); ?>
            <tr>
                <td><input type="checkbox" name="suppliers[]" value="<?= $sid ?>" <?= $on ? 'checked' : '' ?>></td>
                <td><?= e($s['name']) ?></td>
                <td><input name="supplier_price[<?= $sid ?>]" value="<?= e($on ? ($selectedSuppliers[$sid] ?? '') : '') ?>"></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <p><button class="btn">Simpan</button> <a class="btn btn-gray" href="<?= e(url('/products/index.php')) ?>">Batal</a></p>
    </form>
</div>
<?php require __DIR__ . '/../../views/footer.php'; ?>
