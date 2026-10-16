<?php
declare(strict_types=1);

/** Ambil string dari input, rapikan spasi. */
function input_str(array $src, string $key): string
{
    $v = $src[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

/** Validasi syntactic: integer. Kembalikan null jika tidak valid. */
function input_int(array $src, string $key): ?int
{
    $v = $src[$key] ?? null;
    if (!is_string($v) && !is_int($v)) {
        return null;
    }
    $r = filter_var($v, FILTER_VALIDATE_INT);
    return $r === false ? null : (int) $r;
}

/** Validasi angka desimal (harga). */
function input_decimal(array $src, string $key): ?float
{
    $v = $src[$key] ?? null;
    if (!is_string($v) && !is_numeric($v)) {
        return null;
    }
    $r = filter_var($v, FILTER_VALIDATE_FLOAT);
    return $r === false ? null : (float) $r;
}
