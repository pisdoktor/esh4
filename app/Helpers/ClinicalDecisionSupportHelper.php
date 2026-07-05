<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\BarthelAssessment;
use App\Models\BradenAssessment;
use App\Models\HarizmiAssessment;
use App\Models\ItakiAssessment;
use App\Models\MnaAssessment;

/**
 * Klinik karar desteği — ölçek skorlarına göre uyarılar ve yüksek risk tespiti.
 */
final class ClinicalDecisionSupportHelper
{
    public const SEVERITY_DANGER = 'danger';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_INFO = 'info';

    public static function enabled(): bool
    {
        return OperationalSettings::bool('clinical_decision_support', 'enabled', true);
    }

    public static function overdueDays(): int
    {
        $n = OperationalSettings::int('clinical_decision_support', 'overdue_days', 30);

        return max(7, min(180, $n));
    }

    public static function bradenHighThreshold(): int
    {
        $n = OperationalSettings::int('clinical_decision_support', 'braden_high_threshold', 12);

        return max(6, min(18, $n));
    }

    public static function fallRiskThreshold(): int
    {
        $n = OperationalSettings::int('clinical_decision_support', 'fall_risk_threshold', 10);

        return max(5, min(25, $n));
    }

    public static function mnaRiskThreshold(): int
    {
        $n = OperationalSettings::int('clinical_decision_support', 'mna_risk_threshold', 8);

        return max(5, min(11, $n));
    }

    public static function barthelSevereThreshold(): int
    {
        $n = OperationalSettings::int('clinical_decision_support', 'barthel_severe_threshold', 20);

        return max(0, min(40, $n));
    }

    public static function barthelDependencyThreshold(): int
    {
        $n = OperationalSettings::int('clinical_decision_support', 'barthel_dependency_threshold', 60);

        return max(20, min(90, $n));
    }

    public static function showOnDashboard(): bool
    {
        return self::enabled() && OperationalSettings::bool('clinical_decision_support', 'show_on_dashboard', true);
    }

    public static function showOnPatientDetail(): bool
    {
        return self::enabled() && OperationalSettings::bool('clinical_decision_support', 'show_on_patient_detail', true);
    }

    public static function showOnVisitForm(): bool
    {
        return self::enabled() && OperationalSettings::bool('clinical_decision_support', 'show_on_visit_form', true);
    }

    /**
     * @return array{braden: ?object, itaki: ?object, harizmi: ?object, mna: ?object, barthel: ?object}
     */
    public static function loadAssessmentBundle(int|string $hastaId, object $hasta): array
    {
        $hastaIdNorm = IdHelper::normalizeRequestId($hastaId);
        if ($hastaIdNorm === null) {
            return [
                'braden' => null,
                'itaki' => null,
                'harizmi' => null,
                'mna' => null,
                'barthel' => null,
            ];
        }

        $bundle = [
            'braden' => null,
            'itaki' => null,
            'harizmi' => null,
            'mna' => null,
            'barthel' => null,
        ];

        if (PatientClinicalFlagsHelper::isBradenModuleEnabled($hasta)) {
            $model = new BradenAssessment();
            $model->ensureTable();
            $bundle['braden'] = $model->getLatestByHastaId($hastaIdNorm);
        }
        if (PatientClinicalFlagsHelper::isItakiModuleEnabled($hasta)) {
            $model = new ItakiAssessment();
            $model->ensureTable();
            $bundle['itaki'] = $model->getLatestByHastaId($hastaIdNorm);
        }
        if (PatientClinicalFlagsHelper::isHarizmiModuleEnabled($hasta)) {
            $model = new HarizmiAssessment();
            $model->ensureTable();
            $bundle['harizmi'] = $model->getLatestByHastaId($hastaIdNorm);
        }
        if (PatientClinicalFlagsHelper::isMnaModuleEnabled($hasta)) {
            $model = new MnaAssessment();
            $model->ensureTable();
            $bundle['mna'] = $model->getLatestByHastaId($hastaIdNorm);
        }
        if (PatientClinicalFlagsHelper::isBarthelModuleEnabled($hasta)) {
            $model = new BarthelAssessment();
            $model->ensureTable();
            $bundle['barthel'] = $model->getLatestByHastaId($hastaIdNorm);
        }

        return $bundle;
    }

    public static function daysSinceLastCompletedVisit(?string $lastVisitYmd): ?int
    {
        $lastVisitYmd = trim((string) $lastVisitYmd);
        if ($lastVisitYmd === '' || $lastVisitYmd === '0000-00-00') {
            return null;
        }
        $ts = strtotime($lastVisitYmd);
        if ($ts === false) {
            return null;
        }
        $today = strtotime(date('Y-m-d'));

        return (int) floor(($today - $ts) / 86400);
    }

    /**
     * @param array{braden: ?object, itaki: ?object, harizmi: ?object, mna: ?object, barthel: ?object} $assessments
     */
    public static function isHighRiskFromAssessments(array $assessments): bool
    {
        $bradenThr = self::bradenHighThreshold();
        $fallThr = self::fallRiskThreshold();
        $mnaThr = self::mnaRiskThreshold();
        $barthelDepThr = self::barthelDependencyThreshold();

        if ($assessments['braden'] !== null) {
            $score = (int) ($assessments['braden']->toplam_skor ?? 99);
            if ($score <= $bradenThr) {
                return true;
            }
        }
        if ($assessments['itaki'] !== null) {
            $score = (int) ($assessments['itaki']->toplam_skor ?? 0);
            if ($score >= $fallThr) {
                return true;
            }
        }
        if ($assessments['harizmi'] !== null) {
            $score = (int) ($assessments['harizmi']->toplam_skor ?? 0);
            if ($score >= $fallThr) {
                return true;
            }
        }
        if ($assessments['mna'] !== null) {
            $score = (int) ($assessments['mna']->toplam_skor ?? 99);
            if ($score < $mnaThr) {
                return true;
            }
        }
        if ($assessments['barthel'] !== null) {
            $score = (int) ($assessments['barthel']->toplam_skor ?? 100);
            if ($score <= $barthelDepThr) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{braden: ?object, itaki: ?object, harizmi: ?object, mna: ?object, barthel: ?object} $assessments
     * @return list<array{code:string,severity:string,title:string,message:string,action_url?:string,action_label?:string}>
     */
    public static function evaluateAlerts(object $hasta, array $assessments, ?int $daysSinceVisit): array
    {
        if (!self::enabled()) {
            return [];
        }

        $alerts = [];
        $hastaId = IdHelper::normalizeRequestId($hasta->id ?? null) ?? '';
        $bradenThr = self::bradenHighThreshold();
        $fallThr = self::fallRiskThreshold();
        $mnaThr = self::mnaRiskThreshold();
        $barthelSevereThr = self::barthelSevereThreshold();
        $barthelDepThr = self::barthelDependencyThreshold();
        $overdueDays = self::overdueDays();

        if (PatientClinicalFlagsHelper::isBradenModuleEnabled($hasta)) {
            $braden = $assessments['braden'] ?? null;
            if ($braden === null) {
                $alerts[] = [
                    'code' => 'braden_missing',
                    'severity' => self::SEVERITY_WARNING,
                    'title' => 'Braden değerlendirmesi yok',
                    'message' => 'Aktif bası yarası işaretli hastada bası riski (Braden) ölçeği henüz girilmemiş.',
                    'action_url' => $hastaId !== '' ? esh_url('Patient', 'braden', ['id' => $hastaId]) : null,
                    'action_label' => 'Braden gir',
                ];
            } else {
                $score = (int) ($braden->toplam_skor ?? 0);
                if ($score <= 9) {
                    $alerts[] = [
                        'code' => 'braden_very_high',
                        'severity' => self::SEVERITY_DANGER,
                        'title' => 'Braden — çok yüksek bası riski',
                        'message' => 'Son Braden skoru ' . $score . ' (≤9). Pozisyon değişimi ve yara bakımı protokolünü gözden geçirin.',
                        'action_url' => esh_url('Patient', 'braden', ['id' => $hastaId]),
                        'action_label' => 'Braden geçmişi',
                    ];
                } elseif ($score <= $bradenThr) {
                    $alerts[] = [
                        'code' => 'braden_high',
                        'severity' => self::SEVERITY_WARNING,
                        'title' => 'Braden — yüksek bası riski',
                        'message' => 'Son Braden skoru ' . $score . ' (≤' . $bradenThr . '). Önleyici bakım planını güncelleyin.',
                        'action_url' => esh_url('Patient', 'braden', ['id' => $hastaId]),
                        'action_label' => 'Braden geçmişi',
                    ];
                }
            }
        }

        if (PatientClinicalFlagsHelper::isItakiModuleEnabled($hasta)) {
            $itaki = $assessments['itaki'] ?? null;
            if ($itaki === null) {
                $alerts[] = [
                    'code' => 'itaki_missing',
                    'severity' => self::SEVERITY_INFO,
                    'title' => 'İTAKİ değerlendirmesi yok',
                    'message' => 'Yetişkin hasta için düşme riski (İTAKİ II) ölçeği önerilir.',
                    'action_url' => esh_url('Patient', 'itaki', ['id' => $hastaId]),
                    'action_label' => 'İTAKİ gir',
                ];
            } elseif ((int) ($itaki->toplam_skor ?? 0) >= $fallThr) {
                $alerts[] = [
                    'code' => 'itaki_high',
                    'severity' => self::SEVERITY_WARNING,
                    'title' => 'İTAKİ — yüksek düşme riski',
                    'message' => 'Son İTAKİ skoru ' . (int) $itaki->toplam_skor . ' (≥' . $fallThr . '). Düşme önlemlerini uygulayın.',
                    'action_url' => esh_url('Patient', 'itaki', ['id' => $hastaId]),
                    'action_label' => 'İTAKİ geçmişi',
                ];
            }
        }

        if (PatientClinicalFlagsHelper::isHarizmiModuleEnabled($hasta)) {
            $harizmi = $assessments['harizmi'] ?? null;
            if ($harizmi === null) {
                $alerts[] = [
                    'code' => 'harizmi_missing',
                    'severity' => self::SEVERITY_INFO,
                    'title' => 'Harizmi değerlendirmesi yok',
                    'message' => 'Pediatrik hasta için düşme riski (Harizmi II) ölçeği önerilir.',
                    'action_url' => esh_url('Patient', 'harizmi', ['id' => $hastaId]),
                    'action_label' => 'Harizmi gir',
                ];
            } elseif ((int) ($harizmi->toplam_skor ?? 0) >= $fallThr) {
                $alerts[] = [
                    'code' => 'harizmi_high',
                    'severity' => self::SEVERITY_WARNING,
                    'title' => 'Harizmi — yüksek düşme riski',
                    'message' => 'Son Harizmi skoru ' . (int) $harizmi->toplam_skor . ' (≥' . $fallThr . '). Düşme önlemlerini uygulayın.',
                    'action_url' => esh_url('Patient', 'harizmi', ['id' => $hastaId]),
                    'action_label' => 'Harizmi geçmişi',
                ];
            }
        }

        if (PatientClinicalFlagsHelper::isMnaModuleEnabled($hasta)) {
            $mna = $assessments['mna'] ?? null;
            if ($mna === null) {
                $alerts[] = [
                    'code' => 'mna_missing',
                    'severity' => self::SEVERITY_INFO,
                    'title' => 'MNA değerlendirmesi yok',
                    'message' => 'Beslenme durumu (MNA-SF) taraması önerilir.',
                    'action_url' => esh_url('Patient', 'mna', ['id' => $hastaId]),
                    'action_label' => 'MNA gir',
                ];
            } else {
                $score = (int) ($mna->toplam_skor ?? 99);
                if ($score < 8) {
                    $alerts[] = [
                        'code' => 'mna_malnutrition',
                        'severity' => self::SEVERITY_DANGER,
                        'title' => 'MNA — malnütrisyon',
                        'message' => 'Son MNA skoru ' . $score . ' (<8). Beslenme desteği ve diyetisyen değerlendirmesi düşünün.',
                        'action_url' => esh_url('Patient', 'mna', ['id' => $hastaId]),
                        'action_label' => 'MNA geçmişi',
                    ];
                } elseif ($score < $mnaThr) {
                    $alerts[] = [
                        'code' => 'mna_risk',
                        'severity' => self::SEVERITY_WARNING,
                        'title' => 'MNA — malnütrisyon riski',
                        'message' => 'Son MNA skoru ' . $score . ' (<' . $mnaThr . '). Beslenme takibini sıklaştırın.',
                        'action_url' => esh_url('Patient', 'mna', ['id' => $hastaId]),
                        'action_label' => 'MNA geçmişi',
                    ];
                }
            }
        }

        if (PatientClinicalFlagsHelper::isBarthelModuleEnabled($hasta)) {
            $barthel = $assessments['barthel'] ?? null;
            if ($barthel === null) {
                $alerts[] = [
                    'code' => 'barthel_missing',
                    'severity' => self::SEVERITY_INFO,
                    'title' => 'Barthel değerlendirmesi yok',
                    'message' => 'Fonksiyonel bağımsızlık (Barthel indeksi) değerlendirmesi önerilir.',
                    'action_url' => esh_url('Patient', 'barthel', ['id' => $hastaId]),
                    'action_label' => 'Barthel gir',
                ];
            } else {
                $score = (int) ($barthel->toplam_skor ?? 100);
                if ($score <= $barthelSevereThr) {
                    $alerts[] = [
                        'code' => 'barthel_severe',
                        'severity' => self::SEVERITY_DANGER,
                        'title' => 'Barthel — tam bağımlılık',
                        'message' => 'Son Barthel skoru ' . $score . ' (≤' . $barthelSevereThr . '). Yoğun bakım ve rehabilitasyon planını gözden geçirin.',
                        'action_url' => esh_url('Patient', 'barthel', ['id' => $hastaId]),
                        'action_label' => 'Barthel geçmişi',
                    ];
                } elseif ($score <= $barthelDepThr) {
                    $alerts[] = [
                        'code' => 'barthel_dependency',
                        'severity' => self::SEVERITY_WARNING,
                        'title' => 'Barthel — yüksek bağımlılık',
                        'message' => 'Son Barthel skoru ' . $score . ' (≤' . $barthelDepThr . '). Günlük yaşam desteğini artırın.',
                        'action_url' => esh_url('Patient', 'barthel', ['id' => $hastaId]),
                        'action_label' => 'Barthel geçmişi',
                    ];
                }
            }
        }

        $highRisk = self::isHighRiskFromAssessments($assessments);
        $isOverdue = $daysSinceVisit === null || $daysSinceVisit >= $overdueDays;
        if ($highRisk && $isOverdue) {
            $gunText = $daysSinceVisit === null
                ? 'hiç yapılmış izlem kaydı yok'
                : $daysSinceVisit . ' gündür yapılmış izlem yok';
            $alerts[] = [
                'code' => 'visit_overdue_high_risk',
                'severity' => self::SEVERITY_DANGER,
                'title' => 'Yüksek riskli hasta — izlem gecikmesi',
                'message' => 'Risk skorları yüksek ve ' . $gunText . ' (eşik: ' . $overdueDays . ' gün). Öncelikli saha ziyareti planlayın.',
                'action_url' => esh_url('Visit', 'create', ['tc' => (string) ($hasta->tckimlik ?? '')]),
                'action_label' => 'İzlem kaydı',
            ];
        }

        usort($alerts, static function (array $a, array $b): int {
            $order = [self::SEVERITY_DANGER => 0, self::SEVERITY_WARNING => 1, self::SEVERITY_INFO => 2];
            $sa = $order[$a['severity']] ?? 9;
            $sb = $order[$b['severity']] ?? 9;
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }

            return strcmp((string) $a['code'], (string) $b['code']);
        });

        return $alerts;
    }

    /**
     * @param list<array{code:string,severity:string,title:string,message:string,action_url?:string,action_label?:string}> $alerts
     */
    public static function highestSeverity(array $alerts): ?string
    {
        foreach ($alerts as $alert) {
            if (($alert['severity'] ?? '') === self::SEVERITY_DANGER) {
                return self::SEVERITY_DANGER;
            }
        }
        foreach ($alerts as $alert) {
            if (($alert['severity'] ?? '') === self::SEVERITY_WARNING) {
                return self::SEVERITY_WARNING;
            }
        }

        return $alerts !== [] ? self::SEVERITY_INFO : null;
    }
}
