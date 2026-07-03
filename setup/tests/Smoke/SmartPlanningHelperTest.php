<?php
declare(strict_types=1);

use App\Helpers\SmartPlanningHelper;
use PHPUnit\Framework\TestCase;

final class SmartPlanningHelperTest extends TestCase
{
  /** @return array<string, int|float> */
    private function baseCfg(): array
    {
        return [
            'oncelik_yuksek_bonusu' => 75,
            'mahalle_bonusu' => 40,
            'bolge_bonusu' => 50,
            'is_yuku_cezasi' => 10,
            'personel_dosya_sayisi' => 10,
            'izolasyon_oncelik_bonusu' => 60,
            'izolasyon_karisim_cezasi' => 120,
            'yetkinlik_eslesme_bonusu' => 30,
            'varsayilan_arac_kapasitesi' => 4,
            'travel_time_weight' => 1,
        ];
    }

    public function testIsolationPatientGetsPriorityBonus(): void
    {
        $ekip = ['personel' => 2, 'hastalar' => [], 'son_mahalle' => -1, 'son_bolge' => -1, 'unvans' => []];
        $normal = (object) ['izolasyon' => 0, 'mahalle_id' => 1, 'bolge_id' => 1, 'oncelik' => 0, 'gorev_tipi' => 'izlem'];
        $iso = (object) ['izolasyon' => 1, 'mahalle_id' => 1, 'bolge_id' => 1, 'oncelik' => 0, 'gorev_tipi' => 'izlem'];

        $normalScore = SmartPlanningHelper::scoreAssignment($ekip, $normal, 20.0, $this->baseCfg());
        $isoScore = SmartPlanningHelper::scoreAssignment($ekip, $iso, 20.0, $this->baseCfg());

        self::assertLessThan($normalScore, $isoScore);
    }

    public function testMixingIsolationAddsPenalty(): void
    {
        $ekip = [
            'personel' => 2,
            'hastalar' => [(object) ['izolasyon' => 1]],
            'son_mahalle' => -1,
            'son_bolge' => -1,
            'unvans' => [],
        ];
        $cfg = $this->baseCfg();
        $emptyEkip = ['personel' => 2, 'hastalar' => [], 'son_mahalle' => -1, 'son_bolge' => -1, 'unvans' => []];
        $hasta = (object) ['izolasyon' => 0, 'mahalle_id' => 1, 'bolge_id' => 1, 'oncelik' => 0, 'gorev_tipi' => 'izlem'];

        $withIso = SmartPlanningHelper::scoreAssignment($ekip, $hasta, 15.0, $cfg);
        $withoutIso = SmartPlanningHelper::scoreAssignment($emptyEkip, $hasta, 15.0, $cfg);

        self::assertGreaterThan($withoutIso, $withIso);
    }

    public function testCompetenceMatchBonusForPansuman(): void
    {
        $cfg = $this->baseCfg();
        $hasta = (object) [
            'izolasyon' => 0,
            'mahalle_id' => 5,
            'bolge_id' => 2,
            'oncelik' => 0,
            'gorev_tipi' => SmartPlanningHelper::GOREV_PANSUMAN,
        ];
        $matched = [
            'personel' => 2,
            'hastalar' => [],
            'son_mahalle' => -1,
            'son_bolge' => -1,
            'unvans' => ['hemsire'],
        ];
        $unmatched = [
            'personel' => 2,
            'hastalar' => [],
            'son_mahalle' => -1,
            'son_bolge' => -1,
            'unvans' => ['sofor'],
        ];

        self::assertLessThan(
            SmartPlanningHelper::scoreAssignment($unmatched, $hasta, 10.0, $cfg),
            SmartPlanningHelper::scoreAssignment($matched, $hasta, 10.0, $cfg)
        );
    }

    public function testVehicleCapacityBlocksAssignment(): void
    {
        $cfg = $this->baseCfg();
        $ekip = [
            'personel' => 2,
            'arac_kapasite' => 2,
            'hastalar' => [(object) [], (object) []],
            'unvans' => [],
        ];
        $hasta = (object) ['izolasyon' => 0];

        self::assertFalse(SmartPlanningHelper::canAssign($ekip, $hasta, $cfg));
    }

    public function testEffectiveCapacityUsesVehicleLimit(): void
    {
        $cfg = $this->baseCfg();
        $ekip = ['personel' => 5, 'arac_kapasite' => 3];

        self::assertSame(3, SmartPlanningHelper::effectiveCapacity($ekip, $cfg));
    }
}
