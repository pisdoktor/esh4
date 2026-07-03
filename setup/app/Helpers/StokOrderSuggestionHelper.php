<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\StokMalzeme;
use App\Services\Stok\StokService;

/**
 * Stok sipariş önerisi — kritik malzeme listesi ve özet KPI.
 */
final class StokOrderSuggestionHelper
{
    /**
     * @return array{ready:bool,count:int,items:list<object>,toplam_oneri_miktar:float}
     */
    public static function summarize(?int $kurumId = null): array
    {
        if (!StokService::moduleReady()) {
            return ['ready' => false, 'count' => 0, 'items' => [], 'toplam_oneri_miktar' => 0.0];
        }
        $model = new StokMalzeme();
        $items = $model->listSiparisOneri($kurumId);
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) ($item->oneri_miktar ?? 0);
        }

        return [
            'ready' => true,
            'count' => count($items),
            'items' => $items,
            'toplam_oneri_miktar' => $total,
        ];
    }

    /**
     * @return list<array{kod:string,ad:string,mevcut:float,oneri:float,birim:string}>
     */
    public static function exportRows(?int $kurumId = null, int $limit = 100): array
    {
        $summary = self::summarize($kurumId);
        if (!$summary['ready']) {
            return [];
        }
        $rows = [];
        foreach (array_slice($summary['items'], 0, max(1, min(500, $limit))) as $item) {
            $rows[] = [
                'kod' => (string) ($item->kod ?? ''),
                'ad' => (string) ($item->ad ?? ''),
                'mevcut' => (float) ($item->mevcut_miktar ?? 0),
                'oneri' => (float) ($item->oneri_miktar ?? 0),
                'birim' => (string) ($item->birim ?? 'adet'),
            ];
        }

        return $rows;
    }
}
