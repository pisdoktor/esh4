<?php
/** @var array $tables */
/** @var string $token */
/** @var list<string> $groupFilterLabels */
$groupOptions = $groupFilterLabels ?? [];
$mysqldumpOk = !empty($tools['mysqldump']);
?>
<div class="card shadow-sm border-0" id="tablolar">
    <div class="card-header bg-white py-3 border-bottom">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <h6 class="mb-0 fw-bold">Tablo özeti</h6>
                <p class="small text-muted mb-0">Boyuta göre sıralı · <?= count($tables) ?> tablo</p>
            </div>
            <div class="col-md-4">
                <input type="search" id="db-maint-table-search" class="form-control form-control-sm" placeholder="Tablo ara…" autocomplete="off">
            </div>
            <div class="col-md-4">
                <select id="db-maint-table-group-filter" class="form-select form-select-sm">
                    <option value="">Tüm gruplar</option>
                    <?php foreach ($groupOptions as $g): ?>
                        <option value="<?= htmlspecialchars($g, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($g, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 520px;">
            <table class="table table-sm table-hover mb-0 small" id="db-maint-tables-table">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="ps-3">Tablo</th>
                        <th>Grup</th>
                        <th>Motor</th>
                        <th class="text-end">Tahm. satır</th>
                        <th class="text-end">MB</th>
                        <th class="text-end pe-3">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $t): ?>
                        <?php
                        $name = (string) ($t['t'] ?? '');
                        $mb = (float) ($t['mb'] ?? 0);
                        $rows = (int) ($t['est_rows'] ?? 0);
                        $large = $mb >= 100 || $rows >= 1000000;
                        $groupLabel = (string) ($t['group_label'] ?? '');
                        ?>
                        <tr class="db-maint-table-row<?= $large ? ' table-warning' : '' ?>"
                            data-table="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                            data-group="<?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <td class="ps-3"><code><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars((string) ($t['eng'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end"><?= number_format($rows, 0, ',', '.') ?></td>
                            <td class="text-end"><?= htmlspecialchars((string) ($t['mb'] ?? '0'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end pe-3 text-nowrap">
                                <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'createTableBackup'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                    <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="table" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if ($mysqldumpOk): ?>
                                        <input type="hidden" name="use_mysqldump" value="1">
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-outline-secondary btn-sm py-0 px-1" title="Yedekle"><i class="fa-solid fa-download"></i></button>
                                </form>
                                <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'checkTable'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                    <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="table" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-outline-primary btn-sm py-0 px-1" title="CHECK"><i class="fa-solid fa-stethoscope"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
