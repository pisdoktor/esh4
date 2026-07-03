<?php
declare(strict_types=1);

namespace App\Services\Esys;

final class StubEsysApiClient implements EsysApiClientInterface
{
    public function pushBundle(array $payload): array
    {
        $dir = ROOT_PATH . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/esys_stub_push.log';
        @file_put_contents($file, date('c') . ' ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);

        return ['ok' => true, 'status' => 200, 'response' => ['stub' => true]];
    }

    public function ping(): array
    {
        return ['ok' => true, 'status' => 200];
    }
}
