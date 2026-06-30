<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Denizli Büyükşehir Belediyesi mezarlık API üzerinden vefat kontrolü.
 */
final class BelediyeMezarlikVefatProvider
{
    /**
     * @param object $patient isim, soyisim, anneAdi, babaAdi içeren hasta nesnesi
     * @return string|null Ölüm tarihi d-m-Y veya bulunamadıysa null
     */
    public static function checkByPatient(object $patient): ?string
    {
        $limitTimestamp = strtotime('-1 month');
        $isim = mb_strtoupper(trim((string) ($patient->isim ?? '')), 'UTF-8');
        $soyisim = mb_strtoupper(trim((string) ($patient->soyisim ?? '')), 'UTF-8');
        $anneAdi = mb_strtoupper(trim((string) ($patient->anneAdi ?? '')), 'UTF-8');
        $babaAdi = mb_strtoupper(trim((string) ($patient->babaAdi ?? '')), 'UTF-8');

        $link = 'http://mezarlik.denizli.bel.tr/sorgu.ashx?islem=definListesiGetir';
        $link .= '&ad=' . urlencode($isim);
        $link .= '&soyad=' . urlencode($soyisim);
        $link .= '&anneAd=' . urlencode($anneAdi);
        $link .= '&babaAd=' . urlencode($babaAdi);

        try {
            $jsonRaw = @file_get_contents($link);
            if (!$jsonRaw || strlen($jsonRaw) < 10) {
                return null;
            }

            $data = json_decode($jsonRaw, true);
            if (!is_array($data) || !isset($data[0]['olumTarihi'])) {
                return null;
            }

            $olumTarihiRaw = $data[0]['olumTarihi'];
            $olumTimestamp = strtotime((string) $olumTarihiRaw);
            if ($olumTimestamp === false || $olumTimestamp <= $limitTimestamp) {
                return null;
            }

            return date('d-m-Y', $olumTimestamp);
        } catch (\Exception $e) {
            return null;
        }
    }
}
