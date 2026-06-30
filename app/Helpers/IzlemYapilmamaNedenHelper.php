<?php
namespace App\Helpers;

/**
 * İzlem kaydında yapılmadı (yapildimi=0) için yapılmama nedeni kodları (1–8) ve etiketleri.
 * `neden` alanında kod rakamı string olarak saklanır ("1" … "8"). Eski kayıtlarda tam metin kalabilir.
 */
class IzlemYapilmamaNedenHelper {
    /** Diğer / bilinmeyen kod */
    public const KEY_DIGER = 8;

    /**
     * @return array<int, string> 1 => Hastanede yatıyor, …, 8 => Diğer
     */
    public static function labels(): array {
        return [
            1 => 'Hastanede yatıyor',
            2 => 'Vefat etmiş',
            3 => 'Adresi değişmiş',
            4 => '112 ye teslim edildi',
            5 => 'Hizmet reddedildi/İptal edildi',
            6 => 'Evde kendisi/yakını yok',
            7 => 'Gerekli evrak yok',
            self::KEY_DIGER => 'Diğer',
        ];
    }

    public static function defaultKey(): int {
        return 1;
    }

    /**
     * Geçerli 1–8 aralığına indirger; aksi 8 (Diğer).
     */
    public static function normalizeKey($raw): int {
        $n = is_numeric($raw) ? (int) $raw : 0;
        return isset(self::labels()[$n]) ? $n : self::KEY_DIGER;
    }

    /**
     * DB'deki değerden (sayı veya eski tam metin) radyo anahtarı 1–8.
     */
    public static function parseKey(?string $stored): int {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return self::defaultKey();
        }
        if (preg_match('/^\d+$/', $stored)) {
            return self::normalizeKey((int) $stored);
        }

        $labels = self::labels();
        foreach ($labels as $k => $label) {
            if ($stored === $label) {
                return (int) $k;
            }
        }

        $digerLabel = $labels[self::KEY_DIGER];
        if ($stored === $digerLabel) {
            return self::KEY_DIGER;
        }
        $prefix = $digerLabel . ' — ';
        if (strpos($stored, $prefix) === 0) {
            return self::KEY_DIGER;
        }
        if (preg_match('/^' . preg_quote($digerLabel, '/') . '\s*[:\u2014\-]/u', $stored)) {
            return self::KEY_DIGER;
        }

        return self::KEY_DIGER;
    }

    /**
     * DB'ye yazılacak değer: "1" … "8".
     */
    public static function compose(int $key): string {
        return (string) self::normalizeKey($key);
    }

    /**
     * Listelerde gösterim: kod ise etiket; değilse eski metin olduğu gibi.
     */
    public static function labelForStored(?string $stored): string {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return '';
        }
        if (preg_match('/^\d+$/', $stored)) {
            $k = (int) $stored;
            $labels = self::labels();
            return $labels[$k] ?? '';
        }
        return $stored;
    }

    /**
     * Yapılmama nedeni radyoları (name=yapilmama_neden, value=1..8).
     */
    public static function renderRadios(string $idPrefix, int $selectedKey): string {
        $idBase = preg_replace('/[^a-zA-Z0-9_-]/', '', $idPrefix);
        if ($idBase === '') {
            $idBase = 'yn';
        }

        $selectedKey = self::normalizeKey($selectedKey);

        $html = '<div class="vstack gap-2 yapilmama-neden-radios" role="radiogroup" aria-label="Yapılmama nedeni">';
        $idx = 0;
        foreach (self::labels() as $key => $label) {
            $rid = $idBase . '-yn-' . $idx++;
            $checked = ((int) $key === $selectedKey) ? ' checked' : '';
            $escId = htmlspecialchars($rid, ENT_QUOTES, 'UTF-8');
            $escVal = (string) (int) $key;
            $escLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $html .= '<div class="form-check m-0">';
            $html .= '<input class="form-check-input" type="radio" name="yapilmama_neden" id="' . $escId . '" value="' . htmlspecialchars($escVal, ENT_QUOTES, 'UTF-8') . '"' . $checked . '>';
            $html .= '<label class="form-check-label small" for="' . $escId . '">' . $escLabel . '</label>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}
