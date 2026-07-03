<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * @deprecated Birleşik menü AdminNavHelper üzerinden sunulur; geriye uyumluluk için delegasyon.
 */
class SuperadminNavHelper
{
    /**
     * @return array<int, array{title: string, icon: string, accent: string, items: list<array<string, mixed>>}>
     */
    public static function menuGroups(string $currentController, string $currentAction): array
    {
        return AdminNavHelper::menuGroups($currentController, $currentAction);
    }

    public static function renderMegaMenu(string $currentController, string $currentAction): void
    {
        AdminNavHelper::renderOffcanvas($currentController, $currentAction);
    }

    public static function renderOffcanvas(string $currentController, string $currentAction): void
    {
        AdminNavHelper::renderOffcanvas($currentController, $currentAction);
    }
}
