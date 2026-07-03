<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Modül ve işlem id runtime kaydı: public/assets/data/app-settings.json.
 */
final class AppSettingsStore
{
    public const RUNTIME_REL = 'public/assets/data/app-settings.json';

    private const LEGACY_REL = 'storage/app-settings.json';

    /** @var bool */
    private static $migrated = false;

    public static function runtimeRelPath(): string
    {
        return self::RUNTIME_REL;
    }

    public static function runtimePath(): string
    {
        return rtrim((string) ROOT_PATH, '/\\') . '/' . self::RUNTIME_REL;
    }

    public static function migrateIfNeeded(): void
    {
        if (self::$migrated) {
            return;
        }
        self::$migrated = true;

        $newPath = self::runtimePath();
        if (is_readable($newPath)) {
            return;
        }

        $legacy = rtrim((string) ROOT_PATH, '/\\') . '/' . self::LEGACY_REL;
        if (!is_readable($legacy)) {
            return;
        }

        $dir = dirname($newPath);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }

        if (@copy($legacy, $newPath)) {
            @unlink($legacy);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function read(): array
    {
        self::migrateIfNeeded();

        $path = self::runtimePath();
        if (!is_readable($path)) {
            return ['version' => 1, 'modules' => [], 'islem_ids' => []];
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return ['version' => 1, 'modules' => [], 'islem_ids' => []];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : ['version' => 1, 'modules' => [], 'islem_ids' => []];
    }

    /** @var list<string> */
    private const PRESERVE_TOP_LEVEL_KEYS = [
        'map',
        'planning',
        'durations',
        'corporate',
        'public_hastaarama',
        'debug',
        'maintenance',
        'kps',
    ];

    /**
     * Modül / işlem id kayıtlarında operasyonel bölümleri silmemek için birleştir.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function mergePreserveExtraSections(array $data): array
    {
        $current = self::read();
        foreach (self::PRESERVE_TOP_LEVEL_KEYS as $key) {
            if (!array_key_exists($key, $data) && isset($current[$key]) && is_array($current[$key])) {
                $data[$key] = $current[$key];
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return true|string
     */
    public static function write(array $data)
    {
        self::migrateIfNeeded();

        $data = self::mergePreserveExtraSections($data);

        $path = self::runtimePath();
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return 'public/assets/data dizini oluşturulamadı.';
        }
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return 'Ayarlar JSON olarak kodlanamadı.';
        }
        $tmp = $path . '.tmp';
        if (@file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
            return 'Geçici ayar dosyası yazılamadı.';
        }
        if (!@rename($tmp, $path)) {
            @unlink($tmp);

            return 'Ayar dosyası kaydedilemedi (rename).';
        }

        return true;
    }
}
