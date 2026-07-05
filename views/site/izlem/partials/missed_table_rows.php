<?php
/**
 * Yapılmayan izlemler tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $visits
 * @var string $tc
 */
use App\Helpers\AuthHelper;

if (!empty($visits)) {
    foreach ($visits as $v):
        $zamanData = \App\Helpers\ZamanDilimiHelper::badgeFor($v->zaman ?? null);
        ?>
        <tr>
            <td><?= !empty($v->izlemtarihi) ? date('d-m-Y', strtotime($v->izlemtarihi)) : '—' ?></td>
            <td><strong><?= htmlspecialchars(trim(($v->isim ?? '') . ' ' . ($v->soyisim ?? ''))) ?></strong></td>
            <td class="font-monospace small"><?= \App\Helpers\ValidationHelper::formatTc((string) ($v->hastatckimlik ?? '')) ?></td>
            <td class="small"><?= htmlspecialchars($v->yapilanlar ?: '—') ?></td>
            <td><span class="badge bg-<?= htmlspecialchars($zamanData['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($zamanData['text']) ?></span></td>
            <td class="small"><?= htmlspecialchars($v->yapanlar ?: '—') ?></td>
            <td class="small font-monospace"><?= !empty($v->aracplaka) ? htmlspecialchars((string) $v->aracplaka, ENT_QUOTES, 'UTF-8') : '—' ?></td>
            <td class="small text-muted">
                <?php if (!empty($v->neden)): ?>
                    <span class="d-block"><strong>Neden:</strong> <?= htmlspecialchars(\App\Helpers\IzlemYapilmamaNedenHelper::labelForStored((string) $v->neden)) ?></span>
                <?php endif; ?>
                <?php if (!empty($v->aciklama)): ?>
                    <span class="d-block"><?= nl2br(htmlspecialchars((string) $v->aciklama)) ?></span>
                <?php endif; ?>
                <?php if (empty($v->neden) && empty($v->aciklama)): ?>—<?php endif; ?>
            </td>
            <?php
            $__missActs = [
                [
                    'href' => esh_url('Visit', 'edit', ['id' => (string) ($v->id ?? '')]),
                    'title' => 'Düzenle',
                    'icon' => 'fa-solid fa-pen',
                    'variant' => 'primary',
                ],
            ];
            if (AuthHelper::sessionIsAdmin()) {
                $__missActs[] = [
                    'action' => esh_url('Visit', 'delete'),
                    'hidden' => [
                        'id' => (string) ($v->id ?? ''),
                        'tc' => (string) ($tc ?? ''),
                        'return' => 'missed',
                    ],
                    'title' => 'Sil',
                    'icon' => 'fa-solid fa-trash',
                    'variant' => 'danger',
                    'confirm' => 'Bu izlem kaydını kalıcı olarak silmek istediğinize emin misiniz?',
                ];
            }
            echo \App\Helpers\FormHelper::listActionsCell($__missActs);
            unset($__missActs);
            ?>
        </tr>
    <?php endforeach;
} else { ?>
    <tr><td colspan="9" class="text-center text-muted py-4">Yapılmayan izlem kaydı yok.</td></tr>
<?php } ?>
