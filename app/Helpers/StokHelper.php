<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Stok modülü — kategori, birim ve hareket etiketleri.
 */
final class StokHelper
{
    /** @return array<string, string> */
    public static function kategoriOptions(): array
    {
        return [
            'mama' => 'Mama',
            'bez' => 'Bez',
            'pansuman' => 'Pansuman',
            'yatak' => 'Yatak',
            'sarf' => 'Sarf malzeme',
            'cihaz' => 'Cihaz',
            'diger' => 'Diğer',
        ];
    }

    /** @return array<string, string> */
    public static function birimOptions(): array
    {
        return [
            'adet' => 'Adet',
            'kutu' => 'Kutu',
            'paket' => 'Paket',
            'litre' => 'Litre',
            'kg' => 'Kg',
        ];
    }

    /** @return array<string, string> */
    public static function hareketTipiOptions(): array
    {
        return [
            'giris' => 'Giriş',
            'cikis' => 'Çıkış',
            'iade' => 'İade',
        ];
    }

    public static function kategoriLabel(?string $key): string
    {
        $opts = self::kategoriOptions();

        return $opts[$key ?? ''] ?? (string) ($key ?? '—');
    }

    public static function birimLabel(?string $key): string
    {
        $opts = self::birimOptions();

        return $opts[$key ?? ''] ?? (string) ($key ?? '—');
    }

    public static function hareketTipiLabel(?string $key): string
    {
        $opts = self::hareketTipiOptions();

        return $opts[$key ?? ''] ?? (string) ($key ?? '—');
    }

    /**
     * Assoc dizi → FormHelper::fieldSelect seçenek listesi.
     *
     * @param array<string|int, string> $assoc
     * @return list<stdClass>
     */
    public static function toSelectOptions(array $assoc): array
    {
        $out = [];
        foreach ($assoc as $k => $v) {
            $out[] = FormHelper::makeOption((string) $k, (string) $v);
        }

        return $out;
    }

    public static function formatMiktar($miktar): string
    {
        $n = (float) $miktar;
        if (abs($n - round($n)) < 0.001) {
            return (string) (int) round($n);
        }

        return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
    }

    /**
     * Hasta sarf bayrakları özet metni (çıkış formu bilgi satırı).
     *
     * @param object|null $patient
     */
    public static function patientCareFlagsSummary($patient): string
    {
        if ($patient === null) {
            return '';
        }
        $parts = [];
        if (!empty($patient->mama)) {
            $parts[] = 'Mama';
        }
        if (!empty($patient->bez)) {
            $parts[] = 'Bez';
        }
        if (!empty($patient->pansuman)) {
            $parts[] = 'Pansuman';
        }
        if (!empty($patient->yatak)) {
            $parts[] = 'Yatak';
        }
        if ($parts === []) {
            return 'Bakım/sarf bayrağı tanımlı değil';
        }

        return 'Hasta ihtiyaçları: ' . implode(', ', $parts);
    }
}
