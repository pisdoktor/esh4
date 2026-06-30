<?php
declare(strict_types=1);

namespace App\Services\Sms;

final class SmsPhoneNormalizer
{
    /**
     * Türkiye GSM: 905xxxxxxxxx (12 hane) döner; geçersizse null.
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $raw);
        if (!is_string($digits) || $digits === '') {
            return null;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = '9' . $digits;
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            $digits = '90' . $digits;
        }
        if (strlen($digits) !== 12 || !str_starts_with($digits, '905')) {
            return null;
        }

        return $digits;
    }

    public static function mask(string $normalized): string
    {
        if (strlen($normalized) < 8) {
            return '***';
        }

        return substr($normalized, 0, 5) . '***' . substr($normalized, -2);
    }

    public static function toDisplay(string $normalized): string
    {
        if (strlen($normalized) === 12 && str_starts_with($normalized, '90')) {
            return '0' . substr($normalized, 2);
        }

        return $normalized;
    }
}
