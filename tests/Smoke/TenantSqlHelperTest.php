<?php
declare(strict_types=1);

namespace Tests\Smoke;

use App\Helpers\TenantSqlHelper;
use Tests\Support\SessionTestCase;

final class TenantSqlHelperTest extends SessionTestCase
{
    public function test_and_equals_staff_adds_filter(): void
    {
        $this->actAsStaff(1, 3);
        self::assertSame(' AND h.kurum_id = 3', TenantSqlHelper::andEquals('h'));
    }

    public function test_and_equals_superadmin_no_filter(): void
    {
        $this->actAsSuperAdmin();
        self::assertSame('', TenantSqlHelper::andEquals('h'));
    }

    public function test_where_only_staff(): void
    {
        $this->actAsAdmin(1, 2);
        self::assertSame(' WHERE `kurum_id` = 2', TenantSqlHelper::whereOnly());
    }

    public function test_izlem_scope_sql_staff(): void
    {
        $this->actAsStaff(1, 1);
        self::assertStringContainsString('kurum_id = h.kurum_id', TenantSqlHelper::izMatchesPatientKurumSql('v'));
    }

    public function test_izlem_scope_sql_superadmin_empty(): void
    {
        $this->actAsSuperAdmin();
        self::assertSame('', TenantSqlHelper::izMatchesPatientKurumSql('v'));
    }

    public function test_merge_parts_adds_kurum(): void
    {
        $this->actAsStaff(1, 6);
        $parts = ['1=1'];
        TenantSqlHelper::mergeParts($parts, 'p');
        self::assertContains('p.kurum_id = 6', $parts);
    }
}
