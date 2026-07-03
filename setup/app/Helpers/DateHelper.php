<?php
namespace App\Helpers;

use DateTime;

class DateHelper {

    /**
     * Doğum tarihinden yaş hesaplar
     * @param string $birthday (Y-m-d formatında)
     * @return int|string
     */
    public static function calculateAge($birthday) {
        if (!$birthday || $birthday == '0000-00-00') return '-';

        $birthDate = new DateTime($birthday);
        $today = new DateTime('today');
        
        // diff metodu iki tarih arasındaki farkı bir nesne olarak döner
        return $birthDate->diff($today)->y;
    }

    /**
     * Veritabanı formatını (Y-m-d) kısa görüntü formatına (d-m-Y, tire) çevirir.
     */
    public static function toTr($date) {
        if (!$date || $date == '0000-00-00') return '-';
        return date('d-m-Y', strtotime($date));
    }

    /**
     * Y-m-d → d-m-Y + Türkçe hafta günü (örn. 16-05-2026 Cumartesi).
     */
    public static function toTrWithWeekday($date): string {
        if (!$date || $date === '0000-00-00') {
            return '-';
        }
        $s = (string) $date;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return self::toTr($date);
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $s);
        if (!$dt instanceof \DateTimeImmutable) {
            return self::toTr($date);
        }
        $n = (int) $dt->format('N');
        $names = [
            1 => 'Pazartesi',
            2 => 'Salı',
            3 => 'Çarşamba',
            4 => 'Perşembe',
            5 => 'Cuma',
            6 => 'Cumartesi',
            7 => 'Pazar',
        ];
        $dayName = $names[$n] ?? '';

        return $dt->format('d-m-Y') . ($dayName !== '' ? ' ' . $dayName : '');
    }

    /**
     * Form input değeri: boş veya geçersiz tarihte boş string (toTr burada '-' dönmez).
     */
    public static function toTrOrEmpty($date): string {
        if (!$date || $date === '0000-00-00') {
            return '';
        }
        $ts = strtotime((string) $date);
        if ($ts === false) {
            return '';
        }
        return date('d-m-Y', $ts);
    }

    /**
     * Y-m-d → yıl ve ay numarası (liste/istatistik ekranları).
     *
     * @return array{year:int,month:int}|null
     */
    public static function yearMonth(?string $date): ?array {
        if (!$date || $date === '0000-00-00') {
            return null;
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return null;
        }

        return ['year' => (int) date('Y', $ts), 'month' => (int) date('n', $ts)];
    }

    /**
     * Bugünün tarihi datepicker ile uyumlu (GG-AA-YYYY, tire).
     */
    public static function todayTr(): string {
        return date('d-m-Y');
    }

    /**
     * Görüntü formatı: d.m.Y (noktalı; liste/tablo ekranları).
     */
    public static function toTrDotOrEmpty($date): string {
        $s = self::toTrOrEmpty($date);

        return $s === '' ? '' : str_replace('-', '.', $s);
    }

    /**
     * Görüntü formatı: d.m.Y H:i (datetime alanları).
     */
    public static function toTrDotDateTimeOrEmpty($date): string {
        if (!$date || $date === '0000-00-00') {
            return '';
        }
        $ts = strtotime((string) $date);
        if ($ts === false) {
            return '';
        }

        return date('d.m.Y H:i', $ts);
    }

    /** Unix zaman damgası → d.m.Y H:i; geçersizde $emptyPlaceholder (varsayılan —). */
    public static function unixToTrDotDateTimeOrEmpty(?int $ts, string $emptyPlaceholder = '—'): string {
        if ($ts === null || $ts <= 0) {
            return $emptyPlaceholder;
        }

        return date('d.m.Y H:i', $ts);
    }

    /** Şu an: d.m.Y H:i (PDF / rapor üretim zamanı). */
    public static function nowTrDateTime(): string {
        return date('d.m.Y H:i');
    }

    /**
     * Datepicker çıktısı (d-m-Y veya eski d.m.Y) → MySQL tarih (Y-m-d).
     */
    public static function trDateToYmd(string $dateTr): ?string {
        $dateTr = trim($dateTr);
        if ($dateTr === '') {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateTr, $m)) {
            $y = (int) $m[1];
            $mo = (int) $m[2];
            $d = (int) $m[3];
            if (!checkdate($mo, $d, $y)) {
                return null;
            }
            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $dateTr, $m)
            || preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $dateTr, $m)) {
            $d = (int) $m[1];
            $mo = (int) $m[2];
            $y = (int) $m[3];
            if (!checkdate($mo, $d, $y)) {
                return null;
            }

            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }

        return null;
    }

    /** Y-m-d bugünden sonra mı (eşit değil). */
    public static function isYmdAfterToday(string $ymd): bool {
        $ymd = trim($ymd);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return false;
        }

        return strcmp($ymd, date('Y-m-d')) > 0;
    }

    /**
     * Datepicker çıktısı (d-m-Y veya eski d.m.Y) + saat (H:i veya HH:i) → MySQL datetime (Y-m-d H:i:s).
     */
    public static function trDateAndTimeToSql(string $dateTr, string $timeHm = ''): ?string {
        $dateTr = trim($dateTr);
        if ($dateTr === '') {
            return null;
        }
        $ymd = self::trDateToYmd($dateTr);
        if ($ymd === null) {
            return null;
        }
        $m = [];
        preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $m);
        $y = (int) $m[1];
        $mo = (int) $m[2];
        $d = (int) $m[3];

        $timeHm = trim($timeHm);
        if ($timeHm === '') {
            $timeHm = '09:00';
        }
        $h = 9;
        $mi = 0;
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $timeHm, $tm)) {
            $h = (int) $tm[1];
            $mi = (int) $tm[2];
        }
        $h = max(0, min(23, $h));
        $mi = max(0, min(59, $mi));

        return sprintf('%04d-%02d-%02d %02d:%02d:00', $y, $mo, $d, $h, $mi);
    }

    /**
     * Liste filtresi: GET (Y-m-d veya datepicker d-m-y / d.m.y) → Y-m-d; boş veya geçersizse $fallbackYmd.
     */
    public static function parseFilterDate(string $raw, string $fallbackYmd): string {
        $raw = trim($raw);
        if ($raw === '') {
            return $fallbackYmd;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            $parts = explode('-', $raw);
            $y = (int) $parts[0];
            $m = (int) $parts[1];
            $d = (int) $parts[2];
            if (checkdate($m, $d, $y)) {
                return sprintf('%04d-%02d-%02d', $y, $m, $d);
            }

            return $fallbackYmd;
        }
        $ymd = self::trDateToYmd($raw);
        if ($ymd !== null) {
            return $ymd;
        }
        $norm = str_replace(['.', '/'], '-', $raw);
        $ts = strtotime($norm);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return $fallbackYmd;
    }

    /**
     * Bekleyen hasta (unified waiting): kayıttan bu yana geçen süreye göre satır vurgusu.
     * ≥1 ay, ≥3 ay kademeli; 6 ay ve 12 ay+ kırmızıya yaklaşır.
     */
    public static function waitingKayitRowClass($kayitRaw): string
    {
        $raw = trim((string) ($kayitRaw ?? ''));
        if ($raw === '' || $raw === '0000-00-00') {
            return '';
        }
        $ymd = self::trDateToYmd($raw);
        if ($ymd === null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            $ymd = $raw;
        }
        if ($ymd === null) {
            $ts = strtotime(str_replace(['.', '/'], '-', $raw));
            if ($ts === false) {
                return '';
            }
            $ymd = date('Y-m-d', $ts);
        }
        try {
            $kayit = new \DateTimeImmutable($ymd);
            $today = new \DateTimeImmutable('today');
            if ($kayit > $today) {
                return '';
            }
            $days = (int) $kayit->diff($today)->days;
        } catch (\Throwable $e) {
            return '';
        }
        if ($days < 30) {
            return '';
        }
        if ($days < 90) {
            return 'esh-waiting-kayit--ge-1ay';
        }
        if ($days < 180) {
            return 'esh-waiting-kayit--ge-3ay';
        }
        if ($days < 365) {
            return 'esh-waiting-kayit--ge-6ay';
        }

        return 'esh-waiting-kayit--ge-12ay';
    }

    /**
     * GET date_from / date_to filtreleri → Y-m-d aralığı.
     *
     * @return array{0: string, 1: string, 2: string, 3: string, 4: bool} fromYmd, toYmd, fromTr, toTr, filterExpanded
     */
    public static function resolveFilterDateRange(array $get, string $defaultFromExpr = 'first day of this month'): array {
        $dateFromInput = isset($get['date_from']) ? trim((string) $get['date_from']) : '';
        $dateToInput = isset($get['date_to']) ? trim((string) $get['date_to']) : '';
        $defaultFrom = (new \DateTimeImmutable($defaultFromExpr))->format('Y-m-d');
        $defaultTo = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $from = self::trDateToYmd($dateFromInput) ?: $defaultFrom;
        $to = self::trDateToYmd($dateToInput) ?: $defaultTo;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            $from,
            $to,
            self::toTrOrEmpty($from),
            self::toTrOrEmpty($to),
            $dateFromInput !== '' || $dateToInput !== '',
        ];
    }
}