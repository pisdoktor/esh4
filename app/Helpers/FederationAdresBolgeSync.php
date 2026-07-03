<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use App\Models\Address;
use App\Models\FederationRegion;

/**
 * Federasyon bölgesi (#__federation_regions) ↔ adres ağacı kök bölgesi (tip=bolge).
 */
final class FederationAdresBolgeSync
{
    public static function columnReady(): bool
    {
        try {
            $db = Database::getInstance();
            $row = $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
                [$db->replacePrefix('#__adrestablosu'), 'federation_bolge_id']
            );

            return $row !== null && $row !== false && $row !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    public static function buildAdi(object $region): string
    {
        $ad = trim((string) ($region->ad ?? ''));
        $il = trim((string) ($region->il_adi ?? ''));
        if ($ad === '') {
            return 'Bölge';
        }
        if ($il !== '' && stripos($ad, $il) === false) {
            return $ad . ' (' . $il . ')';
        }

        return $ad;
    }

    /**
     * Tek federasyon bölgesi için adres ağacında tip=bolge kaydı oluşturur veya günceller.
     *
     * @return string|null adrestablosu.id
     */
    public static function syncFromRegion(FederationRegion $region): ?string
    {
        if (!self::columnReady() || !FederationRegion::tableExists()) {
            return null;
        }
        $fedId = (int) ($region->id ?? 0);
        if ($fedId <= 0) {
            return null;
        }

        $adi = self::buildAdi($region);
        $db = Database::getInstance();
        $tbl = $db->replacePrefix('#__adrestablosu');
        $existingId = self::findAdresBolgeIdByFederationId($fedId);

        if ($existingId !== null) {
            $db->executePrepared(
                'UPDATE ' . $tbl . ' SET adi = ? WHERE id = ? AND tip = ?',
                [$adi, $existingId, 'bolge']
            );

            return $existingId;
        }

        $linked = self::linkExistingUnlinkedBolge($fedId, $adi);
        if ($linked !== null) {
            return $linked;
        }

        $addr = new Address();
        $newId = Address::generateUuidV4();
        if (!$db->insertPrepared($tbl, [
            'id' => $newId,
            'adi' => $adi,
            'ust_id' => '0',
            'tip' => 'bolge',
            'federation_bolge_id' => $fedId,
            'has_coords' => 0,
        ])) {
            return null;
        }

        return $newId;
    }

    /**
     * Eksik eşlemeleri tamamlar (mevcut kurulumlar için).
     *
     * @return int senkronize edilen kayıt sayısı
     */
    public static function syncMissingLinks(): int
    {
        if (!self::columnReady() || !FederationRegion::tableExists()) {
            return 0;
        }
        $n = 0;
        foreach ((new FederationRegion())->getList(false) as $row) {
            $model = new FederationRegion();
            $model->bind($row, false);
            $fedId = (int) ($model->id ?? 0);
            if ($fedId <= 0 || self::findAdresBolgeIdByFederationId($fedId) !== null) {
                continue;
            }
            if (self::syncFromRegion($model) !== null) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * Federasyon bölgesi silindiğinde bağlı adres kaydını kaldırır veya bağlantıyı koparır.
     *
     * @return string|null hata mesajı
     */
    public static function onFederationRegionDeleted(int $federationBolgeId): ?string
    {
        if (!self::columnReady() || $federationBolgeId <= 0) {
            return null;
        }
        $adresId = self::findAdresBolgeIdByFederationId($federationBolgeId);
        if ($adresId === null) {
            return null;
        }

        $addr = new Address();
        if ($addr->adminChildCount($adresId) > 0) {
            $db = Database::getInstance();
            $db->executePrepared(
                'UPDATE ' . $db->replacePrefix('#__adrestablosu')
                . ' SET federation_bolge_id = NULL WHERE id = ? AND tip = ?',
                [$adresId, 'bolge']
            );

            return 'Federasyon bölgesi silindi; adres ağacındaki bölge kaydı alt ilçeleri olduğu için korundu (eşleme kaldırıldı).';
        }

        if ($addr->adminPatientReferenceCount($adresId) > 0) {
            return 'Adres ağacındaki bölge kaydı hasta verisinde referanslı; otomatik silinmedi.';
        }

        $addr->adminDeleteById($adresId, '0');

        return null;
    }

    public static function findAdresBolgeIdByFederationId(int $federationBolgeId): ?string
    {
        if ($federationBolgeId <= 0 || !self::columnReady()) {
            return null;
        }
        $db = Database::getInstance();
        $row = $db->fetchObjectPrepared(
            'SELECT id FROM ' . $db->replacePrefix('#__adrestablosu')
            . ' WHERE tip = ? AND federation_bolge_id = ? LIMIT 1',
            ['bolge', $federationBolgeId]
        );
        if (!$row || empty($row->id)) {
            return null;
        }

        return (string) $row->id;
    }

    private static function linkExistingUnlinkedBolge(int $fedId, string $adi): ?string
    {
        $db = Database::getInstance();
        $tbl = $db->replacePrefix('#__adrestablosu');
        $row = $db->fetchObjectPrepared(
            'SELECT id FROM ' . $tbl . ' WHERE tip = ? AND federation_bolge_id IS NULL AND adi = ? LIMIT 1',
            ['bolge', $adi]
        );
        if (!$row || empty($row->id)) {
            return null;
        }
        $id = (string) $row->id;
        if (!$db->executePrepared(
            'UPDATE ' . $tbl . ' SET federation_bolge_id = ? WHERE id = ? AND tip = ? AND federation_bolge_id IS NULL',
            [$fedId, $id, 'bolge']
        )) {
            return null;
        }

        return $id;
    }
}
