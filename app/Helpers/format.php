<?php

declare(strict_types=1);

function money(mixed $amount, string $currency = 'GHS'): string
{
    $value = is_numeric($amount) ? (float) $amount : 0.0;
    return $currency . ' ' . number_format($value, 2);
}

function money_plain(mixed $amount): string
{
    $value = is_numeric($amount) ? (float) $amount : 0.0;
    return number_format($value, 2, '.', '');
}

function format_date(mixed $date, string $format = 'd M Y'): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    try {
        $dt = $date instanceof DateTimeInterface ? $date : new DateTimeImmutable((string) $date);
        return $dt->format($format);
    } catch (Exception) {
        return (string) $date;
    }
}

function format_datetime(mixed $date, string $format = 'd M Y, H:i'): string
{
    return format_date($date, $format);
}

function format_qty(mixed $qty): string
{
    $value = is_numeric($qty) ? (float) $qty : 0.0;

    if (abs($value - round($value)) < 0.0001) {
        return number_format($value, 0);
    }

    return rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.');
}

function days_until(mixed $date): ?int
{
    if ($date === null || $date === '') {
        return null;
    }

    try {
        $target = new DateTimeImmutable((string) $date);
        $today = new DateTimeImmutable('today');
        return (int) $today->diff($target)->format('%r%a');
    } catch (Exception) {
        return null;
    }
}
