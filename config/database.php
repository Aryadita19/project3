<?php
declare(strict_types=1);

// ====== SESUAIKAN DENGAN LINGKUNGAN ANDA ======
const DB_HOST = '127.0.0.1';
const DB_NAME = 'inventory_db';
const DB_USER = 'root';
const DB_PASS = '';
// Jika folder proyek di htdocs/inventory-app, maka URL publiknya:
const BASE_URL = '/inventory-app/public';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}
