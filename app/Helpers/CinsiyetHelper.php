<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Hasta cinsiyeti — tek kaynak: 1=erkek, 2=kadın (esh_hastalar.cinsiyet).
 */
final class CinsiyetHelper
{
    public const ERKEK = '1';

    public const KADIN = '2';

    /** Kayıt / API için normalize; yalnızca '1' veya '2' döner. */
    public static function normalize(mixed $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '' || $v === '0') {
            return null;
        }
        $u = function_exists('mb_strtoupper') ? mb_strtoupper($v, 'UTF-8') : strtoupper($v);
        if (in_array($u, ['1', 'E', 'ERKEK', 'M', 'MALE'], true)) {
            return self::ERKEK;
        }
        if (in_array($u, ['2', 'K', 'KADIN', 'F', 'FEMALE'], true)) {
            return self::KADIN;
        }
        $l = function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
        if (in_array($l, ['kadın', 'kadin', 'erkek'], true)) {
            return $l === 'erkek' ? self::ERKEK : self::KADIN;
        }

        return null;
    }

    public static function isErkek(mixed $value): bool
    {
        return self::normalize($value) === self::ERKEK;
    }

    public static function isKadin(mixed $value): bool
    {
        return self::normalize($value) === self::KADIN;
    }

    public static function label(mixed $value, string $default = 'Belirtilmemiş'): string
    {
        $n = self::normalize($value);
        if ($n === self::ERKEK) {
            return 'Erkek';
        }
        if ($n === self::KADIN) {
            return 'Kadın';
        }

        return $default;
    }

    /** Liste / tablo metin rengi (Bootstrap primary / danger). */
    public static function nameColor(mixed $value): string
    {
        return self::isErkek($value) ? '#0d6efd' : '#dc3545';
    }

    public static function patientNameCssClass(mixed $value): string
    {
        return self::isErkek($value) ? 'esh-patient-gender-male' : 'esh-patient-gender-female';
    }

    /**
     * FormHelper btnCheckRadioGroup seçenekleri (Erkek/Kadın).
     *
     * @return list<array<string, mixed>>
     */
    public static function formRadioOptions(object|array|null $patient = null): array
    {
        $cinsiyet = null;
        if (is_object($patient)) {
            $cinsiyet = $patient->cinsiyet ?? null;
        } elseif (is_array($patient)) {
            $cinsiyet = $patient['cinsiyet'] ?? null;
        }
        $norm = self::normalize($cinsiyet);
        $erkekChecked = $norm === self::ERKEK || ($norm === null && $cinsiyet === null);

        return [
            [
                'value' => self::ERKEK,
                'id' => 'genderMale',
                'labelHtml' => '<i class="fa-solid fa-mars me-1"></i> Erkek',
                'btnClass' => 'btn btn-outline-primary shadow-sm py-2',
                'checked' => $erkekChecked,
            ],
            [
                'value' => self::KADIN,
                'id' => 'genderFemale',
                'labelHtml' => '<i class="fa-solid fa-venus me-1"></i> Kadın',
                'btnClass' => 'btn btn-outline-danger shadow-sm py-2',
                'checked' => $norm === self::KADIN,
            ],
        ];
    }
}
