<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * ESYS uyum hazırlığı — alan eşlemesi ve referans no normalizasyonu.
 */
final class EsysComplianceHelper
{
  private const MAPPING_FILE = 'config/esys-field-mapping.json';

  /** @var bool|null */
  private static $columnsReady = null;

  /** @var array<string, mixed>|null */
  private static $mappingCache = null;

  public static function columnsReady(): bool
  {
    if (self::$columnsReady !== null) {
      return self::$columnsReady;
    }
    try {
      $db = \App\Core\Database::getInstance();
      $tbl = $db->replacePrefix('#__hastalar');
      $row = $db->loadResultPrepared(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
        [$tbl, 'esys_hasta_ref']
      );
      self::$columnsReady = $row !== null && $row !== false && $row !== '';
    } catch (\Throwable $e) {
      self::$columnsReady = false;
    }

    return self::$columnsReady;
  }

  public static function enabled(): bool
  {
    return self::columnsReady();
  }

  /**
   * @return array<string, mixed>
   */
  public static function mapping(): array
  {
    if (self::$mappingCache !== null) {
      return self::$mappingCache;
    }

    $path = ROOT_PATH . '/' . self::MAPPING_FILE;
    if (!is_readable($path)) {
      self::$mappingCache = [];

      return self::$mappingCache;
    }

    try {
      $raw = file_get_contents($path);
      $decoded = is_string($raw) ? json_decode($raw, true, 512, JSON_THROW_ON_ERROR) : null;
      self::$mappingCache = is_array($decoded) ? $decoded : [];
    } catch (\Throwable $e) {
      self::$mappingCache = [];
    }

    return self::$mappingCache;
  }

  public static function mappingTitle(): string
  {
    $m = self::mapping();

    return trim((string) ($m['title'] ?? 'ESYS alan eşlemesi'));
  }

  public static function mappingDescription(): string
  {
    $m = self::mapping();

    return trim((string) ($m['description'] ?? ''));
  }

  /**
   * @return list<array{key: string, label: string, data: array<string, mixed>}>
   */
  public static function entitySections(): array
  {
    $m = self::mapping();
    $entities = $m['entities'] ?? [];
    if (!is_array($entities)) {
      return [];
    }

    $out = [];
    foreach ($entities as $key => $data) {
      if (!is_array($data)) {
        continue;
      }
      $out[] = [
        'key' => (string) $key,
        'label' => (string) ($data['label'] ?? $key),
        'data' => $data,
      ];
    }

    return $out;
  }

  public static function normalizeRef(?string $value): ?string
  {
    if ($value === null) {
      return null;
    }
    $v = trim($value);
    if ($v === '') {
      return null;
    }

    return substr($v, 0, 64);
  }

  /**
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
  public static function pickPatientRefs(array $data): array
  {
    $out = [];
    foreach (['esys_hasta_ref', 'esys_basvuru_ref'] as $key) {
      if (array_key_exists($key, $data)) {
        $out[$key] = self::normalizeRef(is_scalar($data[$key]) ? (string) $data[$key] : null);
      }
    }

    return $out;
  }

  /**
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
  public static function pickVisitRefs(array $data): array
  {
    $out = [];
    foreach (['esys_izlem_ref', 'esys_konsultasyon_ref'] as $key) {
      if (array_key_exists($key, $data)) {
        $out[$key] = self::normalizeRef(is_scalar($data[$key]) ? (string) $data[$key] : null);
      }
    }

    return $out;
  }

  /**
   * @return array<string, string>
   */
  public static function patientRefLabels(): array
  {
    return [
      'esys_hasta_ref' => 'ESYS hasta no',
      'esys_basvuru_ref' => 'ESYS başvuru no',
    ];
  }

  /**
   * @return array<string, string>
   */
  public static function visitRefLabels(): array
  {
    return [
      'esys_izlem_ref' => 'ESYS izlem no',
      'esys_konsultasyon_ref' => 'ESYS konsültasyon no',
    ];
  }

  public static function patientHasRefs(object $patient): bool
  {
    foreach (array_keys(self::patientRefLabels()) as $key) {
      $v = trim((string) ($patient->{$key} ?? ''));
      if ($v !== '') {
        return true;
      }
    }

    return false;
  }

  /**
   * @return array{patients_missing:int,visits_missing:int,plans_missing:int,kurum_id:?int}
   */
  public static function complianceKpis(?int $kurumId = null): array
  {
    if (!self::columnsReady()) {
      return ['patients_missing' => 0, 'visits_missing' => 0, 'plans_missing' => 0, 'kurum_id' => $kurumId];
    }
    try {
      $db = \App\Core\Database::getInstance();
      $params = [];
      $kurumSql = '';
      if ($kurumId !== null && $kurumId > 0) {
        $kurumSql = ' AND kurum_id = ?';
        $params[] = $kurumId;
      }
      $pMissing = (int) $db->loadResultPrepared(
        'SELECT COUNT(*) FROM #__hastalar WHERE pasif = 0'
          . ' AND (TRIM(COALESCE(esys_hasta_ref, \'\')) = \'\' OR TRIM(COALESCE(esys_basvuru_ref, \'\')) = \'\')'
          . $kurumSql,
        $params
      );
      $vMissing = (int) $db->loadResultPrepared(
        'SELECT COUNT(*) FROM #__izlemler WHERE yapildimi = 1'
          . ' AND (TRIM(COALESCE(esys_izlem_ref, \'\')) = \'\')'
          . $kurumSql,
        $params
      );
      $plMissing = (int) $db->loadResultPrepared(
        'SELECT COUNT(*) FROM #__pizlemler WHERE COALESCE(durum, 0) = 0'
          . ' AND (TRIM(COALESCE(esys_plan_ref, \'\')) = \'\')'
          . $kurumSql,
        $params
      );

      return [
        'patients_missing' => $pMissing,
        'visits_missing' => $vMissing,
        'plans_missing' => $plMissing,
        'kurum_id' => $kurumId,
      ];
    } catch (\Throwable $e) {
      return ['patients_missing' => 0, 'visits_missing' => 0, 'plans_missing' => 0, 'kurum_id' => $kurumId];
    }
  }

  /**
   * @param array<string, mixed> $bundle
   * @return array{ok:bool,errors:list<string>,warnings:list<string>,counts:array<string,int>}
   */
  public static function validateImportBundle(array $bundle): array
  {
    $errors = [];
    $warnings = [];
    $counts = ['patients' => 0, 'visits' => 0, 'plans' => 0, 'invalid' => 0];
    $seen = [];
    foreach (['patients' => 'tc', 'visits' => 'id', 'plans' => 'id'] as $section => $idKey) {
      $items = $bundle[$section] ?? [];
      if (!is_array($items)) {
        continue;
      }
      foreach ($items as $idx => $item) {
        if (!is_array($item)) {
          $counts['invalid']++;
          $errors[] = $section . '[' . $idx . ']: geçersiz kayıt';
          continue;
        }
        $counts[$section]++;
        $refKeys = $section === 'patients'
          ? ['esys_hasta_ref', 'esys_basvuru_ref']
          : ($section === 'visits' ? ['esys_izlem_ref'] : ['esys_plan_ref']);
        $hasRef = false;
        foreach ($refKeys as $rk) {
          $norm = self::normalizeRef(isset($item[$rk]) ? (string) $item[$rk] : null);
          if ($norm !== null) {
            $hasRef = true;
            $dupKey = $section . ':' . $rk . ':' . $norm;
            if (isset($seen[$dupKey])) {
              $warnings[] = 'Yinelenen referans: ' . $dupKey;
            }
            $seen[$dupKey] = true;
          }
        }
        if (!$hasRef) {
          $warnings[] = $section . '[' . $idx . ']: referans alanı boş';
        }
        $idVal = trim((string) ($item[$idKey] ?? $item['tckimlik'] ?? ''));
        if ($idVal === '') {
          $errors[] = $section . '[' . $idx . ']: kimlik eksik';
        }
      }
    }

    return ['ok' => $errors === [], 'errors' => $errors, 'warnings' => $warnings, 'counts' => $counts];
  }
}
