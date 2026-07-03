<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Helpers\DateHelper;
use App\Helpers\StokHelper;

/**
 * Stok listeleri PDF/Excel dışa aktarma yardımcısı.
 */
final class StokExportHelper
{
    /**
     * @return list<string>
     */
    public static function indexHeaders(): array
    {
        return ['Kod', 'Malzeme', 'Kategori', 'Birim', 'Mevcut', 'Min.', 'Durum'];
    }

    /**
     * @param list<object> $items
     * @return list<list<string>>
     */
    public static function indexRows(array $items): array
    {
        $out = [];
        foreach ($items as $row) {
            $mevcut = (float) ($row->mevcut_miktar ?? 0);
            $min = (float) ($row->min_stok ?? 0);
            $kritik = $min > 0 && $mevcut < $min;
            $out[] = [
                (string) ($row->kod ?? ''),
                (string) ($row->ad ?? ''),
                StokHelper::kategoriLabel((string) ($row->kategori ?? '')),
                StokHelper::birimLabel((string) ($row->birim ?? '')),
                StokHelper::formatMiktar($mevcut),
                StokHelper::formatMiktar($min),
                $kritik ? 'Kritik' : 'Normal',
            ];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function hareketHeaders(): array
    {
        return ['Tarih', 'Tip', 'Malzeme', 'Miktar', 'Birim', 'Hasta', 'Ekip', 'Kullanıcı', 'Not'];
    }

    /**
     * @param list<object> $items
     * @return list<list<string>>
     */
    public static function hareketRows(array $items): array
    {
        $out = [];
        foreach ($items as $row) {
            $hastaAd = trim((string) ($row->hasta_isim ?? '') . ' ' . (string) ($row->hasta_soyisim ?? ''));
            $ekipLbl = '';
            if (!empty($row->ekip_tarih)) {
                $ekipLbl = DateHelper::toTrOrEmpty($row->ekip_tarih);
                if (!empty($row->ekip_no)) {
                    $ekipLbl .= ' #' . (int) $row->ekip_no;
                }
            }
            $out[] = [
                DateHelper::toTrOrEmpty($row->hareket_tarihi ?? ''),
                StokHelper::hareketTipiLabel((string) ($row->hareket_tipi ?? '')),
                (string) ($row->malzeme_adi ?? ''),
                StokHelper::formatMiktar($row->miktar ?? 0),
                StokHelper::birimLabel((string) ($row->malzeme_birim ?? '')),
                $hastaAd !== '' ? $hastaAd : '—',
                $ekipLbl !== '' ? $ekipLbl : '—',
                (string) ($row->kullanici_adi ?? '—'),
                (string) ($row->aciklama ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function siparisHeaders(): array
    {
        return ['Kod', 'Malzeme', 'Kategori', 'Mevcut', 'Min.', 'Öneri', 'Birim', 'Tedarikçi'];
    }

    /**
     * @param list<object> $items
     * @return list<list<string>>
     */
    public static function siparisRows(array $items): array
    {
        $out = [];
        foreach ($items as $row) {
            $out[] = [
                (string) ($row->kod ?? ''),
                (string) ($row->ad ?? ''),
                StokHelper::kategoriLabel((string) ($row->kategori ?? '')),
                StokHelper::formatMiktar($row->mevcut_miktar ?? 0),
                StokHelper::formatMiktar($row->min_stok ?? 0),
                StokHelper::formatMiktar($row->oneri_miktar ?? 0),
                StokHelper::birimLabel((string) ($row->birim ?? '')),
                (string) ($row->tedarikci_adi ?? ''),
            ];
        }

        return $out;
    }
}
