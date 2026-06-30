<?php
namespace App\Helpers;

class ValidationHelper {
    /** TC alanından yalnızca rakamlar. */
    public static function tcDigitsOnly(mixed $tc): string
    {
        return preg_replace('/\D/', '', (string) $tc);
    }

    /** Tam 11 rakam (kontrol hane algoritması uygulanmaz). */
    public static function isTcLength11(mixed $tc): bool
    {
        return strlen(self::tcDigitsOnly($tc)) === 11;
    }

    /**
     * T.C. Kimlik Numarası Algoritma Kontrolü
     */
    public static function isTc($tc) {
        $tc = self::tcDigitsOnly($tc);
        if (strlen($tc) != 11) return false;
        if ($tc[0] == 0) return false;

        $digits = str_split($tc);
        $d10 = (($digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8]) * 7 - 
               ($digits[1] + $digits[3] + $digits[5] + $digits[7])) % 10;
        
        // Mod negatif çıkarsa 10 ekle (Bazı PHP sürümleri için önlem)
        if ($d10 < 0) $d10 += 10;
        
        $d11 = array_sum(array_slice($digits, 0, 10)) % 10;

        if ($digits[9] != $d10 || $digits[10] != $d11) return false;

        return true;
    }

    /** Kayıt sonrası uyarı metni — algoritma tutmuyor. */
    public static function tcChecksumWarningMessage(): string
    {
        return 'TC kimlik numarası kaydedildi; resmî kontrol hane algoritmasına uymuyor. Numarayı doğrulamanız önerilir.';
    }

    /**
     * Boy/kilo vb.: virgül → nokta; yalnızca rakam ve tek ondalık nokta.
     */
    public static function normalizeDecimalDotInput(mixed $value): string
    {
        $s = trim((string) $value);
        if ($s === '') {
            return '';
        }
        $s = str_replace(',', '.', $s);
        $s = preg_replace('/[^0-9.]/', '', $s) ?? '';
        $dot = strpos($s, '.');
        if ($dot !== false) {
            $s = substr($s, 0, $dot + 1) . str_replace('.', '', substr($s, $dot + 1));
        }

        return $s;
    }

    public static function parseDecimalDot(mixed $value): float
    {
        $s = self::normalizeDecimalDotInput($value);

        return $s === '' ? 0.0 : (float) $s;
    }

    /**
     * T.C. Kimlik numarasını 123 456 789 01 formatına çevirir
     */
    public static function formatTc($tc) {
        $tc = preg_replace("/[^0-9]/", "", $tc); // Sadece rakamlar kalsın
        if (strlen($tc) != 11) return $tc;

        return substr($tc, 0, 3) . ' ' .
               substr($tc, 3, 3) . ' ' .
               substr($tc, 6, 3) . ' ' .
               substr($tc, 9, 2);
    }

    /**
     * Telefon rakamları (en fazla 11; başında 0).
     */
    public static function phoneDigits(?string $phone): string {
        $d = preg_replace('/\D/', '', (string) $phone);
        if ($d === '') {
            return '';
        }
        if ($d[0] !== '0') {
            $d = '0' . $d;
        }
        return substr($d, 0, 11);
    }

    /**
     * Tam format: 0 (5xx) xxx xx xx — 11 rakam.
     */
    public static function isPhone($phone): bool {
        return strlen(self::phoneDigits((string) $phone)) === 11;
    }

    /**
     * Görüntü formatı: 0 (532) 123 45 67
     */
    public static function formatPhoneDisplay(?string $phone): string {
        $d = self::phoneDigits($phone);
        if (strlen($d) !== 11) {
            return trim((string) $phone);
        }
        return sprintf(
            '0 (%s) %s %s %s',
            substr($d, 1, 3),
            substr($d, 4, 3),
            substr($d, 7, 2),
            substr($d, 9, 2)
        );
    }

    /**
     * ceptel1 / ceptel2 normalize ve doğrula.
     *
     * @return string|null Hata mesajı
     */
    public static function applyPhoneFields(array &$data, bool $requireCep1 = false): ?string {
        $labels = ['ceptel1' => 'Cep telefonu', 'ceptel2' => 'Telefon 2', 'ailehekimitel' => 'Aile hekimi telefonu'];
        foreach ($labels as $field => $label) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $raw = trim((string) $data[$field]);
            $required = ($field === 'ceptel1' && $requireCep1);
            if ($raw === '') {
                if ($required) {
                    return $label . ' zorunludur.';
                }
                $data[$field] = '';
                continue;
            }
            if (!self::isPhone($raw)) {
                return $label . ' formatı geçersiz. Örnek: 0 (532) 123 45 67';
            }
            $data[$field] = self::formatPhoneDisplay($raw);
        }
        return null;
    }
}