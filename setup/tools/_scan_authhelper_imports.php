<?php
/**
 * AuthHelper import eksikliği tarayıcısı — CLI
 */
declare(strict_types=1);

$roots = [
    __DIR__ . '/../views',
    __DIR__ . '/../templates',
];

$broken = [];
foreach ($roots as $root) {
    if (!is_dir($root)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($it as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        $content = file_get_contents($path);
        if ($content === false) {
            continue;
        }
        if (!preg_match('/(?<!\\\\)AuthHelper::/', $content)) {
            continue;
        }
        $hasUse = (bool) preg_match('/^use\s+App\\\\Helpers\\\\AuthHelper\s*;/m', $content);
        $hasFqcn = (bool) preg_match('/\\\\App\\\\Helpers\\\\AuthHelper::/', $content);
        if (!$hasUse && !$hasFqcn) {
            $broken[] = str_replace('\\', '/', $path);
        }
    }
}

sort($broken);
echo count($broken) . " file(s) missing AuthHelper import:\n";
foreach ($broken as $f) {
    echo $f . "\n";
}
