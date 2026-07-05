<?php
declare(strict_types=1);

/**
 * Inline <script> etiketlerine CSP nonce ekler (src= olanlar atlanır).
 *
 *   php tools/apply_csp_script_nonces.php           # dry-run
 *   php tools/apply_csp_script_nonces.php --write
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
$write = in_array('--write', $argv ?? [], true);
$dirs = [
    $root . '/views',
    $root . '/templates',
    $root . '/app/Helpers',
];

$nonceSnippet = '<?= esh_csp_nonce_attr() ?>';
$nonceSnippetPhp = " . esh_csp_nonce_attr() . ";

/** @var list<string> */
$changed = [];

/**
 * @return array{0:string,1:int} [content, changeCount]
 */
function apply_nonce_to_content(string $content): array
{
    global $nonceSnippet, $nonceSnippetPhp;
    $count = 0;

    if (str_contains($content, 'esh_csp_nonce_attr')) {
        return [$content, 0];
    }

    // PHP: echo '<script>...  / echo "<script>
    $content = preg_replace_callback(
        '/echo\s+([\'"])<script(?![^\'"]*src=)/i',
        static function (array $m) use (&$count, $nonceSnippetPhp): string {
            $count++;

            return 'echo ' . $m[1] . '<script' . $nonceSnippetPhp;
        },
        $content
    ) ?? $content;

    // HTML/PHP view: <script> or <script defer> without src= and without nonce
    $content = preg_replace_callback(
        '/<script\b((?![^>]*\bsrc=)(?![^>]*\bnonce=)[^>]*)>/i',
        static function (array $m) use (&$count, $nonceSnippet): string {
            $attrs = trim($m[1]);
            $count++;
            if ($attrs === '') {
                return '<script' . $nonceSnippet . '>';
            }

            return '<script' . $nonceSnippet . ' ' . $attrs . '>';
        },
        $content
    ) ?? $content;

    return [$content, $count];
}

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['php', 'phtml'], true)) {
            continue;
        }
        $path = $file->getPathname();
        if (str_ends_with($path, '.pre-edit.bak')) {
            continue;
        }
        $original = (string) file_get_contents($path);
        [$updated, $n] = apply_nonce_to_content($original);
        if ($n <= 0 || $updated === $original) {
            continue;
        }
        $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
        $changed[] = $rel . " ({$n})";
        if ($write) {
            file_put_contents($path, $updated);
        }
    }
}

if ($changed === []) {
    echo ($write ? 'Yazılacak' : 'Bulunacak') . " değişiklik yok.\n";
    exit(0);
}

echo ($write ? 'Güncellendi' : 'Dry-run — güncellenecek') . ":\n";
foreach ($changed as $line) {
    echo "  - {$line}\n";
}
echo "\nToplam: " . count($changed) . " dosya\n";
if (!$write) {
    echo "Uygulamak için: php tools/apply_csp_script_nonces.php --write\n";
}

exit(0);
