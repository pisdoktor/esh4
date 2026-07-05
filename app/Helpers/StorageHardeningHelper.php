<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * storage/exports ve benzeri hassas dizinler — web erişim engeli ve doğrulama.
 */
final class StorageHardeningHelper
{
    private const EXPORTS_HTACCESS = <<<'HTA'
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Deny from all
</IfModule>
HTA;

    private const EXPORTS_GITIGNORE = "*.json\n!.gitignore\n";

    public static function exportsDir(?string $root = null): string
    {
        $root ??= dirname(__DIR__, 2);

        return rtrim(str_replace('\\', '/', $root), '/') . '/storage/exports';
    }

    public static function ensureExportsDirectory(?string $root = null): bool
    {
        $dir = self::exportsDir($root);
        if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
            return false;
        }
        $htaccess = $dir . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, self::EXPORTS_HTACCESS);
        }
        $gitignore = $dir . '/.gitignore';
        if (!is_file($gitignore)) {
            @file_put_contents($gitignore, self::EXPORTS_GITIGNORE);
        }

        return is_dir($dir);
    }

    /**
     * @return list<string>
     */
    public static function verifyExportHardening(?string $root = null): array
    {
        $issues = [];
        $dir = self::exportsDir($root);
        if (!is_dir($dir)) {
            $issues[] = 'storage/exports dizini yok';

            return $issues;
        }
        if (!is_file($dir . '/.htaccess')) {
            $issues[] = 'storage/exports/.htaccess eksik';
        } else {
            $body = (string) @file_get_contents($dir . '/.htaccess');
            if ($body !== '' && stripos($body, 'denied') === false && stripos($body, 'Deny from all') === false) {
                $issues[] = 'storage/exports/.htaccess HTTP erişimini engellemiyor olabilir';
            }
        }
        if (!is_writable($dir)) {
            $issues[] = 'storage/exports yazılabilir değil (cron export başarısız olur)';
        }
        $backupsHtaccess = rtrim(str_replace('\\', '/', $root ?? dirname(__DIR__, 2)), '/') . '/storage/backups/.htaccess';
        if (!is_file($backupsHtaccess)) {
            $issues[] = 'storage/backups/.htaccess eksik';
        }

        return $issues;
    }
}
