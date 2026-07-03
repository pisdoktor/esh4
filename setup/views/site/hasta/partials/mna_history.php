<?php
/**
 * MNA değerlendirme geçmişi tablosu.
 *
 * @var object $hasta
 * @var array $mnaAssessments
 * @var bool $pasifDosyaKapali
 */
use App\Helpers\DateHelper;
use App\Helpers\MnaScaleHelper;

$hastaId = (int) ($hasta->id ?? 0);
$rows = isset($mnaAssessments) && is_array($mnaAssessments) ? $mnaAssessments : [];
$fieldLabels = [];
foreach (MnaScaleHelper::getFieldDefinitions() as $key => $def) {
    $fieldLabels[$key] = (string) ($def['label'] ?? $key);
}
?>
<?php if ($rows === []): ?>
    <div class="p-4 text-center text-muted">
        <i class="fa-solid fa-clipboard-list fa-2x mb-2 opacity-50"></i>
        <p class="mb-0 small">Henüz MNA değerlendirmesi kaydedilmemiş.</p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tarih</th>
                    <?php foreach (MnaScaleHelper::SCORE_KEYS as $fk): ?>
                        <th class="text-center" title="<?= htmlspecialchars($fieldLabels[$fk] ?? $fk, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars(mb_substr($fieldLabels[$fk] ?? $fk, 0, 4, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                        </th>
                    <?php endforeach; ?>
                    <th class="text-center">F</th>
                    <th class="text-center">Toplam</th>
                    <th>Durum</th>
                    <th>Kaydeden</th>
                    <th class="text-end">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $total = (int) ($row->toplam_skor ?? 0);
                    $statusMeta = MnaScaleHelper::resolveStatus($total);
                    $tarihTr = DateHelper::toTrOrEmpty((string) ($row->degerlendirme_tarihi ?? ''));
                    $olcumTipi = (string) ($row->bmi_olcum_tipi ?? MnaScaleHelper::OLcum_BMI);
                    ?>
                    <tr>
                        <td class="text-nowrap fw-semibold"><?= htmlspecialchars($tarihTr, ENT_QUOTES, 'UTF-8') ?></td>
                        <?php foreach (MnaScaleHelper::SCORE_KEYS as $fk): ?>
                            <td class="text-center"><?= (int) ($row->$fk ?? 0) ?></td>
                        <?php endforeach; ?>
                        <td class="text-center" title="<?= $olcumTipi === MnaScaleHelper::OLcum_BALDIR ? 'Baldır çevresi' : 'VKİ' ?>">
                            <?= (int) ($row->bmi_skor ?? 0) ?>
                        </td>
                        <td class="text-center fw-bold"><?= $total ?></td>
                        <td>
                            <span class="badge <?= htmlspecialchars((string) $statusMeta['badgeClass'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) ($row->durum_duzeyi ?? $statusMeta['label']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="small text-muted">
                            <?= htmlspecialchars((string) ($row->kaydeden_adi ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td class="text-end">
                            <?php if (empty($pasifDosyaKapali)): ?>
                                <form action="<?= htmlspecialchars(esh_url('Patient', 'deleteMna'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="d-inline" onsubmit="return confirm('Bu MNA değerlendirmesini silmek istediğinize emin misiniz?');">
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
