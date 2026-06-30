<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * POST mass-assignment koruması — yalnızca izin verilen alanları geçirir.
 */
final class PostAllowlistHelper
{
    /**
     * @param list<string> $allowedKeys
     */
    public static function pick(array $source, array $allowedKeys): array
    {
        $out = [];
        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $source)) {
                $out[$key] = $source[$key];
            }
        }

        return $out;
    }
}
