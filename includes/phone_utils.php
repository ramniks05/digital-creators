<?php
declare(strict_types=1);

function phone_normalize_digits(string $to): string
{
    $digits = preg_replace('/\D+/', '', $to) ?: $to;
    if (is_string($digits) && strlen($digits) === 10 && preg_match('/^[6-9]\d{9}$/', $digits)) {
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
