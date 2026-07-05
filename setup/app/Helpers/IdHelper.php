<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Birincil anahtar UUID yardımcıları (domain entity tabloları).
 */
final class IdHelper
{
    private const UUID_V4_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
    /** RFC 4122 biçimi (sürüm/variant doğrulaması yok — DB CHAR(36) entity id). */
    private const UUID_SHAPE_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public static function generateUuidV4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    public static function isValidUuid(mixed $id): bool
    {
        if (!is_string($id)) {
            return false;
        }
        $id = strtolower(trim($id));

        return $id !== '' && preg_match(self::UUID_V4_PATTERN, $id) === 1;
    }

    /**
     * Geçiş dönemi: UUID veya legacy pozitif tamsayı entity id.
     */
    public static function isValidEntityId(mixed $id): bool
    {
        if (self::isValidUuid($id)) {
            return true;
        }
        if (is_string($id)) {
            $id = strtolower(trim($id));
            if ($id !== '' && preg_match(self::UUID_SHAPE_PATTERN, $id) === 1) {
                return true;
            }
        }
        if (is_int($id)) {
            return $id > 0;
        }
        if (is_string($id)) {
            $id = trim($id);
            if ($id === '' || $id === '0') {
                return false;
            }
            if (ctype_digit($id)) {
                return (int) $id > 0;
            }
        }

        return false;
    }

    /**
     * HTTP/POST kaynaklı id normalizasyonu; geçersizse null.
     */
    public static function normalizeRequestId(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        if (is_string($raw)) {
            $raw = trim($raw);
        }
        if ($raw === '' || $raw === 0 || $raw === '0') {
            return null;
        }
        if (self::isValidUuid($raw)) {
            return strtolower((string) $raw);
        }
        if (is_string($raw)) {
            $shape = strtolower(trim($raw));
            if ($shape !== '' && preg_match(self::UUID_SHAPE_PATTERN, $shape) === 1) {
                return $shape;
            }
        }
        if (is_int($raw) && $raw > 0) {
            return (string) $raw;
        }
        if (is_string($raw) && ctype_digit($raw) && (int) $raw > 0) {
            return $raw;
        }

        return null;
    }

    /** Boş / geçersiz entity id (null, '', 0, '0'). */
    public static function isEmptyEntityId(mixed $id): bool
    {
        return self::normalizeRequestId($id) === null;
    }

    /** İki entity id eşit mi (UUID veya legacy int string). */
    public static function idsMatch(mixed $a, mixed $b): bool
    {
        $na = self::normalizeRequestId($a);
        $nb = self::normalizeRequestId($b);

        return $na !== null && $nb !== null && $na === $nb;
    }

    /** normalizeRequestId veya false (legacy int|false dönüşleri için). */
    public static function entityIdOrFalse(mixed $raw): string|false
    {
        $id = self::normalizeRequestId($raw);

        return $id ?? false;
    }

    /**
     * Virgüllü entity id listesi (planiyapan / izlemiyapan CSV).
     *
     * @return list<string>
     */
    public static function csvToEntityIds(?string $csv): array
    {
        $csv = trim((string) $csv);
        if ($csv === '') {
            return [];
        }
        $out = [];
        foreach (preg_split('/\s*,\s*/', str_replace(' ', '', $csv), -1, PREG_SPLIT_NO_EMPTY) as $part) {
            $id = self::normalizeRequestId($part);
            if ($id !== null) {
                $out[] = $id;
            }
        }

        return array_values(array_unique($out));
    }
}
