<?php

declare(strict_types=1);

namespace App\Helpers;

/** ICD-10 kodunu esh_hastalikcat.id (1–21) ile eşler. */
final class Icd10CatMapper
{
    public static function toHastalikCat(string $icd): int
    {
        $icd = strtoupper(trim($icd));
        if ($icd === '') {
            return 0;
        }
        $letter = $icd[0];

        if ($letter === 'A' || $letter === 'B') {
            return 1;
        }
        if ($letter === 'C') {
            return 2;
        }
        if ($letter === 'D') {
            if (preg_match('/^D[5-8]/', $icd)) {
                return 3;
            }

            return 2;
        }
        if ($letter === 'E') {
            return 4;
        }
        if ($letter === 'F') {
            return 5;
        }
        if ($letter === 'G') {
            return 6;
        }
        if ($letter === 'H') {
            if (preg_match('/^H[6-9]/', $icd)) {
                return 8;
            }

            return 7;
        }
        if ($letter === 'I') {
            return 9;
        }
        if ($letter === 'J') {
            return 10;
        }
        if ($letter === 'K') {
            return 11;
        }
        if ($letter === 'L') {
            return 12;
        }
        if ($letter === 'M') {
            return 13;
        }
        if ($letter === 'N') {
            return 14;
        }
        if ($letter === 'O') {
            return 15;
        }
        if ($letter === 'P') {
            return 16;
        }
        if ($letter === 'Q') {
            return 17;
        }
        if ($letter === 'R') {
            return 18;
        }
        if ($letter === 'S' || $letter === 'T') {
            return 19;
        }
        if ($letter === 'V' || $letter === 'W' || $letter === 'X' || $letter === 'Y') {
            return 20;
        }
        if ($letter === 'Z') {
            return 21;
        }
        if ($letter === 'U') {
            if (preg_match('/^U0[47]/', $icd)) {
                return 10;
            }

            return 1;
        }

        return 0;
    }
}
