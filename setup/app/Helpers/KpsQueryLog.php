<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Helpers\IdHelper;
use App\Helpers\AuthHelper;
/**
 * KPS sorgu günlüğü — maskeli TC, storage/logs/kps_queries.log
 */
final class KpsQueryLog
{
    private const REL = 'storage/logs/kps_queries.log';

    public static function write(string $tc, string $status, string $message = '', string $type = 'identity'): void
    {
        if (!OperationalSettings::kpsLogQueries()) {
            return;
        }

        $dir = dirname(self::path());
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }

        $userId = AuthHelper::sessionUserId();
        $line = sprintf(
            "[%s] user=%d type=%s tc=%s status=%s msg=%s\n",
            date('Y-m-d H:i:s'),
            $userId,
            $type,
            self::maskTc($tc),
            $status,
            str_replace(["\n", "\r"], ' ', $message)
        );
        @file_put_contents(self::path(), $line, FILE_APPEND | LOCK_EX);
    }

    public static function maskTc(string $tc): string
    {
        $digits = ValidationHelper::tcDigitsOnly($tc);
        if (strlen($digits) !== 11) {
            return '***';
        }

        return substr($digits, 0, 3) . '****' . substr($digits, -4);
    }

    private static function path(): string
    {
        return rtrim((string) ROOT_PATH, '/\\') . '/' . self::REL;
    }
}
