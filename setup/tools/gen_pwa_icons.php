<?php
declare(strict_types=1);

$dir = dirname(__DIR__) . '/public/icons';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

foreach ([192, 512] as $size) {
    $im = imagecreatetruecolor($size, $size);
    if ($im === false) {
        fwrite(STDERR, "GD failed for size {$size}\n");
        exit(1);
    }
    $bg = imagecolorallocate($im, 25, 135, 84);
    imagefilledrectangle($im, 0, 0, $size - 1, $size - 1, $bg);
    $white = imagecolorallocate($im, 255, 255, 255);
    $font = 5;
    $text = 'S';
    $tw = imagefontwidth($font) * strlen($text);
    $th = imagefontheight($font);
    imagestring($im, $font, (int) (($size - $tw) / 2), (int) (($size - $th) / 2), $text, $white);
    $path = $dir . '/icon-' . $size . '.png';
    if (!imagepng($im, $path)) {
        fwrite(STDERR, "Write failed: {$path}\n");
        exit(1);
    }
    imagedestroy($im);
}

echo "OK: {$dir}\n";
