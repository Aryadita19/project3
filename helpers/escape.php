<?php
declare(strict_types=1);

/** Output encoding: selalu pakai e() saat menampilkan data ke HTML (anti-XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function rupiah(float|int|string $n): string
{
    return 'Rp ' . number_format((float) $n, 0, ',', '.');
}

/** Bangun query string dengan mempertahankan search/filter/sort saat pindah halaman. */
function build_query(array $override = []): string
{
    return http_build_query(array_filter(
        array_merge($_GET, $override),
        fn($v) => $v !== '' && $v !== null
    ));
}
