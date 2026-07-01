<?php
/**
 * İTAKİ / Harizmi değerlendirme geçmişi tablosu.
 *
 * @var object $hasta
 * @var array $fallRiskAssessments
 * @var class-string $fallRiskHelperClass
 * @var string $fallRiskDeleteAction
 * @var bool $pasifDosyaKapali
 */
use App\Helpers\DateHelper;

$hastaId = (int) ($hasta->id ?? 0);
$rows = isset($fallRiskAssessments) && is_array($fallRiskAssessments) ? $fallRiskAssessments : [];
$emptyLabel = str_contains($fallRiskDeleteAction, 'Itaki') || $fallRiskDeleteAction === 'deleteItaki'
    ? 'İTAKİ'
    : 'Harizmi';
?>
<?php if ($rows === []): ?>
    <div class="p-4 text-center text-muted">
        <i class="fa-solid fa-clipboard-list fa-2x mb-2 opacity-50"></i>
        <p class="mb-0 small">Henüz <?= htmlspecialchars($emptyLabel, ENT_QUOTES, 'UTF-8') ?> değerlendirmesi kaydedilmemiş.</p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tarih</th>
                    <th>Gerekçe</th>
                    <th class="text-center">Toplam</th>
                    <th>Risk</th>
                    <th>Seçilen faktörler</th>
                    <th>Kaydeden</th>
                    <th class="text-end">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $total = (int) ($row->toplam_skor ?? 0);
                    $riskMeta = $fallRiskHelperClass::resolveRisk($total);
                    $tarihTr = DateHelper::toTrOrEmpty((string) ($row->degerlendirme_tarihi ?? ''));
                    $selections = $fallRiskHelperClass::decodeSelections((string) ($row->secimler_json ?? ''));
                    $selectionLabels = $fallRiskHelperClass::selectionLabels($selections);
                    $gerekce = $fallRiskHelperClass::evaluationReasonLabel((int) ($row->degerlendirme_gerekcesi ?? 0));
                    ?>
                    <tr>
                        <td class="text-nowrap fw-semibold"><?= htmlspecialchars($tarihTr, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small"><?= htmlspecialchars($gerekce, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-center fw-bold"><?= $total ?></td>
                        <td>
                            <span class="badge <?= htmlspecialchars((string) $riskMeta['badgeClass'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) ($row->risk_duzeyi ?? $riskMeta['label']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="small text-muted" style="max-width: 280px;">
                            <?= $selectionLabels !== []
                                ? htmlspecialchars(implode('; ', $selectionLabels), ENT_QUOTES, 'UTF-8')
                                : '—' ?>
                        </td>
                        <td class="small text-muted">
                            <?= htmlspecialchars((string) ($row->kaydeden_adi ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td class="text-end">
                            <?php if (empty($pasifDosyaKapali)): ?>
                                <form action="<?= htmlspecialchars(esh_url('Patient', $fallRiskDeleteAction), ENT_QUOTES, 'UTF-8') ?>" method="post" class="d-inline" onsubmit="return confirm('Bu değerlendirmeyi silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="id" value="<?= $hastaId ?>">
                                    <input type="hidden" name="assessment_id" value="<?= (int) ($row->id ?? 0) ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Sil">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (!empty($row->notlar)): ?>
                        <tr class="table-light">
                            <td colspan="7" class="small text-muted py-1 ps-3">
                                <i class="fa-solid fa-note-sticky me-1"></i><?= htmlspecialchars((string) $row->notlar, ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
