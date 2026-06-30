<?php
namespace App\Helpers;

/**
 * İstatistik yaş bantları — ageGenderBands ile aynı anahtarlar, etiketler ve SQL/PHP mantığı.
 */
class AgeBandHelper {
    /** @var list<string> */
    public const KEYS = ['g01', 'g22', 'g318', 'g1945', 'g4665', 'g6685', 'g86'];

    /** @return list<string> */
    public static function keys(): array {
        return self::KEYS;
    }

    /** @return array<string, string> */
    public static function labels(): array {
        return [
            'g01' => '0–1 Aylık',
            'g22' => '2 Ay–2 Yaş',
            'g318' => '3–18 Yaş',
            'g1945' => '19–45 Yaş',
            'g4665' => '46–65 Yaş',
            'g6685' => '66–85 Yaş',
            'g86' => '86 Yaş+',
        ];
    }

    /** @return list<string> */
    public static function labelsOrdered(): array {
        return array_values(self::labels());
    }

    public static function label(string $key): string {
        return self::labels()[$key] ?? $key;
    }

    /** @return array<string, int> */
    public static function emptyCounts(): array {
        return array_fill_keys(self::keys(), 0);
    }

    public static function emptyObject(): object {
        return (object) self::emptyCounts();
    }

    /**
     * Doğum tarihi ifadesi için yaş bandı SUM(CASE…) satırları (virgülle ayrılmış; referans: bugün).
     */
    public static function sqlSumCaseLines(string $birthDateExpr): string {
        $d = $birthDateExpr;
        $band = self::sqlBandCaseExpr($d, 'CURDATE()');
        $lines = [];
        foreach (self::keys() as $key) {
            $lines[] = "SUM(CASE WHEN {$band} = '{$key}' THEN 1 ELSE 0 END) AS {$key}";
        }

        return implode(",\n            ", $lines);
    }

    /**
     * Referans tarihe göre yaş bandı SQL ifadesi (kayıt anı vb.).
     */
    public static function sqlBandCaseExpr(string $birthDateExpr, string $referenceDateExpr): string {
        $d = $birthDateExpr;
        $r = $referenceDateExpr;
        $invalid = "({$d} IS NULL OR {$d} = '' OR {$d} = '0000-00-00' OR {$r} IS NULL)";

        return "(CASE
            WHEN {$invalid} THEN NULL
            WHEN {$d} > {$r} THEN NULL
            WHEN {$d} >= DATE_SUB({$r}, INTERVAL 1 MONTH) THEN 'g01'
            WHEN {$d} >= DATE_SUB({$r}, INTERVAL 2 YEAR) AND {$d} < DATE_SUB({$r}, INTERVAL 2 MONTH) THEN 'g22'
            WHEN {$d} >= DATE_SUB({$r}, INTERVAL 18 YEAR) AND {$d} < DATE_SUB({$r}, INTERVAL 2 YEAR) THEN 'g318'
            WHEN {$d} >= DATE_SUB({$r}, INTERVAL 45 YEAR) AND {$d} < DATE_SUB({$r}, INTERVAL 18 YEAR) THEN 'g1945'
            WHEN {$d} >= DATE_SUB({$r}, INTERVAL 65 YEAR) AND {$d} < DATE_SUB({$r}, INTERVAL 45 YEAR) THEN 'g4665'
            WHEN {$d} >= DATE_SUB({$r}, INTERVAL 85 YEAR) AND {$d} < DATE_SUB({$r}, INTERVAL 65 YEAR) THEN 'g6685'
            WHEN {$d} < DATE_SUB({$r}, INTERVAL 85 YEAR) THEN 'g86'
            ELSE NULL
        END)";
    }

    public const UNKNOWN_KEY = '_unknown';

    /** @return array<string, string> */
    public static function labelsWithUnknown(): array {
        $labels = self::labels();
        $labels[self::UNKNOWN_KEY] = 'Yaş bilinmiyor';

        return $labels;
    }

    /** @return list<string> */
    public static function keysWithUnknown(): array {
        return array_merge(self::keys(), [self::UNKNOWN_KEY]);
    }

    /**
     * YYYY-MM-DD doğum tarihinden yaş bandı anahtarı (referans: bugün); geçersiz tarihte null.
     */
    public static function bandFromBirthDate(?string $dogumYmd): ?string {
        return self::bandFromBirthDateAt($dogumYmd, (new \DateTimeImmutable('today'))->format('Y-m-d'));
    }

    /**
     * Referans tarihte (ör. kayıt günü) yaş bandı; geçersiz veya doğum > referans ise null.
     */
    public static function bandFromBirthDateAt(?string $dogumYmd, ?string $refYmd): ?string {
        $dogumYmd = trim((string) $dogumYmd);
        $refYmd = trim((string) $refYmd);
        if ($dogumYmd === '' || str_starts_with($dogumYmd, '0000') || $refYmd === '' || str_starts_with($refYmd, '0000')) {
            return null;
        }
        try {
            $dob = new \DateTimeImmutable($dogumYmd);
            $ref = new \DateTimeImmutable($refYmd);
        } catch (\Exception $e) {
            return null;
        }
        if ($dob > $ref) {
            return null;
        }

        $sub = static fn (string $spec): \DateTimeImmutable => $ref->sub(new \DateInterval($spec));

        if ($dob >= $sub('P1M')) {
            return 'g01';
        }
        if ($dob >= $sub('P2Y') && $dob < $sub('P2M')) {
            return 'g22';
        }
        if ($dob >= $sub('P18Y') && $dob < $sub('P2Y')) {
            return 'g318';
        }
        if ($dob >= $sub('P45Y') && $dob < $sub('P18Y')) {
            return 'g1945';
        }
        if ($dob >= $sub('P65Y') && $dob < $sub('P45Y')) {
            return 'g4665';
        }
        if ($dob >= $sub('P85Y') && $dob < $sub('P65Y')) {
            return 'g6685';
        }
        if ($dob < $sub('P85Y')) {
            return 'g86';
        }

        return null;
    }
}
