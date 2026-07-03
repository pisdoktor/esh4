    <?php if ($topTani !== []): ?>
    <div class="card shadow-sm border-0 mb-4">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('<i class="fa-solid fa-list-ol me-2 text-secondary"></i>En sık 15 tanı (aktif hasta)', 'main'); ?>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th class="ps-3" style="width:12%">ICD</th>
                            <th>Tanı</th>
                            <th class="text-end">Hasta</th>
                            <th class="text-end pe-3">Oran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topTani as $h):
                            $hid = (int) ($h->id ?? 0);
                            $sayi = (int) ($h->sayi ?? 0);
                            $oran = $totalAktif > 0 ? round(100 * $sayi / $totalAktif, 2) : 0.0;
                            $patUrl = esh_url('Stats', 'hastalikPatients', ['id' => $hid]);
                            ?>
                            <tr>
                                <td class="ps-3 small"><?= htmlspecialchars((string) ($h->icd ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small">
                                    <?php if ($hid > 0 && $sayi > 0): ?>
                                        <a href="<?= htmlspecialchars($patUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($h->hastalikadi ?: $h->etiket), ENT_QUOTES, 'UTF-8') ?></a>
                                    <?php else: ?>
                                        <?= htmlspecialchars((string) ($h->hastalikadi ?: $h->etiket), ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end small">
                                    <?php if ($hid > 0 && $sayi > 0): ?>
                                        <a href="<?= htmlspecialchars($patUrl, ENT_QUOTES, 'UTF-8') ?>" class="fw-bold"><?= $sayi ?></a>
                                    <?php else: ?>
                                        <?= $sayi ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3"><span class="badge text-bg-info">% <?= htmlspecialchars((string) $oran, ENT_QUOTES, 'UTF-8') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>