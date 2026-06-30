<?php
/**
 * e-Rapor havuzu tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $reports
 */
/**
 * e-Rapor havuzu tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $reports
 */
$eraporNedenOzet = static function (?string $neden, int $maxLen = 50): array {
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

if (empty($reports)): ?>
    <tr>
        <td colspan="8" class="text-center text-muted py-4">Kayıt bulunamadı.</td>
    </tr>
<?php else: ?>
    <?php foreach ($reports as $row): ?>
        <?php
        $bransEtiket = '';
        if (!empty($row->bransadi)) {
            $bransEtiket = (string) $row->bransadi;
        } elseif ($row->brans !== null && $row->brans !== '') {
            $bransEtiket = (string) $row->brans;
        } else {
            $bransEtiket = '—';
        }
        $nedenOzet = $eraporNedenOzet((string) ($row->neden ?? ''));
        ?>
        <tr>
            <td><?= \App\Helpers\ValidationHelper::formatTc((string) $row->hastatckimlik) ?></td>
                        <td>
                <strong><?= htmlspecialchars((string) $row->isim) ?> <?= htmlspecialchars((string) $row->soyisim) ?></strong>
                <?php
                $tcHavuzAdet = (int) ($row->tc_havuz_adet ?? 0);
                if ($tcHavuzAdet > 1):
                ?>
                    <span class="badge bg-info text-dark ms-1 align-middle esh-erapor-tc-adet-badge"
                          title="Bu TC ile e-Rapor havuzunda <?= $tcHavuzAdet ?> kayıt var"><?= $tcHavuzAdet ?>×</span>
                <?php endif; ?>
            </td>
            <td><?= $row->basvurutarihi ? date('d-m-Y', strtotime((string) $row->basvurutarihi)) : '—' ?></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($bransEtiket) ?></span></td>
            <td>
                <?php if (!empty($row->kayitlimi)): ?>
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sistemde Kayıtlı</span>
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
            <?php
            $__erActs = [
                [
                    'href' => esh_url('Erapor', 'edit', array (
  'id' => '',
)) . (int) ($row->id ?? 0),
                    'title' => 'Düzenle',
                    'icon' => 'fa-solid fa-pen',
                    'variant' => 'primary',
                ],
            ];
            if (!empty($_SESSION['isadmin']) && $_SESSION['isadmin'] == true) {
                $__erActs[] = [
                    'href' => esh_url('Erapor', 'delete', array (
  'id' => '',
)) . (int) ($row->id ?? 0),
                    'title' => 'Sil',
                    'icon' => 'fa-solid fa-trash',
                    'variant' => 'danger',
                    'confirm' => 'Bu e-rapor havuz kaydını kalıcı olarak silmek istediğinize emin misiniz?',
                ];
            }
            echo \App\Helpers\FormHelper::listActionsCell($__erActs, ['smallTd' => false]);
            unset($__erActs);
            ?>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
