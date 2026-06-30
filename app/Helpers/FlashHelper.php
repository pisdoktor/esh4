<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Oturum flash mesajlarını güvenli şekilde JS/toastr çıktısına dönüştürür.
 */
final class FlashHelper
{
    private const JSON_HEX = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;

    public static function jsonForJs(string $message): string
    {
        return json_encode($message, self::JSON_HEX | JSON_UNESCAPED_UNICODE) ?: '""';
    }

    /**
     * toastr.* çağrılarını üretir (jQuery ready bloğu içinde kullanın).
     */
    public static function renderSessionToastrLines(): void
    {
        if (isset($_SESSION['success'])) {
            echo '                toastr.success(' . self::jsonForJs((string) $_SESSION['success']) . ');' . "\n";
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '                toastr.error(' . self::jsonForJs((string) $_SESSION['error']) . ');' . "\n";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['warning'])) {
            echo '                toastr.warning(' . self::jsonForJs((string) $_SESSION['warning']) . ');' . "\n";
            unset($_SESSION['warning']);
        }
    }

    public static function escapeForHtml(string $message): string
    {
        return htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    }
}
