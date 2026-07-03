<?php

declare(strict_types=1);

namespace App\Helpers;

/** Hasta izlem / planlı izlem listelerinde kayıt kurumu gösterimi. */
final class IzlemKurumDisplayHelper
{
    public static function otherKurumHtml(int $recordKurumId, int $viewerKurumId, ?string $kurumAd = null): string
    {
        if ($recordKurumId <= 0 || $viewerKurumId <= 0 || $recordKurumId === $viewerKurumId) {
            return '';
        }

        $label = trim((string) ($kurumAd ?? ''));
        if ($label === '') {
            $label = 'Kurum #' . $recordKurumId;
        }

        return '<div class="small text-muted mt-1">'
            . '<i class="fa-solid fa-building me-1" aria-hidden="true"></i>'
            . '<span class="badge bg-secondary-subtle text-secondary border fw-normal">'
            . htmlspecialchars('Diğer kurum: ' . $label, ENT_QUOTES, 'UTF-8')
            . '</span></div>';
    }
}
