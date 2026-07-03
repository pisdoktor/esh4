<?php
/**
 * Kullanıcı profili — sağ sütun «iş özeti» (User::getProfileStats).
 *
 * @var array<string, mixed> $profileStats
 */
use App\Helpers\DateHelper;
use App\Helpers\UserProfileStatsHelper;

$ps = isset($profileStats) && is_array($profileStats) ? $profileStats : [];
$statsHubUrl = UserProfileStatsHelper::statsHubUrl();
$fmtDate = static function ($v): string {
    $s = DateHelper::toTrDotOrEmpty($v);

    return $s !== '' ? $s : '—';
};
$ni = static function (array $a, string $k): int {
    return (int) ($a[$k] ?? 0);
};
$pct = $ps['visit_completion_pct'] ?? null;
$pctStr = $pct === null ? '—' : (string) (int) $pct . '%';

/** @param int|string $value */
$row = static function (string $label, $value, ?string $hint = null, ?string $metricKey = null) use ($ni, $ps): void {
    $intVal = is_int($value) ? $value : (int) preg_replace('/\D+/', '', (string) $value);
    $displayNum = is_int($value) ? number_format($value, 0, ',', '.') : htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $canLink = $metricKey !== null && $metricKey !== '' && $intVal > 0;
    if ($canLink) {
        $url = UserProfileStatsHelper::statsDetailUrl($metricKey);
        $display = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="text-decoration-none" title="Kayıtları listele">' . $displayNum . ' <i class="fa-solid fa-arrow-up-right-from-square small opacity-75"></i></a>';
    } else {
        $display = htmlspecialchars($displayNum, ENT_QUOTES, 'UTF-8');
    }
    ?>
    <div class="d-flex justify-content-between align-items-start gap-3 py-2 border-bottom border-light-subtle">
        <div class="min-w-0">
            <div class="small text-body-secondary"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($hint !== null && $hint !== ''): ?>
                <div class="text-muted mt-1" style="font-size: 0.72rem; line-height: 1.35;"><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="fw-bold text-nowrap fs-6 text-end"><?= $display ?></div>
    </div>
    <?php
};

$section = static function (string $title, string $iconClass, bool $first = false): void {
    $mt = $first ? '' : ' mt-4';
    ?>
    <h3 class="h6 fw-bold mb-0 pb-2 border-bottom text-primary<?= $mt ?>">
        <i class="<?= htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8') ?> me-2"></i><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
    </h3>
    <?php
};
?>
            <aside class="esh-profile-stats mt-4 mt-lg-0">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-body-tertiary">
                    <div class="card-body p-3 p-lg-4">
                        <div class="mb-3 d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div>
                                <h2 class="h5 fw-bold mb-1">İş özeti</h2>
                                <p class="small text-muted mb-0">İzlem ve planlarda sizi içeren kayıtlar ile nöbet/ekip gibi personel bilgileri. Sayılara tıklayarak listeyi açabilirsiniz.</p>
                            </div>
                            <a href="<?= htmlspecialchars($statsHubUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary rounded-pill flex-shrink-0">
                                <i class="fa-solid fa-chart-pie me-1"></i>Tüm istatistikler
                            </a>
                        </div>

                        <?php $section('İzlem', 'fa-solid fa-stethoscope', true); ?>
                        <?php $row('Toplam izlem kaydı', $ni($ps, 'visits_total'), 'Yapılan: ' . $ni($ps, 'visits_done') . ' · Henüz yapılmayan: ' . $ni($ps, 'visits_missed'), 'visits_total'); ?>
                        <?php $row('Tamamlanma oranı', $pctStr, 'Yapılan izlem / tüm izlemler', 'visits_done'); ?>
                        <?php $row('Farklı hasta (izlemde yer aldığınız)', $ni($ps, 'visits_distinct_patients'), null, 'visits_distinct_patients'); ?>
                        <?php $row('Araç tanımlı izlem', $ni($ps, 'visits_with_vehicle'), 'İzlemde araç seçilmiş', 'visits_with_vehicle'); ?>
                        <?php $row('Konsültasyon (branş yazılı)', $ni($ps, 'visits_with_kons'), null, 'visits_with_kons'); ?>

                        <?php $section('Planlı izlem (takvim)', 'fa-solid fa-calendar-check'); ?>
                        <?php $row('Toplam plan satırı', $ni($ps, 'plans_total'), 'Açık: ' . $ni($ps, 'plans_open') . ' · Tamamlanan: ' . $ni($ps, 'plans_done'), 'plans_total'); ?>
                        <?php $row('Farklı hasta (planda yer aldığınız)', $ni($ps, 'plans_distinct_patients'), null, 'plans_distinct_patients'); ?>
                        <?php $row('Gecikmiş açık plan', $ni($ps, 'plans_open_overdue'), 'Tarihi geçmiş, hâlâ bekleyen', 'plans_open_overdue'); ?>
                        <?php $row('Önümüzdeki 7 gün (açık plan)', $ni($ps, 'plans_due_next_7_days'), 'Bugünden itibaren bir hafta içinde', 'plans_due_next_7_days'); ?>

                        <?php $section('Zaman çizelgesi', 'fa-solid fa-clock'); ?>
                        <?php $row('Bu ay — izlem', $ni($ps, 'visits_this_month'), 'Son izlem tarihi: ' . $fmtDate($ps['last_visit_action'] ?? null), 'visits_this_month'); ?>
                        <?php $row('Bu ay — plan (planlanan ay)', $ni($ps, 'plans_this_month'), 'Son tamamlanma: ' . $fmtDate($ps['last_plan_action'] ?? null), 'plans_this_month'); ?>
                        <?php $row('Bu yıl — izlem', $ni($ps, 'visits_this_year'), null, 'visits_this_year'); ?>
                        <?php $row('Bu yıl — plan (planlanan yıl)', $ni($ps, 'plans_this_year'), null, 'plans_this_year'); ?>
                        <?php $row('Son 30 gün — izlem', $ni($ps, 'visits_last_30_days'), null, 'visits_last_30_days'); ?>
                        <?php $row('Son 7 gün — izlem', $ni($ps, 'visits_last_7_days'), null, 'visits_last_7_days'); ?>
                        <?php $row('İlk izlem kaydınız', $fmtDate($ps['first_visit_date'] ?? null), null, 'visits_total'); ?>

                        <?php $section('Personel ve dosya', 'fa-solid fa-id-badge'); ?>
                        <?php $row('Onaylı nöbet günü', $ni($ps, 'nobet_total'), 'Bu ay: ' . $ni($ps, 'nobet_this_month'), 'nobet_total'); ?>
                        <?php $row('İzin kaydı (takvim)', $ni($ps, 'izin_total'), null, 'izin_total'); ?>
                        <?php $row('Mazeret / talep kaydı', $ni($ps, 'istek_total'), null, 'istek_total'); ?>
                        <?php $row('Ekip listesinde olduğunuz gün', $ni($ps, 'ekip_assignments'), 'Rota ekiplerinde adınızın geçtiği kayıt', 'ekip_assignments'); ?>
                        <?php $row('Yüklediğiniz yara fotoğrafı', $ni($ps, 'wound_photos_uploaded'), null, 'wound_photos_uploaded'); ?>
                    </div>
                </div>
            </aside>
