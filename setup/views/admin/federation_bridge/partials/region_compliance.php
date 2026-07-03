<?php
declare(strict_types=1);

$regionComplianceRows = is_array($regionComplianceRows ?? null) ? $regionComplianceRows : [];
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h2 class="h6 mb-0">Bölge uyum raporu</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Bölge</th>
                    <th>Kurum</th>
                    <th>Aktif hasta</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($regionComplianceRows === []): ?>
                <tr><td colspan="3" class="text-muted">Rapor verisi yok.</td></tr>
            <?php else: ?>
                <?php foreach ($regionComplianceRows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($row['bolge'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) ($row['kurum_count'] ?? 0) ?></td>
                        <td><?= (int) ($row['active_patients'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
