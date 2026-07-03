<?php
declare(strict_types=1);

use App\Helpers\ClinicalDecisionSupportHelper;
use PHPUnit\Framework\TestCase;

final class ClinicalDecisionSupportHelperTest extends TestCase
{
    public function testBradenHighRiskAlert(): void
    {
        $hasta = (object) ['id' => 1, 'tckimlik' => '12345678901', 'basiyarasi' => 1, 'dogumtarihi' => '1980-01-01'];
        $assessments = [
            'braden' => (object) ['toplam_skor' => 10],
            'itaki' => null,
            'harizmi' => null,
            'mna' => null,
            'barthel' => null,
        ];
        $alerts = ClinicalDecisionSupportHelper::evaluateAlerts($hasta, $assessments, 5);
        $codes = array_column($alerts, 'code');
        self::assertContains('braden_high', $codes);
    }

    public function testOverdueHighRiskCombinedAlert(): void
    {
        $hasta = (object) ['id' => 2, 'tckimlik' => '12345678902', 'basiyarasi' => 1, 'dogumtarihi' => '1980-01-01'];
        $assessments = [
            'braden' => (object) ['toplam_skor' => 8],
            'itaki' => null,
            'harizmi' => null,
            'mna' => null,
            'barthel' => null,
        ];
        $alerts = ClinicalDecisionSupportHelper::evaluateAlerts($hasta, $assessments, 45);
        self::assertContains('visit_overdue_high_risk', array_column($alerts, 'code'));
    }

    public function testItakiHighRiskAlert(): void
    {
        $hasta = (object) ['id' => 3, 'tckimlik' => '12345678903', 'basiyarasi' => 0, 'dogumtarihi' => '1970-05-05'];
        $assessments = [
            'braden' => null,
            'itaki' => (object) ['toplam_skor' => 12],
            'harizmi' => null,
            'mna' => null,
            'barthel' => null,
        ];
        $alerts = ClinicalDecisionSupportHelper::evaluateAlerts($hasta, $assessments, 2);
        self::assertContains('itaki_high', array_column($alerts, 'code'));
    }

    public function testMnaMalnutritionAlert(): void
    {
        $hasta = (object) ['id' => 4, 'tckimlik' => '12345678904', 'basiyarasi' => 0, 'dogumtarihi' => '1965-03-03'];
        $assessments = [
            'braden' => null,
            'itaki' => null,
            'harizmi' => null,
            'mna' => (object) ['toplam_skor' => 6],
            'barthel' => null,
        ];
        $alerts = ClinicalDecisionSupportHelper::evaluateAlerts($hasta, $assessments, 1);
        self::assertContains('mna_malnutrition', array_column($alerts, 'code'));
    }

    public function testIsHighRiskFromAssessments(): void
    {
        $assessments = [
            'braden' => (object) ['toplam_skor' => 14],
            'itaki' => (object) ['toplam_skor' => 11],
            'harizmi' => null,
            'mna' => (object) ['toplam_skor' => 12],
            'barthel' => null,
        ];
        self::assertTrue(ClinicalDecisionSupportHelper::isHighRiskFromAssessments($assessments));
    }

    public function testBarthelSevereAlert(): void
    {
        $hasta = (object) ['id' => 5, 'tckimlik' => '12345678905', 'basiyarasi' => 0, 'dogumtarihi' => '1950-01-01'];
        $assessments = [
            'braden' => null,
            'itaki' => null,
            'harizmi' => null,
            'mna' => null,
            'barthel' => (object) ['toplam_skor' => 15],
        ];
        $alerts = ClinicalDecisionSupportHelper::evaluateAlerts($hasta, $assessments, 3);
        self::assertContains('barthel_severe', array_column($alerts, 'code'));
    }

    public function testBarthelDependencyAlert(): void
    {
        $hasta = (object) ['id' => 6, 'tckimlik' => '12345678906', 'basiyarasi' => 0, 'dogumtarihi' => '1950-01-01'];
        $assessments = [
            'braden' => null,
            'itaki' => null,
            'harizmi' => null,
            'mna' => null,
            'barthel' => (object) ['toplam_skor' => 45],
        ];
        $alerts = ClinicalDecisionSupportHelper::evaluateAlerts($hasta, $assessments, 3);
        self::assertContains('barthel_dependency', array_column($alerts, 'code'));
    }

    public function testIsHighRiskFromBarthelScore(): void
    {
        $assessments = [
            'braden' => (object) ['toplam_skor' => 16],
            'itaki' => null,
            'harizmi' => null,
            'mna' => (object) ['toplam_skor' => 12],
            'barthel' => (object) ['toplam_skor' => 55],
        ];
        self::assertTrue(ClinicalDecisionSupportHelper::isHighRiskFromAssessments($assessments));
    }
}
