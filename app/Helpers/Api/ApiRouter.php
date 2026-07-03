<?php
declare(strict_types=1);

namespace App\Helpers\Api;

use App\Services\Api\V1\PatientResource;
use App\Services\Api\V1\PlanResource;
use App\Services\Api\V1\VisitResource;

/**
 * REST /api/v1/* yönlendirme.
 */
final class ApiRouter
{
    public static function dispatch(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $parsed = self::parseRoute();
        if ($parsed === null) {
            ApiResponse::error('Geçersiz API yolu. Örnek: /public/api/v1/patients', 404);
        }

        $tokenRow = ApiAuth::requireAuth();

        if ($method === 'POST' && ($parsed['sub'] ?? '') === 'checkin' && $parsed['resource'] === 'visits') {
            ApiAuth::requireWriteScope($tokenRow, 'visits');
            self::handleVisitCheckin();
            return;
        }

        if ($method === 'PATCH' && $parsed['resource'] === 'visits' && ($parsed['id'] ?? 0) > 0) {
            ApiAuth::requireWriteScope($tokenRow, 'visits');
            self::handleVisitPatch((int) $parsed['id']);
            return;
        }

        if ($method !== 'GET') {
            ApiResponse::error('Desteklenmeyen HTTP metodu.', 405);
        }

        ApiAuth::requireScope($tokenRow, $parsed['resource']);

        $page = ApiAuth::pagination();

        switch ($parsed['resource']) {
            case 'patients':
                self::handlePatients($parsed['id'], $page);
                break;
            case 'visits':
                self::handleVisits($parsed['id'], $page);
                break;
            case 'plans':
                self::handlePlans($parsed['id'], $page);
                break;
            default:
                ApiResponse::error('Bilinmeyen kaynak.', 404);
        }
    }

    /**
     * @return array{resource: string, id: ?int, sub: ?string}|null
     */
    private static function parseRoute(): ?array
    {
        $resource = trim((string) ($_GET['resource'] ?? ''));
        $id = isset($_GET['id']) && $_GET['id'] !== '' ? (int) $_GET['id'] : null;
        $sub = trim((string) ($_GET['sub'] ?? ''));

        if ($resource !== '') {
            return [
                'resource' => self::normalizeResource($resource),
                'id' => $id !== null && $id > 0 ? $id : null,
                'sub' => $sub !== '' ? $sub : null,
            ];
        }

        $uri = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $uri = str_replace('\\', '/', $uri);
        if (preg_match('#/api(?:/index\.php)?/v1/visits/checkin/?$#i', $uri)) {
            return ['resource' => 'visits', 'id' => null, 'sub' => 'checkin'];
        }
        if (preg_match('#/api(?:/index\.php)?/v1/(patients|visits|plans)(?:/(\d+))?/?$#i', $uri, $m)) {
            return [
                'resource' => strtolower($m[1]),
                'id' => isset($m[2]) && $m[2] !== '' ? (int) $m[2] : null,
                'sub' => null,
            ];
        }

        return null;
    }

    private static function normalizeResource(string $resource): string
    {
        $map = [
            'patient' => 'patients',
            'visit' => 'visits',
            'plan' => 'plans',
            'planned_visit' => 'plans',
        ];
        $r = strtolower(trim($resource));

        return $map[$r] ?? $r;
    }

    /**
     * @param array{page: int, per_page: int, offset: int} $page
     */
    private static function handlePatients(?int $id, array $page): void
    {
        $svc = new PatientResource();
        if ($id !== null) {
            $row = $svc->show($id);
            if ($row === null) {
                ApiResponse::error('Hasta bulunamadı veya erişim yok.', 404);
            }
            ApiResponse::ok($svc->serialize($row));
        }

        $status = trim((string) ($_GET['status'] ?? 'active'));
        $search = trim((string) ($_GET['search'] ?? ''));
        $result = $svc->list($page['per_page'], $page['offset'], $status, $search);
        $data = array_map(static fn ($row) => $svc->serialize($row), $result['items']);
        ApiResponse::ok($data, [
            'page' => $page['page'],
            'per_page' => $page['per_page'],
            'total' => $result['total'],
        ]);
    }

    /**
     * @param array{page: int, per_page: int, offset: int} $page
     */
    private static function handleVisits(?int $id, array $page): void
    {
        $svc = new VisitResource();
        if ($id !== null) {
            $row = $svc->show($id);
            if ($row === null) {
                ApiResponse::error('İzlem bulunamadı veya erişim yok.', 404);
            }
            ApiResponse::ok($svc->serialize($row));
        }

        $search = trim((string) ($_GET['search'] ?? ''));
        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        $dateTo = trim((string) ($_GET['date_to'] ?? ''));
        $result = $svc->list($page['per_page'], $page['offset'], $search, $dateFrom, $dateTo);
        $data = array_map(static fn ($row) => $svc->serialize($row), $result['items']);
        ApiResponse::ok($data, [
            'page' => $page['page'],
            'per_page' => $page['per_page'],
            'total' => $result['total'],
        ]);
    }

    /**
     * @param array{page: int, per_page: int, offset: int} $page
     */
    private static function handlePlans(?int $id, array $page): void
    {
        $svc = new PlanResource();
        if ($id !== null) {
            $row = $svc->show($id);
            if ($row === null) {
                ApiResponse::error('Planlı izlem bulunamadı veya erişim yok.', 404);
            }
            ApiResponse::ok($svc->serialize($row));
        }

        $search = trim((string) ($_GET['search'] ?? ''));
        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        $dateTo = trim((string) ($_GET['date_to'] ?? ''));
        $durumRaw = trim((string) ($_GET['durum'] ?? ''));
        $durum = $durumRaw !== '' && ctype_digit($durumRaw) ? (int) $durumRaw : -1;
        $result = $svc->list($page['per_page'], $page['offset'], $search, $durum, $dateFrom, $dateTo);
        $data = array_map(static fn ($row) => $svc->serialize($row), $result['items']);
        ApiResponse::ok($data, [
            'page' => $page['page'],
            'per_page' => $page['per_page'],
            'total' => $result['total'],
        ]);
    }

    private static function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw !== false ? $raw : '', true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function handleVisitCheckin(): void
    {
        $body = self::readJsonBody();
        $visitId = (int) ($body['visit_id'] ?? $body['id'] ?? 0);
        if ($visitId < 1) {
            ApiResponse::error('visit_id gerekli.', 422);
        }
        $svc = new VisitResource();
        $result = $svc->applyCheckin($visitId, $body);
        if (!($result['ok'] ?? false)) {
            ApiResponse::error((string) ($result['error'] ?? 'Check-in başarısız.'), (int) ($result['status'] ?? 422));
        }
        ApiResponse::ok($result['data'] ?? []);
    }

    private static function handleVisitPatch(int $id): void
    {
        $body = self::readJsonBody();
        $svc = new VisitResource();
        $result = $svc->patch($id, $body);
        if (!($result['ok'] ?? false)) {
            ApiResponse::error((string) ($result['error'] ?? 'Güncelleme başarısız.'), (int) ($result['status'] ?? 422));
        }
        ApiResponse::ok($result['data'] ?? []);
    }
}
