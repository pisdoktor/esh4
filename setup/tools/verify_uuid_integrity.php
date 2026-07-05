<?php
declare(strict_types=1);

/**
 * UUID bütünlük denetimi — kullanıcı referansları INT kalıntıları ve şema uyumu.
 *
 *   php tools/verify_uuid_integrity.php
 *   php tools/verify_uuid_integrity.php --with-db
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
$withDb = in_array('--with-db', $argv ?? [], true);

/** @var list<array{severity:string,file:string,line:int,rule:string,snippet:string}> */
$findings = [];

/** @var list<array{pattern:string,rule:string,severity:string}> */
$rules = [
    [
        'rule' => 'int_cast_session_user_id',
        'severity' => 'error',
        'pattern' => '/\(int\)\s*\$_SESSION\s*\[\s*[\'"]user_id[\'"]\s*\]/',
    ],
    [
        'rule' => 'int_cast_request_user_id',
        'severity' => 'error',
        'pattern' => '/\(int\)\s*\$_(?:GET|POST|REQUEST)\s*\[\s*[\'"]user_id[\'"]\s*\]/',
    ],
    [
        'rule' => 'int_cast_filters_user_id',
        'severity' => 'error',
        'pattern' => '/\(int\)\s*\$filters\s*\[\s*[\'"]user_id[\'"]\s*\]/',
    ],
    [
        'rule' => 'ctype_digit_user_filter',
        'severity' => 'error',
        'pattern' => '/ctype_digit\s*\(\s*\$userRaw\s*\)/',
    ],
    [
        'rule' => 'int_cast_olusturan_id',
        'severity' => 'error',
        'pattern' => '/\(int\)\s*\(\s*\$(?:konusma|row|item|g|t)->olusturan_id/',
    ],
    [
        'rule' => 'sync_log_user_id_int_param',
        'severity' => 'error',
        'pattern' => '/\?int\s+\$userId\b/',
    ],
    [
        'rule' => 'stats_for_user_int_param',
        'severity' => 'error',
        'pattern' => '/\?int\s+\$forUserId\b/',
    ],
    [
        'rule' => 'user_id_numeric_gt_zero',
        'severity' => 'error',
        'pattern' => '/\$userId\s*>\s*0\s*\?\s*\$userId/',
    ],
    [
        'rule' => 'olusturan_id_zero_default',
        'severity' => 'warn',
        'pattern' => '/public\s+\$olusturan_id\s*=\s*0\b/',
    ],
    [
        'rule' => 'ensure_table_int_entity_ref',
        'severity' => 'error',
        'pattern' => '/(?:kaydeden_id|yukleyen_id|hasta_id)\s+INT(?:EGER)?(?:\s+UNSIGNED)?/',
    ],
];

/** @var list<string> */
$scanDirs = [
    $root . '/app/Controllers',
    $root . '/app/Models',
    $root . '/app/Services',
    $root . '/app/Helpers',
    $root . '/views',
    $root . '/tools/create_api_token.php',
];

foreach ($scanDirs as $scanPath) {
    if (is_file($scanPath)) {
        scanFile($scanPath, $rules, $findings);
        continue;
    }
    if (!is_dir($scanPath)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scanPath));
    foreach ($it as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        scanFile($file->getPathname(), $rules, $findings);
    }
}

/** @var array<string, list<string>> table => columns */
$userRefColumns = [
    'esh_hasta_braden' => ['kaydeden_id'],
    'esh_hasta_barthel' => ['kaydeden_id'],
    'esh_hasta_harizmi' => ['kaydeden_id'],
    'esh_hasta_itaki' => ['kaydeden_id'],
    'esh_hasta_mna' => ['kaydeden_id'],
    'esh_hasta_yara_fotolar' => ['yukleyen_id'],
    'esh_federation_sync_log' => ['user_id'],
    'esh_esys_sync_log' => ['user_id'],
    'esh_usbs_sync_log' => ['user_id'],
    'esh_audit_log' => ['user_id'],
];

if ($withDb) {
    require_once $root . '/config/config.php';
    spl_autoload_register(static function (string $class) use ($root): void {
        $prefix = 'App\\';
        $base = $root . '/app/';
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }
        $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });

    try {
        $db = \App\Core\Database::getInstance();
        foreach ($userRefColumns as $table => $columns) {
            foreach ($columns as $column) {
                $type = $db->loadResultPrepared(
                    'SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
                    [$table, $column]
                );
                if ($type === null || $type === '') {
                    continue;
                }
                $typeLower = strtolower((string) $type);
                if (!str_contains($typeLower, 'char(36)')) {
                    $findings[] = [
                        'severity' => 'error',
                        'file' => 'database:' . $table . '.' . $column,
                        'line' => 0,
                        'rule' => 'schema_user_ref_not_char36',
                        'snippet' => 'COLUMN_TYPE=' . $type,
                    ];
                }
            }
        }
    } catch (\Throwable $e) {
        $findings[] = [
            'severity' => 'warn',
            'file' => 'database',
            'line' => 0,
            'rule' => 'db_probe_failed',
            'snippet' => $e->getMessage(),
        ];
    }

    // Runtime: sync log record() UUID oturum ile TypeError vermemeli
    $_SESSION['user_id'] = '696ae1cd-e9ff-40ff-8168-f4ee6bd49eed';
    foreach (
        [
            ['App\\Services\\Federation\\FederationBridgeService', 'logSync', ['node_snapshot', 'success', 'probe.json', []]],
            ['App\\Services\\Esys\\EsysBridgeService', 'logSync', ['esh_to_esys', 'success', 'probe.json', []]],
            ['App\\Services\\Usbs\\UsbsBridgeService', 'logSync', ['esh_to_usbs', 'success', 'probe.json', []]],
        ] as [$class, $method, $args]
    ) {
        try {
            $svc = new $class();
            $svc->$method(...$args);
        } catch (\Throwable $e) {
            $findings[] = [
                'severity' => 'error',
                'file' => $class . '::' . $method,
                'line' => 0,
                'rule' => 'runtime_sync_log_uuid',
                'snippet' => $e->getMessage(),
            ];
        }
    }
}

$errors = array_filter($findings, static fn(array $f): bool => $f['severity'] === 'error');
$warns = array_filter($findings, static fn(array $f): bool => $f['severity'] === 'warn');

echo "UUID bütünlük taraması\n";
echo str_repeat('-', 60) . "\n";

if ($findings === []) {
    echo "OK — bilinen UUID anti-pattern bulunamadı.\n";
    exit(0);
}

foreach ($findings as $f) {
    $rel = str_starts_with($f['file'], $root) ? substr($f['file'], strlen($root) + 1) : $f['file'];
    $line = $f['line'] > 0 ? ':' . $f['line'] : '';
    echo sprintf(
        "[%s] %s%s — %s\n  %s\n",
        strtoupper($f['severity']),
        $rel,
        $line,
        $f['rule'],
        $f['snippet']
    );
}

echo str_repeat('-', 60) . "\n";
echo sprintf("Özet: %d hata, %d uyarı\n", count($errors), count($warns));
exit($errors === [] ? 0 : 1);

/**
 * @param list<array{pattern:string,rule:string,severity:string}> $rules
 * @param list<array{severity:string,file:string,line:int,rule:string,snippet:string}> $findings
 */
function scanFile(string $path, array $rules, array &$findings): void
{
    $content = file_get_contents($path);
    if (!is_string($content)) {
        return;
    }
    $lines = preg_split('/\R/', $content) ?: [];
    foreach ($rules as $rule) {
        if (!preg_match_all($rule['pattern'], $content, $matches, PREG_OFFSET_CAPTURE)) {
            continue;
        }
        foreach ($matches[0] as $match) {
            $offset = (int) ($match[1] ?? 0);
            $lineNo = 1;
            if ($offset > 0) {
                $lineNo = substr_count(substr($content, 0, $offset), "\n") + 1;
            }
            $snippet = trim($lines[$lineNo - 1] ?? $match[0]);
            $findings[] = [
                'severity' => $rule['severity'],
                'file' => $path,
                'line' => $lineNo,
                'rule' => $rule['rule'],
                'snippet' => $snippet,
            ];
        }
    }
}
