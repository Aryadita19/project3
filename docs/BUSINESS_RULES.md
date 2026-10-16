# Penjelasan Business Rule - Inventory Management System

| # | Business rule | Di mana diterapkan | Cara kerjanya |
|---|---------------|--------------------|---------------|
| 1 | SKU harus unik | `schema.sql` (`UNIQUE`), `products/form.php` | Dicek di PHP (pesan ramah) dan dijaga lagi oleh constraint UNIQUE database. |
| 2 | Harga & quantity tidak boleh negatif | `CHECK` di schema, validasi `input_decimal` / `input_int` | Server menolak nilai < 0 sebelum query dijalankan. |
| 3 | Barang keluar tidak boleh melebihi stok | `helpers/stock.php` | Di dalam transaction: `SELECT stock ... FOR UPDATE`, lalu bandingkan `quantity <= stock`. |
| 4 | Stok tidak boleh negatif | Rule #3 + `CHECK (stock >= 0)` | Lapisan ganda: aplikasi dan database. |
| 5 | Barang masuk/keluar harus konsisten | `record_movement()` memakai `beginTransaction / commit / rollBack` | Update stok + insert histori + simpan user pelaku: semua berhasil atau semua dibatalkan. |
| 6 | Produk berhistori tidak boleh dihapus | `products/delete.php` + FK `ON DELETE RESTRICT` | Dicek jumlah baris di `stock_movements`; jika > 0 ditolak dengan pesan. |
| 7 | Kategori/supplier yang masih dipakai tidak boleh dihapus | `categories/delete.php`, `suppliers/delete.php` + FK RESTRICT | Menjaga referential integrity. |
| 8 | Halaman internal wajib login | `require_login()` di setiap halaman | Tanpa session -> redirect ke `login.php`. |
| 9 | Staff tidak boleh mengakses administrasi | `require_role('admin')` | Dicek di server; hasilnya HTTP 403 walau URL diketik manual. |
| 10 | Sorting hanya kolom yang diizinkan | `products/index.php` (allowlist `$sortable`) | Nama kolom dipetakan dari daftar tetap, input tak dikenal diganti default. |
| 11 | Stok awal produk baru = 0 | Form produk tidak punya input stok | Stok hanya berubah lewat Barang Masuk/Keluar sehingga selalu ada histori. |

**Rumus:** stok akhir = stok awal + total barang masuk - total barang keluar.

## Hak akses per role
| Fitur | Admin | Staff |
|-------|:-----:|:-----:|
| Dashboard, lihat produk, histori | ya | ya |
| Catat barang masuk / keluar | ya | ya |
| CRUD kategori, produk, supplier | ya | tidak (403) |
| Manajemen user | ya | tidak (403) |

## Keamanan
- **SQL Injection**: semua query memakai prepared statement (PDO, `EMULATE_PREPARES=false`).
- **XSS**: semua output HTML lewat `e()` (`htmlspecialchars`).
- **Password**: `password_hash` / `password_verify`, tidak disimpan plain text.
- **Session**: `session_regenerate_id(true)` setelah login, logout menghancurkan session.
- **CSRF**: token pada setiap form POST.
