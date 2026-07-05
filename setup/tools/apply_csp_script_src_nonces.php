<?php
declare(strict_types=1);

/**
 * script src etiketlerine CSP nonce ekler.
 *
 *   php tools/apply_csp_script_src_nonces.php           # dry-run
 *   php tools/apply_csp_script_src_nonces.php --write
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
$write = in_array('--write', $argv ?? [], true);
$scanRoots = [$root . '/views', $root . '/templates'];
$nonceSnippet = '<?= esh_csp_nonce_attr() ?>';
$phpOpen = '<' . '?=';
$phpClose = '?' . '>';

/** @var list<string> */
$changed = [];

function apply_script_src_nonces(string $content): array
{
    global $nonceSnippet, $phpOpen, $phpClose;
    $count = 0;
    $close = preg_quote($phpClose, '/');

    $content = preg_replace_callback(
        "/echo\s+'<script\s+src=\"'\s*\.\s*htmlspecialchars\(([^,]+),\s*ENT_QUOTES,\s*'UTF-8'\)\s*\.\s*'\"><\/script>'\s*\.\s*\"\\\\n\";/",
        static function (array $m) use (&$count): string {
            $count++;
            return 'echo esh_csp_script_src_tag(' . $m[1] . ');';
        },
        $content
    ) ?? $content;

    $content = preg_replace_callback(
        "/echo\s+'<script\s+defer\s+src=\"'\s*\.\s*htmlspecialchars\(([^,]+),\s*ENT_QUOTES,\s*'UTF-8'\)\s*\.\s*'\"><\/script>'\s*\.\s*\"\\\\n\";/",
        static function (array $m) use (&$count): string {
            $count++;
            return "echo esh_csp_script_src_tag({$m[1]}, 'defer');";
        },
        $content
    ) ?? $content;

    $deferPattern = '/<script\s+defer\s+src="' . preg_quote($phpOpen, '/') . '\s*htmlspecialchars\((.+?),\s*ENT_QUOTES,\s*\'UTF-8\'\)\s*;?\s*' . $close . '"><\/script>/s';
    $content = preg_replace_callback(
        $deferPattern,
        static function (array $m) use (&$count, $phpOpen, $phpClose): string {
            $count++;
            return $phpOpen . ' esh_csp_script_src_tag(' . $m[1] . ", 'defer') " . $phpClose;
        },
        $content
    ) ?? $content;

    $modulePattern = '/<script\s+type="module"\s+src="' . preg_quote($phpOpen, '/') . '\s*htmlspecialchars\((.+?),\s*ENT_QUOTES,\s*\'UTF-8\'\)\s*;?\s*' . $close . '"><\/script>/s';
    $content = preg_replace_callback(
        $modulePattern,
        static function (array $m) use (&$count, $phpOpen, $phpClose): string {
            $count++;
            return $phpOpen . ' esh_csp_script_src_tag(' . $m[1] . ', \'type="module"\') ' . $phpClose;
        },
        $content
    ) ?? $content;

    $srcPattern = '/<script(?![^>]*\bnonce=)\s+src="' . preg_quote($phpOpen, '/') . '\s*htmlspecialchars\((.+?),\s*ENT_QUOTES,\s*\'UTF-8\'\)\s*;?\s*' . $close . '"><\/script>/s';
    $content = preg_replace_callback(
        $srcPattern,
        static function (array $m) use (&$count, $phpOpen, $phpClose): string {
            $count++;
            return $phpOpen . ' esh_csp_script_src_tag(' . $m[1] . ') ' . $phpClose;
        },
        $content
    ) ?? $content;

    $content = preg_replace_callback(
        '/<script(?![^>]*\bnonce=)\s+defer\s+src="([^"]+)"><\/script>/',
        static function (array $m) use (&$count, $nonceSnippet, $phpOpen): string {
            if (str_contains($m[0], $phpOpen)) {
                return $m[0];
            }
            $count++;
            return '<script' . $nonceSnippet . ' defer src="' . $m[1] . '"></script>';
        },
        $content
    ) ?? $content;

    $content = preg_replace_callback(
        '/<script(?![^>]*\bnonce=)(?![^>]*\bdefer)\s+src="([^"]+)"><\/script>/',
        static function (array $m) use (&$count, $nonceSnippet, $phpOpen): string {
            if (str_contains($m[0], $phpOpen)) {
                return $m[0];
            }
            $count++;
            return '<script' . $nonceSnippet . ' src="' . $m[1] . '"></script>';
        },
        $content
    ) ?? $content;

    return [$content, $count];
}

$paths = [];
foreach ($scanRoots as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }
        $paths[] = $file->getPathname();
    }
}

foreach ($paths as $path) {
    $raw = (string) file_get_contents($path);
    if (!str_contains($raw, '<script') || !str_contains($raw, 'src=')) {
        continue;
    }
    [$next, $n] = apply_script_src_nonces($raw);
    if ($n === 0 || $next === $raw) {
        continue;
    }
    $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
    $changed[] = $rel . " ({$n})";
    if ($write) {
        file_put_contents($path, $next);
    }
}

if ($changed === []) {
    echo "No changes.\n";
    exit(0);
}

echo ($write ? 'Updated' : 'Would update') . ' ' . count($changed) . " file(s):\n";
foreach ($changed as $line) {
    echo "  - {$line}\n";
}
if (!$write) {
    echo "\nRe-run with --write to apply.\n";
}
