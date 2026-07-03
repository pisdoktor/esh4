<?php
declare(strict_types=1);

namespace Tests\Smoke;

use App\Helpers\AuditLogHelper;
use App\Helpers\EsysComplianceHelper;
use PHPUnit\Framework\TestCase;

final class ComplianceHelpersTest extends TestCase
{
    public function test_audit_action_options_not_empty(): void
    {
        self::assertNotEmpty(AuditLogHelper::actionOptions());
        self::assertArrayHasKey(AuditLogHelper::ACTION_PATIENT_VIEW, AuditLogHelper::actionOptions());
    }

    public function test_esys_normalize_ref_trims_and_limits(): void
    {
        self::assertNull(EsysComplianceHelper::normalizeRef(''));
        self::assertSame('ABC-123', EsysComplianceHelper::normalizeRef('  ABC-123  '));
        self::assertSame(64, strlen(EsysComplianceHelper::normalizeRef(str_repeat('x', 80)) ?? ''));
    }

    public function test_esys_pick_patient_refs(): void
    {
        $picked = EsysComplianceHelper::pickPatientRefs([
            'esys_hasta_ref' => ' H1 ',
            'esys_basvuru_ref' => '',
            'isim' => 'ignored',
        ]);
        self::assertSame('H1', $picked['esys_hasta_ref']);
        self::assertNull($picked['esys_basvuru_ref']);
        self::assertArrayNotHasKey('isim', $picked);
    }

    public function test_esys_mapping_json_loads(): void
    {
        $mapping = EsysComplianceHelper::mapping();
        self::assertArrayHasKey('entities', $mapping);
        self::assertArrayHasKey('patient', $mapping['entities']);
    }
}
