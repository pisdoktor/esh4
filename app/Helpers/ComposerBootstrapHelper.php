<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * İsteğe bağlı Composer autoload — vendor/ yoksa sessizce atlanır.
 */
final class ComposerBootstrapHelper
{
    /** @var bool|null */
    private static $loaded = null;

    public static function loadIfPresent(): bool
    {
        if (self::$loaded !== null) {
            return self::$loaded;
        }
        if (!defined('ROOT_PATH')) {
            self::$loaded = false;

            return false;
        }
        $autoload = ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (!is_readable($autoload)) {
            self::$loaded = false;

            return false;
        }
        require_once $autoload;
        self::$loaded = true;

        return true;
    }

    public static function isLoaded(): bool
    {
        return self::$loaded === true;
    }
}
