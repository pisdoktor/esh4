<?php

namespace App\Helpers;

/**
 * Hasta ilaç/tanı raporu satır durumu (HastaIlacRapor listesi ile uyumlu).
 */
final class HastaIlacRaporStatusHelper
{
    /** @var 'none'|'raporlu'|'expired'|'expiring'|'bitis_only' */
    public const STATUS_NONE = 'none';
    public const STATUS_RAPORLU = 'raporlu';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_EXPIRING = 'expiring';
    public const STATUS_BITIS_ONLY = 'bitis_only';

    /**
     * @return array{
     *   raporLu: bool,
     *   raporFlag: bool,
     *   status: string,
     *   expired: bool,
     *   expiring: bool
     * }
     */
    public static function evaluateRow(object $r, ?\DateTimeImmutable $today = null): array
    {
        $today = $today ?? new \DateTimeImmutable('today');
        $plus30 = $today->modify('+30 days');
        $raporFlag = (int) ($r->rapor ?? 0) === 1;
        $bitisRaw = trim((string) ($r->bitistarihi ?? ''));
        $hasBitis = $bitisRaw !== '' && substr($bitisRaw, 0, 4) !== '0000';

        $raporLu = $raporFlag;
        if (!$raporLu && $hasBitis) {
            $raporLu = true;
        }

        $status = self::STATUS_NONE;
        $expired = false;
        $expiring = false;

        if ($raporFlag) {
            $status = self::STATUS_RAPORLU;
            if ($hasBitis) {
                $dt = \DateTimeImmutable::createFromFormat('Y-m-d', substr($bitisRaw, 0, 10));
                if ($dt instanceof \DateTimeImmutable) {
                    if ($dt < $today) {
                        $expired = true;
                        $status = self::STATUS_EXPIRED;
                    } elseif ($dt <= $plus30) {
                        $expiring = true;
                        $status = self::STATUS_EXPIRING;
                    }
                }
            }
        } elseif ($raporLu) {
            $status = self::STATUS_BITIS_ONLY;
        }

        return [
            'raporLu' => $raporLu,
            'raporFlag' => $raporFlag,
            'status' => $status,
            'expired' => $expired,
            'expiring' => $expiring,
        ];
    }

    /**
     * @return array{badge: string, liClass: string, badgeLabel: string}
     */
    public static function ozetRowPresentation(object $r): array
    {
        $eval = self::evaluateRow($r);
        if ($eval['status'] === self::STATUS_EXPIRED) {
            return [
                'badge' => 'bg-danger-subtle text-danger border border-danger-subtle',
                'liClass' => 'esh-ilac-rapor-ozet-row--expired',
                'badgeLabel' => 'Süresi doldu',
            ];
        }
        if ($eval['status'] === self::STATUS_EXPIRING) {
            return [
                'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                'liClass' => 'esh-ilac-rapor-ozet-row--expiring',
                'badgeLabel' => '30 gün içinde bitiyor',
            ];
        }

        return [
            'badge' => 'bg-success-subtle text-success border border-success-subtle',
            'liClass' => '',
            'badgeLabel' => 'R',
        ];
    }
}
