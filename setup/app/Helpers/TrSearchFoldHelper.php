<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Türkçe arama fold — JS foldTrSearch (hastailacrapor-index.js) ile uyumlu.
 */
final class TrSearchFoldHelper
{
    public static function fold(string $s): string
    {
        $s = self::trLowerUtf8(trim($s));

        return strtr($s, [
            'ı' => 'i',
            'ş' => 's',
            'ğ' => 'g',
            'ü' => 'u',
            'ö' => 'o',
            'ç' => 'c',
        ]);
    }

    /** tr-TR: I→ı, İ→i; ardından UTF-8 küçültme (JS toLocaleLowerCase('tr-TR') ile uyumlu). */
    private static function trLowerUtf8(string $s): string
    {
        $s = str_replace(['I', 'İ'], ['ı', 'i'], $s);

        return mb_strtolower($s, 'UTF-8');
    }

    public static function likePattern(string $q): string
    {
        $folded = self::fold($q);
        if ($folded === '') {
            return '%';
        }

        return '%' . str_replace(['%', '_'], ['\\%', '\\_'], $folded) . '%';
    }

    /** SQL ifadesi: sütun metnini fold() ile aynı kurallarla küçültür (utf8mb4_turkish_ci LOWER + ASCII fold). */
    public static function sqlFoldExpr(string $columnSql): string
    {
        $lowered = "LOWER({$columnSql})";

        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$lowered}, 'ı', 'i'), 'ş', 's'), 'ğ', 'g'), 'ü', 'u'), 'ö', 'o'), 'ç', 'c')";
    }
}
