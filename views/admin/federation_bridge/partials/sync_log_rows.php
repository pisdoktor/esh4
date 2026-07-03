<?php
declare(strict_types=1);

/** @var list<object> $syncLogs */
$syncLogs = $syncLogs ?? [];

if (empty($syncLogs)): ?>
    <tr><td colspan="5" class="text-center text-muted py-3 small">Henüz senkron kaydı yok.</td></tr>
<?php else:
    foreach ($syncLogs as $row):
        $stats = [];
        $rawStats = (string) ($row->stats_json ?? '');
        if ($rawStats !== '') {
            $decoded = json_decode($rawStats, true);
            if (is_array($decoded)) {
                $stats = $decoded;
            }
        }
        $summary = [];
        foreach (['regions_upserted', 'kurumlar_updated', 'region_count', 'kurum_count'] as $k) {
            if (isset($stats[$k]) && (int) $stats[$k] > 0) {
                $summary[] = $k . ': ' . (int) $stats[$k];
            }
        }
        $status = (string) ($row->status ?? '');
        $badge = match ($status) {
            'success' => 'success',
            'partial' => 'warning',
            default => 'danger',
        };
        ?>
    <tr>
        <td class="small text-nowrap"><?= htmlspecialchars((string) ($row->created_at ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="small font-monospace"><?= htmlspecialchars((string) ($row->direction ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><span class="badge text-bg-<?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span></td>
        <td class="small"><?= htmlspecialchars((string) ($row->file_name ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="small text-muted"><?= htmlspecialchars($summary !== [] ? implode(', ', $summary) : (string) ($row->error_message ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php endforeach;
endif;
