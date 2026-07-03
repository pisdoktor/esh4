<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

/** SKRS ICD-10-TR Excel/CSV → normalize edilmiş tanı satırları. */
final class Icd10SkrsParser
{
    /** @var list<string> */
    private const ICD_HEADERS = ['KOD', 'Kod', 'ICD', 'ICD-10', 'ICD10', 'ICD KODU', 'ICD Kodu', 'ICD KOD', 'KODU'];

    /** @var list<string> */
    private const NAME_HEADERS = ['ADI', 'Ad', 'Adı', 'TANI ADI', 'Tanı Adı', 'TANI', 'HASTALIK ADI', 'Açıklama', 'ACIKLAMA', 'TANI ADI (TR)', 'HASTALIK/TANI ADI'];

    /** @var list<string> */
    private const PARENT_HEADERS = ['ÜST KODU', 'Ust Kodu', 'Üst Kodu', 'Parent', 'Parent ICD'];

    /** @var list<string> */
    private const SEVIYE_HEADERS = ['SEVIYE', 'Seviye', 'Level'];

    /**
     * @return list<array{icd:string,hastalikadi:string}>
     */
    public static function parseFile(string $path): array
    {
        if (!is_readable($path)) {
            throw new RuntimeException('Dosya okunamadı: ' . $path);
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'csv' || $ext === 'txt') {
            return self::parseCsv($path);
        }
        if ($ext === 'xlsx') {
            return self::parseXlsx($path);
        }

        throw new RuntimeException('Desteklenen uzantılar: .csv, .xlsx — verilen: ' . $ext);
    }

    /**
     * @return list<array{icd:string,hastalikadi:string}>
     */
    public static function parseCsv(string $path): array
    {
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            throw new RuntimeException('CSV açılamadı: ' . $path);
        }
        $header = null;
        $icdCol = null;
        $nameCol = null;
        $rows = [];
        while (($line = fgetcsv($fh, 0, ';')) !== false) {
            if ($line === [null] || $line === []) {
                continue;
            }
            if (count($line) === 1 && str_contains((string) $line[0], ',')) {
                $line = str_getcsv((string) $line[0], ',');
            }
            if ($header === null) {
                $header = array_map(static fn($c) => self::normHeader((string) $c), $line);
                $icdCol = self::findColumn($header, self::ICD_HEADERS);
                $nameCol = self::findColumn($header, self::NAME_HEADERS);
                if ($icdCol === null || $nameCol === null) {
                    fclose($fh);
                    throw new RuntimeException('CSV başlık satırında ICD ve tanı adı sütunu bulunamadı.');
                }
                continue;
            }
            $icd = self::normIcd((string) ($line[$icdCol] ?? ''));
            $name = trim((string) ($line[$nameCol] ?? ''));
            if ($icd === '' || $name === '') {
                continue;
            }
            $rows[] = ['icd' => $icd, 'hastalikadi' => $name];
        }
        fclose($fh);

        return self::dedupeRows($rows);
    }

    /**
     * @return list<array{icd:string,hastalikadi:string}>
     */
    public static function parseXlsx(string $path): array
    {
        if (!IlacListesiBuilder::hasZipArchive()) {
            throw new RuntimeException('XLSX okumak için PHP zip eklentisi gerekli.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('XLSX açılamadı: ' . $path);
        }
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();
        if (!is_string($sheetXml) || $sheetXml === '') {
            throw new RuntimeException('XLSX sheet1.xml okunamadı.');
        }
        $shared = self::parseSharedStrings($sharedXml);
        $grid = self::parseSheetGrid($sheetXml, $shared);
        if ($grid === []) {
            return [];
        }
        if (self::isSkrsIcD10ListGrid($grid)) {
            return self::dedupeRows(self::parseSkrsIcD10ListGrid($grid));
        }
        $header = array_map(static fn($c) => self::normHeader((string) $c), $grid[0]);
        $icdCol = self::findColumn($header, self::ICD_HEADERS);
        $nameCol = self::findColumn($header, self::NAME_HEADERS);
        if ($icdCol === null || $nameCol === null) {
            throw new RuntimeException('XLSX başlık satırında ICD ve tanı adı sütunu bulunamadı.');
        }
        $rows = [];
        for ($i = 1, $n = count($grid); $i < $n; $i++) {
            $line = $grid[$i];
            $icd = self::normIcd((string) ($line[$icdCol] ?? ''));
            $name = trim((string) ($line[$nameCol] ?? ''));
            if ($icd === '' || $name === '') {
                continue;
            }
            $rows[] = ['icd' => $icd, 'hastalikadi' => $name];
        }

        return self::dedupeRows($rows);
    }

    /** SKRS ICD10Listesi.xlsx: Adı, Kodu, Üst Kodu, Seviye, Durum … */
    private static function isSkrsIcD10ListGrid(array $grid): bool
    {
        if ($grid === []) {
            return false;
        }
        $header = array_map(static fn($c) => self::normHeader((string) $c), $grid[0]);

        return in_array('ADI', $header, true) && in_array('KODU', $header, true);
    }

    /**
     * @param list<list<string>> $grid
     * @return list<array{icd:string,hastalikadi:string,parent_icd?:?string,seviye?:?int}>
     */
    private static function parseSkrsIcD10ListGrid(array $grid): array
    {
        $header = array_map(static fn($c) => self::normHeader((string) $c), $grid[0]);
        $nameCol = self::findColumn($header, self::NAME_HEADERS);
        $icdCol = self::findColumn($header, self::ICD_HEADERS);
        $parentCol = self::findColumn($header, self::PARENT_HEADERS);
        $seviyeCol = self::findColumn($header, self::SEVIYE_HEADERS);
        $durumCol = array_search('DURUM', $header, true);
        if ($nameCol === null || $icdCol === null) {
            throw new RuntimeException('SKRS ICD-10 listesinde Adı/Kodu sütunları bulunamadı.');
        }
        $rows = [];
        for ($i = 1, $n = count($grid); $i < $n; $i++) {
            $line = $grid[$i];
            $icd = self::normIcd((string) ($line[$icdCol] ?? ''));
            $name = trim((string) ($line[$nameCol] ?? ''));
            if ($icd === '' || $name === '' || $icd === '0') {
                continue;
            }
            if (str_contains($icd, '-')) {
                continue;
            }
            if ($durumCol !== false) {
                $durum = self::normHeader((string) ($line[$durumCol] ?? ''));
                if ($durum === 'PASIF') {
                    continue;
                }
            }
            if (!self::isImportableIcdCode($icd)) {
                continue;
            }
            $parentIcd = null;
            if ($parentCol !== null) {
                $parentRaw = trim((string) ($line[$parentCol] ?? ''));
                if ($parentRaw !== '' && $parentRaw !== '0') {
                    $parentIcd = str_contains($parentRaw, '-')
                        ? strtoupper(str_replace(',', '.', $parentRaw))
                        : self::normIcd($parentRaw);
                }
            }
            $seviye = null;
            if ($seviyeCol !== null) {
                $sev = (int) trim((string) ($line[$seviyeCol] ?? ''));
                $seviye = $sev > 0 ? $sev : null;
            }
            $rows[] = [
                'icd' => $icd,
                'hastalikadi' => $name,
                'parent_icd' => $parentIcd,
                'seviye' => $seviye,
            ];
        }

        return $rows;
    }

    private static function isImportableIcdCode(string $icd): bool
    {
        return (bool) preg_match('/^[A-Z][0-9]{2}(\.[0-9A-Z]+)*$/', $icd);
    }

    /**
     * @param list<array{icd:string,hastalikadi:string,parent_icd?:?string,seviye?:?int}> $rows
     * @return list<array{icd:string,hastalikadi:string,cat:int,parent_icd:?string,seviye:?int}>
     */
    public static function withCategories(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'icd' => $row['icd'],
                'hastalikadi' => $row['hastalikadi'],
                'cat' => Icd10CatMapper::toHastalikCat($row['icd']),
                'parent_icd' => isset($row['parent_icd']) ? ($row['parent_icd'] !== null && $row['parent_icd'] !== '' ? (string) $row['parent_icd'] : null) : null,
                'seviye' => isset($row['seviye']) && $row['seviye'] !== null ? (int) $row['seviye'] : null,
            ];
        }

        return $out;
    }

    /** @param list<string> $header @param list<string> $candidates */
    private static function findColumn(array $header, array $candidates): ?int
    {
        $normCandidates = array_map(static fn($h) => self::normHeader($h), $candidates);
        foreach ($header as $idx => $col) {
            if (in_array($col, $normCandidates, true)) {
                return (int) $idx;
            }
        }

        return null;
    }

    private static function normHeader(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtoupper($value, 'UTF-8');
    }

    private static function normIcd(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = str_replace(',', '.', $value);

        return $value;
    }

    /**
     * @param list<array{icd:string,hastalikadi:string}> $rows
     * @return list<array{icd:string,hastalikadi:string}>
     */
    private static function dedupeRows(array $rows): array
    {
        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            $icd = $row['icd'];
            if (isset($seen[$icd])) {
                continue;
            }
            $seen[$icd] = true;
            $out[] = $row;
        }

        return $out;
    }

    /** @return list<string> */
    private static function parseSharedStrings(?string $xml): array
    {
        if (!is_string($xml) || $xml === '') {
            return [];
        }
        $doc = @simplexml_load_string($xml);
        if ($doc === false) {
            return [];
        }
        $doc->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $strings = [];
        foreach ($doc->xpath('//m:si') ?: [] as $si) {
            $si->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $parts = [];
            foreach ($si->xpath('.//m:t') ?: [] as $t) {
                $parts[] = (string) $t;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    /**
     * @return list<list<string>>
     */
    private static function parseSheetGrid(string $sheetXml, array $shared): array
    {
        $doc = @simplexml_load_string($sheetXml);
        if ($doc === false) {
            return [];
        }
        $doc->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $grid = [];
        foreach ($doc->xpath('//m:sheetData/m:row') ?: [] as $row) {
            $line = [];
            foreach ($row->c as $cell) {
                $ref = (string) ($cell['r'] ?? '');
                $colIdx = self::colRefToIndex($ref);
                $type = (string) ($cell['t'] ?? '');
                $val = '';
                if ($type === 's') {
                    $idx = (int) ($cell->v ?? 0);
                    $val = (string) ($shared[$idx] ?? '');
                } else {
                    $val = (string) ($cell->v ?? ($cell->is->t ?? ''));
                }
                $line[$colIdx] = $val;
            }
            if ($line !== []) {
                $max = max(array_keys($line));
                $normalized = [];
                for ($c = 0; $c <= $max; $c++) {
                    $normalized[$c] = (string) ($line[$c] ?? '');
                }
                $grid[] = $normalized;
            }
        }

        return $grid;
    }

    private static function colRefToIndex(string $ref): int
    {
        if (!preg_match('/^([A-Z]+)/', strtoupper($ref), $m)) {
            return 0;
        }
        $letters = $m[1];
        $idx = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $idx = $idx * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $idx - 1);
    }
}
