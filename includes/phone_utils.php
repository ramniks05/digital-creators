<?php
declare(strict_types=1);

function phone_normalize_digits(string $to): string
{
    $digits = preg_replace('/\D+/', '', $to) ?? '';
    if ($digits === '') {
        return '';
    }
    // Leading 0 on Indian mobile (e.g. 09876543210).
    if (strlen($digits) === 11 && $digits[0] === '0' && preg_match('/^[6-9]\d{9}$/', substr($digits, 1))) {
        $digits = substr($digits, 1);
    }
    if (strlen($digits) === 10 && preg_match('/^[6-9]\d{9}$/', $digits)) {
        return '91' . $digits;
    }

    return $digits;
}

/**
 * @return list<string>
 */
function phone_digit_variants(string $waId): array
{
    $digits = phone_normalize_digits($waId);
    if ($digits === '') {
        return [];
    }
    $variants = [$digits];
    if (str_starts_with($digits, '91') && strlen($digits) === 12) {
        $variants[] = substr($digits, 2);
    }

    return array_values(array_unique($variants));
}
