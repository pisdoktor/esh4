<?php
declare(strict_types=1);

use App\Helpers\AuditLogHelper;
use App\Helpers\AuthHelper;
use App\Helpers\DateHelper;

/** @var list<object> $items */
$items = $items ?? [];

if ($items === []): ?>
    <tr>
        <td colspan="<?= AuthHelper::sessionIsSuperAdmin() ? 8 : 7 ?>" class="text-center text-muted py-4">
            Kayıt bulunamadı.
        </td>
    </tr>
<?php
    return;
endif;

foreach ($items as $row):
    $actionLabel = AuditLogHelper::actionLabel((string) ($row->action ?? ''));
    $entityLabel = AuditLogHelper::entityTypeLabel((string) ($row->entity_type ?? ''));
    $entityId = (int) ($row->entity_id ?? 0);
    $entityRef = trim((string) ($row->entity_ref ?? ''));
    $userName = trim((string) ($row->user_name ?? ''));
    $userUsername = trim((string) ($row->user_username ?? ''));
    $userDisplay = $userName !== '' ? $userName : ($userUsername !== '' ? $userUsername : '—');
    if ($userUsername !== '' && $userName !== '' && $userUsername !== $userName) {
        $userDisplay .= ' <span class="text-muted">(' . htmlspecialchars($userUsername, ENT_QUOTES, 'UTF-8') . ')</span>';
    }
    $ctxRaw = (string) ($row->context_json ?? '');
    $ctxPreview = '';
    if ($ctxRaw !== '') {
        $decoded = json_decode($ctxRaw, true);
        if (is_array($decoded) && $decoded !== []) {
            $parts = [];
            foreach ($decoded as $k => $v) {
                if (is_scalar($v) || $v === null) {
                    $parts[] = (string) $k . ': ' . (string) $v;
                }
            }
            $ctxPreview = implode(', ', array_slice($parts, 0, 4));
        }
    }
    $uri = trim((string) ($row->request_uri ?? ''));
    $detail = $ctxPreview !== '' ? $ctxPreview : ($uri !== '' ? $uri : '—');
    if (strlen($detail) > 120) {
        $detail = substr($detail, 0, 117) . '…';
    }
?>
    <tr>
        <td class="text-nowrap small"><?= htmlspecialchars(DateHelper::toTrDotDateTimeOrEmpty((string) ($row->created_at ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="small"><?= $userDisplay ?></td>
        <?php if (AuthHelper::sessionIsSuperAdmin()): ?>
        <td class="small"><?= htmlspecialchars((string) ($row->kurum_ad ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
        <?php endif; ?>
        <td class="small">
            <span class="badge text-bg-light border"><?= htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </td>
        <td class="small">
            <?= htmlspecialchars($entityLabel, ENT_QUOTES, 'UTF-8') ?>
            <?php if ($entityId > 0): ?>
                <span class="text-muted">#<?= $entityId ?></span>
            <?php endif; ?>
        </td>
        <td class="small font-monospace"><?= $entityRef !== '' ? htmlspecialchars($entityRef, ENT_QUOTES, 'UTF-8') : '—' ?></td>
        <td class="small text-muted"><?= htmlspecialchars((string) ($row->ip_address ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="small text-muted" title="<?= htmlspecialchars($uri !== '' ? $uri : $ctxRaw, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') ?>
        </td>
    </tr>
<?php endforeach; ?>
