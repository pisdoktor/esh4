<?php
namespace App\Helpers;

use App\Models\Islem;

/**
 * İzlem satırındaki yapılan işlemler (virgüllü id) ile işlem id eşlemesi.
 */
final class VisitIslemHelper {

    private static ?string $konsultasyonIslemAdiCache = null;

    public static function konsultasyonIslemId(): int {
        return IslemIdSettings::resolvedInt('konsultasyon_islem_id');
    }

    /**
     * @param string|null $yapilanCsv #__izlemler.yapilan (virgüllü, boşluklu olabilir)
     */
    public static function yapilanCsvContainsIslem(?string $yapilanCsv, int $islemId): bool {
        if ($islemId < 1) {
            return false;
        }
        $csv = trim((string) $yapilanCsv);
        if ($csv === '') {
            return false;
        }
        $norm = str_replace(' ', '', $csv);
        foreach (preg_split('/\s*,\s*/', $norm, -1, PREG_SPLIT_NO_EMPTY) as $p) {
            if ((int) $p === $islemId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Config veya virgüllü stringden pozitif işlem id listesi (örn. "8, 22").
     *
     * @return int[]
     */
    public static function parseConfiguredIslemIdList(string $raw): array {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $out = [];
        foreach (preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) as $p) {
            $i = (int) $p;
            if ($i > 0) {
                $out[] = $i;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * #__izlemler.yapilan virgüllü id dizisine çevirir.
     *
     * @return int[]
     */
    public static function yapilanCsvToIntIds(?string $yapilanCsv): array {
        $csv = trim((string) $yapilanCsv);
        if ($csv === '') {
            return [];
        }
        $norm = str_replace(' ', '', $csv);
        $out = [];
        foreach (preg_split('/\s*,\s*/', $norm, -1, PREG_SPLIT_NO_EMPTY) as $p) {
            $i = (int) $p;
            if ($i > 0) {
                $out[] = $i;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * İzlemde seçilen işlemlere göre hasta sonda alanı: çıkarım id’leri öncelikli, sonra takılı id’leri.
     *
     * @return 'off'|'on'|null null = hasta sonda alanlarına dokunma
     */
    public static function mesaneSondaDecisionFromYapilan(?string $yapilanCsv): ?string {
        $yIds = self::yapilanCsvToIntIds($yapilanCsv);
        if ($yIds === []) {
            return null;
        }
        $off = self::parseConfiguredIslemIdList(
            IslemIdSettings::resolvedCsv('visit_sonda_cikarildi_islem_ids')
        );
        $on = self::parseConfiguredIslemIdList(
            IslemIdSettings::resolvedCsv('visit_sonda_takili_islem_ids')
        );
        if ($off !== [] && array_intersect($yIds, $off) !== []) {
            return 'off';
        }
        if ($on !== [] && array_intersect($yIds, $on) !== []) {
            return 'on';
        }
        return null;
    }

    /**
     * İzlem geçmişi: KONSÜLTASYON alt satırda küçük (branş: istek, …) parantezi.
     */
    public static function yapilanlarHistoryCellHtml(
        ?string $yapilanlar,
        ?string $yapilanCsv,
        ?string $konsBransIstekJson,
        ?string $bransCsv,
        ?string $istekCsv
    ): string {
        $text = trim((string) $yapilanlar);
        if ($text === '') {
            return htmlspecialchars('—', ENT_QUOTES, 'UTF-8');
        }
        if (!self::yapilanCsvContainsIslem($yapilanCsv, self::konsultasyonIslemId())) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }

        $parts = self::yapilanlarKonsultasyonParts(
            $yapilanlar,
            $konsBransIstekJson,
            $bransCsv,
            $istekCsv
        );
        if ($parts === null) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }

        $html = [];
        if ($parts['nonKons'] !== []) {
            $html[] = htmlspecialchars(implode(', ', $parts['nonKons']), ENT_QUOTES, 'UTF-8');
        }
        $konsLabel = $parts['konsLabel'] !== '' ? $parts['konsLabel'] : 'KONSÜLTASYON';
        $html[] = htmlspecialchars($konsLabel, ENT_QUOTES, 'UTF-8');
        if ($parts['paren'] !== '') {
            $html[] = '<span class="text-muted d-block lh-sm" style="font-size:0.82em;">('
                . htmlspecialchars($parts['paren'], ENT_QUOTES, 'UTF-8') . ')</span>';
        }

        return implode('<br>', $html);
    }

    /**
     * Aktif izlem listesi (Visit::index): history ile aynı KONSÜLTASYON alt satır formatı.
     */
    public static function yapilanlarIndexCellHtml(
        ?string $yapilanlar,
        ?string $yapilanCsv,
        ?string $konsBransIstekJson,
        ?string $bransCsv,
        ?string $istekCsv
    ): string {
        return self::yapilanlarHistoryCellHtml(
            $yapilanlar,
            $yapilanCsv,
            $konsBransIstekJson,
            $bransCsv,
            $istekCsv
        );
    }

    /**
     * PDF / düz metin — index konsültasyon satır kırılımı.
     */
    public static function yapilanlarKonsultasyonCellPlain(
        ?string $yapilanlar,
        ?string $yapilanCsv,
        ?string $konsBransIstekJson,
        ?string $bransCsv,
        ?string $istekCsv
    ): string {
        $text = trim((string) $yapilanlar);
        if ($text === '') {
            return '—';
        }
        if (!self::yapilanCsvContainsIslem($yapilanCsv, self::konsultasyonIslemId())) {
            return $text;
        }

        $parts = self::yapilanlarKonsultasyonParts(
            $yapilanlar,
            $konsBransIstekJson,
            $bransCsv,
            $istekCsv
        );
        if ($parts === null) {
            return $text;
        }

        $lines = [];
        if ($parts['nonKons'] !== []) {
            $lines[] = implode(', ', $parts['nonKons']);
        }
        $konsLabel = $parts['konsLabel'] !== '' ? $parts['konsLabel'] : 'KONSÜLTASYON';
        $lines[] = $konsLabel;
        if ($parts['paren'] !== '') {
            $lines[] = '(' . $parts['paren'] . ')';
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{nonKons: string[], konsLabel: string, paren: string}|null
     */
    private static function yapilanlarKonsultasyonParts(
        ?string $yapilanlar,
        ?string $konsBransIstekJson,
        ?string $bransCsv,
        ?string $istekCsv
    ): ?array {
        $text = trim((string) $yapilanlar);
        if ($text === '') {
            return null;
        }

        $konsAd = self::konsultasyonIslemAdi();
        $items = preg_split('/\s*,\s*/', $text) ?: [];
        $nonKons = [];
        $konsLabel = '';
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }
            if ($konsAd !== '' && strcasecmp($item, $konsAd) === 0) {
                $konsLabel = $item;
                continue;
            }
            $nonKons[] = $item;
        }
        if ($konsLabel === '' && $konsAd !== '') {
            $konsLabel = $konsAd;
        }
        if ($konsLabel === '') {
            return null;
        }

        $paren = KonsBransIstekHelper::pairedDisplayText(
            $konsBransIstekJson,
            (string) $bransCsv,
            (string) $istekCsv
        );

        return [
            'nonKons' => $nonKons,
            'konsLabel' => $konsLabel,
            'paren' => $paren,
        ];
    }

    private static function konsultasyonIslemAdi(): string {
        if (self::$konsultasyonIslemAdiCache !== null) {
            return self::$konsultasyonIslemAdiCache;
        }
        $id = self::konsultasyonIslemId();
        if ($id < 1) {
            self::$konsultasyonIslemAdiCache = '';

            return self::$konsultasyonIslemAdiCache;
        }
        $m = new Islem();
        self::$konsultasyonIslemAdiCache = ($m->load($id) && trim((string) $m->islemadi) !== '')
            ? trim((string) $m->islemadi)
            : '';

        return self::$konsultasyonIslemAdiCache;
    }
}
