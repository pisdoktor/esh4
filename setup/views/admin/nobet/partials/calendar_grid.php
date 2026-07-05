    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white fw-bold">Aylık Nöbet Takvimi (<?= sprintf('%02d', (int) $ay) ?>/<?= (int) $yil ?>)</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-xl-3">
                    <div class="border rounded p-2 bg-light h-100">
                        <div class="fw-semibold small text-muted mb-2">Personel Havuzu (Sürükle bırak)</div>
                        <div class="vstack gap-2">
                            <?php if (!empty($nobetHavuzBos)): ?>
                                <div class="small text-muted border rounded p-2 bg-white">
                                    Havuzda personel yok. Sol üstteki uyarıyı ve
                                    <a href="<?= htmlspecialchars(esh_url('Settings', 'index', ['tab' => 'nobet']), ENT_QUOTES, 'UTF-8') ?>">nöbet ayarlarını</a>
                                    kontrol edin.
                                </div>
                            <?php endif; ?>
                            <?php foreach ($personeller as $p): ?>
                                <?php
                                    $pid = \App\Helpers\IdHelper::normalizeRequestId($p->id ?? null) ?? '';
                                    $count = (int) ($personelAylikNobetSayilari[$pid] ?? 0);
                                    $badgeClass = ((string) ($p->unvan ?? '') === 'hemsire') ? 'text-bg-info' : 'text-bg-success';
                                ?>
                                <div class="external-event border rounded p-2 bg-white shadow-sm"
                                     draggable="true"
                                     data-type="person"
                                     data-pid="<?= htmlspecialchars($pid, ENT_QUOTES, 'UTF-8') ?>"
                                     data-name="<?= htmlspecialchars((string) ($p->name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                     data-unvan="<?= htmlspecialchars((string) ($p->unvan ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small fw-semibold"><?= htmlspecialchars((string) ($p->name ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars((string) ($p->unvan ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="small text-muted mt-1">Bu ay nöbet: <strong class="person-count" data-pid="<?= htmlspecialchars($pid, ENT_QUOTES, 'UTF-8') ?>"><?= $count ?></strong></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-xl-9">
                    <?php
                        $gunSayisi = cal_days_in_month(CAL_GREGORIAN, (int) $ay, (int) $yil);
                        $firstWeekday = (int) date('N', strtotime(sprintf('%04d-%02d-01', (int) $yil, (int) $ay))); // 1..7
                        $headers = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
                    ?>
                    <div class="table-responsive">
                        <table class="table table-bordered nobet-calendar align-top mb-0">
                            <thead class="table-light">
                                <tr>
                                    <?php foreach ($headers as $h): ?>
                                        <th class="text-center small"><?= $h ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $day = 1;
                                $started = false;
                                while ($day <= $gunSayisi):
                            ?>
                                <tr>
                                    <?php for ($col = 1; $col <= 7; $col++): ?>
                                        <?php
                                            if (!$started && $col === $firstWeekday) {
                                                $started = true;
                                            }
                                        ?>
                                        <?php if (!$started || $day > $gunSayisi): ?>
                                            <td class="bg-light-subtle"></td>
                                        <?php else: ?>
                                            <?php
                                                $dt = sprintf('%04d-%02d-%02d', (int) $yil, (int) $ay, $day);
                                                $isWeekend = ($col >= 6);
                                                $isHoliday = in_array($dt, $tatilGunleri ?? [], true);
                                            ?>
                                            <td class="nobet-day-cell <?= ($isWeekend || $isHoliday) ? 'table-warning' : '' ?>" data-date="<?= $dt ?>">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <strong class="small"><?= $day ?></strong>
                                                    <small class="text-muted"><?= \App\Helpers\DateHelper::toTr($dt) ?></small>
                                                </div>
                                                <div class="nobet-slot d-flex flex-column gap-1" data-date="<?= $dt ?>">
                                                    <?php foreach (($takvim[$dt] ?? []) as $n): ?>
                                                        <?php
                                                            $unvan = (string) ($n->unvan ?? '');
                                                            $cls = $unvan === 'hemsire' ? 'bg-info-subtle border-info' : 'bg-success-subtle border-success';
                                                        ?>
                                                        <div class="nobet-item border rounded px-2 py-1 small <?= $cls ?>"
                                                             draggable="true"
                                                             data-type="nobet"
                                                             data-id="<?= (int) ($n->id ?? 0) ?>"
                                                             data-pid="<?= htmlspecialchars((string) ($n->personel_id ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                            <div class="d-flex justify-content-between align-items-center gap-1">
                                                                <span class="text-truncate"><?= htmlspecialchars((string) ($n->name ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                                <button type="button" class="btn btn-link p-0 text-danger nobet-delete" data-id="<?= (int) ($n->id ?? 0) ?>" title="Sil">
                                                                    <i class="fa-solid fa-xmark"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <?php $day++; ?>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
