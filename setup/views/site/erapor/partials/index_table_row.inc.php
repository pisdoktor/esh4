<?php
use App\Helpers\AuthHelper;
/**
 * Tek e-Rapor tablo satırı render yardımcısı.
 *
 * @var callable $renderEraporRow (object $row, array $opts = []): void
 */
$eraporNedenOzet = static function (?string $neden, int $maxLen = 20): array {
    $plain = trim(preg_replace('/\s+/u', ' ', str_replace(["\r\n", "\r", "\n"], ' ', (string) $neden)));
    if ($plain === '') {
        return ['text' => '', 'title' => '', 'empty' => true];
    }
    $len = mb_strlen($plain, 'UTF-8');
    if ($len <= $maxLen) {
        return ['text' => $plain, 'title' => '', 'empty' => false];
    }

    return [
        'text' => mb_substr($plain, 0, $maxLen, 'UTF-8') . '…',
        'title' => $plain,
        'empty' => false,
    ];
};

$renderEraporRow = static function (
    object $row,
    array $opts = []
) use ($eraporNedenOzet): void {
    $isChild = !empty($opts['isChild']);
    $isChildVisible = !empty($opts['isChildVisible']);
    $tcGroup = (string) ($opts['tcGroup'] ?? '');
    $isExpandable = !empty($opts['isExpandable']);
    $tcHavuzAdet = (int) ($opts['tcHavuzAdet'] ?? 1);
    $childCountOnPage = (int) ($opts['childCountOnPage'] ?? 0);

    $bransEtiket = '';
    if (!empty($row->bransadi)) {
        $bransEtiket = (string) $row->bransadi;
    } elseif ($row->brans !== null && $row->brans !== '') {
        $bransEtiket = (string) $row->brans;
    } else {
        $bransEtiket = '—';
    }
    $nedenOzet = $eraporNedenOzet((string) ($row->neden ?? ''));

    $__erActs = [
        [
            'href' => esh_url('Erapor', 'edit', ['id' => (string) ($row->id ?? '')]),
            'title' => 'Düzenle',
            'icon' => 'fa-solid fa-pen',
            'variant' => 'primary',
        ],
    ];
    if (AuthHelper::sessionIsAdmin()) {
        $__erActs[] = [
            'action' => esh_url('Erapor', 'delete'),
            'hidden' => ['id' => (string) ($row->id ?? '')],
            'title' => 'Sil',
            'icon' => 'fa-solid fa-trash',
            'variant' => 'danger',
            'confirm' => 'Bu e-rapor havuz kaydını kalıcı olarak silmek istediğinize emin misiniz?',
        ];
    }
    $actionsDropdown = \App\Helpers\FormHelper::listActionsDropdown($__erActs, [
        'btnClass' => 'btn btn-sm btn-light border esh-list-actions-dropdown-toggle',
    ]);
    unset($__erActs);

    $trClasses = [];
    if ($isChild) {
        $trClasses[] = 'esh-erapor-tc-child';
        if (!$isChildVisible) {
            $trClasses[] = 'd-none';
        }
    }
    if ($isExpandable) {
        $trClasses[] = 'esh-erapor-tc-parent';
    }
    $trAttr = '';
    if ($tcGroup !== '') {
        $trAttr .= ' data-esh-tc-group="' . htmlspecialchars($tcGroup, ENT_QUOTES, 'UTF-8') . '"';
    }
    if ($isChild) {
        $trAttr .= ' data-esh-tc-child="1"';
    }
    if ($isExpandable) {
        $trAttr .= ' data-esh-tc-primary-id="' . htmlspecialchars((string) ($row->id ?? ''), ENT_QUOTES, 'UTF-8') . '"';
    }
    $trAttr .= ' data-esh-row-id="' . htmlspecialchars((string) ($row->id ?? ''), ENT_QUOTES, 'UTF-8') . '"';
    ?>
    <tr<?= $trClasses !== [] ? ' class="' . htmlspecialchars(implode(' ', $trClasses), ENT_QUOTES, 'UTF-8') . '"' : '' ?><?= $trAttr ?>>
        <td>
            <?php if ($isChild): ?>
                <span class="esh-erapor-tc-child-marker text-muted" aria-hidden="true">↳</span>
            <?php else: ?>
                <?= \App\Helpers\ValidationHelper::formatTc((string) $row->hastatckimlik) ?>
            <?php endif; ?>
        </td>
        <td class="esh-erapor-name-cell">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <div class="min-w-0 flex-grow-1">
            <?php if ($isChild): ?>
                <strong><?= htmlspecialchars((string) $row->isim) ?> <?= htmlspecialchars((string) $row->soyisim) ?></strong>
            <?php elseif ($isExpandable): ?>
                <?php
                $allLoaded = $childCountOnPage >= ($tcHavuzAdet - 1);
                ?>
                <button type="button"
                        class="btn btn-link p-0 border-0 text-decoration-none text-body esh-erapor-tc-toggle text-start align-baseline"
                        data-esh-tc="<?= htmlspecialchars($tcGroup, ENT_QUOTES, 'UTF-8') ?>"
                        data-esh-tc-total="<?= $tcHavuzAdet ?>"
                        data-esh-tc-loaded="<?= $allLoaded ? '1' : '0' ?>"
                        aria-expanded="false"
                        title="Bu TC'ye ait diğer kayıtları göster/gizle">
                    <i class="fa-solid fa-chevron-right esh-erapor-tc-chevron me-1 small text-secondary" aria-hidden="true"></i>
                    <strong><?= htmlspecialchars((string) $row->isim) ?> <?= htmlspecialchars((string) $row->soyisim) ?></strong>
                    <span class="badge bg-info text-dark ms-1 align-middle esh-erapor-tc-adet-badge"
                          title="Bu TC ile e-Rapor havuzunda <?= $tcHavuzAdet ?> kayıt var"><?= $tcHavuzAdet ?>×</span>
                </button>
            <?php else: ?>
                <strong><?= htmlspecialchars((string) $row->isim) ?> <?= htmlspecialchars((string) $row->soyisim) ?></strong>
            <?php endif; ?>
                </div>
                <?php if ($actionsDropdown !== ''): ?>
                    <div class="flex-shrink-0 esh-erapor-row-actions"><?= $actionsDropdown ?></div>
                <?php endif; ?>
            </div>
        </td>
        <td><?= $row->basvurutarihi ? date('d-m-Y', strtotime((string) $row->basvurutarihi)) : '—' ?></td>
        <td><span class="badge bg-secondary"><?= htmlspecialchars($bransEtiket) ?></span></td>
        <td>
            <?php if (!empty($row->kayitlimi)): ?>
                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Kayıtlı</span>
            <?php else: ?>
                <span class="badge bg-light text-dark border">Yeni Veri</span>
            <?php endif; ?>
        </td>
        <td>
            <?php if ((int) ($row->yenilendimi ?? 0) === 1): ?>
                <span class="badge bg-warning text-dark">Evet</span>
            <?php else: ?>
                <span class="badge bg-secondary">Hayır</span>
            <?php endif; ?>
        </td>
        <td class="esh-erapor-neden-cell small text-secondary">
            <?php if ($nedenOzet['empty']): ?>
                <span class="text-muted">—</span>
            <?php else: ?>
                <span<?= $nedenOzet['title'] !== '' ? ' title="' . htmlspecialchars($nedenOzet['title'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>><?= htmlspecialchars($nedenOzet['text'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </td>
    </tr>
    <?php
};
