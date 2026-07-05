<?php
/**
 * Braden değerlendirme geçmişi tablosu.
 *
 * @var object $hasta
 * @var array $bradenAssessments
 * @var bool $pasifDosyaKapali
 */
use App\Helpers\BradenScaleHelper;
use App\Helpers\DateHelper;

$hastaId = (string) ($hasta->id ?? '');
$rows = isset($bradenAssessments) && is_array($bradenAssessments) ? $bradenAssessments : [];
$fieldLabels = [];
foreach (BradenScaleHelper::getFieldDefinitions() as $key => $def) {
    $fieldLabels[$key] = (string) ($def['label'] ?? $key);
}
?>
<?php if ($rows === []): ?>
    <div class="p-4 text-center text-muted">
        <i class="fa-solid fa-clipboard-list fa-2x mb-2 opacity-50"></i>
        <p class="mb-0 small">Henüz Braden değerlendirmesi kaydedilmemiş.</p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tarih</th>
                    <?php foreach (BradenScaleHelper::SCORE_KEYS as $fk): ?>
                        <th class="text-center" title="<?= htmlspecialchars($fieldLabels[$fk] ?? $fk, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars(mb_substr($fieldLabels[$fk] ?? $fk, 0, 3, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                        </th>
                    <?php endforeach; ?>
                    <th class="text-center">Toplam</th>
                    <th>Risk</th>
                    <th>Kaydeden</th>
                    <th class="text-end">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $total = (int) ($row->toplam_skor ?? 0);
                    $riskMeta = BradenScaleHelper::resolveRisk($total);
                    $tarihTr = DateHelper::toTrOrEmpty((string) ($row->degerlendirme_tarihi ?? ''));
                    ?>
                    <tr>
                        <td class="text-nowrap fw-semibold"><?= htmlspecialchars($tarihTr, ENT_QUOTES, 'UTF-8') ?></td>
                        <?php foreach (BradenScaleHelper::SCORE_KEYS as $fk): ?>
                            <td class="text-center"><?= (int) ($row->$fk ?? 0) ?></td>
                        <?php endforeach; ?>
                        <td class="text-center fw-bold"><?= $total ?></td>
                        <td>
                            <span class="badge <?= htmlspecialchars((string) $riskMeta['badgeClass'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) ($row->risk_duzeyi ?? $riskMeta['label']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="small text-muted">
                            <?= htmlspecialchars((string) ($row->kaydeden_adi ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td class="text-end">
                            <?php if (empty($pasifDosyaKapali)): ?>
                                <form action="<?= htmlspecialchars(esh_url('Patient', 'deleteBraden'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="d-inline" data-esh-confirm="Bu Braden değerlendirmesini silmek istediğinize emin misiniz?">
                                    <input type="hidden" name="id" value="<?= $hastaId ?>">
                                    <input type="hidden" name="assessment_id" value="<?= htmlspecialchars((string) ($row->id ?? ''), ENT_QUOTES, 'UTF-8') ?>">
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
                            <td colspan="11" class="small text-muted py-1 ps-3">
                                <i class="fa-solid fa-note-sticky me-1"></i><?= htmlspecialchars((string) $row->notlar, ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
