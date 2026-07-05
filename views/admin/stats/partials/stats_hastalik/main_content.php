<div class="card border-0 shadow-sm">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('<span class="text-white"><i class="fa-solid fa-chart-area me-2"></i>Hastalıklarına göre hasta sayısı</span>', 'main', 'h5', 'card-header bg-primary text-white py-3'); ?>
        <div class="card-body">
            <div class="accordion" id="accordionHastalik">
                <?php
                $i = 0;
                foreach ($categories as $cat):
                    $i++;
                    $collapseId = 'collapse_hast_' . $i;
                    $headingId = 'heading_hast_' . $i;
                    $rows = $cat['hastaliklar'] ?? [];
                    ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="<?= htmlspecialchars($headingId, ENT_QUOTES, 'UTF-8') ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>" aria-expanded="false" aria-controls="<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fa-solid fa-folder-open me-2 text-secondary"></i><?= htmlspecialchars((string) ($cat['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </h2>
                        <div id="<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>" class="accordion-collapse collapse" aria-labelledby="<?= htmlspecialchars($headingId, ENT_QUOTES, 'UTF-8') ?>" data-bs-parent="#accordionHastalik">
                            <div class="accordion-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr class="small text-muted">
                                                <th style="width:15%">ICD kodu</th>
                                                <th style="width:35%">Hastalık</th>
                                                <th style="width:25%">Hasta sayısı</th>
                                                <th style="width:25%">Toplam hastaya oranı</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($rows === []): ?>
                                                <tr><td colspan="4" class="text-center text-muted py-3 small">Bu kategoride tanı kaydı yok.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($rows as $hast):
                                                    $hid = (int) ($hast->id ?? 0);
                                                    $icdKey = \App\Models\Patient::normalizeHastalikIcd((string) ($hast->icd ?? ''));
                                                    $count = (int) ($counts[$icdKey] ?? 0);
                                                    $oran = ($total_aktif > 0) ? round((100 * $count) / $total_aktif, 2) : 0.0;
                                                    $patUrl = esh_url('Stats', 'hastalikPatients', ['id' => $hid]);
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars((string) ($hast->icd ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td>
                                                            <?php if ($count > 0): ?>
                                                                <a href="<?= htmlspecialchars($patUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($hast->hastalikadi ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                                            <?php else: ?>
                                                                <?= htmlspecialchars((string) ($hast->hastalikadi ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($count > 0): ?>
                                                                <strong><a href="<?= htmlspecialchars($patUrl, ENT_QUOTES, 'UTF-8') ?>"><?= $count ?></a></strong>
                                                            <?php else: ?>
                                                                <strong>0</strong>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><span class="badge text-bg-info">% <?= htmlspecialchars((string) $oran, ENT_QUOTES, 'UTF-8') ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>