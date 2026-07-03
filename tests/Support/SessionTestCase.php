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

    protected function actAsStaff(int $userId = 42, int $kurumId = 1): void
    {
        $_SESSION['user_id'] = $userId;
        AuthHelper::syncSessionFromLevel(AuthHelper::ROLE_STAFF);
        TenantContext::syncSessionFromUser($kurumId, AuthHelper::ROLE_STAFF);
    }

    protected function actAsAdmin(int $userId = 7, int $kurumId = 1): void
    {
        $_SESSION['user_id'] = $userId;
        AuthHelper::syncSessionFromLevel(AuthHelper::ROLE_ADMIN);
        TenantContext::syncSessionFromUser($kurumId, AuthHelper::ROLE_ADMIN);
    }

    protected function actAsSuperAdmin(int $userId = 1, ?int $kurumFilter = null, ?int $assignedBolgeId = null): void
    {
        $_SESSION['user_id'] = $userId;
        AuthHelper::syncSessionFromLevel(AuthHelper::ROLE_SUPERADMIN);
        TenantContext::syncSessionFromUser(null, AuthHelper::ROLE_SUPERADMIN, $assignedBolgeId);
        if ($kurumFilter !== null && $kurumFilter > 0) {
            TenantContext::setSessionKurumFilter($kurumFilter);
        }
    }

    protected function actAsPlatformOwner(int $userId = 1, ?int $kurumFilter = null): void
    {
        $_SESSION['user_id'] = $userId;
        AuthHelper::syncSessionFromLevel(AuthHelper::ROLE_PLATFORM_OWNER);
        TenantContext::syncSessionFromUser(null, AuthHelper::ROLE_PLATFORM_OWNER);
        if ($kurumFilter !== null && $kurumFilter > 0) {
            TenantContext::setSessionKurumFilter($kurumFilter);
        }
    }

    /**
     * @return object{id: int, kurum_id: int, pasif: string}
     */
    protected function patientRow(int $id, int $kurumId, string $pasif = '0'): object
    {
        return (object) [
            'id' => $id,
            'kurum_id' => $kurumId,
            'pasif' => $pasif,
        ];
    }
}
