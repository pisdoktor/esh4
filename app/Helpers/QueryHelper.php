<?php
namespace App\Helpers;

/**
 * SQL parçaları için güvenli yardımcılar (ORDER BY beyaz listesi vb.)
 */
class QueryHelper
{
    /**
     * GET orderby/orderdir → whitelist doğrulama.
     *
     * @param array<string, string> $allowed requestKey => SQL ifadesi
     * @return array{orderby: string, orderdir: string, orderFragment: string}
     */
    public static function parseListSort(
        array $allowed,
        string $defaultKey,
        string $defaultDir = 'ASC'
    ): array {
        $rawKey = isset($_GET['orderby']) ? trim((string) $_GET['orderby']) : $defaultKey;
        $orderby = isset($allowed[$rawKey]) ? $rawKey : $defaultKey;
        $orderdir = (isset($_GET['orderdir']) && strtoupper((string) $_GET['orderdir']) === 'DESC') ? 'DESC' : 'ASC';
        if ($orderby === $defaultKey && !isset($_GET['orderby']) && strtoupper($defaultDir) === 'DESC') {
            $orderdir = 'DESC';
        }
        $orderFragment = $allowed[$orderby] . ' ' . $orderdir;

        return [
            'orderby' => $orderby,
            'orderdir' => $orderdir,
            'orderFragment' => $orderFragment,
        ];
    }

    /**
     * Son izlem tarihi — deferred join öncesi ID sayfalama sorgusunda da kullanılabilir.
     */
    public static function patientSonIzlemDateSqlExpr(bool $matchPatientKurum = false): string
    {
        $izK = TenantSqlHelper::izMatchesPatientKurumSql('i', $matchPatientKurum);

        return '(SELECT i.izlemtarihi FROM #__izlemler i WHERE i.hastatckimlik = h.tckimlik AND i.yapildimi = 1'
            . $izK . ' ORDER BY i.izlemtarihi DESC LIMIT 1)';
    }

    /**
     * Hasta liste görünümlerinde kullanılan sıralama sütunları (view linkleriyle uyumlu).
     */
    public static function patientListOrderBy(string $orderby, string $orderdir): string
    {
        $allowed = self::patientListSortAllowed();
        $col = $allowed[$orderby] ?? 'h.isim';
        $dir = strtoupper($orderdir) === 'DESC' ? 'DESC' : 'ASC';

        return $col . ' ' . $dir;
    }

    /**
     * @return array<string, string>
     */
    public static function patientListSortAllowed(): array
    {
        $sonIzlem = self::patientSonIzlemDateSqlExpr();

        return [
            'h.isim' => 'h.isim',
            'h.tckimlik' => 'h.tckimlik',
            'h.mahalle' => 'h.mahalle',
            'h.dogumtarihi' => 'h.dogumtarihi',
            'h.kayittarihi' => 'h.kayittarihi',
            'sonizlemtarihi' => $sonIzlem,
            'sonizlem' => $sonIzlem,
            'h.pasiftarihi' => 'h.pasiftarihi',
            'h.pasifnedeni' => 'h.pasifnedeni',
            'h.randevutarihi' => 'h.randevutarihi',
            'h.mamaraporbitis' => 'h.mamaraporbitis',
            'h.bezraporbitis' => 'h.bezraporbitis',
        ];
    }

    /**
     * Stats hasta drill-down listeleri (supply, erapor, sonda).
     *
     * @return array{orderby: string, orderdir: string, orderFragment: string}
     */
    public static function statsPatientDrilldownSort(string $defaultKey = 'h.isim', string $defaultDir = 'ASC'): array
    {
        $sort = self::parseListSort(self::patientListSortAllowed(), $defaultKey, $defaultDir);

        return [
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
            'orderFragment' => self::patientListOrderBy($sort['orderby'], $sort['orderdir']),
        ];
    }

    /**
     * Sonda değişim listesi sıralaması.
     *
     * @return array{orderby: string, orderdir: string, orderFragment: string}
     */
    public static function statsSondaSort(string $degisimExpr, string $defaultKey = 'sonda_degisim', string $defaultDir = 'ASC'): array
    {
        $allowed = array_merge(self::patientListSortAllowed(), [
            'sonda_degisim' => $degisimExpr,
        ]);
        $sort = self::parseListSort($allowed, $defaultKey, $defaultDir);
        $fragment = $sort['orderFragment'] . ', h.isim ASC, h.soyisim ASC';

        return [
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
            'orderFragment' => $fragment,
        ];
    }

    /**
     * Workload (w alias) sıralaması.
     *
     * @return array{orderby: string, orderdir: string, orderFragment: string}
     */
    public static function statsWorkloadSort(string $defaultKey = 'hizmet_suresi_gun', string $defaultDir = 'DESC'): array
    {
        $allowed = [
            'hizmet_suresi_gun' => 'w.hizmet_suresi_gun',
            'h.isim' => 'w.isim',
            'h.tckimlik' => 'w.tckimlik',
            'h.mahalle' => 'w.mahalle_adi',
            'h.dogumtarihi' => 'w.dogumtarihi',
            'h.kayittarihi' => 'w.kayittarihi',
            'sonizlemtarihi' => 'w.sonizlemtarihi',
        ];
        $sort = self::parseListSort($allowed, $defaultKey, $defaultDir);
        $fragment = $sort['orderFragment'] . ', w.isim ASC, w.soyisim ASC';

        return [
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
            'orderFragment' => $fragment,
        ];
    }

    /**
     * Arşiv hasta listesi.
     *
     * @return array{orderby: string, orderdir: string, orderFragment: string}
     */
    public static function archivePatientSort(string $defaultKey = 'h.isim', string $defaultDir = 'ASC'): array
    {
        $allowed = [
            'h.isim' => 'h.isim',
            'h.tckimlik' => 'h.tckimlik',
            'mahalleadi' => 'm.adi',
            'ilceadi' => 'ilc.adi',
            'izlemsayisi' => 'izlemsayisi',
            'sonizlemtarihi' => 'sonizlemtarihi',
        ];
        $sort = self::parseListSort($allowed, $defaultKey, $defaultDir);
        $fragment = $sort['orderFragment'] . ', h.isim ASC, h.soyisim ASC';

        return [
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
            'orderFragment' => $fragment,
        ];
    }

    /**
     * Pansuman listesi.
     *
     * @return array{orderby: string, orderdir: string, orderFragment: string}
     */
    public static function pansumanSort(string $defaultKey = 'h.isim', string $defaultDir = 'ASC'): array
    {
        $allowed = [
            'h.isim' => 'h.isim',
            'h.tckimlik' => 'h.tckimlik',
            'mahalle' => 'm.adi',
        ];
        $sort = self::parseListSort($allowed, $defaultKey, $defaultDir);
        $fragment = $sort['orderFragment'] . ', h.isim ASC';

        return [
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
            'orderFragment' => $fragment,
        ];
    }

    /**
     * Mahalle planlama listesi.
     *
     * @return array{orderby: string, orderdir: string, orderFragment: string}
     */
    public static function planningSort(string $bolgeExpr, string $defaultKey = 'bolge', string $defaultDir = 'ASC'): array
    {
        $allowed = [
            'ilce' => 'i.adi',
            'mahalle' => 'm.adi',
            'bolge' => 'CAST(' . $bolgeExpr . ' AS UNSIGNED)',
        ];
        $sort = self::parseListSort($allowed, $defaultKey, $defaultDir);
        $fragment = $sort['orderFragment'] . ', i.adi ASC, m.adi ASC';

        return [
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
            'orderFragment' => $fragment,
        ];
    }

    /**
     * @return array{orderby: string, orderdir: string, orderFragment: string}
     */
    public static function catalogSort(array $allowed, string $defaultKey, string $defaultDir = 'ASC'): array
    {
        return self::parseListSort($allowed, $defaultKey, $defaultDir);
    }

    /**
     * @return array<string, string>
     */
    public static function catalogIdNameAllowed(string $idCol, string $nameCol): array
    {
        return [
            'id' => $idCol,
            'name' => $nameCol,
        ];
    }
}
