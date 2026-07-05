<?php
declare(strict_types=1);

namespace Tests\Support;

use App\Helpers\AuthHelper;
use App\Helpers\TenantContext;
use PHPUnit\Framework\TestCase;

/**
 * Oturum simülasyonu — tenant / erişim smoke testleri için.
 */
abstract class SessionTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    /** Legacy int test id'lerini deterministik UUID'ye çevirir. */
    protected function normalizeTestUserId(int|string $userId): string
    {
        if (is_string($userId)) {
            return $userId;
        }

        return sprintf('11111111-1111-4111-8111-%012d', max(1, min(999999999999, $userId)));
    }

    protected function actAsStaff(int|string $userId = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', int $kurumId = 1): void
    {
        $_SESSION['user_id'] = $this->normalizeTestUserId($userId);
        AuthHelper::syncSessionFromLevel(AuthHelper::ROLE_STAFF);
        TenantContext::syncSessionFromUser($kurumId, AuthHelper::ROLE_STAFF);
    }

    protected function actAsAdmin(int|string $userId = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', int $kurumId = 1): void
    {
        $_SESSION['user_id'] = $this->normalizeTestUserId($userId);
        AuthHelper::syncSessionFromLevel(AuthHelper::ROLE_ADMIN);
        TenantContext::syncSessionFromUser($kurumId, AuthHelper::ROLE_ADMIN);
    }

    protected function actAsSuperAdmin(int|string $userId = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', ?int $kurumFilter = null, ?int $assignedBolgeId = null): void
    {
        $_SESSION['user_id'] = $this->normalizeTestUserId($userId);
        AuthHelper::syncSessionFromLevel(AuthHelper::ROLE_SUPERADMIN);
        TenantContext::syncSessionFromUser(null, AuthHelper::ROLE_SUPERADMIN, $assignedBolgeId);
        if ($kurumFilter !== null && $kurumFilter > 0) {
            TenantContext::setSessionKurumFilter($kurumFilter);
        }
    }

    protected function actAsPlatformOwner(int|string $userId = 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', ?int $kurumFilter = null): void
    {
        $_SESSION['user_id'] = $this->normalizeTestUserId($userId);
        AuthHelper::syncSessionFromLevel(AuthHelper::ROLE_PLATFORM_OWNER);
        TenantContext::syncSessionFromUser(null, AuthHelper::ROLE_PLATFORM_OWNER);
        if ($kurumFilter !== null && $kurumFilter > 0) {
            TenantContext::setSessionKurumFilter($kurumFilter);
        }
    }

    /**
     * @return object{id: string, kurum_id: int, pasif: string}
     */
    protected function patientRow(string $id, int $kurumId, string $pasif = '0'): object
    {
        return (object) [
            'id' => $id,
            'kurum_id' => $kurumId,
            'pasif' => $pasif,
        ];
    }
}
