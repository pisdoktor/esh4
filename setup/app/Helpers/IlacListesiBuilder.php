<?php

declare(strict_types=1);



namespace App\Helpers;



use RuntimeException;



/**

 * TİTCK Modül 43 — E-Reçete İlaç Listesi (.xlsx) → ilac-listesi.json

 *

 * JSON: [{ "ad", "etken_madde", "recete_turu" }, ...] — benzersizlik `ad` (tam ilaç adı) ile;

 * aynı adda birden fazla satır varsa son okunan değer kalır (önceki string[] davranışıyla uyumlu).

 */

final class IlacListesiBuilder

{

    public const TITCK_MODULE_43_URL = 'https://www.titck.gov.tr/dinamikmodul/43';



    /** @var list<string> */

    private const SHEET_ACTIVE = ['AKTİF ÜRÜNLER', 'AKTIF URUNLER'];



    /** @var list<string> */

    private const SHEET_PASIF = ['PASİF ÜRÜNLER', 'PASIF URUNLER'];



    /** @var list<string> */

    private const DRUG_NAME_HEADERS = ['İlaç Adı', 'Ilac Adi', 'ILAC ADI'];



    /** @var list<string> */

    private const ETKEN_HEADERS = [
        'ATC Adı',
        'ATC Adi',
        'ATC ADI',
        'Etken Madde',
        'Etken Maddesi',
        'Etkin Madde',
        'Etkin Maddesi',
        'ETKEN MADDE',
        'ETKEN MADDESI',
        'ETKIN MADDE',
        'ETKIN MADDESI',
    ];



    /** @var list<string> */

    private const RECETE_HEADERS = ['Reçete Türü', 'Recete Turu', 'REÇETE TÜRÜ', 'RECETE TURU'];



    public static function defaultJsonPath(): string

    {

        return ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets'

            . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'ilac-listesi.json';

    }



    /**

     * @return array{

     *   exists: bool,

     *   path: string,

     *   mtime: ?int,

     *   mtimeFormatted: ?string,

     *   count: ?int

     * }

     */

    public static function hasZipArchive(): bool

    {

        return extension_loaded('zip') && class_exists(\ZipArchive::class, false);

    }



    public static function hasIntlCollator(): bool

    {

        return extension_loaded('intl') && class_exists(\Collator::class, false);

    }



    public static function jsonStats(?string $jsonPath = null): array

    {

        $path = $jsonPath ?? self::defaultJsonPath();

        $stats = [

            'exists' => false,

            'path' => $path,

            'mtime' => null,

            'mtimeFormatted' => null,

            'count' => null,

        ];



        if (!is_file($path)) {

            return $stats;

        }



        $stats['exists'] = true;

        $mtime = filemtime($path);

        if ($mtime !== false) {

            $stats['mtime'] = $mtime;

            $stats['mtimeFormatted'] = date('Y-m-d H:i:s', $mtime);

        }



        $raw = file_get_contents($path);

        if ($raw === false) {

            return $stats;

        }



        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {

            $stats['count'] = count($decoded);

        }



        return $stats;

    }



    /**

     * @return array{
     *   count: int,
     *   outputPath: string,
     *   warnings: list<string>,
     *   layoutDebug: ?string
     * }

     */

    public static function buildFromXlsx(string $xlsxPath, bool $includePasif = false, ?string $outputPath = null): array

    {

        if (!is_file($xlsxPath)) {

            throw new RuntimeException('Dosya bulunamadı: ' . $xlsxPath);

        }



        $sheetNames = self::SHEET_ACTIVE;

        if ($includePasif) {

            $sheetNames = array_merge($sheetNames, self::SHEET_PASIF);

        }



        $reader = new TitckXlsxReader($xlsxPath);

        /** @var array<string, array{ad: string, etken_madde: string, recete_turu: string}> */

        $byAd = [];

        $warnings = [];

        $layoutDebug = null;



        foreach ($sheetNames as $label) {

            $sheetXml = $reader->findSheetXmlByName($label);

            if ($sheetXml === null) {

                continue;

            }

            $layout = $reader->findDrugSheetLayout(

                $sheetXml,

                self::DRUG_NAME_HEADERS,

                self::ETKEN_HEADERS,

                self::RECETE_HEADERS

            );

            if ($layout === null) {

                $warnings[] = "«{$label}» sayfasında «İlaç Adı» sütunu bulunamadı; atlandı.";

                continue;

            }

            if ($layout['etken_madde'] === null) {

                $warnings[] = "«{$label}»: «ATC Adı» / «Etken Madde» sütunu bulunamadı; etken_madde boş kalır.";

            }

            if ($layout['recete_turu'] === null) {

                $warnings[] = "«{$label}»: «Reçete Türü» sütunu bulunamadı; recete_turu boş kalır.";

            }

            if ($layoutDebug === null) {

                $layoutDebug = self::formatLayoutDebug($label, $reader->describeDrugLayout($sheetXml, $layout));

            }

            foreach ($reader->readDrugEntries($sheetXml, $layout) as $entry) {
                // Yanlış sütun eşlemesinden kalan barkod değerleri: TİTCK .xlsx yeniden yükleyin.
                $entry['etken_madde'] = self::sanitizeEtkenMadde($entry['etken_madde']);
                $byAd[$entry['ad']] = $entry;
            }

        }



        if ($byAd === []) {

            throw new RuntimeException(

                'Hiç ilaç adı okunamadı. Sayfa/sütun adlarını ve dosyayı kontrol edin.'

            );

        }



        $list = array_values($byAd);

        self::sortDrugEntryList($list);



        if (!self::hasIntlCollator()) {

            $warnings[] = 'PHP intl (Collator) yüklü değil; sıralama yaklaşık Türkçe kurallarıyla yapıldı. '

                . 'Tam tr-TR sıralama için php_intl önerilir.';

        }



        $outPath = $outputPath ?? self::defaultJsonPath();

        $outDir = dirname($outPath);

        if (!is_dir($outDir) && !mkdir($outDir, 0755, true) && !is_dir($outDir)) {

            throw new RuntimeException('Çıktı dizini oluşturulamadı: ' . $outDir);

        }



        $json = json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($json === false) {

            throw new RuntimeException('JSON kodlama hatası: ' . json_last_error_msg());

        }



        if (file_put_contents($outPath, $json . "\n") === false) {

            throw new RuntimeException('Yazılamadı: ' . $outPath);

        }



        return [

            'count' => count($list),

            'outputPath' => $outPath,

            'warnings' => $warnings,

            'layoutDebug' => $layoutDebug,

        ];

    }



    /**

     * @param list<array{ad: string, etken_madde: string, recete_turu: string}> $list

     */

    private static function sortDrugEntryList(array &$list): void

    {

        usort($list, static fn (array $a, array $b): int => self::compareDrugNames($a['ad'], $b['ad']));

    }



    private static function compareDrugNames(string $a, string $b): int

    {

        static $collator = null;

        if (self::hasIntlCollator()) {

            if ($collator === null) {

                $collator = new \Collator('tr_TR');

            }



            return $collator->compare($a, $b);

        }



        return strnatcasecmp(self::drugNameSortKey($a), self::drugNameSortKey($b));

    }



    /**
     * Barkod rakamları etken madde sayılmaz (8–14 hane).
     * Düzeltmeden sonra ilac-listesi.json için TİTCK Excel yeniden yüklenmelidir.
     */
    private static function sanitizeEtkenMadde(string $etken): string
    {
        $etken = trim($etken);
        if ($etken === ''
            || TitckXlsxReader::looksLikeBarcodeValue($etken)
            || TitckXlsxReader::looksLikeAtcCode($etken)) {
            return '';
        }

        return $etken;
    }

    /**
     * @param array{
     *   headerRow: int,
     *   columns: array<string, string|null>,
     *   sample: array{ad: string, etken_madde: string, recete_turu: string}
     * } $desc
     */
    private static function formatLayoutDebug(string $sheetLabel, array $desc): string
    {
        $cols = $desc['columns'];
        $sample = $desc['sample'];
        $etkenSample = $sample['etken_madde'];
        $etkenNote = $etkenSample === ''
            ? 'boş'
            : (TitckXlsxReader::looksLikeAtcCode($etkenSample) ? 'ATC kodu gibi — sütunu kontrol edin' : $etkenSample);

        return sprintf(
            '«%s» başlık satırı %d; sütunlar: ilaç=%s, etken/ATC Adı=%s (tahmin %s), reçete=%s; örnek: %s',
            $sheetLabel,
            $desc['headerRow'],
            $cols['ilac_adi'] ?? '-',
            $cols['etken_madde'] ?? '-',
            $cols['etken_inferred'] ?? '-',
            $cols['recete_turu'] ?? '-',
            $etkenNote
        );
    }

    /** Yaklaşık Türkçe sıralama anahtarı (intl yokken). */

    private static function drugNameSortKey(string $name): string

    {

        $name = str_replace(['İ', 'I'], ['i', 'ı'], $name);

        if (function_exists('mb_strtolower')) {

            return mb_strtolower($name, 'UTF-8');

        }



        return strtolower($name);

    }

}

