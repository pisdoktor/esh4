<?php
declare(strict_types=1);

namespace App\Services\Esys;

final class HttpEsysApiClient implements EsysApiClientInterface
{
    private string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function pushBundle(array $payload): array
    {
        return $this->request('/push', $payload);
    }

    public function ping(): array
    {
        return $this->request('/health', null);
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{ok:bool,status?:int,error?:string,response?:mixed}
     */
    private function request(string $path, ?array $payload): array
    {
        $url = $this->baseUrl . $path;
        $opts = [
            'http' => [
                'method' => $payload === null ? 'GET' : 'POST',
                'timeout' => 10,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nContent-Type: application/json\r\n",
                'content' => $payload === null ? '' : (string) json_encode($payload, JSON_UNESCAPED_UNICODE),
            ],
        ];
        $ctx = stream_context_create($opts);
        $body = @file_get_contents($url, false, $ctx);
        $status = 0;
        $headers = $http_response_header ?? [];
        if (is_array($headers) && isset($headers[0]) && preg_match('/\s(\d{3})\s/', (string) $headers[0], $m)) {
            $status = (int) $m[1];
        }
        if (!is_string($body)) {
            return ['ok' => false, 'status' => $status, 'error' => 'HTTP isteği başarısız'];
        }
        $decoded = json_decode($body, true);

        return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'response' => $decoded ?? $body];
    }
}
