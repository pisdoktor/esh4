<?php

declare(strict_types=1);



/**

 * TİTCK Modül 43 — E-Reçete İlaç Listesi (.xlsx) → ilac-listesi.json

 *

 * Manuel indirilen Excel dosyasından «İlaç Adı», «ATC Adı» (veya «Etken Madde»), «Reçete Türü» sütunlarını okur;

 * autocomplete için benzersiz (ad), tr-TR sıralı JSON nesne dizisi yazar.

 *

 * Kullanım:

 *   php tools/build-ilac-listesi-from-titck.php "path/to/E-Reçete İlaç Listesi.xlsx"

 *   php tools/build-ilac-listesi-from-titck.php dosya.xlsx --include-pasif

 *

 * Yönetici arayüzü: Yönetim → «İlaç listesi (TİTCK)» veya IlacListesi/index

 *

 * Bağımlılık: PHP zip + simplexml (intl önerilir, zorunlu değil). Harici paket yok.

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



$args = array_slice($argv, 1);

$includePasif = false;

$xlsxPath = null;



foreach ($args as $arg) {

    if ($arg === '--include-pasif') {

        $includePasif = true;

        continue;

    }

    if ($arg === '--help' || $arg === '-h') {

        echo "Kullanım: php tools/build-ilac-listesi-from-titck.php <dosya.xlsx> [--include-pasif]\n";

        exit(0);

    }

    if ($xlsxPath === null) {

        $xlsxPath = $arg;

    }

}



if ($xlsxPath === null || $xlsxPath === '') {

    fwrite(STDERR, "Hata: XLSX dosya yolu gerekli.\n");

    fwrite(STDERR, "Örnek: php tools/build-ilac-listesi-from-titck.php \"C:\\Downloads\\E-Reçete İlaç Listesi.xlsx\"\n");

    exit(1);

}



$xlsxPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $xlsxPath);



try {

    $result = IlacListesiBuilder::buildFromXlsx($xlsxPath, $includePasif);

    $outPath = $result['outputPath'];



    foreach ($result['warnings'] as $warning) {

        fwrite(STDERR, "Uyarı: {$warning}\n");

    }



    $mtime = filemtime($xlsxPath);

    $srcDate = $mtime !== false ? date('Y-m-d H:i:s', $mtime) : 'bilinmiyor';



    echo "Kaynak: {$xlsxPath}\n";

    echo "Kaynak tarihi: {$srcDate}\n";

    echo "Çıktı: {$outPath}\n";

    echo "Yazılan benzersiz kayıt (ad): " . $result['count'] . "\n";
    echo "JSON alanları: ad, etken_madde, recete_turu\n";

    if (!$includePasif) {

        echo "Not: Pasif ürünler dahil değil (--include-pasif ile eklenebilir).\n";

    }

} catch (Throwable $e) {

    fwrite(STDERR, 'Hata: ' . $e->getMessage() . "\n");

    exit(1);

}

