<?php
declare(strict_types=1);

/**
 * Mencatat barang masuk/keluar dalam SATU database transaction.
 * Semua berhasil (COMMIT) atau semua dibatalkan (ROLLBACK).
 *
 * @throws DomainException jika melanggar business rule (pesan aman ditampilkan ke user)
 */
function record_movement(PDO $pdo, int $productId, string $type, int $qty, int $userId, ?string $note): void
{
    if (!in_array($type, ['IN', 'OUT'], true)) {
        throw new DomainException('Tipe transaksi tidak valid.');
    }
    if ($qty <= 0) {
        throw new DomainException('Jumlah harus lebih dari nol.');
    }

    $pdo->beginTransaction();
    try {
        // 1. Kunci baris produk agar tidak ada transaksi lain yang mengubah stok bersamaan
        $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $productId]);
        $row = $stmt->fetch();
        if ($row === false) {
            throw new DomainException('Produk tidak ditemukan.');
        }

        // 2. Validasi semantik
        if ($type === 'OUT' && $qty > (int) $row['stock']) {
            throw new DomainException(
                "Stok tidak mencukupi. Stok tersedia: {$row['stock']}, diminta: {$qty}."
            );
        }

        // 3. Ubah stok
        $sql = $type === 'IN'
            ? 'UPDATE products SET stock = stock + :q WHERE id = :id'
            : 'UPDATE products SET stock = stock - :q WHERE id = :id';
        $pdo->prepare($sql)->execute(['q' => $qty, 'id' => $productId]);

        // 4. Catat histori + siapa yang melakukan
        $pdo->prepare(
            'INSERT INTO stock_movements (product_id, user_id, type, quantity, note)
             VALUES (:p, :u, :t, :q, :n)'
        )->execute(['p' => $productId, 'u' => $userId, 't' => $type, 'q' => $qty, 'n' => $note]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
