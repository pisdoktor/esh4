<?php



declare(strict_types=1);



/**

 * TİTCK .xlsx başlık satırlarını ve sütun eşlemesini listeler (doğrulama).

 *

 *   php tools/debug-titck-xlsx-headers.php "path/to/liste.xlsx"

 */



if (PHP_SAPI !== 'cli') {

    fwrite(STDERR, "CLI gerekli.\n");

    exit(1);

}



$root = dirname(__DIR__);

require_once $root . '/config/config.php';



spl_autoload_register(static function (string $class) use ($root): void {

    $prefix = 'App\\';

    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {

        return;

    }

    $file = $root . '/app/' . str_replace('\\', '/', substr($class, $len)) . '.php';

    if (is_file($file)) {

        require $file;

    }

});



use App\Helpers\IlacListesiBuilder;

use App\Helpers\TitckXlsxReader;



$path = $argv[1] ?? '';

if ($path === '') {

    fwrite(STDERR, "Kullanım: php tools/debug-titck-xlsx-headers.php <dosya.xlsx>\n");

    exit(1);

}



$path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

if (!is_file($path)) {

    fwrite(STDERR, "Dosya yok: {$path}\n");

    exit(1);

}



$reader = new TitckXlsxReader($path);

$sheetXml = $reader->findSheetXmlByName('AKTİF ÜRÜNLER')

    ?? $reader->findSheetXmlByName('AKTIF URUNLER');



if ($sheetXml === null) {

    fwrite(STDERR, "AKTİF ÜRÜNLER sayfası bulunamadı.\n");

    exit(1);

}



echo "=== İlk 5 satır — sütun başlıkları (AKTİF ÜRÜNLER) ===\n";

foreach ($reader->dumpHeaderRows($sheetXml, 5) as $block) {

    echo "Satır {$block['excelRow']}:\n";

    foreach ($block['cells'] as $cell) {

        echo "  {$cell['col']}: {$cell['text']} [{$cell['norm']}]\n";

    }

    echo "\n";

}



$layout = $reader->findDrugSheetLayout(
    $sheetXml,
    ['İlaç Adı', 'Ilac Adi', 'ILAC ADI'],
    ['ATC Adı', 'ATC Adi', 'ATC ADI', 'Etken Madde', 'Etken Maddesi', 'Etkin Madde', 'Etkin Maddesi'],
    ['Reçete Türü', 'Recete Turu', 'REÇETE TÜRÜ']
);



if ($layout === null) {

    fwrite(STDERR, "Layout bulunamadı (İlaç Adı başlığı yok).\n");

    exit(1);

}



$desc = $reader->describeDrugLayout($sheetXml, $layout);

echo "=== Tespit edilen layout ===\n";

echo 'Başlık satırı (Excel): ' . $desc['headerRow'] . "\n";

echo 'İlaç adı sütunu: ' . ($desc['columns']['ilac_adi'] ?? '-') . "\n";

echo 'Etken / ATC Adı sütunu: ' . ($desc['columns']['etken_madde'] ?? '-') . "\n";

echo 'Reçete türü sütunu: ' . ($desc['columns']['recete_turu'] ?? '-') . "\n";

echo 'Etken tahmin (son çare): ' . ($desc['columns']['etken_inferred'] ?? '-') . "\n";

echo "\nİlk veri satırı örneği:\n";

echo '  ad: ' . $desc['sample']['ad'] . "\n";

echo '  etken_madde: ' . $desc['sample']['etken_madde'] . "\n";

echo '  recete_turu: ' . $desc['sample']['recete_turu'] . "\n";



if (TitckXlsxReader::looksLikeAtcCode($desc['sample']['etken_madde'])) {

    fwrite(STDERR, "\nUYARI: etken_madde ATC kodu gibi görünüyor — yanlış sütun olabilir.\n");

    exit(2);

}



exit(0);

