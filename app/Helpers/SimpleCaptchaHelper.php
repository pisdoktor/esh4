<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Oturum tabanlı basit matematik captcha (toplama / çıkarma).
 */
final class SimpleCaptchaHelper
{
    private const SESSION_ANSWER_HASH_PREFIX = 'esh_simple_captcha_h_';
    private const SESSION_FLASH_PREFIX = 'esh_captcha_flash_';

    public const INPUT_FIELD = 'captcha_answer';

    /**
     * @return array{question: string}
     */
    public static function issue(string $namespace): array
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $useSubtraction = random_int(0, 1) === 1;

        if ($useSubtraction && $b > $a) {
            [$a, $b] = [$b, $a];
        }

        if ($useSubtraction) {
            $answer = $a - $b;
            $question = $a . ' − ' . $b;
        } else {
            $answer = $a + $b;
            $question = $a . ' + ' . $b;
        }

        $_SESSION[self::answerHashKey($namespace)] = self::hashAnswer($namespace, $answer);

        return ['question' => $question];
    }

    public static function validate(mixed $submitted, string $namespace): bool
    {
        $hashKey = self::answerHashKey($namespace);
        $storedHash = $_SESSION[$hashKey] ?? null;
        self::clear($namespace);

        if (!is_string($storedHash) || $storedHash === '') {
            return false;
        }

        $raw = is_string($submitted) ? trim($submitted) : '';
        if ($raw === '' || !preg_match('/^\d{1,2}$/', $raw)) {
            return false;
        }

        $answer = (int) $raw;
        $expectedHash = self::hashAnswer($namespace, $answer);

        return hash_equals($storedHash, $expectedHash);
    }

    public static function setFlashError(string $namespace, string $message): void
    {
        $_SESSION[self::flashKey($namespace)] = $message;
    }

    public static function consumeFlashError(string $namespace): ?string
    {
        $key = self::flashKey($namespace);
        if (!isset($_SESSION[$key]) || !is_string($_SESSION[$key]) || $_SESSION[$key] === '') {
            return null;
        }

        $message = $_SESSION[$key];
        unset($_SESSION[$key]);

        return $message;
    }

    private static function clear(string $namespace): void
    {
        unset($_SESSION[self::answerHashKey($namespace)]);
    }

    private static function answerHashKey(string $namespace): string
    {
        return self::SESSION_ANSWER_HASH_PREFIX . self::sanitizeNamespace($namespace);
    }

    private static function flashKey(string $namespace): string
    {
        return self::SESSION_FLASH_PREFIX . self::sanitizeNamespace($namespace);
    }

    private static function sanitizeNamespace(string $namespace): string
    {
        $safe = preg_replace('/[^a-z0-9_-]/', '', strtolower($namespace));

        return $safe !== '' ? $safe : 'default';
    }

    private static function hashAnswer(string $namespace, int $answer): string
    {
        $sessionId = session_id();

        return hash_hmac('sha256', (string) $answer, $namespace . '|' . $sessionId);
    }
}
