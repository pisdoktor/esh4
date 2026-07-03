<?php
declare(strict_types=1);
namespace App\Helpers;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;
/**
 * Minimal .xlsx okuyucu (ZipArchive + SimpleXML) — TİTCK E-Reçete İlaç Listesi.
 */
final class TitckXlsxReader
{
    private const HEADER_SCAN_ROWS = 15;
    /** @var list<string> Başlık satırında etken için tam eşleme önceliği (normalizeKey). */
    private const ETKEN_HEADER_EXACT = [
        'ATC ADI',
        'ETKEN MADDE',
        'ETKEN MADDESI',
        'ETKIN MADDE',
        'ETKIN MADDESI',
    ];
    /** @var list<string> */
    private const ILAC_HEADER_EXACT = [
        'ILAC ADI',
    ];
    /** @var list<string> */
    private const RECETE_HEADER_EXACT = [
        'RECETE TURU',
    ];
    private ZipArchive $zip;
    /** @var list<string> */
    private array $sharedStrings = [];
    /** @var array<string, string> normalizedName => worksheet path in zip */
    private array $sheetPaths = [];
    public function __construct(string $path)
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive eklentisi gerekli.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('XLSX açılamadı (ZipArchive): ' . $path);
        }
        $this->zip = $zip;
        $this->loadSharedStrings();
        $this->loadSheetPaths();
    }
    public function __destruct()
    {
        $this->zip->close();
    }
    public function findSheetXmlByName(string $targetName): ?SimpleXMLElement
    {
        $needle = self::normalizeKey($targetName);
        foreach ($this->sheetPaths as $normalized => $zipPath) {
            if ($normalized === $needle || str_contains($normalized, $needle) || str_contains($needle, $normalized)) {
                return $this->loadWorksheetXml($zipPath);
            }
        }
        return null;
    }
    /**
     * @param list<string> $headerCandidates
     */
    public function findColumnByHeader(SimpleXMLElement $sheetXml, array $headerCandidates): ?string
    {
        $needles = array_map([self::class, 'normalizeKey'], $headerCandidates);
        $rows = $this->sheetRows($sheetXml);
        $scanLimit = min(30, count($rows));
        for ($r = 0; $r < $scanLimit; $r++) {
            foreach ($rows[$r] as $col => $value) {
                $key = self::normalizeKey($value);
                foreach ($needles as $needle) {
                    if ($key === $needle) {
                        return $col;
                    }
                }
            }
        }
        return null;
    }
    /**
     * İlaç adı / etken (ATC Adı veya Etken Madde) / reçete türü sütunlarını yalnızca başlık metniyle bulur.
     *
     * @param list<string> $adHeaders
     * @param list<string> $etkenHeaders
     * @param list<string> $receteHeaders
     * @return array{
     *   headerRow: int,
     *   ad: string,
     *   etken_madde: ?string,
     *   recete_turu: ?string,
     *   etken_inferred: bool,
     *   headerRowDisplay: int
     * }|null
     */
    public function findDrugSheetLayout(
        SimpleXMLElement $sheetXml,
        array $adHeaders,
        array $etkenHeaders,
        array $receteHeaders
    ): ?array {
        $adNeedles = self::mergeNormalizedNeedles(self::ILAC_HEADER_EXACT, $adHeaders);
        $etkenNeedles = self::mergeNormalizedNeedles(self::ETKEN_HEADER_EXACT, $etkenHeaders);
        $receteNeedles = self::mergeNormalizedNeedles(self::RECETE_HEADER_EXACT, $receteHeaders);
        $rows = $this->sheetRows($sheetXml);
        $scanLimit = min(self::HEADER_SCAN_ROWS, count($rows));
        $best = null;
        $bestScore = -1;
        for ($r = 0; $r < $scanLimit; $r++) {
            $candidate = self::layoutFromHeaderRow($rows[$r], $r, $adNeedles, $etkenNeedles, $receteNeedles);
            if ($candidate === null) {
                continue;
            }
            $score = self::scoreHeaderRowLayout($candidate);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }
        if ($best === null) {
            return null;
        }
        $etkenInferred = false;
        if ($best['etken_madde'] === null) {
            $inferred = self::inferEtkenColumnBetween(
                $rows,
                $best['headerRow'],
                $best['ad'],
                $best['recete_turu'],
                min($best['headerRow'] + 30, count($rows))
            );
            if ($inferred !== null) {
                $best['etken_madde'] = $inferred;
                $etkenInferred = true;
            }
        }
        $best['etken_inferred'] = $etkenInferred;
        $best['headerRowDisplay'] = $best['headerRow'] + 1;
        return $best;
    }
    /**
     * Yönetici yükleme / CLI doğrulama: başlık satırı ve örnek etken değeri.
     *
     * @param array{headerRow: int, ad: string, etken_madde: ?string, recete_turu: ?string, etken_inferred?: bool} $layout
     * @return array{headerRow: int, columns: array<string, string|null>, sample: array{ad: string, etken_madde: string, recete_turu: string}}
     */
    public function describeDrugLayout(SimpleXMLElement $sheetXml, array $layout): array
    {
        $rows = $this->sheetRows($sheetXml);
        $headerRow = (int) ($layout['headerRow'] ?? -1);
        $sample = ['ad' => '', 'etken_madde' => '', 'recete_turu' => ''];
        $dataRow = $headerRow + 1;
        while ($dataRow < count($rows)) {
            $ad = trim((string) ($rows[$dataRow][$layout['ad']] ?? ''));
            if ($ad !== '') {
                $etkenCol = $layout['etken_madde'] ?? null;
                $receteCol = $layout['recete_turu'] ?? null;
                $sample = [
                    'ad' => $ad,
                    'etken_madde' => $etkenCol !== null
                        ? trim((string) ($rows[$dataRow][$etkenCol] ?? ''))
                        : '',
                    'recete_turu' => $receteCol !== null
                        ? trim((string) ($rows[$dataRow][$receteCol] ?? ''))
                        : '',
                ];
                break;
            }
            $dataRow++;
        }
        return [
            'headerRow' => $headerRow + 1,
            'columns' => [
                'ilac_adi' => $layout['ad'] ?? null,
                'etken_madde' => $layout['etken_madde'] ?? null,
                'recete_turu' => $layout['recete_turu'] ?? null,
                'etken_inferred' => !empty($layout['etken_inferred']) ? 'evet' : 'hayır',
            ],
            'sample' => $sample,
        ];
    }
    /**
     * İlk N başlık satırının tüm sütun başlıklarını listeler (debug).
     *
     * @return list<array{excelRow: int, cells: list<array{col: string, text: string, norm: string}>}>
     */
    public function dumpHeaderRows(SimpleXMLElement $sheetXml, int $maxRows = 5): array
    {
        $rows = $this->sheetRows($sheetXml);
        $limit = min($maxRows, self::HEADER_SCAN_ROWS, count($rows));
        $out = [];
        for ($r = 0; $r < $limit; $r++) {
            $cells = [];
            $row = $rows[$r] ?? [];
            $cols = array_keys($row);
            usort($cols, static fn (string $a, string $b): int => self::columnIndex($a) <=> self::columnIndex($b));
            foreach ($cols as $col) {
                $text = trim((string) ($row[$col] ?? ''));
                if ($text === '') {
                    continue;
                }
                $cells[] = [
                    'col' => $col,
                    'text' => $text,
                    'norm' => self::normalizeKey($text),
                ];
            }
            $out[] = ['excelRow' => $r + 1, 'cells' => $cells];
        }
        return $out;
    }
    /**
     * @param list<string> $priorityExact
     * @param list<string> $extra
     * @return list<string>
     */
    private static function mergeNormalizedNeedles(array $priorityExact, array $extra): array
    {
        $seen = [];
        $out = [];
        foreach (array_merge($priorityExact, array_map([self::class, 'normalizeKey'], $extra)) as $needle) {
            if ($needle === '' || isset($seen[$needle])) {
                continue;
            }
            $seen[$needle] = true;
            $out[] = $needle;
        }
        return $out;
    }
    /**
     * @param array<string, string> $rowCells
     * @param list<string> $adNeedles
     * @param list<string> $etkenNeedles
     * @param list<string> $receteNeedles
     * @return array{headerRow: int, ad: string, etken_madde: ?string, recete_turu: ?string, etken_priority: int}|null
     */
    private static function layoutFromHeaderRow(
        array $rowCells,
        int $rowIndex,
        array $adNeedles,
        array $etkenNeedles,
        array $receteNeedles
    ): ?array {
        $adCol = null;
        $etkenCol = null;
        $etkenPriority = PHP_INT_MAX;
        $receteCol = null;
        foreach ($rowCells as $col => $value) {
            $key = self::normalizeKey($value);
            if ($key === '') {
                continue;
            }
            if ($adCol === null && self::matchIlacHeader($key, $adNeedles)) {
                $adCol = $col;
            }
            $etkenMatch = self::matchEtkenHeader($key, $etkenNeedles);
            if ($etkenMatch !== null && $etkenMatch < $etkenPriority) {
                $etkenCol = $col;
                $etkenPriority = $etkenMatch;
            }
            if ($receteCol === null && self::matchReceteHeader($key, $receteNeedles)) {
                $receteCol = $col;
            }
        }
        if ($adCol === null) {
            return null;
        }
        return [
            'headerRow' => $rowIndex,
            'ad' => $adCol,
            'etken_madde' => $etkenCol,
            'recete_turu' => $receteCol,
            'etken_priority' => $etkenPriority,
        ];
    }
    /**
     * @param array{headerRow: int, ad: string, etken_madde: ?string, recete_turu: ?string, etken_priority?: int} $layout
     */
    private static function scoreHeaderRowLayout(array $layout): int
    {
        $score = 10;
        if ($layout['etken_madde'] !== null) {
            $score += 100;
            $prio = (int) ($layout['etken_priority'] ?? PHP_INT_MAX);
            if ($prio < 100) {
                $score += 50 - min($prio, 49);
            }
        }
        if ($layout['recete_turu'] !== null) {
            $score += 40;
        }
        return $score;
    }
    /**
     * @param list<string> $needles normalized
     */
    private static function matchIlacHeader(string $key, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($key === $needle) {
                return true;
            }
        }
        return false;
    }
    /**
     * @param list<string> $exactNeedles normalized, öncelik sırasıyla
     * @return int|null düşük = yüksek öncelik; 1000 = gevşek eşleme
     */
    private static function matchEtkenHeader(string $key, array $exactNeedles): ?int
    {
        if ($key === '' || self::isBarcodeColumnHeader($key) || self::isExcludedEtkenHeader($key)) {
            return null;
        }
        foreach ($exactNeedles as $i => $needle) {
            if ($key === $needle) {
                return $i;
            }
        }
        if (!self::isStrictEtkenHeader($key)) {
            return null;
        }
        return 1000;
    }
    /**
     * @param list<string> $needles normalized
     */
    private static function matchReceteHeader(string $key, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($key === $needle || str_contains($key, $needle)) {
                return true;
            }
        }
        return false;
    }
    /**
     * TİTCK «ATC Adı» sütunu — okunabilir etken/ATC adı (ATC kodu sütunu değil).
     */
    private static function isAtcAdiHeader(string $key): bool
    {
        return $key === 'ATC ADI' || preg_match('/^ATC\s+ADI$/u', $key) === 1;
    }

    /**
     * ATC kodu sütunu (ATC, ATC Kodu, ATC Kod) — etken_madde olarak kullanılmaz.
     */
    private static function isAtcCodeColumnHeader(string $key): bool
    {
        if (self::isAtcAdiHeader($key)) {
            return false;
        }
        if ($key === 'ATC') {
            return true;
        }
        if (preg_match('/^ATC\s+(KODU|KOD)(\s|$)/u', $key) === 1) {
            return true;
        }
        if (preg_match('/\bATC\b.*\b(KODU|KOD)\b/u', $key) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Etken madde sütunu olarak kullanılmayacak başlıklar (ATC kodu, barkod, sıra no vb.).
     */
    private static function isExcludedEtkenHeader(string $key): bool
    {
        if (self::isAtcAdiHeader($key)) {
            return false;
        }
        if (self::isAtcCodeColumnHeader($key)) {
            return true;
        }
        if (self::isBarcodeColumnHeader($key)) {
            return true;
        }
        $needles = [
            'KODU',
            ' KOD',
            'KOD ',
            'SINIF',
            'SINIFI',
            'NUMARA',
            'GTIN',
            'EAN',
            'BARKOD',
            'SIRA NO',
            'SIRA NUMARA',
            'RECETE',
            'REÇETE',
            'ILAC ADI',
            'ILACADI',
            'FIRMA',
            'FIYAT',
            'FIYATI',
            'DOZ',
            'FORM',
            'AMBALAJ',
            'PAKET',
        ];
        foreach ($needles as $needle) {
            if ($key === $needle || str_contains($key, $needle)) {
                return true;
            }
        }
        if (preg_match('/\bKOD\b/u', $key) === 1 || preg_match('/\bKODU\b/u', $key) === 1) {
            return true;
        }
        return false;
    }
    private static function isStrictEtkenHeader(string $key): bool
    {
        if (self::isAtcAdiHeader($key)) {
            return true;
        }
        if (self::isExcludedEtkenHeader($key)) {
            return false;
        }
        $hasEtken = str_contains($key, 'ETKEN') || str_contains($key, 'ETKIN');
        $hasMadde = str_contains($key, 'MADDE') || str_contains($key, 'MADDESI');
        return $hasEtken && $hasMadde;
    }
    /**
     * Barkod / GTIN / EAN sütunları etken madde olarak kullanılmaz.
     */
    private static function isBarcodeColumnHeader(string $key): bool
    {
        if ($key === '') {
            return false;
        }
        $needles = [
            'BARKOD',
            'BARCODE',
            'GTIN',
            'EAN',
            'URUN BARKOD',
            'BARKOD NO',
            'BARKODNO',
            'BARKOD NUMARASI',
            'BARKOD NUMARA',
        ];
        foreach ($needles as $needle) {
            if ($key === $needle || str_contains($key, $needle)) {
                return true;
            }
        }
        return false;
    }
    /** GTIN/EAN tipik uzunluk (8–14 rakam). */
    public static function looksLikeBarcodeValue(string $value): bool
    {
        $value = trim($value);
        return $value !== '' && (bool) preg_match('/^\d{8,14}$/', $value);
    }
    /** WHO ATC sınıf kodu (ör. B05XA03, N02BE01) — etken madde adı değildir. */
    public static function looksLikeAtcCode(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 12) {
            return false;
        }
        if (!preg_match('/^[A-Z][A-Z0-9]+$/i', $value)) {
            return false;
        }
        if (!preg_match('/\d/', $value) || !preg_match('/[A-Z]/i', $value)) {
            return false;
        }

        return (bool) preg_match('/^[A-Z]\d{2}([A-Z]{2}(\d{2})?)+$/i', $value);
    }
    /**
     * Son çare: ilaç adı ile reçete türü arasında, başlığı ve örnekleri etken maddeye uygun sütun.
     *
     * @param list<array<string, string>> $rows
     */
    private static function inferEtkenColumnBetween(
        array $rows,
        int $headerRow,
        string $adCol,
        ?string $receteCol,
        int $scanLimit
    ): ?string {
        if ($receteCol === null || $adCol === $receteCol) {
            return null;
        }
        $adIdx = self::columnIndex($adCol);
        $recIdx = self::columnIndex($receteCol);
        if ($recIdx <= $adIdx) {
            return null;
        }
        $bestCol = null;
        $bestFilled = 0;
        $sampleEnd = min($headerRow + 25, $scanLimit, count($rows));
        for ($idx = $adIdx + 1; $idx < $recIdx; $idx++) {
            $col = self::columnLetter($idx);
            $headerKey = self::normalizeKey((string) ($rows[$headerRow][$col] ?? ''));
            if ($headerKey !== '' && (self::isBarcodeColumnHeader($headerKey) || self::isExcludedEtkenHeader($headerKey))) {
                continue;
            }
            if (self::columnSampleMostlyBarcode($rows, $col, $headerRow, $sampleEnd)) {
                continue;
            }
            if (self::columnSampleMostlyAtcCodes($rows, $col, $headerRow, $sampleEnd)) {
                continue;
            }
            $filled = 0;
            for ($r = $headerRow + 1; $r < $sampleEnd; $r++) {
                $cell = trim((string) ($rows[$r][$col] ?? ''));
                if ($cell === '' || self::looksLikeBarcodeValue($cell) || self::looksLikeAtcCode($cell)) {
                    continue;
                }
                if (preg_match('/^\d+$/', $cell)) {
                    continue;
                }
                $filled++;
            }
            if ($filled > $bestFilled) {
                $bestFilled = $filled;
                $bestCol = $col;
            }
        }
        return $bestFilled >= 3 ? $bestCol : null;
    }
    /**
     * @param list<array<string, string>> $rows
     */
    private static function columnSampleMostlyBarcode(array $rows, string $col, int $headerRow, int $sampleEnd): bool
    {
        $filled = 0;
        $barcodeLike = 0;
        for ($r = $headerRow + 1; $r < $sampleEnd; $r++) {
            $cell = trim((string) ($rows[$r][$col] ?? ''));
            if ($cell === '') {
                continue;
            }
            $filled++;
            if (self::looksLikeBarcodeValue($cell) || preg_match('/^\d+$/', $cell)) {
                $barcodeLike++;
            }
        }
        return $filled >= 3 && $barcodeLike >= (int) ceil($filled * 0.8);
    }
    /**
     * @param list<array<string, string>> $rows
     */
    private static function columnSampleMostlyAtcCodes(array $rows, string $col, int $headerRow, int $sampleEnd): bool
    {
        $filled = 0;
        $atcLike = 0;
        for ($r = $headerRow + 1; $r < $sampleEnd; $r++) {
            $cell = trim((string) ($rows[$r][$col] ?? ''));
            if ($cell === '') {
                continue;
            }
            $filled++;
            if (self::looksLikeAtcCode($cell)) {
                $atcLike++;
            }
        }
        return $filled >= 3 && $atcLike >= (int) ceil($filled * 0.8);
    }
    private static function columnIndex(string $col): int
    {
        $col = strtoupper($col);
        $n = 0;
        $len = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $n = $n * 26 + (ord($col[$i]) - 64);
        }
        return $n - 1;
    }
    private static function columnLetter(int $index): string
    {
        $index++;
        $letters = '';
        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26);
        }
        return $letters;
    }
    /**
     * @param array{headerRow: int, ad: string, etken_madde: ?string, recete_turu: ?string} $layout
     * @return list<array{ad: string, etken_madde: string, recete_turu: string}>
     */
    public function readDrugEntries(SimpleXMLElement $sheetXml, array $layout): array
    {
        $rows = $this->sheetRows($sheetXml);
        $headerRow = (int) ($layout['headerRow'] ?? -1);
        $adCol = (string) ($layout['ad'] ?? '');
        if ($headerRow < 0 || $adCol === '') {
            return [];
        }
        $etkenCol = $layout['etken_madde'] ?? null;
        $receteCol = $layout['recete_turu'] ?? null;
        $entries = [];
        for ($i = $headerRow + 1, $n = count($rows); $i < $n; $i++) {
            $rawAd = trim((string) ($rows[$i][$adCol] ?? ''));
            if ($rawAd === '') {
                continue;
            }
            $etken = $etkenCol !== null ? trim((string) ($rows[$i][$etkenCol] ?? '')) : '';
            $recete = $receteCol !== null ? trim((string) ($rows[$i][$receteCol] ?? '')) : '';
            $entries[] = [
                'ad' => $rawAd,
                'etken_madde' => $etken,
                'recete_turu' => $recete,
            ];
        }
        return $entries;
    }
    /**
     * @return list<string>
     */
    public function readColumnValues(SimpleXMLElement $sheetXml, string $column): array
    {
        $rows = $this->sheetRows($sheetXml);
        $headerRow = null;
        $needles = array_map([self::class, 'normalizeKey'], ['İlaç Adı', 'Ilac Adi']);
        foreach ($rows as $idx => $row) {
            if (!isset($row[$column])) {
                continue;
            }
            $key = self::normalizeKey($row[$column]);
            foreach ($needles as $needle) {
                if ($key === $needle) {
                    $headerRow = $idx;
                    break 2;
                }
            }
        }
        if ($headerRow === null) {
            return [];
        }
        $values = [];
        for ($i = $headerRow + 1, $n = count($rows); $i < $n; $i++) {
            $raw = $rows[$i][$column] ?? '';
            $value = trim((string) $raw);
            if ($value !== '') {
                $values[] = $value;
            }
        }
        return $values;
    }
    private function loadSharedStrings(): void
    {
        $xml = $this->readZipXml('xl/sharedStrings.xml');
        if ($xml === null) {
            return;
        }
        $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        foreach ($xml->xpath('//m:si') ?: [] as $si) {
            $si->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $parts = $si->xpath('.//m:t') ?: [];
            $text = '';
            foreach ($parts as $part) {
                $text .= (string) $part;
            }
            $this->sharedStrings[] = $text;
        }
    }
    private function loadSheetPaths(): void
    {
        $workbook = $this->readZipXml('xl/workbook.xml');
        if ($workbook === null) {
            throw new RuntimeException('xl/workbook.xml okunamadı.');
        }
        $rels = $this->readZipXml('xl/_rels/workbook.xml.rels');
        if ($rels === null) {
            throw new RuntimeException('xl/_rels/workbook.xml.rels okunamadı.');
        }
        $relMap = [];
        foreach ($rels->Relationship as $rel) {
            $attrs = $rel->attributes();
            if ($attrs === null) {
                continue;
            }
            $id = (string) ($attrs['Id'] ?? '');
            $target = (string) ($attrs['Target'] ?? '');
            if ($id !== '' && $target !== '') {
                $relMap[$id] = 'xl/' . ltrim(str_replace('\\', '/', $target), '/');
            }
        }
        $workbook->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        foreach ($workbook->xpath('//m:sheet') ?: [] as $sheet) {
            $attrs = $sheet->attributes();
            if ($attrs === null) {
                continue;
            }
            $name = (string) ($attrs['name'] ?? '');
            $rid = (string) ($sheet->attributes('r', true)['id'] ?? '');
            if ($name === '' || $rid === '' || !isset($relMap[$rid])) {
                continue;
            }
            $this->sheetPaths[self::normalizeKey($name)] = $relMap[$rid];
        }
        if ($this->sheetPaths === []) {
            throw new RuntimeException('Çalışma sayfası listesi boş.');
        }
    }
    private function loadWorksheetXml(string $zipPath): ?SimpleXMLElement
    {
        return $this->readZipXml($zipPath);
    }
    /**
     * @return list<array<string, string>> rowIndex => [colLetter => value]
     */
    private function sheetRows(SimpleXMLElement $sheetXml): array
    {
        $sheetXml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];
        $rowNodes = $sheetXml->xpath('//m:sheetData/m:row') ?: [];
        foreach ($rowNodes as $rowNode) {
            $rowIndex = (int) ($rowNode->attributes()['r'] ?? 0);
            if ($rowIndex <= 0) {
                continue;
            }
            $cells = [];
            foreach ($rowNode->c as $cell) {
                $ref = (string) ($cell->attributes()['r'] ?? '');
                if ($ref === '' || !preg_match('/^([A-Z]+)\d+$/', $ref, $m)) {
                    continue;
                }
                $cells[$m[1]] = $this->cellValue($cell);
            }
            $rows[$rowIndex - 1] = $cells;
        }
        if ($rows === []) {
            return [];
        }
        $max = max(array_keys($rows));
        $dense = [];
        for ($i = 0; $i <= $max; $i++) {
            $dense[$i] = $rows[$i] ?? [];
        }
        return $dense;
    }
    private function cellValue(SimpleXMLElement $cell): string
    {
        $type = (string) ($cell->attributes()['t'] ?? '');
        if ($type === 's') {
            $idx = (int) ($cell->v ?? 0);
            return $this->sharedStrings[$idx] ?? '';
        }
        if ($type === 'inlineStr') {
            $cell->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $parts = $cell->xpath('.//m:t') ?: [];
            $text = '';
            foreach ($parts as $part) {
                $text .= (string) $part;
            }
            return $text;
        }
        return isset($cell->v) ? (string) $cell->v : '';
    }
    private function readZipXml(string $path): ?SimpleXMLElement
    {
        $content = $this->zip->getFromName($path);
        if ($content === false) {
            return null;
        }
        $xml = @simplexml_load_string($content);
        if ($xml === false) {
            throw new RuntimeException('Geçersiz XML: ' . $path);
        }
        return $xml;
    }
    /** Türkçe I/İ duyarlı, karşılaştırma anahtarı. */
    public static function normalizeKey(string $value): string
    {
        $value = trim($value);
        $value = str_replace(["\xc2\xa0", "\u{00A0}"], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $map = [
            'İ' => 'I', 'I' => 'I', 'ı' => 'I', 'i' => 'I',
            'Ş' => 'S', 'ş' => 'S',
            'Ğ' => 'G', 'ğ' => 'G',
            'Ü' => 'U', 'ü' => 'U',
            'Ö' => 'O', 'ö' => 'O',
            'Ç' => 'C', 'ç' => 'C',
        ];
        $out = '';
        $len = mb_strlen($value, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($value, $i, 1, 'UTF-8');
            if (isset($map[$ch])) {
                $out .= $map[$ch];
            } else {
                $out .= mb_strtoupper($ch, 'UTF-8');
            }
        }
        return $out;
    }
}
